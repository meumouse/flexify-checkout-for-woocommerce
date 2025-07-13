<?php

namespace MeuMouse\Flexify_Checkout\Core;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;

use MeuMouse\Flexify_Checkout\Checkout\Steps;
use MeuMouse\Flexify_Checkout\Checkout\Fields;
use MeuMouse\Flexify_Checkout\Checkout\Conditions;

use MeuMouse\Flexify_Checkout\Views\Styles;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Register/enqueue frontend and backend scripts
 *
 * @since 1.0.0
 * @version 3.9.6
 * @package MeuMouse.com
 */
class Assets {

	/**
	 * Define assets URL directory
	 * 
	 * @since 5.0.0
	 * @return string
	 */
	public $assets_url = FLEXIFY_CHECKOUT_ASSETS;

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		// disable the default stylesheet
		add_action( 'wp', array( $this, 'disable_woo_stylesheet' ) );

		$max_priority = defined('PHP_INT_MAX') ? PHP_INT_MAX : 2147483647;

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ), $max_priority );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );

		// remove password strenght
		if ( Admin_Options::get_setting('check_password_strenght') !== 'yes' ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'disable_password_strenght' ), 99999 );
		}
	}


	/**
	 * Earliest we can check if it's the checkout page
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function disable_woo_stylesheet() {
		if ( ! is_flexify_checkout() ) {
			return;
		}

		// Better x-theme compatibility.
		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
	}


	/**
	 * Frontend assets
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public static function frontend_assets() {
		if ( ! defined( 'IS_FLEXIFY_CHECKOUT' ) || ! IS_FLEXIFY_CHECKOUT ) {
			return;
		}

		global $wp, $wp_scripts, $wp_styles;

		$theme = Themes::get_theme();

		/**
		 * Choose which sources are allowed at checkout
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @param array $allowed_sources | Allowed sources
		 */
		$allowed_sources = apply_filters( 'Flexify_Checkout/Assets/Set_Allowed_Sources', array() );

		foreach ( $wp_scripts->queue as $key => $name ) {
			$src = $wp_scripts->registered[ $name ]->src;

			if ( ! in_array( $wp_scripts->registered[ $name ]->src, $allowed_sources ) && strpos( $src, '/themes/' ) ) {
				wp_dequeue_script( $name );
			}
		}

		foreach ( $wp_styles->queue as $key => $name ) {
			$src = $wp_styles->registered[ $name ]->src;

			// The twenty-x themes have custom CSS within woo.
			if ( ! in_array( $wp_styles->registered[ $name ]->src, $allowed_sources ) && ( strpos( $src, '/themes/' ) || strpos( $src, '/twenty' ) ) ) {
				wp_dequeue_style( $name );
			}
		}

		wp_dequeue_style( 'global-styles' );

		wp_enqueue_style( 'flexify-checkout-theme', $this->assets_url . 'frontend/css/templates/' . $theme . '/main.css', array(), FLEXIFY_CHECKOUT_VERSION, false );

		if ( is_flexify_checkout() || Helpers::is_thankyou_page() ) {
			$settings = get_option('flexify_checkout_settings');
			wp_add_inline_style( 'flexify-checkout-theme', Styles::render_checkout_styles( $settings ) );
		}

		$deps = array(
			'jquery',
			'jquery-blockui',
			'select2',
			'wc-checkout',
			'wc-country-select',
			'wc-address-i18n',
			'wp-hooks',
		);

		// international phone number selector
		if ( Admin_Options::get_setting('enable_ddi_phone_field') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'flexify-international-phone-js', $this->assets_url . 'vendor/intl-tel-input/js/intlTelInput-jquery.min.js', array('jquery'), '17.0.19', false );
			wp_enqueue_style( 'flexify-international-phone-css', $this->assets_url . 'vendor/intl-tel-input/css/intlTelInput.min.css', array(), '17.0.19' );
			$deps[] = 'flexify-international-phone-js';
		}

		$timestamp = time();

		// Add the timestamp as a query parameter to the main.js file URL
		$script = $this->assets_url . 'frontend/js/main.js?version=' . $timestamp;

		// Set script version to null to avoid version-based caching
		$version = null;

		wp_enqueue_script('flexify-checkout-for-woocommerce', $script, $deps, $version, true);

		// autofill address to enter postcode (just valid for Brazil)
		if ( Admin_Options::get_setting('enable_fill_address') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'flexify-checkout-autofill-address-js', $this->assets_url . 'frontend/js/autofill-address.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION, false );

			// send params from JS
			$auto_fill_address_api_params = apply_filters( 'flexify_checkout_auto_fill_address', array(
				'api_service' => Admin_Options::get_setting('get_address_api_service'),
				'address_param' => Admin_Options::get_setting('api_auto_fill_address_param'),
				'neightborhood_param' => Admin_Options::get_setting('api_auto_fill_address_neightborhood_param'),
				'city_param' => Admin_Options::get_setting('api_auto_fill_address_city_param'),
				'state_param' => Admin_Options::get_setting('api_auto_fill_address_state_param'),
			));
			
			wp_localize_script( 'flexify-checkout-autofill-address-js', 'fcw_auto_fill_address_api_params', $auto_fill_address_api_params );
		}

		// autofill field on enter CNPJ (just valid for Brazil)
		if ( Admin_Options::get_setting('enable_autofill_company_info') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'flexify-checkout-autofill-cnpj-js', $this->assets_url . 'frontend/js/autofill-cnpj.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION, false );
		}

		// remove brazilian market fields if is not Brazil country
		if ( Admin_Options::get_setting('enable_unset_wcbcf_fields_not_brazil') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'flexify-checkout-remove-wcbcf-fields', $this->assets_url . 'frontend/js/remove-wcbcf-fields.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );
		}

		// enable field masks
		if ( Admin_Options::get_setting('enable_field_masks') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'jquery-mask-lib', $this->assets_url . 'vendor/jquery-mask/jquery.mask.min.js', array('jquery'), '1.14.16' );
			wp_enqueue_script( 'flexify-checkout-field-masks', $this->assets_url . 'frontend/js/field-masks.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );
			wp_localize_script( 'flexify-checkout-field-masks', 'fcw_field_masks', array( 'get_input_masks' => Fields::get_fields_with_mask() ) );
		}

		// add email suggestions
		if ( Admin_Options::get_setting('email_providers_suggestion') === 'yes' ) {
			wp_enqueue_script( 'flexify-checkout-email-suggestions', $this->assets_url . 'frontend/js/email-suggestions.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );

			$emails_suggestions_params = apply_filters( 'flexify_checkout_emails_suggestions', array(
				'get_providers' => Admin_Options::get_setting('set_email_providers'),
			));

			wp_localize_script( 'flexify-checkout-email-suggestions', 'fcw_emails_suggestions_params', $emails_suggestions_params );
		}

		// add frontend conditions
		if ( ! empty( get_option('flexify_checkout_conditions') ) ) {
			wp_enqueue_script( 'flexify-checkout-conditions', $this->assets_url . 'frontend/js/conditions.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );

			$conditions_params = apply_filters( 'flexify_checkout_front_conditions', array(
				'field_condition' => Conditions::filter_component_type('field'),
			));

			wp_localize_script( 'flexify-checkout-conditions', 'fcw_condition_param', $conditions_params );
		}

		// process animation purchase
		if ( Admin_Options::get_setting('enable_animation_process_purchase') === 'yes' ) {
			wp_enqueue_script( 'lordicon-player', 'https://cdn.lordicon.com/lordicon.js', array() );
		}

		// get fields from fields manager
		$fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

		// set default country for international phone
		if ( Admin_Options::get_setting('enable_manage_fields') === 'yes' && isset( $fields['billing_country']['country'] ) && $fields['billing_country']['country'] !== 'none' ) {
			$base_country = $fields['billing_country']['country'] ?? '';
		} else {
			$base_country = WC()->countries->get_base_country();
		}

		/**
		 * Flexify checkout script localized data
		 *
		 * @since 1.0.0
		 * @version 3.9.4
		 * @return array
		 */
		$flexify_script_data = apply_filters( 'flexify_checkout_script_data', array(
			'allowed_countries' => array_map( 'strtolower', array_keys( WC()->countries->get_allowed_countries() ) ),
			'ajax_url' => admin_url('admin-ajax.php'),
			'is_user_logged_in' => is_user_logged_in(),
			'localstorage_fields' => Fields::get_localstorage_fields(),
			'international_phone' => Admin_Options::get_setting('enable_ddi_phone_field') ? Admin_Options::get_setting('enable_ddi_phone_field') : '',
			'allow_login_existing_user' => 'inline_popup',
			'steps' => Steps::get_steps_hashes(),
			'i18n' => array(
				'error' => __( 'Corrija todos os erros e tente novamente.', 'flexify-checkout-for-woocommerce' ),
				'errorAddressSearch' => __( 'Procure um endereço e tente novamente.', 'flexify-checkout-for-woocommerce' ),
				'login' => __( 'Entrar', 'flexify-checkout-for-woocommerce' ),
				'pay' => __( 'Pagar', 'flexify-checkout-for-woocommerce' ),
				'coupon_success' => __( 'O cupom foi removido.', 'flexify-checkout-for-woocommerce' ),
				'account_exists' => __( 'Uma conta já está registrada com este endereço de e-mail. Gostaria de entrar nela?', 'flexify-checkout-for-woocommerce' ),
				'login_successful' => __( 'Bem vindo de volta!', 'flexify-checkout-for-woocommerce' ),
				'error_occured' => __( 'Ocorreu um erro', 'flexify-checkout-for-woocommerce' ),
				'phone' => array(
					'invalid' => __( 'Por favor, insira um número de telefone válido.', 'flexify-checkout-for-woocommerce' ),
				),
				'cpf' => array(
					'invalid' => __( 'Por favor, insira um CPF válido.', 'flexify-checkout-for-woocommerce' ),
				),
				'cnpj' => array(
					'invalid' => __( 'Por favor, insira um CNPJ válido.', 'flexify-checkout-for-woocommerce' ),
				),
				'required_field' => __( 'obrigatório', 'flexify-checkout-for-woocommerce' ),
			),
			'update_cart_nonce' => wp_create_nonce('update_cart'),
			'shop_page' => Helpers::get_shop_page_url(),
			'base_country' => $base_country,
			'intl_util_path' => plugins_url( 'assets/vendor/intl-tel-input/js/utils.js', FLEXIFY_CHECKOUT_FILE ),
			'get_new_select_fields' => Helpers::get_new_select_fields(),
			'check_password_strenght' => Admin_Options::get_setting('check_password_strenght'),
			'get_all_checkout_fields' => Helpers::export_all_checkout_fields(),
			'opened_default_order_summary' => Admin_Options::get_setting('display_opened_order_review_mobile'),
			'enable_animation_process_purchase' => Admin_Options::get_setting('enable_animation_process_purchase'),
		));

		wp_localize_script( 'flexify-checkout-for-woocommerce', 'flexify_checkout_vars', $flexify_script_data );

		/**
		 * Modify script data
		 *
		 * @since 1.0.0
		 */
		$params = apply_filters( 'woocommerce_get_script_data', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'wc_ajax_url' => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'update_order_review_nonce' => wp_create_nonce('update-order-review'),
			'apply_coupon_nonce' => wp_create_nonce('apply-coupon'),
			'remove_coupon_nonce' => wp_create_nonce('remove-coupon'),
			'checkout_url' => \WC_AJAX::get_endpoint('checkout'),
			'is_checkout' => is_checkout() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ? 1 : 0,
			'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
			'i18n_checkout_error' => esc_attr__( 'Erro ao processar a finalização da compra. Por favor, tente novamente.', 'woocommerce' ),
		), 'wc-checkout', );

		wp_localize_script( 'flexify-checkout-for-woocommerce', 'wc_checkout_params', $params );
	}


	/**
	 * Enqueue admin scripts in page settings only
	 * 
	 * @since 1.0.0
	 * @version 3.8.0
	 * @return void
	 */
	public function admin_assets() {
		$min_file = WP_DEBUG ? '' : '.min';

		// check if is admin settings
		if ( is_flexify_checkout_admin_settings() ) {
			wp_enqueue_media();
			
			wp_enqueue_script( 'flexify-checkout-modal', $this->assets_url . 'components/modal/modal'. $min_file .'.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );
			wp_enqueue_style( 'flexify-checkout-modal-styles', $this->assets_url . 'components/modal/modal'. $min_file .'.css', array(), FLEXIFY_CHECKOUT_VERSION );
			wp_enqueue_script( 'flexify-checkout-visibility-controller', $this->assets_url . 'components/visibility-controller/visibility-controller'. $min_file .'.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );
			
			wp_enqueue_style( 'bootstrap-datepicker-styles', $this->assets_url . 'vendor/bootstrap-datepicker/bootstrap-datepicker'. $min_file .'.css', array(), FLEXIFY_CHECKOUT_VERSION );
			wp_enqueue_script( 'bootstrap-datepicker', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js', array('jquery'), '1.9.0' );
			wp_enqueue_script( 'bootstrap-datepicker-translate-pt-br', $this->assets_url . 'vendor/bootstrap-datepicker/bootstrap-datepicker.pt-BR.min.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );

			wp_enqueue_script( 'flexify-checkout-admin-scripts', $this->assets_url . 'admin/js/flexify-checkout-admin-scripts'. $min_file .'.js', array('jquery', 'media-upload'), FLEXIFY_CHECKOUT_VERSION );
			wp_enqueue_style( 'flexify-checkout-admin-styles', $this->assets_url . 'admin/css/flexify-checkout-admin-styles'. $min_file .'.css', array(), FLEXIFY_CHECKOUT_VERSION );

			if ( ! class_exists('Flexify_Dashboard') ) {
                wp_enqueue_style( 'bootstrap-grid', $this->assets_url . 'vendor/bootstrap/bootstrap-grid.min.css', array(), '5.3.3' );
                wp_enqueue_style( 'bootstrap-utilities', $this->assets_url . 'vendor/bootstrap/bootstrap-utilities.min.css', array(), '5.3.3' );
            }
		
			wp_localize_script( 'flexify-checkout-admin-scripts', 'flexify_checkout_params', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'set_logo_modal_title' => esc_html__( 'Escolher Imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ),
				'use_this_image_title' => esc_html__( 'Usar esta imagem', 'flexify-checkout-for-woocommerce' ),
				'upload_success' => esc_html__( 'Arquivo enviado com sucesso', 'flexify-checkout-for-woocommerce' ),
				'invalid_file' => esc_html__( 'O arquivo enviado não é permitido.', 'flexify-checkout-for-woocommerce' ),
				'font_exists' => esc_html__( 'Ops! Essa fonte já existe.', 'flexify-checkout-for-woocommerce' ),
				'confirm_deactivate_license' => esc_html__( 'Tem certeza que deseja desativar sua licença?', 'flexify-checkout-for-woocommerce' ),
				'offline_toast_header' => esc_html__( 'Ops! Não há conexão com a internet', 'flexify-checkout-for-woocommerce' ),
                'offline_toast_body' => esc_html__( 'As alterações não serão salvas.', 'flexify-checkout-for-woocommerce' ),
				'confirm_exclude_field' => esc_html__( 'Tem certeza que deseja excluir este campo?', 'flexify-checkout-for-woocommerce' ),
				'get_array_checkout_fields' => Helpers::get_array_index_checkout_fields(),
				'confirm_remove_option' => esc_html__( 'Tem certeza que deseja excluir esta opção?', 'flexify-checkout-for-woocommerce' ),
				'new_option_value' => esc_html__( 'Valor da opção', 'flexify-checkout-for-woocommerce' ),
				'new_option_title' => esc_html__( 'Título da opção', 'flexify-checkout-for-woocommerce' ),
				'placeholder_new_option_value' => esc_attr__( 'BR', 'flexify-checkout-for-woocommerce' ),
				'placeholder_new_option_title' => esc_attr__( 'Brasil', 'flexify-checkout-for-woocommerce' ),
				'close_aria_label_notice' => esc_attr__( 'Fechar', 'flexify-checkout-for-woocommerce' ),
				'set_animation_modal_title' => esc_html__( 'Escolher animação', 'flexify-checkout-for-woocommerce' ),
				'set_animation_button_title' => esc_html__( 'Usar este arquivo', 'flexify-checkout-for-woocommerce' ),
			));
		}
	}


	/**
	 * Remove password strenght
	 * 
	 * @since 3.5.0
	 * @version 3.9.7
	 * @return void
	 */
	public function disable_password_strenght() {
		wp_dequeue_script('wc-password-strength-meter');
		wp_deregister_script('wc-password-strength-meter');
	}
}