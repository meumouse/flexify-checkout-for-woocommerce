<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists('Virtuaria_Payments_By_Payco') ) {
    /**
     * Compatibility with Payments by Payco gateway
     *
     * @since 5.3.3
     * @link https://wordpress.org/plugins/virtuaria-payments-by-payco/
     */
    class Payco {

        /**
         * Construct function.
         *
         * @since 5.3.3
         * @return void
         */
        public function __construct() {
            
        }

    }
}