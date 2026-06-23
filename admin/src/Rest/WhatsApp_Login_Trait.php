<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Shared helpers for the WhatsApp OTP login endpoints.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
trait WhatsApp_Login_Trait {

    /**
     * Verify the REST cookie nonce sent by the React app.
     *
     * Works for guests and logged-in users (the nonce is tied to the session).
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    protected function verify_rest_nonce( WP_REST_Request $request ) {
        $nonce = $request->get_header('X-WP-Nonce');

        if ( empty( $nonce ) ) {
            $nonce = (string) $request->get_param('_wpnonce');
        }

        return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
    }


    /**
     * Reduce a phone number to digits only.
     *
     * @since 6.0.0
     * @param string $phone Raw phone.
     * @return string
     */
    protected function normalize_phone( $phone ) {
        return preg_replace( '/\D/', '', (string) $phone );
    }


    /**
     * Transient key holding the OTP for a phone.
     *
     * @since 6.0.0
     * @param string $phone Normalized phone.
     * @return string
     */
    protected function otp_transient_key( $phone ) {
        return 'flexify_wa_otp_' . md5( $phone );
    }


    /**
     * Resolve the WhatsApp sender number.
     *
     * Uses the configured sender, falling back to the first Joinotify sender.
     *
     * @since 6.0.0
     * @return string
     */
    protected function get_sender() {
        $sender = trim( (string) Admin_Options::get_setting('whatsapp_login_sender') );

        if ( '' === $sender && function_exists('joinotify_get_first_sender') ) {
            $sender = (string) joinotify_get_first_sender();
        }

        return $sender;
    }


    /**
     * Basic abuse protection: per-phone cooldown and per-IP throttle.
     *
     * @since 6.0.0
     * @param string $phone Normalized phone.
     * @return bool True when the caller is currently rate limited.
     */
    protected function is_rate_limited( $phone ) {
        $cooldown_key = 'flexify_wa_cd_' . md5( $phone );

        if ( get_transient( $cooldown_key ) ) {
            return true;
        }

        set_transient( $cooldown_key, 1, 45 );

        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $ip_key = 'flexify_wa_ip_' . md5( $ip );
        $count = (int) get_transient( $ip_key );

        if ( $count >= 10 ) {
            return true;
        }

        set_transient( $ip_key, $count + 1, 10 * MINUTE_IN_SECONDS );

        return false;
    }


    /**
     * Find a user whose billing phone matches the given number.
     *
     * Compares on digits only so stored formatting differences still match.
     *
     * @since 6.0.0
     * @param string $phone Normalized phone (digits only).
     * @return \WP_User|null
     */
    protected function find_user_by_phone( $phone ) {
        $candidates = array_unique( array( $phone, ltrim( $phone, '0' ) ) );

        // Drop a leading Brazilian country code to also match locally-stored numbers.
        if ( 0 === strpos( $phone, '55' ) && strlen( $phone ) > 11 ) {
            $candidates[] = substr( $phone, 2 );
        }

        foreach ( $candidates as $candidate ) {
            if ( '' === $candidate ) {
                continue;
            }

            foreach ( array( 'billing_phone', 'billing_cellphone' ) as $meta_key ) {
                $users = get_users( array(
                    'meta_key' => $meta_key,
                    'meta_value' => $candidate,
                    'number' => 1,
                    'fields' => 'all',
                ) );

                if ( ! empty( $users ) ) {
                    return $users[0];
                }
            }
        }

        // Fallback: scan stored phones and compare on digits.
        $users = get_users( array(
            'meta_key' => 'billing_phone',
            'number' => 200,
            'fields' => 'all',
        ) );

        foreach ( $users as $user ) {
            $stored = preg_replace( '/\D/', '', (string) get_user_meta( $user->ID, 'billing_phone', true ) );

            if ( '' === $stored ) {
                continue;
            }

            $ends_with = function( $haystack, $needle ) {
                $len = strlen( $needle );

                return $len > 0 && $len <= strlen( $haystack ) && substr( $haystack, -$len ) === $needle;
            };

            if ( $stored === $phone || $ends_with( $phone, $stored ) || $ends_with( $stored, $phone ) ) {
                return $user;
            }
        }

        return null;
    }
}
