<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Compatibility with Kadence.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Kadence {

    /**
     * Construct function.
     *
     * @since 1.0.0
     * @return void
     */
    public function __construct() {
        add_action( 'init', array( $this, 'hooks' ) );
    }

    /**
     * Hooks
     */
    public function hooks() {
        if ( ! defined('KADENCE_VERSION') ) {
            return;
        }
        
        add_filter( 'flexify_checkout_allowed_sources', array( $this, 'allow_kadnece_sources' ) );
    }

    /**
     * Allow essential Kadence CSS and JS.
     *
     * @param array $allowed_sources Allowed sources.
     *
     * @return array
     */
    public function allow_kadnece_sources( $allowed_sources ) {
        $allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/css/global.min.css';
        $allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/css/global.css';
        $allowed_sources[] = site_url() . '/wp-content/themes/kadence/assets/js/navigation.min.js';

        return $allowed_sources;
    }
}

new Compat_Kadence();