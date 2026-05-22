<?php

namespace MeuMouse\Flexify_Checkout\Tracking;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify internal tracking router.
 *
 * @since 5.4.3
 * @package MeuMouse.com
 */
class Router {

    /**
     * Supported internal events.
     *
     * @since 5.4.3
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
     * @since 5.4.3
     * @var array
     */
    const DESTINATIONS = array( 'data_layer', 'ga4', 'meta', 'tiktok', 'google_ads' );


    /**
     * Construct function.
     *
     * @since 5.4.3
     * @return void
     */
    public function __construct() {
        add_filter( 'Flexify_Checkout/Assets/Script_Data', array( $this, 'append_script_data' ), 20 );
        add_action( 'woocommerce_payment_complete', array( $this, 'maybe_emit_server_purchase' ), 20 );
        add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_emit_server_purchase' ), 20 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_emit_server_purchase' ), 20 );
    }


    /**
     * Append tracking config and payloads to frontend script data.
     *
     * @since 5.4.3
     * @param array $params Script data.
     * @return array
     */
    public function append_script_data( $params ) {
        $params['tracking_router'] = array(
            'enabled' => Admin_Options::get_setting( 'tracking_router_enabled' ) === 'yes' ? 'yes' : 'no',
            'routes' => self::get_tracking_routes(),
            'event_map' => array(
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
            ),
            'checkout_payload' => $this->build_checkout_payload(),
            'purchase_payload' => $this->build_purchase_payload_for_browser(),
        );

        return $params;
    }


    /**
     * Server-side purchase router with deduplication.
     *
     * @since 5.4.3
     * @param int $order_id Order id.
     * @return void
     */
    public function maybe_emit_server_purchase( $order_id ) {
        $order_id = absint( $order_id );

        if ( ! $order_id ) {
            return;
        }

        if ( Admin_Options::get_setting( 'tracking_router_enabled' ) !== 'yes' ) {
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
         * @since 5.4.3
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
     * @since 5.4.3
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
     * @since 5.4.3
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
     * @since 5.4.3
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
     * @since 5.4.3
     * @return array
     */
    public static function get_tracking_routes() {
        $settings = get_option( 'flexify_checkout_settings', array() );
        $routes = isset( $settings['tracking_routes'] ) && is_array( $settings['tracking_routes'] ) ? $settings['tracking_routes'] : array();

        $default = array(
            'fc_begin_checkout' => array( 'data_layer' => 'yes', 'ga4' => 'yes', 'meta' => 'no', 'tiktok' => 'no', 'google_ads' => 'no' ),
            'fc_add_shipping_info' => array( 'data_layer' => 'yes', 'ga4' => 'yes', 'meta' => 'no', 'tiktok' => 'no', 'google_ads' => 'no' ),
            'fc_add_payment_info' => array( 'data_layer' => 'yes', 'ga4' => 'yes', 'meta' => 'no', 'tiktok' => 'no', 'google_ads' => 'no' ),
            'fc_purchase' => array( 'data_layer' => 'yes', 'ga4' => 'yes', 'meta' => 'yes', 'tiktok' => 'yes', 'google_ads' => 'no' ),
        );

        return wp_parse_args( $routes, $default );
    }


}
