<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Fast Cart by Barn2
 * 
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Fastcart {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'hooks' ) );
	}

	/**
	 * Hooks.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function hooks() {
		if ( ! class_exists('Barn2\Plugin\WC_Fast_Cart\Plugin') ) {
			return;
		}

		remove_action( 'template_redirect', array( '\MeuMouse\Flexify_Checkout\Checkout\Sidebar', 'redirect_template_to_checkout' ), 10 );
	}
}

new Fastcart();