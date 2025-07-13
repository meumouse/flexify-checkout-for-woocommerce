<?php

use MeuMouse\Flexify_Checkout\Admin\Admin_Options;
use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="texts" class="nav-content">
    <table class="form-table">
        <?php
        /**
         * Hook for display custom text option
         * 
         * @since 3.6.0
         */
        do_action('flexify_checkout_before_texts_options'); ?>

        <tr>
            <th>
                <?php esc_html_e( 'Texto informativo dos campos da etapa de contato', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_header_step_1" name="text_header_step_1" value="<?php echo Admin_Options::get_setting('text_header_step_1') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Título das formas de entrega', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_shipping_methods_label" name="text_shipping_methods_label" value="<?php echo Admin_Options::get_setting('text_shipping_methods_label') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto informativo dos campos da etapa de entrega', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_header_step_2" name="text_header_step_2" value="<?php echo Admin_Options::get_setting('text_header_step_2') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto informativo dos campos da etapa de pagamento', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_header_step_3" name="text_header_step_3" value="<?php echo Admin_Options::get_setting('text_header_step_3') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto informativo dos itens do carrinho', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_header_sidebar_right" name="text_header_sidebar_right" value="<?php echo Admin_Options::get_setting('text_header_sidebar_right') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto informativo do verificador da etapa de contato', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_check_step_1" name="text_check_step_1" value="<?php echo Admin_Options::get_setting('text_check_step_1') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto informativo do verificador da etapa de entrega', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_check_step_2" name="text_check_step_2" value="<?php echo Admin_Options::get_setting('text_check_step_2') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto informativo do verificador da etapa de pagamento', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_check_step_3" name="text_check_step_3" value="<?php echo Admin_Options::get_setting('text_check_step_3') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto do botão de voltar etapas', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_previous_step_button" name="text_previous_step_button" value="<?php echo Admin_Options::get_setting('text_previous_step_button') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto do botão para revisitar a loja da página de agradecimento', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php esc_html_e( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_view_shop_thankyou" name="text_view_shop_thankyou" value="<?php echo Admin_Options::get_setting('text_view_shop_thankyou') ?>"/>
            </td>
        </tr>
        
        <tr class="container-separator"></tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto do resumo de informações de contato', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description mb-3"><?php esc_html_e( 'Utilize as variáveis abaixo para recuperar as informações de campos, use <br> para quebrar uma linha. Ou deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>

                <?php foreach ( Helpers::get_placeholder_input_values() as $field_id => $value ) : ?>
                    <div class="d-flex mb-1">
                        <span class="flexify-checkout-description"><code><?php echo esc_html( $value['placeholder_html'] ) ?></code>
                        </span><span class="flexify-checkout-description ms-2"><?php echo esc_html( $value['description'] ) ?></span>
                    </div>
                <?php endforeach; ?>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_contact_customer_review" name="text_contact_customer_review" value="<?php echo Admin_Options::get_setting('text_contact_customer_review') ?>"/>
            </td>
        </tr>

        <tr>
            <th>
                <?php esc_html_e( 'Texto do resumo de informações de entrega', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description mb-3"><?php esc_html_e( 'Utilize as variáveis abaixo para recuperar as informações de campos, use <br> para quebrar uma linha. Ou deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>

                <?php foreach ( Helpers::get_placeholder_input_values() as $field_id => $value ) : ?>
                    <div class="d-flex mb-1">
                        <span class="flexify-checkout-description"><code><?php echo esc_html( $value['placeholder_html'] ) ?></code>
                        </span><span class="flexify-checkout-description ms-2"><?php echo esc_html( $value['description'] ) ?></span>
                    </div>
                <?php endforeach; ?>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_shipping_customer_review" name="text_shipping_customer_review" value="<?php echo Admin_Options::get_setting('text_shipping_customer_review') ?>"/>
            </td>
        </tr>
        
        <?php
        /**
         * Hook for display custom text option
         * 
         * @since 3.6.0
         */
        do_action('flexify_checkout_after_texts_options'); ?>

    </table>
</div>