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
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Ajax {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
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
		add_action( 'wp_ajax_flexify_checkout_login', array( $this, 'checkout_login_callback' ) );

		// get AJAX call on login event for not logged users
		add_action( 'wp_ajax_nopriv_flexify_checkout_login', array( $this, 'checkout_login_callback' ) );

		// get AJAX calls on change options
		add_action( 'wp_ajax_admin_ajax_save_options', array( $this, 'ajax_save_options_callback' ) );
	
		// remove checkout fields on click delete button
		add_action( 'wp_ajax_remove_checkout_fields', array( $this, 'remove_checkout_fields_callback' ) );
	
		// processing new field
		add_action( 'wp_ajax_add_new_field_to_checkout', array( $this, 'add_new_field_to_checkout_callback' ) );
	
		// get AJAX call from upload files from alternative activation license
		add_action( 'wp_ajax_alternative_activation_license', array( $this, 'alternative_activation_license_callback' ) );
	
		// get AJAX call from add new font
		add_action( 'wp_ajax_add_new_font_action', array( $this, 'add_new_font_action_callback' ) );
	
		// get AJAX call for query products search
		add_action( 'wp_ajax_get_woo_products_ajax', array( $this, 'get_woo_products_callback' ) );
	
		// get AJAX call for query products categories
		add_action( 'wp_ajax_get_woo_categories_ajax', array( $this, 'get_woo_categories_callback' ) );
		
		// get AJAX call for query products categories
		add_action( 'wp_ajax_get_woo_attributes_ajax', array( $this, 'get_woo_attributes_callback' ) );
	
		// get AJAX call for query WP users
		add_action( 'wp_ajax_search_users_ajax', array( $this, 'search_users_ajax_callback' ) );
	
		// get AJAX call from add new condition
		add_action( 'wp_ajax_add_new_checkout_condition', array( $this, 'add_new_checkout_condition_callback' ) );
	
		// get AJAX call from exclude condition item
		add_action( 'wp_ajax_exclude_condition_item', array( $this, 'exclude_condition_item_callback' ) );
	
		// get AJAX call from add new email provider
		add_action( 'wp_ajax_add_new_email_provider', array( $this, 'add_new_email_provider_callback' ) );
	
		// get AJAX call from remove email provider item
		add_action( 'wp_ajax_remove_email_provider', array( $this, 'remove_email_provider_callback' ) );

		// dismiss billing country notice
		add_action( 'wp_ajax_dismiss_billing_country_warning', array( __CLASS__, 'dismiss_billing_country_warning' ) );

		// on deactive license process
		add_action( 'wp_ajax_deactive_license_action', array( $this, 'deactive_license_callback' ) );

		// clear activation cache
		add_action( 'wp_ajax_clear_activation_cache_action', array( $this, 'clear_activation_cache_callback' ) );

		// reset settings to default
		add_action( 'wp_ajax_reset_plugin_action', array( $this, 'reset_plugin_callback' ) );

		// check field available on create new field
		add_action( 'wp_ajax_check_field_availability', array( $this, 'check_field_availability_callback' ) );

		// remove option from select
		add_action( 'wp_ajax_remove_select_option', array( $this, 'remove_select_option_callback' ) );

		// add new select option live
		add_action( 'wp_ajax_add_new_option_select_live', array( $this, 'add_new_option_select_live_callback' ) );

		// set customer data on checkout session
		add_action( 'wp_ajax_get_checkout_session_data', array( $this, 'get_checkout_session_data_callback' ) );
		add_action( 'wp_ajax_nopriv_get_checkout_session_data', array( $this, 'get_checkout_session_data_callback' ) );

		// enable AJAX request for autofill company field on digit CNPJ
		if ( Admin_Options::get_setting('enable_autofill_company_info') === 'yes' && License::is_valid() ) {
			add_action( 'wp_ajax_cnpj_autofill_query', array( __CLASS__, 'cnpj_autofill_query_callback' ) );
			add_action( 'wp_ajax_nopriv_cnpj_autofill_query', array( __CLASS__, 'cnpj_autofill_query_callback' ) );
		}
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

		$session_key = WC()->session->get('flexify_checkout_ship_different_address') === 'yes' ? 'shipping' : 'billing';

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
				throw new \Exception( '<strong>' . __( 'Erro:', 'woocommerce' ) . '</strong> ' . $validation_error->get_error_message() );
			}

			if ( empty( $credentials['user_login'] ) ) {
				throw new \Exception( '<strong>' . __( 'Erro:', 'woocommerce' ) . '</strong> ' . __( 'Usuário é obrigatório.', 'woocommerce' ) );
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
	 * Save options in AJAX
	 * 
	 * @since 1.0.0
	 * @version 3.9.7
	 * @return void
	 */
	public function ajax_save_options_callback() {
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'admin_ajax_save_options' ) {
			// Convert serialized data into an array
			parse_str( $_POST['form_data'], $form_data );

			$options = get_option('flexify_checkout_settings');
			$options['enable_flexify_checkout'] = isset( $form_data['enable_flexify_checkout'] ) ? 'yes' : 'no';
			$options['enable_autofill_company_info'] = isset( $form_data['enable_autofill_company_info'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_back_to_shop_button'] = isset( $form_data['enable_back_to_shop_button'] ) ? 'yes' : 'no';
			$options['enable_skip_cart_page'] = isset( $form_data['enable_skip_cart_page'] ) ? 'yes' : 'no';
			$options['enable_terms_is_checked_default'] = isset( $form_data['enable_terms_is_checked_default'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_aditional_notes'] = isset( $form_data['enable_aditional_notes'] ) ? 'yes' : 'no';
			$options['enable_optimize_for_digital_products'] = isset( $form_data['enable_optimize_for_digital_products'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_link_image_products'] = isset( $form_data['enable_link_image_products'] ) ? 'yes' : 'no';
			$options['enable_fill_address'] = isset( $form_data['enable_fill_address'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_change_product_quantity'] = isset( $form_data['enable_change_product_quantity'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_remove_product_cart'] = isset( $form_data['enable_remove_product_cart'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_ddi_phone_field'] = isset( $form_data['enable_ddi_phone_field'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_hide_coupon_code_field'] = isset( $form_data['enable_hide_coupon_code_field'] ) ? 'yes' : 'no';
			$options['enable_auto_apply_coupon_code'] = isset( $form_data['enable_auto_apply_coupon_code'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_assign_guest_orders'] = isset( $form_data['enable_assign_guest_orders'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_inter_bank_pix_api'] = isset( $form_data['enable_inter_bank_pix_api'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_inter_bank_ticket_api'] = isset( $form_data['enable_inter_bank_ticket_api'] ) && License::is_valid() ? 'yes' : 'no';
			$options['flexify_checkout_theme'] = isset( $form_data['flexify_checkout_theme'] ) ? 'modern' : 'modern';
			$options['enable_thankyou_page_template'] = isset( $form_data['enable_thankyou_page_template'] ) ? 'yes' : 'no';
			$options['inter_bank_debug_mode'] = isset( $form_data['inter_bank_debug_mode'] ) ? 'yes' : 'no';
			$options['enable_unset_wcbcf_fields_not_brazil'] = isset( $form_data['enable_unset_wcbcf_fields_not_brazil'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_manage_fields'] = isset( $form_data['enable_manage_fields'] ) ? 'yes' : 'no';
			$options['enable_display_local_pickup_kangu'] = isset( $form_data['enable_display_local_pickup_kangu'] ) ? 'yes' : 'no';
			$options['enable_field_masks'] = isset( $form_data['enable_field_masks'] ) ? 'yes' : 'no';
			$options['check_password_strenght'] = isset( $form_data['check_password_strenght'] ) ? 'yes' : 'no';
			$options['email_providers_suggestion'] = isset( $form_data['email_providers_suggestion'] ) ? 'yes' : 'no';
			$options['display_opened_order_review_mobile'] = isset( $form_data['display_opened_order_review_mobile'] ) ? 'yes' : 'no';
			$options['inter_bank_env_mode'] = isset( $form_data['inter_bank_env_mode'] ) ? 'yes' : 'no';
			$options['enable_remove_quantity_select'] = isset( $form_data['enable_remove_quantity_select'] ) ? 'yes' : 'no';
			$options['enable_animation_process_purchase'] = isset( $form_data['enable_animation_process_purchase'] ) && License::is_valid() ? 'yes' : 'no';
			$options['enable_shipping_to_different_address'] = isset( $form_data['enable_shipping_to_different_address'] ) && License::is_valid() ? 'yes' : 'no';	

			// check if form data exists "checkout_step" name and is array
			if ( isset( $form_data['checkout_step'] ) && is_array( $form_data['checkout_step'] ) ) {
				$form_data_fields = $form_data['checkout_step'];
				$fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

				// Iterate through the updated data
				foreach ( $form_data_fields as $index => $value ) {
					// update enabled field on change
					if ( ! isset( $value['enabled'] ) ) {
						$fields[$index]['enabled'] = 'no';
					}

					// update required field on change
					if ( ! isset( $value['required'] ) ) {
						$fields[$index]['required'] = 'no';
					}

					// update priority on change
					if ( isset( $value['priority'] ) ) {
						$fields[$index]['priority'] = $value['priority'];
					}

					// update step on change
					if ( isset( $value['step'] ) ) {
						$fields[$index]['step'] = $value['step'];
					}

					// update label on change
					if ( isset( $value['label'] ) ) {
						$fields[$index]['label'] = $value['label'];
					}

					// update label class on change
					if ( isset( $value['label_classes'] ) ) {
						$fields[$index]['label_classes'] = $value['label_classes'];
					}

					// update field class on change
					if ( isset( $value['classes'] ) ) {
						$fields[$index]['classes'] = $value['classes'];
					}

					// update position on change
					if ( isset( $value['position'] ) ) {
						$fields[$index]['position'] = $value['position'];
					}

					// update field mask on change
					if ( isset( $value['input_mask'] ) ) {
						$fields[$index]['input_mask'] = $value['input_mask'];
					}

					// Merge updated data with existing tab data
					$fields[$index] = array_merge( $fields[$index], $value );
				}

				// Update option with merged data
				if ( License::is_valid() ) {
					update_option('flexify_checkout_step_fields', maybe_serialize( $fields ));
				}
			}

			// Merge the form data with the default options
			$updated_options = wp_parse_args( $form_data, $options );

			// Save the updated options
			$saved_options = update_option( 'flexify_checkout_settings', $updated_options );

			if ( $saved_options ) {
				$response = array(
					'status' => 'success',
					'toast_header_title' => esc_html__( 'Salvo com sucesso', 'flexify-checkout-for-woocommerce' ),
					'toast_body_title' => esc_html__( 'As configurações foram atualizadas!', 'flexify-checkout-for-woocommerce' ),
					'options' => $updated_options,
				);

				wp_send_json( $response ); // Send JSON response
			}
		}
	}


	/**
	 * Remove checkout fields
	 * 
	 * @since 3.2.0
	 * @version 3.8.0
	 * @return void
	 */
	public function remove_checkout_fields_callback() {
		if ( isset( $_POST['field_to_remove'] ) ) {
			$field_to_remove = sanitize_text_field( $_POST['field_to_remove'] );
			$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
		
			// Remove the field with the specified index
			if ( isset( $get_fields[$field_to_remove] ) ) {
				unset( $get_fields[$field_to_remove] );
		
				// Update the fields options
				$removed_field = update_option('flexify_checkout_step_fields', maybe_serialize( $get_fields ));

				if ( $removed_field ) {
					$response = array(
						'status' => 'success',
						'toast_header_title' => esc_html__( 'Campo removido', 'flexify-checkout-for-woocommerce' ),
						'toast_body_title' => esc_html__( 'O campo foi removido com sucesso!', 'flexify-checkout-for-woocommerce' ),
						'field' => $field_to_remove,
					);
				} else {
					$response = array(
						'status' => 'error',
						'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ),
						'toast_body_title' => esc_html__( 'Ocorreu um erro ao redefinir as configurações.', 'flexify-checkout-for-woocommerce' ),
					);
				}

				wp_send_json( $response ); // send response
			}
		}
	}


	/**
	 * Processing form on add new field to checkout
	 * 
	 * @since 3.2.0
	 * @version 5.0.0
	 * @return void
	 */
	public function add_new_field_to_checkout_callback() {
		if ( isset( $_POST['get_field_id'] ) ) {
			$field_id = sanitize_text_field( $_POST['get_field_id'] );
			$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

			if ( ! isset( $get_fields[$field_id] ) ) {
				$new_field = array(
					$field_id => array(
						'id' => $field_id,
						'type' => isset( $_POST['get_field_type'] ) ? sanitize_text_field( $_POST['get_field_type'] ) : '',
						'label' => isset( $_POST['get_field_label'] ) ? sanitize_text_field( $_POST['get_field_label'] ) : '',
						'position' => isset( $_POST['get_field_position'] ) ? sanitize_text_field( $_POST['get_field_position'] ) : '',
						'classes' => isset( $_POST['get_field_classes'] ) ? sanitize_text_field( $_POST['get_field_classes'] ) : '',
						'label_classes' => isset( $_POST['get_field_label_classes'] ) ? sanitize_text_field( $_POST['get_field_label_classes'] ) : '',
						'required' => isset( $_POST['get_field_required'] ) ? sanitize_text_field( $_POST['get_field_required'] ) : '',
						'priority' => isset( $_POST['get_field_priority'] ) ? sanitize_text_field( $_POST['get_field_priority'] ) : '',
						'source' => isset( $_POST['get_field_source'] ) ? sanitize_text_field( $_POST['get_field_source'] ) : '',
						'enabled' => 'yes',
						'step' => isset( $_POST['get_field_step'] ) ? sanitize_text_field( $_POST['get_field_step'] ) : '',
						'options' => isset( $_POST['get_field_options_for_select'] ) ? $_POST['get_field_options_for_select'] : null,
						'input_mask' => isset( $_POST['input_mask'] ) ? sanitize_text_field( $_POST['input_mask'] ) : '',
					),
				);

				// merge new field with existing fields
				$new_field = array_merge( $get_fields, $new_field );
				$field_added = update_option('flexify_checkout_step_fields', maybe_serialize( $new_field ));

				if ( $field_added ) {
					// start buffer
					ob_start();

					Components::render_field( $field_id, $new_field[$field_id], $new_field[$field_id]['step'] );
					$field_html = ob_get_clean();

					$response = array(
						'status' => 'success',
						'toast_header_title' => esc_html( 'Novo campo adicionado', 'flexify-checkout-for-woocommerce' ),
						'toast_body_title' => esc_html( 'Novo campo para finalização de compras adicionado com sucesso!', 'flexify-checkout-for-woocommerce' ),
						'field_html' => $field_html,
					);
				} else {
					$response = array(
						'status' => 'error',
						'toast_header_title' => esc_html( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ),
						'toast_body_title' => esc_html( 'Ocorreu um erro ao adicionar o novo campo.', 'flexify-checkout-for-woocommerce' ),
					);
				}

				wp_send_json( $response ); // send response
			}
		}
	}


	/**
	 * Handle alternative activation license file .key
	 * 
	 * @since 3.3.0
	 * @version 3.8.0
	 * @return void
	 */
	public function alternative_activation_license_callback() {
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'alternative_activation_license' ) {
			$response = array(
				'status' => 'error',
				'message' => __( 'Erro ao carregar o arquivo. A ação não foi acionada corretamente.', 'flexify-checkout-for-woocommerce' ),
			);

			wp_send_json( $response );
		}

		// Verifica se o arquivo foi enviado
		if ( empty( $_FILES['file'] ) ) {
			$response = array(
				'status' => 'error',
				'message' => __( 'Erro ao carregar o arquivo. O arquivo não foi enviado.', 'flexify-checkout-for-woocommerce' ),
			);

			wp_send_json( $response );
		}

		$file = $_FILES['file'];

		// Verifica se é um arquivo .key
		if ( pathinfo( $file['name'], PATHINFO_EXTENSION ) !== 'key' ) {
			$response = array(
				'status' => 'invalid_file',
				'message' => __( 'Arquivo inválido. O arquivo deve ser um .crt ou .key.', 'flexify-checkout-for-woocommerce' ),
			);
			
			wp_send_json( $response );
		}

		// Lê o conteúdo do arquivo
		$file_content = file_get_contents( $file['tmp_name'] );

		$decrypt_keys = array(
			'49D52DA9137137C0', // original product key
			'B729F2659393EE27', // Clube M
		);

		$decrypted_data = License::decrypt_alternative_license( $file_content, $decrypt_keys );

		if ( $decrypted_data !== null ) {
			update_option( 'flexify_checkout_alternative_license_decrypted', $decrypted_data );
			
			$response = array(
				'status' => 'success',
				'message' => __( 'Licença enviada e decriptografada com sucesso.', 'flexify-checkout-for-woocommerce' ),
			);
		} else {
			$response = array(
				'status' => 'error',
				'message' => __( 'Não foi possível descriptografar o arquivo de licença.', 'flexify-checkout-for-woocommerce' ),
			);
		}

		wp_send_json( $response );
	}


	/**
	 * Add new font to library on AJAX callback
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function add_new_font_action_callback() {
		if ( isset( $_POST['new_font_id'] ) ) {
			$font_id = strtolower( $_POST['new_font_id'] );

			$new_font = array(
				$font_id => array(
					'font_name' => $_POST['new_font_name'],
					'font_url' => $_POST['new_font_url'],
				),
			);

			// get settings array
			$options = get_option('flexify_checkout_settings', array());

			// add new theme to array settings
			if ( ! isset( $options['font_family'][$font_id] ) ) {
				$options['font_family'][$font_id] = $new_font[$font_id];
			}

			// save the updated options
			$new_font_added = update_option( 'flexify_checkout_settings', $options );

			// check if new theme added with successful
			if ( $new_font_added ) {
				$response = array(
					'status' => 'success',
					'reload' => true,
				);
			} else {
				$response = array(
					'status' => 'error',
					'font_exists' => true,
					'reload' => false,
				);
			}

			// send response to frontend
			wp_send_json( $response );
		}
	}


	/**
	 * Get WooCommerce products in AJAX
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function get_woo_products_callback() {
		if ( isset( $_POST['search_query'] ) ) {
			$search_query = sanitize_text_field( $_POST['search_query'] );
			
			$args = array(
				'post_type' => 'product',
				'status' => 'publish',
				'posts_per_page' => -1, // Return all results
				's' => $search_query,
			);
			
			$products = new \WP_Query( $args );
			
			if ( $products->have_posts() ) {
				while ( $products->have_posts() ) {
					$products->the_post();

					echo '<li class="list-group-item" data-product-id="'. get_the_ID() .'">' . get_the_title() . '</li>';
				}
			} else {
				echo esc_html__( 'Nenhuma produto encontrado.', 'flexify-checkout-for-woocommerce' );
			}
			
			wp_die(); // end ajax call
		}
	}


	/**
	 * Get WooCommerce product categories in AJAX
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function get_woo_categories_callback() {
		if ( isset( $_POST['search_query'] ) ) {
			$search_query = sanitize_text_field( $_POST['search_query'] );
			
			$args = array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false,
				'name__like' => $search_query,
			);
			
			$categories = get_terms( $args );
			
			if ( ! empty( $categories ) ) {
				foreach ( $categories as $category ) {
					echo '<li class="list-group-item" data-category-id="'. $category->term_id .'">'. $category->name .'</li>';
				}
			} else {
				echo esc_html__( 'Nenhuma categoria encontrada.', 'flexify-checkout-for-woocommerce' );
			}
			
			wp_die(); // end ajax call
		}
	}

	
	/**
	 * Get product attributes in AJAX
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function get_woo_attributes_callback() {
		if ( isset( $_POST['search_query'] ) ) {
			$search_query = sanitize_text_field( $_POST['search_query'] );

			// get all registered attribute taxonomies
			$attribute_taxonomies = wc_get_attribute_taxonomies();

			if ( ! empty( $attribute_taxonomies ) ) {
				foreach ( $attribute_taxonomies as $attribute_taxonomy ) {
					// Use the taxonomy name instead of the 'attribute_name'
					$taxonomy_name = 'pa_' . $attribute_taxonomy->attribute_name;

					// Verify that the taxonomy name contains the search term
					if ( strpos( $taxonomy_name, $search_query ) !== false ) {
						$args = array(
							'taxonomy' => $taxonomy_name,
							'hide_empty' => false,
						);

						$attributes = get_terms( $args );

						if ( ! empty( $attributes ) ) {
							foreach ( $attributes as $attribute ) {
								echo '<li class="list-group-item" data-attribute-id="' . $attribute->term_id . '">' . $attribute->name . '</li>';
							}
						} else {
							echo esc_html__( 'Nenhum atributo encontrado.', 'flexify-checkout-for-woocommerce' );
						}
					}
				}
			}

			wp_die(); // end ajax call
		}
	}


	/**
	 * Search WP users in AJAX
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function search_users_ajax_callback() {
		if ( isset( $_POST['search_query'] ) ) {
			$search_query = sanitize_text_field( $_POST['search_query'] );

			// Run a query to search for users based on the search term
			$args = array(
				'search' => '*' . $search_query . '*',
				'search_columns' => array(
					'user_login',
					'user_email',
					'user_nicename',
					'display_name',
				),
				'number' => -1, // Return all results
			);

			$users = get_users( $args );

			if ( ! empty( $users ) ) {
				foreach ( $users as $user ) {
					echo '<li class="list-group-item" data-user-id="' . $user->ID . '">' . $user->display_name . '</li>';
				}
			} else {
				echo esc_html__( 'Nenhum usuário encontrado.', 'flexify-checkout-for-woocommerce' );
			}

			wp_die(); // end ajax call
		}
	}


	/**
	 * Add new condition AJAX callback
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function add_new_checkout_condition_callback() {
		if ( isset( $_POST['type_rule'] ) && $_POST['type_rule'] !== 'none' ) {
			$form_condition = array(
				'type_rule' => isset( $_POST['type_rule'] ) ? sanitize_text_field( $_POST['type_rule'] ) : null,
				'component' => isset( $_POST['component'] ) ? sanitize_text_field( $_POST['component'] ) : null,
				'component_field' => isset( $_POST['component_field'] ) ? sanitize_text_field( $_POST['component_field'] ) : null,
				'verification_condition' => isset( $_POST['verification_condition'] ) ? sanitize_text_field( $_POST['verification_condition'] ) : null,
				'verification_condition_field' => isset( $_POST['verification_condition_field'] ) ? sanitize_text_field( $_POST['verification_condition_field'] ) : null,
				'condition' => isset( $_POST['condition'] ) ? sanitize_text_field( $_POST['condition'] ) : null,
				'condition_value' => isset( $_POST['condition_value'] ) ? sanitize_text_field( $_POST['condition_value'] ) : null,
				'payment_method' => isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : null,
				'shipping_method' => isset( $_POST['shipping_method'] ) ? sanitize_text_field( $_POST['shipping_method'] ) : null,
				'filter_user' => isset( $_POST['filter_user'] ) ? sanitize_text_field( $_POST['filter_user'] ) : null,
				'specific_user' => isset( $_POST['filter_user'] ) ? $_POST['filter_user'] : null,
				'specific_role' => isset( $_POST['specific_role'] ) ? sanitize_text_field( $_POST['specific_role'] ) : null,
				'specific_products' => isset( $_POST['specific_products'] ) ? $_POST['specific_products'] : null,
				'specific_categories' => isset( $_POST['specific_categories'] ) ? $_POST['specific_categories'] : null,
				'specific_attributes' => isset( $_POST['specific_attributes'] ) ? $_POST['specific_attributes'] : null,
				'product_filter' => isset( $_POST['product_filter'] ) ? sanitize_text_field( $_POST['product_filter'] ) : null,
			);

			// remove null values
			$form_condition = array_filter( $form_condition, function( $value ) {
				return ! is_null( $value );
			});

			// get current conditions
			$current_conditions = get_option('flexify_checkout_conditions', array());

			$empty_conditions = false;

			// check if conditions is empty
			if ( empty( $current_conditions ) ) {
				$empty_conditions = true;
			}

			// merge new condition with existing
			$current_conditions[] = $form_condition;

			// Update conditions
			$update_conditions = update_option( 'flexify_checkout_conditions', $current_conditions );

			// check if successfully updated
			if ( $update_conditions ) {
				$get_fields = Helpers::get_checkout_fields_on_admin();
				$condition_type = array(
					'show' => esc_html__( 'Mostrar', 'flexify-checkout-for-woocommerce' ),
					'hide' => esc_html__( 'Ocultar', 'flexify-checkout-for-woocommerce' ),
				);

				$component_type_label = '';

				if ( $form_condition['component'] === 'field' ) {
					$field_id = $form_condition['component_field'];
					$component_type_label = sprintf( esc_html__( 'Campo %s', 'flexify-checkout-for-woocommerce' ), $get_fields['billing'][$field_id]['label'] );
				} elseif ( $form_condition['component'] === 'shipping' ) {
					$shipping_id = $form_condition['shipping_method'];
					$component_type_label = sprintf( esc_html__( 'Forma de entrega %s', 'flexify-checkout-for-woocommerce' ), WC()->shipping->get_shipping_methods()[$shipping_id]->method_title );
				} elseif ( $form_condition['component'] === 'payment' ) {
					$payment_id = $form_condition['payment_method'];
					$component_type_label = sprintf( esc_html__( 'Forma de pagamento %s', 'flexify-checkout-for-woocommerce' ), WC()->payment_gateways->payment_gateways()[$payment_id]->method_title );
				}

				$component_verification_label = '';

				if ( $form_condition['verification_condition'] === 'field' ) {
					$field_id = $form_condition['verification_condition_field'];
					$component_verification_label = sprintf( esc_html__( 'Campo %s', 'flexify-checkout-for-woocommerce' ), $get_fields['billing'][$field_id]['label'] );
				} elseif ( $form_condition['verification_condition'] === 'qtd_cart_total' ) {
					$component_verification_label = esc_html__( 'Quantidade total do carrinho', 'flexify-checkout-for-woocommerce' );
				} elseif ( $form_condition['verification_condition'] === 'cart_total_value' ) {
					$component_verification_label = esc_html__( 'Valor total do carrinho', 'flexify-checkout-for-woocommerce' );
				}

				$condition = array(
					'is' => esc_html__( 'É', 'flexify-checkout-for-woocommerce' ),
					'is_not' => esc_html__( 'Não é', 'flexify-checkout-for-woocommerce' ),
					'empty' => esc_html__( 'Vazio', 'flexify-checkout-for-woocommerce' ),
					'not_empty' => esc_html__( 'Não está vazio', 'flexify-checkout-for-woocommerce' ),
					'contains' => esc_html__( 'Contém', 'flexify-checkout-for-woocommerce' ),
					'not_contain' => esc_html__( 'Não contém', 'flexify-checkout-for-woocommerce' ),
					'start_with' => esc_html__( 'Começa com', 'flexify-checkout-for-woocommerce' ),
					'finish_with' => esc_html__( 'Termina com', 'flexify-checkout-for-woocommerce' ),
					'bigger_then' => esc_html__( 'Maior que', 'flexify-checkout-for-woocommerce' ),
					'less_than' => esc_html__( 'Menor que', 'flexify-checkout-for-woocommerce' ),
				);
				
				$condition_value = isset( $form_condition['condition_value'] ) ? $form_condition['condition_value'] : '';

				$response = array(
					'status' => 'success',
					'toast_header_title' => esc_html( 'Nova condição adicionada', 'flexify-checkout-for-woocommerce' ),
					'toast_body_title' => esc_html( 'Condição criada com sucesso!', 'flexify-checkout-for-woocommerce' ),
					'condition_line_1' => sprintf( esc_html__( 'Condição: %s %s', 'flexify-checkout-for-woocommerce' ), $condition_type[$form_condition['type_rule']], $component_type_label ),
					'condition_line_2' => sprintf( esc_html__( 'Se: %s %s %s', 'flexify-checkout-for-woocommerce' ), $component_verification_label, mb_strtolower( $condition[$form_condition['condition']] ), $condition_value ),
				);

				if ( $empty_conditions ) {
					$response[] = array(
						'empty_conditions' => 'yes',
					);
				}
			} else {
				$response = array(
					'status' => 'error',
					'error_message' => esc_html__( 'Ops! Não foi possível criar uma nova condição.', 'flexify-checkout-for-woocommerce' ),
				);
			}

			// send response
			wp_send_json( $response );
		}
	}


	/**
	 * Exclude condition item AJAX callback
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function exclude_condition_item_callback() {
		if ( isset( $_POST['condition_index'] ) ) {
			$exclude_item = sanitize_text_field( $_POST['condition_index'] );
			$get_conditions = get_option('flexify_checkout_conditions', array());

			if ( isset( $get_conditions[$exclude_item] ) ) {
				unset( $get_conditions[$exclude_item] );

				$update_conditions = update_option('flexify_checkout_conditions', $get_conditions);

				if ( $update_conditions ) {
					$response = array(
						'status' => 'success',
						'toast_header_title' => esc_html( 'Excluído com sucesso', 'flexify-checkout-for-woocommerce' ),
						'toast_body_title' => esc_html( 'Condição excluída com sucesso!', 'flexify-checkout-for-woocommerce' ),
					);
			
					if ( empty( $get_conditions ) ) {
						$response[] = array(
							'empty_conditions' => 'yes',
							'empty_conditions_message' => esc_html( 'Ainda não existem condições.', 'flexify-checkout-for-woocommerce' ),
						);
					}
				} else {
					$response = array(
						'status' => 'error',
						'toast_header_title' => esc_html( 'Erro ao excluir', 'flexify-checkout-for-woocommerce' ),
						'toast_body_title' => esc_html( 'Ops! Não foi possível excluir a condição.', 'flexify-checkout-for-woocommerce' ),
					);
				}
		
				// send response
				wp_send_json( $response );
			}
		}
	}


	/**
	 * Add new email provider for suggestion on checkout
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function add_new_email_provider_callback() {
		if ( isset( $_POST['new_provider'] ) ) {
			$new_provider = sanitize_text_field( $_POST['new_provider'] );
			$get_options = get_option('flexify_checkout_settings', array());
			$providers = $get_options['set_email_providers'];
			$providers[] = $new_provider;
			$get_options['set_email_providers'] = $providers;
			$update_providers = update_option( 'flexify_checkout_settings', $get_options );

			if ( $update_providers ) {
				$response = array(
					'status' => 'success',
					'new_provider' => $new_provider,
					'toast_header_title' => esc_html( 'Provedor de e-mail adicionado', 'flexify-checkout-for-woocommerce' ),
					'toast_body_title' => esc_html( 'Novo provedor de e-mail adicionado com sucesso!', 'flexify-checkout-for-woocommerce' ),
				);
			} else {
				$response = array(
					'status' => 'error',
					'toast_header_title' => esc_html( 'Erro ao adicionar', 'flexify-checkout-for-woocommerce' ),
					'toast_body_title' => esc_html( 'Ops! Não foi possível adicionar o novo provedor.', 'flexify-checkout-for-woocommerce' ),
				);
			}

			wp_send_json( $response );
		}
	}


	/**
	 * Exclude email provider item
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function remove_email_provider_callback() {
		if ( isset( $_POST['exclude_provider'] ) ) {
			$exclude_provider = sanitize_text_field( $_POST['exclude_provider'] );
			$get_options = get_option('flexify_checkout_settings', array());
			$providers = $get_options['set_email_providers'];
			$search_provider = array_search( $exclude_provider, $providers );

			if ( $search_provider !== false ) {
				unset( $providers[$search_provider] );

				$get_options['set_email_providers'] = $providers;
				$update_providers = update_option( 'flexify_checkout_settings', $providers );

				if ( $update_providers ) {
					$response = array(
						'status' => 'success',
						'toast_header_title' => esc_html( 'Provedor de e-mail removido', 'flexify-checkout-for-woocommerce' ),
						'toast_body_title' => esc_html( 'Provedor de e-mail removido com sucesso!', 'flexify-checkout-for-woocommerce' ),
					);
				} else {
					$response = array(
						'status' => 'error',
						'toast_header_title' => esc_html( 'Erro ao remover', 'flexify-checkout-for-woocommerce' ),
						'toast_body_title' => esc_html( 'Ops! Não foi possível remover o provedor de e-mail.', 'flexify-checkout-for-woocommerce' ),
					);
				}

				wp_send_json( $response );
			}
		}
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
     * Deactive license on AJAX callback
     * 
     * @since 3.8.0
     * @return void
     */
    public function deactive_license_callback() {
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'deactive_license_action' ) {
            $message = '';
            $deactivation = License::deactive_license( FLEXIFY_CHECKOUT_FILE, $message );

            if ( $deactivation ) {
                delete_option('flexify_checkout_license_key');
                delete_option('flexify_checkout_license_response_object');
                delete_option('flexify_checkout_temp_license_key');
				delete_option('flexify_checkout_alternative_license');
                delete_option('flexify_checkout_alternative_license_activation');
				delete_option('flexify_checkout_alternative_license_decrypted');
				delete_transient('flexify_checkout_license_status_cached');
				delete_transient('flexify_checkout_api_request_cache');
                delete_transient('flexify_checkout_api_response_cache');

                $response = array(
                    'status' => 'success',
                    'toast_header_title' => esc_html__( 'A licença foi desativada', 'flexify-checkout-for-woocommerce' ),
                    'toast_body_title' => esc_html__( 'Todos os recursos da versão Pro agora estão desativados!', 'flexify-checkout-for-woocommerce' ),
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ),
                    'toast_body_title' => esc_html__( 'Ocorreu um erro ao desativar sua licença.', 'flexify-checkout-for-woocommerce' ),
                );
            }

            wp_send_json( $response );
        }
    }


	/**
     * Clear activation cache on AJAX callback
     * 
     * @since 3.8.0
     * @return void
     */
    public function clear_activation_cache_callback() {
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'clear_activation_cache_action' ) {
            delete_transient('flexify_checkout_api_request_cache');
            delete_transient('flexify_checkout_api_response_cache');
            delete_transient('flexify_checkout_license_status_cached');
            delete_option('flexify_checkout_alternative_license');
            delete_option('flexify_checkout_alternative_license_activation');

            $response = array(
                'status' => 'success',
                'toast_header_title' => esc_html__( 'Cache de ativação limpo', 'flexify-checkout-for-woocommerce' ),
                'toast_body_title' => esc_html__( 'O cache de ativação foi limpo com sucesso!', 'flexify-checkout-for-woocommerce' ),
            );

            wp_send_json( $response );
        }
    }


	/**
     * Reset plugin options to default on AJAX callback
     * 
     * @since 3.8.0
     * @return void
     */
    public function reset_plugin_callback() {
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'reset_plugin_action' ) {
            $delete_option = delete_option('flexify_checkout_settings');

            if ( $delete_option ) {
				delete_option('flexify_checkout_step_fields');
				delete_option('flexify_checkout_conditions');
				delete_option('flexify_checkout_alternative_license_activation');
				delete_transient('flexify_checkout_api_request_cache');
				delete_transient('flexify_checkout_api_response_cache');
				delete_transient('flexify_checkout_license_status_cached');

                $response = array(
                    'status' => 'success',
                    'toast_header_title' => esc_html__( 'As opções foram redefinidas', 'flexify-checkout-for-woocommerce' ),
                    'toast_body_title' => esc_html__( 'As opções foram redefinidas com sucesso!', 'flexify-checkout-for-woocommerce' ),
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ),
                    'toast_body_title' => esc_html__( 'Ocorreu um erro ao redefinir as configurações.', 'flexify-checkout-for-woocommerce' ),
                );
            }

            wp_send_json( $response );
        }
    }


	/**
	 * Check availability from new checkout field
	 * 
	 * @since 3.8.0
	 * @return void
	 */
	public function check_field_availability_callback() {
		if ( isset( $_POST['field_name'] ) ) {
			$field_name = sanitize_text_field( $_POST['field_name'] );
			$current_fields = Helpers::get_array_index_checkout_fields();
	
			if ( in_array( $field_name, $current_fields ) ) {
				$response = array(
					'status' => 'success',
					'available' => false,
				);
			} else {
				$response = array(
					'status' => 'success',
					'available' => true,
				);
			}

			wp_send_json( $response );
		} else {
			$response = array(
				'status' => 'success',
				'available' => false,
			);

			wp_send_json( $response );
		}
	}


	/**
	 * Remove select option item on AJAX callback
	 * 
	 * @since 3.8.0
	 * @return void
	 */
	public function remove_select_option_callback() {
		if ( isset( $_POST['field_id'] ) && isset( $_POST['exclude_option'] ) ) {
			$field_id = sanitize_text_field( $_POST['field_id'] );
			$exclude_option = sanitize_text_field( $_POST['exclude_option'] );
			$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

			if ( isset( $get_fields[$field_id] ) && $get_fields[$field_id]['type'] === 'select' ) {
				$options = $get_fields[$field_id]['options'];
				
				foreach ( $options as $index => $option ) {
					if ( $option['value'] === $exclude_option ) {
						unset( $options[$index] );
						break;
					}
				}

				$get_fields[$field_id]['options'] = array_values( $options );
				$field_updated = update_option( 'flexify_checkout_step_fields', maybe_serialize( $get_fields ) );

				if ( $field_updated ) {
					$response = array(
						'status' => 'success',
						'toast_header_title' => esc_html__('Opção removida', 'flexify-checkout-for-woocommerce'),
						'toast_body_title' => esc_html__('A opção foi removida com sucesso!', 'flexify-checkout-for-woocommerce'),
					);
				} else {
					$response = array(
						'status' => 'error',
						'toast_header_title' => esc_html__('Erro ao remover', 'flexify-checkout-for-woocommerce'),
						'toast_body_title' => esc_html__('Ops! Não foi possível remover a opção.', 'flexify-checkout-for-woocommerce'),
					);
				}

				wp_send_json( $response );
			} else {
				$response = array(
					'status' => 'error',
					'toast_header_title' => esc_html__('Erro ao remover', 'flexify-checkout-for-woocommerce'),
					'toast_body_title' => esc_html__('Ops! O campo não existe ou não é do tipo select.', 'flexify-checkout-for-woocommerce'),
				);

				wp_send_json( $response );
			}
		}
	}


	/**
	 * Add new option to select field
	 * 
	 * @since 3.8.0
	 * @return void
	 */
	public function add_new_option_select_live_callback() {
		if ( isset( $_POST['option_value'] ) && isset( $_POST['option_title'] ) ) {
			$field_id = sanitize_text_field( $_POST['field_id'] );
			$option_value = sanitize_text_field( $_POST['option_value'] );
			$option_title = sanitize_text_field( $_POST['option_title'] );
			$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

			if ( isset( $get_fields[$field_id] ) && $get_fields[$field_id]['type'] === 'select' ) {
				$options = $get_fields[$field_id]['options'];

				$options[] = array(
					'value' => $option_value,
					'text' => $option_title,
				);

				$get_fields[$field_id]['options'] = $options;
				$field_updated = update_option('flexify_checkout_step_fields', maybe_serialize( $get_fields ));

				if ( $field_updated ) {
					$response = array(
						'status' => 'success',
						'toast_header_title' => esc_html__('Nova opção adicionada', 'flexify-checkout-for-woocommerce'),
						'toast_body_title' => esc_html__('A nova opção foi adicionada com sucesso!', 'flexify-checkout-for-woocommerce'),
					);
				} else {
					$response = array(
						'status' => 'error',
						'toast_header_title' => esc_html__('Erro ao adicionar', 'flexify-checkout-for-woocommerce'),
						'toast_body_title' => esc_html__('Ops! Não foi possível adicionar a nova opção.', 'flexify-checkout-for-woocommerce'),
					);
				}

				wp_send_json( $response );
			} else {
				$response = array(
					'status' => 'error',
					'toast_header_title' => esc_html__('Erro ao adicionar', 'flexify-checkout-for-woocommerce'),
					'toast_body_title' => esc_html__('Ops! O campo não existe ou não é do tipo select.', 'flexify-checkout-for-woocommerce'),
				);

				wp_send_json( $response );
			}
		}
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
			$session_data = array();
		
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
}