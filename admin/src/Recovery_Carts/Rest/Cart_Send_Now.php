<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Opt_Out;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Cart recovery — send a follow-up immediately ("Send now").
 *
 * POST flexify-checkout/v1/recovery/carts/{id}/send. Dispatches one follow-up
 * event to the cart right now through the engine (force = true), reusing the
 * same send path as the scheduler so the Pro gate, opt-out suppression and
 * notification history all apply. An optional "event_key" selects which
 * follow-up to send; otherwise the first enabled, engine-delivered event is used.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Cart_Send_Now extends Abstract_Route {

    /**
     * Route path with the cart id parameter.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/carts/(?P<id>\d+)/send';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';


    /**
     * Handle the request.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle( WP_REST_Request $request ) {
        $id = absint( $request->get_param('id') );

        if ( ! $id || get_post_type( $id ) !== 'fc-recovery-carts' ) {
            return new \WP_Error( 'fcrc_cart_not_found', __( 'Cart not found.', 'flexify-checkout-for-woocommerce' ), array( 'status' => 404 ) );
        }

        // Freemium: manual sending is still a Pro capability.
        if ( ! Helpers::can_send_recovery_messages() ) {
            return new \WP_Error( 'fcrc_pro_required', __( 'Sending recovery messages requires a Pro license.', 'flexify-checkout-for-woocommerce' ), array( 'status' => 403 ) );
        }

        // Respect opt-out.
        if ( Opt_Out::is_cart_suppressed( $id ) ) {
            return new \WP_Error( 'fcrc_opted_out', __( 'This contact opted out of recovery messages.', 'flexify-checkout-for-woocommerce' ), array( 'status' => 422 ) );
        }

        $event_key = sanitize_key( (string) $request->get_param('event_key') );
        $event_key = $this->resolve_event_key( $event_key );

        if ( '' === $event_key ) {
            return new \WP_Error( 'fcrc_no_event', __( 'No follow-up message is available to send.', 'flexify-checkout-for-woocommerce' ), array( 'status' => 400 ) );
        }

        // Detect whether a message actually went out by comparing the cart's
        // notification history before and after firing the engine.
        $before = $this->notifications_count( $id );

        /**
         * Fire the send path synchronously with force = true. The booted
         * Recovery_Handler instance is listening on this hook, so this reuses the
         * exact same delivery logic (channels, placeholders, history) as the
         * scheduler without re-instantiating anything.
         */
        do_action( 'fcrc_send_follow_up_message', array(
            'cart_id'   => $id,
            'event_key' => $event_key,
            'force'     => true,
        ) );

        $after = $this->notifications_count( $id );
        $sent  = $after > $before;

        return $this->success_response( array(
            'sent'      => $sent,
            'event_key' => $event_key,
            'message'   => $sent
                ? __( 'Follow-up sent.', 'flexify-checkout-for-woocommerce' )
                : __( 'Nothing was sent — check the contact info and the channel configuration.', 'flexify-checkout-for-woocommerce' ),
        ) );
    }


    /**
     * Resolve the follow-up event key to send.
     *
     * Validates a provided key, or falls back to the first enabled event that is
     * delivered by the engine (workflow-delegated events are sent by Joinotify).
     *
     * @since 6.0.0
     * @param string $requested | The requested event key ('' for auto).
     * @return string The event key, or '' when none is suitable.
     */
    private function resolve_event_key( $requested ) {
        $events = Admin::get_setting('follow_up_events');

        if ( ! is_array( $events ) || empty( $events ) ) {
            return '';
        }

        if ( '' !== $requested && isset( $events[ $requested ] ) ) {
            return $requested;
        }

        foreach ( $events as $key => $event ) {
            if ( ! isset( $event['enabled'] ) || 'yes' !== $event['enabled'] ) {
                continue;
            }

            $mode = isset( $event['delivery_mode'] ) ? $event['delivery_mode'] : 'engine';

            if ( 'joinotify_workflow' === $mode ) {
                continue;
            }

            return (string) $key;
        }

        return '';
    }


    /**
     * Count the notifications already recorded for a cart.
     *
     * @since 6.0.0
     * @param int $cart_id | The recovery cart post ID.
     * @return int
     */
    private function notifications_count( $cart_id ) {
        $notifications = get_post_meta( $cart_id, '_fcrc_notifications_sent', true );

        return is_array( $notifications ) ? count( $notifications ) : 0;
    }
}
