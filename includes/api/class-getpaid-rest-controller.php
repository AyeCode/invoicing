<?php
/**
 * GetPaid REST controller class.
 * 
 * Extends the WP_REST_Controller class to provide batch support for our REST
 * APIs and also to provide backwards support for our old namespaces.
 * 
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core class to access posts via the REST API.
 *
 * This is version 1.
 *
 * @since 1.0.19
 *
 * @see WP_REST_Controller
 */
class GetPaid_REST_Controller extends WP_REST_Controller {

	/**
     * The namespaces of this controller's route.
     *
     * @since 1.0.19
     * @var array
     */
	protected $namespaces;

	/**
     * The official namespace of this controller's route.
     *
     * @since 1.0.19
     * @var string
     */
	protected $namespace = 'getpaid/v1';

	/**
     * Cached results of get_item_schema.
     *
     * @since 1.0.19
     * @var array
     */
	protected $schema;

    /**
	 * Constructor.
	 *
	 * @since 1.0.19
	 *
	 */
	public function __construct() {

		// Offer several namespaces for backwards compatibility.
		$this->namespaces = apply_filters(
			'getpaid_rest_api_namespaces',
			array(
				'getpaid/v1',
				'invoicing/v1',
				'wpi/v1'
			)
		);

		// Register REST routes.
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

	}

	/**
	 * Registers routes for each namespace.
	 *
	 * @since 1.0.19
	 *
	 */
	public function register_routes() {

		foreach ( $this->namespaces as $namespace ) {
			$this->register_namespace_routes( $namespace );
		}

	}

	/**
	 * Registers routes for a namespace.
	 *
	 * @since 1.0.19
	 *
	 * @param string $namespace
	 */
	public function register_namespace_routes( /** @scrutinizer ignore-unused */ $namespace ) {

		getpaid_doing_it_wrong(
			__CLASS__ . '::' .__METHOD__,
			/* translators: %s: register_namespace_routes() */
			sprintf( __( "Method '%s' must be overridden." ), __METHOD__ ),
			'1.0.19'
		);

	}

	/**
	 * Get normalized rest base.
	 *
	 * @return string
	 */
	protected function get_normalized_rest_base() {
		return preg_replace( '/\(.*\)\//i', '', $this->rest_base );
	}

	/**
	 * Fill batches.
	 *
	 * @param array array of request items.
	 * @return array
	 */
	protected function fill_batch_keys( $items ) {

		$items['create'] = empty( $items['create'] ) ? array() : $items['create'];
		$items['update'] = empty( $items['update'] ) ? array() : $items['update'];
		$items['delete'] = empty( $items['delete'] ) ? array() : wp_parse_id_list( $items['delete'] );
		return $items;

	}

	/**
	 * Check batch limit.
	 *
	 * @param array $items Request items.
	 * @return bool|WP_Error
	 */
	protected function check_batch_limit( $items ) {
		$limit = apply_filters( 'getpaid_rest_batch_items_limit', 100, $this->get_normalized_rest_base() );
		$total = count( $items['create'] ) + count( $items['update'] ) + count( $items['delete'] );

		if ( $total > $limit ) {
			/* translators: %s: items limit */
			return new WP_Error( 'getpaid_rest_request_entity_too_large', sprintf( __( 'Unable to accept more than %s items for this request.', 'invoicing' ), $limit ), array( 'status' => 413 ) );
		}

		return true;
	}

	/**
	 * Bulk create items.
	 *
	 * @param array $items Array of items to create.
	 * @param WP_REST_Request $request Full details about the request.
	 * @param WP_REST_Server $wp_rest_server
	 * @return array()
	 */
	protected function batch_create_items( $items, $request, $wp_rest_server ) {

		$query  = $request->get_query_params();
		$create = array();

		foreach ( $items as $item ) {
			$_item = new WP_REST_Request( 'POST' );

			// Default parameters.
			$defaults = array();
			$schema   = $this->get_public_item_schema();
			foreach ( $schema['properties'] as $arg => $options ) {
				if ( isset( $options['default'] ) ) {
					$defaults[ $arg ] = $options['default'];
				}
			}
			$_item->set_default_params( $defaults );

			// Set request parameters.
			$_item->set_body_params( $item );

			// Set query (GET) parameters.
			$_item->set_query_params( $query );

			// Create the item.
			$_response = $this->create_item( $_item );

			// If an error occured...
			if ( is_wp_error( $_response ) ) {

				$create[]   = array(
					'id'    => 0,
					'error' => array(
						'code'    => $_response->get_error_code(),
						'message' => $_response->get_error_message(),
						'data'    => $_response->get_error_data(),
					),
				);

				continue;
			}

			$create[] = $wp_rest_server->response_to_data( /** @scrutinizer ignore-type */ $_response, false );

		}

		return $create;

	}

