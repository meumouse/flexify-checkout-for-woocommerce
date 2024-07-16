<?php

namespace MeuMouse\Flexify_Checkout\Compat\Shoptimizer;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Shoptimizer
 *
 * @since 1.2.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Compat_Shoptimizer {

    /**
     * Construct function.
     *
     * @since 1.2.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp', array( $this, 'compat_shoptimizer' ) );
    }


    /**
     * Shoptimizer compatibility
     */
    public function compat_shoptimizer() {
        if ( ! flexify_checkout_check_theme_active('Shoptimizer') || ! is_flexify_checkout() ) {
            return;
        }

        remove_action( 'wp_head', 'ccfw_criticalcss', 5 );
        remove_action( 'woocommerce_before_cart', 'shoptimizer_cart_progress' );
        remove_action( 'woocommerce_before_checkout_form', 'shoptimizer_cart_progress', 5 );
        remove_action( 'woocommerce_after_checkout_form', 'woocommerce_checkout_coupon_form' );
        remove_filter( 'woocommerce_cart_item_name', 'shoptimizer_product_thumbnail_in_checkout', 20, 3 );
    }
}

new Compat_Shoptimizer();