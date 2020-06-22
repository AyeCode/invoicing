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
 * WPInv_Gateawy_Reports_Table Class
 *
 * Renders the Gateway Reports table
 *
 * @since 1.0.19
 */
class WPInv_Gateways_Report_Table extends WP_List_Table {

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
		return 'gateway';
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
			'gateway'  => __( 'Gateway', 'invoicing' ),
			'sales'    => __( 'Total Sales', 'invoicing' ),
			'total'    => __( 'Total Earnings', 'invoicing' ),
			'discount' => __( 'Total Discounts', 'invoicing' ),
			'tax'      => __( 'Total Taxes', 'invoicing' ),
			'fees'     => __( 'Total Fees', 'invoicing' ),
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
	 * @return array $reports_data All the data for gateway reports
	 */
	public function reports_data() {

		$reports_data = $this->revenue_reports_data();
		$gateways     = wpinv_get_payment_gateways();

		foreach ( $gateways as $gateway_id => $gateway ) {

			if ( ! empty( $reports_data[ $gateway_id ] ) ) {
				continue;
			}

			$reports_data[] = array(
				'gateway'  => $gateway_id,
				'sales'    => 0,
				'total'    => 0,
				'discount' => 0,
				'tax'      => 0,
				'fees'     => 0,
			);
		}

		$prepared = array();
		foreach ( $reports_data as $report_data ) {
			$prepared[] = array(
				'gateway'  => wpinv_get_gateway_admin_label( $report_data['gateway'] ),
				'sales'    => $report_data['sales'],
				'total'    => wpinv_price( wpinv_format_amount( $report_data['total'] ) ),
				'discount' => wpinv_price( wpinv_format_amount( $report_data['discount'] ) ),
				'tax'      => wpinv_price( wpinv_format_amount( $report_data['tax'] ) ),
				'fees'     => wpinv_price( wpinv_format_amount( $report_data['fees'] ) ),
			);
		}

		return $prepared;
	}

	/**
	 * Retrieves report data.
	 *
	 * @since 1.0.19
	 */
	public function revenue_reports_data() {
		global $wpdb;

		$table =  $wpdb->prefix . 'getpaid_invoices';
		$data  = $wpdb->get_results(
			"SELECT
				COUNT(posts.ID) as sales,
				meta.gateway as gateway,
				SUM(meta.total) as total,
				SUM(meta.discount) as discount,
				SUM(meta.tax) as tax,
				SUM(meta.fees_total) as fees
			FROM $wpdb->posts as posts
			LEFT JOIN $table as meta ON meta.post_id = posts.ID
			WHERE
				meta.post_id IS NOT NULL
				AND posts.post_type = 'wpi_invoice'
                AND ( posts.post_status = 'publish' OR posts.post_status = 'renewal' )
			GROUP BY meta.gateway", ARRAY_A);
		
		$return = array();

		foreach ( $data as $gateway ) {
			$return[ $gateway ['gateway']] = $gateway;
		}

		return $return;

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
