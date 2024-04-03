<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists( 'Flexify_Checkout_Compat_EpicJungle' ) ) {
	return;
}

/**
 * Compatibility with EpicJungle theme
 * 
 * @version 1.8.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Compat_EpicJungle {
	/**
	 * Run
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_epicjungle' ) );
	}

	/**
	 * Disable EpicJungle checkout customisations
	 */
	public static function compat_epicjungle() {
		if ( !class_exists( 'EpicJungle' ) ) {
			return;
		}

		remove_action( 'woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form' );
		remove_filter( 'woocommerce_checkout_fields', 'epicjungle_change_priority_fields_checkout' );
		remove_filter( 'woocommerce_checkout_fields', 'epicjungle_priority_fields_integrate_brazilian_market', 30, 1 );
	}
}