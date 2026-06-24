<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\Admin\Fonts_Manager;
use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\Checkout\Coupons;
use MeuMouse\Flexify_Checkout\Validations\ISO3166;
use MeuMouse\Flexify_Checkout\Views\Settings\Settings_Panel;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Build the settings schema and bootstrap payload for the Vue admin app.
 *
 * The schema is a declarative tree (tabs > cards > fields) consumed by the
 * frontend FieldRenderer. Field visibility dependencies are expressed via
 * visible_when and resolved client-side.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Admin\Settings
 * @author MeuMouse.com
 */
class Registry {

    /**
     * Build the full bootstrap payload consumed by the Vue settings app.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_bootstrap_data() {
        return apply_filters( 'Flexify_Checkout/Admin/Settings_Bootstrap', array(
            'settings' => Repository::get_settings(),
            'schema' => self::get_schema(),
            'runtime' => self::get_runtime_data(),
        ));
    }


    /**
     * Runtime (non-persisted) context for the settings app.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    public static function get_runtime_data() {
        global $wp_version;

        $license_object = get_option( 'flexify_checkout_license_response_object', null );
        $admin_email = get_option('admin_email');

        return array(
            'version' => defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '',
            'docs_link' => defined('FLEXIFY_CHECKOUT_DOCS_LINK') ? FLEXIFY_CHECKOUT_DOCS_LINK : '',
            'is_pro' => License::is_valid(),
            'license' => array(
                'key' => (string) get_option( 'flexify_checkout_license_key', '' ),
                'masked_key' => self::mask_license_key( (string) get_option( 'flexify_checkout_license_key', '' ) ),
                'is_valid' => License::is_valid(),
                'title' => method_exists( License::class, 'license_title' ) ? License::license_title() : '',
                'expire' => method_exists( License::class, 'license_expire' ) ? License::license_expire() : '',
                'domain' => isset( $license_object->domain ) ? (string) $license_object->domain : '',
                'buy_url' => 'https://meumouse.com/plugins/flexify-checkout-para-woocommerce/?utm_source=wordpress&utm_medium=plugins-list&utm_campaign=flexify_checkout',
            ),
            'report_problems_url' => add_query_arg( array(
                'wpf9053_2' => rawurlencode( (string) $admin_email ),
                'wpf9053_5' => rawurlencode( 'Flexify Checkout para WooCommerce' ),
                'wpf9053_9' => rawurlencode( License::is_valid() ? 'Sim' : 'Não' ),
                'wpf9053_7' => rawurlencode( License::get_domain() ),
                'wpf9053_6' => rawurlencode( wp_get_theme()->get('Name') ),
            ), 'https://meumouse.com/reportar-problemas/' ),
            'fields' => Fields_Store::get_fields(),
            'conditions' => Conditions_Store::get_rules_for_client(),
            'layout' => Layout_Store::get_layout_for_client(),
            'integrations' => Integrations_Data::get_cards_for_client(),
            'fonts' => Fonts_Manager::get_fonts(),
            'shipping_methods' => self::build_shipping_method_options(),
            'shipping_zones' => self::build_shipping_zone_options(),
            'payment_gateways' => self::build_payment_gateway_options(),
            'user_roles' => self::build_user_role_options(),
            'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'R$',
            'countries' => array_map( static function ( $code, $label ) {
                return array(
                    'value' => (string) $code,
                    'label' => (string) $label,
                );
            }, array_keys( ISO3166::country_codes() ), array_values( ISO3166::country_codes() ) ),
            'themes' => array_values( array_map( static function ( $theme ) {
                return array(
                    'value' => $theme['id'],
                    'label' => $theme['label'],
                    'status' => isset( $theme['status'] ) ? $theme['status'] : 'active',
                    'icon' => isset( $theme['icon'] ) ? $theme['icon'] : '',
                );
            }, Settings_Panel::get_registered_themes() ) ),
            'system' => array(
                'wp_version' => $wp_version,
                'multisite' => is_multisite(),
                'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
                'php_version' => phpversion(),
                'wc_version' => defined('WC_VERSION') ? WC_VERSION : '',
                'extensions' => array(
                    'dom' => extension_loaded('dom'),
                    'curl' => extension_loaded('curl'),
                    'openssl' => extension_loaded('openssl'),
                    'gd' => extension_loaded('gd'),
                ),
                'php_settings' => array(
                    'post_max_size' => ini_get('post_max_size'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'max_input_vars' => ini_get('max_input_vars'),
                    'memory_limit' => ini_get('memory_limit'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                ),
                'file_get_content' => function_exists('file_get_contents') && filter_var( ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN ),
            ),
        );
    }


    /**
     * Mask a license key for display, keeping only the edges visible.
     *
     * @since 6.0.0
     * @param string $key License key.
     * @return string
     */
    private static function mask_license_key( $key ) {
        if ( '' === $key ) {
            return '';
        }

        $parts = explode( '-', $key );

        if ( count( $parts ) > 2 ) {
            $masked = array();

            foreach ( $parts as $index => $part ) {
                if ( 0 === $index ) {
                    $masked[] = strlen( $part ) > 6 ? substr( $part, 0, 6 ) . str_repeat( 'X', strlen( $part ) - 6 ) : $part;
                } elseif ( $index === count( $parts ) - 1 ) {
                    $masked[] = $part;
                } else {
                    $masked[] = str_repeat( 'X', strlen( $part ) );
                }
            }

            return implode( '-', $masked );
        }

        if ( strlen( $key ) <= 8 ) {
            return $key;
        }

        return substr( $key, 0, 4 ) . str_repeat( 'X', max( 0, strlen( $key ) - 8 ) ) . substr( $key, -4 );
    }


