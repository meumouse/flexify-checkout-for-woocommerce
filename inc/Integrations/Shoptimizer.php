<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( Helpers::check_active_theme('Shoptimizer') ) {
    /**
     * Compatibility with Shoptimizer
     *
     * @since 1.2.0
     * @version 5.0.0
     * @package MeuMouse.com
     */
    class Shoptimizer {

        /**
         * Construct function.
         *
         * @since 1.2.0
         * @version 5.0.0
         * @return void
         */
        public function __construct() {
            add_action( 'wp', array( $this, 'remove_actions' ) );
        }


        /**
         * Shoptimizer compatibility
         * 
         * @since 1.2.0
         * @version 5.0.0
         * @return void
         */
        public function remove_actions() {
            if ( ! Helpers::check_active_theme('Shoptimizer') || ! is_flexify_checkout() ) {
                return;
            }

            remove_action( 'wp_head', 'ccfw_criticalcss', 5 );
            remove_action( 'woocommerce_before_cart', 'shoptimizer_cart_progress' );
            remove_action( 'woocommerce_before_checkout_form', 'shoptimizer_cart_progress', 5 );
            remove_action( 'woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form' );
            remove_filter( 'woocommerce_cart_item_name', 'shoptimizer_product_thumbnail_in_checkout', 20, 3 );
        }
    }
}