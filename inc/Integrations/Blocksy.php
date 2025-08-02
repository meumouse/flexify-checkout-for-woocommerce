<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('Blocksy_Manager') ) {
	/**
	 * Compatibility with Blocksy Theme
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @package MeuMouse.com
	 */
	class Blocksy {

		/**
		 * Construct function
		 * 
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'set_blocksy_global_variable' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'remove_scripts' ), 999 );
		}


		/**
		 * Set this global variable to prevent Blocksy from overriding the checkout template
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function set_blocksy_global_variable() {
			if ( ! is_flexify_checkout() || ! Helpers::check_active_theme('Blocksy') ) {
				return;
			}
			
			$GLOBALS['ct_skip_checkout'] = 1;
		}


		/**
		 * Remove scripts from Blocksy theme on checkout
		 * 
		 * @since 3.8.0
		 * @version 5.0.0
		 * @return void
		 */
		public function remove_scripts() {
			if ( ! is_flexify_checkout() || ! class_exists('Blocksy_Manager') ) {
				return;
			}

			global $wp_styles;
			
			$remove_styles = ['ct-main-styles'];

			foreach ( $wp_styles->registered as $handle => $data ) {
				// If the style depends on 'ct-main-styles', it will be added to the removal list
				if ( isset( $data->deps ) && in_array( 'ct-main-styles', $data->deps ) ) {
					$remove_styles[] = $handle;
				}
			}

			// Remove all styles found in the removal list
			foreach ( $remove_styles as $handle ) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}
}