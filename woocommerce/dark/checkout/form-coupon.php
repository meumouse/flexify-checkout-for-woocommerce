<?php
/**
 * Checkout coupon form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-coupon.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.4
 */

defined('ABSPATH') || exit;

if ( ! wc_coupons_enabled() ) { // @codingStandardsIgnoreLine.
	return;
}

?>
<div class="checkout_coupon woocommerce-form-coupon">
	<div class="woocommerce-form-coupon__inner">
		<p class="form-row form-row-first">
			<label for="coupon_code" class=""><?php esc_html_e( 'Cupom de desconto', 'flexify-checkout-for-woocommerce' ); ?></label>
			<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" />
		</p>
		<p class="form-row form-row-last">
			<button type="submit" class="button flexify-coupon-button flexify-coupon-button--disabled" name="apply_coupon" value="<?php esc_attr_e( 'Aplicar cupom', 'woocommerce' ); ?>"><?php esc_html_e( 'Aplicar', 'flexify-checkout-for-woocommerce' ); ?></button>
		</p>
		<div class="clear"></div>
	</div>
</div>