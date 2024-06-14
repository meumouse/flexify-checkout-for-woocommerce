<?php

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="texts" class="nav-content">
    <table class="form-table">
        <tr>
            <th>
                <?php echo esc_html__( 'Texto informativo dos campos da etapa de contato', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_header_step_1" name="text_header_step_1" value="<?php echo self::get_setting('text_header_step_1') ?>"/>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Título das formas de entrega', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_shipping_methods_label" name="text_shipping_methods_label" value="<?php echo self::get_setting('text_shipping_methods_label') ?>"/>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Texto informativo dos campos da etapa de entrega', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_header_step_2" name="text_header_step_2" value="<?php echo self::get_setting('text_header_step_2') ?>"/>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Texto informativo dos campos da etapa de pagamento', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_header_step_3" name="text_header_step_3" value="<?php echo self::get_setting('text_header_step_3') ?>"/>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Texto informativo dos itens do carrinho', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_header_sidebar_right" name="text_header_sidebar_right" value="<?php echo self::get_setting('text_header_sidebar_right') ?>"/>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Texto informativo do verificador da etapa de contato', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_check_step_1" name="text_check_step_1" value="<?php echo self::get_setting('text_check_step_1') ?>"/>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Texto informativo do verificador da etapa de entrega', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_check_step_2" name="text_check_step_2" value="<?php echo self::get_setting('text_check_step_2') ?>"/>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Texto informativo do verificador da etapa de pagamento', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_check_step_3" name="text_check_step_3" value="<?php echo self::get_setting('text_check_step_3') ?>"/>
            </td>
        </tr>
        <tr>
            <th>
                <?php echo esc_html__( 'Texto do botão de voltar etapas', 'flexify-checkout-for-woocommerce' ) ?>
                <span class="flexify-checkout-description"><?php echo esc_html__( 'Deixe em branco para não exibir.', 'flexify-checkout-for-woocommerce' ) ?></span>
            </th>
            <td>
               <input type="text" class="form-control input-control-wd-20" id="text_previous_step_button" name="text_previous_step_button" value="<?php echo self::get_setting('text_previous_step_button') ?>"/>
            </td>
        </tr>
    </table>
</div>