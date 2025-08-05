<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Register common checkout actions and filters
 *
 * @since 5.0.0
 * @package MeuMouse.com
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