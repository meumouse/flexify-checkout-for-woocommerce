<?php
/**
 * Flexify_Checkout_Compat_Germanized.
 *
 * Compatibility with Germanized.
 *
 * @package Flexify_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Germanized' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Germanized.
 *
 * @class    Flexify_Checkout_Compat_Flatsome.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Germanized {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_germanized' ) );
	}

	/**
	 * Germanized compatibility.
	 */
	public static function compat_germanized() {
		if ( ! class_exists( 'WooCommerce_Germanized' ) ) {
			return;
		}

		add_action( 'woocommerce_review_order_after_payment', array( __CLASS__, 'compat_gzd_order_review_title' ), 100 );
		remove_action( 'woocommerce_review_order_after_cart_contents', 'woocommerce_gzd_template_checkout_back_to_cart' );
	}


	/**
	 * Add title to review order for consistency.
	 */
	public static function compat_gzd_order_review_title() {
		echo '<h4>' . esc_html__( 'Resumo do pedido', 'flexify-checkout-for-woocommerce' ) . '</h4>';
	}
}
