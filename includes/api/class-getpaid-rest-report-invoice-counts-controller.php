<?php
/**
 * REST API invoice counts controller
 *
 * Handles requests to the reports/invoice/counts endpoint.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST invoice counts controller class.
 *
 * @package GetPaid
 */
class GetPaid_REST_Report_Invoice_Counts_Controller extends GetPaid_REST_Reports_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/invoices/counts';

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param  stdClass        $report Report data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $report, $request ) {

		$data    = (array) $report;
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links(
			array(
				'about' => array(
					'href' => rest_url( sprintf( '%s/reports', $this->namespace ) ),
				),
			)
		);

		return apply_filters( 'getpaid_rest_prepare_report_invoices_count', $response, $report, $request );
	}

	/**
	 * Get reports list.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_reports() {

		$counts = wp_count_posts( 'wpi_invoice' );
		$data   = array();

		foreach ( wpinv_get_invoice_statuses() as $slug => $name ) {

			if ( ! isset( $counts->$slug ) ) {
				continue;
			}

			$data[] = array(
				'slug'  => $slug,
				'name'  => $name,
				'count' => (int) $counts->$slug,
			);

		}

		return $data;

	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_invoice_counts',
			'type'       => 'object',
			'properties' => array(
				'slug'  => array(
					'description' => __( 'An alphanumeric identifier for the resource.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'  => array(
					'description' => __( 'Invoice status name.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'count' => array(
					'description' => __( 'Number of invoices.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
