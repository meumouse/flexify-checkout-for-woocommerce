<?php

namespace MeuMouse\Flexify_Checkout\Compat\North_Theme;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with North Theme.
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Compat_North_Theme {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_north_theme' ) );
    }

    /**
     * Disable Divi checkout customizations.
     */
    public function compat_north_theme() {
        if ( ! flexify_checkout_check_theme_active('North') || ! is_flexify_checkout() ) {
            return;
        }

        remove_action( 'woocommerce_checkout_before_customer_details', 'thb_checkout_before_customer_details', 5 );
    }
}

new Compat_North_Theme();