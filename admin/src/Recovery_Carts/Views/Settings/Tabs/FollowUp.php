<?php

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Components as Admin_Components;

/**
 * Tab file for follow up on settings page
 * 
 * @since 1.0.0
 * @version 1.3.5
 * @package MeuMouse.com
 */

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="follow_up" class="nav-content">
    <div class="ps-5">
        <div class="mb-3">
            <span class="fw-semibold"><?php esc_html_e( 'Personalizar lista de mensagens de acompanhamento (Follow up)', 'fc-recovery-carts' ); ?></span>
            <span class="fc-recovery-carts-description"><?php esc_html_e( 'As notificações de follow up irão ser disparadas em um tempo pré-definido para lembrar o usuário de finalizar seu pedido.', 'fc-recovery-carts' ); ?></span>
        </div>

        <?php echo Admin_Components::follow_up_list(); ?>

        <button id="fcrc_add_new_follow_up_trigger" class="btn btn-primary mt-3"><?php esc_html_e( 'Adicionar novo evento', 'fc-recovery-carts' ); ?></button>

        <div id="fcrc_add_new_follow_up_container" class="fcrc-popup-container">
            <div class="fcrc-popup-content">
                <div class="fcrc-popup-header">
                    <h5 class="fcrc-popup-title"><?php esc_html_e( 'Adicionar novo follow up', 'fc-recovery-carts' ); ?></h5>
                    <button id="fcrc_add_new_follow_up_close" class="btn-close fs-5" aria-label="<?php esc_attr_e( 'Fechar', 'fc-recovery-carts' ); ?>"></button>
                </div>

                <div class="fcrc-popup-body">
                    <div class="mb-5">
                        <label class="form-label text-left"><?php esc_html_e( 'Nome do evento: *', 'fc-recovery-carts' ); ?></label>
                        <input id="fcrc_add_new_follow_up_title" type="text" class="form-control" placeholder="<?php esc_attr_e( 'Nome do evento', 'fc-recovery-carts' ); ?>">
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-left"><?php esc_html_e( 'Mensagem: *', 'fc-recovery-carts' ); ?></label>
                        <textarea id="fcrc_add_new_follow_up_message" class="form-control add-emoji-picker" placeholder="<?php esc_attr_e( 'Mensagem que será enviada', 'fc-recovery-carts' ); ?>"></textarea>
                    </div>

                    <div class="placeholders mb-5">
                        <?php echo Admin_Components::render_placeholders(); ?>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-left mb-3"><?php esc_html_e( 'Canal da notificação: *', 'fc-recovery-carts' ); ?></label>
                        
                        <div class="d-flex align-items-center">
                            <span class="fs-6 me-3"><?php esc_html_e( 'WhatsApp (Joinotify)', 'fc-recovery-carts' ); ?></span>
                            <input type="checkbox" id="fcrc_add_new_follow_up_channels_whatsapp" class="toggle-switch toggle-switch-sm mt-1"/>
                        </div>
                    </div>

                    <div class="mb-5">
                        <?php echo Admin_Components::render_coupon_form('new_follow_up_event'); ?>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-left mb-3"><?php esc_html_e( 'Intervalo de envio (horário):', 'fc-recovery-carts' ); ?></label>

                        <div class="row">
                            <div class="col">
                                <label class="form-label text-left"><?php esc_html_e( 'Início', 'fc-recovery-carts' ); ?></label>
                                <input id="fcrc_add_new_follow_up_start_time" type="time" class="form-control" placeholder="<?php esc_attr_e( '08:00', 'fc-recovery-carts' ); ?>">
                            </div>

                            <div class="col">
                                <label class="form-label text-left"><?php esc_html_e( 'Fim', 'fc-recovery-carts' ); ?></label>
                                <input id="fcrc_add_new_follow_up_end_time" type="time" class="form-control" placeholder="<?php esc_attr_e( '20:00', 'fc-recovery-carts' ); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-left mb-3"><?php esc_html_e( 'Atraso: *', 'fc-recovery-carts' ); ?></label>

                        <div class="input-group get-delay-info">
                            <input id="fcrc_add_new_follow_up_delay_time" type="number" class="form-control" min="0" placeholder="<?php esc_attr_e( '1', 'fc-recovery-carts' ); ?>">

                            <select id="fcrc_add_new_follow_up_delay_type" class="form-select">
                                <option value="minutes"><?php esc_html_e( 'Minutos', 'fc-recovery-carts' ); ?></option>
                                <option value="hours"><?php esc_html_e( 'Horas', 'fc-recovery-carts' ); ?></option>
                                <option value="days"><?php esc_html_e( 'Dias', 'fc-recovery-carts' ); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="fcrc-popup-footer">
                    <button id="fcrc_add_new_follow_up_save" class="btn btn-primary"><?php esc_html_e( 'Adicionar', 'fc-recovery-carts' ); ?></button>
                </div> 
            </div>
        </div>
    </div>
</div>