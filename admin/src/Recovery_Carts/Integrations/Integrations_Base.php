<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Abstract base class for integrations
 * 
 * @since 1.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations
 * @author MeuMouse.com
 */
abstract class Integrations_Base {

    /**
     * Add tab items on integration settings tab
     * 
     * @since 1.0.0
     * @return array
     */
    public static function integration_tab_items() {
        return apply_filters( 'Flexify_Checkout/Recovery_Carts/Settings/Tabs/Integrations', array(
            'joinotify' => array(
                'title' => __('Joinotify', 'fc-recovery-carts'),
                'description' => __('Use o melhor e mais completo construtor de automações com WhatsApp para notificar seus usuários.', 'fc-recovery-carts'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 703 882.5"><path d="M908.66,248V666a126.5,126.5,0,0,1-207.21,97.41l-16.7-16.7L434.08,496.07l-62-62a47.19,47.19,0,0,0-72,30.86V843.36a47.52,47.52,0,0,0,69.57,35.22l19.3-19.3,56-56,81.19-81.19,10.44-10.44a47.65,47.65,0,0,1,67.63,65.05l-13,13L428.84,952.12l-9.59,9.59a128,128,0,0,1-213.59-95.18V413.17a124.52,124.52,0,0,1,199.78-82.54l22.13,22.13L674.45,599.64l46.22,46.22,17,17a47.8,47.8,0,0,0,71-31.44V270.19a48.19,48.19,0,0,0-75-40.05L720.43,243.4l-68.09,68.09L575.7,388.13a48.39,48.39,0,0,1-67.43-67.93L680,148.46A136,136,0,0,1,908.66,248Z" transform="translate(-205.66 -112.03)" style="fill:#22c55e"/></svg>',
                'toggle_switch_key' => 'enable_joinotify_integration',
                'action_hook' => 'Flexify_Checkout/Recovery_Carts/Integrations/Joinotify',
                'is_plugin' => true,
                'plugin_active' => array(
                    'joinotify/joinotify.php',
                ),
            ),
            'email' => array(
                'title' => __('E-mail', 'fc-recovery-carts'),
                'description' => __('Notifique seus usuários e clientes com e-mails personalizados da sua marca.', 'fc-recovery-carts'),
                'icon' => '<svg viewBox="0 0 24.00 24.00" xmlns="http://www.w3.org/2000/svg" fill="#495057" stroke="#495057" transform="rotate(0)" stroke-width="0.00024000000000000003"><g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.624"></g><g><path d="M13.025 17H3.707l5.963-5.963L12 12.83l2.33-1.794 1.603 1.603a5.463 5.463 0 0 1 1.004-.41l-1.808-1.808L21 5.9v6.72a5.514 5.514 0 0 1 1 .64V5.5A1.504 1.504 0 0 0 20.5 4h-17A1.504 1.504 0 0 0 2 5.5v11A1.5 1.5 0 0 0 3.5 18h9.525c-.015-.165-.025-.331-.025-.5s.01-.335.025-.5zM3 16.293V5.901l5.871 4.52zM20.5 5c.009 0 .016.005.025.005L12 11.57 3.475 5.005c.009 0 .016-.005.025-.005zm-2 8a4.505 4.505 0 0 0-4.5 4.5 4.403 4.403 0 0 0 .05.5 4.49 4.49 0 0 0 4.45 4h.5v-1h-.5a3.495 3.495 0 0 1-3.45-3 3.455 3.455 0 0 1-.05-.5 3.498 3.498 0 0 1 5.947-2.5H20v.513A2.476 2.476 0 0 0 18.5 15a2.5 2.5 0 1 0 1.733 4.295A1.497 1.497 0 0 0 23 18.5v-1a4.555 4.555 0 0 0-4.5-4.5zm0 6a1.498 1.498 0 0 1-1.408-1 1.483 1.483 0 0 1-.092-.5 1.5 1.5 0 0 1 3 0 1.483 1.483 0 0 1-.092.5 1.498 1.498 0 0 1-1.408 1zm3.5-.5a.5.5 0 0 1-1 0v-3.447a3.639 3.639 0 0 1 1 2.447z"></path><path fill="none" d="M0 0h24v24H0z"></path></g></svg>',
                'toggle_switch_key' => 'enable_email_integration',
                'action_hook' => 'Flexify_Checkout/Recovery_Carts/Integrations/Email',
                'comming_soon' => true,
            ),
        ));
    }
}