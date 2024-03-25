<?php
/**
 * Flexify_Checkout_Compat_Tokoo.
 *
 * Compatibility with Tokoo.
 *
 * @package Flexify_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Tokoo' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Tokoo.
 *
 * @class    Flexify_Checkout_Compat_Tokoo.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Tokoo {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_tokoo' ) );
	}

	/**
	 * Compatibility with Tokoo theme.
	 * https://themeforest.net/item/tokoo-electronics-store-woocommerce-theme-for-affiliates-dropship-and-multivendor-websites/22359036
	 *
	 * @return void
	 */
	public static function compat_tokoo() {

		if ( ! function_exists( 'tokoo_customer_details_open' ) ) {
			return;
		}

		remove_action( 'woocommerce_checkout_before_customer_details', 'tokoo_customer_details_open', 0 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'tokoo_customer_details_close', 90 );
	}
}
