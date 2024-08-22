<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Checkout Field Editor for WooCommerce.
 *
 * @see https://www.themehigh.com/product/woocommerce-checkout-field-editor-pro/
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Checkout_Field_Editor_For_WooCommerce {
	/**
	 * Construct function.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'on_init' ) );
	}

	/**
	 * On init.
	 */
	public static function on_init() {
		if ( ! function_exists('run_thwcfe') ) {
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

new Compat_Checkout_Field_Editor_For_WooCommerce();