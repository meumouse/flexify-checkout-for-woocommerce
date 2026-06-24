<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Neutral helpers for the global webhooks module.
 *
 * Deliberately self-contained: the webhooks engine must not depend on the
 * Recovery_Carts utilities (Helpers::sanitize_array, etc.) so it can run for
 * any checkout context, even with the recovery feature disabled.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks
 * @author MeuMouse.com
 */
class Webhook_Helpers {

    /**
     * Recursively sanitize an arbitrary array of scalar/array values.
     *
     * Keys are sanitized with sanitize_key when they are strings; scalar values
     * with sanitize_text_field. Nested arrays are walked recursively.
     *
     * @since 6.0.0
     * @param mixed $data Raw value (array or scalar).
     * @return mixed
     */
    public static function sanitize_array( $data ) {
        if ( is_array( $data ) ) {
            $clean = array();

            foreach ( $data as $key => $value ) {
                $clean_key = is_string( $key ) ? sanitize_key( $key ) : $key;
                $clean[ $clean_key ] = self::sanitize_array( $value );
            }

            return $clean;
        }

        if ( is_scalar( $data ) ) {
            return is_string( $data ) ? sanitize_text_field( $data ) : $data;
        }

        return '';
    }


    /**
     * Normalize a timestamp (unix int or date string) to ISO8601.
     *
     * @since 6.0.0
     * @param mixed $value Timestamp or date string.
     * @return string ISO8601 date, or empty string when unparseable.
     */
    public static function normalize_timestamp( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $timestamp = is_numeric( $value ) ? absint( $value ) : strtotime( $value );

        if ( ! $timestamp ) {
            return '';
        }

        return gmdate( 'c', $timestamp );
    }


    /**
     * Current time in ISO8601 (UTC).
     *
     * @since 6.0.0
     * @return string
     */
    public static function current_time_iso() {
        return gmdate( 'c', current_time( 'timestamp', true ) );
    }
}
