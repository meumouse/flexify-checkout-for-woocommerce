<?php

namespace MeuMouse\Flexify_Checkout\Core\Webhooks\Payload;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers as Recovery_Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Build a recovery-cart payload (CPT fc-recovery-carts) for webhooks.
 *
 * Lifted from Recovery_Carts\Core\Webhooks::prepare_cart_payload. The recovery
 * feature owns the rich _fcrc_* cart knowledge, so when it is not present this
 * provider degrades to a minimal { id } payload instead of hard-failing. The
 * recovery handlers build their own cart payloads through this class.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Core\Webhooks\Payload
 * @author MeuMouse.com
 */
class Cart_Payload_Provider implements Payload_Provider {

    /**
     * Build the cart payload.
     *
     * @since 6.0.0
     * @param array $context Expects { cart_id: int }.
     * @return array
     */
    public function build( array $context ) {
        $cart_id = isset( $context['cart_id'] ) ? absint( $context['cart_id'] ) : 0;

        if ( ! $cart_id ) {
            return array();
        }

        $cart_post = get_post( $cart_id );

        if ( ! $cart_post || $cart_post->post_type !== 'fc-recovery-carts' ) {
            return array( 'id' => $cart_id );
        }

        $cart_items = get_post_meta( $cart_id, '_fcrc_cart_items', true );
        $cart_total = get_post_meta( $cart_id, '_fcrc_cart_total', true );
        $abandoned_time = get_post_meta( $cart_id, '_fcrc_abandoned_time', true );

        $recovery_url = '';

        if ( class_exists( Recovery_Helpers::class ) && is_callable( array( Recovery_Helpers::class, 'generate_recovery_cart_link' ) ) ) {
            $recovery_url = Recovery_Helpers::generate_recovery_cart_link( $cart_id, 'webhook', 'webhook' );
        }

        return array(
            'id' => $cart_id,
            'status' => get_post_status( $cart_id ),
            'created_at' => get_post_time( 'c', true, $cart_post ),
            'updated_at' => \MeuMouse\Flexify_Checkout\Core\Webhooks\Webhook_Helpers::normalize_timestamp( get_post_meta( $cart_id, '_fcrc_cart_updated_time', true ) ),
            'abandoned_at' => \MeuMouse\Flexify_Checkout\Core\Webhooks\Webhook_Helpers::normalize_timestamp( $abandoned_time ),
            'total' => floatval( $cart_total ),
            'currency' => get_option('woocommerce_currency'),
            'recovery_url' => $recovery_url,
            'customer' => array(
                'first_name' => get_post_meta( $cart_id, '_fcrc_first_name', true ),
                'last_name' => get_post_meta( $cart_id, '_fcrc_last_name', true ),
                'full_name' => get_post_meta( $cart_id, '_fcrc_full_name', true ),
                'email' => get_post_meta( $cart_id, '_fcrc_cart_email', true ),
                'phone' => get_post_meta( $cart_id, '_fcrc_cart_phone', true ),
            ),
            'location' => array(
                'city' => get_post_meta( $cart_id, '_fcrc_location_city', true ),
                'state' => get_post_meta( $cart_id, '_fcrc_location_state', true ),
                'country_code' => get_post_meta( $cart_id, '_fcrc_location_country_code', true ),
                'ip' => get_post_meta( $cart_id, '_fcrc_location_ip', true ),
            ),
            'items' => $this->prepare_cart_items( $cart_items ),
        );
    }


    /**
     * Normalize the stored cart items array.
     *
     * @since 6.0.0
     * @param mixed $items Stored _fcrc_cart_items value.
     * @return array
     */
    protected function prepare_cart_items( $items ) {
        if ( ! is_array( $items ) ) {
            return array();
        }

        $prepared = array();

        foreach ( $items as $item ) {
            $product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;

            $prepared[] = array(
                'product_id' => $product_id,
                'name'       => isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '',
                'quantity'   => isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0,
                'price'      => isset( $item['price'] ) ? floatval( $item['price'] ) : 0.0,
                'total'      => isset( $item['total'] ) ? floatval( $item['total'] ) : 0.0,
                'image'      => isset( $item['image'] ) ? esc_url_raw( $item['image'] ) : '',
                'url'        => $this->get_product_permalink( $product_id ),
            );
        }

        return $prepared;
    }


    /**
     * Resolve a product permalink by ID.
     *
     * @since 6.0.0
     * @param int $product_id Product ID.
     * @return string
     */
    protected function get_product_permalink( $product_id ) {
        $product_id = absint( $product_id );

        if ( ! $product_id ) {
            return '';
        }

        if ( function_exists('wc_get_product') ) {
            $product = wc_get_product( $product_id );

            if ( $product && is_callable( array( $product, 'get_permalink' ) ) ) {
                return esc_url_raw( $product->get_permalink() );
            }
        }

        $url = get_permalink( $product_id );

        return $url ? esc_url_raw( $url ) : '';
    }
}
