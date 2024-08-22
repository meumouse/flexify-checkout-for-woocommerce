<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Astra theme
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Astra {
	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'compat_astra' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'compat_astra_dequeue_scripts' ), 100 );
	}

	/**
	 * Disable astra checkout customizations.
	 */
	public static function compat_astra() {
		if ( ! class_exists('Astra_Woocommerce') || ! is_flexify_checkout() ) {
			return;
		}
		
		$astra = \Astra_Woocommerce::get_instance();

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
		wp_dequeue_script('astra-checkout-persistence-form-data');
	}
}

new Compat_Astra();