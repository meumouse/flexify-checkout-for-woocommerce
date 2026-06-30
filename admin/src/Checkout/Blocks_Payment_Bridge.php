<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Bridge the WooCommerce Blocks payment method integrations into the React (Swift) checkout.
 *
 * The Swift checkout is a standalone React app that does NOT render any gateway's
 * payment fields. Gateways that ship a WooCommerce Blocks integration (Mercado
 * Pago, Stripe, …) register a React payment component plus the runtime scripts
 * (SDK, tokenization) it needs, and expose their data through `wc.wcSettings`.
 *
 * This class makes those same scripts and data available on the Swift checkout
 * page, exactly as the native WooCommerce Checkout block does, so the Swift app
 * can mount the gateway's own payment component (see lib/blocksPaymentBridge.js)
 * and forward the emitted payment data to the Store API as `payment_data`.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Blocks_Payment_Bridge {

    /**
     * Whether the WooCommerce Blocks payment registry is available.
     *
     * @since 6.0.0
     * @return bool
     */
    public static function is_available() {
        return class_exists( Package::class ) && class_exists( PaymentMethodRegistry::class );
    }


    /**
     * Resolve the Blocks payment method registry from the WooCommerce container.
     *
     * @since 6.0.0
     * @return PaymentMethodRegistry|null
     */
    private static function get_registry() {
        if ( ! self::is_available() ) {
            return null;
        }

        try {
            $registry = Package::container()->get( PaymentMethodRegistry::class );
        } catch ( \Throwable $e ) {
            return null;
        }

        return $registry instanceof PaymentMethodRegistry ? $registry : null;
    }


    /**
     * The names (ids) of payment gateways that expose an active Blocks integration.
     *
     * Used by the gateway catalog to flag which gateways the React checkout should
     * render through the Blocks bridge instead of as a plain description.
     *
     * @since 6.0.0
     * @return array<int,string> List of payment method type names (e.g. woo-mercado-pago-custom).
     */
    public static function get_active_block_names() {
        $registry = self::get_registry();

        if ( ! $registry ) {
            return array();
        }

        try {
            return array_values( array_map(
                static function ( $method ) {
                    return (string) $method->get_name();
                },
                $registry->get_all_active_registered()
            ) );
        } catch ( \Throwable $e ) {
            return array();
        }
    }


    /**
     * Enqueue every active Blocks payment method's scripts and expose its data.
     *
     * Mirrors what WooCommerce's Checkout block does on a native blocks checkout:
     * registers/enqueues the payment method script handles (which pull in the
     * gateway SDK + the wc-blocks-registry/wp-element dependencies) and publishes
     * each method's data under `wcSettings` so `getSetting('<name>_data')` works.
     *
     * @since 6.0.0
     * @return void
     */
    public static function enqueue() {
        $registry = self::get_registry();

        if ( ! $registry ) {
            return;
        }

        try {
            // Enqueue the script handles of every active payment method. Each MP
            // block handle declares wc-blocks-registry / wc-blocks-checkout /
            // wp-element / wc-settings as dependencies, and registering the handle
            // also registers the gateway's own checkout/SDK scripts.
            $handles = $registry->get_all_active_payment_method_script_dependencies();

            foreach ( $handles as $handle ) {
                wp_enqueue_script( $handle );
            }

            // Publish the per-method data the same way the Checkout block does, so
            // the gateway component can read getSetting('<name>_data') on register.
            $script_data = $registry->get_all_registered_script_data();
            $payment_method_data = isset( $script_data['paymentMethodData'] ) && is_array( $script_data['paymentMethodData'] )
                ? $script_data['paymentMethodData']
                : array();

            if ( empty( $payment_method_data ) ) {
                return;
            }

            $data_registry = Package::container()->get( AssetDataRegistry::class );

            if ( ! $data_registry->exists('paymentMethodData') ) {
                $data_registry->add( 'paymentMethodData', $payment_method_data );
            }

            // The gateway components read their own top-level key, e.g.
            // getSetting('woo-mercado-pago-custom_data'). Publish each one.
            foreach ( $payment_method_data as $name => $data ) {
                $key = $name . '_data';

                if ( ! $data_registry->exists( $key ) ) {
                    $data_registry->add( $key, $data );
                }
            }
        } catch ( \Throwable $e ) {
            // Never break the checkout if a third-party gateway integration throws.
            return;
        }
    }
}
