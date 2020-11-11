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
	 * Displays the reports tab.
	 *
	 */
	public function display() {
		?>

		<div class="mt-4" style="max-width: 1200px;">

			<div class="row">
				<div class="col-12 col-md-8">
					<?php echo $this->display_left(); ?>
				</div>

				<div class="col-12 col-md-4">
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
		$earnings = new GetPaid_Reports_Report_Earnings();
		$earnings->display();
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

}
