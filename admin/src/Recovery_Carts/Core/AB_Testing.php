<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * A/B testing for follow-up messages.
 *
 * A follow-up event can carry an "ab_test" block with alternate message
 * variants. The event's own "message" is always variant A; each configured
 * alternate becomes B, C, ... The variant sent to a given cart is chosen
 * deterministically from the cart id, so the same recipient always receives the
 * same message while variants are spread evenly across carts. Which variant was
 * sent is recorded in the cart's "_fcrc_notifications_sent" history (the
 * "variant" key), which this class then aggregates into a per-variant
 * sent/recovered report for the Analytics page.
 *
 * Structure of the "ab_test" block stored in each follow-up event:
 *
 *     'ab_test' => array(
 *         'enabled'  => 'yes' | 'no',
 *         'variants' => array(
 *             array( 'message' => '...', 'email_subject' => '...' ),
 *             ...
 *         ),
 *     )
 *
 * @since 6.0.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Core
 * @author MeuMouse.com
 */
class AB_Testing {

    /**
     * Recovery-cart statuses counted as a recovered conversion in the report.
     *
     * @since 6.0.0
     * @var array<int,string>
     */
    const RECOVERED_STATUSES = array( 'recovered', 'purchased' );


    /**
     * Whether A/B testing is active for an event.
     *
     * Requires the block to be enabled and at least one non-empty alternate
     * variant besides the base message.
     *
     * @since 6.0.0
     * @param array $event | The follow-up event settings.
     * @return bool
     */
    public static function is_enabled( $event ) {
        if ( ! is_array( $event ) || empty( $event['ab_test'] ) || ! is_array( $event['ab_test'] ) ) {
            return false;
        }

        if ( ! isset( $event['ab_test']['enabled'] ) || 'yes' !== $event['ab_test']['enabled'] ) {
            return false;
        }

        foreach ( self::raw_variants( $event ) as $variant ) {
            if ( is_array( $variant ) && isset( $variant['message'] ) && '' !== trim( (string) $variant['message'] ) ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Build the full, ordered variant list for an event.
     *
     * The first entry (id "a") is always the event's own base message. Enabled
     * alternate variants follow as "b", "c", ... Empty alternates are skipped.
     *
     * @since 6.0.0
     * @param array $event | The follow-up event settings.
     * @return array<int,array{id:string,label:string,message:string,email_subject:string}>
     */
    public static function get_variants( $event ) {
        $base = array(
            'id'            => 'a',
            'label'         => 'A',
            'message'       => isset( $event['message'] ) ? (string) $event['message'] : '',
            'email_subject' => isset( $event['email_subject'] ) ? (string) $event['email_subject'] : '',
        );

        if ( ! self::is_enabled( $event ) ) {
            return array( $base );
        }

        $variants = array( $base );
        $index = 1;

        foreach ( self::raw_variants( $event ) as $variant ) {
            if ( ! is_array( $variant ) ) {
                continue;
            }

            $message = isset( $variant['message'] ) ? (string) $variant['message'] : '';

            if ( '' === trim( $message ) ) {
                continue;
            }

            $letter = self::index_to_letter( $index );

            $variants[] = array(
                'id'            => $letter,
                'label'         => strtoupper( $letter ),
                'message'       => $message,
                'email_subject' => isset( $variant['email_subject'] ) && '' !== trim( (string) $variant['email_subject'] )
                    ? (string) $variant['email_subject']
                    : $base['email_subject'],
            );

            $index++;
        }

        return $variants;
    }


    /**
     * Pick the variant to send for a given cart.
     *
     * Deterministic per cart (same recipient always gets the same variant) and
     * evenly distributed across carts by using the cart id modulo the variant
     * count. When A/B testing is off, the base variant is returned.
     *
     * @since 6.0.0
     * @param array $event | The follow-up event settings.
     * @param int   $cart_id | The recovery cart post ID.
     * @return array{id:string,label:string,message:string,email_subject:string}
     */
    public static function pick_variant( $event, $cart_id ) {
        $variants = self::get_variants( $event );
        $count = count( $variants );

        if ( $count <= 1 ) {
            return $variants[0];
        }

        $index = absint( $cart_id ) % $count;

        return $variants[ $index ];
    }


    /**
     * Build the per-variant A/B performance report for the Analytics page.
     *
     * Iterates recovery carts created within the period that recorded at least
     * one notification, then tallies, per follow-up event and variant, how many
     * carts received that variant and how many of those were recovered. Only
     * events that actually ran more than one variant are returned.
     *
     * @since 6.0.0
     * @param int $days | Period length in days.
     * @return array<int,array{
     *     event_key:string,
     *     title:string,
     *     variants:array<int,array{id:string,label:string,sent:int,recovered:int,rate:float}>
     * }>
     */
    public static function get_report( $days ) {
        $days = max( 1, (int) $days );
        $start = (int) current_time( 'timestamp', true ) - ( $days * DAY_IN_SECONDS );

        $query = new \WP_Query( array(
            'post_type'      => 'fc-recovery-carts',
            'post_status'    => array( 'lead', 'shopping', 'abandoned', 'order_abandoned', 'recovered', 'lost', 'purchased' ),
            'posts_per_page' => (int) apply_filters( 'Flexify_Checkout/Recovery_Carts/AB_Report/Cap', 5000 ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'date_query'     => array(
                array(
                    'after'     => gmdate( 'Y-m-d H:i:s', $start ),
                    'inclusive' => true,
                ),
            ),
            'meta_query'     => array(
                array(
                    'key'     => '_fcrc_notifications_sent',
                    'compare' => 'EXISTS',
                ),
            ),
        ) );

        $events = array();

        foreach ( $query->posts as $cart_id ) {
            $notifications = get_post_meta( $cart_id, '_fcrc_notifications_sent', true );

            if ( ! is_array( $notifications ) || empty( $notifications ) ) {
                continue;
            }

            $is_recovered = in_array( get_post_status( $cart_id ), self::RECOVERED_STATUSES, true );

            // Credit each (event, variant) at most once per cart, even when the
            // same message went out on multiple channels.
            $counted = array();

            foreach ( $notifications as $notification ) {
                if ( empty( $notification['event_key'] ) ) {
                    continue;
                }

                $event_key = (string) $notification['event_key'];
                $variant = isset( $notification['variant'] ) ? (string) $notification['variant'] : 'a';
                $pair = $event_key . '|' . $variant;

                if ( isset( $counted[ $pair ] ) ) {
                    continue;
                }

                $counted[ $pair ] = true;

                if ( ! isset( $events[ $event_key ] ) ) {
                    $events[ $event_key ] = array();
                }

                if ( ! isset( $events[ $event_key ][ $variant ] ) ) {
                    $events[ $event_key ][ $variant ] = array( 'sent' => 0, 'recovered' => 0 );
                }

                $events[ $event_key ][ $variant ]['sent']++;

                if ( $is_recovered ) {
                    $events[ $event_key ][ $variant ]['recovered']++;
                }
            }
        }

        return self::format_report( $events );
    }


    /**
     * Shape the aggregated tallies into the report payload.
     *
     * @since 6.0.0
     * @param array $events | event_key => variant_id => { sent, recovered }.
     * @return array
     */
    private static function format_report( $events ) {
        $settings = \MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin::get_setting( 'follow_up_events' );
        $settings = is_array( $settings ) ? $settings : array();

        $report = array();

        foreach ( $events as $event_key => $variants ) {
            // Only report events that actually ran an A/B test (>= 2 variants).
            if ( count( $variants ) < 2 ) {
                continue;
            }

            ksort( $variants );

            $rows = array();

            foreach ( $variants as $variant_id => $totals ) {
                $sent = (int) $totals['sent'];
                $recovered = (int) $totals['recovered'];

                $rows[] = array(
                    'id'        => (string) $variant_id,
                    'label'     => strtoupper( (string) $variant_id ),
                    'sent'      => $sent,
                    'recovered' => $recovered,
                    'rate'      => $sent > 0 ? round( ( $recovered / $sent ) * 100, 1 ) : 0.0,
                );
            }

            $title = isset( $settings[ $event_key ]['title'] ) ? (string) $settings[ $event_key ]['title'] : $event_key;

            $report[] = array(
                'event_key' => (string) $event_key,
                'title'     => $title,
                'variants'  => $rows,
            );
        }

        return $report;
    }


    /**
     * Read the raw alternate-variant list from an event.
     *
     * @since 6.0.0
     * @param array $event | The follow-up event settings.
     * @return array
     */
    private static function raw_variants( $event ) {
        if ( isset( $event['ab_test']['variants'] ) && is_array( $event['ab_test']['variants'] ) ) {
            return $event['ab_test']['variants'];
        }

        return array();
    }


    /**
     * Map a zero-based-ish index to a variant letter (1 => "b", 2 => "c", ...).
     *
     * @since 6.0.0
     * @param int $index | The variant index (1 for the first alternate).
     * @return string
     */
    private static function index_to_letter( $index ) {
        return chr( ord( 'a' ) + ( (int) $index % 26 ) );
    }
}
