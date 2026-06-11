<?php

namespace GO;

use DateTimeImmutable;
use DateTimeZone;

if ( class_exists( '\\GO\\Scheduler', false ) ) {
    return;
}

/**
 * Lightweight fallback implementation of the peppeocchi/php-cron-scheduler API.
 *
 * This stub is only loaded when the third-party library is not available, which
 * can happen in development environments where Composer dependencies are not
 * installed. It offers the tiny subset of features used by the plugin: running
 * callback jobs every minute or every hour.
 *
 * When the real package is installed this file is ignored because the
 * Scheduler class already exists.
 */
class Scheduler {
    /**
     * @var array<int, SchedulerJob>
     */
    protected $jobs = array();

    /**
     * @var DateTimeZone
     */
    protected $timezone;

    public function __construct() {
        $this->timezone = new DateTimeZone( date_default_timezone_get() );
    }

    /**
     * Mimic the original API. Accepts either a DateTimeZone instance or a
     * timezone string.
     *
     * @param DateTimeZone|string $timezone
     * @return $this
     */
    public function setTimeZone( $timezone ) {
        if ( $timezone instanceof DateTimeZone ) {
            $this->timezone = $timezone;
        } elseif ( is_string( $timezone ) ) {
            $this->timezone = new DateTimeZone( $timezone );
        }

        return $this;
    }

    /**
     * Registers a callable job.
     *
     * @param callable $callable
     * @return SchedulerJob
     */
    public function call( $callable ) {
        $job = new SchedulerJob( $callable, $this->timezone );
        $this->jobs[] = $job;

        return $job;
    }

    /**
     * Execute due jobs.
     */
    public function run() {
        foreach ( $this->jobs as $job ) {
            if ( $job->is_due() ) {
                $job->execute();
            }
        }
    }
}

class SchedulerJob {
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var DateTimeZone
     */
    protected $timezone;

    /**
     * @var int
     */
    protected $interval = 60;

    /**
     * @var string
     */
    protected $storage_key = '';

    /**
     * @var string
     */
    protected $identifier;

    public function __construct( $callback, DateTimeZone $timezone ) {
        $this->callback = $callback;
        $this->timezone = $timezone;
        $this->identifier = $this->identify_callback( $callback );
        $this->set_interval( $this->interval );
    }

    /**
     * Run every minute.
     *
     * @return $this
     */
    public function everyMinute() {
        return $this->set_interval( 60 );
    }

    /**
     * Run every hour.
     *
     * @return $this
     */
    public function hourly() {
        return $this->set_interval( 3600 );
    }

    /**
     * Determine if the job should run. The stub stores the last run timestamp
     * in a transient option so multiple executions within the same window are
     * avoided.
     */
    public function is_due() {
        if ( ! function_exists('get_option') ) {
            return true;
        }

        $last_run = get_option( $this->storage_key, 0 );
        $now = $this->current_timestamp();

        return ( $now - intval( $last_run ) ) >= $this->interval;
    }

    /**
     * Execute the callback and store the run timestamp.
     */
    public function execute() {
        if ( function_exists('update_option') ) {
            update_option( $this->storage_key, $this->current_timestamp(), false );
        }

        call_user_func( $this->callback );
    }

    /**
     * Get current timestamp in the configured timezone.
     *
     * @return int
     */
    protected function current_timestamp() {
        return ( new DateTimeImmutable( 'now', $this->timezone ) )->getTimestamp();
    }

    /**
     * Set the execution interval and refresh the storage key.
     *
     * @param int $seconds
     * @return $this
     */
    protected function set_interval( $seconds ) {
        $this->interval = intval( $seconds );
        $this->storage_key = 'fcrc_scheduler_stub_' . md5( $this->identifier . '|' . $this->interval );

        return $this;
    }

    /**
     * Try to generate a deterministic identifier for the callback.
     *
     * @param callable $callback
     * @return string
     */
    protected function identify_callback( $callback ) {
        if ( is_string( $callback ) ) {
            return $callback;
        }

        if ( is_array( $callback ) ) {
            $object = is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0];
            return $object . '::' . $callback[1];
        }

        if ( $callback instanceof \Closure ) {
            $reflection = new \ReflectionFunction( $callback );
            return $reflection->getFileName() . ':' . $reflection->getStartLine();
        }

        return spl_object_hash( (object) $callback );
    }
}