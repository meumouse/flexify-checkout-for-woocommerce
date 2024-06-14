<?php
/**
 * Flexify_Checkout_Compat_Divi.
 *
 * Compatibility with Divi.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Divi' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Divi.
 *
 * @class    Flexify_Checkout_Compat_Divi.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Divi {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'template_redirect', array( __CLASS__, 'compat_divi' ) );
	}

	/**
	 * Disable Divi checkout customisations.
	 */
	public static function compat_divi() {
		if ( ! function_exists( 'et_divi_print_stylesheet' ) || ! is_flexify_checkout() ) {
			return;
		}

		remove_action( 'wp_enqueue_scripts', 'et_divi_print_stylesheet', 99999998 );
		remove_action( 'wp_enqueue_scripts', 'et_requeue_child_theme_styles', 99999999 );
		remove_action( 'wp_enqueue_scripts', 'et_divi_enqueue_stylesheet' );
	}
}
