<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( class_exists( 'Flexify_Checkout_Compat_Woodmart' ) ) {
	return;
}

/**
 * Compatibility with Woodmart theme
 *
 * @since 1.0.0
 * @version 1.5.0
 * @package MeuMouse.com
 */
class Flexify_Checkout_Compat_Woodmart {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp', array( __CLASS__, 'compat_woodmart' ) );
		add_action( 'woocommerce_login_form_end', array( __CLASS__, 'render_social_login' ) );
	}


	/**
	 * Disable Woodmart styles
	 * 
	 * @since 1.0.0
	 */
	public static function compat_woodmart() {
		if ( ! Flexify_Checkout_Core::is_checkout() ) {
			return;
		}

		remove_action( 'wp_enqueue_scripts', 'woodmart_enqueue_styles', 10000 );
		remove_action( 'wp_footer', 'woodmart_mobile_menu', 130 );
		remove_action( 'wp_footer', 'woodmart_full_screen_main_nav', 120 );
		remove_action( 'wp_footer', 'woodmart_extra_footer_action', 500 );
		remove_action( 'wp_footer', 'woodmart_search_full_screen', 1 );
		remove_action( 'wp_footer', 'woodmart_core_outdated_message', 10 );
		remove_action( 'wp_footer', 'woodmart_cart_side_widget', 140 );
		remove_action( 'wp_footer', 'woodmart_sticky_toolbar_template', 10 );
		remove_all_actions( 'woocommerce_review_order_before_cart_contents' );
	}


	/**
	 * Render Social Login.
	 *
	 * @return void
	 */
	public static function render_social_login() {
		if ( ! Flexify_Checkout_Core::is_checkout() || ! function_exists( 'woodmart_get_opt' ) ) {
			return;
		}

		$vk_app_id = woodmart_get_opt( 'vk_app_id' );
		$vk_app_secret = woodmart_get_opt( 'vk_app_secret' );
		$fb_app_id = woodmart_get_opt( 'fb_app_id' );
		$fb_app_secret = woodmart_get_opt( 'fb_app_secret' );
		$goo_app_id = woodmart_get_opt( 'goo_app_id' );
		$goo_app_secret = woodmart_get_opt( 'goo_app_secret' );

		if ( class_exists( 'WOODMART_Auth' ) && ( ( ! empty( $fb_app_id ) && ! empty( $fb_app_secret ) ) || ( ! empty( $goo_app_id ) && ! empty( $goo_app_secret ) ) || ( ! empty( $vk_app_id ) && ! empty( $vk_app_secret ) ) ) ) {
			?>
			<?php woodmart_enqueue_inline_style( 'social-login' ); ?>
			<div class="title wd-login-divider social-login-title<?php echo esc_attr( woodmart_get_old_classes( ' wood-login-divider' ) ); ?>"><span><?php esc_html_e( 'Ou faça login com', 'woodmart' ); ?></span></div>
			<div class="wd-social-login">
				<?php if ( ! empty( $fb_app_id ) && ! empty( $fb_app_secret ) ) : ?>
					<div class="social-login-btn">
						<a href="<?php echo esc_url( add_query_arg( 'social_auth', 'facebook', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="login-fb-link btn"><?php esc_html_e( 'Facebook', 'woodmart' ); ?></a>
					</div>
				<?php endif ?>
				<?php if ( ! empty( $goo_app_id ) && ! empty( $goo_app_secret ) ) : ?>
					<div class="social-login-btn">
						<a href="<?php echo esc_url( add_query_arg( 'social_auth', 'google', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="login-goo-link btn"><?php esc_html_e( 'Google', 'woodmart' ); ?></a>
					</div>
				<?php endif ?>
				<?php if ( ! empty( $vk_app_id ) && ! empty( $vk_app_secret ) ) : ?>
					<div class="social-login-btn">
						<a href="<?php echo esc_url( add_query_arg( 'social_auth', 'vkontakte', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="login-vk-link btn"><?php esc_html_e( 'VKontakte', 'woodmart' ); ?></a>
					</div>
				<?php endif ?>
			</div>
			<?php
		}
	}
}