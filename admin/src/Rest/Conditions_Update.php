<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Conditions_Store;
use MeuMouse\Flexify_Checkout\API\License;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Update an existing checkout condition.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Conditions_Update extends Abstract_Route {

    /**
     * Route path for updating conditions.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/conditions/update';

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
        $id = sanitize_text_field( (string) ( $payload['id'] ?? '' ) );
        $rule = isset( $payload['rule'] ) ? $payload['rule'] : array();

        if ( '' === $id || empty( $rule['action'] ) ) {
            return $this->error_response( __( 'Invalid rule data.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( ! Conditions_Store::update_rule( $id, $rule ) ) {
            return $this->error_response( __( 'Oops! Could not update the rule.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'Rule updated successfully!', 'flexify-checkout-for-woocommerce' ),
            'conditions' => Conditions_Store::get_rules_for_client(),
        ) );
    }
}
