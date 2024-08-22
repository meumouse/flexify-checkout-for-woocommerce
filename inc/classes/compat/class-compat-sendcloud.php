<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Sendcloud plugin.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Sendcloud {
    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_sendcloud' ) );
    }

    /**
     * Add compatibility for Sendcloud plugin.
     */
    public function compat_sendcloud() {
        if ( ! function_exists('sendcloudshipping_init') ) {
            return;
        }
        
        add_action( 'woocommerce_checkout_order_review', 'sendcloudshipping_add_service_point_to_checkout', 100 );
    }
}

new Compat_Sendcloud();