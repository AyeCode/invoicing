<?php
/**
 * Contains the abstract report class.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Reports_Abstract_Report Class.
 */
abstract class GetPaid_Reports_Abstract_Report {

	/**
	 * @var array
	 */
	public $stats;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {
		$this->prepare_stats();
	}

	/**
	 * Retrieves the current range.
	 *
	 */
	public function get_range() {
		$valid_ranges = $this->get_periods();

		if ( isset( $_GET['date_range'] ) && array_key_exists( $_GET['date_range'], $valid_ranges ) ) {
			return sanitize_key( $_GET['date_range'] );
		}

		return '7_days';
	}

	/**
	 * Returns an array of date ranges.
	 *
	 * @return array
	 */
	public function get_periods() {

		$periods = array(
            'today'     => __( 'Today', 'invoicing' ),
            'yesterday' => __( 'Yesterday', 'invoicing' ),
            '7_days'    => __( 'Last 7 days', 'invoicing' ),
			'30_days'   => __( 'Last 30 days', 'invoicing' ),
			'60_days'   => __( 'Last 60 days', 'invoicing' ),
			'90_days'   => __( 'Last 90 days', 'invoicing' ),
			'180_days'  => __( 'Last 180 days', 'invoicing' ),
			'360_days'  => __( 'Last 360 days', 'invoicing' ),
		);

		return apply_filters( 'getpaid_earning_periods', $periods );
	}

	/**
	 * Retrieves the current range's sql.
	 *
	 */
	public function get_range_sql( $range ) {

		$date     = 'CAST(meta.completed_date AS DATE)';
        $datetime = 'meta.completed_date';

        // Prepare durations.
        $today                = current_time( 'Y-m-d' );
		$yesterday            = date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );
		$seven_days_ago       = date( 'Y-m-d', strtotime( '-7 days', current_time( 'timestamp' ) ) );
		$thirty_days_ago      = date( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) );
		$ninety_days_ago      = date( 'Y-m-d', strtotime( '-90 days', current_time( 'timestamp' ) ) );
		$sixty_days_ago       = date( 'Y-m-d', strtotime( '-60 days', current_time( 'timestamp' ) ) );
		$one_eighty_days_ago  = date( 'Y-m-d', strtotime( '-180 days', current_time( 'timestamp' ) ) );
		$three_sixty_days_ago = date( 'Y-m-d', strtotime( '-360 days', current_time( 'timestamp' ) ) );

        $ranges = array(

            'today'        => array(
                "DATE_FORMAT($datetime, '%l%p')",
                "$date='$today'"
            ),

            'yesterday'    => array(
                "DATE_FORMAT($datetime, '%l%p')",
                "$date='$yesterday'"
            ),

            '7_days'       => array(
                "DATE($datetime)",
                "$date BETWEEN '$seven_days_ago' AND '$today'"
			),

			'30_days'       => array(
                "DATE($datetime)",
                "$date BETWEEN '$thirty_days_ago' AND '$today'"
			),

			'60_days'       => array(
                "DATE($datetime)",
                "$date BETWEEN '$sixty_days_ago' AND '$today'"
			),

			'90_days'       => array(
                "WEEK($datetime)",
                "$date BETWEEN '$ninety_days_ago' AND '$today'"
			),

			'180_days'       => array(
                "WEEK($datetime)",
                "$date BETWEEN '$one_eighty_days_ago' AND '$today'"
			),

			'360_days'       => array(
                "WEEK($datetime)",
                "$date BETWEEN '$three_sixty_days_ago' AND '$today'"
            ),

        );

		$sql = isset( $ranges[ $range ] ) ? $ranges[ $range ] : $ranges[ '7_days' ];
		return apply_filters( 'getpaid_earning_graphs_get_range_sql', $sql, $range );

	}

	/**
	 * Retrieves the hours in a day
	 *
	 */
	public function get_hours_in_a_day() {

		return array(
			'12AM' => __( '12 AM', 'invoicing'),
			'1AM'  => __( '1 AM', 'invoicing'),
			'2AM'  => __( '2 AM', 'invoicing'),
			'3AM'  => __( '3 AM', 'invoicing'),
			'4AM'  => __( '4 AM', 'invoicing'),
			'5AM'  => __( '5 AM', 'invoicing'),
			'6AM'  => __( '6 AM', 'invoicing'),
			'7AM'  => __( '7 AM', 'invoicing'),
			'8AM'  => __( '8 AM', 'invoicing'),
			'9AM'  => __( '9 AM', 'invoicing'),
			'10AM' => __( '10 AM', 'invoicing'),
			'11AM' => __( '11 AM', 'invoicing'),
			'12pm' => __( '12 PM', 'invoicing'),
			'1PM'  => __( '1 PM', 'invoicing'),
			'2PM'  => __( '2 PM', 'invoicing'),
			'3PM'  => __( '3 PM', 'invoicing'),
			'4PM'  => __( '4 PM', 'invoicing'),
			'5PM'  => __( '5 PM', 'invoicing'),
			'6PM'  => __( '6 PM', 'invoicing'),
			'7PM'  => __( '7 PM', 'invoicing'),
			'8PM'  => __( '8 PM', 'invoicing'),
			'9PM'  => __( '9 PM', 'invoicing'),
			'10PM' => __( '10 PM', 'invoicing'),
			'11PM' => __( '11 PM', 'invoicing'),
		);

	}

	/**
	 * Retrieves the days in a period
	 *
	 */
	public function get_days_in_period( $days ) {

		$return = array();
		$format = 'Y-m-d';

		if ( $days < 8 ) {
			$format = 'D';
		}

		if ( $days < 32 ) {
			$format = 'M j';
		}

		while ( $days > 0 ) {

			$key            = date( 'Y-m-d', strtotime( "-$days days", current_time( 'timestamp' ) ) );
			$label          = date_i18n( $format, strtotime( "-$days days", current_time( 'timestamp' ) ) );
			$return[ $key ] = $label;
			$days--;

		}

		return $return;
	}

	/**
	 * Retrieves the weeks in a period
	 *
	 */
	public function get_weeks_in_period( $days ) {

		$return = array();

		while ( $days > 0 ) {

			$key            = date( 'W', strtotime( "-$days days", current_time( 'timestamp' ) ) );
			$label          = date_i18n( 'Y-m-d', strtotime( "-$days days", current_time( 'timestamp' ) ) );
			$return[ $key ] = $label;
			$days--;

		}

		return $return;
	}

	/**
	 * Displays the report card.
	 *
	 */
	public function display() {
		?>

			<div class="row">
				<div class="col-12">
					<div class="card" style="max-width:100%">
						<div class="card-body">
							<?php $this->display_stats(); ?>
						</div>
					</div>
				</div>
			</div>

		<?php

	}

	/**
	 * Prepares the report stats.
	 *
	 * Extend this in child classes.
	 */
	abstract public function prepare_stats();

	/**
	 * Displays the actual report.
	 *
	 * Extend this in child classes.
	 */
	abstract public function display_stats();

}
