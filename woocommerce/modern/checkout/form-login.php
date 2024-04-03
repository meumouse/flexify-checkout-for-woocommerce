<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Login form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 1.0.0
 */

if ( is_user_logged_in() ) {
	return;
}

$auto_open_class = filter_input( INPUT_POST, 'login' ) ? 'woocommerce-form-login--auto-open' : ''; ?>

<div class='woocommerce-form-login-wrap'>
	<form class="woocommerce-form woocommerce-form-login login <?php echo esc_attr( $auto_open_class ); ?>" method="post">
		<?php
		/**
		 * Before login form.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_login_form_start' ); ?>

		<h2><?php esc_html_e( 'Entre em sua conta de cliente:', 'flexify-checkout-for-woocommerce' ); ?></h2>

		<?php echo ! empty( $message ) ? wp_kses_post( wpautop( wptexturize( $message ) ) ) : ''; ?>

		<p class="form-row form-row-first">
			<label for="username"><?php esc_html_e( 'Nome de usuário ou email', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
			<input type="text" class="input-text" name="username" id="username" autocomplete="username" />
		</p>
		<div class="user-password-login">
			<p class="form-row form-row-last">
				<label for="password"><?php esc_html_e( 'Senha', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
				<input class="input-text flexify-login-password" type="password" name="password" id="password" autocomplete="current-password" />
			</p>
			<div class="toggle-password-visibility">
				<svg class="toggle show-password" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1)"><path d="M12 9a3.02 3.02 0 0 0-3 3c0 1.642 1.358 3 3 3 1.641 0 3-1.358 3-3 0-1.641-1.359-3-3-3z"></path><path d="M12 5c-7.633 0-9.927 6.617-9.948 6.684L1.946 12l.105.316C2.073 12.383 4.367 19 12 19s9.927-6.617 9.948-6.684l.106-.316-.105-.316C21.927 11.617 19.633 5 12 5zm0 12c-5.351 0-7.424-3.846-7.926-5C4.578 10.842 6.652 7 12 7c5.351 0 7.424 3.846 7.926 5-.504 1.158-2.578 5-7.926 5z"></path></svg>
				<svg class="toggle hide-password" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1)"><path d="M12 19c.946 0 1.81-.103 2.598-.281l-1.757-1.757c-.273.021-.55.038-.841.038-5.351 0-7.424-3.846-7.926-5a8.642 8.642 0 0 1 1.508-2.297L4.184 8.305c-1.538 1.667-2.121 3.346-2.132 3.379a.994.994 0 0 0 0 .633C2.073 12.383 4.367 19 12 19zm0-14c-1.837 0-3.346.396-4.604.981L3.707 2.293 2.293 3.707l18 18 1.414-1.414-3.319-3.319c2.614-1.951 3.547-4.615 3.561-4.657a.994.994 0 0 0 0-.633C21.927 11.617 19.633 5 12 5zm4.972 10.558-2.28-2.28c.19-.39.308-.819.308-1.278 0-1.641-1.359-3-3-3-.459 0-.888.118-1.277.309L8.915 7.501A9.26 9.26 0 0 1 12 7c5.351 0 7.424 3.846 7.926 5-.302.692-1.166 2.342-2.954 3.558z"></path></svg>
			</div>
		</div>
		<div class="clear"></div>

		<?php
		/**
		 * Login form.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_login_form' ); ?>

		<p class="form-row">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
				<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Lembrar de mim', 'woocommerce' ); ?></span>
			</label>
			<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
			<input type="hidden" name="redirect" value="<?php echo esc_url( wc_get_checkout_url() ); ?>" />
			<button type="submit" class="flexify-button woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Entrar', 'woocommerce' ); ?>"><?php esc_html_e( 'Entrar', 'woocommerce' ); ?></button>
		</p>
		<p class="lost_password">
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Esqueceu sua senha?', 'woocommerce' ); ?></a>
		</p>

		<?php
		/**
		 * After login form.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_login_form_end' ); ?>

		<div class="clear"></div>
	</form>
</div>