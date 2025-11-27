<?php

namespace MeuMouse\Flexify_Checkout\API;

use MeuMouse\Flexify_Checkout\Core\Logger;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined('ABSPATH') || exit;

/**
 * Direct Checkout API Handler
 *
 * This class handles the creation of a checkout token via REST API
 * and intercepts the token in the URL to rebuild the WooCommerce cart.
 * It uses a single WordPress option (db) for payload persistence,
 * ensuring cross-browser and cross-session reliability.
 *
 * @since 5.4.0
 * @package MeuMouse.com
 */
class Direct_Checkout {

    use Logger;

    /**
     * Enable development logs.
     *
     * @since 5.4.0
     * @var bool
     */
    public $dev_mode = FLEXIFY_CHECKOUT_DEV_MODE;

    /**
     * Constructor.
     *
     * Registers REST routes and template intercept action.
     *
     * @since 5.4.0
     * @return void
     */
    public function __construct() {
        $this->set_logger_source( 'flexify-direct-checkout', false );

        add_action( 'rest_api_init', array( $this, 'register_route' ) );
        add_action( 'template_redirect', array( $this, 'intercept_checkout_token' ) );
    }


    /**
     * Registers the REST route for creating a direct checkout link.
     *
     * @since 5.4.0
     * @route POST: https://site.com/wp-json/flexify-checkout/v1/direct-checkout
     * @see https://ajuda.meumouse.com/docs/flexify-checkout-for-woocommerce/direct-checkout
     * @return void
     */
    public function register_route() {
        register_rest_route( 'flexify-checkout/v1', '/direct-checkout', array(
            array(
                'methods' => WP_REST_Server::CREATABLE, // POST
                'permission_callback' => '__return_true', // Typically, this should have a capability check
                'callback' => array( $this, 'handle_request' ),
                'args' => $this->get_request_args(),
            )
        ));
    }


    /**
     * Defines expected arguments for the REST API request.
     *
     * @since 5.4.0
     * @return array
     */
    private function get_request_args() {
        return array(
            'items' => array(
                'required'    => true,
                'type'        => 'array',
                'description' => 'List of products with variations.',
                'items'       => array( 'type' => 'object' ),
            ),
            'coupon' => array(
                'required'    => false,
                'type'        => 'string',
                'description' => 'Coupon code to apply.',
            ),
            'form_data' => array(
                'required'    => false,
                'type'        => 'object',
                'description' => 'Pre-filled checkout form data.',
            ),
            'redirect' => array(
                'required'    => false,
                'type'        => 'string',
                'description' => 'Optional URL to redirect after cart rebuild (defaults to checkout page).',
            ),
        );
    }


    /**
     * Handle REST API request, normalize data, and generate a checkout token payload (stored in option).
     *
     * @since 5.4.0
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_request( WP_REST_Request $request ) {
        $items = (array) $request->get_param('items');
        $coupon = sanitize_text_field( $request->get_param('coupon') ?? '' );
        $form_data = $request->get_param('form_data');
        $redirect = esc_url_raw( $request->get_param('redirect') ?? '' );
        $clean_items = $this->normalize_items( $items );

        if ( empty( $clean_items ) ) {
            return new WP_Error(
                'no_valid_items',
                'No valid items were provided.',
                array( 'status' => 400 )
            );
        }

        // 2. Generate token
        $token = wp_generate_password( 32, false );

        // 3. Prepare and store payload in an option (Primary persistence method)
        $payload = array(
            'items'     => $clean_items,
            'coupon'    => $coupon,
            'form_data' => $this->deep_sanitize( $form_data ?? [] ),
            'redirect'  => $redirect,
            'created'   => time(),
            'version'   => FLEXIFY_CHECKOUT_VERSION,
        );

        add_option( "flexify_checkout_direct_token_{$token}", $payload );

        // 4. Generate checkout URL
        $checkout_url = add_query_arg( 'direct_checkout', rawurlencode( $token ), home_url() );

        if ( $this->dev_mode ) {
            error_log( '[FLEXIFY CHECKOUT] Direct checkout token created: ' . $token . ' - URL: ' . $checkout_url );
            error_log( '[FLEXIFY CHECKOUT] Payload from direct checkout: ' . print_r( $payload, true ) );
        }

        // 5. Return response
        return new WP_REST_Response(
            array(
                'success' => true,
                'checkout_url' => $checkout_url,
                'token' => $token,
            ),
            200
        );
    }


    /**
     * Normalizes the product array to ensure valid IDs and quantities.
     *
     * @since 5.4.0
     * @param array $items
     * @return array Cleaned items array.
     */
    private function normalize_items( array $items ): array {
        $clean_items = array();

        foreach ( $items as $it ) {
            $product_id = absint( $it['product_id'] ?? 0 );
            $qty = max( 1, absint( $it['quantity'] ?? 1 ) );
            $variation_id = absint( $it['variation_id'] ?? 0 );
            $variation = isset( $it['variation'] ) && is_array( $it['variation'] ) ? $it['variation'] : array();

            if ( $product_id ) {
                $clean_items[] = array(
                    'product_id' => $product_id,
                    'quantity' => $qty,
                    'variation_id' => $variation_id,
                    'variation' => $variation,
                );
            }
        }

        return $clean_items;
    }


