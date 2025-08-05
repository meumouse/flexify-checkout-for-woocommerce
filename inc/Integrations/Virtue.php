<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists('Kadence_Wrapping') ) {
    /**
     * Compatibility with Virtue theme
     *
     * @since 1.0.0
     * @version 5.0.0
     * @package MeuMouse.com
     */
    class Virtue {

        /**
         * Construct function
         *
         * @since 1.0.0
         * @return void
         */
        public function __construct() {
            add_action( 'wp', array( $this, 'compat_virtue' ) );
        }


        /**
         * Virtue makes use this concept: http://scribu.net/wordpress/theme-wrappers.html
         * Disable theme wrapper as we don't need theme's header and footer on checkout page.
         * 
         * @since 1.0.0
         * @return void
         */
        public function compat_virtue() {
            if ( ! class_exists('Kadence_Wrapping') || ! is_flexify_checkout() ) {
                return;
            }
            
            remove_filter( 'template_include', array( 'Kadence_Wrapping', 'wrap' ), 101 );
        }
    }
}