<?php

namespace MeuMouse\Flexify_Checkout\Views;

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;
use MeuMouse\Flexify_Checkout\Validations\ISO3166;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Render components
 *
 * @since 5.0.0
 * @package MeuMouse.com
 */
class Components {

	/**
     * Render each checkout field for panel settings
     * 
     * @since 3.8.0
	 * @version 5.0.0
     * @param string $index | Field ID
     * @param array $value | Field values (ID, type, label, class, etc)
     * @param string $step | Step for render field (1 or 2)
     * @return void
     */
    public static function render_field( $index, $value, $step ) {
        // Checks if the field belongs to the correct step
        if ( isset( $value['step'] ) && $value['step'] !== $step ) {
            return;
        }

        $current_field_step_position = $value['position'] ?? 'full';
        $current_field_step_label = $value['label'] ?? '';
        $current_field_step_classes = $value['classes'] ?? '';
        $current_field_step_label_classes = $value['label_classes'] ?? '';
        $current_field_step_country = $value['country'] ?? 'none';
        $current_field_step_input_mask = $value['input_mask'] ?? ''; ?>

        <div id="<?php echo esc_attr( $index ); ?>" class="field-item d-flex align-items-center justify-content-between <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; echo $value['enabled'] === 'no' ? 'inactive' : ''; ?>">
            <input type="hidden" class="change-priority" name="checkout_step[<?php echo $index; ?>][priority]" value="<?php echo esc_attr( $value['priority'] ?? '' ); ?>">
            <input type="hidden" class="change-step" name="checkout_step[<?php echo $index; ?>][step]" value="<?php echo esc_attr( $value['step'] ?? '' ); ?>">

            <span class="field-name"><?php echo esc_html( $value['label'] ); ?></span>

            <div class="d-flex justify-content-end">
                <button class="btn btn-sm btn-outline-primary ms-auto rounded-3 <?php echo ( ! License::is_valid() ) ? 'require-pro' : 'flexify-checkout-step-trigger'; ?>" data-trigger="<?php echo esc_html($index); ?>">
                    <?php echo esc_html__('Editar', 'flexify-checkout-for-woocommerce'); ?>
                </button>

                <?php if ( isset( $value['source'] ) && $value['source'] !== 'native' ) : ?>
                    <button class="btn btn-outline-danger btn-icon ms-3 rounded-3 exclude-field" data-exclude="<?php echo esc_html( $index ); ?>">
                        <svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                    </button>
                <?php endif; ?>
            </div>

            <div class="flexify-checkout-step-container popup-container">
                <div class="popup-content popup-lg">
                    <div class="popup-header">
                        <h5 class="popup-title"><?php echo sprintf( __('Configurar campo <strong class="field-name">%s</strong>', 'flexify-checkout-for-woocommerce'), esc_html( $value['label'] ) ); ?></h5>
                        <button class="flexify-checkout-step-close-popup btn-close fs-lg" aria-label="<?php esc_html__('Fechar', 'flexify-checkout-for-woocommerce'); ?>"></button>
                    </div>
                    <div class="popup-body">
                        <table class="form-table">
                            <?php self::render_field_options( $index, $value ); ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * Render checkout field options for settings panel
     * 
     * @since 3.8.0
     * @version 5.0.0
     * @param string $field_id | Field ID
     * @param array $value | Field configuration array
     * @return void
     */
    public static function render_field_options( $field_id, $value ) {
        $field_type = $value['type'] ?? 'text'; ?>

        <tr>
            <th class="w-50"><?php echo esc_html__('Ativar/Desativar este campo', 'flexify-checkout-for-woocommerce'); ?>
                <span class="flexify-checkout-description"><?php echo esc_html__('Este é um campo nativo do WooCommerce e não pode ser removido, apenas desativado.', 'flexify-checkout-for-woocommerce'); ?></span>
            </th>

            <td class="w-50">
                <div class="form-check form-switch">
                    <input type="checkbox" class="toggle-switch toggle-active-field" name="checkout_step[<?php echo esc_attr( $field_id ); ?>][enabled]" value="yes" <?php checked( $value['enabled'] === 'yes' ); ?> />
                </div>
            </td>
        </tr>

        <tr>
            <th class="w-50"><?php echo esc_html__('Obrigatoriedade do campo', 'flexify-checkout-for-woocommerce'); ?>
                <span class="flexify-checkout-description"><?php echo esc_html__('Ao desativar, este campo se tornará não obrigatório.', 'flexify-checkout-for-woocommerce'); ?></span>
            </th>

            <td class="w-50">
                <div class="form-check form-switch">
                    <input type="checkbox" class="toggle-switch toggle-active-field" name="checkout_step[<?php echo esc_attr( $field_id ); ?>][required]" value="yes" <?php checked( $value['required'] === 'yes' ); ?> />
                </div>
            </td>
        </tr>

        <?php if ( $field_id === 'billing_country' || $field_id === 'shipping_country' ) : ?>
            <tr>
                <th class="w-50">
                    <?php echo esc_html__('Definir país padrão', 'flexify-checkout-for-woocommerce'); ?>
                    <span class="flexify-checkout-description"><?php echo esc_html__('Define a posição deste campo na finalização de compras.', 'flexify-checkout-for-woocommerce'); ?></span>
                </th>

                <td class="w-50">
                    <select class="form-select" name="checkout_step[<?php echo esc_attr( $field_id ); ?>][country]">
                        <?php foreach ( ISO3166::country_codes() as $code_index => $code_value ) : ?>
                            <option value="<?php echo esc_attr( $code_index ); ?>" <?php echo isset( $value['country'] ) && $value['country'] === esc_attr( $code_index ) ? "selected=selected" : ""; ?>><?php echo esc_html( $code_value ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <th class="w-50">
                <?php echo esc_html__( 'Nome do campo', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__('Define o título que será exibido para este campo.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>

            <td class="w-50">
                <input type="text" class="get-name-field form-control" name="checkout_step[<?php echo $field_id; ?>][label]" value="<?php echo esc_attr( $value['label'] ?? '' ); ?>"/>
            </td>
        </tr>

        <?php if ( $field_id !== 'billing_country' && $field_type === 'select' && isset( $value['options'] ) && is_array( $value['options'] ) ) : ?>
            <tr class="d-flex align-items-start">
                <th class="w-50">
                    <?php echo esc_html__('Opções ', 'flexify-checkout-for-woocommerce'); ?>
                    <span class="flexify-checkout-description"><?php echo esc_html__('Define a posição deste campo na finalização de compras.', 'flexify-checkout-for-woocommerce'); ?></span>
                </th>

                <td class="w-50">
                    <div class="d-grid">
                        <div class="mb-3 options-container-live">
                            <?php foreach ( $value['options'] as $option ) :
                                if ( is_array( $option ) ) : ?>
                                    <div class="d-flex align-items-center mb-3 option-container-live" data-option="<?php echo esc_attr( $option['value'] ) ?>">
										<div class="input-group me-3">
											<span class="input-group-text d-flex align-items-center justify-content-center py-2 w-25"><?php echo esc_attr( $option['value'] ) ?></span>
											<span class="input-group-text d-flex align-items-center justify-content-center w-75"><?php echo esc_html( $option['text'] ) ?></span>
										</div>

										<button class="btn btn-outline-danger btn-icon rounded-3 exclude-option-select-live" data-field-id="<?php echo esc_attr( $value['id'] ) ?>" data-option="<?php echo esc_attr( $option['value'] ) ?>">
											<svg class="icon icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
										</button>
                                    </div>
                                <?php endif;
                            endforeach; ?>
                        </div>

                        <button id="add_new_select_option_live" class="btn btn-outline-secondary"><?php echo esc_html__('Adicionar nova opção', 'flexify-checkout-for-woocommerce'); ?></button>
                    </div>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <th class="w-50">
                <?php echo esc_html__('Posição do campo', 'flexify-checkout-for-woocommerce'); ?>
                <span class="flexify-checkout-description"><?php echo esc_html__('Define a posição deste campo na finalização de compras.', 'flexify-checkout-for-woocommerce'); ?></span>
            </th>

            <td class="w-50">
                <select class="form-select" name="checkout_step[<?php echo esc_attr( $field_id ); ?>][position]">
                    <option value="left" <?php echo $value['position'] === 'left' ? "selected=selected" : ""; ?>><?php echo esc_html__('Esquerda', 'flexify-checkout-for-woocommerce'); ?></option>
                    <option value="right" <?php echo $value['position'] === 'right' ? "selected=selected" : ""; ?>><?php echo esc_html__('Direita', 'flexify-checkout-for-woocommerce'); ?></option>
                    <option value="full" <?php echo $value['position'] === 'full' ? "selected=selected" : ""; ?>><?php echo esc_html__('Largura completa', 'flexify-checkout-for-woocommerce'); ?></option>
                </select>
            </td>
        </tr>

        <tr>
            <th class="w-50">
                <?php echo esc_html__('Classe CSS personalizada do campo (Opcional)', 'flexify-checkout-for-woocommerce'); ?>
                <span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para este campo. (Opcional)', 'flexify-checkout-for-woocommerce'); ?></span>
            </th>

            <td class="w-50">
                <input type="text" class="form-control" name="checkout_step[<?php echo esc_attr( $field_id ); ?>][classes]" value="<?php echo esc_attr( $value['classes'] ?? '' ); ?>"/>
            </td>
        </tr>

        <tr>
            <th class="w-50">
                <?php echo esc_html__('Classe CSS personalizada do título (Opcional)', 'flexify-checkout-for-woocommerce'); ?>
                <span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para o título (label) deste campo. (Opcional)', 'flexify-checkout-for-woocommerce'); ?></span>
            </th>

            <td class="w-50">
                <input type="text" class="form-control" name="checkout_step[<?php echo esc_attr( $field_id ); ?>][label_classes]" value="<?php echo esc_attr( $value['label_classes'] ?? '' ); ?>"/>
            </td>
        </tr>

        <?php if ( isset( $value['input_mask'] ) ) : ?>
            <tr class="require-input-mask">
                <th class="w-50">
                    <?php echo esc_html__('Máscara do campo (Opcional)', 'flexify-checkout-for-woocommerce'); ?>
                    <span class="flexify-checkout-description"><?php echo esc_html__('Adicione uma máscara de preenchimento para este campo, seguindo o padrão informado pela documentação. (Opcional)', 'flexify-checkout-for-woocommerce'); ?></span>
                </th>

                <td class="w-50">
                    <input type="text" class="form-control" name="checkout_step[<?php echo esc_attr( $field_id ); ?>][input_mask]" value="<?php echo esc_attr( $value['input_mask'] ?? '' ); ?>"/>
                </td>
            </tr>
        <?php endif;
    }


    /**
     * Render step container for settings panel
     * 
     * @since 3.8.0
     * @version 5.0.0
     * @param string $step | Steps - 1 or 2
     * @param string $title | Title from step
     * @param array $fields | Array with fields
	 * @return string
     */
    public static function render_step( $step, $title, $fields ) {
        ob_start(); ?>

        <td class="step-container">
            <span class="step-title"><?php echo $title; ?></span>

            <div id="flexify_checkout_step_<?php echo esc_attr( $step ); ?>" data-step="<?php echo esc_attr( $step ); ?>">
                <?php foreach ( $fields as $index => $value ) :
                    if ( strpos( $index, 'billing_') !== false ) {
                        self::render_field( $index, $value, $step );
                    }
                endforeach; ?>
            </div>
        </td>

        <?php return ob_get_clean();
    }


    /**
	 * Add processing purchase animation
	 * 
	 * @since 3.9.4
     * @version 5.0.0
	 * @return void
	 */
	public static function add_processing_purchase_animation() {
		if ( Admin_Options::get_setting('enable_animation_process_purchase') === 'yes' && License::is_valid() ) : ?>
			<div id="flexify_checkout_purchase_animation" class="purchase-animations-group">
				<div class="animations-content">
					<div class="animations-group">
						<div class="purchase-animation-item animation-1">
							<lord-icon class="animation-item" src="<?php echo esc_url( Admin_Options::get_setting('animation_process_purchase_file_1') ) ?>" trigger="loop" target=".purchase-animation-item" delay="1000" stroke="regular" state="hover" colors="primary:#212529, secondary:#212529"></lord-icon>
							<h5 class="text-animation-item"><?php echo esc_html( Admin_Options::get_setting('text_animation_process_purchase_1') ) ?></h5>
						</div>

						<div class="purchase-animation-item animation-2">
							<lord-icon class="animation-item" src="<?php echo esc_url( Admin_Options::get_setting('animation_process_purchase_file_2') ) ?>" trigger="loop" target=".purchase-animation-item" delay="1000" stroke="regular" state="hover" colors="primary:#212529, secondary:#212529"></lord-icon>
							<h5 class="text-animation-item"><?php echo esc_html( Admin_Options::get_setting('text_animation_process_purchase_2') ) ?></h5>
						</div>

						<div class="purchase-animation-item animation-3">
							<lord-icon class="animation-item" src="<?php echo esc_url( Admin_Options::get_setting('animation_process_purchase_file_3') ) ?>" trigger="loop" target=".purchase-animation-item" delay="1000" stroke="regular" state="hover" colors="primary:#212529, secondary:#212529"></lord-icon>
							<h5 class="text-animation-item"><?php echo esc_html( Admin_Options::get_setting('text_animation_process_purchase_3') ) ?></h5>
						</div>
					</div>

					<div class="animation-progress-content">
						<div class="animation-progress-container">
							<div class="progress-bar animation-progress-bar"></div>
							<div class="progress-bar animation-progress-base"></div>
						</div>
						<span class="description-progress-bar"><?php esc_html_e( 'Aguarde alguns instantes', 'flexify-checkout-for-woocommerce' ) ?></span>
					</div>
				</div>
			</div>
		<?php endif;
	}


	/**
	 * Render new fields table form
	 * 
	 * @since 3.0.0
	 * @version 5.0.0
	 * @return string
	 */
	public static function add_new_fields_form() {
		ob_start(); ?>

		<table id="add_new_fields_form" class="form-table">
			<tbody>
				<tr id="set_field_id">
					<th class="w-50">
						<?php echo esc_html__( 'Nome e ID do campo *', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__( 'Informe o nome que será usado no campo em letras minúsculas, usando underline no lugar dos espaços e após o prefixo "billing_".', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>

					<td class="w-50">
						<div class="input-group">
							<span class="w-fit input-group-text"><?php echo esc_html__( 'billing_', 'flexify-checkout-for-woocommerce' ) ?></span>
							<input type="text" class="form-control" id="checkout_field_name" name="checkout_field_name" value=""/>
						</div>

						<input type="hidden" id="checkout_field_name_concat" value=""/>
						
						<div id="check_field_availability" class="d-none bg-translucent-danger text-danger px-3 py-2 rounded-pill mt-2 w-fit" data-avalability="true"><?php echo esc_html__( 'Este nome e ID do campo já está em uso. Use um outro nome.', 'flexify-checkout-for-woocommerce' ) ?></div>
					</td>
				</tr>
				
				<tr>
					<th class="w-50">
						<?php echo esc_html__( 'Tipo do campo *', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__( 'Selecione o tipo do campo que será incluído na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>

					<td class="w-50">
						<select id="checkout_field_type" class="form-select">
							<option value="text"><?php echo esc_html__( 'Texto', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="textarea"><?php echo esc_html__( 'Área de texto', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="number"><?php echo esc_html__( 'Número', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="password"><?php echo esc_html__( 'Senha', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="phone"><?php echo esc_html__( 'Telefone', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="url"><?php echo esc_html__( 'URL', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="select"><?php echo esc_html__( 'Seletor/Lista suspensa', 'flexify-checkout-for-woocommerce' ) ?></option>
						</select>
					</td>
				</tr>

				<tr class="container-separator require-add-new-field-select d-none"></tr>

				<tr class="require-add-new-field-select d-none">
					<td class="w-100 d-flex flex-column align-items-start">
						<select id="preview_select_new_field" class="form-select"></select>
						
						<div id="preview_options_container" class="my-3"></div>

						<div class="d-flex align-items-center mt-4">
							<div class="input-group me-2">
								<span class="input-group-text w-fit"><?php echo esc_html__( 'Valor da opção', 'flexify-checkout-for-woocommerce' ) ?></span>
								<input type="text" id="add_new_field_select_option_value" class="form-control input-control-wd-12" value="" placeholder="<?php echo esc_html__( 'BR', 'flexify-checkout-for-woocommerce' ) ?>"/>
							</div>
							
							<div class="input-group me-3">
								<span class="input-group-text w-fit"><?php echo esc_html__( 'Título da opção', 'flexify-checkout-for-woocommerce' ) ?></span>
								<input type="text" id="add_new_field_select_option_title" class="form-control input-control-wd-12" value="" placeholder="<?php echo esc_html__( 'Brasil', 'flexify-checkout-for-woocommerce' ) ?>"/>
							</div>

							<div class="w-25">
								<button id="add_new_options_to_select" class="btn btn-icon btn-icon-lg btn-outline-secondary">
									<svg class="icon icon-dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"></path></svg>
								</button>
							</div>
						</div>
					</td>
				</tr>

				<tr class="container-separator require-add-new-field-select d-none"></tr>

				<tr class="container-separator require-add-new-field-multicheckbox d-none"></tr>

				<tr class="require-add-new-field-multicheckbox d-none">
					<td class="w-100 d-flex flex-column align-items-start">
						<div id="preview_multicheckbox_container" class="my-3"></div>

						<div class="d-flex align-items-center mt-4">
							<div class="input-group me-2">
								<span class="input-group-text w-fit"><?php echo esc_html__( 'ID da opção', 'flexify-checkout-for-woocommerce' ) ?></span>
								<input type="text" id="add_new_field_multicheckbox_option_id" class="form-control input-control-wd-12" value="" placeholder="<?php echo esc_html__( 'verify_gdpr', 'flexify-checkout-for-woocommerce' ) ?>"/>
							</div>

							<div class="input-group me-3">
								<span class="input-group-text w-fit"><?php echo esc_html__( 'Título da opção', 'flexify-checkout-for-woocommerce' ) ?></span>
								<input type="text" id="add_new_field_multicheckbox_option_title" class="form-control input-control-wd-12" value="" placeholder="<?php echo esc_html__( 'GDPR', 'flexify-checkout-for-woocommerce' ) ?>"/>
							</div>

							<div class="w-25">
								<button id="add_new_options_to_multicheckbox" class="btn btn-icon btn-icon-lg btn-outline-secondary">
									<svg class="icon icon-dark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"></path></svg>
								</button>
							</div>
						</div>
					</td>
				</tr>

				<tr class="container-separator require-add-new-field-multicheckbox d-none"></tr>
				
				<tr>
					<th class="w-50">
						<?php echo esc_html__( 'Título do campo *', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__( 'Informe o título do campo.', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>
					<td class="w-50">
						<input type="text" class="form-control" id="checkout_field_title" name="checkout_field_title" value=""/>
					</td>
				</tr>

				<tr>
					<th class="w-50">
						<?php echo esc_html__( 'Obrigatoriedade do campo', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__('Ao desativar, este campo se tornará não obrigatório.', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>
					<td class="w-50">
						<div class="form-check form-switch">
							<input type="checkbox" class="toggle-switch toggle-active-field" id="required_field" value="no"/>
						</div>
					</td>
				</tr>

				<tr>
					<th class="w-50">
						<?php echo esc_html__( 'Posição do campo', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__('Define a posição deste campo na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>
					<td class="w-50">
						<select class="form-select" id="field_position">
							<option value="left"><?php echo esc_html__( 'Esquerda', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="right"><?php echo esc_html__( 'Direita', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="full"><?php echo esc_html__( 'Largura completa', 'flexify-checkout-for-woocommerce' ) ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th class="w-50">
						<?php echo esc_html__( 'Classe CSS personalizada do campo (Opcional)', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para este campo. (Opcional)', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>
					<td class="w-50">
						<input type="text" class="form-control" id="field_classes" value=""/>
					</td>
				</tr>

				<tr>
					<th class="w-50">
						<?php echo esc_html__( 'Classe CSS personalizada do título (Opcional)', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para o título (label) deste campo. (Opcional)', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>
					<td class="w-50">
						<input type="text" class="form-control" id="field_label_classes" value=""/>
					</td>
				</tr>

				<tr class="require-input-mask">
					<th class="w-50">
						<?php echo esc_html__( 'Máscara do campo (Opcional)', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__('Adicione uma máscara de preenchimento para este campo, seguindo o padrão informado pela documentação. (Opcional)', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>
					<td class="w-50">
						<input type="text" class="form-control" id="field_input_mask" value=""/>
					</td>
				</tr>

				<tr>
					<th class="w-50">
						<?php echo esc_html__( 'Etapa do campo', 'flexify-checkout-for-woocommerce' ) ?>
						<span class="flexify-checkout-description"><?php echo esc_html__('Define em qual etapa da finalização de compras o campo será exibido.', 'flexify-checkout-for-woocommerce' ) ?></span>
					</th>
					<td class="w-50">
						<select class="form-select" id="field_step">
							<option value="1"><?php echo esc_html__( 'Etapa 1 (Contato)', 'flexify-checkout-for-woocommerce' ) ?></option>
							<option value="2"><?php echo esc_html__( 'Etapa 2 (Entrega)', 'flexify-checkout-for-woocommerce' ) ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<?php return ob_get_clean();
	}


	/**
	 * Render notice message
	 *
	 * @since 5.0.0
	 * @param string $message | Notice message
	 * @param string $type | Type of notice (success, error, etc.)
	 * @return string
	 */
	public static function render_notice( $message, $type = 'success' ) {
		$notice = array(
			'notice' => $message,
			'type' => $type,
		);

		$attrs = wc_get_notice_data_attr( $notice );

		// start buffer
		ob_start(); ?>

		<div class="woocommerce-message flexify-checkout-notice <?php echo esc_attr( $type ) ?>" <?php echo $attrs; ?> role="alert">
			<?php echo wc_kses_notice( $message ); ?>
			<button class="close-notice btn-close btn-close-white"></button>
		</div>

		<?php return ob_get_clean();
	}
}