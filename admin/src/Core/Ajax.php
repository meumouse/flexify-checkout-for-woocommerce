<?php

namespace MeuMouse\Flexify_Checkout\Core;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Checkout\Fields;
use MeuMouse\Flexify_Checkout\Checkout\Steps;
use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Views\Components;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for handle AJAX events
 *
 * @since 1.0.0
 * @version 5.4.0

 * @author MeuMouse.com
 */
class Ajax {

	public $response_obj;
    public $license_message;

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @version 5.3.0
	 * @return void
	 */
	public function __construct() {
		$actions = array(
			// action => callback
			'flexify_check_for_inline_error'        => array( __CLASS__, 'check_for_inline_error' ),
			'flexify_check_for_inline_errors'       => array( __CLASS__, 'check_for_inline_errors' ),
			'flexify_checkout_login'                => array( $this, 'checkout_login_callback' ),
			'flexify_checkout_lostpassword'         => array( $this, 'checkout_lostpassword_callback' ),
			'dismiss_billing_country_warning'       => array( __CLASS__, 'dismiss_billing_country_warning' ),
			'get_checkout_session_data'             => array( $this, 'get_checkout_session_data_callback' ),
			'flexify_checkout_remove_product'       => array( $this, 'remove_product_callback' ),
			'flexify_checkout_undo_remove_product'  => array( $this, 'undo_remove_product_callback' ),
			'flexify_checkout_destroy_session'      => array( $this, 'destroy_session_callback' ),
		);

		// needs to be called by non-logged users
		$nopriv_actions = array(
			'flexify_check_for_inline_error',
			'flexify_check_for_inline_errors',
			'flexify_checkout_login',
			'flexify_checkout_lostpassword',
			'get_checkout_session_data',
			'flexify_checkout_remove_product',
			'flexify_checkout_undo_remove_product',
			'flexify_checkout_destroy_session',
		);

		foreach ( $actions as $action => $callback ) {
			$is_public = in_array( $action, $nopriv_actions, true );

			// Public actions keep the original callback; every other action only
			// runs on the admin settings screen, so gate it behind a capability
			// check to stop lower-privileged logged-in users (e.g. customers)
			// from reaching administrative handlers.
			$handler = $is_public ? $callback : self::guard_admin_action( $callback );

			add_action( "wp_ajax_{$action}", $handler );

			if ( $is_public ) {
				add_action( "wp_ajax_nopriv_{$action}", $callback );
			}
		}

		if ( Admin_Options::get_setting( 'enable_autofill_company_info' ) === 'yes' && License::is_valid() ) {
			add_action( 'wp_ajax_cnpj_autofill_query', array( __CLASS__, 'cnpj_autofill_query_callback' ) );
			add_action( 'wp_ajax_nopriv_cnpj_autofill_query', array( __CLASS__, 'cnpj_autofill_query_callback' ) );
		}
	}