	/**
	 * Bulk update items.
	 *
	 * @param array $items Array of items to update.
	 * @param WP_REST_Request $request Full details about the request.
	 * @param WP_REST_Server $wp_rest_server
	 * @return array()
	 */
	protected function batch_update_items( $items, $request, $wp_rest_server ) {

		$query  = $request->get_query_params();
		$update = array();

		foreach ( $items as $item ) {

			// Create a dummy request.
			$_item = new WP_REST_Request( 'PUT' );

			// Add body params.
			$_item->set_body_params( $item );

			// Set query (GET) parameters.
			$_item->set_query_params( $query );

			// Update the item.
			$_response = $this->update_item( $_item );

			// If an error occured...
			if ( is_wp_error( $_response ) ) {

				$update[] = array(
					'id'    => $item['id'],
					'error' => array(
						'code'    => $_response->get_error_code(),
						'message' => $_response->get_error_message(),
						'data'    => $_response->get_error_data(),
					),
				);

				continue;

			}

			$update[] = $wp_rest_server->response_to_data( /** @scrutinizer ignore-type */ $_response, false );

		}

		return $update;

	}

	/**
	 * Bulk delete items.
	 *
	 * @param array $items Array of items to delete.
	 * @param WP_REST_Server $wp_rest_server
	 * @return array()
	 */
	protected function batch_delete_items( $items, $wp_rest_server ) {

		$delete = array();

		foreach ( array_filter( $items ) as $id ) {

			// Prepare the request.
			$_item = new WP_REST_Request( 'DELETE' );
			$_item->set_query_params(
				array(
					'id'    => $id,
					'force' => true,
				)
			);

			// Delete the item.
			$_response = $this->delete_item( $_item );

			if ( is_wp_error( $_response ) ) {

				$delete[] = array(
					'id'    => $id,
					'error' => array(
						'code'    => $_response->get_error_code(),
						'message' => $_response->get_error_message(),
						'data'    => $_response->get_error_data(),
					),
				);

				continue;
			}

			$delete[] = $wp_rest_server->response_to_data( /** @scrutinizer ignore-type */ $_response, false );

		}

		return $delete;

	}

	/**
	 * Bulk create, update and delete items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|array.
	 */
	public function batch_items( $request ) {
		global $wp_rest_server;

		// Prepare the batch items.
		$items = $this->fill_batch_keys( array_filter( $request->get_params() ) );

		// Ensure that the batch has not exceeded the limit to prevent abuse.
		$limit = $this->check_batch_limit( $items );
		if ( is_wp_error( $limit ) ) {
			return $limit;
		}

		// Process the items.
		return array(
			'create' => $this->batch_create_items( $items['create'], $request, $wp_rest_server ),
			'update' => $this->batch_update_items( $items['update'], $request, $wp_rest_server ),
			'delete' => $this->batch_delete_items( $items['delete'], $wp_rest_server ),
		);

	}

	/**
	 * Add meta query.
	 *
	 * @since 1.0.19
	 * @param array $args       Query args.
	 * @param array $meta_query Meta query.
	 * @return array
	 */
	protected function add_meta_query( $args, $meta_query ) {
		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}

		$args['meta_query'][] = $meta_query;

