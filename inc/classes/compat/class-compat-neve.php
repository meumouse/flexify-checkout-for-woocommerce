<?php

namespace MeuMouse\Flexify_Checkout\Compat;
use MeuMouse\Flexify_Checkout\Core;
use MeuMouse\Flexify_Checkout\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Neve
 *
 * @since 1.0.0
 * @version 3.8.8
 * @package MeuMouse.com
 */
class Compat_Neve {

    /**
     * Construct function
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_filter( 'neve_filter_main_modules', array( $this, 'modify_modules' ) );
    }


    /**
     * Disable Woo module
     *
     * @since 1.0.0
     * @version 3.8.8
     * @param array $modules | Array of modules
     * @return mixed
     */
    public function modify_modules( $modules ) {
        if ( ! Helpers::check_active_theme('Neve') || ! is_flexify_checkout() ) {
            return $modules;
        }

        $key = array_search( 'Compatibility\WooCommerce', $modules );

        if ( false !== $key ) {
            unset( $modules[ $key ] );
        }

        return $modules;
    }
}

new Compat_Neve();