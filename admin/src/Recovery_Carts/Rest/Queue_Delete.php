<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — cancel a queued event.
 *
 * DELETE flexify-checkout/v1/recovery/queue/{id} removes a scheduled
 * fcrc-cron-event post, replacing the legacy WP_List_Table delete action.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Queue_Delete extends Abstract_Route {

    /**
     * Route path with the event id parameter.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/queue/(?P<id>\d+)';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'DELETE';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle( WP_REST_Request $request ) {
        $id = absint( $request->get_param('id') );

        if ( ! $id || get_post_type( $id ) !== 'fcrc-cron-event' ) {
            return new \WP_Error( 'fcrc_event_not_found', __( 'Evento não encontrado.', 'fc-recovery-carts' ), array( 'status' => 404 ) );
        }

        wp_delete_post( $id, true );

        return $this->success_response( array( 'deleted' => $id ) );
    }
}
