<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Tokoo theme.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @link https://themeforest.net/item/tokoo-electronics-store-woocommerce-theme-for-affiliates-dropship-and-multivendor-websites/22359036
 */
class Compat_Tokoo {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_tokoo' ) );
    }

    /**
     * Compatibility with Tokoo theme.
     *
     * @return void
     */
    public function compat_tokoo() {
        if ( ! function_exists('tokoo_customer_details_open') ) {
            return;
        }
        
        remove_action( 'woocommerce_checkout_before_customer_details', 'tokoo_customer_details_open', 0 );
        remove_action( 'woocommerce_checkout_after_customer_details', 'tokoo_customer_details_close', 90 );
    }
}

new Compat_Tokoo();