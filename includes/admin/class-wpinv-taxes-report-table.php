<?php
/**
 * Gateways Reports Table Class
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WPInv_Taxes_Reports_Table Class
 *
 * Renders the Gateway Reports table
 *
 * @since 1.0.19
 */
class WPInv_Taxes_Reports_Table extends WP_List_Table {

	/**
	 * @var int Number of items per page
	 * @since 1.0.19
	 */
	public $per_page = 300;


	/**
	 * Get things started
	 *
	 * @since 1.0.19
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		// Set parent defaults
		parent::__construct( array(
			'singular' => 'id',
			'plural'   => 'ids',
			'ajax'     => false,
		) );

	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 1.0.19
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'month';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @since 1.0.19
	 *
	 * @param array $item Contains all the data of the gateways
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		return esc_html( $item[ $column_name ] );
	}

	/**
	 * Retrieve the table columns
	 *
	 * @since 1.0.19
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {

		return array(
			'month'    => __( 'Month', 'invoicing' ),
			'tax'      => __( 'Total Taxes', 'invoicing' ),
		);

	}

	/**
	 * Retrieve the current page number
	 *
	 * @since 1.0.19
	 * @return int Current page number
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Outputs the reporting views
	 *
	 * @since 1.0.19
	 * @return void
	 */
	public function bulk_actions( $which = '' ) {
		return array();
	}

	/**
	 * Build all the reports data
	 *
	 * @since 1.0.19
	 * @return array $reports_data All the data for taxes reports
	 */
	public function reports_data() {

		$reports_data = $this->taxes_reports_data();
		$months       = array(
			'1' => __( 'January', 'invoicing' ),
			'2' => __( 'February', 'invoicing' ),
			'3' => __( 'March', 'invoicing' ),
			'4' => __( 'April', 'invoicing' ),
			'5' => __( 'May', 'invoicing' ),
			'6' => __( 'June', 'invoicing' ),
			'7' => __( 'July', 'invoicing' ),
			'8' => __( 'August', 'invoicing' ),
			'9' => __( 'September', 'invoicing' ),
			'10' => __( 'October', 'invoicing' ),
			'11' => __( 'November', 'invoicing' ),
			'12' => __( 'December', 'invoicing' ),
		);

		$prepared = array();
		foreach ( $months as $month => $label ) {

			$tax = wpinv_price( 0 );
			if ( ! empty( $reports_data[ $month ] ) ) {
				$tax = wpinv_price( wpinv_format_amount( $reports_data[ $month ] ) );
			}

			$prepared[] = array(
				'month'    => $label,
				'tax'      => $tax,
			);

		}

		return $prepared;
	}

	/**
	 * Retrieves taxes data.
	 *
	 * @since 1.0.19
	 */
	public function taxes_reports_data() {
		global $wpdb;

		$table =  $wpdb->prefix . 'getpaid_invoices';
		$year  = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : date( 'Y' );
		$data  = $wpdb->get_results(
			"SELECT
				MONTH(meta.completed_date) as _month,
				SUM(meta.tax) as tax
			FROM $wpdb->posts as posts
			LEFT JOIN $table as meta ON meta.post_id = posts.ID
			WHERE
				meta.post_id IS NOT NULL
				AND posts.post_type = 'wpi_invoice'
                AND ( posts.post_status = 'publish' OR posts.post_status = 'renewal' )
				AND ( YEAR(meta.completed_date) = '$year' )
			GROUP BY MONTH(meta.completed_date)");

		return wp_list_pluck( $data, 'tax', '_month' );

	}

	/**
	 * Setup the final data for the table
	 *
	 * @since 1.0.19
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->reports_data();
	}
}
