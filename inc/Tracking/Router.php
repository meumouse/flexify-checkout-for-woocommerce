<?php

namespace MeuMouse\Flexify_Checkout\Tracking;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify internal tracking router.
 *
 * @since 5.5.0
 * @package MeuMouse.com
 */
class Router {

    /**
     * Supported internal events.
     *
     * @since 5.5.0
     * @var array
     */
    const EVENTS = array(
        'fc_begin_checkout',
        'fc_add_shipping_info',
        'fc_add_payment_info',
        'fc_purchase',
    );

    /**
     * Destinations.
     *
     * @since 5.5.0
     * @var array
     */
    const DESTINATIONS = array( 'data_layer', 'ga4', 'meta', 'tiktok', 'google_ads' );

    /**
     * Default internal event map.
     *
     * @since 5.5.0
     * @return array
     */
    private function get_event_map() {
        return apply_filters( 'Flexify_Checkout/Tracking/Event_Map', array(
            'fc_begin_checkout' => array(
                'ga4' => 'begin_checkout',
                'meta' => 'InitiateCheckout',
                'tiktok' => 'InitiateCheckout',
                'google_ads' => 'begin_checkout',
            ),
            'fc_add_shipping_info' => array(
                'ga4' => 'add_shipping_info',
                'meta' => 'AddShippingInfo',
                'tiktok' => 'AddShippingInfo',
                'google_ads' => 'add_shipping_info',
            ),
            'fc_add_payment_info' => array(
                'ga4' => 'add_payment_info',
                'meta' => 'AddPaymentInfo',
                'tiktok' => 'AddPaymentInfo',
                'google_ads' => 'add_payment_info',
            ),
            'fc_purchase' => array(
                'ga4' => 'purchase',
                'meta' => 'Purchase',
                'tiktok' => 'CompletePayment',
                'google_ads' => 'purchase',
            ),
        ));
    }


