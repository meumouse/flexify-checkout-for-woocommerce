<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Placeholders;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Coupons;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Opt_Out;

use MeuMouse\Joinotify\Core\Helpers as Joinotify_Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Joinotify integration class
 * 
 * @since 1.0.0
 * @version 1.3.5
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations
 * @author MeuMouse.com
 */
class Joinotify extends Integrations_Base {

    /**
     * Construct function
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'Flexify_Checkout/Recovery_Carts/Integrations/Joinotify', array( $this, 'joinotify_settings' ) );

        // send coupon message on collect lead
        add_action( 'Flexify_Checkout/Recovery_Carts/Lead_Collected', array( $this, 'send_coupon_message' ), 10, 2 );
    }


    /**
     * Display Joinotify settings
     * 
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function joinotify_settings() {
        // get current sender registered
        $current_sender = Admin::get_setting('joinotify_sender_phone');
        $current_senders = get_option('joinotify_get_phones_senders');
        $selected_value = $current_sender ?? '';
        
        // if empty sender, use first registered on Joinotify
        if ( empty( $selected_value ) || $selected_value === 'none' ) :
            if ( is_array( $current_senders ) && ! empty( $current_senders ) && function_exists('joinotify_get_first_sender') ) {
                $first_sender = joinotify_get_first_sender();

                if ( $first_sender ) {
                    $selected_value = $first_sender;
                }
            }
        endif; ?>

        <button id="fcrc_joinotify_settings_trigger" class="btn btn-outline-primary mb-5"><?php esc_html_e( 'Configure', 'flexify-checkout-for-woocommerce' ) ?></button>

        <div id="fcrc_joinotify_settings_container" class="fcrc-popup-container">
            <div class="fcrc-popup-content">
                <div class="fcrc-popup-header">
                    <h5 class="fcrc-popup-title"><?php esc_html_e( 'Integration settings: Joinotify', 'flexify-checkout-for-woocommerce' ); ?></h5>
                    <button id="fcrc_joinotify_settings_close" class="btn-close fs-5" aria-label="<?php esc_attr_e( 'Close', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                </div>

                <div class="fcrc-popup-body">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th class="w-50">
                                    <?php esc_html_e( 'Notifications sender', 'flexify-checkout-for-woocommerce' ); ?>
                                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Select a sender that will send the cart and order recovery notifications.', 'flexify-checkout-for-woocommerce' ); ?></span>
                                </th>
                                <td class="w-50">
                                    <select class="form-select" id="joinotify_sender_phone" name="joinotify_sender_phone">
                                        <option value="none" <?php selected( $selected_value, 'none', true ) ?>><?php esc_html_e( 'Select a sender', 'flexify-checkout-for-woocommerce' ) ?></option>
                                        
                                        <?php
                                        if ( is_array( $current_senders ) ) :
                                            foreach ( $current_senders as $sender ) : ?>
                                                <option value="<?php esc_attr_e( $sender ) ?>" <?php selected( $selected_value, $sender, true ) ?> class="get-sender-number"><?php echo class_exists('MeuMouse\Joinotify\Core\Helpers') ? esc_html( Joinotify_Helpers::validate_and_format_phone( $sender ) ) : esc_html( $sender ); ?></option>
                                            <?php endforeach;
                                        endif; ?>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <th class="w-50">
                                    <?php esc_html_e( 'Phone for follow-up testing', 'flexify-checkout-for-woocommerce' ); ?>
                                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Number that will receive the messages sent by the "Test" button of each follow-up. Enter it with country code + area code + number (e.g.: 5511999999999).', 'flexify-checkout-for-woocommerce' ); ?></span>
                                </th>
                                <td class="w-50">
                                    <input type="text" class="form-control" id="joinotify_test_phone" name="joinotify_test_phone" value="<?php echo esc_attr( Admin::get_setting('joinotify_test_phone') ); ?>" placeholder="5511999999999">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * Send coupon message for user
     * 
     * @since 1.0.0
     * @version 1.3.0
     * @param int $cart_id | Cart ID
     * @param array $lead_data | Lead data
     * @return void
     */
    public function send_coupon_message( $cart_id, $lead_data ) {
        // Freemium gate: sending the lead-capture coupon over WhatsApp is a Pro
        // capability, like every other recovery message dispatch.
        if ( ! Helpers::can_send_recovery_messages() ) {
            return;
        }

        // Respect opt-out for the captured contact.
        $lead_phone = isset( $lead_data['phone'] ) ? $lead_data['phone'] : '';
        $lead_email = isset( $lead_data['email'] ) ? $lead_data['email'] : '';

        if ( Opt_Out::is_suppressed( $lead_phone, $lead_email ) ) {
            return;
        }

        $modal_data = Admin::get_setting('collect_lead_modal');

        // check if message must be sent
        if ( Admin::get_switch('enable_modal_add_to_cart') !== 'yes' || $modal_data['coupon']['enabled'] !== 'yes' ) {
            return;
        }

        // check if Joinotify is active
        if ( function_exists('joinotify_send_whatsapp_message_text') ) {
            // Replace placeholders in the message
            $message = Placeholders::replace_placeholders( $modal_data['message'], $cart_id, $modal_data );
            $sender = Admin::get_setting('joinotify_sender_phone');
            $receiver = function_exists('joinotify_prepare_receiver') ? joinotify_prepare_receiver( $lead_data['phone'] ) : $lead_data['phone'];

            if ( FC_RECOVERY_CARTS_DEBUG_MODE ) {
                error_log( 'Sending coupon message for cart: ' . $cart_id );
                error_log( 'Message: ' . print_r( $message, true ) );
                error_log( 'Sender: ' . $sender );
                error_log( 'Receiver: ' . $receiver );
            }
            
            joinotify_send_whatsapp_message_text( $sender, $receiver, $message );
        }
    }
}