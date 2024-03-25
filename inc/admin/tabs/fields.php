<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; ?>

<div id="fields" class="nav-content">
    <table class="form-table">
        <tr>
            <th>
                <?php echo esc_html__( 'Mostrar campo de observações adicionais', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para mostrar o campo de observações adicionais no pedido.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
                <div class="form-check form-switch">
                <input type="checkbox" class="toggle-switch" id="enable_aditional_notes" name="enable_aditional_notes" value="yes" <?php checked( self::get_setting('enable_aditional_notes') === 'yes' ); ?> />
                </div>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Ocultar campo de cupom de desconto', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Quando ativado, o campo do cupom não será exibido aos usuários e eles não poderão inserir um código de cupom. No entanto, você pode aplicar um cupom automaticamente.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
                <div class="form-check form-switch">
                <input type="checkbox" class="toggle-switch" id="enable_hide_coupon_code_field" name="enable_hide_coupon_code_field" value="yes" <?php checked( self::get_setting('enable_hide_coupon_code_field') === 'yes' ); ?> />
                </div>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Otimizar para produtos digitais', 'flexify-checkout-for-woocommerce' );
                
                if ( ! self::license_valid() ) {
                    ?>
                    <span class="badge pro bg-primary rounded-pill ms-2">
                       <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                       <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                    </span>
                    <?php
                }
                ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para remover a etapa de entrega em produtos digitais.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
                <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
                <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_optimize_for_digital_products" name="enable_optimize_for_digital_products" value="yes" <?php checked( self::get_setting('enable_optimize_for_digital_products') === 'yes' && self::license_valid() ); ?> />
                </div>
            </td>
        </tr>

        <?php

        if ( class_exists('Extra_Checkout_Fields_For_Brazil') ) {
            ?>
            <tr>
                <th>
                    <?php echo esc_html__( 'Ocultar campos do mercado brasileiro se país não for Brasil', 'flexify-checkout-for-woocommerce' );
                    
                    if ( ! self::license_valid() ) {
                        ?>
                        <span class="badge pro bg-primary rounded-pill ms-2">
                        <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                        <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                        </span>
                        <?php
                    }
                    ?>
                    <span class="flexify-checkout-description"><?php echo esc_html__( 'Ative esta opção para ocultar os campos inseridos pelo plugin Brazilian Market on WooCommerce quando o país selecionado não for Brasil.', 'flexify-checkout-for-woocommerce' ) ?></span>
                </th>
                <td>
                    <div class="form-check form-switch <?php echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
                    <input type="checkbox" class="toggle-switch <?php echo ( ! self::license_valid() ) ? 'pro-version' : ''; ?>" id="enable_unset_wcbcf_fields_not_brazil" name="enable_unset_wcbcf_fields_not_brazil" value="yes" <?php checked( self::get_setting('enable_unset_wcbcf_fields_not_brazil') === 'yes' && self::license_valid() ); ?> />
                    </div>
                </td>
            </tr>
            <?php
        }

        ?>
       
        <tr class="container-separator"></tr>

        <tr class="w-100">
            <th class="w-100">
                <?php echo esc_html__( 'Gerencie os campos e etapas da finalização de compras', 'flexify-checkout-for-woocommerce' );
                
                if ( ! self::license_valid() ) {
                    ?>
                    <span class="badge pro bg-primary rounded-pill ms-2">
                       <svg class="icon-pro" viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.336"></g><g><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 3C12.3334 3 12.6449 3.16613 12.8306 3.443L16.6106 9.07917L21.2523 3.85213C21.5515 3.51525 22.039 3.42002 22.4429 3.61953C22.8469 3.81904 23.0675 4.26404 22.9818 4.70634L20.2956 18.5706C20.0223 19.9812 18.7872 21 17.3504 21H6.64977C5.21293 21 3.97784 19.9812 3.70454 18.5706L1.01833 4.70634C0.932635 4.26404 1.15329 3.81904 1.55723 3.61953C1.96117 3.42002 2.44865 3.51525 2.74781 3.85213L7.38953 9.07917L11.1696 3.443C11.3553 3.16613 11.6667 3 12.0001 3ZM12.0001 5.79533L8.33059 11.2667C8.1582 11.5237 7.8765 11.6865 7.56772 11.7074C7.25893 11.7283 6.95785 11.6051 6.75234 11.3737L3.67615 7.90958L5.66802 18.1902C5.75913 18.6604 6.17082 19 6.64977 19H17.3504C17.8293 19 18.241 18.6604 18.3321 18.1902L20.324 7.90958L17.2478 11.3737C17.0423 11.6051 16.7412 11.7283 16.4324 11.7074C16.1236 11.6865 15.842 11.5237 15.6696 11.2667L12.0001 5.79533Z"></path> </g></svg>
                       <?php echo esc_html__( 'Pro', 'flexify-checkout-for-woocommerce' ) ?>
                    </span>
                    <?php
                }
                ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Arraste e solte o campo para reordenar ou mudar o campo de etapa.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
        </tr>
        <tr class="step-checkout-fields-container align-items-start mt-4">
            <?php
            
            $fields = get_option('flexify_checkout_step_fields', array());
            $fields = maybe_unserialize( $fields ); ?>

            <td class="step-container">
                <span class="step-title"><?php echo esc_html__( 'Etapa 1 (Contato)', 'flexify-checkout-for-woocommerce' ) ?></span>
                <div id="contact_step" data-step="1">
                    <?php
        
                    foreach ( $fields as $index => $value ) {
                        $current_field_step_position = isset( $fields[$index]['position'] ) ? $fields[$index]['position'] : 'full';
                        $current_field_step_label = isset( $fields[$index]['label'] ) ? $fields[$index]['label'] : '';
                        $current_field_step_classes = isset( $fields[$index]['classes'] ) ? $fields[$index]['classes'] : '';
                        $current_field_step_label_classes = isset( $fields[$index]['label_classes'] ) ? $fields[$index]['label_classes'] : '';
                        
                        // skip field if is different of step 1
                        if ( $value['step'] !== '1' ) {
                            continue;
                        }
                        ?>

                        <div id="<?php echo esc_attr( $index ); ?>" class="field-item <?php echo isset( $value['enabled'] ) && $value['enabled'] !== 'yes' ? 'inactive' : ''; echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
                            <input type="hidden" class="change-priority" name="checkout_step[<?php echo $index; ?>][priority]" value="<?php echo esc_html( $value['priority'] ) ?>">
                            <input type="hidden" class="change-step" name="checkout_step[<?php echo $index; ?>][step]" value="<?php echo esc_attr( $value['step'] ) ?>">

                            <span class="field-name"><?php echo esc_html( $value['label'] ) ?></span>
                            <button class="btn btn-sm btn-outline-primary ms-auto rounded-3 <?php echo ( ! self::license_valid() ) ? 'require-pro' : 'flexify-checkout-step-trigger'; ?>" data-trigger="<?php echo esc_html( $index ) ?>"><?php echo esc_html__( 'Editar campo', 'flexify-checkout-for-woocommerce' ) ?></button>

                            <div class="flexify-checkout-step-container">
                                <div class="popup-content">
                                    <div class="popup-header">
                                        <h5 class="popup-title"><?php echo sprintf( __('Configurar campo <strong class="field-name">%s</strong>', 'flexify-checkout-for-woocommerce'), esc_html( $value['label'] ) ); ?></h5>
                                        <button class="flexify-checkout-step-close-popup btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                                    </div>
                                    <div class="popup-body">
                                        <table class="form-table">
                                            <?php
                                                if ( isset( $value['source'] ) && $value['source'] === 'native' && $index !== 'billing_country' || isset( $value['source'] ) && $value['source'] === 'plugin' && $index !== 'billing_country' ) {
                                                    ?>
                                                    <tr>
                                                        <th class="w-50">
                                                            <?php echo esc_html__( 'Ativar/Desativar este campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                            <span class="flexify-checkout-description"><?php echo esc_html__('Este é um campo nativo do WooCommerce e não pode ser removido, apenas desativado.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                        </th>
                                                        <td class="w-50">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="toggle-switch toggle-active-tab" name="checkout_step[<?php echo $index; ?>][enabled]" value="yes" <?php checked( $fields[$index]['enabled'] === 'yes' ); ?> />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="w-50">
                                                            <?php echo esc_html__( 'Obrigatoriedade do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                            <span class="flexify-checkout-description"><?php echo esc_html__('Ao desativar, este campo se tornará não obrigatório.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                        </th>
                                                        <td class="w-50">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="toggle-switch toggle-active-tab" name="checkout_step[<?php echo $index; ?>][required]" value="yes" <?php checked( $fields[$index]['required'] === 'yes' ); ?> />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php
                                                } elseif ( $index === 'billing_country' ) {
                                                    ?>
                                                    <tr>
                                                        <th class="w-50">
                                                            <?php echo esc_html__( 'Ativar/Desativar este campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                            <span class="flexify-checkout-description"><?php echo esc_html__('Este é um campo obrigatório para finalização de compras e não pode ser desativado.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                        </th>
                                                        <td class="w-50">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="toggle-switch toggle-active-tab" name="checkout_step[<?php echo $index; ?>][enabled]" value="yes" disabled <?php checked( true ); ?> />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            ?>
                                            <tr>
                                                <th class="w-50">
                                                    <?php echo esc_html__( 'Nome do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                    <span class="flexify-checkout-description"><?php echo esc_html__('Define o título que será exibido para este campo.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                </th>
                                                <td class="w-50">
                                                    <input type="text" class="get-name-field form-control" name="checkout_step[<?php echo $index; ?>][label]" value="<?php echo $current_field_step_label ?>"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="w-50">
                                                    <?php echo esc_html__( 'Posição do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                    <span class="flexify-checkout-description"><?php echo esc_html__('Define o endpoint que será usado como link permanente para esta guia.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                </th>
                                                <td class="w-50">
                                                    <select class="form-select" name="checkout_step[<?php echo $index; ?>][position]">
                                                        <option value="left" <?php echo $current_field_step_position === 'left' ? "selected=selected" : ""; ?>><?php echo esc_html__( 'Esquerda', 'flexify-checkout-for-woocommerce' ) ?></option>
                                                        <option value="right" <?php echo $current_field_step_position === 'right' ? "selected=selected" : ""; ?>><?php echo esc_html__( 'Direita', 'flexify-checkout-for-woocommerce' ) ?></option>
                                                        <option value="full" <?php echo $current_field_step_position === 'full' ? "selected=selected" : ""; ?>><?php echo esc_html__( 'Largura completa', 'flexify-checkout-for-woocommerce' ) ?></option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="w-50">
                                                    <?php echo esc_html__( 'Classe CSS personalizada do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                    <span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para este campo. (Opcional)', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                </th>
                                                <td class="w-50">
                                                    <input type="text" class="form-control" name="checkout_step[<?php echo $index; ?>][classes]" value="<?php echo $current_field_step_classes ?>"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="w-50">
                                                    <?php echo esc_html__( 'Classe CSS personalizada do título', 'flexify-checkout-for-woocommerce' ) ?>
                                                    <span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para o título (label) deste campo. (Opcional)', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                </th>
                                                <td class="w-50">
                                                    <input type="text" class="form-control" name="checkout_step[<?php echo $index; ?>][label_classes]" value="<?php echo $current_field_step_label_classes ?>"/>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }

                    ?>
                </div>
            </td>
            <td class="step-container">
                <span class="step-title"><?php echo esc_html__( 'Etapa 2 (Entrega)', 'flexify-checkout-for-woocommerce' ) ?></span>
                <div id="shipping_step" data-step="2">
                    <?php

                    foreach ( $fields as $index => $value ) {
                        $current_field_step_position = isset( $fields[$index]['position'] ) ? $fields[$index]['position'] : 'full';
                        $current_field_step_label = isset( $fields[$index]['label'] ) ? $fields[$index]['label'] : '';
                        $current_field_step_classes = isset( $fields[$index]['classes'] ) ? $fields[$index]['classes'] : '';
                        $current_field_step_label_classes = isset( $fields[$index]['label_classes'] ) ? $fields[$index]['label_classes'] : '';
                        
                        // skip field if is different of step 2
                        if ( $value['step'] !== '2' ) {
                            continue;
                        }
                        ?>

                        <div id="<?php echo esc_attr( $index ); ?>" class="field-item <?php echo isset( $value['enabled'] ) && $value['enabled'] !== 'yes' ? 'inactive' : ''; echo ( ! self::license_valid() ) ? 'require-pro' : ''; ?>">
                            <input type="hidden" class="change-priority" name="checkout_step[<?php echo $index; ?>][priority]" value="<?php echo esc_attr( $value['priority'] ) ?>">
                            <input type="hidden" class="change-step" name="checkout_step[<?php echo $index; ?>][step]" value="<?php echo esc_attr( $value['step'] ) ?>">
                        
                            <span class="field-name"><?php echo esc_html( $value['label'] ) ?></span>
                            <button class="btn btn-sm btn-outline-primary ms-auto rounded-3 <?php echo ( ! self::license_valid() ) ? 'require-pro' : 'flexify-checkout-step-trigger'; ?>" data-trigger="<?php echo esc_html( $index ) ?>"><?php echo esc_html__( 'Editar campo', 'flexify-checkout-for-woocommerce' ) ?></button>

                            <div class="flexify-checkout-step-container">
                                <div class="popup-content">
                                    <div class="popup-header">
                                        <h5 class="popup-title"><?php echo sprintf( __('Configurar campo <strong class="field-name">%s</strong>', 'flexify-checkout-for-woocommerce'), esc_html( $value['label'] ) ); ?></h5>
                                        <button class="flexify-checkout-step-close-popup btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                                    </div>
                                    <div class="popup-body">
                                        <table class="form-table">
                                            <?php
                                                if ( isset( $value['source'] ) && $value['source'] === 'native' && $index !== 'billing_country' || isset( $value['source'] ) && $value['source'] === 'plugin' && ! $index !== 'billing_country' ) {
                                                    ?>
                                                    <tr>
                                                        <th class="w-50">
                                                            <?php echo esc_html__( 'Ativar/Desativar este campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                            <span class="flexify-checkout-description"><?php echo esc_html__('Este é um campo nativo do WooCommerce e não pode ser removido, apenas desativado.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                        </th>
                                                        <td class="w-50">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="toggle-switch toggle-active-tab" name="checkout_step[<?php echo $index; ?>][enabled]" value="yes" <?php checked( $fields[$index]['enabled'] === 'yes' ); ?> />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="w-50">
                                                            <?php echo esc_html__( 'Obrigatoriedade do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                            <span class="flexify-checkout-description"><?php echo esc_html__('Ao desativar, este campo se tornará não obrigatório.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                        </th>
                                                        <td class="w-50">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="toggle-switch toggle-active-tab" name="checkout_step[<?php echo $index; ?>][required]" value="yes" <?php checked( $fields[$index]['required'] === 'yes' ); ?> />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                } elseif ( $index === 'billing_country' ) {
                                                    ?>
                                                    <tr>
                                                        <th class="w-50">
                                                            <?php echo esc_html__( 'Ativar/Desativar este campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                            <span class="flexify-checkout-description"><?php echo esc_html__('Este é um campo obrigatório para finalização de compras e não pode ser desativado.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                        </th>
                                                        <td class="w-50">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="toggle-switch toggle-active-tab" name="checkout_step[<?php echo $index; ?>][enabled]" value="yes" disabled <?php checked( true ); ?> />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            ?>
                                            <tr>
                                                <th class="w-50">
                                                    <?php echo esc_html__( 'Nome do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                    <span class="flexify-checkout-description"><?php echo esc_html__('Define o título que será exibido para este campo.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                </th>
                                                <td class="w-50">
                                                    <input type="text" class="get-name-field form-control" name="checkout_step[<?php echo $index; ?>][label]" value="<?php echo $current_field_step_label ?>"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="w-50">
                                                    <?php echo esc_html__( 'Posição do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                    <span class="flexify-checkout-description"><?php echo esc_html__('Define o endpoint que será usado como link permanente para esta guia.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                </th>
                                                <td class="w-50">
                                                    <select class="form-select" name="checkout_step[<?php echo $index; ?>][position]">
                                                        <option value="left" <?php echo $current_field_step_position === 'left' ? "selected=selected" : ""; ?>><?php echo esc_html__( 'Esquerda', 'flexify-checkout-for-woocommerce' ) ?></option>
                                                        <option value="right" <?php echo $current_field_step_position === 'right' ? "selected=selected" : ""; ?>><?php echo esc_html__( 'Direita', 'flexify-checkout-for-woocommerce' ) ?></option>
                                                        <option value="full" <?php echo $current_field_step_position === 'full' ? "selected=selected" : ""; ?>><?php echo esc_html__( 'Largura completa', 'flexify-checkout-for-woocommerce' ) ?></option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="w-50">
                                                    <?php echo esc_html__( 'Classe CSS personalizada do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                                    <span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para este campo. (Opcional)', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                </th>
                                                <td class="w-50">
                                                    <input type="text" class="form-control" name="checkout_step[<?php echo $index; ?>][classes]" value="<?php echo $current_field_step_classes ?>"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="w-50">
                                                    <?php echo esc_html__( 'Classe CSS personalizada do título', 'flexify-checkout-for-woocommerce' ) ?>
                                                    <span class="flexify-checkout-description"><?php echo esc_html__('Informe a(s) classe(s) CSS personalizadas para o título (label) deste campo. (Opcional)', 'flexify-checkout-for-woocommerce' ) ?></span>
                                                </th>
                                                <td class="w-50">
                                                    <input type="text" class="form-control" name="checkout_step[<?php echo $index; ?>][label_classes]" value="<?php echo $current_field_step_label_classes ?>"/>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }

                    ?>
                </div>
            </td>
        </tr>
        <tr class="mt-4">
            <td>
            <button id="add_new_checkout_fields_trigger" class="btn btn-primary d-flex align-items-center" disabled>
                    <svg class="icon icon-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"></path></svg>
                    <?php echo esc_html__('Adicionar novos campos', 'flexify-checkout-for-woocommerce' ) ?>
                </button>

                <div class="add-new-checkout-fields-container">
                    <div class="popup-content">
                        <div class="popup-header">
                            <h5 class="popup-title"><?php echo esc_html__('Adicionar novo campo para finalização de compras', 'flexify-checkout-for-woocommerce') ?></h5>
                            <button class="add-new-checkout-fields-close btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                        </div>
                        <div class="popup-body">
                            <table class="form-table">
                                <tr>
                                    <th class="w-50">
                                        <?php echo esc_html__( 'Nome e ID do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                        <span class="flexify-checkout-description"><?php echo esc_html__( 'Informe o nome que será usado no campo em letras maiusculas, usando underline no lugar dos espaços e após o prefixo "billing_".', 'flexify-checkout-for-woocommerce' ) ?></span>
                                    </th>
                                    <td class="w-50">
                                        <div class="input-group">
                                            <span class="w-fit input-group-text fs-lg"><?php echo esc_html__( 'billing_', 'flexify-checkout-for-woocommerce' ) ?></span>
                                            <input type="text" class="form-control" id="checkout_field_name" name="checkout_field_name" value=""/>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="w-50">
                                        <?php echo esc_html__( 'Tipo do campo', 'flexify-checkout-for-woocommerce' ) ?>
                                        <span class="flexify-checkout-description"><?php echo esc_html__( 'Selecione o tipo do campo que será incluído na finalização de compras.', 'flexify-checkout-for-woocommerce' ) ?></span>
                                    </th>
                                    <td class="w-50">
                                        <select name="checkout_field_type" class="form-select">
                                            <option value="text"><?php echo esc_html__( 'Texto', 'flexify-checkout-for-woocommerce' ) ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <span class="bg-translucent-warning text-warning py-2 px-3 rounded-2 d-flex align-items-center">
                    <svg class="icon icon-warning me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
                    <?php echo esc_html__('Este recurso será implementado em breve.', 'flexify-checkout-for-woocommerce' ) ?>
                </span>
            </td>
        </tr>
    </table>
</div>