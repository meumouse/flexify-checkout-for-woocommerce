<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( Helpers::check_active_theme('North') ) {
    /**
     * Compatibility with North Theme
     *
     * @since 1.0.0
     * @version 5.0.0
     * @package MeuMouse.com
     */
    class North {

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
         * Disable Divi checkout customizations
         * 
         * @since 1.0.0
         * @version 5.0.0
         * @return void
         */
        public function remove_actions() {
            if ( ! Helpers::check_active_theme('North') || ! is_flexify_checkout() ) {
                return;
            }

            remove_action( 'woocommerce_checkout_before_customer_details', 'thb_checkout_before_customer_details', 5 );
        }
    }
}