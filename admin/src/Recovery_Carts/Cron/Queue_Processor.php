<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Cron;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Processes queued cron events when PHP Cron is enabled.
 *
 * @since 1.3.2
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Cron
 * @author MeuMouse.com
 */
class Queue_Processor {

    /**
     * Run all due events in the queue
     * 
     * @since 1.3.2
     * @return void
     */
    public static function dispatch_due_events() {
        if ( ! Scheduler_Manager::is_php_cron_enabled() ) {
            return;
        }

        $now = current_time( 'timestamp', true );

        $events = get_posts( array(
            'post_type'      => 'fcrc-cron-event',
            'post_status'    => 'publish',
            'posts_per_page' => -1, // all posts
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
            'meta_key'       => '_fcrc_cron_scheduled_at',
            'meta_query'     => array(
                array(
                    'key'     => '_fcrc_cron_scheduled_at',
                    'value'   => $now,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ),
            ),
        ) );

        if ( empty( $events ) ) {
            Scheduler_Manager::schedule_queue_runner();
        }

        foreach ( $events as $event ) {
            $hook = get_post_meta( $event->ID, '_fcrc_cron_event_key', true );

            if ( ! $hook ) {
                wp_delete_post( $event->ID, true );
                continue;
            }

            $args = get_post_meta( $event->ID, '_fcrc_cron_args', true );

            if ( ! is_array( $args ) ) {
                $args = array();
            }

            $args['cron_post_id'] = $event->ID;

            // Prevent concurrent execution by marking as draft before running.
            wp_update_post( array(
                'ID' => $event->ID,
                'post_status' => 'draft',
            ));

            do_action_ref_array( $hook, array_values( $args ) );

            if ( get_post_status( $event->ID ) !== 'trash' ) {
                wp_delete_post( $event->ID, true );
            }
        }

        Scheduler_Manager::schedule_queue_runner();
    }


    /**
     * Remove expired/orphaned events from the processing queue.
     *
     * An event is considered removable when it satisfies ANY of:
     *  - scheduled_at < (now - tolerance) AND status is still 'publish'
     *    (i.e. queue runner never picked it up)
     *  - referenced cart no longer exists
     *  - referenced cart is in a finalized status (recovered, lost,
     *    purchased, order_abandoned) and therefore should not fire
     *
     * For each removable event, unschedules the equivalent WP-Cron entry
     * (when applicable) and deletes the queue post.
     *
     * @since 1.4.0
     * @param int $tolerance_seconds | Grace period in seconds before treating a past-due event as expired (default: 1 hour).
     * @return int Number of events removed.
     */
    public static function cleanup_expired_events( $tolerance_seconds = HOUR_IN_SECONDS ) {
        $tolerance_seconds = (int) apply_filters( 'Flexify_Checkout/Recovery_Carts/Queue/Expired_Tolerance_Seconds', $tolerance_seconds );

        if ( $tolerance_seconds < 0 ) {
            $tolerance_seconds = 0;
        }

        $now = current_time( 'timestamp', true );
        $threshold = $now - $tolerance_seconds;

        $finalized_statuses = apply_filters( 'Flexify_Checkout/Recovery_Carts/Queue/Finalized_Cart_Statuses', array( 'recovered', 'lost', 'purchased', 'order_abandoned' ) );

        $events = get_posts( array(
            'post_type'      => 'fcrc-cron-event',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        if ( empty( $events ) ) {
            return 0;
        }

        $removed = 0;

        foreach ( $events as $event_id ) {
            $should_remove = false;
            $scheduled_at = (int) get_post_meta( $event_id, '_fcrc_cron_scheduled_at', true );
            $status = get_post_status( $event_id );

            // expired and never processed
            if ( $scheduled_at > 0 && $scheduled_at < $threshold && $status === 'publish' ) {
                $should_remove = true;
            }

            // orphaned or finalized cart
            if ( ! $should_remove ) {
                $cart_id = (int) get_post_meta( $event_id, '_fcrc_cart_id', true );

                if ( $cart_id > 0 ) {
                    $cart_status = get_post_status( $cart_id );

                    if ( $cart_status === false ) {
                        $should_remove = true;
                    } elseif ( in_array( $cart_status, $finalized_statuses, true ) ) {
                        $should_remove = true;
                    }
                }
            }

            if ( ! $should_remove ) {
                continue;
            }

            $hook = get_post_meta( $event_id, '_fcrc_cron_event_key', true );
            $args = get_post_meta( $event_id, '_fcrc_cron_args', true );

            if ( $hook ) {
                Scheduler_Manager::unschedule_event( $hook, is_array( $args ) ? $args : array() );
            }

            if ( get_post( $event_id ) ) {
                wp_delete_post( $event_id, true );
            }

            $removed++;
        }

        if ( $removed > 0 && defined('FC_RECOVERY_CARTS_DEBUG_MODE') && FC_RECOVERY_CARTS_DEBUG_MODE ) {
            error_log( '[Queue_Processor] cleanup_expired_events removed ' . $removed . ' event(s).' );
        }

        return $removed;
    }
}