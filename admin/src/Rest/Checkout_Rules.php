<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Checkout\Headless_Data;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * GET /flexify-checkout/v1/checkout/rules
 *
 * Public, read-only checkout field/step rules for headless rendering and
 * validation. Gated behind the "expose headless API" toggle.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Checkout_Rules extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/checkout/rules';


    /**
     * Only register when the headless API is enabled.
     *
     * @since 6.0.0
     * @return bool
     */
    protected function should_register() {
        return Admin_Options::get_setting('enable_headless_checkout_api') === 'yes';
    }


    /**
     * Public read access.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return true;
    }


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        return rest_ensure_response( Headless_Data::get_checkout_rules() );
    }
}
