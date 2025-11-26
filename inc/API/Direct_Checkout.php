<?php

namespace MeuMouse\Flexify_Checkout\API;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle Direct Checkout API endpoint
 *
 * Supports variable products, variation attributes
 * and any custom checkout field (billing_, shipping_, or custom meta).
 *
 * @since 5.4.0
 * @package MeuMouse.com
 */
class Direct_Checkout {

    /**
     * Construct function
     * 
     * @since 5.4.0
     * @return void
     */
    public function __construct() {
        // register REST API route
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

        // preload fields into checkout form
        add_action( 'template_redirect', array( $this, 'preload_checkout_fields' ) );

        // fill WooCommerce values
        add_filter( 'woocommerce_checkout_get_value', array( $this, 'fill_checkout_fields' ), 10, 2 );
    }


    /**
     * Register direct checkout endpoint
     *
     * @since 5.4.0
     * @return void
     */
    public function register_routes() {
        register_rest_route( 'flexify-checkout/v1', '/direct-checkout', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_request' ),
            'permission_callback' => '__return_true',
        ));
    }


    /**
     * Handle direct checkout API request
     *
     * @since 5.4.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|array
     */
    public function handle_request( $request ) {
        $params = $request->get_json_params();

        // Validate products
        if ( empty( $params['products'] ) || ! is_array( $params['products'] ) ) {
            return new \WP_Error(
                'no_products',
                'No products were sent in the request.',
                array( 'status' => 400 )
            );
        }

        // Ensure WC session and cart are loaded inside REST requests
        if ( ! WC()->session ) {
            WC()->initialize_session();
        }

        if ( ! WC()->cart ) {
            wc_load_cart();
        }

        // empty cart before adding new items
        if ( WC()->cart ) {
            WC()->cart->empty_cart();
        }

        // Add products (supports variable)
        foreach ( $params['products'] as $product ) {
            $product_id   = isset( $product['id'] ) ? absint( $product['id'] ) : 0;
            $qty          = isset( $product['qty'] ) ? absint( $product['qty'] ) : 1;
            $variation_id = isset( $product['variation_id'] ) ? absint( $product['variation_id'] ) : 0;
            $attributes   = isset( $product['attributes'] ) && is_array( $product['attributes'] ) ? $product['attributes'] : array();

            if ( ! $product_id || ! $qty ) {
                continue;
            }

            $product = wc_get_product( $product_id );

            if ( ! $product ) {
                return new \WP_Error(
                    'invalid_product',
                    "Product ID {$product_id} does not exist.",
                    array( 'status' => 400 )
                );
            }

            if ( $variation_id ) {
                $variation_obj = wc_get_product( $variation_id );

                if ( ! $variation_obj ) {
                    return new \WP_Error(
                        'invalid_variation',
                        "Variation ID {$variation_id} does not exist.",
                        array( 'status' => 400 )
                    );
                }
            }

            /**
             * Normalize variation attributes
             * WooCommerce expects keys like "attribute_pa_color"
             */
            $variations = array();

            if ( ! empty( $attributes ) ) {
                foreach ( $attributes as $attr_key => $attr_value ) {
                    // sanitize attribute slug
                    $clean_key = wc_attribute_taxonomy_slug( sanitize_title( $attr_key ) );

                    // build correct parameter name
                    if ( strpos( $clean_key, 'pa_' ) === false ) {
                        $clean_key = 'pa_' . $clean_key;
                    }

                    $variations[ 'attribute_' . $clean_key ] = sanitize_text_field( $attr_value );
                }
            }

            // add product / variation to cart
            WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variations );
        }

        // Apply coupon if sent
        if ( ! empty( $params['coupon'] ) ) {
            WC()->cart->apply_coupon( sanitize_text_field( $params['coupon'] ) );
        }

        // Store checkout fields
        // Supports ANY custom field (billing_, shipping_, custom)
        if ( ! empty( $params['fields'] ) ) {
            $sanitized = $this->deep_sanitize( $params['fields'] );

            WC()->session->set( 'flexify_direct_fields', $sanitized );
        }


        // Create secure token for checkout
        $token = wp_generate_password( 32, false );

        WC()->session->set( 'flexify_direct_token', $token );

        // generate checkout URL
        $checkout_url = wc_get_checkout_url() . '?direct_checkout=' . $token;

        return array(
            'checkout_url' => esc_url( $checkout_url ),
        );
    }



    /**
     * Deep sanitize array recursively
     *
     * Supports nested structures and avoids losing
     * fields like billing_phone_1_full, arrays of passengers, etc.
     *
     * @since 5.4.0
     * @param mixed $value
     * @return mixed
     */
    private function deep_sanitize( $value ) {
        if ( is_array( $value ) ) {
            $clean = array();

            foreach ( $value as $key => $val ) {
                $clean[ sanitize_key( $key ) ] = $this->deep_sanitize( $val );
            }

            return $clean;
        }

        return sanitize_text_field( $value );
    }



    /**
     * Preload checkout fields on checkout screen
     *
     * @since 5.4.0
     * @return void
     */
    public function preload_checkout_fields() {
        if ( ! is_checkout() ) {
            return;
        }

        $token = isset( $_GET['direct_checkout'] ) ? sanitize_text_field( $_GET['direct_checkout'] ) : '';

        if ( empty( $token ) ) {
            return;
        }

        $saved_token = WC()->session->get('flexify_direct_token');
        $saved_fields = WC()->session->get('flexify_direct_fields');

        if ( empty( $saved_token ) || $token !== $saved_token ) {
            return;
        }

        if ( ! empty( $saved_fields ) ) {

            /**
             * Important:
             * Flexify Checkout uses $_POST to pre-fill Step fields.
             * This preserves compatibility with:
             * - Tickets step
             * - intl-tel-input
             * - Multi-passenger fields
             * - Custom fields added by modules
             */
            foreach ( $saved_fields as $key => $value ) {
                $_POST[ $key ] = $value;
            }
        }
    }



    /**
     * Fill WooCommerce checkout field values
     *
     * Ensures fields appear already filled before user typing.
     *
     * @since 5.4.0
     * @param mixed  $value | Current field value
     * @param string $input | Field key
     * @return mixed
     */
    public function fill_checkout_fields( $value, $input ) {
        $saved_fields = WC()->session->get('flexify_direct_fields');

        if (
            is_array( $saved_fields ) &&
            isset( $saved_fields[ $input ] )
        ) {
            return $saved_fields[ $input ];
        }

        return $value;
    }
}