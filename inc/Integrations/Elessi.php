<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Elessi theme
 *
 * @since 3.8.8
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Elessi {
	
	/**
     * Construct function
     *
     * @since 3.8.8
     * @return void
     */
    public function __construct() {
		add_action( 'wp', array( $this, 'compat_elessi' ), 20 );
        add_action( 'init', array( $this, 'remove_actions' ), 5 );
	}


	/**
	 * Remove filters and actions
	 * 
	 * @since 3.8.8
     * @return void
	 */
	public function compat_elessi() {
		if ( ! is_flexify_template() || ! function_exists('elessi_setup') ) {
			return;
		}

        remove_action('wp_footer', 'elessi_run_static_content', 9);
	}


    /**
     * Remove WooCommerce actions on checkout
     * 
     * @since 3.8.8
     * @version 3.9.3
     * @return void
     */
    public function remove_actions() {
        if ( ! is_flexify_template() || ! function_exists('elessi_setup') ) {
			return;
		}

        remove_action('init', 'elessi_add_action_woo');
    }
}

new Elessi();