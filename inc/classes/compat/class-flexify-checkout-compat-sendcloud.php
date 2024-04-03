<?php
/**
 * Flexify_Checkout_Compat_Sendcloud.
 *
 * Compatibility with Sendcloud.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Sendcloud' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Sendcloud.
 *
 * @class    Flexify_Checkout_Compat_Sendcloud.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Sendcloud {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_sendcloud' ) );
	}

	/**
	 * Add compatibility for Sendcloud plugin.
	 */
	public static function compat_sendcloud() {
		if ( ! function_exists( 'sendcloudshipping_init' ) ) {
			return;
		}

		add_action( 'woocommerce_checkout_order_review', 'sendcloudshipping_add_service_point_to_checkout', 100 );
	}
}
