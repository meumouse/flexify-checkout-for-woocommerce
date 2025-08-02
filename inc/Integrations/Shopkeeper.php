<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( function_exists('shopkeeper_setup') ) {
    /**
     * Compatibility with Shopkeeper theme
     *
     * @since 1.0.0
     * @version 5.0.0
     * @package MeuMouse.com
     */
    class Shopkeeper {

        /**
         * Construct function
         *
         * @since 1.0.0
         * @return void
         */
        public function __construct() {
            add_action( 'wp', array( $this, 'remove_actions' ) );
        }


        /**
         * Disable shopkeeper customizations
         * 
         * @since 1.0.0
         * @version 5.0.0
         * @return void
         */
        public function remove_actions() {
            if ( ! function_exists('shopkeeper_setup') || ! is_flexify_checkout() ) {
                return;
            }
            
            remove_action( 'wp_head', 'shopkeeper_custom_styles', 99 );
        }
    }
}