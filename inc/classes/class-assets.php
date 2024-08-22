<?php

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Init;
use MeuMouse\Flexify_Checkout\License;
use MeuMouse\Flexify_Checkout\Helpers;
use MeuMouse\Flexify_Checkout\Steps;
use MeuMouse\Flexify_Checkout\Core;
use MeuMouse\Flexify_Checkout\Conditions;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Register/enqueue frontend and backend scripts
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Assets {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		$max_priority = defined( 'PHP_INT_MAX' ) ? PHP_INT_MAX : 2147483647;

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ), $max_priority );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}


	/**
	 * Frontend assets
	 * 
	 * @since 1.0.0
	 * @version 3.5.0
	 * @return void
	 */
	public static function frontend_assets() {
		if ( ! defined( 'IS_FLEXIFY_CHECKOUT' ) || ! IS_FLEXIFY_CHECKOUT ) {
			return;
		}

		global $wp, $wp_scripts, $wp_styles;

		$settings = get_option('flexify_checkout_settings');
		$theme = Core::get_theme();

		/**
		 * Choose which sources are allowed at checkout
		 *
		 * @since 1.0.0
		 */
		$allowed_sources = apply_filters( 'flexify_checkout_allowed_sources', array() );

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

		wp_enqueue_style( 'flexify-checkout-theme', FLEXIFY_CHECKOUT_ASSETS . 'frontend/css/templates/' . $theme . '/main.css', array(), FLEXIFY_CHECKOUT_VERSION, false );

		if ( is_flexify_checkout() || Core::is_thankyou_page() ) {
			wp_add_inline_style( 'flexify-checkout-theme', self::get_dynamic_styles( $settings ) );
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
		if ( Init::get_setting('enable_ddi_phone_field') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'flexify-international-phone-js', FLEXIFY_CHECKOUT_ASSETS . 'vendor/intl-tel-input/js/intlTelInput-jquery.min.js', array('jquery'), '17.0.19', false );
			wp_enqueue_style( 'flexify-international-phone-css', FLEXIFY_CHECKOUT_ASSETS . 'vendor/intl-tel-input/css/intlTelInput.min.css', array(), '17.0.19' );
			$deps[] = 'flexify-international-phone-js';
		}

		$timestamp = time();

		// Add the timestamp as a query parameter to the main.js file URL
		$script = FLEXIFY_CHECKOUT_ASSETS . 'frontend/js/main.js?version=' . $timestamp;

		// Set script version to null to avoid version-based caching
		$version = null;

		wp_enqueue_script('flexify-checkout-for-woocommerce', $script, $deps, $version, true);

		// autofill address to enter postcode (just valid for Brazil)
		if ( Init::get_setting('enable_fill_address') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'flexify-checkout-autofill-address-js', FLEXIFY_CHECKOUT_ASSETS . 'frontend/js/autofill-address.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION, false );

			// send params from JS
			$auto_fill_address_api_params = apply_filters( 'flexify_checkout_auto_fill_address', array(
				'api_service' => Init::get_setting('get_address_api_service'),
				'address_param' => Init::get_setting('api_auto_fill_address_param'),
				'neightborhood_param' => Init::get_setting('api_auto_fill_address_neightborhood_param'),
				'city_param' => Init::get_setting('api_auto_fill_address_city_param'),
				'state_param' => Init::get_setting('api_auto_fill_address_state_param'),
			));
			
			wp_localize_script( 'flexify-checkout-autofill-address-js', 'fcw_auto_fill_address_api_params', $auto_fill_address_api_params );
		}

		// autofill field on enter CNPJ (just valid for Brazil)
		if ( Init::get_setting('enable_autofill_company_info') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'flexify-checkout-autofill-cnpj-js', FLEXIFY_CHECKOUT_ASSETS . 'frontend/js/autofill-cnpj.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION, false );
		}

		// remove brazilian market fields if is not Brazil country
		if ( Init::get_setting('enable_unset_wcbcf_fields_not_brazil') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'flexify-checkout-remove-wcbcf-fields', FLEXIFY_CHECKOUT_ASSETS . 'frontend/js/remove-wcbcf-fields.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );
		}

		// enable field masks
		if ( Init::get_setting('enable_field_masks') === 'yes' && is_flexify_checkout() && License::is_valid() ) {
			wp_enqueue_script( 'jquery-mask-lib', FLEXIFY_CHECKOUT_ASSETS . 'vendor/jquery-mask/jquery.mask.min.js', array('jquery'), '1.14.16' );
			wp_enqueue_script( 'flexify-checkout-field-masks', FLEXIFY_CHECKOUT_ASSETS . 'frontend/js/field-masks.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );
			wp_localize_script( 'flexify-checkout-field-masks', 'fcw_field_masks', array( 'get_input_masks' => Core::get_fields_with_mask() ) );
		}

		// add email suggestions
		if ( Init::get_setting('email_providers_suggestion') === 'yes' ) {
			wp_enqueue_script( 'flexify-checkout-email-suggestions', FLEXIFY_CHECKOUT_ASSETS . 'frontend/js/email-suggestions.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );

			$emails_suggestions_params = apply_filters( 'flexify_checkout_emails_suggestions', array(
				'get_providers' => Init::get_setting('set_email_providers'),
			));

			wp_localize_script( 'flexify-checkout-email-suggestions', 'fcw_emails_suggestions_params', $emails_suggestions_params );
		}

		// add frontend conditions
		if ( ! empty( get_option('flexify_checkout_conditions') ) ) {
			wp_enqueue_script( 'flexify-checkout-conditions', FLEXIFY_CHECKOUT_ASSETS . 'frontend/js/conditions.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );

			$conditions_params = apply_filters( 'flexify_checkout_front_conditions', array(
				'field_condition' => Conditions::filter_component_type('field'),
			));

			wp_localize_script( 'flexify-checkout-conditions', 'fcw_condition_param', $conditions_params );
		}

		/**
		 * Flexify checkout script localized data
		 *
		 * @since 1.0.0
		 * @version 3.5.0
		 * @return array
		 */
		$flexify_script_data = apply_filters( 'flexify_checkout_script_data', array(
			'allowed_countries' => array_map( 'strtolower', array_keys( WC()->countries->get_allowed_countries() ) ),
			'ajax_url' => admin_url('admin-ajax.php'),
			'is_user_logged_in' => is_user_logged_in(),
			'localstorage_fields' => self::get_localstorage_fields(),
			'international_phone' => Init::get_setting('enable_ddi_phone_field') ? Init::get_setting('enable_ddi_phone_field') : '',
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
			'base_country' => WC()->countries->get_base_country(),
			'intl_util_path' => plugins_url( 'assets/vendor/intl-tel-input/js/utils.js', FLEXIFY_CHECKOUT_FILE ),
			'get_new_select_fields' => Helpers::get_new_select_fields(),
			'check_password_strenght' => Init::get_setting('check_password_strenght'),
			'get_all_checkout_fields' => Helpers::export_all_checkout_fields(),
			'opened_default_order_summary' => Init::get_setting('display_opened_order_review_mobile'),
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
			
			wp_enqueue_script( 'flexify-checkout-modal', FLEXIFY_CHECKOUT_ASSETS . 'components/modal/modal'. $min_file .'.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );
			wp_enqueue_style( 'flexify-checkout-modal-styles', FLEXIFY_CHECKOUT_ASSETS . 'components/modal/modal'. $min_file .'.css', array(), FLEXIFY_CHECKOUT_VERSION );
			wp_enqueue_script( 'flexify-checkout-visibility-controller', FLEXIFY_CHECKOUT_ASSETS . 'components/visibility-controller/visibility-controller'. $min_file .'.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );
			
			wp_enqueue_style( 'bootstrap-datepicker-styles', FLEXIFY_CHECKOUT_ASSETS . 'vendor/bootstrap-datepicker/bootstrap-datepicker'. $min_file .'.css', array(), FLEXIFY_CHECKOUT_VERSION );
			wp_enqueue_script( 'bootstrap-datepicker', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js', array('jquery'), '1.9.0' );
			wp_enqueue_script( 'bootstrap-datepicker-translate-pt-br', FLEXIFY_CHECKOUT_ASSETS . 'vendor/bootstrap-datepicker/bootstrap-datepicker.pt-BR.min.js', array('jquery'), FLEXIFY_CHECKOUT_VERSION );

			wp_enqueue_script( 'flexify-checkout-admin-scripts', FLEXIFY_CHECKOUT_ASSETS . 'admin/js/flexify-checkout-admin-scripts'. $min_file .'.js', array('jquery', 'media-upload'), FLEXIFY_CHECKOUT_VERSION );
			wp_enqueue_style( 'flexify-checkout-admin-styles', FLEXIFY_CHECKOUT_ASSETS . 'admin/css/flexify-checkout-admin-styles'. $min_file .'.css', array(), FLEXIFY_CHECKOUT_VERSION );

			if ( ! class_exists('Flexify_Dashboard') ) {
                wp_enqueue_style( 'bootstrap-grid', FLEXIFY_CHECKOUT_ASSETS . 'vendor/bootstrap/bootstrap-grid.min.css', array(), '5.3.3' );
                wp_enqueue_style( 'bootstrap-utilities', FLEXIFY_CHECKOUT_ASSETS . 'vendor/bootstrap/bootstrap-utilities.min.css', array(), '5.3.3' );
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
			));
		}
	}


	/**
	 * Return list of fields for which data is persistently stored on the browser
	 *
	 * @since 1.0.0
	 * @version 3.5.0
	 * @return array
	 */
	public static function get_localstorage_fields() {
		$fields = array(
			'billing_first_name',
			'billing_last_name',
			'billing_phone',
			'billing_persontype',
			'billing_cpf',
			'billing_rg',
			'billing_cnpj',
			'billing_ie',
			'billing_cellphone',
			'billing_birthdate',
			'billing_sex',
			'billing_company',
			'billing_email',
			'billing_country',
			'billing_street_number',
			'billing_number',
			'billing_address_1',
			'billing_address_2',
			'billing_neighborhood',
			'shipping_neighborhood',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_country',
			'shipping_street_number',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'order_comments',
			'jckwds-delivery-time',
			'jckwds-delivery-date',
		);

		// add compatibility with manage checkout fields feature
		if ( Init::get_setting('enable_manage_fields') === 'yes' ) {
			$get_step_fields = get_option('flexify_checkout_step_fields', array());
			$get_step_fields = maybe_unserialize( $get_step_fields );
	
			if ( is_array( $get_step_fields ) ) {
				foreach ( $get_step_fields as $step_field => $value ) {
					if ( ! in_array( $step_field, $fields ) ) {
						$fields[] = $step_field;
					}
				}
			}
		}

		return apply_filters( 'flexify_checkout_localstorage_fields', $fields );
	}


	/**
	 * Get dynamic styles
	 *
	 * @since 1.0.0
	 * @version 3.5.2
	 * @param array $settings | Get plugin settings
	 * @return string
	 */
	public static function get_dynamic_styles( $settings ) {
		$theme = Core::get_theme();
		$settings = get_option('flexify_checkout_settings', array());
		$primary = Init::get_setting('set_primary_color');
		$primary_hover = Init::get_setting('set_primary_color_on_hover');
		$set_placeholder_color = Init::get_setting('set_placeholder_color');
		$border_radius = Init::get_setting('input_border_radius') . Init::get_setting('unit_input_border_radius');
		$font = Init::get_setting('set_font_family');

		ob_start(); ?>

		@import url('<?php echo esc_attr( $settings['font_family'][$font]['font_url'] ); ?>');

		* {
			font-family: <?php echo esc_attr( $settings['font_family'][$font]['font_name'] ); ?>, Inter, Helvetica, Arial, sans-serif;
		}

		<?php
		/**
		 * We are using a style sheet tag so we have nice markup,
		 * but we are not rendering it, output buffer comes after
		 * the start and before the end.
		 */
		if ( 'modern' === $theme ) :
			if ( $settings['set_placeholder_color'] ) : ?>
				.flexify-checkout ::-webkit-input-placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout ::-moz-placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout ::-ms-input-placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout ::placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout :-ms-input-placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?> !important;
				}

				.flexify-checkout .form-row label:not(.checkbox) {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout .form-row label:not(.checkbox) abbr,
				.flexify-checkout .form-row label:not(.checkbox) span {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}
			<?php endif;

			if ( $settings['set_primary_color'] ) : ?>
				::-webkit-scrollbar-thumb:hover {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-checkout .flexify-button,
				.flexify-checkout .flexify-button:hover,
				.button,
				button {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.select2-container--default .select2-results__option--highlighted[aria-selected],
				.select2-container--default .select2-results__option--highlighted[data-selected] {
					background-color: <?php echo esc_attr( $primary ); ?> !important;
				}

				.select2-container--open .select2-dropdown,
				.form-row .select2-container--open .select2-selection--single {
					border-color: <?php echo esc_attr( $primary ); ?> !important;
				}

				.flexify-heading__count {
					background-color: <?php echo esc_attr( $primary ); ?> !important;
				}

				.form-row .select2-selection:focus,
				.form-row .select2-selection:hover,
				.form-row > .woocommerce-input-wrapper > strong:focus,
				.form-row > .woocommerce-input-wrapper > strong:hover,
				.form-row input[type="email"]:focus,
				.form-row input[type="email"]:hover,
				.form-row input[type="password"]:focus,
				.form-row input[type="password"]:hover,
				.form-row input[type="tel"]:focus,
				.form-row input[type="tel"]:hover,
				.form-row input[type="text"]:focus,
				.form-row input[type="text"]:hover,
				.form-row select:focus,
				.form-row select:hover,
				.form-row textarea:focus,
				.form-row textarea:hover {
					border-color: <?php echo esc_attr( $primary ); ?> !important;
				}

				.flexify-checkout a, .lost_password a {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-checkout a:hover, .lost_password a:hover {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
					filter: brightness( 80% );
				}

				.flexify-checkout.flexify-checkout--modern .flexify-checkout__login-button {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-checkout.flexify-checkout--modern .flexify-checkout__login-button:hover {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
					filter: brightness( 80% );
				}

				.flexify-checkout #payment .payment_methods li.wc_payment_method > input[type=radio]:checked + label:after,
				.flexify-checkout input[type=radio]:checked + label:after, .flexify-checkout input[type=radio]:checked + label:after {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
					border-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-review-customer__buttons a[data-stepper-goto] {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-review-customer__buttons a[data-stepper-goto]:hover {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.shipping-method-item.selected-method,
				.shipping-method-item:hover {
					border-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

				.shipping-method-item.selected-method:before {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

				.mp-details-pix-button {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

				.mp-qr-input:focus {
					border-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

				input[type="checkbox"]:checked {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
					border-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

			<?php endif;

			if ( $settings['set_primary_color_on_hover'] ) : ?>
				.flexify-checkout .button:not(.wc-forward,.woocommerce-MyAccount-downloads-file),
				.flexify-checkout .button:not(.wc-forward,.woocommerce-MyAccount-downloads-file):hover, .button:hover, button:hover {
					background-color: <?php echo esc_attr( $settings['set_primary_color_on_hover'] ); ?>;
				}

				.mp-details-pix-button:hover {
					background-color: <?php echo esc_attr( $settings['set_primary_color_on_hover'] ); ?> !important;
				}
			<?php endif;

			// set border radius
			if ( ! empty( $settings['input_border_radius'] ) ) : ?>
				.form-row .select2-selection,
				.form-row .select2-selection,
				.form-row input[type="email"],
				.form-row input[type="email"],
				.form-row input[type="password"],
				.form-row input[type="password"],
				.form-row input[type="tel"],
				.form-row input[type="tel"],
				.form-row input[type="text"],
				.form-row input[type="text"],
				.form-row select,
				.form-row select,
				.form-row textarea,
				.form-row textarea,
				#shipping_method li label,
				.button,
				.flexify-button,
				.flexify-ty-status {
					border-radius: <?php echo esc_attr( $border_radius ); ?> !important;
				}
	
				#order_review .quantity .quantity__button--minus {
					border-top-left-radius: <?php echo esc_attr( $border_radius ); ?> !important;
					border-bottom-left-radius: <?php echo esc_attr( $border_radius ); ?> !important;
				}
	
				#order_review .quantity .quantity__button--plus {
					border-top-right-radius: <?php echo esc_attr( $border_radius ); ?> !important;
					border-bottom-right-radius: <?php echo esc_attr( $border_radius ); ?> !important;
				}
			<?php endif;

			// set h2 font size
			if ( ! empty( $settings['h2_size'] ) ) : ?>
				.h2, h2 {
					font-size: <?php echo esc_attr( $settings['h2_size'] . $settings['h2_size_unit'] ); ?> !important;
				}
			<?php endif; ?>

			.processing .blockUI.blockOverlay {
  				background-image: url("<?php echo esc_attr( FLEXIFY_CHECKOUT_ASSETS . 'frontend/img/loader.gif' ); ?>") !important;
			}
		<?php endif;

		if ( Init::get_setting('enable_inter_bank_pix_api') === 'yes' ) : ?>
			.interpix-open-browser {
				background: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				border: 1px solid <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
			}
		<?php endif;
		
		$css = ob_get_clean();
		$css = wp_strip_all_tags( $css );

		return $css;
	}

}

new Assets();

if ( ! class_exists('MeuMouse\Flexify_Checkout\Assets\Assets') ) {
    class_alias( 'MeuMouse\Flexify_Checkout\Assets', 'MeuMouse\Flexify_Checkout\Assets\Assets' );
}