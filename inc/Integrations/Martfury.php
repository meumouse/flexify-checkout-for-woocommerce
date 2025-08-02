<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( Helpers::check_active_theme('Martfury') ) {
    /**
     * Compatibility with Martfury theme
     *
     * @since 1.0.0
     * @version 5.0.0
     * @package MeuMouse.com
     */
    class Martfury {

        /**
         * Construct function
         *
         * @since 1.0.0
         * @return void
         */
        public function __construct() {
            add_action( 'wp', array( $this, 'compat_martfury' ) );
        }


        /**
         * Martfury theme compatibility
         * 
         * @since 1.0.0
         * @version 3.8.8
         */
        public function compat_martfury() {
            if ( ! Helpers::check_active_theme('Martfury') || ! is_flexify_checkout() ) {
                return;
            }

            global $martfury_mobile;

            remove_action( 'wp_footer', 'martfury_quick_view_modal' );
            remove_action( 'wp_footer', 'martfury_off_canvas_mobile_menu' );
            remove_action( 'wp_footer', 'martfury_off_canvas_layer' );
            remove_action( 'wp_footer', 'martfury_off_canvas_user_menu' );
            remove_action( 'wp_footer', 'martfury_back_to_top' );

            if ( $martfury_mobile ) {
                remove_action( 'wp_footer', array( $martfury_mobile, 'mobile_modal_popup' ) );
                remove_action( 'wp_footer', array( $martfury_mobile, 'navigation_mobile' ) );
            }
        }
    }
}