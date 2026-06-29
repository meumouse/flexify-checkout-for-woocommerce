<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle with Flexify Checkout theme templates
 *
 * @since 5.0.0
 * @package MeuMouse\Flexify_Checkout\Checkout
 * @author MeuMouse.com
 */
class Themes {

    /**
     * Construct function
     *
     * @since 5.0.0
     * @return void
     */
    public function __construct() {
        // load template
        add_filter( 'template_include', array( $this, 'load_checkout_theme' ), 100 );
    }


    /**
	 * Get checkout theme
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return string
	 */
	public static function get_theme() {
		return Admin_Options::get_setting('flexify_checkout_theme') ? Admin_Options::get_setting('flexify_checkout_theme') : 'modern';
	}


    /**
	 * Include checkout theme template
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @param string $template | Template path
	 * @return string
	 */
	public function load_checkout_theme( $template ) {
		if ( ! is_flexify_template() ) {
			return $template;
		}

		$theme = self::get_theme();

		remove_action( 'woocommerce_before_shop_loop', 'wc_print_notices', 10 );
		remove_action( 'woocommerce_before_single_product', 'wc_print_notices', 10 );

		// set active checkout template
		define( 'IS_FLEXIFY_CHECKOUT', true );

		$template_file = FLEXIFY_CHECKOUT_PATH . 'templates/template-' . $theme . '.php';

		// The "Swift" theme has no classic template of its own — it is served by
		// React_Checkout (priority 101) when the React checkout can render. When
		// it can't (e.g. invalid license), fall back to the modern template so
		// the store never loads a missing template.
		if ( ! file_exists( $template_file ) ) {
			$template_file = FLEXIFY_CHECKOUT_PATH . 'templates/template-modern.php';
		}

		return $template_file;
	}
}