    /**
     * Intercepts direct_checkout token in URL, retrieves payload, rebuilds cart, and redirects.
     * This runs on 'template_redirect'.
     *
     * @since 5.4.0
     * @return void
     */
    public function intercept_checkout_token() {
        if ( ! function_exists('WC') ) {
            return;
        }

        $token = isset( $_GET['direct_checkout'] ) ? sanitize_text_field( $_GET['direct_checkout'] ) : null;

        if ( empty( $token ) ) {
            return;
        }

        // Retrieve payload from option
        $opt = get_option( 'flexify_checkout_direct_token_' . $token );

        if ( $this->dev_mode ) {
            error_log( '[FLEXIFY CHECKOUT] Attempting to retrieve token: ' . $token );

            if ( $opt ) {
                error_log( '[FLEXIFY CHECKOUT] Direct checkout object loaded successfully: ' . print_r( $opt, true ) );
            }
        }

        if ( ! is_array( $opt ) || empty( $opt['items'] ) ) {
            // Token not found or payload is empty, prevent further execution.
            return;
        }

        // Extract data from option
        $session_items = $opt['items'] ?? array();
        $session_coupon = $opt['coupon'] ?? '';
        $session_fields = $opt['form_data'] ?? array();
        $session_redirect = $opt['redirect'] ?? '';

        // Initialize WC session and cart objects if necessary
        $this->initialize_wc_objects();

        // Empty the current cart
        if ( WC()->cart ) {
            WC()->cart->empty_cart();
        }

        // Add products to the cart
        $this->add_products_to_cart( $session_items );

        // Apply coupon
        if ( ! empty( $session_coupon ) ) {
            $this->apply_coupon( $session_coupon );
        }

        // Handle final redirect with local storage injection
        $this->final_redirect( $session_redirect, $session_fields );
    }


    /**
     * Ensures WooCommerce session and cart objects are initialized.
     *
     * @since 5.4.0
     * @return void
     */
    private function initialize_wc_objects() {
        if ( ! WC()->session ) {
            WC()->initialize_session();
        }

        // Ensure session cookie is set
        if ( WC()->session && ! WC()->session->has_session() ) {
            WC()->session->set_customer_session_cookie( true );
        }
        
        // Ensure cart is available
        if ( ! WC()->cart ) {
            WC()->cart = new \WC_Cart();
        }
    }


    /**
     * Adds products to the WooCommerce cart based on the payload.
     *
     * @since 5.4.0
     * @param array $items | Products for add to cart
     * @return void
     */
    private function add_products_to_cart( array $items ) {
        if ( ! WC()->cart ) {
            return;
        }

        foreach ( $items as $it ) {
            $product_id = absint( $it['product_id'] );
            $qty = absint( $it['quantity'] );
            $variation_id = absint( $it['variation_id'] );
            $variation = is_array( $it['variation'] ) ? $it['variation'] : array();

            WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variation );
        }
    }


    /**
     * Applies a coupon code to the cart.
     *
     * @since 5.4.0
     * @param string $coupon_code | Coupon code for apply to the cart
     * @return void
     */
    private function apply_coupon( string $coupon_code ) {
        if ( ! WC()->cart || ! $coupon_code ) {
            return;
        }

        try {
            WC()->cart->apply_coupon( $coupon_code );
        } catch ( \Exception $e ) {
            $this->log( 'Coupon application failed: ' . $e->getMessage() );
        }
    }


    /**
     * Performs the final redirect, injecting form data into Local Storage via an HTML script.
     *
     * @since 5.4.0
     * @param string $redirect_url | Optional custom redirect URL.
     * @param array $form_data | Form data to inject.
     * @return void
     */
    private function final_redirect( string $redirect_url, array $form_data ) {
        $final_url = $redirect_url ?: wc_get_checkout_url();
        $ls_key = apply_filters( 'Flexify_Checkout/API/Direct_Checkout/Form_Data_Key', 'flexify_checkout_form_data' );

        // Use a simple HTML/JS redirect to execute localStorage injection reliably
        $html = '<!doctype html><html><head><title>Redirecting...</title><meta name="robots" content="noindex,nofollow"></head><body><script>';
        $html .= 'try { var d=' . wp_json_encode( $form_data ) . '; if(d){ localStorage.setItem(' . wp_json_encode( $ls_key ) . ', JSON.stringify(d)); } }catch(e){console.error("Flexify Checkout Error:", e);}';
        $html .= 'window.location.replace(' . wp_json_encode( $final_url ) . ');';
        $html .= '</script></body></html>';

        status_header( 200 );
        header( 'Content-Type: text/html; charset=utf-8' );

        echo $html;
        exit;
    }


    /**
     * Deep recursive sanitation for form_data.
     *
     * @since 5.4.0
     * @param mixed $value
     * @return mixed
     */
    private function deep_sanitize( $value ) {
        if ( is_array( $value ) ) {
            $clean = array();

            foreach ( $value as $k => $v ) {
                $clean[ sanitize_key( $k ) ] = $this->deep_sanitize( $v );
            }

            return $clean;
        }

        return sanitize_text_field( $value );
    }
}