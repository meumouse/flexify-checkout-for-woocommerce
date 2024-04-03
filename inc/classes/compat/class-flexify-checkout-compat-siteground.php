<?php
/**
 * Flexify_Checkout_Compat_Siteground.
 *
 * Compatibility with Siteground.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Siteground' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Siteground.
 *
 * @class    Flexify_Checkout_Compat_Siteground.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Siteground {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'sgo_css_combine_exclude', array( __CLASS__, 'compat_siteground_exclude' ) );
	}

	/**
	 * Siteground optimizer compatibility.
	 *
	 * @param array $exclude_list Exclude List.
	 *
	 * @return array
	 */
	public static function compat_siteground_exclude( $exclude_list ) {
		$exclude_list[] = 'flexify-checkout-for-woocommerce';

		return $exclude_list;
	}
}
