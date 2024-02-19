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

		// Check if the period is x_days.
		if ( preg_match( '/^(\d+)_days$/', $request['period'], $matches ) ) {
			$date_range = $this->get_x_days_date_range( absint( $matches[1] ) );
		} elseif ( is_callable( array( $this, 'get_' . $request['period'] . '_date_range' ) ) ) {
			$date_range = call_user_func( array( $this, 'get_' . $request['period'] . '_date_range' ), $request );
		} else {
			$request['period'] = '7_days';
			$date_range        = $this->get_x_days_date_range();
		}

		// 3 months max for day view.
		$before = strtotime( $date_range['before'] );
		$after  = strtotime( $date_range['after'] );
		if ( floor( ( $before - $after ) / MONTH_IN_SECONDS ) > 2 ) {
			$this->groupby = 'month';
		}

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

		$before = strtotime( $range['before'] );
		$after  = strtotime( $range['after'] );
		if ( 'day' === $this->groupby ) {
			$difference     = max( DAY_IN_SECONDS, ( DAY_IN_SECONDS + $before - $after ) ); // Prevent division by 0;
			$this->interval = absint( ceil( max( 1, $difference / DAY_IN_SECONDS ) ) );
			return;
		}

		$this->interval = 0;
		$min_date       = strtotime( gmdate( 'Y-m-01', $after ) );

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
		$before = gmdate( 'Y-m-d' );
		
		if ( ! empty( $request['before'] ) ) {
			$before  = min( strtotime( $before ), strtotime( sanitize_text_field( $request['before'] ) ) );
		}

		// Set the previous date range.
		$difference           = $before - $after;
		$this->previous_range = array(
			'period' => 'custom',
			'before' => gmdate( 'Y-m-d', $before - $difference - DAY_IN_SECONDS ),
			'after'  => gmdate( 'Y-m-d', $after - $difference - DAY_IN_SECONDS ),
		);

		// Generate the report.
		return array(
			'before' => gmdate( 'Y-m-d', $before ),
			'after'  => gmdate( 'Y-m-d', $after ),
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
			'before' => gmdate( 'Y-m-d' ),
			'after'  => gmdate( 'Y-m-d' ),
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
			'before' => gmdate( 'Y-m-d', strtotime( '-2 days' ) ),
			'after'  => gmdate( 'Y-m-d', strtotime( '-2 days' ) ),
		);

		// Generate the report.
		return array(
			'before' => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
			'after'  => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
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
		$week_starts = absint( get_option( 'start_of_week' ) );
		return array(
			'before' => gmdate( 'Y-m-d' ),
			'after'  => gmdate( 'Y-m-d', strtotime( 'next Sunday -' . ( 7 - $week_starts ) . ' days' ) ),
		);
	}

	/**
	 * Retrieves last week's date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_last_week_date_range() {

		$week_starts = absint( get_option( 'start_of_week' ) );
		$week_starts = strtotime( 'last Sunday -' . ( 7 - $week_starts ) . ' days' );
		$date_range  = array(
			'before' => gmdate( 'Y-m-d', $week_starts + 6 * DAY_IN_SECONDS ),
			'after'  => gmdate( 'Y-m-d', $week_starts ),
		);

		// Set the previous date range.
		$week_starts          = $week_starts - 7 * DAY_IN_SECONDS;
		$this->previous_range = array(
			'period' => 'custom',
			'before' => gmdate( 'Y-m-d', $week_starts + 6 * DAY_IN_SECONDS ),
			'after'  => gmdate( 'Y-m-d', $week_starts ),
		);

		// Generate the report.
		return $date_range;
	}

	/**
	 * Retrieves last x days date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_x_days_date_range( $days = 7 ) {

		$days--;

		$date_range  = array(
			'before' => gmdate( 'Y-m-d' ),
			'after'  => gmdate( 'Y-m-d', strtotime( "-$days days" ) ),
		);

		$days++;

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'before' => gmdate( 'Y-m-d', strtotime( $date_range['before'] ) - $days * DAY_IN_SECONDS ),
			'after'  => gmdate( 'Y-m-d', strtotime( $date_range['after'] ) - $days * DAY_IN_SECONDS ),
		);

		// Generate the report.
		return $date_range;
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
			'after'  => gmdate( 'Y-m-01' ),
			'before' => gmdate( 'Y-m-t' ),
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
			'after'  => gmdate( 'Y-m-01', strtotime( '-2 months' ) ),
			'before' => gmdate( 'Y-m-t', strtotime( '-2 months' ) ),
		);

		// Generate the report.
		return array(
			'after'  => gmdate( 'Y-m-01', strtotime( 'last month' ) ),
			'before' => gmdate( 'Y-m-t', strtotime( 'last month' ) ),
		);

	}

	/**
	 * Retrieves this quarter date range.
	 *
	 * @return array The available quarters.
	 */
	public function get_quarters() {

		$year      = (int) gmdate( 'Y' );
		$last_year = (int) $year - 1;
		return array(

			// Third quarter of previous year: July 1st to September 30th
			array(
				'before' => "{$last_year}-09-30",
				'after'  => "{$last_year}-07-01",
			),

			// Last quarter of previous year: October 1st to December 31st
			array(
				'before' => "{$last_year}-12-31",
        		'after'  => "{$last_year}-10-01",
			),

			// First quarter: January 1st to March 31st
			array(
				'before' => "{$year}-03-31",
				'after'  => "{$year}-01-01",
			),

			// Second quarter: April 1st to June 30th
			array(
				'before' => "{$year}-06-30",
				'after'  => "{$year}-04-01",
			),

			// Third quarter: July 1st to September 30th
			array(
				'before' => "{$year}-09-30",
				'after'  => "{$year}-07-01",
			),

			// Fourth quarter: October 1st to December 31st
			array(
				'before' => "{$year}-12-31",
				'after'  => "{$year}-10-01",
			),
		);
	}

	/**
	 * Retrieves the current quater.
	 *
	 * @return int The current quarter.
	 */
	public function get_quarter() {

		$month    = (int) gmdate( 'n' );
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
		$quarter  = $this->get_quarter();
		$quarters = $this->get_quarters();
		return $quarters[ $quarter + 1 ];

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

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'last_year',
		);

		// Generate the report.
		return array(
			'after'  => gmdate( 'Y-01-01' ),
			'before' => gmdate( 'Y-12-31' ),
		);

	}

	/**
	 * Retrieves last year date range.
	 *
	 * @return array The appropriate date range.
	 */
	public function get_last_year_date_range() {

		// Set the previous date range.
		$this->previous_range = array(
			'period' => 'custom',
			'after'  => gmdate( 'Y-01-01', strtotime( '-2 years' ) ),
			'before' => gmdate( 'Y-12-31', strtotime( '-2 years' ) ),
		);

		// Generate the report.
		return array(
			'after'  => gmdate( 'Y-01-01', strtotime( 'last year' ) ),
			'before' => gmdate( 'Y-12-31', strtotime( 'last year' ) ),
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
			$sql .= ' AND ' . $wpdb->prepare(
				"$date_field >= %s",
				$range['after']
			);
		}

		if ( ! empty( $range['before'] ) ) {
			$sql .= ' AND ' . $wpdb->prepare(
				"$date_field <= %s",
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
			'period'  => array(
				'description'       => __( 'Limit to results of a specific period.', 'invoicing' ),
				'type'              => 'string',
				'enum'              => array( 'custom', 'today', 'yesterday', 'week', 'last_week', '7_days', '30_days', '60_days', '90_days', '180_days', 'month', 'last_month', 'quarter', 'last_quarter', 'year', 'last_year', 'quarter', 'last_quarter' ),
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '7_days',
			),
			'after'   => array(
				/* translators: %s: date format */
				'description'       => sprintf( __( 'Limit to results after a specific date, the date needs to be in the %s format.', 'invoicing' ), 'YYYY-MM-DD' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
			),
			'before'  => array(
				/* translators: %s: date format */
				'description'       => sprintf( __( 'Limit to results before a specific date, the date needs to be in the %s format.', 'invoicing' ), 'YYYY-MM-DD' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => gmdate( 'Y-m-d' ),
			),
		);
	}
}
