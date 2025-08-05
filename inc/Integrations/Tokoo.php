<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( function_exists('tokoo_customer_details_open') ) {
    /**
     * Compatibility with Tokoo theme
     *
     * @since 1.0.0
     * @version 5.0.0
     * @link https://themeforest.net/item/tokoo-electronics-store-woocommerce-theme-for-affiliates-dropship-and-multivendor-websites/22359036
     * @package MeuMouse.com
     */
    class Tokoo {

        /**
         * Construct function
         *
         * @since 1.0.0
         * @return void
         */
        public function __construct() {
            add_action( 'init', array( $this, 'remove_actions' ) );
        }


        /**
         * Compatibility with Tokoo theme
         *
         * @since 1.0.0
         * @version 5.0.0
         * @return void
         */
        public function remove_actions() {
            if ( ! is_flexify_checkout() || ! function_exists('tokoo_customer_details_open') ) {
                return;
            }
            
            remove_action( 'woocommerce_checkout_before_customer_details', 'tokoo_customer_details_open', 0 );
            remove_action( 'woocommerce_checkout_after_customer_details', 'tokoo_customer_details_close', 90 );
        }
    }
}