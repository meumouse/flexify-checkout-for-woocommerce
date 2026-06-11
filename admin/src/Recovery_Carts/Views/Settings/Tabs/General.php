<?php

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Components as Admin_Components;

/**
 * Template file for general settings
 * 
 * @since 1.0.0
 * @version 1.3.0
 * @package MeuMouse.com
 */

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="general" class="nav-content">
    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <?php esc_html_e( 'Agendador de tarefas', 'fc-recovery-carts' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Escolha como as notificações serão agendadas e processadas.', 'fc-recovery-carts' ); ?></span>
                </th>
                <td>
                    <?php $task_scheduler = Admin::get_setting('task_scheduler'); ?>
                    <select class="form-select" name="task_scheduler">
                        <option value="wp_cron" <?php selected( $task_scheduler, 'wp_cron' ); ?>><?php esc_html_e( 'WP-Cron (padrão)', 'fc-recovery-carts' ); ?></option>
                        <option value="php_cron" <?php selected( $task_scheduler, 'php_cron' ); ?>><?php esc_html_e( 'PHP-Cron', 'fc-recovery-carts' ); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Ativar modal de coleta de lead', 'fc-recovery-carts' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Ative essa opção para exibir o modal de coleta informações de contato quando o usuário adicionar um produto ao carrinho.', 'fc-recovery-carts' ); ?></span>
                </th>
                <td>
                    <input type="checkbox" id="enable_modal_add_to_cart" class="toggle-switch" name="toggle_switchs[enable_modal_add_to_cart]" value="yes" <?php checked( Admin::get_switch('enable_modal_add_to_cart') === 'yes' ); ?> />

                    <button id="collect_lead_modal_settings_trigger" class="btn btn-outline-primary ms-3"><?php esc_html_e( 'Configurar', 'fc-recovery-carts' ) ?></button>

                    <div id="collect_lead_modal_settings_container" class="fcrc-popup-container">
                        <div class="fcrc-popup-content popup-lg">
                            <div class="fcrc-popup-header">
                                <h5 class="fcrc-popup-title"><?php esc_html_e( 'Configurar modal de coleta de leads', 'fc-recovery-carts' ); ?></h5>
                                <button id="collect_lead_modal_settings_close" class="btn-close fs-5" aria-label="<?php esc_attr_e( 'Fechar', 'fc-recovery-carts' ); ?>"></button>
                            </div>

                            <div class="fcrc-popup-body">
                                <table class="popup-table">
                                    <tbody>
                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Título do modal de de coleta de leads', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Título exibido dentro do modal de coleta de leads.', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <textarea id="title_modal_add_to_cart" class="form-control" name="collect_lead_modal[title]"><?php echo esc_textarea( Admin::get_setting('collect_lead_modal')['title'] ) ?></textarea>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Título do botão de ação do modal', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Título exibido no botão dentro do modal de coleta de leads.', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="title_modal_send_lead" class="form-control" name="collect_lead_modal[button_title]" value="<?php echo Admin::get_setting('collect_lead_modal')['button_title'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Ativar telefone internacional no modal', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Ative essa opção para exibir o seletor de lista de países dentro do modal de coleta de leads (Recomendado).', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50 d-flex align-items-center">
                                                <input type="checkbox" id="enable_international_phone_modal" class="toggle-switch" name="toggle_switchs[enable_international_phone_modal]" value="yes" <?php checked( Admin::get_switch('enable_international_phone_modal') === 'yes' ) ?>/>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Mostrar modal apenas para usuários logados', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Ative essa opção para exibir o modal de coleta de leads apenas para usuários logados.', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50 d-flex align-items-center">
                                                <input type="checkbox" id="display_modal_for_logged_users" class="toggle-switch" name="toggle_switchs[display_modal_for_logged_users]" value="yes" <?php checked( Admin::get_switch('display_modal_for_logged_users') === 'yes' ) ?>/>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Seletores de acionamento do modal', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Os seletores são os componentes HTML que ao passar o mouse sobre o elemento mostra o modal. Exemplo: button[name="add-to-cart"]', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <textarea id="modal_triggers_list" class="form-control" name="collect_lead_modal[triggers_list]"><?php echo esc_textarea( Admin::get_setting('collect_lead_modal')['triggers_list'] ) ?></textarea>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-100">
                                                <?php echo Admin_Components::render_coupon_form( 'collect_lead_modal', Admin::get_setting('collect_lead_modal')['coupon'] ); ?>
                                            </th>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Mensagem a ser enviada', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Mensagem que será enviada ao usuário ao enviar os dados do formulário.', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <textarea id="message_to_send_lead_collected" class="form-control add-emoji-picker" name="collect_lead_modal[message]"><?php echo esc_textarea( Admin::get_setting('collect_lead_modal')['message'] ) ?></textarea>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-100">
                                                <div class="placeholders mt-4">
                                                    <?php echo Admin_Components::render_placeholders(); ?>
                                                </div>
                                            </th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Ativar coleta de localização através do IP', 'fc-recovery-carts' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Ative essa opção coletar a localização do usuário através do seu IP.', 'fc-recovery-carts' ); ?></span>
                </th>
                <td>
                    <input type="checkbox" id="enable_get_location_from_ip" class="toggle-switch" name="toggle_switchs[enable_get_location_from_ip]" value="yes" <?php checked( Admin::get_switch('enable_get_location_from_ip') === 'yes' ); ?> />

                    <button id="ip_api_settings_trigger" class="btn btn-outline-primary ms-3"><?php esc_html_e( 'Configurar API', 'fc-recovery-carts' ) ?></button>

                    <div id="ip_api_settings_container" class="fcrc-popup-container">
                        <div class="fcrc-popup-content popup-lg">
                            <div class="fcrc-popup-header">
                                <h5 class="fcrc-popup-title"><?php esc_html_e( 'Configurar API de coleta de IP', 'fc-recovery-carts' ); ?></h5>
                                <button id="ip_api_settings_close" class="btn-close fs-5" aria-label="<?php esc_attr_e( 'Fechar', 'fc-recovery-carts' ); ?>"></button>
                            </div>

                            <div class="fcrc-popup-body">
                                <table class="popup-table">
                                    <tbody>
                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Endereço da API', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Informe o endereço da API à serem feitas as requisições, podendo ser utilizado a variável {ip_address} para referenciar o IP do usuário.', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="ip_api_url" class="form-control" name="ip_api_settings[ip_api_url]" value="<?php echo Admin::get_setting('ip_api_settings')['ip_api_url'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Mapeamento do código do país', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Informe o retorno de código de país da API em formato de objeto JSON. Por exemplo: body.countryCode', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="country_code_map" class="form-control" name="ip_api_settings[country_code_map]" value="<?php echo Admin::get_setting('ip_api_settings')['country_code_map'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Mapeamento de nome de país', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Informe o retorno de nome de país da API em formato de objeto JSON. Por exemplo: body.country', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="country_name_map" class="form-control" name="ip_api_settings[country_name_map]" value="<?php echo Admin::get_setting('ip_api_settings')['country_name_map'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Mapeamento de nome de estado/região', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Informe o retorno de nome de estado ou região da API em formato de objeto JSON. Por exemplo: body.regionName', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="state_name_map" class="form-control" name="ip_api_settings[state_name_map]" value="<?php echo Admin::get_setting('ip_api_settings')['state_name_map'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Mapeamento de nome de cidade', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Informe o retorno de nome de cidade da API em formato de objeto JSON. Por exemplo: body.city', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="city_name_map" class="form-control" name="ip_api_settings[city_name_map]" value="<?php echo Admin::get_setting('ip_api_settings')['city_name_map'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Mapeamento de IP do usuário', 'fc-recovery-carts' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Informe o retorno do IP do usuário da API em formato de objeto JSON. Por exemplo: body.query', 'fc-recovery-carts' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="ip_map" class="form-control" name="ip_api_settings[ip_map]" value="<?php echo Admin::get_setting('ip_api_settings')['ip_map'] ?>" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Fallback para nome do usuário', 'fc-recovery-carts' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Texto alternativo para quando não for possível recuperar o nome do usuário.', 'fc-recovery-carts' ); ?></span>
                </th>
                <td>
                    <input type="text" id="fallback_first_name" class="form-control" name="fallback_first_name" value="<?php echo Admin::get_setting('fallback_first_name') ?>"/>
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Tempo para um carrinho ser considerado abandonado', 'fc-recovery-carts' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Permite definir o tempo para que um carrinho seja considerado abandonado.', 'fc-recovery-carts' ); ?></span>
                </th>
                <td>
                    <div class="input-group">
                        <input type="number" id="time_for_lost_carts" class="form-control" name="time_for_lost_carts" min="1" value="<?php echo esc_attr( Admin::get_setting('time_for_lost_carts') ); ?>" />

                        <select id="time_unit_for_lost_carts" class="form-select" name="time_unit_for_lost_carts">
                            <option value="minutes" <?php selected( Admin::get_setting('time_unit_for_lost_carts'), 'minutes', true ) ?>><?php esc_html_e( 'Minutos', 'fc-recovery-carts' ); ?></option>
                            <option value="hours" <?php selected( Admin::get_setting('time_unit_for_lost_carts'), 'hours', true ) ?>><?php esc_html_e( 'Horas', 'fc-recovery-carts' ); ?></option>
                            <option value="days" <?php selected( Admin::get_setting('time_unit_for_lost_carts'), 'days', true ) ?>><?php esc_html_e( 'Dias', 'fc-recovery-carts' ); ?></option>
                        </select>
                    </div>
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Bloquear follow ups após compra', 'fc-recovery-carts' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Impede o envio de follow ups para contatos que já fizeram uma compra recente com o mesmo e-mail ou telefone durante um período de dias.', 'fc-recovery-carts' ); ?></span>
                </th>
                <td>
                    <div class="input-group">
                        <input type="number" id="follow_up_purchase_block_days" class="form-control" name="follow_up_purchase_block_days" min="0" value="<?php echo esc_attr( Admin::get_setting('follow_up_purchase_block_days') ); ?>" />
                        <span class="input-group-text"><?php esc_html_e( 'Dias', 'fc-recovery-carts' ); ?></span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>