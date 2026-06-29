<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Conditions_Store;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Remove a checkout condition.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Conditions_Remove extends Abstract_Route {

    /**
     * Route path for removing conditions.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/conditions/remove';

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
        $payload = $request->get_json_params();
        $id = sanitize_text_field( (string) ( $payload['id'] ?? '' ) );

        if ( '' === $id || ! Conditions_Store::remove_rule( $id ) ) {
            return $this->error_response( __( 'Oops! Could not delete the rule.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'Rule deleted successfully!', 'flexify-checkout-for-woocommerce' ),
            'conditions' => Conditions_Store::get_rules_for_client(),
        ) );
    }
}
