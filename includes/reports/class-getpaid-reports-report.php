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
	 * Displays the right side.
	 *
	 */
	public function display_right() {

		?>

			<?php foreach ( $this->views as $view ) : ?>
				<div class="row mb-4">
					<div class="col-12">
						<div class="card m-0 p-0" style="max-width:100%">
							<div class="card-header">
								<strong><?php echo $view['label']; ?></strong>
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
