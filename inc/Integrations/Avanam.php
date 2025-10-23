<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use Base;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( defined('AVANAM_VERSION') ) {

	/**
	 * Compatibility with Avanam theme
	 *
	 * @since 5.3.0
	 * @package MeuMouse.com
	 */
	class Avanam {

		/**
		 * Construct function
		 *
		 * @since 5.3.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'remove_scripts' ), 100 );
			add_action( 'wp_print_scripts',   array( $this, 'remove_scripts' ), 100 );

            // remove checkout hooks from template mela core plugin
			add_action( 'wp', array( $this, 'strip_tmcore_hooks_on_checkout' ), 99 );

            // dark mode switcher
            add_filter( 'base_dark_mode_enable', '__return_false', 99 );

            remove_action( 'wp_footer', 'Base\scroll_up', 99 );
		}


		/**
		 * Dequeue styles/scripts
		 *
		 * @since 5.3.0
		 * @return void
		 */
		public function remove_scripts() {
			if ( function_exists('is_flexify_template') && ! is_flexify_template() ) {
				return;
			}

			wp_dequeue_style('tmcore-woocommerce-style');
			wp_deregister_style('tmcore-woocommerce-style');
			wp_dequeue_script('tmcore-woocommerce-main');
			wp_deregister_script('tmcore-woocommerce-main');
			wp_dequeue_script('tmcore-common-archive');
			wp_deregister_script('tmcore-common-archive');
		}


		/**
		 * Remove all TemplateMelaCore_WooCommerce hooks on checkout
		 *
		 * @since 5.3.0
		 * @return void
		 */
		public function strip_tmcore_hooks_on_checkout() {
			$in_checkout = function_exists('is_flexify_template') ? is_flexify_template() : ( function_exists('is_checkout') && is_checkout() );

			if ( ! $in_checkout || ! class_exists('TemplateMelaCore_WooCommerce') ) {
				return;
			}

			$this->remove_all_callbacks_from_class('TemplateMelaCore_WooCommerce');
		}


		/**
		 * Loop through all registered hooks and remove those
		 *
		 * @since 5.3.0
         * @param string $class_name | Class name to strip callbacks from
		 * @return void
		 */
		private function remove_all_callbacks_from_class( $class_name ) {
			global $wp_filter;

			if ( empty( $wp_filter ) || ! is_array( $wp_filter ) ) {
				return;
			}

			foreach ( $wp_filter as $tag => $hook_obj ) {
				if ( ! $hook_obj instanceof \WP_Hook ) {
					continue;
				}

				foreach ( $hook_obj->callbacks as $priority => $callbacks ) {
					if ( empty( $callbacks ) || ! is_array( $callbacks ) ) {
						continue;
					}

					foreach ( $callbacks as $idx => $callback ) {
						$fn = $callback['function'];

						if ( is_array( $fn ) && isset( $fn[0], $fn[1] ) && is_object( $fn[0] ) ) {
							if ( is_a( $fn[0], $class_name ) ) {
								remove_filter( $tag, array( $fn[0], $fn[1] ), $priority );
							}
						}
					}
				}
			}
		}
	}
}