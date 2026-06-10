<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Registry;
use MeuMouse\Flexify_Checkout\Admin\Settings\Repository;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Reset plugin settings to defaults.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Settings_Reset extends Abstract_Route {

    /**
     * Route path for resetting settings.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/settings/reset';

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
        $reset = Repository::reset_settings();

        if ( ! $reset ) {
            return $this->error_response( __( 'Ocorreu um erro ao redefinir as configurações.', 'flexify-checkout-for-woocommerce' ) );
        }

        return $this->success_response( array(
            'message' => __( 'As opções foram redefinidas com sucesso!', 'flexify-checkout-for-woocommerce' ),
            'settings' => Repository::get_settings(),
            'runtime' => Registry::get_runtime_data(),
        ) );
    }
}
