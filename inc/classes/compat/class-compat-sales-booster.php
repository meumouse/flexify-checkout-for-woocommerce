<?php

namespace MeuMouse\Flexify_Checkout\Compat\Sales_Booster;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Sales Booster.
 *
 * @since 1.0.0
 * @version 3.7.0
 * @package MeuMouse.com
 */
class Compat_Sales_Booster {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'iconic_wsb_supported_hooks', array( $this, 'v2_hook_support' ) );
    }

    /**
     * Add Fast Checkout Compatibility.
     *
     * @param array $hooks Hooks.
     *
     * @return array
     */
    public function v2_hook_support( $hooks ) {
        foreach ( $hooks as $key => &$hook ) {
            if ( 'woocommerce_after_checkout_form' === $key || $hook['flexify_support'] ) {
                continue;
            }

            $hook['flexify_support'] = true;
        }

        return $hooks;
    }
}

new Compat_Sales_Booster();