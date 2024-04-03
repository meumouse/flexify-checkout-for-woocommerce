<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify_Checkout_Compat_Shoptimizer.
 *
 * Compatibility with Shoptimizer.
 *
 * @package Flexify_Checkout
 */

if ( class_exists( 'Flexify_Checkout_Compat_Shoptimizer' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Shoptimizer.
 *
 * @class Flexify_Checkout_Compat_Shoptimizer
 * @since 1.2.0
 * @version 1.6.2
 * @package MeuMouse.com
 */
class Flexify_Checkout_Compat_Shoptimizer {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp', array( __CLASS__, 'compat_shoptimizer' ) );
	}

	/**
	 * Shoptimizer compatibility.
	 */
	public static function compat_shoptimizer() {
		if ( ! function_exists( 'shoptimizer_get_option' ) || ! Flexify_Checkout_Core::is_checkout() ) {
			return;
		}

		remove_action( 'wp_head', 'ccfw_criticalcss', 5 );
		remove_action( 'woocommerce_before_cart', 'shoptimizer_cart_progress' );
		remove_action( 'woocommerce_before_checkout_form', 'shoptimizer_cart_progress', 5 );
		remove_action( 'woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form' );
		remove_filter( 'woocommerce_cart_item_name', 'shoptimizer_product_thumbnail_in_checkout', 20, 3 );
	}
}
