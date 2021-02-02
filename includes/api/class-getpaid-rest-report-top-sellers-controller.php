<?php
/**
 * REST API top sellers controller
 *
 * Handles requests to the reports/top_sellers endpoint.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST top sellers controller class.
 *
 * @package GetPaid
 */
class GetPaid_REST_Report_Top_Sellers_Controller extends GetPaid_REST_Report_Sales_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/top_sellers';

	/**
	 * Get top sellers report.
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {

		// Prepare items.
		$this->report_range = $this->get_date_range( $request );
		$report_data        = $this->get_report_data();

		$top_sellers = array();

		foreach ( $report_data as $item ) {

			$item_obj  = new WPInv_Item( $item );
			$item_name = $item->invoice_item_name;
			$item_qty  = floatval( $item->invoice_item_qty );
			$item_id   = absint( $item->invoice_item_id );
			$price     = sanitize_text_field( wpinv_price( $item->invoice_item_price ) );

			$item_obj  = new WPInv_Item( $item_id );

			if ( $item_obj->exists() ) {
				$item_name = $item_obj->get_name();
			} else {
				$item_id   = 0; 
			}

			$top_sellers[] = array(
				'name'               =>sanitize_text_field( $item_name ),
				'item_id'            => $item_id,
				'quantity'           => $item_qty,
				'earnings'           => wpinv_round_amount( $item->invoice_item_price ),
				'earnings_formatted' => sanitize_text_field( wpinv_price( $price ) ),
			);

		}

		$data = array();
		foreach ( $top_sellers as $top_seller ) {
			$item   = $this->prepare_item_for_response( (object) $top_seller, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );

	}

	/**
	 * Prepare a report sales object for serialization.
	 *
	 * @param stdClass $top_seller
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $top_seller, $request ) {
		$data    = (array) $top_seller;

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$links = array(
			'about' => array(
				'href' => rest_url( sprintf( '%s/reports', $this->namespace ) ),
			),
		);

		if ( ! empty( $top_seller->item_id ) ) {
			$links['item']   = array(
				'href'       => rest_url( sprintf( '/%s/items/%s', $this->namespace, $top_seller->item_id ) ),
				'embeddable' => true,
			);
		}

		$response->add_links( $links );
		return apply_filters( 'getpaid_rest_prepare_report_' . $this->rest_base, $response, $top_seller, $request );
	}

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
				'order_by'       => 'invoice_item_qty DESC',
				'query_type'     => 'get_results',
				'limit'          => 10,
				'filter_range'   => $this->report_range,
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
			'title'      => $this->rest_base,
			'type'       => 'object',
			'properties' => array(
				'name' => array(
					'description' => __( 'Item name.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'item_id'         => array(
					'description' => __( 'Item ID.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'quantity' => array(
					'description' => __( 'Total number of purchases.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'earnings' => array(
					'description' => __( 'Total earnings for the item.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'earnings_formatted"' => array(
					'description' => __( 'Total earnings (formatted) for the item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
