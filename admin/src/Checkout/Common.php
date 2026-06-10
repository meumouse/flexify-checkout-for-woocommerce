<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Register common checkout actions and filters
 *
 * @since 5.0.0
 * @version 5.5.2
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Common {

    /**
     * Construct function
     *
     * @since 5.0.0
     * @return void
     */
    public function __construct() {
        // disable direct after add to cart
		add_filter( 'option_woocommerce_cart_redirect_after_add', array( $this, 'disable_redirect_after_add_to_cart' ), 10, 1 );

        // add custom message for empty payment methods
        add_filter( 'woocommerce_no_available_payment_methods_message', array( $this, 'empty_payment_methods_message' ) );

        // force WooCommerce is_checkout() to return true on Flexify checkout context
        add_filter( 'woocommerce_is_checkout', array( $this, 'force_is_checkout_on_flexify_context' ) );
    }


    /**
     * Force WooCommerce is_checkout() to return true on the Flexify checkout context.
     *
     * The native check fails for third-party integrations that load the checkout
     * via wc-ajax or render it on a non-default page. We resolve the checkout
     * page id directly from the queried object/request URI to avoid recursion
     * with is_flexify_checkout(), which itself relies on is_checkout().
     *
     * @since 5.5.2
     * @param bool $is_checkout Current value provided by WooCommerce.
     * @return bool
     */
    public function force_is_checkout_on_flexify_context( $is_checkout ) {
        if ( $is_checkout ) {
            return $is_checkout;
        }

        if ( ! function_exists('wc_get_page_id') ) {
            return $is_checkout;
        }

        // Never force is_checkout() on order-received / order-pay endpoints.
        // url_to_postid() resolves those URLs to the checkout page ID (the
        // endpoints live under it), which would otherwise make analytics
        // integrations (e.g. PixelYourSite gtag pipeline) treat the thank-you
        // page as a checkout page and drop purchase value/items/transaction_id.
        if ( function_exists('is_wc_endpoint_url') ) {
            if ( is_wc_endpoint_url('order-received') || is_wc_endpoint_url('order-pay') ) {
                return $is_checkout;
            }
        }

        if ( function_exists('is_order_received_page') && is_order_received_page() ) {
            return $is_checkout;
        }

        $request_uri_check = ! empty( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';

        if ( $request_uri_check !== '' && $this->request_uri_matches_thankyou_endpoints( $request_uri_check ) ) {
            return $is_checkout;
        }

        $wc_ajax = filter_input( INPUT_GET, 'wc-ajax', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if ( 'update_order_review' === $wc_ajax ) {
            return true;
        }

        $checkout_page_id = wc_get_page_id('checkout');

        if ( $checkout_page_id <= 0 ) {
            return $is_checkout;
        }

        $queried_object = function_exists('get_queried_object') ? get_queried_object() : null;

        if ( $queried_object && isset( $queried_object->ID ) && (int) $queried_object->ID === (int) $checkout_page_id ) {
            return true;
        }

        $request_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        if ( $request_uri !== '' ) {
            $page_id = url_to_postid( home_url( $request_uri ) );

            if ( $page_id > 0 && (int) $page_id === (int) $checkout_page_id ) {
                return true;
            }
        }

        return $is_checkout;
    }


    /**
     * Check whether the current request URI matches the configured order-received
     * or order-pay endpoint slugs. Reads the slugs from the WooCommerce options
     * so that merchants who renamed the endpoints (Settings → Advanced → Checkout
     * endpoints) are still detected correctly.
     *
     * @since 5.5.3
     * @param string $request_uri Request URI to inspect.
     * @return bool
     */
    protected function request_uri_matches_thankyou_endpoints( $request_uri ) {
        $received_slug = get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' );
        $pay_slug = get_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' );

        if ( ! is_string( $received_slug ) || $received_slug === '' ) {
            $received_slug = 'order-received';
        }

        if ( ! is_string( $pay_slug ) || $pay_slug === '' ) {
            $pay_slug = 'order-pay';
        }

        $slugs = array(
            trim( $received_slug, '/' ),
            trim( $pay_slug, '/' ),
            'order-received',
            'order-pay',
        );

        /**
         * Filter the endpoint slugs that should be treated as thank-you/order-pay
         * URLs when deciding whether to force is_checkout() to true.
         *
         * @since 5.5.3
         * @param array $slugs List of endpoint slugs (without slashes).
         */
        $slugs = apply_filters( 'Flexify_Checkout/Checkout/Thankyou_Endpoint_Slugs', $slugs );

        if ( ! is_array( $slugs ) ) {
            return false;
        }

        $path = (string) parse_url( $request_uri, PHP_URL_PATH );

        if ( $path === '' ) {
            $path = $request_uri;
        }

        foreach ( $slugs as $slug ) {
            if ( ! is_string( $slug ) || $slug === '' ) {
                continue;
            }

            if ( preg_match( '#/' . preg_quote( $slug, '#' ) . '(/|$)#', $path ) ) {
                return true;
            }
        }

        return false;
    }


    /**
	 * Disable add to cart redirection for checkout
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @param array $value | Current value
	 * @return mixed
	 */
	public function disable_redirect_after_add_to_cart( $value ) {
		$add_to_cart = filter_input( INPUT_GET, 'add-to-cart' );

		if ( empty( $add_to_cart ) || ! did_filter('woocommerce_add_to_cart_product_id') ) {
			return $value;
		}

		if ( ! is_flexify_checkout( true ) ) {
			return $value;
		}

		return false;
	}


    /**
	 * Change message if empty payment forms
	 * 
	 * @since 1.2.5
	 * @version 5.0.0
	 * @param string $message | Default message
	 * @return string
	 */
	public function empty_payment_methods_message( $message ) {
		$message = __( 'Desculpe, parece que não há métodos de pagamento disponíveis para sua localização. Entre em contato conosco se precisar de assistência ou desejar pagar de outra forma.', 'flexify-checkout-for-woocommerce' );
		
		return $message;
	}
}
