<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Core\Webhooks\Dispatcher;
use MeuMouse\Flexify_Checkout\Core\Webhooks\Event_Registry;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Global checkout webhooks — send-test endpoint.
 *
 * POST flexify-checkout/v1/webhooks/test with { event_key, endpoint } sends a
 * single test request to the given endpoint and returns the HTTP result, so the
 * admin can validate a URL before saving.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Webhooks_Test extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/webhooks/test';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';


    /**
     * Pro-gated permission.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return current_user_can('manage_woocommerce') && License::is_valid();
    }


    /**
     * Handle the test request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $event_key = sanitize_key( (string) $request->get_param('event_key') );
        $endpoint = $request->get_param('endpoint');
        $endpoint = is_array( $endpoint ) ? $endpoint : array();

        if ( ! Event_Registry::has_event( $event_key ) ) {
            return $this->error_response( esc_html__( 'Invalid event.', 'flexify-checkout-for-woocommerce' ) );
        }

        $url = isset( $endpoint['url'] ) ? esc_url_raw( trim( (string) $endpoint['url'] ) ) : '';

        if ( $url === '' || ! wp_http_validate_url( $url ) ) {
            return $this->error_response( esc_html__( 'Invalid webhook URL.', 'flexify-checkout-for-woocommerce' ) );
        }

        $result = Dispatcher::send_test( $event_key, array(
            'url' => $url,
            'headers' => isset( $endpoint['headers'] ) && is_array( $endpoint['headers'] ) ? $endpoint['headers'] : array(),
        ) );

        if ( empty( $result['ok'] ) ) {
            return $this->error_response(
                esc_html__( 'Failed to send the test webhook.', 'flexify-checkout-for-woocommerce' ),
                array( 'http_code' => $result['http_code'] ?? 0, 'error' => $result['error'] ?? '' )
            );
        }

        return $this->success_response( array( 'http_code' => $result['http_code'] ) );
    }
}
