<?php

namespace MeuMouse\Flexify_Checkout\Compatibility;

use MeuMouse\Flexify_Checkout\Core\Modules;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handles backward compatibility
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Backward_Compatibility {

	/**
	 * Construct function
	 * 
	 * @since 5.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'handle_module_conflicts' ), 999 );
		add_action( 'admin_init', array( $this, 'maybe_reinstall_inter_bank_module' ) );
	}


	/**
	 * Handle conflict with old versions of module-inter-bank-for-flexify-checkout
	 * 
	 * @since 5.0.0
	 * @return void
	 */
	public function handle_module_conflicts() {
		$main_plugin  = 'flexify-checkout-for-woocommerce/flexify-checkout-for-woocommerce.php';
		$addon_plugin = 'module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php';

		if ( get_option('flexify_reinstall_inter_bank_module') ) {
			return;
		}

		if ( ! function_exists('get_plugins') ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();

		if ( isset( $all_plugins[ $main_plugin ] ) && isset( $all_plugins[ $addon_plugin ] ) ) {
			$flexify_version = $all_plugins[ $main_plugin ]['Version'];
			$addon_version = $all_plugins[ $addon_plugin ]['Version'];

			if ( empty( $addon_version ) || ! is_string( $addon_version ) ) {
				return;
			}

			if (
				version_compare( $flexify_version, '5.0.0', '>=' ) &&
				version_compare( $addon_version, '1.3.0', '<' ) &&
				is_plugin_active( $addon_plugin )
			) {
				deactivate_plugins( $addon_plugin );
				delete_plugins( [ $addon_plugin ] );

				update_option( 'flexify_reinstall_inter_bank_module', true );
			}
		}
	}


	/**
	 * Reinstall module-inter-bank if it was removed due to incompatibility
	 * 
	 * @since 5.0.0
	 * @return void
	 */
	public function maybe_reinstall_inter_bank_module() {
		if ( ! get_option('flexify_reinstall_inter_bank_module') ) {
			return;
		}

		if ( ! current_user_can('install_plugins') ) {
			return;
		}

		if ( ! class_exists( Modules::class ) ) {
			return;
		}

		$modules = new Modules();
		$installed = $modules->install_plugin('https://github.com/meumouse/module-inter-bank-for-flexify-checkout/raw/main/dist/module-inter-bank-for-flexify-checkout.zip');

		if ( ! is_wp_error( $installed ) && $installed ) {
			activate_plugin('module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php');
		}

		delete_option('flexify_reinstall_inter_bank_module');
	}
}