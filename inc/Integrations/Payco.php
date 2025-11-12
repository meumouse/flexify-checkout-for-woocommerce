<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists('Virtuaria_Payments_By_Payco') ) {
    /**
     * Compatibility with Payments by Payco gateway
     *
     * @since 5.3.3
     * @link https://wordpress.org/plugins/virtuaria-payments-by-payco/
     */
    class Payco {

        /**
         * Plugin slug
         * 
         * @since 5.3.3
         * @return string
         */
        private $plugin_slug = 'virtuaria-payments-by-payco/virtuaria-payments-by-payco.php';

        /**
         * Option name
         * 
         * @since 5.3.3
         * @return string
         */
        private $option_name = 'virtuaria_payments_payco_settings';

        /**
         * Subpartner ID
         * 
         * @since 5.3.3
         * @return string
         */
        private $subpartner_id = '6e349681-55c1-4189-8499-637b0e47aeaa';

        /**
         * Construct function.
         *
         * @since 5.3.3
         * @return void
         */
        public function __construct() {
            // activated plugin successfully
            add_action( 'Flexify_Checkout/Modules/Activate/Success', array( $this, 'on_module_activate_success' ), 10, 1 );
        }


        /**
         * On module activate success
         *
         * @since 5.3.3
         * @param string $slug | Plugin slug
         */
        public function on_module_activate_success( $slug ) {
            if ( $slug === $this->plugin_slug ) {
                $this->seed_payco_settings();
            }
        }


        /**
         * Add seed settings on Payco settings plugin
         * 
         * @since 5.3.3
         * @return void
         */
        private function seed_payco_settings() {
            $options = get_option( $this->option_name, array() );
            $options['subpartner_id'] = $this->subpartner_id;

            update_option( $this->option_name, $options );
        }
    }
}