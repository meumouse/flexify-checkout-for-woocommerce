<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Contact opt-out (suppression) for cart recovery messages.
 *
 * LGPD/GDPR compliance: a customer must be able to stop receiving recovery
 * follow-ups. This class keeps a lightweight suppression list (hashed phone and
 * e-mail, so no raw personal data is stored in the option) and a signed public
 * opt-out link that customers can include in their messages via the
 * {{ optout_link }} placeholder. The send path checks is_cart_suppressed()
 * before dispatching anything.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Opt_Out {

    /**
     * Option storing the suppression list ( hash => unix timestamp ).
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION = 'flexify_checkout_recovery_optouts';

    /**
     * Query var carrying the cart ID on the public opt-out link.
     *
     * @since 6.0.0
     * @var string
     */
    const QUERY_VAR = 'fcrc_optout';

    /**
     * Register the public opt-out request handler.
     *
     * @since 6.0.0
     * @return void
     */
    public function init() {
        add_action( 'template_redirect', array( __CLASS__, 'handle_optout_request' ) );
    }


    /**
     * Normalize a phone number to digits only.
     *
     * @since 6.0.0
     * @param string $phone | Raw phone number.
     * @return string
     */
    private static function normalize_phone( $phone ) {
        return preg_replace( '/\D+/', '', (string) $phone );
    }


    /**
     * Build the suppression hash for a contact value.
     *
     * @since 6.0.0
     * @param string $type | 'p' for phone, 'e' for e-mail.
     * @param string $value | The normalized contact value.
     * @return string
     */
    private static function hash( $type, $value ) {
        return sha1( $type . ':' . $value );
    }


    /**
     * Whether the given phone and/or e-mail is on the suppression list.
     *
     * @since 6.0.0
     * @param string $phone | Phone number (any format).
     * @param string $email | E-mail address.
     * @return bool
     */
    public static function is_suppressed( $phone = '', $email = '' ) {
        $list = get_option( self::OPTION, array() );

        if ( ! is_array( $list ) || empty( $list ) ) {
            return false;
        }

        $phone = self::normalize_phone( $phone );

        if ( $phone !== '' && isset( $list[ self::hash( 'p', $phone ) ] ) ) {
            return true;
        }

        $email = strtolower( trim( (string) $email ) );

        if ( $email !== '' && isset( $list[ self::hash( 'e', $email ) ] ) ) {
            return true;
        }

        return false;
    }


    /**
     * Whether the cart's captured contact has opted out.
     *
     * @since 6.0.0
     * @param int $cart_id | The recovery cart post ID.
     * @return bool
     */
    public static function is_cart_suppressed( $cart_id ) {
        $phone = get_post_meta( $cart_id, '_fcrc_cart_phone', true );
        $email = get_post_meta( $cart_id, '_fcrc_cart_email', true );

        return self::is_suppressed( $phone, $email );
    }


    /**
     * Add a phone and/or e-mail to the suppression list.
     *
     * @since 6.0.0
     * @param string $phone | Phone number (any format).
     * @param string $email | E-mail address.
     * @return void
     */
    public static function suppress( $phone = '', $email = '' ) {
        $list = get_option( self::OPTION, array() );
        $list = is_array( $list ) ? $list : array();

        $now = (int) current_time( 'timestamp', true );
        $phone = self::normalize_phone( $phone );
        $email = strtolower( trim( (string) $email ) );

        if ( $phone !== '' ) {
            $list[ self::hash( 'p', $phone ) ] = $now;
        }

        if ( $email !== '' ) {
            $list[ self::hash( 'e', $email ) ] = $now;
        }

        update_option( self::OPTION, $list, false );

        /**
         * Fires after a contact opts out of recovery messages.
         *
         * @since 6.0.0
         * @param string $phone | Normalized phone number.
         * @param string $email | Normalized e-mail address.
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/Contact_Opted_Out', $phone, $email );
    }


    /**
     * Signed token for a cart's opt-out link.
     *
     * The link carries only the cart ID plus this token, never raw personal
     * data, keeping contact details out of URLs.
     *
     * @since 6.0.0
     * @param int $cart_id | The recovery cart post ID.
     * @return string
     */
    private static function token( $cart_id ) {
        return hash_hmac( 'sha256', 'fcrc-optout|' . (int) $cart_id, wp_salt( 'auth' ) );
    }


    /**
     * Public opt-out link for a cart.
     *
     * @since 6.0.0
     * @param int $cart_id | The recovery cart post ID.
     * @return string
     */
    public static function generate_optout_link( $cart_id ) {
        if ( ! $cart_id ) {
            return '';
        }

        return add_query_arg(
            array(
                self::QUERY_VAR => (int) $cart_id,
                'fcrc_token'    => self::token( $cart_id ),
            ),
            home_url( '/' )
        );
    }


    /**
     * Handle a public opt-out request.
     *
     * Verifies the signed token, suppresses the cart's contacts and renders a
     * short confirmation. Runs on the front-end only.
     *
     * @since 6.0.0
     * @return void
     */
    public static function handle_optout_request() {
        if ( is_admin() || ! isset( $_GET[ self::QUERY_VAR ], $_GET['fcrc_token'] ) ) {
            return;
        }

        $cart_id = absint( $_GET[ self::QUERY_VAR ] );
        $token   = sanitize_text_field( wp_unslash( $_GET['fcrc_token'] ) );

        if ( ! $cart_id || ! hash_equals( self::token( $cart_id ), $token ) ) {
            return;
        }

        $phone = get_post_meta( $cart_id, '_fcrc_cart_phone', true );
        $email = get_post_meta( $cart_id, '_fcrc_cart_email', true );

        self::suppress( $phone, $email );

        // Cancel any follow-ups already scheduled for this cart.
        if ( class_exists('\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Hooks') ) {
            Hooks::cancel_scheduled_cart_process( $cart_id );
        }

        $message = apply_filters(
            'Flexify_Checkout/Recovery_Carts/Opt_Out_Confirmation_Message',
            esc_html__( 'You have been unsubscribed and will no longer receive cart recovery messages.', 'flexify-checkout-for-woocommerce' ),
            $cart_id
        );

        wp_die(
            esc_html( $message ),
            esc_html__( 'Unsubscribed', 'flexify-checkout-for-woocommerce' ),
            array( 'response' => 200 )
        );
    }
}
