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
            <span class="fw-semibold"><?php esc_html_e( 'Customize the list of follow-up messages', 'flexify-checkout-for-woocommerce' ); ?></span>
            <span class="fc-recovery-carts-description"><?php esc_html_e( 'Follow-up notifications will be triggered at a predefined time to remind the user to complete their order.', 'flexify-checkout-for-woocommerce' ); ?></span>
        </div>

        <?php echo Admin_Components::follow_up_list(); ?>

        <button id="fcrc_add_new_follow_up_trigger" class="btn btn-primary mt-3"><?php esc_html_e( 'Add new event', 'flexify-checkout-for-woocommerce' ); ?></button>

        <div id="fcrc_add_new_follow_up_container" class="fcrc-popup-container">
            <div class="fcrc-popup-content">
                <div class="fcrc-popup-header">
                    <h5 class="fcrc-popup-title"><?php esc_html_e( 'Add new follow-up', 'flexify-checkout-for-woocommerce' ); ?></h5>
                    <button id="fcrc_add_new_follow_up_close" class="btn-close fs-5" aria-label="<?php esc_attr_e( 'Close', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                </div>

                <div class="fcrc-popup-body">
                    <div class="mb-5">
                        <label class="form-label text-left"><?php esc_html_e( 'Event name: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                        <input id="fcrc_add_new_follow_up_title" type="text" class="form-control" placeholder="<?php esc_attr_e( 'Event name', 'flexify-checkout-for-woocommerce' ); ?>">
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-left"><?php esc_html_e( 'Message: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                        <textarea id="fcrc_add_new_follow_up_message" class="form-control add-emoji-picker" placeholder="<?php esc_attr_e( 'Message that will be sent', 'flexify-checkout-for-woocommerce' ); ?>"></textarea>
                    </div>

                    <div class="placeholders mb-5">
                        <?php echo Admin_Components::render_placeholders(); ?>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-left mb-3"><?php esc_html_e( 'Notification channel: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                        
                        <div class="d-flex align-items-center">
                            <span class="fs-6 me-3"><?php esc_html_e( 'WhatsApp (Joinotify)', 'flexify-checkout-for-woocommerce' ); ?></span>
                            <input type="checkbox" id="fcrc_add_new_follow_up_channels_whatsapp" class="toggle-switch toggle-switch-sm mt-1"/>
                        </div>
                    </div>

                    <div class="mb-5">
                        <?php echo Admin_Components::render_coupon_form('new_follow_up_event'); ?>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-left mb-3"><?php esc_html_e( 'Sending interval (time):', 'flexify-checkout-for-woocommerce' ); ?></label>

                        <div class="row">
                            <div class="col">
                                <label class="form-label text-left"><?php esc_html_e( 'Start', 'flexify-checkout-for-woocommerce' ); ?></label>
                                <input id="fcrc_add_new_follow_up_start_time" type="time" class="form-control" placeholder="<?php esc_attr_e( '08:00', 'flexify-checkout-for-woocommerce' ); ?>">
                            </div>

                            <div class="col">
                                <label class="form-label text-left"><?php esc_html_e( 'End', 'flexify-checkout-for-woocommerce' ); ?></label>
                                <input id="fcrc_add_new_follow_up_end_time" type="time" class="form-control" placeholder="<?php esc_attr_e( '20:00', 'flexify-checkout-for-woocommerce' ); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label text-left mb-3"><?php esc_html_e( 'Delay: *', 'flexify-checkout-for-woocommerce' ); ?></label>

                        <div class="input-group get-delay-info">
                            <input id="fcrc_add_new_follow_up_delay_time" type="number" class="form-control" min="0" placeholder="<?php esc_attr_e( '1', 'flexify-checkout-for-woocommerce' ); ?>">

                            <select id="fcrc_add_new_follow_up_delay_type" class="form-select">
                                <option value="minutes"><?php esc_html_e( 'Minutes', 'flexify-checkout-for-woocommerce' ); ?></option>
                                <option value="hours"><?php esc_html_e( 'Hours', 'flexify-checkout-for-woocommerce' ); ?></option>
                                <option value="days"><?php esc_html_e( 'Days', 'flexify-checkout-for-woocommerce' ); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="fcrc-popup-footer">
                    <button id="fcrc_add_new_follow_up_save" class="btn btn-primary"><?php esc_html_e( 'Add', 'flexify-checkout-for-woocommerce' ); ?></button>
                </div> 
            </div>
        </div>
    </div>
</div>