<?php

namespace MeuMouse\Flexify_Checkout\Compat\Gift_Vouchers_Codemenschen;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Gift Vouchers and Packages by Codemenschen.
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Compat_Gift_Vouchers_Codemenschen {
    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
    }

    /**
     * Add compatibility on init.
     */
    public function init() {
        if ( ! class_exists( 'WPGV_Redeem_Voucher' ) ) {
            return;
        }
        
        global $wpgv_redeem_voucher;

        remove_action( 'woocommerce_after_cart_contents', array( $wpgv_redeem_voucher, 'woocommerce_after_cart_contents' ) );
        add_action( 'woocommerce_checkout_order_review', array( $wpgv_redeem_voucher, 'woocommerce_after_cart_contents' ) );
        add_filter( 'flexify_checkout_allowed_sources', array( $this, 'allowed_sources' ) );
    }

    /**
     * Allow script dequeue exception.
     *
     * @param array $scripts Scripts.
     *
     * @return array
     */
    public function allowed_sources( $scripts ) {
        $scripts[] = 'wpgv-woocommerce-script';

        return $scripts;
    }
}

new Compat_Gift_Vouchers_Codemenschen();