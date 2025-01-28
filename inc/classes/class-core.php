<?php

namespace MeuMouse\Flexify_Checkout;

use MeuMouse\Flexify_Checkout\Init;
use MeuMouse\Flexify_Checkout\License;
use MeuMouse\Flexify_Checkout\Helpers;
use MeuMouse\Flexify_Checkout\Steps;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Checkout core actions
 *
 * @since 1.0.0
 * @version 4.0.0
 * @package MeuMouse.com
 */
class Core {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
	 * @version 4.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'maybe_optimize_for_digital' ) );
		add_action( 'wp', array( __CLASS__, 'wp' ) );
		add_action( 'woocommerce_loaded', array( __CLASS__, 'remove_checkout_shipping' ) );
		add_filter( 'template_include', array( __CLASS__, 'include_template' ), 100 );
		
		add_filter( 'woocommerce_checkout_before_customer_details', array( __CLASS__, 'express_checkout_button_wrap' ) );

		add_action( 'body_class', array( __CLASS__, 'update_body_class' ) );
		
		add_filter( 'woocommerce_get_country_locale_base', array( __CLASS__, 'remove_empty_placeholders' ), 100 );

		// Locate template
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'woocommerce_locate_template' ), 100, 3 );

		// Unhook default coupon form
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

		// filter for display thankyou page when purchase is same email adress
		if ( Init::get_setting('enable_assign_guest_orders') === 'yes' && License::is_valid() ) {
			add_filter( 'woocommerce_order_email_verification_required', '__return_false' );
			add_filter( 'woocommerce_order_received_verify_known_shoppers', '__return_false' );
		}

		add_filter( 'woocommerce_no_available_payment_methods_message', array( __CLASS__, 'custom_no_payment_methods_message' ) );

		// set terms and conditions default if option is activated
		if ( Init::get_setting('enable_terms_is_checked_default') === 'yes' && License::is_valid() ) {
			add_filter( 'woocommerce_terms_is_checked_default', '__return_true' );
		}

		// remove section aditional notes if option is deactivated
		if ( Init::get_setting('enable_aditional_notes') === 'no' ) {
			add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
		}

		// add custom header on checkout page
		add_action( 'flexify_checkout_before_layout', array( $this, 'custom_header' ), 10 );

		// add custom footer on checkout page
		add_action( 'flexify_checkout_after_layout', array( $this, 'custom_footer' ) );

		// set default country on checkout
		if ( Init::get_setting('enable_manage_fields') === 'yes' && License::is_valid() ) {
			add_filter( 'default_checkout_billing_country', array( $this, 'get_default_checkout_country' ) );
		}

		// replace original checkout notices
		add_filter( 'wc_get_template', array( $this, 'flexify_checkout_notices' ), 10, 5 );

		// update fragments on update_order_review event
		add_action( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'update_order_review_framents' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'override_empty_cart_fragment' ) );

		// add custom footer on checkout page
		add_action( 'flexify_checkout_after_layout', array( $this, 'add_processing_purchase_animation' ) );

		// allow user to ship to different address
		if ( Init::get_setting('enable_shipping_to_different_address') !== 'yes' ) {
			add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
		}

		// force load form login template
		add_action( 'flexify_checkout_before_layout', array( __CLASS__, 'load_form_login_template' ) );

		add_filter( 'option_woocommerce_cart_redirect_after_add', array( __CLASS__, 'disable_add_to_cart_redirect_for_checkout' ) );
	}


	/**
	 * WP Hook.
	 *
	 * Earliest we can check if it's the checkout page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function wp() {
		if ( ! is_flexify_checkout() ) {
			return;
		}

		// Better x-theme compatibility.
		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
	}


	/**
	 * Maybe optimize for digital products
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function maybe_optimize_for_digital() {
		if ( Init::get_setting('enable_optimize_for_digital_products') === 'yes' && License::is_valid() ) {
			add_filter( 'flexify_custom_steps', array( __CLASS__, 'disable_address_step' ) );
		}
	}


	/**
	 * Remove checkout shipping fields as we add them ourselves
	 * 
	 * @since 1.0.0
	 */
	public static function remove_checkout_shipping() {
		remove_action( 'woocommerce_checkout_shipping', array( \WC_Checkout::instance(), 'checkout_form_shipping' ) );
	}


	/**
	 * Include Template.
	 *
	 * @since 1.0.0
	 * @param string $template | Template Path
	 * @return string
	 */
	public static function include_template( $template ) {
		if ( ! is_flexify_template() ) {
			return $template;
		}

		$theme = self::get_theme();

		remove_action( 'woocommerce_before_shop_loop', 'wc_print_notices', 10 );
		remove_action( 'woocommerce_before_single_product', 'wc_print_notices', 10 );

		define( 'IS_FLEXIFY_CHECKOUT', true );

		global $flexify_shipping_prefix;
		$flexify_shipping_prefix = '';

		return FLEXIFY_CHECKOUT_PATH . 'templates/template-' . $theme . '.php';
	}


	/**
	 * Check if current page is a Thank you page.
	 *
	 * @since 1.0.0
	 * @version 3.8.0
	 * @return bool
	 */
	public static function is_thankyou_page() {
		$force = filter_input( INPUT_GET, 'flexify_force_ty', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( '1' !== $force && Init::get_setting('enable_thankyou_page_template') !== 'yes' ) {
			return false;
		}

		if ( ! is_wc_endpoint_url('order-received') ) {
			return false;
		}

		return true;
	}


	/**
	 * Disable the address step
	 *
	 * @since 1.0.0
	 * @version 3.5.0
	 * @param array $steps | Checkout Fields
	 * @return array
	 */
	public static function disable_address_step( $steps ) {
		if ( ! flexify_checkout_only_virtual() ) {
			return $steps;
		}

		unset( $steps[1] );

		return array_values( $steps );
	}


	/**
	 * Remove empty placeholder attributes on checkout fields
	 *
	 * @since 1.0.0
	 * @param array $locale_base
	 * @return array
	 */
	public static function remove_empty_placeholders( $locale_base ) {
		if ( empty( $locale_base ) || ! is_array( $locale_base ) ) {
			return $locale_base;
		}

		foreach ( $locale_base as $key => $data ) {
			if ( ! isset( $data['placeholder'] ) || ! empty( $data['placeholder'] ) ) {
				continue;
			}

			unset( $locale_base[ $key ]['placeholder'] );
		}

		return $locale_base;
	}


	/**
	 * Change message if empty payment forms
	 * 
	 * @since 1.2.5
	 * @return string
	 */
	public static function custom_no_payment_methods_message( $message ) {
		$message = __( 'Desculpe, parece que não há métodos de pagamento disponíveis para sua localização. Entre em contato conosco se precisar de assistência ou desejar pagar de outra forma.', 'flexify-checkout-for-woocommerce' );
		
		return $message;
	}


	/**
	 * Get theme
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_theme() {
		return Init::get_setting('flexify_checkout_theme') ? Init::get_setting('flexify_checkout_theme') : 'modern';
	}


	/**
	 * Update order review fragments
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param array $fragments | Checkout fragments
	 * @return array
	 */
	public static function update_order_review_framents( $fragments ) {
		$session_key = WC()->session->get('flexify_checkout_ship_different_address') === 'yes' ? 'shipping' : 'billing';
		
		$fragments['.flexify-review-customer'] = Steps::render_customer_review();
		$fragments['.flexify-checkout-review-customer-contact'] = Steps::replace_placeholders( Init::get_setting('text_contact_customer_review'), Steps::get_review_customer_fragment() );
		$fragments['.flexify-checkout-review-shipping-address'] = Steps::replace_placeholders( Init::get_setting('text_shipping_customer_review'), Steps::get_review_customer_fragment(), $session_key );
		$fragments['.flexify-checkout-review-shipping-method'] = Helpers::get_shipping_method();

		// Heading with cart item count
		ob_start();

		wc_get_template('checkout/cart-heading.php');

		$fragments['.flexify-heading--order-review'] = ob_get_clean();

		$new_fragments = array(
			'total' => WC()->cart->get_total(),
			'shipping_row' => Steps::get_shipping_row(),
			'shipping_options' => Steps::get_shipping_options_fragment(),
        	'payment_options' => Steps::get_payment_options_fragment(),
		);

		if ( isset( $fragments['flexify'] ) ) {
			$fragments['flexify'] = array_merge( $fragments['flexify'], $new_fragments );
		} else {
			$fragments['flexify'] = $new_fragments;
		}

		return $fragments;
	}


	/**
	 * Add additional classes to the body tag on checkout page.
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param array $classes | Body classes
	 * @return array
	 */
	public static function update_body_class( $classes ) {
		if ( ! is_checkout() ) {
			return $classes;
		}

		$classes[] = 'flexify-checkout-enabled';

		if ( ! is_user_logged_in() && 'yes' === get_option('woocommerce_enable_checkout_login_reminder') ) {
			$classes[] = 'flexify-wc-allow-login';
		}

		return $classes;
	}

	
	/**
	 * Locate templates
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @param string $template | Template
	 * @param string $template_name | Template name
	 * @param string $template_path | Template path
	 * @return mixed|string
	 */
	public static function woocommerce_locate_template( $template, $template_name, $template_path ) {
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
		$theme = self::get_theme();
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
	 * Override empty cart fragment
	 *
	 * @since 1.0.0
	 * @param array $fragments | Checkout fragments
	 * @return array
	 */
	public static function override_empty_cart_fragment( $fragments ) {
		if ( ! WC()->cart->is_empty() || is_customize_preview() ) {
			return $fragments;
		}

		unset( $fragments['form.woocommerce-checkout'] );

		ob_start();

		include FLEXIFY_CHECKOUT_PATH . 'woocommerce/common/checkout/empty-cart.php';

		$fragments['flexify'] = array(
			'empty_cart' => ob_get_clean(),
		);

		return $fragments;
	}


	/**
	 * Add custom header on checkout
	 * 
	 * @since 3.0.0
	 * @return void
	 */
	public function custom_header() {
		$shortcode_header = Init::get_setting('shortcode_header');

		if ( ! empty( $shortcode_header ) ) {
			echo do_shortcode( $shortcode_header );
		}
	}


	/**
	 * Add custom footer on checkout
	 * 
	 * @since 3.0.0
	 * @return void
	 */
	public function custom_footer() {
		$shortcode_footer = Init::get_setting('shortcode_footer');

		if ( ! empty( $shortcode_footer ) ) {
			echo do_shortcode( $shortcode_footer );
		}
	}


	/**
	 * Express checkout buttons wrap
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function express_checkout_button_wrap() {
		?>
		<div class="flexify-express-checkout-wrap"></div>
		<?php
	}


	/**
	 * Set default country on WooCommerce checkout
	 * 
	 * @since 3.2.0
	 * @return string
	 */
	public function get_default_checkout_country() {
		$fields = get_option('flexify_checkout_step_fields', array());
		$fields = maybe_unserialize( $fields );
		$country = isset( $fields['billing_country']['country'] ) ? $fields['billing_country']['country'] : '';

		return $country;
	}


	/**
	 * Get fields with input mask
	 * 
	 * @since 3.5.0
	 * @return array
	 */
	public static function get_fields_with_mask() {
		$fields = get_option('flexify_checkout_step_fields', array());
		$fields = maybe_unserialize( $fields );
		$input_masks = array();

		foreach ( $fields as $key => $value ) {
			if ( ! empty( $value['input_mask'] ) ) {
				$input_masks[$key] = $value['input_mask'];
			}
		}

		return $input_masks;
	}


	/**
	 * Replace checkout notices
	 * 
	 * @since 3.5.0
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
			$template = FLEXIFY_CHECKOUT_TPL_PATH . 'notices/error.php';
		}

		// replace info notice
		if ( $template_name === 'notices/notice.php' ) {
			$template = FLEXIFY_CHECKOUT_TPL_PATH . 'notices/notice.php';
		}

		// replace success notice
		if ( $template_name === 'notices/success.php' ) {
			$template = FLEXIFY_CHECKOUT_TPL_PATH . 'notices/success.php';
		}

		return $template;
	}


	/**
	 * Add processing purchase animation
	 * 
	 * @since 3.9.4
	 * @return void
	 */
	public function add_processing_purchase_animation() {
		if ( Init::get_setting('enable_animation_process_purchase') === 'yes' && License::is_valid() ) : ?>
			<div id="flexify_checkout_purchase_animation" class="purchase-animations-group">
				<div class="animations-content">
					<div class="animations-group">
						<div class="purchase-animation-item animation-1">
							<lord-icon class="animation-item" src="<?php echo esc_url( Init::get_setting('animation_process_purchase_file_1') ) ?>" trigger="loop" target=".purchase-animation-item" delay="1000" stroke="regular" state="hover" colors="primary:#212529, secondary:#212529"></lord-icon>
							<h5 class="text-animation-item"><?php echo esc_html( Init::get_setting('text_animation_process_purchase_1') ) ?></h5>
						</div>

						<div class="purchase-animation-item animation-2">
							<lord-icon class="animation-item" src="<?php echo esc_url( Init::get_setting('animation_process_purchase_file_2') ) ?>" trigger="loop" target=".purchase-animation-item" delay="1000" stroke="regular" state="hover" colors="primary:#212529, secondary:#212529"></lord-icon>
							<h5 class="text-animation-item"><?php echo esc_html( Init::get_setting('text_animation_process_purchase_2') ) ?></h5>
						</div>

						<div class="purchase-animation-item animation-3">
							<lord-icon class="animation-item" src="<?php echo esc_url( Init::get_setting('animation_process_purchase_file_3') ) ?>" trigger="loop" target=".purchase-animation-item" delay="1000" stroke="regular" state="hover" colors="primary:#212529, secondary:#212529"></lord-icon>
							<h5 class="text-animation-item"><?php echo esc_html( Init::get_setting('text_animation_process_purchase_3') ) ?></h5>
						</div>
					</div>

					<div class="animation-progress-content">
						<div class="animation-progress-container">
							<div class="progress-bar animation-progress-bar"></div>
							<div class="progress-bar animation-progress-base"></div>
						</div>
						<span class="description-progress-bar"><?php esc_html_e( 'Aguarde alguns instantes', 'flexify-checkout-for-woocommerce' ) ?></span>
					</div>
				</div>
			</div>
		<?php endif;
	}


	/**
	 * Force load form login template on checkout
	 * 
	 * @since 3.9.8
	 * @return void
	 */
	public static function load_form_login_template() {
		if ( did_action('flexify_checkout_form_login_loaded') > 0 ) {
			return; // template has been loaded
		}
	
		if ( is_flexify_template() ) {
			$form_login_path = FLEXIFY_CHECKOUT_PATH . 'woocommerce/common/checkout/form-login.php';
	
			// Verifica se o arquivo existe
			if ( file_exists( $form_login_path ) ) {
				include_once $form_login_path;

				do_action('flexify_checkout_form_login_loaded'); // set loaded template
			}
		}
	}


	/**
	 * Disable add to cart redirection for checkout
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 * @param array $value
	 * @return mixed
	 */
	public static function disable_add_to_cart_redirect_for_checkout( $value ) {
		$add_to_cart = filter_input( INPUT_GET, 'add-to-cart' );

		if ( empty( $add_to_cart ) || ! did_filter('woocommerce_add_to_cart_product_id') ) {
			return $value;
		}

		if ( ! is_flexify_checkout( true ) ) {
			return $value;
		}

		return false;
	}
}

if ( Init::get_setting('enable_flexify_checkout') === 'yes' ) {
	new Core();
}

if ( ! class_exists('MeuMouse\Flexify_Checkout\Core\Core') ) {
    class_alias( 'MeuMouse\Flexify_Checkout\Core', 'MeuMouse\Flexify_Checkout\Core\Core' );
}