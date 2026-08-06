<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Opt_Out;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * E-mail delivery channel for cart recovery follow-ups.
 *
 * Sends the follow-up message as a branded HTML e-mail through wp_mail(). The
 * message body is authored once (WhatsApp-style plain text with placeholders)
 * and rendered into a minimal, responsive HTML shell with an unsubscribe footer.
 * This is the second delivery channel alongside Joinotify/WhatsApp and enables
 * the multichannel orchestration in Recovery_Handler.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations
 * @author MeuMouse.com
 */
class Email {

    /**
     * Send a follow-up e-mail.
     *
     * @since 6.0.0
     * @param string $to      | Recipient e-mail address.
     * @param array  $event   | The follow-up event settings.
     * @param string $message | The message body with placeholders already replaced.
     * @param int    $cart_id | The recovery cart post ID.
     * @return bool True when wp_mail accepted the message for delivery.
     */
    public static function send( $to, $event, $message, $cart_id ) {
        if ( empty( $to ) || ! is_email( $to ) ) {
            return false;
        }

        $subject = self::build_subject( $event );
        $body    = self::build_body( $message, $cart_id );
        $headers = self::build_headers();

        /**
         * Filter the recovery e-mail arguments before sending.
         *
         * @since 6.0.0
         * @param array $args | Associative array: to, subject, body, headers.
         * @param array $event | The follow-up event settings.
         * @param int $cart_id | The recovery cart post ID.
         */
        $args = apply_filters( 'Flexify_Checkout/Recovery_Carts/Email/Args', array(
            'to'      => $to,
            'subject' => $subject,
            'body'    => $body,
            'headers' => $headers,
        ), $event, $cart_id );

        return (bool) wp_mail( $args['to'], $args['subject'], $args['body'], $args['headers'] );
    }


    /**
     * Resolve the e-mail subject for an event.
     *
     * Uses the event's own subject when set, otherwise a sensible default.
     *
     * @since 6.0.0
     * @param array $event | The follow-up event settings.
     * @return string
     */
    private static function build_subject( $event ) {
        $subject = isset( $event['email_subject'] ) ? trim( (string) $event['email_subject'] ) : '';

        if ( $subject === '' ) {
            $subject = sprintf(
                /* translators: %s: store name. */
                __( 'You left items in your cart at %s', 'flexify-checkout-for-woocommerce' ),
                get_bloginfo( 'name' )
            );
        }

        return $subject;
    }


    /**
     * Build the HTML e-mail body from the plain-text message.
     *
     * @since 6.0.0
     * @param string $message | The message body (placeholders already replaced).
     * @param int    $cart_id | The recovery cart post ID.
     * @return string
     */
    private static function build_body( $message, $cart_id ) {
        // The message is authored as plain text (often with WhatsApp *bold*).
        // Strip the asterisk emphasis, escape, linkify and keep line breaks.
        $clean = preg_replace( '/\*(.+?)\*/s', '$1', (string) $message );
        $html  = wpautop( make_clickable( esc_html( $clean ) ) );

        $store   = esc_html( get_bloginfo( 'name' ) );
        $optout  = Opt_Out::generate_optout_link( $cart_id );
        $unsub   = esc_html__( 'If you no longer wish to receive these messages, unsubscribe here.', 'flexify-checkout-for-woocommerce' );

        $footer = '';

        if ( $optout ) {
            $footer = '<p style="margin:24px 0 0;font-size:12px;color:#94a3b8;text-align:center;">'
                . '<a href="' . esc_url( $optout ) . '" style="color:#94a3b8;">' . $unsub . '</a></p>';
        }

        $template = '<div style="margin:0;padding:24px;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;">'
            . '<h2 style="margin:0 0 16px;font-size:18px;color:#0f172a;">' . $store . '</h2>'
            . '<div style="font-size:15px;line-height:1.6;color:#334155;">' . $html . '</div>'
            . $footer
            . '</div></div>';

        /**
         * Filter the rendered recovery e-mail HTML.
         *
         * @since 6.0.0
         * @param string $template | The HTML body.
         * @param string $message | The original message text.
         * @param int $cart_id | The recovery cart post ID.
         */
        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Email/Body', $template, $message, $cart_id );
    }


    /**
     * Build the e-mail headers (HTML content type + From).
     *
     * @since 6.0.0
     * @return array<int,string>
     */
    private static function build_headers() {
        $from_name  = apply_filters( 'Flexify_Checkout/Recovery_Carts/Email/From_Name', get_bloginfo( 'name' ) );
        $from_email = apply_filters( 'Flexify_Checkout/Recovery_Carts/Email/From_Address', get_option( 'admin_email' ) );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        if ( is_email( $from_email ) ) {
            $headers[] = sprintf( 'From: %s <%s>', $from_name, $from_email );
        }

        return $headers;
    }
}
