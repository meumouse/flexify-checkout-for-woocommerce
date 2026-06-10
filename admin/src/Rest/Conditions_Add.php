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
            return $this->error_response( __( 'O gerenciador de condições requer uma licença Pro ativa.', 'flexify-checkout-for-woocommerce' ) );
        }

        $payload = $request->get_json_params();
        $condition = Conditions_Store::sanitize_payload( $payload['condition'] ?? array() );

        if ( empty( $condition['type_rule'] ) || 'none' === $condition['type_rule'] ) {
            return $this->error_response( __( 'Dados da condição inválidos.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( ! Conditions_Store::add_condition( $condition ) ) {
            return $this->error_response( __( 'Ops! Não foi possível criar uma nova condição.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'Condição criada com sucesso!', 'flexify-checkout-for-woocommerce' ),
            'conditions' => Conditions_Store::get_conditions_for_client(),
        ) );
    }
}
