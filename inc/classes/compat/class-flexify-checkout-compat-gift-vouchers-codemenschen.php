<?php
/**
 * Flexify_Checkout_Compat_Gift_Vouchers_Codemenschen.
 *
 * Add compatibility with Gift Vouchers and Packages by Codemenschen.
 *
 * @package Flexify_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Gift_Vouchers_Codemenschen' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Gift_Vouchers_Codemenschen.
 *
 * @class    Flexify_Checkout_Compat_Gift_Vouchers_Codemenschen.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Gift_Vouchers_Codemenschen {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Add compatibility on init.
	 */
	public static function init() {
		if ( ! class_exists( 'WPGV_Redeem_Voucher' ) ) {
			return;
		}

		global $wpgv_redeem_voucher;

		remove_action( 'woocommerce_after_cart_contents', array( $wpgv_redeem_voucher, 'woocommerce_after_cart_contents' ) );
		add_action( 'woocommerce_checkout_order_review', array( $wpgv_redeem_voucher, 'woocommerce_after_cart_contents' ) );
		add_filter( 'flexify_checkout_allowed_sources', array( __CLASS__, 'allowed_sources' ) );
	}

	/**
	 * Allow script dequeue exception.
	 *
	 * @param array $scripts Scripts.
	 *
	 * @return array
	 */
	public static function allowed_sources( $scripts ) {
		$scripts[] = 'wpgv-woocommerce-script';

		return $scripts;
	}
}
