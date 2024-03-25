<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checkout core functions
 *
 * @since 1.0.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Core {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'maybe_optimize_for_digital' ) );
		add_action( 'wp', array( __CLASS__, 'wp' ) );
		add_action( 'woocommerce_loaded', array( __CLASS__, 'remove_checkout_shipping' ) );
		add_filter( 'template_include', array( __CLASS__, 'include_template' ), 100 );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'override_empty_cart_fragment' ) );
		add_filter( 'woocommerce_checkout_before_customer_details', array( __CLASS__, 'express_checkout_button_wrap' ) );

		// Hooks.
		add_action( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'update_order_review_framents' ) );
		add_action( 'body_class', array( __CLASS__, 'update_body_class' ) );

		// Set priorities.
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'custom_override_checkout_fields' ), 100 );

		add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'custom_override_billing_field_priorities' ), 100 );
		add_filter( 'woocommerce_shipping_fields', array( __CLASS__, 'custom_override_shipping_field_priorities' ), 100 );

		// Additonal JS Patterns.
		add_filter( 'woocommerce_form_field_args', array( __CLASS__, 'field_args' ), 20, 3 );

		// Remove placeholders.
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'custom_override_default_fields' ) );
		add_filter( 'woocommerce_get_country_locale_base', array( __CLASS__, 'remove_empty_placeholders' ), 100 );
		add_filter( 'woocommerce_form_field', array( __CLASS__, 'remove_empty_placeholders_html' ), 10, 4 );
		add_filter( 'woocommerce_form_field_text', array( __CLASS__, 'modify_form_field_replace_placeholder' ) );
		add_filter( 'woocommerce_form_field_tel', array( __CLASS__, 'modify_form_field_replace_placeholder' ) );
		add_filter( 'woocommerce_form_field_email', array( __CLASS__, 'modify_form_field_replace_placeholder' ) );

		// Locate template.
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'woocommerce_locate_template' ), 100, 3 );

		// set customer data on checkout session
		add_action( 'wp_ajax_get_checkout_session_data', array( $this, 'get_checkout_session_data_callback' ) );
		add_action( 'wp_ajax_nopriv_get_checkout_session_data', array( $this, 'get_checkout_session_data_callback' ) );

		// log checkout sessions
		add_action( 'woocommerce_session_set_flexify_checkout', array(__CLASS__, 'log_flexify_checkout_session' ) );

		// On save.
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'prepend_street_number_to_address_1' ), 10, 2 );

		// Unhook Default Coupon Form.
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

		// filter for display thankyou page when purchase is same email adress
		if ( Flexify_Checkout_Init::get_setting('enable_assign_guest_orders') === 'yes' && Flexify_Checkout_Init::license_valid() ) {
			add_filter( 'woocommerce_order_email_verification_required', '__return_false' );
			add_filter( 'woocommerce_order_received_verify_known_shoppers', '__return_false' );
		}

		add_filter( 'woocommerce_no_available_payment_methods_message', array( __CLASS__, 'custom_no_payment_methods_message' ) );

		// Add inline errors.
		add_filter( 'woocommerce_form_field', array( __CLASS__, 'render_inline_errors' ), 10, 5 );

		// Apply coupon via URL param on load page
		add_action( 'template_redirect', array( __CLASS__, 'apply_coupon_via_url' ) );

		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'replace_phone_number_on_submit' ), 10, 3 );

		// set terms and conditions default if option is activated
		if ( Flexify_Checkout_Init::get_setting('enable_terms_is_checked_default') === 'yes' && Flexify_Checkout_Init::license_valid() ) {
			add_filter( 'woocommerce_terms_is_checked_default', '__return_true' );
		}

		// remove section aditional notes if option is deactivated
		if ( Flexify_Checkout_Init::get_setting('enable_aditional_notes') === 'no' ) {
			add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
		}

		// enable AJAX request for autofill company field on digit CNPJ
		if ( Flexify_Checkout_Init::get_setting('enable_autofill_company_info') === 'yes' && Flexify_Checkout_Init::license_valid() ) {
			add_action( 'wp_ajax_cnpj_autofill_query', array( __CLASS__, 'cnpj_autofill_query_callback' ) );
			add_action( 'wp_ajax_nopriv_cnpj_autofill_query', array( __CLASS__, 'cnpj_autofill_query_callback' ) );
		}

		// add custom header on checkout page
		add_action( 'flexify_checkout_before_layout', array( $this, 'custom_header' ), 10 );

		// add custom footer on checkout page
		add_action( 'flexify_checkout_after_layout', array( $this, 'custom_footer' ) );

		// disable inter bank gateways if deactivated
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'disable_inter_bank_gateways' ) );
	}


	/**
	 * WP Hook.
	 *
	 * Earliest we can check if it's the checkout page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function wp() {
		if ( ! self::is_checkout() ) {
			return;
		}

		// Better x-theme compatibility.
		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
	}


	/**
	 * Maybe optimize for digital products
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_optimize_for_digital() {
		if ( Flexify_Checkout_Init::get_setting('enable_optimize_for_digital_products') === 'yes' && Flexify_Checkout_Init::license_valid() ) {
			add_filter( 'flexify_custom_steps', array( __CLASS__, 'disable_address_step' ) );
			add_filter( 'flexify_checkout_details_fields', array( __CLASS__, 'move_address_fields_to_step_1' ) );
		}
	}


	/**
	 * Remove checkout shipping fields as we add them ourselves
	 * 
	 * @since 1.0.0
	 */
	public static function remove_checkout_shipping() {
		remove_action( 'woocommerce_checkout_shipping', array( WC_Checkout::instance(), 'checkout_form_shipping' ) );
	}


	/**
	 * Include Template.
	 *
	 * @param string $template Template Path.
	 *
	 * @return string
	 */
	public static function include_template( $template ) {
		if ( ! self::is_flexify_template() ) {
			return $template;
		}

		$theme = self::get_theme();

		remove_action( 'woocommerce_before_shop_loop', 'wc_print_notices', 10 );
		remove_action( 'woocommerce_before_single_product', 'wc_print_notices', 10 );

		define( 'IS_FLEXIFY_CHECKOUT', true );

		global $flexify_shipping_prefix;
		$flexify_shipping_prefix = '';

		return FLEXIFY_CHECKOUT_PATH . 'templates/template-' . $theme . '.php';
	}


	/**
	 * Is Desktop.
	 *
	 * Check to see if this we are in desktop mode.
	 *
	 * @return boolean
	 */
	public static function is_desktop() {
		return ! wp_is_mobile();
	}


	/**
	 * Must run on `wp` hook at the earliest.
	 *
	 * @since 1.0.0
	 * @param bool $force_early Force early check by getting the post ID from the URL.
	 * @return bool
	 */
	public static function is_checkout( $force_early = false ) {
		if ( $force_early ) {
			$request_uri = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$page_id = url_to_postid( home_url( $request_uri ) );

			return wc_get_page_id( 'checkout' ) === $page_id;
		}

		if ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
			return false;
		}

		if ( is_checkout() ) {
			return true;
		}

		$wc_ajax = filter_input( INPUT_GET, 'wc-ajax', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( 'update_order_review' === $wc_ajax ) {
			return true;
		}

		$queried_object = get_queried_object();

		if ( is_wp_error( $queried_object ) || empty( $queried_object ) || ! isset( $queried_object->ID ) ) {
			return false;
		}

		$checkout_page_id = wc_get_page_id( 'checkout' );

		return $checkout_page_id === $queried_object->ID && $queried_object->is_main_query();
	}


	/**
	 * Check if current page is a Thank you page.
	 *
	 * @since 1.0.0
	 * @version 1.9.2
	 * @return bool
	 */
	public static function is_thankyou_page() {
		$force = filter_input( INPUT_GET, 'flexify_force_ty' );

		if ( '1' !== $force && Flexify_Checkout_Init::get_setting('enable_thankyou_page_template') !== 'yes' ) {
			return false;
		}

		if ( ! is_wc_endpoint_url( 'order-received' ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Disable the address step
	 *
	 * @since 1.0.0
	 * @param array $steps | Checkout Fields
	 * @return array
	 */
	public static function disable_address_step( $steps ) {
		if ( ! self::is_virtual_only_cart() ) {
			return $steps;
		}

		unset( $steps[1] );

		return array_values( $steps );
	}


	/**
	 * Move address fields to step 1
	 *
	 * @since 1.0.0
	 * @param array $details_fields Fields
	 * @return array
	 */
	public static function move_address_fields_to_step_1( $details_fields ) {
		if ( ! self::is_virtual_only_cart() ) {
			return $details_fields;
		}

		return array_merge( $details_fields, array( 'billing_country' ) );
	}

	
	/**
	 * Override checkout fields
	 *
	 * @since 1.0.0
	 * @version 3.0.0
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function custom_override_checkout_fields( $fields ) {
		if ( empty( $fields['shipping']['shipping_address_2']['label'] ) ) {
			$fields['shipping']['shipping_address_2']['label'] = __( 'Apartamento, suíte, unidade etc.', 'flexify-checkout-for-woocommerce' );
		}
		
		$remove_placeholder = array(
			'address_1',
			'address_2',
			'state',
			'country',
			'city',
			'postcode',
			'first_name',
			'last_name',
			'username',
			'password',
		);

		foreach ( $remove_placeholder as $field_name ) {
			if ( isset( $fields['billing'][ 'billing_' . $field_name ] ) ) {
				$fields['billing'][ 'billing_' . $field_name ]['placeholder'] = '';
			}

			if ( isset( $fields['shipping'][ 'shipping_' . $field_name ] ) ) {
				$fields['shipping'][ 'shipping_' . $field_name ]['placeholder'] = '';
			}

			if ( isset( $fields['account'][ 'account_' . $field_name ] ) ) {
				$fields['account'][ 'account_' . $field_name ]['placeholder'] = '';
			}
		}

		$fields['billing']['billing_first_name']['class'][] = 'required';
		$fields['billing']['billing_last_name']['class'][] = 'required';
		$fields['shipping']['shipping_first_name']['required'] = true;
		$fields['shipping']['shipping_last_name']['required'] = true;
		$fields['shipping']['shipping_first_name']['class'][] = 'required';
		$fields['shipping']['shipping_last_name']['class'][] = 'required';
		$fields['billing']['billing_address_2']['label_class'] = '';

		// set class for international phone number
		if ( isset( $fields['billing']['billing_phone'] ) && Flexify_Checkout_Init::get_setting('enable_ddi_phone_field') === 'yes' && Flexify_Checkout_Init::license_valid() ) {
			$fields['billing']['billing_phone']['class'][] = 'flexify-intl-phone';
		}

		// get checkout fields from reorder controller
		$get_field_options = get_option('flexify_checkout_step_fields', array());
		$get_field_options = maybe_unserialize( $get_field_options );

		// remove shipping fields if optimize for digital products option is active
		if ( Flexify_Checkout_Init::get_setting('enable_optimize_for_digital_products') === 'yes' && Flexify_Checkout_Init::license_valid() ) {
			if ( self::is_virtual_only_cart() ) {
				foreach ( $get_field_options as $index => $value ) {
					if ( isset( $value['step'] ) && $value['step'] === '2' ) {
						unset( $fields['billing'][$index] );
					}
				}

				unset( $fields['order']['order_comments'] );

				// Remove the last class from the postcode field.
				if ( isset( $fields['billing']['billing_postcode'] ) && isset( $fields['billing']['billing_postcode']['class'] ) ) {
					$search = array_search( 'form-row-last', $fields['billing']['billing_postcode']['class'] ); // get the key of the value to be removed

					if ( false !== $search ) {
						unset( $fields['billing']['billing_postcode']['class'][ $search ] ); // remove the item from the array using its key
					}
				}
			}
		}

		// check fields conditions
		if ( Flexify_Checkout_Init::license_valid() ) {
			foreach ( $get_field_options as $index => $value ) {
				// change array key for valid class
				$field_class = array(
					'left' => 'form-row-first',
					'right' => 'form-row-last',
					'full' => 'form-row-wide',
				);

				// field position
				if ( isset( $value['position'] ) ) {
					$fields['billing'][$index]['class'][] = $field_class[$value['position']];
				}

				// field custom class
				if ( isset( $value['classes'] ) ) {
					$fields['billing'][$index]['class'][] = $value['classes'];
				}

				// field custom label class
				if ( isset( $value['label_classes'] ) ) {
					$fields['billing'][$index]['label_class'] = $value['label_classes'];
				}

				// field label
				if ( isset( $value['label'] ) ) {
					$fields['billing'][$index]['label'] = $value['label'];
				}

				$required_filter = array(
					'yes' => true,
					'no' => false,
				);

				// required field
				if ( isset( $value['required'] ) && $index !== 'billing_country' ) {
					$fields['billing'][$index]['required'] = $required_filter[$value['required']];
				}

				// remove fields thats disabled
				if ( isset( $value['enabled'] ) && $value['enabled'] === 'no' && $index !== 'billing_country' ) {
					unset( $fields['billing'][$index] );
				}
			}
		}
		
		return $fields;
	}


	/**
	 * Check if the cart only contains virtual products
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_virtual_only_cart() {
		static $only_virtual = null;

		if ( null !== $only_virtual ) {
			return $only_virtual;
		}

		$only_virtual = true;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			// Check if there are non-virtual products.
			if ( ! $cart_item['data']->is_virtual() ) {
				$only_virtual = false;
				break;
			}
		}

		return $only_virtual;
	}


	/**
	 * Override billing field priorities.
	 *
	 * We override the priorities in `woocommerce_billing_fields` instead of
	 * `flexify_custom_override_checkout_fields` because plugins such as
	 * Checkout Field Editor for WooCommerce by ThemeHigh get the defaults
	 * from the earlier hook.
	 *
	 * @since 1.0.0
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function custom_override_billing_field_priorities( $fields ) {
		$step_fields = get_option('flexify_checkout_step_fields', array());
		$step_fields = maybe_unserialize( $step_fields );

		foreach ( $step_fields as $index => $value ) {
			self::set_field_priority( $fields, $index, $value['priority'] );
		}

		return $fields;
	}


	/**
	 * Override shipping field priorities.
	 *
	 * We override the priorities in `woocommerce_shipping_fields` instead of
	 * `flexify_custom_override_checkout_fields` because plugins such as
	 * Checkout Field Editor for WooCommerce by ThemeHigh get the defaults
	 * from the earlier hook.
	 *
	 * @since 1.0.0
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function custom_override_shipping_field_priorities( $fields ) {
		self::set_field_priority( $fields, 'shipping_first_name', 150 );
		self::set_field_priority( $fields, 'shipping_last_name', 160 );
		self::set_field_priority( $fields, 'shipping_company', 170 );
		self::set_field_priority( $fields, 'shipping_postcode', 180 );
		self::set_field_priority( $fields, 'shipping_address_1', 190 );
		self::set_field_priority( $fields, 'shipping_number', 200 );
		self::set_field_priority( $fields, 'shipping_address_2', 210 );
		self::set_field_priority( $fields, 'shipping_city', 220 );
		self::set_field_priority( $fields, 'shipping_state', 230 );

		return $fields;
	}
	

	/**
	 * Check if field exits, if it does then set the priority of given field.
	 *
	 * @param array $fields_group
	 * @param string $field_id
	 * @param int $priority
	 */
	public static function set_field_priority( &$fields_group, $field_id, $priority ) {
		if ( isset( $fields_group[ $field_id ] ) ) {
			$fields_group[ $field_id ]['priority'] = $priority;
		}
	}


	/**
	 * Override default fields.
	 *
	 * @since 1.0.0
	 * @param array $fields
	 * @return array
	 */
	public static function custom_override_default_fields( $fields ) {
		$fields_to_remove_placeholder = array( 'street_number', 'address_1', 'address_2', 'state', 'country', 'postcode', 'first_name', 'last_name' );
		$fields['address_2']['label'] = __( 'Apartamento, suíte, unidade etc.', 'flexify-checkout-for-woocommerce' );

		// Otherwise remove the placeholders.
		foreach ( $fields_to_remove_placeholder as $field_name ) {
			if ( isset( $fields[ $field_name ] ) ) {
				$fields[ $field_name ]['placeholder'] = '';
			}
		}

		return $fields;
	}


	/**
	 * Field args
	 *
	 * @since 1.0.0
	 * @param array $data
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public static function field_args( $data, $key, $value ) {
		if ( 'billing_phone' === $key ) {
			$data['custom_attributes']['pattern'] = '^(\(?\+?[0-9]*\)?)?[0-9_\- \(\)]*$';
		}

		return $data;
	}


	/**
	 * Remove empty placeholder attributes on checkout fields
	 *
	 * @since 1.0.0
	 * @param array $locale_base
	 * @return array
	 */
	public static function remove_empty_placeholders( $locale_base ) {
		if ( empty( $locale_base ) || ! is_array( $locale_base ) ) {
			return $locale_base;
		}

		foreach ( $locale_base as $key => $data ) {
			if ( ! isset( $data['placeholder'] ) || ! empty( $data['placeholder'] ) ) {
				continue;
			}

			unset( $locale_base[ $key ]['placeholder'] );
		}

		return $locale_base;
	}


	/**
	 * Remove empty placeholders from the HTML.
	 *
	 * @param string $field Field.
	 * @param string $key Key.
	 * @param array  $args Args.
	 * @param string $value Value.
	 *
	 * @return string
	 */
	public static function remove_empty_placeholders_html( $field, $key, $args, $value ) {
		if ( strpos( $field, 'placeholder=""' ) === false ) {
			return $field;
		}

		return str_replace( 'placeholder=""', '', $field );
	}


	/**
	 * Modify form field HTML.
	 *
	 * @param string $field Field.
	 * @param string $key Key.
	 * @param array  $args Args.
	 * @param string $value Value.
	 *
	 * @return string
	 */
	public static function modify_form_field_html( $field, $key, $args, $value ) {
		$field_required = __( 'Este campo é obrigatório', 'flexify-checkout-for-woocommerce' );
		$valid_number = __( 'Por favor insira um número de telefone válido', 'flexify-checkout-for-woocommerce' );

		if ( 'billing_phone' === $key || 'shipping_phone' === $key ) {
			return str_replace( '</p>', "<span class=\".error\">$valid_number</span></p>", $field );
		}

		if ( 'billing_first_name' === $key || 'billing_last_name' === $key || 'billing_email' === $key ) {
			return str_replace( '</p>', "<span class=\".error\">$field_required</span></p>", $field );
		}

		return $field;
	}


	/**
	 * Modify Form Field Replace Placeholder.
	 *
	 * @since 1.0.0
	 * @param string $field
	 * @return string
	 */
	public static function modify_form_field_replace_placeholder( $field ) {
		$field = str_replace( 'placeholder=""', '', $field );

		return str_replace( 'placeholder ', '', $field );
	}


	/**
	 * Send JSON from frontend for autofill fields
	 * 
	 * @since 1.4.5
	 * @return array
	 */
	public static function query_cnpj_data( $cnpj ) {
		$url = 'https://www.receitaws.com.br/v1/cnpj/' . $cnpj;
		$response = wp_safe_remote_get($url);
	
		if ( is_wp_error( $response ) ) {
			return false;
		}
	
		$body = wp_remote_retrieve_body( $response );

		return json_decode( $body );
	}


	/**
	 * AJAX callback function for get CNPJ data
	 * 
	 * @since 1.4.5
	 * @return void
	 */
	public static function cnpj_autofill_query_callback() {
		$cnpj = sanitize_text_field( $_POST['cnpj'] );
		$data = self::query_cnpj_data( $cnpj );
	
		if ( $data ) {
			wp_send_json_success( $data );
		}
	
		wp_die();
	}


	/**
	 * Change message if empty payment forms
	 * 
	 * @since 1.2.5
	 * @return string
	 */
	public static function custom_no_payment_methods_message( $message ) {
		$message = __( 'Desculpe, parece que não há métodos de pagamento disponíveis para sua localização. Entre em contato conosco se precisar de assistência ou desejar pagar de outra forma.', 'flexify-checkout-for-woocommerce' );
		
		return $message;
	}


	/**
	 * Prepend street number to billing and shipping address_1 field when order is created.
	 *
	 * @param WC_Order $order Order.
	 * @param array    $data  Posted Data.
	 *
	 * @return void
	 */
	public static function prepend_street_number_to_address_1( $order, $data ) {
		$current_billing_address = $order->get_billing_address_1();
		$billing_street_no = isset( $data['billing_street_number'] ) ? $data['billing_street_number'] : '';

		if ( $billing_street_no ) {
			$new_billing_address = sprintf( '%s, %s', $billing_street_no, $current_billing_address );

			/**
			 * Filter checkout billing address 1 before creating an order.
			 *
			 * @param string   $new_billing_address     New billing address.
			 * @param string   $current_billing_address Current billing address.
			 * @param string   $billing_street_no       Billing street number.
			 * @param WC_Order $order                   Order object.
			 *
			 * @return string
			 *
			 * @since 2.0.0
			 */
			$new_billing_address = apply_filters( 'checkout_billing_address_1_before_create_order', $new_billing_address, $current_billing_address, $billing_street_no, $order );
			$order->set_billing_address_1( $new_billing_address );
		}

		$current_shipping_address = $order->get_shipping_address_1();
		$shipping_street_no = isset( $data['shipping_street_number'] ) ? $data['shipping_street_number'] : '';

		if ( $shipping_street_no ) {
			$new_shipping_address = sprintf( '%s, %s', $shipping_street_no, $current_shipping_address );

			/**
			 * Filter checkout shipping address 1 before creating an order.
			 *
			 * @param string   $new_shipping_address     New shipping address.
			 * @param string   $current_shipping_address Current shipping address.
			 * @param string   $shipping_street_no       Shipping street number.
			 * @param WC_Order $order                    Order object.
			 *
			 * @return string
			 *
			 * @since 2.0.0
			 */
			$new_shipping_address = apply_filters( 'checkout_shipping_address_1_before_create_order', $new_shipping_address, $current_shipping_address, $shipping_street_no, $order );
			$order->set_shipping_address_1( $new_shipping_address );
		}
	}


	/**
	 * Render Inline Errors
	 *
	 * @param string $field Field.
	 * @param string $key Key.
	 * @param array $args Arguments.
	 * @param string $value Value.
	 * @param string $country Country.
	 *
	 * @return string
	 */
	public static function render_inline_errors( $field = '', $key = '', $args = array(), $value = '', $country = '' ) {
		$called_inline = false;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $key ) ) {
			$called_inline = true;
		}

		// If we are doing AJAX, get the parameters from POST request.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! $called_inline ) {
			$key = filter_input( INPUT_POST, 'key', FILTER_SANITIZE_STRING );
			$args = filter_input( INPUT_POST, 'args', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
			$value = filter_input( INPUT_POST, 'value', FILTER_SANITIZE_STRING );
			$country = filter_input( INPUT_POST, 'country', FILTER_SANITIZE_STRING );
		}

		$message = '';
		$message_type = 'error';
		$global_message = false;
		$custom = false;

		if ( (bool) $args['required'] ) {
			$message = sprintf( __( '%s é um campo obrigatório.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
			/**
			 * Filters the required field error message.
			 *
			 * @since 1.0.0
			 * @param string $message Message.
			 * @param string $key Key.
			 * @param array $args Arguments.
			 * @return string
			 */
			$message = apply_filters( 'flexify_required_field_error_msg', $message, $key, $args );
		}

		if ( (bool) $args['required'] && $value ) {

			if ( 'country' === $args['type'] && property_exists( WC()->countries, 'country_exists' ) && WC()->countries && ! WC()->countries->country_exists( $value ) ) {
				/* translators: ISO 3166-1 alpha-2 country code */
				$message = sprintf( __( "'%s' não é um código de país válido.", 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
				$custom  = true;
			}

			if ( 'postcode' === $args['type'] && ! WC_Validation::is_postcode( $value, $country ) ) {
				switch ( $country ) {
					case 'IE':
						/* translators: %1$s: field name, %2$s finder.eircode.ie URL */
						$message = sprintf( __( '%1$s não é válido. Você pode procurar o Eircode correto <a target="_blank" href="%2$s">aqui</a>.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ), 'https://finder.eircode.ie' );
						$custom  = true;
						break;
					default:
						/* translators: %s: field name */
						$message = sprintf( __( '%s não é um CEP válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
						$custom  = true;
						break;
				}
			}

			if ( 'phone' === $args['type'] && ! WC_Validation::is_phone( $value ) ) {
				$message = sprintf( __( '%s não é um número de telefone válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
			}

			if ( 'email' === $args['type'] && ! is_email( $value ) ) {
				$message = sprintf( __( '%s não é um endereço de e-mail válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
			}

		/*	if ( 'tel' === $args['type'] && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cpf( $value ) ) {
				$message = sprintf( __( 'O %s informado não é válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
			}

			if ( 'tel' === $args['type'] && ! Extra_Checkout_Fields_For_Brazil_Formatting::is_cnpj( $value ) ) {
				$message = sprintf( __( 'O %s informado não é válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
			}*/

			if ( 'email' === $args['type'] && ! is_user_logged_in() && email_exists( $value ) ) {
				/**
				 * Filter text displayed during registration when an email already exists.
				 *
				 * @since 1.0.0
				 * @param string $email | Email address.
				 * @return string
				 */
				$message = apply_filters( 'flexify_woocommerce_registration_error_email_exists', sprintf( __( 'Uma conta já está registrada com este endereço de e-mail. <a href="#" data-login>Deseja entrar na sua conta?</a>', 'flexify-checkout-for-woocommerce' ), '' ) );
				$message_type = 'info';
			}
		}

		/**
		 * Filters the Inline Error Message.
		 *
		 * @param string $message Message.
		 * @param string $field Field.
		 * @param string $key Key.
		 * @param array $args Arguments.
		 * @param string $value Value.
		 * @param string $country Country.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		$message = apply_filters( 'flexify_custom_inline_message', $message, $field, $key, $args, $value, $country );

		/**
		 * Filters the Global Error Message.
		 *
		 * @since 1.0.0
		 * @param string $message Message.
		 * @param string $field Field.
		 * @param string $key Key.
		 * @param array  $args Arguments.
		 * @param string $value Value.
		 * @param string $country Country.
		 * @return string
		 */
		$global_message = apply_filters( 'flexify_custom_global_message', $global_message, $field, $key, $args, $value, $country );

		// If we are doing AJAX, just return the message.
		$action = filter_input( INPUT_POST, 'action' );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && in_array( $action, array( 'flexify_check_for_inline_error', 'flexify_check_for_inline_errors' ), true ) ) {
			$response = array(
				'message' => $message,
				'isCustom' => $custom,
				'globalMessage' => $global_message,
				'globalData' => array( 'data-flexify-error' => 1 ),
				'messageType' => $message_type,
			);

			if ( $called_inline ) {
				return $response;
			}

			wp_send_json_success( $response );

			exit;
		}

		$target_fields = array(
			'billing_first_name',
			'billing_last_name',
			'billing_phone',
			'billing_phone_full_number',
			'billing_email',
			'billing_company',
			'billing_gender',
			'billing_cpf',
			'billing_rg',
			'billing_birthdate',
			'billing_cnpj',
			'billing_ie',
			'billing_country',
			'billing_postcode',
			'billing_address_1',
			'billing_number',
			'billing_neighborhood',
			'billing_city',
			'billing_state',
		);

		$data_attributes = '<p ';
		$data_attributes .= sprintf( 'data-type="%s"', esc_attr( $args['type'] ) ) . ' ';
		$data_attributes .= sprintf( 'data-label="%s"', esc_attr( $args['label'] ) ) . ' ';

		// check if field is allowed from array $target_fields list
		if ( in_array( $args['id'], $target_fields ) ) {
			if ( strpos( $field, '</p>' ) !== false ) {
				$error = '<span class="error">';
					$error .= $message;
				$error .= '</span>';

				$field = substr_replace( $field, $error, strpos( $field, '</p>' ), 0 ); // Add before closing paragraph tag.
				$field = substr_replace( $field, $data_attributes, strpos( $field, '<p>' ), 2 ); // Add to opening paragraph tag.
			}
		}

		return $field;
	}


	/**
	 * Get theme
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_theme() {
		return Flexify_Checkout_Init::get_setting('flexify_checkout_theme') ? Flexify_Checkout_Init::get_setting('flexify_checkout_theme') : 'modern';
	}


	/**
	 * Update order review fragments
	 *
	 * @since 1.0.0
	 * @param array $fragments
	 * @return array
	 */
	public static function update_order_review_framents( $fragments ) {
		$fragments['.flexify-review-customer'] = Flexify_Checkout_Steps::get_review_customer_fragment();

		// Heading with cart item count.
		ob_start();
		wc_get_template( 'checkout/cart-heading.php' );
		$fragments['.flexify-heading--order-review'] = ob_get_clean();

		$new_fragments = array(
			'total' => WC()->cart->get_total(),
			'shipping_row' => Flexify_Checkout_Steps::get_shipping_row(),
		);

		if ( isset( $fragments['flexify'] ) ) {
			$fragments['flexify'] = array_merge( $fragments['flexify'], $new_fragments );
		} else {
			$fragments['flexify'] = $new_fragments;
		}

		return $fragments;
	}


	/**
	 * Add additional classes to the body tag on checkout page.
	 *
	 * @since 1.0.0
	 * @param array $classes Classes
	 * @return array
	 */
	public static function update_body_class( $classes ) {
		if ( ! is_checkout() ) {
			return $classes;
		}

		if ( ! is_user_logged_in() && 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
			$classes[] = 'flexify-wc-allow-login';
		}

		return $classes;
	}

	
	/**
	 * Locate templates.
	 *
	 * @since 1.0.0
	 * @param string $template Template.
	 * @param string $template_name Template Name.
	 * @param string $template_path Template Path.
	 * @return mixed|string
	 */
	public static function woocommerce_locate_template( $template, $template_name, $template_path ) {
		if ( ! self::is_flexify_template() ) {
			return $template;
		}

		/**
		 * Match any templates relating to the checkout, including those from Flexify itself.
		 *
		 * If the template contains one of these strings, continue through this function, so we can
		 * either change it to a Flexify template, or revert it back to the WooCommerce path.
		 *
		 * We don't want the theme to load any of these templates, as they are all handled by Flexify.
		 *
		 * @param array $templates Templates.
		 * @param string $template Template.
		 * @param string $template_name Template name.
		 * @param string $template_path Template path.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		$reset_templates_src = apply_filters(
			'flexify_match_checkout_template_sources',
			array(
				'woocommerce/checkout', // Catches any file in the woocommerce/checkout override folder.
				'global/quantity-input.php',
				'templates/checkout/',
				'common/checkout/',
				'notices/',
			),
			$template,
			$template_name,
			$template_path
		);

		if ( ! empty( $reset_templates_src ) ) {
			$reset_template_src_matched = false;

			foreach ( $reset_templates_src as $reset_template_src ) {
				if ( strpos( strtolower( $template ), $reset_template_src ) ) {
					$reset_template_src_matched = true;

					break;
				}
			}

			if ( ! $reset_template_src_matched ) {
				return $template;
			}
		}

		/**
		 * Filter $template_name's which *are* allowed to be overridden by theme.
		 *
		 * @since 1.0.0
		 * @param array  $templates Array of template names.
		 * @param string $template Template.
		 * @param string $template_name Template name.
		 * @param string $template_path Template path.
		 * @return array
		 */
		$allowed_templates = apply_filters( 'flexify_allowed_template_overrides', array(), $template, $template_name, $template_path );

		if ( in_array( $template_name, $allowed_templates, true ) ) {
			return $template;
		}

		// Get the Flexify theme.
		$theme = self::get_theme();

		$plugin_path = FLEXIFY_CHECKOUT_PATH . 'woocommerce/' . $theme . '/'; // Flexify theme folder.
		$plugin_path_common = FLEXIFY_CHECKOUT_PATH . 'woocommerce/common/';

		$flexify_template = '';

		// Search the Flexify theme and common folders for the template.
		if ( file_exists( $plugin_path . $template_name ) ) {
			$flexify_template = $plugin_path . $template_name;
		} elseif ( file_exists( $plugin_path_common . $template_name ) ) {
			$flexify_template = $plugin_path_common . $template_name;
		}

		// If this template exists in Flexify, use it.
		if ( ! empty( $flexify_template ) && file_exists( $flexify_template ) ) {
			return $flexify_template;
		}

		// Otherwise, check in WooCommerce template folder path.
		$woo_template_path = WC()->plugin_path() . '/templates/' . $template_name;
		
		if ( $template_name && file_exists( $woo_template_path ) ) {
			return $woo_template_path;
		}

		// If not found anywhere else, return the original path.
		return $template;
	}


	/**
	 * Save customer data in WooCommerce 'flexify_checkout' session
	 * 
	 * @since 1.8.5
	 * @version 1.8.5
	 * @return void
	 */
	public function get_checkout_session_data_callback() {
		$first_name = isset( $_POST['billing_first_name'] ) ? sanitize_text_field( $_POST['billing_first_name'] ) : '';
		$last_name = isset( $_POST['billing_last_name'] ) ? sanitize_text_field( $_POST['billing_last_name'] ) : '';
		$phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( $_POST['billing_phone'] ) : '';
		$international_phone = isset( $_POST['billing_phone_full_number'] ) ? sanitize_text_field( $_POST['billing_phone_full_number'] ) : '';
		$email = isset( $_POST['billing_email'] ) ? sanitize_text_field( $_POST['billing_email'] ) : '';
		$company = isset( $_POST['billing_company'] ) ? sanitize_text_field( $_POST['billing_company'] ) : '';
		$billing_gender = isset( $_POST['billing_gender'] ) ? sanitize_text_field( $_POST['billing_gender'] ) : '';
		$billing_persontype = isset( $_POST['billing_persontype'] ) ? sanitize_text_field( $_POST['billing_persontype'] ) : '';
		$billing_cpf = isset( $_POST['billing_cpf'] ) ? sanitize_text_field( $_POST['billing_cpf'] ) : '';
		$billing_rg = isset( $_POST['billing_rg'] ) ? sanitize_text_field( $_POST['billing_rg'] ) : '';
		$billing_birthdate = isset( $_POST['billing_birthdate'] ) ? sanitize_text_field( $_POST['billing_birthdate'] ) : '';
		$billing_cnpj = isset( $_POST['billing_cnpj'] ) ? sanitize_text_field( $_POST['billing_cnpj'] ) : '';
		$billing_ie = isset( $_POST['billing_ie'] ) ? sanitize_text_field( $_POST['billing_ie'] ) : '';
		$billing_country = isset( $_POST['billing_country'] ) ? sanitize_text_field( $_POST['billing_country'] ) : '';
		$billing_postcode = isset( $_POST['billing_postcode'] ) ? sanitize_text_field( $_POST['billing_postcode'] ) : '';
		$billing_address_1 = isset( $_POST['billing_address_1'] ) ? sanitize_text_field( $_POST['billing_address_1'] ) : '';
		$billing_number = isset( $_POST['billing_number'] ) ? sanitize_text_field( $_POST['billing_number'] ) : '';
		$billing_neighborhood = isset( $_POST['billing_neighborhood'] ) ? sanitize_text_field( $_POST['billing_neighborhood'] ) : '';
		$billing_address_2 = isset( $_POST['billing_address_2'] ) ? sanitize_text_field( $_POST['billing_address_2'] ) : '';
		$billing_city = isset( $_POST['billing_city'] ) ? sanitize_text_field( $_POST['billing_city'] ) : '';
		$billing_state = isset( $_POST['billing_state'] ) ? sanitize_text_field( $_POST['billing_state'] ) : '';

		$flexify_checkout_session = array(
			'first_name' => $first_name,
			'last_name' => $last_name,
			'phone' => $phone,
			'international_phone' => $international_phone,
			'email' => $email,
			'company' => $company,
			'billing_gender' => $billing_gender,
			'billing_persontype' => $billing_persontype,
			'billing_cpf' => $billing_cpf,
			'billing_rg' => $billing_rg,
			'billing_birthdate' => $billing_birthdate,
			'billing_cnpj' => $billing_cnpj,
			'billing_ie' => $billing_ie,
			'billing_country' => $billing_country,
			'billing_postcode' => $billing_postcode,
			'billing_address_1' => $billing_address_1,
			'billing_number' => $billing_number,
			'billing_neighborhood' => $billing_neighborhood,
			'billing_address_2' => $billing_address_2,
			'billing_city' => $billing_city,
			'billing_state' => $billing_state,
		);

		// set flexify session with data recovered
		WC()->session->set( 'flexify_checkout', $flexify_checkout_session );

		$response = array(
			'status' => 'success',
			'data' => $flexify_checkout_session,
			'message' => 'Dados atualizados com sucesso.'
		);

		// create session logs
		do_action( 'woocommerce_session_set_flexify_checkout', $flexify_checkout_session );
	
		wp_send_json_success( $response );

		wp_die();
	}


	/**
	 * Create checkout session logs
	 * 
	 * @since 1.8.5
	 * @param array $session_data
	 * @return void
	 */
	public static function log_flexify_checkout_session( $session_data ) {
		$logger = wc_get_logger();
	
		if ( $logger ) {
			$logger_context = array(
				'source' => 'flexify_checkout_session',
				'session_data' => $session_data,
			);
			
			$logger->info( __( 'Dados da sessão atualizados', 'flexify-checkout-for-woocommerce' ), $session_data, $logger_context );
		}
	}


	/**
	 * Override empty cart fragment
	 *
	 * @since 1.0.0
	 * @param array $fragments
	 * @return array
	 */
	public static function override_empty_cart_fragment( $fragments ) {
		if ( ! WC()->cart->is_empty() || is_customize_preview() ) {
			return $fragments;
		}

		unset( $fragments['form.woocommerce-checkout'] );

		ob_start();

		include FLEXIFY_CHECKOUT_PATH . 'woocommerce/common/checkout/empty-cart.php';

		$fragments['flexify'] = array(
			'empty_cart' => ob_get_clean(),
		);

		return $fragments;
	}


	/**
	 * Replace phone number - use the phone number saved in the hidden field by intl-tel-input script.
	 *
	 * @since 1.0.0
	 * @param int $order_id | Order ID.
	 * @param array $posted_data | Posted Data.
	 * @param WC_Order $order | Order class
	 * @return void
	 */
	public static function replace_phone_number_on_submit( $order_id, $posted_data, $order ) {
		$billing_phone_formated = filter_input( INPUT_POST, 'billing_phone_full_number' );

		if ( empty( $billing_phone_formated ) ) {
			return;
		}

		$order->set_billing_phone( $billing_phone_formated );
		$order->save();
	}


	/**
	 * Add custom header on checkout
	 * 
	 * @since 3.0.0
	 * @return void
	 */
	public function custom_header() {
		$shortcode_header = Flexify_Checkout_Init::get_setting('shortcode_header');

		if ( ! empty( $shortcode_header ) ) {
			echo do_shortcode( $shortcode_header );
		}
	}


	/**
	 * Add custom footer on checkout
	 * 
	 * @since 3.0.0
	 * @return void
	 */
	public function custom_footer() {
		$shortcode_footer = Flexify_Checkout_Init::get_setting('shortcode_footer');

		if ( ! empty( $shortcode_footer ) ) {
			echo do_shortcode( $shortcode_footer );
		}
	}


	/**
	 * Apply coupon via URL
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function apply_coupon_via_url() {
		$coupon = Flexify_Checkout_Init::get_setting('coupon_code_for_auto_apply') && Flexify_Checkout_Init::license_valid() ? Flexify_Checkout_Init::get_setting('coupon_code_for_auto_apply') : '';

		if ( empty( $coupon ) || ! is_checkout() ) {
			return;
		}

		if ( WC()->cart->has_discount( $coupon ) ) {
			return;
		}

		if ( Flexify_Checkout_Init::get_setting('enable_auto_apply_coupon_code') === 'yes' && Flexify_Checkout_Init::license_valid() ) {
			WC()->cart->add_discount( sanitize_text_field( $coupon ) );
		}
	}

	
	/**
	 * Helper: Is Flexify template.
	 *
	 * @return bool
	 */
	public static function is_flexify_template() {
		/**
		 * Is flexify template.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'flexify_is_flexify_template', self::is_checkout() || self::is_thankyou_page() || is_wc_endpoint_url( 'order-pay' ) );
	}


	/**
	 * Express checkout buttons wrap
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function express_checkout_button_wrap() {
		?>
		<div class="flexify-express-checkout-wrap"></div>
		<?php
	}


	/**
	 * Disable Inter bank gateways
	 * 
	 * @since 2.3.0
	 * @param array $available_gateways
	 * @return array
	 */
	public function disable_inter_bank_gateways( $available_gateways ) {
		if ( Flexify_Checkout_Init::get_setting('enable_inter_bank_pix_api') !== 'yes' && isset( $available_gateways['interpix'] ) ) {
			unset( $available_gateways['interpix'] );
		}

		if ( Flexify_Checkout_Init::get_setting('enable_inter_bank_ticket_api') !== 'yes' && isset( $available_gateways['interboleto'] ) ) {
			unset( $available_gateways['interboleto'] );
		}

		return $available_gateways;
	}
}

if ( Flexify_Checkout_Init::get_setting('enable_flexify_checkout') ) {
	new Flexify_Checkout_Core();
}