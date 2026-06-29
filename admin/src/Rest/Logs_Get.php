<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Core\Logs\Logger;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Return the parsed, filtered log entries for the admin logs viewer.
 *
 * GET flexify-checkout/v1/admin/logs?level=&category=&search=&page=&per_page=
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Logs_Get extends Abstract_Route {

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
    protected $methods = 'GET';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $data = Logger::get_entries( array(
            'level' => sanitize_key( (string) $request->get_param('level') ),
            'category' => sanitize_key( (string) $request->get_param('category') ),
            'search' => sanitize_text_field( (string) $request->get_param('search') ),
            'page' => (int) $request->get_param('page'),
            'per_page' => (int) $request->get_param('per_page'),
        ) );

        return $this->success_response( array(
            'entries' => $data['entries'],
            'total' => $data['total'],
            'page' => $data['page'],
            'per_page' => $data['per_page'],
            'categories' => $data['categories'],
            'levels' => $data['levels'],
            'files' => Logger::get_files(),
        ) );
    }
}
