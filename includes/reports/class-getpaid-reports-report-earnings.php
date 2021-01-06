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
class GetPaid_Reports_Report_Earnings extends GetPaid_Reports_Abstract_Report {

	/**
	 * Retrieves the earning graphs.
	 *
	 */
	public function get_graphs() {

		$graphs = array(

            'total'      => __( 'Earnings', 'invoicing' ),
            'discount'   => __( 'Discount', 'invoicing' ),
			'fees_total' => __( 'Fees', 'invoicing' ),
			'tax'        => __( 'Tax', 'invoicing' ),

		);

		return apply_filters( 'getpaid_earning_graphs', $graphs );

	}

	/**
	 * Retrieves the earning sql.
	 *
	 */
	public function get_sql( $range ) {
		global $wpdb;

		$table      = $wpdb->prefix . 'getpaid_invoices';
		$clauses    = $this->get_range_sql( $range );
		$graphs     = array_keys( $this->get_graphs() );
		$graphs_sql = array();

		foreach ( $graphs as $graph ) {
			$graphs_sql[] = "SUM( meta.$graph ) AS $graph";
		}

		$graphs_sql = implode( ', ', $graphs_sql );
		$sql        = "SELECT {$clauses[0]} AS completed_date, $graphs_sql
            FROM $wpdb->posts
            LEFT JOIN $table as meta ON meta.post_id = $wpdb->posts.ID
            WHERE meta.post_id IS NOT NULL
                AND $wpdb->posts.post_type = 'wpi_invoice'
                AND ( $wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'wpi-renewal' )
                AND {$clauses[1]}
            GROUP BY {$clauses[0]}
        ";

		return apply_filters( 'getpaid_earning_graphs_get_sql', $sql, $range );

	}

	/**
	 * Prepares the report stats.
	 *
	 */
	public function prepare_stats() {
		global $wpdb;
		$this->stats = $wpdb->get_results( $this->get_sql( $this->get_range() ) );
	}

	/**
	 * Retrieves report labels.
	 *
	 */
	public function get_labels( $range ) {

		$labels = array(
			'today'     => $this->get_hours_in_a_day(),
			'yesterday' => $this->get_hours_in_a_day(),
			'7_days'    => $this->get_days_in_period( 7 ),
			'30_days'   => $this->get_days_in_period( 30 ),
			'60_days'   => $this->get_days_in_period( 60 ),
			'90_days'   => $this->get_weeks_in_period( 90 ),
			'180_days'  => $this->get_weeks_in_period( 180 ),
			'360_days'  => $this->get_weeks_in_period( 360 ),
		);

		$label = isset( $labels[ $range ] ) ? $labels[ $range ] : $labels[ '7_days' ];
		return apply_filters( 'getpaid_earning_graphs_get_labels', $label, $range );
	}

	/**
	 * Retrieves report datasets.
	 *
	 */
	public function get_datasets( $labels ) {

		$datasets = array();

		foreach ( $this->get_graphs() as $key => $label ) {
			$datasets[ $key ] = array(
				'label' => $label,
				'data'  => $this->get_data( $key, $labels )
			);
		}

		return apply_filters( 'getpaid_earning_graphs_get_datasets', $datasets, $labels );
	}

	/**
	 * Retrieves report data.
	 *
	 */
	public function get_data( $key, $labels ) {

		$data     = wp_list_pluck( $this->stats, $key, 'completed_date' );
		$prepared = array();

		foreach ( $labels as $label ) {

			$value = 0;
			if ( isset( $data[ $label ] ) ) {
				$value = wpinv_round_amount( wpinv_sanitize_amount( $data[ $label ] ) );
			}

			$prepared[] = $value;
		}

		return apply_filters( 'getpaid_earning_graphs_get_data', $prepared, $key, $labels );

	}

	/**
	 * Displays the report card.
	 *
	 */
	public function display() {

		$labels     = $this->get_labels( $this->get_range() );
		$chart_data = array(
			'labels'   => array_values( $labels ),
			'datasets' => $this->get_datasets( array_keys( $labels ) ),
		);

		?>

			<?php foreach ( $chart_data['datasets'] as $key => $dataset ) : ?>
				<div class="row mb-4">
					<div class="col-12">
						<div class="card m-0 p-0" style="max-width:100%">
							<div class="card-header d-flex align-items-center">
								<strong class="flex-grow-1"><?php echo $dataset['label']; ?></strong>
								<?php $this->display_range_selector(); ?>
							</div>
							<div class="card-body">
								<?php $this->display_graph( $key, $dataset, $chart_data['labels'] ); ?>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

		<?php

	}

	/**
	 * Displays the actual report.
	 *
	 */
	public function display_graph( $key, $dataset, $labels ) {

		?>

		<canvas id="getpaid-chartjs-earnings-<?php echo sanitize_key( $key ); ?>"></canvas>

		<script>
			window.addEventListener( 'DOMContentLoaded', function() {

				var ctx = document.getElementById( 'getpaid-chartjs-earnings-<?php echo sanitize_key( $key ); ?>' ).getContext('2d');
				new Chart(
					ctx,
					{
						type: 'line',
						data: {
							'labels': <?php echo wp_json_encode( $labels ); ?>,
							'datasets': [
								{
									label: '<?php echo esc_attr( $dataset['label'] ); ?>',
									data: <?php echo wp_json_encode( $dataset['data'] ); ?>,
									backgroundColor: 'rgba(54, 162, 235, 0.1)',
									borderColor: 'rgb(54, 162, 235)',
									borderWidth: 4,
									pointBackgroundColor: 'rgb(54, 162, 235)'
								}
							]
						},
						options: {
							scales: {
								yAxes: [{
									ticks: {
										beginAtZero: true
									}
								}],
								xAxes: [{
									ticks: {
										maxTicksLimit: 15
									}
                    			}]
							},
							legend: {
    							display: false
    						}
						}
					}
				);

			})

		</script>

		<?php
	}

	/**
	 * Displays the actual report.
	 *
	 */
	public function display_stats() {}

	/**
	 * Displays the range selector.
	 *
	 */
	public function display_range_selector() {

	}

}
