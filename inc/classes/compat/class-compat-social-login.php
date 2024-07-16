<?php

namespace MeuMouse\Flexify_Checkout\Compat\Social_Login;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Social Login.
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Compat_Social_Login {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'compat_social_login' ) );
    }

    /**
     * Add social login compatibility.
     */
    public function compat_social_login() {
        if ( ! function_exists('wc_social_login') ) {
            return;
        }
        
        $social_login = wc_social_login();

        remove_action( 'woocommerce_login_form_end', array( $social_login->get_frontend_instance(), 'render_social_login_buttons' ) );
        add_action( 'woocommerce_login_form_start', array( $social_login->get_frontend_instance(), 'render_social_login_buttons' ) );
    }
}

new Compat_Social_Login();