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

        if ( ! isset( $payload['index'] ) || ! Conditions_Store::remove_condition( $payload['index'] ) ) {
            return $this->error_response( __( 'Ops! Não foi possível excluir a condição.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'Condição excluída com sucesso!', 'flexify-checkout-for-woocommerce' ),
            'conditions' => Conditions_Store::get_conditions_for_client(),
        ) );
    }
}
