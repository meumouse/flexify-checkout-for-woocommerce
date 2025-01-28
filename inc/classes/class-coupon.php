<?php

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Init;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Coupon related functions
 *
 * @since 1.0.0
 * @version 4.0.0
 * @package MeuMouse.com
 */
class Coupon {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @version 3.9.8
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', array( __CLASS__, 'auto_apply_coupon' ) );
		add_action( 'woocommerce_removed_coupon', array( __CLASS__, 'register_removed_coupon' ) );
		add_filter( 'option_woocommerce_cart_redirect_after_add', array( __CLASS__, 'disable_add_to_cart_redirect_for_checkout' ) );

		// Apply coupon via URL param on load page
		add_action( 'template_redirect', array( __CLASS__, 'apply_coupon_via_url' ) );
	}


	/**
	 * Auto apply coupon if enabled in the settings
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function auto_apply_coupon() {
		if ( Init::get_setting('enable_auto_apply_coupon_code') === 'no' || empty( Init::get_setting('coupon_code_for_auto_apply') ) || ! License::is_valid() ) {
			return;
		}

		$coupon = Init::get_setting('coupon_code_for_auto_apply');

		if ( empty( $coupon ) || ! is_checkout() ) {
			return;
		}

		if ( '1' === WC()->session->get( 'flexify_dont_auto_apply_coupon_flag' ) ) {
			return;
		}

		if ( ! WC()->cart->has_discount( $coupon ) ) {
			WC()->cart->apply_coupon( $coupon );
		}
	}


	/**
	 * Register removed coupon in session so we do not apply it automatically
	 *
	 * @since 1.0.0
	 * @param string $removed_coupon
	 * @return void
	 */
	public static function register_removed_coupon( $removed_coupon ) {
		$auto_coupon = self::get_auto_apply_coupon();

		if ( empty( $auto_coupon ) ) {
			return;
		}

		if ( $removed_coupon === $auto_coupon ) {
			WC()->session->set( 'flexify_dont_auto_apply_coupon_flag', '1' );
		}
	}


	/**
	 * Get Coupon to be auto applied
	 *
	 * @since 1.0.0
	 * @return string|false
	 */
	public static function get_auto_apply_coupon() {
		if ( Init::get_setting('enable_auto_apply_coupon_code') === 'no' ) {
			return apply_filters( 'flexify_auto_apply_coupon', false );
		}

		/**
		 * Coupon to be auto-applied
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'flexify_auto_apply_coupon', Init::get_setting('coupon_code_for_auto_apply') );
	}


	/**
	 * Disable Add to cart redirection for checkout.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 * @param array $value
	 * @return mixed
	 */
	public static function disable_add_to_cart_redirect_for_checkout( $value ) {
		$add_to_cart = filter_input( INPUT_GET, 'add-to-cart' );

		if ( empty( $add_to_cart ) || ! did_filter('woocommerce_add_to_cart_product_id') ) {
			return $value;
		}

		if ( ! is_flexify_checkout( true ) ) {
			return $value;
		}

		return false;
	}


	/**
	 * Apply coupon via URL
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @return void
	 */
	public static function apply_coupon_via_url() {
		$coupon = Init::get_setting('coupon_code_for_auto_apply') && License::is_valid() ? Init::get_setting('coupon_code_for_auto_apply') : '';

		if ( empty( $coupon ) || ! is_checkout() ) {
			return;
		}

		if ( WC()->cart->has_discount( $coupon ) ) {
			return;
		}

		if ( Init::get_setting('enable_auto_apply_coupon_code') === 'yes' && License::is_valid() ) {
			WC()->cart->add_discount( sanitize_text_field( $coupon ) );
		}
	}
}

new Coupon();

if ( ! class_exists('MeuMouse\Flexify_Checkout\Coupon\Coupon') ) {
    class_alias( 'MeuMouse\Flexify_Checkout\Coupon', 'MeuMouse\Flexify_Checkout\Coupon\Coupon' );
}