<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Woodmart theme
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Woodmart {
	
	/**
     * Construct function
     *
     * @since 1.0.0
     * @version 5.0.0
     * @return void
     */
    public function __construct() {
		add_action( 'wp', array( $this, 'remove_actions' ) );
		add_action( 'woocommerce_login_form_end', array( $this, 'render_social_login' ) );
	}


	/**
	 * Remove Woodmart actions
	 * 
	 * @since 1.0.0
	 * @version 5.0.0
     * @return void
	 */
	public function remove_actions() {
		if ( ! is_flexify_template() || ! defined('WOODMART_VERSION') || ! function_exists('woodmart_theme_setup') || ! function_exists('woodmart_child_enqueue_styles') ) {
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
	 * Render social login
	 *
	 * @since 1.0.0
	 * @version 5.0.0
	 * @return void
	 */
	public function render_social_login() {
		if ( ! is_flexify_checkout() || ! function_exists('woodmart_get_opt') ) {
			return;
		}

		$vk_app_id = woodmart_get_opt('vk_app_id');
		$vk_app_secret = woodmart_get_opt('vk_app_secret');
		$fb_app_id = woodmart_get_opt('fb_app_id');
		$fb_app_secret = woodmart_get_opt('fb_app_secret');
		$goo_app_id = woodmart_get_opt('goo_app_id');
		$goo_app_secret = woodmart_get_opt('goo_app_secret');

		if ( class_exists('WOODMART_Auth') && ( ( ! empty( $fb_app_id ) && ! empty( $fb_app_secret ) ) || ( ! empty( $goo_app_id ) && ! empty( $goo_app_secret ) ) || ( ! empty( $vk_app_id ) && ! empty( $vk_app_secret ) ) ) ) : 
			woodmart_enqueue_inline_style('social-login'); ?>

			<div class="title wd-login-divider social-login-title <?php echo esc_attr( woodmart_get_old_classes('wood-login-divider') ); ?>">
				<span><?php esc_html_e( 'Ou faça login com', 'woodmart' ); ?></span>
			</div>

			<div class="wd-social-login">
				<?php if ( ! empty( $fb_app_id ) && ! empty( $fb_app_secret ) ) : ?>
					<div class="social-login-btn facebook">
						<a href="<?php echo esc_url( add_query_arg( 'social_auth', 'facebook', wc_get_page_permalink('myaccount') ) ); ?>" class="login-fb-link btn">
							<svg class="social-icon" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g><path fill="#1877F2" d="M15 8a7 7 0 00-7-7 7 7 0 00-1.094 13.915v-4.892H5.13V8h1.777V6.458c0-1.754 1.045-2.724 2.644-2.724.766 0 1.567.137 1.567.137v1.723h-.883c-.87 0-1.14.54-1.14 1.093V8h1.941l-.31 2.023H9.094v4.892A7.001 7.001 0 0015 8z"></path><path fill="#ffffff" d="M10.725 10.023L11.035 8H9.094V6.687c0-.553.27-1.093 1.14-1.093h.883V3.87s-.801-.137-1.567-.137c-1.6 0-2.644.97-2.644 2.724V8H5.13v2.023h1.777v4.892a7.037 7.037 0 002.188 0v-4.892h1.63z"></path></g></svg>
							<?php esc_html_e( 'Facebook', 'woodmart' ); ?>
						</a>
					</div>
				<?php endif ?>

				<?php if ( ! empty( $goo_app_id ) && ! empty( $goo_app_secret ) ) : ?>
					<div class="social-login-btn google">
						<a href="<?php echo esc_url( add_query_arg( 'social_auth', 'google', wc_get_page_permalink('myaccount') ) ); ?>" class="login-goo-link btn">
							<svg class="social-icon" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g><path fill="#4285F4" d="M14.9 8.161c0-.476-.039-.954-.121-1.422h-6.64v2.695h3.802a3.24 3.24 0 01-1.407 2.127v1.75h2.269c1.332-1.22 2.097-3.02 2.097-5.15z"></path><path fill="#34A853" d="M8.14 15c1.898 0 3.499-.62 4.665-1.69l-2.268-1.749c-.631.427-1.446.669-2.395.669-1.836 0-3.393-1.232-3.952-2.888H1.85v1.803A7.044 7.044 0 008.14 15z"></path><path fill="#FBBC04" d="M4.187 9.342a4.17 4.17 0 010-2.68V4.859H1.849a6.97 6.97 0 000 6.286l2.338-1.803z"></path><path fill="#EA4335" d="M8.14 3.77a3.837 3.837 0 012.7 1.05l2.01-1.999a6.786 6.786 0 00-4.71-1.82 7.042 7.042 0 00-6.29 3.858L4.186 6.66c.556-1.658 2.116-2.89 3.952-2.89z"></path></g></svg>
							<?php esc_html_e( 'Google', 'woodmart' ); ?>
						</a>
					</div>
				<?php endif ?>

				<?php if ( ! empty( $vk_app_id ) && ! empty( $vk_app_secret ) ) : ?>
					<div class="social-login-btn vkontakte">
						<a href="<?php echo esc_url( add_query_arg( 'social_auth', 'vkontakte', wc_get_page_permalink('myaccount') ) ); ?>" class="login-vk-link btn">
							<svg class="social-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g><path style="fill:#436EAB;" d="M440.649,295.361c16.984,16.582,34.909,32.182,50.142,50.436 c6.729,8.112,13.099,16.482,17.973,25.896c6.906,13.382,0.651,28.108-11.348,28.907l-74.59-0.034 c-19.238,1.596-34.585-6.148-47.489-19.302c-10.327-10.519-19.891-21.714-29.821-32.588c-4.071-4.444-8.332-8.626-13.422-11.932 c-10.182-6.609-19.021-4.586-24.84,6.034c-5.926,10.802-7.271,22.762-7.853,34.8c-0.799,17.564-6.108,22.182-23.751,22.986 c-37.705,1.778-73.489-3.926-106.732-22.947c-29.308-16.768-52.034-40.441-71.816-67.24 C58.589,258.194,29.094,200.852,2.586,141.904c-5.967-13.281-1.603-20.41,13.051-20.663c24.333-0.473,48.663-0.439,73.025-0.034 c9.89,0.145,16.437,5.817,20.256,15.16c13.165,32.371,29.274,63.169,49.494,91.716c5.385,7.6,10.876,15.201,18.694,20.55 c8.65,5.923,15.236,3.96,19.305-5.676c2.582-6.11,3.713-12.691,4.295-19.234c1.928-22.513,2.182-44.988-1.199-67.422 c-2.076-14.001-9.962-23.065-23.933-25.714c-7.129-1.351-6.068-4.004-2.616-8.073c5.995-7.018,11.634-11.387,22.875-11.387h84.298 c13.271,2.619,16.218,8.581,18.035,21.934l0.072,93.637c-0.145,5.169,2.582,20.51,11.893,23.931 c7.452,2.436,12.364-3.526,16.836-8.251c20.183-21.421,34.588-46.737,47.457-72.951c5.711-11.527,10.622-23.497,15.381-35.458 c3.526-8.875,9.059-13.242,19.056-13.049l81.132,0.072c2.406,0,4.84,0.035,7.17,0.434c13.671,2.33,17.418,8.211,13.195,21.561 c-6.653,20.945-19.598,38.4-32.255,55.935c-13.53,18.721-28.001,36.802-41.418,55.634 C424.357,271.756,425.336,280.424,440.649,295.361L440.649,295.361z"></path> </g></svg>
							<?php esc_html_e( 'VKontakte', 'woodmart' ); ?>
						</a>
					</div>
				<?php endif ?>
			</div>
		<?php endif;
	}
}

new Woodmart();