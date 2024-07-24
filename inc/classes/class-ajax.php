<?php

namespace MeuMouse\Flexify_Checkout\Ajax;

use MeuMouse\Flexify_Checkout\Init\Init;
use MeuMouse\Flexify_Checkout\Core\Core;
use MeuMouse\Flexify_Checkout\Steps\Steps;
use MeuMouse\Flexify_Checkout\Helpers\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for Handle AJAX events
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Ajax {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @version 3.7.0
	 * @return void
	 */
	public function __construct() {
		// get AJAX call on check inline errors
		add_action( 'wp_ajax_flexify_check_for_inline_error', array( __CLASS__, 'check_for_inline_error' ) );

		// get AJAX call on check inline errors for not logged users
		add_action( 'wp_ajax_nopriv_flexify_check_for_inline_error', array( __CLASS__, 'check_for_inline_error' ) );

		// get AJAX call on check error on proceed step
		add_action( 'wp_ajax_flexify_check_for_inline_errors', array( __CLASS__, 'check_for_inline_errors' ) );

		// get AJAX call on check error on proceed step for not logged users
		add_action( 'wp_ajax_nopriv_flexify_check_for_inline_errors', array( __CLASS__, 'check_for_inline_errors' ) );

		// get AJAX call on login event
		add_action( 'wp_ajax_flexify_login', array( __CLASS__, 'login' ) );

		// get AJAX call on login event for not logged users
		add_action( 'wp_ajax_nopriv_flexify_login', array( __CLASS__, 'login' ) );
	}


	/**
	 * Check for inline errors
	 * 
	 * @since 1.0.0
	 * @version 3.6.5
	 * @return void
	 */
	public static function check_for_inline_errors() {
		// filter and sanitize array fields from frontend
		$fields = filter_input( INPUT_POST, 'fields', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		$messages = array();

		foreach ( $fields as $field ) {
			$messages[$field['key']] = Core::render_inline_errors( $field['id'], $field['key'], $field['args'], $field['value'], $field['country'] );
		}

		$messages['fragments'] = array(
			'.flexify-review-customer' => Steps::render_customer_review(),
			'.flexify-checkout-review-customer-contact' => Steps::strings_to_replace( Init::get_setting('text_contact_customer_review'), Steps::get_review_customer_fragment() ),
			'.flexify-checkout-review-shipping-address' => Steps::strings_to_replace( Init::get_setting('text_shipping_customer_review'), Steps::get_review_customer_fragment() ),
			'.flexify-checkout-review-shipping-method' => Helpers::get_shipping_method(),
		);

		wp_send_json_success( $messages );
		
		exit;
	}


	/**
	 * Check for inline error for the given field
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function check_for_inline_error() {
		Core::render_inline_errors();
	}


	/**
	 * Login
	 *
	 * @since 1.0.0
	 * @throws Exception On login error.
	 */
	public static function login() {
		check_admin_referer('woocommerce-login');

		try {
			$username = filter_input( INPUT_POST, 'username' );
			$password = filter_input( INPUT_POST, 'password' );
			$rememberme = filter_input( INPUT_POST, 'rememberme' );

			$creds = array(
				'user_login' => trim( $username ),
				'user_password' => $password,
				'remember' => ! empty( $rememberme ),
			);

			$validation_error = new \WP_Error();

			/**
			 * Process login Validation Error.
			 *
			 * @since 1.0.0
			 */
			$validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password'] );

			if ( $validation_error->get_error_code() ) {
				throw new \Exception( '<strong>' . __( 'Erro:', 'woocommerce' ) . '</strong> ' . $validation_error->get_error_message() );
			}

			if ( empty( $creds['user_login'] ) ) {
				throw new \Exception( '<strong>' . __( 'Erro:', 'woocommerce' ) . '</strong> ' . __( 'Usuário é obrigatório.', 'woocommerce' ) );
			}

			// On multisite, ensure user exists on current site, if not add them before allowing login.
			if ( is_multisite() ) {
				$user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

				if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
					add_user_to_blog( get_current_blog_id(), $user_data->ID, 'customer' );
				}
			}

			// Perform the login.

			/**
			 * Login credentials.
			 *
			 * @since 1.0.0
			 */
			$user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );

			if ( is_wp_error( $user ) ) {
				throw new \Exception( $user->get_error_message() );
			} else {
				wp_send_json_success();
			}
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}
}

new Ajax();