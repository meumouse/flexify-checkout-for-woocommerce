<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Admin\Settings\Layout_Store;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Bridge the visual checkout builder layout onto the legacy server-rendered steps.
 *
 * The legacy checkout only supports the three semantic steps (customer-info,
 * address, payment). This bridge honors the builder by reordering the two
 * non-payment semantic steps to match the layout (payment always stays last),
 * while step labels are already mirrored into the text_check_step_N settings on
 * save. Custom steps and rich components are React-only and intentionally not
 * rendered here; their field items already fold into the nearest semantic step
 * via Layout_Store::sync_fields_from_layout().
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Legacy_Layout_Bridge {

    /**
     * Map a semantic step type to its legacy step slug.
     *
     * @since 6.0.0
     * @var array<string,string>
     */
    const TYPE_TO_SLUG = array(
        'contact' => 'customer-info',
        'shipping' => 'address',
        'payment' => 'payment',
    );


    /**
     * Hook the legacy step filter.
     *
     * @since 6.0.0
     */
    public function __construct() {
        add_filter( 'Flexify_Checkout/Steps/Set_Custom_Steps', array( $this, 'reorder_steps' ), 20 );
    }


    /**
     * Reorder the legacy semantic steps to follow the builder layout.
     *
     * @since 6.0.0
     * @param array<int,array<string,mixed>> $steps Legacy steps.
     * @return array<int,array<string,mixed>>
     */
    public function reorder_steps( $steps ) {
        if ( Admin_Options::get_setting('enable_checkout_builder') !== 'yes' || ! Layout_Store::has_saved_layout() ) {
            return $steps;
        }

        if ( ! is_array( $steps ) || empty( $steps ) ) {
            return $steps;
        }

        $layout = Layout_Store::get_layout();

        // Desired slug order from the layout (semantic steps only), payment last.
        $desired = array();

        foreach ( $layout['steps'] as $step ) {
            if ( isset( self::TYPE_TO_SLUG[ $step['type'] ] ) && 'payment' !== $step['type'] ) {
                $desired[] = self::TYPE_TO_SLUG[ $step['type'] ];
            }
        }

        if ( empty( $desired ) ) {
            return $steps;
        }

        // Index the existing legacy steps by slug so callbacks stay intact.
        $by_slug = array();
        $payment_step = null;
        $extras = array();

        foreach ( $steps as $step ) {
            $slug = $step['slug'] ?? '';

            if ( 'payment' === $slug ) {
                $payment_step = $step;
            } elseif ( in_array( $slug, $desired, true ) ) {
                $by_slug[ $slug ] = $step;
            } else {
                $extras[] = $step;
            }
        }

        $ordered = array();

        foreach ( $desired as $slug ) {
            if ( isset( $by_slug[ $slug ] ) ) {
                $ordered[] = $by_slug[ $slug ];
                unset( $by_slug[ $slug ] );
            }
        }

        // Preserve any unrecognized steps (e.g. third-party additions), then payment.
        $ordered = array_merge( $ordered, array_values( $by_slug ), $extras );

        if ( null !== $payment_step ) {
            $ordered[] = $payment_step;
        }

        return $ordered;
    }
}
