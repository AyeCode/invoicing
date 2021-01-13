<?php
/**
 * Helper for the date based controllers.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST date based controller class.
 *
 * @package Invoicing
 */
class GetPaid_REST_Date_Based_Controller extends GetPaid_REST_Controller {

	/**
	 * Group response items by day or month.
	 *
	 * @var string
	 */
	public $groupby = 'day';

	/**
	 * Returns an array with arguments to request the previous report.
	 *
	 * @var array
	 */
	public $previous_range = array();

	/**
	 * The period interval.
	 *
	 * @var int
	 */
	public $interval;

	/**
	 * Retrieves the before and after dates.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array The appropriate date range.
	 */
	public function get_date_range( $request ) {

		// If not supported, assume all time.
		if ( ! in_array( $request['period'], array( 'custom', 'today', 'yesterday', 'week', 'last_week', '7_days', '30_days', '60_days', '90_days', '180_days', 'month', 'last_month', 'quarter', 'last_quarter', 'year', 'last_year' ) ) ) {
			$request['period'] = '7_days';
		}

		$date_range = call_user_func( array( $this, 'get_' . $request['period'] . '_date_range' ), $request );
		$this->prepare_interval( $date_range );

		return $date_range;

	}

	/**
	 * Groups by month or days.
	 *
	 * @param array $range Date range.
	 * @return array The appropriate date range.
	 */
	public function prepare_interval( $range ) {

		$before = strtotime( $range['before'] ) - DAY_IN_SECONDS;
		$after  = strtotime( $range['after'] ) + DAY_IN_SECONDS;
		if ( 'day' === $this->groupby ) {
			$difference     = max( DAY_IN_SECONDS, ( DAY_IN_SECONDS + $before - $after ) ); // Prevent division by 0;
			$this->interval = absint( ceil( max( 1, $difference / DAY_IN_SECONDS ) ) );
			return;
		}

		$this->interval = 0;
		$min_date       = strtotime( date( 'Y-m-01', $after ) );

		while ( $min_date <= $before ) {
			$this->interval ++;
			$min_date = strtotime( '+1 MONTH', $min_date );
		}

		$this->interval = max( 1, $this->interval );

	}

	/**
	 * Retrieves a custom date range.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array The appropriate date range.
	 */
	public function get_custom_date_range( $request ) {

		$after  = max( strtotime( '-20 years' ), strtotime( sanitize_text_field( $request['after'] ) ) );
		$before = strtotime( '+1 day', current_time( 'timestamp' ) );

		if ( ! empty( $request['before'] ) ) {
			$before  = min( $before, strtotime( sanitize_text_field( $request['before'] ) ) );
		}

		// 3 months max for day view
		if ( floor( ( $before - $after ) / MONTH_IN_SECONDS ) > 3 ) {
			$this->groupby = 'month';
		}

		// Set the previous date range.
		$difference           = $before - $after;
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', $before - $difference ),
			'after'  => date( 'Y-m-d', $after - $difference ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', $before ),
			'after' => date( 'Y-m-d', $after ),
		);

	}

	/**
	 * Retrieves todays date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_today_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'yesterday',
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves yesterdays date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_yesterday_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-d', strtotime( '-3 days', current_time( 'timestamp' ) ) ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'after'  => date( 'Y-m-d', strtotime( '-2 days', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves this week's date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_week_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'last_week',
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', strtotime( 'sunday last week', current_time( 'timestamp' )  ) + 8 * DAY_IN_SECONDS ),
			'after'  => date( 'Y-m-d', strtotime( 'sunday last week', current_time( 'timestamp' )  ) ),
		);

	}

	/**
	 * Retrieves last week's date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_last_week_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', strtotime( 'monday last week', current_time( 'timestamp' )  ) ),
			'after'  => date( 'Y-m-d', strtotime( 'monday last week', current_time( 'timestamp' )  ) - 8 * DAY_IN_SECONDS ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', strtotime( 'monday this week', current_time( 'timestamp' )  ) ),
			'after'  => date( 'Y-m-d', strtotime( 'monday last week', current_time( 'timestamp' )  ) - DAY_IN_SECONDS ),
		);

	}

	/**
	 * Retrieves last 7 days date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_7_days_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-d', strtotime( '-15 days', current_time( 'timestamp' ) ) ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'after'  => date( 'Y-m-d', strtotime( '-8 days', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves last 30 days date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_30_days_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-d', strtotime( '-61 days', current_time( 'timestamp' ) ) ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'after'  => date( 'Y-m-d', strtotime( '-31 days', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves last 90 days date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_90_days_date_range() {

		$this->groupby = 'month';

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', strtotime( '-90 days', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-d', strtotime( '-181 days', current_time( 'timestamp' ) ) ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'after'  => date( 'Y-m-d', strtotime( '-91 days', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves last 180 days date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_180_days_date_range() {

		$this->groupby = 'month';

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', strtotime( '-180 days', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-d', strtotime( '-361 days', current_time( 'timestamp' ) ) ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'after'  => date( 'Y-m-d', strtotime( '-181 days', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves last 60 days date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_60_days_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', strtotime( '-60 days', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-d', strtotime( '-121 days', current_time( 'timestamp' ) ) ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', current_time( 'timestamp' ) ),
			'after'  => date( 'Y-m-d', strtotime( '-61 days', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves this month date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_month_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'last_month',
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-01', strtotime( 'next month', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-t', strtotime( 'last month', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves last month's date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_last_month_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-1', strtotime( 'last month', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-t', strtotime( "-3 months", current_time( 'timestamp' ) ) ),
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-1', current_time( 'timestamp' ) ),
			'after'  => date( 'Y-m-t', strtotime( "-2 months", current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves this quarter date range.
	 *
	 * @return array The available quarters.
	 */
	public function get_quarters() {

		$last_year = (int) date('Y') - 1;
		$next_year = (int) date('Y') + 1;
		$year      = (int) date('Y');
		return array(

			array(
				'after'  => "$last_year-06-30",
				'before' => "$last_year-10-01",
			),

			array(
				'before' => "$year-01-01",
				'after'  => "$last_year-09-30",
			),

			array(
				'before' => "$year-04-01",
				'after'  => "$last_year-12-31",
			),

			array(
				'before' => "$year-07-01",
				'after'  => "$year-03-31",
			),

			array(
				'after'  => "$year-06-30",
				'before' => "$year-10-01",
			),

			array(
				'before' => "$next_year-01-01",
				'after'  => "$year-09-30",
			)

		);

	}

