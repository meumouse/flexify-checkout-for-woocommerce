<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Conditions_Store;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Create a new checkout condition.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Conditions_Add extends Abstract_Route {

    /**
     * Route path for creating conditions.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/conditions';

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
            return $this->error_response( __( 'The conditions manager requires an active Pro license.', 'flexify-checkout-for-woocommerce' ) );
        }

        $payload = $request->get_json_params();
        $rule = isset( $payload['rule'] ) ? $payload['rule'] : array();

        if ( empty( $rule['action'] ) ) {
            return $this->error_response( __( 'Invalid rule data.', 'flexify-checkout-for-woocommerce' ) );
        }

        $id = Conditions_Store::add_rule( $rule );

        if ( ! $id ) {
            return $this->error_response( __( 'Oops! Could not create a new rule.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'Rule created successfully!', 'flexify-checkout-for-woocommerce' ),
            'id' => $id,
            'conditions' => Conditions_Store::get_rules_for_client(),
        ) );
    }
}
