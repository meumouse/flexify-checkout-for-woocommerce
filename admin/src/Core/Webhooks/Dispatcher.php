<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks;

use MeuMouse\Flexify_Checkout\Core\Logs\Logger;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Sends webhook HTTP requests for checkout events.
 *
 * Transport lifted from Recovery_Carts\Core\Webhooks::dispatch_event_webhooks /
 * prepare_headers / maybe_log, generalized to any event. Endpoint config is read
 * through Webhook_Settings (single source of truth, with legacy fallback).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks
 * @author MeuMouse.com
 */
class Dispatcher {

    /**
     * Debug logging flag.
     *
     * @since 6.0.0
     * @var bool
     */
    public static $debug_mode = false;


    /**
     * Resolve the debug flag from the plugin constant when available.
     *
     * @since 6.0.0
     */
    public function __construct() {
        if ( defined('FLEXIFY_CHECKOUT_DEBUG_MODE') ) {
            self::$debug_mode = (bool) FLEXIFY_CHECKOUT_DEBUG_MODE;
        }
    }


    /**
     * Dispatch all configured endpoints for an event.
     *
     * @since 6.0.0
     * @param string $event_key Event key.
     * @param array  $payload Event payload (the dispatcher adds event/triggered_at).
     * @return void
     */
    public static function dispatch( $event_key, array $payload ) {
        $event_key = sanitize_key( $event_key );
        $endpoints = Webhook_Settings::get_event_webhooks( $event_key );

        if ( empty( $endpoints ) ) {
            return;
        }

        // Ensure envelope fields are always present.
        if ( ! isset( $payload['event'] ) ) {
            $payload = array_merge( array( 'event' => $event_key ), $payload );
        }

        if ( ! isset( $payload['triggered_at'] ) ) {
            $payload['triggered_at'] = Webhook_Helpers::current_time_iso();
        }

        foreach ( $endpoints as $endpoint ) {
            self::send( $event_key, $endpoint, $payload );
        }
    }


    /**
     * Send a single endpoint request (skips disabled/invalid URLs).
     *
     * @since 6.0.0
     * @param string $event_key Event key.
     * @param array  $endpoint Endpoint config { enabled, url, headers }.
     * @param array  $payload Payload to send.
     * @return array { ok, http_code, error }
     */
    public static function send( $event_key, array $endpoint, array $payload ) {
        $enabled = $endpoint['enabled'] ?? 'yes';
        $url = isset( $endpoint['url'] ) ? esc_url_raw( $endpoint['url'] ) : '';

        if ( $enabled !== 'yes' || empty( $url ) || ! wp_http_validate_url( $url ) ) {
            return array( 'ok' => false, 'http_code' => 0, 'error' => 'invalid_or_disabled' );
        }

        $headers = self::prepare_headers( $endpoint['headers'] ?? array() );

        /**
         * Filter the webhook payload (global, then per-event).
         *
         * @since 6.0.0
         */
        $prepared_payload = apply_filters( 'Flexify_Checkout/Webhooks/Payload', $payload, $event_key, $endpoint );
        $prepared_payload = apply_filters( 'Flexify_Checkout/Webhooks/Payload/' . $event_key, $prepared_payload, $endpoint );

        $body = wp_json_encode( $prepared_payload );

        if ( false === $body ) {
            self::maybe_log( sprintf( '[Flexify_Checkout][Webhooks] Falha ao codificar payload JSON para o evento %s.', $event_key ) );

            return array( 'ok' => false, 'http_code' => 0, 'error' => 'json_encode_failed' );
        }

        $request_args = array(
            'method' => 'POST',
            'timeout' => 10,
            'headers' => $headers,
            'body' => $body,
        );

        /**
         * Filter the wp_remote_post request args.
         *
         * @since 6.0.0
         */
        $request_args = apply_filters( 'Flexify_Checkout/Webhooks/Request_Args', $request_args, $event_key, $endpoint, $prepared_payload );

        $response = wp_remote_post( $url, $request_args );

        if ( is_wp_error( $response ) ) {
            self::maybe_log( sprintf( '[Flexify_Checkout][Webhooks] Erro ao enviar webhook (%s): %s', $event_key, $response->get_error_message() ) );

            Logger::error( 'webhook', sprintf( 'Webhook "%s" failed: %s', $event_key, $response->get_error_message() ), array(
                'event' => $event_key,
                'url' => $url,
                'error' => $response->get_error_message(),
            ) );

            return array( 'ok' => false, 'http_code' => 0, 'error' => $response->get_error_message() );
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );

        if ( $status_code < 200 || $status_code >= 300 ) {
            self::maybe_log( sprintf( '[Flexify_Checkout][Webhooks] Resposta inesperada (%s): HTTP %d', $event_key, $status_code ) );

            Logger::error( 'webhook', sprintf( 'Webhook "%s" returned HTTP %d', $event_key, $status_code ), array(
                'event' => $event_key,
                'url' => $url,
                'http_code' => $status_code,
            ) );

            return array( 'ok' => false, 'http_code' => $status_code, 'error' => 'unexpected_status' );
        }

        Logger::info( 'webhook', sprintf( 'Webhook "%s" sent → HTTP %d', $event_key, $status_code ), array(
            'event' => $event_key,
            'url' => $url,
            'http_code' => $status_code,
        ) );

        return array( 'ok' => true, 'http_code' => $status_code, 'error' => '' );
    }


    /**
     * Send a single test request for an endpoint, used by the test REST route.
     *
     * @since 6.0.0
     * @param string $event_key Event key.
     * @param array  $endpoint Endpoint config { enabled, url, headers }.
     * @return array { ok, http_code, error }
     */
    public static function send_test( $event_key, array $endpoint ) {
        $event_key = sanitize_key( $event_key );

        $payload = array(
            'event' => $event_key,
            'test' => true,
            'triggered_at' => Webhook_Helpers::current_time_iso(),
            'data' => array(
                'message' => 'Flexify Checkout webhook test',
                'site' => home_url(),
            ),
        );

        // Force enabled for the test even if the stored toggle is off.
        $endpoint['enabled'] = 'yes';

        return self::send( $event_key, $endpoint, $payload );
    }


    /**
     * Merge custom headers over the defaults.
     *
     * @since 6.0.0
     * @param mixed $headers Stored headers (list of { name, value }).
     * @return array<string,string>
     */
    protected static function prepare_headers( $headers ) {
        $version = defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '';

        $prepared = array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'User-Agent' => 'Flexify Checkout/' . $version,
        );

        if ( is_array( $headers ) ) {
            foreach ( $headers as $header ) {
                $name = isset( $header['name'] ) ? trim( $header['name'] ) : '';
                $value = isset( $header['value'] ) ? trim( $header['value'] ) : '';

                if ( $name !== '' && $value !== '' ) {
                    $prepared[ $name ] = $value;
                }
            }
        }

        /**
         * Filter the outgoing webhook headers.
         *
         * @since 6.0.0
         */
        return apply_filters( 'Flexify_Checkout/Webhooks/Headers', $prepared );
    }


    /**
     * Log a debug message when debug mode is on.
     *
     * @since 6.0.0
     * @param string $message Message to log.
     * @return void
     */
    protected static function maybe_log( $message ) {
        if ( self::$debug_mode ) {
            error_log( $message );
        }
    }
}
