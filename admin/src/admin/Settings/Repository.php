<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\Admin\Default_Options;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Storage and sanitization helpers for Flexify Checkout settings.
 *
 * All simple options live in a single array option (flexify_checkout_settings).
 * Saving merges the incoming payload over the stored values, sanitizing each
 * key based on its field definition declared in Registry.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Repository {

    /**
     * Option name holding the settings array.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION_NAME = 'flexify_checkout_settings';


    /**
     * Read current settings merged with defaults.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_settings() {
        $defaults = ( new Default_Options() )->set_default_data_options();

        return wp_parse_args( get_option( self::OPTION_NAME, array() ), $defaults );
    }


    /**
     * Persist a settings payload and return the sanitized result.
     *
     * Keys missing from the payload keep their stored value, so partial saves
     * are safe (the REST client always sends the full settings object anyway).
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw payload from the REST request.
     * @return array<string,mixed>
     */
    public static function save_settings( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();
        $definitions = Registry::get_field_definitions();
        $sanitized = self::get_settings();

        foreach ( $incoming as $key => $value ) {
            $key = sanitize_key( $key );

            if ( '' === $key ) {
                continue;
            }

            $definition = isset( $definitions[ $key ] ) ? $definitions[ $key ] : array();
            $sanitized[ $key ] = self::sanitize_setting_value( $key, $value, $definition );
        }

        /**
         * Filter the sanitized settings before persisting.
         *
         * @since 6.0.0
         * @param array $sanitized Sanitized settings array.
         * @param array $incoming Raw incoming payload.
         */
        $sanitized = apply_filters( 'Flexify_Checkout/Admin/Save_Settings', $sanitized, $incoming );

        update_option( self::OPTION_NAME, $sanitized );

        return $sanitized;
    }


    /**
     * Reset plugin options to a pristine state.
     *
     * Mirrors the legacy flexify_checkout_reset_plugin_action AJAX behavior.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function reset_settings() {
        $deleted = delete_option( self::OPTION_NAME );

        if ( $deleted ) {
            delete_option('flexify_checkout_step_fields');
            delete_option('flexify_checkout_conditions');
            delete_option('flexify_checkout_alternative_license_activation');
            delete_transient('flexify_checkout_api_request_cache');
            delete_transient('flexify_checkout_api_response_cache');
            delete_transient('flexify_checkout_license_status_cached');

            // set default options again
            $admin_options = new \MeuMouse\Flexify_Checkout\Admin\Admin_Options();
            $admin_options->set_default_options();
            $admin_options->set_checkout_step_fields();
        }

        return (bool) $deleted;
    }


    /**
     * Convert a switch value to yes/no.
     *
     * @since 6.0.0
     * @param mixed $value Raw value.
     * @return string
     */
    private static function sanitize_toggle( $value ) {
        return in_array( $value, array( 'yes', '1', 1, true, 'true', 'on' ), true ) ? 'yes' : 'no';
    }


    /**
     * Sanitize a setting based on its field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param mixed $value Raw value.
     * @param array<string,mixed> $definition Field definition from Registry.
     * @return mixed
     */
    private static function sanitize_setting_value( $key, $value, $definition ) {
        $type = isset( $definition['type'] ) ? (string) $definition['type'] : '';

        if ( 'toggle' === $type ) {
            return self::sanitize_toggle( $value );
        }

        if ( 'color' === $type ) {
            return self::sanitize_color_value( $value );
        }

        // Custom CSS/JS editors keep raw content (admin-only capability).
        if ( 'code-editor' === $type ) {
            return is_string( $value ) ? $value : '';
        }

        if ( is_array( $value ) ) {
            return self::sanitize_array_value( $value );
        }

        if ( 'textarea' === $type ) {
            return sanitize_textarea_field( (string) $value );
        }

        if ( 'number' === $type ) {
            return is_numeric( $value ) ? (string) $value : '';
        }

        if ( 'url' === $type ) {
            return esc_url_raw( (string) $value );
        }

        return sanitize_text_field( (string) $value );
    }


    /**
     * Sanitize a plain nested array setting.
     *
     * @since 6.0.0
     * @param array<mixed> $value Raw array value.
     * @return array<mixed>
     */
    private static function sanitize_array_value( $value ) {
        $sanitized = array();

        foreach ( $value as $item_key => $item_value ) {
            if ( is_array( $item_value ) ) {
                $sanitized[ $item_key ] = self::sanitize_array_value( $item_value );
                continue;
            }

            if ( is_bool( $item_value ) || is_int( $item_value ) || is_float( $item_value ) ) {
                $sanitized[ $item_key ] = $item_value;
                continue;
            }

            $sanitized[ $item_key ] = sanitize_text_field( (string) $item_value );
        }

        return $sanitized;
    }


    /**
     * Sanitize a hex color value.
     *
     * @since 6.0.0
     * @param mixed $value Raw color value.
     * @return string
     */
    private static function sanitize_color_value( $value ) {
        $value = is_string( $value ) ? trim( $value ) : '';

        if ( '' === $value ) {
            return '';
        }

        $sanitized = sanitize_hex_color( $value );

        if ( null !== $sanitized && '' !== $sanitized ) {
            return $sanitized;
        }

        return sanitize_text_field( $value );
    }
}
