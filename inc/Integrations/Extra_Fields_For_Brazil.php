<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists('Extra_Checkout_Fields_For_Brazil') ) {
    /**
     * Compatibility with Brazilian Market on WooCommerce plugin
     *
     * @since 3.8.0
     * @version 5.1.0
     * @package MeuMouse.com
     */
    class Extra_Fields_For_Brazil {

        /**
         * Construct function
         *
         * @since 3.8.0
         * @return void
         */
        public function __construct() {
            add_action( 'wp_enqueue_scripts', array( $this, 'compat_scripts' ), 100 );
        }

        /**
         * Add compatibility with scripts on checkout
         * 
         * @since 3.8.0
         * @version 5.1.0
         * @return void
         */
        public function compat_scripts() {
            if ( ! class_exists('Extra_Checkout_Fields_For_Brazil') || ! is_flexify_checkout() ) {
                return;
            }
            
            // prevent conflict between jQuery Mask of both plugins
            if ( Admin_Options::get_setting('enable_field_masks') === 'yes' ) {
                wp_localize_script( 'woocommerce-extra-checkout-fields-for-brazil-front', 'bmwPublicParams', array(
                    'maskedinput' => 'no', // prevent conflict with Flexify Checkout
                ));
            }
        }
    }
}