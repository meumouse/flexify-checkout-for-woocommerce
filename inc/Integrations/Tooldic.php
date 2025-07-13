<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Tooldic theme
 *
 * @since 3.8.8
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Tooldic {

	/**
	 * Construct function
	 * 
	 * @since 3.8.8
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'remove_actions' ), 20 );
	}


	/**
	 * Remove action hooks
	 * 
	 * @since 3.8.8
     * @version 5.0.0
	 * @return void
	 */
	public function remove_actions() {
		if ( ! Helpers::check_active_theme('Tooldic') || ! is_flexify_checkout() ) {
			return;
		}
		
		remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_checkout_coupon_form_custom', 90 );
	}
}

new Tooldic();