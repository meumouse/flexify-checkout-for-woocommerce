<?php

namespace MeuMouse\Flexify_Checkout\Admin;

use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle export and import of plugin settings as JSON.
 *
 * Exports only the configuration option groups (general settings, checkout
 * step fields and conditions), deliberately leaving out license, cache and
 * runtime state so a snapshot can be safely moved between installations.
 *
 * @since 5.5.4
 * @author MeuMouse.com
 */
class Settings_Import_Export {

    /**
     * Nonce action shared by the export and import requests.
     *
     * @since 5.5.4
     * @var string
     */
    const NONCE_ACTION = 'flexify_checkout_import_export';

    /**
     * Format identifier written into exported files for forward validation.
     *
     * @since 5.5.4
     * @var string
     */
    const FORMAT = 'flexify-checkout-settings';

    /**
     * Option groups included in the export/import snapshot.
     *
     * The 'serialized' flag marks options stored through maybe_serialize() so
     * they are unserialized before export and re-serialized on import.
     *
     * @since 5.5.4
     * @var array<string,array{serialized:bool}>
     */
    const OPTION_GROUPS = array(
        'flexify_checkout_settings'   => array( 'serialized' => false ),
        'flexify_checkout_step_fields' => array( 'serialized' => true ),
        'flexify_checkout_conditions' => array( 'serialized' => false ),
    );

    /**
     * Construct function
     *
     * @since 5.5.4
     * @return void
     */
    public function __construct() {
        add_action( 'wp_ajax_flexify_checkout_export_settings', array( $this, 'export_settings_callback' ) );
        add_action( 'wp_ajax_flexify_checkout_import_settings', array( $this, 'import_settings_callback' ) );
    }


    /**
     * Build the export payload from the current configuration options.
     *
     * @since 5.5.4
     * @return array
     */
    public function build_payload() {
        $data = array();

        foreach ( self::OPTION_GROUPS as $option => $args ) {
            $value = get_option( $option, array() );

            if ( $args['serialized'] ) {
                $value = maybe_unserialize( $value );
            }

            $data[ $option ] = $value;
        }

        return array(
            'format'  => self::FORMAT,
            'version' => defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '',
            'site_url' => home_url(),
            'exported_at' => current_time('mysql'),
            'data' => $data,
        );
    }


    /**
     * Stream the configuration as a downloadable JSON file.
     *
     * @since 5.5.4
     * @return void
     */
    public function export_settings_callback() {
        if ( ! current_user_can('manage_woocommerce') ) {
            wp_die( esc_html__( 'Você não tem permissão para exportar as configurações.', 'flexify-checkout-for-woocommerce' ), '', array( 'response' => 403 ) );
        }

        $nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            wp_die( esc_html__( 'Verificação de segurança falhou. Recarregue a página e tente novamente.', 'flexify-checkout-for-woocommerce' ), '', array( 'response' => 403 ) );
        }

        $payload = $this->build_payload();
        $json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

        $site_slug = sanitize_title( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'site' );
        $filename = sprintf( 'flexify-checkout-settings-%s-%s.json', $site_slug, gmdate('Y-m-d') );

        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $json ) );

        echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw JSON file body.

        exit;
    }


    /**
     * Apply an imported settings snapshot, overwriting the current option groups.
     *
     * @since 5.5.4
     * @param array $data Map of option name => value from a valid payload.
     * @return bool True when at least one option group was written.
     */
    public function apply_payload( $data ) {
        $applied = false;

        foreach ( self::OPTION_GROUPS as $option => $args ) {
            if ( ! array_key_exists( $option, $data ) ) {
                continue;
            }

            $value = $data[ $option ];

            // Skip Pro-only field overrides for sites without an active license so
            // an imported snapshot can't silently unlock gated configuration.
            if ( $option === 'flexify_checkout_step_fields' && ! License::is_valid() ) {
                continue;
            }

            if ( $args['serialized'] ) {
                $value = maybe_serialize( $value );
            }

            update_option( $option, $value );
            $applied = true;
        }

        return $applied;
    }


    /**
     * Validate and import a settings snapshot sent from the admin UI.
     *
     * @since 5.5.4
     * @return void
     */
    public function import_settings_callback() {
        if ( ! current_user_can('manage_woocommerce') ) {
            wp_send_json( array(
                'status' => 'error',
                'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ),
                'toast_body_title' => esc_html__( 'Você não tem permissão para importar as configurações.', 'flexify-checkout-for-woocommerce' ),
            ) );
        }

        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
            wp_send_json( array(
                'status' => 'error',
                'toast_header_title' => esc_html__( 'Ops! Ocorreu um erro.', 'flexify-checkout-for-woocommerce' ),
                'toast_body_title' => esc_html__( 'Verificação de segurança falhou. Recarregue a página e tente novamente.', 'flexify-checkout-for-woocommerce' ),
            ) );
        }

        // The payload arrives as a raw JSON string; unslash before decoding so
        // escaped quotes added by WordPress don't break the parse.
        $raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '';
        $payload = json_decode( $raw, true );

        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $payload ) ) {
            wp_send_json( array(
                'status' => 'error',
                'toast_header_title' => esc_html__( 'Arquivo inválido', 'flexify-checkout-for-woocommerce' ),
                'toast_body_title' => esc_html__( 'Não foi possível ler o arquivo. Verifique se é um JSON de configurações válido.', 'flexify-checkout-for-woocommerce' ),
            ) );
        }

        if ( ! isset( $payload['format'] ) || $payload['format'] !== self::FORMAT || ! isset( $payload['data'] ) || ! is_array( $payload['data'] ) ) {
            wp_send_json( array(
                'status' => 'error',
                'toast_header_title' => esc_html__( 'Arquivo incompatível', 'flexify-checkout-for-woocommerce' ),
                'toast_body_title' => esc_html__( 'Este arquivo não é uma exportação de configurações do Flexify Checkout.', 'flexify-checkout-for-woocommerce' ),
            ) );
        }

        $applied = $this->apply_payload( $payload['data'] );

        if ( $applied ) {
            wp_send_json( array(
                'status' => 'success',
                'toast_header_title' => esc_html__( 'Importado com sucesso', 'flexify-checkout-for-woocommerce' ),
                'toast_body_title' => esc_html__( 'As configurações foram importadas com sucesso!', 'flexify-checkout-for-woocommerce' ),
            ) );
        }

        wp_send_json( array(
            'status' => 'error',
            'toast_header_title' => esc_html__( 'Nada para importar', 'flexify-checkout-for-woocommerce' ),
            'toast_body_title' => esc_html__( 'O arquivo não continha configurações aplicáveis.', 'flexify-checkout-for-woocommerce' ),
        ) );
    }
}
