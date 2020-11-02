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
				),

				'parent'  	            => array(
					'description'       => __( 'Limit result set to those of particular parent IDs.', 'invoicing' ),
					'type'              => 'array',
					'items'             => array(
						'type'          => 'integer',
					),
					'sanitize_callback' => 'wp_parse_id_list',
					'default'           => array(),
				),

				'parent_exclude'  	    => array(
					'description'       => __( 'Limit result set to all items except those of a particular parent ID.', 'invoicing' ),
					'type'              => 'array',
					'items'             => array(
						'type'          => 'integer',
					),
					'sanitize_callback' => 'wp_parse_id_list',
					'default'           => array(),
				),

			)

		);

		// Filter collection parameters for the invoices controller.
		return apply_filters( 'getpaid_rest_invoices_collection_params', $params, $this );
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
		if ( ! empty( $request['customers'] ) ) {
			$query_args['author__in'] = $request['customers'];
		}

		// Skip invoices for specific customers.
		if ( ! empty( $request['exclude_customers'] ) ) {
			$query_args['author__not_in'] = $request['exclude_customers'];
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
		return array_keys( wpinv_get_invoice_statuses( true, false, $this->post_type ) );
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