	/**
	 * Wrap an admin AJAX callback with a capability check.
	 *
	 * Returns a closure that blocks the request with a JSON error when the
	 * current user lacks the WooCommerce management capability, otherwise it
	 * forwards the call to the original handler. Applied to every non-public
	 * action so customers/subscribers cannot reach administrative handlers.
	 *
	 * @since 5.5.5
	 * @param callable $callback | Original AJAX callback
	 * @return callable
	 */
	private static function guard_admin_action( $callback ) {
		return function() use ( $callback ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Você não tem permissão para executar esta ação.', 'flexify-checkout-for-woocommerce' ),
				), 403 );
			}

			// CSRF protection: the admin nonce is attached to every admin-ajax
			// request by the settings.js prefilter (field: flexify_admin_nonce).
			if ( ! check_ajax_referer( 'flexify_checkout_admin_nonce', 'flexify_admin_nonce', false ) ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Falha na verificação de segurança. Atualize a página e tente novamente.', 'flexify-checkout-for-woocommerce' ),
				), 403 );
			}

			return call_user_func( $callback );
		};
	}


	/**
	 * Update option on get AJAX call for hide notice
	 *
	 * @since 3.7.3
	 * @version 3.8.0
	 * @return void
	 */
	public static function dismiss_billing_country_warning() {
		update_user_meta( get_current_user_id(), 'hide_billing_country_notice', true );
		wp_die();
	}


	/**
	 * Check for inline errors
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public static function check_for_inline_errors() {
		// filter and sanitize array fields from frontend
		$fields = filter_input( INPUT_POST, 'fields', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );
		$messages = array();

		foreach ( $fields as $field ) {
			$field_id = isset( $field['id'] ) ? $field['id'] : '';
			$field_key = isset( $field['key'] ) ? $field['key'] : '';
			$field_args = isset( $field['args'] ) ? $field['args'] : array();
			$field_value = isset( $field['value'] ) ? $field['value'] : '';
			$field_country = isset( $field['country'] ) ? $field['country'] : '';

			$messages[$field_key] = Fields::render_inline_errors( $field_id, $field_key, $field_args, $field_value, $field_country );
		}

		$session_key = Steps::get_review_address_prefix();

		$messages['fragments'] = array(
			'.flexify-review-customer' => Steps::render_customer_review(),
			'.flexify-checkout-review-customer-contact' => Steps::replace_placeholders( Admin_Options::get_setting('text_contact_customer_review'), Steps::get_review_customer_fragment() ),
			'.flexify-checkout-review-shipping-address' => Steps::replace_placeholders( Admin_Options::get_setting('text_shipping_customer_review'), Steps::get_review_customer_fragment(), $session_key ),
			'.flexify-checkout-review-shipping-method' => Helpers::get_shipping_method(),
		);

		wp_send_json_success( $messages );
	}


	/**
	 * Check for inline error for the given field
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public static function check_for_inline_error() {
		Fields::render_inline_errors();
	}


	/**
	 * Handle with login form on checkout
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @throws Exception On login error
	 */
	public function checkout_login_callback() {
		check_admin_referer('woocommerce-login');

		try {
			$username = filter_input( INPUT_POST, 'username' );
			$password = filter_input( INPUT_POST, 'password' );
			$rememberme = filter_input( INPUT_POST, 'rememberme' );

			$credentials = array(
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
			$validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $credentials['user_login'], $credentials['user_password'] );

			if ( $validation_error->get_error_code() ) {
				throw new \Exception( '<strong>' . __( 'Erro:', 'flexify-checkout-for-woocommerce' ) . '</strong> ' . $validation_error->get_error_message() );
			}

			if ( empty( $credentials['user_login'] ) ) {
				throw new \Exception( '<strong>' . __( 'Erro:', 'flexify-checkout-for-woocommerce' ) . '</strong> ' . __( 'Usuário é obrigatório.', 'flexify-checkout-for-woocommerce' ) );
			}

			// On multisite, ensure user exists on current site, if not add them before allowing login.
			if ( is_multisite() ) {
				$user_data = get_user_by( is_email( $credentials['user_login'] ) ? 'email' : 'login', $credentials['user_login'] );

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
			$user = wp_signon( apply_filters( 'woocommerce_login_credentials', $credentials ), is_ssl() );

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


	/**
	 * Handle lost password request from checkout login modal.
	 *
	 * @since 5.5.0
	 * @return void
	 */
	public function checkout_lostpassword_callback() {
		$nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'flexify-checkout-lostpassword' ) ) {
			wp_send_json_error( array(
				'error' => __( 'Sua sessao expirou. Recarregue a pagina e tente novamente.', 'flexify-checkout-for-woocommerce' ),
			) );
		}

		$user_login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';

		if ( empty( $user_login ) || ! is_email( $user_login ) ) {
			wp_send_json_error( array(
				'error' => __( 'Por favor, insira um e-mail valido.', 'flexify-checkout-for-woocommerce' ),
			) );
		}

		$_POST['user_login'] = $user_login;
		$recover_password = retrieve_password();

		if ( is_wp_error( $recover_password ) ) {
			$error_codes = $recover_password->get_error_codes();

			// Keep a generic success response to prevent user enumeration.
			if ( in_array( 'invalidcombo', $error_codes, true ) ) {
				wp_send_json_success();
			}

			wp_send_json_error( array(
				'error' => __( 'Nao foi possivel enviar o e-mail de redefinição. Tente novamente.', 'flexify-checkout-for-woocommerce' ),
			) );
		}

		wp_send_json_success();
	}


	/**
	 * Save billing fields data in custom session
	 * 
	 * @since 1.8.5
	 * @version 5.0.0
	 * @return void
	 */
	public function get_checkout_session_data_callback() {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'get_checkout_session_data' ) {
			// Receive data from POST fields
			$fields_data = isset( $_POST['fields_data'] ) ? json_decode( stripslashes( $_POST['fields_data'] ), true ) : array();
			$ship_to_different_address = isset( $_POST['ship_to_different_address'] ) ? sanitize_text_field( $_POST['ship_to_different_address'] ) : '';
			$selected_shipping_method = isset( $_POST['selected_shipping_method'] ) ? sanitize_text_field( $_POST['selected_shipping_method'] ) : '';
			$selected_shipping_method_label = isset( $_POST['selected_shipping_method_label'] ) ? sanitize_text_field( $_POST['selected_shipping_method_label'] ) : '';
			$session_data = WC()->session->get( 'flexify_checkout_customer_fields' );
			$session_data = is_array( $session_data ) ? $session_data : array();
		
			foreach ( $fields_data as $field ) {
				// Add field and value to array if they exist and are not empty
				if ( isset( $field['field_id'] ) && isset( $field['value'] ) ) {
					$field_id = $field['field_id'];
					$field_value = sanitize_text_field( $field['value'] );
					$session_data[$field_id] = $field_value;
				}
			}

			WC()->session->set( 'flexify_checkout_customer_fields', $session_data );
			WC()->session->set( 'flexify_checkout_ship_different_address', $ship_to_different_address );

			if ( ! empty( $selected_shipping_method ) ) {
				WC()->session->set( 'flexify_checkout_selected_shipping_method', $selected_shipping_method );
			}

			if ( ! empty( $selected_shipping_method_label ) ) {
				WC()->session->set( 'flexify_checkout_selected_shipping_method_label', $selected_shipping_method_label );
			}

			wp_send_json_success( $session_data );
		}
	}


	/**
	 * AJAX callback function for get CNPJ data
	 * 
	 * @since 1.4.5
	 * @version 5.0.0
	 * @return void
	 */
	public static function cnpj_autofill_query_callback() {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'cnpj_autofill_query' ) {
			$cnpj = sanitize_text_field( $_POST['cnpj'] );
			$url = 'https://www.receitaws.com.br/v1/cnpj/' . $cnpj;
			$response = wp_safe_remote_get( $url );
		
			if ( is_wp_error( $response ) ) {
				return false;
			}
		
			$body = wp_remote_retrieve_body( $response );

			if ( empty( $body ) ) {
				wp_send_json_error( 'Empty response from API.' );
			}
		
			$data = json_decode( $body, true );

			if ( null === $data ) {
				wp_send_json_error( 'Fail on decode JSON.' );
			}

			wp_send_json_success( $data );
		}
	}


	/**
	 * Remove product from cart via AJAX
	 * 
	 * @since 5.0.0
	 * @return void
	 */
	public function remove_product_callback() {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'flexify_checkout_remove_product' ) {
			check_ajax_referer( 'flexify_checkout_remove_product', 'nonce' );

			$product_id = absint( $_POST['product_id'] ?? 0 );
			$cart_item_key = sanitize_text_field( $_POST['cart_item_key'] ?? '' );

			if ( ! $cart_item_key || ! WC()->cart->get_cart_item( $cart_item_key ) ) {
				wp_send_json_error( array(
					'message' => 'Produto não encontrado no carrinho.',
				));
			}

			// get product object
			$product = wc_get_product( $product_id );

			// remove product item from cart
			WC()->cart->remove_cart_item( $cart_item_key );

			$message = sprintf(
				__( '<strong>%s</strong> removido do carrinho. <strong><a class="undo-remove-product" data-product_id="%d" href="#">Desfazer</a></strong>', 'flexify-checkout-for-woocommerce' ),
				esc_html( $product->get_name() ),
				$product->get_id()
			);

			// send response
			wp_send_json_success( array(
				'message' => 'Product removed successfully.',
				'cart_item_key' => $cart_item_key,
				'notice_html' => Components::render_notice( $message, 'success' ),
			));
		}
	}


	/**
	 * Undo remove product from cart via AJAX
	 * 
	 * @since 5.0.0
	 * @return void
	 */
	public function undo_remove_product_callback() {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'flexify_checkout_undo_remove_product' ) {
			check_ajax_referer( 'flexify_checkout_undo_remove_product', 'nonce' );

			$product_id = absint( $_POST['product_id'] ?? 0 );
			$quantity = 1;

			if ( ! $product_id || ! $quantity ) {
				wp_send_json_error( array(
					'message' => 'Invalid product data',
				));
			}

			$added = WC()->cart->add_to_cart( $product_id, $quantity );

			if ( $added ) {
				wp_send_json_success( array(
					'message' => 'Product re-added.',
				));
			}

			wp_send_json_error( array(
				'message' => 'Fail on re-add product.',
			));
		}
	}


	/**
	 * Destroy WooCommerce session
	 *
	 * @since 5.2.0
	 * @return void
	 */
	public function destroy_session_callback() {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'flexify_checkout_destroy_session' ) {
			$cart_items = array();

			if ( WC()->cart ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $item ) {
					$product        = $item['data'] ?? null;
					$product_id     = $item['product_id'] ?? 0;
					$variation_id   = $item['variation_id'] ?? 0;
					$qty            = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
					$line_subtotal  = isset( $item['line_subtotal'] ) ? (float) $item['line_subtotal'] : 0.0;
					$line_total     = isset( $item['line_total'] ) ? (float) $item['line_total'] : 0.0;
					$name           = $product ? $product->get_name() : get_the_title( $product_id );

					$cart_items[] = array(
						'key'           => $cart_item_key,
						'product_id'    => $product_id,
						'variation_id'  => $variation_id,
						'name'          => $name,
						'quantity'      => $qty,
						'line_subtotal' => $line_subtotal,
						'line_total'    => $line_total,
						'variation'     => isset( $item['variation'] ) ? $item['variation'] : array(),
						'meta'          => isset( $item['addons'] ) ? $item['addons'] : array(),
					);
				}
			}

			$cart_totals = array();

			if ( WC()->cart ) {
				$cart_totals = array(
					'subtotal'        => (float) WC()->cart->get_subtotal(),
					'discount_total'  => (float) WC()->cart->get_discount_total(),
					'fee_total'       => (float) WC()->cart->get_fee_total(),
					'shipping_total'  => (float) WC()->cart->get_shipping_total(),
					'total'           => (float) WC()->cart->get_total( 'edit' ),
					'tax_total'       => (float) WC()->cart->get_total_tax(),
					'items_count'     => (int)   WC()->cart->get_cart_contents_count(),
				);
			}

			$session_data = WC()->session->get('flexify_checkout_customer_fields');
			$applied_coupons = WC()->cart ? WC()->cart->get_applied_coupons() : array();
			$redirect_url = Admin_Options::get_setting('checkout_countdown_redirect_url');

			/**
			 * Fired hook after destroy session
			 * 
			 * @since 5.2.0
			 * @param array $session_data | Array with session data before destroy
			 * @param array $cart_items | Array with cart items data before destroy
			 * @param array $applied_coupons | Array with applied coupons before destroy
			 * @param array $cart_totals | Array with cart totals data before destroy
			 * @param string $redirect_url | Redirect URL after destroy session
			 */
			do_action( 'Flexify_Checkout/Countdown/Session_Destroyed', $session_data, $cart_items, $applied_coupons, $cart_totals, $redirect_url );

			// clear cart
			if ( WC()->cart ) {
				WC()->cart->empty_cart( true );
			}

			if ( WC()->session ) {
				WC()->session->cleanup_sessions();
				WC()->session->destroy_session();
			}

			// send response
			wp_send_json( array(
				'status' => 'success',
				'redirect_url' => $redirect_url,
			));
		}
	}


	/**
	 * Remove checkout conditions that reference fields which no longer exist.
	 *
	 * Thin wrapper kept for call sites in the fields manager and the schema
	 * migration; the actual scrubbing lives in the conditions store.
	 *
	 * @since 6.0.0
	 * @param string|null $field_id Specific field to scrub, or null for all orphans.
	 * @return bool True when the stored rules changed.
	 */
	public static function scrub_orphan_checkout_conditions( $field_id = null ) {
		return \MeuMouse\Flexify_Checkout\Admin\Settings\Conditions_Store::scrub_orphan_field_conditions( $field_id );
	}
}

