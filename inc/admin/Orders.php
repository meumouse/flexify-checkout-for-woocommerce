<?php

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Orders class
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Orders {

	/**
	 * Construct function
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'maybe_assign_guest_order_to_existing_customer' ) );

		if ( Admin_Options::get_setting('enable_manage_fields') === 'yes' && License::is_valid() ) {
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_custom_fields_in_admin_order' ), 10, 1 );
			add_filter( 'woocommerce_email_order_meta_fields', array( __CLASS__, 'add_custom_fields_to_order_emails' ), 10, 3 );
			add_filter( 'woocommerce_admin_billing_fields', array( $this, 'shop_order_billing_fields' ) );
			add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_custom_checkout_fields' ), 10, 2 );
		}

		// Change street number on create order
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'prepend_street_number_to_address_1' ), 10, 2 );

		// replace phone number for international
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'replace_phone_number_on_submit' ), 10, 3 );

		// save ship to different address input value
        add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'save_flexify_ship_different_address_meta' ), 10, 2 );
	}


	/**
	 * For the guest orders, check if there exists a user with matching email
	 * If it does then assign this order to the user
	 *
	 * @since 1.0.0
	 * @version 3.7.0
	 * @param int $order_id | Order ID
	 * @return void
	 */
	public static function maybe_assign_guest_order_to_existing_customer( $order_id ) {
		if ( Admin_Options::get_setting('enable_assign_guest_orders') !== 'yes' ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( empty( $order ) ) {
			return;
		}

		if ( 0 !== $order->get_user_id() ) {
			return;
		}

		$email = $order->get_billing_email();

		// Check if there is an existing user with the given email address.
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return;
		}

		// Assign the order to the existing user
		$order->set_customer_id( $user->ID );

		// save order
		$order->save();
	}


	/**
	 * Get customer fragment from order based on billing fields
	 *
	 * @since 3.9.8
	 * @param WC_Order $order | Order object
	 * @return array
	 */
	public static function get_order_customer_fragment( $order ) {
		if ( ! isset( $order ) || ! $order instanceof \WC_Order ) {
			return array();
		}
	
		// Check if the shipping address is different from the billing address
		$ship_different = get_post_meta( $order->get_id(), '_flexify_ship_different_address', true ) === 'yes';
	
		// Initialize fragment data array
		$fragment_data = array();
	
		// Retrieve all registered checkout fields
		$fields = Helpers::export_all_checkout_fields();
	
		// Iterate over the fields to populate the fragment data
		foreach ( $fields as $field_id => $field_data ) {
			$meta_value = '';
	
			// Process billing fields
			if ( strpos( $field_id, 'billing_' ) === 0 ) {
				$meta_value = $order->get_meta( $field_id, true );
	
				// Fallback to dynamically accessing billing methods
				if ( empty( $meta_value ) && method_exists( $order, "get_$field_id" ) ) {
					$meta_value = $order->{"get_$field_id"}();
				}
	
			// Process shipping fields if the shipping address is different
			} elseif ( strpos( $field_id, 'shipping_' ) === 0 && $ship_different ) {
				$meta_value = $order->get_meta( $field_id, true );
	
				// Fallback to dynamically accessing shipping methods
				if ( empty( $meta_value ) && method_exists( $order, "get_$field_id" ) ) {
					$meta_value = $order->{"get_$field_id"}();
				}
			}
	
			// Additional fallback: Check directly in the metadata array
			if ( empty( $meta_value ) ) {
				foreach ( $order->get_meta_data() as $meta_data ) {
					if ( isset( $meta_data->key ) && $meta_data->key === "_$field_id" ) {
						$meta_value = $meta_data->value;
						break;
					}
				}
			}
	
			// Save the value in the fragment data while preserving the prefix
			if ( ! empty( $meta_value ) ) {
				$fragment_data[ $field_id ] = $meta_value;
			}
		}
	
		/**
		 * Filter: Customize the order customer fragment data.
		 *
		 * @since 3.9.8
		 * @param array $fragment_data | Customer fragment data
		 * @param \WC_Order $order | Order object
		 */
		return apply_filters( 'flexify_checkout_order_customer_fragments', $fragment_data, $order );
	}


	/**
	 * Get shipping method names from an order, separated by commas
	 *
	 * @since 3.9.8
	 * @param WC_Order $order | Order object
	 * @return string Comma-separated shipping method names
	 */
	public static function get_order_shipping_methods( $order ) {
		if ( ! isset( $order ) ) {
			return '';
		}

		// get all shipping methods from order
		$shipping_methods = $order->get_shipping_methods();
		$shipping_labels = array();

		// iterate for each shipping method for get the names
		foreach ( $shipping_methods as $shipping_method ) {
			$label = $shipping_method->get_method_title();

			if ( ! empty( $label ) ) {
				$shipping_labels[] = $label;
			}
		}

		// return shipping method names comma separated
		return implode( ', ', $shipping_labels );
	}


	/**
	 * Display new checkout fields on admin order
	 * 
	 * @since 3.7.0
	 * @version 3.9.8
	 * @param object|array $order | Order objetct
	 * @return void
	 */
	public static function display_custom_fields_in_admin_order( $order ) {
		echo '<h3>' . esc_html__( 'Informações do cliente', 'flexify-checkout-for-woocommerce' ) . '</h3>';

		echo '<div class="flexify-checkout-order-fields"><p>';

		$new_fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );
	
		foreach ( $new_fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] !== 'native' ) {
				$field_value = get_post_meta( $order->get_id(), $index, true );

				// field type select
				if ( $value['type'] === 'select' && isset( $value['options'] ) && is_array( $value['options'] ) ) {
					$select_options = array();
					
					// get select options
					foreach ( $value['options'] as $option ) {
						if ( is_array( $option ) ) {
							$select_options[$option['value']] = $option['text'] ?? '';
						}
					}

					echo '<strong>' . $value['label'] . ':</strong> ' . $select_options[$field_value] . '<br>';
				}

				if ( ! empty( $field_value ) ) {
					if ( $value['type'] === 'select' ) {
						continue;
					}
					
					echo '<strong>' . $value['label'] . ':</strong> ' . $field_value . '<br>';
				}
			}
		}

		echo '<strong>' . esc_html__( 'Telefone', 'flexify-checkout-for-woocommerce' ) . ': </strong>' . esc_html( $order->get_billing_phone() ) . '<br>';
		echo '<strong>' . esc_html__( 'E-mail', 'flexify-checkout-for-woocommerce' ) . ': </strong>' . wp_kses_post( make_clickable( $order->get_billing_email() ) ) . '<br>';
		echo '</p></div>';
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
	public static function add_custom_fields_to_order_emails( $fields, $sent_to_admin, $order ) {
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
	 * Custom shop order billing fields
	 *
	 * @since 3.9.8
	 * @param array $data | Default order billing fields
	 * @return array Custom order billing fields
	 */
	public function shop_order_billing_fields( $data ) {
		$billing_data['phone'] = $data['phone'];
		$billing_data['phone']['show'] = false;

		$billing_data['email'] = $data['email'];
		$billing_data['email']['show'] = false;

		return apply_filters( 'flexify_checkout_admin_billing_fields', $billing_data );
	}


	/**
	 * Save new checkout fields on order
	 * 
	 * @since 3.7.0
     * @version 3.9.8
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
	 * Prepend street number to billing and shipping address_1 field when order is created.
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param WC_Order $order | Order object
	 * @param array $data | Posted Data
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
			 * @since 1.0.0
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
			 * @since 1.0.0
			 */
			$new_shipping_address = apply_filters( 'checkout_shipping_address_1_before_create_order', $new_shipping_address, $current_shipping_address, $shipping_street_no, $order );
			$order->set_shipping_address_1( $new_shipping_address );
		}
	}


	/**
	 * Replace phone number - use the phone number saved in the hidden field by intl-tel-input script.
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param int $order_id | Order ID
	 * @param array $posted_data | Posted Data
	 * @param WC_Order $order | Order class
	 * @return void
	 */
	public static function replace_phone_number_on_submit( $order_id, $posted_data, $order ) {
		$billing_phone_formated = filter_input( INPUT_POST, 'billing_phone_full_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $billing_phone_formated ) ) {
			return;
		}

		$order->set_billing_phone( $billing_phone_formated );

		// save order
		$order->save();
	}


	/**
     * Save the 'flexify_checkout_ship_different_address' session value as order meta
     *
     * @since 3.9.8
     * @param int $order_id | The order ID
     * @param array $posted_data | The posted checkout data
     */
    public static function save_flexify_ship_different_address_meta( $order_id, $posted_data ) {
        // Get the session value
        $ship_different_address = WC()->session->get('flexify_checkout_ship_different_address');

        // Save it as order meta if it exists
        if ( isset( $ship_different_address ) ) {
            update_post_meta( $order_id, '_flexify_ship_different_address', $ship_different_address );
        }
    }
}