<?php
/**
 * Flexify_Checkout_Compat_WooCommerce_Subscriptions.
 *
 * Compatibility with WooCommerce Subscriptions.
 * [https://woocommerce.com/products/woocommerce-subscriptions/]
 *
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_WooCommerce_Subscriptions' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_WooCommerce_Subscriptions.
 *
 * @class    Flexify_Checkout_Compat_WooCommerce_Subscriptions.
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_WooCommerce_Subscriptions {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Run on init hook.
	 */
	public static function init() {
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return;
		}

		remove_filter( 'wcs_cart_totals_order_total_html', 'wcs_add_cart_first_renewal_payment_date', 10 );
		add_filter( 'wcs_cart_totals_order_total_html', array( __CLASS__, 'add_cart_first_renewal_payment_date' ), 10, 2 );
	}

	/**
	 * Append the first renewal payment date to a string (which is the order total HTML string by default).
	 *
	 * @param string $order_total_html Order total HTML.
	 * @param mixed  $cart             Cart.
	 *
	 * @return string
	 */
	public static function add_cart_first_renewal_payment_date( $order_total_html, $cart ) {
		if ( 0 !== $cart->next_payment_date ) {
			$first_renewal_date = date_i18n( wc_date_format(), wcs_date_to_time( get_date_from_gmt( $cart->next_payment_date ) ) );
			// Translators: placeholder is a date.
			$order_total_html .= '<div class="first-payment-date"><small>' . __( 'Primeira renovação', 'flexify-checkout-for-woocommerce' ) . '<br />' . $first_renewal_date . '</small></div>';
		}

		return $order_total_html;
	}
}
