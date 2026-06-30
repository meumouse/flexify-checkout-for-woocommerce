<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Rest;

use MeuMouse\Flexify_Checkout\Rest\Abstract_Route;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Funnel_Analytics;
use WP_REST_Request;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Public checkout funnel beacon.
 *
 * The checkout UI pings this route as the shopper advances through the contact,
 * shipping and payment steps. Each session is identified by an opaque client id
 * (cid) generated browser-side so we can de-duplicate repeated pings for the
 * same step/day without cookies or login. The purchase step is intentionally
 * not accepted here — Funnel_Analytics records it server-side from the order so
 * it can never be spoofed or missed.
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Rest
 * @author MeuMouse.com
 */
class Track_Funnel extends Abstract_Route {

    /**
     * Route path.
     *
     * @since 6.0.0
     * @var string
     */
    protected $route = '/recovery/track-step';

    /**
     * Allowed HTTP methods.
     *
     * @since 6.0.0
     * @var string
     */
    protected $methods = 'POST';

    /**
     * Argument schema.
     *
     * @since 6.0.0
     * @var array
     */
    protected $args = array(
        'step' => array(
            'type' => 'string',
            'required' => true,
        ),
        'cid' => array(
            'type' => 'string',
            'default' => '',
        ),
        'device' => array(
            'type' => 'string',
            'default' => '',
        ),
        'source' => array(
            'type' => 'string',
            'default' => '',
        ),
    );


    /**
     * Public endpoint — anyone going through checkout can report progress.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return bool
     */
    public function permission( WP_REST_Request $request ) {
        return true;
    }


    /**
     * Handle the beacon.
     *
     * @since 6.0.0
     * @param WP_REST_Request $request REST request instance.
     * @return \WP_REST_Response
     */
    public function handle( WP_REST_Request $request ) {
        $step = sanitize_key( $request->get_param('step') );

        // Only pre-purchase steps are accepted from the browser.
        if ( ! in_array( $step, array( 'contact', 'shipping', 'payment' ), true ) ) {
            return $this->error_response( esc_html__( 'Invalid funnel step.', 'flexify-checkout-for-woocommerce' ) );
        }

        $cid = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $request->get_param('cid') );
        $date = gmdate( 'Y-m-d', (int) current_time('timestamp', true) );

        // De-duplicate per client/step/day so a chatty UI can't inflate counts.
        if ( $cid !== '' ) {
            $key = 'fcrc_funnel_' . md5( $cid . '|' . $step . '|' . $date );

            if ( get_transient( $key ) ) {
                return $this->success_response( array( 'skipped' => 'duplicate' ) );
            }

            set_transient( $key, 1, DAY_IN_SECONDS );
        }

        $context = array(
            'device' => $this->resolve_device( (string) $request->get_param('device') ),
            'source' => sanitize_key( (string) $request->get_param('source') ),
        );

        Funnel_Analytics::record_step( $step, $context, $date );

        return $this->success_response( array( 'recorded' => $step ) );
    }


    /**
     * Normalise the device hint, falling back to a server-side guess.
     *
     * @since 6.0.0
     * @param string $hint Client-provided device hint.
     * @return string desktop|mobile|tablet
     */
    private function resolve_device( $hint ) {
        $hint = sanitize_key( $hint );

        if ( in_array( $hint, array( 'desktop', 'mobile', 'tablet' ), true ) ) {
            return $hint;
        }

        return wp_is_mobile() ? 'mobile' : 'desktop';
    }
}
