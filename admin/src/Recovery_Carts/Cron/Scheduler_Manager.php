<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Cron;

use GO\Scheduler as GoScheduler;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

require_once FC_RECOVERY_CARTS_INC . 'Cron/Scheduler_Fallback.php';

/**
 * Centralises the interaction with the selected task scheduler
 *
 * @since 1.3.2
 * @version 1.4.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Cron
 * @author MeuMouse.com
 */
class Scheduler_Manager {
    
    public const TYPE_WP_CRON = 'wp_cron';
    public const TYPE_PHP_CRON = 'php_cron';

    /**
     * Retrieve the configured scheduler
     * 
     * @since 1.3.2
     * @return string
     */
    public static function get_scheduler_type() {
        $type = Admin::get_setting('task_scheduler');

        if ( ! in_array( $type, array( self::TYPE_PHP_CRON, self::TYPE_WP_CRON ), true ) ) {
            $type = self::TYPE_WP_CRON;
        }

        return $type;
    }


    /**
     * Whether PHP Cron is enabled
     * 
     * @since 1.3.2
     * @return bool
     */
    public static function is_php_cron_enabled() {
        return self::TYPE_PHP_CRON === self::get_scheduler_type();
    }


    /**
     * Whether WP Cron is enabled
     * 
     * @since 1.3.2
     * @return bool
     */
    public static function is_wp_cron_enabled() {
        return self::TYPE_WP_CRON === self::get_scheduler_type();
    }


    /**
     * Schedule a single event.
     *
     * @since 1.3.2
     * @version 1.4.0
     * @param int $timestamp | Unix timestamp
     * @param string $hook | Action hook name
     * @param array  $args | Arguments passed to the action hook
     * @param array  $meta | Additional meta saved on the queue post
     * @return int  Post ID of the queue entry
     */
    public static function schedule_single_event( $timestamp, $hook, $args = array(), $meta = array() ) {
        $timestamp = absint( $timestamp );

        if ( $timestamp <= 0 ) {
            return 0;
        }

        $args = is_array( $args ) ? $args : array();
        $args = Helpers::sanitize_array( $args );
        $meta = is_array( $meta ) ? $meta : array();
        $args_hash = md5( wp_json_encode( array( 'hook' => $hook, 'args' => $args ) ) );
        $existing  = self::find_existing_event( $hook, $args_hash );

        if ( $existing ) {
            $post_id = $existing->ID;
        } else {
            $post_id = wp_insert_post( array(
                'post_type' => 'fcrc-cron-event',
                'post_status' => 'publish',
                'post_title' => sanitize_text_field( $hook ),
            ));
        }

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return 0;
        }

        $queue_args = $args;
        $queue_args['cron_post_id'] = $post_id;

        $meta_input = array_merge(
            array(
                '_fcrc_cron_event_key' => sanitize_text_field( $hook ),
                '_fcrc_cron_scheduled_at' => $timestamp,
                '_fcrc_cron_args' => $queue_args,
                '_fcrc_cron_args_hash' => $args_hash,
            ),
            $meta
        );

        if ( isset( $queue_args['cart_id'] ) ) {
            $meta_input['_fcrc_cart_id'] = absint( $queue_args['cart_id'] );
        }

        foreach ( $meta_input as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        if ( self::is_wp_cron_enabled() ) {
            self::cleanup_wp_cron_duplicates( $hook, $args, $queue_args );

            if ( ! wp_next_scheduled( $hook, $queue_args ) ) {
                wp_schedule_single_event( $timestamp, $hook, $queue_args );
            }
        }

        self::schedule_queue_runner();

        return $post_id;
    }


    /**
     * Remove WP-Cron events that match the same logical args but carry a different cron_post_id.
     *
     * @since 1.4.0
     * @param string $hook | Action hook name.
     * @param array  $base_args | Args without cron_post_id.
     * @param array  $queue_args | Args with the current cron_post_id.
     * @return void
     */
    protected static function cleanup_wp_cron_duplicates( $hook, array $base_args, array $queue_args ) {
        if ( ! function_exists('_get_cron_array') ) {
            return;
        }

        $cron = _get_cron_array();

        if ( empty( $cron ) || ! is_array( $cron ) ) {
            return;
        }

        foreach ( $cron as $timestamp => $hooks ) {
            if ( empty( $hooks[ $hook ] ) || ! is_array( $hooks[ $hook ] ) ) {
                continue;
            }

            foreach ( $hooks[ $hook ] as $event ) {
                if ( empty( $event['args'] ) || ! is_array( $event['args'] ) ) {
                    continue;
                }

                $event_args = $event['args'];
                $compare_args = $event_args;
                unset( $compare_args['cron_post_id'] );

                if ( $compare_args === $base_args && $event_args !== $queue_args ) {
                    wp_unschedule_event( $timestamp, $hook, $event_args );
                }
            }
        }
    }


