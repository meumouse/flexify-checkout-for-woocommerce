<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Build the checkout config / rules / public settings payloads.
 *
 * Single source of truth shared by the React checkout bootstrap (localized
 * inline data) and the public headless REST endpoints, so the internal and
 * external consumers never drift. Shapes mirror the reference storefront
 * contracts (src/lib/types/checkout-config.ts and storefront-settings.ts).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Headless_Data {

    /**
     * Read the stored checkout step fields, normalized to an array.
     *
     * @since 6.0.0
     * @return array<string,array<string,mixed>>
     */
    public static function get_step_fields() {
        $fields = maybe_unserialize( get_option( 'flexify_checkout_step_fields', array() ) );

        return is_array( $fields ) ? $fields : array();
    }


    /**
     * Whether a given field is enabled and required.
     *
     * @since 6.0.0
     * @param array<string,array<string,mixed>> $fields Step fields map.
     * @param string $field_id Field id.
     * @return bool
     */
    private static function is_field_required( $fields, $field_id ) {
        return isset( $fields[ $field_id ] )
            && ( $fields[ $field_id ]['enabled'] ?? 'no' ) === 'yes'
            && ( $fields[ $field_id ]['required'] ?? 'no' ) === 'yes';
    }


    /**
     * Build the checkout config payload (gateways + field requirements).
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_checkout_config() {
        $fields = self::get_step_fields();
        $required = array();

        foreach ( $fields as $field_id => $field ) {
            if ( ( $field['enabled'] ?? 'no' ) === 'yes' && ( $field['required'] ?? 'no' ) === 'yes' ) {
                $required[] = (string) $field_id;
            }
        }

        $config = array(
            'gateways' => Gateway_Catalog::get_gateways(),
            'required_fields' => array_values( $required ),
            'cpf_required' => self::is_field_required( $fields, 'billing_cpf' ),
            'birthdate_required' => self::is_field_required( $fields, 'billing_birthdate' ),
            'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'BRL',
            'split_payment' => Admin_Options::get_setting('enable_payment_split') === 'yes',
        );

        /**
         * Filter the checkout config payload.
         *
         * @since 6.0.0
         * @param array $config Config payload.
         */
        return apply_filters( 'Flexify_Checkout/Headless/Checkout_Config', $config );
    }


    /**
     * Build the checkout rules payload (fields + steps for headless rendering).
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_checkout_rules() {
        $fields = self::get_step_fields();
        $normalized = array();

        foreach ( $fields as $field_id => $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            $normalized[] = array(
                'id' => (string) $field_id,
                'type' => (string) ( $field['type'] ?? 'text' ),
                'label' => (string) ( $field['label'] ?? '' ),
                'step' => (string) ( $field['step'] ?? '1' ),
                'position' => (string) ( $field['position'] ?? 'full' ),
                'priority' => (string) ( $field['priority'] ?? '0' ),
                'required' => ( $field['required'] ?? 'no' ) === 'yes',
                'enabled' => ( $field['enabled'] ?? 'no' ) === 'yes',
                'input_mask' => (string) ( $field['input_mask'] ?? '' ),
                'options' => isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array(),
            );
        }

        $rules = array(
            'fields' => $normalized,
            'steps' => array(
                array( 'index' => 1, 'label' => (string) Admin_Options::get_setting('text_check_step_1') ),
                array( 'index' => 2, 'label' => (string) Admin_Options::get_setting('text_check_step_2') ),
                array( 'index' => 3, 'label' => (string) Admin_Options::get_setting('text_check_step_3') ),
            ),
            'manage_fields_enabled' => Admin_Options::get_setting('enable_manage_fields') === 'yes',
            'shipping_to_different_address' => Admin_Options::get_setting('enable_shipping_to_different_address') === 'yes',
            'additional_notes' => Admin_Options::get_setting('enable_aditional_notes') === 'yes',
            'field_masks' => Admin_Options::get_setting('enable_field_masks') === 'yes',
        );

        /**
         * Filter the checkout rules payload.
         *
         * @since 6.0.0
         * @param array $rules Rules payload.
         */
        return apply_filters( 'Flexify_Checkout/Headless/Checkout_Rules', $rules );
    }


    /**
     * Whether WhatsApp login is available (toggle on + Joinotify present).
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_whatsapp_login_available() {
        return Admin_Options::get_setting('enable_whatsapp_login') === 'yes'
            && function_exists('joinotify_send_whatsapp_message_text');
    }


    /**
     * Whether the Google address search is available (toggle on + key set).
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_address_search_available() {
        return Admin_Options::get_setting('enable_google_address_search') === 'yes'
            && '' !== trim( (string) Admin_Options::get_setting('google_maps_api_key') );
    }


    /**
     * Build the public settings payload (operator-tunable, non-sensitive).
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_public_settings() {
        $fields = self::get_step_fields();

        $settings = array(
            'checkout' => array(
                'split_payment' => Admin_Options::get_setting('enable_payment_split') === 'yes',
                'cpf_required' => self::is_field_required( $fields, 'billing_cpf' ),
                'birthdate_required' => self::is_field_required( $fields, 'billing_birthdate' ),
                'pix_discount_percent' => 0,
                'max_installments' => 1,
                'interest_free_installments' => 0,
                'monthly_interest_rate' => 0,
            ),
            'whatsapp' => array(
                'enabled' => self::is_whatsapp_login_available(),
            ),
            'address_search' => array(
                'enabled' => self::is_address_search_available(),
            ),
        );

        /**
         * Filter the public settings payload.
         *
         * @since 6.0.0
         * @param array $settings Settings payload.
         */
        return apply_filters( 'Flexify_Checkout/Headless/Public_Settings', $settings );
    }
}
