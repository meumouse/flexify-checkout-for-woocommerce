<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use Glozin\WooCommerce\Checkout;
use Glozin\WooCommerce\General;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('\Glozin\WooCommerce\Checkout') ) {

    /**
     * Compatibility with Glozin theme
     *
     * @since 5.3.4
     * @link https://wpglozin.com/
     * @package MeuMouse.com
     */
    class Glozin {

        /**
         * Construct function
         *
         * @since 5.3.4
         * @return void
         */
        public function __construct() {
            add_action( 'wp', array( $this, 'remove_actions' ) );
        }


        /**
         * Compatibility with Glozin theme
         *
         * @since 5.3.4
         * @return void
         */
        public function remove_actions() {
            if ( ! is_flexify_checkout() ) {
                return;
            }
            
            $checkout_instance = Checkout::instance();

            remove_action( 'wp_enqueue_scripts', array( $checkout_instance, 'enqueue_scripts' ) );
            remove_filter('glozin_site_content_container_class', array( $checkout_instance, 'site_content_container_class' ));
            remove_action( 'woocommerce_before_checkout_form', array( $checkout_instance, 'before_login_form' ), 10 );
            remove_action( 'woocommerce_before_checkout_form', array( $checkout_instance, 'login_form' ), 10 );
            remove_action( 'woocommerce_before_checkout_form', array( $checkout_instance, 'coupon_form' ), 10 );
            remove_action( 'woocommerce_before_checkout_form', array( $checkout_instance, 'after_login_form' ), 10 );
            remove_filter( 'woocommerce_checkout_coupon_message', array( $checkout_instance, 'coupon_form_name' ), 10);
            remove_action( 'woocommerce_checkout_order_review', array( $checkout_instance, 'information_box' ), 30 );

            $general_instance = General::instance();

            remove_filter( 'woocommerce_widget_cart_item_quantity', array( $general_instance, 'cart_item_quantity'	), 10, 3 );
            remove_action( 'woocommerce_before_quantity_input_field', array( $general_instance, 'quantity_icon_decrease' ) );
            remove_action( 'woocommerce_after_quantity_input_field', array( $general_instance, 'quantity_icon_increase' ) );
            remove_filter( 'woocommerce_cart_item_name', array( $general_instance, 'review_product_name_html' ), 10, 3 );
        }
    }
}