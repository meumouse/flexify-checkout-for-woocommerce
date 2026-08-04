<?php

namespace MeuMouse\Flexify_Checkout\Admin;

use MeuMouse\Flexify_Checkout\Admin\Settings\Fields_Store;
use MeuMouse\Flexify_Checkout\Admin\Settings\Conditions_Store;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Keep the native Brazilian checkout fields and their person-type conditions in
 * sync with the operator settings (WCBCF parity).
 *
 * The granular settings (person type mode, RG/IE/birthdate/gender toggles, cell
 * phone mode, neighborhood requirement, CPF/CNPJ validation) are the single
 * source of truth; on every settings save this service seeds/enables/disables
 * the matching keys in the flexify_checkout_step_fields registry and seeds or
 * prunes the person-type show conditions (flexify_checkout_conditions, legacy
 * shape — normalized on read by Conditions_Store). Those registries already
 * drive both the classic checkout (Checkout\Fields / Checkout\Conditions) and
 * the React payload (Checkout\Headless_Data).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin
 * @author MeuMouse.com
 */
class Brazilian_Fields_Setup {

    /**
     * Brazilian field keys managed by this service.
     *
     * @since 6.0.0
     * @var string[]
     */
    const MANAGED_FIELDS = array(
        'billing_persontype', 'billing_cpf', 'billing_cnpj', 'billing_ie',
        'billing_cellphone', 'billing_rg', 'billing_birthdate', 'billing_gender',
        'billing_number', 'billing_neighborhood',
    );

    /**
     * Construct function
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        // Re-sync whenever the plugin settings are saved (Vue admin REST / update_option).
        add_action( 'update_option_flexify_checkout_settings', array( __CLASS__, 'on_settings_saved' ), 20 );
    }


    /**
     * Sync after a settings save when the master toggle is on.
     *
     * @since 6.0.0
     * @return void
     */
    public static function on_settings_saved() {
        if ( 'yes' === Admin_Options::get_setting('enable_native_brazilian_fields') ) {
            self::sync();
        }
    }


    /**
     * Whether the Brazilian Market on WooCommerce plugin is active.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_wcbcf_active() {
        return class_exists('Extra_Checkout_Fields_For_Brazil_Front_End')
            || class_exists('Extra_Checkout_Fields_For_Brazil');
    }


    /**
     * Reconcile the step-fields registry and conditions with the settings.
     *
     * @since 6.0.0
     * @return void
     */
    public static function sync() {
        $fields = Fields_Store::get_fields();
        $defs = Default_Options::get_brazilian_checkout_fields();
        $native = ( new Default_Options() )->get_native_checkout_fields();

        $mode = (string) Admin_Options::get_setting('brazilian_person_type_mode');
        $show_rg = 'yes' === Admin_Options::get_setting('brazilian_show_rg');
        $show_ie = 'yes' === Admin_Options::get_setting('brazilian_show_ie');
        $show_birthdate = 'yes' === Admin_Options::get_setting('brazilian_show_birthdate');
        $show_gender = 'yes' === Admin_Options::get_setting('brazilian_show_gender');
        $cellphone_mode = (string) Admin_Options::get_setting('brazilian_cellphone_mode');
        $neighborhood_required = 'yes' === Admin_Options::get_setting('brazilian_neighborhood_required');

        // Address extras: always present. Number required; neighborhood per setting.
        self::set_field( $fields, $defs, 'billing_number', true, true );
        self::set_field( $fields, $defs, 'billing_neighborhood', true, $neighborhood_required );

        // Person type flow.
        switch ( $mode ) {
            case 'individual':
                self::set_field( $fields, $defs, 'billing_persontype', false );
                self::set_field( $fields, $defs, 'billing_cpf', true, true );
                self::set_field( $fields, $defs, 'billing_cnpj', false );
                break;

            case 'legal':
                self::set_field( $fields, $defs, 'billing_persontype', false );
                self::set_field( $fields, $defs, 'billing_cpf', false );
                self::set_field( $fields, $defs, 'billing_cnpj', true, true );
                self::ensure_company( $fields, $native, true );
                break;

            case 'none':
                self::set_field( $fields, $defs, 'billing_persontype', false );
                self::set_field( $fields, $defs, 'billing_cpf', true, false );
                self::set_field( $fields, $defs, 'billing_cnpj', true, false );
                break;

            case 'both':
            default:
                self::set_field( $fields, $defs, 'billing_persontype', true, false );
                self::set_field( $fields, $defs, 'billing_cpf', true, true );
                self::set_field( $fields, $defs, 'billing_cnpj', true, true );
                self::ensure_company( $fields, $native, true );
                break;
        }

        // RG applies to individuals; IE to legal entities.
        self::set_field( $fields, $defs, 'billing_rg', $show_rg && 'legal' !== $mode, false );
        self::set_field( $fields, $defs, 'billing_ie', $show_ie && 'individual' !== $mode, false );

        // Birthdate / gender are required when shown (mirrors WCBCF).
        self::set_field( $fields, $defs, 'billing_birthdate', $show_birthdate, $show_birthdate );

        $gender_extra = array();

        if ( $show_gender && empty( $fields['billing_gender']['options'] ) && empty( $defs['billing_gender']['options'] ) ) {
            $gender_extra['options'] = self::default_gender_options();
        }

        self::set_field( $fields, $defs, 'billing_gender', $show_gender, $show_gender, $gender_extra );

        // Cell phone.
        if ( 'disable' === $cellphone_mode ) {
            self::set_field( $fields, $defs, 'billing_cellphone', false );
        } else {
            self::set_field( $fields, $defs, 'billing_cellphone', true, 'required' === $cellphone_mode );
        }

        update_option( Fields_Store::OPTION_NAME, maybe_serialize( $fields ) );

        // Person-type conditions only make sense in the selectable ("both") mode.
        if ( 'both' === $mode ) {
            self::seed_person_type_conditions( $show_rg, $show_ie );
        } else {
            self::remove_person_type_conditions();
        }
    }


