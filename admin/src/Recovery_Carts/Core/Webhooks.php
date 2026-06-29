<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Core\Webhooks\Dispatcher;
use MeuMouse\Flexify_Checkout\Core\Webhooks\Payload\Cart_Payload_Provider;
use MeuMouse\Flexify_Checkout\Core\Webhooks\Payload\Order_Payload_Provider;
use MeuMouse\Flexify_Checkout\Core\Webhooks\Webhook_Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Recovery cart webhook events.
 *
 * Since 6.0.0 webhooks are a global, checkout-wide feature (see
 * MeuMouse\Flexify_Checkout\Core\Webhooks). This class no longer owns the
 * transport or the storage — it only:
 *
 *   1. Registers the 7 recovery events into the global Event_Registry through
 *      the 'Flexify_Checkout/Webhooks/Registered_Events' filter (category
 *      "recovery"), so they appear in the global Webhooks tab.
 *   2. Binds the recovery actions and builds rich cart/order payloads, then
 *      delegates delivery to the global Dispatcher.
 *
 * The legacy 'Flexify_Checkout/Recovery_Carts/Webhooks/*' filters are still
 * applied for backward compatibility with third-party integrations.
 *
 * @since 1.3.2
 * @version 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Webhooks {

    /**
     * Constructor
     *
     * @since 1.3.2
     * @version 6.0.0
     */
    public function __construct() {
        // Register recovery events into the global webhooks catalog.
        add_filter( 'Flexify_Checkout/Webhooks/Registered_Events', array( $this, 'register_events' ) );

        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Abandoned', array( $this, 'handle_cart_abandoned' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Abandoned_Manually', array( $this, 'handle_cart_abandoned' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Lost', array( $this, 'handle_cart_lost' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Lost_Manually', array( $this, 'handle_cart_lost' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Recovered', array( $this, 'handle_cart_recovered' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Recovered_Manually', array( $this, 'handle_cart_recovered_manual' ), 10, 1 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Purchased_Cart', array( $this, 'handle_cart_purchased' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Order_Abandoned', array( $this, 'handle_order_abandoned' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Lead_Collected', array( $this, 'handle_lead_collected' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Checkout_Lead_Collected', array( $this, 'handle_checkout_lead_collected' ), 10, 2 );
        add_action( 'Flexify_Checkout/Recovery_Carts/Follow_Up_Message_Sent', array( $this, 'handle_follow_up_sent' ), 10, 5 );
    }


    /**
     * The 7 recovery events as registered in the catalog.
     *
     * Static and side-effect free so it can be read without instantiating the
     * class (the constructor registers hooks; instantiating it just to read the
     * catalog would double-register them).
     *
     * @since 6.0.0
     * @return array<string,array<string,string>>
     */
    private static function recovery_events() {
        return array(
            'cart_abandoned' => array(
                'label' => esc_html__( 'Abandoned cart', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when a cart is marked as abandoned.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'recovery',
                'hook' => 'Flexify_Checkout/Recovery_Carts/Cart_Abandoned',
            ),
            'order_abandoned' => array(
                'label' => esc_html__( 'Abandoned order', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Run when an order remains pending and is marked as abandoned.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'recovery',
                'hook' => 'Flexify_Checkout/Recovery_Carts/Order_Abandoned',
            ),
            'cart_lost' => array(
                'label' => esc_html__( 'Lost cart', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered after the follow-ups end when the customer has not completed the order.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'recovery',
                'hook' => 'Flexify_Checkout/Recovery_Carts/Cart_Lost',
            ),
            'cart_recovered' => array(
                'label' => esc_html__( 'Recovered cart', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Run when an abandoned cart generates a completed order.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'recovery',
                'hook' => 'Flexify_Checkout/Recovery_Carts/Cart_Recovered',
            ),
            'purchased_cart' => array(
                'label' => esc_html__( 'Completed order', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when an order is completed directly by the customer.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'recovery',
                'hook' => 'Flexify_Checkout/Recovery_Carts/Purchased_Cart',
            ),
            'lead_collected' => array(
                'label' => esc_html__( 'Lead captured', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered when contact data is collected via modal or checkout.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'recovery',
                'hook' => 'Flexify_Checkout/Recovery_Carts/Lead_Collected',
            ),
            'follow_up_sent' => array(
                'label' => esc_html__( 'Follow-up sent', 'flexify-checkout-for-woocommerce' ),
                'description' => esc_html__( 'Triggered whenever a follow-up message is sent.', 'flexify-checkout-for-woocommerce' ),
                'category' => 'recovery',
                'hook' => 'Flexify_Checkout/Recovery_Carts/Follow_Up_Message_Sent',
            ),
        );
    }


    /**
     * Inject the recovery events into the global webhook catalog.
     *
     * @since 6.0.0
     * @param array $events Existing event catalog.
     * @return array
     */
    public function register_events( $events ) {
        if ( ! is_array( $events ) ) {
            $events = array();
        }

        return array_merge( $events, self::recovery_events() );
    }


    /**
     * Backward-compatible accessor for the recovery events (key + label/desc).
     *
     * Kept so third-party code that called Webhooks::get_registered_events()
     * keeps working; the global Event_Registry is the source of truth now.
     *
     * @since 1.3.2
     * @version 6.0.0
     * @return array
     */
    public static function get_registered_events() {
        $events = self::recovery_events();

        /**
         * Legacy filter, retained for backward compatibility.
         *
         * @since 1.3.2
         * @param array $events Recovery event definitions.
         */
        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Webhooks/Registered_Events', $events );
    }


    /**
     * Dispatch a recovery webhook, applying the legacy recovery payload filters
     * before handing the payload to the global Dispatcher.
     *
     * @since 6.0.0
     * @param string $event_key Event key.
     * @param array  $payload Payload to send.
     * @return void
     */
    private function dispatch( $event_key, array $payload ) {
        /**
         * Legacy recovery payload filters, retained for backward compatibility.
         *
         * @since 1.3.2
         */
        $payload = apply_filters( 'Flexify_Checkout/Recovery_Carts/Webhooks/Payload', $payload, $event_key );
        $payload = apply_filters( 'Flexify_Checkout/Recovery_Carts/Webhooks/Payload/' . $event_key, $payload );

        Dispatcher::dispatch( $event_key, $payload );
    }


    /**
     * Build the recovery cart payload through the global provider.
     *
     * @since 6.0.0
     * @param int $cart_id Cart ID.
     * @return array
     */
    private function cart_payload( $cart_id ) {
        return ( new Cart_Payload_Provider() )->build( array( 'cart_id' => absint( $cart_id ) ) );
    }


    /**
     * Build the order payload through the global provider.
     *
     * @since 6.0.0
     * @param int|null $order_id Order ID.
     * @return array
     */
    private function order_payload( $order_id ) {
        return ( new Order_Payload_Provider() )->build( array( 'order_id' => $order_id ? absint( $order_id ) : 0 ) );
    }


    /**
     * Handle cart abandoned webhook.
     *
     * @since 1.3.2
     * @param int $cart_id Cart ID.
     * @return void
     */
    public function handle_cart_abandoned( $cart_id ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return;
        }

        $this->dispatch( 'cart_abandoned', array(
            'event' => 'cart_abandoned',
            'triggered_at' => Webhook_Helpers::current_time_iso(),
            'cart' => $this->cart_payload( $cart_id ),
        ) );
    }


    /**
     * Handle cart lost webhook.
     *
     * @since 1.3.2
     * @param int $cart_id Cart ID.
     * @return void
     */
    public function handle_cart_lost( $cart_id ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return;
        }

        $this->dispatch( 'cart_lost', array(
            'event' => 'cart_lost',
            'triggered_at' => Webhook_Helpers::current_time_iso(),
            'cart' => $this->cart_payload( $cart_id ),
        ) );
    }


    /**
     * Handle cart recovered webhook.
     *
     * @since 1.3.2
     * @param int $cart_id Cart ID.
     * @param int|null $order_id WooCommerce order ID.
     * @return void
     */
    public function handle_cart_recovered( $cart_id, $order_id = null ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return;
        }

        $this->dispatch( 'cart_recovered', array(
            'event' => 'cart_recovered',
            'triggered_at' => Webhook_Helpers::current_time_iso(),
            'cart' => $this->cart_payload( $cart_id ),
            'order' => $this->order_payload( $order_id ),
        ) );
    }


    /**
     * Handle cart recovered manually webhook.
     *
     * @since 1.3.2
     * @param int $cart_id Cart ID.
     * @return void
     */
    public function handle_cart_recovered_manual( $cart_id ) {
        $this->handle_cart_recovered( $cart_id, null );
    }


    /**
     * Handle purchased cart webhook.
     *
     * @since 1.3.2
     * @param int $cart_id Cart ID.
     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public function handle_cart_purchased( $cart_id, $order_id ) {
        $cart_id = absint( $cart_id );
        $order_id = absint( $order_id );

        if ( ! $cart_id || ! $order_id ) {
            return;
        }

        $this->dispatch( 'purchased_cart', array(
            'event' => 'purchased_cart',
            'triggered_at' => Webhook_Helpers::current_time_iso(),
            'cart' => $this->cart_payload( $cart_id ),
            'order' => $this->order_payload( $order_id ),
        ) );
    }


    /**
     * Handle order abandoned webhook.
     *
     * @since 1.3.2
     * @param int $order_id WooCommerce order ID.
     * @param int $cart_id Cart ID.
     * @return void
     */
    public function handle_order_abandoned( $order_id, $cart_id ) {
        $cart_id = absint( $cart_id );
        $order_id = absint( $order_id );

        if ( ! $cart_id ) {
            return;
        }

        $this->dispatch( 'order_abandoned', array(
            'event' => 'order_abandoned',
            'triggered_at' => Webhook_Helpers::current_time_iso(),
            'cart' => $this->cart_payload( $cart_id ),
            'order' => $this->order_payload( $order_id ),
        ) );
    }


    /**
     * Handle lead collected webhook (modal source).
     *
     * @since 1.3.2
     * @param int $cart_id Cart ID.
     * @param array $lead_data Lead data.
     * @return void
     */
    public function handle_lead_collected( $cart_id, $lead_data ) {
        $this->dispatch_lead( $cart_id, $lead_data, 'modal' );
    }


    /**
     * Handle checkout lead collected webhook (checkout source).
     *
     * @since 1.3.2
     * @param int $cart_id Cart ID.
     * @param array $lead_data Lead data.
     * @return void
     */
    public function handle_checkout_lead_collected( $cart_id, $lead_data ) {
        $this->dispatch_lead( $cart_id, $lead_data, 'checkout' );
    }


    /**
     * Shared lead dispatch for both modal and checkout sources.
     *
     * @since 6.0.0
     * @param int $cart_id Cart ID.
     * @param array $lead_data Lead data.
     * @param string $source Lead source.
     * @return void
     */
    private function dispatch_lead( $cart_id, $lead_data, $source ) {
        $cart_id = absint( $cart_id );

        if ( ! $cart_id ) {
            return;
        }

        $this->dispatch( 'lead_collected', array(
            'event' => 'lead_collected',
            'source' => $source,
            'triggered_at' => Webhook_Helpers::current_time_iso(),
            'cart' => $this->cart_payload( $cart_id ),
            'lead' => is_array( $lead_data ) ? Webhook_Helpers::sanitize_array( $lead_data ) : array(),
        ) );
    }


    /**
     * Handle follow up webhook.
     *
     * @since 1.3.2
     * @param int $cart_id Cart ID.
     * @param string $event_key Follow up event key.
     * @param string $message Message sent.
     * @param array $channels Channels used.
     * @param array $event_settings Original event settings.
     * @return void
     */
    public function handle_follow_up_sent( $cart_id, $event_key, $message, $channels, $event_settings ) {
        $cart_id = absint( $cart_id );
        $event_key = sanitize_key( $event_key );

        if ( ! $cart_id || ! $event_key ) {
            return;
        }

        $channels = array_filter( array_map( 'sanitize_key', (array) $channels ) );
        $event_settings = is_array( $event_settings ) ? Webhook_Helpers::sanitize_array( $event_settings ) : array();

        $this->dispatch( 'follow_up_sent', array(
            'event' => 'follow_up_sent',
            'triggered_at' => Webhook_Helpers::current_time_iso(),
            'cart' => $this->cart_payload( $cart_id ),
            'follow_up' => array(
                'event_key' => $event_key,
                'title' => $event_settings['title'] ?? '',
                'message' => is_string( $message ) ? $message : '',
                'channels' => array_values( $channels ),
                'delay_time' => $event_settings['delay_time'] ?? '',
                'delay_type' => $event_settings['delay_type'] ?? '',
                'coupon' => $event_settings['coupon'] ?? array(),
            ),
        ) );
    }
}
