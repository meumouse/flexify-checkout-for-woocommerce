<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Admin\Orders;
use MeuMouse\Flexify_Checkout\Core\Helpers;

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
     * When a registered-field map is supplied, a field that is not registered in
     * WooCommerce's checkout fields filter is never treated as required.
     *
     * @since 6.0.0
     * @param array<string,array<string,mixed>> $fields Step fields map.
     * @param string $field_id Field id.
     * @param array<string,bool>|null $registered Registered field keys map, or null to skip the check.
     * @return bool
     */
    private static function is_field_required( $fields, $field_id, $registered = null ) {
        if ( is_array( $registered ) && ! isset( $registered[ (string) $field_id ] ) ) {
            return false;
        }

        return isset( $fields[ $field_id ] )
            && ( $fields[ $field_id ]['enabled'] ?? 'no' ) === 'yes'
            && ( $fields[ $field_id ]['required'] ?? 'no' ) === 'yes';
    }


    /**
     * Get the field keys actually registered in WooCommerce checkout fields.
     *
     * Mirrors the classic checkout, which renders only fields present in the
     * `woocommerce_checkout_fields` filter result. Used to reconcile the headless
     * payloads so the React checkout never exposes fields (e.g. CPF/CNPJ seeded
     * for a Brazilian store without the matching plugin) that are not actually
     * registered in WooCommerce.
     *
     * @since 6.0.0
     * @return array<string,bool>|null Map of field key => true, or null when the
     *                                 registered fields are unavailable (callers
     *                                 should then skip reconciliation).
     */
    private static function get_registered_field_keys() {
        if ( ! function_exists('WC') || ! WC() || ! WC()->checkout ) {
            return null;
        }

        $checkout_fields = WC()->checkout->get_checkout_fields();

        if ( ! is_array( $checkout_fields ) || empty( $checkout_fields ) ) {
            return null;
        }

        $keys = array();

        foreach ( $checkout_fields as $group_fields ) {
            if ( ! is_array( $group_fields ) ) {
                continue;
            }

            foreach ( $group_fields as $field_key => $field ) {
                $keys[ (string) $field_key ] = true;
            }
        }

        return $keys;
    }


    /**
     * Build the checkout config payload (gateways + field requirements).
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_checkout_config() {
        $fields = self::get_step_fields();
        $registered = self::get_registered_field_keys();
        $required = array();

        foreach ( $fields as $field_id => $field ) {
            // Skip fields not registered in WooCommerce's checkout fields filter.
            if ( is_array( $registered ) && ! isset( $registered[ (string) $field_id ] ) ) {
                continue;
            }

            if ( ( $field['enabled'] ?? 'no' ) === 'yes' && ( $field['required'] ?? 'no' ) === 'yes' ) {
                $required[] = (string) $field_id;
            }
        }

        $config = array(
            'gateways' => Gateway_Catalog::get_gateways(),
            'required_fields' => array_values( $required ),
            'cpf_required' => self::is_field_required( $fields, 'billing_cpf', $registered ),
            'birthdate_required' => self::is_field_required( $fields, 'billing_birthdate', $registered ),
            'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'BRL',
            'split_payment' => Admin_Options::get_setting('enable_payment_split') === 'yes',
            // Coupon field visibility + placement, so the React checkout mirrors the
            // classic checkout's "Coupon field position" setting (sidebar, before the
            // payment methods, or both).
            'coupon_enabled' => class_exists( Helpers::class ) ? Helpers::is_coupon_enabled() : true,
            'coupon_position' => (string) Admin_Options::get_setting('render_coupon_field_hook'),
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
        $registered = self::get_registered_field_keys();
        $normalized = array();

        foreach ( $fields as $field_id => $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            // Emit only fields registered in WooCommerce's checkout fields filter,
            // matching the classic checkout and avoiding fields registered nowhere.
            if ( is_array( $registered ) && ! isset( $registered[ (string) $field_id ] ) ) {
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
            // Field show/hide rules (person type → CPF/RG vs CNPJ/IE, …) evaluated
            // live in the React checkout (see app/src/checkout-react/lib/conditions.js).
            'conditions' => Conditions::export_field_rules_for_js(),
        );

        // Emit the visual builder layout only when the operator opted in and an
        // explicit layout exists. Otherwise React falls back to its default steps.
        if ( Admin_Options::get_setting('enable_checkout_builder') === 'yes' && \MeuMouse\Flexify_Checkout\Admin\Settings\Layout_Store::has_saved_layout() ) {
            $rules['layout'] = \MeuMouse\Flexify_Checkout\Admin\Settings\Layout_Store::get_layout_for_react();
        }

        /**
         * Filter the checkout rules payload.
         *
         * @since 6.0.0
         * @param array $rules Rules payload.
         */
        return apply_filters( 'Flexify_Checkout/Headless/Checkout_Rules', $rules );
    }


    /**
     * Build the geo data payload (allowed countries + their states).
     *
     * Feeds the modern country/state selectors in the React (Swift) checkout.
     * States are emitted only for countries that register them; a country with
     * no states is omitted, so the React state field falls back to a free-text
     * input — matching WooCommerce's own behavior.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_geo_data() {
        if ( ! function_exists('WC') || ! WC() || ! WC()->countries ) {
            return array( 'countries' => array(), 'states' => array() );
        }

        $wc_countries = WC()->countries;
        $allowed = $wc_countries->get_allowed_countries();
        $countries = array();
        $states = array();

        foreach ( $allowed as $code => $name ) {
            $countries[] = array(
                'value' => (string) $code,
                'text' => html_entity_decode( (string) $name, ENT_QUOTES ),
            );

            $country_states = $wc_countries->get_states( $code );

            if ( ! is_array( $country_states ) || empty( $country_states ) ) {
                continue;
            }

            $options = array();

            foreach ( $country_states as $state_code => $state_name ) {
                $options[] = array(
                    'value' => (string) $state_code,
                    'text' => html_entity_decode( (string) $state_name, ENT_QUOTES ),
                );
            }

            $states[ (string) $code ] = $options;
        }

        $geo = array(
            'countries' => $countries,
            'states' => $states,
        );

        /**
         * Filter the geo data payload (countries + states) for the headless checkout.
         *
         * @since 6.0.0
         * @param array $geo Geo payload.
         */
        return apply_filters( 'Flexify_Checkout/Headless/Geo_Data', $geo );
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
     * Whether the customer address book (saved addresses) is available.
     *
     * Pro-gated: requires a valid license and the operator toggle left on.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_saved_addresses_available() {
        return \MeuMouse\Flexify_Checkout\API\License::is_valid()
            && Admin_Options::get_setting('enable_saved_addresses') !== 'no';
    }


    /**
     * Resolve the payment methods display mode for the React checkout.
     *
     * Falls back to the card grid when the stored value is missing or unknown,
     * so the frontend never has to guard against an invalid layout.
     *
     * @since 6.0.0
     * @return string One of: cards, accordion.
     */
    public static function resolve_payment_methods_layout() {
        $layout = (string) Admin_Options::get_setting('payment_methods_layout');

        return in_array( $layout, array( 'cards', 'accordion' ), true ) ? $layout : 'cards';
    }


    /**
     * Build the public settings payload (operator-tunable, non-sensitive).
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_public_settings() {
        $fields = self::get_step_fields();
        $registered = self::get_registered_field_keys();

        $settings = array(
            'checkout' => array(
                'split_payment' => Admin_Options::get_setting('enable_payment_split') === 'yes',
                'cpf_required' => self::is_field_required( $fields, 'billing_cpf', $registered ),
                'birthdate_required' => self::is_field_required( $fields, 'billing_birthdate', $registered ),
                'payment_methods_layout' => self::resolve_payment_methods_layout(),
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


    /**
     * Build the thank-you (order-received) payload for the React checkout.
     *
     * Carries the order data the Swift thank-you page renders, plus the captured
     * HTML of the WooCommerce thank-you hooks so third-party integrations (payment
     * instructions, tracking pixels, etc.) still run. The hooks fire here, once,
     * via output buffering — the React template replaces WooCommerce's own
     * thankyou.php, so they would not run otherwise.
     *
     * @since 6.0.0
     * @param \WC_Order $order Order object.
     * @return array<string,mixed>|null
     */
    public static function get_thankyou_data( $order ) {
        if ( ! ( $order instanceof \WC_Order ) ) {
            return null;
        }

        $order_id = $order->get_id();
        $has_shipping = function_exists('order_has_shipping_method') ? order_has_shipping_method( $order ) : $order->needs_shipping_address();

        // Items.
        $items = array();

        foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
            $product = $item->get_product();

            if ( ! $product instanceof \WC_Product ) {
                continue;
            }

            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '';

            if ( empty( $image_url ) && function_exists('wc_placeholder_img_src') ) {
                $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
            }

            $items[] = array(
                'name' => $product->get_name(),
                'meta_html' => wc_display_item_meta( $item, array( 'echo' => false ) ),
                'quantity' => (int) $item->get_quantity(),
                'price_html' => $order->get_formatted_line_subtotal( $item ),
                'image' => $image_url,
            );
        }

        // Totals (subtotal / shipping / tax / total ...).
        $totals = array();

        foreach ( $order->get_order_item_totals() as $key => $total ) {
            if ( 'payment_method' === $key ) {
                continue;
            }

            $totals[] = array(
                'key' => (string) $key,
                'label' => trim( (string) $total['label'], ':' ),
                'value_html' => (string) $total['value'],
            );
        }

        // Shipping / billing address.
        $ship_different = get_post_meta( $order_id, '_flexify_ship_different_address', true ) === 'yes';
        $address = ( $has_shipping || $ship_different ) ? $order->get_formatted_shipping_address() : '';

        if ( empty( $address ) ) {
            $address = $order->get_formatted_billing_address();
        }

        $ship_name = trim( $order->get_formatted_shipping_full_name() );

        if ( empty( $ship_name ) ) {
            $ship_name = trim( $order->get_formatted_billing_full_name() );
        }

        // Help links.
        $contact_page = Admin_Options::get_setting('contact_page_thankyou');
        $support_url = '';

        if ( ! empty( $contact_page ) ) {
            $support_url = $contact_page !== 'custom_link' ? get_permalink( $contact_page ) : Admin_Options::get_setting('contact_page_thankyou_custom_link');
        }

        // Downloadable items table (rendered by WooCommerce's own template).
        $downloads_html = '';

        if ( $order->has_downloadable_item() && $order->is_download_permitted() ) {
            ob_start();
            Thankyou::downloads( $order );
            $downloads_html = ob_get_clean();
        }

        // Capture the WooCommerce thank-you hooks. They fire here once; the React
        // template replaces WooCommerce's thankyou.php, so this is the single call.
        $hooks = self::capture_thankyou_hooks( $order );

        $received_text = apply_filters(
            'woocommerce_thankyou_order_received_text',
            esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ),
            $order
        );

        // Confetti palette (mirrors the server-rendered Swift thank-you).
        $colors = apply_filters( 'flexify_checkout_thankyou_confetti_colors', array(
            Admin_Options::get_setting('set_primary_color') ?: '#22c55e',
            '#6366f1',
            '#f59e0b',
            '#ec4899',
            '#06b6d4',
            '#a855f7',
        ), $order );

        $colors = array_values( array_filter( array_map( 'sanitize_hex_color', (array) $colors ) ) );

        $data = array(
            'order_id' => $order_id,
            'order_key' => $order->get_order_key(),
            'order_number' => Thankyou::get_order_number( $order ),
            'first_name' => $order->get_billing_first_name(),
            'email' => $order->get_billing_email(),
            'status' => $order->get_status(),
            'needs_shipping' => (bool) $has_shipping,
            'received_text' => $received_text,
            'items' => $items,
            'totals' => $totals,
            'shipping' => array(
                'name' => $ship_name,
                'address_html' => $address,
                'method' => Orders::get_order_shipping_methods( $order ),
            ),
            'estimated_delivery' => Thankyou::get_estimated_delivery( $order ),
            'progress' => Thankyou::get_progress_steps( $order ),
            'view_order_url' => $order->get_view_order_url(),
            'support_url' => $support_url,
            'downloads_html' => $downloads_html,
            'hooks' => $hooks,
            'confetti' => array(
                'enabled' => (bool) apply_filters( 'flexify_checkout_thankyou_confetti', true, $order ),
                'colors' => ! empty( $colors ) ? $colors : array( '#22c55e' ),
            ),
        );

        /**
         * Filter the React thank-you payload.
         *
         * @since 6.0.0
         * @param array     $data  Thank-you payload.
         * @param \WC_Order $order Order object.
         */
        return apply_filters( 'Flexify_Checkout/Headless/Thankyou_Data', $data, $order );
    }


    /**
     * Fire and capture the WooCommerce thank-you hooks as HTML strings.
     *
     * @since 6.0.0
     * @param \WC_Order $order Order object.
     * @return array<string,string>
     */
    private static function capture_thankyou_hooks( $order ) {
        $order_id = $order->get_id();
        $payment_method = $order->get_payment_method();

        ob_start();
        do_action( 'woocommerce_before_thankyou', $order_id );
        $before = ob_get_clean();

        ob_start();

        if ( $payment_method ) {
            do_action( 'woocommerce_thankyou_' . $payment_method, $order_id );
        }

        $payment = ob_get_clean();

        ob_start();
        do_action( 'woocommerce_thankyou', $order_id );
        $thankyou = ob_get_clean();

        return array(
            'before' => $before,
            'payment' => $payment,
            'thankyou' => $thankyou,
        );
    }
}
