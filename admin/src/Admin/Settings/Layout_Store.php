<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Storage helpers for the visual checkout builder (steps + fields + components).
 *
 * The layout lives in the flexify_checkout_layout option and is the source of
 * truth for which steps exist, their order, and the ordered items inside each
 * step. An item is either a FIELD (a thin reference to a field id stored in
 * flexify_checkout_step_fields) or a rich COMPONENT (order bump, html block,
 * coupon, summary, notes) with its own config.
 *
 * Field *properties* still live in flexify_checkout_step_fields (managed by
 * Fields_Store); this store only owns step membership + ordering and keeps the
 * field record's step/priority derived from the layout on every save so the
 * legacy checkout, the Fields Manager and the Conditions engine keep working.
 *
 * Mirrors the shape and conventions of Conditions_Store.
 *
 * @since 6.0.0
 * @version 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Layout_Store {

    /**
     * Option name holding the layout.
     *
     * @since 6.0.0
     * @var string
     */
    const OPTION_NAME = 'flexify_checkout_layout';

    /**
     * Semantic step types WooCommerce relies on (always emitted to React).
     *
     * @since 6.0.0
     * @var string[]
     */
    const SEMANTIC_TYPES = array( 'contact', 'shipping', 'payment' );

    /**
     * Rich component types placeable inside a step.
     *
     * @since 6.0.0
     * @var string[]
     */
    const COMPONENTS = array( 'order_bump', 'html', 'coupon', 'summary', 'notes' );

    /**
     * Map a step type to the legacy field "step" bucket ('1' contact, '2' delivery).
     *
     * @since 6.0.0
     * @var array<string,string>
     */
    const TYPE_TO_FIELD_STEP = array(
        'contact' => '1',
        'shipping' => '2',
        'payment' => '2',
    );

    /**
     * Map a semantic step type to its text_check_step_N label setting.
     *
     * @since 6.0.0
     * @var array<string,string>
     */
    const TYPE_TO_LABEL_SETTING = array(
        'contact' => 'text_check_step_1',
        'shipping' => 'text_check_step_2',
        'payment' => 'text_check_step_3',
    );


    /**
     * Read the stored layout, falling back to a default derived from the current
     * checkout fields and reconciling any field not yet placed in the layout.
     *
     * @since 6.0.0
     * @return array<string,mixed> Layout array: { version:int, steps:array }.
     */
    public static function get_layout() {
        $stored = get_option( self::OPTION_NAME, array() );
        $stored = is_array( $stored ) ? $stored : array();

        if ( empty( $stored ) || empty( $stored['steps'] ) ) {
            return self::default_layout();
        }

        $layout = self::sanitize_layout( $stored );

        return self::reconcile_orphan_fields( $layout );
    }


    /**
     * Persist a layout, sync field step/priority and mirror semantic labels.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw layout payload.
     * @return array<string,mixed> The sanitized layout that was stored.
     */
    public static function save_layout( $incoming ) {
        $layout = self::sanitize_layout( $incoming );

        update_option( self::OPTION_NAME, $layout );

        self::sync_fields_from_layout( $layout );
        self::mirror_step_labels( $layout );

        return $layout;
    }


    /**
     * Build the admin client payload: layout with resolved order bump products
     * plus the field catalog used by the builder inspector.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_layout_for_client() {
        $layout = self::get_layout();

        return array(
            'version' => $layout['version'],
            'steps' => self::resolve_order_bumps( $layout['steps'] ),
            'field_catalog' => self::build_field_catalog(),
        );
    }


    /**
     * Build the React checkout payload: layout with resolved order bump products.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_layout_for_react() {
        $layout = self::get_layout();

        return array(
            'version' => $layout['version'],
            'steps' => self::resolve_order_bumps( $layout['steps'] ),
        );
    }


    /**
     * Whether an explicit layout has been saved by the operator.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function has_saved_layout() {
        $stored = get_option( self::OPTION_NAME, array() );

        return is_array( $stored ) && ! empty( $stored['steps'] );
    }


    /**
     * Sanitize an incoming layout payload, enforcing the structural invariants.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw layout data.
     * @return array<string,mixed>
     */
    public static function sanitize_layout( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();
        $raw_steps = isset( $incoming['steps'] ) && is_array( $incoming['steps'] ) ? $incoming['steps'] : array();

        $steps = array();

        foreach ( $raw_steps as $step ) {
            $sanitized = self::sanitize_step( $step );

            if ( null !== $sanitized ) {
                $steps[] = $sanitized;
            }
        }

        $steps = self::enforce_semantic_steps( $steps );

        // Normalize order indexes after any reordering / appends.
        foreach ( $steps as $index => $step ) {
            $steps[ $index ]['order'] = $index;
        }

        return array(
            'version' => 1,
            'steps' => array_values( $steps ),
        );
    }


    /**
     * Sanitize a single step.
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw step data.
     * @return array<string,mixed>|null
     */
    public static function sanitize_step( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();

        $type = in_array( $incoming['type'] ?? '', array( 'contact', 'shipping', 'payment', 'custom' ), true )
            ? $incoming['type']
            : 'custom';

        $id = isset( $incoming['id'] ) ? sanitize_key( (string) $incoming['id'] ) : '';

        if ( '' === $id ) {
            $id = self::generate_id( 'step' );
        }

        $items = array();

        foreach ( (array) ( $incoming['items'] ?? array() ) as $item ) {
            $sanitized = self::sanitize_item( $item );

            if ( null !== $sanitized ) {
                $sanitized['order'] = count( $items );
                $items[] = $sanitized;
            }
        }

        return array(
            'id' => $id,
            'type' => $type,
            'label' => sanitize_text_field( (string) ( $incoming['label'] ?? '' ) ),
            'enabled' => ! isset( $incoming['enabled'] ) || ! empty( $incoming['enabled'] ),
            'order' => 0,
            'items' => $items,
        );
    }


    /**
     * Sanitize a single step item (field reference or component).
     *
     * @since 6.0.0
     * @param array<string,mixed> $incoming Raw item data.
     * @return array<string,mixed>|null
     */
    public static function sanitize_item( $incoming ) {
        $incoming = is_array( $incoming ) ? $incoming : array();
        $kind = ( ( $incoming['kind'] ?? '' ) === 'component' ) ? 'component' : 'field';

        $id = isset( $incoming['id'] ) ? sanitize_key( (string) $incoming['id'] ) : '';

        if ( '' === $id ) {
            $id = self::generate_id( 'it' );
        }

        if ( 'field' === $kind ) {
            $field_id = sanitize_text_field( (string) ( $incoming['field_id'] ?? '' ) );

            if ( '' === $field_id ) {
                return null;
            }

            return array(
                'id' => $id,
                'kind' => 'field',
                'field_id' => $field_id,
                'order' => 0,
            );
        }

        $component = in_array( $incoming['component'] ?? '', self::COMPONENTS, true ) ? $incoming['component'] : '';

        if ( '' === $component ) {
            return null;
        }

        return array(
            'id' => $id,
            'kind' => 'component',
            'component' => $component,
            'config' => self::sanitize_component_config( $component, $incoming['config'] ?? array() ),
            'order' => 0,
        );
    }


    /**
     * Sanitize a component config according to its type contract.
     *
     * @since 6.0.0
     * @param string $component Component type.
     * @param array<string,mixed> $config Raw config.
     * @return array<string,mixed>
     */
    public static function sanitize_component_config( $component, $config ) {
        $config = is_array( $config ) ? $config : array();

        switch ( $component ) {
            case 'order_bump':
                return array(
                    'product_id' => absint( $config['product_id'] ?? 0 ),
                    'quantity' => max( 1, absint( $config['quantity'] ?? 1 ) ),
                    'headline' => sanitize_text_field( (string) ( $config['headline'] ?? '' ) ),
                    'description' => sanitize_textarea_field( (string) ( $config['description'] ?? '' ) ),
                    'image_id' => absint( $config['image_id'] ?? 0 ),
                    'discount_label' => sanitize_text_field( (string) ( $config['discount_label'] ?? '' ) ),
                    'default_checked' => ! empty( $config['default_checked'] ),
                    'highlight_color' => sanitize_hex_color( (string) ( $config['highlight_color'] ?? '' ) ) ?: '',
                );

            case 'html':
                return array(
                    'html' => wp_kses_post( (string) ( $config['html'] ?? '' ) ),
                    'variant' => in_array( $config['variant'] ?? '', array( 'banner', 'badges', 'divider', 'raw' ), true ) ? $config['variant'] : 'raw',
                    'align' => in_array( $config['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $config['align'] : 'left',
                );

            case 'coupon':
                return array(
                    'title' => sanitize_text_field( (string) ( $config['title'] ?? '' ) ),
                );

            case 'summary':
                return array(
                    'title' => sanitize_text_field( (string) ( $config['title'] ?? '' ) ),
                    'collapsible' => ! empty( $config['collapsible'] ),
                    'hide_coupon' => ! empty( $config['hide_coupon'] ),
                );

            case 'notes':
                return array(
                    'label' => sanitize_text_field( (string) ( $config['label'] ?? '' ) ),
                    'placeholder' => sanitize_text_field( (string) ( $config['placeholder'] ?? '' ) ),
                    'required' => ! empty( $config['required'] ),
                );
        }

        return array();
    }


    /**
     * Sync field step/priority into flexify_checkout_step_fields from the layout.
     *
     * Each field item's step bucket is the nearest semantic ancestor (contact -> '1',
     * shipping/payment -> '2'); fields inside a custom step inherit the nearest
     * preceding semantic bucket. Priority is a monotonic counter following layout
     * order so the legacy renderer keeps a sane in-step ordering.
     *
     * @since 6.0.0
     * @param array<string,mixed> $layout Sanitized layout.
     * @return void
     */
    public static function sync_fields_from_layout( $layout ) {
        $updates = array();
        $bucket = '1';
        $priority = 0;

        foreach ( $layout['steps'] as $step ) {
            if ( in_array( $step['type'], self::SEMANTIC_TYPES, true ) ) {
                $bucket = self::TYPE_TO_FIELD_STEP[ $step['type'] ];
            }

            foreach ( $step['items'] as $item ) {
                if ( 'field' !== $item['kind'] ) {
                    continue;
                }

                $priority += 10;

                $updates[ $item['field_id'] ] = array(
                    'step' => $bucket,
                    'priority' => (string) $priority,
                );
            }
        }

        if ( ! empty( $updates ) ) {
            // Reuses the existing field sanitization; only updates known fields.
            Fields_Store::save_fields( $updates );
        }
    }


    /**
     * Mirror the three semantic step labels into the text_check_step_N settings.
     *
     * @since 6.0.0
     * @param array<string,mixed> $layout Sanitized layout.
     * @return void
     */
    public static function mirror_step_labels( $layout ) {
        $options = get_option( 'flexify_checkout_settings', array() );

        if ( ! is_array( $options ) ) {
            return;
        }

        $changed = false;

        foreach ( $layout['steps'] as $step ) {
            if ( ! isset( self::TYPE_TO_LABEL_SETTING[ $step['type'] ] ) ) {
                continue;
            }

            $label = trim( (string) $step['label'] );

            if ( '' === $label ) {
                continue;
            }

            $key = self::TYPE_TO_LABEL_SETTING[ $step['type'] ];

            if ( ( $options[ $key ] ?? '' ) !== $label ) {
                $options[ $key ] = $label;
                $changed = true;
            }
        }

        if ( $changed ) {
            update_option( 'flexify_checkout_settings', $options );
        }
    }


    /**
     * Ensure the three semantic step types exist and payment is last.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $steps Sanitized steps.
     * @return array<int,array<string,mixed>>
     */
    private static function enforce_semantic_steps( $steps ) {
        $present = array();

        foreach ( $steps as $step ) {
            if ( in_array( $step['type'], self::SEMANTIC_TYPES, true ) ) {
                $present[ $step['type'] ] = true;
            }
        }

        // Append any missing semantic step using a default label.
        foreach ( self::SEMANTIC_TYPES as $type ) {
            if ( empty( $present[ $type ] ) ) {
                $steps[] = array(
                    'id' => self::generate_id( 'step' ),
                    'type' => $type,
                    'label' => self::default_label( $type ),
                    'enabled' => true,
                    'order' => 0,
                    'items' => self::default_items_for( $type ),
                );
            }
        }

        // Keep the payment step last so totals/gateways finalize the order.
        usort( $steps, static function ( $a, $b ) {
            $a_pay = ( 'payment' === $a['type'] ) ? 1 : 0;
            $b_pay = ( 'payment' === $b['type'] ) ? 1 : 0;

            return $a_pay <=> $b_pay;
        } );

        return $steps;
    }


    /**
     * Append any field present in step_fields but missing from the layout to the
     * step whose type matches its stored field step.
     *
     * @since 6.0.0
     * @param array<string,mixed> $layout Sanitized layout.
     * @return array<string,mixed>
     */
    private static function reconcile_orphan_fields( $layout ) {
        $fields = Fields_Store::get_fields();
        $referenced = array();

        foreach ( $layout['steps'] as $step ) {
            foreach ( $step['items'] as $item ) {
                if ( 'field' === $item['kind'] ) {
                    $referenced[ $item['field_id'] ] = true;
                }
            }
        }

        // Index semantic steps by their field bucket for quick placement.
        $bucket_step_index = array();

        foreach ( $layout['steps'] as $index => $step ) {
            if ( in_array( $step['type'], self::SEMANTIC_TYPES, true ) ) {
                $bucket = self::TYPE_TO_FIELD_STEP[ $step['type'] ];

                // First matching semantic step wins ('2' may map to shipping).
                if ( ! isset( $bucket_step_index[ $bucket ] ) ) {
                    $bucket_step_index[ $bucket ] = $index;
                }
            }
        }

        foreach ( $fields as $field_id => $field ) {
            if ( isset( $referenced[ $field_id ] ) || ! is_array( $field ) ) {
                continue;
            }

            $field_step = (string) ( $field['step'] ?? '1' );
            $target = $bucket_step_index[ $field_step ] ?? ( $bucket_step_index['1'] ?? 0 );

            $layout['steps'][ $target ]['items'][] = array(
                'id' => self::generate_id( 'it' ),
                'kind' => 'field',
                'field_id' => (string) $field_id,
                'order' => count( $layout['steps'][ $target ]['items'] ),
            );
        }

        return $layout;
    }


    /**
     * Build the default layout from the current checkout step fields.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function default_layout() {
        $fields = Fields_Store::get_fields();

        $contact_items = self::field_items_for_step( $fields, '1' );
        $shipping_items = self::field_items_for_step( $fields, '2' );

        $steps = array(
            array(
                'id' => 'step_contact',
                'type' => 'contact',
                'label' => self::default_label( 'contact' ),
                'enabled' => true,
                'order' => 0,
                'items' => $contact_items,
            ),
            array(
                'id' => 'step_shipping',
                'type' => 'shipping',
                'label' => self::default_label( 'shipping' ),
                'enabled' => true,
                'order' => 1,
                'items' => $shipping_items,
            ),
            array(
                'id' => 'step_payment',
                'type' => 'payment',
                'label' => self::default_label( 'payment' ),
                'enabled' => true,
                'order' => 2,
                'items' => self::default_items_for( 'payment' ),
            ),
        );

        return array(
            'version' => 1,
            'steps' => $steps,
        );
    }


    /**
     * Build ordered field items for a given legacy field step bucket.
     *
     * @since 6.0.0
     * @param array<string,array<string,mixed>> $fields Step fields map.
     * @param string $step Field step bucket ('1' or '2').
     * @return array<int,array<string,mixed>>
     */
    private static function field_items_for_step( $fields, $step ) {
        $matching = array();

        foreach ( $fields as $field_id => $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            if ( (string) ( $field['step'] ?? '1' ) === $step ) {
                $matching[ (string) $field_id ] = (int) ( $field['priority'] ?? 0 );
            }
        }

        asort( $matching );

        $items = array();
        $order = 0;

        foreach ( array_keys( $matching ) as $field_id ) {
            $items[] = array(
                'id' => self::generate_id( 'it' ),
                'kind' => 'field',
                'field_id' => $field_id,
                'order' => $order++,
            );
        }

        return $items;
    }


    /**
     * Default component items seeded into a freshly created semantic step.
     *
     * @since 6.0.0
     * @param string $type Step type.
     * @return array<int,array<string,mixed>>
     */
    private static function default_items_for( $type ) {
        // Only the order notes are seeded: the persistent sidebar already shows
        // the order summary + coupon, so seeding those here would duplicate them.
        // Summary/coupon blocks remain available for the operator to add inline.
        if ( 'payment' !== $type ) {
            return array();
        }

        return array(
            array(
                'id' => self::generate_id( 'it' ),
                'kind' => 'component',
                'component' => 'notes',
                'config' => self::sanitize_component_config( 'notes', array(
                    'label' => __( 'Observações do pedido', 'flexify-checkout-for-woocommerce' ),
                ) ),
                'order' => 0,
            ),
        );
    }


    /**
     * Resolve order bump product data (title, price, thumb) for each order bump.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $steps Sanitized steps.
     * @return array<int,array<string,mixed>>
     */
    private static function resolve_order_bumps( $steps ) {
        foreach ( $steps as $s => $step ) {
            foreach ( $step['items'] as $i => $item ) {
                if ( 'component' !== $item['kind'] || 'order_bump' !== ( $item['component'] ?? '' ) ) {
                    continue;
                }

                $product_id = (int) ( $item['config']['product_id'] ?? 0 );
                $product = $product_id && function_exists('wc_get_product') ? wc_get_product( $product_id ) : null;

                $steps[ $s ]['items'][ $i ]['product'] = ( $product && ! is_wp_error( $product ) ) ? array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'price_html' => $product->get_price_html(),
                    'image' => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
                    'purchasable' => $product->is_purchasable() && $product->is_in_stock(),
                ) : null;
            }
        }

        return $steps;
    }


    /**
     * Build the field catalog for the builder inspector dropdown.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function build_field_catalog() {
        $catalog = array();

        foreach ( Fields_Store::get_fields() as $field_id => $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            $catalog[] = array(
                'value' => (string) $field_id,
                'label' => (string) ( $field['label'] ?: $field_id ),
                'type' => (string) ( $field['type'] ?? 'text' ),
                'enabled' => ( $field['enabled'] ?? 'no' ) === 'yes',
            );
        }

        return $catalog;
    }


    /**
     * Default label for a semantic step, preferring the configured step text.
     *
     * @since 6.0.0
     * @param string $type Step type.
     * @return string
     */
    private static function default_label( $type ) {
        $fallbacks = array(
            'contact' => __( 'Contato', 'flexify-checkout-for-woocommerce' ),
            'shipping' => __( 'Entrega', 'flexify-checkout-for-woocommerce' ),
            'payment' => __( 'Pagamento', 'flexify-checkout-for-woocommerce' ),
        );

        if ( isset( self::TYPE_TO_LABEL_SETTING[ $type ] ) ) {
            $configured = trim( (string) Admin_Options::get_setting( self::TYPE_TO_LABEL_SETTING[ $type ] ) );

            if ( '' !== $configured ) {
                return $configured;
            }
        }

        return $fallbacks[ $type ] ?? ucfirst( $type );
    }


    /**
     * Generate a unique id with a given prefix.
     *
     * @since 6.0.0
     * @param string $prefix Id prefix (e.g. step, it).
     * @return string
     */
    public static function generate_id( $prefix = 'it' ) {
        return $prefix . '_' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 12 );
    }
}
