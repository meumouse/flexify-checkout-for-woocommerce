<?php

use MeuMouse\Flexify_Checkout\API\License;

/**
 * Template for display integrations options
 * 
 * @since 3.6.0
 * @version 4.1.0
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

	<div class="cards-group ps-5 mb-5">
		<div class="card text-center p-0 m-4">
			<div class="card-header border-bottom w-100">
				<div class="integration-item p-4 rounded-circle">
					<svg version="1.1" id="inter_empresas_logo" xmlns="http://www.w3.org/2000/svg" x="0" y="0" viewBox="0 0 1920 1080" style="max-width: 10rem;" xml:space="preserve"><style>.inter-logo{fill:#ea7100}</style><g id="Inter_Empresas"><g id="Empresas"><path d="M795.7 865.8c-10.2-5.7-18.1-13.9-23.7-24.6-5.6-10.6-8.5-23-8.5-37 0-13.8 2.8-26.1 8.5-37 5.6-10.8 13.6-19.3 23.9-25.4 10.3-6.1 22.1-9.1 35.3-9.1 12.9 0 24.3 3 34.1 8.9s17.4 14 22.8 24.2c5.4 10.2 8.1 21.4 8.1 33.6v3.3h-119c.2 19.1 5.1 33.7 14.7 43.9 9.6 10.2 22.6 15.3 39 15.3 12.9 0 23.9-3.4 33-10.1 9.1-6.7 15.2-15.3 18.3-25.7h13.6c-2 9.3-6 17.6-11.9 24.8-5.9 7.3-13.5 13-22.6 17.2-9.2 4.2-19.3 6.3-30.4 6.3-13.3 0-25-2.9-35.2-8.6zm86-75.4c-2-13.1-7.5-23.8-16.5-32.2-9-8.4-20.3-12.6-34-12.6-14 0-26 4.1-35.9 12.1-9.9 8.1-15.8 19-17.6 32.6h104zM920.1 735.2h13.4v13.4h.8c4.4-4.9 9.6-8.8 15.8-11.7 6.2-2.9 12.7-4.4 19.6-4.4 6.4 0 12.3 1.2 17.9 3.5 5.5 2.4 10.2 6 14.1 10.9h.8c3.8-4.4 8.7-7.9 14.6-10.5 5.9-2.6 12.7-4 20.3-4 13.1 0 23.1 3.4 30 10.1 6.9 6.7 10.4 16.6 10.4 29.7v99.3h-13.6V769.6c0-9.1-2.4-15.5-7.2-19.1-4.8-3.6-12.1-5.5-21.7-5.5-13.5 0-23.3 3.7-29.5 11.2v115.4h-13.6V766.9c0-7.8-2.2-13.4-6.7-16.8-4.5-3.4-11-5-19.5-5-11.8 0-22.6 3.7-32.2 11.2v115.4h-13.6V735.2zM1115.4 862.4v52.9h-13.6V735.2h12.5v13.9h.5c6-4.9 13-8.8 20.9-11.7 7.9-2.9 15.9-4.4 23.9-4.4 14.2 0 26.4 3.1 36.6 9.4 10.2 6.3 17.9 14.8 23.1 25.5 5.2 10.7 7.8 22.9 7.8 36.6 0 12.7-2.7 24.5-8.2 35.2-5.5 10.7-13.4 19.3-23.9 25.7-10.5 6.4-22.9 9.6-37.2 9.6-16.2 0-30.3-4.2-42.4-12.6zm20.1-3.3c7.5 2.2 14.8 3.3 22.1 3.3 11.6 0 21.6-2.6 30-7.9 8.4-5.3 14.6-12.2 18.8-20.9 4.2-8.6 6.3-18.1 6.3-28.2 0-17.6-4.7-32-14.1-43.1-9.4-11.1-22.4-16.6-39.2-16.6-8.2 0-15.9 1.3-23.1 4-7.2 2.6-14.2 6.4-21.1 11.3v88.7c6.2 4.1 12.9 7.2 20.3 9.4zM1249.9 735.2h11.5v17.5h.8c2.2-6.9 5.9-11.9 11.1-15 5.2-3.1 11.9-4.6 20.1-4.6h12.6v13.6h-19.9c-7.3 0-12.8 1.8-16.6 5.3-3.8 3.5-5.7 8.8-5.7 15.7v104h-13.6V735.2zM1340.2 865.8c-10.2-5.7-18.1-13.9-23.7-24.6-5.6-10.6-8.5-23-8.5-37 0-13.8 2.8-26.1 8.5-37 5.6-10.8 13.6-19.3 23.9-25.4 10.3-6.1 22.1-9.1 35.3-9.1 12.9 0 24.3 3 34.1 8.9s17.4 14 22.8 24.2c5.4 10.2 8.1 21.4 8.1 33.6v3.3h-119c.2 19.1 5.1 33.7 14.7 43.9 9.6 10.2 22.6 15.3 39 15.3 12.9 0 23.9-3.4 33-10.1 9.1-6.7 15.2-15.3 18.3-25.7h13.6c-2 9.3-6 17.6-11.9 24.8-5.9 7.3-13.5 13-22.6 17.2-9.2 4.2-19.3 6.3-30.4 6.3-13.3 0-25-2.9-35.2-8.6zm86-75.4c-2-13.1-7.5-23.8-16.5-32.2-9-8.4-20.3-12.6-34-12.6-14 0-26 4.1-35.9 12.1-9.9 8.1-15.8 19-17.6 32.6h104zM1455.1 872.5v-11.2h21c24.6 0 36.8-7.4 36.8-22.1 0-5.6-2.1-10.6-6.4-15-4.3-4.4-10.6-9.6-19-15.8-6.9-4.9-12.2-8.9-16-12-3.7-3.1-7.3-7.3-10.6-12.7-3.4-5.4-5-11.4-5-18.1 0-10.4 4.1-18.5 12.4-24.3 8.3-5.8 19.6-8.7 34-8.7 8.6 0 15.3.4 20.2 1.1v12h-15.8c-12.6 0-21.9 1.4-28.1 4.1-6.2 2.7-9.3 8-9.3 15.8 0 4.4 1.2 8.3 3.6 11.7 2.4 3.5 4.9 6.3 7.5 8.5 2.6 2.2 8 6.2 16 12 9.1 6.4 16.4 12.6 22 18.8 5.5 6.2 8.3 13.4 8.3 21.6 0 10.7-3.8 19.5-11.3 26.2-7.5 6.7-19.4 10.1-35.6 10.1-10.2-.1-18.3-.7-24.7-2zM1556.3 863.5c-8.5-7.3-12.7-17.5-12.7-30.6 0-8.7 2.2-16.4 6.7-22.9s10.4-11.5 17.7-15c7.4-3.5 15.4-5.2 24.1-5.2 6.9 0 13.4 1 19.4 3 6 2 11 5 15 9h1.1v-21c0-23.3-10.7-34.9-32.2-34.9-10 0-18.1 2.7-24.2 8-6.1 5.4-9.1 12.9-9.1 22.5h-13.6c0-7.5 2-14.5 6-21.1 4-6.6 9.6-12 16.8-16.1 7.2-4.1 15.4-6.1 24.7-6.1 14.2 0 25.3 4.2 33.4 12.5 8.1 8.4 12.1 20.1 12.1 35.2v90.9h-12.3v-15.8h-1.1c-3.6 5.5-9.2 9.9-16.6 13.4-7.5 3.5-15.5 5.2-24 5.2-12.3-.1-22.8-3.7-31.2-11zm55.1-6.1c5.7-3.2 11.2-7.6 16.5-13.2V810c-4.4-2.5-9.6-4.5-15.8-5.9-6.2-1.4-12.6-2-19.1-2-10.6 0-19.1 2.5-25.5 7.6-6.5 5.1-9.7 12.6-9.7 22.6 0 10 2.7 17.5 8.2 22.4 5.5 4.9 13.3 7.4 23.5 7.4 8.8 0 16.2-1.6 21.9-4.7zM1660.5 872.5v-11.2h21c24.6 0 36.8-7.4 36.8-22.1 0-5.6-2.1-10.6-6.4-15-4.3-4.4-10.6-9.6-19-15.8-6.9-4.9-12.2-8.9-16-12-3.7-3.1-7.3-7.3-10.6-12.7-3.4-5.4-5-11.4-5-18.1 0-10.4 4.1-18.5 12.4-24.3 8.3-5.8 19.6-8.7 34-8.7 8.6 0 15.3.4 20.2 1.1v12h-15.8c-12.6 0-21.9 1.4-28.1 4.1-6.2 2.7-9.3 8-9.3 15.8 0 4.4 1.2 8.3 3.6 11.7 2.4 3.5 4.9 6.3 7.5 8.5 2.6 2.2 8 6.2 16 12 9.1 6.4 16.4 12.6 22 18.8 5.5 6.2 8.3 13.4 8.3 21.6 0 10.7-3.8 19.5-11.3 26.2-7.5 6.7-19.4 10.1-35.6 10.1-10.2-.1-18.4-.7-24.7-2z"/></g><g id="Principal"><path class="inter-logo" d="M1152.8 513.8h70.8V590h-58.9c-61.9 0-100-21.4-100-100V319.7h-42.1c-7.4 0-11.2-8.8-6.2-14.2L1138 167.4c5.2-5.7 14.7-2 14.7 5.7v70.4h70.2v76.2h-70.2v194.1zM900.3 238.2c-42.3 0-73.8 24.4-89.3 62.5h-3v-57.2h-83.9V590h88.1V321.5h58.9c25.6 0 35.1 5.4 35.1 32.7V590h88.1V337c.1-69.7-33.2-98.8-94-98.8zM571.7 590h88.1V243.5h-88.1V590zm1153.8-285.2h-3.6v-61.3h-87.5V590h93.5V326.3h82.2v-88.1h-11.3c-43.6 0-62.6 19-73.3 66.6zm-132.2 103V434H1329c4.7 51.5 37.1 85.7 86.9 85.7 44.6 0 76.8-23.8 83.3-53h94.1C1573.7 540 1506.4 596 1414.7 596c-107.2 0-179.8-76.2-179.8-176.2s70.2-181.6 182.2-181.6c105.4 0 176.2 72.6 176.2 169.6zm-261.7-37.5h170.6c-13.4-34.8-41.5-59.5-85.1-59.5-38.2 0-71.7 20.1-85.5 59.5zM110 575.2h181.2l1-5-178.7-37.1 3.5-19.8 178.7 38.6 1.5-6.9-168.8-73.3 7.4-20.3L305 522.7l3.5-5-150-111.4 15.3-24.3 149 111.4 4.5-5.9L210 338.6l28.7-23.3L355 457.4l4.9-4.5-67.8-172.3 41.1-17.3 63.9 168.8 5-1.5V243.5h65.8V590H110v-14.8z"/></g></g></svg>
				</div>
			</div>
			
			<div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
				<h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Flexify Checkout - Inter addon', 'flexify-checkout-for-woocommerce' ) ?></h5>
				
				<?php if ( ! License::is_valid() ) : ?>
					<span class="badge pro bg-primary rounded-pill ms-2 mb-3">
						<svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
						<?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
					</span>
				<?php endif; ?>

				<p class="card-text fs-sm mb-4"><?php esc_html_e( 'Comece a receber via Pix e Boleto com aprovação imediata e sem cobrança de taxas. Exclusivo para clientes Inter Empresas.', 'flexify-checkout-for-woocommerce' ) ?></p>
				
				<?php if ( is_plugin_active('module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php') ) : ?>
					<button id="require_inter_bank_module_trigger" class="btn btn-sm btn-outline-primary <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>"><?php esc_html_e( 'Configurar', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php elseif ( array_key_exists( 'module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php', get_plugins() ) ) : ?>
					<button class="btn btn-sm btn-primary activate-plugin <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-slug="<?php echo esc_attr('module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php'); ?>"><?php esc_html_e( 'Ativar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php else : ?>
					<button class="btn btn-sm btn-primary install-module <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-url="https://github.com/meumouse/module-inter-bank-for-flexify-checkout/raw/main/dist/module-inter-bank-for-flexify-checkout.zip" data-plugin-slug="<?php echo esc_attr('module-inter-bank-for-flexify-checkout/module-inter-bank-for-flexify-checkout.php'); ?>"><?php esc_html_e( 'Instalar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php endif; ?>
			</div>

			<?php
			/**
			 * Add module settings for Inter bank
			* 
			* @since 3.8.0
			*/
			do_action('flexify_checkout_inter_module'); ?>
		</div>

		<div class="card text-center p-0 m-4">
			<div class="card-header border-bottom w-100">
				<div class="integration-item p-4 rounded-circle">
					<svg x="0px" y="0px" viewBox="0 0 1080 1080" xml:space="preserve"><g><path fill="#141D26" d="M513.96,116.38c-234.22,0-424.07,189.86-424.07,424.07c0,234.21,189.86,424.08,424.07,424.08 c234.21,0,424.07-189.86,424.07-424.08C938.03,306.25,748.17,116.38,513.96,116.38z M685.34,542.48 c-141.76,0.37-257.11,117.68-257.41,259.44h-88.21c0-191.79,153.83-347.41,345.62-347.41V542.48z M685.34,365.84 c-141.76,0.2-266.84,69.9-346.06,176.13V410.6c91.73-82.48,212.64-133.1,346.06-133.1V365.84z"/><circle fill="#fff" cx="870.13" cy="237.99" r="120.99"/></g><g><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M808.53,271.68c-6.78-27.14-10.18-40.71-3.05-49.83c7.12-9.12,21.11-9.12,49.08-9.12h36.62 c27.97,0,41.96,0,49.08,9.12c7.12,9.12,3.73,22.69-3.05,49.83c-4.32,17.26-6.47,25.89-12.91,30.91 c-6.44,5.02-15.33,5.02-33.12,5.02h-36.62c-17.79,0-26.69,0-33.12-5.02C815,297.57,812.84,288.94,808.53,271.68z"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M932.17,216.68l-5.62-20.6c-2.17-7.94-3.25-11.92-5.47-14.91c-2.21-2.98-5.22-5.28-8.67-6.63 c-3.47-1.36-7.59-1.36-15.82-1.36 M813.56,216.68l5.62-20.6c2.17-7.94,3.25-11.92,5.47-14.91c2.21-2.98,5.22-5.28,8.67-6.63 c3.47-1.36,7.59-1.36,15.82-1.36"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-miterlimit: 133.3333;" d="M849.14,173.19c0-4.37,3.54-7.91,7.91-7.91h31.63c4.37,0,7.91,3.54,7.91,7.91c0,4.37-3.54,7.91-7.91,7.91 h-31.63C852.68,181.1,849.14,177.56,849.14,173.19z"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M841.24,244.36v31.63"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M904.5,244.36v31.63"/><path style="fill: none; stroke: #141D26; stroke-width: 15; stroke-linecap: round;  stroke-linejoin: round; stroke-miterlimit: 133.3333;" d="M872.87,244.36v31.63"/></g></svg>
				</div>
			</div>
			
			<div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
				<h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Flexify Checkout - Recuperação de carrinhos abandonados', 'flexify-checkout-for-woocommerce' ) ?></h5>
				
				<?php if ( ! License::is_valid() ) : ?>
					<span class="badge pro bg-primary rounded-pill ms-2 mb-3">
							<svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
							<?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
					</span>
				<?php endif; ?>

				<p class="card-text fs-sm mb-4"><?php esc_html_e( 'Recupere carrinhos e pedidos abandonados com follow up cadenciado. Envie notificações via WhatsApp de forma automática e muito mais!', 'flexify-checkout-for-woocommerce' ) ?></p>
				
				<?php if ( is_plugin_active('flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php') ) : ?>
					<a class="btn btn-sm btn-outline-primary <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" href="<?php echo admin_url('admin.php?page=fc-recovery-carts-settings') ?>"><?php esc_html_e( 'Configurar', 'flexify-checkout-for-woocommerce' ) ?></a>
				<?php elseif ( array_key_exists( 'flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php', get_plugins() ) ) : ?>
					<button class="btn btn-sm btn-primary activate-plugin <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-slug="<?php echo esc_attr('flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php'); ?>"><?php esc_html_e( 'Ativar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php else : ?>
					<button class="btn btn-sm btn-primary install-module <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-url="https://github.com/meumouse/flexify-checkout-recovery-carts-addon/raw/refs/heads/main/dist/flexify-checkout-recovery-carts-addon.zip" data-plugin-slug="<?php echo esc_attr('flexify-checkout-recovery-carts-addon/flexify-checkout-recovery-carts-addon.php'); ?>"><?php esc_html_e( 'Instalar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php endif; ?>
			</div>

			<?php
			/**
			 * Add module settings for Inter bank
			* 
			* @since 4.1.0
			*/
			do_action('flexify_checkout_recovery_carts_settings'); ?>
		</div>

		<div class="card text-center p-0 m-4">
			<div class="card-header border-bottom w-100">
				<div class="integration-item p-4 rounded-circle">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 703 882.5"><path d="M908.66,248V666a126.5,126.5,0,0,1-207.21,97.41l-16.7-16.7L434.08,496.07l-62-62a47.19,47.19,0,0,0-72,30.86V843.36a47.52,47.52,0,0,0,69.57,35.22l19.3-19.3,56-56,81.19-81.19,10.44-10.44a47.65,47.65,0,0,1,67.63,65.05l-13,13L428.84,952.12l-9.59,9.59a128,128,0,0,1-213.59-95.18V413.17a124.52,124.52,0,0,1,199.78-82.54l22.13,22.13L674.45,599.64l46.22,46.22,17,17a47.8,47.8,0,0,0,71-31.44V270.19a48.19,48.19,0,0,0-75-40.05L720.43,243.4l-68.09,68.09L575.7,388.13a48.39,48.39,0,0,1-67.43-67.93L680,148.46A136,136,0,0,1,908.66,248Z" transform="translate(-205.66 -112.03)" style="fill:#22c55e"/></svg>
				</div>
			</div>
			
			<div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
				<h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Joinotify', 'flexify-checkout-for-woocommerce' ) ?></h5>
				<p class="card-text fs-sm mb-4"><?php esc_html_e( 'Automatize o envio de mensagens via WhatsApp ao receber eventos no Flexify Checkout, recupere vendas de maneira mais assertiva. Deixe tudo no fluxo certo sem esforço!', 'flexify-checkout-for-woocommerce' ) ?></p>

				<?php if ( is_plugin_active('joinotify/joinotify.php') ) : ?>
					<a class="btn btn-sm btn-outline-primary <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" href="<?php echo admin_url('admin.php?page=joinotify-settings') ?>"><?php esc_html_e( 'Configurar', 'flexify-checkout-for-woocommerce' ) ?></a>
				<?php elseif ( array_key_exists( 'joinotify/joinotify.php', get_plugins() ) ) : ?>
					<button class="btn btn-sm btn-primary activate-plugin <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-slug="<?php echo esc_attr('joinotify/joinotify.php'); ?>"><?php esc_html_e( 'Ativar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php else : ?>
					<button class="btn btn-sm btn-primary install-module <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" data-plugin-url="https://github.com/meumouse/joinotify/raw/refs/heads/main/dist/joinotify.zip" data-plugin-slug="<?php echo esc_attr('joinotify/joinotify.php'); ?>"><?php esc_html_e( 'Instalar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php endif; ?>
			</div>

			<?php
			/**
			 * Add module settings for Joinotify
			* 
			* @since 3.8.0
			*/
			do_action('flexify_checkout_joinotify'); ?>
		</div>

		<div class="card text-center p-0 m-4">
			<div class="card-header border-bottom w-100">
				<div class="integration-item p-4 rounded-circle">
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-label="Google Maps" role="img" viewBox="0 0 512 512" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <rect id="a" width="512" height="512" x="0" y="0" rx="15%" fill="#ffffff"></rect> <clipPath id="b"> <use xlink:href="#a"></use> </clipPath> <g clip-path="url(#b)"> <path fill="#35a85b" d="M0 512V0h512z"></path> <path fill="#5881ca" d="M256 288L32 512h448z"></path> <path fill="#c1c0be" d="M288 256L512 32v448z"></path> <path stroke="#fadb2a" stroke-width="71" d="M0 512L512 0"></path> <path fill="none" stroke="#f2f2f2" stroke-width="22" d="M175 173h50a50 54 0 1 1-15-41"></path> <path fill="#de3738" d="M353 85a70 70 0 0 1 140 0c0 70-70 70-70 157 0-87-70-87-70-157"></path> <circle cx="423" cy="89" r="25" fill="#7d2426"></circle> </g> </g></svg>
				</div>
			</div>
			
			<div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
				<h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Google Maps', 'flexify-checkout-for-woocommerce' ) ?></h5>
				<p class="card-text fs-sm mb-4"><?php esc_html_e( 'Facilite o preenchimento do endereço de entrega aos usuários, permitindo pesquisar seu endereço ou usando recursos de geolocalização com Google Maps.', 'flexify-checkout-for-woocommerce' ) ?></p>

				<div class="badge bg-translucent-primary rounded-pill py-2 px-3 fs-md"><?php esc_html_e( 'Em breve', 'flexify-checkout-for-woocommerce' ) ?></div>
			</div>

			<?php
			/**
			 * Add module settings for Google Maps
			 * 
			 * @since 3.8.0
			 */
			do_action('flexify_checkout_google_maps'); ?>
		</div>

		<div class="card text-center p-0 m-4">
			<div class="card-header border-bottom w-100">
				<div class="integration-item p-4 rounded-circle">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 135 31" fill="none" style="max-width: 10rem;">
							<g clip-path="url(#clip0_1254_772)">
							<path d="M98.1596 24.169C98.7996 24.169 99.3446 24.4147 99.7954 24.9053C100.252 25.3959 100.48 25.9924 100.48 26.6932C100.48 27.3939 100.252 28.006 99.7954 28.4905C99.3515 28.975 98.8065 29.2173 98.1596 29.2173C97.5127 29.2173 96.9807 28.9846 96.6188 28.5192V29.0836H95.3923V22.3916H96.6188V24.868C96.9798 24.4026 97.4937 24.1699 98.1596 24.1699V24.169ZM96.9902 27.6683C97.2372 27.9166 97.5515 28.0408 97.9316 28.0408C98.3116 28.0408 98.626 27.9166 98.873 27.6683C99.12 27.4199 99.253 27.0882 99.253 26.6932C99.253 26.2981 99.126 25.9759 98.873 25.7276C98.626 25.4732 98.3116 25.3456 97.9316 25.3456C97.5515 25.3456 97.2372 25.4732 96.9902 25.7276C96.7431 25.9759 96.6196 26.2981 96.6196 26.6932C96.6196 27.0882 96.7431 27.4138 96.9902 27.6683Z" fill="#424242"/>
							<path d="M103.2 27.5154L104.255 24.3027H105.567L103.837 29.0827C103.59 29.7713 103.261 30.2697 102.852 30.5788C102.443 30.8879 101.934 31.0268 101.326 30.9947V29.8477C101.649 29.8538 101.909 29.7843 102.106 29.6376C102.302 29.4908 102.457 29.2555 102.572 28.9299L100.623 24.3027H101.964L103.2 27.5154Z" fill="#424242"/>
							<path d="M111.292 24.1689C111.932 24.1689 112.477 24.4147 112.928 24.9053C113.385 25.3958 113.613 25.9924 113.613 26.6931C113.613 27.3938 113.385 28.006 112.928 28.4905C112.484 28.975 111.939 29.2172 111.292 29.2172C110.645 29.2172 110.113 28.9845 109.751 28.5191V30.9955H108.525V24.3035H109.751V24.8679C110.112 24.4025 110.626 24.1698 111.292 24.1698V24.1689ZM110.122 27.6682C110.369 27.9165 110.684 28.0407 111.064 28.0407C111.444 28.0407 111.758 27.9165 112.005 27.6682C112.252 27.4199 112.385 27.0882 112.385 26.6931C112.385 26.298 112.258 25.9759 112.005 25.7275C111.758 25.4731 111.444 25.3455 111.064 25.3455C110.684 25.3455 110.369 25.4731 110.122 25.7275C109.875 25.9759 109.752 26.298 109.752 26.6931C109.752 27.0882 109.875 27.4138 110.122 27.6682Z" fill="#424242"/>
							<path d="M118.092 24.867V24.3026H119.318V29.0825H118.092V28.5181C117.724 28.9836 117.207 29.2163 116.541 29.2163C115.876 29.2163 115.363 28.974 114.906 28.4895C114.456 27.9989 114.23 27.3998 114.23 26.6921C114.23 25.9845 114.455 25.3949 114.906 24.9043C115.363 24.4137 115.908 24.168 116.541 24.168C117.207 24.168 117.724 24.4007 118.092 24.8661V24.867ZM115.828 27.6681C116.075 27.9164 116.389 28.0406 116.769 28.0406C117.149 28.0406 117.464 27.9164 117.711 27.6681C117.965 27.4137 118.091 27.0881 118.091 26.693C118.091 26.2979 117.964 25.9758 117.711 25.7274C117.464 25.473 117.149 25.3454 116.769 25.3454C116.389 25.3454 116.075 25.473 115.828 25.7274C115.581 25.9758 115.458 26.2979 115.458 26.693C115.458 27.0881 115.581 27.4137 115.828 27.6681Z" fill="#424242"/>
							<path d="M122.476 27.5154L123.531 24.3027H124.843L123.112 29.0827C122.865 29.7713 122.537 30.2697 122.128 30.5788C121.718 30.8879 121.21 31.0268 120.602 30.9947V29.8477C120.925 29.8538 121.185 29.7843 121.382 29.6376C121.578 29.4908 121.733 29.2555 121.848 28.9299L119.899 24.3027H121.24L122.476 27.5154Z" fill="#424242"/>
							<path d="M127.496 29.2172C126.779 29.2172 126.181 28.975 125.699 28.4905C125.223 28.006 124.986 27.4068 124.986 26.6931C124.986 25.9793 125.223 25.3802 125.699 24.8957C126.181 24.4112 126.779 24.1689 127.496 24.1689C127.959 24.1689 128.381 24.281 128.761 24.5032C129.141 24.7264 129.429 25.026 129.626 25.4019L128.571 26.0236C128.476 25.8265 128.332 25.6702 128.138 25.5547C127.945 25.4401 127.728 25.3828 127.487 25.3828C127.119 25.3828 126.815 25.5053 126.574 25.751C126.333 25.9967 126.213 26.3102 126.213 26.6931C126.213 27.076 126.333 27.3756 126.574 27.63C126.815 27.8722 127.119 27.9929 127.487 27.9929C127.734 27.9929 127.954 27.9374 128.148 27.8253C128.341 27.7142 128.485 27.5597 128.58 27.3617L129.645 27.9738C129.436 28.3559 129.14 28.6589 128.756 28.8821C128.373 29.1052 127.953 29.2164 127.496 29.2164V29.2172Z" fill="#424242"/>
							<path d="M134.268 28.4905C133.786 28.975 133.19 29.2172 132.48 29.2172C131.77 29.2172 131.174 28.975 130.692 28.4905C130.21 28.006 129.969 27.4068 129.969 26.6931C129.969 25.9793 130.21 25.3898 130.692 24.9053C131.18 24.4147 131.776 24.1689 132.48 24.1689C133.184 24.1689 133.78 24.4147 134.268 24.9053C134.756 25.3958 135 25.9924 135 26.6931C135 27.3938 134.756 27.9999 134.268 28.4905ZM131.557 27.6395C131.804 27.8879 132.112 28.012 132.48 28.012C132.848 28.012 133.155 27.8879 133.402 27.6395C133.649 27.3912 133.773 27.0751 133.773 26.6931C133.773 26.311 133.649 25.995 133.402 25.7466C133.155 25.4983 132.848 25.3741 132.48 25.3741C132.112 25.3741 131.804 25.4983 131.557 25.7466C131.316 26.0019 131.196 26.3171 131.196 26.6931C131.196 27.0691 131.316 27.3851 131.557 27.6395Z" fill="#424242"/>
							<path d="M8.83636 3.74316C10.7667 3.74316 12.4319 4.50553 13.8311 6.03113C15.2095 7.53676 15.8996 9.38797 15.8996 11.5865C15.8996 13.785 15.2095 15.6666 13.8311 17.1723C12.4517 18.6779 10.7874 19.4298 8.83636 19.4298C6.8853 19.4298 5.45763 18.8255 4.43329 17.6177V20.4996C4.43243 22.9612 2.44855 24.9566 0 24.9566V4.15908H3.04363C3.81058 4.15908 4.43243 4.78425 4.43243 5.5553C5.45676 4.3475 6.92416 3.74316 8.8355 3.74316H8.83636ZM5.40839 14.2305C6.07775 14.8843 6.92503 15.2108 7.95022 15.2108C8.97541 15.2108 9.81232 14.8843 10.4618 14.2305C11.1312 13.5766 11.4663 12.6962 11.4663 11.5865C11.4663 10.4768 11.1312 9.59637 10.4618 8.94254C9.81146 8.28871 8.97369 7.96223 7.95022 7.96223C6.92675 7.96223 6.07861 8.28871 5.40839 8.94254C4.75804 9.59637 4.43329 10.4777 4.43329 11.5865C4.43329 12.6953 4.75804 13.5775 5.40839 14.2305Z" fill="#601DFA"/>
							<path d="M29.1382 5.5553C29.1382 4.78425 29.76 4.15908 30.527 4.15908H33.5706V19.0139H29.1382V17.6177C28.1138 18.8264 26.6456 19.4298 24.7351 19.4298C22.8246 19.4298 21.1197 18.677 19.7404 17.1723C18.3611 15.6675 17.6719 13.805 17.6719 11.5865C17.6719 9.368 18.3611 7.53589 19.7404 6.03113C21.1387 4.5064 22.8039 3.74316 24.7351 3.74316C26.6663 3.74316 28.1138 4.3475 29.1382 5.5553ZM22.1052 11.5865C22.1052 12.6962 22.4299 13.5775 23.0803 14.2305C23.7306 14.8843 24.577 15.2108 25.6221 15.2108C26.6672 15.2108 27.5136 14.8843 28.1639 14.2305C28.8143 13.5766 29.139 12.6962 29.139 11.5865C29.139 10.4768 28.8143 9.59637 28.1639 8.94254C27.5136 8.28871 26.6663 7.96223 25.6221 7.96223C24.5779 7.96223 23.7306 8.28871 23.0803 8.94254C22.4299 9.59637 22.1052 10.4777 22.1052 11.5865Z" fill="#601DFA"/>
							<path d="M43.3518 13.2503L46.1294 4.15918H50.8581L45.8047 18.5686C44.9773 20.8861 43.8882 22.5541 42.5391 23.5743C41.1892 24.5946 39.4704 25.0548 37.382 24.9558V20.7967C38.4064 20.7967 39.1941 20.6134 39.7459 20.247C40.297 19.8806 40.7409 19.2415 41.076 18.3307L35.1952 4.15918H40.0716L43.3518 13.2503Z" fill="#601DFA"/>
							<path d="M69.0326 3.74316C70.7073 3.74316 72.0658 4.30756 73.1109 5.43635C74.1352 6.52606 74.6474 8.01172 74.6474 9.89246V19.0131H70.215V10.278C70.215 9.50519 70.0327 8.90694 69.6682 8.4806C69.3038 8.05514 68.7769 7.84153 68.0868 7.84153C67.3579 7.84153 66.7913 8.089 66.388 8.58393C65.9838 9.07886 65.7825 9.76221 65.7825 10.634V19.0122H61.3501V10.2771C61.3501 9.50433 61.1679 8.90607 60.8034 8.47973C60.4389 8.05427 59.9121 7.84067 59.222 7.84067C58.493 7.84067 57.9264 8.08813 57.5231 8.58306C57.1189 9.07799 56.9177 9.76134 56.9177 10.6331V19.0113H52.4852V4.15908H55.5582C56.3088 4.15908 56.9177 4.77123 56.9177 5.52578C57.7252 4.33708 59.0458 3.74316 60.8777 3.74316C62.5912 3.74316 63.8816 4.39699 64.7487 5.70378C65.6547 4.39612 67.0832 3.74316 69.0334 3.74316H69.0326Z" fill="#601DFA"/>
							<path d="M92.1423 13.3691H81.6511C82.1434 14.7558 83.3258 15.4487 85.1974 15.4487C86.3988 15.4487 87.3445 15.0727 88.0346 14.3199L91.5809 16.37C90.1428 18.4105 87.9949 19.4298 85.1386 19.4298C82.6754 19.4298 80.6863 18.6875 79.1689 17.2018C77.6911 15.7161 76.9526 13.845 76.9526 11.5865C76.9526 9.32806 77.6816 7.50637 79.1395 6.00074C80.6173 4.49598 82.5087 3.74316 84.8139 3.74316C86.961 3.74316 88.7445 4.49598 90.1627 6.00074C91.6007 7.46643 92.3202 9.32806 92.3202 11.5865C92.3202 12.2204 92.2615 12.8151 92.1431 13.3691H92.1423ZM81.563 10.0418H87.9163C87.4827 8.47713 86.4385 7.69479 84.7837 7.69479C83.1289 7.69479 81.9957 8.47713 81.563 10.0418Z" fill="#601DFA"/>
							<path d="M103.49 3.74316C105.086 3.74316 106.396 4.28846 107.421 5.3773C108.485 6.48699 109.017 7.99175 109.017 9.89332V19.0139H104.585V10.5463C104.585 9.69448 104.348 9.0311 103.875 8.55614C103.403 8.08118 102.782 7.84327 102.013 7.84327C101.146 7.84327 100.472 8.10637 99.9888 8.63082C99.506 9.15614 99.2651 9.91329 99.2651 10.904V19.0148H94.8326V4.15908H97.8763C98.6432 4.15908 99.2651 4.78425 99.2651 5.5553C100.151 4.3475 101.56 3.74316 103.491 3.74316H103.49Z" fill="#601DFA"/>
							<path d="M120.718 4.15893V8.43704H117.675V13.6955C117.675 14.2703 117.901 14.6419 118.354 14.8095C118.808 14.9779 119.595 15.0326 120.718 14.9727V19.0129C117.921 19.3099 115.97 19.0424 114.867 18.2106C113.783 17.3588 113.241 15.854 113.241 13.6946V8.43617H110.877V4.15807H113.241C113.241 2.48312 114.335 1.00614 115.93 0.524236L117.674 -0.00195312V4.1572H120.717L120.718 4.15893Z" fill="#601DFA"/>
							<path d="M130.586 19.7814C130.586 20.8138 129.753 21.65 128.727 21.65C127.701 21.65 126.868 20.8138 126.868 19.7814C126.868 19.7171 126.872 19.6529 126.878 19.5904C127.359 19.6572 127.867 19.6911 128.401 19.6911C129.164 19.6911 129.884 19.6129 130.557 19.4575C130.576 19.5626 130.586 19.6711 130.586 19.7814Z" fill="#601DFA"/>
							<path d="M130.586 3.34709C130.586 3.47473 130.573 3.59977 130.549 3.72133C129.919 3.56243 129.243 3.48342 128.52 3.48342C127.948 3.48342 127.405 3.53204 126.89 3.63016C126.875 3.53725 126.868 3.44348 126.868 3.34709C126.868 2.31469 127.7 1.47852 128.727 1.47852C129.754 1.47852 130.586 2.31469 130.586 3.34709Z" fill="#601DFA"/>
							<path d="M134.548 14.5579C134.548 16.1625 133.957 17.3808 132.775 18.2126C132.07 18.6884 131.278 19.0253 130.397 19.2216C129.776 19.3614 129.111 19.4308 128.401 19.4308C127.911 19.4308 127.445 19.4013 127.003 19.3431C124.645 19.0297 122.994 17.8809 122.047 15.8951L125.889 13.6966C126.283 14.8653 127.121 15.4497 128.401 15.4497C129.466 15.4497 129.997 15.1423 129.997 14.5284C129.997 14.0934 129.14 13.6575 127.426 13.2216C126.795 13.0428 126.254 12.8604 125.801 12.672C125.348 12.4836 124.86 12.2161 124.338 11.8697C123.815 11.5232 123.416 11.0726 123.141 10.5177C122.865 9.96376 122.727 9.32904 122.727 8.61617C122.727 7.09143 123.279 5.89318 124.382 5.02141C125.138 4.43791 126.024 4.05412 127.039 3.87091C127.505 3.78669 127.999 3.74414 128.52 3.74414C129.181 3.74414 129.799 3.8136 130.373 3.9534C132.041 4.35629 133.344 5.34615 134.282 6.92298L130.499 8.97304C130.027 8.10126 129.366 7.66538 128.52 7.66538C127.673 7.66538 127.279 7.94323 127.279 8.49721C127.279 8.75509 127.485 8.97825 127.899 9.1658C128.313 9.35422 128.963 9.56695 129.849 9.80487C133.001 10.4978 134.567 12.0824 134.548 14.5579Z" fill="#601DFA"/>
							</g><defs><clipPath id="clip0_1254_772"><rect width="135" height="31" fill="white"/></clipPath></defs>
						</svg>
				</div>
			</div>
			
			<div class="card-body px-3 py-4 d-flex flex-column align-items-center text-center">
				<h5 class="card-title pt-0 border-top-0"><?php esc_html_e( 'Payments by Payco', 'flexify-checkout-for-woocommerce' ) ?></h5>

				<p class="card-text fs-sm mb-4"><?php esc_html_e( 'Receba pagamentos com Pix, Cartão de Crédito, Open Finance e Boleto Bancário.', 'flexify-checkout-for-woocommerce' ) ?></p>
				
				<?php if ( is_plugin_active('virtuaria-payments-by-payco/virtuaria-payments-by-payco.php') ) : ?>
					<a class="btn btn-sm btn-outline-primary" href="<?php echo admin_url('admin.php?page=virtuaria_payments_payco') ?>"><?php esc_html_e( 'Configurar', 'flexify-checkout-for-woocommerce' ) ?></a>
				<?php elseif ( array_key_exists( 'virtuaria-payments-by-payco/virtuaria-payments-by-payco.php', get_plugins() ) ) : ?>
					<button class="btn btn-sm btn-primary activate-plugin" data-plugin-slug="<?php echo esc_attr('virtuaria-payments-by-payco/virtuaria-payments-by-payco.php'); ?>"><?php esc_html_e( 'Ativar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php else : ?>
					<button class="btn btn-sm btn-primary install-module" data-plugin-url="https://downloads.wordpress.org/plugin/virtuaria-payments-by-payco.zip" data-plugin-slug="<?php echo esc_attr('virtuaria-payments-by-payco/virtuaria-payments-by-payco.php'); ?>"><?php esc_html_e( 'Instalar módulo', 'flexify-checkout-for-woocommerce' ) ?></button>
				<?php endif; ?>
			</div>

			<?php
			/**
			 * Add module settings for Inter bank
			 * 
			 * @since 5.3.3
			 */
			do_action('flexify_checkout_payco'); ?>
		</div>
	</div>

	<?php
	/**
		* Hook for display custom fields options
		* 
		* @since 3.6.0
		*/
	do_action('flexify_checkout_after_integrations_options'); ?>
</div>