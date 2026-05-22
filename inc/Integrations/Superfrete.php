<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with SuperFrete 3.3.x.
 *
 * @since 5.5.0

 * @author MeuMouse.com
 */
class Superfrete {

    /**
     * SuperFrete required fields map.
     *
     * @since 5.5.0
     * @var array
     */
    const REQUIRED_FIELDS = array(
        'billing_number' => array(
            'type' => 'text',
            'label' => 'Numero',
            'step' => '2',
            'position' => 'right',
            'priority' => '17',
            'required' => 'yes',
        ),
        'billing_neighborhood' => array(
            'type' => 'text',
            'label' => 'Bairro',
            'step' => '2',
            'position' => 'left',
            'priority' => '18',
            'required' => 'yes',
        ),
        'shipping_number' => array(
            'type' => 'text',
            'label' => 'Numero',
            'required' => 'yes',
            'priority' => 70,
        ),
        'shipping_neighborhood' => array(
            'type' => 'text',
            'label' => 'Bairro',
            'required' => 'yes',
            'priority' => 80,
        ),
        'billing_document' => array(
            'type' => 'text',
            'label' => 'Documento',
            'step' => '1',
            'position' => 'full',
            'priority' => '13',
            'required' => 'yes',
        ),
    );


    /**
     * Construct function.
     *
     * @since 5.5.0
     * @return void
     */
    public function __construct() {
        add_action( 'wp', array( $this, 'remove_legacy_actions' ), 20 );
        add_filter( 'woocommerce_checkout_fields', array( $this, 'ensure_checkout_fields' ), 250 );
        add_filter( 'Flexify_Checkout/Assets/Script_Data', array( $this, 'append_script_flags' ) );
        add_action( 'init', array( $this, 'ensure_step_fields_registry' ), 30 );
    }


    /**
     * Check if SuperFrete is active.
     *
     * @since 5.5.0
     * @return bool
     */
    public static function is_active() {
        return class_exists('SuperfreteShipping') || class_exists('SuperFrete\\App\\Controllers\\CheckoutFields');
    }


    /**
     * Remove old SuperFrete action that can duplicate shipping rows.
     *
     * @since 5.5.0
     * @return void
     */
    public function remove_legacy_actions() {
        if ( ! is_flexify_checkout() || ! self::is_active() ) {
            return;
        }

        global $wp_filter;

        if ( isset( $wp_filter['woocommerce_review_order_before_order_total']->callbacks ) ) {
            foreach ( $wp_filter['woocommerce_review_order_before_order_total']->callbacks as $priority => $hooks ) {
                foreach ( $hooks as $hook ) {
                    if ( is_array( $hook['function'] ) && is_a( $hook['function'][0], 'SuperfreteShipping' ) && $hook['function'][1] === 'superfrete_get_shippings_to_cart' ) {
                        remove_action( 'woocommerce_review_order_before_order_total', array( $hook['function'][0], $hook['function'][1] ), $priority );
                    }
                }
            }
        }
    }


