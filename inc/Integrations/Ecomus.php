<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Ecomus theme
 *
 * @since 3.9.4
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Ecomus {

	/**
	 * Construct function
	 * 
	 * @since 3.9.4
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'remove_actions' ), 20 );
	}


	/**
	 * Disable Ecomus checkout customizations
	 * 
	 * @since 3.9.4
     * @return void
	 */
	public function remove_actions() {
		if ( ! class_exists('\Ecomus\WooCommerce') || ! is_flexify_checkout() ) {
			return;
		}

		$checkout_instance = \Ecomus\WooCommerce\Checkout::instance();

        if ( $checkout_instance ) {
            remove_action( 'woocommerce_before_checkout_form', array( $checkout_instance, 'before_login_form' ), 10 );
            remove_action( 'woocommerce_before_checkout_form', array( $checkout_instance, 'login_form' ), 10 );
            remove_action( 'woocommerce_before_checkout_form', array( $checkout_instance, 'coupon_form' ), 10 );
            remove_action( 'woocommerce_before_checkout_form', array( $checkout_instance, 'after_login_form' ), 10 );
            remove_filter( 'woocommerce_checkout_coupon_message', array( $checkout_instance, 'coupon_form_name' ), 10 );
        }

        $general_instance = \Ecomus\WooCommerce\General::instance();

        if ( $general_instance ) {
            remove_action( 'woocommerce_before_cart_totals', array( $general_instance, 'before_cart_totals' ), 10 );
            remove_action( 'woocommerce_after_cart_totals', array( $general_instance, 'after_cart_totals' ), 10 );
            remove_filter( 'woocommerce_cart_subtotal', array( $general_instance, 'cart_subtotal' ), 10, 3);
            remove_filter( 'woocommerce_cart_item_name', array( $general_instance, 'review_product_name_html' ), 10, 3);
            remove_filter( 'woocommerce_checkout_cart_item_quantity', array( $general_instance, 'review_cart_item_quantity_html' ), 10, 3);
        }
	}
}

new Ecomus();