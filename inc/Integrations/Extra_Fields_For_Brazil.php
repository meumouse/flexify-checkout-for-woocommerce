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
     * @version 5.1.1
     * @package MeuMouse.com
     */
    class Extra_Fields_For_Brazil {

        /**
         * Construct function
         *
         * @since 3.8.0
         * @version 5.1.1
         * @return void
         */
        public function __construct() {
            add_action( 'wp_enqueue_scripts', array( $this, 'compat_scripts' ), 100 );
            add_filter( 'Flexify_Checkout/Assets/Script_Data', array( $this, 'bmw_param_settings' ), 10, 1 );
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

        /**
         * Add Extra Checkout Fields for Brazil settings to Flexify Checkout script data
         * 
         * @since 5.1.1
         * @param array $params | Current script parameters
         * @return array
         */
        public function bmw_param_settings( $params ) {
            $params['bmw_active'] = class_exists('Extra_Checkout_Fields_For_Brazil') ? 'yes' : 'no';
            $params['bmw_settings'] = get_option('wcbcf_settings');
        }
    }
}