    /**
     * Construct function.
     *
     * @since 5.5.0
     * @return void
     */
    public function __construct() {
        add_filter( 'Flexify_Checkout/Assets/Script_Data', array( $this, 'append_script_data' ), 20 );
        add_action( 'woocommerce_payment_complete', array( $this, 'maybe_emit_server_purchase' ), 20 );
        add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_emit_server_purchase' ), 20 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_emit_server_purchase' ), 20 );
        add_action( 'wp_ajax_flexify_checkout_tracking_async', array( $this, 'ajax_tracking_async_callback' ) );
        add_action( 'wp_ajax_nopriv_flexify_checkout_tracking_async', array( $this, 'ajax_tracking_async_callback' ) );
    }


    /**
     * Append tracking config and payloads to frontend script data.
     *
     * @since 5.5.0
     * @param array $params Script data.
     * @return array
     */
    public function append_script_data( $params ) {
        $event_map = $this->get_event_map();

        $params['tracking_router'] = array(
            'enabled' => $this->is_router_enabled() ? 'yes' : 'no',
            'routes' => self::get_tracking_routes(),
            'event_map' => $event_map,
            'checkout_payload' => $this->build_checkout_payload(),
            'purchase_payload' => $this->build_purchase_payload_for_browser(),
            'async' => array(
                'enabled' => $this->is_async_enabled() ? 'yes' : 'no',
                'action' => 'flexify_checkout_tracking_async',
                'nonce' => wp_create_nonce( 'flexify_checkout_tracking_async' ),
            ),
        );

        return $params;
    }


    /**
     * Server-side purchase router with deduplication.
     *
     * @since 5.5.0
     * @param int $order_id Order id.
     * @return void
     */
    public function maybe_emit_server_purchase( $order_id ) {
        $order_id = absint( $order_id );

        if ( ! $order_id ) {
            return;
        }

        if ( ! $this->is_router_enabled() ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $already_sent = get_post_meta( $order_id, '_flexify_tracking_server_purchase_sent', true );

        if ( $already_sent === 'yes' ) {
            return;
        }

        $event_id = get_post_meta( $order_id, '_flexify_tracking_purchase_event_id', true );

        if ( empty( $event_id ) ) {
            $event_id = 'fc_purchase_' . $order_id . '_' . wp_generate_uuid4();
            update_post_meta( $order_id, '_flexify_tracking_purchase_event_id', $event_id );
        }

        $payload = $this->build_order_payload( $order, $event_id );

        /**
         * Fires when server-side purchase event is emitted by Flexify router.
         *
         * @since 5.5.0
         * @param array $payload Unified payload.
         * @param WC_Order $order Woo order.
         */
        do_action( 'Flexify_Checkout/Tracking/Server_Purchase', $payload, $order );

        update_post_meta( $order_id, '_flexify_tracking_server_purchase_sent', 'yes' );
        update_post_meta( $order_id, '_flexify_tracking_server_purchase_at', current_time( 'mysql' ) );
    }


    /**
     * Build checkout payload from cart/session.
     *
     * @since 5.5.0
     * @return array
     */
    private function build_checkout_payload() {
        $currency = get_woocommerce_currency();
        $value = 0;
        $items = array();

        if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
            $value = (float) WC()->cart->total;

            foreach ( WC()->cart->get_cart() as $cart_item ) {
                $product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
                $items[] = array(
                    'item_id' => isset( $cart_item['product_id'] ) ? (string) $cart_item['product_id'] : '',
                    'item_name' => $product ? $product->get_name() : '',
                    'quantity' => isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1,
                    'price' => isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : 0,
                );
            }
        }

        return array(
            'currency' => $currency,
            'value' => $value,
            'coupon' => ( function_exists( 'WC' ) && WC() && WC()->cart ) ? implode( ',', WC()->cart->get_applied_coupons() ) : '',
            'items' => $items,
            'customer' => array(
                'is_logged_in' => is_user_logged_in() ? 1 : 0,
            ),
            'meta' => array(
                'source' => 'flexify_checkout',
            ),
        );
    }


    /**
     * Build purchase payload for browser (thankyou page only).
     *
     * @since 5.5.0
     * @return array
     */
    private function build_purchase_payload_for_browser() {
        if ( ! is_order_received_page() ) {
            return array();
        }

        $order_id = absint( get_query_var( 'order-received' ) );
        $order = $order_id ? wc_get_order( $order_id ) : null;

        if ( ! $order ) {
            return array();
        }

        $event_id = get_post_meta( $order_id, '_flexify_tracking_purchase_event_id', true );

        if ( empty( $event_id ) ) {
            $event_id = 'fc_purchase_' . $order_id . '_' . wp_generate_uuid4();
            update_post_meta( $order_id, '_flexify_tracking_purchase_event_id', $event_id );
        }

        return $this->build_order_payload( $order, $event_id );
    }


    /**
     * Build normalized order payload.
     *
     * @since 5.5.0
     * @param \WC_Order $order Order.
     * @param string $event_id Event id.
     * @return array
     */
    private function build_order_payload( $order, $event_id ) {
        $items = array();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            $items[] = array(
                'item_id' => (string) $item->get_product_id(),
                'item_name' => $product ? $product->get_name() : $item->get_name(),
                'quantity' => (int) $item->get_quantity(),
                'price' => (float) $order->get_item_total( $item, false, false ),
            );
        }

        return array(
            'event_name' => 'fc_purchase',
            'event_id' => $event_id,
            'transaction_id' => (string) $order->get_id(),
            'currency' => $order->get_currency(),
            'value' => (float) $order->get_total(),
            'shipping' => (float) $order->get_shipping_total(),
            'tax' => (float) $order->get_total_tax(),
            'coupon' => implode( ',', $order->get_coupon_codes() ),
            'payment_type' => (string) $order->get_payment_method(),
            'shipping_tier' => (string) $order->get_shipping_method(),
            'customer' => array(
                'email' => (string) $order->get_billing_email(),
                'phone' => (string) $order->get_billing_phone(),
                'first_name' => (string) $order->get_billing_first_name(),
                'last_name' => (string) $order->get_billing_last_name(),
            ),
            'items' => $items,
            'meta' => array(
                'source' => 'flexify_checkout',
                'sent_from' => 'browser_and_server',
            ),
        );
    }


    /**
     * Default tracking routes map.
     *
     * @since 5.5.0
     * @return array
     */
    public static function get_tracking_routes() {
        $settings = get_option( 'flexify_checkout_settings', array() );
        $routes = isset( $settings['tracking_routes'] ) && is_array( $settings['tracking_routes'] ) ? $settings['tracking_routes'] : array();
        $destinations = apply_filters( 'Flexify_Checkout/Tracking/Destinations', self::DESTINATIONS );
        $destinations = is_array( $destinations ) ? array_values( array_unique( $destinations ) ) : self::DESTINATIONS;

        $default = array(
            'fc_begin_checkout' => array( 'data_layer' => 'yes', 'ga4' => 'yes', 'meta' => 'no', 'tiktok' => 'no', 'google_ads' => 'no' ),
            'fc_add_shipping_info' => array( 'data_layer' => 'yes', 'ga4' => 'yes', 'meta' => 'no', 'tiktok' => 'no', 'google_ads' => 'no' ),
            'fc_add_payment_info' => array( 'data_layer' => 'yes', 'ga4' => 'yes', 'meta' => 'no', 'tiktok' => 'no', 'google_ads' => 'no' ),
            'fc_purchase' => array( 'data_layer' => 'yes', 'ga4' => 'yes', 'meta' => 'yes', 'tiktok' => 'yes', 'google_ads' => 'no' ),
        );

        $default = apply_filters( 'Flexify_Checkout/Tracking/Default_Routes', $default, $destinations );
        $merged = wp_parse_args( $routes, $default );

        foreach ( $merged as $event_name => $event_routes ) {
            if ( ! is_array( $event_routes ) ) {
                $event_routes = array();
            }

            foreach ( $destinations as $destination ) {
                if ( ! isset( $event_routes[ $destination ] ) ) {
                    $event_routes[ $destination ] = 'no';
                }
            }

            $merged[ $event_name ] = $event_routes;
        }

        return apply_filters( 'Flexify_Checkout/Tracking/Routes', $merged, $default, $destinations );
    }


    /**
     * Handle async tracking event ingestion from checkout frontend.
     *
     * @since 5.5.0
     * @return void
     */
    public function ajax_tracking_async_callback() {
        check_ajax_referer( 'flexify_checkout_tracking_async', 'nonce' );

        if ( ! $this->is_async_enabled() ) {
            wp_send_json_success( array( 'skipped' => 'disabled' ) );
        }

        $event_name = isset( $_POST['event_name'] ) ? sanitize_text_field( wp_unslash( $_POST['event_name'] ) ) : '';
        $raw_payload = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : array();
        $payload = $this->normalize_payload( $raw_payload, $event_name );

        $allowed_events = apply_filters( 'Flexify_Checkout/Tracking/Events', self::EVENTS );
        $allowed_events = is_array( $allowed_events ) ? $allowed_events : self::EVENTS;

        if ( empty( $event_name ) || ! in_array( $event_name, $allowed_events, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid event' ) );
        }

        if ( empty( $payload['event_id'] ) ) {
            wp_send_json_error( array( 'message' => 'Missing event id' ) );
        }

        if ( $this->is_duplicate_event( $payload['event_id'] ) ) {
            wp_send_json_success( array( 'skipped' => 'duplicate' ) );
        }

        $results = $this->dispatch_to_platforms( $event_name, $payload );
        wp_send_json_success( array( 'results' => $results ) );
    }


    /**
     * Checks if async integration mode is enabled.
     *
     * @since 5.5.0
     * @return bool
     */
    private function is_async_enabled() {
        if ( ! License::is_valid() ) {
            return false;
        }

        if ( ! $this->is_router_enabled() ) {
            return false;
        }

        $settings = $this->get_tracking_integrations_settings();
        return isset( $settings['enabled'] ) && $settings['enabled'] === 'yes';
    }


    /**
     * Checks if tracking router is enabled.
     *
     * Compatibility rules:
     * - Legacy key `tracking_router_enabled=yes` keeps router enabled.
     * - Pro tracking module toggle also enables router when valid license is active.
     *
     * @since 5.5.0
     * @return bool
     */
    private function is_router_enabled() {
        if ( Admin_Options::get_setting( 'tracking_router_enabled' ) === 'yes' ) {
            return true;
        }

        if ( ! License::is_valid() ) {
            return false;
        }

        $settings = $this->get_tracking_integrations_settings();
        
        return isset( $settings['enabled'] ) && $settings['enabled'] === 'yes';
    }


    /**
     * Get tracking integrations settings with defaults.
     *
     * @since 5.5.0
     * @return array
     */
    private function get_tracking_integrations_settings() {
        $settings = Admin_Options::get_setting( 'tracking_integrations' );
        $settings = is_array( $settings ) ? $settings : array();

        $default = array(
            'enabled' => 'no',
            'ga4' => array(
                'enabled' => 'no',
                'measurement_id' => '',
                'api_secret' => '',
            ),
            'google_ads' => array(
                'enabled' => 'no',
                'conversion_id' => '',
                'conversion_label' => '',
            ),
            'meta' => array(
                'enabled' => 'no',
                'pixel_id' => '',
                'access_token' => '',
                'test_event_code' => '',
            ),
        );

        return wp_parse_args( $settings, $default );
    }


    /**
     * Normalize payload from request.
     *
     * @since 5.5.0
     * @param mixed $raw_payload Raw payload.
     * @param string $event_name Event name.
     * @return array
     */
    private function normalize_payload( $raw_payload, $event_name ) {
        if ( is_string( $raw_payload ) ) {
            $decoded = json_decode( $raw_payload, true );
            $raw_payload = is_array( $decoded ) ? $decoded : array();
        }

        if ( ! is_array( $raw_payload ) ) {
            $raw_payload = array();
        }

        $raw_payload['event_name'] = $event_name;

        if ( empty( $raw_payload['event_id'] ) ) {
            $raw_payload['event_id'] = $event_name . '_' . wp_generate_uuid4();
        }

        return $raw_payload;
    }


    /**
     * Check whether event id was already consumed recently.
     *
     * @since 5.5.0
     * @param string $event_id Event id.
     * @return bool
     */
    private function is_duplicate_event( $event_id ) {
        $key = 'flexify_track_evt_' . md5( (string) $event_id );
        $exists = get_transient( $key );

        if ( $exists ) {
            return true;
        }

        set_transient( $key, '1', 15 * MINUTE_IN_SECONDS );
        return false;
    }


    /**
     * Dispatchs event to enabled platforms.
     *
     * @since 5.5.0
     * @param string $event_name Event name.
     * @param array $payload Event payload.
     * @return array
     */
    private function dispatch_to_platforms( $event_name, $payload ) {
        $settings = $this->get_tracking_integrations_settings();
        $results = array();
        $handlers = apply_filters( 'Flexify_Checkout/Tracking/Platform_Handlers', array(
            'ga4' => array( $this, 'send_to_ga4' ),
            'google_ads' => array( $this, 'send_to_google_ads' ),
            'meta' => array( $this, 'send_to_meta' ),
        ), $event_name, $payload, $settings, $this );

        if ( ! is_array( $handlers ) ) {
            $handlers = array();
        }

        foreach ( $handlers as $destination => $handler ) {
            if ( ! is_callable( $handler ) ) {
                continue;
            }

            if ( ! $this->can_route_to( $event_name, $destination ) ) {
                continue;
            }

            $is_enabled = $this->is_destination_enabled( $destination, $settings, $event_name, $payload );

            if ( ! $is_enabled ) {
                continue;
            }

            $platform_settings = ( isset( $settings[ $destination ] ) && is_array( $settings[ $destination ] ) ) ? $settings[ $destination ] : array();
            $results[ $destination ] = call_user_func( $handler, $event_name, $payload, $platform_settings );
        }

        $results = apply_filters( 'Flexify_Checkout/Tracking/Dispatch_Results', $results, $event_name, $payload, $settings, $handlers, $this );

        return $results;
    }


    /**
     * Check whether destination is enabled for dispatch.
     *
     * @since 5.5.0
     * @param string $destination Destination key.
     * @param array $settings Tracking settings.
     * @param string $event_name Event name.
     * @param array $payload Event payload.
     * @return bool
     */
    private function is_destination_enabled( $destination, $settings, $event_name, $payload ) {
        $enabled = isset( $settings[ $destination ]['enabled'] ) && $settings[ $destination ]['enabled'] === 'yes';

        return (bool) apply_filters( 'Flexify_Checkout/Tracking/Is_Destination_Enabled', $enabled, $destination, $settings, $event_name, $payload, $this );
    }


    /**
     * Route check for destination.
     *
     * @since 5.5.0
     * @param string $event_name Event name.
     * @param string $destination Destination key.
     * @return bool
     */
    private function can_route_to( $event_name, $destination ) {
        $routes = self::get_tracking_routes();
        return isset( $routes[ $event_name ][ $destination ] ) && $routes[ $event_name ][ $destination ] === 'yes';
    }


    /**
     * Send event to GA4 Measurement Protocol.
     *
     * @since 5.5.0
     * @param string $event_name Event name.
     * @param array $payload Event payload.
     * @param array $settings GA4 settings.
     * @return array
     */
    private function send_to_ga4( $event_name, $payload, $settings ) {
        $measurement_id = isset( $settings['measurement_id'] ) ? trim( (string) $settings['measurement_id'] ) : '';
        $api_secret = isset( $settings['api_secret'] ) ? trim( (string) $settings['api_secret'] ) : '';

        if ( empty( $measurement_id ) || empty( $api_secret ) ) {
            return array( 'status' => 'skipped', 'reason' => 'missing_credentials' );
        }

        $params = $payload;
        unset( $params['event_name'] );

        $body = array(
            'client_id' => ! empty( $payload['event_id'] ) ? (string) $payload['event_id'] : wp_generate_uuid4(),
            'events' => array(
                array(
                    'name' => $this->get_external_event_name( $event_name, 'ga4' ),
                    'params' => $params,
                ),
            ),
        );

        $url = add_query_arg(
            array(
                'measurement_id' => rawurlencode( $measurement_id ),
                'api_secret' => rawurlencode( $api_secret ),
            ),
            'https://www.google-analytics.com/mp/collect'
        );

        $response = wp_remote_post( $url, array(
            'timeout' => 3,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body' => wp_json_encode( $body ),
        ) );

        return $this->format_remote_result( 'ga4', $response );
    }


    /**
     * Send event to Google Ads endpoint.
     *
     * @since 5.5.0
     * @param string $event_name Event name.
     * @param array $payload Event payload.
     * @param array $settings Google Ads settings.
     * @return array
     */
    private function send_to_google_ads( $event_name, $payload, $settings ) {
        $conversion_id = isset( $settings['conversion_id'] ) ? trim( (string) $settings['conversion_id'] ) : '';
        $conversion_label = isset( $settings['conversion_label'] ) ? trim( (string) $settings['conversion_label'] ) : '';

        if ( empty( $conversion_id ) || empty( $conversion_label ) ) {
            return array( 'status' => 'skipped', 'reason' => 'missing_credentials' );
        }

        $clean_id = preg_replace( '/[^0-9]/', '', $conversion_id );

        if ( empty( $clean_id ) ) {
            return array( 'status' => 'skipped', 'reason' => 'invalid_conversion_id' );
        }

        $url = add_query_arg(
            array(
                'label' => $conversion_label,
                'value' => isset( $payload['value'] ) ? (float) $payload['value'] : 0,
                'currency_code' => isset( $payload['currency'] ) ? sanitize_text_field( $payload['currency'] ) : get_woocommerce_currency(),
                'guid' => 'ON',
                'script' => 0,
                'event_id' => isset( $payload['event_id'] ) ? sanitize_text_field( $payload['event_id'] ) : '',
            ),
            'https://www.googleadservices.com/pagead/conversion/' . $clean_id . '/'
        );

        $response = wp_remote_get( $url, array( 'timeout' => 3 ) );

        return $this->format_remote_result( 'google_ads', $response );
    }


    /**
     * Send event to Meta Conversions API.
     *
     * @since 5.5.0
     * @param string $event_name Event name.
     * @param array $payload Event payload.
     * @param array $settings Meta settings.
     * @return array
     */
    private function send_to_meta( $event_name, $payload, $settings ) {
        $pixel_id = isset( $settings['pixel_id'] ) ? trim( (string) $settings['pixel_id'] ) : '';
        $access_token = isset( $settings['access_token'] ) ? trim( (string) $settings['access_token'] ) : '';

        if ( empty( $pixel_id ) || empty( $access_token ) ) {
            return array( 'status' => 'skipped', 'reason' => 'missing_credentials' );
        }

        $customer = isset( $payload['customer'] ) && is_array( $payload['customer'] ) ? $payload['customer'] : array();
        $user_data = array_filter( array(
            'em' => isset( $customer['email'] ) ? $this->hash_user_data( $customer['email'] ) : '',
            'ph' => isset( $customer['phone'] ) ? $this->hash_user_data( $customer['phone'] ) : '',
            'fn' => isset( $customer['first_name'] ) ? $this->hash_user_data( $customer['first_name'] ) : '',
            'ln' => isset( $customer['last_name'] ) ? $this->hash_user_data( $customer['last_name'] ) : '',
        ) );

        $custom_data = array(
            'currency' => isset( $payload['currency'] ) ? sanitize_text_field( $payload['currency'] ) : get_woocommerce_currency(),
            'value' => isset( $payload['value'] ) ? (float) $payload['value'] : 0,
        );

        if ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
            $custom_data['contents'] = $payload['items'];
        }

        $body = array(
            'data' => array(
                array(
                    'event_name' => $this->get_external_event_name( $event_name, 'meta' ),
                    'event_time' => time(),
                    'event_id' => isset( $payload['event_id'] ) ? sanitize_text_field( $payload['event_id'] ) : '',
                    'action_source' => 'website',
                    'event_source_url' => home_url( add_query_arg( null, null ) ),
                    'user_data' => $user_data,
                    'custom_data' => $custom_data,
                ),
            ),
        );

        if ( ! empty( $settings['test_event_code'] ) ) {
            $body['test_event_code'] = sanitize_text_field( $settings['test_event_code'] );
        }

        $endpoint = 'https://graph.facebook.com/v20.0/' . rawurlencode( $pixel_id ) . '/events?access_token=' . rawurlencode( $access_token );
        $response = wp_remote_post( $endpoint, array(
            'timeout' => 4,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body' => wp_json_encode( $body ),
        ) );

        return $this->format_remote_result( 'meta', $response );
    }


    /**
     * Format remote call result and log only when debug mode is enabled.
     *
     * @since 5.5.0
     * @param string $platform Platform name.
     * @param mixed $response Response.
     * @return array
     */
    private function format_remote_result( $platform, $response ) {
        if ( is_wp_error( $response ) ) {
            $this->debug_log( $platform . ' error: ' . $response->get_error_message(), 'error' );
            return array( 'status' => 'error', 'message' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code >= 200 && $code < 300 ) {
            $this->debug_log( $platform . ' success [' . $code . ']', 'debug' );
            return array( 'status' => 'success', 'code' => $code );
        }

        $this->debug_log( $platform . ' failed [' . $code . '] ' . $body, 'warning' );
        return array( 'status' => 'error', 'code' => $code );
    }


    /**
     * Get external mapped event name.
     *
     * @since 5.5.0
     * @param string $event_name Internal event.
     * @param string $destination Destination.
     * @return string
     */
    private function get_external_event_name( $event_name, $destination ) {
        $event_map = $this->get_event_map();
        $external_name = isset( $event_map[ $event_name ][ $destination ] ) ? $event_map[ $event_name ][ $destination ] : $event_name;

        return apply_filters( 'Flexify_Checkout/Tracking/External_Event_Name', $external_name, $event_name, $destination, $event_map );
    }


    /**
     * Hash user data for Meta CAPI.
     *
     * @since 5.5.0
     * @param string $value Raw value.
     * @return string
     */
    private function hash_user_data( $value ) {
        $normalized = strtolower( trim( (string) $value ) );
        return hash( 'sha256', $normalized );
    }


    /**
     * Logs only in debug mode.
     *
     * @since 5.5.0
     * @param string $message Message.
     * @param string $level Level.
     * @return void
     */
    private function debug_log( $message, $level = 'debug' ) {
        if ( Admin_Options::get_setting( 'enable_debug_mode' ) !== 'yes' ) {
            return;
        }

        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->log( $level, $message, array( 'source' => 'flexify-tracking-router' ) );
        }
    }


}
