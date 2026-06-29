<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Layout_Store;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Return the checkout builder layout plus the field catalog.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Layout_Get extends Abstract_Route {

    /**
     * Route path for reading the layout.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/layout';

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
        if ( ! License::is_valid() ) {
            return $this->error_response( __( 'The checkout builder requires an active Pro license.', 'flexify-checkout-for-woocommerce' ) );
        }

        $payload = Layout_Store::get_layout_for_client();

        return $this->success_response( array(
            'layout' => array(
                'version' => $payload['version'],
                'steps' => $payload['steps'],
            ),
            'field_catalog' => $payload['field_catalog'],
        ) );
    }
}
