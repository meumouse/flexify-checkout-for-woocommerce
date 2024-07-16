<?php

namespace MeuMouse\Flexify_Checkout\Compat\Shopkeeper;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Shopkeeper theme.
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Compat_Shopkeeper {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp', array( $this, 'compat_shopkeeper' ) );
    }

    /**
     * Disable shopkeeper customizations.
     */
    public function compat_shopkeeper() {
        if ( ! function_exists('shopkeeper_setup') || ! is_flexify_checkout() ) {
            return;
        }
        
        remove_action( 'wp_head', 'shopkeeper_custom_styles', 99 );
    }
}

new Compat_Shopkeeper();