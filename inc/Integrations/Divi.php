<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Divi
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Divi {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'remove_actions' ) );
	}


	/**
	 * Disable Divi checkout customisations.
	 * 
	 * @since 1.0.0
     * @version 5.0.0
     * @return void
	 */
	public static function remove_actions() {
		if ( ! function_exists('et_divi_print_stylesheet') || ! is_flexify_checkout() ) {
			return;
		}

		remove_action( 'wp_enqueue_scripts', 'et_divi_print_stylesheet', 99999998 );
		remove_action( 'wp_enqueue_scripts', 'et_requeue_child_theme_styles', 99999999 );
		remove_action( 'wp_enqueue_scripts', 'et_divi_enqueue_stylesheet' );
	}
}

new Divi();