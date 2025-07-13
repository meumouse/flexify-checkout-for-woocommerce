<?php

namespace MeuMouse\Flexify_Checkout\Integrations;

use MeuMouse\Flexify_Checkout\Core\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Iconic Delivery slots
 *
 * @since 1.0.0
 * @version 5.0.0
 * @package MeuMouse.com
 */
class Delivery_Slots {

	/**
	 * Construct function
	 * 
	 * @since 1.0.0
     * @version 5.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'remove_actions' ) );
	}


	/**
	 * Remove actions
     * 
     * @since 1.0.0
     * @version 5.0.0
     * @return void
	 */
	public function remove_actions() {
		if ( ! class_exists('Iconic_WDS') ) {
			return;
		}

		add_filter( 'iconic_wds_labels_by_type', array( $this, 'remove_placeholder' ), 101 );

		if ( Helpers::is_thankyou_page() ) {
			add_action( 'flexify_thankyou_after_customer_details_payment_row', array( $this, 'add_delivery_fields' ) );
		}
	}


	/**
	 * Remove placeholder
	 *
     * @since 1.0.0
     * @version 5.0.0
	 * @param array $labels | Labels
	 * @return array
	 */
	public function remove_placeholder( $labels ) {
		$labels['delivery']['select_date'] = '';
		$labels['collection']['select_date'] = '';

		return $labels;
	}


	/**
	 * Add delivery date/time fields to the Thank you page
	 *
     * @since 1.0.0
     * @version 5.0.0
	 * @param $order | Order object
	 * @return void
	 */
	public function add_delivery_fields( $order ) {
		$delivery_slot_data = \Iconic_WDS_Order::get_delivery_slot_data( $order );

		if ( empty( $delivery_slot_data ) || empty( $delivery_slot_data['date'] ) ) {
			return;
		}

		$time = ! empty( $delivery_slot_data['time_slot'] ) ? $delivery_slot_data['time_slot'] : ( ! empty( $delivery_slot_data['time'] ) ? $delivery_slot_data['time'] : '' ); ?>

		<div class="flexify-review-customer__row">
			<div class='flexify-review-customer__label'>
                <label><?php echo esc_html( \Iconic_WDS_Helpers::get_label( 'date', $order ) ); ?></label>
            </div>
			
            <div class='flexify-review-customer__content'>
				<p><?php echo esc_html( $delivery_slot_data['date'] ); ?></p>
			</div>
		</div>

		<?php if ( ! empty( $time ) ) : ?>
			<div class="flexify-review-customer__row">
				<div class='flexify-review-customer__label'>
                    <label><?php echo esc_html( \Iconic_WDS_Helpers::get_label( 'time_slot', $order ) ); ?></label>
                </div>
				
                <div class='flexify-review-customer__content'>
					<p><?php echo esc_html( $time ); ?></p>
				</div>
			</div>
        <?php endif;
	}
}

new Delivery_Slots();