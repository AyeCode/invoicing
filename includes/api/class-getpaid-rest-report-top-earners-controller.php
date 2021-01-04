<?php
/**
 * REST API top earners controller
 *
 * Handles requests to the reports/top_earners endpoint.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST top earners controller class.
 *
 * @package GetPaid
 */
class GetPaid_REST_Report_Top_Earners_Controller extends GetPaid_REST_Report_Top_Sellers_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/top_earners';

	/**
	 * Get all data needed for this report and store in the class.
	 */
	protected function query_report_data() {

		$this->report_data = GetPaid_Reports_Helper::get_invoice_report_data(
			array(
				'data'              => array(
					'quantity'      => array(
						'type'            => 'invoice_item',
						'function'        => 'SUM',
						'name'            => 'invoice_item_qty',
					),
					'item_id'             => array(
						'type'            => 'invoice_item',
						'function'        => '',
						'name'            => 'invoice_item_id',
					),
					'item_name'           => array(
						'type'            => 'invoice_item',
						'function'        => '',
						'name'            => 'invoice_item_name',
					),
					'price'               => array(
						'type'            => 'invoice_item',
						'function'        => 'SUM',
						'name'            => 'invoice_item_price',
					),
				),
				'group_by'       => 'invoice_item_id',
				'order_by'       => 'invoice_item_price DESC',
				'query_type'     => 'get_results',
				'limit'          => 10,
				'filter_range'   => $this->report_range,
			)
		);

	}

}
