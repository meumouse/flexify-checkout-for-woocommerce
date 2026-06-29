<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Central catalog of webhook events for the whole checkout.
 *
 * Any feature registers its events here through the
 * 'Flexify_Checkout/Webhooks/Registered_Events' filter (the cart recovery
 * feature injects its 7 events this way), so the catalog is no longer tied to
 * the recovery module. Each event declares its category, a human label and the
 * source hook (informational, the actual binding lives in Bootstrap_Webhooks
 * or in the feature that owns the event).
 *
 * Event shape:
 *   key => [ label, description, category, hook ]
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks
 * @author MeuMouse.com
 */
class Event_Registry {

    /**
     * Ordered event categories.
     *
     * @since 6.0.0
     * @return array<string,string> category key => label
     */
    public static function get_categories() {
        $categories = array(
            'order'    => esc_html__( 'Order', 'flexify-checkout-for-woocommerce' ),
            'checkout' => esc_html__( 'Checkout', 'flexify-checkout-for-woocommerce' ),
            'tracking' => esc_html__( 'Tracking', 'flexify-checkout-for-woocommerce' ),
            'recovery' => esc_html__( 'Recovery', 'flexify-checkout-for-woocommerce' ),
            'account'  => esc_html__( 'Account', 'flexify-checkout-for-woocommerce' ),
        );

        /**
         * Filter the webhook event categories.
         *
         * @since 6.0.0
         * @param array $categories Ordered category key => label map.
         */
        return apply_filters( 'Flexify_Checkout/Webhooks/Categories', $categories );
    }


    /**
     * Built-in, non-recovery event catalog.
     *
     * @since 6.0.0
     * @return array<string,array<string,string>>
     */
    private static function core_events() {
        return array(
            // Pedido
            'order_created' => array(
                'label' => esc_html__( 'Order created', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when an order is created at checkout.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'order',
                'hook' => 'woocommerce_checkout_order_processed',
            ),
            'payment_complete' => array(
                'label' => esc_html__( 'Payment completed', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when an order\'s payment is confirmed.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'order',
                'hook' => 'woocommerce_payment_complete',
            ),
            'order_processing' => array(
                'label' => esc_html__( 'Order processing', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when an order enters the "processing" status.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'order',
                'hook' => 'woocommerce_order_status_processing',
            ),
            'order_completed' => array(
                'label' => esc_html__( 'Completed order', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when an order enters the "completed" status.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'order',
                'hook' => 'woocommerce_order_status_completed',
            ),
            'order_cancelled' => array(
                'label' => esc_html__( 'Cancelled order', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when an order enters the "cancelled" status.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'order',
                'hook' => 'woocommerce_order_status_cancelled',
            ),
            'order_refunded' => array(
                'label' => esc_html__( 'Refunded order', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when an order enters the "refunded" status.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'order',
                'hook' => 'woocommerce_order_status_refunded',
            ),
            'order_status_changed' => array(
                'label' => esc_html__( 'Order status changed', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered on any order status change, with the source and target statuses.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'order',
                'hook' => 'woocommerce_order_status_changed',
            ),

            // Checkout
            'thankyou' => array(
                'label' => esc_html__( 'Thank-you page', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered once when the customer reaches the order thank-you page.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'checkout',
                'hook' => 'woocommerce_thankyou',
            ),
            'countdown_expired' => array(
                'label' => esc_html__( 'Countdown expired', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when the checkout countdown expires and the session is destroyed.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'checkout',
                'hook' => 'Flexify_Checkout/Countdown/Session_Destroyed',
            ),

            // Rastreamento
            'purchase' => array(
                'label' => esc_html__( 'Purchase (tracking)', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered on the purchase event of server-side tracking.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'tracking',
                'hook' => 'Flexify_Checkout/Tracking/Server_Purchase',
            ),
            'begin_checkout' => array(
                'label' => esc_html__( 'Checkout start', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when the customer starts the checkout.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'tracking',
                'hook' => 'Flexify_Checkout/Tracking/Server_Event',
            ),
            'add_shipping_info' => array(
                'label' => esc_html__( 'Delivery information', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when the customer provides the shipping details.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'tracking',
                'hook' => 'Flexify_Checkout/Tracking/Server_Event',
            ),
            'add_payment_info' => array(
                'label' => esc_html__( 'Payment information', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when the customer provides the payment details.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'tracking',
                'hook' => 'Flexify_Checkout/Tracking/Server_Event',
            ),

            // Conta
            'whatsapp_verified' => array(
                'label' => esc_html__( 'WhatsApp verified', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when a customer verifies their WhatsApp number at checkout login.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'account',
                'hook' => 'Flexify_Checkout/React_Checkout/WhatsApp_Logged_In',
            ),
        );
    }


    /**
     * Full catalog of registered events.
     *
     * @since 6.0.0
     * @return array<string,array<string,string>>
     */
    public static function get_events() {
        $events = self::core_events();

        /**
         * Filter the registered webhook events. Features (e.g. cart recovery)
         * append their own events here.
         *
         * @since 6.0.0
         * @param array $events Event key => definition map.
         */
        $events = apply_filters( 'Flexify_Checkout/Webhooks/Registered_Events', $events );

        return is_array( $events ) ? $events : array();
    }


    /**
     * Whether an event key exists in the catalog.
     *
     * @since 6.0.0
     * @param string $key Event key.
     * @return bool
     */
    public static function has_event( $key ) {
        $events = self::get_events();

        return isset( $events[ $key ] );
    }


    /**
     * Get a single event definition.
     *
     * @since 6.0.0
     * @param string $key Event key.
     * @return array<string,string>|null
     */
    public static function get_event( $key ) {
        $events = self::get_events();

        return isset( $events[ $key ] ) ? $events[ $key ] : null;
    }


    /**
     * Events bucketed by category, ready for REST/UI consumption.
     *
     * Returns an ordered list of categories, each with its events list. Empty
     * categories are skipped.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_events_grouped() {
        $categories = self::get_categories();
        $events = self::get_events();
        $grouped = array();

        foreach ( $categories as $cat_key => $cat_label ) {
            $bucket = array();

            foreach ( $events as $event_key => $event ) {
                $event_cat = isset( $event['category'] ) ? $event['category'] : 'order';

                if ( $event_cat !== $cat_key ) {
                    continue;
                }

                $bucket[] = array(
                    'key' => $event_key,
                    'label' => isset( $event['label'] ) ? $event['label'] : $event_key,
                    'description' => isset( $event['description'] ) ? $event['description'] : '',
                );
            }

            if ( ! empty( $bucket ) ) {
                $grouped[] = array(
                    'category' => $cat_key,
                    'label' => $cat_label,
                    'events' => $bucket,
                );
            }
        }

        return $grouped;
    }
}
