<?php
/**
 * REST discounts controller.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API discounts controller class.
 *
 * @package Invoicing
 */
class WPInv_REST_Discounts_Controller extends GetPaid_REST_Posts_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wpi_discount';

	/**
	 * The base of this controller's route.
	 *
	 * @since 1.0.13
	 * @var string
	 */
	protected $rest_base = 'discounts';

	/** Contains this controller's class name.
	 *
	 * @var string
	 */
	public $crud_class = 'WPInv_Discount';

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.0.19
	 *
	 * @see register_rest_route()
	 */
	public function register_namespace_routes( $namespace ) {

		parent::register_namespace_routes( $namespace );

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/discount-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_discount_types' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

	}

	/**
	 * Handles rest requests for discount types.
	 *
	 * @since 1.0.13
	 *
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_discount_types() {
		return rest_ensure_response( wpinv_get_discount_types() );
	}

    /**
	 * Retrieves the query params for the discount collection.
	 *
	 * @since 1.0.13
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {

		$params = array_merge(

			parent::get_collection_params(),

        	array(

				// Discount types
				'type'                  => array(
					'description'       => __( 'Type of discounts to fetch.', 'invoicing' ),
					'type'              => array( 'array', 'string' ),
					'default'           => 'any',
					'validate_callback' => 'rest_validate_request_arg',
					'sanitize_callback' => 'wpinv_parse_list',
					'items'             => array(
						'enum'          => array_merge( array( 'any' ), array_keys( wpinv_get_discount_types() ) ),
						'type'          => 'string',
					),
				),

			)
		);

		// Filter collection parameters for the discounts controller.
		return apply_filters( 'getpaid_rest_discounts_collection_params', $params, $this );
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

		// Retrieve items by type.
		if ( ! in_array( 'any', $request['type'] ) ) {

			if ( empty( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}

			$query_args['meta_query'][] = array(
				'key'     => '_wpi_discount_type',
				'value'   => implode( ',', $request['type'] ),
				'compare' => 'IN',
			);

		}

		return apply_filters( 'getpaid_rest_discounts_prepare_items_query', $query_args, $request, $this );

	}

	/**
	 * Retrieves a valid list of post statuses.
	 *
	 * @since 1.0.15
	 *
	 * @return array A list of registered item statuses.
	 */
	public function get_post_statuses() {
		return array( 'publish', 'pending', 'draft', 'expired' );
	}

}
