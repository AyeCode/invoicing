<?php
/**
 * Contains the class that displays the earnings report.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Reports_Report_Earnings Class.
 */
class GetPaid_Reports_Report_Earnings {

	/**
	 * @var array
	 */
	public $stats;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {

	}

	/**
	 * Displays the reports tab.
	 *
	 */
	public function display() {
		?>

			<div class="row">
				<div class="col-12">
					<div class="card" style="max-width:720px">
						<div class="card-body">
							<?php $this->display_range_selector(); ?>
							<canvas id="getpaid-chartjs-earnings"></canvas>
						</div>
					</div>
				</div>
			</div>

			<script>

				window.addEventListener( 'DOMContentLoaded', function() {

					var ctx = document.getElementById( 'getpaid-chartjs-earnings' ).getContext('2d');

					var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
        datasets: [{
            label: '# of Votes',
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    }
});

				});

			</script>

		<?php

		wp_enqueue_script( 'chart-js', WPINV_PLUGIN_URL . 'assets/js/chart.bundle.min.js', array( 'jquery' ), '2.9.4', true );
		wp_enqueue_style( 'chart-js', WPINV_PLUGIN_URL . 'assets/css/chart.min.css', array(), '2.9.4' );

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
			'90_days'   => __( 'Last 90 days', 'invoicing' ),
		);

		return apply_filters( 'getpaid_earning_periods', $periods );
	}

	/**
	 * Displays the range selector.
	 *
	 */
	public function display_range_selector() {

		?>

			<form method="get" class="d-block mt-4 getpaid-filter-earnings">
				<?php

					getpaid_hidden_field( 'page', 'wpinv-reports' );
					getpaid_hidden_field( 'tab', 'reports' );

					?>

					<div class="row">
						<div class="col-12 col-sm-4">

							<?php
								echo aui()->select(
									array(
										'name'        => 'date_range',
										'id'          => 'view' . uniqid( '_' ),
										'placeholder' => __( 'Select a date range', 'invoicing' ),
										'label'       => __( 'Date Range', 'invoicing' ),
										'options'     => $this->get_periods(),
										'value'       => isset( $_GET['date_range'] ) ? sanitize_key( $_GET['date_range'] ) : '7_days',
										'no_wrap'     => true,
									)
								);
							?>

						</div>

						<div class='getpaid-custom-range d-none col-12 col-sm-4'>

							<?php

								echo aui()->input(
									array(
										'type'        => 'datepicker',
										'id'          => 'getpaid_earnings_report_range',
										'name'        => 'reports_range',
										'label'       => __( 'Start Date', 'invoicing' ),
										'placeholder' => 'YYYY-MM-DD 00:00',
										'value'       => '2020-11-01 00:00 to 2020-11-05 00:00',
										'extra_attributes' => array(
											'data-mode'        => 'range',
											'data-date-format' => 'Y-m-d',
											'data-max-date'    => 'today',
										),
									)
								);

							?>

						</div>

						<div class="col-12 col-sm-4">
							<?php getpaid_submit_field( __( 'Filter', 'invoicing' ), '', 'btn-secondary' ); ?>
						</div>

					</div>

			</form>

		<?php
	}

}
