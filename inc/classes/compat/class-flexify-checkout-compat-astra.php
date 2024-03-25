<?php
/**
 * Flexify_Checkout_Compat_Astra.
 *
 * Compatibility with Astra.
 *
 * @package Flexify_Checkout
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Flexify_Checkout_Compat_Astra' ) ) {
	return;
}

/**
 * Flexify_Checkout_Compat_Astra.
 *
 * @class Flexify_Checkout_Compat_Astra.
 * @version 1.1.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Compat_Astra {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'compat_astra' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'compat_astra_dequeue_scripts' ), 100 );
	}

	/**
	 * Disable astra checkout customisations.
	 */
	public static function compat_astra() {
		if ( ! class_exists( 'Astra_Woocommerce' ) || ! Flexify_Checkout_Core::is_checkout() ) {
			return;
		}

		$astra = Astra_Woocommerce::get_instance();

		remove_action( 'wp', array( $astra, 'woocommerce_checkout' ) );
		remove_action( 'wp_enqueue_scripts', array( $astra, 'add_styles' ) );
		remove_filter( 'woocommerce_enqueue_styles', array( $astra, 'woo_filter_style' ) );
		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
		remove_filter( 'woocommerce_cart_item_remove_link', array( $astra, 'change_cart_close_icon' ), 10, 2 );
	}

	/**
	 * Dequeue scripts.
	 */
	public static function compat_astra_dequeue_scripts() {
		wp_dequeue_script( 'astra-checkout-persistence-form-data' );
	}
}