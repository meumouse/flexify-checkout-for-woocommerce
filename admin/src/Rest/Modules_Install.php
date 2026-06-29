<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings\Integrations_Data;
use MeuMouse\Flexify_Checkout\Core\Modules;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Install (or upgrade) and activate an integration module.
 *
 * Mirrors the legacy flexify_checkout_install_modules AJAX behavior.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Modules_Install extends Abstract_Route {

    /**
     * Route path for installing modules.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/modules/install';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';


    /**
     * Only administrators that can install plugins may use this route.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return current_user_can('install_plugins');
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
        $plugin_zip = isset( $payload['download_url'] ) ? esc_url_raw( (string) $payload['download_url'] ) : '';

        if ( '' === $plugin_slug || '' === $plugin_zip ) {
            return $this->error_response( __( 'Invalid module data.', 'flexify-checkout-for-woocommerce' ) );
        }

        $modules = new Modules();

        // Capture any output to avoid HTML mixed with JSON
        ob_start();

        if ( $modules->is_plugin_installed( $plugin_slug ) ) {
            $installed = $modules->upgrade_plugin( $plugin_slug );
        } else {
            $installed = $modules->install_plugin( $plugin_zip );
        }

        ob_end_clean();

        if ( is_wp_error( $installed ) || ! $installed ) {
            return $this->error_response( __( 'Failed to install/update the plugin.', 'flexify-checkout-for-woocommerce' ) );
        }

        $activate = activate_plugin( WP_PLUGIN_DIR . '/' . $plugin_slug );

        if ( is_wp_error( $activate ) ) {
            return $this->error_response(
                __( 'The plugin was installed, but could not be activated.', 'flexify-checkout-for-woocommerce' ),
                array( 'integrations' => Integrations_Data::get_cards_for_client() )
            );
        }

        return $this->success_response( array(
            'message' => __( 'Plugin installed and activated successfully.', 'flexify-checkout-for-woocommerce' ),
            'integrations' => Integrations_Data::get_cards_for_client(),
        ) );
    }
}
