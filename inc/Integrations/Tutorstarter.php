<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('Tutor_Starter\\Init') || defined('TUTOR_STARTER_VERSION') ) {
	/**
	 * Compatibility with Tutor Starter theme
	 *
	 * @since 3.8.8
	 * @version 5.2.3
	 * @package MeuMouse.com
	 */
	class Tutorstarter {
		
		/**
		 * Construct function
		 *
		 * @since 3.8.8
		 * @version 5.2.3
		 * @return void
		 */
		public function __construct() {
			/**
			 * Try removing as early as possible during page lifecycle,
			 * but also right before checkout renders.
			 */
			add_action( 'init', array( $this, 'maybe_unhook' ), 20 );
			add_action( 'wp', array( $this, 'maybe_unhook' ), 20 );

			// Most reliable: right before WooCommerce renders the checkout form.
			add_action( 'woocommerce_before_checkout_form', array( $this, 'maybe_unhook' ), 1 );
		}


		/**
		 * Check context and remove TutorStarter filters from order button HTML.
		 *
		 * @since 5.2.3
		 * @return void
		 */
		public function maybe_unhook() {
			// Only on Flexify Checkout, if helper exists.
			if ( function_exists('is_flexify_checkout') && ! is_flexify_checkout() ) {
				return;
			}

			// Ensure we are on a checkout page (extra safety).
			if ( function_exists('is_checkout') && ! is_checkout() ) {
				return;
			}

			// Optionally ensure theme is present (constant or class).
			if ( ! defined('TUTOR_STARTER_VERSION') && ! class_exists('Tutor_Starter\\Init') ) {
				return;
			}

			// Remove at the exact attached priority if possible.
			$attached_priority = has_filter( 'woocommerce_order_button_html', 'tutorstarter_order_btn_html' );
			
			if ( false !== $attached_priority ) {
				remove_filter( 'woocommerce_order_button_html', 'tutorstarter_order_btn_html', (int) $attached_priority );
			}

			// Also attempt removal across a broad priority range and anything registered.
			$this->remove_callback_across_all_priorities( 'woocommerce_order_button_html', 'tutorstarter_order_btn_html' );
		}

		/**
		 * Remove a callback from a hook across all priorities (defensive).
		 *
		 * @since 5.2.3
		 * @param string $hook
		 * @param string $callback
		 * @return void
		 */
		private function remove_callback_across_all_priorities( $hook, $callback ) {
			global $wp_filter;

			// Common priority guesses first (fast path).
			foreach ( array( 1, 5, 10, 11, 15, 20, 50, 100 ) as $prio ) {
				remove_filter( $hook, $callback, $prio );
			}

			// Deep sweep: iterate everything registered under this hook.
			if ( isset( $wp_filter[ $hook ] ) && isset( $wp_filter[ $hook ]->callbacks ) && is_array( $wp_filter[ $hook ]->callbacks ) ) {
				foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
					if ( isset( $callbacks ) && is_array( $callbacks ) ) {
						remove_filter( $hook, $callback, (int) $priority );
					}
				}
			}
		}
	}
}