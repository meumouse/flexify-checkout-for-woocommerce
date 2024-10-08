<?php

namespace MeuMouse\Flexify_Checkout\Compat;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Compatibility with Iconic Delivery slots.
 *
 * @since 1.0.0
 * @version 3.8.0
 * @package MeuMouse.com
 */
class Delivery_Slots {
	/**
	 * Construct function.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'on_init' ) );
	}


	/**
	 * On init.
	 */
	public static function on_init() {
		if ( ! class_exists('Iconic_WDS') ) {
			return;
		}

		add_filter( 'iconic_wds_labels_by_type', array( __CLASS__, 'remove_placeholder' ), 101 );

		$force = filter_input( INPUT_GET, 'flexify_force_ty' );
		
		if ( '1' === $force || Flexify_Checkout_Core_Settings::$settings['thankyou_thankyou_enable_thankyou_page'] ) {
			add_action( 'flexify_thankyou_after_customer_details_payment_row', array( __CLASS__, 'add_delivery_fields' ) );
		}
	}

	/**
	 * Remove placeholder.
	 *
	 * @param array $labels Labels.
	 * @return array
	 * @since 1.0.0
	 */
	public static function remove_placeholder( $labels ) {
		$labels['delivery']['select_date']   = '';
		$labels['collection']['select_date'] = '';
		return $labels;
	}

	/**
	 * Add delivery date/time fields to the Thank you page.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 * @since 1.0.0
	 */
	public static function add_delivery_fields( $order ) {
		$delivery_slot_data = \Iconic_WDS_Order::get_delivery_slot_data( $order );

		if ( empty( $delivery_slot_data ) || empty( $delivery_slot_data['date'] ) ) {
			return;
		}

		$time = ! empty( $delivery_slot_data['time_slot'] ) ? $delivery_slot_data['time_slot'] : ( ! empty( $delivery_slot_data['time'] ) ? $delivery_slot_data['time'] : '' );

		?>
		<div class="flexify-review-customer__row">
			<div class='flexify-review-customer__label'><label><?php echo esc_html( \Iconic_WDS_Helpers::get_label( 'date', $order ) ); ?></label></div>
			<div class='flexify-review-customer__content'>
				<p>
				<?php
					echo esc_html( $delivery_slot_data['date'] );
				?>
				</p>
			</div>
		</div>
		<?php

		if ( ! empty( $time ) ) {
			?>
			<div class="flexify-review-customer__row">
				<div class='flexify-review-customer__label'><label><?php echo esc_html( \Iconic_WDS_Helpers::get_label( 'time_slot', $order ) ); ?></label></div>
				<div class='flexify-review-customer__content'>
					<p>
					<?php
						echo esc_html( $time );
					?>
					</p>
				</div>
			</div>
			<?php
		}
	}
}

new Delivery_Slots();