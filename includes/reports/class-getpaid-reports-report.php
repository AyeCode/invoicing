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
	 * @var string
	 */
	public $current_view;

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
            'earnings'   => __( 'Earnings', 'invoicing' ),
            'items'      => __( 'Items', 'invoicing' ),
            'gateways'   => __( 'Payment Methods', 'invoicing' ),
            'taxes'      => __( 'Taxes', 'invoicing' ),
        );

		$this->views        = apply_filters( 'wpinv_report_views', $this->views );
		$this->current_view = 'earnings';

		if ( isset( $_GET['view'] ) && array_key_exists( $_GET['view'], $this->views ) ) {
			$this->current_view = sanitize_text_field( $_GET['view'] );
		}

	}

	/**
	 * Displays the reports tab.
	 *
	 */
	public function display() {
		?>

			<form method="get" class="d-block mt-4 getpaid-change-view">

				<?php

					getpaid_hidden_field( 'page', 'wpinv-reports' );
					getpaid_hidden_field( 'tab', 'reports' );

					echo aui()->select(
						array(
							'name'        => 'view',
							'id'          => 'view' . uniqid( '_' ),
							'placeholder' => __( 'Select a report', 'invoicing' ),
							'label'       => __( 'Report Type', 'invoicing' ),
							'options'     => $this->views,
							'no_wrap'     => true,
						)
					);

					echo "&nbsp;";

					getpaid_submit_field( __( 'Show', 'invoicing' ), '', 'btn-secondary' );

				?>

	        </form>

		<?php

		$this->display_current_view();

	}

	/**
	 * Displays a single reports view.
	 *
	 */
	public function display_current_view() {

		$default_classes = array(
			'earnings'   => 'GetPaid_Reports_Report_Earnings',
			'items'      => 'GetPaid_Reports_Report_Items',
			'gateways'   => 'GetPaid_Reports_Report_Gateways',
			'taxes'      => 'GetPaid_Reports_Report_Taxes',
		);
		$class = 'GetPaid_Reports_Report_' . ucfirst( $this->current_view );

		if ( isset( $default_classes[ $this->current_view ] ) ) {

			$class = new $default_classes[ $this->current_view ]();
			$class->display();

		}

		do_action( 'wpinv_reports_tab' . $this->current_view );

	}

}
