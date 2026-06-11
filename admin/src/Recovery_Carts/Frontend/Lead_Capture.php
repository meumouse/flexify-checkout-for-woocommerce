<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Frontend;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Responsible for managing lead data collection
 * 
 * @since 1.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Frontend
 * @author MeuMouse.com
 */
class Lead_Capture {

    /**
     * Construct function
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        // render lead capture modal
        if ( Admin::get_switch('enable_modal_add_to_cart') === 'yes' ) {
            add_action( 'wp_footer', array( $this, 'render_modal' ) );
        }
    }


    /**
     * Render lead capture modal
     * 
     * @since 1.0.0
     * @return void
     */
    public function render_modal() {
        if ( ! Helpers::is_product()
            || Admin::get_switch('display_modal_for_logged_users') === 'yes' && ! is_user_logged_in()
            || isset( $_COOKIE['fcrc_lead_collected'] ) && $_COOKIE['fcrc_lead_collected'] === 'yes'
            || is_user_logged_in() && get_user_meta( get_current_user_id(), '_fcrc_lead_collected', true ) ) {
            return;
        }

        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $first_name = ! empty( $current_user->first_name ) ? esc_attr( $current_user->first_name ) : '';
            $last_name = ! empty( $current_user->last_name ) ? esc_attr( $current_user->last_name ) : '';
            $email = ! empty( $current_user->user_email ) ? esc_attr( $current_user->user_email ) : '';
            $phone = get_user_meta( $current_user->ID, 'billing_phone', true );
            $phone = ! empty( $phone ) ? esc_attr( $phone ) : '';
        }

        ob_start(); ?>

        <div class="fcrc-popup-container lead-capture-modal">
            <div class="fcrc-popup-content">
                <div class="fcrc-popup-header">
                    <button class="fcrc-popup-close" aria-label="<?php esc_attr_e( 'Fechar', 'fc-recovery-carts' ); ?>"></button>
                </div>

                <div class="fcrc-popup-body">
                    <div class="fcrc-pre-checkout-title mb-5">
                        <div class="fcrc-pre-checkout-title-icon">
                            <svg class="coupon-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g> <path fill-rule="evenodd" d="M15,6 C15,6.55228475 14.5522847,7 14,7 C13.4477153,7 13,6.55228475 13,6 L3,6 L3,7.99946819 C4.2410063,8.93038753 5,10.3994926 5,12 C5,13.6005074 4.2410063,15.0696125 3,16.0005318 L3,18 L13,18 C13,17.4477153 13.4477153,17 14,17 C14.5522847,17 15,17.4477153 15,18 L21,18 L21,16.0005318 C19.7589937,15.0696125 19,13.6005074 19,12 C19,10.3994926 19.7589937,8.93038753 21,7.99946819 L21,6 L15,6 Z M23,18 C23,19.1045695 22.1045695,20 21,20 L3,20 C1.8954305,20 1,19.1045695 1,18 L1,14.8880798 L1.49927404,14.5992654 C2.42112628,14.0660026 3,13.0839642 3,12 C3,10.9160358 2.42112628,9.93399737 1.49927404,9.40073465 L1,9.11192021 L1,6 C1,4.8954305 1.8954305,4 3,4 L21,4 C22.1045695,4 23,4.8954305 23,6 L23,9.11192021 L22.500726,9.40073465 C21.5788737,9.93399737 21,10.9160358 21,12 C21,13.0839642 21.5788737,14.0660026 22.500726,14.5992654 L23,14.8880798 L23,18 Z M14,16 C13.4477153,16 13,15.5522847 13,15 C13,14.4477153 13.4477153,14 14,14 C14.5522847,14 15,14.4477153 15,15 C15,15.5522847 14.5522847,16 14,16 Z M14,13 C13.4477153,13 13,12.5522847 13,12 C13,11.4477153 13.4477153,11 14,11 C14.5522847,11 15,11.4477153 15,12 C15,12.5522847 14.5522847,13 14,13 Z M14,10 C13.4477153,10 13,9.55228475 13,9 C13,8.44771525 13.4477153,8 14,8 C14.5522847,8 15,8.44771525 15,9 C15,9.55228475 14.5522847,10 14,10 Z"></path></g></svg>
                        </div>

                        <h3 class="fcrc-pre-checkout-title-text"><?php echo Admin::get_setting('collect_lead_modal')['title'] ?></h3>
                    </div>

                    <div class="fcrc-pre-checkout-form">
                        <?php
                        /**
                         * Hook for display custom contents before modal fields
                         * 
                         * @since 1.0.0
                         */
                        do_action('Flexify_Checkout/Recovery_Carts/Before_Modal_Fields'); ?>

                        <div class="fcrc-contact-name-wrapper mb-4">
                            <div class="fcrc-first-name-wrapper">
                                <label class="form-label"><?php esc_html_e( 'Nome: *', 'fc-recovery-carts' ); ?></label>
                                <input type="text" class="fcrc-input fcrc-get-first-name" required placeholder="<?php esc_attr_e( 'João', 'fc-recovery-carts' ); ?>" value="<?php echo $first_name ?? ''; ?>">
                            </div>

                            <div class="fcrc-last-name-wrapper">
                                <label class="form-label"><?php esc_html_e( 'Sobrenome: *', 'fc-recovery-carts' ); ?></label>
                                <input type="text" class="fcrc-input fcrc-get-last-name" required placeholder="<?php esc_attr_e( 'da Silva', 'fc-recovery-carts' ); ?>" value="<?php echo $last_name ?? ''; ?>">
                            </div>
                        </div>

                        <div class="fcrc-contact-phone-wrapper mb-4">
                            <div class="me-3 w-100">
                                <label class="form-label"><?php esc_html_e( 'Telefone / WhatsApp: *', 'fc-recovery-carts' ); ?></label>
                                <input type="tel" class="fcrc-input fcrc-get-phone" required placeholder="<?php esc_attr_e( '+55 11 91234-5678', 'fc-recovery-carts' ); ?>" value="<?php echo $phone ?? ''; ?>">
                            </div>
                        </div>

                        <div class="fcrc-contact-email-wrapper mb-4">
                            <div class="me-3 w-100">
                                <label class="form-label"><?php esc_html_e( 'Seu melhor e-mail: *', 'fc-recovery-carts' ); ?></label>
                                <input type="email" class="fcrc-input fcrc-get-email" required placeholder="<?php esc_attr_e( 'joaodasilva@email.com', 'fc-recovery-carts' ); ?>" value="<?php echo $email ?? ''; ?>">
                            </div>
                        </div>
                        
                        <?php
                        /**
                         * Hook for display custom contents after modal fields
                         * 
                         * @since 1.0.0
                         */
                        do_action('Flexify_Checkout/Recovery_Carts/After_Modal_Fields'); ?>
                    </div>
                </div>

                <div class="fcrc-popup-footer">
                    <button class="fcrc-btn fcrc-btn-primary fcrc-trigger-send-lead"><?php echo Admin::get_setting('collect_lead_modal')['button_title'] ?></button>
                </div> 
            </div>
        </div>

        <?php print( ob_get_clean() );
    }
}