<?php
/**
 * Compatibility with Checkout Field Editor for WooCommerce.
 *
 * @see https://www.themehigh.com/product/woocommerce-checkout-field-editor-pro/
 * @package Flexify_Checkout
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

/**
 * Flexify_Checkout_Compat_Checkout_Field_Editor_for_WooCommerce.
 *
 * @class    Flexify_Checkout_Compat_Checkout_Field_Editor_for_WooCommerce.
 * @since    2.0.1.0
 * @package  Flexify_Checkout
 */
class Flexify_Checkout_Compat_Checkout_Field_Editor_For_WooCommerce {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'on_init' ) );
	}

	/**
	 * On init.
	 */
	public static function on_init() {
		if ( ! function_exists( 'run_thwcfe' ) ) {
			return;
		}

		add_filter( 'thwcfe_hidden_fields_display_position', array( __CLASS__, 'set_thwcfe_hidden_fields_position' ) );
	}

	/**
	 * Set the position of the thwcfe hidden fields.
	 *
	 * The Checkout Field Editor for WooCommerce uses
	 * hidden fields to control the checkout fields.
	 *
	 * @return string
	 */
	public static function set_thwcfe_hidden_fields_position() {
		return 'flexify_after_stepper';
	}
}
