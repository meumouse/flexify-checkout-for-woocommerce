<?php
/**
 * Flexify_Checkout_Compat_North_Theme.
 *
 * Compatibility with North Theme.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_North_Theme' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_North_Theme.
 *
 * @class    Flexify_Checkout_Compat_North_Theme.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_North_Theme {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_north_theme' ) );
	}

	/**
	 * Disable Divi checkout customisations.
	 */
	public static function compat_north_theme() {
		$theme = wp_get_theme();

		if ( 'North' !== $theme->name && 'north-wp' !== $theme->template ) {
			return;
		}

		remove_action(
			'woocommerce_checkout_before_customer_details',
			'thb_checkout_before_customer_details',
			5
		);
	}
}
