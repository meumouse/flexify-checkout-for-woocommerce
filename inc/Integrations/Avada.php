<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('Avada_Woocommerce') ) {
	/**
	 * Compatibility with Avada theme
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @package MeuMouse.com
	 */
	class Avada {

		/**
		 * Construct function
		 * 
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'wp_footer', array( $this, 'dequeue_scripts' ), 15 );
			add_action( 'wp', array( $this, 'remove_filters' ), 100 );
			add_action( 'wp', array( $this, 'remove_styles' ), 0 );
		}


		/**
		 * Dequeue scripts
		 * Hook just after scripts are enqueued, but before they're output in the footer.
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function dequeue_scripts() {
			global $wp_scripts;

			$wp_scripts->dequeue('avada-quantity');
			$wp_scripts->dequeue('avada-drop-down');
			$wp_scripts->dequeue('fusion-scripts');
		}


		/**
		 * Disable avada checkout customizations
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function remove_filters() {
			if ( ! class_exists('Avada_Woocommerce') || ! is_flexify_checkout() ) {
				return;
			}

			global $avada_woocommerce;

			$off_canvas = AWB_Off_Canvas_Front_End();
			remove_action( 'wp_footer', array( $off_canvas, 'insert' ), 0 );
			remove_filter( 'woocommerce_order_button_html', array( $avada_woocommerce, 'order_button_html' ) );
			remove_action( 'woocommerce_checkout_terms_and_conditions', array( $avada_woocommerce, 'change_allowed_post_tags_before_terms' ), 15 );
			remove_action( 'woocommerce_checkout_terms_and_conditions', array( $avada_woocommerce, 'change_allowed_post_tags_after_terms' ), 35 );
			remove_action( 'woocommerce_before_checkout_form', array( $avada_woocommerce, 'avada_top_user_container' ), 1 );
			remove_action( 'woocommerce_before_checkout_form', array( $avada_woocommerce, 'checkout_coupon_form' ) );
			remove_action( 'woocommerce_checkout_after_order_review', array( $avada_woocommerce, 'checkout_after_order_review' ), 20 );
			remove_action( 'woocommerce_before_checkout_form', array( $avada_woocommerce, 'before_checkout_form' ) );
			remove_action( 'woocommerce_after_checkout_form', array( $avada_woocommerce, 'after_checkout_form' ) );
			remove_action( 'woocommerce_checkout_before_customer_details', array( $avada_woocommerce, 'checkout_before_customer_details' ) );
			remove_action( 'woocommerce_checkout_after_customer_details', array( $avada_woocommerce, 'checkout_after_customer_details' ) );
			remove_action( 'woocommerce_checkout_billing', array( $avada_woocommerce, 'checkout_billing' ), 20 );
			remove_action( 'woocommerce_checkout_shipping', array( $avada_woocommerce, 'checkout_shipping' ), 20 );
			remove_filter( 'woocommerce_enable_order_notes_field', array( $avada_woocommerce, 'enable_order_notes_field' ) );
			remove_filter( 'woocommerce_thankyou', array( $avada_woocommerce, 'view_order' ) );
		}

		
		/**
		 * Disable Avada CSS
		 * 
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function remove_styles() {
			if ( ! is_flexify_checkout() || ! class_exists('Fusion_Dynamic_CSS') ) {
				return;
			}

			$fusion_dynamic_css = \Fusion_Dynamic_CSS::get_instance();

			remove_action( 'wp_enqueue_scripts', array( $fusion_dynamic_css, 'init' ), 110 );
		}
	}
}