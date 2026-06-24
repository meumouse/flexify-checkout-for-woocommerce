<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks;

use MeuMouse\Flexify_Checkout\Core\Webhooks\Payload\Order_Payload_Provider;
use MeuMouse\Flexify_Checkout\Core\Webhooks\Payload\Generic_Payload_Provider;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Boots the global checkout webhooks: wires every non-recovery event to the
 * Dispatcher and runs the one-shot data migration.
 *
 * Must boot on BOTH admin and frontend/REST (order/payment hooks fire during
 * the checkout request), so it is registered as a core manual class in Init,
 * not as an admin-only class.
 *
 * Recovery events are NOT bound here — the recovery feature registers and
 * dispatches its own 7 events (see Recovery_Carts\Core\Webhooks).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks
 * @author MeuMouse.com
 */
class Bootstrap_Webhooks {

    /**
     * Constructor — register all global event bindings.
     *
     * @since 6.0.0
     */
    public function __construct() {
        // Resolve the debug flag once.
        new Dispatcher();

        // Pedido (order lifecycle)
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'handle_order_created' ), 20, 1 );
        add_action( 'woocommerce_payment_complete', array( $this, 'handle_payment_complete' ), 20, 1 );
        add_action( 'woocommerce_order_status_processing', array( $this, 'handle_order_processing' ), 20, 1 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'handle_order_completed' ), 20, 1 );
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'handle_order_cancelled' ), 20, 1 );
        add_action( 'woocommerce_order_status_refunded', array( $this, 'handle_order_refunded' ), 20, 1 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'handle_order_status_changed' ), 20, 3 );

        // Checkout
        add_action( 'woocommerce_thankyou', array( $this, 'handle_thankyou' ), 20, 1 );
        add_action( 'Flexify_Checkout/Countdown/Session_Destroyed', array( $this, 'handle_countdown_expired' ), 10, 4 );

        // Rastreamento (tracking)
        add_action( 'Flexify_Checkout/Tracking/Server_Purchase', array( $this, 'handle_tracking_purchase' ), 10, 2 );
        add_action( 'Flexify_Checkout/Tracking/Server_Event', array( $this, 'handle_tracking_event' ), 10, 2 );

        // Conta (account)
        add_action( 'Flexify_Checkout/React_Checkout/WhatsApp_Logged_In', array( $this, 'handle_whatsapp_verified' ), 10, 2 );

        // One-shot migration of legacy recovery webhooks into the unified option.
        add_action( 'admin_init', array( Webhook_Migration::class, 'maybe_migrate' ) );
    }


    /**
     * Whether at least one endpoint is configured for an event. Used to skip
     * building payloads (e.g. wc_get_order) when nothing is listening.
     *
     * @since 6.0.0
     * @param string $event_key Event key.
     * @return bool
     */
    protected function has_endpoints( $event_key ) {
        return ! empty( Webhook_Settings::get_event_webhooks( $event_key ) );
    }


    /**
     * Dispatch an order-based event by id.
     *
     * @since 6.0.0
     * @param string $event_key Event key.
     * @param int    $order_id Order id.
     * @param array  $extra Extra payload keys to merge.
     * @return void
     */
    protected function dispatch_order_event( $event_key, $order_id, array $extra = array() ) {
        if ( ! $this->has_endpoints( $event_key ) ) {
            return;
        }

        $order = ( new Order_Payload_Provider() )->build( array( 'order_id' => absint( $order_id ) ) );

        Dispatcher::dispatch( $event_key, array_merge( array( 'order' => $order ), $extra ) );
    }


    /**
     * woocommerce_checkout_order_processed handler.
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function handle_order_created( $order_id ) {
        $this->dispatch_order_event( 'order_created', $order_id );
    }


    /**
     * woocommerce_payment_complete handler.
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function handle_payment_complete( $order_id ) {
        $this->dispatch_order_event( 'payment_complete', $order_id );
    }


    /**
     * woocommerce_order_status_processing handler.
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function handle_order_processing( $order_id ) {
        $this->dispatch_order_event( 'order_processing', $order_id );
    }


    /**
     * woocommerce_order_status_completed handler.
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function handle_order_completed( $order_id ) {
        $this->dispatch_order_event( 'order_completed', $order_id );
    }


    /**
     * woocommerce_order_status_cancelled handler.
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function handle_order_cancelled( $order_id ) {
        $this->dispatch_order_event( 'order_cancelled', $order_id );
    }


    /**
     * woocommerce_order_status_refunded handler.
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function handle_order_refunded( $order_id ) {
        $this->dispatch_order_event( 'order_refunded', $order_id );
    }


    /**
     * woocommerce_order_status_changed handler.
     *
     * @since 6.0.0
     * @param int    $order_id Order id.
     * @param string $from Old status.
     * @param string $to New status.
     * @return void
     */
    public function handle_order_status_changed( $order_id, $from, $to ) {
        $this->dispatch_order_event( 'order_status_changed', $order_id, array(
            'from_status' => sanitize_key( $from ),
            'to_status' => sanitize_key( $to ),
        ) );
    }


    /**
     * woocommerce_thankyou handler. Fires once per order (guarded by meta, as
     * the thank-you page can be refreshed any number of times).
     *
     * @since 6.0.0
     * @param int $order_id Order id.
     * @return void
     */
    public function handle_thankyou( $order_id ) {
        $order_id = absint( $order_id );

        if ( ! $order_id || ! $this->has_endpoints( 'thankyou' ) ) {
            return;
        }

        if ( get_post_meta( $order_id, '_flexify_webhook_thankyou_sent', true ) === 'yes' ) {
            return;
        }

        $order = ( new Order_Payload_Provider() )->build( array( 'order_id' => $order_id ) );

        Dispatcher::dispatch( 'thankyou', array( 'order' => $order ) );

        update_post_meta( $order_id, '_flexify_webhook_thankyou_sent', 'yes' );
    }


    /**
     * Countdown session destroyed handler.
     *
     * @since 6.0.0
     * @param array $session_data Session data.
     * @param array $cart_items Cart items.
     * @param array $applied_coupons Applied coupons.
     * @param array $cart_totals Cart totals.
     * @return void
     */
    public function handle_countdown_expired( $session_data = array(), $cart_items = array(), $applied_coupons = array(), $cart_totals = array() ) {
        if ( ! $this->has_endpoints( 'countdown_expired' ) ) {
            return;
        }

        $payload = ( new Generic_Payload_Provider() )->build( array(
            'data' => array(
                'session' => is_array( $session_data ) ? $session_data : array(),
                'items' => is_array( $cart_items ) ? $cart_items : array(),
                'coupons' => is_array( $applied_coupons ) ? $applied_coupons : array(),
                'totals' => is_array( $cart_totals ) ? $cart_totals : array(),
            ),
        ) );

        Dispatcher::dispatch( 'countdown_expired', $payload );
    }


    /**
     * Tracking server-side purchase handler (reuses the router payload).
     *
     * @since 6.0.0
     * @param array $payload Unified tracking payload.
     * @param mixed $order WC order (unused, payload already carries the data).
     * @return void
     */
    public function handle_tracking_purchase( $payload, $order = null ) {
        if ( ! $this->has_endpoints( 'purchase' ) ) {
            return;
        }

        Dispatcher::dispatch( 'purchase', array(
            'data' => is_array( $payload ) ? $payload : array(),
        ) );
    }


    /**
     * Tracking server-side event handler (begin_checkout / add_shipping_info /
     * add_payment_info). The browser event names are prefixed with "fc_".
     *
     * @since 6.0.0
     * @param string $event_name Browser event name (fc_*).
     * @param array  $payload Event payload.
     * @return void
     */
    public function handle_tracking_event( $event_name, $payload ) {
        $event_key = preg_replace( '/^fc_/', '', sanitize_key( (string) $event_name ) );

        // Only the three explicitly cataloged tracking events (purchase is
        // handled by its own server hook above).
        $allowed = array( 'begin_checkout', 'add_shipping_info', 'add_payment_info' );

        if ( ! in_array( $event_key, $allowed, true ) || ! $this->has_endpoints( $event_key ) ) {
            return;
        }

        Dispatcher::dispatch( $event_key, array(
            'data' => is_array( $payload ) ? $payload : array(),
        ) );
    }


    /**
     * WhatsApp verified handler. Sends a minimal payload (no full user object).
     *
     * @since 6.0.0
     * @param mixed $user WP_User or user id.
     * @param string $phone Verified phone.
     * @return void
     */
    public function handle_whatsapp_verified( $user, $phone ) {
        if ( ! $this->has_endpoints( 'whatsapp_verified' ) ) {
            return;
        }

        $user_id = 0;

        if ( is_object( $user ) && isset( $user->ID ) ) {
            $user_id = absint( $user->ID );
        } elseif ( is_numeric( $user ) ) {
            $user_id = absint( $user );
        }

        $payload = ( new Generic_Payload_Provider() )->build( array(
            'data' => array(
                'user_id' => $user_id,
                'phone' => sanitize_text_field( (string) $phone ),
            ),
        ) );

        Dispatcher::dispatch( 'whatsapp_verified', $payload );
    }
}
