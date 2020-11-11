<?php
/**
 * Contains the class that displays the items report.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Reports_Report_Items Class.
 */
class GetPaid_Reports_Report_Items extends GetPaid_Reports_Abstract_Report {

	/**
	 * @var string
	 */
	public $field = 'item_name';

	/**
	 * Retrieves the earning sql.
	 *
	 */
	public function get_sql( $range ) {
		global $wpdb;

		$table      = $wpdb->prefix . 'getpaid_invoices';
		$table2     = $wpdb->prefix . 'getpaid_invoice_items';
		$clauses    = $this->get_range_sql( $range );

		$sql        = "SELECT
				item.item_name AS item_name,
				item.item_id AS item_id,
				SUM(price) as total
            FROM $wpdb->posts
            LEFT JOIN $table as meta ON meta.post_id = $wpdb->posts.ID
				INNER JOIN $table2 as item ON item.post_id = wp_posts.ID
            WHERE meta.post_id IS NOT NULL
                AND $wpdb->posts.post_type = 'wpi_invoice'
                AND ( $wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'wpi-renewal' )
                AND {$clauses[1]}
            GROUP BY item_id
			ORDER BY total DESC
        ";

		return apply_filters( 'getpaid_items_graphs_get_sql', $sql, $range );

	}

	/**
	 * Prepares the report stats.
	 *
	 */
	public function prepare_stats() {
		global $wpdb;
		$this->stats = $wpdb->get_results( $this->get_sql( $this->get_range() ) );
		$this->stats = $this->normalize_stats( $this->stats );
	}

	/**
	 * Normalizes the report stats.
	 *
	 */
	public function normalize_stats( $stats ) {
		$normalized = array();
		$others     = 0;
		$did        = 0;

		foreach ( $stats as $stat ) {

			if ( $did > 4 ) {

				$others += wpinv_round_amount( wpinv_sanitize_amount( $stat->total ) );

			} else {

				$normalized[] = array(
					'total'     => wpinv_round_amount( wpinv_sanitize_amount( $stat->total ) ),
					'item_name' => strip_tags( $stat->item_name ),
				);

			}

			$did++;
		}

		if ( $others > 0 ) {

			$normalized[] = array(
				'total'     => wpinv_round_amount( wpinv_sanitize_amount( $others ) ),
				'item_name' => esc_html__( 'Others', 'invoicing' ),
			);

		}

		return $normalized;
	}

	/**
	 * Retrieves report data.
	 *
	 */
	public function get_data() {

		$data     = wp_list_pluck( $this->stats, 'total' );
		$colors   = array( '#009688','#4caf50','#8bc34a','#00bcd4','#03a9f4','#2196f3' );

		shuffle( $colors );

		return array(
			'data'            => $data,
			'backgroundColor' => $colors,
		);

	}

	/**
	 * Retrieves report labels.
	 *
	 */
	public function get_labels() {
		return wp_list_pluck( $this->stats, 'item_name' );
	}

	/**
	 * Displays the actual report.
	 *
	 */
	public function display_stats() {
		?>

			<canvas id="getpaid-chartjs-earnings-items"></canvas>

			<script>
				window.addEventListener( 'DOMContentLoaded', function() {

					var ctx = document.getElementById( 'getpaid-chartjs-earnings-items' ).getContext('2d');
					new Chart(
						ctx,
						{
							type: 'doughnut',
							data: {
								'labels': <?php echo wp_json_encode( $this->get_labels() ); ?>,
								'datasets': [ <?php echo wp_json_encode( $this->get_data() ); ?> ]
							},
							options: {
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

}
