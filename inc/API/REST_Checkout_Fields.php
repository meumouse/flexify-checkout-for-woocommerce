<?php

namespace MeuMouse\Flexify_Checkout\API;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Expose Flexify checkout extra fields in WooCommerce REST API.
 *
 * @since 5.5.0
 * @package MeuMouse\Flexify_Checkout\API
 * @author MeuMouse.com
 */
class REST_Checkout_Fields {

	/**
	 * Constructor.
	 *
	 * @since 5.5.0
	 * @return void
	 */
	public function __construct() {
		add_filter( 'woocommerce_rest_prepare_customer', array( $this, 'prepare_customer_response' ), 20, 3 );
		add_filter( 'woocommerce_rest_prepare_shop_order', array( $this, 'prepare_order_response' ), 20, 3 );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'prepare_order_response' ), 20, 3 );

		add_filter( 'woocommerce_rest_customer_schema', array( $this, 'extend_customer_schema' ), 20 );
		add_filter( 'woocommerce_rest_shop_order_schema', array( $this, 'extend_order_schema' ), 20 );
	}


	/**
	 * Extend customer response with checkout extra fields.
	 *
	 * @since 5.5.0
	 * @param \WP_REST_Response $response Response object.
	 * @param \WP_User $user User object.
	 * @return \WP_REST_Response
	 */
	public function prepare_customer_response( $response, $user ) {
		if ( ! $response instanceof \WP_REST_Response || ! $user instanceof \WP_User ) {
			return $response;
		}

		$data = $response->get_data();
		$data['billing'] = isset( $data['billing'] ) && is_array( $data['billing'] ) ? $data['billing'] : array();
		$data['shipping'] = isset( $data['shipping'] ) && is_array( $data['shipping'] ) ? $data['shipping'] : array();
		$field_ids = self::get_registered_field_ids();
		$generic_fields = array();

		foreach ( $field_ids as $field_id ) {
			$value = get_user_meta( $user->ID, $field_id, true );

			if ( '' === $value || null === $value ) {
				continue;
			}

			if ( 0 === strpos( $field_id, 'billing_' ) ) {
				$key = substr( $field_id, 8 );
				$data['billing'][ $key ] = $value;
				continue;
			}

			if ( 0 === strpos( $field_id, 'shipping_' ) ) {
				$key = substr( $field_id, 9 );
				$data['shipping'][ $key ] = $value;

				continue;
			}

			$generic_fields[ $field_id ] = $value;
		}

		$data['flexify_checkout'] = array(
			'fields' => $generic_fields,
		);

		$data = apply_filters( 'Flexify_Checkout/REST/Checkout_Fields/Customer_Response', $data, $user );
		$response->set_data( $data );

		return $response;
	}


	/**
	 * Extend order response with checkout extra fields.
	 *
	 * @since 5.5.0
	 * @param \WP_REST_Response $response Response object.
	 * @param \WC_Order $order Order object.
	 * @return \WP_REST_Response
	 */
	public function prepare_order_response( $response, $order ) {
		if ( ! $response instanceof \WP_REST_Response || ! is_a( $order, 'WC_Order' ) ) {
			return $response;
		}

		$data = $response->get_data();
		$data['billing'] = isset( $data['billing'] ) && is_array( $data['billing'] ) ? $data['billing'] : array();
		$data['shipping'] = isset( $data['shipping'] ) && is_array( $data['shipping'] ) ? $data['shipping'] : array();
		$field_ids = self::get_registered_field_ids();
		$generic_fields = array();

		foreach ( $field_ids as $field_id ) {
			$value = $order->get_meta( $field_id, true );

			if ( '' === $value || null === $value ) {
				continue;
			}

			if ( 0 === strpos( $field_id, 'billing_' ) ) {
				$key = substr( $field_id, 8 );
				$data['billing'][ $key ] = $value;
				continue;
			}

			if ( 0 === strpos( $field_id, 'shipping_' ) ) {
				$key = substr( $field_id, 9 );
				$data['shipping'][ $key ] = $value;
				continue;
			}

			$generic_fields[ $field_id ] = $value;
		}

		$data['flexify_checkout'] = array(
			'fields' => $generic_fields,
		);

		$data = apply_filters( 'Flexify_Checkout/REST/Checkout_Fields/Order_Response', $data, $order );
		$response->set_data( $data );

		return $response;
	}


	/**
	 * Extend customer schema.
	 *
	 * @since 5.5.0
	 * @param array $schema Endpoint schema.
	 * @return array
	 */
	public function extend_customer_schema( $schema ) {
		$fields = self::get_registered_field_ids();
		$schema = self::inject_fields_schema( $schema, $fields );

		return apply_filters( 'Flexify_Checkout/REST/Checkout_Fields/Customer_Schema', $schema, $fields );
	}


	/**
	 * Extend order schema.
	 *
	 * @since 5.5.0
	 * @param array $schema Endpoint schema.
	 * @return array
	 */
	public function extend_order_schema( $schema ) {
		$fields = self::get_registered_field_ids();
		$schema = self::inject_fields_schema( $schema, $fields );

		return apply_filters( 'Flexify_Checkout/REST/Checkout_Fields/Order_Schema', $schema, $fields );
	}


	/**
	 * Add flexify fields schema and augment billing/shipping object schemas.
	 *
	 * @since 5.5.0
	 * @param array $schema Endpoint schema.
	 * @param array $field_ids Field ids.
	 * @return array
	 */
	private static function inject_fields_schema( $schema, $field_ids ) {
		if ( ! isset( $schema['properties'] ) || ! is_array( $schema['properties'] ) ) {
			$schema['properties'] = array();
		}

		if ( ! isset( $schema['properties']['billing']['properties'] ) || ! is_array( $schema['properties']['billing']['properties'] ) ) {
			$schema['properties']['billing']['properties'] = array();
		}

		if ( ! isset( $schema['properties']['shipping']['properties'] ) || ! is_array( $schema['properties']['shipping']['properties'] ) ) {
			$schema['properties']['shipping']['properties'] = array();
		}

		foreach ( $field_ids as $field_id ) {
			$field_schema = array(
				'description' => sprintf( __( 'Campo adicional do checkout (%s).', 'flexify-checkout-for-woocommerce' ), $field_id ),
				'type' => array( 'string', 'null' ),
				'context' => array( 'view', 'edit' ),
			);

			if ( 0 === strpos( $field_id, 'billing_' ) ) {
				$key = substr( $field_id, 8 );
				$schema['properties']['billing']['properties'][ $key ] = $field_schema;
				continue;
			}

			if ( 0 === strpos( $field_id, 'shipping_' ) ) {
				$key = substr( $field_id, 9 );
				$schema['properties']['shipping']['properties'][ $key ] = $field_schema;
				continue;
			}
		}

		$schema['properties']['flexify_checkout'] = array(
			'description' => __( 'Namespace do Flexify Checkout para campos adicionais.', 'flexify-checkout-for-woocommerce' ),
			'type' => 'object',
			'context' => array( 'view', 'edit' ),
			'readonly' => true,
			'properties' => array(
				'fields' => array(
					'description' => __( 'Coleção de campos adicionais genéricos.', 'flexify-checkout-for-woocommerce' ),
					'type' => 'object',
					'context' => array( 'view', 'edit' ),
					'additionalProperties' => array(
						'type' => array( 'string', 'number', 'boolean', 'array', 'object', 'null' ),
					),
				),
			),
		);

		return $schema;
	}


	/**
	 * Return registered field ids from flexify step fields.
	 *
	 * @since 5.5.0
	 * @return array
	 */
	private static function get_registered_field_ids() {
		$fields = maybe_unserialize( get_option( 'flexify_checkout_step_fields', array() ) );

		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return array();
		}

		$field_ids = array_keys( $fields );

		return apply_filters( 'Flexify_Checkout/REST/Checkout_Fields/Registered_Field_Ids', $field_ids, $fields );
	}
}