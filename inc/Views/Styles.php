<?php

namespace MeuMouse\Flexify_Checkout\Views;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\Checkout\Themes;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Enqueue styles on checkout
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Styles {

    /**
     * Construct function
     *
     * @since 5.0.0
     * @return void
     */
    public function __construct() {
        // add custom header on checkout page
		add_action( 'flexify_checkout_before_layout', array( $this, 'custom_header' ), 10 );

		// add custom footer on checkout page
		add_action( 'flexify_checkout_after_layout', array( $this, 'custom_footer' ) );
    }


    /**
	 * Add custom header on checkout
	 * 
	 * @since 3.0.0
     * @version 5.0.0
	 * @return void
	 */
	public function custom_header() {
		$shortcode_header = Admin_Options::get_setting('shortcode_header');

		if ( ! empty( $shortcode_header ) ) {
			echo do_shortcode( $shortcode_header );
		}
	}


	/**
	 * Add custom footer on checkout
	 * 
	 * @since 3.0.0
     * @version 5.0.0
	 * @return void
	 */
	public function custom_footer() {
		$shortcode_footer = Admin_Options::get_setting('shortcode_footer');

		if ( ! empty( $shortcode_footer ) ) {
			echo do_shortcode( $shortcode_footer );
		}
	}


    /**
	 * Get dynamic styles
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @param array $settings | Get plugin settings
	 * @return string
	 */
	public static function render_checkout_styles( $settings ) {
		$theme = Themes::get_theme();
		$settings = get_option('flexify_checkout_settings', array());
		$primary = Admin_Options::get_setting('set_primary_color');
		$primary_hover = Admin_Options::get_setting('set_primary_color_on_hover');
		$set_placeholder_color = Admin_Options::get_setting('set_placeholder_color');
		$border_radius = Admin_Options::get_setting('input_border_radius') . Admin_Options::get_setting('unit_input_border_radius');
		$font = Admin_Options::get_setting('set_font_family');

		ob_start(); ?>

		@import url('<?php echo esc_attr( $settings['font_family'][$font]['font_url'] ); ?>');

		* {
			font-family: <?php echo esc_attr( $settings['font_family'][$font]['font_name'] ); ?>, Inter, Helvetica, Arial, sans-serif;
		}

		<?php
		/**
		 * We are using a style sheet tag so we have nice markup,
		 * but we are not rendering it, output buffer comes after
		 * the start and before the end.
		 */
		if ( 'modern' === $theme ) :
			if ( $settings['set_placeholder_color'] ) : ?>
				.flexify-checkout ::-webkit-input-placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout ::-moz-placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout ::-ms-input-placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout ::placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout :-ms-input-placeholder {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?> !important;
				}

				.flexify-checkout .form-row label:not(.checkbox) {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}

				.flexify-checkout .form-row label:not(.checkbox) abbr,
				.flexify-checkout .form-row label:not(.checkbox) span {
					color: <?php echo esc_attr( $settings['set_placeholder_color'] ); ?>;
				}
			<?php endif;

			if ( $settings['set_primary_color'] ) : ?>
				::-webkit-scrollbar-thumb:hover {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-checkout .flexify-button,
				.flexify-checkout .flexify-button:hover,
				.button,
				button {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.select2-container--default .select2-results__option--highlighted[aria-selected],
				.select2-container--default .select2-results__option--highlighted[data-selected] {
					background-color: <?php echo esc_attr( $primary ); ?> !important;
				}

				.select2-container--open .select2-dropdown,
				.form-row .select2-container--open .select2-selection--single {
					border-color: <?php echo esc_attr( $primary ); ?> !important;
				}

				.flexify-heading__count {
					background-color: <?php echo esc_attr( $primary ); ?> !important;
				}

				.form-row .select2-selection:focus,
				.form-row .select2-selection:hover,
				.form-row > .woocommerce-input-wrapper > strong:focus,
				.form-row > .woocommerce-input-wrapper > strong:hover,
				.form-row input[type="email"]:focus,
				.form-row input[type="email"]:hover,
				.form-row input[type="password"]:focus,
				.form-row input[type="password"]:hover,
				.form-row input[type="tel"]:focus,
				.form-row input[type="tel"]:hover,
				.form-row input[type="text"]:focus,
				.form-row input[type="text"]:hover,
				.form-row select:focus,
				.form-row select:hover,
				.form-row textarea:focus,
				.form-row textarea:hover {
					border-color: <?php echo esc_attr( $primary ); ?> !important;
				}

				.flexify-checkout a, .lost_password a {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-checkout a:hover, .lost_password a:hover {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
					filter: brightness( 80% );
				}

				.flexify-checkout.flexify-checkout--modern .flexify-checkout__login-button {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-checkout.flexify-checkout--modern .flexify-checkout__login-button:hover {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
					filter: brightness( 80% );
				}

				.flexify-checkout #payment .payment_methods li.wc_payment_method > input[type=radio]:checked + label:after,
				.flexify-checkout input[type=radio]:checked + label:after, .flexify-checkout input[type=radio]:checked + label:after {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
					border-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-review-customer__buttons a[data-stepper-goto] {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.flexify-review-customer__buttons a[data-stepper-goto]:hover {
					color: <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
				}

				.shipping-method-item.selected-method,
				.shipping-method-item:hover {
					border-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

				.shipping-method-item.selected-method:before {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

				.mp-details-pix-button {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

				.mp-qr-input:focus {
					border-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

				input[type="checkbox"]:checked {
					background-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
					border-color: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				}

			<?php endif;

			if ( $settings['set_primary_color_on_hover'] ) : ?>
				.flexify-checkout .button:not(.wc-forward,.woocommerce-MyAccount-downloads-file),
				.flexify-checkout .button:not(.wc-forward,.woocommerce-MyAccount-downloads-file):hover, .button:hover, button:hover {
					background-color: <?php echo esc_attr( $settings['set_primary_color_on_hover'] ); ?>;
				}

				.mp-details-pix-button:hover {
					background-color: <?php echo esc_attr( $settings['set_primary_color_on_hover'] ); ?> !important;
				}
			<?php endif;

			// set border radius
			if ( ! empty( $settings['input_border_radius'] ) ) : ?>
				.form-row .select2-selection,
				.form-row .select2-selection,
				.form-row input[type="email"],
				.form-row input[type="email"],
				.form-row input[type="password"],
				.form-row input[type="password"],
				.form-row input[type="tel"],
				.form-row input[type="tel"],
				.form-row input[type="text"],
				.form-row input[type="text"],
				.form-row select,
				.form-row select,
				.form-row textarea,
				.form-row textarea,
				#shipping_method li label,
				.button,
				.flexify-button,
				.flexify-ty-status {
					border-radius: <?php echo esc_attr( $border_radius ); ?> !important;
				}
	
				#order_review .quantity .quantity__button--minus {
					border-top-left-radius: <?php echo esc_attr( $border_radius ); ?> !important;
					border-bottom-left-radius: <?php echo esc_attr( $border_radius ); ?> !important;
				}
	
				#order_review .quantity .quantity__button--plus {
					border-top-right-radius: <?php echo esc_attr( $border_radius ); ?> !important;
					border-bottom-right-radius: <?php echo esc_attr( $border_radius ); ?> !important;
				}
			<?php endif;

			// set h2 font size
			if ( ! empty( $settings['h2_size'] ) ) : ?>
				.h2, h2 {
					font-size: <?php echo esc_attr( $settings['h2_size'] . $settings['h2_size_unit'] ); ?> !important;
				}
			<?php endif; ?>

			.processing .blockUI.blockOverlay {
  				background-image: url("<?php echo esc_attr( $this->assets_url . 'frontend/img/loader.gif' ); ?>") !important;
			}
		<?php endif;

		if ( Admin_Options::get_setting('enable_inter_bank_pix_api') === 'yes' ) : ?>
			.interpix-open-browser {
				background: <?php echo esc_attr( $settings['set_primary_color'] ); ?> !important;
				border: 1px solid <?php echo esc_attr( $settings['set_primary_color'] ); ?>;
			}
		<?php endif;

		$css = ob_get_clean();
		$css = wp_strip_all_tags( $css );

		return $css;
	}
}