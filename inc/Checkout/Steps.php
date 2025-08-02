<?php

namespace MeuMouse\Flexify_Checkout\Checkout;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle de steps
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Steps {

	/**
	 * Render header
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_header( $show_breadcrumps = true ) {
		/**
		 * Hook before header
		 *
		 * @since 1.0.0
		 */
		do_action('flexify_checkout_before_header'); ?>

		<header class="flexify-checkout__header header">
			<div class="header__inner">
				<?php
				/**
				 * Allows you to override the hyperlink on the header logo
				 *
				 * @since 1.0.0
				 */
				$back_url = apply_filters( 'flexify_checkout_logo_href', esc_url( Admin_Options::get_setting('logo_header_link') ) ); ?>

				<a class="header__link" href="<?php echo esc_url( $back_url ); ?>">
					<?php if ( Admin_Options::get_setting('checkout_header_type') === 'text' ) : ?>
						<h1 class="header__title"><?php echo esc_html( Helpers::get_header_text() ); ?></h1>
					<?php else :
						$width = Helpers::get_logo_width(); 

						if ( Helpers::get_logo_image() !== NULL ) : ?>
							<img class="header__image" src="<?php echo esc_url( Helpers::get_logo_image() ); ?>"<?php echo ! empty( $width ) ? 'style="width:' . esc_attr( $width ) . Admin_Options::get_setting('unit_header_width_image_checkout') .'"' : ''; ?> />
						<?php endif;
					endif; ?>
				</a>
			</div>
			<?php if ( $show_breadcrumps ) :
				self::render_stepper();
			endif; ?>
		</header>

		<?php
		/**
		 * After header
		 *
		 * @since 1.0.0
		 */
		do_action('flexify_checkout_after_header');
	}


	/**
	 * Render Stepper
	 */
	public static function render_stepper() {
		if ( Helpers::is_thankyou_page() ) {
			return;
		}

		$steps = self::get_steps();

		if ( empty( $steps ) ) {
			return;
		}

		?>
		<nav class="flexify-stepper">
			<ul>
				<?php
				foreach ( $steps as $key => $step ) {
					$enabled = 0 !== $key ? ' disabled' : ' selected'; ?>

					<li data-stepper-li="<?php echo esc_attr( $key + 1 ); ?>" class="flexify-stepper__step flexify-stepper__step--<?php echo esc_attr( $key + 1 ); ?> stepper__step--<?php echo esc_attr( $step['slug'] ); ?> <?php echo esc_attr( $enabled ); ?>">
						<button type="button" class="flexify-stepper__button" data-stepper="<?php echo esc_attr( $key + 1 ); ?>" data-step-show="<?php echo esc_attr( $key + 2 ); ?>"<?php echo esc_attr( $enabled ); ?> data-hash="<?php echo esc_attr( $step['slug'] ); ?>">
							<svg class="icon icon--checkmark step-<?php echo esc_attr( $key + 1 ); ?> <?php echo esc_attr( $enabled ); ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
								<circle class="icon--checkmark__circle <?php echo esc_attr( $enabled ); ?>" cx="26" cy="26" r="25" fill="none"/>
								<path class="icon--checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
							</svg>
							<span class="flexify-stepper__indicator">
								<?php echo esc_html( $key + 1 ); ?>
							</span>
							<span class="flexify-stepper__title">
								<?php echo esc_html( $step['title'] ); ?>
							</span>
						</button>
					</li>
					<?php
				}
				?>
			</ul>
		</nav>
		<?php
	}


	/**
	 * Render checkout steps
	 * 
	 * @since 1.0.0
	 * @version 3.8.0
	 * @return void
	 */
	public static function render_steps() {
		$checkout = WC()->checkout;
		$steps = self::get_steps();

		if ( empty( $steps ) ) {
			return;
		}

		foreach ( $steps as $key => $step ) : ?>
			<section data-step="<?php echo esc_attr( $key + 1 ); ?>" class="flexify-step flexify-step--<?php echo esc_attr( $key + 1 ); ?> flexify-step--<?php echo esc_attr( $step['slug'] ); ?>" <?php echo 0 !== $key ? 'style="display:none;" aria-hidden="true"' : ''; ?>>
				<?php if ( 0 === $key ) :
					/**
					 * Before customer details
					 *
					 * @since 1.0.0
					 */
					do_action( 'woocommerce_checkout_before_customer_details' );

					/**
					 * Before checkout billing form
					 *
					 * @param WC_Checkout $checkout | Checkout object
					 *
					 * @since 1.0.0
					 */
					do_action( 'woocommerce_before_checkout_billing_form', $checkout );
				endif; ?>

				<div class="flexify-step__content">
					<?php
						/**
						 * Before step content
						 *
						 * @since 1.0.0
						 */
						do_action( 'flexify_checkout_before_step_content', $step );

						call_user_func( $step['callback'] );

						/**
						 * After step content.
						 *
						 * @since 1.0.0
						 */
						do_action( 'flexify_checkout_after_step_content', $step );
					?>
				</div>

				<?php if ( count( $steps ) - 2 === $key ) {
					// Render before last step

					/**
					 * After checkout billing form
					 *
					 * @param WC_Checkout $checkout | Checkout object
					 *
					 * @since 2.0.0
					 */
					do_action( 'woocommerce_after_checkout_billing_form', $checkout );

					/**
					 * After customer details
					 *
					 * @since 2.0.0
					 */
					do_action( 'woocommerce_checkout_after_customer_details' );
				}
				
				if ( count( $steps ) - 1 !== $key ) :
					if ( Themes::get_theme() === 'classic' ) : ?>
						<button class="flexify-button--step flexify-button" data-step-next data-step-show="<?php echo esc_attr( $key + 2 ); ?>">
							<?php esc_html_e( 'Continuar', 'flexify-checkout-for-woocommerce' ); ?>
						</button>
					<?php else : ?>
						<footer class="flexify-footer <?php echo ( Admin_Options::get_setting('enable_back_to_shop_button') !== 'yes' && 'customer-info' === $step['slug'] ) ? 'flexify-footer--no-back-shop' : ''; ?>">
							<?php self::back_button( $step['slug'] ); ?>

							<button class="flexify-button" data-step-next data-step-show="<?php echo esc_attr( $key + 2 ); ?>">
								<?php esc_html_e( 'Continuar para', 'flexify-checkout-for-woocommerce' ); ?>&nbsp;<?php echo strtolower( esc_html( $steps[ $key + 1 ]['title'] ) ); ?>
							</button>
						</footer>
					<?php endif;
				endif; ?>
			</section>
		<?php endforeach;
	}


	/**
	 * Get checkout steps
	 *
	 * Currently returns steps from an array. In the future this will support
	 * steps being defined via sub-pages of the checkout page.
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return array
	 */
	public static function get_steps() {
		$steps = array(
			array(
				'callback' => array( __CLASS__, 'render_default_customer_details' ),
				'slug' => 'customer-info',
				'title' => Admin_Options::get_setting('text_check_step_1'),
				'post_id' => 0,
			),
			array(
				'callback' => array( __CLASS__, 'render_default_billing_address' ),
				'slug' => 'address',
				'title' => Admin_Options::get_setting('text_check_step_2'),
				'post_id' => 0,
			),
			array(
				'callback' => array( __CLASS__, 'render_payment_details' ),
				'slug' => 'payment',
				'title' => Admin_Options::get_setting('text_check_step_3'),
				'post_id' => 0,
			),
		);

		/**
		 * Filters the custom steps
		 *
		 * @since 1.0.0
		 * @version 5.0.0
		 * @param array $steps | Checkout steps
		 * @return array
		 */
		return apply_filters( 'Flexify_Checkout/Steps/Set_Custom_Steps', $steps );
	}

	
	/**
	 * Get the billing address when page has not been defined
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public static function render_default_customer_details() {
		$checkout = WC()->checkout;
		$theme = Themes::get_theme();

		if ( 'classic' === $theme ) {
			self::render_login_button();
		}

		$has_login_btn_class = ( ! is_user_logged_in() && 'no' !== get_option( 'woocommerce_enable_checkout_login_reminder' ) ) ? 'flexify-heading--has-login-btn' : '';
		
		if ( ! empty( Admin_Options::get_setting('text_header_step_1') ) ) : ?>
			<h2 class="flexify-heading flexify-heading--customer-details <?php echo esc_attr( $has_login_btn_class ); ?>">
				<?php echo Admin_Options::get_setting('text_header_step_1') ?>
			</h2>
		<?php endif;

		/**
		 * Hook before fields on step 1
		 * 
		 * @since 3.2.0
		 */
		do_action('flexify_checkout_before_fields_step_1');

		if ( 'classic' !== $theme ) {
			self::render_modern_login_button();
		}

		foreach ( Helpers::get_details_fields( $checkout ) as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		};

		/**
		 * Hook after fields on step 1
		 * 
		 * @since 3.2.0
		 */
		do_action('flexify_checkout_after_fields_step_1');

		self::render_account_form( $checkout );

		/**
		 * Hook after account form on step 1
		 * 
		 * @since 3.2.0
		 */
		do_action('flexify_checkout_after_account_form_step_1');
	}


	/**
	 * Get the billing address when page has not been defined
	 *
	 * @since 1.0.0
	 * @version 3.7.0
	 * @return void
	 */
	public static function render_default_billing_address() {
		if ( Helpers::is_modern_theme() ) {
			echo self::render_customer_review();
		}

		/**
		 * Display custom content before shipping title
		 * 
		 * @since 3.7.0
		 */
		do_action('flexify_checkout_before_heading_shipping_title');

		if ( ! empty( Admin_Options::get_setting('text_header_step_2') ) ) : ?>
			<h2 class="flexify-heading flexify-heading--billing"><?php echo Admin_Options::get_setting('text_header_step_2') ?></h2>
		<?php endif;

		/**
		 * After billing address heading
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_checkout_before_billing_address_heading' ); ?>

		<div class="woocommerce-billing-fields__wrapper">
			<?php self::render_address_search(); ?>

			<div class="woocommerce-billing-fields">
				<div class="woocommerce-billing-fields__fields-wrapper">
					<?php if ( Helpers::is_modern_theme() ) : ?>
						<p class="flexify-address-button-wrapper flexify-address-button-wrapper--billing-lookup">
							<button class="flexify-address-button flexify-address-button--lookup flexify-address-button--billing-lookup">
								<?php esc_attr_e( 'Pesquisar um endereço', 'flexify-checkout-for-woocommerce' ); ?>
							</button>
						</p>
					<?php endif;

					/**
					 * Hook before fields on step 2
					 * 
					 * @since 3.2.0
					 */
					do_action('flexify_checkout_before_fields_step_2');

					$checkout = WC()->checkout;

					foreach ( Helpers::get_billing_fields( $checkout ) as $key => $field ) {
						woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
					};

					/**
					 * Hook after fields on step 2
					 * 
					 * @since 3.2.0
					 */
					do_action('flexify_checkout_after_fields_step_2'); ?>

					<div class="clear"></div>
				</div>
			</div>
		</div>

		<div class="woocommerce-shipping-fields__wrapper">
			<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>
				<h3 id="ship-to-different-address">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<?php
						/**
						 * Ship to a different address checked by default.
						 *
						 * @since 1.0.0
						 */
						$checked = checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1, false ); ?>
						
						<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="ship_to_different_address" value="1" <?php echo esc_attr( $checked ); ?>/>
						<span class="toggle__ie11"></span>
						<span><?php esc_html_e( 'Enviar para um endereço diferente?', 'flexify-checkout-for-woocommerce' ); ?></span>
					</label>
				</h3>
				<div class="shipping_address">
					<?php self::render_shipping_address_search(); ?>
					
					<div class="woocommerce-shipping-fields">
						<div class="woocommerce-shipping-fields__fields-wrapper">
							<?php if ( Helpers::is_modern_theme() ) : ?>
								<p class="flexify-address-button-wrapper flexify-address-button-wrapper--shipping-lookup">
									<button class="flexify-address-button flexify-address-button--lookup flexify-address-button--shipping-lookup">
										<?php esc_attr_e( 'Procurar um endereço', 'flexify-checkout-for-woocommerce' ); ?>
									</button>
								</p>
							<?php endif;

							foreach ( Helpers::get_shipping_fields( $checkout ) as $key => $field ) :
								woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
							endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php
		/**
		 * Hook before shipping methods on step 2
		 * 
		 * @since 3.2.0
		 */
		do_action('flexify_checkout_before_shipping_methods_step_2'); ?>

		<table class="flexify-checkout__shipping-table">
			<tbody></tbody>
		</table>

		<?php

		/**
		 * Hook after shipping methods on step 2
		 * 
		 * @since 3.2.0
		 */
		do_action('flexify_checkout_after_shipping_methods_step_2');

		/**
		 * Enable order notes field
		 *
		 * @since 1.0.0
		 * @return void
		 */
		if ( apply_filters( 'woocommerce_enable_order_notes_field', true ) ) : ?>
			<div class="woocommerce-additional-fields__wrapper">
				<?php
				/**
				 * Before order notes.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_before_order_notes', $checkout ); ?>

				<h3 id="show-additional-fields">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input id="show-additional-fields-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="show_additional_fields" value="1" />
						<span class="toggle__ie11"></span>
						<span><?php esc_html_e( 'Adicionar observações ao pedido?', 'flexify-checkout-for-woocommerce' ); ?></span>
					</label>
				</h3>
				<div class="woocommerce-additional-fields" style="display:none;" aria-hidden="true">
					<div class="woocommerce-additional-fields__field-wrapper">
						<?php foreach ( $checkout->checkout_fields['order'] as $key => $field ) :
							woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
						endforeach; ?>
					</div>
				</div>
				<?php
				/**
				 * After order notes.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_after_order_notes', $checkout ); ?>
			</div>
		<?php endif;
	}


	/**
	 * Get the payment details when page has not been defined
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public static function render_payment_details() {
		/**
		 * Before checkout order review heading.
		 *
		 * @since 1.0.0
		 */
		do_action('woocommerce_checkout_before_order_review_heading');

		if ( Helpers::is_modern_theme() ) {
			echo self::render_customer_review();

			if ( Admin_Options::get_setting('text_header_step_3') ) : ?>
				<h2 class="flexify-heading flexify-heading--payment"><?php echo Admin_Options::get_setting('text_header_step_3') ?></h2>
			<?php endif;
		}

		$sidebar_enabled = Sidebar::is_sidebar_enabled();

		if ( ! $sidebar_enabled ) {
			self::render_coupon_form();
		}

		/**
		 * Render custom content before payment title
		 *
		 * @since 5.0.0
		 */
		do_action('Flexify_Checkout/Steps/Payment/Before_Title');

		$heading_class = $sidebar_enabled ? '' : 'flexify-heading--order-review'; ?>

		<h2 class="flexify-heading <?php echo esc_attr( $heading_class ); ?>" id="order_review_heading">
			<?php esc_html_e( 'Pagamento', 'flexify-checkout-for-woocommerce' ); ?>
		</h2>

		<?php
		/**
		 * Before checkout order review
		 *
		 * @since 1.0.0
		 */
		do_action('woocommerce_checkout_before_order_review'); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php
			/**
			 * Checkout order review
			 *
			 * @since 1.0.0
			 */
			do_action('woocommerce_checkout_order_review'); ?>
		</div>

		<?php
		/**
		 * After checkout order review
		 *
		 * @since 1.0.0
		 */
		do_action('woocommerce_checkout_after_order_review');
	}


	/**
	 * Render Login Button
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_login_button() {
		if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
			return;
		}

		?>
		<button class="flexify-checkout__login-button login-button" data-login type="button">
			<?php esc_html_e( 'Já é um cliente?', 'flexify-checkout-for-woocommerce' ); ?>
		</button>
		<?php
	}


	/**
	 * Render Login Button.
	 */
	public static function render_modern_login_button() {
		if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
			return;
		}

		?>
		<p class="flexify-checkout__login"><?php esc_html_e( 'Já é um cliente?', 'flexify-checkout-for-woocommerce' ); ?>
			<button class="flexify-checkout__login-button login-button" data-login type="button"><?php esc_html_e( 'Entrar', 'flexify-checkout-for-woocommerce' ); ?> </button>
		</p>
		<?php
	}


	/**
	 * Render Account Form.
	 *
	 * @param object $checkout Checkout.
	 */
	public static function render_account_form( $checkout ) {
		if ( is_user_logged_in() || ! $checkout->enable_signup ) {
			return;
		}

		$style = $checkout->enable_guest_checkout ? 'display:none;' : 'display:block;'; ?>

		<div class="woocommerce-account-fields">
			<?php if ( $checkout->enable_guest_checkout ) : ?>

				<p class="form-row form-row-wide create-account">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<?php
						/**
						 * Check the create account toggle by default.
						 *
						 * @param bool $checked Checked.
						 *
						 * @since 2.0.0
						 */
						$woocommerce_create_account_default_checked = apply_filters( 'woocommerce_create_account_default_checked', false ); ?>

						<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === $woocommerce_create_account_default_checked ) ), true ); ?> type="checkbox" name="createaccount" value="1" /><span class="toggle__ie11"></span> <span><?php esc_html_e( 'Criar uma conta?', 'flexify-checkout-for-woocommerce' ); ?></span>
					</label>
				</p>

			<?php endif; ?>

			<?php
			/**
			 * Before checkout registration form.
			 *
			 * @param WC_Checkout $checkout Checout object.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

			<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

				<div class="create-account" style="<?php echo esc_attr( $style ); ?>">
					<p class="flexify-text flexify-text--subtle">
						<?php
						if ( 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
							if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) {
								// Translators: %1$s = Opening login link, %2$s = Closing login link.
								printf( esc_html__( 'Crie uma conta inserindo as informações abaixo. Se você já é um cliente, por favor faça %1$login na página da sua conta%2$s.', 'flexify-checkout-for-woocommerce' ), '<a href="' . esc_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ) . '">', '</a>' );
							} else {
								esc_html_e( 'Crie uma conta inserindo as informações abaixo. Se você já é um cliente, faça login.', 'flexify-checkout-for-woocommerce' );
							}
						} else {
							esc_html_e( 'Crie uma senha para sua conta, caso seja sua primeira compra. Se já é um cliente, entre em sua conta para resgatar sua informações pessoais.', 'flexify-checkout-for-woocommerce' );
						}
						?>
					</p>
					<?php foreach ( $checkout->get_checkout_fields('account') as $key => $field ) : ?>
						<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
					<?php endforeach;

					// check password strenght
					if ( Admin_Options::get_setting('check_password_strenght') === 'yes' ) : ?>
						<div class="password-meter">
							<div class="password-strength-meter"></div>
						</div>
					<?php endif; ?>

					<div class="clear"></div>
				</div>

			<?php endif; ?>

			<?php
			/**
			 * After checkout registration form.
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
		</div>
		<?php
	}


	/**
	 * Render address search
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_address_search() {
		$is_modern_theme = Helpers::is_modern_theme();
		$is_pre_populated = Helpers::has_prepopulated_fields('billing');

		if ( ( $is_modern_theme && Helpers::use_autocomplete() ) || ( Helpers::use_autocomplete() && ! $is_pre_populated ) ) : ?>
			<div class="billing-address-search<?php echo $is_pre_populated ? ' billing-address-search--pre-populated' : ''; ?>">
				<p class="flexify-address-search__hint">
					<?php esc_html_e( 'Comece a digitar seu endereço para pesquisar.', 'flexify-checkout-for-woocommerce' ); ?>
					<span class="flexify-tooltip" for="billing-address-info" aria-describedby="billing-address-info">
						<i class="flexify-tooltip__icon" role="tooltip">
							<?php esc_html_e( 'Informações', 'flexify-checkout-for-woocommerce' ); ?>
						</i>
						<span class="flexify-tooltip__tip" id="billing-address-info">
							<?php esc_html_e( 'Comece com o endereço da sua casa e depois o número da casa.', 'flexify-checkout-for-woocommerce' ); ?>
						</span>
					</span>
				</p>
				<p class="form-row form-row-wide is-active" id="billing_address_info">
					<label for="billing_address_info">
						<?php esc_html_e( 'Entrega', 'flexify-checkout-for-woocommerce' ); ?>
					</label>
					<span class="woocommerce-input-wrapper">
						<input type="text" class="input-text" name="billing_address_search" id="billing_address_search" value="" />
					</span>
					<span class="error"><?php esc_html_e( 'Por favor insira seu endereço', 'flexify-checkout-for-woocommerce' ); ?></span>
				</p>
				<p class="flexify-address-button-wrapper flexify-address-button-wrapper--billing-manual">
					<button class="flexify-address-button flexify-address-button--manual flexify-address-button--billing-manual" id="billing_address_not_found">
						<?php esc_attr_e( 'Digitar endereço manualmente', 'flexify-checkout-for-woocommerce' ); ?>
					</button>
				</p>
			</div>
		<?php endif;
	}
	

	/**
	 * Render shipping search
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_shipping_address_search() {
		$is_modern = Admin_Options::get_setting('flexify_checkout_theme') === 'modern';
		$is_pre_populated = Helpers::has_prepopulated_fields( 'shipping' );

		if ( ( $is_modern && Helpers::use_autocomplete() ) || ( Helpers::use_autocomplete() && ! $is_pre_populated ) ) : ?>
			<div class="shipping-address-search<?php echo $is_pre_populated ? ' shipping-address-search--pre-populated' : ''; ?>">
				<p class="flexify-address-search__hint">
					<?php esc_html_e( 'Comece a digitar seu endereço para pesquisar.', 'flexify-checkout-for-woocommerce' ); ?>
					<span class="flexify-tooltip" for="shipping-address-info" aria-describedby="shipping-address-info">
						<i class="flexify-tooltip__icon" role="tooltip">
							<?php esc_html_e( 'Informações', 'flexify-checkout-for-woocommerce' ); ?>
						</i>
						<span class="flexify-tooltip__tip" id="shipping-address-info">
							<?php esc_html_e( 'Comece com o endereço da sua casa e depois o número da casa.', 'flexify-checkout-for-woocommerce' ); ?>
						</span>
					</span>
				</p>
				<p class="form-row form-row-wide" id="shipping_address_info">
					<label for="shipping_address_info">
						<?php esc_html_e( 'Entrega', 'flexify-checkout-for-woocommerce' ); ?>
					</label>
					<span class="woocommerce-input-wrapper">
						<input type="text" class="input-text" name="shipping_address_search" id="shipping_address_search" value="" />
					</span>
				</p>
				<p class="flexify-address-button-wrapper flexify-address-button-wrapper--shipping-manual">
					<button class="flexify-address-button flexify-address-button--manual flexify-address-button--shipping-manual" id="shipping_address_not_found">
						<?php esc_attr_e( 'Digitar endereço manualmente', 'flexify-checkout-for-woocommerce' ); ?>
					</button>
				</p>
			</div>
		<?php endif;
	}

	/**
	 * Render coupon form
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_coupon_form() {
		if ( wc_coupons_enabled() ) {
			do_action('flexify_checkout_before_coupon_form'); ?>

			<div class="woocommerce-form-coupon__wrapper">
				<?php if ( ! Helpers::is_modern_theme() ) : ?>
					<p class="enter-coupon woocommerce-form-coupon-toggle">
						<button class="enter-coupon__button showcoupon" id="enter_coupon_button" data-show-coupon>
							<?php esc_attr_e( 'Inserir cupom de desconto', 'flexify-checkout-for-woocommerce' ); ?>
						</button>
					</p>
				<?php endif;
				
				\woocommerce_checkout_coupon_form(); ?>
			</div>

			<?php do_action('flexify_checkout_after_coupon_form');
		}
	}
	

	/**
	 * Print back button
	 *
	 * @since 1.0.0
	 * @version 3.8.0
	 * @param array $step | Step slug
	 * @return void
	 */
	public static function back_button( $step ) {
		if ( 'customer-info' === $step ) {
			if ( Admin_Options::get_setting('enable_back_to_shop_button') === 'yes' ) :
				/**
				 * Filter to modify the URL for the back button
				 *
				 * @since 2.0.0
				 * @param string $url | Back button URL
				 */
				$button_url = apply_filters( 'flexify_checkout_back_button_href', get_permalink( wc_get_page_id('shop') ) ); ?>

				<a class="flexify-step__back flexify-step__back--back-shop" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html__( 'Voltar à loja', 'flexify-checkout-for-woocommerce' ); ?></a>
			<?php endif;
		} else {
			if ( ! empty( Admin_Options::get_setting('text_previous_step_button') ) ) : ?>
				<a class="flexify-step__back flexify-step__back--back-history" href="#<?php echo esc_attr( self::get_prev_step_slug( $step ) ); ?>">
					<?php echo Admin_Options::get_setting('text_previous_step_button'); ?>
				</a>
			<?php endif;
		}
	}


	/**
	 * Get selected shipping method
	 * 
	 * @since 1.0.0
	 * @version 3.9.8
	 * @return string
	 */
	public static function get_shipping_row() {
		$packages = WC()->shipping()->get_packages();
		$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

		if ( empty( $packages ) || empty( $chosen_shipping_methods ) ) {
			return '';
		}
	
		$total_shipping_cost = 0;
	
		// Iterate over packages to calculate total shipping cost
		foreach ( $packages as $index => $package ) {
			// Get the shipping method chosen for this package
			if ( isset( $chosen_shipping_methods[ $index ] ) ) {
				$chosen_method = $chosen_shipping_methods[ $index ];
				$rates = $package['rates'];
	
				// Checks if the corresponding rate is available
				if ( isset( $rates[ $chosen_method ] ) ) {
					$total_shipping_cost += (float) $rates[ $chosen_method ]->cost; // Add the cost to the total
				}
			}
		}
	
		// Returns a formatted HTML line to display the total shipping cost
		return sprintf( '<tr><th>%s</th><td>%s</td></tr>', esc_html__( 'Frete', 'flexify-checkout-for-woocommerce' ), wc_price( $total_shipping_cost ) );
	}


	/**
	 * Get all shipping options for add on checkout fragments
	 *
	 * @since 3.5.0
	 * @version 3.7.4
	 * @return string
	 */
	public static function get_shipping_options_fragment() {
		// Check if all required WooCommerce objects are available
		if ( empty( WC() ) || empty( WC()->shipping() ) || empty( WC()->cart ) ) {
			return '';
		}
	
		$packages = WC()->shipping()->get_packages();
	
		// Check for available packages and shipping rates
		if ( empty( $packages ) || empty( $packages[0]['rates'] ) || flexify_checkout_only_virtual() ) {
			return '';
		}
	
		$shipping_methods = $packages[0]['rates'];
		$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
	
		// Make sure your chosen shipping method is set
		$chosen_shipping_method = ! empty( $chosen_shipping_methods[0] ) ? $chosen_shipping_methods[0] : '';
	
		ob_start();
	
		echo '<ul class="woocommerce-shipping-methods">';
	
		foreach ( $shipping_methods as $method ) {
			// Check if the current shipping method is the one chosen
			$is_checked = $method->id === $chosen_shipping_method ? 'checked="checked"' : '';
			$is_selected_class = $method->id === $chosen_shipping_method ? 'selected-method' : '';
	
			echo sprintf(
				'<li class="shipping-method-item %s">
					<input type="radio" name="shipping_method[0]" value="%s" class="shipping_method %s" %s />
					<label>%s</label>
				</li>',
				esc_attr( $is_selected_class ),
				esc_attr( $method->id ),
				esc_attr( $is_selected_class ),
				$is_checked,
				__( $method->label . ' - ' . wc_price( $method->cost ) )
			);
		}
	
		echo '</ul>';
	
		return ob_get_clean();
	}


	/**
	 * Get all payment options for add on checkout fragments
	 *
	 * @since 3.5.0
	 * @version 3.5.1
	 * @return string
	 */
	public static function get_payment_options_fragment() {
		if ( empty( WC() ) || empty( WC()->payment_gateways() ) ) {
			return '';
		}

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		
		ob_start();

		echo '<ul class="wc_payment_methods payment_methods methods">';

		foreach ( $available_gateways as $gateway ) {
			echo sprintf('<li class="wc_payment_method payment_method_%s"><input id="payment_method_%s" type="radio" class="input-radio" name="payment_method" value="%s" %s data-order_button_text="%s" /><label for="payment_method_%s">%s</label></li>',
				esc_attr( $gateway->id ),
				esc_attr( $gateway->id ),
				esc_attr( $gateway->id ),
				checked( $gateway->chosen, true, false ),
				esc_attr( $gateway->order_button_text ),
				esc_attr( $gateway->id ),
				esc_html( $gateway->get_title() )
			);
		}

		echo '</ul>';

		return ob_get_clean();
	}


	/**
	 * Render customer details review section
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @return void
	 */
	public static function render_customer_review() {
		ob_start(); ?>

		<div class="flexify-review-customer">
			<?php
			/**
			 * Display custom content before contact info
			 * 
			 * @since 3.6.0
			 */
			do_action('flexify_checkout_before_contact_review'); 
			
			$customer_review = self::replace_placeholders( Admin_Options::get_setting('text_contact_customer_review'), self::get_review_customer_fragment(), 'billing' );

			if ( ! empty( $customer_review ) ) : ?>
				<div class="flexify-review-customer--checkout">
					<div class="flexify-review-customer__row flexify-review-customer__row--contact">
						<div class="flexify-review-customer__label flexify-review-customer__label">
							<label><?php echo esc_html__( 'Contato', 'flexify-checkout-for-woocommerce' ); ?></label>
						</div>

						<div class="flexify-review-customer__content flexify-review-customer__content--contact">
							<div class="flexify-checkout-review-customer-contact"><?php echo $customer_review ?></div>
						</div>
						
						<div class="flexify-review-customer__buttons">
							<a href="#customer-info|billing_first_name_field" data-stepper-goto="1"><?php esc_html_e( 'Editar', 'flexify-checkout-for-woocommerce' ); ?></a>
						</div>
					</div>
				</div>
			<?php endif;
			
			/**
			 * Display custom content after contact info
			 * 
			 * @since 3.6.0
			 */
			do_action('flexify_checkout_after_contact_review');

			/**
			 * Display custom content before shipping info
			 * 
			 * @since 3.6.0
			 */
			do_action('flexify_checkout_before_shipping_review');

			$has_shipping = true;

			if ( Admin_Options::get_setting('enable_optimize_for_digital_products') === 'yes' && flexify_checkout_only_virtual() ) {
				$has_shipping = false;
			}

			$session_key = WC()->session->get('flexify_checkout_ship_different_address') === 'yes' ? 'shipping' : 'billing';
			$shipping_review = self::replace_placeholders( Admin_Options::get_setting('text_shipping_customer_review'), self::get_review_customer_fragment(), $session_key );

			if ( $has_shipping && ! empty( $shipping_review ) ) : ?>
				<div class="flexify-review-customer--checkout">
					<div class="flexify-review-customer__row flexify-review-customer__row--address">
						<div class="flexify-review-customer__label">
							<label><?php esc_html_e( 'Entrega', 'flexify-checkout-for-woocommerce' ); ?></label>
						</div>

						<div class="flexify-review-customer__content flexify-review-customer__content--address">
							<div class="flexify-checkout-review-shipping-address"><?php echo $shipping_review ?></div>
						</div>

						<div class="flexify-review-customer__buttons">
							<a href="#address|billing_country" data-stepper-goto="2"><?php esc_html_e( 'Editar', 'flexify-checkout-for-woocommerce' ); ?></a>
						</div>
					</div>
				</div>

				<?php if ( ! empty( Helpers::get_shipping_method() ) ) : ?>
					<div class="flexify-review-customer--checkout">
						<div class="flexify-review-customer__row flexify-review-customer__row--shipping-method">
							<div class="flexify-review-customer__label">
								<label><?php esc_html_e('Frete', 'flexify-checkout-for-woocommerce'); ?></label>
							</div>

							<div class="flexify-review-customer__content flexify-review-customer__content--shipping-method">
								<div class="flexify-checkout-review-shipping-method"><?php echo esc_html( Helpers::get_shipping_method() ); ?></div>
							</div>

							<div class="flexify-review-customer__buttons">
								<a href="#address|shipping_method" data-stepper-goto="2"><?php esc_html_e('Editar', 'flexify-checkout-for-woocommerce'); ?></a>
							</div>
						</div>
					</div>
				<?php endif;
			endif;

			/**
			 * Display custom content after shipping info
			 * 
			 * @since 3.6.0
			 */
			do_action('flexify_checkout_after_shipping_review'); ?>
		</div>

		<?php return ob_get_clean();
	}


	/**
	 * Get review customer fragment
	 *
	 * @since 1.0.0
	 * @version 3.9.8
	 * @return array
	 */
	public static function get_review_customer_fragment() {
		// Get checkout session data
		$session_data = WC()->session->get('flexify_checkout_customer_fields');
		$fragment_data = array();

		// Returns an empty array if session data is not loaded
		if ( ! is_array( $session_data ) || empty( $session_data ) ) {
			return $fragment_data;
		}

		foreach ( $session_data as $field_id => $value ) {
			$fragment_data[$field_id] = isset( $value ) ? $value : '';
		}

		return apply_filters( 'flexify_checkout_review_customer_fragments', $fragment_data );
	}


	/**
	 * Get customer review text with placeholder values
	 * 
	 * @since 3.6.0
	 * @version 3.9.8
	 * @param string $text | Text with placeholders
	 * @param array $data | Data for replace on placeholders
	 * @param string $prefix | Optional prefix to filter data (billing/shipping)
	 * @return string
	 */
	public static function replace_placeholders( $text, $data, $prefix = 'billing' ) {
		if ( empty( $data ) ) {
			return '';
		}

		// Filter data by prefix if provided
		if ( $prefix ) {
			$data = array_filter( $data, function( $key ) use ( $prefix ) {
				return strpos( $key, $prefix . '_' ) === 0;
			}, ARRAY_FILTER_USE_KEY );

			// Remove prefix from keys
			$data = array_combine(
				array_map( function( $key ) use ( $prefix ) {
					return str_replace( $prefix . '_', '', $key );
				}, array_keys( $data ) ),
				$data
			);
		}

		$placeholders = array_map( 'esc_html', $data );

		// split text into chunks and replace placeholders
		$parts = preg_split( '/(\{\{\s*\w+\s*\}\}|<br>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		$output = '<div class="customer-review-container">';
		$current_paragraph = '';

		foreach ( $parts as $part ) {
			if ( preg_match( '/\{\{\s*(\w+)\s*\}\}/', $part, $matches ) ) {
				$key = $matches[1];
				$value = $placeholders[ $key ] ?? $matches[0];
				$current_paragraph .= sprintf( '<span class="customer-details-info %s">%s</span>', esc_attr( $key ), $value );
			} elseif ( $part === '<br>' ) {
				if ( ! empty( $current_paragraph ) ) {
					$output .= '<p class="customer-details-info">' . $current_paragraph . '</p>';
					$current_paragraph = '';
				}
			} else {
				$current_paragraph .= $part;
			}
		}

		if ( ! empty( $current_paragraph ) ) {
			$output .= '<p class="customer-details-info">' . $current_paragraph . '</p>';
		}

		$output .= '</div>';

		return $output;
	}
	

	/**
	 * Get steps formatted for use in the stepper
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_steps_hashes() {
		$steps = self::get_steps();
		$result = array();

		foreach ( $steps as $index => $step ) {
			$result[ $index + 1 ] = $step['slug'];
		}

		return $result;
	}


	/**
	 * Get slug of the previous step
	 *
	 * @since 1.0.0
	 * @param string $current_step
	 * @return string
	 */
	public static function get_prev_step_slug( $current_step ) {
		$steps = self::get_steps_hashes();
		$current_index = 0;

		foreach ( $steps as $index => $hash ) {
			if ( $hash === $current_step ) {
				$current_index = $index;
			}
		}

		$prev_index = --$current_index;

		return isset( $steps[ $prev_index ] ) ? $steps[ $prev_index ] : '';
	}


	/**
	 * Disable the address step
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @param array $steps | Checkout steps
	 * @return array
	 */
	public static function disable_address_step( $steps ) {
		if ( ! flexify_checkout_only_virtual() ) {
			return $steps;
		}

		// remove address step
		unset( $steps[1] );

		// re-index the array
		return array_values( $steps );
	}
}