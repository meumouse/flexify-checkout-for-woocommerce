<?php

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Init;
use MeuMouse\Flexify_Checkout\License;
use MeuMouse\Flexify_Checkout\Helpers;
use MeuMouse\Flexify_Checkout\Steps;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Checkout core actions
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Core {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @version 3.8.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'maybe_optimize_for_digital' ) );
		add_action( 'wp', array( __CLASS__, 'wp' ) );
		add_action( 'woocommerce_loaded', array( __CLASS__, 'remove_checkout_shipping' ) );
		add_filter( 'template_include', array( __CLASS__, 'include_template' ), 100 );
		
		add_filter( 'woocommerce_checkout_before_customer_details', array( __CLASS__, 'express_checkout_button_wrap' ) );

		add_action( 'body_class', array( __CLASS__, 'update_body_class' ) );

		// Set priorities.
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'custom_override_checkout_fields' ), 100 );

		// enable checkout fields manager
		if ( Init::get_setting('enable_manage_fields') === 'yes' && License::is_valid() ) {
			add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'flexify_checkout_fields_manager' ), 150 );
			add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_custom_checkout_fields' ), 10, 2 );
			add_filter( 'woocommerce_admin_billing_fields', array( __CLASS__, 'custom_admin_billing_fields' ), 10, 1 );
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_custom_checkout_fields_in_admin_order_meta' ), 10, 1 );
			add_filter( 'woocommerce_email_order_meta_fields', array( __CLASS__, 'add_custom_checkout_fields_to_order_emails' ), 10, 3 );
			add_action( 'woocommerce_account_orders_columns', array( __CLASS__, 'add_custom_checkout_fields_to_my_account_orders' ), 10, 1 );
			add_action( 'woocommerce_my_account_my_orders_column', array( __CLASS__, 'display_custom_checkout_fields_in_my_account_orders' ), 10, 2 );
			add_action( 'woocommerce_customer_save_address', array( __CLASS__, 'save_custom_address_fields' ), 10, 1 );
			add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'add_custom_fields_to_billing_address' ), 20, 1 );
			add_filter( 'woocommerce_customer_meta_fields', array( __CLASS__, 'custom_fields_on_user_profile' ) );
			add_filter( 'woocommerce_user_column_billing_address', array( __CLASS__, 'user_column_billing_address' ), 1, 2 );
		}

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

		// set cart items data on checkout session
		add_action( 'wp_ajax_get_product_cart_session_data', array( $this, 'get_product_cart_session_data_callback' ) );
		add_action( 'wp_ajax_nopriv_get_product_cart_session_data', array( $this, 'get_product_cart_session_data_callback' ) );

		// set entry time on checkout session
		add_action( 'wp_ajax_set_checkout_entry_time', array( $this, 'set_checkout_entry_time_callback' ) );
		add_action( 'wp_ajax_nopriv_set_checkout_entry_time', array( $this, 'set_checkout_entry_time_callback' ) );

		// On save.
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'prepend_street_number_to_address_1' ), 10, 2 );

		// Unhook Default Coupon Form.
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

		// filter for display thankyou page when purchase is same email adress
		if ( Init::get_setting('enable_assign_guest_orders') === 'yes' && License::is_valid() ) {
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
		if ( Init::get_setting('enable_terms_is_checked_default') === 'yes' && License::is_valid() ) {
			add_filter( 'woocommerce_terms_is_checked_default', '__return_true' );
		}

		// remove section aditional notes if option is deactivated
		if ( Init::get_setting('enable_aditional_notes') === 'no' ) {
			add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
		}

		// enable AJAX request for autofill company field on digit CNPJ
		if ( Init::get_setting('enable_autofill_company_info') === 'yes' && License::is_valid() ) {
			add_action( 'wp_ajax_cnpj_autofill_query', array( __CLASS__, 'cnpj_autofill_query_callback' ) );
			add_action( 'wp_ajax_nopriv_cnpj_autofill_query', array( __CLASS__, 'cnpj_autofill_query_callback' ) );
		}

		// add custom header on checkout page
		add_action( 'flexify_checkout_before_layout', array( $this, 'custom_header' ), 10 );

		// add custom footer on checkout page
		add_action( 'flexify_checkout_after_layout', array( $this, 'custom_footer' ) );

		// set default country on checkout
		if ( Init::get_setting('enable_manage_fields') === 'yes' && License::is_valid() ) {
			add_filter( 'default_checkout_billing_country', array( $this, 'get_default_checkout_country' ) );
		}

		// remove password strenght
		if ( Init::get_setting('check_password_strenght') !== 'yes' ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'flexify_checkout_disable_password_strenght' ), 99999 );
		}

		// replace original checkout notices
		add_filter( 'wc_get_template', array( $this, 'flexify_checkout_notices' ), 10, 5 );

		// update fragments on update_order_review event
		add_action( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'update_order_review_framents' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'override_empty_cart_fragment' ) );
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
		if ( ! is_flexify_checkout() ) {
			return;
		}

		// Better x-theme compatibility.
		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
	}


	/**
	 * Maybe optimize for digital products
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function maybe_optimize_for_digital() {
		if ( Init::get_setting('enable_optimize_for_digital_products') === 'yes' && License::is_valid() ) {
			add_filter( 'flexify_custom_steps', array( __CLASS__, 'disable_address_step' ) );
		}
	}


	/**
	 * Remove checkout shipping fields as we add them ourselves
	 * 
	 * @since 1.0.0
	 */
	public static function remove_checkout_shipping() {
		remove_action( 'woocommerce_checkout_shipping', array( \WC_Checkout::instance(), 'checkout_form_shipping' ) );
	}


	/**
	 * Include Template.
	 *
	 * @since 1.0.0
	 * @param string $template | Template Path
	 * @return string
	 */
	public static function include_template( $template ) {
		if ( ! is_flexify_template() ) {
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
	 * Check if current page is a Thank you page.
	 *
	 * @since 1.0.0
	 * @version 3.8.0
	 * @return bool
	 */
	public static function is_thankyou_page() {
		$force = filter_input( INPUT_GET, 'flexify_force_ty', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( '1' !== $force && Init::get_setting('enable_thankyou_page_template') !== 'yes' ) {
			return false;
		}

		if ( ! is_wc_endpoint_url('order-received') ) {
			return false;
		}

		return true;
	}


	/**
	 * Disable the address step
	 *
	 * @since 1.0.0
	 * @version 3.5.0
	 * @param array $steps | Checkout Fields
	 * @return array
	 */
	public static function disable_address_step( $steps ) {
		if ( ! flexify_checkout_only_virtual() ) {
			return $steps;
		}

		unset( $steps[1] );

		return array_values( $steps );
	}

	
	/**
	 * Override checkout fields
	 *
	 * @since 1.0.0
	 * @version 3.7.3
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
		if ( isset( $fields['billing']['billing_phone'] ) && Init::get_setting('enable_ddi_phone_field') === 'yes' && License::is_valid() ) {
			$fields['billing']['billing_phone']['class'][] = 'flexify-intl-phone';
		}

		// check fields conditions
		if ( Init::get_setting('enable_manage_fields') !== 'yes' ) {
			if ( isset( $fields['billing']['billing_address_1'] ) ) {
				$fields['billing']['billing_address_1']['class'][] = 'row-first';
			}

			if ( isset( $fields['billing']['billing_number'] ) ) {
				$fields['billing']['billing_number']['class'][] = 'row-last';
			}

			if ( isset( $fields['billing']['billing_address_2'] ) ) {
				$fields['billing']['billing_address_2']['class'][] = 'row-first';
			}

			if ( isset( $fields['billing']['billing_neighborhood'] ) ) {
				$fields['billing']['billing_neighborhood']['class'][] = 'row-last';
			}

			if ( isset( $fields['billing']['billing_city'] ) ) {
				$fields['billing']['billing_city']['class'][] = 'row-first';
			}

			if ( isset( $fields['billing']['billing_state'] ) ) {
				$fields['billing']['billing_state']['class'][] = 'row-last';
			}
		}

		// remove shipping fields if optimize for digital products option is active
		if ( Init::get_setting('enable_optimize_for_digital_products') === 'yes' && License::is_valid() && flexify_checkout_only_virtual() ) {
			unset( $fields['order']['order_comments'] );

			// Remove the last class from the postcode field.
			if ( isset( $fields['billing']['billing_postcode'] ) && isset( $fields['billing']['billing_postcode']['class'] ) ) {
				$search = array_search( 'form-row-last', $fields['billing']['billing_postcode']['class'] ); // get the key of the value to be removed

				if ( false !== $search ) {
					unset( $fields['billing']['billing_postcode']['class'][ $search ] ); // remove the item from the array using its key
				}
			}

			$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
				
			foreach ( $get_fields as $index => $value ) {
				if ( isset( $value['step'] ) && $value['step'] === '2' && isset( $value['enabled'] ) && $value['enabled'] !== 'no' ) {
					$fields['billing'][$index]['required'] = false;
					unset( $fields['billing'][$index] );
				}
			}
		}
		
		return $fields;
	}


	/**
	 * Add new fields, reorder positions, and manage fields from WooCommerce checkout
	 * 
	 * @since 3.0.0
	 * @version 3.7.3
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function flexify_checkout_fields_manager( $fields ) {
		// get checkout fields from checkout controller
		$get_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

		// iterate for each step field
		foreach ( $get_fields as $index => $value ) {
			// field custom class
			if ( isset( $value['classes'] ) ) {
				$fields['billing'][$index]['class'][] = $value['classes'];
			}

			// field input masks
			if ( ! empty( $value['input_mask'] ) ) {
				$fields['billing'][$index]['class'][] = 'has-mask';
			}

			// change array key for valid class
			$field_class = array(
				'left' => 'row-first',
				'right' => 'row-last',
				'full' => 'form-row-wide',
			);

			// field position
			if ( isset( $value['position'] ) ) {
				$fields['billing'][$index]['class'][] = $field_class[$value['position']];
			}

			// required field
			if ( isset( $value['required'] ) ) {
				$fields['billing'][$index]['required'] = $value['required'] === 'yes' ? true : false;
				$fields['billing'][$index]['class'][] = 'required';
			}

			// field custom label class
			if ( isset( $value['label_classes'] ) ) {
				$fields['billing'][$index]['label_class'] = $value['label_classes'];
			}

			// field label
			if ( isset( $value['label'] ) ) {
				$fields['billing'][$index]['label'] = $value['label'];
			}

			// add field priority
			if ( isset( $value['priority'] ) ) {
				$fields['billing'][$index]['priority'] = $value['priority'];
			}

			// add new field type text
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) && $value['type'] === 'text' ) {
				$fields['billing'][$index] = array(
					'type' => 'text',
					'label' => $value['label'],
					'class' => array( $value['classes'] ),
					'clear' => true,
					'required' => $value['required'] === 'yes' ? true : false,
					'priority' => $value['priority'],
				);

				// field position
				if ( isset( $value['position'] ) ) {
					$fields['billing'][$index]['class'][] = $field_class[$value['position']];
				}
			}

			// add new field type textarea
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) && $value['type'] === 'textarea' ) {
				$fields['billing'][$index] = array(
					'type' => 'textarea',
					'label' => $value['label'],
					'class' => array( $value['classes'] ),
					'clear' => true,
					'required' => $value['required'] === 'yes' ? true : false,
					'priority' => $value['priority'],
				);

				// field position
				if ( isset( $value['position'] ) ) {
					$fields['billing'][$index]['class'][] = $field_class[$value['position']];
				}
			}

			// add new field type number
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) && $value['type'] === 'number' ) {
				$fields['billing'][$index] = array(
					'type' => 'number',
					'label' => $value['label'],
					'class' => array( $value['classes'] ),
					'clear' => true,
					'required' => $value['required'] === 'yes' ? true : false,
					'priority' => $value['priority'],
				);

				// field position
				if ( isset( $value['position'] ) ) {
					$fields['billing'][$index]['class'][] = $field_class[$value['position']];
				}
			}

			// add new field type password
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) && $value['type'] === 'password' ) {
				$fields['billing'][$index] = array(
					'type' => 'password',
					'label' => $value['label'],
					'class' => array( $value['classes'] ),
					'clear' => true,
					'required' => $value['required'] === 'yes' ? true : false,
					'priority' => $value['priority'],
				);

				// field position
				if ( isset( $value['position'] ) ) {
					$fields['billing'][$index]['class'][] = $field_class[$value['position']];
				}
			}

			// add new field type tel
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) && $value['type'] === 'phone' ) {
				$fields['billing'][$index] = array(
					'type' => 'tel',
					'label' => $value['label'],
					'class' => array( $value['classes'] ),
					'clear' => true,
					'validate' => array('phone'),
					'required' => $value['required'] === 'yes' ? true : false,
					'priority' => $value['priority'],
				);

				// field position
				if ( isset( $value['position'] ) ) {
					$fields['billing'][$index]['class'][] = $field_class[$value['position']];
				}
			}

			// add new field type url
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) && $value['type'] === 'url' ) {
				$fields['billing'][$index] = array(
					'type' => 'url',
					'label' => $value['label'],
					'class' => array( $value['classes'] ),
					'clear' => true,
					'required' => $value['required'] === 'yes' ? true : false,
					'priority' => $value['priority'],
				);

				// field position
				if ( isset( $value['position'] ) ) {
					$fields['billing'][$index]['class'][] = $field_class[$value['position']];
				}
			}

			// add new field type select
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && isset( $value['type'] ) && $value['type'] === 'select' ) {
				$index_option = array();
				
				// get select options
				foreach ( $value['options'] as $option ) {
					$index_option[$option['value']] = $option['text'] ?? '';
				}

				$fields['billing'][$index] = array(
					'type' => 'select',
					'options' => $index_option,
					'label' => $value['label'],
					'class' => array( $value['classes'] ),
					'clear' => true,
					'required' => $value['required'] === 'yes' ? true : false,
					'priority' => $value['priority'],
				);

				// field position
				if ( isset( $value['position'] ) ) {
					$fields['billing'][$index]['class'][] = $field_class[$value['position']];
				}
			}

			// remove fields thats disabled
			if ( isset( $value['enabled'] ) && $value['enabled'] === 'no' ) {
				unset( $fields['billing'][$index] );
			}
		}

		return $fields;
	}


	/**
	 * Save new checkout fields on order
	 * 
	 * @since 3.7.0
	 * @param int $order_id | Order ID
	 * @param array $data | Order data
	 * @return void
	 */
	public static function save_custom_checkout_fields( $order_id, $data ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				if ( ! empty( $_POST[$index] ) ) {
					update_post_meta( $order_id, $index, sanitize_text_field( $_POST[$index] ) );
				}
			}
		}
	}


	/**
	 * Add custom billing fields to the billing fields array in the admin
	 *
	 * @since 3.7.3
	 * @param array $fields | Billing fields array
	 * @return array
	 */
	public static function custom_admin_billing_fields( $fields ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$fields[$index] = array(
					'label' => isset( $value['label'] ) ? $value['label'] : '',
					'show'  => true,
					'class' => isset( $value['class'] ) ? $value['class'] : '',
				);
			}
		}

		return $fields;
	}


	/**
	 * Display new checkout fields on admin order
	 * 
	 * @since 3.7.0
	 * @param object|array $order | Order objetct
	 * @return void
	 */
	public static function display_custom_checkout_fields_in_admin_order_meta( $order ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$field_value = get_post_meta( $order->get_id(), $index, true );

				if ( ! empty( $field_value ) ) {
					echo '<p><strong>' . $value['label'] . ':</strong> ' . $field_value . '</p>';
				}
			}
		}
	}


	/**
	 * Add new checkout fields on order e-mails
	 * 
	 * @since 3.7.0
	 * @param array $fields | Checkout fields
	 * @param bool $sent_to_admin | If should sent to admin
	 * @param WC_Order $order | Order instance
	 * @return array
	 */
	public static function add_custom_checkout_fields_to_order_emails( $fields, $sent_to_admin, $order ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$field_value = get_post_meta( $order->get_id(), $index, true );

				if ( ! empty( $field_value ) ) {
					$fields[$index] = array(
						'label' => $value['label'],
						'value' => $field_value,
					);
				}
			}
		}
	
		return $fields;
	}


	/**
	 * Display new checkout fields on my account order columns
	 * 
	 * @since 3.7.0
	 * @param array $columns | My account columns
	 * @return array
	 */
	public static function add_custom_checkout_fields_to_my_account_orders( $columns ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()));
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$columns[$index] = $value['label'];
			}
		}
	
		return $columns;
	}


	/**
	 * Display new checkout fields on my account orders
	 * 
	 * @since 3.7.0
	 * @param WC_Order $order | Order instance
	 * @return void
	 */
	public static function display_custom_checkout_fields_in_my_account_orders( $order ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$field_value = get_post_meta( $order->get_id(), $index, true );

				if ( ! empty( $field_value ) ) {
					echo '<td>' . $field_value . '</td>';
				}
			}
		}
	}


	/**
	 * Save custom address fields for WooCommerce customers
	 * 
	 * @since 3.7.0
	 * @param int $user_id | User ID
	 * @return void
	 */
	public static function save_custom_address_fields( $user_id ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				if ( isset( $_POST[$index] ) ) {
					update_user_meta( $user_id, $index, sanitize_text_field( $_POST[$index] ) );
				}
			}
		}
	}


	/**
	 * Show custom address fields in WooCommerce My Account addresses section
	 * 
	 * @since 3.7.0
	 * @param array $address | Address fields
	 * @param string $load_address | Address type (billing or shipping)
	 * @return array
	 */
	public static function show_custom_address_fields_in_my_account( $address, $load_address ) {
		$user_id = get_current_user_id();
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$address[$index] = array(
					'label' => $value['label'],
					'value' => get_user_meta( $user_id, $index, true ),
					'required' => isset( $value['required'] ) ? ( $value['required'] === 'yes' ? true : false ) : false,
					'class' => isset( $value['classes'] ) ? array( $value['classes'] ) : array(),
					'priority' => isset( $value['priority'] ) ? $value['priority'] : 100,
				);
			}
		}
	
		return $address;
	}


	/**
	 * Add custom fields to WooCommerce billing address fields
	 * 
	 * @since 3.7.0
	 * @param array $fields | Billing address fields
	 * @return array
	 */
	public static function add_custom_fields_to_billing_address( $fields ) {
		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$fields[$index] = array(
					'label' => $value['label'],
					'type' => isset( $value['type'] ) ? $value['type'] : 'text',
					'required' => isset( $value['required'] ) ? ( $value['required'] === 'yes' ? true : false ) : false,
					'class' => isset( $value['classes'] ) ? array( $value['classes'] ) : array(),
					'priority' => isset( $value['priority'] ) ? $value['priority'] : 100,
				);
			}
		}
	
		return $fields;
	}


	/**
	 * Add new custom fields to user profile on WordPress user meta fields
	 * 
	 * @since 3.8.0
	 * @param array $fields | Current fields
	 * @return array
	 */
	public static function custom_fields_on_user_profile( $fields ) {
		$custom_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
		$new_fields['billing']['title'] = __( 'Endereço de cobrança', 'flexify-checkout-for-woocommerce' );
		
		foreach ( $custom_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' && $value['type'] !== 'select' ) {
				$new_fields['billing']['fields'][$index] = array(
					'label' => isset( $value['label'] ) ? $value['label'] : '',
					'description' => '',
					'type' => isset( $value['type'] ) ? $value['type'] : 'text',
					'required' => isset( $value['required'] ) && $value['required'] === 'yes' ? true : false,
				);
			}
		}
	
		$new_fields = apply_filters( 'flexify_customer_user_meta_fields', $new_fields );
	
		return array_merge( $fields, $new_fields );
	}


	/**
	 * Add column billing address on user meta fields
	 * 
	 * @since 3.8.0
	 * @param array $address | Address column
	 * @param int $user_id | User ID
	 * @return array
	 */
	public static function user_column_billing_address( $address, $user_id ) {
		$custom_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $custom_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$address[$index] = get_user_meta( $user_id, 'billing_' . $index, true );
			}
		}
	
		return $address;
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
		if ( Init::get_setting('enable_manage_fields') === 'yes' && License::is_valid() ) {
			$step_fields = get_option('flexify_checkout_step_fields', array());
			$step_fields = maybe_unserialize( $step_fields );
	
			foreach ( $step_fields as $index => $value ) {
				$priority = isset( $value['priority'] ) ? $value['priority'] : '';

				self::set_field_priority( $fields, $index, $priority );
			}
		} else {
			self::set_field_priority( $fields, 'billing_email', 5 );
			self::set_field_priority( $fields, 'billing_first_name', 10 );
			self::set_field_priority( $fields, 'billing_last_name', 20 );
			self::set_field_priority( $fields, 'billing_phone', 40 );
			self::set_field_priority( $fields, 'billing_cellphone', 45 );
			self::set_field_priority( $fields, 'billing_persontype', 50 );
			self::set_field_priority( $fields, 'billing_cpf', 55 );
			self::set_field_priority( $fields, 'billing_rg', 60 );
			self::set_field_priority( $fields, 'billing_cnpj', 65 );
			self::set_field_priority( $fields, 'billing_ie', 70 );
			self::set_field_priority( $fields, 'billing_company', 75 );
			self::set_field_priority( $fields, 'billing_birthdate', 76 );
			self::set_field_priority( $fields, 'billing_sex', 77 );
			self::set_field_priority( $fields, 'billing_gender', 78 );
			self::set_field_priority( $fields, 'billing_country', 80 );
			self::set_field_priority( $fields, 'billing_postcode', 90 );
			self::set_field_priority( $fields, 'billing_address_1', 100 );
			self::set_field_priority( $fields, 'billing_number', 110 );
			self::set_field_priority( $fields, 'billing_neighborhood', 115 );
			self::set_field_priority( $fields, 'billing_address_2', 120 );
			self::set_field_priority( $fields, 'billing_city', 130 );
			self::set_field_priority( $fields, 'billing_state', 140 );
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
		if ( isset( $fields_group[$field_id] ) ) {
			$fields_group[$field_id]['priority'] = $priority;
		}
	}


	/**
	 * Override default fields
	 *
	 * @since 1.0.0
	 * @version 3.1.0
	 * @param array $fields | Checkout fields
	 * @return array
	 */
	public static function custom_override_default_fields( $fields ) {
		$fields_to_remove_placeholder = array(
			'street_number',
			'address_1',
			'address_2',
			'state',
			'country',
			'postcode',
			'first_name',
			'last_name',
		);

		$fields['address_2']['label'] = __( 'Apartamento, suíte, unidade etc.', 'flexify-checkout-for-woocommerce' );

		// Otherwise remove the placeholders.
		foreach ( $fields_to_remove_placeholder as $index ) {
			if ( isset( $fields[ $index ] ) ) {
				$fields[ $index ]['placeholder'] = '';
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
	 * @param array $data | Posted Data.
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
	 * Render inline errors for validate fields
	 *
	 * @since 1.0.0
	 * @version 3.8.0
	 * @param string $field | Checkout field
	 * @param string $key | Field name and ID
	 * @param array $args | Array of field parameters (type, country, label, description, placeholder, maxlenght, required, autocomplete, id, class, label_class, input_class, return, options, custom_attributes, validate, default, autofocus)
	 * @param string $value | Field value by default
	 * @param string $country Country
	 * @return string
	 */
	public static function render_inline_errors( $field = '', $key = '', $args = array(), $value = '', $country = '' ) {
		$called_inline = false;

		if ( defined('DOING_AJAX') && DOING_AJAX && ! empty( $key ) ) {
			$called_inline = true;
		}

		// If we are doing AJAX, get the parameters from POST request.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! $called_inline ) {
			$key = filter_input( INPUT_POST, 'key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$args = filter_input( INPUT_POST, 'args', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );
			$value = filter_input( INPUT_POST, 'value', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$country = filter_input( INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}

		$message = '';
		$message_type = 'error';
		$global_message = false;
		$custom = false;

		if ( (bool) $args['required'] || $args['class'] === 'required-field' ) {
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

		// is required field
		if ( (bool) $args['required'] && $value ) {
			if ( 'country' === $args['type'] && property_exists( WC()->countries, 'country_exists' ) && WC()->countries && ! WC()->countries->country_exists( $value ) ) {
				/* translators: ISO 3166-1 alpha-2 country code */
				$message = sprintf( __( "'%s' não é um código de país válido.", 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
				$custom  = true;
			}

			if ( 'billing_postcode' === $key && ! \WC_Validation::is_postcode( $value, $country ) ) {
				switch ( $country ) {
					case 'IE':
						/* translators: %1$s: field name, %2$s finder.eircode.ie URL */
						$message = sprintf( __( '%1$s não é válido. Você pode procurar o Eircode correto <a target="_blank" href="%2$s">aqui</a>.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ), 'https://finder.eircode.ie' );
						$custom  = true;
						break;
					default:
						/* translators: %s: field name */
						$message = sprintf( __( '%s não é um código postal válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
						$custom  = true;
						break;
				}
			}

			if ( 'phone' === $args['type'] && ! \WC_Validation::is_phone( $value ) || strpos( $key, 'billing_phone') !== false && ! \WC_Validation::is_phone( $value ) ) {
				$message = sprintf( __( '%s não é um número de telefone válido.', 'flexify-checkout-for-woocommerce' ), esc_html( $args['label'] ) );
				$custom  = true;
			}

			// add compatibility with multiple cpf fields
			if ( strpos( $key, 'billing_cpf' ) !== false && ! Helpers::validate_cpf( $value ) || ( isset( $args['class'] ) && 'validate-cpf-field' === $args['class'] && ! Helpers::validate_cpf( $value ) ) ) {
				$message = sprintf( __('O %s informado não é válido.', 'flexify-checkout-for-woocommerce'), esc_html( $args['label'] ) );
				$custom  = true;
			}

			// add compatibility with multiple cnpj fields
			if ( strpos( $key, 'billing_cnpj' ) !== false && ! Helpers::validate_cnpj( $value ) || ( isset( $args['class'] ) && 'validate-cnpj-field' === $args['class'] && ! Helpers::validate_cnpj( $value ) ) ) {
				$message = sprintf(__('O %s informado não é válido.', 'flexify-checkout-for-woocommerce'), esc_html( $args['label'] ) );
				$custom  = true;
			}

			if ( 'email' === $args['type'] && ! is_email( $value ) || ( isset( $args['class'] ) && 'validate-email-field' === $args['class'] && ! is_email( $value ) ) ) {
				$message = sprintf( __('%s não é um endereço de e-mail válido.', 'flexify-checkout-for-woocommerce'), esc_html( $args['label'] ) );
				$custom  = true;
			}

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
		 * @since 1.0.0
		 * @param string $message Message.
		 * @param string $field Field.
		 * @param string $key Key.
		 * @param array $args Arguments.
		 * @param string $value Value.
		 * @param string $country Country.
		 * @return string
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

		if ( defined('DOING_AJAX') && DOING_AJAX && in_array( $action, array( 'flexify_check_for_inline_error', 'flexify_check_for_inline_errors' ), true ) ) {
			$response = array(
				'message' => $message,
				'isCustom' => $custom,
				'globalMessage' => $global_message,
				'globalData' => array( 'data-flexify-error' => 1 ),
				'messageType' => $message_type,
				'input_value' => $value,
				'input_id' => $key,
			);

			if ( $called_inline ) {
				return $response;
			}

			wp_send_json_success( $response );

			exit;
		}

		// get step fields
		$fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
		$target_fields = array();

		foreach ( $fields as $index => $value ) {
			$target_fields[] = $index;
		}

		// filter for add check errors on custom conditions
		$target_fields = apply_filters( 'flexify_checkout_target_fields_for_check_errors', $target_fields );

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
		return Init::get_setting('flexify_checkout_theme') ? Init::get_setting('flexify_checkout_theme') : 'modern';
	}


	/**
	 * Update order review fragments
	 *
	 * @since 1.0.0
	 * @version 3.6.0
	 * @param array $fragments | Checkout fragments
	 * @return array
	 */
	public static function update_order_review_framents( $fragments ) {
		$fragments['.flexify-review-customer'] = Steps::render_customer_review();
		$fragments['.flexify-checkout-review-customer-contact'] = Steps::strings_to_replace( Init::get_setting('text_contact_customer_review'), Steps::get_review_customer_fragment() );
		$fragments['.flexify-checkout-review-shipping-address'] = Steps::strings_to_replace( Init::get_setting('text_shipping_customer_review'), Steps::get_review_customer_fragment() );
		$fragments['.flexify-checkout-review-shipping-method'] = Helpers::get_shipping_method();

		// Heading with cart item count.
		ob_start();

		wc_get_template('checkout/cart-heading.php');
		$fragments['.flexify-heading--order-review'] = ob_get_clean();

		$new_fragments = array(
			'total' => WC()->cart->get_total(),
			'shipping_row' => Steps::get_shipping_row(),
			'shipping_options' => Steps::get_shipping_options_fragment(),
        	'payment_options' => Steps::get_payment_options_fragment(),
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
	 * @param array $classes | Body classes
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
	 * Locate templates
	 *
	 * @since 1.0.0
	 * @version 3.5.0
	 * @param string $template Template.
	 * @param string $template_name Template Name.
	 * @param string $template_path Template Path.
	 * @return mixed|string
	 */
	public static function woocommerce_locate_template( $template, $template_name, $template_path ) {
		if ( ! is_flexify_template() ) {
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
				'woocommerce/', // Catches any file in the woocommerce/ override folder.
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

		// Get the Flexify theme
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
	 * Save billing fields data in custom session
	 * 
	 * @since 1.8.5
	 * @version 3.7.2
	 * @return void
	 */
	public function get_checkout_session_data_callback() {
		// Receive data from POST fields
		$fields_data = isset( $_POST['fields_data'] ) ? json_decode( stripslashes( $_POST['fields_data'] ), true ) : [];
		$flexify_checkout_session = array();
		
		foreach ( $fields_data as $field ) {
			// Add field and value to array if they exist and are not empty
			if ( isset( $field['field_id'] ) && isset( $field['value'] ) ) {
				$field_id = $field['field_id'];
				$field_value = sanitize_text_field( $field['value'] );
				
				// Remove 'billing_' prefix if present
				if ( 'billing_' === substr( $field_id, 0, 8 ) ) {
					$field_id = str_replace( 'billing_', '', $field_id );
				}
				
				// Save Field and Value in WooCommerce Session
				$flexify_checkout_session[$field_id] = $field_value;
			}
		}
		
		// Change session name from "flexify checkout" to "flexify_checkout_customer_fields" in 3.5.0 update
		WC()->session->set( 'flexify_checkout_customer_fields', $flexify_checkout_session );
	}


	/**
	 * Override empty cart fragment
	 *
	 * @since 1.0.0
	 * @param array $fragments | Checkout fragments
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
	 * @version 3.8.0
	 * @param int $order_id | Order ID.
	 * @param array $posted_data | Posted Data.
	 * @param WC_Order $order | Order class
	 * @return void
	 */
	public static function replace_phone_number_on_submit( $order_id, $posted_data, $order ) {
		$billing_phone_formated = filter_input( INPUT_POST, 'billing_phone_full_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

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
		$shortcode_header = Init::get_setting('shortcode_header');

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
		$shortcode_footer = Init::get_setting('shortcode_footer');

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
		$coupon = Init::get_setting('coupon_code_for_auto_apply') && License::is_valid() ? Init::get_setting('coupon_code_for_auto_apply') : '';

		if ( empty( $coupon ) || ! is_checkout() ) {
			return;
		}

		if ( WC()->cart->has_discount( $coupon ) ) {
			return;
		}

		if ( Init::get_setting('enable_auto_apply_coupon_code') === 'yes' && License::is_valid() ) {
			WC()->cart->add_discount( sanitize_text_field( $coupon ) );
		}
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
	 * Set default country on WooCommerce checkout
	 * 
	 * @since 3.2.0
	 * @return string
	 */
	public function get_default_checkout_country() {
		$fields = get_option('flexify_checkout_step_fields', array());
		$fields = maybe_unserialize( $fields );
		$country = isset( $fields['billing_country']['country'] ) ? $fields['billing_country']['country'] : '';

		return $country;
	}


	/**
	 * Get fields with input mask
	 * 
	 * @since 3.5.0
	 * @return array
	 */
	public static function get_fields_with_mask() {
		$fields = get_option('flexify_checkout_step_fields', array());
		$fields = maybe_unserialize( $fields );
		$input_masks = array();

		foreach ( $fields as $key => $value ) {
			if ( ! empty( $value['input_mask'] ) ) {
				$input_masks[$key] = $value['input_mask'];
			}
		}

		return $input_masks;
	}


	/**
	 * Remove password strenght
	 * 
	 * @since 3.5.0
	 * @return void
	 */
	public function flexify_checkout_disable_password_strenght() {
		wp_dequeue_script('wc-password-strength-meter');
		wp_deregister_script('wc-password-strength-meter');
	}


	/**
	 * Get product cart data from checkout session in AJAX callback
	 * 
	 * @since 3.5.0
	 * @version 3.8.0
	 * @return void
	 */
	public function get_product_cart_session_data_callback() {
		$cart_items = WC()->cart->get_cart();
		$items = array();
	
		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			$product_id = $product->get_id();
			$quantity = $cart_item['quantity'];
			
			if ( $product->is_type('variable') ) {
				$variation_id = $cart_item['variation_id'];
				$variation_data = wc_get_product( $variation_id )->get_variation_attributes();
				$items[] = array(
					'product_name' => $product->get_title(),
					'product_id' => $product_id,
					'variation_id' => $variation_id,
					'quantity' => $quantity,
					'variation_data' => $variation_data,
				);
			} else {
				$items[] = array(
					'product_name' => $product->get_title(),
					'product_id' => $product_id,
					'quantity' => $quantity,
				);
			}
		}
	
		// Get selected payment method
		$payment_method = WC()->session->get('chosen_payment_method');
		$payment_method_label = __( 'Nenhuma forma de pagamento selecionada', 'flexify-checkout-for-woocommerce' );

		if ( $payment_method ) {
			$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

			if ( isset( $available_gateways[$payment_method] ) ) {
				$payment_method_label = $available_gateways[$payment_method]->get_title();
			}
		}
	
		$session_data = array(
			'items' => $items,
			'shipping_method' => array(
				'id' => WC()->session->get('chosen_shipping_methods'),
				'label' => Helpers::get_selected_shipping_method_name(),
			),
			'payment_method' => array(
				'id' => $payment_method,
				'label' => $payment_method_label,
			),
			'checkout_entry_time' => WC()->session->get('checkout_entry_time'),
		);
	
		WC()->session->set( 'flexify_checkout_items_cart', $session_data );
	
		$response = array(
			'status' => 'success',
			'data' => $session_data,
		);
	
		wp_send_json( $response );
	}
	

	/**
	 * Set entry time on checkout session in AJAX callback
	 * 
	 * @since 3.5.0
	 * @return void
	 */
	public function set_checkout_entry_time_callback() {
		if ( isset( $_POST['entry_time'] ) && $_POST['entry_time'] === 'yes' ) {
			$current_time = current_time('mysql');
			$entry_time_formatted = date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $current_time ) );

			WC()->session->set('checkout_entry_time', $entry_time_formatted);

			$response = array(
				'status' => 'success',
				'data' => $entry_time_formatted,
			);
		} else {
			$response = array(
				'status' => 'error',
			);
		}

		wp_send_json( $response );
	}

	
	/**
	 * Replace checkout notices
	 * 
	 * @since 3.5.0
	 * @param string $template | Default template file path
	 * @param string $template_name | Template file slug
	 * @param array $args | Template arguments
	 * @param string $template_path | Template file name
	 * @param string $default_path Default path
	 * @return string The new Template file path
	 */
	public function flexify_checkout_notices( $template, $template_name, $args, $template_path, $default_path ) {
		if ( ! is_flexify_template() ) {
			return $template;
		}
		
		// replace error notice
		if ( $template_name === 'notices/error.php' ) {
			$template = FLEXIFY_CHECKOUT_TPL_PATH . 'notices/error.php';
		}

		// replace info notice
		if ( $template_name === 'notices/notice.php' ) {
			$template = FLEXIFY_CHECKOUT_TPL_PATH . 'notices/notice.php';
		}

		// replace success notice
		if ( $template_name === 'notices/success.php' ) {
			$template = FLEXIFY_CHECKOUT_TPL_PATH . 'notices/success.php';
		}

		return $template;
	}
}

if ( Init::get_setting('enable_flexify_checkout') === 'yes' ) {
	new Core();
}

if ( ! class_exists('MeuMouse\Flexify_Checkout\Core\Core') ) {
    class_alias( 'MeuMouse\Flexify_Checkout\Core', 'MeuMouse\Flexify_Checkout\Core\Core' );
}