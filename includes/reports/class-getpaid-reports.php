<?php
/**
 * Contains the reports class
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Reports Class.
 */
class GetPaid_Reports {

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_reports_page' ), 20 );
		add_action( 'wpinv_reports_tab_reports', array( $this, 'display_reports_tab' ) );
		add_action( 'wpinv_reports_tab_export', array( $this, 'display_exports_tab' ) );
		add_action( 'getpaid_authenticated_admin_action_download_graph', array( $this, 'download_graph' ) );
		add_action( 'getpaid_authenticated_admin_action_export_invoices', array( $this, 'export_invoices' ) );

	}

	/**
	 * Registers the reports page.
	 *
	 */
	public function register_reports_page() {

		add_submenu_page(
            'wpinv',
            __( 'Reports', 'invoicing' ),
            __( 'Reports', 'invoicing' ),
            wpinv_get_capability(),
            'wpinv-reports',
            array( $this, 'display_reports_page' )
		);

	}

	/**
	 * Displays the reports page.
	 *
	 */
	public function display_reports_page() {

		// Prepare variables.
		$tabs        = $this->get_tabs();
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'reports';
		$current_tab = array_key_exists( $current_tab, $tabs ) ? $current_tab : 'reports';

		// Display the current tab.
		?>

        <div class="wrap">

			<h1><?php echo sanitize_text_field( $tabs[ $current_tab ] ); ?></h1>

			<nav class="nav-tab-wrapper">

				<?php
					foreach( $tabs as $key => $label ) {

						$key   = sanitize_text_field( $key );
						$label = sanitize_text_field( $label );
						$class = $key == $current_tab ? 'nav-tab nav-tab-active' : 'nav-tab';
						$url   = esc_url(
							add_query_arg( 'tab', $key, admin_url( 'admin.php?page=wpinv-reports' ) )
						);

						echo "\n\t\t\t<a href='$url' class='$class'>$label</a>";

					}
				?>

			</nav>

			<div class="bsui <?php echo esc_attr( $current_tab ); ?>">
				<?php do_action( "wpinv_reports_tab_{$current_tab}" ); ?>
			</div>

        </div>
		<?php

			// Wordfence loads an unsupported version of chart js on our page.
			wp_deregister_style( 'chart-js' );
			wp_deregister_script( 'chart-js' );
			wp_enqueue_script( 'chart-js', WPINV_PLUGIN_URL . 'assets/js/chart.bundle.min.js', array( 'jquery' ), '2.9.4', true );
			wp_enqueue_style( 'chart-js', WPINV_PLUGIN_URL . 'assets/css/chart.min.css', array(), '2.9.4' );

	}

	/**
	 * Retrieves reports page tabs.
	 *
	 * @return array
	 */
	public function get_tabs() {

		$tabs = array(
			'reports' => __( 'Reports', 'invoicing' ),
			'export'  => __( 'Export', 'invoicing' ),
		);

		return apply_filters( 'getpaid_report_tabs', $tabs );
	}

	/**
	 * Displays the reports tab.
	 *
	 */
	public function display_reports_tab() {

		$reports = new GetPaid_Reports_Report();
		$reports->display();

	}

	/**
	 * Displays the exports tab.
	 *
	 */
	public function display_exports_tab() {

		$exports = new GetPaid_Reports_Export();
		$exports->display();

	}

	/**
	 * Donwnloads a graph.
	 *
	 * @param array $args
	 */
	public function download_graph( $args ) {

		if ( ! empty( $args['graph'] ) ) {
			$downloader = new GetPaid_Graph_Downloader();
			$downloader->download( $args['graph'] );
		}

	}

	/**
	 * Exports invoices.
	 *
	 * @param array $args
	 */
	public function export_invoices( $args ) {

		if ( ! empty( $args['post_type'] ) ) {
			$downloader = new GetPaid_Invoice_Exporter();
			$downloader->export( $args['post_type'], $args );
		}

	}

}
