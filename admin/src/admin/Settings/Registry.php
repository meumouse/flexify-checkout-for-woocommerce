<?php

namespace MeuMouse\Flexify_Checkout\Admin\Settings;

use MeuMouse\Flexify_Checkout\API\License;
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

        return array(
            'version' => defined('FLEXIFY_CHECKOUT_VERSION') ? FLEXIFY_CHECKOUT_VERSION : '',
            'docs_link' => defined('FLEXIFY_CHECKOUT_DOCS_LINK') ? FLEXIFY_CHECKOUT_DOCS_LINK : '',
            'is_pro' => License::is_valid(),
            'license' => array(
                'key' => (string) get_option( 'flexify_checkout_license_key', '' ),
                'is_valid' => License::is_valid(),
                'title' => method_exists( License::class, 'license_title' ) ? License::license_title() : '',
                'expire' => method_exists( License::class, 'license_expire' ) ? License::license_expire() : '',
                'domain' => isset( $license_object->domain ) ? (string) $license_object->domain : '',
            ),
            'fields' => Fields_Store::get_fields(),
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
            ),
        );
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

                foreach ( $card['fields'] as $field ) {
                    if ( ! empty( $field['key'] ) ) {
                        $definitions[ $field['key'] ] = $field;
                    }
                }
            }
        }

        return $definitions;
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
            self::tab_texts(),
            self::tab_fields(),
            self::tab_conditions(),
            self::tab_integrations(),
            self::tab_styles(),
            self::tab_about(),
        );

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
        return array(
            'id' => 'general',
            'title' => __( 'Geral', 'flexify-checkout-for-woocommerce' ),
            'description' => __( 'Preferências básicas da finalização de compras.', 'flexify-checkout-for-woocommerce' ),
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'general-shop',
                    'title' => __( 'Loja e navegação', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Comportamento de navegação entre loja, carrinho e checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_back_to_shop_button', __( 'Mostrar botão Voltar à loja', 'flexify-checkout-for-woocommerce' ), __( 'Exibe um botão para retornar à loja na primeira etapa do checkout.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_link_image_products', __( 'Tornar imagem de produtos clicáveis', 'flexify-checkout-for-woocommerce' ), __( 'Permite abrir a página do produto ao clicar na imagem dentro do carrinho.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_skip_cart_page', __( 'Pular página do carrinho', 'flexify-checkout-for-woocommerce' ), __( 'Redireciona o cliente diretamente para o checkout ao adicionar um produto.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'display_opened_order_review_mobile', __( 'Mostrar resumo do pedido aberto por padrão', 'flexify-checkout-for-woocommerce' ), __( 'Em dispositivos móveis, exibe o resumo do pedido expandido.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_remove_quantity_select', __( 'Remover controles de quantidade', 'flexify-checkout-for-woocommerce' ), __( 'Oculta o seletor de quantidade dos produtos no checkout.', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
                array(
                    'id' => 'general-login',
                    'title' => __( 'Login e segurança', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Acesso de clientes e validações de credenciais.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'auto_display_login_modal', __( 'Abrir popup de login automaticamente', 'flexify-checkout-for-woocommerce' ), __( 'Exibe o popup de login quando o e-mail informado já possui cadastro.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'check_password_strenght', __( 'Ativar verificação de força da senha', 'flexify-checkout-for-woocommerce' ), __( 'Mostra um indicador de força de senha no cadastro.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'email_providers_suggestion', __( 'Ativar sugestão de preenchimento do e-mail', 'flexify-checkout-for-woocommerce' ), __( 'Sugere provedores de e-mail conhecidos durante a digitação.', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
                array(
                    'id' => 'general-cart',
                    'title' => __( 'Carrinho', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Ações disponíveis para o cliente dentro do checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_change_product_quantity', __( 'Permitir alterar quantidade de produtos', 'flexify-checkout-for-woocommerce' ), __( 'O cliente pode alterar quantidades diretamente no resumo do pedido.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_remove_product_cart', __( 'Permitir remover produtos do carrinho', 'flexify-checkout-for-woocommerce' ), __( 'O cliente pode remover itens diretamente no resumo do pedido.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'general-coupon',
                    'title' => __( 'Cupom de desconto', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Aplicação automática de cupons.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_auto_apply_coupon_code', __( 'Aplicar cupom de desconto automaticamente', 'flexify-checkout-for-woocommerce' ), __( 'Aplica um cupom predefinido ao abrir o checkout.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_text( 'coupon_code_for_auto_apply', __( 'Código do cupom de desconto', 'flexify-checkout-for-woocommerce' ), __( 'Cupom aplicado automaticamente quando o recurso está ativo.', 'flexify-checkout-for-woocommerce' ), array(
                            'visible_when' => array( array( 'field' => 'enable_auto_apply_coupon_code', 'equals' => 'yes' ) ),
                        ) ),
                    ),
                ),
                array(
                    'id' => 'general-countdown',
                    'title' => __( 'Contagem regressiva', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Cria senso de urgência exibindo um cronômetro no checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_checkout_countdown', __( 'Ativar contagem regressiva do checkout', 'flexify-checkout-for-woocommerce' ), __( 'Exibe um cronômetro com ação configurável ao expirar.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_text( 'checkout_countdown_title', __( 'Título da contagem regressiva', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_number( 'checkout_countdown_value', __( 'Duração total', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_select( 'checkout_countdown_unit', __( 'Unidade de duração', 'flexify-checkout-for-woocommerce' ), '', array(
                            array( 'value' => 'minutes', 'label' => __( 'Minutos', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'days', 'label' => __( 'Dias', 'flexify-checkout-for-woocommerce' ) ),
                        ), array(
                            'visible_when' => array( array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_select( 'checkout_countdown_action', __( 'Ação após expirar', 'flexify-checkout-for-woocommerce' ), '', array(
                            array( 'value' => 'hide', 'label' => __( 'Ocultar', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'restart', 'label' => __( 'Reiniciar contagem', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'logout', 'label' => __( 'Encerrar sessão do checkout', 'flexify-checkout-for-woocommerce' ) ),
                        ), array(
                            'visible_when' => array( array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'checkout_countdown_redirect_url', __( 'URL de redirecionamento', 'flexify-checkout-for-woocommerce' ), __( 'Para onde o cliente é enviado quando a sessão é encerrada.', 'flexify-checkout-for-woocommerce' ), array(
                            'type' => 'url',
                            'visible_when' => array(
                                array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ),
                                array( 'field' => 'checkout_countdown_action', 'equals' => 'logout' ),
                            ),
                        ) ),
                        self::field_select( 'countdown_background_type', __( 'Cor de fundo', 'flexify-checkout-for-woocommerce' ), '', array(
                            array( 'value' => 'primary', 'label' => __( 'Usar cor padrão', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'custom', 'label' => __( 'Definir personalizada', 'flexify-checkout-for-woocommerce' ) ),
                        ), array(
                            'visible_when' => array( array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_color( 'countdown_background_color', __( 'Cor de fundo personalizada', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array(
                                array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ),
                                array( 'field' => 'countdown_background_type', 'equals' => 'custom' ),
                            ),
                        ) ),
                        self::field_select( 'countdown_font_color_type', __( 'Cor do texto', 'flexify-checkout-for-woocommerce' ), '', array(
                            array( 'value' => 'default', 'label' => __( 'Usar cor padrão', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'custom', 'label' => __( 'Definir personalizada', 'flexify-checkout-for-woocommerce' ) ),
                        ), array(
                            'visible_when' => array( array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_color( 'countdown_font_color', __( 'Cor do texto personalizada', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array(
                                array( 'field' => 'enable_checkout_countdown', 'equals' => 'yes' ),
                                array( 'field' => 'countdown_font_color_type', 'equals' => 'custom' ),
                            ),
                        ) ),
                    ),
                ),
                array(
                    'id' => 'general-brazil',
                    'title' => __( 'Mercado brasileiro', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Preenchimento automático de endereço e dados de empresa.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_autofill_company_info', __( 'Preencher informações da empresa automaticamente', 'flexify-checkout-for-woocommerce' ), __( 'Consulta o CNPJ informado e preenche os dados da empresa.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_fill_address', __( 'Preencher endereço automaticamente', 'flexify-checkout-for-woocommerce' ), __( 'Consulta o CEP informado e preenche o endereço.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_text( 'get_address_api_service', __( 'Serviço de API de busca de endereço', 'flexify-checkout-for-woocommerce' ), __( 'Use {postcode} como placeholder do CEP consultado.', 'flexify-checkout-for-woocommerce' ), array(
                            'visible_when' => array( array( 'field' => 'enable_fill_address', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'api_auto_fill_address_param', __( 'Propriedade do endereço', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_fill_address', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'api_auto_fill_address_neightborhood_param', __( 'Propriedade do bairro', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_fill_address', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'api_auto_fill_address_city_param', __( 'Propriedade da cidade', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_fill_address', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'api_auto_fill_address_state_param', __( 'Propriedade do estado', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_fill_address', 'equals' => 'yes' ) ),
                        ) ),
                    ),
                ),
                array(
                    'id' => 'general-shipping',
                    'title' => __( 'Endereço e entrega', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Comportamento de endereços de entrega no checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_shipping_to_different_address', __( 'Permitir envio para endereço diferente', 'flexify-checkout-for-woocommerce' ), __( 'Habilita o formulário de endereço de entrega alternativo.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'validate_address_by_postcode', __( 'Ativar validação de endereço por CEP', 'flexify-checkout-for-woocommerce' ), __( 'Valida o CEP informado durante o preenchimento.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_toggle( 'enable_display_local_pickup_kangu', __( 'Mostrar endereço de loja física (Kangu)', 'flexify-checkout-for-woocommerce' ), __( 'Exibe pontos de retirada da integração Kangu.', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
                array(
                    'id' => 'general-orders',
                    'title' => __( 'Pedidos e recursos avançados', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Atribuição de pedidos, termos e APIs.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_assign_guest_orders', __( 'Atribuir pedidos de usuários convidados', 'flexify-checkout-for-woocommerce' ), __( 'Associa pedidos de convidados a contas existentes com o mesmo e-mail.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_terms_is_checked_default', __( 'Termos e condições marcados por padrão', 'flexify-checkout-for-woocommerce' ), __( 'O aceite de termos já inicia selecionado.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'enable_ddi_phone_field', __( 'Ativar telefone internacional', 'flexify-checkout-for-woocommerce' ), __( 'Adiciona seletor de DDI ao campo de telefone.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_toggle( 'direct_checkout_api', __( 'Ativar API de links de checkout direto', 'flexify-checkout-for-woocommerce' ), __( 'Permite criar links que montam o carrinho automaticamente.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                    ),
                ),
                array(
                    'id' => 'general-animations',
                    'title' => __( 'Animações de processamento', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Animações exibidas enquanto a compra é processada.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_animation_process_purchase', __( 'Ativar animações de processamento', 'flexify-checkout-for-woocommerce' ), __( 'Exibe animações Lottie durante o processamento da compra.', 'flexify-checkout-for-woocommerce' ), array( 'pro' => true ) ),
                        self::field_text( 'text_animation_process_purchase_1', __( 'Texto da animação 1', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_animation_process_purchase', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'animation_process_purchase_file_1', __( 'Arquivo da animação 1 (Lottie .json)', 'flexify-checkout-for-woocommerce' ), '', array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'enable_animation_process_purchase', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'text_animation_process_purchase_2', __( 'Texto da animação 2', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_animation_process_purchase', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'animation_process_purchase_file_2', __( 'Arquivo da animação 2 (Lottie .json)', 'flexify-checkout-for-woocommerce' ), '', array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'enable_animation_process_purchase', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'text_animation_process_purchase_3', __( 'Texto da animação 3', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'enable_animation_process_purchase', 'equals' => 'yes' ) ),
                        ) ),
                        self::field_text( 'animation_process_purchase_file_3', __( 'Arquivo da animação 3 (Lottie .json)', 'flexify-checkout-for-woocommerce' ), '', array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'enable_animation_process_purchase', 'equals' => 'yes' ) ),
                        ) ),
                    ),
                ),
                array(
                    'id' => 'general-thankyou',
                    'title' => __( 'Página de agradecimento', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Template e links da página exibida após a compra.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_thankyou_page_template', __( 'Ativar página de agradecimento Flexify', 'flexify-checkout-for-woocommerce' ), __( 'Substitui a página padrão de pedido recebido do WooCommerce.', 'flexify-checkout-for-woocommerce' ) ),
                        self::field_select( 'contact_page_thankyou', __( 'Página de contato', 'flexify-checkout-for-woocommerce' ), __( 'Página vinculada ao botão de contato na página de agradecimento.', 'flexify-checkout-for-woocommerce' ), self::build_pages_options(), array(
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
        $placeholders_hint = __( 'Placeholders disponíveis: {{ first_name }}, {{ last_name }}, {{ phone }}, {{ email }}, {{ address_1 }}, {{ number }}, {{ city }}, {{ state }}, {{ postcode }}', 'flexify-checkout-for-woocommerce' );

        return array(
            'id' => 'texts',
            'title' => __( 'Textos', 'flexify-checkout-for-woocommerce' ),
            'description' => __( 'Personalize os textos exibidos nas etapas do checkout.', 'flexify-checkout-for-woocommerce' ),
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'texts-steps',
                    'title' => __( 'Textos das etapas', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Títulos informativos exibidos em cada etapa.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_text( 'text_header_step_1', __( 'Etapa de contato', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_text( 'text_header_step_2', __( 'Etapa de entrega', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_text( 'text_header_step_3', __( 'Etapa de pagamento', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_text( 'text_shipping_methods_label', __( 'Título das formas de entrega', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_text( 'text_header_sidebar_right', __( 'Título dos itens do carrinho', 'flexify-checkout-for-woocommerce' ), '' ),
                    ),
                ),
                array(
                    'id' => 'texts-steppers',
                    'title' => __( 'Textos verificadores', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Rótulos do indicador de etapas (stepper).', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_text( 'text_check_step_1', __( 'Verificador da etapa de contato', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_text( 'text_check_step_2', __( 'Verificador da etapa de entrega', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_text( 'text_check_step_3', __( 'Verificador da etapa de pagamento', 'flexify-checkout-for-woocommerce' ), '' ),
                    ),
                ),
                array(
                    'id' => 'texts-buttons',
                    'title' => __( 'Botões', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Textos de botões de navegação.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_text( 'text_previous_step_button', __( 'Botão de voltar etapa', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_text( 'text_view_shop_thankyou', __( 'Botão de revisitar loja (agradecimento)', 'flexify-checkout-for-woocommerce' ), '' ),
                    ),
                ),
                array(
                    'id' => 'texts-reviews',
                    'title' => __( 'Resumos do cliente', 'flexify-checkout-for-woocommerce' ),
                    'description' => $placeholders_hint,
                    'fields' => array(
                        self::field_textarea( 'text_contact_customer_review', __( 'Resumo das informações de contato', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_textarea( 'text_shipping_customer_review', __( 'Resumo das informações de entrega', 'flexify-checkout-for-woocommerce' ), '' ),
                    ),
                ),
            ),
        );
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
            'description' => __( 'Gerencie os campos exibidos no formulário do checkout.', 'flexify-checkout-for-woocommerce' ),
            'layout' => 'cards',
            'cards' => array(
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
            'description' => __( 'Crie regras condicionais para campos, entregas e pagamentos.', 'flexify-checkout-for-woocommerce' ),
            'layout' => 'custom',
            'cards' => array(
                array(
                    'id' => 'conditions-manager',
                    'title' => __( 'Gerenciador de condições', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Exiba ou oculte componentes do checkout com base em regras.', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'conditions-manager',
                ),
            ),
        );
    }


    /**
     * Integrations tab definition.
     *
     * @since 6.0.0
     * @return array<string,mixed>
     */
    private static function tab_integrations() {
        return array(
            'id' => 'integrations',
            'title' => __( 'Integrações', 'flexify-checkout-for-woocommerce' ),
            'description' => __( 'Compatibilidades e integrações com plugins e serviços.', 'flexify-checkout-for-woocommerce' ),
            'layout' => 'custom',
            'cards' => array(
                array(
                    'id' => 'integrations-cards',
                    'title' => __( 'Integrações disponíveis', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Módulos de integração detectados nesta instalação.', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'integrations-list',
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
            'description' => __( 'Tema, cores, fontes e personalizações visuais do checkout.', 'flexify-checkout-for-woocommerce' ),
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'styles-theme',
                    'title' => __( 'Tema', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Selecione o template visual do checkout.', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'theme-picker',
                    'fields' => array(
                        self::field_select( 'flexify_checkout_theme', __( 'Tema do checkout', 'flexify-checkout-for-woocommerce' ), '', self::build_theme_options() ),
                    ),
                ),
                array(
                    'id' => 'styles-header',
                    'title' => __( 'Cabeçalho', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Marca exibida no topo do checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_select( 'checkout_header_type', __( 'Tipo de marca do cabeçalho', 'flexify-checkout-for-woocommerce' ), '', array(
                            array( 'value' => 'logo', 'label' => __( 'Logo (imagem)', 'flexify-checkout-for-woocommerce' ) ),
                            array( 'value' => 'text', 'label' => __( 'Texto', 'flexify-checkout-for-woocommerce' ) ),
                        ) ),
                        self::field_text( 'search_image_header_checkout', __( 'Imagem do cabeçalho', 'flexify-checkout-for-woocommerce' ), __( 'URL da imagem exibida no cabeçalho.', 'flexify-checkout-for-woocommerce' ), array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_text( 'logo_header_link', __( 'Link da imagem do cabeçalho', 'flexify-checkout-for-woocommerce' ), '', array(
                            'type' => 'url',
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_number( 'header_width_image_checkout', __( 'Largura da imagem', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_select( 'unit_header_width_image_checkout', __( 'Unidade da largura', 'flexify-checkout-for-woocommerce' ), '', $unit_options, array(
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'logo' ) ),
                        ) ),
                        self::field_text( 'text_brand_checkout_header', __( 'Texto do cabeçalho', 'flexify-checkout-for-woocommerce' ), '', array(
                            'visible_when' => array( array( 'field' => 'checkout_header_type', 'equals' => 'text' ) ),
                        ) ),
                    ),
                ),
                array(
                    'id' => 'styles-colors',
                    'title' => __( 'Cores', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Paleta de cores aplicada ao checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_color( 'set_primary_color', __( 'Cor primária', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_color( 'set_primary_color_on_hover', __( 'Cor secundária (hover)', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_color( 'set_placeholder_color', __( 'Cor do título dos campos', 'flexify-checkout-for-woocommerce' ), '' ),
                    ),
                ),
                array(
                    'id' => 'styles-dimensions',
                    'title' => __( 'Dimensões', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Medidas de elementos do checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_number( 'input_border_radius', __( 'Raio da borda dos elementos', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_select( 'unit_input_border_radius', __( 'Unidade do raio', 'flexify-checkout-for-woocommerce' ), '', $unit_options ),
                    ),
                ),
                array(
                    'id' => 'styles-fonts',
                    'title' => __( 'Fontes', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Tipografia do checkout. O gerenciador de fontes personalizado chega na próxima fase.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_select( 'set_font_family', __( 'Família de fontes', 'flexify-checkout-for-woocommerce' ), '', self::build_font_options() ),
                        self::field_number( 'h2_size', __( 'Tamanho dos títulos (h2)', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_select( 'h2_size_unit', __( 'Unidade do tamanho', 'flexify-checkout-for-woocommerce' ), '', $unit_options ),
                    ),
                ),
                array(
                    'id' => 'styles-shortcodes',
                    'title' => __( 'Cabeçalho e rodapé personalizados', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Shortcodes renderizados no topo e na base do checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_text( 'shortcode_header', __( 'Cabeçalho personalizado (shortcode)', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_text( 'shortcode_footer', __( 'Rodapé personalizado (shortcode)', 'flexify-checkout-for-woocommerce' ), '' ),
                    ),
                ),
                array(
                    'id' => 'styles-code',
                    'title' => __( 'Código personalizado', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'CSS e JavaScript adicionais aplicados apenas no checkout.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_code( 'custom_css_checkout', __( 'CSS personalizado', 'flexify-checkout-for-woocommerce' ), '', 'css' ),
                        self::field_code( 'custom_js_checkout', __( 'JavaScript personalizado', 'flexify-checkout-for-woocommerce' ), '', 'javascript' ),
                    ),
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
            'description' => __( 'Licença, atualizações e informações do sistema.', 'flexify-checkout-for-woocommerce' ),
            'layout' => 'cards',
            'cards' => array(
                array(
                    'id' => 'about-license',
                    'title' => __( 'Licença', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Ative sua licença para desbloquear os recursos Pro.', 'flexify-checkout-for-woocommerce' ),
                    'component' => 'license-manager',
                ),
                array(
                    'id' => 'about-updates',
                    'title' => __( 'Atualizações e depuração', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Comportamento de atualizações do plugin e modo de depuração.', 'flexify-checkout-for-woocommerce' ),
                    'fields' => array(
                        self::field_toggle( 'enable_auto_updates', __( 'Ativar atualizações automáticas', 'flexify-checkout-for-woocommerce' ), '', array( 'pro' => true ) ),
                        self::field_toggle( 'enable_update_notices', __( 'Mostrar notificação de atualização', 'flexify-checkout-for-woocommerce' ), '' ),
                        self::field_toggle( 'enable_debug_mode', __( 'Ativar modo de depuração', 'flexify-checkout-for-woocommerce' ), __( 'Registra logs detalhados para diagnóstico.', 'flexify-checkout-for-woocommerce' ) ),
                    ),
                ),
                array(
                    'id' => 'about-system',
                    'title' => __( 'Status do sistema', 'flexify-checkout-for-woocommerce' ),
                    'description' => __( 'Informações do ambiente para suporte.', 'flexify-checkout-for-woocommerce' ),
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
