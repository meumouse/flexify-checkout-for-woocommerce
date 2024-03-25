<?php
/**
 * Flexify_Checkout_Compat_Sales_Booster.
 *
 * Compatibility with Sales Booster.
 *
 * @package Flexify_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Sales_Booster' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Sales_Booster.
 *
 * @class    Flexify_Checkout_Compat_Sales_Booster.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Sales_Booster {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'iconic_wsb_supported_hooks', array( __CLASS__, 'v2_hook_support' ) );
	}

	/**
	 * Add Fast Checkout Compatibility.
	 *
	 * @param array $hooks Hooks.
	 *
	 * @return array
	 */
	public static function v2_hook_support( $hooks ) {

		foreach ( $hooks as $key => &$hook ) {
			if ( 'woocommerce_after_checkout_form' === $key || $hook['flexify_support'] ) {
				continue;
			}

			$hook['flexify_support'] = true;
		}

		return $hooks;
	}
}
