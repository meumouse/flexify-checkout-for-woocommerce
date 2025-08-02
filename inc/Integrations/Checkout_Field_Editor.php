<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( function_exists('run_thwcfe') ) {
	/**
	 * Compatibility with Checkout Field Editor for WooCommerce
	 *
	 * @see https://www.themehigh.com/product/woocommerce-checkout-field-editor-pro/
	 * @since 1.0.0
	 * @version 5.0.0
	 * @package MeuMouse.com
	 */
	class Checkout_Field_Editor {

		/**
		 * Construct function
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'on_init' ) );
		}


		/**
		 * On init
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function on_init() {
			if ( ! function_exists('run_thwcfe') ) {
				return;
			}
			
			add_filter( 'thwcfe_hidden_fields_display_position', array( $this, 'set_fields_position' ) );
		}


		/**
		 * Set the position of the thwcfe hidden fields
		 *
		 * The Checkout Field Editor for WooCommerce uses
		 * hidden fields to control the checkout fields.
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return string
		 */
		public function set_fields_position() {
			return 'flexify_after_stepper';
		}
	}
}