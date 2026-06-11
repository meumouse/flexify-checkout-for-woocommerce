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
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'page' => array( 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint' ),
        'per_page' => array( 'type' => 'integer', 'default' => 20, 'sanitize_callback' => 'absint' ),
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

        $query = new WP_Query( array(
            'post_type' => 'fcrc-cron-event',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'meta_value_num',
            'meta_key' => '_fcrc_cron_scheduled_at',
            'order' => 'ASC',
            'no_found_rows' => false,
        ) );

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
        ) );
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
