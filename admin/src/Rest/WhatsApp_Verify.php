<?php

namespace MeuMouse\Flexify_Checkout\Rest;

use MeuMouse\Flexify_Checkout\Checkout\Headless_Data;
use WP_REST_Server;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * POST /flexify-checkout/v1/auth/whatsapp/verify
 *
 * Validate a WhatsApp one-time code. On success, log the matching user in (if
 * one exists for the phone) and return fresh Store API / REST nonces so the
 * React checkout can keep talking to the authenticated session.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Rest
 * @author MeuMouse.com
 */
class WhatsApp_Verify extends Abstract_Route {

    use WhatsApp_Login_Trait;

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/auth/whatsapp/verify';

    /**
     * HTTP method.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = WP_REST_Server::CREATABLE;

    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'phone' => array(
            'required' => true,
            'type' => 'string',
        ),
        'code' => array(
            'required' => true,
            'type' => 'string',
        ),
    );


    /**
     * Only register when WhatsApp login is available.
     *
     * @since 6.0.0
     * @return bool
     */
    protected function should_register() {
        return Headless_Data::is_whatsapp_login_available();
    }


    /**
     * Public access guarded by a REST nonce.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return $this->verify_rest_nonce( $request );
    }


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $phone = $this->normalize_phone( (string) $request->get_param('phone') );
        $code = preg_replace( '/\D/', '', (string) $request->get_param('code') );
        $key = $this->otp_transient_key( $phone );
        $stored = get_transient( $key );

        if ( ! is_array( $stored ) || empty( $stored['hash'] ) ) {
            return $this->error_response( __( 'Código expirado. Solicite um novo.', 'flexify-checkout-for-woocommerce' ) );
        }

        $attempts = (int) ( $stored['attempts'] ?? 0 );

        if ( $attempts >= 5 ) {
            delete_transient( $key );

            return $this->error_response( __( 'Muitas tentativas. Solicite um novo código.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( ! hash_equals( (string) $stored['hash'], wp_hash( $code ) ) ) {
            $stored['attempts'] = $attempts + 1;
            set_transient( $key, $stored, 5 * MINUTE_IN_SECONDS );

            return $this->error_response( __( 'Código inválido. Tente novamente.', 'flexify-checkout-for-woocommerce' ) );
        }

        // Code is valid — consume it.
        delete_transient( $key );

        $user = $this->find_user_by_phone( $phone );

        if ( $user instanceof \WP_User ) {
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true );

            /**
             * Fires after a successful WhatsApp login.
             *
             * @since 6.0.0
             * @param \WP_User $user Logged-in user.
             * @param string $phone Normalized phone.
             */
            do_action( 'Flexify_Checkout/React_Checkout/WhatsApp_Logged_In', $user, $phone );

            return $this->success_response( array(
                'logged_in' => true,
                'phone' => $phone,
                'user' => array(
                    'id' => $user->ID,
                    'display_name' => $user->display_name,
                    'email' => $user->user_email,
                    'first_name' => get_user_meta( $user->ID, 'first_name', true ),
                ),
                'wp_rest_nonce' => wp_create_nonce('wp_rest'),
                'store_api_nonce' => wp_create_nonce('wc_store_api'),
            ) );
        }

        // No account — verified guest, return the phone for prefilling.
        return $this->success_response( array(
            'logged_in' => false,
            'verified' => true,
            'phone' => $phone,
        ) );
    }
}
