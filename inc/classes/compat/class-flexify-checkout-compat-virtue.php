<?php
/**
 * Flexify_Checkout_Compat_Virtue.
 *
 * Compatibility with Virtue.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Virtue' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Virtue.
 *
 * @class    Flexify_Checkout_Compat_Virtue.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Virtue {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp', array( __CLASS__, 'compat_virtue' ) );
	}

	/**
	 * Virtue makes use this concept: http://scribu.net/wordpress/theme-wrappers.html
	 * Disable theme wrapper as we don't need theme's header and footer on checkout page.
	 */
	public static function compat_virtue() {
		if ( ! class_exists( 'Kadence_Wrapping' ) || ! Flexify_Checkout_Core::is_checkout() ) {
			return;
		}

		remove_filter( 'template_include', array( 'Kadence_Wrapping', 'wrap' ), 101 );
	}
}