		return $args['meta_query'];
	}

	/**
	 * Get the batch schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_public_batch_schema() {

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'batch',
			'type'       => 'object',
			'properties' => array(
				'create' => array(
					'description' => __( 'List of created resources.', 'invoicing' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
					),
				),
				'update' => array(
					'description' => __( 'List of updated resources.', 'invoicing' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
					),
				),
				'delete' => array(
					'description' => __( 'List of deleted resources.', 'invoicing' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'integer',
					),
				),
			),
		);

	}

	/**
	 * Returns the value of schema['properties']
	 * 
	 * i.e Schema fields.
	 *
	 * @since 1.0.19
	 * @return array
	 */
	protected function get_schema_properties() {

		$schema     = $this->get_item_schema();
		$properties = isset( $schema['properties'] ) ? $schema['properties'] : array();

		// For back-compat, include any field with an empty schema
		// because it won't be present in $this->get_item_schema().
		foreach ( $this->get_additional_fields() as $field_name => $field_options ) {
			if ( is_null( $field_options['schema'] ) ) {
				$properties[ $field_name ] = $field_options;
			}
		}

		return $properties;
	}

	/**
	 * Filters fields by context.
	 *
	 * @param array $fields Array of fields
	 * @param string|null context view, edit or embed
	 * @since 1.0.19
	 * @return array
	 */
	protected function filter_response_fields_by_context( $fields, $context ) {

		if ( empty( $context ) ) {
			return $fields;
		}

		foreach ( $fields as $name => $options ) {
			if ( ! empty( $options['context'] ) && ! in_array( $context, $options['context'], true ) ) {
				unset( $fields[ $name ] );
			}
		}

		return $fields;

	}

	/**
	 * Filters fields by an array of requested fields.
	 *
	 * @param array $fields Array of available fields
	 * @param array $requested array of requested fields.
	 * @since 1.0.19
	 * @return array
	 */
	protected function filter_response_fields_by_array( $fields, $requested ) {

		// Trim off any whitespace from the list array.
		$requested = array_map( 'trim', $requested );

		// Always persist 'id', because it can be needed for add_additional_fields_to_object().
		if ( in_array( 'id', $fields, true ) ) {
			$requested[] = 'id';
		}

		// Get rid of duplicate fields.
		$requested = array_unique( $requested );

		// Return the list of all included fields which are available.
		return array_reduce(
			$requested,
			function( $response_fields, $field ) use ( $fields ) {

				if ( in_array( $field, $fields, true ) ) {
					$response_fields[] = $field;
					return $response_fields;
				}

				// Check for nested fields if $field is not a direct match.
				$nested_fields = explode( '.', $field );

				// A nested field is included so long as its top-level property is
				// present in the schema.
				if ( in_array( $nested_fields[0], $fields, true ) ) {
					$response_fields[] = $field;
				}

				return $response_fields;
			},
			array()
		);

	}

	/**
	 * Gets an array of fields to be included on the response.
	 *
	 * Included fields are based on item schema and `_fields=` request argument.
	 * Copied from WordPress 5.3 to support old versions.
	 *
	 * @since 1.0.19
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Fields to be included in the response.
	 */
	public function get_fields_for_response( $request ) {

		// Retrieve fields in the schema.
		$properties = $this->get_schema_properties();

		// Exclude fields that specify a different context than the request context.
		$properties = $this->filter_response_fields_by_context( $properties, $request['context'] );

		// We only need the field keys.
		$fields = array_keys( $properties );

		// Is the user filtering the response fields??
		if ( empty( $request['_fields'] ) ) {
			return $fields;
		}

		return $this->filter_response_fields_by_array( $fields, wpinv_parse_list( $request['_fields'] ) );

	}

	/**
	 * Limits an object to the requested fields.
	 *
	 * Included fields are based on the `_fields` request argument.
	 *
	 * @since 1.0.19
	 * @param array $data Fields to include in the response.
	 * @param array $fields Requested fields.
	 * @return array Fields to be included in the response.
	 */
	public function limit_object_to_requested_fields( $data, $fields, $prefix = '' ) {

		// Is the user filtering the response fields??
		if ( empty( $fields ) ) {
			return $data;
		}

		foreach ( $data as $key => $value ) {

			// Numeric arrays.
			if ( is_numeric( $key ) && is_array( $value ) ) {
				$data[ $key ] = $this->limit_object_to_requested_fields( $value, $fields, $prefix );
				continue;
			}

			// Generate a new prefix.
			$new_prefix = empty( $prefix ) ? $key : "$prefix.$key";

			// Check if it was requested.
			if ( ! empty( $key ) && ! $this->is_field_included( $new_prefix, $fields ) ) {
				unset( $data[ $key ] );
				continue;
			}

			if ( $key != 'meta_data' && is_array( $value ) ) {
				$data[ $key ] = $this->limit_object_to_requested_fields( $value, $fields, $new_prefix );
			}

		}

		return $data;
	}

	/**
	 * Given an array of fields to include in a response, some of which may be
	 * `nested.fields`, determine whether the provided field should be included
	 * in the response body.
	 *
	 * Copied from WordPress 5.3 to support old versions.
	 *
	 * @since 1.0.19
	 *
	 * @param string $field  A field to test for inclusion in the response body.
	 * @param array  $fields An array of string fields supported by the endpoint.
	 * @return bool Whether to include the field or not.
	 * @see rest_is_field_included()
	 */
	public function is_field_included( $field, $fields ) {
		if ( in_array( $field, $fields, true ) ) {
			return true;
		}

		foreach ( $fields as $accepted_field ) {
			// Check to see if $field is the parent of any item in $fields.
			// A field "parent" should be accepted if "parent.child" is accepted.
			if ( strpos( $accepted_field, "$field." ) === 0 ) {
				return true;
			}
			// Conversely, if "parent" is accepted, all "parent.child" fields
			// should also be accepted.
			if ( strpos( $field, "$accepted_field." ) === 0 ) {
				return true;
			}
		}

		return false;
	}

}
