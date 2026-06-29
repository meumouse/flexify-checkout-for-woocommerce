<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Core\Logs\Logger;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Delete every Flexify Checkout log file.
 *
 * DELETE flexify-checkout/v1/admin/logs
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Logs_Clear extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/logs';

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
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $removed = Logger::clear();

        return $this->success_response( array(
            'message' => __( 'The logs have been cleared.', 'flexify-checkout-for-woocommerce' ),
            'removed' => $removed,
        ) );
    }
}
