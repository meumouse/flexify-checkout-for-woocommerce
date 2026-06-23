<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings_Import_Export;
use MeuMouse\Flexify_Checkout\Admin\Settings\Registry;
use MeuMouse\Flexify_Checkout\Admin\Settings\Repository;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Import a plugin settings snapshot.
 *
 * POST flexify-checkout/v1/admin/settings/import. Validates and applies a
 * payload previously produced by the export endpoint, replacing the legacy
 * admin-ajax "flexify_checkout_import_settings" action. The Vue admin reads the
 * uploaded file, parses the JSON and sends the object in the `payload` param.
 * Validation and persistence stay single-sourced in Settings_Import_Export.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Settings_Import extends Abstract_Route {

    /**
     * Route path for importing settings.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/settings/import';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';

    /**
     * REST argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'payload' => array(
            'required' => true,
            'type' => 'object',
        ),
    );


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $payload = $request->get_param('payload');

        if ( ! is_array( $payload ) ) {
            return $this->error_response( __( 'Não foi possível ler o arquivo. Verifique se é um JSON de configurações válido.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( ! isset( $payload['format'] ) || $payload['format'] !== Settings_Import_Export::FORMAT || ! isset( $payload['data'] ) || ! is_array( $payload['data'] ) ) {
            return $this->error_response( __( 'Este arquivo não é uma exportação de configurações do Flexify Checkout.', 'flexify-checkout-for-woocommerce' ) );
        }

        $applied = Settings_Import_Export::apply_payload( $payload['data'] );

        if ( ! $applied ) {
            return $this->error_response( __( 'O arquivo não continha configurações aplicáveis.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'As configurações foram importadas com sucesso!', 'flexify-checkout-for-woocommerce' ),
            'settings' => Repository::get_settings(),
            'runtime' => Registry::get_runtime_data(),
            'reload' => true,
        ) );
    }
}
