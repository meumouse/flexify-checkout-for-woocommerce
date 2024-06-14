<?php
/**
 * Flexify_Checkout_Compat_Shopkeeper.
 *
 * Compatibility with Shopkeeper.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Shopkeeper' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Shopkeeper.
 *
 * @class    Flexify_Checkout_Compat_Shopkeeper.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Shopkeeper {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp', array( __CLASS__, 'compat_shopkeeper' ) );
	}

	/**
	 * Disable shopkeeper customisations.
	 */
	public static function compat_shopkeeper() {
		if ( ! function_exists( 'shopkeeper_setup' ) || ! is_flexify_checkout() ) {
			return;
		}

		remove_action( 'wp_head', 'shopkeeper_custom_styles', 99 );
	}

}
