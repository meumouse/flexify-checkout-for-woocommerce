<?php

namespace MeuMouse\Flexify_Checkout\Compatibility;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handles legacy filters for backward compatibility
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Legacy_Filters {

	/**
	 * Construct function
	 * 
	 * @since 5.0.0
	 * @return void
	 */
	public function __construct() {
		if ( defined('FLEXIFY_CHECKOUT_VERSION') && version_compare( FLEXIFY_CHECKOUT_VERSION, '5.0.0', '>=' ) ) {
			self::map_legacy_filters();
			add_action( 'init', array( $this, 'check_legacy_filters_usage' ), 999 );
		}
	}


	/**
	 * Map legacy filters to their new equivalents
	 *
	 * @since 5.0.0
	 * @return void
	 */
	protected static function map_legacy_filters() {
		$filters = self::get_legacy_filters();

		foreach ( $filters as $old_filter => $data ) {
			add_filter( $old_filter, array( self::class, 'deprecated_filter_callback' ), 9999, 99 );
		}
	}


	/**
	 * Callback to redirect deprecated filters
	 *
	 * @since 5.0.0
     * @param mixed $value | Value to be filtered
     * @param mixed ...$args | Arguments passed to the filter
	 * @return mixed
	 */
	public static function deprecated_filter_callback( $value, ...$args ) {
		$called_filter = current_filter();
		$filters = self::get_legacy_filters();

		if ( isset( $filters[ $called_filter ] ) ) {
			$new_filter = $filters[ $called_filter ]['new_filter'];
			$version = $filters[ $called_filter ]['version'];

			self::warn_deprecated_filter( $called_filter, $new_filter, $version );

			// redirect to new filter
			return apply_filters_ref_array( $new_filter, array_merge( [ $value ], $args ) );
		}

		return $value;
	}


	/**
	 * Check if legacy filters are being used
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function check_legacy_filters_usage() {
		$filters = self::get_legacy_filters();

		foreach ( $filters as $old_filter => $data ) {
			global $wp_filter;

			if ( ! isset( $wp_filter[ $old_filter ] ) ) {
				continue;
			}

			$hook = $wp_filter[ $old_filter ];

			if ( is_a( $hook, 'WP_Hook' ) ) {
				$callbacks = $hook->callbacks ?? [];

				// remove our own redirector
				unset( $callbacks[9999] );

				// if have other callbacks registered, emit warning
				if ( ! empty( $callbacks ) ) {
					self::warn_deprecated_filter( $old_filter, $data['new_filter'], $data['version'] );
				}
			}
		}
	}


	/**
	 * Render a deprecation warning for old filters
	 *
	 * @since 5.0.0
	 * @param string $old_filter | Old filter name
	 * @param string $new_filter | New filter name
	 * @param string $version | Version when the filter was deprecated
	 * @return void
	 */
	protected static function warn_deprecated_filter( $old_filter, $new_filter, $version ) {
		if ( function_exists( '_doing_it_wrong' ) ) {
			$message = sprintf(
				__( 'O filtro "%1$s" está obsoleto desde a versão %3$s. Use "%2$s" em seu lugar.', 'flexify-checkout-for-woocommerce' ),
				$old_filter,
				$new_filter,
				$version
			);

			_doing_it_wrong( $old_filter, $message, $version );
		}

		// Log on debug.log
		if ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
			error_log( "[FLEXIFY CHECKOUT] Obsolet filter detected: {$old_filter} → {$new_filter} (since version: {$version})" );
		}
	}


	/**
	 * Return legacy filters to their new equivalents
	 *
	 * @since 5.0.0
	 * @return array
	 */
	protected static function get_legacy_filters() {
		return array(
			'flexify_checkout_localstorage_fields' => array(
				'new_filter' => 'Flexify_Checkout/Fields/Set_Local_Storage_Fields',
				'version' => '5.0.0',
			),
			'flexify_custom_steps' => array(
				'new_filter' => 'Flexify_Checkout/Steps/Set_Custom_Steps',
				'version' => '5.0.0',
			),
			'flexify_checkout_allowed_sources' => array(
				'new_filter' => 'Flexify_Checkout/Assets/Set_Allowed_Sources',
				'version' => '5.0.0',
			),
			'flexify_auto_apply_coupon' => array(
				'new_filter' => 'Flexify_Checkout/Coupons/Auto_Apply_Coupon',
				'version' => '5.0.0',
			)
		);
	}
}