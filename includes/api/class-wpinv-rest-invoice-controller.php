<?php
/**
 * REST invoices controllers.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API invoices controller class.
 *
 * @package Invoicing
 */
class WPInv_REST_Invoice_Controller extends GetPaid_REST_Posts_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wpi_invoice';

	/**
	 * The base of this controller's route.
	 *
	 * @since 1.0.13
	 * @var string
	 */
	protected $rest_base = 'invoices';

	/** Contains this controller's class name.
	 *
	 * @var string
	 */
	public $crud_class = 'WPInv_Invoice';

    /**
	 * Retrieves the query params for the invoices collection.
	 *
	 * @since 1.0.13
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {

		$params = array_merge(

			parent::get_collection_params(),

			array(


				'customers' => array(
					'description'       => __( 'Limit result set to invoices for specific user ids.', 'invoicing' ),
					'type'              => 'array',
					'items'             => array(
						'type'          => 'integer',
					),
					'default'           => array(),
					'sanitize_callback' => 'wp_parse_id_list',
				),

				'exclude_customers'  	=> array(
					'description' 		=> __( 'Exclude invoices to specific users.', 'invoicing' ),
					'type'        		=> 'array',
					'items'       		=> array(
						'type'          => 'integer',
					),
					'default'     		=> array(),
					'sanitize_callback' => 'wp_parse_id_list',
				)

			)

		);

		// Filter collection parameters for the invoices controller.
		return apply_filters( 'getpaid_rest_invoices_collection_params', $params, $this );
	}

	/**
	 * Get all the WP Query vars that are allowed for the API request.
	 *
	 * @return array
	 */
	protected function get_allowed_query_vars() {

		$vars = array_merge(
			array(
				'customers',
				'exclude_customers'
			),
			parent::get_allowed_query_vars()
		);

		return apply_filters( 'getpaid_rest_invoices_allowed_query_vars', $vars, $this );
	}

	/**
	 * Determine the allowed query_vars for a get_items() response and
	 * prepare for WP_Query.
	 *
	 * @param array           $prepared_args Prepared arguments.
	 * @param WP_REST_Request $request Request object.
	 * @return array          $query_args
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {

		$query_args = parent::prepare_items_query( $prepared_args );

		// Retrieve invoices for specific customers.
		if (  isset( $query_args['customers'] ) ) {
			$query_args['author__in'] = $query_args['customers'];
			unset( $query_args['customers'] );
		}

		// Skip invoices for specific customers.
		if (  isset( $query_args['exclude_customers'] ) ) {
			$query_args['author__not_in'] = $query_args['exclude_customers'];
			unset( $query_args['exclude_customers'] );
		}

		return apply_filters( 'getpaid_rest_invoices_prepare_items_query', $query_args, $request, $this );

	}

	/**
	 * Retrieves a valid list of post statuses.
	 *
	 * @since 1.0.15
	 *
	 * @return array A list of registered item statuses.
	 */
	public function get_post_statuses() {
		return array_keys( wpinv_get_invoice_statuses( true ) );
	}

	/**
	 * Saves a single invoice.
	 *
	 * @param WPInv_Invoice $invoice Invoice to save.
	 * @return WP_Error|WPInv_Invoice
	 */
	protected function save_object( $invoice ) {
		$invoice->recalculate_total();
		return parent::save_object( $invoice );
	}

}
