<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handles with orders events
 *
 * @since 1.0.0
 * @version 1.3.6
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class Order_Events {

    /**
     * Construct function
     *
     * @since 1.0.0
     * @version 1.2.0
     * @return void
     */
    public function __construct() {
        // schedule event for order abandonment check
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'schedule_order_abandonment_check' ), 10, 3 );

        // check if order still unpaid
        add_action( 'fcrc_check_order_payment_status', array( $this, 'check_order_payment_status' ), 10, 1 );

        // save cart id on order meta data
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'set_recovered_cart' ), 10, 1 );

        // Listen for order status changes
        add_action( 'woocommerce_order_status_changed', array( $this, 'mark_cart_as_recovered' ), 10, 3 );

        // listen for order payment complete
        add_action( 'woocommerce_payment_complete', array( $this, 'maybe_mark_cart_as_recovered' ), 10, 1 );
    }


    /**
     * Schedules an event to check if the order is paid within the configured delay
     *
     * @since 1.0.0
     * @version 1.3.0
     * @param int $order_id | The order ID
     * @param array $posted_data | The posted data from checkout
     * @param object $order | The WooCommerce order object
     * @return void
     */
    public function schedule_order_abandonment_check( $order_id, $posted_data, $order ) {
        if ( ! $order_id ) {
            return;
        }

        $payment_method = $order->get_payment_method();
        $payment_methods = Admin::get_setting('payment_methods');

        // Get delay time based on the payment method
        if ( isset( $payment_methods[$payment_method] ) ) {
            $delay_time = $payment_methods[$payment_method]['delay_time'];
            $delay_unit = $payment_methods[$payment_method]['delay_unit'];
        } else {
            // Default delay if payment method is not found
            $delay_time = 5;
            $delay_unit = 'minutes';
        }

        // Convert delay time to seconds
        $delay_seconds = Helpers::convert_to_seconds( $delay_time, $delay_unit );

        // Schedule a task to check order payment status after the delay
        $event_delay = current_time('timestamp', true) + $delay_seconds;

        \MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Scheduler_Manager::schedule_single_event(
            $event_delay,
            'fcrc_check_final_cart_status',
            array(
                'cart_id' => intval( $order_id ),
            ),
            array(
                '_fcrc_cart_id' => intval( $order_id ),
            )
        );
    }


    /**
     * Checks if an order is still unpaid after the delay and marks it as abandoned
     *
     * @since 1.0.0
     * @version 1.3.2
     * @param int $order_id | The order ID
     * @return void
     */
    public function check_order_payment_status( $order_id ) {
        $order = wc_get_order( $order_id );
        $cart_id = get_post_meta( $order_id, '_fcrc_cart_id', true );

        if ( ! $order || $order->is_paid() || ! $cart_id ) {
            return;
        }

        // Mark order as abandoned
        update_post_meta( $cart_id, '_fcrc_abandoned_time', current_time('timestamp', true) );

        // update cart post
        wp_update_post( array(
            'ID' => $cart_id,
            'post_status' => 'order_abandoned',
        ));

        /**
         * Fire a hook when an order is considered abandoned
         *
         * @since 1.0.0
         * @param int $order_id | The abandoned order ID
         * @param int $cart_id | The abandoned cart ID
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/Order_Abandoned', $order_id, $cart_id );
    }


    /**
     * Saves the cart ID, products, billing details, and location to the order meta when a new order is created.
     *
     * @since 1.0.0
     * @version 1.3.0
     * @param int $order_id | The order ID
     * @return void
     */
    public function set_recovered_cart( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        if ( function_exists('WC') && WC()->session instanceof \WC_Session ) {
            $cart_id = WC()->session->get('fcrc_cart_id') ?: ( $_COOKIE['fcrc_cart_id'] ?? null );
        } else {
            $cart_id = $_COOKIE['fcrc_cart_id'] ?? null;
        }

        if ( ! $cart_id ) {
            return;
        }

        // Retrieve stored cart meta
        $stored_meta = get_post_meta( $cart_id );

        // Get billing information from the order
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();
        $billing_full_name = sprintf( '%s %s', $billing_first_name, $billing_last_name );
        $billing_phone = $order->get_billing_phone();
        $billing_email = $order->get_billing_email();
        $order_total = $order->get_total();
        $user_id = $order->get_user_id();

        // Get billing location information from the order
        $billing_city = $order->get_billing_city();
        $billing_state = $order->get_billing_state();
        $billing_zipcode = $order->get_billing_postcode();
        $billing_country = $order->get_billing_country();

        // Get stored IP address from cart meta
        $billing_ip = get_post_meta( $cart_id, '_fcrc_location_ip', true );

        // Prepare updated meta data
        $updated_meta = array(
            '_fcrc_first_name' => $billing_first_name,
            '_fcrc_last_name' => $billing_last_name,
            '_fcrc_full_name' => $billing_full_name,
            '_fcrc_cart_phone' => $billing_phone,
            '_fcrc_cart_email' => $billing_email,
            '_fcrc_cart_total' => $order_total,
            '_fcrc_user_id' => $user_id ?: 0,
            '_fcrc_location_city' => $billing_city,
            '_fcrc_location_state' => $billing_state,
            '_fcrc_location_zipcode' => $billing_zipcode,
            '_fcrc_location_country_code' => $billing_country,
            '_fcrc_location_ip' => $billing_ip,
        );

        // Compare existing meta and update only if different
        foreach ( $updated_meta as $meta_key => $new_value ) {
            $stored_value = isset( $stored_meta[$meta_key][0] ) ? $stored_meta[$meta_key][0] : null;

            if ( $stored_value !== $new_value ) {
                update_post_meta( $cart_id, $meta_key, $new_value );
            }
        }

        // Retrieve order items
        $order_items = array();

        foreach ( $order->get_items() as $item_id => $item ) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
            $price = floatval( $item->get_total() ) / $quantity;

            $order_items[$product_id] = array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $item->get_total(),
                'name' => $item->get_name(),
                'image' => get_the_post_thumbnail_url( $product_id, 'thumbnail' ),
            );
        }

        // cancel scheduled follow up events
        Helpers::cancel_scheduled_follow_up_events( $cart_id );

        // update cart post
        wp_update_post( array(
            'ID' => $cart_id,
            'post_status' => 'purchased',
        ));

        $payment_method = $order->get_payment_method();
        $payment_method_title = $order->get_payment_method_title();

        update_post_meta( $cart_id, '_fcrc_cart_items', $order_items );
        update_post_meta( $cart_id, '_fcrc_purchased', true );
        update_post_meta( $cart_id, '_fcrc_order_id', $order_id );
        update_post_meta( $cart_id, '_fcrc_order_date_created', $order->get_date_created() );
        update_post_meta( $order_id, '_fcrc_cart_id', $cart_id );
        update_post_meta( $cart_id, '_fcrc_payment_method', $payment_method );
        update_post_meta( $cart_id, '_fcrc_payment_method_title', $payment_method_title );

        if ( defined('FC_RECOVERY_CARTS_DEBUG_MODE') && FC_RECOVERY_CARTS_DEBUG_MODE ) {
            error_log( "Cart ID {$cart_id} updated with billing & location info for order {$order_id}" );
        }

        /**
         * Fire a hook when user purchases a cart
         *
         * @since 1.0.0
         * @param int $cart_id | The cart ID
         * @param int $order_id | The WooCommerce order ID
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/Purchased_Cart', $cart_id, $order_id );
    }


    /**
     * Marks the cart as recovered when the order is paid (processing or completed)
     *
     * @since 1.0.0
     * @version 1.3.6
     * @param int $order_id | The WooCommerce order ID
     * @param string $old_status | The previous order status
     * @param string $new_status | The new order status
     * @return void
     */
    public function mark_cart_as_recovered( $order_id, $old_status, $new_status ) {
        if ( ! in_array( $new_status, array( 'processing', 'completed' ) ) ) {
            return;
        }

        // Get the cart ID linked to this order
        $cart_id = get_post_meta( $order_id, '_fcrc_cart_id', true );

        if ( ! $cart_id ) {
            return;
        }

        // Cancel any scheduled follow-up notifications for this cart
        Helpers::cancel_scheduled_follow_up_events( $cart_id );

        // Update the cart status to recovered
        wp_update_post( array(
            'ID' => $cart_id,
            'post_status' => 'recovered',
        ));

        update_post_meta( $cart_id, '_fcrc_purchased', true );

        if ( FC_RECOVERY_CARTS_DEBUG_MODE ) {
            error_log( "Cart ID {$cart_id} linked to Order ID {$order_id} marked as recovered." );
        }

        /**
         * Fire a hook when a cart is recovered
         *
         * @since 1.0.0
         * @param int $cart_id | The cart ID
         * @param int $order_id | The WooCommerce order ID
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/Cart_Recovered', $cart_id, $order_id );

        // Get the order object
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $cart_items = array();
        $cart_total = 0;

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();

            if ( ! $product ) {
                continue;
            }

            $product_id = $product->get_id();
            $quantity = $item->get_quantity();
            $price = floatval( $item->get_total() ) / max( 1, $quantity ); // unit price
            $total_price = floatval( $item->get_total() );
            $cart_total += $total_price;

            $cart_items[ $product_id ] = array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total_price,
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url( $product_id, 'thumbnail' ),
            );
        }

        // Save updated items to cart post
        update_post_meta( $cart_id, '_fcrc_cart_items', $cart_items );
        update_post_meta( $cart_id, '_fcrc_cart_total', $cart_total );
    }


    /**
     * Triggers cart recovery when payment is marked complete
     *
     * @since 1.2.0
     * @version 1.3.6
     * @param int $order_id | The WooCommerce order ID
     * @return void
     */
    public function maybe_mark_cart_as_recovered( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $cart_id = get_post_meta( $order_id, '_fcrc_cart_id', true );

        if ( ! $cart_id ) {
            return;
        }

        $cart_post = get_post( $cart_id );

        if ( ! $cart_post || $cart_post->post_status === 'recovered' ) {
            return;
        }

        // Avoid duplicate recovery
        $already_recovered = get_post_meta( $cart_id, '_fcrc_cart_recovered', true );
        
        if ( $already_recovered ) {
            return;
        }

        // Cancel any scheduled follow-up notifications for this cart
        Helpers::cancel_scheduled_follow_up_events( $cart_id );

        wp_update_post( array(
            'ID' => $cart_id,
            'post_status' => 'recovered',
        ));

        update_post_meta( $cart_id, '_fcrc_purchased', true );
        update_post_meta( $cart_id, '_fcrc_cart_recovered', true );

        if ( FC_RECOVERY_CARTS_DEBUG_MODE ) {
            error_log( "[woocommerce_payment_complete] Cart ID {$cart_id} linked to Order ID {$order_id} marked as recovered." );
        }

        /**
         * Fire a hook when a cart is recovered
         *
         * @since 1.2.0
         * @param int $cart_id | Cart ID
         * @param int $order_id | Order ID
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/Cart_Recovered', $cart_id, $order_id );
    }
}
