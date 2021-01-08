<?php
/**
 * REST API reports controller
 *
 * Handles requests to the /reports/sales endpoint.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST reports controller class.
 *
 * @package GetPaid
 */
class GetPaid_REST_Report_Sales_Controller extends GetPaid_REST_Date_Based_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/sales';

	/**
	 * The report data.
	 *
	 * @var stdClass
	 */
	protected $report_data;

	/**
	 * The report range.
	 *
	 * @var array
	 */
	protected $report_range;

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 2.0.0
	 *
	 * @see register_rest_route()
	 */
	public function register_namespace_routes( $namespace ) {

		// Get sales report.
		register_rest_route(
			$namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Makes sure the current user has access to READ the report APIs.
	 *
	 * @since  2.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! wpinv_current_user_can_manage_invoicing() ) {
			return new WP_Error( 'rest_cannot_view', __( 'Sorry, you cannot list resources.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get sales reports.
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$data   = array();
		$item   = $this->prepare_item_for_response( null, $request );
		$data[] = $this->prepare_response_for_collection( $item );

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a report sales object for serialization.
	 *
	 * @param null $_
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $_, $request ) {

		// Set report range.
		$this->report_range = $this->get_date_range( $request );

		$report_data     = $this->get_report_data();
		$period_totals   = array();

		// Setup period totals by ensuring each period in the interval has data.
		$start_date      = strtotime( $this->report_range['after'] ) + DAY_IN_SECONDS;

		if ( 'month' === $this->groupby ) {
			$start_date      = strtotime( date( 'Y-m-01', $start_date ) );
		}

		for ( $i = 0; $i < $this->interval; $i++ ) {

			switch ( $this->groupby ) {
				case 'day' :
					$time = date( 'Y-m-d', strtotime( "+{$i} DAY", $start_date ) );
					break;
				default :
					$time = date( 'Y-m', strtotime( "+{$i} MONTH", $start_date ) );
					break;
			}

			// Set the defaults for each period.
			$period_totals[ $time ] = array(
				'sales'             => wpinv_round_amount( 0.00 ),
				'invoices'          => 0,
				'refunds'           => wpinv_round_amount( 0.00 ),
				'items'             => 0,
				'refunded_items'    => 0,
				'tax'               => wpinv_round_amount( 0.00 ),
				'refunded_tax'      => wpinv_round_amount( 0.00 ),
				'subtotal'          => wpinv_round_amount( 0.00 ),
				'refunded_subtotal' => wpinv_round_amount( 0.00 ),
				'fees'              => wpinv_round_amount( 0.00 ),
				'refunded_fees'     => wpinv_round_amount( 0.00 ),
				'discount'          => wpinv_round_amount( 0.00 ),
			);

		}

		// add total sales, total invoice count, total tax for each period
		$date_format = ( 'day' === $this->groupby ) ? 'Y-m-d' : 'Y-m';
		foreach ( $report_data->invoices as $invoice ) {
			$time = date( $date_format, strtotime( $invoice->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) ) {
				continue;
			}

			$period_totals[ $time ]['sales']    = wpinv_round_amount( $invoice->total_sales );
			$period_totals[ $time ]['tax']      = wpinv_round_amount( $invoice->total_tax );
			$period_totals[ $time ]['subtotal'] = wpinv_round_amount( $invoice->subtotal );
			$period_totals[ $time ]['fees']     = wpinv_round_amount( $invoice->total_fees );

		}

		foreach ( $report_data->refunds as $invoice ) {
			$time = date( $date_format, strtotime( $invoice->post_date ) );

			if ( ! isset( $period_totals[ $time ] ) ) {
				continue;
			}

			$period_totals[ $time ]['refunds']           = wpinv_round_amount( $invoice->total_sales );
			$period_totals[ $time ]['refunded_tax']      = wpinv_round_amount( $invoice->total_tax );
			$period_totals[ $time ]['refunded_subtotal'] = wpinv_round_amount( $invoice->subtotal );
			$period_totals[ $time ]['refunded_fees']     = wpinv_round_amount( $invoice->total_fees );

		}

		foreach ( $report_data->invoice_counts as $invoice ) {
			$time = date( $date_format, strtotime( $invoice->post_date ) );

			if ( isset( $period_totals[ $time ] ) ) {
				$period_totals[ $time ]['invoices']   = (int) $invoice->count;
			}

		}

		// Add total invoice items for each period.
		foreach ( $report_data->invoice_items as $invoice_item ) {
			$time = ( 'day' === $this->groupby ) ? date( 'Y-m-d', strtotime( $invoice_item->post_date ) ) : date( 'Y-m', strtotime( $invoice_item->post_date ) );

			if ( isset( $period_totals[ $time ] ) ) {
				$period_totals[ $time ]['items'] = (int) $invoice_item->invoice_item_count;
			}

		}

		// Add total discount for each period.
		foreach ( $report_data->coupons as $discount ) {
			$time = ( 'day' === $this->groupby ) ? date( 'Y-m-d', strtotime( $discount->post_date ) ) : date( 'Y-m', strtotime( $discount->post_date ) );

			if ( isset( $period_totals[ $time ] ) ) {
				$period_totals[ $time ]['discount'] = wpinv_round_amount( $discount->discount_amount );
			}

		}

		$report_data->totals            = $period_totals;
		$report_data->grouped_by        = $this->groupby;
		$report_data->interval          = max( $this->interval, 1 );
		$report_data->currency          = wpinv_get_currency();
		$report_data->currency_symbol   = wpinv_currency_symbol();
		$report_data->currency_position = wpinv_currency_position();
		$report_data->decimal_places    = wpinv_decimals();
		$report_data->thousands_sep     = wpinv_thousands_separator();
		$report_data->decimals_sep      = wpinv_decimal_separator();
		$report_data->start_date        = date( 'Y-m-d', strtotime( $this->report_range['after'] ) + DAY_IN_SECONDS );
		$report_data->end_date          = date( 'Y-m-d', strtotime( $this->report_range['before'] ) - DAY_IN_SECONDS );
		$report_data->start_date_locale = getpaid_format_date( date( 'Y-m-d', strtotime( $this->report_range['after'] ) + DAY_IN_SECONDS ) );
		$report_data->end_date_locale   = getpaid_format_date( date( 'Y-m-d', strtotime( $this->report_range['before'] ) - DAY_IN_SECONDS ) );
		$report_data->decimals_sep      = wpinv_decimal_separator();

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $report_data;
		unset( $data->invoice_counts, $data->invoices, $data->coupons, $data->refunds, $data->invoice_items );
		$data    = $this->add_additional_fields_to_object( (array) $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( array(
			'about' => array(
				'href' => rest_url( sprintf( '%s/reports', $this->namespace ) ),
			),
		) );

		return apply_filters( 'getpaid_rest_prepare_report_sales', $response, $report_data, $request );
	}

	/**
	 * Get report data.
	 *
	 * @return stdClass
	 */
	public function get_report_data() {
		if ( empty( $this->report_data ) ) {
			$this->query_report_data();
		}
		return $this->report_data;
	}

	/**
	 * Get all data needed for this report and store in the class.
	 */
	protected function query_report_data() {

		// Prepare reports.
		$this->report_data = (object) array(
			'invoice_counts' => $this->query_invoice_counts(),//count, post_date
			'coupons'        => $this->query_coupon_counts(), // discount_amount, post_date
			'invoice_items'  => $this->query_item_counts(), // invoice_item_count, post_date
			'refunded_items' => $this->count_refunded_items(), // invoice_item_count, post_date
			'invoices'       => $this->query_invoice_totals(), // total_sales, total_tax, total_discount, total_fees, subtotal, post_date
			'refunds'        => $this->query_refunded_totals(), // total_sales, total_tax, total_discount, total_fees, subtotal, post_date
			'previous_range' => $this->previous_range,
		);

		// Calculated totals.
		$this->report_data->total_tax          = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->invoices, 'total_tax' ) ) );
		$this->report_data->total_sales        = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->invoices, 'total_sales' ) ) );
		$this->report_data->total_discount     = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->invoices, 'total_discount' ) ) );
		$this->report_data->total_fees         = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->invoices, 'total_fees' ) ) );
		$this->report_data->subtotal           = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->invoices, 'subtotal' ) ) );
		$this->report_data->net_sales          = wpinv_round_amount( $this->report_data->total_sales - max( 0, $this->report_data->total_tax ) );
		$this->report_data->total_refunded_tax = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->refunds, 'total_tax' ) ) );
		$this->report_data->total_refunds      = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->refunds, 'total_sales' ) ) );
		$this->report_data->refunded_discount  = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->refunds, 'total_discount' ) ) );
		$this->report_data->refunded_fees      = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->refunds, 'total_fees' ) ) );
		$this->report_data->refunded_subtotal  = wpinv_round_amount( array_sum( wp_list_pluck( $this->report_data->refunds, 'subtotal' ) ) );
		$this->report_data->net_refunds        = wpinv_round_amount( $this->report_data->total_refunds + max( 0, $this->report_data->total_refunded_tax ) );


		// Calculate average based on net.
		$this->report_data->average_sales       = wpinv_round_amount( $this->report_data->net_sales / max( $this->interval, 1 ), 2 );
		$this->report_data->average_total_sales = wpinv_round_amount( $this->report_data->total_sales / max( $this->interval, 1 ), 2 );

		// Total invoices in this period, even if refunded.
		$this->report_data->total_invoices = absint( array_sum( wp_list_pluck( $this->report_data->invoice_counts, 'count' ) ) );

		// Items invoiced in this period, even if refunded.
		$this->report_data->total_items = absint( array_sum( wp_list_pluck( $this->report_data->invoice_items, 'invoice_item_count' ) ) );

		// 3rd party filtering of report data
		$this->report_data = apply_filters( 'getpaid_rest_api_filter_report_data', $this->report_data );
	}

	/**
	 * Prepares invoice counts.
	 *
	 * @return array.
	 */
	protected function query_invoice_counts() {

		return (array) GetPaid_Reports_Helper::get_invoice_report_data(
			array(
				'data'         => array(
					'ID'        => array(
						'type'     => 'post_data',
						'function' => 'COUNT',
						'name'     => 'count',
						'distinct' => true,
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'       => $this->get_group_by_sql( 'posts.post_date' ),
				'order_by'       => 'post_date ASC',
				'query_type'     => 'get_results',
				'filter_range'   => $this->report_range,
				'invoice_status' => array( 'publish', 'wpi-processing', 'wpi-onhold', 'wpi-refunded', 'wpi-renewal' ),
			)
		);

	}

	/**
	 * Prepares coupon counts.
	 *
	 * @return array.
	 */
	protected function query_coupon_counts() {

		return (array) GetPaid_Reports_Helper::get_invoice_report_data(
			array(
				'data'         => array(
					'discount' => array(
						'type'     => 'invoice_data',
						'function' => 'SUM',
						'name'     => 'discount_amount',
					),
					'post_date'       => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'       => $this->get_group_by_sql( 'posts.post_date' ),
				'order_by'       => 'post_date ASC',
				'query_type'     => 'get_results',
				'filter_range'   => $this->report_range,
				'invoice_status' => array( 'publish', 'wpi-processing', 'wpi-onhold', 'wpi-refunded', 'wpi-renewal' ),
			)
		);

	}

	/**
	 * Prepares item counts.
	 *
	 * @return array.
	 */
	protected function query_item_counts() {

		return (array) GetPaid_Reports_Helper::get_invoice_report_data(
			array(
				'data'         => array(
					'quantity'      => array(
						'type'            => 'invoice_item',
						'function'        => 'SUM',
						'name'            => 'invoice_item_count',
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'       => $this->get_group_by_sql( 'posts.post_date' ),
				'order_by'       => 'post_date ASC',
				'query_type'     => 'get_results',
				'filter_range'   => $this->report_range,
				'invoice_status' => array( 'publish', 'wpi-processing', 'wpi-onhold', 'wpi-refunded', 'wpi-renewal' ),
			)
		);

	}

	/**
	 * Prepares refunded item counts.
	 *
	 * @return array.
	 */
	protected function count_refunded_items() {

		return (int) GetPaid_Reports_Helper::get_invoice_report_data(
			array(
				'data'         => array(
					'quantity'      => array(
						'type'            => 'invoice_item',
						'function'        => 'SUM',
						'name'            => 'invoice_item_count',
					),
				),
				'query_type'     => 'get_var',
				'filter_range'   => $this->report_range,
				'invoice_status' => array( 'wpi-refunded' ),
			)
		);

	}

	/**
	 * Prepares daily invoice totals.
	 *
	 * @return array.
	 */
	protected function query_invoice_totals() {

		return (array) GetPaid_Reports_Helper::get_invoice_report_data(
			array(
				'data'         => array(
					'total'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'total_sales',
					),
					'tax'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'total_tax',
					),
					'discount'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'total_discount',
					),
					'fees_total'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'total_fees',
					),
					'subtotal'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'subtotal',
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'       => $this->get_group_by_sql( 'posts.post_date' ),
				'order_by'       => 'post_date ASC',
				'query_type'     => 'get_results',
				'filter_range'   => $this->report_range,
				'invoice_status' => array( 'publish', 'wpi-processing', 'wpi-onhold', 'wpi-renewal' ),
			)
		);

	}

	/**
	 * Prepares daily invoice totals.
	 *
	 * @return array.
	 */
	protected function query_refunded_totals() {

		return (array) GetPaid_Reports_Helper::get_invoice_report_data(
			array(
				'data'         => array(
					'total'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'total_sales',
					),
					'tax'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'total_tax',
					),
					'discount'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'total_discount',
					),
					'fees_total'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'total_fees',
					),
					'subtotal'      => array(
						'type'            => 'invoice_data',
						'function'        => 'SUM',
						'name'            => 'subtotal',
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date',
					),
				),
				'group_by'       => $this->get_group_by_sql( 'posts.post_date' ),
				'order_by'       => 'post_date ASC',
				'query_type'     => 'get_results',
				'filter_range'   => $this->report_range,
				'invoice_status' => array( 'wpi-refunded' ),
			)
		);

	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'sales_report',
			'type'       => 'object',
			'properties' => array(
				'total_sales' => array(
					'description' => __( 'Gross sales in the period.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'net_sales' => array(
					'description' => __( 'Net sales in the period.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'average_sales' => array(
					'description' => __( 'Average net daily sales.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'average_total_sales' => array(
					'description' => __( 'Average gross daily sales.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_invoices'  => array(
					'description' => __( 'Number of paid invoices.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_items' => array(
					'description' => __( 'Number of items purchased.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'refunded_items' => array(
					'description' => __( 'Number of items refunded.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_tax' => array(
					'description' => __( 'Total charged for taxes.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_refunded_tax' => array(
					'description' => __( 'Total refunded for taxes.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_fees' => array(
					'description' => __( 'Total fees charged.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_refunds' => array(
					'description' => __( 'Total of refunded invoices.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'net_refunds' => array(
					'description' => __( 'Net of refunded invoices.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_discount' => array(
					'description' => __( 'Total of discounts used.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'totals' => array(
					'description' => __( 'Totals.', 'invoicing' ),
					'type'        => 'array',
					'items'       => array(
						'type'    => 'array',
					),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'interval' => array(
					'description' => __( 'Number of months/days in the report period.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'previous_range'  => array(
					'description' => __( 'The previous report period.', 'invoicing' ),
					'type'        => 'array',
					'items'       => array(
						'type'    => 'string',
					),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'grouped_by' => array(
					'description' => __( 'The period used to group the totals.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'enum'        => array( 'day', 'month' ),
					'readonly'    => true,
				),
				'currency' => array(
					'description' => __( 'The default store currency.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'currency_symbol' => array(
					'description' => __( 'The default store currency symbol.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'currency_position' => array(
					'description' => __( 'The default store currency position.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'decimal_places' => array(
					'description' => __( 'The default store decimal places.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'thousands_sep' => array(
					'description' => __( 'The default store thousands separator.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'decimals_sep' => array(
					'description' => __( 'The default store decimals separator.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );

	}

}
