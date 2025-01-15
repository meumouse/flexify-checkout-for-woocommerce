<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Siteground Optimizer.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Siteground {
    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'sgo_css_combine_exclude', array( $this, 'compat_siteground_exclude' ) );
    }

    /**
     * Siteground optimizer compatibility.
     *
     * @param array $exclude_list Exclude list.
     *
     * @return array
     */
    public function compat_siteground_exclude( $exclude_list ) {
        $exclude_list[] = 'flexify-checkout-for-woocommerce';

        return $exclude_list;
    }
}

new Compat_Siteground();