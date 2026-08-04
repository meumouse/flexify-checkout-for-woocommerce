<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Admin\Settings\Fields_Store;
use MeuMouse\Flexify_Checkout\Validations\Utils;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Persist and validate the Swift (React) checkout extra fields over the Store API.
 *
 * The React checkout ships everything that is not a standard WooCommerce address
 * key (CPF, CNPJ, person type, RG, IE, birthdate, gender, street number,
 * neighborhood, …) inside the `flexify-checkout` checkout extension envelope
 * (see app/src/checkout-react/context/CheckoutContext.jsx placeOrder). The
 * classic checkout persists these through the `woocommerce_checkout_fields`
 * filter + Orders::save_custom_checkout_fields(); the Store API request never
 * runs those, so without this integration the values are silently dropped.
 *
 * This class:
 *  - registers the `flexify-checkout` endpoint data on the cart/checkout schemas
 *    (so saved values can ride back to a returning customer);
 *  - persists the posted extra fields to order + user meta, reusing the exact
 *    field keys the store already uses (billing_cpf, billing_number, …), so
 *    Orders::display_custom_fields_in_admin_order() / order e-mails work unchanged;
 *  - validates CPF / CNPJ / cellphone server-side reusing Validations\Utils,
 *    honoring the person-type and validation settings.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Store_API_Fields {

    /**
     * Store API extension namespace shared with the React checkout payload.
     *
     * @since 6.0.0
     * @var string
     */
    const NAMESPACE = 'flexify-checkout';

    /**
     * Standard WooCommerce address keys already persisted by the Store API from
     * the billing_address / shipping_address payload. Everything else in the
     * step-fields registry is a Flexify-managed extra field handled here.
     *
     * Mirrors STANDARD_ADDRESS_KEYS in app/src/checkout-react/lib/fields.js.
     *
     * @since 6.0.0
     * @var string[]
     */
    const STANDARD_ADDRESS_KEYS = array(
        'first_name', 'last_name', 'company', 'address_1', 'address_2',
        'city', 'state', 'postcode', 'country', 'email', 'phone',
    );

    /**
     * Construct function
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        // Register endpoint data once WooCommerce (and the Store API) is ready.
        add_action( 'woocommerce_init', array( $this, 'register_endpoint_data' ) );

        // Persist the posted extra fields to the order being created. Runs on
        // both cart→checkout and order-pay Store API requests.
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'persist_fields' ), 10, 2 );
    }


    /**
     * Register the flexify-checkout endpoint data on the cart + checkout schemas.
     *
     * Guarded on the Store API registration helper so the plugin stays compatible
     * with WooCommerce versions predating the Store API extensibility hooks.
     *
     * @since 6.0.0
     * @return void
     */
    public function register_endpoint_data() {
        if ( ! function_exists('woocommerce_store_api_register_endpoint_data') ) {
            return;
        }

        foreach ( array( 'checkout', 'cart' ) as $endpoint ) {
            woocommerce_store_api_register_endpoint_data( array(
                'endpoint'        => $endpoint,
                'namespace'       => self::NAMESPACE,
                'data_callback'   => array( $this, 'get_endpoint_data' ),
                'schema_callback' => array( $this, 'get_endpoint_schema' ),
                'schema_type'     => ARRAY_A,
            ) );
        }
    }


    /**
     * Endpoint data: the current customer's saved extra field values.
     *
     * Lets a returning (logged-in) customer see CPF / number / neighborhood, …
     * pre-filled without an extra round-trip. Limited to the managed key set.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public function get_endpoint_data() {
        return array( 'fields' => self::get_customer_fields() );
    }


    /**
     * Endpoint schema for the exposed data.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public function get_endpoint_schema() {
        return array(
            'fields' => array(
                'description' => __( 'Flexify Checkout managed extra field values.', 'flexify-checkout-for-woocommerce' ),
                'type'        => 'object',
                'context'     => array( 'view', 'edit' ),
                'readonly'    => true,
            ),
        );
    }


    /**
     * Persist the posted extra fields onto the order (and the user, when logged in).
     *
     * @since 6.0.0
     * @param \WC_Order $order Order being created.
     * @param \WP_REST_Request $request Store API request.
     * @return void
     * @throws RouteException When a CPF / CNPJ / cellphone fails validation.
     */
    public function persist_fields( $order, $request ) {
        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        $posted = $this->extract_posted_fields( $request );

        if ( empty( $posted ) ) {
            return;
        }

        $allowed = self::get_managed_field_keys();

        if ( empty( $allowed ) ) {
            return;
        }

        $values = array();

        foreach ( $posted as $key => $value ) {
            $key = (string) $key;

            if ( ! isset( $allowed[ $key ] ) ) {
                continue;
            }

            $values[ $key ] = $this->sanitize_value( $key, $value );
        }

        if ( empty( $values ) ) {
            return;
        }

        // Normalize the person type (WCBCF exposes F/J; we store 1/2) before use.
        if ( isset( $values['billing_persontype'] ) ) {
            $values['billing_persontype'] = self::normalize_person_type( $values['billing_persontype'] );
        }

        // Validate documents before writing anything.
        $this->validate_fields( $values );

        $user_id = $order->get_customer_id();

        foreach ( $values as $key => $value ) {
            $order->update_meta_data( $key, $value );

            // Mirror WooCommerce's own billing_company handling so the legal
            // entity name shows in the native billing column too.
            if ( 'billing_company' === $key && '' !== $value ) {
                $order->set_billing_company( $value );
            }

            // Pre-fill next time for logged-in customers.
            if ( $user_id > 0 ) {
                update_user_meta( $user_id, $key, $value );
            }
        }
    }


    /**
     * Read the posted extra fields from the checkout extension envelope.
     *
     * @since 6.0.0
     * @param \WP_REST_Request $request Store API request.
     * @return array<string,mixed>
     */
    private function extract_posted_fields( $request ) {
        $extensions = isset( $request['extensions'] ) ? $request['extensions'] : array();

        if ( ! is_array( $extensions ) || empty( $extensions[ self::NAMESPACE ]['fields'] ) ) {
            return array();
        }

        $fields = $extensions[ self::NAMESPACE ]['fields'];

        return is_array( $fields ) ? $fields : array();
    }


    /**
     * Sanitize a single field value.
     *
     * @since 6.0.0
     * @param string $key Field key.
     * @param mixed $value Raw value.
     * @return string
     */
    private function sanitize_value( $key, $value ) {
        if ( is_array( $value ) ) {
            $value = reset( $value );
        }

        return sanitize_text_field( (string) $value );
    }


    /**
     * Validate the Brazilian documents, throwing on the first hard failure.
     *
     * Only validates a document when its value is present and the matching
     * validation setting is on. Emptiness / requiredness is enforced by the
     * field-required checks the classic and React checkouts already run.
     *
     * @since 6.0.0
     * @param array<string,string> $values Sanitized field values.
     * @return void
     * @throws RouteException When a document is invalid.
     */
    private function validate_fields( $values ) {
        $person_type = isset( $values['billing_persontype'] ) ? (string) $values['billing_persontype'] : '';

        // CPF: validate for individuals (or when no person type is in play).
        if ( ! empty( $values['billing_cpf'] ) && '2' !== $person_type && $this->should_validate('cpf') ) {
            if ( ! Utils::validate_cpf( $values['billing_cpf'] ) ) {
                throw new RouteException(
                    'flexify_invalid_cpf',
                    __( 'The CPF provided is not valid.', 'flexify-checkout-for-woocommerce' ),
                    400
                );
            }
        }

        // CNPJ: validate for companies (or when no person type is in play).
        if ( ! empty( $values['billing_cnpj'] ) && '1' !== $person_type && $this->should_validate('cnpj') ) {
            if ( ! Utils::validate_cnpj( $values['billing_cnpj'] ) ) {
                throw new RouteException(
                    'flexify_invalid_cnpj',
                    __( 'The CNPJ provided is not valid.', 'flexify-checkout-for-woocommerce' ),
                    400
                );
            }
        }

        if ( ! empty( $values['billing_cellphone'] ) && ! Utils::is_valid_phone( $values['billing_cellphone'] ) ) {
            throw new RouteException(
                'flexify_invalid_cellphone',
                __( 'The cell phone number provided is not valid.', 'flexify-checkout-for-woocommerce' ),
                400
            );
        }
    }


    /**
     * Whether a given document validation is enabled.
     *
     * Defaults to on (mirrors WCBCF's defaults) unless the operator turned it off
     * through the native Brazilian fields settings.
     *
     * @since 6.0.0
     * @param string $doc Either 'cpf' or 'cnpj'.
     * @return bool
     */
    private function should_validate( $doc ) {
        $setting = Admin_Options::get_setting( 'brazilian_validate_' . $doc );

        // Unset defaults to enabled; only an explicit 'no' disables it.
        return 'no' !== $setting;
    }


    /**
     * Get the map of Flexify-managed extra field keys (enabled, non-address).
     *
     * @since 6.0.0
     * @return array<string,bool> Map of field key => true.
     */
    public static function get_managed_field_keys() {
        $fields = Fields_Store::get_fields();
        $keys = array();

        foreach ( $fields as $field_id => $field ) {
            $field_id = (string) $field_id;

            if ( ! is_array( $field ) || ( $field['enabled'] ?? 'no' ) !== 'yes' ) {
                continue;
            }

            if ( self::is_standard_address_key( $field_id ) ) {
                continue;
            }

            $keys[ $field_id ] = true;
        }

        return $keys;
    }


    /**
     * Whether a field id maps to a standard WooCommerce address key.
     *
     * @since 6.0.0
     * @param string $field_id Field id.
     * @return bool
     */
    private static function is_standard_address_key( $field_id ) {
        if ( 0 !== strpos( $field_id, 'billing_' ) && 0 !== strpos( $field_id, 'shipping_' ) ) {
            return false;
        }

        $key = substr( $field_id, strpos( $field_id, '_' ) + 1 );

        return in_array( $key, self::STANDARD_ADDRESS_KEYS, true );
    }


    /**
     * Read the current customer's saved values for the managed keys.
     *
     * @since 6.0.0
     * @return array<string,string>
     */
    public static function get_customer_fields() {
        $user_id = get_current_user_id();

        if ( $user_id <= 0 ) {
            return array();
        }

        $out = array();

        foreach ( array_keys( self::get_managed_field_keys() ) as $key ) {
            $value = get_user_meta( $user_id, $key, true );

            if ( '' !== $value && null !== $value ) {
                $out[ $key ] = (string) $value;
            }
        }

        return $out;
    }


    /**
     * Normalize a person type value to the canonical 1 (individual) / 2 (company).
     *
     * WCBCF stores 1/2 but exposes F/J through its REST layer; accept both so
     * imported / API-created data keeps working with the conditions engine.
     *
     * @since 6.0.0
     * @param string $value Raw person type.
     * @return string
     */
    public static function normalize_person_type( $value ) {
        $value = strtoupper( trim( (string) $value ) );

        if ( 'F' === $value ) {
            return '1';
        }

        if ( 'J' === $value ) {
            return '2';
        }

        return $value;
    }
}
