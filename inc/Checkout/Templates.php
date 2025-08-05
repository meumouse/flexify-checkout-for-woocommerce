<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Add and manipulate WooCommerce part templates
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Templates {

    /**
     * Construct function
     *
     * @since 5.0.0
     * @return void
     */
    public function __construct() {
		// load templates
		add_filter( 'woocommerce_locate_template', array( $this, 'load_templates' ), 10, 3 );

        // replace original checkout notices
		add_filter( 'wc_get_template', array( $this, 'flexify_checkout_notices' ), 99, 5 );

		// add wrap for express gateways
		add_filter( 'woocommerce_checkout_before_customer_details', array( $this, 'express_checkout_button_wrap' ) );

		// add purchase animation
		add_action( 'flexify_checkout_after_layout', array( '\MeuMouse\Flexify_Checkout\Views\Components', 'add_processing_purchase_animation' ) );

		// disable block editor for checkout
		add_filter( 'woocommerce_checkout_is_block_editor_enabled', '__return_false' );
    }


	/**
	 * Change WooCommerce single product price template
	 * 
	 * @since 4.5.0
     * @version 5.0.0
	 * @param string $template | The full path of the current template being loaded by WooCommerce
	 * @param string $template_name | The name of the template being loaded (e.g. 'single-product/price.php')
	 * @param string $template_path | WooCommerce template directory path
	 * @return string $template | The full path of the template to be used by WooCommerce, which can be the original or a customized one
	 */
	public function load_templates( $template, $template_name, $template_path ) {
		if ( ! is_flexify_template() ) {
			return $template;
		}

		// prevent duplicate form login template
		if ( $template_name === 'checkout/form-login.php' && did_action('flexify_checkout_form_login_loaded') > 0 ) {
			return $template;
		}

		/**
		 * Match any templates relating to the checkout, including those from Flexify itself.
		 *
		 * If the template contains one of these strings, continue through this function, so we can
		 * either change it to a Flexify template, or revert it back to the WooCommerce path.
		 *
		 * We don't want the theme to load any of these templates, as they are all handled by Flexify.
		 *
		 * @param array $templates Templates.
		 * @param string $template Template.
		 * @param string $template_name Template name.
		 * @param string $template_path Template path.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		$reset_templates_src = apply_filters( 'flexify_match_checkout_template_sources', array(
				'woocommerce/', // Catches any file in the woocommerce/ override folder.
				'global/quantity-input.php',
				'templates/checkout/',
				'common/checkout/',
				'notices/',
			),
			$template,
			$template_name,
			$template_path
		);

		if ( ! empty( $reset_templates_src ) ) {
			$reset_template_src_matched = false;

			foreach ( $reset_templates_src as $reset_template_src ) {
				if ( strpos( strtolower( $template ), $reset_template_src ) ) {
					$reset_template_src_matched = true;

					break;
				}
			}

			if ( ! $reset_template_src_matched ) {
				return $template;
			}
		}

		/**
		 * Filter $template_name's which *are* allowed to be overridden by theme.
		 *
		 * @since 1.0.0
		 * @param array  $templates Array of template names.
		 * @param string $template Template.
		 * @param string $template_name Template name.
		 * @param string $template_path Template path.
		 * @return array
		 */
		$allowed_templates = apply_filters( 'flexify_allowed_template_overrides', array(), $template, $template_name, $template_path );

		if ( in_array( $template_name, $allowed_templates, true ) ) {
			return $template;
		}

		// Get the Flexify theme
		$theme = Themes::get_theme();
		$plugin_path = FLEXIFY_CHECKOUT_PATH . 'woocommerce/' . $theme . '/'; // Flexify theme folder.
		$plugin_path_common = FLEXIFY_CHECKOUT_PATH . 'woocommerce/common/';
		$flexify_template = '';

		// Search the Flexify theme and common folders for the template.
		if ( file_exists( $plugin_path . $template_name ) ) {
			$flexify_template = $plugin_path . $template_name;
		} elseif ( file_exists( $plugin_path_common . $template_name ) ) {
			$flexify_template = $plugin_path_common . $template_name;
		}

		// If this template exists in Flexify, use it.
		if ( ! empty( $flexify_template ) && file_exists( $flexify_template ) ) {
			if ( $template_name === 'common/checkout/form-login.php' ) {
				do_action('flexify_checkout_form_login_loaded'); //set loaded template
			}

			return $flexify_template;
		}

		// Otherwise, check in WooCommerce template folder path.
		$woo_template_path = WC()->plugin_path() . '/templates/' . $template_name;
		
		if ( $template_name && file_exists( $woo_template_path ) ) {
			return $woo_template_path;
		}

		// If not found anywhere else, return the original path.
		return $template;
	}


	/**
	 * Replace checkout notices
	 * 
	 * @since 3.5.0
	 * @version 5.0.0
	 * @param string $template | Default template file path
	 * @param string $template_name | Template file slug
	 * @param array $args | Template arguments
	 * @param string $template_path | Template file name
	 * @param string $default_path Default path
	 * @return string The new Template file path
	 */
	public function flexify_checkout_notices( $template, $template_name, $args, $template_path, $default_path ) {
		if ( ! is_flexify_template() ) {
			return $template;
		}
		
		// replace error notice
		if ( $template_name === 'notices/error.php' ) {
			$template = FLEXIFY_CHECKOUT_TEMPLATES_DIR . 'notices/error.php';
		}

		// replace info notice
		if ( $template_name === 'notices/notice.php' ) {
			$template = FLEXIFY_CHECKOUT_TEMPLATES_DIR . 'notices/notice.php';
		}

		// replace success notice
		if ( $template_name === 'notices/success.php' ) {
			$template = FLEXIFY_CHECKOUT_TEMPLATES_DIR . 'notices/success.php';
		}

		return $template;
	}


	/**
	 * Express checkout buttons wrap
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function express_checkout_button_wrap() {
		?>
		<div class="flexify-express-checkout-wrap"></div>
		<?php
	}
}