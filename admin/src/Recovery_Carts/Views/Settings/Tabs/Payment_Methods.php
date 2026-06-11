<?php

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Components as Admin_Components;

/**
 * Template file for payment methods settings
 * 
 * @since 1.3.0
 * @package MeuMouse.com
 */

// Exit if accessed directly.
defined('ABSPATH') || exit; ?>

<div id="payment_methods" class="nav-content">
    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <?php esc_html_e( 'Configurar tempo de atraso para formas de pagamentos', 'fc-recovery-carts' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Permite definir o tempo para que um pedido seja considerado abandonado de acordo com a forma de pagamento.', 'fc-recovery-carts' ); ?></span>
                </th>
                <td>
                    <?php echo Admin_Components::get_payment_methods_delay_options( Admin::get_setting('payment_methods') ); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>