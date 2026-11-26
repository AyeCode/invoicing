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
	public function __construct() {

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

		// Tell the admin (Tools > Site Health) when the daily job has stopped running.
		add_filter( 'site_status_tests', array( $this, 'add_site_health_test' ) );

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

		// Starts the record of runs, so a job that never runs is noticed too.
		self::get_run_record();

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
					'year'    => gmdate( 'Y' ),
					'month'   => gmdate( 'n' ),
					'day'     => gmdate( 'j' ),
					'compare' => '=',
				),
			),
		);

		$subscriptions = new GetPaid_Subscriptions_Query( $args );

		foreach ( $subscriptions->get_results() as $subscription ) {
			/** @var WPInv_Subscription $subscription */
			if ( $subscription->is_last_renewal() ) {
				$subscription->complete();
			} else {
				do_action( 'getpaid_should_renew_subscription', $subscription, $subscription->get_parent_invoice() );
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

		$record             = self::get_run_record();
		$record['last_run'] = time();
		update_option( 'getpaid_daily_maintenance_runs', $record, false );
	}

	/**
	 * When the daily job was first watched (since) and when it last ran (last_run, 0 if never), as timestamps.
	 *
	 * @since 2.8.68
	 * @return array
	 */
	public static function get_run_record() {
		$record = get_option( 'getpaid_daily_maintenance_runs' );

		if ( ! is_array( $record ) || empty( $record['since'] ) ) {
			$record = array(
				'since'    => time(),
				'last_run' => 0,
			);
			update_option( 'getpaid_daily_maintenance_runs', $record, false );
		}

		$record['last_run'] = empty( $record['last_run'] ) ? 0 : (int) $record['last_run'];

		return $record;
	}

	/**
	 * Whether the daily job is overdue: no run for two days (or never, two days after it was first watched).
	 *
	 * @since 2.8.68
	 * @return bool
	 */
	public static function is_overdue() {
		$record = self::get_run_record();
		$last   = max( (int) $record['last_run'], (int) $record['since'] );

		/**
		 * Filters how long the daily job may go without running before it is reported.
		 *
		 * @since 2.8.68
		 * @param int $seconds Two days by default.
		 */
		return ( time() - $last ) > (int) apply_filters( 'getpaid_daily_maintenance_overdue_after', 2 * DAY_IN_SECONDS );
	}

	/**
	 * Adds the daily job to the Site Health tests.
	 *
	 * @since 2.8.68
	 * @param array $tests The Site Health tests.
	 * @return array
	 */
	public function add_site_health_test( $tests ) {

		$tests['direct']['getpaid_daily_maintenance'] = array(
			'label' => __( 'GetPaid daily job', 'invoicing' ),
			'test'  => array( $this, 'site_health_test' ),
		);

		return $tests;
	}

	/**
	 * The Site Health test: has the daily job run in the last two days?
	 *
	 * @since 2.8.68
	 * @return array
	 */
	public function site_health_test() {
		$record = self::get_run_record();

		/**
		 * Filters the list of things that depend on the daily job, shown in the Site Health test.
		 *
		 * @since 2.8.68
		 * @param string[] $tasks Short descriptions, plain text.
		 */
		$tasks = (array) apply_filters(
			'getpaid_daily_maintenance_tasks',
			array(
				__( 'Expiring subscriptions that were not renewed', 'invoicing' ),
				__( 'Subscription renewal reminders', 'invoicing' ),
				__( 'Overdue invoice reminders', 'invoicing' ),
			)
		);

		$list = '';
		foreach ( $tasks as $task ) {
			$list .= '<li>' . esc_html( $task ) . '</li>';
		}

		$result = array(
			'label'       => __( 'The GetPaid daily job is running', 'invoicing' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'GetPaid', 'invoicing' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html__( 'GetPaid runs a job once a day through WP-Cron. These depend on it:', 'invoicing' ) . '</p><ul>' . $list . '</ul>',
			'actions'     => '',
			'test'        => 'getpaid_daily_maintenance',
		);

		if ( ! self::is_overdue() ) {
			return $result;
		}

		if ( empty( $record['last_run'] ) ) {
			$when = __( 'The GetPaid daily job has never run on this site.', 'invoicing' );
		} else {
			$when = sprintf(
				/* translators: %s: how long ago, e.g. "3 days" */
				__( 'The GetPaid daily job last ran %s ago.', 'invoicing' ),
				human_time_diff( (int) $record['last_run'], time() )
			);
		}

		$result['label']       = __( 'The GetPaid daily job is not running', 'invoicing' );
		$result['status']      = 'critical';
		$result['badge']       = array(
			'label' => __( 'GetPaid', 'invoicing' ),
			'color' => 'red',
		);
		$result['description'] = '<p>' . esc_html( $when ) . ' ' . esc_html__( 'This usually means WP-Cron is not running on this site. Until it runs again, these do not happen:', 'invoicing' ) . '</p><ul>' . $list . '</ul>';
		$result['actions']     = '<p>' . esc_html__( 'Ask your host to check that WP-Cron (wp-cron.php) is being called, or set up a real cron job for it.', 'invoicing' ) . '</p>';

		return $result;
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
