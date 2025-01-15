<?php

namespace MeuMouse\Flexify_Checkout\Compat;

use MeuMouse\Flexify_Checkout\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with North Theme
 *
 * @since 1.0.0
 * @version 3.8.8
 * @package MeuMouse.com
 */
class Compat_North_Theme {

    /**
     * Construct function
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_north_theme' ) );
    }


    /**
     * Disable Divi checkout customizations
     * 
     * @since 1.0.0
     * @version 3.8.8
     */
    public function compat_north_theme() {
        if ( ! Helpers::check_active_theme('North') || ! is_flexify_checkout() ) {
            return;
        }

        remove_action( 'woocommerce_checkout_before_customer_details', 'thb_checkout_before_customer_details', 5 );
    }
}

new Compat_North_Theme();