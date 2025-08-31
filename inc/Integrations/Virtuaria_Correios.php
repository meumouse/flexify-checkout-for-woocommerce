<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists('Virtuaria_Correios') ) {
    /**
     * Compatibility with Virtuaria Correios plugin
     *
     * @since 5.1.0
     * @see 
     * @package MeuMouse.com
     */
    class Virtuaria_Correios {

        /**
         * Construct function
         *
         * @since 5.1.0
         * @return void
         */
        public function __construct() {
            add_action( 'wp_enqueue_scripts', array( $this, 'compat_scripts' ), 100 );
        }

        /**
         * Add compatibility with scripts on checkout
         * 
         * @since 5.1.0
         * @return void
         */
        public function compat_scripts() {
            if ( ! class_exists('Virtuaria_Correios') || ! is_flexify_checkout() ) {
                return;
            }
            
            wp_dequeue_script('virtuaria-correios-international');
            wp_deregister_script('virtuaria-correios-international');
        }
    }
}