    /**
     * Ensure required SuperFrete fields exist and are required in checkout.
     *
     * @since 5.5.0
     * @param array $fields Checkout fields.
     * @return array
     */
    public function ensure_checkout_fields( $fields ) {
        if ( ! self::is_active() ) {
            return $fields;
        }

        foreach ( array( 'billing_number', 'billing_neighborhood', 'billing_document' ) as $field_id ) {
            if ( isset( $fields['billing'][ $field_id ] ) ) {
                $fields['billing'][ $field_id ]['required'] = true;
                $fields['billing'][ $field_id ]['class'][] = 'required';
                $fields['billing'][ $field_id ]['class'][] = 'validate-required';
            }
        }

        foreach ( array( 'shipping_number', 'shipping_neighborhood' ) as $field_id ) {
            if ( isset( $fields['shipping'][ $field_id ] ) ) {
                $fields['shipping'][ $field_id ]['required'] = true;
                $fields['shipping'][ $field_id ]['class'][] = 'required';
                $fields['shipping'][ $field_id ]['class'][] = 'validate-required';
                continue;
            }

            $fields['shipping'][ $field_id ] = array(
                'type' => 'text',
                'label' => $field_id === 'shipping_number' ? esc_html__( 'Numero', 'flexify-checkout-for-woocommerce' ) : esc_html__( 'Bairro', 'flexify-checkout-for-woocommerce' ),
                'required' => true,
                'class' => array( $field_id === 'shipping_number' ? 'row-last' : 'row-first', 'required', 'validate-required' ),
                'priority' => $field_id === 'shipping_number' ? 70 : 80,
            );
        }

        foreach ( array( 'billing_number', 'billing_neighborhood' ) as $field_id ) {
            if ( ! isset( $fields['billing'][ $field_id ] ) ) {
                $fields['billing'][ $field_id ] = array(
                    'type' => 'text',
                    'label' => $field_id === 'billing_number' ? esc_html__( 'Numero', 'flexify-checkout-for-woocommerce' ) : esc_html__( 'Bairro', 'flexify-checkout-for-woocommerce' ),
                    'required' => true,
                    'class' => array( $field_id === 'billing_number' ? 'row-last' : 'row-first', 'required', 'validate-required' ),
                    'priority' => $field_id === 'billing_number' ? 110 : 115,
                );
            }
        }

        // If billing_document is not provided by SuperFrete, create it with a safe default.
        if ( ! isset( $fields['billing']['billing_document'] ) ) {
            $fields['billing']['billing_document'] = array(
                'type' => 'text',
                'label' => esc_html__( 'Documento', 'flexify-checkout-for-woocommerce' ),
                'required' => true,
                'class' => array( 'form-row-wide', 'required', 'validate-required' ),
                'priority' => 72,
            );
        }

        return $fields;
    }


    /**
     * Append SuperFrete flags to frontend script data.
     *
     * @since 5.5.0
     * @param array $params Script params.
     * @return array
     */
    public function append_script_flags( $params ) {
        $params['superfrete_active'] = self::is_active() ? 'yes' : 'no';

        return $params;
    }


    /**
     * Ensure Flexify step fields include SuperFrete required fields metadata.
     *
     * @since 5.5.0
     * @return void
     */
    public function ensure_step_fields_registry() {
        if ( ! self::is_active() ) {
            return;
        }

        $step_fields = maybe_unserialize( get_option( 'flexify_checkout_step_fields', array() ) );

        if ( ! is_array( $step_fields ) ) {
            return;
        }

        $updated = false;

        foreach ( self::REQUIRED_FIELDS as $field_id => $config ) {
            // shipping_* does not belong to the step manager list.
            if ( strpos( $field_id, 'shipping_' ) === 0 ) {
                continue;
            }

            if ( ! isset( $step_fields[ $field_id ] ) ) {
                $step_fields[ $field_id ] = array(
                    'id' => $field_id,
                    'type' => $config['type'],
                    'label' => $config['label'],
                    'position' => $config['position'],
                    'classes' => '',
                    'label_classes' => '',
                    'required' => $config['required'],
                    'priority' => $config['priority'],
                    'source' => 'plugin',
                    'enabled' => 'yes',
                    'step' => $config['step'],
                );
                $updated = true;
                continue;
            }

            $step_fields[ $field_id ]['required'] = 'yes';
            $step_fields[ $field_id ]['enabled'] = 'yes';
            $step_fields[ $field_id ]['step'] = isset( $config['step'] ) ? $config['step'] : $step_fields[ $field_id ]['step'];
            $step_fields[ $field_id ]['position'] = isset( $config['position'] ) ? $config['position'] : $step_fields[ $field_id ]['position'];
            $step_fields[ $field_id ]['priority'] = isset( $config['priority'] ) ? $config['priority'] : $step_fields[ $field_id ]['priority'];
            $updated = true;
        }

        if ( $updated ) {
            update_option( 'flexify_checkout_step_fields', maybe_serialize( $step_fields ) );
        }
    }
}

