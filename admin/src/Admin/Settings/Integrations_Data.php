<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\Admin\Settings\Views\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Build the integrations cards payload for the Vue settings app.
 *
 * Plugin-owned cards are described as structured data; cards registered by
 * third parties through the Flexify_Checkout/Admin/Settings/Integrations/Cards
 * filter are captured as raw HTML so existing extensions keep rendering.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Integrations_Data {

    /**
     * Build the cards list for the client.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_cards_for_client() {
        if ( ! function_exists('get_plugins') ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $cards = array(
            array(
                'id' => 'tracking-platforms',
                'type' => 'tracking',
                'priority' => 1,
                'pro' => true,
                'title' => __( 'Rastreamento de dados', 'flexify-checkout-for-woocommerce' ),
                'description' => __( 'Configure o GA4, Google Ads e Meta para envio de dados de eventos coletados no checkout.', 'flexify-checkout-for-woocommerce' ),
            ),
            self::module_card( array(
                'id' => 'inter-bank',
                'priority' => 10,
                'pro' => true,
                'title' => __( 'Flexify Checkout - Inter addon', 'flexify-checkout-for-woocommerce' ),
                'description' => __( 'Comece a receber via Pix e Boleto com aprovação imediata e sem cobrança de taxas. Exclusivo para clientes Inter Empresas.', 'flexify-checkout-for-woocommerce' ),
                'slug' => 'module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php',
                'download_url' => 'https://github.com/meumouse/module-inter-bank-for-flexify-checkout/raw/main/dist/module-inter-bank-for-flexify-checkout.zip',
                'settings_url' => admin_url('admin.php?page=flexify-checkout-for-woocommerce&legacy=1#integrations'),
            ) ),
            self::module_card( array(
                'id' => 'recovery-carts',
                'priority' => 20,
                'pro' => true,
                'title' => __( 'Flexify Checkout - Recuperação de carrinhos abandonados', 'flexify-checkout-for-woocommerce' ),
                'description' => __( 'Recupere carrinhos e pedidos abandonados com follow up cadenciado. Envie notificações via WhatsApp de forma automática e muito mais!', 'flexify-checkout-for-woocommerce' ),
                'slug' => 'flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php',
                'download_url' => 'https://github.com/meumouse/flexify-checkout-recovery-carts-addon/raw/refs/heads/main/dist/flexify-checkout-recovery-carts-addon.zip',
                'settings_url' => admin_url('admin.php?page=fc-recovery-carts-settings'),
            ) ),
            self::module_card( array(
                'id' => 'joinotify',
                'priority' => 30,
                'pro' => false,
                'title' => __( 'Joinotify', 'flexify-checkout-for-woocommerce' ),
                'description' => __( 'Automatize o envio de mensagens via WhatsApp ao receber eventos no Flexify Checkout, recupere vendas de maneira mais assertiva. Deixe tudo no fluxo certo sem esforço!', 'flexify-checkout-for-woocommerce' ),
                'slug' => 'joinotify/joinotify.php',
                'download_url' => 'https://github.com/meumouse/joinotify/raw/refs/heads/main/dist/joinotify.zip',
                'settings_url' => admin_url('admin.php?page=joinotify-settings'),
            ) ),
            array(
                'id' => 'google-maps',
                'type' => 'soon',
                'priority' => 40,
                'pro' => false,
                'title' => __( 'Google Maps', 'flexify-checkout-for-woocommerce' ),
                'description' => __( 'Facilite o preenchimento do endereço de entrega aos usuários, permitindo pesquisar seu endereço ou usando recursos de geolocalização com Google Maps.', 'flexify-checkout-for-woocommerce' ),
            ),
            self::module_card( array(
                'id' => 'payco',
                'priority' => 50,
                'pro' => false,
                'title' => __( 'Payments by Payco', 'flexify-checkout-for-woocommerce' ),
                'description' => __( 'Receba pagamentos com Pix, Cartão de Crédito, Open Finance e Boleto Bancário.', 'flexify-checkout-for-woocommerce' ),
                'slug' => 'virtuaria-payments-by-payco/virtuaria-payments-by-payco.php',
                'download_url' => 'https://downloads.wordpress.org/plugin/virtuaria-payments-by-payco.zip',
                'settings_url' => admin_url('admin.php?page=virtuaria_payments_payco'),
            ) ),
        );

        $cards = array_merge( $cards, self::get_third_party_cards() );

        usort( $cards, static function ( $a, $b ) {
            return intval( $a['priority'] ?? 10 ) <=> intval( $b['priority'] ?? 10 );
        } );

        return $cards;
    }


    /**
     * Build a module card with its current installation state.
     *
     * @since 6.0.0
     * @param array<string,mixed> $card Card definition with slug/download_url/settings_url.
     * @return array<string,mixed>
     */
    private static function module_card( $card ) {
        $slug = $card['slug'];

        if ( is_plugin_active( $slug ) ) {
            $state = 'active';
        } elseif ( array_key_exists( $slug, get_plugins() ) ) {
            $state = 'installed';
        } else {
            $state = 'missing';
        }

        return array_merge( $card, array(
            'type' => 'module',
            'state' => $state,
        ) );
    }


    /**
     * Capture third-party cards registered on the legacy filter as raw HTML.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    private static function get_third_party_cards() {
        $default_ids = array( 'tracking-platforms', 'inter-bank', 'recovery-carts', 'joinotify', 'google-maps', 'payco' );
        $registry = new Integrations();
        $cards = array();

        foreach ( $registry->get_cards() as $card ) {
            if ( empty( $card['id'] ) || in_array( $card['id'], $default_ids, true ) ) {
                continue;
            }

            if ( empty( $card['callback'] ) || ! is_callable( $card['callback'] ) ) {
                continue;
            }

            ob_start();
            call_user_func( $card['callback'], $card );
            $html = (string) ob_get_clean();

            if ( '' === trim( $html ) ) {
                continue;
            }

            $cards[] = array(
                'id' => $card['id'],
                'type' => 'custom',
                'priority' => intval( $card['priority'] ?? 10 ),
                'html' => $html,
            );
        }

        return $cards;
    }
}