    /**
     * Remove scheduled event and queue entry.
     *
     * @since 1.3.2
     * @param string $hook | Action hook name.
     * @param array $args | Hook args used when scheduling
     * @return void
     */
    public static function unschedule_event( $hook, $args ) {
        $args = is_array( $args ) ? $args : array();

        if ( self::is_wp_cron_enabled() ) {
            $timestamp = wp_next_scheduled( $hook, $args );

            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook, $args );
            }
        }

        $hash = md5( wp_json_encode( array( 'hook' => $hook, 'args' => $args ) ) );
        $existing = self::find_existing_event( $hook, $hash );

        if ( $existing ) {
            wp_delete_post( $existing->ID, true );
        }

        self::schedule_queue_runner();
    }


    /**
     * Locate an existing queue post
     * 
     * @since 1.3.2
     * @param string $hook | Action hook name
     * @param string $hash | Args hash
     * @return WP_Post|null
     */
    protected static function find_existing_event( $hook, $hash ) {
        $query = get_posts( array(
            'post_type' => 'fcrc-cron-event',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_fcrc_cron_event_key',
                    'value' => sanitize_text_field( $hook ),
                ),
                array(
                    'key' => '_fcrc_cron_args_hash',
                    'value' => sanitize_text_field( $hash ),
                ),
            ),
            'orderby' => 'ID',
            'order' => 'ASC',
        ));

        return ! empty( $query ) ? $query[0] : null;
    }


    /**
     * Helper to build a scheduler instance (real or stub)
     * 
     * @since 1.3.2
     * @return GoScheduler
     */
    public static function build_scheduler() {
        $scheduler = new GoScheduler();

        $timezone = function_exists('wp_timezone') ? wp_timezone() : null;

        if ( $timezone instanceof \DateTimeZone ) {
            $scheduler->setTimeZone( $timezone );
        } else {
            $wp_timezone = wp_timezone_string();

            if ( $wp_timezone ) {
                $scheduler->setTimeZone( $wp_timezone );
            }
        }

        return $scheduler;
    }


    /**
     * Retrieve the interval (in seconds) used by the PHP Cron runner.
     *
     * @since 1.3.2
     * @return int
     */
    public static function get_php_cron_interval_seconds() {
        $interval = apply_filters( 'Flexify_Checkout/Recovery_Carts/PHP_Cron_Interval', 5 * MINUTE_IN_SECONDS );

        if ( ! is_numeric( $interval ) ) {
            $interval = MINUTE_IN_SECONDS;
        }

        $interval = (int) $interval;

        if ( $interval < 1 ) {
            $interval = MINUTE_IN_SECONDS;
        }

        return $interval;
    }


    /**
     * Ensure the queue processor is scheduled when using PHP Cron.
     *
     * This provides a fallback so that hosts triggering wp-cron.php
     * (or any request that runs WP-Cron) can dispatch the queued
     * follow-up events even if the dedicated CLI job is unavailable.
     *
     * @since 1.3.2
     * @return void
     */
    public static function schedule_queue_runner() {
        if ( ! self::is_wp_cron_available() ) {
            return;
        }

        if ( ! self::is_php_cron_enabled() ) {
            self::clear_queue_runner();
            return;
        }

        $next_timestamp = self::get_next_queue_timestamp();

        if ( ! $next_timestamp ) {
            self::clear_queue_runner();
            return;
        }

        $next_timestamp = max( $next_timestamp, current_time( 'timestamp', true ) );
        $scheduled = wp_next_scheduled( 'fcrc_dispatch_queue' );

        if ( $scheduled && absint( $scheduled ) === $next_timestamp ) {
            return;
        }

        if ( $scheduled ) {
            wp_unschedule_event( $scheduled, 'fcrc_dispatch_queue' );
        }

        wp_schedule_single_event( $next_timestamp, 'fcrc_dispatch_queue' );
    }


    /**
     * Remove any scheduled queue runner events.
     *
     * @since 1.3.2
     * @return void
     */
    protected static function clear_queue_runner() {
        if ( ! self::is_wp_cron_available() ) {
            return;
        }

        $timestamp = wp_next_scheduled('fcrc_dispatch_queue');

        while ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'fcrc_dispatch_queue' );
            $timestamp = wp_next_scheduled('fcrc_dispatch_queue');
        }
    }


    /**
     * Retrieve the next queued event timestamp.
     *
     * @since 1.3.2
     * @return int Timestamp in GMT or 0 when there is no queued event.
     */
    protected static function get_next_queue_timestamp() {
        $events = get_posts( array(
            'post_type'      => 'fcrc-cron-event',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
            'meta_key'       => '_fcrc_cron_scheduled_at',
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ) );

        if ( empty( $events ) ) {
            return 0;
        }

        $timestamp = get_post_meta( $events[0], '_fcrc_cron_scheduled_at', true );

        return $timestamp ? absint( $timestamp ) : 0;
    }


    /**
     * Whether WP-Cron is available for scheduling fallbacks.
     *
     * @since 1.3.2
     * @return bool
     */
    protected static function is_wp_cron_available() {
        if ( ! function_exists('wp_next_scheduled') ) {
            return false;
        }

        return ! ( defined('DISABLE_WP_CRON') && DISABLE_WP_CRON );
    }
}