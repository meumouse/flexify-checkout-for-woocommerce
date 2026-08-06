<?php

namespace MeuMouse\Flexify_Checkout\Recovery_Carts\Cron;

use MeuMouse\Flexify_Checkout\Recovery_Carts\Admin\Admin;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Placeholders;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Hooks;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Opt_Out;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Scheduler_Manager;
use MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Queue_Processor;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handle Cron jobs
 * 
 * @since 1.0.0
 * @version 1.4.0
 * @package MeuMouse\Flexify_Checkout\Recovery_Carts\Cron
 * @author MeuMouse.com
 */
class Recovery_Handler {

    /**
     * Get debug mode
     * 
     * @since 1.3.0
     * @return bool
     */
    public static $debug_mode = FC_RECOVERY_CARTS_DEBUG_MODE;
   
    /**
     * Construct function
     *
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function __construct() {
        // check for abandoned carts
        add_action( 'wp_loaded', array( $this, 'check_abandoned_carts' ) );

        if ( Scheduler_Manager::is_php_cron_enabled() ) {
            add_action( 'wp_loaded', array( __CLASS__, 'maybe_run_queue' ), 1 );
        }

        // start recovery carts
        add_action( 'Flexify_Checkout/Recovery_Carts/Cart_Abandoned', array( $this, 'init_follow_up_events' ), 10, 1 );

        // Hook into WordPress to check for cart recovery link on page load.
        // Priority 1 ensures we run before WooCommerce/Flexify Checkout redirects
        // an empty /checkout/ to /cart/, which would drop the recovery_cart query arg.
        add_action( 'template_redirect', array( '\MeuMouse\Flexify_Checkout\Recovery_Carts\Core\Helpers', 'maybe_restore_cart' ), 1 );

        // Hook to handle the scheduled follow-up messages
        add_action( 'fcrc_send_follow_up_message', array( $this, 'send_follow_up_message_callback' ), 10, 4 );

        // Hook to handle the scheduled final cart status check
        add_action( 'fcrc_check_final_cart_status', array( $this, 'check_final_cart_status_callback' ), 10, 2 );

        // Fallback queue runner for PHP-Cron when wp-cron.php is triggered externally.
        add_action( 'fcrc_dispatch_queue', array( '\MeuMouse\Flexify_Checkout\Recovery_Carts\Cron\Queue_Processor', 'dispatch_due_events' ) );

        Scheduler_Manager::schedule_queue_runner();
    }


    /**
     * Trigger the queue processor on regular requests when PHP Cron is active.
     *
     * @since 1.3.2
     * @return void
     */
    public static function maybe_run_queue() {
        Queue_Processor::dispatch_due_events();
    }