    /**
     * Upsert a managed field from its default definition and set its flags.
     *
     * @since 6.0.0
     * @param array<string,array<string,mixed>> $fields Step-fields registry (by ref).
     * @param array<string,array<string,mixed>> $defs Brazilian field definitions.
     * @param string $id Field id.
     * @param bool $enabled Whether the field is enabled.
     * @param bool|null $required Whether the field is required, or null to leave as-is.
     * @param array<string,mixed> $extra Extra props to set on the field.
     * @return void
     */
    private static function set_field( &$fields, $defs, $id, $enabled, $required = null, $extra = array() ) {
        if ( ! isset( $fields[ $id ] ) ) {
            if ( ! isset( $defs[ $id ] ) ) {
                return;
            }

            $fields[ $id ] = $defs[ $id ];
        }

        $fields[ $id ]['enabled'] = $enabled ? 'yes' : 'no';

        if ( null !== $required ) {
            $fields[ $id ]['required'] = $required ? 'yes' : 'no';
        }

        foreach ( $extra as $key => $value ) {
            $fields[ $id ][ $key ] = $value;
        }
    }


    /**
     * Ensure the native company field exists and matches the enabled flag.
     *
     * @since 6.0.0
     * @param array<string,array<string,mixed>> $fields Step-fields registry (by ref).
     * @param array<string,array<string,mixed>> $native Native field definitions.
     * @param bool $enabled Whether to enable the company field.
     * @return void
     */
    private static function ensure_company( &$fields, $native, $enabled ) {
        if ( ! isset( $fields['billing_company'] ) && isset( $native['billing_company'] ) ) {
            $fields['billing_company'] = $native['billing_company'];
        }

        if ( isset( $fields['billing_company'] ) ) {
            $fields['billing_company']['enabled'] = $enabled ? 'yes' : 'no';
        }
    }


