<?php

use MeuMouse\Flexify_Checkout\Views\Components;
use MeuMouse\Flexify_Checkout\Checkout\Coupons;
use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="fields" class="nav-content">
    <table class="form-table">
        <?php
        /**
         * Hook for display custom fields options
         * 
         * @since 3.6.0
         */
        do_action('flexify_checkout_before_fields_options'); ?>

        <tr>
            <th>
                <?php esc_html_e( 'Mostrar campo de observações adicionais', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para mostrar o campo de observações adicionais no pedido.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            
            <td>
                <div class="form-check form-switch">
                    <input type="checkbox" class="toggle-switch" id="enable_aditional_notes" name="enable_aditional_notes" value="yes" <?php checked( Admin_Options::get_setting('enable_aditional_notes') === 'yes' ); ?> />
                </div>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Ocultar campo de cupom de desconto', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Quando ativado, o campo do cupom não será exibido aos usuários e eles não poderão inserir um código de cupom. No entanto, você pode aplicar um cupom automaticamente.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>

            <td>
                <div class="form-check form-switch">
                    <input type="checkbox" class="toggle-switch" id="enable_hide_coupon_code_field" name="enable_hide_coupon_code_field" value="yes" <?php checked( Admin_Options::get_setting('enable_hide_coupon_code_field') === 'yes' ); ?> />
                </div>
            </td>
        </tr>

        <tr class="coupon-position-wrapper">
            <th>
                <?php esc_html_e( 'Posição do campo de cupom de desconto', 'flexify-checkout-for-woocommerce' );

                if ( ! License::is_valid() ) : ?>
                    <span class="badge pro bg-primary rounded-pill ms-2">
                        <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                        <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                    </span>
                <?php endif; ?>

                <span class="flexify-checkout-description"><?php esc_html_e( 'Permite alterar a posição do campo de cupom de desconto.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>

            <td>
                <select name="render_coupon_field_hook" class="form-select <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>">
					<?php foreach ( Coupons::get_coupon_field_position() as $position => $value ) :
						$selected = ( Admin_Options::get_setting('render_coupon_field_hook') === esc_attr( $position ) ) ? 'selected="selected"' : '';
						echo '<option value="'. esc_attr( $position ) .'" ' . $selected . '>' . esc_html( $value['title'] ) . '</option>';
					endforeach; ?>
				</select>
            </td>
        </tr>
        
        <tr>
            <th>
                <?php esc_html_e( 'Adicionar máscaras para campos', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para adicionar máscaras de preenchimento em campos da finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>

            <td>
                <div class="form-check form-switch">
                    <input type="checkbox" class="toggle-switch" id="enable_field_masks" name="enable_field_masks" value="yes" <?php checked( Admin_Options::get_setting('enable_field_masks') === 'yes' ); ?> />
                </div>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Otimizar para produtos digitais', 'flexify-checkout-for-woocommerce' );
                
                if ( ! License::is_valid() ) : ?>
                    <span class="badge pro bg-primary rounded-pill ms-2">
                        <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                        <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                    </span>
                <?php endif; ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para remover a etapa de entrega em produtos digitais.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>


            <td>
                <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
                    <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_optimize_for_digital_products" name="enable_optimize_for_digital_products" value="yes" <?php checked( Admin_Options::get_setting('enable_optimize_for_digital_products') === 'yes' && License::is_valid() ); ?> />
                </div>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Ocultar indicador de etapas', 'flexify-checkout-for-woocommerce' );
                
                if ( ! License::is_valid() ) : ?>
                    <span class="badge pro bg-primary rounded-pill ms-2">
                        <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                        <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                    </span>
                <?php endif; ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para remover o indicador de etapas do cabeçalho do checkout.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>


            <td>
                <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
                    <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="hide_header_stepper_buttons" name="hide_header_stepper_buttons" value="yes" <?php checked( Admin_Options::get_setting('hide_header_stepper_buttons') === 'yes' && License::is_valid() ); ?> />
                </div>
            </td>
        </tr>

        <?php if ( class_exists('Extra_Checkout_Fields_For_Brazil') ) : ?>
            <tr>
                <th>
                    <?php esc_html_e( 'Ocultar campos do mercado brasileiro se país não for Brasil', 'flexify-checkout-for-woocommerce' );
                    
                    if ( ! License::is_valid() ) : ?>
                        <span class="badge pro bg-primary rounded-pill ms-2">
                            <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                            <?php esc_html_e( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                        </span>
                    <?php endif; ?>

                    <span class="flexify-checkout-description"><?php esc_html_e( 'Ative esta opção para ocultar os campos inseridos pelo plugin Brazilian Market on WooCommerce quando o país selecionado não for Brasil.', 'flexify-checkout-for-woocommerce' ) ?></span>
                </th>

                <td>
                    <div class="form-check form-switch <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
                        <input type="checkbox" class="toggle-switch <?php echo ( ! License::is_valid() ) ? 'pro-version' : ''; ?>" id="enable_unset_wcbcf_fields_not_brazil" name="enable_unset_wcbcf_fields_not_brazil" value="yes" <?php checked( Admin_Options::get_setting('enable_unset_wcbcf_fields_not_brazil') === 'yes' && License::is_valid() ); ?> />
                    </div>
                </td>
            </tr>
        <?php endif; ?>
       
        <tr class="container-separator"></tr>

        <tr class="w-100">
            <th>
                <?php esc_html_e( 'Gerenciar os campos e etapas da finalização de compras', 'flexify-checkout-for-woocommerce' ); ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Arraste e solte o campo para reordenar ou mudar o campo de etapa.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>

            <td>
                <div class="form-check form-switch">
                    <input type="checkbox" class="toggle-switch" id="enable_manage_fields" name="enable_manage_fields" value="yes" <?php checked( Admin_Options::get_setting('enable_manage_fields') === 'yes' && License::is_valid() ); ?> />
                </div>
            </td>
        </tr>
        
        <tr class="step-checkout-fields-container align-items-start mt-4">
            <?php $fields = maybe_unserialize( get_option('flexify_checkout_step_fields', array()) );

            echo Components::render_step( '1', esc_html__( 'Etapa 1 (Contato)', 'flexify-checkout-for-woocommerce' ), $fields );
            echo Components::render_step( '2', esc_html__( 'Etapa 2 (Entrega)', 'flexify-checkout-for-woocommerce' ), $fields );
            
            /**
             * Display custom fields container
             * 
             * @since 5.2.0
             * @param array $fields
             */
            do_action( 'Flexify_Checkout/Settings/Fields_Container', $fields ); ?>
        </tr>

        <tr class="mt-4 step-checkout-fields-container">
            <td>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <button id="add_new_checkout_fields_trigger" class="btn btn-primary d-flex align-items-center">
                        <svg class="icon icon-white me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"></path></svg>
                        <?php esc_html_e('Adicionar novos campos', 'flexify-checkout-for-woocommerce' ) ?>
                    </button>

                    <button id="reset_checkout_fields_trigger" class="btn btn-outline-warning d-flex align-items-center">
                        <svg class="icon icon-lg icon-warning me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 16c1.671 0 3-1.331 3-3s-1.329-3-3-3-3 1.331-3 3 1.329 3 3 3z"></path><path d="M20.817 11.186a8.94 8.94 0 0 0-1.355-3.219 9.053 9.053 0 0 0-2.43-2.43 8.95 8.95 0 0 0-3.219-1.355 9.028 9.028 0 0 0-1.838-.18V2L8 5l3.975 3V6.002c.484-.002.968.044 1.435.14a6.961 6.961 0 0 1 2.502 1.053 7.005 7.005 0 0 1 1.892 1.892A6.967 6.967 0 0 1 19 13a7.032 7.032 0 0 1-.55 2.725 7.11 7.11 0 0 1-.644 1.188 7.2 7.2 0 0 1-.858 1.039 7.028 7.028 0 0 1-3.536 1.907 7.13 7.13 0 0 1-2.822 0 6.961 6.961 0 0 1-2.503-1.054 7.002 7.002 0 0 1-1.89-1.89A6.996 6.996 0 0 1 5 13H3a9.02 9.02 0 0 0 1.539 5.034 9.096 9.096 0 0 0 2.428 2.428A8.95 8.95 0 0 0 12 22a9.09 9.09 0 0 0 1.814-.183 9.014 9.014 0 0 0 3.218-1.355 8.886 8.886 0 0 0 1.331-1.099 9.228 9.228 0 0 0 1.1-1.332A8.952 8.952 0 0 0 21 13a9.09 9.09 0 0 0-.183-1.814z"></path></svg>
                        <?php esc_html_e('Redefinir campos para o padrão', 'flexify-checkout-for-woocommerce' ) ?>
                    </button>
                </div>

                <div id="reset_checkout_fields_container" class="popup-container">
                    <div class="popup-content">
                        <div class="popup-header border-bottom-0 justify-content-end">
                            <button id="close_reset_checkout_fields" class="btn-close" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                        </div>

                        <div class="popup-body">
                            <div class="d-flex flex-column align-items-center p-4">
                                <div class="btn-icon rounded-circle p-2 mb-3 bg-translucent-danger">
                                    <svg class="icon icon-lg icon-danger" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M11.953 2C6.465 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.493 2 11.953 2zM12 20c-4.411 0-8-3.589-8-8s3.567-8 7.953-8C16.391 4 20 7.589 20 12s-3.589 8-8 8z"></path><path d="M11 7h2v7h-2zm0 8h2v2h-2z"></path></svg>
                                </div>
                                <h5 class="popup-title text-center"><?php esc_html_e('Atenção! Você realmente deseja redefinir os campos?', 'flexify-checkout-for-woocommerce'); ?></h5>
                                <span class="title-hightlight bg-danger mt-2 mb-3"></span>
                                <span class="text-muted fs-lg p-3 text-center"><?php esc_html_e( 'Ao redefinir os campos, todas as personalizações de etapas, ordem e campos adicionados serão removidas, voltando à configuração padrão de campos da finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
                            </div>

                            <div class="my-4 p-3 d-flex justify-content-center">
                                <button id="confirm_reset_checkout_fields" class="btn btn-lg btn-outline-secondary"><?php esc_html_e('Sim, desejo redefinir', 'flexify-checkout-for-woocommerce'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="add-new-checkout-fields-container popup-container">
                    <div class="popup-content">
                        <div class="popup-header">
                            <h5 class="popup-title"><?php echo esc_html__('Adicionar novo campo para finalização de compras', 'flexify-checkout-for-woocommerce') ?></h5>
                            <button class="add-new-checkout-fields-close btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                        </div>

                        <div class="popup-body">
                            <input type="hidden" id="field_source" value="added"/>

                            <?php echo Components::add_new_fields_form(); ?>
                        </div>

                        <div class="popup-footer">
                            <div class="w-100 d-flex justify-content-end">
                                <button id="fcw_add_new_field" class="btn btn-primary" ><?php echo esc_html__( 'Adicionar campo', 'flexify-checkout-for-woocommerce' ) ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>

        <?php
        /**
         * Hook for display custom fields options
         * 
         * @since 3.6.0
         */
        do_action('flexify_checkout_after_fields_options'); ?>

    </table>
</div>