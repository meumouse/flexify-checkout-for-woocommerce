<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Core\Logs\Logger;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Return the raw concatenated log contents so the admin can download a .log file.
 *
 * GET flexify-checkout/v1/admin/logs/download
 *
 * The client builds a Blob from the returned content/filename and triggers the
 * download, which keeps the nonce in the X-WP-Nonce header instead of the URL.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Logs_Download extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/logs/download';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $date = function_exists('current_time') ? current_time('Y-m-d') : gmdate('Y-m-d');

        return $this->success_response( array(
            'filename' => sprintf( 'flexify-checkout-logs-%s.log', $date ),
            'content' => Logger::get_raw_contents(),
        ) );
    }
}
