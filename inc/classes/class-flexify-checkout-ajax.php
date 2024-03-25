<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for Handle AJAX events
 *
 * @since 1.0.0
 * @version 1.8.5
 * @package MeuMouse.com
 */
class Flexify_Checkout_Ajax {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_flexify_check_for_inline_errors', array( __CLASS__, 'check_for_inline_errors' ) );
		add_action( 'wp_ajax_nopriv_flexify_check_for_inline_errors', array( __CLASS__, 'check_for_inline_errors' ) );
		add_action( 'wp_ajax_flexify_check_for_inline_error', array( __CLASS__, 'check_for_inline_error' ) );
		add_action( 'wp_ajax_nopriv_flexify_check_for_inline_error', array( __CLASS__, 'check_for_inline_error' ) );
		add_action( 'wp_ajax_flexify_login', array( __CLASS__, 'login' ) );
		add_action( 'wp_ajax_nopriv_flexify_login', array( __CLASS__, 'login' ) );
	}


	/**
	 * Check for inline errors
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function check_for_inline_errors() {
		$fields = filter_input( INPUT_POST, 'fields', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
		$messages = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$messages[ $field['key'] ] = Flexify_Checkout_Core::render_inline_errors( '', $field['key'], $field['args'], $field['value'], $field['country'] );
		}

		$messages['fragments'] = array(
			'.flexify-review-customer' => Flexify_Checkout_Steps::get_review_customer_fragment(),
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
		Flexify_Checkout_Core::render_inline_errors();
	}


	/**
	 * Login
	 *
	 * @since 1.0.0
	 * @throws Exception On login error.
	 */
	public static function login() {
		check_admin_referer( 'woocommerce-login' );

		try {
			$username = filter_input( INPUT_POST, 'username' );
			$password = filter_input( INPUT_POST, 'password' );
			$rememberme = filter_input( INPUT_POST, 'rememberme' );

			$creds = array(
				'user_login' => trim( $username ),
				'user_password' => $password,
				'remember' => ! empty( $rememberme ),
			);

			$validation_error = new WP_Error();

			/**
			 * Process login Validation Error.
			 *
			 * @since 1.0.0
			 */
			$validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password'] );

			if ( $validation_error->get_error_code() ) {
				throw new Exception( '<strong>' . __( 'Erro:', 'woocommerce' ) . '</strong> ' . $validation_error->get_error_message() );
			}

			if ( empty( $creds['user_login'] ) ) {
				throw new Exception( '<strong>' . __( 'Erro:', 'woocommerce' ) . '</strong> ' . __( 'Usuário é obrigatório.', 'woocommerce' ) );
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
				throw new Exception( $user->get_error_message() );
			} else {
				wp_send_json_success();
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}
}

new Flexify_Checkout_Ajax();