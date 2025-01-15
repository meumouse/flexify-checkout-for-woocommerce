<?php

namespace MeuMouse\Flexify_Checkout\Compat;

use MeuMouse\Flexify_Checkout\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Sala theme
 *
 * @since 1.0.0
 * @version 3.8.8
 * @package MeuMouse.com
 */
class Compat_Sala {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'after_setup_theme', array( $this, 'compat_sala' ), 20 );
    }


    /**
     * Disable Sala checkout customizations
     * 
     * @since 1.0.0
     * @version 3.8.8
     */
    public function compat_sala() {
        if ( ! Helpers::check_active_theme('Sala') || ! is_flexify_checkout( true ) ) {
            return;
        }
        
        add_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
        remove_action( 'woocommerce_after_order_notes', 'woocommerce_checkout_payment', 20 );
    }
}

new Compat_Sala();