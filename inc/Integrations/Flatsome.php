<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( defined('UXTHEMES_ACCOUNT_URL') ) {
    /**
     * Compatibility with Flatsome.
     *
     * @since 1.0.0
     * @version 5.0.0
     * @package MeuMouse.com
     */
    class Flatsome {

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
         * Disable Flatsome customizations
         *
         * @since 1.0.0
         * @version 5.0.0
         * @return void
         */
        public function remove_actions() {
            if ( ! defined('UXTHEMES_ACCOUNT_URL') || ! is_flexify_checkout() ) {
                return;
            }
            
            if ( function_exists('flatsome_scripts') ) {
                remove_action( 'wp_head', 'flatsome_google_fonts_lazy', 10 );
                remove_action( 'wp_head', 'flatsome_custom_css', 100 );
            }

            add_filter( 'Flexify_Checkout/Assets/Set_Allowed_Sources', array( $this, 'add_allowed_sources' ) );
            add_action( 'wp_head', array( $this, 'add_custom_css_js' ) );
        }


        /**
         * Add allowed sources for Flexify Checkout
         *
         * @since 1.0.0
         * @version 5.0.0
         *
         * @param array $allowed_sources | Allowed sources
         * @return array Modified allowed sources.
         */
        public function add_allowed_sources( $allowed_sources ) {
            $uri = get_template_directory_uri();

            $allowed_sources[] = $uri . '/assets/js/flatsome.js';
            $allowed_sources[] = $uri . '/assets/js/woocommerce.js';
            $allowed_sources[] = $uri . '/inc/extensions/flatsome-cookie-notice/flatsome-cookie-notice.js';

            wp_enqueue_style( 'magnific-popup-css', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css', array(), '1.1.0' );

            return $allowed_sources;
        }


        /**
         * Add custom CSS and JS to head
         *
         * @since 1.0.0
         * @version 5.0.0
         * @return void
         */
        public function add_custom_css_js() {
            ob_start(); ?>

            .lightbox-content {
                background-color: #fff;
                max-width: 875px;
                margin: 0 auto;
                transform: translateZ(0);
                box-shadow: 3px 3px 20px 0 rgb(0 0 0 / 15%);
                position: relative;
            }

            .mfp-content, .stuck, button.mfp-close {
                top: 32px !important;
            }

            .flatsome-cookies {
                background-color: #fff;
                bottom: 0;
                box-shadow: 0 0 9px rgb(0 0 0 / 14%);
                left: 0;
                padding: 15px 30px;
                position: fixed;
                right: 0;
                top: auto;
                transform: translate3d(0,100%,0);
                transition: transform .35s ease;
                z-index: 999;
            }

            .flatsome-cookies__inner {
                align-items: center;
                display: flex;
                justify-content: space-between;
            }

            .flatsome-cookies__text {
                flex: 1 1 auto;
                padding-right: 30px;
            }

            .flatsome-cookies__buttons {
                flex: 0 0 auto;
            }

            .flatsome-cookies__buttons > a {
                margin-bottom: 0;
                margin-right: 20px;
                text-decoration: none;
            }

            .flatsome-cookies__buttons a span {
                color: #fff;
            }

            .flatsome-cookies__buttons > a:last-child {
                margin-right: 0;
            }

            .flatsome-cookies--inactive {
                transform: translate3d(0,100%,0);
            }

            .flatsome-cookies--active {
                transform: none;
            }

            <?php $css = ob_get_clean();

            printf( __('<style>%s</style>'), esc_html( $css ) );

            ob_start(); ?>

            jQuery(document).on( 'click', '#terms-and-conditions-accept', function() {
                jQuery('#terms').closest('.mdl-checkbox').addClass('is-checked');
            });

            <?php $js = ob_get_clean();

            printf( __('<script>%s</script>'), wp_kses_post( $js ) );
        }
    }
}