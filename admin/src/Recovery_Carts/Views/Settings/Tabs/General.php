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
                    <?php esc_html_e( 'Enable cart recovery', 'flexify-checkout-for-woocommerce' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enable abandoned cart tracking and the Analytics, All carts and Processing queue pages in the Flexify Checkout menu. When disabled, these pages are hidden.', 'flexify-checkout-for-woocommerce' ); ?></span>
                </th>
                <td>
                    <input type="checkbox" id="enable_cart_recovery" class="toggle-switch" name="toggle_switchs[enable_cart_recovery]" value="yes" <?php checked( Admin::get_switch('enable_cart_recovery') !== 'no' ); ?> />
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Task scheduler', 'flexify-checkout-for-woocommerce' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Choose how notifications will be scheduled and processed.', 'flexify-checkout-for-woocommerce' ); ?></span>
                </th>
                <td>
                    <?php $task_scheduler = Admin::get_setting('task_scheduler'); ?>
                    <select class="form-select" name="task_scheduler">
                        <option value="wp_cron" <?php selected( $task_scheduler, 'wp_cron' ); ?>><?php esc_html_e( 'WP-Cron (default)', 'flexify-checkout-for-woocommerce' ); ?></option>
                        <option value="php_cron" <?php selected( $task_scheduler, 'php_cron' ); ?>><?php esc_html_e( 'PHP-Cron', 'flexify-checkout-for-woocommerce' ); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Enable lead capture modal', 'flexify-checkout-for-woocommerce' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enable this option to display the contact information collection modal when the user adds a product to the cart.', 'flexify-checkout-for-woocommerce' ); ?></span>
                </th>
                <td>
                    <input type="checkbox" id="enable_modal_add_to_cart" class="toggle-switch" name="toggle_switchs[enable_modal_add_to_cart]" value="yes" <?php checked( Admin::get_switch('enable_modal_add_to_cart') === 'yes' ); ?> />

                    <button id="collect_lead_modal_settings_trigger" class="btn btn-outline-primary ms-3"><?php esc_html_e( 'Configure', 'flexify-checkout-for-woocommerce' ) ?></button>

                    <div id="collect_lead_modal_settings_container" class="fcrc-popup-container">
                        <div class="fcrc-popup-content popup-lg">
                            <div class="fcrc-popup-header">
                                <h5 class="fcrc-popup-title"><?php esc_html_e( 'Configure lead capture modal', 'flexify-checkout-for-woocommerce' ); ?></h5>
                                <button id="collect_lead_modal_settings_close" class="btn-close fs-5" aria-label="<?php esc_attr_e( 'Close', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                            </div>

                            <div class="fcrc-popup-body">
                                <table class="popup-table">
                                    <tbody>
                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Lead capture modal title', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Title displayed inside the lead capture modal.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <textarea id="title_modal_add_to_cart" class="form-control" name="collect_lead_modal[title]"><?php echo esc_textarea( Admin::get_setting('collect_lead_modal')['title'] ) ?></textarea>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Modal action button title', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Title displayed on the button inside the lead capture modal.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="title_modal_send_lead" class="form-control" name="collect_lead_modal[button_title]" value="<?php echo Admin::get_setting('collect_lead_modal')['button_title'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Enable international phone in the modal', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enable this option to display the country list selector inside the lead capture modal (Recommended).', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50 d-flex align-items-center">
                                                <input type="checkbox" id="enable_international_phone_modal" class="toggle-switch" name="toggle_switchs[enable_international_phone_modal]" value="yes" <?php checked( Admin::get_switch('enable_international_phone_modal') === 'yes' ) ?>/>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Show modal only for logged-in users', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enable this option to display the lead capture modal only for logged-in users.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50 d-flex align-items-center">
                                                <input type="checkbox" id="display_modal_for_logged_users" class="toggle-switch" name="toggle_switchs[display_modal_for_logged_users]" value="yes" <?php checked( Admin::get_switch('display_modal_for_logged_users') === 'yes' ) ?>/>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Modal trigger selectors', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Selectors are the HTML components that show the modal when hovering over the element. Example: button[name="add-to-cart"]', 'flexify-checkout-for-woocommerce' ); ?></span>
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
                                                <?php esc_html_e( 'Message to be sent', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Message that will be sent to the user when submitting the form data.', 'flexify-checkout-for-woocommerce' ); ?></span>
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
                    <?php esc_html_e( 'Enable location collection via IP', 'flexify-checkout-for-woocommerce' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enable this option to collect the user\'s location through their IP.', 'flexify-checkout-for-woocommerce' ); ?></span>
                </th>
                <td>
                    <input type="checkbox" id="enable_get_location_from_ip" class="toggle-switch" name="toggle_switchs[enable_get_location_from_ip]" value="yes" <?php checked( Admin::get_switch('enable_get_location_from_ip') === 'yes' ); ?> />

                    <button id="ip_api_settings_trigger" class="btn btn-outline-primary ms-3"><?php esc_html_e( 'Configure API', 'flexify-checkout-for-woocommerce' ) ?></button>

                    <div id="ip_api_settings_container" class="fcrc-popup-container">
                        <div class="fcrc-popup-content popup-lg">
                            <div class="fcrc-popup-header">
                                <h5 class="fcrc-popup-title"><?php esc_html_e( 'Configure IP collection API', 'flexify-checkout-for-woocommerce' ); ?></h5>
                                <button id="ip_api_settings_close" class="btn-close fs-5" aria-label="<?php esc_attr_e( 'Close', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                            </div>

                            <div class="fcrc-popup-body">
                                <table class="popup-table">
                                    <tbody>
                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'API address', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enter the API address where the requests will be made; you can use the {ip_address} variable to reference the user\'s IP.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="ip_api_url" class="form-control" name="ip_api_settings[ip_api_url]" value="<?php echo Admin::get_setting('ip_api_settings')['ip_api_url'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Country code mapping', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enter the API\'s country code return in JSON object format. For example: body.countryCode', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="country_code_map" class="form-control" name="ip_api_settings[country_code_map]" value="<?php echo Admin::get_setting('ip_api_settings')['country_code_map'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'Country name mapping', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enter the API\'s country name return in JSON object format. For example: body.country', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="country_name_map" class="form-control" name="ip_api_settings[country_name_map]" value="<?php echo Admin::get_setting('ip_api_settings')['country_name_map'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'State/region name mapping', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enter the API\'s state or region name return in JSON object format. For example: body.regionName', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="state_name_map" class="form-control" name="ip_api_settings[state_name_map]" value="<?php echo Admin::get_setting('ip_api_settings')['state_name_map'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'City name mapping', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enter the API\'s city name return in JSON object format. For example: body.city', 'flexify-checkout-for-woocommerce' ); ?></span>
                                            </th>
                                            <td class="w-50">
                                                <input type="text" id="city_name_map" class="form-control" name="ip_api_settings[city_name_map]" value="<?php echo Admin::get_setting('ip_api_settings')['city_name_map'] ?>" />
                                            </td>
                                        </tr>

                                        <tr>
                                            <th class="w-50">
                                                <?php esc_html_e( 'User IP mapping', 'flexify-checkout-for-woocommerce' ); ?>
                                                <span class="fc-recovery-carts-description"><?php esc_html_e( 'Enter the API\'s user IP return in JSON object format. For example: body.query', 'flexify-checkout-for-woocommerce' ); ?></span>
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
                    <?php esc_html_e( 'Fallback for user name', 'flexify-checkout-for-woocommerce' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Alternative text for when the user\'s name cannot be retrieved.', 'flexify-checkout-for-woocommerce' ); ?></span>
                </th>
                <td>
                    <input type="text" id="fallback_first_name" class="form-control" name="fallback_first_name" value="<?php echo Admin::get_setting('fallback_first_name') ?>"/>
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Time for a cart to be considered abandoned', 'flexify-checkout-for-woocommerce' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Lets you set the time for a cart to be considered abandoned.', 'flexify-checkout-for-woocommerce' ); ?></span>
                </th>
                <td>
                    <div class="input-group">
                        <input type="number" id="time_for_lost_carts" class="form-control" name="time_for_lost_carts" min="1" value="<?php echo esc_attr( Admin::get_setting('time_for_lost_carts') ); ?>" />

                        <select id="time_unit_for_lost_carts" class="form-select" name="time_unit_for_lost_carts">
                            <option value="minutes" <?php selected( Admin::get_setting('time_unit_for_lost_carts'), 'minutes', true ) ?>><?php esc_html_e( 'Minutes', 'flexify-checkout-for-woocommerce' ); ?></option>
                            <option value="hours" <?php selected( Admin::get_setting('time_unit_for_lost_carts'), 'hours', true ) ?>><?php esc_html_e( 'Hours', 'flexify-checkout-for-woocommerce' ); ?></option>
                            <option value="days" <?php selected( Admin::get_setting('time_unit_for_lost_carts'), 'days', true ) ?>><?php esc_html_e( 'Days', 'flexify-checkout-for-woocommerce' ); ?></option>
                        </select>
                    </div>
                </td>
            </tr>

            <tr>
                <th>
                    <?php esc_html_e( 'Block follow-ups after purchase', 'flexify-checkout-for-woocommerce' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Prevents sending follow-ups to contacts who have already made a recent purchase with the same email or phone within a period of days.', 'flexify-checkout-for-woocommerce' ); ?></span>
                </th>
                <td>
                    <div class="input-group">
                        <input type="number" id="follow_up_purchase_block_days" class="form-control" name="follow_up_purchase_block_days" min="0" value="<?php echo esc_attr( Admin::get_setting('follow_up_purchase_block_days') ); ?>" />
                        <span class="input-group-text"><?php esc_html_e( 'Days', 'flexify-checkout-for-woocommerce' ); ?></span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>