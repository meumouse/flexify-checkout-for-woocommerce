<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Integrations_Data;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Activate an installed integration module.
 *
 * Mirrors the legacy flexify_checkout_activate_plugin AJAX behavior.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Modules_Activate extends Abstract_Route {

    /**
     * Route path for activating modules.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/modules/activate';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';


    /**
     * Only administrators that can activate plugins may use this route.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return current_user_can('activate_plugins');
    }


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $payload = $request->get_json_params();
        $plugin_slug = isset( $payload['slug'] ) ? sanitize_text_field( (string) $payload['slug'] ) : '';

        if ( '' === $plugin_slug ) {
            return $this->error_response( __( 'Dados do módulo inválidos.', 'flexify-checkout-for-woocommerce' ) );
        }

        $activate = activate_plugin( $plugin_slug );

        if ( is_wp_error( $activate ) ) {
            return $this->error_response( __( 'Não foi possível ativar o plugin.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'Plugin ativado com sucesso.', 'flexify-checkout-for-woocommerce' ),
            'integrations' => Integrations_Data::get_cards_for_client(),
        ) );
    }
}
