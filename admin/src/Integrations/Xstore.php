<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with XStore theme and child theme.
 *
 * @since 5.5.0
 * @package MeuMouse\Flexify_Checkout\Integrations
 * @author MeuMouse.com
 */
class Xstore {

    /**
     * Construct function.
     *
     * @since 5.5.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp_head', array( $this, 'add_header_styles' ), 999 );
    }


    /**
     * Check if XStore theme (or child theme) is active.
     *
     * @since 5.5.0
     * @return bool
     */
    public static function is_active() {
        if ( defined('ETHEME_THEME_SLUG') && ETHEME_THEME_SLUG === 'xstore' ) {
            return true;
        }

        if ( function_exists('etheme_theme_setup') || function_exists('etheme_child_styles') ) {
            return true;
        }

        if ( ! function_exists('wp_get_theme') ) {
            return false;
        }

        $theme = wp_get_theme();

        if ( ! $theme instanceof \WP_Theme ) {
            return false;
        }

        $parent_theme = $theme->parent();
        $candidates = array(
            strtolower( (string) $theme->get_template() ),
            strtolower( (string) $theme->get_stylesheet() ),
            strtolower( (string) $theme->get('TextDomain') ),
            strtolower( (string) $theme->get('Name') ),
        );

        if ( $parent_theme instanceof \WP_Theme ) {
            $candidates[] = strtolower( (string) $parent_theme->get_template() );
            $candidates[] = strtolower( (string) $parent_theme->get_stylesheet() );
            $candidates[] = strtolower( (string) $parent_theme->get('TextDomain') );
            $candidates[] = strtolower( (string) $parent_theme->get('Name') );
        }

        foreach ( $candidates as $candidate ) {
            if ( $candidate === 'xstore' || strpos( $candidate, 'xstore' ) !== false ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Add header styles to hide show/hide password triggers on login form.
     *
     * @since 5.5.0
     * @return void
     */
    public function add_header_styles() {
        if ( ! self::is_active() || ! function_exists('is_flexify_checkout') || ! is_flexify_checkout() ) {
            return;
        }

        $css = '.flexify-login-form .input-password-wrap .show-password,';
        $css .= '.flexify-login-form .input-password-wrap .hide-password {';
        $css .= 'display: none !important;';
        $css .= '}';
        ?>
        <style type="text/css">
            <?php echo $css; ?>
        </style>
        <?php
    }
}
