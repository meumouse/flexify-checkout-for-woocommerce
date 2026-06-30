<?php

namespace MeuMouse\Flexify_Checkout\Core;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Admin\Fonts_Manager;
use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Checkout\Themes;
use MeuMouse\Flexify_Checkout\Checkout\Steps;
use MeuMouse\Flexify_Checkout\Checkout\Fields;
use MeuMouse\Flexify_Checkout\Checkout\Conditions;
use MeuMouse\Flexify_Checkout\Checkout\Headless_Data;
use MeuMouse\Flexify_Checkout\Checkout\React_Checkout;
use MeuMouse\Flexify_Checkout\Views\Styles;
use MeuMouse\Flexify_Checkout\Validations\ISO3166;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Register/enqueue frontend and backend scripts
 *
 * @since 1.0.0
 * @version 5.5.0
 * @package MeuMouse\Flexify_Checkout\Core
 * @author MeuMouse.com
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
	 * Set min file extension
	 *
	 * Always empty: the hand-maintained minified bundles were removed because
	 * there is no build pipeline to keep them in sync with the source, and
	 * gzip/brotli already handle on-the-wire compression. Assets are served
	 * from their unminified source. Kept as a property so enqueue calls stay
	 * unchanged and a future build step can repopulate it.
	 *
	 * @since 5.0.0
	 * @return string
	 */
	public $min_file = '';

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
	 * @version 5.3.3
	 * @return void
	 */
	public function frontend_assets() {
		if ( ! defined( 'IS_FLEXIFY_CHECKOUT' ) || ! IS_FLEXIFY_CHECKOUT ) {
			return;
		}

		global $wp, $wp_scripts, $wp_styles;

		$theme = Themes::get_theme();
		$custom_inline_js = '';

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

		// When the React checkout is active on the checkout step — or an admin is
		// previewing it inside the live builder — enqueue the React bundle instead
		// of the legacy stack and bail out early. Also covers the React thank-you
		// (order-received) page, which renders the React bundle in thank-you mode.
		if (
			( is_flexify_checkout() && ( Helpers::is_react_checkout_enabled() || Helpers::is_builder_preview() ) )
			|| $this->is_react_thankyou()
		) {
			$this->react_checkout_assets();

			return;
		}

		// enqueue checkout theme styles
		wp_enqueue_style( 'flexify-checkout-theme', $this->assets_url . 'frontend/css/templates/' . $theme . '/main'. $this->min_file .'.css', array(), $this->version, false );

		// render dynamic styles
		if ( is_flexify_checkout() || Helpers::is_thankyou_page() ) {
			$settings = get_option('flexify_checkout_settings');
			wp_add_inline_style( 'flexify-checkout-theme', Styles::render_dynamic_styles( $settings ) );

			if ( ! empty( $settings['custom_css_checkout'] ) ) {
				wp_add_inline_style( 'flexify-checkout-theme', trim( (string) $settings['custom_css_checkout'] ) );
			}

			if ( ! empty( $settings['custom_js_checkout'] ) ) {
				$custom_inline_js = trim( (string) $settings['custom_js_checkout'] );
			}
		}

		// set dependencies for scripts
		$deps = array(
			'jquery',
			'jquery-blockui',
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

		// process animation purchase
		if ( Admin_Options::get_setting('enable_animation_process_purchase') === 'yes' ) {
			wp_enqueue_script( 'lordicon-player', 'https://cdn.lordicon.com/lordicon.js', array() );
		}

		// enqueue plugin scripts (Vite build of app/src/checkout, versioned by build mtime)
		wp_enqueue_script(
			'flexify-checkout-for-woocommerce',
			FLEXIFY_CHECKOUT_URL . 'app/dist/checkout/main.js',
			$deps,
			Scripts::get_asset_version('checkout/main.js'),
			true
		);

		if ( $custom_inline_js && ( is_flexify_checkout() || Helpers::is_thankyou_page() ) ) {
			wp_add_inline_script( 'flexify-checkout-for-woocommerce', $custom_inline_js, 'after' );
		}

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
				'error' => __( 'Please correct all errors and try again.', 'flexify-checkout-for-woocommerce' ),
				'errorAddressSearch' => __( 'Look for an address and try again.', 'flexify-checkout-for-woocommerce' ),
				'login' => __( 'Enter', 'flexify-checkout-for-woocommerce' ),
				'pay' => __( 'Pay', 'flexify-checkout-for-woocommerce' ),
				'coupon_success' => __( 'The coupon has been removed.', 'flexify-checkout-for-woocommerce' ),
				'account_exists' => __( 'An account is already registered with this email address. Would you like to log into it?', 'flexify-checkout-for-woocommerce' ),
				'login_successful' => __( 'Welcome back!', 'flexify-checkout-for-woocommerce' ),
				'lostpassword_success' => __( 'If the provided email is registered, you will receive a link to reset your password.', 'flexify-checkout-for-woocommerce' ),
				'lostpassword_invalid_email' => __( 'Please enter a valid email.', 'flexify-checkout-for-woocommerce' ),
				'lostpassword_error' => __( 'Could not send the reset email. Please try again.', 'flexify-checkout-for-woocommerce' ),
				'error_occured' => __( 'An error occurred', 'flexify-checkout-for-woocommerce' ),
				'phone' => array(
					'invalid' => __( 'Please enter a valid phone number.', 'flexify-checkout-for-woocommerce' ),
				),
				'cpf' => array(
					'invalid' => __( 'Please enter a valid CPF.', 'flexify-checkout-for-woocommerce' ),
				),
				'cnpj' => array(
					'invalid' => __( 'Please enter a valid CNPJ.', 'flexify-checkout-for-woocommerce' ),
				),
				'required_field' => __( 'mandatory', 'flexify-checkout-for-woocommerce' ),
			),
			'nonces' => array(
				'remove_product' => wp_create_nonce('flexify_checkout_remove_product'),
				'undo_remove_product' => wp_create_nonce('flexify_checkout_undo_remove_product'),
			),
			'shop_page' => Helpers::get_shop_page_url(),
			'base_country' => Fields::get_base_country(),
			'path_to_utils' => $this->assets_url . 'vendor/intl-tel-input/js/utils.js',
			'get_new_select_fields' => Helpers::get_new_select_fields(),
			'check_password_strenght' => Admin_Options::get_setting('check_password_strenght'),
			'get_all_checkout_fields' => Helpers::export_all_checkout_fields(),
			'opened_default_order_summary' => Admin_Options::get_setting('display_opened_order_review_mobile'),
			'enable_animation_process_purchase' => Admin_Options::get_setting('enable_animation_process_purchase'),
			'field_condition' => Conditions::export_field_rules_for_js(),
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
			'countdown_enabled' => Admin_Options::get_setting('enable_checkout_countdown'),
			'countdown_value' => Admin_Options::get_setting('checkout_countdown_value'),
			'countdown_unit' => Admin_Options::get_setting('checkout_countdown_unit'),
			'countdown_action' => Admin_Options::get_setting('checkout_countdown_action'),
			'countdown_title' => Admin_Options::get_setting('checkout_countdown_title'),
			'is_thankyou' => is_order_received_page() ? 'yes' : 'no',
			'funnel_beacon_url' => esc_url_raw( rest_url('flexify-checkout/v1/recovery/track-step') ),
			'plugin_version' => $this->version,
			'debug_mode' => defined('FLEXIFY_CHECKOUT_DEBUG_MODE') && FLEXIFY_CHECKOUT_DEBUG_MODE === true ? 'yes' : 'no',
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
			'i18n_checkout_error' => esc_attr__( 'Error processing checkout. Please try again.', 'flexify-checkout-for-woocommerce' ),
		), 'wc-checkout', );

		$params = array_merge( $params, $wc_params );

		// send params to frontend
		wp_localize_script( 'flexify-checkout-for-woocommerce', 'flexify_checkout_params', $params );
	}


	/**
	 * Whether the current request is the React thank-you (order-received) page.
	 *
	 * @since 6.0.0
	 * @return bool
	 */
	private function is_react_thankyou() {
		return Helpers::is_react_checkout_enabled() && Helpers::is_thankyou_page();
	}


	/**
	 * Enqueue and localize the React checkout bundle.
	 *
	 * Built by app/vite.checkout-react.config.js into app/dist/checkout-react/.
	 * The app reads the localized flexify_react_checkout object, talks to the
	 * WooCommerce Store API (cart/totals/place-order) and the flexify-checkout/v1
	 * endpoints (rules, WhatsApp login, address search).
	 *
	 * @since 6.0.0
	 * @return void
	 */
	public function react_checkout_assets() {
		$settings = get_option('flexify_checkout_settings');

		// React app styles (emitted by the Vite lib build) + dynamic color vars.
		wp_enqueue_style(
			'flexify-react-checkout',
			FLEXIFY_CHECKOUT_URL . 'app/dist/checkout-react/main.css',
			array(),
			Scripts::get_asset_version('checkout-react/main.css')
		);

		if ( is_array( $settings ) ) {
			wp_add_inline_style( 'flexify-react-checkout', Styles::render_dynamic_styles( $settings ) );

			// Drive the React checkout theme (palette + globals) from settings, so
			// the initial paint is correct without JS. The builder pushes the same
			// values live over postMessage while editing.
			$accent_vars = '';

			$color_map = array(
				'--fc-primary' => 'set_primary_color',
				'--fc-primary-hover' => 'set_primary_color_on_hover',
				'--fc-secondary' => 'set_secondary_color',
				'--fc-success' => 'set_success_color',
				'--fc-warning' => 'set_warning_color',
				'--fc-danger' => 'set_danger_color',
				'--fc-info' => 'set_info_color',
				'--fc-bg' => 'checkout_bg_color',
				'--fc-text' => 'checkout_text_color',
			);

			foreach ( $color_map as $css_var => $setting_key ) {
				$value = Admin_Options::get_setting( $setting_key );

				if ( ! empty( $value ) ) {
					$accent_vars .= $css_var . ':' . esc_attr( $value ) . ';';
				}
			}

			$px_map = array(
				'--fc-radius' => 'checkout_border_radius',
				'--fc-font-size' => 'checkout_base_font_size',
				'--fc-field-height' => 'checkout_field_height',
			);

			foreach ( $px_map as $css_var => $setting_key ) {
				$value = Admin_Options::get_setting( $setting_key );

				if ( $value !== '' && $value !== null ) {
					$accent_vars .= $css_var . ':' . esc_attr( (int) $value ) . 'px;';
				}
			}

			// Selected font family: drive it through --fc-font-family so the React
			// checkout body AND the section headings (.fc-heading) pick it up. The
			// @font-face/@import itself is emitted by Styles::render_dynamic_styles().
			$font_id = Admin_Options::get_setting('set_font_family');
			$fonts = Fonts_Manager::get_fonts();

			if ( ! empty( $font_id ) && isset( $fonts[ $font_id ]['font_name'] ) ) {
				$font_name = $fonts[ $font_id ]['font_name'];
				$accent_vars .= '--fc-font-family:\'' . esc_attr( $font_name ) . '\', Inter, Helvetica, Arial, sans-serif;';
			}

			if ( $accent_vars !== '' ) {
				wp_add_inline_style( 'flexify-react-checkout', '#flexify-react-checkout{' . $accent_vars . '}' );
			}

			if ( ! empty( $settings['custom_css_checkout'] ) ) {
				wp_add_inline_style( 'flexify-react-checkout', trim( (string) $settings['custom_css_checkout'] ) );
			}
		}

		// Lordicon web component player, used by the React purchase animation overlay.
		if ( Admin_Options::get_setting('enable_animation_process_purchase') === 'yes' && License::is_valid() ) {
			wp_enqueue_script( 'lordicon-player', 'https://cdn.lordicon.com/lordicon.js', array(), null, true );
		}

		wp_enqueue_script(
			'flexify-react-checkout',
			FLEXIFY_CHECKOUT_URL . 'app/dist/checkout-react/main.js',
			array(),
			Scripts::get_asset_version('checkout-react/main.js'),
			true
		);

		/**
		 * Filter the data localized for the React checkout app.
		 *
		 * @since 6.0.0
		 * @param array $data Localized data.
		 */
		$data = apply_filters( 'Flexify_Checkout/React_Checkout/Script_Data', array(
			'rest_url' => esc_url_raw( rest_url('flexify-checkout/v1/') ),
			'store_api_url' => esc_url_raw( rest_url('wc/store/v1/') ),
			'wp_rest_nonce' => wp_create_nonce('wp_rest'),
			'store_api_nonce' => wp_create_nonce('wc_store_api'),
			'ajax_url' => admin_url('admin-ajax.php'),
			'is_user_logged_in' => is_user_logged_in(),
			'session_hash' => $this->get_session_hash(),
			'base_country' => Fields::get_base_country(),
			'geo' => Headless_Data::get_geo_data(),
			'localstorage_fields' => Fields::get_localstorage_fields(),
			'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'BRL',
			'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'R$',
			'logo' => $this->get_checkout_logo(),
			'reservation' => array(
				'enabled' => Admin_Options::get_setting('enable_checkout_countdown') === 'yes',
				'minutes' => $this->get_reservation_minutes(),
				'title' => Admin_Options::get_setting('checkout_countdown_title'),
			),
			'urls' => array(
				'checkout' => wc_get_checkout_url(),
				'cart' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/'),
				'shop' => Helpers::get_shop_page_url(),
				'order_received' => wc_get_checkout_url(),
			),
			'config' => Headless_Data::get_checkout_config(),
			'rules' => Headless_Data::get_checkout_rules(),
			'settings' => Headless_Data::get_public_settings(),
			'purchase_animation' => array(
				'enabled' => Admin_Options::get_setting('enable_animation_process_purchase') === 'yes' && License::is_valid(),
				'wait_text' => __( 'Please wait a few moments', 'flexify-checkout-for-woocommerce' ),
				'items' => array(
					array(
						'file' => esc_url( Admin_Options::get_setting('animation_process_purchase_file_1') ),
						'text' => Admin_Options::get_setting('text_animation_process_purchase_1'),
					),
					array(
						'file' => esc_url( Admin_Options::get_setting('animation_process_purchase_file_2') ),
						'text' => Admin_Options::get_setting('text_animation_process_purchase_2'),
					),
					array(
						'file' => esc_url( Admin_Options::get_setting('animation_process_purchase_file_3') ),
						'text' => Admin_Options::get_setting('text_animation_process_purchase_3'),
					),
				),
			),
			'flags' => array(
				'whatsapp_login' => Headless_Data::is_whatsapp_login_available(),
				'address_search' => Headless_Data::is_address_search_available(),
				'split_payment' => Admin_Options::get_setting('enable_payment_split') === 'yes',
				// When on (Pro), the delivery step is hidden for virtual-only carts.
				// When off (default), the delivery step always shows — independent of
				// whether any shipping method is configured.
				'optimize_digital' => Admin_Options::get_setting('enable_optimize_for_digital_products') === 'yes' && License::is_valid(),
			),
			'i18n' => array(
				'contact' => __( 'Contact', 'flexify-checkout-for-woocommerce' ),
				'shipping' => __( 'Delivery', 'flexify-checkout-for-woocommerce' ),
				'payment' => __( 'Payment', 'flexify-checkout-for-woocommerce' ),
				'order_summary' => __( 'Order summary', 'flexify-checkout-for-woocommerce' ),
				'cart' => __( 'Cart', 'flexify-checkout-for-woocommerce' ),
				'continue' => __( 'Continue', 'flexify-checkout-for-woocommerce' ),
				'continue_to_shipping' => __( 'Continue to shipping', 'flexify-checkout-for-woocommerce' ),
				'continue_to_payment' => __( 'Continue to payment', 'flexify-checkout-for-woocommerce' ),
				'back' => __( 'Back', 'flexify-checkout-for-woocommerce' ),
				'back_to_shop' => __( 'Back to shop', 'flexify-checkout-for-woocommerce' ),
				'edit' => __( 'Edit', 'flexify-checkout-for-woocommerce' ),
				'step' => __( 'Step', 'flexify-checkout-for-woocommerce' ),
				'reserved_for' => __( 'Your products have been reserved for:', 'flexify-checkout-for-woocommerce' ),
				'view_summary' => __( 'View order summary', 'flexify-checkout-for-woocommerce' ),
				'contact_title' => __( 'Purchaser details', 'flexify-checkout-for-woocommerce' ),
				'shipping_address' => __( 'Shipping address', 'flexify-checkout-for-woocommerce' ),
				'new_address' => __( 'New address', 'flexify-checkout-for-woocommerce' ),
				'shipping_methods' => __( 'Shipping methods', 'flexify-checkout-for-woocommerce' ),
				'payment_methods' => __( 'Payment methods', 'flexify-checkout-for-woocommerce' ),
				'order_notes' => __( 'Order notes', 'flexify-checkout-for-woocommerce' ),
				'subtotal' => __( 'Subtotal', 'flexify-checkout-for-woocommerce' ),
				'discount' => __( 'Discount', 'flexify-checkout-for-woocommerce' ),
				'total' => __( 'Total', 'flexify-checkout-for-woocommerce' ),
				'place_order' => __( 'Complete purchase', 'flexify-checkout-for-woocommerce' ),
				'apply' => __( 'Apply', 'flexify-checkout-for-woocommerce' ),
				'coupon_placeholder' => __( 'Discount coupon', 'flexify-checkout-for-woocommerce' ),
				'login_whatsapp' => __( 'Sign in with WhatsApp', 'flexify-checkout-for-woocommerce' ),
				'send_code' => __( 'Send code', 'flexify-checkout-for-woocommerce' ),
				'verify_code' => __( 'Verify code', 'flexify-checkout-for-woocommerce' ),
				'search_address' => __( 'Search address', 'flexify-checkout-for-woocommerce' ),
				'search' => __( 'Search…', 'flexify-checkout-for-woocommerce' ),
				'loading' => __( 'Loading…', 'flexify-checkout-for-woocommerce' ),
				'finishing_order' => __( 'Finalizing your order', 'flexify-checkout-for-woocommerce' ),
				'please_wait' => __( 'Please wait a few moments', 'flexify-checkout-for-woocommerce' ),
				'empty_cart' => __( 'Your cart is empty.', 'flexify-checkout-for-woocommerce' ),
				'generic_error' => __( 'An error occurred. Please try again.', 'flexify-checkout-for-woocommerce' ),
				'thankyou_title' => __( 'Thank you for your order!', 'flexify-checkout-for-woocommerce' ),
				'thankyou_greeting' => __( 'Thank you, %s!', 'flexify-checkout-for-woocommerce' ),
				'order_number' => __( 'Order number', 'flexify-checkout-for-woocommerce' ),
				'qty' => __( 'Qty', 'flexify-checkout-for-woocommerce' ),
				'delivery_details' => __( 'Delivery Details', 'flexify-checkout-for-woocommerce' ),
				'shipping_to' => __( 'Shipping to', 'flexify-checkout-for-woocommerce' ),
				'shipping_method' => __( 'Shipping method', 'flexify-checkout-for-woocommerce' ),
				'estimated_delivery' => __( 'Estimated delivery', 'flexify-checkout-for-woocommerce' ),
				'estimated_delivery_note' => __( 'We will send you tracking information as soon as your order ships', 'flexify-checkout-for-woocommerce' ),
				'what_happens_next' => __( 'What happens next', 'flexify-checkout-for-woocommerce' ),
				'need_help' => __( 'Need help with your order?', 'flexify-checkout-for-woocommerce' ),
				'track_order' => __( 'Track order', 'flexify-checkout-for-woocommerce' ),
				'contact_support' => __( 'Contact support', 'flexify-checkout-for-woocommerce' ),
			),
			'mode' => $this->is_react_thankyou() ? 'thankyou' : 'checkout',
			'thankyou' => $this->is_react_thankyou() ? Headless_Data::get_thankyou_data( React_Checkout::get_current_order() ) : null,
		));

		// Live builder preview: force editor mode and always feed the current
		// layout (even when the public toggle is off), so the admin can design
		// the checkout before enabling it for shoppers.
		if ( Helpers::is_builder_preview() ) {
			$data['editor'] = true;
			$data['builder_nonce'] = wp_create_nonce('flexify_builder_save');
			$data['rules']['layout'] = \MeuMouse\Flexify_Checkout\Admin\Settings\Layout_Store::get_layout_for_react();
		}

		wp_localize_script( 'flexify-react-checkout', 'flexify_react_checkout', $data );
	}


	/**
	 * Resolve the checkout header logo URL.
	 *
	 * Prefers the plugin's configured checkout logo, falling back to the theme
	 * custom logo. Returns an empty string when none is set.
	 *
	 * @since 6.0.0
	 * @return string
	 */
	private function get_checkout_logo() {
		$logo = Helpers::get_logo_image();

		if ( ! empty( $logo ) ) {
			return esc_url_raw( $logo );
		}

		$custom_logo_id = get_theme_mod('custom_logo');

		if ( $custom_logo_id ) {
			$url = wp_get_attachment_image_url( (int) $custom_logo_id, 'full' );

			if ( $url ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}


	/**
	 * Reservation countdown duration in minutes, derived from the checkout
	 * countdown settings (value + unit).
	 *
	 * @since 6.0.0
	 * @return int
	 */
	private function get_reservation_minutes() {
		$value = (int) Admin_Options::get_setting('checkout_countdown_value');

		if ( $value <= 0 ) {
			$value = 15;
		}

		$unit = Admin_Options::get_setting('checkout_countdown_unit');

		if ( $unit === 'hours' ) {
			return $value * 60;
		}

		if ( $unit === 'seconds' ) {
			return max( 1, (int) round( $value / 60 ) );
		}

		return $value;
	}


	/**
	 * Short, non-reversible fingerprint of the current WooCommerce session.
	 *
	 * The React checkout uses it to scope persisted form data (localStorage),
	 * so a regenerated session or a different shopper on the same browser starts
	 * the checkout clean instead of inheriting stale values.
	 *
	 * @since 6.0.0
	 * @return string Session hash, or '' when no session is available yet.
	 */
	private function get_session_hash() {
		if ( ! function_exists('WC') || ! WC()->session ) {
			return '';
		}

		$customer_id = WC()->session->get_customer_id();

		if ( empty( $customer_id ) ) {
			return '';
		}

		return substr( wp_hash( 'flexify_checkout_session_' . $customer_id ), 0, 16 );
	}


	/**
     * Enqueue all intl-tel-input i18n based on countries sold
     *
     * @since 5.0.0
	 * @version 5.1.0
     * @return array
     */
    public function build_iti_i18n() {
		// Keys come in uppercase; convert to lowercase for ITI
		$raw = ISO3166::country_codes();
		$countries = array_change_key_case( $raw, CASE_LOWER );

		/**
		 * Filter for modify i18n iti object
		 * 
		 * @since 5.0.0
		 */
		$labels = apply_filters( 'Flexify_Checkout/Assets/Iti_I18n', array(
			'selectedCountryAriaLabel'  => __( 'Selected country', 'flexify-checkout-for-woocommerce' ),
			'noCountrySelected'         => __( 'No country selected', 'flexify-checkout-for-woocommerce' ),
			'countryListAriaLabel'      => __( 'List of countries', 'flexify-checkout-for-woocommerce' ),
			'searchPlaceholder'         => __( 'Search', 'flexify-checkout-for-woocommerce' ),
			'zeroSearchResults'         => __( 'No results found', 'flexify-checkout-for-woocommerce' ),
			'oneSearchResult'           => __( '1 result found', 'flexify-checkout-for-woocommerce' ),
			'multipleSearchResults'     => __( '${count} results found', 'flexify-checkout-for-woocommerce' ),
		));

		// Merge into one flat i18n object
		return array_merge( $countries, $labels );
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