<?php
/**
 * Flexify_Checkout_Compat_Fastcart.
 *
 * Compatibility with Fast Cart by Barn2.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Fastcart' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Fastcart.
 *
 * @class    Flexify_Checkout_Compat_Fastcart.
 * @version  2.3.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Fastcart {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'hooks' ) );
	}

	/**
	 * Hooks.
	 */
	public static function hooks() {
		if ( ! class_exists( 'Barn2\Plugin\WC_Fast_Cart\Plugin' ) ) {
			return;
		}

		remove_action( 'template_redirect', array( 'Flexify_Checkout_Sidebar', 'redirect_template_to_checkout' ), 10 );
	}
}
