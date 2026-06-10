<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Base class for Flexify Checkout admin REST endpoints.
 *
 * Each subclass declares a route path and HTTP method; registration happens
 * automatically on rest_api_init when the class is booted by Init.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
abstract class Abstract_Route {

    /**
     * REST namespace shared by all admin endpoints.
     *
     * @since 6.0.0
     * @var string
     */
    const REST_NAMESPACE = 'flexify-checkout/v1';

    /**
     * REST route path, including the leading slash.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '';

    /**
     * Allowed HTTP method or method list.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';

    /**
     * REST argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array();


    /**
     * Register the route when WordPress initializes REST endpoints.
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        if ( $this->should_register() ) {
            add_action( 'rest_api_init', array( $this, 'register_route' ) );
        }
    }


    /**
     * Decide whether the route should be registered.
     *
     * @since 6.0.0
     * @return bool
     */
    protected function should_register() {
        return true;
    }


    /**
     * Register the route with the WordPress REST API.
     *
     * @since 6.0.0
     * @return void
     */
    public function register_route() {
        register_rest_route( self::REST_NAMESPACE, $this->route, array(
            'methods' => $this->methods,
            'callback' => array( $this, 'handle' ),
            'permission_callback' => array( $this, 'permission' ),
            'args' => $this->args,
        ) );
    }


    /**
     * Default permission check for admin-only endpoints.
     *
     * Uses the same capability required by the settings page submenu.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return current_user_can('manage_woocommerce');
    }


    /**
     * Handle the REST request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return mixed
     */
    abstract public function handle( WP_REST_Request $request );


    /**
     * Build a standardised success REST response.
     *
     * @since 6.0.0
     * @param array $data Additional key/value pairs to merge into the response body.
     * @return \WP_REST_Response
     */
    protected function success_response( array $data = array() ) {
        return rest_ensure_response( array_merge( array( 'status' => 'success' ), $data ) );
    }


    /**
     * Build a standardised error REST response.
     *
     * @since 6.0.0
     * @param string $message Human-readable error description (already escaped).
     * @param array  $data Optional additional key/value pairs.
     * @return \WP_REST_Response
     */
    protected function error_response( $message, array $data = array() ) {
        return rest_ensure_response( array_merge( array(
            'status' => 'error',
            'message' => $message,
        ), $data ) );
    }
}
