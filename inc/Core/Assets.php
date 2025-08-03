<?php

namespace MeuMouse\Flexify_Checkout\Core;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;

use MeuMouse\Flexify_Checkout\Checkout\Themes;
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
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Assets {

	/**
	 * Get assets URL directory
	 * 
	 * @since 5.0.0
	 * @return string
	 */
	public $assets_url = FLEXIFY_CHECKOUT_ASSETS;

	/**
	 * Get plugin version
	 * 
	 * @since 5.0.0
	 * @return string
	 */
	public $version = FLEXIFY_CHECKOUT_VERSION;

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

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ), $max_priority );
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
	public function frontend_assets() {
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

		// Remove theme global styles
		wp_dequeue_style('global-styles');

		// enqueue checkout theme styles
		wp_enqueue_style( 'flexify-checkout-theme', $this->assets_url . 'frontend/css/templates/' . $theme . '/main.css', array(), $this->version, false );

		// render dynamic styles
		if ( is_flexify_checkout() || Helpers::is_thankyou_page() ) {
			$settings = get_option('flexify_checkout_settings');
			wp_add_inline_style( 'flexify-checkout-theme', Styles::render_dynamic_styles( $settings ) );
		}

		// set dependencies for scripts
		$deps = array(
			'jquery',
			'select2',
			'wc-checkout',
			'wc-country-select',
			'wc-address-i18n',
		);

		// load magnific popup library
		wp_enqueue_script( 'flexify-magnific-popup-js', $this->assets_url . 'vendor/magnific-popup/jquery.magnific-popup.min.js', array('jquery'), '1.2.0', false );
		wp_enqueue_style( 'flexify-magnific-popup-css', $this->assets_url . 'vendor/magnific-popup/magnific-popup.css', array(), '1.2.0' );
		$deps[] = 'flexify-magnific-popup-js';

		// international phone number selector
		if ( Admin_Options::get_setting('enable_ddi_phone_field') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			// load intl-tel-input library
			wp_enqueue_script( 'flexify-international-phone-js', $this->assets_url . 'vendor/intl-tel-input/js/intlTelInput.min.js', array(), '25.3.1', false );
			wp_enqueue_style( 'flexify-international-phone-css', $this->assets_url . 'vendor/intl-tel-input/css/intlTelInput.min.css', array(), '25.3.1' );
			wp_enqueue_style( 'flexify-international-phone-flag-offset-2x', $this->assets_url . 'vendor/intl-tel-input/css/flag-offset-2x.min.css', array(), $this->version );
			
			$deps[] = 'flexify-international-phone-js';
		}

		$timestamp = time();

		// Set script version to null to avoid version-based caching
		$version = null;

		// enable field masks
		if ( Admin_Options::get_setting('enable_field_masks') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			// try to prevent conflicts with Brazilian Market on WooCommerce plugin
			if ( ! wp_script_is( 'jquery-mask', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery-mask-lib', $this->assets_url . 'vendor/jquery-mask/jquery.mask.min.js', array('jquery'), '1.14.16' );

				$deps[] = 'jquery-mask-lib';
			}
		}

		// process animation purchase
		if ( Admin_Options::get_setting('enable_animation_process_purchase') === 'yes' ) {
			wp_enqueue_script( 'lordicon-player', 'https://cdn.lordicon.com/lordicon.js', array() );
		}

		// enqueue plugin scripts
		wp_enqueue_script( 'flexify-checkout-for-woocommerce', $this->assets_url . 'frontend/js/main.js?version=' . $timestamp, $deps, $version, true );

		/**
		 * Flexify checkout script localized data
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return array
		 */
		$params = apply_filters( 'Flexify_Checkout/Assets/Script_Data', array(
			'allowed_countries' => array_map( 'strtolower', array_keys( WC()->countries->get_allowed_countries() ) ),
			'ajax_url' => admin_url('admin-ajax.php'),
			'debug_mode' => FLEXIFY_CHECKOUT_DEBUG_MODE,
			'license_is_valid' => License::is_valid(),
			'is_user_logged_in' => is_user_logged_in(),
			'localstorage_fields' => Fields::get_localstorage_fields(),
			'international_phone' => Admin_Options::get_setting('enable_ddi_phone_field'),
			'auto_display_login_modal' => Admin_Options::get_setting('auto_display_login_modal'),
			'steps' => Steps::get_steps_hashes(),
			'i18n' => array(
				'iti_i18n' => $this->build_iti_i18n(),
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
			'base_country' => Fields::get_base_country(),
			'path_to_utils' => $this->assets_url . 'vendor/intl-tel-input/js/utils.js',
			'get_new_select_fields' => Helpers::get_new_select_fields(),
			'check_password_strenght' => Admin_Options::get_setting('check_password_strenght'),
			'get_all_checkout_fields' => Helpers::export_all_checkout_fields(),
			'opened_default_order_summary' => Admin_Options::get_setting('display_opened_order_review_mobile'),
			'enable_animation_process_purchase' => Admin_Options::get_setting('enable_animation_process_purchase'),
			'field_condition' => Conditions::filter_component_type('field'),
			'enable_emails_suggestions' => Admin_Options::get_setting('email_providers_suggestion'),
			'get_email_providers' => Admin_Options::get_setting('set_email_providers'),
			'enable_field_masks' => Admin_Options::get_setting('enable_field_masks'),
			'get_input_masks' => Fields::get_fields_with_mask(),
			'fill_address' => array(
				'enable_auto_fill_address' => Admin_Options::get_setting('enable_fill_address'),
				'api_service' => Admin_Options::get_setting('get_address_api_service'),
				'address_param' => Admin_Options::get_setting('api_auto_fill_address_param'),
				'neightborhood_param' => Admin_Options::get_setting('api_auto_fill_address_neightborhood_param'),
				'city_param' => Admin_Options::get_setting('api_auto_fill_address_city_param'),
				'state_param' => Admin_Options::get_setting('api_auto_fill_address_state_param'),
			),
			'enable_autofill_company_info' => Admin_Options::get_setting('enable_autofill_company_info'),
			'enable_hide_brazilian_market_fields' => Admin_Options::get_setting('enable_unset_wcbcf_fields_not_brazil'),
		));

		/**
		 * Modify script data
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 */
		$wc_params = apply_filters( 'woocommerce_get_script_data', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'wc_ajax_url' => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'update_order_review_nonce' => wp_create_nonce('update-order-review'),
			'apply_coupon_nonce' => wp_create_nonce('apply-coupon'),
			'remove_coupon_nonce' => wp_create_nonce('remove-coupon'),
			'checkout_url' => \WC_AJAX::get_endpoint('checkout'),
			'is_checkout' => is_checkout() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ? 1 : 0,
			'i18n_checkout_error' => esc_attr__( 'Erro ao processar a finalização da compra. Por favor, tente novamente.', 'woocommerce' ),
		), 'wc-checkout', );

		$params = array_merge( $params, $wc_params );

		// send params to frontend
		wp_localize_script( 'flexify-checkout-for-woocommerce', 'flexify_checkout_params', $params );
	}


	/**
     * Enqueue all intl-tel-input i18n based on countries sold
     *
     * @since 5.0.0
     * @return array
     */
    public function build_iti_i18n() {
		// Keys come in uppercase; convert to lowercase for ITI
		$raw = \MeuMouse\Flexify_Checkout\Validations\ISO3166::country_codes();
		$countries = array_change_key_case( $raw, CASE_LOWER );

		/**
		 * Filter for modify i18n iti object
		 * 
		 * @since 5.0.0
		 */
		$labels = apply_filters( 'Flexify_Checkout/Assets/Iti_I18n', array(
			'selectedCountryAriaLabel'  => __( 'País selecionado', 'flexify-checkout-for-woocommerce' ),
			'noCountrySelected'         => __( 'Nenhum país selecionado', 'flexify-checkout-for-woocommerce' ),
			'countryListAriaLabel'      => __( 'Lista de países', 'flexify-checkout-for-woocommerce' ),
			'searchPlaceholder'         => __( 'Pesquisar', 'flexify-checkout-for-woocommerce' ),
			'zeroSearchResults'         => __( 'Nenhum resultado encontrado', 'flexify-checkout-for-woocommerce' ),
			'oneSearchResult'           => __( '1 resultado encontrado', 'flexify-checkout-for-woocommerce' ),
			'multipleSearchResults'     => __( '${count} resultados encontrados', 'flexify-checkout-for-woocommerce' ),
		));

		// Merge into one flat i18n object
		return array_merge( $countries, $labels );
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
			
			wp_enqueue_script( 'flexify-checkout-modal', $this->assets_url . 'components/modal/modal'. $min_file .'.js', array('jquery'), $this->version );
			wp_enqueue_style( 'flexify-checkout-modal-styles', $this->assets_url . 'components/modal/modal'. $min_file .'.css', array(), $this->version );
			wp_enqueue_script( 'flexify-checkout-visibility-controller', $this->assets_url . 'components/visibility-controller/visibility-controller'. $min_file .'.js', array('jquery'), $this->version );
			
			wp_enqueue_style( 'bootstrap-datepicker-styles', $this->assets_url . 'vendor/bootstrap-datepicker/bootstrap-datepicker'. $min_file .'.css', array(), $this->version );
			wp_enqueue_script( 'bootstrap-datepicker', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js', array('jquery'), '1.9.0' );
			wp_enqueue_script( 'bootstrap-datepicker-translate-pt-br', $this->assets_url . 'vendor/bootstrap-datepicker/bootstrap-datepicker.pt-BR.min.js', array('jquery'), $this->version );

			wp_enqueue_script( 'flexify-checkout-admin-scripts', $this->assets_url . 'admin/js/flexify-checkout-admin-scripts'. $min_file .'.js', array('jquery', 'media-upload'), $this->version );
			wp_enqueue_style( 'flexify-checkout-admin-styles', $this->assets_url . 'admin/css/flexify-checkout-admin-styles'. $min_file .'.css', array(), $this->version );

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