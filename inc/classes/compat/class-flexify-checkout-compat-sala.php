<?php
/**
 * Flexify_Checkout_Compat_Sala.
 *
 * Compatibility with Sala theme.
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Sala' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Sala.
 *
 * @class    Flexify_Checkout_Compat_Sala.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Sala {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'after_setup_theme', array( __CLASS__, 'compat_sala' ), 20 );
	}

	/**
	 * Disable Sala checkout customisations.
	 */
	public static function compat_sala() {
		if ( ! class_exists( 'Sala_Woo' ) || ! Flexify_Checkout_Core::is_checkout( true ) ) {
			return;
		}

		add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		remove_action( 'woocommerce_after_order_notes', 'woocommerce_checkout_payment', 20 );
	}
}