    /**
     * Flat map of field key => definition, derived from the schema.
     *
     * Used by Repository to sanitize incoming values by declared type.
     *
     * @since 6.0.0
     * @return array<string,array<string,mixed>>
     */
    public static function get_field_definitions() {
        $definitions = array();

        foreach ( self::get_schema() as $tab ) {
            if ( empty( $tab['cards'] ) || ! is_array( $tab['cards'] ) ) {
                continue;
            }

            foreach ( $tab['cards'] as $card ) {
                if ( empty( $card['fields'] ) || ! is_array( $card['fields'] ) ) {
                    continue;
                }

                self::collect_field_definitions( $card['fields'], $definitions );
            }
        }

        return $definitions;
    }


    /**
     * Collect field definitions recursively, including popup sub-fields.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $fields Field definitions list.
     * @param array<string,array<string,mixed>> $definitions Accumulator (by reference).
     * @return void
     */
    private static function collect_field_definitions( $fields, &$definitions ) {
        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            if ( ! empty( $field['key'] ) ) {
                $definitions[ $field['key'] ] = $field;
            }

            if ( ! empty( $field['popup']['fields'] ) && is_array( $field['popup']['fields'] ) ) {
                self::collect_field_definitions( $field['popup']['fields'], $definitions );
            }
        }
    }


    /**
     * Build the settings schema consumed by the Vue app.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_schema() {
        $schema = array(
            self::tab_general(),
            self::tab_cart(),
            self::tab_account(),
            self::tab_fields(),
            self::tab_conditions(),
            self::tab_texts(),
            self::tab_thankyou(),
            self::tab_styles(),
        );

        // Global, checkout-wide webhooks. Pro-gated (webhooks were previously
        // only available behind the Pro recovery tab).
        if ( License::is_valid() ) {
            $schema[] = self::tab_webhooks();
        }

        $schema[] = self::tab_about();

        /**
         * Filter the settings schema before sending it to the Vue app.
         *
         * @since 6.0.0
         * @param array $schema Schema tree (tabs > cards > fields).
         */
        return apply_filters( 'Flexify_Checkout/Admin/Settings_Schema', $schema );
    }


    /**
     * General tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_general() {
        $shop_card_fields = array(
            self::field_toggle( 'enable_react_checkout', __( 'Ativar checkout em React (beta)', 'flexify-checkout-for-woocommerce' ), __( 'Substitui a finalização de compra clássica por uma interface em React que consome a WooCommerce Store API. Requer licença válida.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
            self::field_toggle( 'enable_back_to_shop_button', __( 'Mostrar botão Voltar à loja', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para exibir o botão "Voltar à loja" na primeira etapa de finalização de compra.', 'flexify-checkout-for-woocommerce' ) ),
            self::field_toggle( 'enable_skip_cart_page', __( 'Pular página do carrinho', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para redirecionar o usuário da página de carrinho para a finalização de compra automaticamente.', 'flexify-checkout-for-woocommerce' ) ),
            self::field_toggle( 'display_opened_order_review_mobile', __( 'Mostrar resumo do pedido aberto por padrão', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para mostrar o resumo do pedido aberto por padrão em celulares.', 'flexify-checkout-for-woocommerce' ) ),
            self::field_toggle( 'enable_checkout_countdown', __( 'Ativar contagem regressiva do checkout', 'flexify-checkout-for-woocommerce' ), __( 'Permite definir um tempo limite para o cliente finalizar a compra ou gerar urgência na compra.', 'flexify-checkout-for-woocommerce' ), array(
                'popup' => array(
                    'button' => __( 'Configurar', 'flexify-checkout-for-woocommerce' ),
                    'title' => __( 'Configurar contagem regressiva', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_text( 'checkout_countdown_title', __( 'Título da contagem regressiva', 'flexify-checkout-for-woocommerce' ), __( 'Permite definir um titulo a ser exibido ao lado da contagem regressiva.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_dimension( 'checkout_countdown_value', 'checkout_countdown_unit', __( 'Duração total', 'flexify-checkout-for-woocommerce' ), __( 'Permite definir o tempo limite da contagem regressiva.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'minutes', 'label' => __( 'Minutos', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'days', 'label' => __( 'Dias', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_select( 'checkout_countdown_action', __( 'Ação após expirar', 'flexify-checkout-for-woocommerce' ), __( 'Permite definir o tipo de ação a ser executado após a expiração da contagem regressiva.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'hide', 'label' => __( 'Ocultar', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'restart', 'label' => __( 'Reiniciar contagem', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'logout', 'label' => __( 'Encerrar sessão do checkout', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_text( 'checkout_countdown_redirect_url', __( 'URL de redirecionamento', 'flexify-checkout-for-woocommerce' ), __( 'Permite definir para qual endereço o usuário será redirecionado após a sessão ser encerrada.', 'flexify-checkout-for-woocommerce' ), array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'checkout_countdown_action', 'equals' => 'logout' ) ),
                        ) ),
                        self::field_select( 'countdown_background_type', __( 'Cor de fundo da contagem regressiva', 'flexify-checkout-for-woocommerce' ), __( 'Informe a cor de fundo da barra de contagem regressiva.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'primary', 'label' => __( 'Usar cor padrão', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'custom', 'label' => __( 'Definir personalizada', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_color( 'countdown_background_color', __( 'Cor de fundo personalizada', 'flexify-checkout-for-woocommerce' ), '', array(
                            'default' => '#141D26',
                            'visible_when' => array( array( 'field' => 'countdown_background_type', 'equals' => 'custom' ) ),
                        ) ),
                        self::field_select( 'countdown_font_color_type', __( 'Cor do texto da contagem regressiva', 'flexify-checkout-for-woocommerce' ), __( 'Informe a cor do texto da barra de contagem regressiva.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'default', 'label' => __( 'Usar cor padrão', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'custom', 'label' => __( 'Definir personalizada', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_color( 'countdown_font_color', __( 'Cor do texto personalizada', 'flexify-checkout-for-woocommerce' ), '', array(
                            'default' => '#ffffff',
                            'visible_when' => array( array( 'field' => 'countdown_font_color_type', 'equals' => 'custom' ) ),
                        ) ),
                    ),
                ),
            ) ),
            self::field_toggle( 'enable_animation_process_purchase', __( 'Ativar animações de processamento de compra', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para personalizar a animação de processamento da compra.', 'flexify-checkout-for-woocommerce' ), array(
                'pro' => true,
                'popup' => array(
                    'button' => __( 'Configurar animação', 'flexify-checkout-for-woocommerce' ),
                    'title' => __( 'Configurar animação de processamento', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_text( 'text_animation_process_purchase_1', __( 'Texto da animação 1', 'flexify-checkout-for-woocommerce' ), __( 'Informe o texto que será exibido na animação 1 de processamento.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_media( 'animation_process_purchase_file_1', __( 'Arquivo da animação 1', 'flexify-checkout-for-woocommerce' ), __( 'Anexe o link ou arquivo da animação Lottie em formato .json', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_text( 'text_animation_process_purchase_2', __( 'Texto da animação 2', 'flexify-checkout-for-woocommerce' ), __( 'Informe o texto que será exibido na animação 2 de processamento.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_media( 'animation_process_purchase_file_2', __( 'Arquivo da animação 2', 'flexify-checkout-for-woocommerce' ), __( 'Anexe o link ou arquivo da animação Lottie em formato .json', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_text( 'text_animation_process_purchase_3', __( 'Texto da animação 3', 'flexify-checkout-for-woocommerce' ), __( 'Informe o texto que será exibido na animação 3 de processamento.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_media( 'animation_process_purchase_file_3', __( 'Arquivo da animação 3', 'flexify-checkout-for-woocommerce' ), __( 'Anexe o link ou arquivo da animação Lottie em formato .json', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
            ) ),
        );

        if ( class_exists('Kangu_Shipping_Method') ) {
            $shop_card_fields[] = self::field_toggle( 'enable_display_local_pickup_kangu', __( 'Mostrar endereço da loja física para retirada da encomenda Kangu', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para mostrar o endereço da sua loja como ponto de retirada da encomenda Kangu.', 'flexify-checkout-for-woocommerce' ) );
        }

        return array(
            'id' => 'general',
            'title' => __( 'Geral', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'slider-alt',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'general-shop',
                    'fields' => $shop_card_fields,
                ),
                array(
                    'id' => 'general-checkout',
                    'fields' => array(
                        self::field_toggle( 'enable_terms_is_checked_default', __( 'Termos e condições ativo por padrão', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para a opção de termos e condições da última etapa ficar ativa por padrão, caso exista uma página de termos e condições configurada.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_auto_apply_coupon_code', __( 'Aplicar cupom de desconto automaticamente', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para informar um cupom de desconto para ser aplicado automaticamente na finalização de compra.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_text( 'coupon_code_for_auto_apply', __( 'Código do cupom de desconto', 'flexify-checkout-for-woocommerce' ), __( 'Informe o código do cupom de desconto que será aplicado automaticamente na finalização de compra.', 'flexify-checkout-for-woocommerce' ), array(
                            'placeholder' => 'CUPOMDEDESCONTO',
                            'visible_when' => array( array( 'field' => 'enable_auto_apply_coupon_code', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_toggle( 'direct_checkout_api', __( 'Ativar API para criação de links de checkout direto', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para habilitar um endpoint para criação de links de checkout direto via API.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Cart tab definition.
     *
     * Holds the cart/product behaviors previously rendered under the General
     * tab. Storage keys are unchanged — only the display tab moves.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_cart() {
        return array(
            'id' => 'cart',
            'title' => __( 'Carrinho', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'cart',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'cart-products',
                    'fields' => array(
                        self::field_toggle( 'enable_link_image_products', __( 'Tornar imagem de produtos clicáveis', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para permitir que o usuário acesse o produto ao clicar na imagem do produto.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_change_product_quantity', __( 'Permitir alterar quantidade de produtos', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para exibir os seletores de quantidades do produto na finalização de compras.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_remove_product_cart', __( 'Permitir remover produtos do carrinho', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para exibir o botão de remoção do produto do carrinho na finalização de compras.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_remove_quantity_select', __( 'Remover controles de quantidade em produtos vendidos individualmente', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para remover os controles de quantidade na finalização de compras em produtos que são vendidos individualmente.', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Account & access tab definition.
     *
     * Holds the login/account behaviors previously rendered under the General
     * tab. Storage keys are unchanged — only the display tab moves.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_account() {
        return array(
            'id' => 'account',
            'title' => __( 'Conta & Acesso', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'user',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'account-access',
                    'fields' => array(
                        self::field_toggle( 'auto_display_login_modal', __( 'Abrir popup de login automaticamente', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para que o popup de login seja aberto automaticamente ao reconhecer uma conta existente com o e-mail informado.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'check_password_strenght', __( 'Ativar verificação de força da senha do usuário', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para forçar a verificação da força de senha na criação da conta do usuário, na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'email_providers_suggestion', __( 'Ativar sugestão de preenchimento do e-mail', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para exibir a sugestão do provedor de e-mail, na finalização de compras.', 'flexify-checkout-for-woocommerce' ), array(
                            'popup' => array(
                                'button' => __( 'Configurar provedores', 'flexify-checkout-for-woocommerce' ),
                                'title' => __( 'Configurar sugestão de e-mails', 'flexify-checkout-for-woocommerce' ),
                                'component' => 'email-providers',
                            ),
                        ) ),
                        self::field_toggle( 'enable_assign_guest_orders', __( 'Atribuir pedidos de usuários convidados', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para que pedidos de usuários convidados na finalização de compra sejam atribuídos a usuários existentes.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Thank you page tab definition.
     *
     * Consolidates the thank-you page options previously rendered under the
     * General tab. Storage keys are unchanged — only the display tab moves.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_thankyou() {
        return array(
            'id' => 'thankyou',
            'title' => __( 'Página de Agradecimento', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'like',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'thankyou-main',
                    'fields' => array(
                        self::field_toggle( 'enable_thankyou_page_template', __( 'Ativar página de agradecimento do Flexify Checkout', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para carregar o modelo de página de agradecimento do Flexify Checkout.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_select( 'contact_page_thankyou', __( 'Página de contato', 'flexify-checkout-for-woocommerce' ), __( 'Selecione a página de contato que será exibida aos clientes na finalização de compra.', 'flexify-checkout-for-woocommerce' ), self::build_pages_options(), array(
                            'visible_when' => array( array( 'field' => 'enable_thankyou_page_template', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'contact_page_thankyou_custom_link', __( 'Link personalizado de contato', 'flexify-checkout-for-woocommerce' ), '', array(
                            'type' => 'url',
                            'visible_when' => array(
                                array( 'field' => 'enable_thankyou_page_template', 'equals' => 'yes' ),
                                array( 'field' => 'contact_page_thankyou', 'equals' => 'custom_link' ),
                            ),
                        ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Texts tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_texts() {
        $empty_hint = __( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' );
        $review_hint = __( 'Utilize as variáveis abaixo para recuperar as informações de campos. Ou deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' );
        $placeholders = self::build_placeholder_hints();

        return array(
            'id' => 'texts',
            'title' => __( 'Textos', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'text',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'texts-steps',
                    'fields' => array(
                        self::field_text( 'text_header_step_1', __( 'Texto informativo dos campos da etapa de contato', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_shipping_methods_label', __( 'Título das formas de entrega', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_header_step_2', __( 'Texto informativo dos campos da etapa de entrega', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_header_step_3', __( 'Texto informativo dos campos da etapa de pagamento', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_header_sidebar_right', __( 'Texto informativo dos itens do carrinho', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_check_step_1', __( 'Texto informativo do verificador da etapa de contato', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_check_step_2', __( 'Texto informativo do verificador da etapa de entrega', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_check_step_3', __( 'Texto informativo do verificador da etapa de pagamento', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_previous_step_button', __( 'Texto do botão de voltar etapas', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                        self::field_text( 'text_view_shop_thankyou', __( 'Texto do botão para revisitar a loja da página de agradecimento', 'flexify-checkout-for-woocommerce' ), $empty_hint ),
                    ),
                ),
                array(
                    'id' => 'texts-reviews',
                    'fields' => array(
                        self::field_textarea( 'text_contact_customer_review', __( 'Texto do resumo de informações de contato', 'flexify-checkout-for-woocommerce' ), $review_hint, array(
                            'placeholders' => $placeholders,
                        ) ),
                        self::field_textarea( 'text_shipping_customer_review', __( 'Texto do resumo de informações de entrega', 'flexify-checkout-for-woocommerce' ), $review_hint, array(
                            'placeholders' => $placeholders,
                        ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Build the placeholder hints list for the review text fields.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_placeholder_hints() {
        $hints = array();

        if ( method_exists( Helpers::class, 'get_placeholder_input_values' ) ) {
            foreach ( Helpers::get_placeholder_input_values() as $value ) {
                $hints[] = array(
                    'token' => isset( $value['placeholder_html'] ) ? (string) $value['placeholder_html'] : '',
                    'description' => isset( $value['description'] ) ? (string) $value['description'] : '',
                );
            }
        }

        return $hints;
    }


    /**
     * Fields tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_fields() {
        return array(
            'id' => 'fields',
            'title' => __( 'Campos e etapas', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'list-plus',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'checkout-builder',
                    'title' => __( 'Construtor de checkout', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Monte visualmente as etapas, campos e componentes do checkout React (order bump, blocos de conteúdo, cupom, resumo e observações) com pré-visualização ao vivo.', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'checkout-builder',
                    'fields' => array(
                        self::field_toggle( 'enable_checkout_builder', __( 'Ativar o construtor de checkout', 'flexify-checkout-for-woocommerce' ), __( 'Aplica o layout montado no construtor ao checkout React. Quando desativado, o checkout usa as etapas padrão.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'fields-manager',
                    'title' => __( 'Gerenciador de campos', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Adicione, edite, ordene e remova campos das etapas do checkout.', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'fields-manager',
                    'fields' => array(
                        self::field_toggle( 'enable_manage_fields', __( 'Gerenciar os campos e etapas da finalização de compras', 'flexify-checkout-for-woocommerce' ), __( 'Aplica as personalizações de campos configuradas abaixo no checkout.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'fields-options',
                    'title' => __( 'Opções de campos', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Comportamentos gerais dos campos do formulário.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_aditional_notes', __( 'Mostrar campo de observações adicionais', 'flexify-checkout-for-woocommerce' ), __( 'Exibe o campo de observações do pedido.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_field_masks', __( 'Adicionar máscaras aos campos', 'flexify-checkout-for-woocommerce' ), __( 'Aplica máscaras de digitação (CPF, CEP, telefone...).', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_optimize_for_digital_products', __( 'Otimizar para produtos digitais', 'flexify-checkout-for-woocommerce' ), __( 'Oculta campos de entrega quando o carrinho contém apenas produtos virtuais.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'hide_header_stepper_buttons', __( 'Ocultar indicador de etapas', 'flexify-checkout-for-woocommerce' ), __( 'Remove o stepper do cabeçalho do checkout.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_unset_wcbcf_fields_not_brazil', __( 'Ocultar campos brasileiros para outros países', 'flexify-checkout-for-woocommerce' ), __( 'Oculta campos do Brazilian Market quando o país não é Brasil.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'fields-address',
                    'title' => __( 'Endereço', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Preenchimento, validação e comportamento dos campos de endereço.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_autofill_company_info', __( 'Preencher informações da empresa automaticamente', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para preencher as informações da empresa automaticamente ao digitar o CNPJ (Disponível apenas no Brasil).', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_fill_address', __( 'Preencher endereço automaticamente', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para preencher os campos de entrega ao digitar o CEP (Recomendado), (Disponível apenas no Brasil).', 'flexify-checkout-for-woocommerce' ), array(
                            'pro' => true,
                            'popup' => array(
                                'button' => __( 'Configurar API', 'flexify-checkout-for-woocommerce' ),
                                'title' => __( 'Configurar API de preenchimento de endereço', 'flexify-checkout-for-woocommerce' ),
                                'fields' => array(
                                    self::field_text( 'get_address_api_service', __( 'Serviço de API para busca de endereço', 'flexify-checkout-for-woocommerce' ), __( 'Informe o endereço da API para obter o endereço do usuário através do seu CEP em formato JSON. Use a variável {postcode} para informar o CEP.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_text( 'api_auto_fill_address_param', __( 'Propriedade de obtenção de endereço', 'flexify-checkout-for-woocommerce' ), __( 'Informe a propriedade para obter o endereço que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_text( 'api_auto_fill_address_neightborhood_param', __( 'Propriedade de obtenção do bairro', 'flexify-checkout-for-woocommerce' ), __( 'Informe a propriedade para obter o bairro que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_text( 'api_auto_fill_address_city_param', __( 'Propriedade de obtenção de cidade', 'flexify-checkout-for-woocommerce' ), __( 'Informe a propriedade para obter a cidade que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ),
                                    self::field_text( 'api_auto_fill_address_state_param', __( 'Propriedade de obtenção de estado', 'flexify-checkout-for-woocommerce' ), __( 'Informe a propriedade para obter o estado que é retornado pelo serviço da API.', 'flexify-checkout-for-woocommerce' ) ),
                                ),
                            ),
                        ) ),
                        self::field_toggle( 'enable_shipping_to_different_address', __( 'Permitir envio para um endereço diferente', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para permitir que o usuário possa enviar seu pedido para um endereço diferente do endereço de faturamento.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'validate_address_by_postcode', __( 'Ativar validação de endereço por CEP', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para validar se a cidade e estado do usuário confere com CEP de cobrança informado.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_ddi_phone_field', __( 'Ativar telefone internacional', 'flexify-checkout-for-woocommerce' ), __( 'Ative esta opção para exibir o seletor de país no campo de número de telefone. Útil se você vende para outros países.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'fields-coupon',
                    'title' => __( 'Campo de cupom', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Visibilidade e posição do campo de cupom de desconto.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_hide_coupon_code_field', __( 'Ocultar campo de cupom de desconto', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_select( 'render_coupon_field_hook', __( 'Posição do campo de cupom', 'flexify-checkout-for-woocommerce' ), '', self::build_coupon_position_options(), array( 'pro' => true ) ),
                    ),
                ),
            ),
        );
    }


    /**
     * Conditions tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_conditions() {
        return array(
            'id' => 'conditions',
            'title' => __( 'Condições', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'filter-alt',
            'layout' => 'custom',
            'cards' => array(
                array(
                    'id' => 'conditions-manager',
                    'title' => __( 'Gerenciador de condições', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Exiba, oculte ou aplique descontos em campos, formas de entrega e pagamento com base em regras condicionais (usuários, produtos, países, regiões de entrega e mais).', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'conditions-manager',
                ),
            ),
        );
    }


    /**
     * Styles tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_styles() {
        $unit_options = array(
            array( 'value' => 'px', 'label' => 'px' ),
            array( 'value' => 'em', 'label' => 'em' ),
            array( 'value' => 'rem', 'label' => 'rem' ),
        );

        return array(
            'id' => 'styles',
            'title' => __( 'Estilos', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'palette',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'styles-theme',
                    'component' => 'theme-picker',
                ),
                array(
                    'id' => 'styles-header',
                    'fields' => array(
                        self::field_select( 'checkout_header_type', __( 'Tipo de marca no cabeçalho', 'flexify-checkout-for-woocommerce' ), __( 'Selecione o tipo de marca que será exibida no cabeçalho da página de finalização de compra.', 'flexify-checkout-for-woocommerce' ), array(
                            array( 'value' => 'logo', 'label' => __( 'Imagem (Padrão)', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'text', 'label' => __( 'Texto', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_media( 'search_image_header_checkout', __( 'Imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_text( 'logo_header_link', __( 'Link da imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ), __( 'Informe o link da imagem do cabeçalho.', 'flexify-checkout-for-woocommerce' ), array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_dimension( 'header_width_image_checkout', 'unit_header_width_image_checkout', __( 'Largura da imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ), '', $unit_options, array(
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_text( 'text_brand_checkout_header', __( 'Texto do cabeçalho', 'flexify-checkout-for-woocommerce' ), __( 'Informe o texto que será exibido no cabeçalho da página de finalização de compras.', 'flexify-checkout-for-woocommerce' ), array(
                            'placeholder' => 'CHECKOUT',
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'text' ) ),
                        ) ),
                    ),
                ),
                array(
                    'id' => 'styles-shortcodes',
                    'fields' => array(
                        self::field_text( 'shortcode_header', __( 'Cabeçalho personalizado', 'flexify-checkout-for-woocommerce' ), __( 'Adicione seu cabeçalho personalizado informando o shortcode.', 'flexify-checkout-for-woocommerce' ), array(
                            'placeholder' => '[shortcode id="100"]',
                        ) ),
                        self::field_text( 'shortcode_footer', __( 'Rodapé personalizado', 'flexify-checkout-for-woocommerce' ), __( 'Adicione seu rodapé personalizado informando o shortcode.', 'flexify-checkout-for-woocommerce' ), array(
                            'placeholder' => '[shortcode id="101"]',
                        ) ),
                    ),
                ),
                array(
                    'id' => 'styles-appearance',
                    'fields' => array(
                        self::field_color( 'set_primary_color', __( 'Cor primária', 'flexify-checkout-for-woocommerce' ), __( 'A cor primária define a cor dos elementos que terão ações ou informações na página de finalização de compras.', 'flexify-checkout-for-woocommerce' ), array( 'default' => '#141D26' ) ),
                        self::field_color( 'set_primary_color_on_hover', __( 'Cor secundára', 'flexify-checkout-for-woocommerce' ), __( 'A cor secundária define a cor dos elementos que terão ações ou informações na página de finalização de compras.', 'flexify-checkout-for-woocommerce' ), array( 'default' => '#33404D' ) ),
                        self::field_color( 'set_placeholder_color', __( 'Cor do título dos campos', 'flexify-checkout-for-woocommerce' ), __( 'Informe a cor do título dos campos da finalização de compras.', 'flexify-checkout-for-woocommerce' ), array( 'default' => '#33404D' ) ),
                        self::field_dimension( 'input_border_radius', 'unit_input_border_radius', __( 'Raio da borda dos elementos', 'flexify-checkout-for-woocommerce' ), __( 'Define o raio da borda dos campos, botões e elementos da finalização de compra.', 'flexify-checkout-for-woocommerce' ), $unit_options ),
                        self::field_select( 'set_font_family', __( 'Família de fontes', 'flexify-checkout-for-woocommerce' ), __( 'Defina qual fonte será aplicada na finalização de compra. Você pode adicionar novas fontes personalizadas ou do Google Fonts.', 'flexify-checkout-for-woocommerce' ), self::build_font_options(), array(
                            'popup' => array(
                                'button' => __( 'Gerenciar fontes', 'flexify-checkout-for-woocommerce' ),
                                'title' => __( 'Gerenciar fontes', 'flexify-checkout-for-woocommerce' ),
                                'component' => 'fonts-manager',
                            ),
                        ) ),
                        self::field_dimension( 'h2_size', 'h2_size_unit', __( 'Tamanho do h2', 'flexify-checkout-for-woocommerce' ), __( 'Define o tamanho da fonte para tags h2 de subtítulos (Heading 2).', 'flexify-checkout-for-woocommerce' ), $unit_options ),
                        self::field_code( 'custom_css_checkout', __( 'CSS personalizado', 'flexify-checkout-for-woocommerce' ), __( 'Adicione CSS customizado que será aplicado no checkout.', 'flexify-checkout-for-woocommerce' ), 'css' ),
                        self::field_code( 'custom_js_checkout', __( 'JS personalizado', 'flexify-checkout-for-woocommerce' ), __( 'Adicione JavaScript customizado que será executado no checkout.', 'flexify-checkout-for-woocommerce' ), 'javascript' ),
                    ),
                ),
            ),
        );
    }


    /**
     * Webhooks tab definition.
     *
     * Renders the global webhooks manager component, which reads/writes its
     * configuration through its own REST endpoint (flexify-checkout/v1/webhooks).
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_webhooks() {
        return array(
            'id' => 'webhooks',
            'title' => __( 'Webhooks', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'webhook',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'webhooks-manager',
                    'component' => 'webhooks-manager',
                ),
            ),
        );
    }


    /**
     * About tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_about() {
        return array(
            'id' => 'about',
            'title' => __( 'Sobre', 'flexify-checkout-for-woocommerce' ),
            'icon' => 'info-circle',
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'about-updates',
                    'fields' => array(
                        self::field_toggle( 'enable_auto_updates', __( 'Ativar atualizações automáticas', 'flexify-checkout-for-woocommerce' ), __( 'Ative essa opção para que o plugin Flexify Checkout seja atualizado automaticamente sempre que possível.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_update_notices', __( 'Mostrar notificação de atualização disponível', 'flexify-checkout-for-woocommerce' ), __( 'Ative essa opção para que seja exibido uma notificação de atualização disponível.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_debug_mode', __( 'Ativar modo depuração', 'flexify-checkout-for-woocommerce' ), __( 'Ative essa opção para ativar o modo depuração e ter acesso a informações no console do navegador, desativar minificação de scripts e estilos e demais detalhes para resolução de problemas.', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
                array(
                    'id' => 'about-actions',
                    'component' => 'about-actions',
                ),
                array(
                    'id' => 'about-system',
                    'component' => 'system-status',
                ),
            ),
        );
    }


    /**
     * Build a toggle field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_toggle( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'toggle',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a text field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_text( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'text',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a textarea field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_textarea( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'textarea',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a number field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_number( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'number',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a select field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<int,array<string,string>> $options Options list (value/label pairs).
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_select( $key, $label, $description, $options, $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'select',
            'label' => $label,
            'description' => $description,
            'options' => $options,
        ), $extra );
    }


    /**
     * Build a color field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_color( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'color',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a dimension field definition (numeric value + unit select).
     *
     * @since 6.0.0
     * @param string $key Value setting key.
     * @param string $unit_key Unit setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<int,array<string,string>> $units Unit options.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_dimension( $key, $unit_key, $label, $description, $units, $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'dimension',
            'unit_key' => $unit_key,
            'label' => $label,
            'description' => $description,
            'units' => $units,
        ), $extra );
    }


    /**
     * Build a media field definition (URL input + WordPress media picker).
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_media( $key, $label, $description = '', $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'media',
            'label' => $label,
            'description' => $description,
        ), $extra );
    }


    /**
     * Build a code editor field definition.
     *
     * @since 6.0.0
     * @param string $key Setting key.
     * @param string $label Field label.
     * @param string $description Field description.
     * @param string $language Editor language (css|javascript).
     * @param array<string,mixed> $extra Extra definition entries.
     * @return array<string,mixed>
     */
    private static function field_code( $key, $label, $description, $language, $extra = array() ) {
        return array_merge( array(
            'key' => $key,
            'type' => 'code-editor',
            'label' => $label,
            'description' => $description,
            'language' => $language,
        ), $extra );
    }


    /**
     * Build theme options from the registered themes.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_theme_options() {
        $options = array();

        foreach ( Settings_Panel::get_registered_themes() as $theme ) {
            $options[] = array(
                'value' => $theme['id'],
                'label' => $theme['label'],
                'disabled' => isset( $theme['status'] ) && 'active' !== $theme['status'],
            );
        }

        return $options;
    }


    /**
     * Build font family options from the saved fonts library.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_font_options() {
        $settings = Repository::get_settings();
        $fonts = isset( $settings['font_family'] ) && is_array( $settings['font_family'] ) ? $settings['font_family'] : array();
        $options = array();

        foreach ( $fonts as $font_id => $font ) {
            $options[] = array(
                'value' => (string) $font_id,
                'label' => isset( $font['font_name'] ) ? (string) $font['font_name'] : (string) $font_id,
            );
        }

        return $options;
    }


    /**
     * Build coupon field position options.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_coupon_position_options() {
        $options = array();

        if ( class_exists( Coupons::class ) && method_exists( Coupons::class, 'get_coupon_field_position' ) ) {
            foreach ( Coupons::get_coupon_field_position() as $position => $value ) {
                $options[] = array(
                    'value' => (string) $position,
                    'label' => isset( $value['title'] ) ? (string) $value['title'] : (string) $position,
                );
            }
        }

        return $options;
    }


    /**
     * Build options for the registered shipping methods.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_shipping_method_options() {
        $options = array();

        if ( function_exists('WC') && WC()->shipping ) {
            foreach ( WC()->shipping->get_shipping_methods() as $shipping ) {
                $options[] = array(
                    'value' => (string) $shipping->id,
                    'label' => (string) $shipping->method_title,
                );
            }
        }

        return $options;
    }


    /**
     * Build options for the configured WooCommerce shipping zones.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_shipping_zone_options() {
        $options = array();

        if ( ! class_exists('\WC_Shipping_Zones') ) {
            return $options;
        }

        // "Rest of the World" zone (id 0).
        $options[] = array(
            'value' => '0',
            'label' => __( 'Resto do mundo', 'flexify-checkout-for-woocommerce' ),
        );

        foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
            $options[] = array(
                'value' => (string) $zone['id'],
                'label' => (string) $zone['zone_name'],
            );
        }

        return $options;
    }


    /**
     * Build options for the registered payment gateways.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_payment_gateway_options() {
        $options = array();

        if ( function_exists('WC') && WC()->payment_gateways ) {
            foreach ( WC()->payment_gateways->payment_gateways() as $payment ) {
                $options[] = array(
                    'value' => (string) $payment->id,
                    'label' => (string) $payment->get_title(),
                );
            }
        }

        return $options;
    }


    /**
     * Build options for the registered user roles.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_user_role_options() {
        $translations = array(
            'administrator' => __( 'Administrador', 'flexify-checkout-for-woocommerce' ),
            'author' => __( 'Autor', 'flexify-checkout-for-woocommerce' ),
            'subscriber' => __( 'Assinante', 'flexify-checkout-for-woocommerce' ),
            'customer' => __( 'Cliente', 'flexify-checkout-for-woocommerce' ),
            'contributor' => __( 'Colaborador', 'flexify-checkout-for-woocommerce' ),
            'editor' => __( 'Editor', 'flexify-checkout-for-woocommerce' ),
            'shop_manager' => __( 'Gerente de loja', 'flexify-checkout-for-woocommerce' ),
            'translator' => __( 'Tradutor', 'flexify-checkout-for-woocommerce' ),
        );

        $options = array();

        foreach ( wp_roles()->roles as $role_key => $role ) {
            $options[] = array(
                'value' => (string) $role_key,
                'label' => isset( $translations[ $role_key ] ) ? $translations[ $role_key ] : (string) $role['name'],
            );
        }

        return $options;
    }


    /**
     * Build page options for the thank you contact link select.
     *
     * @since 6.0.0
     * @return array<int,array<string,string>>
     */
    private static function build_pages_options() {
        $options = array(
            array(
                'value' => 'custom_link',
                'label' => __( 'Link personalizado', 'flexify-checkout-for-woocommerce' ),
            ),
        );

        foreach ( get_pages() as $page ) {
            $options[] = array(
                'value' => (string) $page->ID,
                'label' => $page->post_title,
            );
        }

        return $options;
    }
}
