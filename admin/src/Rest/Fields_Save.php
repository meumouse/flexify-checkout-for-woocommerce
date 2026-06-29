<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Fields_Store;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Persist checkout step field updates.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Fields_Save extends Abstract_Route {

    /**
     * Route path for saving checkout fields.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/fields';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        if ( ! License::is_valid() ) {
            return $this->error_response( __( 'The fields manager requires an active Pro license.', 'flexify-checkout-for-woocommerce' ) );
        }

        $payload = $request->get_json_params();
        $incoming = isset( $payload['fields'] ) && is_array( $payload['fields'] ) ? $payload['fields'] : array();

        if ( empty( $incoming ) ) {
            return $this->error_response( __( 'No field received.', 'flexify-checkout-for-woocommerce' ) );
        }

        $fields = Fields_Store::save_fields( $incoming );

        return $this->success_response( array(
            'message' => __( 'The fields have been updated!', 'flexify-checkout-for-woocommerce' ),
            'fields' => $fields,
        ) );
    }
}
