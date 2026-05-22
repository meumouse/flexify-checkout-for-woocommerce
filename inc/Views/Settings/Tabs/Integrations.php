<?php

/**
 * Template for display integrations options
 * 
 * @since 3.6.0
 * @version 5.5.0
 * @package MeuMouse.com
 */

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="integrations" class="nav-content">
	<?php
	/**
	 * Hook for display custom fields options
	 * 
	 * @since 3.6.0
	 */
	do_action('flexify_checkout_before_integrations_options'); ?>

	<div class="cards-group p-5 mb-5">
		<?php
		/**
		 * Render integration cards.
		 *
		 * @since 5.5.0
		 */
		do_action( 'Flexify_Checkout/Settings/Integrations/Render_Cards' ); ?>
	</div>

	<?php
	/**
	 * Hook for display custom fields options
	 * 
	 * @since 3.6.0
	 */
	do_action('flexify_checkout_after_integrations_options'); ?>
</div>
