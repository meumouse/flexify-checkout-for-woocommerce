<?php
/**
 * Flexify_Checkout_Compat_Stripe_Express_Checkout.
 *
 * Compatibility with Google Pay/Apple Pay express by Stripe.
 *
 * @package Flexify_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'Flexify_Checkout_Compat_Stripe_Express_Checkout' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Stripe_Express_Checkout.
 *
 * @class    Flexify_Checkout_Compat_Stripe_Express_Checkout.
 * @version  2.4.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Stripe_Express_Checkout {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp', array( __CLASS__, 'compat_express_checkout' ) );
	}

	/**
	 * Disable street number fields validation when order is placed from Google Pay/Apple Pay express
	 * checkout button.
	 */
	public static function compat_express_checkout() {
		$wc_ajax = filter_input( INPUT_GET, 'wc-ajax' );
		if ( 'wc_stripe_create_order' !== $wc_ajax ) {
			return;
		}

		add_action( 'woocommerce_checkout_fields', array( __CLASS__, 'make_street_number_fields_options' ) );
	}

	/**
	 * Make street number fields options.
	 *
	 * @param array $fields Fields.
	 *
	 * @return array
	 */
	public static function make_street_number_fields_options( $fields ) {
		if ( isset( $fields['billing']['billing_street_number'] ) ) {
			$fields['billing']['billing_street_number']['required'] = false;
		}

		if ( isset( $fields['shipping']['shipping_street_number'] ) ) {
			$fields['shipping']['shipping_street_number']['required'] = false;
		}

		return $fields;
	}
}
