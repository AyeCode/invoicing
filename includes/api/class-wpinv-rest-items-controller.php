<?php
/**
 * REST items controllers.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API items controller class.
 *
 * @package Invoicing
 */
class WPInv_REST_Items_Controller extends GetPaid_REST_Posts_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wpi_item';
	
	/**
	 * The base of this controller's route.
	 *
	 * @since 1.0.13
	 * @var string
	 */
	protected $rest_base = 'items';

	/** Contains this controller's class name.
	 *
	 * @var string
	 */
	public $crud_class = 'WPInv_Item';

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
			'/' . $this->rest_base . '/item-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_types' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

	}

	/**
	 * Handles rest requests for item types.
	 *
	 * @since 1.0.13
	 * 
	 * 
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item_types() {
		return rest_ensure_response( wpinv_get_item_types() );
	}

    /**
	 * Retrieves the query params for the items collection.
	 *
	 * @since 1.0.13
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {

		$params = array_merge(

			parent::get_collection_params(),

        	array(

				// Item types
				'type'                  => array(
					'description'       => __( 'Type of items to fetch.', 'invoicing' ),
					'type'              => array( 'array', 'string' ),
					'default'           => 'any',
					'validate_callback' => 'rest_validate_request_arg',
					'sanitize_callback' => 'wpinv_parse_list',
					'items'             => array(
						'enum'          => array_merge( array( 'any' ), wpinv_item_types() ),
						'type'          => 'string',
					),
				),

			)
		);

		// Filter collection parameters for the items controller.
		return apply_filters( 'getpaid_rest_items_collection_params', $params, $this );

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
				'key'     => '_wpinv_type',
				'value'   => implode( ',', $request['type'] ),
				'compare' => 'IN',
			);

		}

		return apply_filters( 'getpaid_rest_items_prepare_items_query', $query_args, $request, $this );

	}

	/**
	 * Retrieves a valid list of post statuses.
	 *
	 * @since 1.0.15
	 *
	 * @return array A list of registered item statuses.
	 */
	public function get_post_statuses() {
		return array( 'draft', 'pending', 'publish' );
	}

	/**
	 * Checks if a key should be included in a response.
	 *
	 * @since  1.0.19
	 * @param  WPInv_Item   $item  Item object.
	 * @param  string       $field_key The key to check for.
	 * @return bool
	 */
	public function object_supports_field( $item, $field_key ) {

		if ( 'minimum_price' == $field_key && ! $item->user_can_set_their_price() ) {
			return false;
		}

		foreach( wpinv_parse_list( 'initial_price the_initial_price recurring_price the_recurring_price recurring_period recurring_interval recurring_limit is_free_trial trial_period trial_interval first_renewal_date' ) as $key ) {

			if ( $key == $field_key && ! $item->is_recurring() ) {
				return false;
			}

		}

		foreach( wpinv_parse_list( 'trial_period trial_interval' ) as $key ) {

			if ( $key == $field_key && ! $item->has_free_trial() ) {
				return false;
			}

		}

		return parent::object_supports_field( $item, $field_key );
	}

}
