<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Ajax;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Storage helpers for the checkout step fields manager.
 *
 * Fields live in the flexify_checkout_step_fields option, keyed by field id.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Fields_Store {

    /**
     * Option name holding the checkout step fields.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION_NAME = 'flexify_checkout_step_fields';

    /**
     * Field properties accepted from the manager UI.
     *
     * @since 6.0.0
     * @var string[]
     */
    const EDITABLE_PROPS = array( 'enabled', 'required', 'label', 'classes', 'label_classes', 'position', 'input_mask', 'priority', 'step', 'country' );


    /**
     * Read the checkout step fields, initializing defaults when empty.
     *
     * @since 6.0.0
     * @return array<string,array<string,mixed>>
     */
    public static function get_fields() {
        $fields = maybe_unserialize( get_option( self::OPTION_NAME, array() ) );

        if ( ! is_array( $fields ) || empty( $fields ) ) {
            ( new Admin_Options() )->set_checkout_step_fields();
            $fields = maybe_unserialize( get_option( self::OPTION_NAME, array() ) );
        }

        return is_array( $fields ) ? $fields : array();
    }


    /**
     * Merge a map of field updates into the stored fields.
     *
     * Mirrors the legacy checkout_step handling of the settings save AJAX.
     *
     * @since 6.0.0
     * @param array<string,array<string,mixed>> $incoming Map of field id => properties.
     * @return array<string,array<string,mixed>> Updated fields map.
     */
    public static function save_fields( $incoming ) {
        $fields = self::get_fields();

        foreach ( (array) $incoming as $field_id => $props ) {
            $field_id = sanitize_text_field( (string) $field_id );

            if ( '' === $field_id || ! isset( $fields[ $field_id ] ) || ! is_array( $props ) ) {
                continue;
            }

            foreach ( self::EDITABLE_PROPS as $prop ) {
                if ( ! array_key_exists( $prop, $props ) ) {
                    continue;
                }

                $value = $props[ $prop ];

                if ( in_array( $prop, array( 'enabled', 'required' ), true ) ) {
                    $value = ( 'yes' === $value ) ? 'yes' : 'no';
                } else {
                    $value = sanitize_text_field( (string) $value );
                }

                $fields[ $field_id ][ $prop ] = $value;
            }

            // Select options managed by the UI (custom selects only).
            if ( isset( $props['options'] ) && is_array( $props['options'] ) && 'billing_country' !== $field_id ) {
                $options = array();

                foreach ( $props['options'] as $option ) {
                    if ( ! is_array( $option ) || ! isset( $option['value'] ) ) {
                        continue;
                    }

                    $options[] = array(
                        'value' => sanitize_text_field( (string) $option['value'] ),
                        'text' => sanitize_text_field( (string) ( $option['text'] ?? '' ) ),
                    );
                }

                $fields[ $field_id ]['options'] = $options;
            }
        }

        update_option( self::OPTION_NAME, maybe_serialize( $fields ) );

        return $fields;
    }


    /**
     * Add a new custom field.
     *
     * @since 6.0.0
     * @param array<string,mixed> $data Field data from the manager UI.
     * @return array<string,mixed>|\WP_Error Updated fields map or error.
     */
    public static function add_field( $data ) {
        $field_id = isset( $data['id'] ) ? sanitize_key( (string) $data['id'] ) : '';

        if ( '' === $field_id || 0 !== strpos( $field_id, 'billing_' ) ) {
            return new \WP_Error( 'invalid_field_id', __( 'Informe um ID de campo válido com o prefixo billing_.', 'flexify-checkout-for-woocommerce' ) );
        }

        $fields = self::get_fields();

        if ( isset( $fields[ $field_id ] ) ) {
            return new \WP_Error( 'field_exists', __( 'Este nome e ID do campo já está em uso. Use um outro nome.', 'flexify-checkout-for-woocommerce' ) );
        }

        $options = null;

        if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
            $options = array();

            foreach ( $data['options'] as $option ) {
                if ( ! is_array( $option ) || ! isset( $option['value'] ) ) {
                    continue;
                }

                $options[] = array(
                    'value' => sanitize_text_field( (string) $option['value'] ),
                    'text' => sanitize_text_field( (string) ( $option['text'] ?? '' ) ),
                );
            }
        }

        $fields[ $field_id ] = array(
            'id' => $field_id,
            'type' => sanitize_text_field( (string) ( $data['type'] ?? 'text' ) ),
            'label' => sanitize_text_field( (string) ( $data['label'] ?? '' ) ),
            'position' => sanitize_text_field( (string) ( $data['position'] ?? 'full' ) ),
            'classes' => sanitize_text_field( (string) ( $data['classes'] ?? '' ) ),
            'label_classes' => sanitize_text_field( (string) ( $data['label_classes'] ?? '' ) ),
            'required' => ( 'yes' === ( $data['required'] ?? 'no' ) ) ? 'yes' : 'no',
            'priority' => (string) absint( $data['priority'] ?? ( count( $fields ) + 1 ) ),
            'source' => 'added',
            'enabled' => 'yes',
            'step' => in_array( (string) ( $data['step'] ?? '1' ), array( '1', '2' ), true ) ? (string) $data['step'] : '1',
            'options' => $options,
            'input_mask' => sanitize_text_field( (string) ( $data['input_mask'] ?? '' ) ),
        );

        update_option( self::OPTION_NAME, maybe_serialize( $fields ) );

        return $fields;
    }


    /**
     * Remove a field and scrub conditions referencing it.
     *
     * @since 6.0.0
     * @param string $field_id Field id to remove.
     * @return array<string,mixed>|\WP_Error Updated fields map or error.
     */
    public static function remove_field( $field_id ) {
        $field_id = sanitize_text_field( (string) $field_id );
        $fields = self::get_fields();

        if ( ! isset( $fields[ $field_id ] ) ) {
            return new \WP_Error( 'field_not_found', __( 'O campo informado não foi encontrado.', 'flexify-checkout-for-woocommerce' ) );
        }

        if ( isset( $fields[ $field_id ]['source'] ) && 'native' === $fields[ $field_id ]['source'] ) {
            return new \WP_Error( 'field_is_native', __( 'Campos nativos do WooCommerce não podem ser removidos, apenas desativados.', 'flexify-checkout-for-woocommerce' ) );
        }

        unset( $fields[ $field_id ] );

        update_option( self::OPTION_NAME, maybe_serialize( $fields ) );

        // Remove conditions that reference the removed field.
        Ajax::scrub_orphan_checkout_conditions( $field_id );

        return $fields;
    }
}
