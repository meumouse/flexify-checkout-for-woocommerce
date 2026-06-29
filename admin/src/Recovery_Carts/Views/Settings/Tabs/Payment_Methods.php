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
                    <?php esc_html_e( 'Configure delay time for payment methods', 'flexify-checkout-for-woocommerce' ); ?>
                    <span class="fc-recovery-carts-description"><?php esc_html_e( 'Lets you set the time for an order to be considered abandoned according to the payment method.', 'flexify-checkout-for-woocommerce' ); ?></span>
                </th>
                <td>
                    <?php echo Admin_Components::get_payment_methods_delay_options( Admin::get_setting('payment_methods') ); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>