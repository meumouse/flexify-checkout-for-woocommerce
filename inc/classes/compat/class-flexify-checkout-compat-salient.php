<?php
/**
 * Flexify_Checkout_Compat_Salient.
 *
 * Compatibility with Salient.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Salient' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Salient.
 *
 * @class    Flexify_Checkout_Compat_Salient.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Salient {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp', array( __CLASS__, 'compat_salient' ) );
	}

	/**
	 * Disable Salient checkout customisations.
	 */
	public static function compat_salient() {
		if ( ! function_exists( 'nectar_get_theme_version' ) || ! Flexify_Checkout_Core::is_checkout() ) {
			return;
		}

		remove_action( 'woocommerce_before_quantity_input_field', 'nectar_quantity_markup_mod_before', 10 );
		remove_action( 'woocommerce_after_quantity_input_field', 'nectar_quantity_markup_mod_after', 10 );
	}
}
