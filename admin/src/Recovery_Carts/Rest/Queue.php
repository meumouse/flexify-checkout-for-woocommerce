<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use WP_REST_Request;
use WP_Query;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — processing queue listing endpoint.
 *
 * Paginated list of scheduled cron-event posts (fcrc-cron-event) backing the
 * "Fila de processamentos" Vue page, replacing the legacy WP_List_Table.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Queue extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/queue';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';

    /**
     * Known cron event keys, in display order. Powers the filter tabs.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const EVENTS = array( 'fcrc_send_follow_up_message', 'fcrc_check_final_cart_status' );

    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'page' => array( 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint' ),
        'per_page' => array( 'type' => 'integer', 'default' => 20, 'sanitize_callback' => 'absint' ),
        'event' => array( 'type' => 'string', 'default' => 'all', 'sanitize_callback' => 'sanitize_text_field' ),
        'date_from' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
        'date_to' => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
    );


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $page = max( 1, absint( $request->get_param('page') ) );
        $per_page = min( 100, max( 1, absint( $request->get_param('per_page') ) ) );
        $event = sanitize_text_field( (string) $request->get_param('event') );
        $date_from = sanitize_text_field( (string) $request->get_param('date_from') );
        $date_to = sanitize_text_field( (string) $request->get_param('date_to') );

        $base_args = self::base_query_args( $date_from, $date_to );

        $query = new WP_Query( array_merge( $base_args, array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => self::merge_meta_query( $base_args, $event ),
            'no_found_rows' => false,
        ) ) );

        $items = array();

        foreach ( $query->posts as $post ) {
            $items[] = $this->format_event( $post->ID );
        }

        return $this->success_response( array(
            'items' => $items,
            'total' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page' => $page,
            'per_page' => $per_page,
            'events' => $this->event_options(),
            'counts' => $this->count_by_event( $base_args ),
        ) );
    }


    /**
     * Build the shared WP_Query args (post type, ordering and scheduled-date
     * range) reused by the list query, the counts and the bulk-delete resolver.
     *
     * @since 6.0.0
     * @param string $date_from Start date (Y-m-d) or empty.
     * @param string $date_to End date (Y-m-d) or empty.
     * @return array
     */
    public static function base_query_args( $date_from, $date_to ) {
        $args = array(
            'post_type' => 'fcrc-cron-event',
            'post_status' => 'publish',
            'orderby' => 'meta_value_num',
            'meta_key' => '_fcrc_cron_scheduled_at',
            'order' => 'ASC',
        );

        $range = self::build_date_meta( $date_from, $date_to );

        if ( $range ) {
            // Stored alongside the base args so callers can fold it into their
            // own meta_query (kept separate from the orderby meta_key above).
            $args['_fcrc_date_clause'] = $range;
        }

        return $args;
    }


    /**
     * Combine the date-range clause with an optional event-key clause into a
     * single meta_query (AND), suitable for WP_Query.
     *
     * @since 6.0.0
     * @param array  $base_args Shared args from {@see base_query_args()}.
     * @param string $event Event key to filter by, or 'all'/empty for any.
     * @return array
     */
    public static function merge_meta_query( $base_args, $event ) {
        $meta_query = array( 'relation' => 'AND' );

        if ( ! empty( $base_args['_fcrc_date_clause'] ) ) {
            $meta_query[] = $base_args['_fcrc_date_clause'];
        }

        if ( $event && in_array( $event, self::EVENTS, true ) ) {
            $meta_query[] = array(
                'key' => '_fcrc_cron_event_key',
                'value' => $event,
                'compare' => '=',
            );
        }

        return count( $meta_query ) > 1 ? $meta_query : array();
    }


    /**
     * Build a numeric BETWEEN meta clause on the scheduled timestamp.
     *
     * @since 6.0.0
     * @param string $from Start date (Y-m-d) or empty.
     * @param string $to End date (Y-m-d) or empty.
     * @return array Empty when no valid bound is given.
     */
    public static function build_date_meta( $from, $to ) {
        $from_ts = ( $from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) ? strtotime( $from . ' 00:00:00' ) : 0;
        $to_ts = ( $to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) ? strtotime( $to . ' 23:59:59' ) : 0;

        if ( $from_ts && $to_ts ) {
            return array( 'key' => '_fcrc_cron_scheduled_at', 'value' => array( $from_ts, $to_ts ), 'type' => 'NUMERIC', 'compare' => 'BETWEEN' );
        }

        if ( $from_ts ) {
            return array( 'key' => '_fcrc_cron_scheduled_at', 'value' => $from_ts, 'type' => 'NUMERIC', 'compare' => '>=' );
        }

        if ( $to_ts ) {
            return array( 'key' => '_fcrc_cron_scheduled_at', 'value' => $to_ts, 'type' => 'NUMERIC', 'compare' => '<=' );
        }

        return array();
    }


    /**
     * Count queued events per event key within the current date scope.
     *
     * @since 6.0.0
     * @param array $base_args Shared query args from {@see base_query_args()}.
     * @return array<string,int>
     */
    private function count_by_event( $base_args ) {
        $counts = array( 'all' => 0 );

        foreach ( self::EVENTS as $event ) {
            $query = new WP_Query( array_merge( $base_args, array(
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_query' => self::merge_meta_query( $base_args, $event ),
                'no_found_rows' => false,
            ) ) );

            $counts[ $event ] = (int) $query->found_posts;
            $counts['all'] += (int) $query->found_posts;
        }

        return $counts;
    }


    /**
     * Event filter options for the UI ("all" first).
     *
     * @since 6.0.0
     * @return array<int,array{value:string,label:string}>
     */
    private function event_options() {
        $options = array( array( 'value' => 'all', 'label' => __( 'Todos os eventos', 'flexify-checkout-for-woocommerce' ) ) );

        foreach ( self::EVENTS as $event ) {
            $options[] = array( 'value' => $event, 'label' => $this->event_label( $event ) );
        }

        return $options;
    }


    /**
     * Build the API representation of a queued event.
     *
     * @since 6.0.0
     * @param int $id Cron-event post ID.
     * @return array
     */
    private function format_event( $id ) {
        $cart_id = absint( get_post_meta( $id, '_fcrc_cart_id', true ) );
        $event_key = (string) get_post_meta( $id, '_fcrc_cron_event_key', true );
        $timestamp = absint( get_post_meta( $id, '_fcrc_cron_scheduled_at', true ) );

        return array(
            'id' => (int) $id,
            'cart_id' => $cart_id,
            'event_key' => $event_key,
            'event_name' => $this->event_label( $event_key ),
            'contact' => array(
                'name' => $cart_id ? (string) get_post_meta( $cart_id, '_fcrc_full_name', true ) : '',
                'phone' => $cart_id ? (string) get_post_meta( $cart_id, '_fcrc_cart_phone', true ) : '',
                'email' => $cart_id ? (string) get_post_meta( $cart_id, '_fcrc_cart_email', true ) : '',
            ),
            'scheduled_ts' => $timestamp,
            'scheduled_at' => $timestamp ? wp_date( get_option('date_format') . ' ' . get_option('time_format'), $timestamp ) : '—',
        );
    }


    /**
     * Human label for a cron event key.
     *
     * @since 6.0.0
     * @param string $key Event key (hook name).
     * @return string
     */
    private function event_label( $key ) {
        $map = array(
            'fcrc_send_follow_up_message' => __( 'Follow up', 'flexify-checkout-for-woocommerce' ),
            'fcrc_check_final_cart_status' => __( 'Aguardando pagamento', 'flexify-checkout-for-woocommerce' ),
        );

        return isset( $map[ $key ] ) ? $map[ $key ] : $key;
    }
}
