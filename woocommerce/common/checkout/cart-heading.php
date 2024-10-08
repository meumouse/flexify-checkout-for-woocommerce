<?php

use MeuMouse\Flexify_Checkout\Init;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Flexify checkout heading
 * This template can be overridden by copying it to yourtheme/woocommerce/common/checkout/cart-heading.php.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
if ( ! empty( Init::get_setting('text_header_sidebar_right') ) ) : ?>
	<div class="flexify-checkout-header-info-container flexify-heading--order-review">
		<svg class="flexify-checkout-heading-count-icon" viewBox="0 0 24 24" fill="none"><path d="M3.86376 16.4552C3.00581 13.0234 2.57684 11.3075 3.47767 10.1538C4.3785 9 6.14721 9 9.68462 9H14.3153C17.8527 9 19.6214 9 20.5222 10.1538C21.4231 11.3075 20.9941 13.0234 20.1362 16.4552C19.5905 18.6379 19.3176 19.7292 18.5039 20.3646C17.6901 21 16.5652 21 14.3153 21H9.68462C7.43476 21 6.30983 21 5.49605 20.3646C4.68227 19.7292 4.40943 18.6379 3.86376 16.4552Z" stroke="#1C274C" stroke-width="1.5"/><path d="M19.5 9.5L18.7896 6.89465C18.5157 5.89005 18.3787 5.38775 18.0978 5.00946C17.818 4.63273 17.4378 4.34234 17.0008 4.17152C16.5619 4 16.0413 4 15 4M4.5 9.5L5.2104 6.89465C5.48432 5.89005 5.62128 5.38775 5.90221 5.00946C6.18199 4.63273 6.56216 4.34234 6.99922 4.17152C7.43808 4 7.95872 4 9 4" stroke="#1C274C" stroke-width="1.5"/><path d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4C15 4.55228 14.5523 5 14 5H10C9.44772 5 9 4.55228 9 4Z" stroke="#1C274C" stroke-width="1.5"/><path d="M8 13V17" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 13V17" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 13V17" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<h2 class="flexify-heading flexify-heading--order-review-cart-count" id="order_review_heading">
			<?php echo Init::get_setting('text_header_sidebar_right') ?>
			<span class="flexify-heading__count"><?php echo esc_html( WC()->cart->cart_contents_count ); ?></span>
		</h2>
	</div>
<?php endif;