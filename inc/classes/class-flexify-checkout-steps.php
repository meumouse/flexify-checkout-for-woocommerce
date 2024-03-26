<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle de steps
 *
 * @since 1.0.0
 * @version 1.7.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Steps {

	/**
	 * Render header
	 *
	 * @since 1.0.0
	 */
	public static function render_header( $show_breadcrumps = true ) {
		/**
		 * Before Header
		 *
		 * @since 1.0.0
		 */
		do_action('flexify_checkout_before_header'); ?>

		<header class="flexify-checkout__header header">
			<div class="header__inner">
				<?php
				/**
				 * Allows you to override the hyperlink on the header logo.
				 *
				 * @since 1.0.0
				 */
				$back_url = apply_filters( 'flexify_checkout_logo_href', esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ) ); ?>

				<a class="header__link" href="<?php echo esc_url( $back_url ); ?>">
					<?php
					if ( Flexify_Checkout_Init::get_setting('checkout_header_type') === 'text' ) {
						?>
						<h1 class="header__title"><?php echo esc_html( Flexify_Checkout_Helpers::get_header_text() ); ?></h1>
						<?php 
					} else {
						$width = Flexify_Checkout_Helpers::get_logo_width(); 

						if ( Flexify_Checkout_Helpers::get_logo_image() !== NULL ) {
							?>
								<img class="header__image" src="<?php echo esc_url( Flexify_Checkout_Helpers::get_logo_image() ); ?>"
									<?php echo ! empty( $width ) ? 'style="width:' . esc_attr( $width ) . Flexify_Checkout_Init::get_setting('unit_header_width_image_checkout') .'"' : ''; ?> />
							<?php
						}
					} ?>
				</a>
			</div>
			<?php
			
			if ( $show_breadcrumps ) {
				self::render_stepper();
			}
			?>
		</header>

		<?php
		/**
		 * After header
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_checkout_after_header' );
	}


	/**
	 * Render Stepper
	 */
	public static function render_stepper() {
		if ( Flexify_Checkout_Core::is_thankyou_page() ) {
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
	 * @return void
	 */
	public static function render_steps() {
		$checkout = WC()->checkout;
		$theme = Flexify_Checkout_Core::get_theme();
		$show_back_to_shop = Flexify_Checkout_Init::get_setting('enable_back_to_shop_button') === 'yes';
		$steps = self::get_steps();

		if ( empty( $steps ) ) {
			return;
		}

		foreach ( $steps as $key => $step ) {
			?>
			<section data-step="<?php echo esc_attr( $key + 1 ); ?>" class="flexify-step flexify-step--<?php echo esc_attr( $key + 1 ); ?> flexify-step--<?php echo esc_attr( $step['slug'] ); ?>" <?php echo 0 !== $key ? 'style="display:none;" aria-hidden="true"' : ''; ?>>
				<?php
				// @todo: should these be blocks, so they can be placed by the customer?
				if ( 0 === $key ) {
					/**
					 * Before customer details
					 *
					 * @since 1.0.0
					 */
					do_action( 'woocommerce_checkout_before_customer_details' );

					/**
					 * Before checkout billing form.
					 *
					 * @param WC_Checkout $checkout
					 *
					 * @since 1.0.0
					 */
					do_action( 'woocommerce_before_checkout_billing_form', $checkout );
				}
				?>
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
				<?php
				// Render before last step.
				if ( count( $steps ) - 2 === $key ) {
					// @todo: should these be blocks, so they can be placed by the customer?
					/**
					 * After checkout billing form.
					 *
					 * @param WC_Checkout $checkout Checkout object.
					 *
					 * @since 2.0.0
					 */
					do_action( 'woocommerce_after_checkout_billing_form', $checkout );

					/**
					 * After customer details.
					 *
					 * @since 2.0.0
					 */
					do_action( 'woocommerce_checkout_after_customer_details' );
				}
				if ( count( $steps ) - 1 !== $key ) {
					if ( 'classic' === $theme ) {
						?>
						<button class="flexify-button--step flexify-button" data-step-next data-step-show="<?php echo esc_attr( $key + 2 ); ?>">
							<?php esc_html_e( 'Continuar', 'flexify-checkout-for-woocommerce' ); ?>
						</button>
						<?php
					} else {
						?>
						<footer class="flexify-footer <?php echo ( ! $show_back_to_shop && 'details' === $step['slug'] ) ? 'flexify-footer--no-back-shop' : ''; ?>">
							<?php self::back_button( $step['slug'] ); ?>
							<button class="flexify-button" data-step-next data-step-show="<?php echo esc_attr( $key + 2 ); ?>">
								<?php esc_html_e( 'Continuar para', 'flexify-checkout-for-woocommerce' ); ?>&nbsp;<?php echo strtolower( esc_html( $steps[ $key + 1 ]['title'] ) ); ?>
							</button>
						</footer>
						<?php
					}
				}
				?>
			</section>
			<?php
		}
	}


	/**
	 * Get checkout steps
	 *
	 * Currently returns steps from an array. In the future this will support
	 * steps being defined via sub-pages of the checkout page.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_steps() {
		$steps = array(
			array(
				'callback' => array( __CLASS__, 'render_default_customer_details' ),
				'slug' => 'customer-info',
				'title' => esc_html__( 'Contato', 'flexify-checkout-for-woocommerce' ),
				'post_id' => 0,
			),
			array(
				'callback' => array( __CLASS__, 'render_default_billing_address' ),
				'slug' => 'address',
				'title' => esc_html__( 'Entrega', 'flexify-checkout-for-woocommerce' ),
				'post_id' => 0,
			),
			array(
				'callback' => array( __CLASS__, 'render_payment_details' ),
				'slug' => 'payment',
				'title' => esc_html__( 'Pagamento', 'flexify-checkout-for-woocommerce' ),
				'post_id' => 0,
			),
		);

		/**
		 * Filters the Custom Steps.
		 *
		 * @since 1.0.0
		 * @param array $steps Steps.
		 * @return array
		 */
		return apply_filters( 'flexify_custom_steps', $steps );
	}

	
	/**
	 * Get Default Billing Address.
	 *
	 * Get the billing address when page has not been defined.
	 */
	public static function render_default_customer_details() {
		$checkout = WC()->checkout;
		$theme = Flexify_Checkout_Core::get_theme();

		if ( 'classic' === $theme ) {
			// @todo this needs turning into a dynamic block when Gutenberg integration is built.
			self::render_login_button();
		}

		// @todo: this will be a title block.

		$has_login_btn_class = ( ! is_user_logged_in() && 'no' !== get_option( 'woocommerce_enable_checkout_login_reminder' ) ) ? 'flexify-heading--has-login-btn' : '';
		?>

		<h2 class="flexify-heading flexify-heading--customer-details  <?php echo esc_attr( $has_login_btn_class ); ?>"><?php echo esc_html__( 'Informações do cliente', 'flexify-checkout-for-woocommerce' ); ?></h2>
		<?php

		if ( 'classic' !== $theme ) {
			// @todo this needs turning into a dynamic block when Gutenberg integration is built.
			self::render_modern_login_button();
		}

		// @todo dynamic block needed for each type of Woo field, plus additional custom fields.
		foreach ( Flexify_Checkout_Helpers::get_details_fields( $checkout ) as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		};

		// @todo this needs turning into a dynamic block when Gutenberg integration is built.
		self::render_account_form( $checkout );
	}


	/**
	 * Get the billing address when page has not been defined
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_default_billing_address() {
		$checkout = WC()->checkout;
		$is_modern_theme = Flexify_Checkout_Helpers::is_modern_theme();

		if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) {
			$billing_title = esc_html__( 'Endereço de cobrança e entrega', 'flexify-checkout-for-woocommerce' );
		} else {
			$billing_title = esc_html__( 'Endereço de entrega', 'flexify-checkout-for-woocommerce' );
		}

		if ( $is_modern_theme ) {
			self::render_customer_review();
		}

		// @todo: this will be a title block, however because it needs logic, we may need to create a custom block.
		?>
		<h2 class="flexify-heading flexify-heading--billing"><?php echo esc_html( $billing_title ); ?></h2>

		<?php
		/**
		 * After billing address heading
		 *
		 * @since 1.0.0
		 */
		do_action( 'flexify_checkout_before_billing_address_heading' );

		// @todo: this will be a wrapper block that will contain fields (billing form wrapper block,
		// with option for address search. Fields can only be inserted into wrapper, so show hide works correctly).
		?>
		<div class="woocommerce-billing-fields__wrapper">
			<?php self::render_address_search(); ?>
			<div class="woocommerce-billing-fields">
				<div class="woocommerce-billing-fields__fields-wrapper">
					<?php if ( Flexify_Checkout_Helpers::is_modern_theme() ) { ?>
						<p class="flexify-address-button-wrapper flexify-address-button-wrapper--billing-lookup">
							<button class="flexify-address-button flexify-address-button--lookup flexify-address-button--billing-lookup">
								<?php esc_attr_e( 'Pesquisar um endereço', 'flexify-checkout-for-woocommerce' ); ?>
							</button>
						</p>
					<?php } ?>
					<?php

					// @todo dynamic block needed for each type of Woo field, plus additional custom fields.
					foreach ( Flexify_Checkout_Helpers::get_billing_fields( $checkout ) as $key => $field ) {
						woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
					};

					?>
					<div class="clear"></div>
				</div>
			</div>
		</div>

		<?php
		// @todo: this will be a wrapper block that will contain fields (shipping form wrapper block,
		// with option for address search. Fields can only be inserted into wrapper, so show hide works correctly).
		?>
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
						$checked = checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1, false );
						?>
						<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="ship_to_different_address" value="1" <?php echo esc_attr( $checked ); ?>/>
						<span class="toggle__ie11"></span>
						<span><?php esc_html_e( 'Enviar para um endereço diferente?', 'flexify-checkout-for-woocommerce' ); ?></span>
					</label>
				</h3>
				<div class="shipping_address">
					<?php self::render_shipping_address_search(); ?>
					<div class="woocommerce-shipping-fields">
						<div class="woocommerce-shipping-fields__fields-wrapper">
							<?php if ( Flexify_Checkout_Helpers::is_modern_theme() ) { ?>
								<p class="flexify-address-button-wrapper flexify-address-button-wrapper--shipping-lookup">
									<button class="flexify-address-button flexify-address-button--lookup flexify-address-button--shipping-lookup">
										<?php esc_attr_e( 'Procurar um endereço', 'flexify-checkout-for-woocommerce' ); ?>
									</button>
								</p>
							<?php } ?>
							<?php

							foreach ( Flexify_Checkout_Helpers::get_shipping_fields( $checkout ) as $key => $field ) {
								woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
							};

							?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<table class="flexify-checkout__shipping-table">
			<tbody></tbody>
		</table>

		<?php

		/**
		 * Enable order notes field
		 *
		 * @since 1.0.0
		 * @return void
		 */
		if ( apply_filters( 'woocommerce_enable_order_notes_field', true ) ) {
			?>
			<div class="woocommerce-additional-fields__wrapper">
				<?php
					/**
					 * Before order notes.
					 *
					 * @since 1.0.0
					 */
					do_action( 'woocommerce_before_order_notes', $checkout );
				?>
				<h3 id="show-additional-fields">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input id="show-additional-fields-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="show_additional_fields" value="1" />
						<span class="toggle__ie11"></span>
						<span><?php esc_html_e( 'Adicionar observações ao pedido?', 'flexify-checkout-for-woocommerce' ); ?></span>
					</label>
				</h3>
				<div class="woocommerce-additional-fields" style="display:none;" aria-hidden="true">
					<div class="woocommerce-additional-fields__field-wrapper">
						<?php
						// @todo dynamic block needed for each type of Woo field, plus additional custom fields.
						foreach ( $checkout->checkout_fields['order'] as $key => $field ) {
							woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
						};
						?>
					</div>
				</div>
				<?php
					/**
					 * After order notes.
					 *
					 * @since 1.0.0
					 */
					do_action( 'woocommerce_after_order_notes', $checkout );
				?>
			</div>
			<?php
		}
	}

	/**
	 * Get Payment Details.
	 *
	 * Get the payment details when page has not been defined.
	 */
	public static function render_payment_details() {
		$is_modern_theme = Flexify_Checkout_Helpers::is_modern_theme();

		/**
		 * Before checkout order review heading.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_checkout_before_order_review_heading' );

		if ( $is_modern_theme ) {
			self::render_customer_review(); ?>
			
			<h2 class="flexify-heading flexify-heading--payment"><?php echo esc_html__( 'Forma de pagamento', 'flexify-checkout-for-woocommerce' ); ?></h2>
			<?php
		}

		// @todo create a block component for show/hide coupon code.
		if ( ! Flexify_Checkout_Sidebar::is_sidebar_enabled() ) {
			self::render_coupon_form();
		}

		// @todo: this will be a title block.
		$heading_class = Flexify_Checkout_Sidebar::is_sidebar_enabled() ? '' : 'flexify-heading--order-review'; ?>

		<h2 class="flexify-heading <?php echo esc_attr( $heading_class ); ?>" id="order_review_heading">
			<?php esc_html_e( 'Pagamento', 'flexify-checkout-for-woocommerce' ); ?>
		</h2>

		<?php
		/**
		 * Before checkout order review
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_checkout_before_order_review' ); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php
			/**
			 * Checkout order review
			 *
			 * @since 1.0.0
			 */
			do_action( 'woocommerce_checkout_order_review' ); ?>
		</div>

		<?php
		/**
		 * After checkout order review
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_checkout_after_order_review' );
	}

	/**
	 * Render Login Button.
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

		$style = $checkout->enable_guest_checkout ? 'display:none;' : 'display:block;'

		?>
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
						$woocommerce_create_account_default_checked = apply_filters( 'woocommerce_create_account_default_checked', false );
						?>
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
					<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
						<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
					<?php endforeach; ?>
					<div class="password-meter">
						<div class="password-strength-meter"></div>
					</div>
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
	 * Render customer details review section
	 *
	 * @since 1.0.0
	 * @param string $customer_name
	 * @param string $customer_phone
	 * @param string $customer_email
	 * @param string $customer_address
	 * @return void
	 */
	public static function render_customer_review( $customer_name = '', $customer_phone = '', $customer_email = '', $customer_address = '' ) {
		?>
		<div class="flexify-review-customer flexify-review-customer--checkout">
			<?php if ( $customer_name || $customer_phone || $customer_email ) { ?>
				<div class="flexify-review-customer__row flexify-review-customer__row--contact">
					<div class="flexify-review-customer__label flexify-review-customer__label">
						<label><?php esc_html_e( 'Contato', 'flexify-checkout-for-woocommerce' ); ?></label>
					</div>
					<div class="flexify-review-customer__content">
						<p class="woocommerce-customer-details--name"><?php echo esc_html( $customer_name ); ?></p>
						<p class="woocommerce-customer-details--phone"><?php echo esc_html( $customer_phone ); ?></p>
						<p class="woocommerce-customer-details--email"><?php echo esc_html( $customer_email ); ?></p>
					</div>
					<div class="flexify-review-customer__buttons">
						<a href="#customer-info|billing_first_name_field" data-stepper-goto='1'><?php esc_html_e( 'Editar', 'flexify-checkout-for-woocommerce' ); ?></a>
					</div>
				</div>
			<?php } ?>
			<?php if ( $customer_address ) { ?>
				<div class="flexify-review-customer__row flexify-review-customer__row--address">
					<div class="flexify-review-customer__label">
						<label><?php esc_html_e( 'Entrega', 'flexify-checkout-for-woocommerce' ); ?></label>
					</div>
					<div class="flexify-review-customer__content flexify-review-customer__content--address">
						<p><?php echo esc_html( $customer_address ); ?></p>
					</div>
					<div class="flexify-review-customer__buttons">
						<a href="#address|billing_country" data-stepper-goto='2'><?php esc_html_e( 'Editar', 'flexify-checkout-for-woocommerce' ); ?></a>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render Address Search.
	 */
	public static function render_address_search() {
		$is_modern_theme = Flexify_Checkout_Helpers::is_modern_theme();
		$is_pre_populated = Flexify_Checkout_Helpers::has_prepopulated_fields( 'billing' );

		// @todo billing form wrapper block, with option for address search. Fields can only be inserted into wrapper, so show hide works correctly.
		if ( ( $is_modern_theme && Flexify_Checkout_Helpers::use_autocomplete() ) || ( Flexify_Checkout_Helpers::use_autocomplete() && ! $is_pre_populated ) ) {
			?>
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
			<?php
		}
	}

	/**
	 * Render Shipping Search.
	 */
	public static function render_shipping_address_search() {
		$is_modern = Flexify_Checkout_Init::get_setting('flexify_checkout_theme') === 'modern';
		$is_pre_populated = Flexify_Checkout_Helpers::has_prepopulated_fields( 'shipping' );

		// @todo shipping form wrapper block, with option for address search. Fields can only be inserted into wrapper, so show hide works correctly.
		if ( ( $is_modern && Flexify_Checkout_Helpers::use_autocomplete() ) || ( Flexify_Checkout_Helpers::use_autocomplete() && ! $is_pre_populated ) ) {
			?>
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
			<?php
		}
	}

	/**
	 * Render coupon form
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_coupon_form() {
		if ( wc_coupons_enabled() ) {
			do_action( 'flexify_checkout_before_coupon_form' ); ?>

			<div class="woocommerce-form-coupon__wrapper">
				<?php if ( ! Flexify_Checkout_Helpers::is_modern_theme() ) { ?>
					<p class="enter-coupon woocommerce-form-coupon-toggle">
						<button class="enter-coupon__button showcoupon" id="enter_coupon_button" data-show-coupon>
							<?php esc_attr_e( 'Inserir cupom de desconto', 'flexify-checkout-for-woocommerce' ); ?>
						</button>
					</p>
				<?php } ?>
				<?php woocommerce_checkout_coupon_form(); ?>
			</div>
			<?php do_action( 'flexify_checkout_after_coupon_form' );
		}
	}
	

	/**
	 * Print Back button.
	 *
	 * @since 1.0.0
	 * @param array $step_slug
	 * @return void
	 */
	public static function back_button( $step_slug ) {
		$show_back_to_shop = Flexify_Checkout_Init::get_setting('enable_back_to_shop_button');

		if ( 'details' === $step_slug ) {
			if ( $show_back_to_shop == 'yes' ) {
				$shop_id = wc_get_page_id( 'shop' );

				/**
				 * Filter to modify the URL for the back button.
				 *
				 * @param string $url Back button URL.
				 *
				 * @since 2.0.0
				 */
				$flexify_checkout_back_button_href = apply_filters( 'flexify_checkout_back_button_href', get_permalink( $shop_id ) ); ?>

				<a class="flexify-step__back flexify-step__back--back-shop" href="<?php echo esc_url( $flexify_checkout_back_button_href ); ?>">
					<?php echo esc_html__( 'Voltar à loja', 'flexify-checkout-for-woocommerce' ); ?>
				</a>
				<?php
			}
		} else {
			$prev_slug = self::get_prev_step_slug( $step_slug ); ?>

			<a class="flexify-step__back flexify-step__back--back-history" href="#<?php echo esc_attr( $prev_slug ); ?>">
				<?php esc_html_e( 'Voltar', 'flexify-checkout-for-woocommerce' ); ?>
			</a>
			<?php
		}
	}


	/**
	 * Get shipping price row for mobile view
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_shipping_row() {
		if ( empty( WC() ) || empty( WC()->shipping() ) || empty( WC()->session ) || empty( WC()->session->chosen_shipping_methods[0] ) ) {
			return '';
		}
	
		$packages = WC()->shipping()->get_packages();
		$chosen_shipping_method = WC()->session->chosen_shipping_methods[0];
	
		if ( empty( $packages ) || empty( $packages[0]['rates'][$chosen_shipping_method] ) ) {
			return '';
		}
	
		// Do not show shipping if address is empty.
		$formatted_destination = WC()->countries->get_formatted_address( $packages[0]['destination'], ', ' );

		if ( empty( $formatted_destination ) ) {
			return;
		}
	
		$shipping_rate = $packages[0]['rates'][$chosen_shipping_method];
	
		if ( empty( $shipping_rate->label ) || empty( $shipping_rate->cost ) ) {
			return '';
		}
	
		return sprintf('<th>%s</th><td>%s</td>', esc_html( $shipping_rate->label ), wc_price( $shipping_rate->cost ) );
	}


	/**
	 * Get review customer frament
	 *
	 * @since 1.0.0
	 * @return string|false
	 */
	public static function get_review_customer_fragment() {
		global $woocommerce;

		$session_data = WC()->session->get('flexify_checkout');
		$billing_first_name = isset( $session_data['first_name'] ) ? $session_data['first_name'] : '';
		$billing_last_name = isset( $session_data['last_name'] ) ? $session_data['last_name'] : '';
		$billing_phone = isset( $session_data['phone'] ) ? $session_data['phone'] : '';
		$billing_email = isset( $session_data['email'] ) ? $session_data['email'] : '';
		$customer_address = '';
		$customer_name = sprintf( '%s %s', esc_html( $billing_first_name ), esc_html( $billing_last_name ), );
		$customer_phone = sprintf( '%s', esc_html( $billing_phone ) );
		$customer_email = sprintf( '%s', esc_html( $billing_email ) );
		$billing_address = isset( $session_data['billing_address_1'] ) ? $session_data['billing_address_1'] . ',' : '';
		$billing_number = isset( $session_data['billing_number'] ) ? $session_data['billing_number'] . ',' : '';
		$billing_neighborhood = isset( $session_data['billing_neighborhood'] ) ? $session_data['billing_neighborhood'] . ',' : '';
		$city = isset( $session_data['billing_city'] ) ? $session_data['billing_city'] . ' - ' : '';
		$state = isset( $session_data['billing_state'] ) ? $session_data['billing_state'] : '';
		$postcode = isset( $session_data['billing_postcode'] ) ? $session_data['billing_postcode'] : '';

		$customer_address = sprintf(
			'%s %s %s %s %s (%s: %s)',
			esc_html( $billing_address ),
			esc_html( $billing_number ),
			esc_html( $billing_neighborhood ),
			esc_html( $city ),
			esc_html( $state ),
			__('CEP', 'flexify-checkout-for-woocommerce'), esc_html( $postcode )
		);
	
		ob_start();
	
		self::render_customer_review( $customer_name, $customer_phone, $customer_email, $customer_address );
	
		return ob_get_clean();
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
}