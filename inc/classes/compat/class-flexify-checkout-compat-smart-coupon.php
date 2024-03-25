<?php
/**
 * Flexify_Checkout_Compat_Smart_Coupon.
 *
 * Compatibility with Smart Coupon.
 *
 * @package Flexify_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Smart_Coupon' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Smart_Coupon.
 *
 * @class    Flexify_Checkout_Compat_Smart_Coupon.
 * @version  2.0.0.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Smart_Coupon {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_woo_smart_coupon' ) );
	}

	/**
	 * Compatibility between Flexify checkout and WooCommerce Smart Coupons [https://woocommerce.com/products/smart-coupons/]
	 */
	public static function compat_woo_smart_coupon() {
		if ( ! class_exists( 'WC_SC_Purchase_Credit' ) ) {
			return;
		}

		$woo_smart_coupon_purchase_credit = WC_SC_Purchase_Credit::get_instance();
		add_action( 'woocommerce_after_checkout_billing_form', array( $woo_smart_coupon_purchase_credit, 'gift_certificate_receiver_detail_form' ) );
	}
}
