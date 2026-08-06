<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — per-cart timeline / detail endpoint.
 *
 * GET flexify-checkout/v1/recovery/carts/{id}/timeline. Returns a single cart's
 * full detail (contact, products, totals, coupon, linked order) plus a merged,
 * chronological event history built from the cart's own meta: creation, lead
 * capture, abandonment, every follow-up recorded in "_fcrc_notifications_sent"
 * (with channel and A/B variant), and the recovering order. Backs the detail
 * drawer opened from the "Todos os carrinhos" Vue page.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Cart_Timeline extends Abstract_Route {

    /**
     * Route path with the cart id parameter.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/carts/(?P<id>\d+)/timeline';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle( WP_REST_Request $request ) {
        $id = absint( $request->get_param('id') );

        if ( ! $id || get_post_type( $id ) !== 'fc-recovery-carts' ) {
            return new \WP_Error( 'fcrc_cart_not_found', __( 'Cart not found.', 'flexify-checkout-for-woocommerce' ), array( 'status' => 404 ) );
        }

        return $this->success_response( array(
            'cart'   => $this->format_cart( $id ),
            'events' => $this->build_events( $id ),
        ) );
    }


    /**
     * Build the cart detail payload.
     *
     * @since 6.0.0
     * @param int $id | The recovery cart post ID.
     * @return array
     */
    private function format_cart( $id ) {
        $status = get_post_status( $id );
        $items = get_post_meta( $id, '_fcrc_cart_items', true );
        $items = is_array( $items ) ? $items : array();

        $products = array();

        foreach ( $items as $item ) {
            $products[] = array(
                'name'     => isset( $item['name'] ) ? (string) $item['name'] : '',
                'quantity' => isset( $item['quantity'] ) ? (int) $item['quantity'] : 1,
                'image'    => isset( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
            );
        }

        $total = get_post_meta( $id, '_fcrc_cart_total', true );
        $notifications = get_post_meta( $id, '_fcrc_notifications_sent', true );

        return array(
            'id'              => (int) $id,
            'status'          => $status,
            'status_label'    => self::status_label( $status ),
            'contact'         => array(
                'name'  => (string) get_post_meta( $id, '_fcrc_full_name', true ),
                'phone' => (string) get_post_meta( $id, '_fcrc_cart_phone', true ),
                'email' => (string) get_post_meta( $id, '_fcrc_cart_email', true ),
            ),
            'location'        => $this->format_location( $id ),
            'products'        => $products,
            'total_formatted' => ( 'lead' !== $status && $total && function_exists('wc_price') ) ? wp_strip_all_tags( wc_price( $total ) ) : '—',
            'coupon_code'     => (string) get_post_meta( $id, '_fcrc_coupon_code', true ),
            'notifications_count' => is_array( $notifications ) ? count( $notifications ) : 0,
            'order'           => $this->format_order( $id ),
        );
    }


    /**
     * Build the chronological event list for a cart.
     *
     * @since 6.0.0
     * @param int $id | The recovery cart post ID.
     * @return array<int,array{type:string,label:string,description:string,date:string,timestamp:int}>
     */
    private function build_events( $id ) {
        $events = array();

        // Cart created (post creation time, UTC). The captured contact is shown
        // inline here — the cart record is created at lead capture, so this is
        // also when the contact became known.
        $created = (int) get_post_time( 'U', true, $id );

        if ( $created > 0 ) {
            $contact = $this->contact_summary( $id );
            $label = '' !== $contact ? __( 'Cart created — contact captured', 'flexify-checkout-for-woocommerce' ) : __( 'Cart created', 'flexify-checkout-for-woocommerce' );

            $events[] = $this->event( 'lead', $label, $contact, $created );
        }

        // Cart abandoned.
        $abandoned = (int) get_post_meta( $id, '_fcrc_abandoned_time', true );

        if ( $abandoned > 0 ) {
            $events[] = $this->event( 'abandoned', __( 'Cart abandoned', 'flexify-checkout-for-woocommerce' ), '', $abandoned );
        }

        // Follow-up messages sent.
        $notifications = get_post_meta( $id, '_fcrc_notifications_sent', true );
        $follow_ups = Admin::get_setting('follow_up_events');
        $follow_ups = is_array( $follow_ups ) ? $follow_ups : array();

        if ( is_array( $notifications ) ) {
            foreach ( $notifications as $notification ) {
                $sent_at = isset( $notification['sent_at'] ) ? (int) $notification['sent_at'] : 0;
                $event_key = isset( $notification['event_key'] ) ? (string) $notification['event_key'] : '';
                $channel = isset( $notification['channel'] ) ? (string) $notification['channel'] : '';
                $variant = isset( $notification['variant'] ) ? (string) $notification['variant'] : '';

                $title = isset( $follow_ups[ $event_key ]['title'] ) ? (string) $follow_ups[ $event_key ]['title'] : $event_key;

                $label = sprintf(
                    /* translators: %s: follow-up message title. */
                    __( 'Follow-up sent: %s', 'flexify-checkout-for-woocommerce' ),
                    $title
                );

                $events[] = $this->event( 'notification', $label, $this->notification_meta( $channel, $variant ), $sent_at );
            }
        }

        // Recovering order.
        $order = $this->format_order( $id );

        if ( $order ) {
            $label = sprintf(
                /* translators: %s: order number. */
                __( 'Order placed: #%s', 'flexify-checkout-for-woocommerce' ),
                $order['number']
            );

            $events[] = $this->event( 'order', $label, $order['status_label'], (int) $order['timestamp'] );
        }

        // Sort ascending by time; keep zero-timestamp entries last (unknown time).
        usort( $events, function( $a, $b ) {
            if ( $a['timestamp'] === $b['timestamp'] ) {
                return 0;
            }

            if ( 0 === $a['timestamp'] ) {
                return 1;
            }

            if ( 0 === $b['timestamp'] ) {
                return -1;
            }

            return ( $a['timestamp'] < $b['timestamp'] ) ? -1 : 1;
        } );

        return $events;
    }


    /**
     * Build a single timeline event entry.
     *
     * @since 6.0.0
     * @param string $type | Machine event type (created|lead|abandoned|notification|order).
     * @param string $label | Human label.
     * @param string $description | Optional secondary line.
     * @param int    $timestamp | UTC timestamp (0 when unknown).
     * @return array
     */
    private function event( $type, $label, $description, $timestamp ) {
        return array(
            'type'        => $type,
            'label'       => $label,
            'description' => $description,
            'timestamp'   => (int) $timestamp,
            'date'        => $timestamp > 0 ? wp_date( get_option('date_format') . ' ' . get_option('time_format'), $timestamp ) : '',
        );
    }


    /**
     * Compose a short description for the follow-up event (channel + variant).
     *
     * @since 6.0.0
     * @param string $channel | Channel key (whatsapp|email).
     * @param string $variant | A/B variant id, or empty.
     * @return string
     */
    private function notification_meta( $channel, $variant ) {
        $channels = array(
            'whatsapp' => __( 'WhatsApp', 'flexify-checkout-for-woocommerce' ),
            'email'    => __( 'E-mail', 'flexify-checkout-for-woocommerce' ),
        );

        $parts = array();

        if ( '' !== $channel ) {
            $parts[] = isset( $channels[ $channel ] ) ? $channels[ $channel ] : ucfirst( $channel );
        }

        if ( '' !== $variant ) {
            $parts[] = sprintf(
                /* translators: %s: A/B test variant letter. */
                __( 'Variant %s', 'flexify-checkout-for-woocommerce' ),
                strtoupper( $variant )
            );
        }

        return implode( ' • ', $parts );
    }


    /**
     * Short contact summary line for the lead-captured event.
     *
     * @since 6.0.0
     * @param int $id | The recovery cart post ID.
     * @return string
     */
    private function contact_summary( $id ) {
        $parts = array_filter( array(
            (string) get_post_meta( $id, '_fcrc_cart_phone', true ),
            (string) get_post_meta( $id, '_fcrc_cart_email', true ),
        ) );

        return implode( ' • ', $parts );
    }


    /**
     * Build the linked-order payload, or null when the cart has no order.
     *
     * @since 6.0.0
     * @param int $id | The recovery cart post ID.
     * @return array|null
     */
    private function format_order( $id ) {
        $order_id = absint( get_post_meta( $id, '_fcrc_order_id', true ) );

        if ( ! $order_id || ! function_exists('wc_get_order') ) {
            return null;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return null;
        }

        $created = $order->get_date_created();

        return array(
            'id'           => $order_id,
            'number'       => $order->get_order_number(),
            'status'       => $order->get_status(),
            'status_label' => function_exists('wc_get_order_status_name') ? wc_get_order_status_name( $order->get_status() ) : $order->get_status(),
            'total'        => function_exists('wc_price') ? wp_strip_all_tags( wc_price( $order->get_total() ) ) : (string) $order->get_total(),
            'edit_url'     => $order->get_edit_order_url(),
            'timestamp'    => $created ? $created->getTimestamp() : 0,
        );
    }


    /**
     * Build a formatted location string from stored geolocation meta.
     *
     * @since 6.0.0
     * @param int $id | The recovery cart post ID.
     * @return string
     */
    private function format_location( $id ) {
        $parts = array_filter( array(
            (string) get_post_meta( $id, '_fcrc_location_city', true ),
            (string) get_post_meta( $id, '_fcrc_location_state', true ),
            (string) get_post_meta( $id, '_fcrc_location_country_code', true ),
        ) );

        return implode( ', ', $parts );
    }


    /**
     * Human label for a recovery-cart status.
     *
     * @since 6.0.0
     * @param string $status | Status key.
     * @return string
     */
    public static function status_label( $status ) {
        $labels = array(
            'lead'            => __( 'Lead', 'flexify-checkout-for-woocommerce' ),
            'shopping'        => __( 'Buying', 'flexify-checkout-for-woocommerce' ),
            'abandoned'       => __( 'Abandoned', 'flexify-checkout-for-woocommerce' ),
            'order_abandoned' => __( 'Abandoned order', 'flexify-checkout-for-woocommerce' ),
            'recovered'       => __( 'Recovered', 'flexify-checkout-for-woocommerce' ),
            'lost'            => __( 'Lost', 'flexify-checkout-for-woocommerce' ),
            'purchased'       => __( 'Purchased', 'flexify-checkout-for-woocommerce' ),
        );

        return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
    }
}
