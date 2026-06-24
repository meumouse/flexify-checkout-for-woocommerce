<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks\Payload;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Build a WooCommerce order payload for webhooks.
 *
 * Self-contained: depends only on wc_get_order, so it works for any checkout
 * event (order lifecycle, tracking, thank-you) without the recovery feature.
 *
 * Lifted from Recovery_Carts\Core\Webhooks::prepare_order_payload.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks\Payload
 * @author MeuMouse.com
 */
class Order_Payload_Provider implements Payload_Provider {

    /**
     * Build the order payload.
     *
     * @since 6.0.0
     * @param array $context Expects { order_id: int }.
     * @return array
     */
    public function build( array $context ) {
        $order_id = isset( $context['order_id'] ) ? absint( $context['order_id'] ) : 0;

        if ( ! $order_id ) {
            return array();
        }

        if ( ! function_exists('wc_get_order') ) {
            return array( 'id' => $order_id );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return array( 'id' => $order_id );
        }

        $items = array();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();

            $items[] = array(
                'product_id' => $product ? $product->get_id() : 0,
                'name'       => $item->get_name(),
                'quantity'   => $item->get_quantity(),
                'total'      => floatval( $item->get_total() ),
                'subtotal'   => floatval( $item->get_subtotal() ),
                'url'        => $product ? esc_url_raw( $product->get_permalink() ) : '',
            );
        }

        return array(
            'id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => floatval( $order->get_total() ),
            'currency' => $order->get_currency(),
            'created_at' => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'c' ) : '',
            'updated_at' => $order->get_date_modified() ? $order->get_date_modified()->date_i18n( 'c' ) : '',
            'payment_method' => $order->get_payment_method(),
            'billing' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ),
            'items' => $items,
        );
    }
}
