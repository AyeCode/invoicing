<?php
/**
 * GetPaid REST CRUD controller class.
 *
 * Extends the GetPaid_REST_Controller class to provide functionalities for endpoints
 * that use our CRUD classes
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST CRUD controller class.
 *
 * @package Invoicing
 */
class GetPaid_REST_CRUD_Controller extends GetPaid_REST_Controller {

	/**
	 * Contains this controller's class name.
	 *
	 * @var string
	 */
	public $crud_class;

	/**
	 * Contains the current CRUD object.
	 *
	 * @var GetPaid_Data
	 */
	protected $data_object;

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.0.19
	 *
	 * @see register_rest_route()
	 */
	public function register_namespace_routes( $namespace ) {

		register_rest_route(
			$namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);

		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.', 'invoicing' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $get_item_args,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to bypass Trash and force deletion.', 'invoicing' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Saves a single object.
	 *
	 * @param GetPaid_Data $object Object to save.
	 * @return WP_Error|GetPaid_Data
	 */
	protected function save_object( $object ) {
		$object->save();

		if ( ! empty( $object->last_error ) ) {
			return new WP_Error( 'rest_cannot_save', $object->last_error, array( 'status' => 400 ) );
		}

		return new $this->crud_class( $object->get_id() );
	}

	/**
	 * Retrieves a single object.
	 *
	 * @since 1.0.13
	 *
	 * @param int|WP_Post $object_id Supplied ID.
	 * @return GetPaid_Data|WP_Error GetPaid_Data object if ID is valid, WP_Error otherwise.
	 */
	protected function get_object( $object_id ) {

		// Do we have an object?
		if ( empty( $this->crud_class ) || ! class_exists( $this->crud_class ) ) {
			return new WP_Error( 'no_crud_class', __( 'You need to specify a CRUD class for this controller', 'invoicing' ) );
		}

		// Fetch the object.
		$object = new $this->crud_class( $object_id );
		if ( ! empty( $object->last_error ) ) {
			return new WP_Error( 'rest_object_invalid_id', $object->last_error, array( 'status' => 404 ) );
		}

		$this->data_object = $object;
		return $object->get_id() ? $object : new WP_Error( 'rest_object_invalid_id', __( 'Invalid ID.', 'invoicing' ), array( 'status' => 404 ) );

	}

	/**
	 * Get a single object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		// Fetch the item.
		$object = $this->get_object( $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		// Generate a response.
		return rest_ensure_response( $this->prepare_item_for_response( $object, $request ) );

	}

	/**
	 * Create a single object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		// Can not create an existing item.
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new WP_Error( "getpaid_rest_{$this->rest_base}_exists", __( 'Cannot create existing resource.', 'invoicing' ), array( 'status' => 400 ) );
		}

		// Generate a GetPaid_Data object from the request.
		$object = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		// Save the object.
		$object = $this->save_object( $object );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		// Save special fields.
		$save_special = $this->update_additional_fields_for_object( $object, $request );
		if ( is_wp_error( $save_special ) ) {
			$object->delete( true );
			return $save_special;
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $object, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ) );

		return $response;
	}

	/**
	 * Update a single object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {

		// Fetch the item.
		$object = $this->get_object( $request['id'] );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		// Prepare the item for saving.
		$object = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		// Save the item.
		$object = $this->save_object( $object );
		if ( is_wp_error( $object ) ) {
			return $object;
		}

		// Save special fields (those added via hooks).
		$save_special = $this->update_additional_fields_for_object( $object, $request );
		if ( is_wp_error( $save_special ) ) {
			return $save_special;
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $object, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param GetPaid_Data    $object GetPaid_Data object.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $object ) {

		$links = array(
			'self'       => array(
				'href'   => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href'   => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();
		$params['context']['default'] = 'view';
		return $params;
	}

	/**
	 * Only return writable props from schema.
	 *
	 * @param  array $schema Schema.
	 * @return bool
	 */
	public function filter_writable_props( $schema ) {
		return empty( $schema['readonly'] );
	}

	/**
	 * Prepare a single object for create or update.
	 *
	 * @since 1.0.19
	 * @param  WP_REST_Request $request Request object.
	 * @return GetPaid_Data|WP_Error Data object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {

		// Do we have an object?
		if ( empty( $this->crud_class ) || ! class_exists( $this->crud_class ) ) {
			return new WP_Error( 'no_crud_class', __( 'You need to specify a CRUD class for this controller', 'invoicing' ) );
		}

		// Prepare the object.
		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$object    = new $this->crud_class( $id );

		// Abort if an error exists.
		if ( ! empty( $object->last_error ) ) {
			return new WP_Error( 'invalid_item', $object->last_error );
		}

		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );

		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {

					case 'meta_data':
						if ( is_array( $value ) ) {
							foreach ( $value as $meta ) {
								$object->update_meta_data( $meta['key'], $meta['value'], isset( $meta['id'] ) ? $meta['id'] : '' );
							}
						}
						break;

					default:
						if ( is_callable( array( $object, "set_{$key}" ) ) ) {
							$object->{"set_{$key}"}( $value );
						}
						break;
				}
			}

		}

		// Filters an object before it is inserted via the REST API..
		return apply_filters( "getpaid_rest_pre_insert_{$this->rest_base}_object", $object, $request );
	}

	/**
	 * Retrieves data from a GetPaid class.
	 *
	 * @since  1.0.19
	 * @param  GetPaid_Meta_Data[]    $meta_data  meta data objects.
	 * @return array
	 */
	protected function prepare_object_meta_data( $meta_data ) {
		$meta = array();

		foreach( $meta_data as $object ) {
			$meta[] = $object->get_data();
		}

		return $meta;
	}

	/**
	 * Retrieves invoice items.
	 *
	 * @since  1.0.19
	 * @param  WPInv_Invoice $invoice  Invoice items.
	 * @param array            $fields Fields to include.
	 * @return array
	 */
	protected function prepare_invoice_items( $invoice ) {
		$items = array();

		foreach( $invoice->get_items() as $item ) {

			$item_data = $item->prepare_data_for_saving();

			if ( 'amount' == $invoice->get_template() ) {
				$item_data['quantity'] = 1;
			}

			$items[] = $item_data;
		}

		return $items;
	}

	/**
	 * Retrieves data from a GetPaid class.
	 *
	 * @since  1.0.19
	 * @param  GetPaid_Data    $object  Data object.
	 * @param array            $fields Fields to include.
	 * @param string           $context either view or edit.
	 * @return array
	 */
	protected function prepare_object_data( $object, $fields, $context = 'view' ) {

		$data = array();

		// Handle all writable props.
		foreach ( array_keys( $this->get_schema_properties() ) as $key ) {

			// Abort if it is not included.
			if ( ! empty( $fields ) && ! $this->is_field_included( $key, $fields ) ) {
				continue;
			}

			// Or this current object does not support the field.
			if ( ! $this->object_supports_field( $object, $key ) ) {
				continue;
			}

			// Handle meta data.
			if ( $key == 'meta_data' ) {
				$data['meta_data'] = $this->prepare_object_meta_data( $object->get_meta_data() );
				continue;
			}

			// Handle items.
			if ( $key == 'items' && is_a( $object, 'WPInv_Invoice' )  ) {
				$data['items'] = $this->prepare_invoice_items( $object );
				continue;
			}

			// Booleans.
			if ( is_callable( array( $object, $key ) ) ) {
				$data[ $key ] = $object->$key( $context );
				continue;
			}

			// Get object value.
			if ( is_callable( array( $object, "get_{$key}" ) ) ) {
				$value = $object->{"get_{$key}"}( $context );

				// If the value is an instance of GetPaid_Data...
				if ( is_a( $value, 'GetPaid_Data' ) ) {
					$value = $value->get_data( $context );
				}

				// For objects, retrieves it's properties.
				$data[ $key ] = is_object( $value ) ? get_object_vars( $value ) :  $value ;
				continue;
			}

		}

		return $data;
	}

	/**
	 * Checks if a key should be included in a response.
	 *
	 * @since  1.0.19
	 * @param  GetPaid_Data $object  Data object.
	 * @param  string       $field_key The key to check for.
	 * @return bool
	 */
	public function object_supports_field( $object, $field_key ) {
		return apply_filters( 'getpaid_rest_object_supports_key', true, $object, $field_key );
	}

	/**
	 * Prepare a single object output for response.
	 *
	 * @since  1.0.19
	 * @param  GetPaid_Data    $object  Data object.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $object, $request ) {
		remove_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10 );

		$this->data_object = $object;

		// Fetch the fields to include in this response.
		$fields = $this->get_fields_for_response( $request );

		// Prepare object data.
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->prepare_object_data( $object, $fields, $context );
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->limit_object_to_requested_fields( $data, $fields );
		$data    = $this->filter_response_by_context( $data, $context );

		// Prepare the response.
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		// Filter item response.
		return apply_filters( "getpaid_rest_prepare_{$this->rest_base}_object", $response, $object, $request );
	}

}
