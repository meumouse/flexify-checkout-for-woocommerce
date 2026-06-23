<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Admin\Settings_Import_Export;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Export plugin settings as a JSON snapshot.
 *
 * GET flexify-checkout/v1/admin/settings/export. Returns the same payload the
 * legacy admin-ajax "flexify_checkout_export_settings" action streamed as a
 * file download; the Vue admin turns the JSON body into a downloadable file
 * client-side. Snapshot building stays single-sourced in Settings_Import_Export.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Settings_Export extends Abstract_Route {

    /**
     * Route path for exporting settings.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/admin/settings/export';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'GET';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        return $this->success_response( array(
            'payload' => Settings_Import_Export::build_payload(),
            'filename' => $this->build_filename(),
        ) );
    }


    /**
     * Build the suggested download filename for the snapshot.
     *
     * @since 6.0.0
     * @return string
     */
    private function build_filename() {
        $site_slug = sanitize_title( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'site' );

        return sprintf( 'flexify-checkout-settings-%s-%s.json', $site_slug, gmdate('Y-m-d') );
    }
}
