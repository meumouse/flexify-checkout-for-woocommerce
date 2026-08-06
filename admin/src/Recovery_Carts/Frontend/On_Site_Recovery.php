<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Frontend;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * On-site cart recovery (exit-intent bar).
 *
 * A lightweight, self-contained exit-intent bar shown on the cart and checkout
 * pages (and product pages) when the visitor moves to leave with items still in
 * the cart. It nudges them back to checkout before they abandon, complementing
 * the off-site follow-up messages. Reuses the same popup look and the shared
 * "--fc-recovery-carts-primary" styling as the lead-capture modal in
 * {@see Lead_Capture}, and is gated by the "enable_on_site_recovery" setting.
 *
 * Markup, style and behaviour are inlined in the footer so the feature needs no
 * extra built asset; the tiny inline script wires a desktop mouse-leave trigger
 * and a once-per-session guard.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Frontend
 * @author MeuMouse.com
 */
class On_Site_Recovery {

    /**
     * Construct function
     *
     * @since 6.0.0
     * @return void
     */
    public function __construct() {
        if ( Admin::get_switch('enable_on_site_recovery') === 'yes' ) {
            add_action( 'wp_footer', array( $this, 'render_bar' ) );
        }
    }


    /**
     * Whether the bar should render on the current request.
     *
     * Limited to the cart and checkout pages with a non-empty cart — the moments
     * an exit-intent nudge is worth showing.
     *
     * @since 6.0.0
     * @return bool
     */
    private function should_render() {
        if ( is_admin() ) {
            return false;
        }

        $on_cart_or_checkout = ( function_exists('is_cart') && is_cart() ) || ( function_exists('is_checkout') && is_checkout() );

        if ( ! $on_cart_or_checkout ) {
            return false;
        }

        // Only nudge when there is actually something to recover.
        if ( function_exists('WC') && WC()->cart && WC()->cart->is_empty() ) {
            return false;
        }

        /**
         * Filter whether the on-site recovery bar renders on this request.
         *
         * @since 6.0.0
         * @param bool $render | Whether to render the bar.
         */
        return (bool) apply_filters( 'Flexify_Checkout/Recovery_Carts/Render_On_Site_Bar', true );
    }


    /**
     * Render the exit-intent bar markup, style and behaviour.
     *
     * @since 6.0.0
     * @return void
     */
    public function render_bar() {
        if ( ! $this->should_render() ) {
            return;
        }

        $config = Admin::get_setting('on_site_recovery');
        $config = is_array( $config ) ? $config : array();

        $title = ! empty( $config['title'] ) ? $config['title'] : __( 'Wait! Your cart is still here', 'flexify-checkout-for-woocommerce' );
        $message = ! empty( $config['message'] ) ? $config['message'] : __( 'Complete your purchase before your items run out.', 'flexify-checkout-for-woocommerce' );
        $button = ! empty( $config['button_title'] ) ? $config['button_title'] : __( 'Complete my order', 'flexify-checkout-for-woocommerce' );

        $primary = Admin::get_setting('primary_color');
        $primary = $primary ? $primary : '#008aff';
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/');
        ?>
        <div class="fcrc-onsite-bar" id="fcrc-onsite-bar" role="dialog" aria-live="polite" hidden style="--fcrc-onsite-primary: <?php echo esc_attr( $primary ); ?>;">
            <div class="fcrc-onsite-inner">
                <div class="fcrc-onsite-text">
                    <strong class="fcrc-onsite-title"><?php echo esc_html( $title ); ?></strong>
                    <span class="fcrc-onsite-message"><?php echo esc_html( $message ); ?></span>
                </div>

                <div class="fcrc-onsite-actions">
                    <a class="fcrc-onsite-cta" href="<?php echo esc_url( $checkout_url ); ?>"><?php echo esc_html( $button ); ?></a>
                    <button type="button" class="fcrc-onsite-close" aria-label="<?php esc_attr_e( 'Close', 'flexify-checkout-for-woocommerce' ); ?>">&times;</button>
                </div>
            </div>
        </div>

        <style>
            .fcrc-onsite-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 99998; background: #fff; box-shadow: 0 -6px 24px rgba(15, 23, 42, 0.15); border-top: 3px solid var(--fcrc-onsite-primary); transform: translateY(100%); transition: transform 0.32s ease; }
            .fcrc-onsite-bar.is-visible { transform: translateY(0); }
            .fcrc-onsite-inner { max-width: 960px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 20px; flex-wrap: wrap; }
            .fcrc-onsite-text { display: flex; flex-direction: column; gap: 2px; }
            .fcrc-onsite-title { font-size: 15px; color: #0f172a; }
            .fcrc-onsite-message { font-size: 13px; color: #475569; }
            .fcrc-onsite-actions { display: flex; align-items: center; gap: 10px; }
            .fcrc-onsite-cta { display: inline-flex; align-items: center; background: var(--fcrc-onsite-primary); color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 10px 18px; border-radius: 8px; }
            .fcrc-onsite-cta:hover { filter: brightness(0.95); color: #fff; }
            .fcrc-onsite-close { background: transparent; border: 0; font-size: 22px; line-height: 1; color: #94a3b8; cursor: pointer; padding: 4px 8px; }
        </style>

        <script>
            ( function() {
                var bar = document.getElementById('fcrc-onsite-bar');

                if ( ! bar ) {
                    return;
                }

                var KEY = 'fcrc_onsite_shown';

                function alreadyShown() {
                    try {
                        return window.sessionStorage.getItem( KEY ) === 'yes';
                    } catch ( e ) {
                        return false;
                    }
                }

                function markShown() {
                    try {
                        window.sessionStorage.setItem( KEY, 'yes' );
                    } catch ( e ) {}
                }

                function show() {
                    if ( alreadyShown() ) {
                        return;
                    }

                    markShown();
                    bar.hidden = false;
                    // Force reflow so the transform transition runs.
                    void bar.offsetWidth;
                    bar.classList.add('is-visible');
                }

                function hide() {
                    bar.classList.remove('is-visible');
                    window.setTimeout( function() { bar.hidden = true; }, 320 );
                }

                document.addEventListener('mouseout', function( e ) {
                    // Desktop exit-intent: cursor leaves through the top of the viewport.
                    if ( ! e.relatedTarget && e.clientY <= 0 ) {
                        show();
                    }
                });

                bar.querySelector('.fcrc-onsite-close').addEventListener('click', hide);
            } )();
        </script>
        <?php
    }
}
