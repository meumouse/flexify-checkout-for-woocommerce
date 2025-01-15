<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Show error messages
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/notices/error.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package MeuMouse.com
 * @since 3.5.0
 * @version 1.0.0
 */

if ( ! $notices ) {
	return;
}

foreach ( $notices as $notice ) : ?>
	<div class="woocommerce-error flexify-checkout-notice error" <?php echo wc_get_notice_data_attr( $notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> role="alert">
		<?php echo wc_kses_notice( $notice['notice'] ); ?>
		<button class="close-notice btn-close btn-close-white"></button>
	</div>
<?php endforeach; ?>