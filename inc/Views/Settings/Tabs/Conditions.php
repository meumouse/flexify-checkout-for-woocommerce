<?php

use MeuMouse\Flexify_Checkout\Core\Helpers;
use MeuMouse\Flexify_Checkout\API\License;

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="conditions" class="nav-content">
   <table class="form-table">
        <?php
        /**
         * Hook for display custom conditions options
        * 
        * @since 3.6.0
        */
        do_action('flexify_checkout_before_conditions_options');

        $get_conditions = get_option('flexify_checkout_conditions', array());
        $get_fields = Helpers::get_checkout_fields_on_admin();
        
        if ( empty( $get_conditions ) ) : ?>
            <tr>
                <td>
                    <div id="empty_conditions" class="alert alert-info d-flex align-items-center">
                        <svg class="icon icon-info me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"></path><path d="M11 11h2v6h-2zm0-4h2v2h-2z"></path></svg>
                        <?php esc_html_e( 'Ainda não existem condições.', 'flexify-checkout-for-woocommerce' ) ?>
                    </div>
                </td>
            </tr>
        <?php else : ?>
            <tr>
                <td>
                    <div id="display_conditions" class="mb-3">
                        <ul class="list-group">
                            <?php foreach ( $get_conditions as $condition => $value ) : ?>
                                <li class="list-group-item d-flex align-items-center justify-content-between" data-condition="<?php echo esc_attr( $condition ) ?>">
                                    <?php

                                    $condition_type = array(
                                        'show' => esc_html__( 'Mostrar', 'flexify-checkout-for-woocommerce' ),
                                        'hide' => esc_html__( 'Ocultar', 'flexify-checkout-for-woocommerce' ),
                                    );

                                    $component_type_label = '';

                                    if ( $value['component'] === 'field' ) {
                                        $field_id = $value['component_field'];
                                        $component_type_label = sprintf( esc_html__( 'Campo: %s', 'flexify-checkout-for-woocommerce' ), isset( $get_fields['billing'][$field_id]['label'] ) ? esc_html( $get_fields['billing'][$field_id]['label'] ) : '' );
                                    } elseif ( $value['component'] === 'shipping' ) {
                                        $shipping_id = $value['shipping_method'];
                                        $component_type_label = sprintf( esc_html__( 'Forma de entrega: %s', 'flexify-checkout-for-woocommerce' ), WC()->shipping->get_shipping_methods()[$shipping_id]->method_title );
                                    } elseif ( $value['component'] === 'payment' ) {
                                        $payment_id = $value['payment_method'];
                                        $component_type_label = sprintf( esc_html__( 'Forma de pagamento: %s', 'flexify-checkout-for-woocommerce' ), WC()->payment_gateways->payment_gateways()[$payment_id]->method_title );
                                    }

                                    $component_verification_label = '';

                                    if ( $value['verification_condition'] === 'field' ) {
                                        $field_id = $value['verification_condition_field'];
                                        $component_verification_label = sprintf( esc_html__( 'Campo %s', 'flexify-checkout-for-woocommerce' ), isset( $get_fields['billing'][$field_id]['label'] ) ? $get_fields['billing'][$field_id]['label'] : '' );
                                    } elseif ( $value['verification_condition'] === 'qtd_cart_total' ) {
                                        $component_verification_label = esc_html__( 'Quantidade total do carrinho', 'flexify-checkout-for-woocommerce' );
                                    } elseif ( $value['verification_condition'] === 'cart_total_value' ) {
                                        $component_verification_label = esc_html__( 'Valor total do carrinho', 'flexify-checkout-for-woocommerce' );
                                    }

                                    $condition = array(
                                        'is' => esc_html__( 'É', 'flexify-checkout-for-woocommerce' ),
                                        'is_not' => esc_html__( 'Não é', 'flexify-checkout-for-woocommerce' ),
                                        'empty' => esc_html__( 'Vazio', 'flexify-checkout-for-woocommerce' ),
                                        'not_empty' => esc_html__( 'Não está vazio', 'flexify-checkout-for-woocommerce' ),
                                        'contains' => esc_html__( 'Contém', 'flexify-checkout-for-woocommerce' ),
                                        'not_contain' => esc_html__( 'Não contém', 'flexify-checkout-for-woocommerce' ),
                                        'start_with' => esc_html__( 'Começa com', 'flexify-checkout-for-woocommerce' ),
                                        'finish_with' => esc_html__( 'Termina com', 'flexify-checkout-for-woocommerce' ),
                                        'bigger_then' => esc_html__( 'Maior que', 'flexify-checkout-for-woocommerce' ),
                                        'less_than' => esc_html__( 'Menor que', 'flexify-checkout-for-woocommerce' ),
                                        'checked' => esc_html__( 'Marcado', 'flexify-checkout-for-woocommerce' ),
                                        'not_checked' => esc_html__( 'Desmarcado', 'flexify-checkout-for-woocommerce' ),
                                    );
                                    
                                    $condition_value = isset( $value['condition_value'] ) ? $value['condition_value'] : ''; ?>

                                    <div class="d-grid">
                                        <div class="mb-2">
                                            <?php echo sprintf( esc_html__( 'Condição: %s %s', 'flexify-checkout-for-woocommerce' ), $condition_type[$value['type_rule']], $component_type_label ) ?>
                                        </div>
                                        <div>
                                            <?php echo sprintf( esc_html__( 'Se: %s %s %s', 'flexify-checkout-for-woocommerce' ), $component_verification_label, mb_strtolower( $condition[$value['condition']] ), $condition_value ) ?>
                                        </div>
                                    </div>
                                    <button class="exclude-condition btn btn-icon btn-sm btn-outline-danger rounded-3 ms-3">
                                        <svg class="icon icon-sm icon-danger" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 2H9c-1.103 0-2 .897-2 2v2H3v2h2v12c0 1.103.897 2 2 2h10c1.103 0 2-.897 2-2V8h2V6h-4V4c0-1.103-.897-2-2-2zM9 4h6v2H9V4zm8 16H7V8h10v12z"></path></svg>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <td>
                <button <?php echo ( License::is_valid() ) ? 'id="add_new_checkout_condition_trigger"' : ''; ?> class="btn btn-primary d-flex align-items-center <?php echo ( ! License::is_valid() ) ? 'require-pro' : ''; ?>">
                    <svg class="icon icon-white me-2" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19 11h-6V5h-2v6H5v2h6v6h2v-6h6z"></path></svg>
                    <?php esc_html_e( 'Criar uma nova condição', 'flexify-checkout-for-woocommerce' ) ?>
                </button>

                <div id="add_new_checkout_condition_container" class="popup-container">
                    <div class="popup-content popup-fullscreen">
                        <div class="popup-header border-bottom-0 justify-content-end">
                            <button id="close_add_new_checkout_condition" class="btn-close fs-lg" aria-label="<?php esc_html( 'Fechar', 'flexify-checkout-for-woocommerce' ); ?>"></button>
                        </div>

                        <div class="popup-body p-4 d-flex flex-column justify-content-center">
                            <div class="mb-4 pb-3 d-block">
                                <h2 class="mb-2"><?php esc_html_e( 'Crie uma nova condição para finalização de compras', 'flexify-checkout-for-woocommerce' ) ?></h2>
                                <span class="fs-lg text-muted"><?php esc_html_e( 'Personalize componentes da finalização de compras com regras específicas.', 'flexify-checkout-for-woocommerce' ) ?></span>
                            </div>

                            <div id="add_new_condition_container_master">
                                <h3 class="pt-2 mt-4 mb-0 pt-2 d-block"><?php esc_html_e( 'Regra', 'flexify-checkout-for-woocommerce' ) ?></h3>

                                <div class="mt-4 d-flex align-items-center justify-content-center text-left">
                                    <!-- Select type rule -->
                                    <div class="me-3">
                                        <label class="form-label" for="add_new_condition_type_rule"><?php esc_html_e( 'Tipo da regra: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <select id="add_new_condition_type_rule" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione o tipo de regra', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="show"><?php esc_html_e( 'Mostrar', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="hide"><?php esc_html_e( 'Ocultar', 'flexify-checkout-for-woocommerce' ) ?></option>
                                        </select>
                                    </div>

                                    <!-- Select component type -->
                                    <div class="me-3">
                                        <label class="form-label" for="add_new_condition_component"><?php esc_html_e( 'Componente: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <select id="add_new_condition_component" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione um componente', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="field"><?php esc_html_e( 'Campo', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="payment"><?php esc_html_e( 'Forma de pagamento', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="shipping"><?php esc_html_e( 'Forma de entrega', 'flexify-checkout-for-woocommerce' ) ?></option>
                                        </select>
                                    </div>

                                    <!-- Select specific field -->
                                    <div class="specific-component-fields d-none specific-value">
                                        <label class="form-label" for="add_new_condition_specific_field_component"><?php esc_html_e( 'Campo do checkout: *', 'flexify-checkout-for-woocommerce' ) ?></label>
                                        
                                        <select id="add_new_condition_specific_field_component" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione um campo do checkout', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            
                                            <?php foreach ( $get_fields['billing'] as $field => $value ) : ?>
                                                <option value="<?php echo esc_attr( $field ) ?>" data-type="<?php echo isset( $value['type'] ) ? esc_attr( $value['type'] ) : '' ?>"><?php echo isset( $value['label'] ) ? esc_html( $value['label'] ) : '' ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Select specific shipping method -->
                                    <div class="specific-component-shipping d-none specific-value">
                                        <label class="form-label" for="add_new_condition_specific_shipping_component"><?php esc_html_e( 'Forma de entrega:', 'flexify-checkout-for-woocommerce' ) ?></label>

                                        <select id="add_new_condition_specific_shipping_component" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione uma forma de entrega', 'flexify-checkout-for-woocommerce' ); ?></option>
                                            
                                            <?php foreach ( WC()->shipping->get_shipping_methods() as $shipping ) : ?>
                                                <option value="<?php echo esc_attr( $shipping->id ) ?>"><?php echo esc_html( $shipping->method_title ) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Select specific payment method -->
                                    <div class="specific-component-payment d-none specific-value">
                                        <label class="form-label" for="add_new_condition_specific_payment_component"><?php esc_html_e( 'Forma de pagamento:', 'flexify-checkout-for-woocommerce' ) ?></label>

                                        <select id="add_new_condition_specific_payment_component" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione uma forma de pagamento', 'flexify-checkout-for-woocommerce' ); ?></option>
                                            
                                            <?php foreach ( WC()->payment_gateways->payment_gateways() as $payment ) : ?>
                                                <option value="<?php echo esc_attr( $payment->id ) ?>"><?php echo esc_html( $payment->get_title() ) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <h3 class="pt-2 mt-4 mb-0 pt-2 d-block"><?php esc_html_e( 'Verificação', 'flexify-checkout-for-woocommerce' ) ?></h3>

                                <!-- Check conditions -->
                                <div class="mt-4 d-flex align-items-center justify-content-center text-left">
                                    <!-- Select condition -->
                                    <div class="me-3">
                                        <label class="form-label" for="add_new_condition_component_verification"><?php esc_html_e( 'Condição de verificação: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <select id="add_new_condition_component_verification" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione uma condição de verificação', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="field"><?php esc_html_e( 'Campo', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="qtd_cart_total"><?php esc_html_e( 'Quantidade total do carrinho', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="cart_total_value"><?php esc_html_e( 'Valor total do carrinho', 'flexify-checkout-for-woocommerce' ) ?></option>
                                        </select>
                                    </div>

                                    <!-- Select specific field -->
                                    <div class="specific-checkout-fields d-none specific-value me-3">
                                        <label class="form-label" for="add_new_condition_specific_field"><?php esc_html_e( 'Campo do checkout: *', 'flexify-checkout-for-woocommerce' ) ?></label>
                                        
                                        <select id="add_new_condition_specific_field" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione um campo do checkout', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            
                                            <?php foreach ( $get_fields['billing'] as $field => $value ) : ?>
                                                <option value="<?php echo esc_attr( $field ) ?>" data-type="<?php echo isset( $value['type'] ) ? esc_attr( $value['type'] ) : '' ?>"><?php echo isset( $value['label'] ) ? esc_html( $value['label'] ) : '' ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Select condition -->
                                    <div class="me-3">
                                        <label class="form-label" for="add_new_condition_component_type"><?php esc_html_e( 'Condição: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <select id="add_new_condition_component_type" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione uma condição', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="is"><?php esc_html_e( 'É', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="is_not"><?php esc_html_e( 'Não é', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="empty"><?php esc_html_e( 'Vazio', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="not_empty"><?php esc_html_e( 'Não está vazio', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="contains"><?php esc_html_e( 'Contém', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="not_contain"><?php esc_html_e( 'Não contém', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="start_with"><?php esc_html_e( 'Começa com', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="finish_with"><?php esc_html_e( 'Termina com', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="bigger_then"><?php esc_html_e( 'Maior que', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="less_than"><?php esc_html_e( 'Menor que', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="checked"><?php esc_html_e( 'Marcado', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="not_checked"><?php esc_html_e( 'Desmarcado', 'flexify-checkout-for-woocommerce' ) ?></option>
                                        </select>
                                    </div>

                                    <!-- Display condition value -->
                                    <div class="condition-value d-none">
                                        <label class="form-label" for="add_new_condition_get_condition_value"><?php esc_html_e( 'Valor da condição: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <input type="text" id="add_new_condition_get_condition_value" class="form-control" value="" placeholder="<?php echo esc_attr( 'Valor', 'flexify-checkout-for-woocommerce' ); ?>"/>
                                    </div>
                                </div>

                                <!-- Set specific total cart value -->
                                <div class="specific-total-cart-value mt-4 mb-5 d-none specific-value">
                                    <label class="form-label"><?php esc_html_e( 'Valor total do carrinho:', 'flexify-checkout-for-woocommerce' ) ?></label>
                                    
                                    <div class="input-group">
                                        <span class="input-group-text"><?php echo get_woocommerce_currency_symbol() ?></span>
                                        <input type="number" id="add_new_condition_specific_total_cart_value" class="form-control" placeholder="<?php echo esc_attr( 'Valor', 'flexify-checkout-for-woocommerce' ) ?>">
                                    </div>
                                </div>

                                <!-- Set specific quantity total cart -->
                                <div class="specific-qtd-cart-total mt-4 mb-5 d-none specific-value">
                                    <label class="form-label"><?php esc_html_e( 'Quantidade total do carrinho:', 'flexify-checkout-for-woocommerce' ) ?></label>

                                    <div class="input-group">
                                        <span class="input-group-text"><?php echo get_woocommerce_currency_symbol() ?></span>
                                        <input type="number" id="add_new_condition_specific_qtd_cart_total" class="form-control" placeholder="<?php echo esc_attr( 'Quantidade', 'flexify-checkout-for-woocommerce' ) ?>">
                                    </div>
                                </div>

                                <h3 class="pt-2 mt-4 mb-0 pt-2 d-block"><?php esc_html_e( 'Filtros', 'flexify-checkout-for-woocommerce' ) ?></h3>

                                <div class="d-flex align-items-center justify-content-center text-left mt-4">
                                    <!-- Select user or function rule -->
                                    <div class="me-3">
                                        <label class="form-label" for="add_new_condition_user_function"><?php esc_html_e( 'Selecionar Usuário/Função *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <select id="add_new_condition_user_function" class="form-select">
                                            <option value="all_users"><?php esc_html_e( 'Todos os usuários (Padrão)', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="all_roles"><?php esc_html_e( 'Todas as funções', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="specific_user"><?php esc_html_e( 'Usuários específicos', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="specific_role"><?php esc_html_e( 'Função de usuário específica', 'flexify-checkout-for-woocommerce' ) ?></option>
                                        </select>
                                    </div>

                                    <!-- Select product filter -->
                                    <div class="me-3">
                                        <label class="form-label" for="add_new_condition_product_filter"><?php esc_html_e( 'Filtro de produtos: *', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <select id="add_new_condition_product_filter" class="form-select">
                                            <option value="all_products"><?php esc_html_e( 'Todos os produtos (Padrão)', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="all_categories"><?php esc_html_e( 'Todas as categorias', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="all_attributes"><?php esc_html_e( 'Todos os atributos', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="specific_products"><?php esc_html_e( 'Produtos específicos', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="specific_categories"><?php esc_html_e( 'Categorias específicas', 'flexify-checkout-for-woocommerce' ) ?></option>
                                            <option value="specific_attributes"><?php esc_html_e( 'Atributos específicos', 'flexify-checkout-for-woocommerce' ) ?></option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Condition filters -->
                                <div class="d-flex justify-content-center text-left mt-4">
                                    <!-- Select specific users -->
                                    <div class="specific-users-container mt-4 mb-5 me-3 d-none specific-value">
                                        <label class="form-label"><?php esc_html_e( 'Usuários específicos:', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <input type="text" class="form-control user-search" placeholder="<?php echo esc_attr( 'Comece a digitar para pesquisar...', 'flexify-checkout-for-woocommerce' ); ?>">
                                        <ul id="get_specific_users" class="list-group"></ul>
                                    </div>

                                    <!-- Select specific user roles -->
                                    <div class="specific-roles-container mt-4 mb-5 me-3 d-none specific-value">
                                        <label class="form-label" for="add_new_condition_specific_user_role"><?php esc_html_e( 'Função de usuário específica:', 'flexify-checkout-for-woocommerce' ); ?></label>
                                        <select id="add_new_condition_specific_user_role" class="form-select">
                                            <option value="none"><?php esc_html_e( 'Selecione uma função de usuário', 'flexify-checkout-for-woocommerce' ) ?></option>

                                            <?php $user_role_translations = array(
                                                'administrator' => __( 'Administrador', 'flexify-checkout-for-woocommerce' ),
                                                'author' => __( 'Autor', 'flexify-checkout-for-woocommerce' ),
                                                'subscriber' => __( 'Assinante', 'flexify-checkout-for-woocommerce' ),
                                                'customer' => __( 'Cliente', 'flexify-checkout-for-woocommerce' ),
                                                'contributor' => __( 'Colaborador', 'flexify-checkout-for-woocommerce' ),
                                                'editor' => __( 'Editor', 'flexify-checkout-for-woocommerce' ),
                                                'shop_manager' => __( 'Gerente de loja', 'flexify-checkout-for-woocommerce' ),
                                                'translator' => __( 'Tradutor', 'flexify-checkout-for-woocommerce' ),
                                            );

                                            foreach ( wp_roles()->roles as $role_key => $value ) :
                                                $translated_role_name = isset( $user_role_translations[$role_key] ) ? $user_role_translations[$role_key] : $value['name'];
                                                
                                                echo '<option value="'. esc_attr( $role_key ) .'">'. esc_html( $translated_role_name ) .'</option>';
                                            endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Select specific products -->
                                    <div class="specific-products mt-4 mb-5 d-none specific-value">
                                        <label class="form-label"><?php esc_html_e( 'Produtos específicos:', 'flexify-checkout-for-woocommerce' ) ?></label>
                                        <input type="text" class="form-control product-search" placeholder="<?php echo esc_attr( 'Comece a digitar para pesquisar...', 'flexify-checkout-for-woocommerce' ) ?>">
                                        <ul id="get_specific_products" class="list-group"></ul>
                                    </div>

                                    <!-- Select specific categories -->
                                    <div class="specific-categories mt-4 mb-5 d-none specific-value">
                                        <label class="form-label"><?php esc_html_e( 'Categorias específicas:', 'flexify-checkout-for-woocommerce' ) ?></label>
                                        <input type="text" class="form-control category-search" placeholder="<?php echo esc_attr( 'Comece a digitar para pesquisar...', 'flexify-checkout-for-woocommerce' ) ?>">
                                        <ul id="get_specific_categories" class="list-group"></ul>
                                    </div>

                                    <!-- Select specific attributes -->
                                    <div class="specific-attributes mt-4 mb-5 d-none specific-value">
                                        <label class="form-label"><?php esc_html_e( 'Atributos específicos:', 'flexify-checkout-for-woocommerce' ) ?></label>
                                        <input type="text" class="form-control attribute-search" placeholder="<?php echo esc_attr( 'Comece a digitar para pesquisar...', 'flexify-checkout-for-woocommerce' ) ?>">
                                        <ul id="get_specific_attribute" class="list-group"></ul>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-center mt-4 pt-3">
                                    <button id="add_new_condition_submit" class="btn btn-primary" disabled><?php esc_html_e( 'Criar condição', 'flexify-checkout-for-woocommerce' ) ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
        </tr>

        <?php
        /**
         * Hook for display custom conditions options
        * 
        * @since 3.6.0
        */
        do_action('flexify_checkout_after_general_options'); ?>

    </table>
</div>