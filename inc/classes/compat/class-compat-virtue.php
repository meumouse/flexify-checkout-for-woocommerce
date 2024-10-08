<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Virtue theme.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Virtue {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp', array( $this, 'compat_virtue' ) );
    }

    /**
     * Virtue makes use this concept: http://scribu.net/wordpress/theme-wrappers.html
     * Disable theme wrapper as we don't need theme's header and footer on checkout page.
     */
    public function compat_virtue() {
        if ( ! class_exists('Kadence_Wrapping') || ! is_flexify_checkout() ) {
            return;
        }
        
        remove_filter( 'template_include', array( 'Kadence_Wrapping', 'wrap' ), 101 );
    }
}

new Compat_Virtue();