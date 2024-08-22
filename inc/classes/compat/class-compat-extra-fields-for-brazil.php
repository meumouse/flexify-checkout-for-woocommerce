<?php

namespace MeuMouse\Flexify_Checkout\Compat;

use MeuMouse\Flexify_Checkout\Init;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Brazilian Market on WooCommerce plugin
 *
 * @since 3.8.0
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
        }
    }
}

new Extra_Fields_For_Brazil();