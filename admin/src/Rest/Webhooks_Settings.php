<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Core\Webhooks\Event_Registry;
use MeuMouse\Flexify_Checkout\Core\Webhooks\Webhook_Settings;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Global checkout webhooks — settings endpoint.
 *
 * GET/POST flexify-checkout/v1/webhooks/settings. Reads the event catalog
 * (grouped by category) and the stored endpoint configuration, and persists
 * the per-event endpoint list. Backs the "Webhooks" tab of the Vue settings.
 *
 * Pro-gated: webhooks were previously available only behind the Pro recovery
 * tab, so this keeps the same commercial gating.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class Webhooks_Settings extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/webhooks/settings';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var array
     */
    protected $methods = array( 'GET', 'POST' );


    /**
     * Pro-gated permission on top of the manage_woocommerce capability.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return current_user_can('manage_woocommerce') && License::is_valid();
    }


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        if ( 'POST' === $request->get_method() ) {
            return $this->save( $request );
        }

        return $this->success_response( array(
            'events_grouped' => Event_Registry::get_events_grouped(),
            'categories' => Event_Registry::get_categories(),
            'settings' => Webhook_Settings::get_all(),
        ) );
    }


    /**
     * Persist the submitted webhooks map.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    private function save( WP_REST_Request $request ) {
        $input = $request->get_param('webhooks');
        $input = is_array( $input ) ? $input : array();

        $clean = $this->sanitize_webhooks( $input );

        Webhook_Settings::save_all( $clean );

        return $this->success_response( array(
            'settings' => Webhook_Settings::get_all(),
        ) );
    }


    /**
     * Sanitize the incoming webhooks map. Only known event keys are kept.
     *
     * @since 6.0.0
     * @param array $input Raw { event_key => [ {enabled,url,headers} ] } map.
     * @return array
     */
    private function sanitize_webhooks( array $input ) {
        $clean = array();

        foreach ( $input as $event_key => $endpoints ) {
            $event_key = sanitize_key( $event_key );

            if ( ! Event_Registry::has_event( $event_key ) || ! is_array( $endpoints ) ) {
                continue;
            }

            $list = array();

            foreach ( $endpoints as $endpoint ) {
                if ( ! is_array( $endpoint ) ) {
                    continue;
                }

                $url = isset( $endpoint['url'] ) ? esc_url_raw( trim( (string) $endpoint['url'] ) ) : '';

                if ( $url === '' ) {
                    continue;
                }

                $list[] = array(
                    'enabled' => ( isset( $endpoint['enabled'] ) && $endpoint['enabled'] === 'no' ) ? 'no' : 'yes',
                    'url' => $url,
                    'headers' => $this->sanitize_headers( isset( $endpoint['headers'] ) ? $endpoint['headers'] : array() ),
                );
            }

            if ( ! empty( $list ) ) {
                $clean[ $event_key ] = $list;
            }
        }

        return $clean;
    }


    /**
     * Sanitize a list of custom headers ({name,value} rows).
     *
     * @since 6.0.0
     * @param mixed $headers Raw headers value.
     * @return array<int,array{name:string,value:string}>
     */
    private function sanitize_headers( $headers ) {
        if ( ! is_array( $headers ) ) {
            return array();
        }

        $clean = array();

        foreach ( $headers as $header ) {
            if ( ! is_array( $header ) ) {
                continue;
            }

            $name = isset( $header['name'] ) ? sanitize_text_field( trim( (string) $header['name'] ) ) : '';
            $value = isset( $header['value'] ) ? sanitize_text_field( trim( (string) $header['value'] ) ) : '';

            if ( $name !== '' ) {
                $clean[] = array( 'name' => $name, 'value' => $value );
            }
        }

        return $clean;
    }
}
