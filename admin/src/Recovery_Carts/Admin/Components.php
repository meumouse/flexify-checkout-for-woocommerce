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
                                    <button id="fcrc_edit_follow_up_<?php esc_attr_e( $key ) ?>" class="btn btn-sm btn-outline-primary edit-follow-up-item"><?php esc_html_e( 'Edit', 'flexify-checkout-for-woocommerce' ); ?></button>

                                    <div id="fcrc_edit_follow_up_container_<?php esc_attr_e( $key ) ?>" class="fcrc-popup-container edit-follow-up-container" data-follow-up-item="<?php esc_attr_e( $key ) ?>">
                                        <div class="fcrc-popup-content">
                                            <div class="fcrc-popup-header">
                                                <h5 class="fcrc-popup-title"><?php esc_html_e( 'Edit follow-up event', 'flexify-checkout-for-woocommerce' ); ?></h5>
                                                <button id="fcrc_edit_follow_up_close_<?php esc_attr_e( $key ) ?>" class="btn-close edit-follow-up-close fs-5 " aria-label="<?php esc_attr_e( 'Close', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                                            </div>

                                            <div class="fcrc-popup-body">
                                                <div class="mb-5">
                                                    <label class="form-label text-left"><?php esc_html_e( 'Event name: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                                    <input type="text" class="form-control get-follow-up-title" name="follow_up_events[<?php esc_attr_e( $key ) ?>][title]" value="<?php esc_attr_e( $follow_up['title'] ); ?>" placeholder="<?php esc_attr_e( 'Event name', 'flexify-checkout-for-woocommerce' ); ?>">
                                                </div>

                                                <div class="mb-5">
                                                    <label class="form-label text-left"><?php esc_html_e( 'Message: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                                    <textarea class="form-control get-follow-up-message add-emoji-picker" name="follow_up_events[<?php esc_attr_e( $key ) ?>][message]" placeholder="<?php esc_attr_e( 'Message that will be sent', 'flexify-checkout-for-woocommerce' ); ?>"><?php echo esc_textarea( $follow_up['message'] ) ?></textarea>
                                                </div>

                                                <div class="placeholders mb-5">
                                                    <?php echo self::render_placeholders(); ?>
                                                </div>

                                                <div class="mb-5">
                                                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Notification channel: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                                    
                                                    <div class="d-flex align-items-center">
                                                        <span class="fs-6 me-3"><?php esc_html_e( 'WhatsApp (Joinotify)', 'flexify-checkout-for-woocommerce' ); ?></span>
                                                        <input type="checkbox" class="toggle-switch toggle-switch-sm mt-1 get-channel whatsapp" name="follow_up_events[<?php esc_attr_e( $key ) ?>][channels][whatsapp]" value="yes" <?php disabled( Admin::get_switch('enable_joinotify_integration') !== 'yes' ); checked( $follow_up['channels']['whatsapp'] === 'yes' ); ?> />
                                                    </div>
                                                </div>

                                                <div class="mb-5">
                                                    <?php echo self::render_coupon_form( 'follow_up_events['. $key .']', $follow_up['coupon'] ); ?>
                                                </div>

                                                <div class="mb-5">
                                                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Sending interval (time):', 'flexify-checkout-for-woocommerce' ); ?></label>

                                                    <div class="row">
                                                        <div class="col">
                                                            <label class="form-label text-left"><?php esc_html_e( 'Start', 'flexify-checkout-for-woocommerce' ); ?></label>
                                                            <input type="time" class="form-control" name="follow_up_events[<?php esc_attr_e( $key ) ?>][send_window][start_time]" value="<?php esc_attr_e( $follow_up['send_window']['start_time'] ?? '' ); ?>" placeholder="<?php esc_attr_e( '08:00', 'flexify-checkout-for-woocommerce' ); ?>">
                                                        </div>

                                                        <div class="col">
                                                            <label class="form-label text-left"><?php esc_html_e( 'End', 'flexify-checkout-for-woocommerce' ); ?></label>
                                                            <input type="time" class="form-control" name="follow_up_events[<?php esc_attr_e( $key ) ?>][send_window][end_time]" value="<?php esc_attr_e( $follow_up['send_window']['end_time'] ?? '' ); ?>" placeholder="<?php esc_attr_e( '20:00', 'flexify-checkout-for-woocommerce' ); ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-5">
                                                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Delay: *', 'flexify-checkout-for-woocommerce' ); ?></label>

                                                    <div class="input-group get-delay-info">
                                                        <input type="number" class="form-control get-delay-time" name="follow_up_events[<?php esc_attr_e( $key ) ?>][delay_time]" min="0" placeholder="<?php esc_attr_e( '1', 'flexify-checkout-for-woocommerce' ); ?>" value="<?php esc_attr_e( $follow_up['delay_time'] ?? '' ); ?>">

                                                        <select class="form-select get-delay-unit" name="follow_up_events[<?php esc_attr_e( $key ) ?>][delay_type]">
                                                            <option value="minutes" <?php selected( $follow_up['delay_type'] ?? '', 'minutes' ); ?>><?php esc_html_e( 'Minutes', 'flexify-checkout-for-woocommerce' ); ?></option>
                                                            <option value="hours" <?php selected( $follow_up['delay_type'] ?? '', 'hours' ); ?>><?php esc_html_e( 'Hours', 'flexify-checkout-for-woocommerce' ); ?></option>
                                                            <option value="days" <?php selected( $follow_up['delay_type'] ?? '', 'days' ); ?>><?php esc_html_e( 'Days', 'flexify-checkout-for-woocommerce' ); ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button class="btn btn-sm btn-outline-secondary test-follow-up-item ms-3" data-follow-up-item="<?php esc_attr_e( $key ) ?>" title="<?php esc_attr_e( 'Send a test message to the phone configured in the Joinotify options', 'flexify-checkout-for-woocommerce' ); ?>">
                                    <?php esc_html_e( 'Test', 'flexify-checkout-for-woocommerce' ); ?>
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
            <div class="alert alert-info w-fit"><?php esc_html_e( 'No follow-up event added yet.', 'flexify-checkout-for-woocommerce' ); ?></div>
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
                <?php echo esc_html__( 'Text variables:', 'flexify-checkout-for-woocommerce' ); ?>
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
                <label class="form-label text-left me-3"><?php esc_html_e( 'Enable coupon sending:', 'flexify-checkout-for-woocommerce' ); ?></label>
                <input type="checkbox" class="toggle-switch toggle-switch-sm enable-send-coupon" name="<?php printf( '%s[coupon][enabled]', $index ); ?>" value="yes" <?php checked( $send_coupon === 'yes' ); ?>>
            </div>

            <div class="generate-coupon-wrapper mb-4 d-flex align-items-center">
                <label class="form-label text-left me-3"><?php esc_html_e( 'Generate coupon automatically:', 'flexify-checkout-for-woocommerce' ); ?></label>
                <input type="checkbox" class="toggle-switch toggle-switch-sm enable-generate-coupon" name="<?php printf( '%s[coupon][generate_coupon]', $index ); ?>" value="yes" <?php checked( $generate_coupon === 'yes' ); ?>>
            </div>

            <div class="coupon-preset-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Discount coupon: *', 'flexify-checkout-for-woocommerce' ); ?></label>

                <?php $coupons = get_posts( array(
                    'post_type' => 'shop_coupon',
                    'posts_per_page' => -1, // Get all coupons
                    'post_status' => 'publish',
                )); ?>

                <select name="<?php printf( '%s[coupon][coupon_code]', $index ); ?>" class="form-select get-coupon-code">
                    <option value="none" <?php selected( $settings['coupon_code'] ?? '', 'none', true ) ?>><?php esc_html_e( 'Select a discount coupon', 'flexify-checkout-for-woocommerce' ); ?></option>

                    <?php foreach ( $coupons as $coupon ) : 
                        $coupon_code = get_the_title( $coupon->ID ); ?>

                        <option value="<?php echo esc_attr( $coupon_code ); ?>" <?php selected( $settings['coupon_code'] ?? '', $coupon_code, true ) ?>><?php echo esc_html( $coupon_code ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="coupon-prefix-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Coupon prefix: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                <input type="text" class="form-control get-coupon-prefix" name="<?php printf( '%s[coupon][coupon_prefix]', $index ); ?>" value="<?php esc_attr_e( $settings['coupon_prefix'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'COUPON_', 'flexify-checkout-for-woocommerce' ); ?>">
            </div>

            <div class="discount-type-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Discount type: *', 'flexify-checkout-for-woocommerce' ); ?></label>

                <select class="form-select get-coupon-type" name="<?php printf( '%s[coupon][discount_type]', $index ); ?>">
                    <option value="fixed_cart" <?php selected( $settings['discount_type'] ?? '', 'fixed_cart' ); ?>><?php esc_html_e( 'Fixed value', 'flexify-checkout-for-woocommerce' ); ?></option>
                    <option value="percent" <?php selected( $settings['discount_type'] ?? '', 'percent' ); ?>><?php esc_html_e( 'Percentage (%)', 'flexify-checkout-for-woocommerce' ); ?></option>
                </select>
            </div>

            <div class="coupon-value-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Coupon value: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                <input type="number" class="form-control get-coupon-value" name="<?php printf( '%s[coupon][discount_value]', $index ); ?>" value="<?php esc_attr_e( $settings['discount_value'] ?? '' ); ?>">
            </div>

            <div class="coupon-allow-free-shipping-wrapper mb-4 d-flex align-items-center">
                <label class="form-label text-left me-3"><?php esc_html_e( 'Allow free shipping:', 'flexify-checkout-for-woocommerce' ); ?></label>
                <input type="checkbox" class="toggle-switch toggle-switch-sm get-coupon-allow-free-shipping" name="<?php printf( '%s[coupon][allow_free_shipping]', $index ); ?>" value="yes" <?php checked( $allow_free_shipping === 'yes' ); ?>>
            </div>

            <div class="coupon-expire-time-wrapper mb-4">
                <label class="form-label text-left mb-3"><?php esc_html_e( 'Coupon expiration time: *', 'flexify-checkout-for-woocommerce' ); ?></label>

                <div class="input-group">
                    <input type="number" class="form-control get-coupon-expire-time" name="<?php printf( '%s[coupon][expiration_time]', $index ); ?>" value="<?php esc_attr_e( $settings['expiration_time'] ?? '' ); ?>">
                    
                    <select name="<?php printf( '%s[coupon][expiration_time_unit]', $index ); ?>" class="form-select get-coupon-expire-time-type">
                        <option value="minutes" <?php selected( $settings['expiration_time_unit'] ?? '', 'minutes' ); ?>><?php esc_html_e( 'Minutes', 'flexify-checkout-for-woocommerce' ); ?></option>
                        <option value="hours" <?php selected( $settings['expiration_time_unit'] ?? '', 'hours' ); ?>><?php esc_html_e( 'Hours', 'flexify-checkout-for-woocommerce' ); ?></option>
                        <option value="days" <?php selected( $settings['expiration_time_unit'] ?? '', 'days' ); ?>><?php esc_html_e( 'Days', 'flexify-checkout-for-woocommerce' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="restrictions-wrapper mb-4">
                <span class="d-block text-left mb-4 fs-6"><?php esc_html_e( 'Restrictions:', 'flexify-checkout-for-woocommerce' ); ?></span>

                <div class="mb-3">
                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Usage limit per coupon:', 'flexify-checkout-for-woocommerce' ); ?></label>
                    <input type="number" class="get-coupon-limit-usage form-control" name="<?php printf( '%s[coupon][limit_usages]', $index ); ?>" value="<?php esc_attr_e( $settings['limit_usages'] ?? '' ); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-left mb-3"><?php esc_html_e( 'Usage limit per customer:', 'flexify-checkout-for-woocommerce' ); ?></label>
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
            'minutes' => esc_html__( 'Minutes', 'flexify-checkout-for-woocommerce' ),
            'hours' => esc_html__( 'Hours', 'flexify-checkout-for-woocommerce' ),
            'days' => esc_html__( 'Days', 'flexify-checkout-for-woocommerce' ),
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
                <span class="fcrc-widget-title"><?php printf( __( 'Total recovered %s', 'flexify-checkout-for-woocommerce' ), wc_price( $total ) ); ?></span>
                <span class="fcrc-widget-description"><?php printf( __( 'Data related to the last %d days', 'flexify-checkout-for-woocommerce' ), $period ); ?></span>
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
            7 => esc_html__( '7 days', 'flexify-checkout-for-woocommerce' ),
            15 => esc_html__( '15 days', 'flexify-checkout-for-woocommerce' ),
            30 => esc_html__( '30 days', 'flexify-checkout-for-woocommerce' ),
            90 => esc_html__( '90 days', 'flexify-checkout-for-woocommerce' ),
            365 => esc_html__( '365 days', 'flexify-checkout-for-woocommerce' ),
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
                <span class="fcrc-widget-title"><?php esc_html_e( 'Cart and order status', 'flexify-checkout-for-woocommerce' ); ?></span>
                <span class="fcrc-widget-description"><?php printf( __( 'Data related to the last %d days', 'flexify-checkout-for-woocommerce' ), $period ); ?></span>
            </div>

            <div class="fcrc-analytics-widget-body">
                <div class="fcrc-carts-group">
                    <div class="fcrc-carts-group-item shopping">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Active carts', 'flexify-checkout-for-woocommerce' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item abandoned">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Abandoned carts', 'flexify-checkout-for-woocommerce' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item recovered">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Recovered carts', 'flexify-checkout-for-woocommerce' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item lost">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Lost carts', 'flexify-checkout-for-woocommerce' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item leads">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Captured visitors', 'flexify-checkout-for-woocommerce' ); ?></span>
                    </div>

                    <div class="fcrc-carts-group-item order_abandoned">
                        <span class="fcrc-cart-item-title">0</span>
                        <span class="fcrc-cart-item-description"><?php esc_html_e( 'Abandoned orders', 'flexify-checkout-for-woocommerce' ); ?></span>
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
                <span class="fcrc-widget-title"><?php esc_html_e( 'Follow-up notifications sent', 'flexify-checkout-for-woocommerce' ); ?></span>
                <span class="fcrc-widget-description"><?php printf( __( 'Data related to the last %d days', 'flexify-checkout-for-woocommerce' ), $period ); ?></span>
            </div>

            <div class="fcrc-analytics-widget-body">
                <div id="fcrc_sent_notifications_chart" class="chart-container" style="height: 320px;"></div>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}