    /**
     * Default gender options used when enabling the gender field.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function default_gender_options() {
        return array(
            array( 'value' => '', 'text' => __( 'Prefer not to say', 'flexify-checkout-for-woocommerce' ) ),
            array( 'value' => 'female', 'text' => __( 'Female', 'flexify-checkout-for-woocommerce' ) ),
            array( 'value' => 'male', 'text' => __( 'Male', 'flexify-checkout-for-woocommerce' ) ),
            array( 'value' => 'other', 'text' => __( 'Other', 'flexify-checkout-for-woocommerce' ) ),
        );
    }


    /**
     * Seed the person-type show conditions (idempotent).
     *
     * Works in the current rule shape (action + groups). Reading through
     * Conditions_Store::get_rules() first normalizes any legacy rows, so the
     * dedup below never adds duplicates regardless of prior migration state.
     *
     * @since 6.0.0
     * @param bool $show_rg Whether the RG condition should be seeded.
     * @param bool $show_ie Whether the IE condition should be seeded.
     * @return void
     */
    private static function seed_person_type_conditions( $show_rg, $show_ie ) {
        $rules = Conditions_Store::get_rules();

        // field id => person type value that reveals it.
        $seed = array(
            'billing_cpf' => '1',
            'billing_cnpj' => '2',
            'billing_company' => '2',
        );

        if ( $show_rg ) {
            $seed['billing_rg'] = '1';
        }

        if ( $show_ie ) {
            $seed['billing_ie'] = '2';
        }

        foreach ( $seed as $field => $value ) {
            if ( self::has_person_type_rule( $rules, $field, $value ) ) {
                continue;
            }

            Conditions_Store::add_rule( array(
                'enabled' => true,
                'name' => sprintf( 'Person type → %s', $field ),
                'action' => array( 'type' => 'show', 'component' => 'field', 'field' => $field ),
                'match' => 'all',
                'groups' => array(
                    array(
                        'match' => 'all',
                        'conditions' => array(
                            array( 'subject' => 'field', 'field' => 'billing_persontype', 'operator' => 'is', 'value' => $value ),
                        ),
                    ),
                ),
            ) );

            // Keep our local copy in sync so a later seed in the same run dedupes.
            $rules = Conditions_Store::get_rules();
        }
    }


    /**
     * Remove any person-type-driven show conditions targeting the managed fields.
     *
     * @since 6.0.0
     * @return void
     */
    private static function remove_person_type_conditions() {
        $rules = Conditions_Store::get_rules();

        if ( empty( $rules ) ) {
            return;
        }

        $targets = array( 'billing_cpf', 'billing_cnpj', 'billing_company', 'billing_rg', 'billing_ie' );
        $kept = array();

        foreach ( $rules as $rule ) {
            $action = $rule['action'] ?? array();

            $is_person_type_rule = ( $action['component'] ?? '' ) === 'field'
                && in_array( $action['field'] ?? '', $targets, true )
                && self::rule_has_person_type_condition( $rule );

            if ( ! $is_person_type_rule ) {
                $kept[] = $rule;
            }
        }

        if ( count( $kept ) !== count( $rules ) ) {
            Conditions_Store::save_rules( $kept );
        }
    }


    /**
     * Whether a "show $field when billing_persontype is $value" rule already exists.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $rules Stored rules (new shape).
     * @param string $field Target field id.
     * @param string $value Person type value.
     * @return bool
     */
    private static function has_person_type_rule( $rules, $field, $value ) {
        foreach ( $rules as $rule ) {
            $action = $rule['action'] ?? array();

            if ( ( $action['component'] ?? '' ) !== 'field'
                || ( $action['field'] ?? '' ) !== $field
                || ( $action['type'] ?? '' ) !== 'show' ) {
                continue;
            }

            foreach ( (array) ( $rule['groups'] ?? array() ) as $group ) {
                foreach ( (array) ( $group['conditions'] ?? array() ) as $condition ) {
                    if ( ( $condition['subject'] ?? '' ) === 'field'
                        && ( $condition['field'] ?? '' ) === 'billing_persontype'
                        && ( $condition['operator'] ?? '' ) === 'is'
                        && (string) ( $condition['value'] ?? '' ) === (string) $value ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }


    /**
     * Whether a rule has any condition against the billing_persontype field.
     *
     * @since 6.0.0
     * @param array<string,mixed> $rule Rule (new shape).
     * @return bool
     */
    private static function rule_has_person_type_condition( $rule ) {
        foreach ( (array) ( $rule['groups'] ?? array() ) as $group ) {
            foreach ( (array) ( $group['conditions'] ?? array() ) as $condition ) {
                if ( ( $condition['subject'] ?? '' ) === 'field' && ( $condition['field'] ?? '' ) === 'billing_persontype' ) {
                    return true;
                }
            }
        }

        return false;
    }
}
