<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Admin;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Placeholders;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Admin components class
 * 
 * @since 1.0.0
 * @version 1.3.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Admin
 * @author MeuMouse.com
 */
class Components {

    /**
     * Register settings tabs through a filter
     *
     * @since 1.0.0
     * @version 1.3.0
     * @return array
     */
    public static function get_settings_tabs() {
        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Admin/Register_Settings_Tabs', array(
            'general' => array(
                'id' => 'general',
                'label' => esc_html__('Geral', 'fc-recovery-carts'),
                'icon' => '<svg class="fc-recovery-carts-tab-icon" xmlns="http://www.w3.org/2000/svg"><path d="M7.5 14.5c-1.58 0-2.903 1.06-3.337 2.5H2v2h2.163c.434 1.44 1.757 2.5 3.337 2.5s2.903-1.06 3.337-2.5H22v-2H10.837c-.434-1.44-1.757-2.5-3.337-2.5zm0 5c-.827 0-1.5-.673-1.5-1.5s.673-1.5 1.5-1.5S9 17.173 9 18s-.673 1.5-1.5 1.5zm9-11c-1.58 0-2.903 1.06-3.337 2.5H2v2h11.163c.434 1.44 1.757 2.5 3.337 2.5s2.903-1.06 3.337-2.5H22v-2h-2.163c-.434-1.44-1.757-2.5-3.337-2.5zm0 5c-.827 0-1.5-.673-1.5-1.5s.673-1.5 1.5-1.5 1.5.673 1.5 1.5-.673 1.5-1.5 1.5z"></path><path d="M12.837 5C12.403 3.56 11.08 2.5 9.5 2.5S6.597 3.56 6.163 5H2v2h4.163C6.597 8.44 7.92 9.5 9.5 9.5s2.903-1.06 3.337-2.5h9.288V5h-9.288zM9.5 7.5C8.673 7.5 8 6.827 8 6s.673-1.5 1.5-1.5S11 5.173 11 6s-.673 1.5-1.5 1.5z"></path></svg>',
                'file' => FC_RECOVERY_CARTS_INC . 'Views/Settings/Tabs/General.php',
            ),
            'follow_up' => array(
                'id' => 'follow_up',
                'label' => esc_html__('Follow Up', 'fc-recovery-carts'),
                'icon' => '<svg class="fc-recovery-carts-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 7.999a1 1 0 0 0-.516-.874l-9.022-5a1.003 1.003 0 0 0-.968 0l-8.978 4.96a1 1 0 0 0-.003 1.748l9.022 5.04a.995.995 0 0 0 .973.001l8.978-5A1 1 0 0 0 22 7.999zm-9.977 3.855L5.06 7.965l6.917-3.822 6.964 3.859-6.918 3.852z"></path><path d="M20.515 11.126 12 15.856l-8.515-4.73-.971 1.748 9 5a1 1 0 0 0 .971 0l9-5-.97-1.748z"></path><path d="M20.515 15.126 12 19.856l-8.515-4.73-.971 1.748 9 5a1 1 0 0 0 .971 0l9-5-.97-1.748z"></path></svg>',
                'file' => FC_RECOVERY_CARTS_INC . 'Views/Settings/Tabs/FollowUp.php',
            ),
            'payment_methods' => array(
                'id' => 'payment_methods',
                'label' => esc_html__('Formas de pagamento', 'fc-recovery-carts'),
                'icon' => '<svg class="fc-recovery-carts-tab-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zM4 6h16v2H4V6zm0 12v-6h16.001l.001 6H4z"></path><path d="M6 14h6v2H6z"></path></svg>',
                'file' => FC_RECOVERY_CARTS_INC . 'Views/Settings/Tabs/Payment_Methods.php',
            ),
            'integrations' => array(
                'id' => 'integrations',
                'label' => esc_html__('Integrações', 'fc-recovery-carts'),
                'icon' => '<svg class="fc-recovery-carts-tab-icon"><path d="M3 8h2v5c0 2.206 1.794 4 4 4h2v5h2v-5h2c2.206 0 4-1.794 4-4V8h2V6H3v2zm4 0h10v5c0 1.103-.897 2-2 2H9c-1.103 0-2-.897-2-2V8zm0-6h2v3H7zm8 0h2v3h-2z"></path></svg>',
                'file' => FC_RECOVERY_CARTS_INC . 'Views/Settings/Tabs/Integrations.php',
            ),
            'webhooks' => array(
                'id' => 'webhooks',
                'label' => esc_html__('Webhooks', 'fc-recovery-carts'),
                'icon' => '<svg class="fc-recovery-carts-tab-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g><title>webhook</title> <rect width="24" height="24" fill="none"></rect> <path d="M10.46,19a4.59,4.59,0,0,1-6.37,1.15,4.63,4.63,0,0,1,2.49-8.38l0,1.43a3.17,3.17,0,0,0-2.36,1.36A3.13,3.13,0,0,0,5,18.91a3.11,3.11,0,0,0,4.31-.84,3.33,3.33,0,0,0,.56-1.44v-1l5.58,0,.07-.11a1.88,1.88,0,1,1,.67,2.59,1.77,1.77,0,0,1-.83-1l-4.07,0A5,5,0,0,1,10.46,19m7.28-7.14a4.55,4.55,0,1,1-1.12,9,4.63,4.63,0,0,1-3.43-2.21L14.43,18a3.22,3.22,0,0,0,2.32,1.45,3.05,3.05,0,1,0,.75-6.06,3.39,3.39,0,0,0-1.53.18l-.85.44L12.54,9.2h-.22a1.88,1.88,0,1,1,.13-3.76A1.93,1.93,0,0,1,14.3,7.39a1.88,1.88,0,0,1-.46,1.15l1.9,3.51a4.75,4.75,0,0,1,2-.19M8.25,9.14A4.54,4.54,0,1,1,16.62,5.6a4.61,4.61,0,0,1-.2,4.07L15.18,9a3.17,3.17,0,0,0,.09-2.73A3.05,3.05,0,1,0,9.65,8.6,3.21,3.21,0,0,0,11,10.11l.39.21-3.07,5a1.09,1.09,0,0,1,.1.19,1.88,1.88,0,1,1-2.56-.83,1.77,1.77,0,0,1,1.23-.17l2.31-3.77A4.41,4.41,0,0,1,8.25,9.14Z"></path></g></svg>',
                'file' => FC_RECOVERY_CARTS_INC . 'Views/Settings/Tabs/Webhooks.php',
            ),
            'styles' => array(
                'id' => 'styles',
                'label' => esc_html__('Estilos', 'fc-recovery-carts'),
                'icon' => '<svg class="fc-recovery-carts-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M13.4 2.096a10.08 10.08 0 0 0-8.937 3.331A10.054 10.054 0 0 0 2.096 13.4c.53 3.894 3.458 7.207 7.285 8.246a9.982 9.982 0 0 0 2.618.354l.142-.001a3.001 3.001 0 0 0 2.516-1.426 2.989 2.989 0 0 0 .153-2.879l-.199-.416a1.919 1.919 0 0 1 .094-1.912 2.004 2.004 0 0 1 2.576-.755l.412.197c.412.198.85.299 1.301.299A3.022 3.022 0 0 0 22 12.14a9.935 9.935 0 0 0-.353-2.76c-1.04-3.826-4.353-6.754-8.247-7.284zm5.158 10.909-.412-.197c-1.828-.878-4.07-.198-5.135 1.494-.738 1.176-.813 2.576-.204 3.842l.199.416a.983.983 0 0 1-.051.961.992.992 0 0 1-.844.479h-.112a8.061 8.061 0 0 1-2.095-.283c-3.063-.831-5.403-3.479-5.826-6.586-.321-2.355.352-4.623 1.893-6.389a8.002 8.002 0 0 1 7.16-2.664c3.107.423 5.755 2.764 6.586 5.826.198.73.293 1.474.282 2.207-.012.807-.845 1.183-1.441.894z"></path><circle cx="7.5" cy="14.5" r="1.5"></circle><circle cx="7.5" cy="10.5" r="1.5"></circle><circle cx="10.5" cy="7.5" r="1.5"></circle><circle cx="14.5" cy="7.5" r="1.5"></circle></svg>',
                'file' => FC_RECOVERY_CARTS_INC . 'Views/Settings/Tabs/Styles.php',
            ),
        ));
    }


    /**
     * Render follow up list settings
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @return string
     */
    public static function follow_up_list() {
        $follow_up_list = Admin::get_setting('follow_up_events');

        ob_start(); ?>

        <?php if ( ! empty( $follow_up_list ) ) : ?>
            <ul class="list-group fcrc-follow-up-list mb-3">
                <?php foreach ( $follow_up_list as $key => $follow_up ) : ?>
                    <li class="list-group-item px-4 py-3" data-follow-up-item="<?php esc_attr_e( $key ) ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fcrc-follow-up-item-title fs-6"><?php esc_html_e( $follow_up['title'] ); ?></span>

                            <div class="d-flex align-items-center">
                                <div class="edit-follow-up-actions">
                                    <button id="fcrc_edit_follow_up_<?php esc_attr_e( $key ) ?>" class="btn btn-sm btn-outline-primary edit-follow-up-item"><?php esc_html_e( 'Editar', 'fc-recovery-carts' ); ?></button>

                                    <div id="fcrc_edit_follow_up_container_<?php esc_attr_e( $key ) ?>" class="fcrc-popup-container edit-follow-up-container" data-follow-up-item="<?php esc_attr_e( $key ) ?>">
                                        <div class="fcrc-popup-content">
                                            <div class="fcrc-popup-header">
                                                <h5 class="fcrc-popup-title"><?php esc_html_e( 'Editar evento de follow up', 'fc-recovery-carts' ); ?></h5>
                                                <button id="fcrc_edit_follow_up_close_<?php esc_attr_e( $key ) ?>" class="btn-close edit-follow-up-close fs-5 " aria-label="<?php esc_attr_e( 'Fechar', 'fc-recovery-carts' ); ?>"></button>
                                            </div>

                                            <div class="fcrc-popup-body">
                                                <div class="mb-5">
                                                    <label class="form-label text-left"><?php esc_html_e( 'Nome do evento: *', 'fc-recovery-carts' ); ?></label>
                                                    <input type="text" class="form-control get-follow-up-title" name="follow_up_events[<?php esc_attr_e( $key ) ?>][title]" value="<?php esc_attr_e( $follow_up['title'] ); ?>" placeholder="<?php esc_attr_e( 'Nome do evento', 'fc-recovery-carts' ); ?>">
                                                </div>

                                                <div class="mb-5">
                                                    <label class="form-label text-left"><?php esc_html_e( 'Mensagem: *', 'fc-recovery-carts' ); ?></label>
                                                    <textarea class="form-control get-follow-up-message add-emoji-picker" name="follow_up_events[<?php esc_attr_e( $key ) ?>][message]" placeholder="<?php esc_attr_e( 'Mensagem que será enviada', 'fc-recovery-carts' ); ?>"><?php echo esc_textarea( $follow_up['message'] ) ?></textarea>
                                                </div>

                                                <div class="placeholders mb-5">
                                                    <?php echo self::render_placeholders(); ?>
                                                </div>

                                                <div class="mb-5">
                                                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Canal da notificação: *', 'fc-recovery-carts' ); ?></label>
                                                    
                                                    <div class="d-flex align-items-center">
                                                        <span class="fs-6 me-3"><?php esc_html_e( 'WhatsApp (Joinotify)', 'fc-recovery-carts' ); ?></span>
                                                        <input type="checkbox" class="toggle-switch toggle-switch-sm mt-1 get-channel whatsapp" name="follow_up_events[<?php esc_attr_e( $key ) ?>][channels][whatsapp]" value="yes" <?php disabled( Admin::get_switch('enable_joinotify_integration') !== 'yes' ); checked( $follow_up['channels']['whatsapp'] === 'yes' ); ?> />
                                                    </div>
                                                </div>

                                                <div class="mb-5">
                                                    <?php echo self::render_coupon_form( 'follow_up_events['. $key .']', $follow_up['coupon'] ); ?>
                                                </div>

                                                <div class="mb-5">
                                                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Intervalo de envio (horário):', 'fc-recovery-carts' ); ?></label>

                                                    <div class="row">
                                                        <div class="col">
                                                            <label class="form-label text-left"><?php esc_html_e( 'Início', 'fc-recovery-carts' ); ?></label>
                                                            <input type="time" class="form-control" name="follow_up_events[<?php esc_attr_e( $key ) ?>][send_window][start_time]" value="<?php esc_attr_e( $follow_up['send_window']['start_time'] ?? '' ); ?>" placeholder="<?php esc_attr_e( '08:00', 'fc-recovery-carts' ); ?>">
                                                        </div>

                                                        <div class="col">
                                                            <label class="form-label text-left"><?php esc_html_e( 'Fim', 'fc-recovery-carts' ); ?></label>
                                                            <input type="time" class="form-control" name="follow_up_events[<?php esc_attr_e( $key ) ?>][send_window][end_time]" value="<?php esc_attr_e( $follow_up['send_window']['end_time'] ?? '' ); ?>" placeholder="<?php esc_attr_e( '20:00', 'fc-recovery-carts' ); ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-5">
                                                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Atraso: *', 'fc-recovery-carts' ); ?></label>

                                                    <div class="input-group get-delay-info">
                                                        <input type="number" class="form-control get-delay-time" name="follow_up_events[<?php esc_attr_e( $key ) ?>][delay_time]" min="0" placeholder="<?php esc_attr_e( '1', 'fc-recovery-carts' ); ?>" value="<?php esc_attr_e( $follow_up['delay_time'] ?? '' ); ?>">

                                                        <select class="form-select get-delay-unit" name="follow_up_events[<?php esc_attr_e( $key ) ?>][delay_type]">
                                                            <option value="minutes" <?php selected( $follow_up['delay_type'] ?? '', 'minutes' ); ?>><?php esc_html_e( 'Minutos', 'fc-recovery-carts' ); ?></option>
                                                            <option value="hours" <?php selected( $follow_up['delay_type'] ?? '', 'hours' ); ?>><?php esc_html_e( 'Horas', 'fc-recovery-carts' ); ?></option>
                                                            <option value="days" <?php selected( $follow_up['delay_type'] ?? '', 'days' ); ?>><?php esc_html_e( 'Dias', 'fc-recovery-carts' ); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button class="btn btn-sm btn-outline-secondary test-follow-up-item ms-3" data-follow-up-item="<?php esc_attr_e( $key ) ?>" title="<?php esc_attr_e( 'Enviar mensagem de teste para o telefone configurado nas opções do Joinotify', 'fc-recovery-carts' ); ?>">
                                    <?php esc_html_e( 'Testar', 'fc-recovery-carts' ); ?>
                                </button>

                                <button class="btn btn-icon btn-outline-danger delete-follow-up-item ms-3" data-follow-up-item="<?php esc_attr_e( $key ) ?>">
                                    <svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                                </button>

                                <input type="checkbox" class="toggle-switch ms-3" name="follow_up_events[<?php esc_attr_e( $key ) ?>][enabled]" value="yes" <?php checked( $follow_up['enabled'] === 'yes' ); ?> />
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <div class="alert alert-info w-fit"><?php esc_html_e( 'Nenhum evento de follow up adicionado ainda.', 'fc-recovery-carts' ); ?></div>
        <?php endif; ?>

        <?php return ob_get_clean();
    }


    /**
     * Render message placeholders
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @return string
     */
    public static function render_placeholders() {
        $placeholders = Placeholders::register_placeholders();

        // start buffer
        ob_start(); ?>

        <div class="message-placeholders w-fit">
            <label class="form-label text-left mb-3">
                <?php echo esc_html__( 'Variáveis de texto:', 'fc-recovery-carts' ); ?>
            </label>

            <?php foreach ( $placeholders as $placeholder => $data ) : ?>
                <div class="d-flex align-items-center mb-3">
                    <span class="fs-sm fs-italic me-2"><code><?php echo esc_html( $placeholder ); ?></code></span>
                    <span class="fs-sm mt-1"><?php echo esc_html( $data['title'] ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <?php return ob_get_clean();
    }


    /**
     * Render  coupon form component
     * 
     * @since 1.0.0
     * @param int $index | Current coupon index
     * @param array $settings | Current coupon settings
     * @return string
     */
    public static function render_coupon_form( $index = '', $settings = array() ) {
        $send_coupon = $settings['enabled'] ?? '';
        $generate_coupon = $settings['generate_coupon'] ?? '';
        $allow_free_shipping = $settings['allow_free_shipping'] ?? '';

        ob_start(); ?>

        <div class="coupon-form-wrapper">
            <div class="enable-send-coupon-wrapper mb-4 d-flex align-items-center">
                <label class="form-label text-left me-3"><?php esc_html_e( 'Ativar envio de cupom:', 'fc-recovery-carts' ); ?></label>
                <input type="checkbox" class="toggle-switch toggle-switch-sm enable-send-coupon" name="<?php printf( '%s[coupon][enabled]', $index ); ?>" value="yes" <?php checked( $send_coupon === 'yes' ); ?>>
            </div>

            <div class="generate-coupon-wrapper mb-4 d-flex align-items-center">
                <label class="form-label text-left me-3"><?php esc_html_e( 'Gerar cupom automaticamente:', 'fc-recovery-carts' ); ?></label>
                <input type="checkbox" class="toggle-switch toggle-switch-sm enable-generate-coupon" name="<?php printf( '%s[coupon][generate_coupon]', $index ); ?>" value="yes" <?php checked( $generate_coupon === 'yes' ); ?>>
            </div>

            <div class="coupon-preset-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Cupom de desconto: *', 'fc-recovery-carts' ); ?></label>

                <?php $coupons = get_posts( array(
                    'post_type' => 'shop_coupon',
                    'posts_per_page' => -1, // Get all coupons
                    'post_status' => 'publish',
                )); ?>

                <select name="<?php printf( '%s[coupon][coupon_code]', $index ); ?>" class="form-select get-coupon-code">
                    <option value="none" <?php selected( $settings['coupon_code'] ?? '', 'none', true ) ?>><?php esc_html_e( 'Selecione um cupom de desconto', 'fc-recovery-carts' ); ?></option>

                    <?php foreach ( $coupons as $coupon ) : 
                        $coupon_code = get_the_title( $coupon->ID ); ?>

                        <option value="<?php echo esc_attr( $coupon_code ); ?>" <?php selected( $settings['coupon_code'] ?? '', $coupon_code, true ) ?>><?php echo esc_html( $coupon_code ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="coupon-prefix-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Prefixo do cupom: *', 'fc-recovery-carts' ); ?></label>
                <input type="text" class="form-control get-coupon-prefix" name="<?php printf( '%s[coupon][coupon_prefix]', $index ); ?>" value="<?php esc_attr_e( $settings['coupon_prefix'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'CUPOM_', 'fc-recovery-carts' ); ?>">
            </div>

            <div class="discount-type-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Tipo do desconto: *', 'fc-recovery-carts' ); ?></label>

                <select class="form-select get-coupon-type" name="<?php printf( '%s[coupon][discount_type]', $index ); ?>">
                    <option value="fixed_cart" <?php selected( $settings['discount_type'] ?? '', 'fixed_cart' ); ?>><?php esc_html_e( 'Valor fixo', 'fc-recovery-carts' ); ?></option>
                    <option value="percent" <?php selected( $settings['discount_type'] ?? '', 'percent' ); ?>><?php esc_html_e( 'Percentual (%)', 'fc-recovery-carts' ); ?></option>
                </select>
            </div>

            <div class="coupon-value-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Valor do cupom: *', 'fc-recovery-carts' ); ?></label>
                <input type="number" class="form-control get-coupon-value" name="<?php printf( '%s[coupon][discount_value]', $index ); ?>" value="<?php esc_attr_e( $settings['discount_value'] ?? '' ); ?>">
            </div>

            <div class="coupon-allow-free-shipping-wrapper mb-4 d-flex align-items-center">
                <label class="form-label text-left me-3"><?php esc_html_e( 'Permitir frete grátis:', 'fc-recovery-carts' ); ?></label>
                <input type="checkbox" class="toggle-switch toggle-switch-sm get-coupon-allow-free-shipping" name="<?php printf( '%s[coupon][allow_free_shipping]', $index ); ?>" value="yes" <?php checked( $allow_free_shipping === 'yes' ); ?>>
            </div>

            <div class="coupon-expire-time-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Tempo de expiração do cupom: *', 'fc-recovery-carts' ); ?></label>

                <div class="input-group">
                    <input type="number" class="form-control get-coupon-expire-time" name="<?php printf( '%s[coupon][expiration_time]', $index ); ?>" value="<?php esc_attr_e( $settings['expiration_time'] ?? '' ); ?>">
                    
                    <select name="<?php printf( '%s[coupon][expiration_time_unit]', $index ); ?>" class="form-select get-coupon-expire-time-type">
                        <option value="minutes" <?php selected( $settings['expiration_time_unit'] ?? '', 'minutes' ); ?>><?php esc_html_e( 'Minutos', 'fc-recovery-carts' ); ?></option>
                        <option value="hours" <?php selected( $settings['expiration_time_unit'] ?? '', 'hours' ); ?>><?php esc_html_e( 'Horas', 'fc-recovery-carts' ); ?></option>
                        <option value="days" <?php selected( $settings['expiration_time_unit'] ?? '', 'days' ); ?>><?php esc_html_e( 'Dias', 'fc-recovery-carts' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="restrictions-wrapper mb-4">
                <span class="d-block text-left mb-4 fs-6"><?php esc_html_e( 'Restrições:', 'fc-recovery-carts' ); ?></span>

                <div class="mb-3">
                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Limite de uso por cupom:', 'fc-recovery-carts' ); ?></label>
                    <input type="number" class="get-coupon-limit-usage form-control" name="<?php printf( '%s[coupon][limit_usages]', $index ); ?>" value="<?php esc_attr_e( $settings['limit_usages'] ?? '' ); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Limite de uso por cliente:', 'fc-recovery-carts' ); ?></label>
                    <input type="number" class="get-coupon-limit-usage-per-user form-control" name="<?php printf( '%s[coupon][limit_usages_per_user]', $index ); ?>" value="<?php esc_attr_e( $settings['limit_usages_per_user'] ?? '' ); ?>">
                </div>
            </div>
        </div>

        <?php return ob_get_clean();
    }


    /**
     * Generate a list group with WooCommerce payment methods and delay time selection.
     *
     * @since 1.0.0
     * @param array $settings Optional. Previously saved settings.
     * @return string HTML output of the list group.
     */
    public static function get_payment_methods_delay_options( $settings = array() ) {
        // Get available payment methods
        $payment_gateways = WC()->payment_gateways->payment_gateways();

        // Delay time units
        $time_units = array(
            'minutes' => esc_html__( 'Minutos', 'fc-recovery-carts' ),
            'hours' => esc_html__( 'Horas', 'fc-recovery-carts' ),
            'days' => esc_html__( 'Dias', 'fc-recovery-carts' ),
        );

        ob_start(); ?>

        <ul class="list-group">
            <?php foreach ( $payment_gateways as $key => $gateway ) : ?>
                <li class="list-group-item d-flex justify-content-between align-items-center payment-method-delay-item">
                    <span class="payment-method-title"><?php echo esc_html( $gateway->get_title() ); ?></span>
                    
                    <div class="input-group">
                        <input type="number" class="form-control" name="payment_methods[<?php echo esc_attr( $key ); ?>][delay_time]" min="0" value="<?php echo esc_attr( $settings[ $key ]['delay_time'] ?? '' ); ?>">
                        
                        <select class="form-select" name="payment_methods[<?php echo esc_attr( $key ); ?>][delay_unit]">
                            <?php foreach ( $time_units as $unit_key => $unit_label ) : ?>
                                <option value="<?php echo esc_attr( $unit_key ); ?>" <?php selected( $settings[ $key ]['delay_unit'] ?? '', $unit_key, true ); ?>>
                                    <?php echo esc_html( $unit_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php return ob_get_clean();
    }


    /**
     * Total recovered widget for analytics dashboard
     * 
     * @since 1.3.0
     * @param int $total | Total recovered
     * @param int $period | Period to calculate the total recovered
     * @return string
     */
    public static function get_total_recovered( $total = 0, $period = 7 ) {
        ob_start(); ?>

        <div class="fcrc-analytics-widget total-recovered-widget">
            <div class="fcrc-analytics-widget-header">
                <span class="fcrc-widget-title"><?php printf( __( 'Total recuperado %s', 'fc-recovery-carts' ), wc_price( $total ) ); ?></span>
                <span class="fcrc-widget-description"><?php printf( __( 'Dados relacionados aos últimos %d dias', 'fc-recovery-carts' ), $period ); ?></span>
            </div>

            <div class="fcrc-analytics-widget-body">
                <div id="fcrc-recovered-chart" class="chart-container" style="height: 320px;"></div>
            </div
        </div>

        <?php return ob_get_clean();
    }


    /**
     * Register period filter for analytics dashboard
     * 
     * @since 1.3.0
     * @return array
     */
    public static function period_filter() {
        /**
         * Filter to add new period filter
         * 
         * @since 1.3.0
         * @return array
         */
        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Analytics/Period_Filter', array(
            7 => esc_html__( '7 dias', 'fc-recovery-carts' ),
            15 => esc_html__( '15 dias', 'fc-recovery-carts' ),
            30 => esc_html__( '30 dias', 'fc-recovery-carts' ),
            90 => esc_html__( '90 dias', 'fc-recovery-carts' ),
            365 => esc_html__( '365 dias', 'fc-recovery-carts' ),
        ));
    }


    /**
     * Get cart status
     * 
     * @since 1.3.0
     * @param int $period | Period to calculate the total recovered
     * @return string
     */
    public static function get_cart_status( $period = 7 ) {
        ob_start(); ?>

        <div class="fcrc-analytics-widget cart-status-widget">
            <div class="fcrc-analytics-widget-header">
                <span class="fcrc-widget-title"><?php esc_html_e( 'Status de carrinhos e pedidos', 'fc-recovery-carts' ); ?></span>
                <span class="fcrc-widget-description"><?php printf( __( 'Dados relacionados aos últimos %d dias', 'fc-recovery-carts' ), $period ); ?></span>
            </div>

            <div class="fcrc-analytics-widget-body">
                <div class="fcrc-carts-group">
                    <div class="fcrc-carts-group-item shopping">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Carrinhos ativos', 'fc-recovery-carts' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item abandoned">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Carrinhos abandonados', 'fc-recovery-carts' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item recovered">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Carrinhos recuperados', 'fc-recovery-carts' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item lost">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Carrinhos perdidos', 'fc-recovery-carts' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item leads">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Visitantes capturados', 'fc-recovery-carts' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item order_abandoned">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Pedidos abandonados', 'fc-recovery-carts' ); ?></span>
                    </div>
                </div>
            </div
        </div>

        <?php return ob_get_clean();
    }


    /**
     * Render carts sent notifications
     * 
     * @since 1.3.0
     * @param int $period | Period to calculate the total recovered
     * @return string
     */
    public static function render_sent_notifications( $period = 7 ) {
        ob_start(); ?>

        <div class="fcrc-analytics-widget cart-status-widget">
            <div class="fcrc-analytics-widget-header">
                <span class="fcrc-widget-title"><?php esc_html_e( 'Notificações de follow up enviadas', 'fc-recovery-carts' ); ?></span>
                <span class="fcrc-widget-description"><?php printf( __( 'Dados relacionados aos últimos %d dias', 'fc-recovery-carts' ), $period ); ?></span>
            </div>

            <div class="fcrc-analytics-widget-body">
                <div id="fcrc_sent_notifications_chart" class="chart-container" style="height: 320px;"></div>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}