<?php
/**
 * REST API reports controller
 *
 * Handles requests to the /reports endpoint.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST reports controller class.
 *
 * @package Invoicing
 */
class GetPaid_REST_Reports_Controller extends GetPaid_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports';

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 2.0.0
	 *
	 * @see register_rest_route()
	 */
	public function register_namespace_routes( $namespace ) {

		// List all available reports.
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
	 * Get reports list.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_reports() {

		$reports = array(
			array(
				'slug'        => 'sales',
				'description' => __( 'List of sales reports.', 'invoicing' ),
			),
			array(
				'slug'        => 'top_sellers',
				'description' => __( 'List of top selling items.', 'invoicing' ),
			),
			array(
				'slug'        => 'top_earners',
				'description' => __( 'List of top earning items.', 'invoicing' ),
			),
			array(
				'slug'        => 'invoices/counts',
				'description' => __( 'Invoice counts.', 'invoicing' ),
			),
		);

		return apply_filters( 'getpaid_available_api_reports', $reports );

	}

	/**
	 * Get all reports.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$data    = array();
		$reports = $this->get_reports();

		foreach ( $reports as $report ) {
			$item   = $this->prepare_item_for_response( (object) $report, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @since 2.0.0
	 * @param stdClass $report Report data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $report, $request ) {
		$data = array(
			'slug'        => $report->slug,
			'description' => $report->description,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $report->slug ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		) );

		return apply_filters( 'getpaid_rest_prepare_report', $response, $report, $request );
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report',
			'type'       => 'object',
			'properties' => array(
				'slug' => array(
					'description' => __( 'An alphanumeric identifier for the resource.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'A human-readable description of the resource.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}
