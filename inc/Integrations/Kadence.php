<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( defined('KADENCE_VERSION') ) {
    /**
     * Compatibility with Kadence.
     *
     * @since 1.0.0
     * @version 5.0.0
     * @package MeuMouse.com
     */
    class Kadence {

        /**
         * Construct function.
         *
         * @since 1.0.0
         * @return void
         */
        public function __construct() {
            add_action( 'init', array( $this, 'hooks' ) );
        }

        /**
         * Hooks
         */
        public function hooks() {
            if ( ! defined('KADENCE_VERSION') ) {
                return;
            }
            
            add_filter( 'Flexify_Checkout/Assets/Set_Allowed_Sources', array( $this, 'allow_kadence_sources' ) );
        }


        /**
         * Allow essential Kadence CSS and JS.
         *
         * @since 1.0.0
         * @version 5.0.0
         * @param array $allowed_sources | Allowed sources
         * @return array
         */
        public function allow_kadence_sources( $allowed_sources ) {
            $allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/css/global.min.css';
            $allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/css/global.css';
            $allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/js/navigation.min.js';

            return $allowed_sources;
        }
    }
}