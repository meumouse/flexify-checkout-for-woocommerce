<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\API\MDS;
use WP_REST_Request;
use WP_REST_Response;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Inbound license webhook receiver.
 *
 * Lets the MDS server push license lifecycle events (deactivation, expiry,
 * revocation, reactivation) to the site so it reacts immediately instead of
 * waiting for the daily heartbeat — e.g. after a refund, chargeback, admin
 * revoke or domain unbind.
 *
 * Security: this is a public, server-to-server endpoint (no cookie/nonce auth).
 * Authenticity is enforced by the same ed25519 signature scheme the SDK uses
 * for API responses: the server signs "{timestamp}.{nonce}.{sha256(raw_body)}"
 * with its private key and sends X-Mds-Signature / X-Mds-Timestamp /
 * X-Mds-Nonce headers; we verify with the embedded public key and refuse
 * anything unsigned, stale (±5 min) or replayed. Fail-closed.
 *
 * Depends on the SDK being embedded (SignatureVerifier) and a configured
 * public key; until then the endpoint rejects every call.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class License_Webhook extends Abstract_Route {

    /**
     * Route path for the inbound license webhook.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/license/webhook';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';

    /**
     * Transient prefix used to remember recently seen nonces (anti-replay).
     *
     * @since 6.0.0
     * @var string
     */
    const NONCE_PREFIX = 'flexify_checkout_mds_wh_nonce_';


    /**
     * Public endpoint: authentication is by signature, not by capability.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return true;
    }


    /**
     * Handle an inbound webhook.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $public_key = MDS::public_key();

        if ( '' === $public_key || ! class_exists('\MeuMouse\MDS\SDK\Security\SignatureVerifier') ) {
            return new WP_REST_Response( array( 'status' => 'error', 'message' => 'Webhook not configured.' ), 503 );
        }

        $raw_body = (string) $request->get_body();

        $headers = array(
            'x-mds-signature' => (string) $request->get_header('x-mds-signature'),
            'x-mds-timestamp' => (string) $request->get_header('x-mds-timestamp'),
            'x-mds-nonce'     => (string) $request->get_header('x-mds-nonce'),
        );

        $verifier = new \MeuMouse\MDS\SDK\Security\SignatureVerifier( $public_key );

        if ( ! $verifier->verify( $raw_body, $headers ) ) {
            return new WP_REST_Response( array( 'status' => 'error', 'message' => 'Invalid signature.' ), 401 );
        }

        // Replay guard: a signed message is valid only once within its window.
        $nonce = $headers['x-mds-nonce'];

        if ( '' !== $nonce ) {
            $seen_key = self::NONCE_PREFIX . md5( $nonce );

            if ( get_transient( $seen_key ) ) {
                return new WP_REST_Response( array( 'status' => 'ignored', 'message' => 'Replay.' ), 200 );
            }

            set_transient( $seen_key, 1, 10 * MINUTE_IN_SECONDS );
        }

        $payload = json_decode( $raw_body, true );

        if ( ! is_array( $payload ) ) {
            return new WP_REST_Response( array( 'status' => 'error', 'message' => 'Malformed payload.' ), 400 );
        }

        $event = isset( $payload['event'] ) ? (string) $payload['event'] : '';
        $license_key = isset( $payload['license_key'] ) ? (string) $payload['license_key'] : '';
        $domain = isset( $payload['domain'] ) ? (string) $payload['domain'] : '';
        $reason = isset( $payload['reason'] ) ? (string) $payload['reason'] : '';

        // Bind the event to this site and to the stored key.
        $stored_key = (string) get_option('flexify_checkout_license_key', '');

        if ( '' !== $license_key && '' !== $stored_key && ! hash_equals( $stored_key, $license_key ) ) {
            return new WP_REST_Response( array( 'status' => 'ignored', 'message' => 'License mismatch.' ), 200 );
        }

        if ( '' !== $domain && ! $this->domain_matches( $domain ) ) {
            return new WP_REST_Response( array( 'status' => 'ignored', 'message' => 'Domain mismatch.' ), 200 );
        }

        switch ( $event ) {
            case 'license.deactivated':
            case 'license.revoked':
            case 'license.expired':
                $this->deactivate_locally( $reason );
                break;

            case 'license.reactivated':
                // Force a fresh validation on the next check so features re-enable.
                delete_transient('flexify_checkout_license_status_cached');

                if ( MDS::is_enabled() && ( $manager = MDS::license() ) ) {
                    $manager->validate();
                }
                break;

            default:
                return new WP_REST_Response( array( 'status' => 'ignored', 'message' => 'Unhandled event.' ), 200 );
        }

        return new WP_REST_Response( array( 'status' => 'success' ), 200 );
    }


    /**
     * Whether the payload domain matches this installation.
     *
     * @since 6.0.0
     * @param string $domain Domain reported by the webhook.
     * @return bool
     */
    private function domain_matches( $domain ) {
        $candidates = array( home_url(), site_url() );

        $normalize = static function( $url ) {
            $host = wp_parse_url( $url, PHP_URL_HOST );

            return $host ? strtolower( $host ) : strtolower( untrailingslashit( (string) $url ) );
        };

        $incoming = $normalize( $domain );

        foreach ( $candidates as $candidate ) {
            if ( $normalize( $candidate ) === $incoming ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Clear local license state so Pro features gate off immediately.
     *
     * Idempotent: does nothing meaningful if already deactivated. Does NOT call
     * the licensing server (this is a server-initiated event); it only tears
     * down local state and lets the SDK manager forget the activation too.
     *
     * @since 6.0.0
     * @param string $reason Reason reported by the server, for the action hook.
     * @return void
     */
    private function deactivate_locally( $reason ) {
        if ( MDS::is_enabled() && ( $manager = MDS::license() ) ) {
            $manager->deactivate();
        }

        update_option( 'flexify_checkout_license_status', 'invalid' );
        delete_option('flexify_checkout_license_key');
        delete_option('flexify_checkout_license_response_object');
        delete_option('flexify_checkout_temp_license_key');
        delete_option('flexify_checkout_alternative_license');
        delete_option('flexify_checkout_alternative_license_activation');
        delete_option('flexify_checkout_alternative_license_decrypted');
        delete_transient('flexify_checkout_license_status_cached');
        delete_transient('flexify_checkout_api_request_cache');
        delete_transient('flexify_checkout_api_response_cache');

        /**
         * Fires after a license is deactivated by a signed server webhook.
         *
         * @since 6.0.0
         * @param string $reason Reason reported by the server (refund, chargeback, ...).
         */
        do_action( 'Flexify_Checkout/License/Webhook_Deactivated', $reason );
    }
}
