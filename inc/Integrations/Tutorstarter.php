<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

defined('ABSPATH') || exit;

/**
 * Compatibility for Tutor Starter theme: neutralize its checkout button override
 *
 * Why buttons disappear after update_order_review:
 * - WooCommerce re-renders checkout fragments via wc-ajax=update_order_review.
 * - Your early guards (is_checkout/is_flexify_checkout) can be false in AJAX context,
 *   so the theme filter stays attached for that request and the fragment comes back without your fix.
 *
 * Fix:
 * - Unhook also during the AJAX lifecycle (woocommerce_checkout_update_order_review + wc-ajax check).
 * - Skip the is_checkout/is_flexify guards specifically for that AJAX call.
 * - Optionally enforce our own button HTML at very high priority as a last resort.
 *
 * @since 3.8.8
 * @version 5.3.0
 * @package MeuMouse.com
 */
class Tutorstarter {

	/**
	 * Construct function
	 * 
	 * @since 3.8.8
	 * @version 5.3.0
	 * @return void
	 */
	public function __construct() {
		// Normal page lifecycle.
		add_action( 'init', array( $this, 'maybe_unhook' ), 20 );
		add_action( 'wp', array( $this, 'maybe_unhook' ), 20 );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'maybe_unhook' ), 1 );

		// **Critical for AJAX**: fires inside wc-ajax=update_order_review handler before fragments render.
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'maybe_unhook' ), 0 );
	}

	/**
	 * Remove TutorStarter's filter in all relevant contexts.
	 *
	 * - Runs on normal requests and during wc-ajax=update_order_review.
	 * - During the AJAX call, do NOT rely on is_checkout/is_flexify guards.
	 *
	 * @since 5.3.0
	 * @return void
	 */
	public function maybe_unhook() {
		// Only restrict context outside wc-ajax=update_order_review.
		if ( ! wp_doing_ajax() && isset( $_REQUEST['wc-ajax'] ) && 'update_order_review' === sanitize_key( wp_unslash( $_REQUEST['wc-ajax'] ) ) ) {
			if ( function_exists('is_flexify_checkout') && ! is_flexify_checkout() ) {
				return;
			}

			if ( function_exists('is_checkout') && ! is_checkout() ) {
				return;
			}
		}

		$filter = 'woocommerce_order_button_html';
		$target = 'tutorstarter_order_btn_html';

		// Remove at exact attached priority if known.
		$priority = has_filter( $filter, $target );

		if ( false !== $priority ) {
			remove_filter( $filter, $target, (int) $priority );
		}

		// Defensive sweep across priorities (covers re-attachments).
		$this->remove_callback_across_all_priorities( $filter, $target );
	}


	/**
	 * Deep removal across all priorities for a hook.
	 *
	 * @since 5.3.0
	 * @param string $hook | Hook name
	 * @param string $callback | Callback name
	 * @return void
	 */
	private function remove_callback_across_all_priorities( $hook, $callback ) {
		global $wp_filter;

		foreach ( array( 1, 5, 10, 11, 15, 20, 50, 100, 9999 ) as $p ) {
			remove_filter( $hook, $callback, $p );
		}

		if ( isset( $wp_filter[ $hook ] ) && isset( $wp_filter[ $hook ]->callbacks ) && is_array( $wp_filter[ $hook ]->callbacks ) ) {
			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				remove_filter( $hook, $callback, (int) $priority );
			}
		}
	}
}