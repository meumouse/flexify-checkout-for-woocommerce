<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Auros theme
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Auros {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
     * @version 5.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'remove_hooks' ), 20 );
	}

	/**
	 * Remove hooks for prevent conflicts
	 *
     * @since 1.0.0
     * @version 5.0.0
	 * @return void
	 */
	public function remove_hooks() {
		if ( ! class_exists('auros_setup_theme') ) {
			return;
		}
		
		// Remove custom HTML added by auros theme on checkout page.
		remove_action( 'woocommerce_checkout_before_customer_details', 'auros_checkout_before_customer_details_container', 1 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'auros_checkout_after_customer_details_container', 1 );
		remove_action( 'woocommerce_checkout_after_order_review', 'auros_checkout_after_order_review_container', 1 );
		remove_action( 'woocommerce_checkout_order_review', 'auros_woocommerce_order_review_heading', 1 );

		remove_action( 'woocommerce_checkout_before_customer_details', 'osf_checkout_before_customer_details_container', 1 );
		remove_action( 'woocommerce_checkout_after_customer_details', 'osf_checkout_after_customer_details_container', 1 );
		remove_action( 'woocommerce_checkout_after_order_review', 'osf_checkout_after_order_review_container', 1 );
		remove_action( 'woocommerce_checkout_order_review', 'osf_woocommerce_order_review_heading', 1 );

		if ( class_exists('Auros_WooCommerce') ) {
			$auro_woo = \Auros_WooCommerce::getInstance();

			if ( ! empty( $auro_woo ) ) {
				remove_action( 'wp_footer', array( $auro_woo, 'mobile_handheld_footer_bar' ) );
			}
		}

		if ( class_exists('osf_WooCommerce') ) {
			$auro_woo = \osf_WooCommerce::getInstance();

			if ( ! empty( $auro_woo ) ) {
				remove_action( 'wp_footer', array( $auro_woo, 'mobile_handheld_footer_bar' ) );
			}
		}
	}
}

new Auros();