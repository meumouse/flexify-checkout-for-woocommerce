<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists('Flexify_Checkout_Compat_Pagarme') && ! class_exists('Woocommerce\Pagarme\Core') ) {
    return;
}

/**
 * Compatibility with Pagar.me gateway
 * 
 * @since 2.1.0
 * @version 3.3.0
 * @link https://br.wordpress.org/plugins/pagarme-payments-for-woocommerce/
 */
class Flexify_Checkout_Compat_Pagarme {

    public static function run() {
        add_action( 'wp_print_styles', array( __CLASS__, 'remove_style_from_core' ), 999 );
        add_action( 'wp_head', array( __CLASS__, 'add_header_styles' ), 999 );
    }

    public static function remove_style_from_core() {
        if ( class_exists('Woocommerce\Pagarme\Core') ) {
            wp_dequeue_style( 'front-style-' . Woocommerce\Pagarme\Core::SLUG );
        }
    }

    public static function add_header_styles() {
        $css = '#wcmp-checkout-errors {';
            $css .= 'display: none;';
        $css .= '}';

        ?>
        <style type="text/css">
        <?php echo $css; ?>
        </style> <?php
    }
}