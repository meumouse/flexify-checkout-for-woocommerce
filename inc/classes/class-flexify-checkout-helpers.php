<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Useful helper functions
 *
 * @since 1.0.0
 * @version 3.6.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Helpers {

	/**
	 * Get details fields for first step checkout
	 *
	 * @since 1.0.0
	 * @param object $checkout
	 * @return array
	 */
	public static function get_details_fields( $checkout ) {
		$all_fields = $checkout->checkout_fields['billing'];
		$allowed = self::get_allowed_details_fields();

		return array_intersect_key( $all_fields, array_flip( $allowed ) );
	}

	
	/**
	 * Get billing fields used at checkout
	 *
	 * @since 1.0.0
	 * @version 3.1.0
	 * @return array
	 */
	public static function get_allowed_details_fields() {
		$fields = array();
		
		if ( Flexify_Checkout_Init::get_setting('enable_manage_fields') === 'yes' && Flexify_Checkout_Init::license_valid() ) {
			$get_field_options = get_option('flexify_checkout_step_fields', array());
			$get_field_options = maybe_unserialize( $get_field_options );
	
			foreach ( $get_field_options as $key => $value ) {
				if ( isset( $value['step'] ) && $value['step'] === '1' ) {
					$fields[] = $key;
				}
			}
		} else {
			$fields = array( 
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_phone',
				'billing_cellphone',
				'billing_email',
				'billing_persontype',
				'billing_cpf',
				'billing_rg',
				'billing_cnpj',
				'billing_ie',
				'billing_birthdate',
				'billing_sex',
			);
		}
		
		return apply_filters( 'flexify_checkout_details_fields', $fields );
	}


	/**
	 * Get shipping fields
	 *
	 * @since 1.0.0
	 * @param object $checkout
	 * @return array
	 */
	public static function get_shipping_fields( $checkout ) {
		$all_fields = $checkout->checkout_fields['shipping'];
		$allowed = array(
			'shipping_phone',
			'shipping_email',
		);

		// Shipping fields
		return apply_filters( 'flexify_shipping_fields', array_diff_key( $all_fields, array_flip( $allowed ) ), $checkout );
	}
	

	/**
	 * Use autocomplete address with Google Maps API
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function use_autocomplete() {
		if ( Flexify_Checkout_Init::get_setting('use_autocomplete') === 'yes' ) {
			return Flexify_Checkout_Init::get_setting('use_autocomplete');
		}
	}


	/**
	 * Get billing fields
	 *
	 * @since 1.0.0
	 * @param object $checkout
	 * @return array
	 */
	public static function get_billing_fields( $checkout ) {
		$all_fields = $checkout->checkout_fields['billing'];
		$allowed = self::get_allowed_details_fields();
		$fields = array_diff_key( $all_fields, array_flip( $allowed ) );

		return $fields;
	}


	/**
	 * Check if the checkout has any pre-populated fields.
	 *
	 * @since 1.0.0
	 * @param string $type Type.
	 * @return bool
	 */
	public static function has_prepopulated_fields( $type ) {
		$has_prepopulated_fields = false;
		$checkout = WC_Checkout::instance();
		$address_1 = $checkout->get_value( $type . '_address_1' );
		$address_2 = $checkout->get_value( $type . '_address_2' );

		if ( ! empty( $address_1 ) || ! empty( $address_2 ) ) {
			$has_prepopulated_fields = true;
		}

		/**
		 * Filter whether Flexify has prepoulated fields.
		 *
		 * @since 1.0.0
		 * @param bool $has_prepopulated_fields | Had prepopulated fields.
		 * @param string $type
		 * @return bool
		 */
		return apply_filters( 'flexify_has_prepopulated_fields', $has_prepopulated_fields, $type );
	}


	/**
	 * Render address panel
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_address_panel() {
		wc_get_template( 'flexify/form-address.php', array( 'checkout' => WC_Checkout::instance() ) );
	}


	/**
	 * Render Details Panel.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_details_panel() {
		wc_get_template( 'flexify/form-details.php', array( 'checkout' => WC_Checkout::instance() ) );
	}


	/**
	 * Get logo image
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_logo_image() {
		$logo_image = Flexify_Checkout_Init::get_setting('search_image_header_checkout');

		if ( ! empty( $logo_image ) ) {
			return $logo_image;
		}
	}


	/**
	 * Get logo image
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_logo_width() {
		$width = intval( Flexify_Checkout_Init::get_setting('header_width_image_checkout') );

		if ( ! $width ) {
			$width = self::is_modern_theme() ? 200 : 40; // Default.
		}

		return $width;
	}


	/**
	 * Get checkout header text
	 *
	 * @since 1.00.
	 * @return string
	 */
	public static function get_header_text() {
		$use_image = 'image' === Flexify_Checkout_Init::get_setting('checkout_header_type');

		if ( $use_image ) {
			return false;
		}

		$header_text = Flexify_Checkout_Init::get_setting('text_brand_checkout_header');

		return $header_text;
	}


	/**
	 * Convert hex to rgba.
	 *
	 * @param string $color Colour.
	 * @param bool $opacity Opacity.
	 *
	 * @return string
	 */
	public static function hex2rgba( $color, $opacity = false ) {
		$default = 'rgb(0,0,0)';

		// Return default if no color provided.
		if ( empty( $color ) ) {
			return $default;
		}

		// Sanitize $color if "#" is provided.
		if ( '#' === $color[0] ) {
			$color = substr( $color, 1 );
		}

		// Check if color has 6 or 3 characters and get values.
		if ( strlen( $color ) == 6 ) {
			$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) == 3 ) {
			$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return $default;
		}

		// Convert hexadec to rgb.
		$rgb = array_map( 'hexdec', $hex );

		// Check if opacity is set(rgba or rgb).
		if ( $opacity ) {
			if ( abs( $opacity ) > 1 ) {
				$opacity = 1.0;
			}

			$output = 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';
		} else {
			$output = 'rgb(' . implode( ',', $rgb ) . ')';
		}

		// Return rgb(a) color string.
		return $output;
	}


	/**
	 * Remove Class Filter.
	 *
	 * Removes action when Object is Anonymous.
	 *
	 * @since 1.0.0
	 * @param string $tag
	 * @param string $class_name
	 * @param string $method_name
	 * @param int    $priority
	 * @return bool.
	 */
	public static function remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $tag ] ) ) {
			return false;
		}

		if ( ! is_object( $wp_filter[ $tag ] ) || ! isset( $wp_filter[ $tag ]->callbacks ) ) {
			return false;
		}

		$filter_object = $wp_filter[ $tag ];
		$callbacks = &$wp_filter[ $tag ]->callbacks;
		$callbacks_priority = isset( $callbacks[ $priority ] ) ? (array) $callbacks[ $priority ] : array();

		if ( ! empty( $callbacks_priority ) ) {
			foreach ( $callbacks_priority as $filter ) {
				if ( ! isset( $filter['function'] ) || ! is_array( $filter['function'] ) ) {
					continue;
				}

				if ( ! is_object( $filter['function'][0] ) ) {
					continue;
				}

				if ( $filter['function'][1] !== $method_name ) {
					continue;
				}

				if ( get_class( $filter['function'][0] ) === $class_name ) {
					if ( ! isset( $filter_object ) ) {
						return false;
					}

					$filter_object->remove_filter( $tag, $filter['function'], $priority );

					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Is modern theme
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_modern_theme() {
		return Flexify_Checkout_Init::get_setting('flexify_checkout_theme') === 'modern';
	}


	/**
	 * Get shop page URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_shop_page_url() {
		$shop_page_id = wc_get_page_id( 'shop' );

		if ( -1 === $shop_page_id ) {
			/**
			 * Shop Page URL.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'flexify_checkout_shop_page_url', site_url() );
		}

		/**
		 * Shop Page URL.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'flexify_checkout_shop_page_url', get_permalink( $shop_page_id ) );
	}


	/**
	 * Is coupon enabled
	 * 
	 * @since 1.0.0
	 * @return bool
	 */
	public static function is_coupon_enabled() {
		$hide_coupon = Flexify_Checkout_Init::get_setting('enable_hide_coupon_code_field') ? Flexify_Checkout_Init::get_setting('enable_hide_coupon_code_field') : 'no';

		return apply_filters( 'flexify_checkout_is_coupon_enabled', wc_coupons_enabled() && 'yes' !== $hide_coupon );
	}


	/**
	 * Get Order Pay button text
	 *
	 * @since 1.0.0
	 * @param WC_Order $order
	 * @return string
	 */
	public static function get_order_pay_btn_text( $order ) {
		return esc_html__( 'Pagar pelo pedido', 'flexify-checkout-for-woocommerce' ) . ' - ' . wc_price( $order->get_total() );
	}


	/**
	 * Get new select fields for init Select2 on frontend
	 * 
	 * @since 3.2.0
	 * @return array
	 */
	public static function get_new_select_fields() {
		$fields = get_option('flexify_checkout_step_fields', array());
		$fields = maybe_unserialize( $fields );
		$selects = array();

		foreach ( $fields as $index => $value ) {
			if ( isset( $value['source'] ) && $value['source'] === 'added' && isset( $value['type'] ) && $value['type'] === 'select' ) {
				$selects[] = $index;
			}
		}

		return $selects;
	}


	/**
	 * Checks if the CPF is valid
	 *
	 * @since 3.2.0
	 * @version 3.5.0
	 * @param string $cpf | CPF to validate
	 * @return bool
	 */
	public static function validate_cpf( $cpf ) {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		if ( 11 !== strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
			return false;
		}

		$digit = substr( $cpf, 0, 9 );

		for ( $j = 10; $j <= 11; $j++ ) {
			$sum = 0;

			for ( $i = 0; $i < $j - 1; $i++ ) {
				$sum += ( $j - $i ) * intval( $digit[ $i ] );
			}

			$summod11 = $sum % 11;
			$digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
		}

		return intval( $digit[9] ) === intval( $cpf[9] ) && intval( $digit[10] ) === intval( $cpf[10] );
	}


	/**
	 * Checks if the CNPJ is valid
	 *
	 * @since 3.2.0
	 * @version 3.5.0
	 * @param string $cnpj | CNPJ to validate
	 * @return bool
	 */
	public static function validate_cnpj( $cnpj ) {
		$cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );

		if ( 14 !== strlen( $cnpj ) || 0 === intval( substr( $cnpj, -4 ) ) ) {
			return false;
		}

		for ( $t = 11; $t < 13; ) {
			for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c--, ( $p < 9 ) ? $p++ : $p = 2 ) {
				$d += $cnpj[ $c ] * $p;
			}

			$d = ( ( 10 * $d ) % 11 ) % 10;

			if ( intval( $cnpj[ ++$t ] ) !== $d ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Get all checkout fields available
	 * 
	 * @since 3.5.0
	 * @version 3.6.0
	 * @return array
	 */
	public static function get_checkout_fields_on_admin() {
		WC()->session = new WC_Session_Handler;
		WC()->customer = new WC_Customer;

		return WC()->checkout->get_checkout_fields();
	}


	/**
	 * Export array fields id
	 * 
	 * @since 3.5.0
	 * @return array
	 */
	public static function export_all_checkout_fields() {
		$get_fields = WC()->checkout->get_checkout_fields();
		$fields = array();

		foreach ( $get_fields['billing'] as $field_id => $value ) {
			$fields[$field_id] = $value;
		}
		
		return apply_filters( 'flexify_checkout_export_checkout_fields_id', $fields );
	}


	/**
	 * Get checkout input values and parcial name for customer review fragments
	 * 
	 * @since 3.6.0
	 * @return array
	 */
	public static function get_placeholder_input_values() {
		$fields = array();

		foreach ( self::get_checkout_fields_on_admin()['billing'] as $field_id => $value ) {
			$placeholder_id = ( strpos( $field_id, 'billing_' ) === 0 ) ? substr( $field_id, 8 ) : $field_id;

			$fields[$field_id] = array(
				'placeholder_id' => $placeholder_id,
				'placeholder_html' => sprintf( esc_html( '{{ %s }}', 'flexify-checkout-for-woocommerce' ), $placeholder_id ),
				'description' => sprintf( esc_html( 'Para recuperar o valor de %s', 'flexify-checkout-for-woocommerce' ), $value['label'] ),
			);
		}

		return apply_filters( 'flexify_checkout_customer_review_fields_placeholder', $fields );
	}


	/**
	 * Get selected shipping method
	 * 
	 * @since 3.6.0
	 * @return string
	 */
	public static function get_shipping_method() {
		$current_shipping_method = WC()->session->get('chosen_shipping_methods');
		$shipping_method_label = __('Nenhuma forma de entrega selecionada', 'flexify-checkout-for-woocommerce');

		if ( ! empty( $current_shipping_method ) ) {
			$packages = WC()->shipping()->get_packages();

			if ( ! empty( $packages ) && is_array( $packages ) ) {
				$package = $packages[0];
				$available_methods = $package['rates'];
				
				foreach ( $available_methods as $key => $method ) {
					if ( $current_shipping_method[0] === $method->id ) {
						$shipping_method_label = $method->label;
					}
				}
			}
		}

		return $shipping_method_label;
	}
}