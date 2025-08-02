<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( function_exists('Breakdance\ActionsFilters\template_include') ) {
	/**
	 * Compatibility with Breakdance builder
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @package MeuMouse.com
	 */
	class Breakdance {

		/**
		 * Construct function
		 * 
		 * @since 1.0.0
		 * @return void
		 */
		public function __construct() {
			add_action( 'wp', array( $this, 'disable_template_functions' ) );
		}


		/**
		 * Disable Breakdance template functions
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @return void
		 */
		public function disable_template_functions() {
			if ( ! function_exists('Breakdance\ActionsFilters\template_include') || ! is_flexify_checkout() ) {
				return;
			}

			remove_filter( 'template_include', 'Breakdance\ActionsFilters\template_include', 1000000 );

			global $wp_filter;

			Helpers::unhook_unonymous_callbacks( 'wc_get_template', 10 );
			Helpers::unhook_unonymous_callbacks( 'wp_head', BREAKDANCE_ASSETS_PRIORITY );
			Helpers::unhook_unonymous_callbacks( 'wp_footer', BREAKDANCE_ASSETS_PRIORITY );
		}
	}
}