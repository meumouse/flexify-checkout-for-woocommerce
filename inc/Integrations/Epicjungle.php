<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Epicjungle theme
 * 
 * @since 1.8.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Epicjungle {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'remove_actions' ) );
	}


	/**
	 * Disable Epicjungle checkout customisations
	 * 
	 * @since 1.0.0
     * @version 5.0.0
	 * @return void
	 */
	public function remove_actions() {
		if ( ! class_exists('EpicJungle') ) {
			return;
		}

		remove_action( 'woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form' );
		remove_filter( 'woocommerce_checkout_fields', 'epicjungle_change_priority_fields_checkout' );
		remove_filter( 'woocommerce_checkout_fields', 'epicjungle_priority_fields_integrate_brazilian_market', 30, 1 );
	}
}

new Epicjungle();