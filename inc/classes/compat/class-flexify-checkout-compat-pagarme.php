<?php

/**
 * Compatibility with pagar.me gateway
 * 
 * @since 2.1.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists( 'Flexify_Checkout_Compat_Pagarme' ) ) {
    return;
}

class Flexify_Checkout_Compat_Pagarme {

    public static function run() {
        add_action( 'wp_print_styles', array( __CLASS__, 'remove_style_from_core' ), 999 );
    }

    public static function remove_style_from_core() {
        if ( class_exists( 'Woocommerce\Pagarme\Core' ) ) {
            wp_dequeue_style( 'front-style-' . Woocommerce\Pagarme\Core::SLUG );
        }
    }
}