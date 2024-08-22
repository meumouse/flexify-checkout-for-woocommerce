<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Salient theme.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Salient {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp', array( $this, 'compat_salient' ) );
    }

    /**
     * Disable Salient checkout customizations.
     */
    public function compat_salient() {
        if ( ! function_exists('nectar_get_theme_version') || ! is_flexify_checkout() ) {
            return;
        }
        
        remove_action( 'woocommerce_before_quantity_input_field', 'nectar_quantity_markup_mod_before', 10 );
        remove_action( 'woocommerce_after_quantity_input_field', 'nectar_quantity_markup_mod_after', 10 );
    }
}

new Compat_Salient();