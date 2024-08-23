<?php

namespace MeuMouse\Flexify_Checkout\Compat;

use MeuMouse\Flexify_Checkout\Init;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Brazilian Market on WooCommerce plugin
 *
 * @since 3.8.0
 * @version 3.8.2
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
     * @version 3.8.2
     * @return void
     */
    public function compat_scripts() {
        if ( ! class_exists('Extra_Checkout_Fields_For_Brazil') || ! is_flexify_checkout() ) {
            return;
        }
        
        if ( Init::get_setting('enable_field_masks') ) {
            // Prevent conflict with jQuery mask from Brazilian Market on WooCommerce plugin
            wp_dequeue_script('jquery-mask');
            wp_deregister_script('jquery-mask');

            // Dequeue the original script
            wp_dequeue_script('woocommerce-extra-checkout-fields-for-brazil-front');
            wp_deregister_script('woocommerce-extra-checkout-fields-for-brazil-front');

            // Register a new version of the script without the 'jquery-mask' dependency
            $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            wp_register_script( 'woocommerce-extra-checkout-fields-for-brazil-front-custom',
                plugins_url() . '/woocommerce-extra-checkout-fields-for-brazil/assets/js/frontend/frontend' . $suffix . '.js',
                array( 'jquery', 'mailcheck' ), // Removed 'jquery-mask' from dependencies
                \Extra_Checkout_Fields_For_Brazil::VERSION,
                true
            );

            // Enqueue the custom version of the script
            wp_enqueue_script('woocommerce-extra-checkout-fields-for-brazil-front-custom');
        }
    }
}

new Extra_Fields_For_Brazil();