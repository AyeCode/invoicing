<?php
/**
 * Contains the class that displays a single report.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Reports_Report Class.
 */
class GetPaid_Reports_Report {

	/**
	 * @var array
	 */
	public $views;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {

		$this->views        = array(

            'items'     => array(
				'label' => __( 'Items', 'invoicing' ),
				'class' => 'GetPaid_Reports_Report_Items',
			),

			'gateways'  => array(
				'label' => __( 'Payment Methods', 'invoicing' ),
				'class' => 'GetPaid_Reports_Report_Gateways',
			),

			'discounts'  => array(
				'label' => __( 'Discount Codes', 'invoicing' ),
				'class' => 'GetPaid_Reports_Report_Discounts',
			),

        );

		$this->views        = apply_filters( 'wpinv_report_views', $this->views );

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
			'today'        => __( 'Today', 'invoicing' ),
			'yesterday'    => __( 'Yesterday', 'invoicing' ),
			'week'         => __( 'This week', 'invoicing' ),
			'last_week'    => __( 'Last week', 'invoicing' ),
			'7_days'       => __( 'Last 7 days', 'invoicing' ),
			'month'        => __( 'This month', 'invoicing' ),
			'last_month'   => __( 'Last month', 'invoicing' ),
			'30_days'      => __( 'Last 30 days', 'invoicing' ),
			'quarter'      => __( 'This Quarter', 'invoicing' ),
			'last_quarter' => __( 'Last Quarter', 'invoicing' ),
			'year'         => __( 'This year', 'invoicing' ),
			'last_year'    => __( 'Last Year', 'invoicing' ),
			'custom'       => __( 'Custom Date Range', 'invoicing' ),
		);

