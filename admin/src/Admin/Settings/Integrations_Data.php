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
                'settings_url' => admin_url('admin.php?page=flexify-checkout-apps'),
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

        foreach ( $cards as &$card ) {
            if ( empty( $card['icon'] ) ) {
                $card['icon'] = self::get_card_icon( $card['id'] );
            }
        }

        unset( $card );

        $cards = array_merge( $cards, self::get_third_party_cards() );

        usort( $cards, static function ( $a, $b ) {
            return intval( $a['priority'] ?? 10 ) <=> intval( $b['priority'] ?? 10 );
        } );

        return $cards;
    }


    /**
     * Icon markup for the plugin-owned integration cards.
     *
     * @since 6.0.0
     * @param string $card_id Card identifier.
     * @return string
     */
    private static function get_card_icon( $card_id ) {
        $icons = array(
            'tracking-platforms' => '<svg fill="#000000" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path d="M68.8,20.3A10.9,10.9,0,1,1,58.3,34.2h-.4a5,5,0,0,0-5,5h0v4.3l6.3,6.3a1.93,1.93,0,0,1,0,2.8L57.9,54a1.93,1.93,0,0,1-2.8,0L53,51.9v9A10.85,10.85,0,0,1,42.5,71.8h-.8A10.91,10.91,0,1,1,31.2,57.9a11,11,0,0,1,10.5,7.9h.4a5,5,0,0,0,5-5h0V52.1L45.3,54a1.93,1.93,0,0,1-2.8,0l-1.4-1.4a1.93,1.93,0,0,1,0-2.8L47,43.9V39.2A10.85,10.85,0,0,1,57.5,28.3h.8A10.83,10.83,0,0,1,68.8,20.3ZM31.2,63.9a5,5,0,0,0-5,5,5,5,0,0,0,10,0A5,5,0,0,0,31.2,63.9ZM68.8,26.2a5,5,0,0,0-5,5,5,5,0,1,0,10,0A5,5,0,0,0,68.8,26.2Z"></path></svg>',
            'inter-bank' => '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080" style="max-width: 10rem;"><g><g><path fill="#424242" d="M795.7 865.8c-10.2-5.7-18.1-13.9-23.7-24.6-5.6-10.6-8.5-23-8.5-37 0-13.8 2.8-26.1 8.5-37 5.6-10.8 13.6-19.3 23.9-25.4 10.3-6.1 22.1-9.1 35.3-9.1 12.9 0 24.3 3 34.1 8.9s17.4 14 22.8 24.2c5.4 10.2 8.1 21.4 8.1 33.6v3.3h-119c.2 19.1 5.1 33.7 14.7 43.9 9.6 10.2 22.6 15.3 39 15.3 12.9 0 23.9-3.4 33-10.1 9.1-6.7 15.2-15.3 18.3-25.7h13.6c-2 9.3-6 17.6-11.9 24.8-5.9 7.3-13.5 13-22.6 17.2-9.2 4.2-19.3 6.3-30.4 6.3-13.3 0-25-2.9-35.2-8.6zm86-75.4c-2-13.1-7.5-23.8-16.5-32.2-9-8.4-20.3-12.6-34-12.6-14 0-26 4.1-35.9 12.1-9.9 8.1-15.8 19-17.6 32.6h104z"/></g><g><path fill="#ea7100" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z"/><path fill="#ffffff" d="M110 575.2h181.2l1-5-178.7-37.1 3.5-19.8 178.7 38.6 1.5-6.9-168.8-73.3 7.4-20.3L305 522.7l3.5-5-150-111.4 15.3-24.3 149 111.4 4.5-5.9L210 338.6l28.7-23.3L355 457.4l4.9-4.5-67.8-172.3 41.1-17.3 63.9 168.8 5-1.5V243.5h65.8V590H110v-14.8z"/></g></svg>',
            'recovery-carts' => '<svg viewBox="0 0 1080 1080" xmlns="http://www.w3.org/2000/svg"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/><circle fill="#fff" cx="870.13" cy="237.99" r="120.99"/></g><g><path style="fill:none;stroke:#141D26;stroke-width:15;stroke-miterlimit:133.3333;" d="M808.53,271.68c-6.78-27.14-10.18-40.71-3.05-49.83c7.12-9.12,21.11-9.12,49.08-9.12h36.62 c27.97,0,41.96,0,49.08,9.12c7.12,9.12,3.73,22.69-3.05,49.83c-4.32,17.26-6.47,25.89-12.91,30.91 c-6.44,5.02-15.33,5.02-33.12,5.02h-36.62c-17.79,0-26.69,0-33.12-5.02C815,297.57,812.84,288.94,808.53,271.68z"/><path style="fill:none;stroke:#141D26;stroke-width:15;stroke-miterlimit:133.3333;" d="M932.17,216.68l-5.62-20.6c-2.17-7.94-3.25-11.92-5.47-14.91c-2.21-2.98-5.22-5.28-8.67-6.63 c-3.47-1.36-7.59-1.36-15.82-1.36 M813.56,216.68l5.62-20.6c2.17-7.94,3.25-11.92,5.47-14.91c2.21-2.98,5.22-5.28,8.67-6.63 c3.47-1.36,7.59-1.36,15.82-1.36"/><path style="fill:none;stroke:#141D26;stroke-width:15;stroke-miterlimit:133.3333;" d="M849.14,173.19c0-4.37,3.54-7.91,7.91-7.91h31.63c4.37,0,7.91,3.54,7.91,7.91c0,4.37-3.54,7.91-7.91,7.91 h-31.63C852.68,181.1,849.14,177.56,849.14,173.19z"/><path style="fill:none;stroke:#141D26;stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:133.3333;" d="M841.24,244.36v31.63"/><path style="fill:none;stroke:#141D26;stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:133.3333;" d="M904.5,244.36v31.63"/><path style="fill:none;stroke:#141D26;stroke-width:15;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:133.3333;" d="M872.87,244.36v31.63"/></g></svg>',
            'joinotify' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 703 882.5"><path d="M908.66,248V666a126.5,126.5,0,0,1-207.21,97.41l-16.7-16.7L434.08,496.07l-62-62a47.19,47.19,0,0,0-72,30.86V843.36a47.52,47.52,0,0,0,69.57,35.22l19.3-19.3,56-56,81.19-81.19,10.44-10.44a47.65,47.65,0,0,1,67.63,65.05l-13,13L428.84,952.12l-9.59,9.59a128,128,0,0,1-213.59-95.18V413.17a124.52,124.52,0,0,1,199.78-82.54l22.13,22.13L674.45,599.64l46.22,46.22,17,17a47.8,47.8,0,0,0,71-31.44V270.19a48.19,48.19,0,0,0-75-40.05L720.43,243.4l-68.09,68.09L575.7,388.13a48.39,48.39,0,0,1-67.43-67.93L680,148.46A136,136,0,0,1,908.66,248Z" transform="translate(-205.66 -112.03)" style="fill:#22c55e"/></svg>',
            'google-maps' => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-label="Google Maps" role="img" viewBox="0 0 512 512" fill="#000000"><rect id="fc-gmaps-a" width="512" height="512" x="0" y="0" rx="15%" fill="#ffffff"></rect><clipPath id="fc-gmaps-b"><use xlink:href="#fc-gmaps-a"></use></clipPath><g clip-path="url(#fc-gmaps-b)"><path fill="#35a85b" d="M0 512V0h512z"></path><path fill="#5881ca" d="M256 288L32 512h448z"></path><path fill="#c1c0be" d="M288 256L512 32v448z"></path><path stroke="#fadb2a" stroke-width="71" d="M0 512L512 0"></path><path fill="none" stroke="#f2f2f2" stroke-width="22" d="M175 173h50a50 54 0 1 1-15-41"></path><path fill="#de3738" d="M353 85a70 70 0 0 1 140 0c0 70-70 70-70 157 0-87-70-87-70-157"></path><circle cx="423" cy="89" r="25" fill="#7d2426"></circle></g></svg>',
            'payco' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 135 31" fill="none" style="max-width: 10rem;"><path d="M8.83636 3.74316C10.7667 3.74316 12.4319 4.50553 13.8311 6.03113C15.2095 7.53676 15.8996 9.38797 15.8996 11.5865C15.8996 13.785 15.2095 15.6666 13.8311 17.1723C12.4517 18.6779 10.7874 19.4298 8.83636 19.4298C6.8853 19.4298 5.45763 18.8255 4.43329 17.6177V20.4996C4.43243 22.9612 2.44855 24.9566 0 24.9566V4.15908H3.04363C3.81058 4.15908 4.43243 4.78425 4.43243 5.5553C5.45676 4.3475 6.92416 3.74316 8.8355 3.74316H8.83636ZM5.40839 14.2305C6.07775 14.8843 6.92503 15.2108 7.95022 15.2108C8.97541 15.2108 9.81232 14.8843 10.4618 14.2305C11.1312 13.5766 11.4663 12.6962 11.4663 11.5865C11.4663 10.4768 11.1312 9.59637 10.4618 8.94254C9.81146 8.28871 8.97369 7.96223 7.95022 7.96223C6.92675 7.96223 6.07861 8.28871 5.40839 8.94254C4.75804 9.59637 4.43329 10.4777 4.43329 11.5865C4.43329 12.6953 4.75804 13.5775 5.40839 14.2305Z" fill="#601DFA"/><path d="M29.1382 5.5553C29.1382 4.78425 29.76 4.15908 30.527 4.15908H33.5706V19.0139H29.1382V17.6177C28.1138 18.8264 26.6456 19.4298 24.7351 19.4298C22.8246 19.4298 21.1197 18.677 19.7404 17.1723C18.3611 15.6675 17.6719 13.805 17.6719 11.5865C17.6719 9.368 18.3611 7.53589 19.7404 6.03113C21.1387 4.5064 22.8039 3.74316 24.7351 3.74316C26.6663 3.74316 28.1138 4.3475 29.1382 5.5553ZM22.1052 11.5865C22.1052 12.6962 22.4299 13.5775 23.0803 14.2305C23.7306 14.8843 24.577 15.2108 25.6221 15.2108C26.6672 15.2108 27.5136 14.8843 28.1639 14.2305C28.8143 13.5766 29.139 12.6962 29.139 11.5865C29.139 10.4768 28.8143 9.59637 28.1639 8.94254C27.5136 8.28871 26.6663 7.96223 25.6221 7.96223C24.5779 7.96223 23.7306 8.28871 23.0803 8.94254C22.4299 9.59637 22.1052 10.4777 22.1052 11.5865Z" fill="#601DFA"/><path d="M43.3518 13.2503L46.1294 4.15918H50.8581L45.8047 18.5686C44.9773 20.8861 43.8882 22.5541 42.5391 23.5743C41.1892 24.5946 39.4704 25.0548 37.382 24.9558V20.7967C38.4064 20.7967 39.1941 20.6134 39.7459 20.247C40.297 19.8806 40.7409 19.2415 41.076 18.3307L35.1952 4.15918H40.0716L43.3518 13.2503Z" fill="#601DFA"/><path d="M69.0326 3.74316C70.7073 3.74316 72.0658 4.30756 73.1109 5.43635C74.1352 6.52606 74.6474 8.01172 74.6474 9.89246V19.0131H70.215V10.278C70.215 9.50519 70.0327 8.90694 69.6682 8.4806C69.3038 8.05514 68.7769 7.84153 68.0868 7.84153C67.3579 7.84153 66.7913 8.089 66.388 8.58393C65.9838 9.07886 65.7825 9.76221 65.7825 10.634V19.0122H61.3501V10.2771C61.3501 9.50433 61.1679 8.90607 60.8034 8.47973C60.4389 8.05427 59.9121 7.84067 59.222 7.84067C58.493 7.84067 57.9264 8.08813 57.5231 8.58306C57.1189 9.07799 56.9177 9.76134 56.9177 10.6331V19.0113H52.4852V4.15908H55.5582C56.3088 4.15908 56.9177 4.77123 56.9177 5.52578C57.7252 4.33708 59.0458 3.74316 60.8777 3.74316C62.5912 3.74316 63.8816 4.39699 64.7487 5.70378C65.6547 4.39612 67.0832 3.74316 69.0334 3.74316H69.0326Z" fill="#601DFA"/><path d="M92.1423 13.3691H81.6511C82.1434 14.7558 83.3258 15.4487 85.1974 15.4487C86.3988 15.4487 87.3445 15.0727 88.0346 14.3199L91.5809 16.37C90.1428 18.4105 87.9949 19.4298 85.1386 19.4298C82.6754 19.4298 80.6863 18.6875 79.1689 17.2018C77.6911 15.7161 76.9526 13.845 76.9526 11.5865C76.9526 9.32806 77.6816 7.50637 79.1395 6.00074C80.6173 4.49598 82.5087 3.74316 84.8139 3.74316C86.961 3.74316 88.7445 4.49598 90.1627 6.00074C91.6007 7.46643 92.3202 9.32806 92.3202 11.5865C92.3202 12.2204 92.2615 12.8151 92.1431 13.3691H92.1423ZM81.563 10.0418H87.9163C87.4827 8.47713 86.4385 7.69479 84.7837 7.69479C83.1289 7.69479 81.9957 8.47713 81.563 10.0418Z" fill="#601DFA"/><path d="M103.49 3.74316C105.086 3.74316 106.396 4.28846 107.421 5.3773C108.485 6.48699 109.017 7.99175 109.017 9.89332V19.0139H104.585V10.5463C104.585 9.69448 104.348 9.0311 103.875 8.55614C103.403 8.08118 102.782 7.84327 102.013 7.84327C101.146 7.84327 100.472 8.10637 99.9888 8.63082C99.506 9.15614 99.2651 9.91329 99.2651 10.904V19.0148H94.8326V4.15908H97.8763C98.6432 4.15908 99.2651 4.78425 99.2651 5.5553C100.151 4.3475 101.56 3.74316 103.491 3.74316H103.49Z" fill="#601DFA"/><path d="M120.718 4.15893V8.43704H117.675V13.6955C117.675 14.2703 117.901 14.6419 118.354 14.8095C118.808 14.9779 119.595 15.0326 120.718 14.9727V19.0129C117.921 19.3099 115.97 19.0424 114.867 18.2106C113.783 17.3588 113.241 15.854 113.241 13.6946V8.43617H110.877V4.15807H113.241C113.241 2.48312 114.335 1.00614 115.93 0.524236L117.674 -0.00195312V4.1572H120.717L120.718 4.15893Z" fill="#601DFA"/><path d="M134.548 14.5579C134.548 16.1625 133.957 17.3808 132.775 18.2126C132.07 18.6884 131.278 19.0253 130.397 19.2216C129.776 19.3614 129.111 19.4308 128.401 19.4308C127.911 19.4308 127.445 19.4013 127.003 19.3431C124.645 19.0297 122.994 17.8809 122.047 15.8951L125.889 13.6966C126.283 14.8653 127.121 15.4497 128.401 15.4497C129.466 15.4497 129.997 15.1423 129.997 14.5284C129.997 14.0934 129.14 13.6575 127.426 13.2216C126.795 13.0428 126.254 12.8604 125.801 12.672C125.348 12.4836 124.86 12.2161 124.338 11.8697C123.815 11.5232 123.416 11.0726 123.141 10.5177C122.865 9.96376 122.727 9.32904 122.727 8.61617C122.727 7.09143 123.279 5.89318 124.382 5.02141C125.138 4.43791 126.024 4.05412 127.039 3.87091C127.505 3.78669 127.999 3.74414 128.52 3.74414C129.181 3.74414 129.799 3.8136 130.373 3.9534C132.041 4.35629 133.344 5.34615 134.282 6.92298L130.499 8.97304C130.027 8.10126 129.366 7.66538 128.52 7.66538C127.673 7.66538 127.279 7.94323 127.279 8.49721C127.279 8.75509 127.485 8.97825 127.899 9.1658C128.313 9.35422 128.963 9.56695 129.849 9.80487C133.001 10.4978 134.567 12.0824 134.548 14.5579Z" fill="#601DFA"/></svg>',
        );

        return isset( $icons[ $card_id ] ) ? $icons[ $card_id ] : '';
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
