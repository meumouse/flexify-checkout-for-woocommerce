<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Map WooCommerce payment gateways to a canonical, frontend-stable shape.
 *
 * The React checkout and any headless storefront depend only on a canonical
 * `kind` (pix/boleto/card/wallet/offline) plus capability flags, never on the
 * real gateway ID, so swapping a gateway in WooCommerce does not require a
 * frontend change. Mirrors the contract documented in the reference storefront
 * (src/lib/types/checkout-config.ts).
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Gateway_Catalog {

    /**
     * Resolve the canonical kind for a gateway.
     *
     * Uses simple id/title heuristics, overridable per gateway via the
     * Flexify_Checkout/Checkout/Gateway_Kind filter so payment add-ons can
     * declare their own mapping.
     *
     * @since 6.0.0
     * @param \WC_Payment_Gateway $gateway Gateway instance.
     * @return string One of: pix, boleto, card, wallet, offline.
     */
    public static function resolve_kind( $gateway ) {
        $id = strtolower( (string) $gateway->id );
        $haystack = $id . ' ' . strtolower( (string) $gateway->get_title() );
        $kind = 'offline';

        if ( strpos( $haystack, 'pix' ) !== false ) {
            $kind = 'pix';
        } elseif ( strpos( $haystack, 'boleto' ) !== false || strpos( $haystack, 'billet' ) !== false ) {
            $kind = 'boleto';
        } elseif ( preg_match( '/(credit|card|cartao|cartão|\bcc\b|stripe|cielo|rede|pagar\.?me|getnet|mercadopago|maxipago|adyen|appmax)/', $haystack ) ) {
            $kind = 'card';
        } elseif ( preg_match( '/(paypal|wallet|applepay|apple_pay|googlepay|google_pay|ame|picpay|samsung)/', $haystack ) ) {
            $kind = 'wallet';
        } elseif ( in_array( $id, array( 'cod', 'bacs', 'cheque' ), true ) ) {
            $kind = 'offline';
        }

        /**
         * Filter the canonical kind resolved for a payment gateway.
         *
         * @since 6.0.0
         * @param string $kind Resolved kind.
         * @param \WC_Payment_Gateway $gateway Gateway instance.
         */
        return (string) apply_filters( 'Flexify_Checkout/Checkout/Gateway_Kind', $kind, $gateway );
    }


    /**
     * Build the canonical list of available payment gateways.
     *
     * installments / discount_rule / publishable_key are placeholders (null/'')
     * until the dedicated payment modules land; the contract keeps the keys so
     * the frontend never needs to branch on their presence.
     *
     * @since 6.0.0
     * @return array<int,array<string,mixed>>
     */
    public static function get_gateways() {
        $gateways = array();

        if ( ! function_exists('WC') || ! WC()->payment_gateways ) {
            return $gateways;
        }

        // Prefer cart-aware availability, but the headless endpoint can run
        // without a cart context where availability checks may fail. Fall back
        // to the enabled gateways so the catalog is never empty by accident.
        try {
            $available = WC()->cart ? WC()->payment_gateways->get_available_payment_gateways() : array();
        } catch ( \Throwable $e ) {
            $available = array();
        }

        if ( empty( $available ) ) {
            foreach ( WC()->payment_gateways->payment_gateways() as $gateway ) {
                if ( 'yes' === $gateway->enabled ) {
                    $available[ $gateway->id ] = $gateway;
                }
            }
        }

        // Gateways that ship a WooCommerce Blocks payment integration. The React
        // checkout mounts their own payment component (SDK card form, tokenization,
        // 3DS) through the Blocks bridge instead of rendering a plain description.
        $block_names = Blocks_Payment_Bridge::get_active_block_names();

        foreach ( $available as $gateway ) {
            // The Blocks payment method name matches the gateway id for the
            // integrations we bridge (e.g. woo-mercado-pago-custom).
            $has_block = in_array( (string) $gateway->id, $block_names, true );

            $entry = array(
                'id' => (string) $gateway->id,
                'kind' => self::resolve_kind( $gateway ),
                'title' => wp_strip_all_tags( (string) $gateway->get_title() ),
                'description' => wp_strip_all_tags( (string) $gateway->get_description() ),
                'icon_url' => self::extract_icon_url( $gateway ),
                'supports_redirect' => method_exists( $gateway, 'get_return_url' ),
                'supports_tokenization' => (bool) $gateway->supports('tokenization'),
                'async_confirmation' => in_array( self::resolve_kind( $gateway ), array( 'pix', 'boleto' ), true ),
                // When true, the React checkout renders the gateway's Blocks payment
                // component (its own fields/SDK) and forwards the emitted data to the
                // Store API as payment_data. blocks_name is the registered method name.
                'blocks' => $has_block,
                'blocks_name' => $has_block ? (string) $gateway->id : '',
                'publishable_key' => '',
                'installments' => null,
                'discount_rule' => null,
            );

            /**
             * Filter a single gateway entry of the headless catalog.
             *
             * Payment modules attach installments/discount_rule/publishable_key here.
             *
             * @since 6.0.0
             * @param array $entry Gateway entry.
             * @param \WC_Payment_Gateway $gateway Gateway instance.
             */
            $gateways[] = apply_filters( 'Flexify_Checkout/Checkout/Gateway_Entry', $entry, $gateway );
        }

        return $gateways;
    }


    /**
     * Best-effort extraction of a gateway icon URL.
     *
     * @since 6.0.0
     * @param \WC_Payment_Gateway $gateway Gateway instance.
     * @return string
     */
    private static function extract_icon_url( $gateway ) {
        $icon_html = (string) $gateway->get_icon();

        if ( '' === $icon_html ) {
            return '';
        }

        if ( preg_match( '/src=["\']([^"\']+)["\']/', $icon_html, $matches ) ) {
            return esc_url_raw( $matches[1] );
        }

        return '';
    }
}