		return apply_filters( 'getpaid_earning_periods', $periods );
	}

	/**
	 * Displays the range selector.
	 *
	 */
	public function display_range_selector() {

		$range = $this->get_range();
		?>

			<form method="get" class="getpaid-filter-earnings float-right">
				<?php getpaid_hidden_field( 'page', 'wpinv-reports' );  ?>
				<?php getpaid_hidden_field( 'tab', 'reports' );  ?>
				<select name='date_range'>
					<?php foreach( $this->get_periods() as $key => $label ) :?>
						<option value="<?php echo sanitize_key( $key ); ?>" <?php selected( $key, $range ); ?>><?php echo sanitize_text_field( $label ); ?></option>
					<?php endforeach;?>
				</select>
				<span class="getpaid-date-range-picker <?php echo 'custom' == $range ? '' : 'd-none'; ?>">
					<input type="text" name="from" class="getpaid-from align-middle" />
						<?php _e( 'to', 'invoicing' ); ?>
					<input type="text" name="to" class="getpaid-to align-middle" />
				</span>
				<button type="submit" class="button button-primary">
					<i class="fa fa-chevron-right fa-lg"></i>
					<span class="screen-reader-text"><?php _e( 'View Reports', 'invoicing' ); ?></span>
				</button>
			</form>

		<?php
	}

	/**
	 * Displays the reports tab.
	 *
	 */
	public function display() {
		?>

		<div class="mt-4" style="max-width: 1200px;">

			<section class="mb-4">

				<!-- Period Selector Card -->
				<div class="card mw-100">

					<div class="card-body py-0">

						<!--Grid row-->
						<div class="row">

							<!--Grid column-->
							<div class="col-md-6 offset-md-6">
								<?php $this->display_range_selector(); ?>
							</div>
							<!--Grid column-->

						</div>
						<!--Grid row-->

					</div>

				</div>
				<!-- Period SelectorCard -->

			</section>

			<div class="row">
				<div class="col-12 col-md-8">
					<?php echo $this->display_left(); ?>
				</div>

				<div class="col-12 col-md-4">
					<div class="row getpaid-report-cards">
						<?php foreach( $this->get_cards() as $key => $card ) : ?>
							<div class="col-12 mb-4">

								<!-- <?php echo sanitize_text_field(  $card['label']  ); ?> Card -->
								<div class="card p-0 m-0 shadow-none <?php echo sanitize_html_class( $key ); ?>">

									<div class="card-body">

										<p class="getpaid-current text-uppercase small mb-2">
											<strong><?php echo sanitize_text_field( $card['label']  ); ?></strong>
											<span title="<?php echo esc_attr( $card['description'] ); ?>" class="wpi-help-tip dashicons dashicons-editor-help text-muted" style="margin-top: -2px;"></span>
										</p>
										<h5 class="font-weight-bold mb-0">
											<span class="getpaid-report-card-value">
												<span class="spinner is-active float-none"></span>
											</span>
											<small class="getpaid-report-card-growth ml-2"></small>
										</h5>

										<hr>

										<p class="getpaid-previous text-uppercase text-muted small mb-2"><strong><?php _e( 'Previous Period', 'invoicing' ); ?></strong></p>
										<h5 class="getpaid-report-card-previous-value font-weight-bold text-muted mb-0">
											<span class="spinner is-active float-none"></span>
										</h5>

									</div>

								</div>
								<!-- <?php echo sanitize_text_field( $card['label'] ); ?> Card -->

							</div>
						<?php endforeach; ?>
					</div>

					<?php echo $this->display_right(); ?>
				</div>
			</div>

		</div>

		<?php

	}

	/**
	 * Displays the left side.
	 *
	 */
	public function display_left() {
		$graphs = array(
			'sales'    => __( 'Earnings', 'invoicing' ),
			'refunds'  => __( 'Refunds', 'invoicing' ),
			'tax'      => __( 'Taxes', 'invoicing' ),
			'fees'     => __( 'Fees', 'invoicing' ),
			'discount' => __( 'Discounts', 'invoicing' ),
			'invoices' => __( 'Invoices', 'invoicing' ),
			'items'    => __( 'Purchased Items', 'invoicing' ),
		);

		?>

			<?php foreach ( $graphs as $key => $graph ) : ?>
				<div class="row mb-4">
					<div class="col-12">
						<div class="card m-0 p-0 single-report-card" style="max-width:100%">
							<div class="card-header">
								<strong><?php echo wpinv_clean( $graph ); ?></strong>
							</div>
							<div class="card-body">
								<canvas id="getpaid-chartjs-<?php echo sanitize_key( $key ); ?>"></canvas>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

		<?php

	}

	/**
	 * Retrieves the download url.
	 *
	 */
	public function get_download_url( $graph, $file_type ) {

		return wp_nonce_url(
			add_query_arg(
				array(
					'getpaid-admin-action' => 'download_graph',
					'file_type'            => urlencode( $file_type ),
					'graph'                => urlencode( $graph ),
				)
			),
			'getpaid-nonce',
			'getpaid-nonce'
		);

	}

	/**
	 * Displays the right side.
	 *
	 */
	public function display_right() {

		?>

			<?php foreach ( $this->views as $key => $view ) : ?>
				<div class="row mb-4">
					<div class="col-12">
						<div class="card m-0 p-0" style="max-width:100%">
							<div class="card-header">
								<div class="row">
									<div class="col-9">
										<strong><?php echo $view['label']; ?></strong>
									</div>
									<div class="col-3">
										<a title="<?php esc_attr_e( 'Download JSON', 'invoicing' ); ?>" href="<?php echo esc_url( $this->get_download_url( $key, 'json' ) ); ?>">
											<i class="fa fa-download text-dark" style="font-size: 16px" aria-hidden="true"></i>
											<span class="screen-reader-text"><?php _e( 'Download JSON', 'invoicing' ); ?></span>
										</a>
										<a title="<?php esc_attr_e( 'Download CSV', 'invoicing' ); ?>" href="<?php echo esc_url( $this->get_download_url( $key, 'csv' ) ); ?>">
											<i class="fa fa-file-csv text-dark" style="font-size: 16px" aria-hidden="true"></i>
											<span class="screen-reader-text"><?php _e( 'Download CSV', 'invoicing' ); ?></span>
										</a>
										<a title="<?php esc_attr_e( 'Download XML', 'invoicing' ); ?>" href="<?php echo esc_url( $this->get_download_url( $key, 'xml' ) ); ?>">
											<i class="fa fa-file-code text-dark" style="font-size: 16px" aria-hidden="true"></i>
											<span class="screen-reader-text"><?php _e( 'Download XML', 'invoicing' ); ?></span>
										</a>
									</div>
								</div>
							</div>
							<div class="card-body">
								<?php
									$class = $view['class'];
									$class = new $class();
									$class->display_stats();
								?>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

		<?php

	}

	/**
	 * Returns a list of report cards.
	 *
	 */
	public function get_cards() {

		$cards = array(
			'total_sales' => array(
				'description' => __( 'Gross sales in the period.', 'invoicing' ),
				'label'       => __( 'Gross Revenue', 'invoicing' ),
			),
			'net_sales' => array(
				'description' => __( 'Net sales in the period.', 'invoicing' ),
				'label'       => __( 'Net Revenue', 'invoicing' ),
			),
			'average_sales' => array(
				'description' => __( 'Average net daily/monthly sales.', 'invoicing' ),
				'label'       => __( 'Avg. Net Sales', 'invoicing' ),
			),
			'average_total_sales' => array(
				'description' => __( 'Average gross daily/monthly sales.', 'invoicing' ),
				'label'       => __( 'Avg. Gross Sales', 'invoicing' ),
			),
			'total_invoices'  => array(
				'description' => __( 'Number of paid invoices.', 'invoicing' ),
				'label'       => __( 'Paid Invoices', 'invoicing' ),
			),
			'total_items' => array(
				'description' => __( 'Number of items purchased.', 'invoicing' ),
				'label'       => __( 'Purchased Items', 'invoicing' ),
			),
			'refunded_items' => array(
				'description' => __( 'Number of items refunded.', 'invoicing' ),
				'label'       => __( 'Refunded Items', 'invoicing' ),
			),
			'total_tax' => array(
				'description' => __( 'Total charged for taxes.', 'invoicing' ),
				'label'       => __( 'Tax', 'invoicing' ),
			),
			'total_refunded_tax' => array(
				'description' => __( 'Total refunded for taxes.', 'invoicing' ),
				'label'       => __( 'Refunded Tax', 'invoicing' ),
			),
			'total_fees' => array(
				'description' => __( 'Total fees charged.', 'invoicing' ),
				'label'       => __( 'Fees', 'invoicing' ),
			),
			'total_refunds' => array(
				'description' => __( 'Total of refunded invoices.', 'invoicing' ),
				'label'       => __( 'Refunded', 'invoicing' ),
			),
			'total_discount'  => array(
				'description' => __( 'Total of discounts used.', 'invoicing' ),
				'label'       => __( 'Discounted', 'invoicing' ),
			),
		);

		return apply_filters( 'wpinv_report_cards', $cards );
	}

	

}
