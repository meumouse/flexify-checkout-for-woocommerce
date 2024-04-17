<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( ! class_exists( 'Flexify_Checkout_Compat_Cielo_Loja5' ) && class_exists('WC_Gateway_Loja5_Woo_Cielo_Webservice') ) {
    return;    
}

/**
 * Compatibility with Cielo API - Loja5
 * 
 * @since 3.2.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Compat_Cielo_Loja5 {

    public static function run() {
        remove_filter( 'woocommerce_order_button_html', 'loja5_woo_cielo_webservice_custom_order_button_html' );
    }
}