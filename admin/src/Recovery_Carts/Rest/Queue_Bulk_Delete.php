<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use WP_REST_Request;
use WP_Query;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — bulk delete (cancel) queued events.
 *
 * POST flexify-checkout/v1/recovery/queue/bulk-delete. Backs the
 * "Excluir selecionados" and "Limpar tudo" actions of the queue Vue table:
 *
 *  - { ids: [1, 2, 3] }  → cancel the given queued events.
 *  - { all: true, event, date_from, date_to } → cancel every event matching the
 *    current filters ("clear all").
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Queue_Bulk_Delete extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/queue/bulk-delete';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';

    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'default' => array() ),
        'all' => array( 'type' => 'boolean', 'default' => false ),
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
        $ids = $request->get_param('all')
            ? $this->resolve_filtered_ids( $request )
            : array_map( 'absint', (array) $request->get_param('ids') );

        $deleted = array();

        foreach ( array_unique( array_filter( $ids ) ) as $id ) {
            if ( get_post_type( $id ) !== 'fcrc-cron-event' ) {
                continue;
            }

            wp_delete_post( $id, true );
            $deleted[] = $id;
        }

        return $this->success_response( array(
            'deleted' => count( $deleted ),
            'ids' => $deleted,
        ) );
    }


    /**
     * Resolve every event id matching the current event/date filters.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return array<int,int>
     */
    private function resolve_filtered_ids( WP_REST_Request $request ) {
        $event = sanitize_text_field( (string) $request->get_param('event') );
        $date_from = sanitize_text_field( (string) $request->get_param('date_from') );
        $date_to = sanitize_text_field( (string) $request->get_param('date_to') );

        $base_args = Queue::base_query_args( $date_from, $date_to );

        $query = new WP_Query( array_merge( $base_args, array(
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => Queue::merge_meta_query( $base_args, $event ),
            'no_found_rows' => true,
        ) ) );

        return array_map( 'absint', $query->posts );
    }
}
