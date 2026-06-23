<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Settings\Conditions_Store;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Evaluate and apply checkout conditions (show / hide / discount).
 *
 * Rules carry a single action and a two-level condition tree. See
 * {@see Conditions_Store} for the persisted shape. This class walks that tree
 * against the live cart / customer / posted data and applies the matching
 * action to checkout fields, payment gateways, shipping methods or cart fees.
 *
 * @since 3.5.0
 * @version 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Conditions {

    /**
     * Construct function
     *
     * @since 3.5.0
     * @version 6.0.0
     * @return void
     */
    public function __construct() {
        // Conditions for checkout fields
        add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields_conditions' ), 150 );

        // Conditions for payment gateways
        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'payment_gateways_conditions' ), 10, 1 );

        // Conditions for shipping methods
        add_filter( 'woocommerce_package_rates', array( $this, 'shipping_methods_conditions' ), 10, 2 );

        // Conditional cart discounts (negative fee)
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_discount_fees' ), 20, 1 );

        // Validate fields with conditions
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_fields' ), 10, 2 );
    }


    /**
     * Get enabled rules filtered by their action target.
     *
     * @since 6.0.0
     * @param string $filter One of: field, shipping, payment, discount.
     * @return array<int,array<string,mixed>>
     */
    public static function get_rules_filtered( $filter ) {
        $rules = array_filter( Conditions_Store::get_rules(), static function ( $rule ) {
            return ! empty( $rule['enabled'] );
        } );

        if ( 'discount' === $filter ) {
            return array_values( array_filter( $rules, static function ( $rule ) {
                return isset( $rule['action']['type'] ) && 'discount' === $rule['action']['type'];
            } ) );
        }

        return array_values( array_filter( $rules, static function ( $rule ) use ( $filter ) {
            return isset( $rule['action']['type'], $rule['action']['component'] )
                && in_array( $rule['action']['type'], array( 'show', 'hide' ), true )
                && $filter === $rule['action']['component'];
        } ) );
    }


    /**
     * Set custom conditions for checkout fields.
     *
     * The actual show/hide of a field that depends on other field values is done
     * live on the client (see app/src/checkout/main.js). Here we only flag the
     * targeted fields so the front-end script and the required-marker logic work.
     *
     * @since 3.5.0
     * @version 6.0.0
     * @param array $fields | Checkout fields
     * @return array $fields
     */
    public function checkout_fields_conditions( $fields ) {
        $rules = self::get_rules_filtered('field');

        if ( ! is_flexify_checkout() || empty( $rules ) ) {
            return $fields;
        }

        foreach ( $rules as $rule ) {
            $target = isset( $rule['action']['field'] ) ? $rule['action']['field'] : '';

            if ( '' === $target ) {
                continue;
            }

            foreach ( $fields as $fieldset_key => $fieldset ) {
                if ( ! isset( $fieldset[ $target ] ) ) {
                    continue;
                }

                $field = $fieldset[ $target ];
                $fields[ $fieldset_key ][ $target ]['required'] = false;
                $fields[ $fieldset_key ][ $target ]['class'][] = 'has-condition';

                if ( isset( $field['required'] ) && $field['required'] ) {
                    $fields[ $fieldset_key ][ $target ]['class'][] = 'required-field';
                    $fields[ $fieldset_key ][ $target ]['label_class'] = 'has-condition required-field';
                } else {
                    $fields[ $fieldset_key ][ $target ]['label_class'] = 'has-condition';
                }
            }
        }

        return $fields;
    }


    /**
     * Set custom conditions for payment gateways.
     *
     * @since 3.5.0
     * @version 6.0.0
     * @param array $available_gateways | Available payment gateways
     * @return array
     */
    public function payment_gateways_conditions( $available_gateways ) {
        $rules = self::get_rules_filtered('payment');

        if ( ! is_flexify_checkout() || empty( $rules ) ) {
            return $available_gateways;
        }

        $context = self::build_context();

        foreach ( $rules as $rule ) {
            $gateway_id = isset( $rule['action']['payment_method'] ) ? $rule['action']['payment_method'] : '';

            if ( '' === $gateway_id || ! isset( $available_gateways[ $gateway_id ] ) ) {
                continue;
            }

            $matched = self::evaluate_rule( $rule, $context );
            $type = $rule['action']['type'];

            if ( ( 'show' === $type && ! $matched ) || ( 'hide' === $type && $matched ) ) {
                unset( $available_gateways[ $gateway_id ] );
            }
        }

        return $available_gateways;
    }


    /**
     * Set custom conditions for checkout shipping methods.
     *
     * @since 3.5.0
     * @version 6.0.0
     * @param array $shipping_methods | Package rates
     * @param array $package | Package of cart items
     * @return array $rates
     */
    public function shipping_methods_conditions( $shipping_methods, $package ) {
        $rules = self::get_rules_filtered('shipping');

        if ( ! is_flexify_checkout() || empty( $rules ) ) {
            return $shipping_methods;
        }

        $context = self::build_context( $package );

        foreach ( $rules as $rule ) {
            $method = isset( $rule['action']['shipping_method'] ) ? $rule['action']['shipping_method'] : '';

            if ( '' === $method ) {
                continue;
            }

            $matched = self::evaluate_rule( $rule, $context );
            $type = $rule['action']['type'];

            foreach ( $shipping_methods as $rate_id => $rate ) {
                $base = explode( ':', $rate_id )[0];

                if ( $base !== $method ) {
                    continue;
                }

                if ( ( 'show' === $type && ! $matched ) || ( 'hide' === $type && $matched ) ) {
                    unset( $shipping_methods[ $rate_id ] );
                }
            }
        }

        return $shipping_methods;
    }


    /**
     * Apply conditional discounts as negative cart fees.
     *
     * @since 6.0.0
     * @param \WC_Cart $cart | Cart object
     * @return void
     */
    public function apply_discount_fees( $cart ) {
        if ( ! $cart instanceof \WC_Cart ) {
            return;
        }

        $rules = self::get_rules_filtered('discount');

        if ( empty( $rules ) ) {
            return;
        }

        $context = self::build_context();
        $context['cart'] = $cart;

        foreach ( $rules as $rule ) {
            if ( ! self::evaluate_rule( $rule, $context ) ) {
                continue;
            }

            $discount = isset( $rule['action']['discount'] ) ? $rule['action']['discount'] : array();
            $value = isset( $discount['value'] ) ? (float) $discount['value'] : 0;

            if ( $value <= 0 ) {
                continue;
            }

            $amount = ( ( $discount['mode'] ?? 'percent' ) === 'fixed' )
                ? $value
                : ( $cart->get_subtotal() * $value / 100 );

            if ( $amount <= 0 ) {
                continue;
            }

            $label = ( isset( $discount['label'] ) && '' !== $discount['label'] )
                ? $discount['label']
                : __( 'Desconto', 'flexify-checkout-for-woocommerce' );

            $cart->add_fee( $label, -1 * $amount, false );
        }
    }


    /**
     * Build the evaluation context from the live cart and customer.
     *
     * @since 6.0.0
     * @param array|null $package | Optional shipping package for zone resolution.
     * @return array<string,mixed>
     */
    public static function build_context( $package = null ) {
        $cart = ( function_exists('WC') && WC()->cart ) ? WC()->cart : null;
        $customer = ( function_exists('WC') && WC()->customer ) ? WC()->customer : null;

        list( $product_ids, $category_ids, $attribute_ids ) = self::get_cart_term_ids( $cart );

        $shipping_country = $customer ? $customer->get_shipping_country() : '';
        $billing_country = $customer ? $customer->get_billing_country() : '';

        return array(
            'cart' => $cart,
            'customer' => $customer,
            'product_ids' => $product_ids,
            'category_ids' => $category_ids,
            'attribute_ids' => $attribute_ids,
            'country' => $shipping_country ?: $billing_country,
            'zone_id' => self::resolve_zone_id( $package, $customer ),
        );
    }


    /**
     * Resolve the matching WooCommerce shipping zone id.
     *
     * @since 6.0.0
     * @param array|null $package | Shipping package, when available.
     * @param \WC_Customer|null $customer | Customer object.
     * @return int
     */
    public static function resolve_zone_id( $package, $customer ) {
        if ( ! function_exists('wc_get_shipping_zone') ) {
            return 0;
        }

        if ( is_array( $package ) ) {
            $zone = wc_get_shipping_zone( $package );

            return $zone ? (int) $zone->get_id() : 0;
        }

        if ( $customer ) {
            $zone = wc_get_shipping_zone( array(
                'destination' => array(
                    'country' => $customer->get_shipping_country(),
                    'state' => $customer->get_shipping_state(),
                    'postcode' => $customer->get_shipping_postcode(),
                ),
            ) );

            return $zone ? (int) $zone->get_id() : 0;
        }

        return 0;
    }


    /**
     * Collect product, category and attribute term ids from the cart.
     *
     * @since 6.0.0
     * @param \WC_Cart|null $cart | Cart object.
     * @return array{0:array<int,int>,1:array<int,int>,2:array<int,int>}
     */
    public static function get_cart_term_ids( $cart ) {
        $product_ids = array();
        $category_ids = array();
        $attribute_ids = array();

        if ( ! $cart instanceof \WC_Cart ) {
            return array( $product_ids, $category_ids, $attribute_ids );
        }

        foreach ( $cart->get_cart() as $cart_item ) {
            $product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;

            if ( ! $product instanceof \WC_Product ) {
                continue;
            }

            $product_id = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : (int) $product->get_id();
            $product_ids[] = $product_id;

            $cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

            if ( ! is_wp_error( $cats ) ) {
                $category_ids = array_merge( $category_ids, array_map( 'intval', $cats ) );
            }

            foreach ( $product->get_attributes() as $attribute ) {
                if ( is_object( $attribute ) && method_exists( $attribute, 'is_taxonomy' ) && $attribute->is_taxonomy() ) {
                    $terms = wc_get_product_terms( $product_id, $attribute->get_name(), array( 'fields' => 'ids' ) );

                    if ( ! is_wp_error( $terms ) ) {
                        $attribute_ids = array_merge( $attribute_ids, array_map( 'intval', $terms ) );
                    }
                }
            }
        }

        return array(
            array_values( array_unique( $product_ids ) ),
            array_values( array_unique( $category_ids ) ),
            array_values( array_unique( $attribute_ids ) ),
        );
    }


    /**
     * Evaluate a full rule (groups joined by all|any).
     *
     * @since 6.0.0
     * @param array<string,mixed> $rule | Rule data.
     * @param array<string,mixed> $context | Evaluation context.
     * @return bool
     */
    public static function evaluate_rule( $rule, $context ) {
        $groups = isset( $rule['groups'] ) ? $rule['groups'] : array();

        if ( empty( $groups ) ) {
            return true;
        }

        $any = isset( $rule['match'] ) && 'any' === $rule['match'];

        foreach ( $groups as $group ) {
            $passed = self::evaluate_group( $group, $context );

            if ( $any && $passed ) {
                return true;
            }

            if ( ! $any && ! $passed ) {
                return false;
            }
        }

        return ! $any;
    }


    /**
     * Evaluate a condition group (conditions joined by all|any).
     *
     * @since 6.0.0
     * @param array<string,mixed> $group | Group data.
     * @param array<string,mixed> $context | Evaluation context.
     * @return bool
     */
    public static function evaluate_group( $group, $context ) {
        $conditions = isset( $group['conditions'] ) ? $group['conditions'] : array();

        if ( empty( $conditions ) ) {
            return true;
        }

        $any = isset( $group['match'] ) && 'any' === $group['match'];

        foreach ( $conditions as $condition ) {
            $passed = self::evaluate_condition( $condition, $context );

            if ( $any && $passed ) {
                return true;
            }

            if ( ! $any && ! $passed ) {
                return false;
            }
        }

        return ! $any;
    }


    /**
     * Evaluate a single condition against the context.
     *
     * @since 6.0.0
     * @param array<string,mixed> $condition | Condition data.
     * @param array<string,mixed> $context | Evaluation context.
     * @return bool
     */
    public static function evaluate_condition( $condition, $context ) {
        $subject = isset( $condition['subject'] ) ? $condition['subject'] : '';
        $operator = isset( $condition['operator'] ) ? $condition['operator'] : 'is';
        $value = isset( $condition['value'] ) ? $condition['value'] : '';
        $items = isset( $condition['items'] ) ? (array) $condition['items'] : array();
        $cart = isset( $context['cart'] ) ? $context['cart'] : null;

        switch ( $subject ) {
            case 'field':
                return self::check_fields_conditions( $operator, isset( $condition['field'] ) ? $condition['field'] : '', $value );

            case 'cart_qty':
                $qty = $cart instanceof \WC_Cart ? $cart->get_cart_contents_count() : 0;
                return self::check_condition( $operator, $qty, $value );

            case 'cart_total':
                $total = $cart instanceof \WC_Cart ? $cart->get_cart_contents_total() : 0;
                return self::check_condition( $operator, $total, $value );

            case 'user':
                return self::match_list( $operator, array( get_current_user_id() ), $items );

            case 'user_role':
                $user = wp_get_current_user();
                $roles = ( $user && isset( $user->roles ) ) ? (array) $user->roles : array();
                return self::match_list( $operator, $roles, $items );

            case 'product':
                return self::match_list( $operator, isset( $context['product_ids'] ) ? $context['product_ids'] : array(), $items );

            case 'category':
                return self::match_list( $operator, isset( $context['category_ids'] ) ? $context['category_ids'] : array(), $items );

            case 'attribute':
                return self::match_list( $operator, isset( $context['attribute_ids'] ) ? $context['attribute_ids'] : array(), $items );

            case 'country':
                $country = isset( $context['country'] ) ? $context['country'] : '';
                return self::match_list( $operator, '' !== $country ? array( $country ) : array(), $items );

            case 'shipping_region':
                $zone = isset( $context['zone_id'] ) ? (int) $context['zone_id'] : 0;
                return self::match_list( $operator, array( $zone ), $items );
        }

        return false;
    }


    /**
     * Match a haystack against a list of needles using is_one_of / is_not_one_of.
     *
     * @since 6.0.0
     * @param string $operator | Operator (is_one_of|is_not_one_of).
     * @param array $haystack | Current values.
     * @param array $needles | Allowed values.
     * @return bool
     */
    public static function match_list( $operator, $haystack, $needles ) {
        $haystack = array_map( 'strval', (array) $haystack );
        $needles = array_map( 'strval', (array) $needles );

        if ( empty( $needles ) ) {
            return false;
        }

        $intersects = count( array_intersect( $haystack, $needles ) ) > 0;

        return 'is_not_one_of' === $operator ? ! $intersects : $intersects;
    }


    /**
     * Export field-action rules for the front-end visibility script.
     *
     * Non-field conditions are pre-evaluated server-side (server_pass), while
     * field conditions are evaluated live in the browser.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function export_field_rules_for_js() {
        $rules = self::get_rules_filtered('field');

        if ( empty( $rules ) ) {
            return array();
        }

        $context = self::build_context();
        $output = array();

        foreach ( $rules as $rule ) {
            $groups = array();

            foreach ( $rule['groups'] as $group ) {
                $conditions = array();

                foreach ( $group['conditions'] as $condition ) {
                    $entry = array(
                        'subject' => $condition['subject'],
                        'field' => isset( $condition['field'] ) ? $condition['field'] : '',
                        'operator' => $condition['operator'],
                        'value' => isset( $condition['value'] ) ? $condition['value'] : '',
                    );

                    if ( 'field' !== $condition['subject'] ) {
                        $entry['server_pass'] = self::evaluate_condition( $condition, $context );
                    }

                    $conditions[] = $entry;
                }

                $groups[] = array(
                    'match' => $group['match'],
                    'conditions' => $conditions,
                );
            }

            $output[] = array(
                'id' => $rule['id'],
                'action' => array(
                    'type' => $rule['action']['type'],
                    'field' => isset( $rule['action']['field'] ) ? $rule['action']['field'] : '',
                ),
                'match' => $rule['match'],
                'groups' => $groups,
            );
        }

        return $output;
    }


    /**
     * Check a generic condition operator.
     *
     * @since 3.5.0
     * @version 6.0.0
     * @param string $condition | Operator.
     * @param string $value | Current value.
     * @param string $value_compare | Optional value to compare against.
     * @return bool
     */
    public static function check_condition( $condition, $value, $value_compare = '' ) {
        switch ( $condition ) {
            case 'is':
                return (string) $value === (string) $value_compare;

            case 'is_not':
                return (string) $value !== (string) $value_compare;

            case 'empty':
                return empty( $value );

            case 'not_empty':
                return ! empty( $value );

            case 'contains':
                return '' !== (string) $value_compare && strpos( (string) $value, (string) $value_compare ) !== false;

            case 'not_contain':
                return strpos( (string) $value, (string) $value_compare ) === false;

            case 'start_with':
                if ( version_compare( PHP_VERSION, '8.0.0' ) >= 0 ) {
                    return str_starts_with( (string) $value, (string) $value_compare );
                }

                return strpos( (string) $value, (string) $value_compare ) === 0;

            case 'finish_with':
                if ( version_compare( PHP_VERSION, '8.0.0' ) >= 0 ) {
                    return str_ends_with( (string) $value, (string) $value_compare );
                }

                $length = strlen( (string) $value_compare );
                return substr( (string) $value, -$length ) === (string) $value_compare;

            case 'bigger_then':
                return (float) $value > (float) $value_compare;

            case 'less_than':
                return (float) $value < (float) $value_compare;

            case 'checked':
                return in_array( $value, array( '1', 1, 'on', 'yes', 'true', true ), true );

            case 'not_checked':
                return ! in_array( $value, array( '1', 1, 'on', 'yes', 'true', true ), true );

            default:
                return false;
        }
    }


    /**
     * Check a checkout field value condition (reads from the current POST).
     *
     * @since 3.5.0
     * @version 6.0.0
     * @param string $condition | Operator.
     * @param string $field | Field id.
     * @param string $value | Value to compare.
     * @return bool
     */
    public static function check_fields_conditions( $condition, $field, $value ) {
        if ( '' === $field ) {
            return false;
        }

        $field_value = filter_input( INPUT_POST, $field, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

        if ( is_null( $field_value ) ) {
            $field_value = '';
        }

        if ( in_array( $condition, array( 'checked', 'not_checked' ), true ) ) {
            return self::check_condition( $condition, $field_value );
        }

        return self::check_condition( $condition, $field_value, $value );
    }


    /**
     * Validate checkout fields based on conditions (skip required for hidden fields).
     *
     * @since 5.1.0
     * @version 6.0.0
     * @param array $data | Posted checkout data.
     * @param object $errors | Validation errors.
     * @return void
     */
    public function validate_fields( $data, $errors ) {
        $rules = self::get_rules_filtered('field');

        if ( ! is_flexify_checkout() || empty( $rules ) ) {
            return;
        }

        $context = self::build_context();

        $remove_field_errors = function( $errors_obj, $field_id ) {
            $errors_obj->remove( "{$field_id}_required" );

            foreach ( (array) $errors_obj->get_error_codes() as $code ) {
                $data_arg = $errors_obj->get_error_data( $code );

                if ( is_array( $data_arg ) && isset( $data_arg['id'] ) && $data_arg['id'] === $field_id ) {
                    $errors_obj->remove( $code );
                }
            }
        };

        foreach ( $rules as $rule ) {
            $field_key = isset( $rule['action']['field'] ) ? $rule['action']['field'] : '';

            if ( '' === $field_key ) {
                continue;
            }

            $matched = self::evaluate_rule( $rule, $context );
            $type = $rule['action']['type'];
            $should_hide = ( 'show' === $type && ! $matched ) || ( 'hide' === $type && $matched );

            if ( $should_hide ) {
                $remove_field_errors( $errors, $field_key );

                if ( isset( $data[ $field_key ] ) ) {
                    $data[ $field_key ] = '';
                }
            }
        }
    }
}
