<?php

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Views\Components;

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="styles" class="nav-content">
	<table class="form-table">
		<?php
		/**
		 * Hook for display custom design options
		 * 
		 * @since 3.6.0
		 */
		do_action('flexify_checkout_before_design_options'); ?>

		<tr>
			<th>
				<?php esc_html_e( 'Selecione um tema', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Selecione um tema que será carregado na página de finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
			</th>
		</tr>

		<?php echo Components::render_theme_options(); ?>

		<tr class="container-separator"></tr>

		<tr>
			<th>
				<?php esc_html_e( 'Tipo de marca no cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Selecione o tipo de marca que será exibida no cabeçalho da página de finalização de compra.', 'flexify-checkout-for-woocommerce' ) ?></span>
			</th>

			<td>
				<select id="checkout_header_type" name="checkout_header_type" class="form-select">
					<option value="logo" <?php echo ( Admin_Options::get_setting('checkout_header_type') === 'logo' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'Imagem (Padrão)', 'flexify-checkout-for-woocommerce' ) ?></option>
					<option value="text" <?php echo ( Admin_Options::get_setting('checkout_header_type') === 'text' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'Texto', 'flexify-checkout-for-woocommerce' ) ?></option>
				</select>
			</td>
		</tr>

		<tr class="header-styles-option-logo">
			<th>
				<?php esc_html_e( 'Imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
			</th>
			
			<td>
				<div class="input-group">
				<input type="text" name="search_image_header_checkout" class="form-control" value="<?php echo Admin_Options::get_setting('search_image_header_checkout') ?>"/>
				<button id="flexify-checkout-search-header-logo" class="input-group-button btn btn-outline-secondary"><?php esc_html_e( 'Procurar', 'flexify-checkout-for-woocommerce' ) ?></button>
				</div>
			</td>
		</tr>

		<tr class="header-styles-option-logo">
			<th>
				<?php esc_html_e( 'Link da imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Informe o link da imagem do cabeçalho.', 'flexify-checkout-for-woocommerce' ) ?></span>
			</th>
			
			<td>
				<input type="text" name="logo_header_link" class="form-control input-control-wd-20" value="<?php echo esc_attr( Admin_Options::get_setting('logo_header_link') ) ?>"/>
			</td>
		</tr>

		<tr class="header-styles-option-logo">
			<th>
				<?php esc_html_e( 'Largura da imagem de cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
			</th>

			<td>
				<div class="input-group">
					<input type="text" name="header_width_image_checkout" class="form-control input-control-wd-5 allow-numbers-be-1" value="<?php echo Admin_Options::get_setting('header_width_image_checkout') ?>"/>
					
					<select id="unit_header_width_image_checkout" class="form-select" name="unit_header_width_image_checkout">
						<option value="px" <?php echo ( Admin_Options::get_setting('unit_header_width_image_checkout') == 'px' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'px', 'flexify-checkout-for-woocommerce' ) ?></option>
						<option value="em" <?php echo ( Admin_Options::get_setting('unit_header_width_image_checkout') == 'em' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'em', 'flexify-checkout-for-woocommerce' ) ?></option>
						<option value="rem" <?php echo ( Admin_Options::get_setting('unit_header_width_image_checkout') == 'rem' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'rem', 'flexify-checkout-for-woocommerce' ) ?></option>
					</select>
				</div>
			</td>
		</tr>

		<tr class="header-styles-option-text">
			<th>
				<?php esc_html_e( 'Texto do cabeçalho', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Informe o texto que será exibido no cabeçalho da página de finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
			</th>

			<td>
				<input type="text" name="text_brand_checkout_header" class="form-control" placeholder="<?php esc_html_e( 'CHECKOUT', 'flexify-checkout-for-woocommerce' ) ?>" value="<?php echo Admin_Options::get_setting('text_brand_checkout_header') ?>"/>
			</td>
		</tr>
		
		<tr class="container-separator"></tr>

		<tr>
			<th>
				<?php esc_html_e( 'Cabeçalho personalizado', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Adicione seu cabeçalho personalizado informando o shortcode.', 'flexify-checkout-for-woocommerce' ) ?></span>
			</th>

			<td>
				<input type="text" name="shortcode_header" class="form-control input-control-wd-20" placeholder="<?php esc_html_e( '[shortcode id="100"]', 'flexify-checkout-for-woocommerce' ) ?>" value="<?php echo esc_attr( Admin_Options::get_setting('shortcode_header') ) ?>"/>
			</td>
		</tr>

		<tr>
			<th>
				<?php esc_html_e( 'Rodapé personalizado', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Adicione seu rodapé personalizado informando o shortcode.', 'flexify-checkout-for-woocommerce' ) ?></span>
			</th>

			<td>
				<input type="text" name="shortcode_footer" class="form-control input-control-wd-20" placeholder="<?php esc_html_e( '[shortcode id="101"]', 'flexify-checkout-for-woocommerce' ) ?>" value="<?php echo esc_attr( Admin_Options::get_setting('shortcode_footer') ) ?>"/>
			</td>
		</tr>
		
		<tr class="container-separator"></tr>

		<tr>
			<th>
				<?php esc_html_e( 'Cor primária', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'A cor primária define a cor dos elementos que terão ações ou informações na página de finalização de compras.' ) ?></span>
			</th>

			<td>
				<div class="color-container input-group">
					<input type="color" name="set_primary_color" class="form-control-color" value="<?php echo Admin_Options::get_setting('set_primary_color') ?>"/>
					
					<input type="text" class="get-color-selected form-control input-control-wd-10" value="<?php echo Admin_Options::get_setting('set_primary_color') ?>"/>
					
					<button class="btn btn-outline-secondary btn-icon reset-color fcw-tooltip" data-color="#141D26" data-text="<?php esc_html_e( 'Redefinir para cor padrão', 'flexify-checkout-for-woocommerce' ) ?>">
						<svg class="icon-button" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 16c1.671 0 3-1.331 3-3s-1.329-3-3-3-3 1.331-3 3 1.329 3 3 3z"></path><path d="M20.817 11.186a8.94 8.94 0 0 0-1.355-3.219 9.053 9.053 0 0 0-2.43-2.43 8.95 8.95 0 0 0-3.219-1.355 9.028 9.028 0 0 0-1.838-.18V2L8 5l3.975 3V6.002c.484-.002.968.044 1.435.14a6.961 6.961 0 0 1 2.502 1.053 7.005 7.005 0 0 1 1.892 1.892A6.967 6.967 0 0 1 19 13a7.032 7.032 0 0 1-.55 2.725 7.11 7.11 0 0 1-.644 1.188 7.2 7.2 0 0 1-.858 1.039 7.028 7.028 0 0 1-3.536 1.907 7.13 7.13 0 0 1-2.822 0 6.961 6.961 0 0 1-2.503-1.054 7.002 7.002 0 0 1-1.89-1.89A6.996 6.996 0 0 1 5 13H3a9.02 9.02 0 0 0 1.539 5.034 9.096 9.096 0 0 0 2.428 2.428A8.95 8.95 0 0 0 12 22a9.09 9.09 0 0 0 1.814-.183 9.014 9.014 0 0 0 3.218-1.355 8.886 8.886 0 0 0 1.331-1.099 9.228 9.228 0 0 0 1.1-1.332A8.952 8.952 0 0 0 21 13a9.09 9.09 0 0 0-.183-1.814z"></path></svg>
					</button>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php esc_html_e( 'Cor secundára', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'A cor secundária define a cor dos elementos que terão ações ou informações na página de finalização de compras.' ) ?></span>
			</th>

			<td>
				<div class="color-container input-group">
					<input type="color" name="set_primary_color_on_hover" class="form-control-color" value="<?php echo Admin_Options::get_setting('set_primary_color_on_hover') ?>"/>
					
					<input type="text" class="get-color-selected form-control input-control-wd-10" value="<?php echo Admin_Options::get_setting('set_primary_color_on_hover') ?>"/>
					
					<button class="btn btn-outline-secondary btn-icon reset-color fcw-tooltip" data-color="#33404d" data-text="<?php esc_html_e( 'Redefinir para cor padrão', 'flexify-checkout-for-woocommerce' ) ?>">
						<svg class="icon-button" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 16c1.671 0 3-1.331 3-3s-1.329-3-3-3-3 1.331-3 3 1.329 3 3 3z"></path><path d="M20.817 11.186a8.94 8.94 0 0 0-1.355-3.219 9.053 9.053 0 0 0-2.43-2.43 8.95 8.95 0 0 0-3.219-1.355 9.028 9.028 0 0 0-1.838-.18V2L8 5l3.975 3V6.002c.484-.002.968.044 1.435.14a6.961 6.961 0 0 1 2.502 1.053 7.005 7.005 0 0 1 1.892 1.892A6.967 6.967 0 0 1 19 13a7.032 7.032 0 0 1-.55 2.725 7.11 7.11 0 0 1-.644 1.188 7.2 7.2 0 0 1-.858 1.039 7.028 7.028 0 0 1-3.536 1.907 7.13 7.13 0 0 1-2.822 0 6.961 6.961 0 0 1-2.503-1.054 7.002 7.002 0 0 1-1.89-1.89A6.996 6.996 0 0 1 5 13H3a9.02 9.02 0 0 0 1.539 5.034 9.096 9.096 0 0 0 2.428 2.428A8.95 8.95 0 0 0 12 22a9.09 9.09 0 0 0 1.814-.183 9.014 9.014 0 0 0 3.218-1.355 8.886 8.886 0 0 0 1.331-1.099 9.228 9.228 0 0 0 1.1-1.332A8.952 8.952 0 0 0 21 13a9.09 9.09 0 0 0-.183-1.814z"></path></svg>
					</button>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php esc_html_e( 'Cor do título dos campos', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Informe a cor do título dos campos da finalização de compras.' ) ?></span>
			</th>

			<td>
				<div class="color-container input-group">
					<input type="color" name="set_placeholder_color" class="form-control-color" value="<?php echo Admin_Options::get_setting('set_placeholder_color') ?>"/>
					
					<input type="text" class="get-color-selected form-control input-control-wd-10" value="<?php echo Admin_Options::get_setting('set_placeholder_color') ?>"/>
					
					<button class="btn btn-outline-secondary btn-icon reset-color fcw-tooltip" data-color="#33404d" data-text="<?php esc_html_e( 'Redefinir para cor padrão', 'flexify-checkout-for-woocommerce' ) ?>">
						<svg class="icon-button" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 16c1.671 0 3-1.331 3-3s-1.329-3-3-3-3 1.331-3 3 1.329 3 3 3z"></path><path d="M20.817 11.186a8.94 8.94 0 0 0-1.355-3.219 9.053 9.053 0 0 0-2.43-2.43 8.95 8.95 0 0 0-3.219-1.355 9.028 9.028 0 0 0-1.838-.18V2L8 5l3.975 3V6.002c.484-.002.968.044 1.435.14a6.961 6.961 0 0 1 2.502 1.053 7.005 7.005 0 0 1 1.892 1.892A6.967 6.967 0 0 1 19 13a7.032 7.032 0 0 1-.55 2.725 7.11 7.11 0 0 1-.644 1.188 7.2 7.2 0 0 1-.858 1.039 7.028 7.028 0 0 1-3.536 1.907 7.13 7.13 0 0 1-2.822 0 6.961 6.961 0 0 1-2.503-1.054 7.002 7.002 0 0 1-1.89-1.89A6.996 6.996 0 0 1 5 13H3a9.02 9.02 0 0 0 1.539 5.034 9.096 9.096 0 0 0 2.428 2.428A8.95 8.95 0 0 0 12 22a9.09 9.09 0 0 0 1.814-.183 9.014 9.014 0 0 0 3.218-1.355 8.886 8.886 0 0 0 1.331-1.099 9.228 9.228 0 0 0 1.1-1.332A8.952 8.952 0 0 0 21 13a9.09 9.09 0 0 0-.183-1.814z"></path></svg>
					</button>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php esc_html_e( 'Raio da borda dos elementos', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Define o raio da borda dos campos, botões e elementos da finalização de compra.' ) ?></span>
			</th>

			<td>
				<div class="input-group">
					<input type="text" name="input_border_radius" class="form-control input-control-wd-5 design-parameters" value="<?php echo Admin_Options::get_setting('input_border_radius') ?>"/>
					
					<select id="unit_input_border_radius" class="form-select" name="unit_input_border_radius">
						<option value="px" <?php echo ( Admin_Options::get_setting('unit_input_border_radius') == 'px' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'px', 'flexify-checkout-for-woocommerce' ) ?></option>
						<option value="em" <?php echo ( Admin_Options::get_setting('unit_input_border_radius') == 'em' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'em', 'flexify-checkout-for-woocommerce' ) ?></option>
						<option value="rem" <?php echo ( Admin_Options::get_setting('unit_input_border_radius') == 'rem' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'rem', 'flexify-checkout-for-woocommerce' ) ?></option>
					</select>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php esc_html_e( 'Fontes do Google', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Define a família de fontes do Google que serão utilizadas na página de finalização de compra.' ) ?></span>
			</th>

			<td>
				<div class="input-group">
					<select id="set_font_family" class="form-select" name="set_font_family">
						<?php $options = get_option('flexify_checkout_settings', array());

						foreach ( $options['font_family'] as $font => $value ) : ?>
							<option value="<?php echo esc_attr( $font ) ?>" <?php echo ( Admin_Options::get_setting('set_font_family') === $font ) ? "selected=selected" : ""; ?>><?php echo esc_html( $value['font_name'] ) ?></option>
						<?php endforeach; ?>
					</select>

					<button id="set_new_font_family_trigger" class="btn btn-outline-secondary input-group-button ms-2"><?php esc_html_e( 'Adicionar nova fonte', 'flexify-checkout-for-woocommerce' ) ?></button>
				</div>
			</td>
		</tr>

		<tr>
			<th>
				<?php esc_html_e( 'Tamanho do h2', 'flexify-checkout-for-woocommerce' ) ?>
				<span class="flexify-checkout-description"><?php esc_html_e( 'Define o tamanho da fonte para tags h2 de subtítulos (Heading 2).' ) ?></span>
			</th>

			<td>
				<div class="input-group">
					<input type="text" name="h2_size" class="form-control input-control-wd-5 design-parameters" value="<?php echo Admin_Options::get_setting('h2_size') ?>"/>
					
					<select id="h2_size_unit" class="form-select" name="h2_size_unit">
						<option value="px" <?php echo ( Admin_Options::get_setting( 'h2_size_unit' ) == 'px' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'px', 'flexify-checkout-for-woocommerce' ) ?></option>
						<option value="em" <?php echo ( Admin_Options::get_setting( 'h2_size_unit' ) == 'em' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'em', 'flexify-checkout-for-woocommerce' ) ?></option>
						<option value="rem" <?php echo ( Admin_Options::get_setting( 'h2_size_unit' ) == 'rem' ) ? "selected=selected" : ""; ?>><?php esc_html_e( 'rem', 'flexify-checkout-for-woocommerce' ) ?></option>
					</select>
				</div>
			</td>
		</tr>

		<?php
		/**
		 * Hook for display custom design options
		 * 
		 * @since 3.6.0
		 */
		do_action('flexify_checkout_after_design_options'); ?>

	</table>
</div>

<!-- Add new font modal -->
<div id="set_new_font_family_container" class="popup-container">
	<div class="popup-content">
		<div class="popup-header">
			<h5 class="popup-title"><?php esc_html_e('Adicione uma nova fonte à biblioteca', 'flexify-checkout-for-woocommerce') ?></h5>
			<button id="close_new_font_family" class="btn-close" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
		</div>

		<div class="popup-body">
			<table class="form-table">
				<tr>
					<th class="w-50">
						<?php esc_html_e( 'Nome da fonte', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php esc_html_e( 'Informe o nome da fonte do Google que deseja adicionar à biblioteca de opções.', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>
					
					<td class="w-50">
						<input type="text" class="form-control " id="set_new_font_family_name" name="set_new_font_family_name" value=""/>
					</td>
				</tr>

				<tr>
					<th class="w-50">
						<?php esc_html_e( 'URL da fonte', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php esc_html_e( 'Informe o link da fonte para ser usado no formato @import na folha de estilos da finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>

					<td class="w-50">
						<input type="text" class="form-control" id="set_new_font_family_url" name="set_new_font_family_url" value=""/>
					</td>
				</tr>

				<tr>
					<td class="w-100 d-flex justify-content-end">
						<button id="add_new_font_to_lib" class="btn btn-primary d-flex align-items-center justify-content-center mt-2" disabled>
							<svg class="me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #fff;"><path d="M19 15v-3h-2v3h-3v2h3v3h2v-3h3v-2h-.937zM4 7h11v2H4zm0 4h11v2H4zm0 4h8v2H4z"></path></svg>
							<?php esc_html_e('Adicionar fonte', 'flexify-checkout-for-woocommerce') ?>
						</button>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>