    /**
     * Checks for abandoned carts by verifying last ping time
     *
     * @since 1.0.0
     * @version 1.3.5
     * @return void
     */
    public function check_abandoned_carts() {
        if ( self::$debug_mode ) {
            error_log( '[Recovery_Handler] check_abandoned_carts() called at ' . current_time('Y-m-d H:i:s') );
        }

        $time_limit_seconds = Helpers::get_abandonment_time_seconds();
        $current_time = current_time( 'timestamp', true );
        $time_threshold = $current_time - ( $time_limit_seconds + 30 ); // add 30 seconds to account for any time differences

        if ( self::$debug_mode ) {
            error_log( '[Recovery_Handler] Time limit seconds: ' . $time_limit_seconds );
            error_log( '[Recovery_Handler] Current timestamp: ' . $current_time );
            error_log( '[Recovery_Handler] Time threshold: ' . $time_threshold );
            error_log( '[Recovery_Handler] Looking for carts older than: ' . date('Y-m-d H:i:s', $time_threshold) );
        }

        $query = new \WP_Query( array(
            'post_type' => 'fc-recovery-carts',
            'post_status' => array( 'shopping' ),
            'posts_per_page' => -1, // get all posts
            'meta_query' => array(
                array(
                    'key' => '_fcrc_cart_updated_time',
                    'value' => $time_threshold,
                    'compare' => '<',
                    'type' => 'NUMERIC',
                ),
            ),
        ));

        if ( self::$debug_mode ) {
            error_log( '[Recovery_Handler] Found ' . $query->found_posts . ' shopping carts to check' );
        }

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $cart_id = get_the_ID();
                $current_status = get_post_status( $cart_id );

                if ( self::$debug_mode ) {
                    error_log( '[Recovery_Handler] Checking cart ID: ' . $cart_id . ' with status: ' . $current_status );
                }

                if ( $current_status !== 'shopping' ) {
                    if ( self::$debug_mode ) {
                        error_log( '[Recovery_Handler] Cart ID ' . $cart_id . ' status is not shopping (' . $current_status . '). Skipping.' );
                    }

                    continue;
                }

                // Get the last ping time
                $last_ping = get_post_meta( $cart_id, '_fcrc_cart_updated_time', true );
                $last_ping = intval( $last_ping );

                if ( self::$debug_mode ) {
                    error_log( '[Recovery_Handler] Cart ID ' . $cart_id . ' last ping: ' . $last_ping . ' (' . date('Y-m-d H:i:s', $last_ping) . ')' );
                    error_log( '[Recovery_Handler] Comparing: ' . $last_ping . ' < ' . $time_threshold . ' ?' );
                }

                // Check if cart should be marked as abandoned
                if ( empty( $last_ping ) || $last_ping < $time_threshold ) {
                    // Update abandoned time
                    update_post_meta( $cart_id, '_fcrc_abandoned_time', $current_time );

                    // Update status to abandoned
                    wp_update_post( array(
                        'ID' => $cart_id,
                        'post_status' => 'abandoned',
                    ));

                    if ( self::$debug_mode ) {
                        error_log( '[Recovery_Handler] Cart marked as abandoned: ' . $cart_id . ' | Last ping: ' . $last_ping . ' (' . date('Y-m-d H:i:s', $last_ping) . ')' );
                        error_log( '[Recovery_Handler] Abandonment time threshold: ' . date('Y-m-d H:i:s', $time_threshold) );
                    }

                    /**
                     * Fire hook when a cart is abandoned
                     *
                     * @since 1.0.0
                     * @param int $cart_id
                     */
                    do_action( 'Flexify_Checkout/Recovery_Carts/Cart_Abandoned', $cart_id );
                } else {
                    if ( self::$debug_mode ) {
                        error_log( '[Recovery_Handler] Cart ID ' . $cart_id . ' still active. Last ping is within threshold.' );
                    }
                }
            }
        } else {
            if ( self::$debug_mode ) {
                error_log( '[Recovery_Handler] No shopping carts found to check for abandonment.' );
            }
        }

        wp_reset_postdata();
        
        if ( self::$debug_mode ) {
            error_log( '[Recovery_Handler] check_abandoned_carts() completed.' );
        }
    }


    /**
     * Schedules follow-up messages based on admin settings
     *
     * @since 1.0.0
     * @version 1.4.1
     * @param int $cart_id | The abandoned cart ID
     * @return void
     */
    public function init_follow_up_events( $cart_id ) {
        // Freemium gate: capturing and tracking carts is free, but the automatic
        // follow-up sending requires Pro. Bail before scheduling anything.
        if ( ! Helpers::can_send_recovery_messages() ) {
            return;
        }

        $follow_up_events = Admin::get_setting('follow_up_events');

        // check if has follow up events
        if ( ! $follow_up_events || ! is_array( $follow_up_events ) ) {
            return;
        }

        // Respect opt-out: never schedule follow-ups for a suppressed contact.
        if ( Opt_Out::is_cart_suppressed( $cart_id ) ) {
            if ( self::$debug_mode ) {
                error_log( '[Recovery_Handler] Cart ID ' . $cart_id . ' contact opted out. No follow-up scheduled.' );
            }

            return;
        }

        $user_phone = get_post_meta( $cart_id, '_fcrc_cart_phone', true );
        $user_email = get_post_meta( $cart_id, '_fcrc_cart_email', true );

        // Without any contact information there is no channel we can reach the
        // customer on, so there is nothing to schedule. We intentionally do NOT
        // require BOTH phone AND email here: a WhatsApp-only follow-up only needs
        // a phone, and an email-only follow-up only needs an email. The per-event
        // channel check below decides whether each event is actually deliverable.
        if ( empty( $user_phone ) && empty( $user_email ) ) {
            if ( self::$debug_mode ) {
                error_log( '[Recovery_Handler] No phone or email for cart ID ' . $cart_id . '. No follow-up scheduled.' );
            }

            return;
        }

        $current_time = current_time( 'timestamp', true );

        if ( $this->should_block_follow_up_for_recent_purchase( $cart_id ) ) {
            if ( self::$debug_mode ) {
                error_log( '[Recovery_Handler] Follow-up blocked due to recent purchase for cart ID: ' . $cart_id );
            }

            return;
        }

        // collect event keys already delivered to this cart, so we don't resend
        // them when the cart is re-abandoned after the user resumed it.
        $already_sent_events = $this->get_already_sent_event_keys( $cart_id );

        // iterate for each follow up event
        foreach ( $follow_up_events as $event_key => $event_data ) {
            // check if follow up event is enabled
            if ( ! isset( $event_data['enabled'] ) || $event_data['enabled'] !== 'yes' ) {
                continue;
            }

            // events delegated to a Joinotify workflow are not scheduled by the
            // engine — the already-fired "Cart_Abandoned" action drives the
            // workflow, which owns its own timing and delivery. This prevents
            // duplicate messages in hybrid mode.
            if ( $this->event_is_delegated_to_workflow( $event_data ) ) {
                if ( self::$debug_mode ) {
                    error_log( '[Recovery_Handler] Skipping follow-up event "' . $event_key . '" for cart ID ' . $cart_id . ' — delegated to a Joinotify workflow.' );
                }

                continue;
            }

            // skip events that were already sent to this cart in a previous abandonment cycle
            if ( in_array( $event_key, $already_sent_events, true ) ) {
                if ( self::$debug_mode ) {
                    error_log( '[Recovery_Handler] Skipping follow-up event "' . $event_key . '" for cart ID ' . $cart_id . ' — already sent previously.' );
                }

                continue;
            }

            // skip events whose enabled channel(s) have no usable contact info
            // (e.g. a WhatsApp-only event when the cart captured no phone number).
            if ( ! $this->event_has_deliverable_channel( $event_data, $user_phone, $user_email ) ) {
                if ( self::$debug_mode ) {
                    error_log( '[Recovery_Handler] Skipping follow-up event "' . $event_key . '" for cart ID ' . $cart_id . ' — no contact info for its enabled channel(s).' );
                }

                continue;
            }

            // get delay time for event
            $delay = Helpers::convert_to_seconds( $event_data['delay_time'], $event_data['delay_type'] );

            if ( $delay ) {
                $event_timestamp = $current_time + $delay;

                // Check if the event has a send window configured
                if ( ! empty( $event_data['send_window'] ) && ! empty( $event_data['send_window']['start_time'] ) && ! empty( $event_data['send_window']['end_time'] ) ) {
                    // Calculate the next available window time
                    $window_time = $this->get_next_available_window_time( $event_data, $event_timestamp );
                    
                    if ( $window_time['next_window'] !== null ) {
                        // Reschedule to the next available window
                        $event_timestamp = $window_time['next_window'];
                    }
                }

                Scheduler_Manager::schedule_single_event(
                    $event_timestamp,
                    'fcrc_send_follow_up_message',
                    array(
                        'cart_id'    => intval( $cart_id ),
                        'event_key'  => sanitize_key( $event_key ),
                    ),
                    array(
                        '_fcrc_cart_id' => intval( $cart_id ),
                        '_fcrc_follow_up_event_key' => sanitize_key( $event_key ),
                    )
                );
            }
        }
    }


    /**
     * Determine whether a follow-up event can be delivered with the available
     * contact information.
     *
     * A WhatsApp channel only requires a phone number; an email channel only
     * requires an email address. This avoids aborting the whole follow-up
     * schedule just because one channel's contact field is missing — the
     * previous behaviour required both phone AND email for every event, so a
     * store that only captures phone numbers never scheduled any follow-up.
     *
     * @since 1.4.1
     * @param array  $event_data | The follow-up event settings.
     * @param string $user_phone | The cart's captured phone number.
     * @param string $user_email | The cart's captured email address.
     * @return bool
     */
    private function event_has_deliverable_channel( $event_data, $user_phone, $user_email ) {
        $channels = isset( $event_data['channels'] ) && is_array( $event_data['channels'] ) ? $event_data['channels'] : array();

        $whatsapp_enabled = isset( $channels['whatsapp'] ) && $channels['whatsapp'] === 'yes';
        $email_enabled    = isset( $channels['email'] ) && $channels['email'] === 'yes';

        // WhatsApp follow-ups only need a phone number.
        if ( $whatsapp_enabled && ! empty( $user_phone ) ) {
            return true;
        }

        // Email follow-ups only need an email address.
        if ( $email_enabled && ! empty( $user_email ) ) {
            return true;
        }

        return false;
    }


    /**
     * Whether a follow-up event is delegated to a Joinotify workflow instead of
     * being sent by the built-in engine.
     *
     * In hybrid mode each event routes either to the Flexify engine ('engine',
     * the default) or to a Joinotify visual workflow ('joinotify_workflow'). For
     * the latter the engine must not schedule or send anything: the
     * "Flexify_Checkout/Recovery_Carts/*" action hooks already fired drive the
     * workflow, which owns delivery. This guardrail prevents duplicate messages.
     *
     * If the event is set to workflow delivery but Joinotify is not available,
     * we fall back to the engine so the follow-up is not silently dropped (there
     * is no workflow that could double-send in that case).
     *
     * @since 6.0.0
     * @param array $event_data | The follow-up event settings.
     * @return bool
     */
    private function event_is_delegated_to_workflow( $event_data ) {
        $mode = isset( $event_data['delivery_mode'] ) ? $event_data['delivery_mode'] : 'engine';

        if ( $mode !== 'joinotify_workflow' ) {
            return false;
        }

        // Only delegate when Joinotify can actually run the workflow.
        return function_exists('joinotify_send_whatsapp_message_text');
    }


    /**
     * Sends a follow-up message based on the event
     *
     * @since 1.0.0
     * @version 1.4.0
     * @param int $cart_id | The abandoned cart ID
     * @param string $event_key | The follow-up event key
     * @param int $cron_post_id | The cron post ID
     * @return void
     */
    public function send_follow_up_message_callback( ...$raw_args ) {
        $args = $this->normalize_callback_arguments(
            $raw_args,
            array(
                'cart_id' => 0,
                'event_key' => '',
                'cron_post_id' => null,
                'force' => false,
            )
        );

        $cart_id = absint( $args['cart_id'] );
        $event_key = sanitize_key( $args['event_key'] );
        $cron_post_id = $args['cron_post_id'] ? absint( $args['cron_post_id'] ) : null;
        $force = ! empty( $args['force'] );

        if ( ! $cart_id || ! $event_key ) {
            return;
        }

        // Freemium gate: never dispatch without Pro, even for queued events left
        // over from an expired license. A manual send does not bypass licensing.
        if ( ! Helpers::can_send_recovery_messages() ) {
            if ( $cron_post_id ) {
                wp_delete_post( intval( $cron_post_id ), true );
            }

            return;
        }

        $settings = Admin::get_setting('follow_up_events');

        if ( ! isset( $settings[ $event_key ] ) ) {
            return;
        }

        $event = $settings[ $event_key ];

        // Respect opt-out: a contact may have unsubscribed after this message was
        // queued. Cancel the whole schedule and drop this event.
        if ( Opt_Out::is_cart_suppressed( $cart_id ) ) {
            Helpers::cancel_scheduled_follow_up_events( $cart_id );

            if ( $cron_post_id ) {
                wp_delete_post( intval( $cron_post_id ), true );
            }

            return;
        }

        // Guardrail: if the event was switched to Joinotify-workflow delivery
        // after this message had already been queued, drop it here so the engine
        // does not send a message the workflow is also responsible for. A forced
        // manual send still goes through.
        if ( ! $force && $this->event_is_delegated_to_workflow( $event ) ) {
            if ( $cron_post_id ) {
                wp_delete_post( intval( $cron_post_id ), true );
            }

            return;
        }

        if ( ! $force && $this->should_block_follow_up_for_recent_purchase( $cart_id ) ) {
            Helpers::cancel_scheduled_follow_up_events( $cart_id );

            if ( self::$debug_mode ) {
                error_log( '[Recovery_Handler] Follow-up cancelled due to recent purchase for cart ID: ' . $cart_id );
            }

            if ( $cron_post_id ) {
                wp_delete_post( intval( $cron_post_id ), true );
            }

            return;
        }

        $current_timestamp = current_time('timestamp');

        if ( ! $force && Helpers::maybe_cancel_followups_after_late_purchase( $cart_id ) ) {
            if ( $cron_post_id ) {
                wp_delete_post( intval( $cron_post_id ), true );
            }

            return;
        }

        $send_window_time = $this->get_next_available_window_time( $event, $current_timestamp );

        if ( ! $force && ! empty( $send_window_time['next_window'] ) && $send_window_time['next_window'] > $current_timestamp ) {
            Scheduler_Manager::schedule_single_event(
                $send_window_time['next_window'],
                'fcrc_send_follow_up_message',
                array(
                    'cart_id' => $cart_id,
                    'event_key' => $event_key,
                ),
                array(
                    '_fcrc_cart_id' => $cart_id,
                    '_fcrc_follow_up_event_key' => $event_key,
                )
            );

            if ( self::$debug_mode ) {
                error_log( '[Recovery_Handler] Evento reagendado para janela permitida. Horário atual fora da janela.' );
            }

            return;
        }

        $cart_data = get_post_meta( $cart_id );
        $phone = $cart_data['_fcrc_cart_phone'][0] ?? '';

        // get message with placeholders replaced
        $message = Placeholders::replace_placeholders( $event['message'], $cart_id, $event );

        // get receiver phone number
        $receiver = function_exists('joinotify_prepare_receiver') ? joinotify_prepare_receiver( $phone ) : $phone;

        // save notification data
        $sent_channels = array();

        $whatsapp_enabled = isset( $event['channels']['whatsapp'] ) && $event['channels']['whatsapp'] === 'yes';
        $email_enabled    = isset( $event['channels']['email'] ) && $event['channels']['email'] === 'yes';

        // send via WhatsApp if enabled and a phone is available
        if ( $whatsapp_enabled && ! empty( $phone ) ) {
            // send message
            $response_code = self::send_whatsapp_message( $receiver, $message );

            if ( 201 === $response_code ) {
                $sent_channels[] = 'whatsapp';
            }
        }

        // send via e-mail. When both channels are enabled, e-mail acts as a
        // fallback: it only goes out if WhatsApp was not delivered (no phone or a
        // failed send), avoiding messaging the same customer twice for one event.
        if ( $email_enabled && ( ! $whatsapp_enabled || empty( $sent_channels ) ) ) {
            $email = $cart_data['_fcrc_cart_email'][0] ?? '';

            if ( self::send_email_message( $cart_id, $email, $event, $message ) ) {
                $sent_channels[] = 'email';
            }
        }

        if ( empty( $sent_channels ) ) {
            return;
        }

        // get current history
        $notifications = get_post_meta( $cart_id, '_fcrc_notifications_sent', true );

        if ( ! is_array( $notifications ) ) {
            $notifications = array();
        }

        // add new notification data
        foreach ( $sent_channels as $channel ) {
            $notifications[] = array(
                'event_key' => sanitize_key( $event_key ),
                'channel' => sanitize_key( $channel ),
                'sent_at' => current_time('timestamp', true),
            );
        }

        // save notifications
        update_post_meta( $cart_id, '_fcrc_notifications_sent', $notifications );

        /**
         * Trigger webhook dispatch for follow up messages
         *
         * @since 1.3.2
         * @param int $cart_id | Cart ID
         * @param string $event_key | Follow up event key
         * @param string $message | Message sent
         * @param array $sent_channels | Channels used
         * @param array $event | Follow up settings
         */
        do_action( 'Flexify_Checkout/Recovery_Carts/Follow_Up_Message_Sent', $cart_id, $event_key, $message, $sent_channels, $event );

        if ( $cron_post_id ) {
            wp_delete_post( intval( $cron_post_id ), true );
        }
    }


    /**
     * Get the list of follow-up event keys already sent for a cart.
     *
     * Used to avoid re-sending the same follow-up when a cart is re-abandoned
     * after the lead resumed it (cart returns to 'shopping' then back to 'abandoned').
     *
     * @since 1.4.0
     * @param int $cart_id | The cart ID.
     * @return array<string>
     */
    private function get_already_sent_event_keys( $cart_id ) {
        $notifications = get_post_meta( $cart_id, '_fcrc_notifications_sent', true );

        if ( ! is_array( $notifications ) || empty( $notifications ) ) {
            return array();
        }

        $keys = array();

        foreach ( $notifications as $notification ) {
            if ( ! empty( $notification['event_key'] ) ) {
                $keys[] = (string) $notification['event_key'];
            }
        }

        return array_values( array_unique( $keys ) );
    }


    /**
     * Check if follow-ups should be blocked for a cart due to recent purchases.
     *
     * @since 1.4.0
     * @param int $cart_id | The abandoned cart ID
     * @return bool
     */
    private function should_block_follow_up_for_recent_purchase( $cart_id ) {
        $days = absint( Admin::get_setting('follow_up_purchase_block_days') );

        if ( $days <= 0 ) {
            return false;
        }

        if ( ! function_exists('wc_get_orders') ) {
            return false;
        }

        $email = sanitize_email( get_post_meta( $cart_id, '_fcrc_cart_email', true ) );
        $phone = trim( (string) get_post_meta( $cart_id, '_fcrc_cart_phone', true ) );

        if ( empty( $email ) && empty( $phone ) ) {
            return false;
        }

        $after_timestamp = current_time( 'timestamp', true ) - ( $days * DAY_IN_SECONDS );
        $order_query = array(
            'limit' => 1,
            'return' => 'ids',
            'status' => apply_filters( 'Flexify_Checkout/Should_Block_Purchases/Statuses', array( 'wc-processing', 'wc-completed' ) ),
            'date_created' => '>' . $after_timestamp,
        );

        if ( ! empty( $email ) ) {
            $orders_by_email = wc_get_orders( array_merge( $order_query, array( 'billing_email' => $email ) ) );

            if ( ! empty( $orders_by_email ) ) {
                return true;
            }
        }

        if ( ! empty( $phone ) ) {
            $orders_by_phone = wc_get_orders( array_merge( $order_query, array( 'billing_phone' => $phone ) ) );

            if ( ! empty( $orders_by_phone ) ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Get the next available window time for sending a follow-up message.
     *
     * @since 1.3.5
     * @param array $event | The follow-up event settings.
     * @param int   $current_timestamp | Current timestamp in the site's timezone.
     * @return array{
     *     next_window: int|null,
     * }
     */
    private function get_next_available_window_time( $event, $current_timestamp ) {
        $window_settings = $event['send_window'] ?? array();
        $start_time = $window_settings['start_time'] ?? '';
        $end_time = $window_settings['end_time'] ?? '';

        if ( empty( $start_time ) || empty( $end_time ) ) {
            return array( 'next_window' => null );
        }

        try {
            $timezone = wp_timezone();
            $now_datetime = new \DateTime( '@' . $current_timestamp );
            $now_datetime->setTimezone( $timezone );

            $start_parts = explode( ':', $start_time );
            $end_parts   = explode( ':', $end_time );

            if ( count( $start_parts ) < 2 || count( $end_parts ) < 2 ) {
                return array( 'next_window' => null );
            }

            list( $start_hour, $start_minute ) = array_map( 'intval', array_slice( $start_parts, 0, 2 ) );
            list( $end_hour, $end_minute ) = array_map( 'intval', array_slice( $end_parts, 0, 2 ) );

            $start_datetime = ( clone $now_datetime )->setTime( $start_hour, $start_minute, 0 );
            $end_datetime = ( clone $now_datetime )->setTime( $end_hour, $end_minute, 0 );

            if ( $end_datetime <= $start_datetime ) {
                $end_datetime->modify( '+1 day' );

                if ( $now_datetime < $start_datetime ) {
                    $start_datetime->modify( '-1 day' );
                }
            }

            if ( $now_datetime < $start_datetime ) {
                return array( 'next_window' => $start_datetime->getTimestamp() );
            }

            if ( $now_datetime > $end_datetime ) {
                $start_datetime->modify( '+1 day' );
                
                return array( 'next_window' => $start_datetime->getTimestamp() );
            }

            return array( 'next_window' => null );
        } catch ( \Exception $e ) {
            return array( 'next_window' => null );
        }
    }


    /**
     * Checks the final status of the abandoned cart after the last follow-up.
     *
     * @since 1.0.0
     * @version 1.3.2
     * @param int $cart_id The abandoned cart ID
     * @param int $cron_post_id | The cron post ID
     * @return void
     */
    public function check_final_cart_status_callback( ...$raw_args ) {
        $args = $this->normalize_callback_arguments(
            $raw_args,
            array(
                'cart_id' => 0,
                'cron_post_id' => null,
            )
        );

        $cart_id = absint( $args['cart_id'] );
        $cron_post_id = $args['cron_post_id'] ? absint( $args['cron_post_id'] ) : null;

        if ( ! $cart_id ) {
            return;
        }

        $cart_status = get_post_status( $cart_id );

        // if still abandoned after 1 hour, mark as "lost"
        if ( $cart_status === 'abandoned' ) {
            wp_update_post( array(
                'ID' => $cart_id,
                'post_status' => 'lost',
            ));

            if ( self::$debug_mode ) {
                error_log( 'Cart marked as lost: ' . $cart_id );
            }

            /**
             * Fire a hook when an order is considered lost
             *
             * @since 1.0.0
             * @param int $cart_id | The abandoned cart ID
             */
            do_action( 'Flexify_Checkout/Recovery_Carts/Cart_Lost', $cart_id );
        }

        if ( $cron_post_id ) {
            wp_delete_post( intval( $cron_post_id ), true );
        }
    }


    /**
     * Normalizes callback arguments passed by WP Cron or PHP Cron.
     *
     * @since 1.3.2
     * @param array $raw_args | Arguments forwarded by the scheduler.
     * @param array $defaults | Default values keyed by argument name.
     * @return array Normalized associative array of arguments.
     */
    private function normalize_callback_arguments( array $raw_args, array $defaults ) {
        if ( empty( $raw_args ) ) {
            return $defaults;
        }

        if ( 1 === count( $raw_args ) && is_array( $raw_args[0] ) ) {
            $raw_args = $raw_args[0];
        }

        $normalized = array();
        $index = 0;

        foreach ( $defaults as $key => $default ) {
            if ( array_key_exists( $key, $raw_args ) ) {
                $normalized[ $key ] = $raw_args[ $key ];
            } elseif ( array_key_exists( $index, $raw_args ) ) {
                $normalized[ $key ] = $raw_args[ $index ];
            } else {
                $normalized[ $key ] = $default;
            }

            $index++;
        }

        return $normalized;
    }


    /**
     * Sends a WhatsApp message with Joinotify
     *
     * @since 1.0.0
     * @version 1.4.0
     * @param string $receiver | The recipient's phone number
     * @param string $message | The message to send
     */
    public static function send_whatsapp_message( $receiver, $message ) {
        if ( function_exists('joinotify_send_whatsapp_message_text') ) {
            $sender = Admin::get_setting('joinotify_sender_phone');

            return joinotify_send_whatsapp_message_text( $sender, $receiver, $message );
        }

        return null;
    }


    /**
     * Sends a follow-up message as an e-mail.
     *
     * @since 6.0.0
     * @param int    $cart_id | The recovery cart post ID.
     * @param string $email   | The recipient e-mail address.
     * @param array  $event   | The follow-up event settings.
     * @param string $message | The message body (placeholders already replaced).
     * @return bool True when the e-mail was accepted for delivery.
     */
    public static function send_email_message( $cart_id, $email, $event, $message ) {
        if ( empty( $email ) || ! is_email( $email ) ) {
            return false;
        }

        return \MeuMouse\Flexify_Checkout\Recovery_Carts\Integrations\Email::send( $email, $event, $message, $cart_id );
    }


    /**
     * Detect if user is restoring an abandoned cart
     *
     * @since 1.0.0
     * @version 1.3.2
     * @return void
     */
    public static function detect_cart_recovery() {
        if ( is_admin() ) {
            return;
        }
        
        $cart_id = Helpers::get_current_cart_id();
    
        if ( ! $cart_id ) {
            return;
        }
    
        // check current status
        $cart_status = get_post_status( $cart_id );
    
        // ignore already recovered or purchased carts
        if ( in_array( $cart_status, array( 'recovered', 'purchased' ), true ) ) {
            return;
        }
    
        // only proceed if status is abandoned
        if ( $cart_status !== 'abandoned' ) {
            return;
        }
    
        // check if there was recent activity
        $last_ping = (int) get_post_meta( $cart_id, '_fcrc_cart_updated_time', true );
        $time_limit_seconds = Helpers::get_abandonment_time_seconds();
        $time_threshold = current_time('timestamp', true) - $time_limit_seconds;
    
        // if last ping is before the time threshold, it's considered abandoned
        if ( $last_ping < $time_threshold ) {
            return;
        }
    
        // cancel scheduled events
        Hooks::cancel_scheduled_cart_process( $cart_id );
    
        if ( self::$debug_mode ) {
            error_log( 'Cart resumed: ' . $cart_id );
        }

        // set flag for prevent duplicate carts
        WC()->session->set( 'fcrc_active_cart', true );
    
        // update status
        wp_update_post( array(
            'ID' => $cart_id,
            'post_status' => 'shopping',
        ));
    
        // clean up
        delete_post_meta( $cart_id, '_fcrc_abandoned_time' );
    }
    

    /**
     * Compare current cart items with abandoned cart items
     *
     * @since 1.0.0
     * @param array $current_cart_items | The current WooCommerce cart items
     * @param array $saved_cart_items | The saved abandoned cart items
     * @return bool
     */
    public static function compare_cart_items( $current_cart_items, $saved_cart_items ) {
        $current_cart = array_map( function( $item ) {
            return array(
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            );
        }, $current_cart_items );

        $saved_cart = array_map( function( $item ) {
            return array(
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            );
        }, $saved_cart_items );

        return $current_cart === $saved_cart;
    }
}