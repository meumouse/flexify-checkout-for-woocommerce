<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Breakdance builder.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Compat_Breakdance {
	/**
	 * Construct function.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp', array( __CLASS__, 'compat_breakdance' ) );
	}

	/**
	 * Disable Breakdance template functions.
	 *
	 * @return void
	 */
	public static function compat_breakdance() {
		if ( ! function_exists('Breakdance\ActionsFilters\template_include') || ! is_flexify_checkout() ) {
			return;
		}

		remove_filter( 'template_include', 'Breakdance\ActionsFilters\template_include', 1000000 );

		global $wp_filter;

		self::unhook_unonymous_callbacks( 'wc_get_template', 10 );
		self::unhook_unonymous_callbacks( 'wp_head', BREAKDANCE_ASSETS_PRIORITY );
		self::unhook_unonymous_callbacks( 'wp_footer', BREAKDANCE_ASSETS_PRIORITY );
	}

	/**
	 * Unhook anonymous/closure functions from the given action and priority.
	 *
	 * @param string $action   Action.
	 * @param int    $priority Priority.
	 *
	 * @return void
	 */
	public static function unhook_unonymous_callbacks( $action, $priority ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $action ] ) || empty( $wp_filter[ $action ]->callbacks[ $priority ] ) ) {
			return;
		}

		foreach ( $wp_filter[ $action ]->callbacks[ $priority ] as $function ) {
			if ( is_object( $function['function'] ) ) {
				unset( $wp_filter[ $action ]->callbacks[ $priority ] );
			}
		}
	}
}

new Compat_Breakdance();