	/**
	 * Retrieves the current quater.
	 *
	 * @return int The current quarter.
	 */
	public function get_quarter() {

		$month    = (int) date( 'n', current_time( 'timestamp' ) );
		$quarters = array( 1, 1, 1, 2, 2, 2, 3, 3, 3, 4, 4, 4 );
		return $quarters[ $month - 1 ];

	}

	/**
	 * Retrieves this quarter date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_quarter_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'last_quarter',
		);

		// Generate the report.
		$quarters = $this->get_quarters();
		return $quarters[ $this->get_quarter() + 1 ];

	}

	/**
	 * Retrieves last quarter's date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_last_quarter_date_range() {

		$quarters = $this->get_quarters();
		$quarter  = $this->get_quarter();

		// Set the previous date range.
		$this->previous_range = array_merge(
			$quarters[ $quarter - 1 ],
			array( 'period' => 'custom' )
		);

		// Generate the report.
		return $quarters[ $quarter ];

	}

	/**
	 * Retrieves this year date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_year_date_range() {

		$this->groupby = 'month';

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'last_year',
		);

		// Generate the report.
		return array(
			'before' => date( 'Y-m-d', strtotime( 'next year January 1st', current_time( 'timestamp' ) ) ),
			'after'  => date( 'Y-m-d', strtotime( 'last year December 31st', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Retrieves last year date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_last_year_date_range() {

		$this->groupby = 'month';

		// Set the previous date range.
		$year          = (int) date('Y') - 3;
		$this->previous_range = array(
			'period' => 'custom',
			'before' => date( 'Y-m-d', strtotime( 'first day of january last year', current_time( 'timestamp' ) ) ),
			'after'  => "$year-12-31",
		);

		// Generate the report.
		$year          = (int) date('Y') - 2;
		return array(
			'after'  => "$year-12-31",
			'before' => date( 'Y-m-d', strtotime( 'first day of january this year', current_time( 'timestamp' ) ) ),
		);

	}

	/**
	 * Prepare a the request date for SQL usage.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @param string $date_field The date field.
	 * @return string The appropriate SQL.
	 */
	public function get_date_range_sql( $request, $date_field ) {
		global $wpdb;

		$sql = '1=1';
		$range = $this->get_date_range( $request );

		if ( ! empty( $range['after'] ) ) {
			$sql .= ' AND ' .  $wpdb->prepare(
				"$date_field > %s",
				$range['after']
			);
		}

		if ( ! empty( $range['before'] ) ) {
			$sql .= ' AND ' .  $wpdb->prepare(
				"$date_field < %s",
				$range['before']
			);
		}

		return $sql;

	}

	/**
	 * Prepares a group by query.
	 *
	 * @param string $date_field The date field.
	 * @return string The appropriate SQL.
	 */
	public function get_group_by_sql( $date_field ) {

		if ( 'day' === $this->groupby ) {
			return "YEAR($date_field), MONTH($date_field), DAY($date_field)";
		}

		return "YEAR($date_field), MONTH($date_field)";
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			'period' => array(
				'description'       => __( 'Limit to results of a specific period.', 'invoicing' ),
				'type'              => 'string',
				'enum'              => array( 'custom', 'today', 'yesterday', 'week', 'last_week', '7_days', '30_days', '60_days' , '90_days', '180_days', 'month', 'last_month', 'quarter', 'last_quarter', 'year', 'last_year', 'quarter', 'last_quarter' ),
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '7_days',
			),
			'after' => array(
				/* translators: %s: date format */
				'description'       => sprintf( __( 'Limit to results after a specific date, the date needs to be in the %s format.', 'invoicing' ), 'YYYY-MM-DD' ),
				'type'              => 'string',
				'format'            => 'date',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => date( 'Y-m-d', strtotime( '-8 days', current_time( 'timestamp' ) ) ),
			),
			'before' => array(
				/* translators: %s: date format */
				'description'       => sprintf( __( 'Limit to results before a specific date, the date needs to be in the %s format.', 'invoicing' ), 'YYYY-MM-DD' ),
				'type'              => 'string',
				'format'            => 'date',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => date( 'Y-m-d', current_time( 'timestamp' ) ),
			),
		);
	}
}
