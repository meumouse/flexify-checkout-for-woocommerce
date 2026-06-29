<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Storage helpers for the checkout conditions manager.
 *
 * Conditions live in the flexify_checkout_conditions option as a list of
 * "rules". Each rule pairs a single action (show / hide / discount) with a
 * two-level condition tree (groups joined by all|any, conditions inside each
 * group joined by all|any). Legacy flat conditions (<= 5.x) are migrated to
 * this shape automatically on first read.
 *
 * @since 6.0.0
 * @version 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Conditions_Store {

    /**
     * Option name holding the rules.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION_NAME = 'flexify_checkout_conditions';

    /**
     * Condition subjects that resolve to integer ids (products, users, terms, zones).
     *
     * @since 6.0.0
     * @var string[]
     */
    const INT_ITEM_SUBJECTS = array( 'user', 'product', 'category', 'attribute', 'shipping_region' );

    /**
     * Condition subjects that resolve to string items (country codes, role keys).
     *
     * @since 6.0.0
     * @var string[]
     */
    const STRING_ITEM_SUBJECTS = array( 'country', 'user_role' );

    /**
     * All accepted condition subjects.
     *
     * @since 6.0.0
     * @var string[]
     */
    const SUBJECTS = array( 'field', 'cart_qty', 'cart_total', 'user', 'user_role', 'product', 'category', 'attribute', 'country', 'shipping_region' );


    /**
     * Read the stored rules, migrating legacy data on demand.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_rules() {
        $stored = get_option( self::OPTION_NAME, array() );
        $stored = is_array( $stored ) ? $stored : array();

        if ( empty( $stored ) ) {
            return array();
        }

        $migrated = false;
        $rules = array();

        foreach ( $stored as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            // Already in the new shape.
            if ( isset( $item['action'] ) && isset( $item['groups'] ) ) {
                $rules[] = self::sanitize_rule( $item, isset( $item['id'] ) ? (string) $item['id'] : self::generate_id() );
                continue;
            }

            // Legacy flat condition -> convert.
            $rules[] = self::migrate_legacy_condition( $item );
            $migrated = true;
        }

        if ( $migrated ) {
            update_option( self::OPTION_NAME, $rules );
        }

        return $rules;
    }


    /**
     * Persist the full list of rules.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $rules Rules to store.
     * @return bool
     */
    public static function save_rules( $rules ) {
        return update_option( self::OPTION_NAME, array_values( $rules ) );
    }


    /**
     * Build the client payload: rules with summaries and resolved item labels.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_rules_for_client() {
        $items = array();

        foreach ( self::get_rules() as $rule ) {
            $items[] = array_merge( $rule, array(
                'summary' => self::summarize( $rule ),
                'groups' => self::label_groups( $rule['groups'] ),
            ) );
        }

        return $items;
    }


    /**
     * Add a new rule.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw rule payload.
     * @return string|false New rule id on success, false on failure.
     */
    public static function add_rule( $incoming ) {
        $rules = self::get_rules();
        $rule = self::sanitize_rule( $incoming, self::generate_id() );
        $rules[] = $rule;

        return self::save_rules( $rules ) ? $rule['id'] : false;
    }


    /**
     * Update an existing rule by its id.
     *
     * @since 6.0.0
     * @param string $id Rule id.
     * @param array<string,mixed> $incoming Raw rule payload.
     * @return bool
     */
    public static function update_rule( $id, $incoming ) {
        $rules = self::get_rules();
        $found = false;

        foreach ( $rules as $index => $rule ) {
            if ( isset( $rule['id'] ) && $rule['id'] === $id ) {
                $rules[ $index ] = self::sanitize_rule( $incoming, $id );
                $found = true;
                break;
            }
        }

        if ( ! $found ) {
            return false;
        }

        return self::save_rules( $rules );
    }


    /**
     * Remove a rule by its id.
     *
     * @since 6.0.0
     * @param string $id Rule id.
     * @return bool
     */
    public static function remove_rule( $id ) {
        $rules = self::get_rules();
        $next = array();
        $found = false;

        foreach ( $rules as $rule ) {
            if ( isset( $rule['id'] ) && $rule['id'] === $id ) {
                $found = true;
                continue;
            }

            $next[] = $rule;
        }

        if ( ! $found ) {
            return false;
        }

        return self::save_rules( $next );
    }


    /**
     * Remove conditions and rules that reference checkout fields which no
     * longer exist.
     *
     * Conditions point at fields in two places: a condition with the "field"
     * subject targets a field as its left-hand operand, and a rule action with
     * the "field" component targets a field as the thing being shown/hidden.
     * When the referenced field is gone, the condition is dropped and a rule
     * whose action target is gone is removed entirely (it can no longer act on
     * anything).
     *
     * @since 6.0.0
     * @param string|null $field_id Specific field to scrub. When null, scrubs
     *                              every field id that is not in the fields store.
     * @return bool True when the stored rules changed.
     */
    public static function scrub_orphan_field_conditions( $field_id = null ) {
        $rules = self::get_rules();

        if ( empty( $rules ) ) {
            return false;
        }

        $field_id = ( null === $field_id ) ? null : sanitize_text_field( (string) $field_id );

        // Resolve which field references count as orphan.
        $is_orphan = static function ( $referenced ) use ( $field_id ) {
            $referenced = (string) $referenced;

            if ( '' === $referenced ) {
                return false;
            }

            if ( null !== $field_id ) {
                return $referenced === $field_id;
            }

            $existing = Fields_Store::get_fields();

            return ! isset( $existing[ $referenced ] );
        };

        $next = array();

        foreach ( $rules as $rule ) {
            $action = $rule['action'] ?? array();

            // Drop the whole rule when its action targets a missing field.
            if ( ( $action['component'] ?? '' ) === 'field' && $is_orphan( $action['field'] ?? '' ) ) {
                continue;
            }

            foreach ( (array) ( $rule['groups'] ?? array() ) as $g => $group ) {
                $conditions = array();

                foreach ( (array) ( $group['conditions'] ?? array() ) as $condition ) {
                    if ( ( $condition['subject'] ?? '' ) === 'field' && $is_orphan( $condition['field'] ?? '' ) ) {
                        continue;
                    }

                    $conditions[] = $condition;
                }

                $rule['groups'][ $g ]['conditions'] = $conditions;
            }

            $next[] = $rule;
        }

        if ( $next === $rules ) {
            return false;
        }

        return self::save_rules( $next );
    }


    /**
     * Sanitize an incoming rule payload.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw rule data.
     * @param string $id Rule id to assign.
     * @return array<string,mixed>
     */
    public static function sanitize_rule( $incoming, $id ) {
        $incoming = is_array( $incoming ) ? $incoming : array();

        $rule = array(
            'id' => (string) $id,
            'enabled' => ! isset( $incoming['enabled'] ) || ! empty( $incoming['enabled'] ),
            'name' => isset( $incoming['name'] ) ? sanitize_text_field( (string) $incoming['name'] ) : '',
            'action' => self::sanitize_action( $incoming['action'] ?? array() ),
            'match' => ( isset( $incoming['match'] ) && 'any' === $incoming['match'] ) ? 'any' : 'all',
            'groups' => array(),
        );

        foreach ( (array) ( $incoming['groups'] ?? array() ) as $group ) {
            $rule['groups'][] = self::sanitize_group( $group );
        }

        if ( empty( $rule['groups'] ) ) {
            $rule['groups'][] = array( 'match' => 'all', 'conditions' => array() );
        }

        return $rule;
    }


    /**
     * Sanitize a rule action.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw action data.
     * @return array<string,mixed>
     */
    public static function sanitize_action( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();
        $type = in_array( $incoming['type'] ?? '', array( 'show', 'hide', 'discount' ), true ) ? $incoming['type'] : 'show';
        $action = array( 'type' => $type );

        if ( 'discount' === $type ) {
            $raw = is_array( $incoming['discount'] ?? null ) ? $incoming['discount'] : array();

            $action['discount'] = array(
                'mode' => ( ( $raw['mode'] ?? 'percent' ) === 'fixed' ) ? 'fixed' : 'percent',
                'value' => max( 0, (float) ( $raw['value'] ?? 0 ) ),
                'label' => sanitize_text_field( (string) ( $raw['label'] ?? '' ) ),
            );

            return $action;
        }

        $component = in_array( $incoming['component'] ?? '', array( 'field', 'shipping', 'payment' ), true ) ? $incoming['component'] : 'field';
        $action['component'] = $component;

        if ( 'field' === $component ) {
            $action['field'] = sanitize_text_field( (string) ( $incoming['field'] ?? '' ) );
        } elseif ( 'shipping' === $component ) {
            $action['shipping_method'] = sanitize_text_field( (string) ( $incoming['shipping_method'] ?? '' ) );
        } elseif ( 'payment' === $component ) {
            $action['payment_method'] = sanitize_text_field( (string) ( $incoming['payment_method'] ?? '' ) );
        }

        return $action;
    }


    /**
     * Sanitize a condition group.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw group data.
     * @return array<string,mixed>
     */
    public static function sanitize_group( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();

        $group = array(
            'match' => ( ( $incoming['match'] ?? 'all' ) === 'any' ) ? 'any' : 'all',
            'conditions' => array(),
        );

        foreach ( (array) ( $incoming['conditions'] ?? array() ) as $condition ) {
            $sanitized = self::sanitize_condition( $condition );

            if ( null !== $sanitized ) {
                $group['conditions'][] = $sanitized;
            }
        }

        return $group;
    }


    /**
     * Sanitize a single condition.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw condition data.
     * @return array<string,mixed>|null
     */
    public static function sanitize_condition( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();
        $subject = sanitize_key( $incoming['subject'] ?? '' );

        if ( ! in_array( $subject, self::SUBJECTS, true ) ) {
            return null;
        }

        $condition = array(
            'subject' => $subject,
            'operator' => sanitize_key( $incoming['operator'] ?? 'is' ),
            'value' => sanitize_text_field( (string) ( $incoming['value'] ?? '' ) ),
            'field' => 'field' === $subject ? sanitize_text_field( (string) ( $incoming['field'] ?? '' ) ) : '',
            'items' => array(),
        );

        $raw_items = $incoming['items'] ?? array();

        if ( is_string( $raw_items ) ) {
            $raw_items = '' === $raw_items ? array() : explode( ',', $raw_items );
        }

        $raw_items = is_array( $raw_items ) ? $raw_items : array();

        // Accept arrays of {id,label} objects (from the Vue multiselect) or scalars.
        $raw_items = array_map( static function ( $item ) {
            return is_array( $item ) && isset( $item['id'] ) ? $item['id'] : $item;
        }, $raw_items );

        if ( in_array( $subject, self::INT_ITEM_SUBJECTS, true ) ) {
            $condition['items'] = array_values( array_unique( array_filter( array_map( 'absint', $raw_items ) ) ) );
        } elseif ( in_array( $subject, self::STRING_ITEM_SUBJECTS, true ) ) {
            $condition['items'] = array_values( array_unique( array_filter( array_map( static function ( $item ) {
                return sanitize_text_field( (string) $item );
            }, $raw_items ) ) ) );
        }

        return $condition;
    }


    /**
     * Generate a unique rule id.
     *
     * @since 6.0.0
     * @return string
     */
    public static function generate_id() {
        return 'cond_' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 12 );
    }


    /**
     * Convert a legacy flat condition into a new rule.
     *
     * @since 6.0.0
     * @param array<string,mixed> $legacy Legacy condition data.
     * @return array<string,mixed>
     */
    public static function migrate_legacy_condition( $legacy ) {
        $component = $legacy['component'] ?? 'field';

        $action = array(
            'type' => in_array( $legacy['type_rule'] ?? '', array( 'show', 'hide' ), true ) ? $legacy['type_rule'] : 'show',
            'component' => in_array( $component, array( 'field', 'shipping', 'payment' ), true ) ? $component : 'field',
        );

        if ( 'field' === $action['component'] ) {
            $action['field'] = (string) ( $legacy['component_field'] ?? '' );
        } elseif ( 'shipping' === $action['component'] ) {
            $action['shipping_method'] = (string) ( $legacy['shipping_method'] ?? '' );
        } elseif ( 'payment' === $action['component'] ) {
            $action['payment_method'] = (string) ( $legacy['payment_method'] ?? '' );
        }

        $conditions = array();

        // Primary verification.
        $verification = $legacy['verification_condition'] ?? '';
        $operator = (string) ( $legacy['condition'] ?? 'is' );
        $value = (string) ( $legacy['condition_value'] ?? '' );

        if ( 'field' === $verification ) {
            $conditions[] = array(
                'subject' => 'field',
                'operator' => $operator,
                'value' => $value,
                'field' => (string) ( $legacy['verification_condition_field'] ?? '' ),
                'items' => array(),
            );
        } elseif ( 'qtd_cart_total' === $verification ) {
            $conditions[] = array( 'subject' => 'cart_qty', 'operator' => $operator, 'value' => $value, 'field' => '', 'items' => array() );
        } elseif ( 'cart_total_value' === $verification ) {
            $conditions[] = array( 'subject' => 'cart_total', 'operator' => $operator, 'value' => $value, 'field' => '', 'items' => array() );
        }

        // User filter.
        if ( ( $legacy['filter_user'] ?? '' ) === 'specific_user' && ! empty( $legacy['specific_user'] ) ) {
            $conditions[] = array( 'subject' => 'user', 'operator' => 'is_one_of', 'value' => '', 'field' => '', 'items' => array_map( 'absint', (array) $legacy['specific_user'] ) );
        } elseif ( ( $legacy['filter_user'] ?? '' ) === 'specific_role' && ! empty( $legacy['specific_role'] ) ) {
            $conditions[] = array( 'subject' => 'user_role', 'operator' => 'is_one_of', 'value' => '', 'field' => '', 'items' => array( (string) $legacy['specific_role'] ) );
        }

        // Product filter.
        $product_map = array(
            'specific_products' => 'product',
            'specific_categories' => 'category',
            'specific_attributes' => 'attribute',
        );

        foreach ( $product_map as $legacy_key => $subject ) {
            if ( ! empty( $legacy[ $legacy_key ] ) && is_array( $legacy[ $legacy_key ] ) ) {
                $conditions[] = array( 'subject' => $subject, 'operator' => 'is_one_of', 'value' => '', 'field' => '', 'items' => array_map( 'absint', $legacy[ $legacy_key ] ) );
            }
        }

        return array(
            'id' => self::generate_id(),
            'enabled' => true,
            'name' => '',
            'action' => $action,
            'match' => 'all',
            'groups' => array(
                array( 'match' => 'all', 'conditions' => $conditions ),
            ),
        );
    }


    /**
     * Attach resolved {id,label} lists to id-based conditions for the editor UI.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $groups Rule groups.
     * @return array<int,array<string,mixed>>
     */
    public static function label_groups( $groups ) {
        foreach ( $groups as $g => $group ) {
            foreach ( $group['conditions'] as $c => $condition ) {
                if ( in_array( $condition['subject'], self::INT_ITEM_SUBJECTS, true ) && 'shipping_region' !== $condition['subject'] ) {
                    $groups[ $g ]['conditions'][ $c ]['items_labeled'] = self::label_items( $condition['subject'], $condition['items'] );
                }
            }
        }

        return $groups;
    }


    /**
     * Resolve a list of ids into {id,label} pairs for a given subject.
     *
     * @since 6.0.0
     * @param string $subject Condition subject.
     * @param array<int,int> $ids Item ids.
     * @return array<int,array<string,mixed>>
     */
    public static function label_items( $subject, $ids ) {
        $ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );

        if ( empty( $ids ) ) {
            return array();
        }

        $labeled = array();

        if ( 'product' === $subject ) {
            $products = get_posts( array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'post__in' => $ids,
                'orderby' => 'post__in',
            ) );

            foreach ( $products as $product ) {
                $labeled[] = array( 'id' => (int) $product->ID, 'label' => $product->post_title );
            }
        } elseif ( 'user' === $subject ) {
            foreach ( get_users( array( 'include' => $ids, 'orderby' => 'include' ) ) as $user ) {
                $labeled[] = array( 'id' => (int) $user->ID, 'label' => $user->display_name );
            }
        } else { // category / attribute terms.
            foreach ( $ids as $term_id ) {
                $term = get_term( $term_id );

                if ( $term && ! is_wp_error( $term ) ) {
                    $labeled[] = array( 'id' => (int) $term->term_id, 'label' => $term->name );
                }
            }
        }

        return $labeled;
    }


    /**
     * Build a human-readable summary for the rules list.
     *
     * @since 6.0.0
     * @param array<string,mixed> $rule Rule data.
     * @return array<string,string>
     */
    public static function summarize( $rule ) {
        $action = $rule['action'] ?? array();
        $type = $action['type'] ?? 'show';

        $type_labels = array(
            'show' => __( 'Mostrar', 'flexify-checkout-for-woocommerce' ),
            'hide' => __( 'Ocultar', 'flexify-checkout-for-woocommerce' ),
        );

        if ( 'discount' === $type ) {
            $discount = $action['discount'] ?? array();
            $value = isset( $discount['value'] ) ? (float) $discount['value'] : 0;
            $amount = ( ( $discount['mode'] ?? 'percent' ) === 'fixed' )
                ? html_entity_decode( ( function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'R$' ) ) . ' ' . number_format_i18n( $value, 2 )
                : rtrim( rtrim( number_format_i18n( $value, 2 ), '0' ), ',.' ) . '%';

            $line_1 = sprintf( __( 'Aplicar desconto de %s', 'flexify-checkout-for-woocommerce' ), $amount );
        } else {
            $type_label = $type_labels[ $type ] ?? $type_labels['show'];
            $line_1 = sprintf( '%s %s', $type_label, self::describe_target( $action ) );
        }

        return array(
            'line_1' => $line_1,
            'line_2' => self::describe_conditions( $rule ),
        );
    }


    /**
     * Describe the action target (field / shipping / payment).
     *
     * @since 6.0.0
     * @param array<string,mixed> $action Action data.
     * @return string
     */
    private static function describe_target( $action ) {
        $component = $action['component'] ?? 'field';

        if ( 'field' === $component ) {
            $fields = Helpers::get_checkout_fields_on_admin();
            $field_id = (string) ( $action['field'] ?? '' );
            $label = isset( $fields['billing'][ $field_id ]['label'] ) ? $fields['billing'][ $field_id ]['label'] : $field_id;

            return sprintf( __( 'o campo %s', 'flexify-checkout-for-woocommerce' ), $label );
        }

        if ( 'shipping' === $component ) {
            $id = (string) ( $action['shipping_method'] ?? '' );
            $methods = function_exists('WC') && WC()->shipping ? WC()->shipping->get_shipping_methods() : array();
            $title = isset( $methods[ $id ] ) ? $methods[ $id ]->method_title : $id;

            return sprintf( __( 'a entrega %s', 'flexify-checkout-for-woocommerce' ), $title );
        }

        $id = (string) ( $action['payment_method'] ?? '' );
        $gateways = function_exists('WC') && WC()->payment_gateways ? WC()->payment_gateways->payment_gateways() : array();
        $title = isset( $gateways[ $id ] ) ? $gateways[ $id ]->get_title() : $id;

        return sprintf( __( 'o pagamento %s', 'flexify-checkout-for-woocommerce' ), $title );
    }


    /**
     * Build a short textual description of a rule's conditions.
     *
     * @since 6.0.0
     * @param array<string,mixed> $rule Rule data.
     * @return string
     */
    private static function describe_conditions( $rule ) {
        $groups = $rule['groups'] ?? array();
        $count = 0;

        foreach ( $groups as $group ) {
            $count += count( $group['conditions'] ?? array() );
        }

        if ( 0 === $count ) {
            return __( 'Sempre (sem condições)', 'flexify-checkout-for-woocommerce' );
        }

        $join = ( ( $rule['match'] ?? 'all' ) === 'any' ) ? __( 'qualquer', 'flexify-checkout-for-woocommerce' ) : __( 'todas', 'flexify-checkout-for-woocommerce' );

        return sprintf(
            _n( 'Quando %1$s de %2$d condição for atendida', 'Quando %1$s de %2$d condições forem atendidas', $count, 'flexify-checkout-for-woocommerce' ),
            $join,
            $count
        );
    }


    /**
     * Operator labels (shared with the legacy summary helpers).
     *
     * @since 6.0.0
     * @return array<string,string>
     */
    public static function get_condition_labels() {
        return array(
            'is' => __( 'É', 'flexify-checkout-for-woocommerce' ),
            'is_not' => __( 'Não é', 'flexify-checkout-for-woocommerce' ),
            'empty' => __( 'Vazio', 'flexify-checkout-for-woocommerce' ),
            'not_empty' => __( 'Não está vazio', 'flexify-checkout-for-woocommerce' ),
            'contains' => __( 'Contém', 'flexify-checkout-for-woocommerce' ),
            'not_contain' => __( 'Não contém', 'flexify-checkout-for-woocommerce' ),
            'start_with' => __( 'Começa com', 'flexify-checkout-for-woocommerce' ),
            'finish_with' => __( 'Termina com', 'flexify-checkout-for-woocommerce' ),
            'bigger_then' => __( 'Maior que', 'flexify-checkout-for-woocommerce' ),
            'less_than' => __( 'Menor que', 'flexify-checkout-for-woocommerce' ),
            'checked' => __( 'Marcado', 'flexify-checkout-for-woocommerce' ),
            'not_checked' => __( 'Desmarcado', 'flexify-checkout-for-woocommerce' ),
            'is_one_of' => __( 'É um de', 'flexify-checkout-for-woocommerce' ),
            'is_not_one_of' => __( 'Não é um de', 'flexify-checkout-for-woocommerce' ),
        );
    }
}
