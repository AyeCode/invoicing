<?php
/**
 * Daily maintenance class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Daily maintenance class.
 *
 */
class GetPaid_Daily_Maintenance {

	/**
	 * Class constructor.
	 */
	public function __construct(){

		// Clear deprecated events.
		add_action( 'wp', array( $this, 'maybe_clear_deprecated_events' ) );

		// (Maybe) schedule a cron that runs daily.
		add_action( 'wp', array( $this, 'maybe_create_scheduled_event' ) );

		// Fired everyday at 7 a.m (this might vary for sites with few visitors)
		add_action( 'getpaid_daily_maintenance', array( $this, 'log_cron_run' ) );
		add_action( 'getpaid_daily_maintenance', array( $this, 'backwards_compat' ) );
		add_action( 'getpaid_daily_maintenance', array( $this, 'maybe_expire_subscriptions' ) );
		add_action( 'getpaid_daily_maintenance', array( $this, 'check_renewing_subscriptions' ) );
		add_action( 'getpaid_daily_maintenance', array( $this, 'maybe_update_geoip_databases' ) );

	}

	/**
	 * Schedules a cron to run every day at 7 a.m
	 *
	 */
	public function maybe_create_scheduled_event() {

		if ( ! wp_next_scheduled( 'getpaid_daily_maintenance' ) ) {
			$timestamp = strtotime( 'tomorrow 07:00:00', current_time( 'timestamp' ) );
			wp_schedule_event( $timestamp, 'daily', 'getpaid_daily_maintenance' );
		}

	}

	/**
	 * Clears deprecated events.
	 *
	 */
	public function maybe_clear_deprecated_events() {

		if ( ! get_option( 'wpinv_cleared_old_events' ) ) {
			wp_clear_scheduled_hook( 'wpinv_register_schedule_event_twicedaily' );
			wp_clear_scheduled_hook( 'wpinv_register_schedule_event_daily' );
			update_option( 'wpinv_cleared_old_events', 1 );
		}

	}

	/**
	 * Fires the old hook for backwards compatibility.
	 *
	 */
	public function backwards_compat() {
		do_action( 'wpinv_register_schedule_event_daily' );
	}

	/**
	 * Checks for subscriptions that are scheduled to renew.
	 *
	 */
	public function check_renewing_subscriptions() {

		// Fetch subscriptions that expire today.
		$args  = array(
			'number'             => -1,
			'count_total'        => false,
			'status'             => 'trialling active',
			'date_expires_query' => array(
				array(
					'year'          => date( 'Y', current_time( 'timestamp' ) ),
					'month'         => date( 'n', current_time( 'timestamp' ) ),
					'day'           => date( 'j', current_time( 'timestamp' ) ),
					'compare'       => '=',
				),
			),
		);

		$subscriptions = new GetPaid_Subscriptions_Query( $args );

		foreach ( $subscriptions->get_results() as $subscription ) {

			/** @var WPInv_Subscription $subscription */
			if ( $subscription->is_last_renewal() ) {
				$subscription->complete();
			} else {
				do_action( 'getpaid_should_renew_subscription', $subscription );
			}

		}

	}

	/**
	 * Expires expired subscriptions.
	 *
	 */
	public function maybe_expire_subscriptions() {

		// Fetch expired subscriptions (skips those that expire today).
		$args  = array(
			'number'             => -1,
			'count_total'        => false,
			'status'             => 'trialling active failing cancelled',
			'date_expires_query' => array(
				'before'    => 'yesterday',
				'inclusive' => false,
			),
		);

		$subscriptions = new GetPaid_Subscriptions_Query( $args );

		foreach ( $subscriptions->get_results() as $subscription ) {
			if ( apply_filters( 'getpaid_daily_maintenance_should_expire_subscription', false, $subscription ) ) {
				$subscription->set_status( 'expired' );
				$subscription->save();
			}
		}

	}

	/**
	 * Logs cron runs.
	 *
	 */
	public function log_cron_run() {
		wpinv_error_log( 'GetPaid Daily Cron', false );
	}

	/**
	 * Updates GeoIP databases.
	 *
	 */
	public function maybe_update_geoip_databases() {
		$updated = get_transient( 'getpaid_updated_geoip_databases' );

		if ( false === $updated ) {
			set_transient( 'getpaid_updated_geoip_databases', 1, 15 * DAY_IN_SECONDS );
			do_action( 'getpaid_update_geoip_databases' );
		}

	}

}
