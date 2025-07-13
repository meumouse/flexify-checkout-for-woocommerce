<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Siteground Optimizer
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Siteground {

    /**
     * Construct function
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
     * @since 1.0.0
     * @version 5.0.0
     * @param array $exclude_list | Exclude list
     * @return array
     */
    public function compat_siteground_exclude( $exclude_list ) {
        $exclude_list[] = 'flexify-checkout-for-woocommerce';

        return $exclude_list;
    }
}

new Siteground();