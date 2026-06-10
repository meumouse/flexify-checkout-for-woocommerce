<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Storage helpers for the checkout conditions manager.
 *
 * Conditions live in the flexify_checkout_conditions option as a list of
 * associative arrays (see build_payload for the accepted properties).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Conditions_Store {

    /**
     * Option name holding the conditions.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION_NAME = 'flexify_checkout_conditions';

    /**
     * Scalar condition properties.
     *
     * @since 6.0.0
     * @var string[]
     */
    const SCALAR_PROPS = array( 'type_rule', 'component', 'component_field', 'verification_condition', 'verification_condition_field', 'condition', 'condition_value', 'payment_method', 'shipping_method', 'filter_user', 'specific_role', 'product_filter' );

    /**
     * List condition properties (arrays of ids).
     *
     * @since 6.0.0
     * @var string[]
     */
    const LIST_PROPS = array( 'specific_user', 'specific_products', 'specific_categories', 'specific_attributes' );


    /**
     * Read the stored conditions.
     *
     * @since 6.0.0
     * @return array<int|string,array<string,mixed>>
     */
    public static function get_conditions() {
        $conditions = get_option( self::OPTION_NAME, array() );

        return is_array( $conditions ) ? $conditions : array();
    }


    /**
     * Build the client payload: conditions with their indexes and summaries.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_conditions_for_client() {
        $items = array();

        foreach ( self::get_conditions() as $index => $condition ) {
            if ( ! is_array( $condition ) ) {
                continue;
            }

            $items[] = array(
                'index' => $index,
                'condition' => $condition,
                'summary' => self::summarize( $condition ),
                'selected_items' => self::resolve_selected_items( $condition ),
            );
        }

        return $items;
    }


    /**
     * Sanitize an incoming condition payload.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw condition data.
     * @return array<string,mixed>
     */
    public static function sanitize_payload( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();
        $condition = array();

        foreach ( self::SCALAR_PROPS as $prop ) {
            if ( array_key_exists( $prop, $incoming ) && null !== $incoming[ $prop ] ) {
                $condition[ $prop ] = sanitize_text_field( (string) $incoming[ $prop ] );
            }
        }

        foreach ( self::LIST_PROPS as $prop ) {
            if ( ! array_key_exists( $prop, $incoming ) || null === $incoming[ $prop ] ) {
                continue;
            }

            $list = is_array( $incoming[ $prop ] ) ? $incoming[ $prop ] : explode( ',', (string) $incoming[ $prop ] );
            $condition[ $prop ] = array_values( array_filter( array_map( 'absint', $list ) ) );
        }

        return $condition;
    }


    /**
     * Append a new condition.
     *
     * @since 6.0.0
     * @param array<string,mixed> $condition Sanitized condition.
     * @return bool
     */
    public static function add_condition( $condition ) {
        $conditions = self::get_conditions();
        $conditions[] = $condition;

        return update_option( self::OPTION_NAME, $conditions );
    }


    /**
     * Update a condition by its index.
     *
     * @since 6.0.0
     * @param int|string $index Condition index.
     * @param array<string,mixed> $condition Sanitized condition.
     * @return bool
     */
    public static function update_condition( $index, $condition ) {
        $conditions = self::get_conditions();

        if ( ! isset( $conditions[ $index ] ) ) {
            return false;
        }

        $is_equal = $conditions[ $index ] === $condition;
        $conditions[ $index ] = $condition;

        return update_option( self::OPTION_NAME, $conditions ) || $is_equal;
    }


    /**
     * Remove a condition by its index.
     *
     * @since 6.0.0
     * @param int|string $index Condition index.
     * @return bool
     */
    public static function remove_condition( $index ) {
        $conditions = self::get_conditions();

        if ( ! isset( $conditions[ $index ] ) ) {
            return false;
        }

        unset( $conditions[ $index ] );

        return update_option( self::OPTION_NAME, $conditions );
    }


    /**
     * Build the human-readable summary lines for a condition.
     *
     * Mirrors the legacy Ajax::build_condition_summary output.
     *
     * @since 6.0.0
     * @param array<string,mixed> $condition Condition data.
     * @return array<string,string>
     */
    public static function summarize( $condition ) {
        $get_fields = Helpers::get_checkout_fields_on_admin();
        $component_type_label = '';

        if ( isset( $condition['component'] ) && 'field' === $condition['component'] ) {
            $field_id = isset( $condition['component_field'] ) ? $condition['component_field'] : '';
            $field_label = isset( $get_fields['billing'][ $field_id ]['label'] ) ? $get_fields['billing'][ $field_id ]['label'] : $field_id;
            $component_type_label = sprintf( esc_html__( 'Campo: %s', 'flexify-checkout-for-woocommerce' ), $field_label );
        } elseif ( isset( $condition['component'] ) && 'shipping' === $condition['component'] ) {
            $shipping_id = isset( $condition['shipping_method'] ) ? $condition['shipping_method'] : '';
            $shipping_methods = function_exists('WC') && WC()->shipping ? WC()->shipping->get_shipping_methods() : array();
            $shipping_title = isset( $shipping_methods[ $shipping_id ] ) ? $shipping_methods[ $shipping_id ]->method_title : $shipping_id;
            $component_type_label = sprintf( esc_html__( 'Forma de entrega: %s', 'flexify-checkout-for-woocommerce' ), $shipping_title );
        } elseif ( isset( $condition['component'] ) && 'payment' === $condition['component'] ) {
            $payment_id = isset( $condition['payment_method'] ) ? $condition['payment_method'] : '';
            $payment_methods = function_exists('WC') && WC()->payment_gateways ? WC()->payment_gateways->payment_gateways() : array();
            $payment_title = isset( $payment_methods[ $payment_id ] ) ? $payment_methods[ $payment_id ]->method_title : $payment_id;
            $component_type_label = sprintf( esc_html__( 'Forma de pagamento: %s', 'flexify-checkout-for-woocommerce' ), $payment_title );
        }

        $component_verification_label = '';

        if ( isset( $condition['verification_condition'] ) && 'field' === $condition['verification_condition'] ) {
            $field_id = isset( $condition['verification_condition_field'] ) ? $condition['verification_condition_field'] : '';
            $field_label = isset( $get_fields['billing'][ $field_id ]['label'] ) ? $get_fields['billing'][ $field_id ]['label'] : $field_id;
            $component_verification_label = sprintf( esc_html__( 'Campo %s', 'flexify-checkout-for-woocommerce' ), $field_label );
        } elseif ( isset( $condition['verification_condition'] ) && 'qtd_cart_total' === $condition['verification_condition'] ) {
            $component_verification_label = esc_html__( 'Quantidade total do carrinho', 'flexify-checkout-for-woocommerce' );
        } elseif ( isset( $condition['verification_condition'] ) && 'cart_total_value' === $condition['verification_condition'] ) {
            $component_verification_label = esc_html__( 'Valor total do carrinho', 'flexify-checkout-for-woocommerce' );
        }

        $condition_type = array(
            'show' => esc_html__( 'Mostrar', 'flexify-checkout-for-woocommerce' ),
            'hide' => esc_html__( 'Ocultar', 'flexify-checkout-for-woocommerce' ),
        );

        $condition_labels = self::get_condition_labels();
        $condition_value = isset( $condition['condition_value'] ) ? $condition['condition_value'] : '';
        $condition_key = isset( $condition['condition'] ) ? $condition['condition'] : '';
        $condition_label = isset( $condition_labels[ $condition_key ] ) ? mb_strtolower( $condition_labels[ $condition_key ] ) : '';
        $type_key = isset( $condition['type_rule'] ) ? $condition['type_rule'] : '';
        $type_label = isset( $condition_type[ $type_key ] ) ? $condition_type[ $type_key ] : '';

        return array(
            'line_1' => sprintf( esc_html__( 'Condição: %s %s', 'flexify-checkout-for-woocommerce' ), $type_label, $component_type_label ),
            'line_2' => sprintf( esc_html__( 'Se: %s %s %s', 'flexify-checkout-for-woocommerce' ), $component_verification_label, $condition_label, $condition_value ),
        );
    }


    /**
     * Resolve id lists into labeled items for the editor UI.
     *
     * @since 6.0.0
     * @param array<string,mixed> $condition Condition data.
     * @return array<string,array<int,array<string,mixed>>>
     */
    public static function resolve_selected_items( $condition ) {
        $selected_items = array(
            'specific_products' => array(),
            'specific_categories' => array(),
            'specific_attributes' => array(),
            'specific_users' => array(),
        );

        $product_ids = self::parse_list( $condition['specific_products'] ?? array() );
        $category_ids = self::parse_list( $condition['specific_categories'] ?? array() );
        $attribute_ids = self::parse_list( $condition['specific_attributes'] ?? array() );
        $user_ids = self::parse_list( $condition['specific_user'] ?? array() );

        if ( ! empty( $product_ids ) ) {
            $products = get_posts( array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'post__in' => $product_ids,
                'orderby' => 'post__in',
            ) );

            foreach ( $products as $product ) {
                $selected_items['specific_products'][] = array(
                    'id' => (int) $product->ID,
                    'label' => $product->post_title,
                );
            }
        }

        foreach ( $category_ids as $term_id ) {
            $term = get_term( $term_id, 'product_cat' );

            if ( $term && ! is_wp_error( $term ) ) {
                $selected_items['specific_categories'][] = array(
                    'id' => (int) $term->term_id,
                    'label' => $term->name,
                );
            }
        }

        foreach ( $attribute_ids as $term_id ) {
            $term = get_term( $term_id );

            if ( $term && ! is_wp_error( $term ) ) {
                $selected_items['specific_attributes'][] = array(
                    'id' => (int) $term->term_id,
                    'label' => $term->name,
                );
            }
        }

        if ( ! empty( $user_ids ) ) {
            $users = get_users( array(
                'include' => $user_ids,
                'orderby' => 'include',
            ) );

            foreach ( $users as $user ) {
                $selected_items['specific_users'][] = array(
                    'id' => (int) $user->ID,
                    'label' => $user->display_name,
                );
            }
        }

        return $selected_items;
    }


    /**
     * Condition operator labels.
     *
     * @since 6.0.0
     * @return array<string,string>
     */
    public static function get_condition_labels() {
        return array(
            'is' => esc_html__( 'É', 'flexify-checkout-for-woocommerce' ),
            'is_not' => esc_html__( 'Não é', 'flexify-checkout-for-woocommerce' ),
            'empty' => esc_html__( 'Vazio', 'flexify-checkout-for-woocommerce' ),
            'not_empty' => esc_html__( 'Não está vazio', 'flexify-checkout-for-woocommerce' ),
            'contains' => esc_html__( 'Contém', 'flexify-checkout-for-woocommerce' ),
            'not_contain' => esc_html__( 'Não contém', 'flexify-checkout-for-woocommerce' ),
            'start_with' => esc_html__( 'Começa com', 'flexify-checkout-for-woocommerce' ),
            'finish_with' => esc_html__( 'Termina com', 'flexify-checkout-for-woocommerce' ),
            'bigger_then' => esc_html__( 'Maior que', 'flexify-checkout-for-woocommerce' ),
            'less_than' => esc_html__( 'Menor que', 'flexify-checkout-for-woocommerce' ),
            'checked' => esc_html__( 'Marcado', 'flexify-checkout-for-woocommerce' ),
            'not_checked' => esc_html__( 'Desmarcado', 'flexify-checkout-for-woocommerce' ),
        );
    }


    /**
     * Parse a stored list value into an int array.
     *
     * @since 6.0.0
     * @param mixed $value Stored value.
     * @return array<int,int>
     */
    private static function parse_list( $value ) {
        if ( is_string( $value ) ) {
            $value = explode( ',', $value );
        }

        if ( ! is_array( $value ) ) {
            return array();
        }

        return array_values( array_filter( array_map( 'absint', $value ) ) );
    }
}
