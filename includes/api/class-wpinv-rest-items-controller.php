<?php
/**
 * REST API Items controller
 *
 * Handles requests to the discounts endpoint.
 *
 * @package  Invoicing
 * @since    1.0.13
 */

if ( !defined( 'WPINC' ) ) {
    exit;
}

/**
 * REST API items controller class.
 *
 * @package Invoicing
 */
class WPInv_REST_Items_Controller extends WP_REST_Posts_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wpi_item';
	
	/**
	 * Cached results of get_item_schema.
	 *
	 * @since 1.0.13
	 * @var array
	 */
	protected $schema;

    /**
	 * Constructor.
	 *
	 * @since 1.0.13
	 *
	 * @param string $namespace Api Namespace
	 */
	public function __construct( $namespace ) {
        
        // Set api namespace...
		$this->namespace = $namespace;

        // ... and the rest base
        $this->rest_base = 'items';
		
    }
	
	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.0.13
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/item-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item_types' ),
				),
			)
		);

	}

    /**
	 * Checks if a given request has access to read items.
     * 
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
	
		if ( current_user_can( 'manage_options' ) ||  current_user_can( 'manage_invoicing' ) ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view invoice items.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );

    }
    
    /**
	 * Retrieves a collection of invoice items.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		
		// Retrieve the list of registered item query parameters.
        $registered = $this->get_collection_params();
        
        $args       = array();

        foreach( array_keys( $registered ) as $key ) {

            if( isset( $request[ $key] ) ) {
                $args[ $key ] = $request[ $key];
            }

        }

		/**
		 * Filters the wpinv_get_items arguments for items rest requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
        $args       = apply_filters( "wpinv_rest_get_items_arguments", $args, $request, $this );
		
		// Special args
		$args[ 'return' ]   = 'objects';
		$args[ 'paginate' ] = true;

        // Run the query.
		$query = wpinv_get_all_items( $args );
		
		// Prepare the retrieved items
		$items = array();
		foreach( $query->items as $item ) {

			if ( ! $this->check_read_permission( $item ) ) {
				continue;
			}

			$data       = $this->prepare_item_for_response( $item, $request );
			$items[]    = $this->prepare_response_for_collection( $data );

		}

		// Prepare the response.
		$response = rest_ensure_response( $items );
		$response->header( 'X-WP-Total', (int) $query->total );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		/**
		 * Filters the responses for item requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param arrWP_REST_Response $response    Response object.
		 * @param WP_REST_Request     $request The request used.
         * @param array               $args Array of args used to retrieve the items
		 */
        $response       = apply_filters( "wpinv_rest_items_response", $response, $request, $args );

        return rest_ensure_response( $response );
        
    }

    /**
	 * Get the post, if the ID is valid.
	 *
	 * @since 1.0.13
	 *
	 * @param int $item_id Supplied ID.
	 * @return WPInv_Item|WP_Error Item object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $item_id ) {
		
		$error     = new WP_Error( 'rest_item_invalid_id', __( 'Invalid item ID.', 'invoicing' ), array( 'status' => 404 ) );

        // Ids start from 1
        if ( (int) $item_id <= 0 ) {
			return $error;
		}

		$item = wpinv_get_item_by( 'id', (int) $item_id );
		if ( empty( $item ) ) {
			return $error;
        }

        return $item;

    }

    /**
	 * Checks if a given request has access to read an invoice item.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the invoice item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {

        // Retrieve the item object.
        $item = $this->get_post( $request['id'] );
        
        // Ensure it is valid.
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( ! current_user_can(  $post_type->cap->read_post, $item->ID  ) ) {
			return new WP_Error( 
                'rest_cannot_edit', 
                __( 'Sorry, you are not allowed to view this item.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
        }

		return $this->check_read_permission( $item );
    }
    
    /**
	 * Checks if an item can be read.
	 * 
	 * An item can be read by site admins.
	 *
	 *
	 * @since 1.0.13
	 *
	 * @param WPInv_Item $item WPInv_Item object.
	 * @return bool Whether the post can be read.
	 */
	public function check_read_permission( $item ) {

		// An item can be read by an admin...
		if ( current_user_can( 'manage_options' ) ||  current_user_can( 'manage_invoicing' ) ) {
			return true;
		}

		return false;
    }
    
    /**
	 * Retrieves a single invoice item.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {

        // Fetch the item.
        $item = $this->get_post( $request['id'] );
        
        // Abort early if it does not exist
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		// Prepare the response
		$response = $this->prepare_item_for_response( $item, $request );

		/**
		 * Filters the responses for single invoice item requests.
		 *
		 *
		 * @since 1.0.13
		 * @var WP_HTTP_Response
		 *
		 * @param WP_HTTP_Response $response Response.
		 * @param WP_REST_Request  $request The request used.
		 */
        $response       = apply_filters( "wpinv_rest_get_item_response", $response, $request );

        return rest_ensure_response( $response );

    }
    
    /**
	 * Checks if a given request has access to create an invoice item.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
	
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'rest_item_exists', __( 'Cannot create existing item.', 'invoicing' ), array( 'status' => 400 ) );
		}

		if ( current_user_can( 'manage_options' ) ||  current_user_can( 'manage_invoicing' ) ) {
			return true;
		}

		$post_type = get_post_type_object( $this->post_type );
		if ( ! current_user_can( $post_type->cap->create_posts ) ) {
			return new WP_Error( 
                'rest_cannot_create', 
                __( 'Sorry, you are not allowed to create invoice items as this user.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
        }

		return true;
    }
    
    /**
	 * Creates a single invoice item.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'rest_item_exists', __( 'Cannot create existing invoice item.', 'invoicing' ), array( 'status' => 400 ) );
		}

		$request->set_param( 'context', 'edit' );

		// Prepare the updated data.
		$item_data = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $item_data ) ) {
			return $item_data;
		}

		// Try creating the item.
        $item = wpinv_create_item( $item_data, true );

		if ( is_wp_error( $item ) ) {
            return $item;
		}

		// Prepare the response
		$response = $this->prepare_item_for_response( $item, $request );

		/**
		 * Fires after a single invoice item is created or updated via the REST API.
		 *
		 * @since 1.0.13
		 *
		 * @param WPinv_Item   $item  Inserted or updated item object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a post, false when updating.
		 */
		do_action( "wpinv_rest_insert_item", $item, $request, true );

		/**
		 * Filters the responses for creating single item requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $item_data Invoice properties.
		 * @param WP_REST_Request $request The request used.
		 */
        $response       = apply_filters( "wpinv_rest_create_item_response", $response, $request );

        return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to update an item.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {

		// Retrieve the item.
		$item = $this->get_post( $request['id'] );
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		if ( wpinv_current_user_can_manage_invoicing() ) {
			return true;
		}

		return new WP_Error( 
			'rest_cannot_edit', 
			__( 'Sorry, you are not allowed to update this item.', 'invoicing' ), 
			array( 
				'status' => rest_authorization_required_code(),
			)
		);

	}

	/**
	 * Updates a single item.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		
		// Ensure the item exists.
        $valid_check = $this->get_post( $request['id'] );
        
        // Abort early if it does not exist
		if ( is_wp_error( $valid_check ) ) {
			return $valid_check;
		}

		$request->set_param( 'context', 'edit' );

		// Prepare the updated data.
		$data_to_update = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $data_to_update ) ) {
			return $data_to_update;
		}

		// Abort if no item data is provided
        if( empty( $data_to_update ) ) {
            return new WP_Error( 'missing_data', __( 'An update request cannot be empty.', 'invoicing' ) );
        }

		// Include the item ID
		$data_to_update['ID'] = $request['id'];

		// Update the item
		$updated_item = wpinv_update_item( $data_to_update, true );

		// Incase the update operation failed...
		if ( is_wp_error( $updated_item ) ) {
			return $updated_item;
		}

		// Prepare the response
		$response = $this->prepare_item_for_response( $updated_item, $request );

		/** This action is documented in includes/class-wpinv-rest-item-controller.php */
		do_action( "wpinv_rest_insert_item", $updated_item, $request, false );

		/**
		 * Filters the responses for updating single item requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $data_to_update Item properties.
		 * @param WP_REST_Request $request The request used.
		 */
        $response       = apply_filters( "wpinv_rest_update_item_response", $response,  $data_to_update, $request );

        return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to delete an item.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {

		// Retrieve the item.
		$item = $this->get_post( $request['id'] );
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		// 

		// Ensure the current user can delete the item
		if (! wpinv_can_delete_item( $request['id'] ) ) {
			return new WP_Error( 
                'rest_cannot_delete', 
                __( 'Sorry, you are not allowed to delete this item.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
		}

		return true;
	}

	/**
	 * Deletes a single item.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		
		// Retrieve the item.
		$item = $this->get_post( $request['id'] );
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$request->set_param( 'context', 'edit' );

		// Prepare the item id
		$id    = $item->ID;

		// Prepare the response
		$response = $this->prepare_item_for_response( $item, $request );

		// Check if the user wants to bypass the trash...
		$force_delete = (bool) $request['force'];

		// Try deleting the item.
		$deleted = wp_delete_post( $id, $force_delete );

		// Abort early if we can't delete the item.
		if ( ! $deleted ) {
			return new WP_Error( 'rest_cannot_delete', __( 'The item cannot be deleted.', 'invoicing' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires immediately after a single item is deleted or trashed via the REST API.
		 *
		 *
		 * @since 1.0.13
		 *
		 * @param WPInv_Item    $item  The deleted or trashed item.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "wpinv_rest_delete_item", $item, $request );

		return $response;

	}
    
    
    /**
	 * Retrieves the query params for the items collection.
	 *
	 * @since 1.0.13
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
        
        $query_params               = array(

            // Item status.
            'status'                => array(
                'default'           => 'publish',
                'description'       => __( 'Limit result set to items assigned one or more statuses.', 'invoicing' ),
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_post_statuses' ),
            ),
            
            // Item types
            'type'                  => array(
				'description'       => __( 'Type of items to fetch.', 'invoicing' ),
				'type'              => 'array',
				'default'           => wpinv_item_types(),
				'items'             => array(
                    'enum'          => wpinv_item_types(),
                    'type'          => 'string',
                ),
			),
			
			// Number of results per page
            'limit'                 => array(
				'description'       => __( 'Number of items to fetch.', 'invoicing' ),
				'type'              => 'integer',
				'default'           => (int) get_option( 'posts_per_page' ),
            ),

            // Pagination
            'page'     => array(
				'description'       => __( 'Current page to fetch.', 'invoicing' ),
				'type'              => 'integer',
				'default'           => 1,
            ),

            // Exclude certain items
            'exclude'  => array(
                'description' => __( 'Ensure result set excludes specific IDs.', 'invoicing' ),
                'type'        => 'array',
                'items'       => array(
                    'type' => 'integer',
                ),
                'default'     => array(),
            ),

            // Order items by
            'orderby'  => array(
                'description' => __( 'Sort items by object attribute.', 'invoicing' ),
                'type'        => 'string',
                'default'     => 'date',
                'enum'        => array(
                    'author',
                    'date',
                    'ID',
                    'modified',
					'title',
					'relevance',
					'rand'
                ),
            ),

            // How to order
            'order'    => array(
                'description' => __( 'Order sort attribute ascending or descending.', 'invoicing' ),
                'type'        => 'string',
                'default'     => 'DESC',
                'enum'        => array( 'ASC', 'DESC' ),
			),
			
			// Search term
            'search'                => array(
				'description'       => __( 'Return items that match the search term.', 'invoicing' ),
				'type'              => 'string',
            ),
        );

		/**
		 * Filter collection parameters for the items controller.
		 *
		 *
		 * @since 1.0.13
		 *
		 * @param array        $query_params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( "wpinv_rest_items_collection_params", $query_params );
    }
    
    /**
	 * Checks if a given post type can be viewed or managed.
	 *
	 * @since 1.0.13
	 *
	 * @param object|string $post_type Post type name or object.
	 * @return bool Whether the post type is allowed in REST.
	 */
	protected function check_is_post_type_allowed( $post_type ) {
		return true;
	}

	/**
	 * Prepares a single item for create or update.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Invoice Properties or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_item = new stdClass();

		// Post ID.
		if ( isset( $request['id'] ) ) {
			$existing_item = $this->get_post( $request['id'] );
			if ( is_wp_error( $existing_item ) ) {
				return $existing_item;
			}

			$prepared_item->ID 		  = $existing_item->ID;
		}

		$schema = $this->get_item_schema();

		// item title.
		if ( ! empty( $schema['properties']['name'] ) && isset( $request['name'] ) ) {
			$prepared_item->title = sanitize_text_field( $request['name'] );
		}

		// item summary.
		if ( ! empty( $schema['properties']['summary'] ) && isset( $request['summary'] ) ) {
			$prepared_item->excerpt = wp_kses_post( $request['summary'] );
		}

		// item price.
		if ( ! empty( $schema['properties']['price'] ) && isset( $request['price'] ) ) {
			$prepared_item->price = floatval( $request['price'] );
		}

		// minimum price (for dynamc items).
		if ( ! empty( $schema['properties']['minimum_price'] ) && isset( $request['minimum_price'] ) ) {
			$prepared_item->minimum_price = floatval( $request['minimum_price'] );
		}

		// item status.
		if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) ) {
			$prepared_item->status = 'publish' === $request['status'] ? 'publish' : 'pending';
		}

		// item type.
		if ( ! empty( $schema['properties']['type'] ) && isset( $request['type'] ) ) {
			$prepared_item->type = in_array( $request['type'], wpinv_item_types() ) ? trim( strtolower( $request['type'] ) ) : 'custom';
		}

		// VAT rule.
		if ( ! empty( $schema['properties']['vat_rule'] ) && isset( $request['vat_rule'] ) ) {
			$prepared_item->vat_rule = 'digital' === $request['vat_rule'] ? 'digital' : 'physical';
		}

		// Simple strings.
		foreach( array( 'custom_id', 'custom_name', 'custom_singular_name' ) as $property ) {

			if ( ! empty( $schema['properties'][$property] ) && isset( $request[$property] ) ) {
				$prepared_item->$property = sanitize_text_field( $request[$property] );
			}

		}

		// Simple integers.
		foreach( array( 'is_recurring', 'recurring_interval', 'recurring_limit', 'free_trial', 'trial_interval', 'dynamic_pricing', 'editable' ) as $property ) {

			if ( ! empty( $schema['properties'][$property] ) && isset( $request[$property] ) ) {
				$prepared_item->$property = intval( $request[$property] );
			}

		}

		// Time periods.
		foreach( array( 'recurring_period',  'trial_period' ) as $property ) {

			if ( ! empty( $schema['properties'][$property] ) && isset( $request[$property] ) ) {
				$prepared_item->$property = in_array( $request[$property], array( 'D', 'W', 'M', 'Y' ) ) ? trim( strtoupper( $request[$property] ) ) : 'D';
			}

		}

		$item_data = (array) wp_unslash( $prepared_item );

		/**
		 * Filters an item before it is inserted via the REST API.
		 *
		 * @since 1.0.13
		 *
		 * @param array        $item_data An array of item data
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "wpinv_rest_pre_insert_item", $item_data, $request );

	}

	/**
	 * Prepares a single item output for response.
	 *
	 * @since 1.0.13
	 *
	 * @param WPInv_Item   $item    item object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {

		$GLOBALS['post'] = get_post( $item->get_ID() );

		setup_postdata( $item->get_ID() );

		// Fetch the fields to include in this response.
		$fields = $this->get_fields_for_response( $request );

		// Base fields for every item.
		$data = array();

		// Set up ID
		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $item->get_ID();
		}


		// Item properties
		$item_properties = array(
			'name', 'summary', 'price', 'status', 'type',
			'vat_rule', 'vat_class',
			'custom_id', 'custom_name', 'custom_singular_name', 
			'editable'
		);

		foreach( $item_properties as $property ) {

			if ( rest_is_field_included( $property, $fields ) && method_exists( $item, 'get_' . $property ) ) {
				$data[$property] = call_user_func( array( $item, 'get_' . $property ) );
			}

		}

		// Dynamic pricing.
		if( $item->supports_dynamic_pricing() ) {

			if( rest_is_field_included( 'dynamic_pricing', $fields ) ) {
				$data['dynamic_pricing'] = $item->get_is_dynamic_pricing();
			}

			if( rest_is_field_included( 'minimum_price', $fields ) ) {
				$data['minimum_price'] = $item->get_minimum_price();
			}
		}

		// Subscriptions.
		if( rest_is_field_included( 'is_recurring', $fields ) ) {
			$data['is_recurring'] = $item->get_is_recurring();
		}

		if( $item->is_recurring() ) {

			$recurring_fields = array( 'is_recurring', 'recurring_period', 'recurring_interval', 'recurring_limit', 'free_trial' );
			foreach( $recurring_fields as $field ) {

				if ( rest_is_field_included( $field, $fields ) && method_exists( $item, 'get_' . $field ) ) {
					$data[$field] = call_user_func( array( $item, 'get_' . $field ) );
				}
	
			}

			if( $item->has_free_trial() ) {

				$trial_fields = array( 'trial_period', 'trial_interval' );
				foreach( $trial_fields as $field ) {

					if ( rest_is_field_included( $field, $fields ) && method_exists( $item, 'get_' . $field ) ) {
						$data[$field] = call_user_func( array( $item, 'get_' . $field ) );
					}
	
				}

			}

		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $item );
		$response->add_links( $links );

		if ( ! empty( $links['self']['href'] ) ) {
			$actions = $this->get_available_actions( $item, $request );

			$self = $links['self']['href'];

			foreach ( $actions as $rel ) {
				$response->add_link( $rel, $self );
			}
		}

		/**
		 * Filters the item data for a response.
		 *
		 * @since 1.0.13
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WPInv_Item    $item  The item object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "wpinv_rest_prepare_item", $response, $item, $request );
	}

	/**
	 * Gets an array of fields to be included on the response.
	 *
	 * Included fields are based on item schema and `_fields=` request argument.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Fields to be included in the response.
	 */
	public function get_fields_for_response( $request ) {
		$schema     = $this->get_item_schema();
		$properties = isset( $schema['properties'] ) ? $schema['properties'] : array();

		$additional_fields = $this->get_additional_fields();
		foreach ( $additional_fields as $field_name => $field_options ) {
			// For back-compat, include any field with an empty schema
			// because it won't be present in $this->get_item_schema().
			if ( is_null( $field_options['schema'] ) ) {
				$properties[ $field_name ] = $field_options;
			}
		}

		// Exclude fields that specify a different context than the request context.
		$context = $request['context'];
		if ( $context ) {
			foreach ( $properties as $name => $options ) {
				if ( ! empty( $options['context'] ) && ! in_array( $context, $options['context'], true ) ) {
					unset( $properties[ $name ] );
				}
			}
		}

		$fields = array_keys( $properties );

		if ( ! isset( $request['_fields'] ) ) {
			return $fields;
		}
		$requested_fields = wpinv_parse_list( $request['_fields'] );
		if ( 0 === count( $requested_fields ) ) {
			return $fields;
		}
		// Trim off outside whitespace from the comma delimited list.
		$requested_fields = array_map( 'trim', $requested_fields );
		// Always persist 'id', because it can be needed for add_additional_fields_to_object().
		if ( in_array( 'id', $fields, true ) ) {
			$requested_fields[] = 'id';
		}
		// Return the list of all requested fields which appear in the schema.
		return array_reduce(
			$requested_fields,
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
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.13
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {

		// Maybe retrieve the schema from cache.
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',

			// Base properties for every Item.
			'properties' 		  => array(

				'id'           => array(
					'description' => __( 'Unique identifier for the item.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'name'			  => array(
					'description' => __( 'The name for the item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'summary'        => array(
					'description' => __( 'A summary for the item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'price'        => array(
					'description' => __( 'The price for the item.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'status'       => array(
					'description' => __( 'A named status for the item.', 'invoicing' ),
					'type'        => 'string',
					'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					'context'     => array( 'view', 'edit' ),
				),

				'type'       => array(
					'description' => __( 'The item type.', 'invoicing' ),
					'type'        => 'string',
					'enum'        => wpinv_item_types(),
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'vat_rule'       => array(
					'description' => __( 'VAT rule applied to the item.', 'invoicing' ),
					'type'        => 'string',
					'enum'        => array( 'digital', 'physical' ),
					'context'     => array( 'view', 'edit' ),
				),

				'vat_class'       => array(
					'description' => __( 'VAT class for the item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),

				'custom_id'       => array(
					'description' => __( 'Custom id for the item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				
				'custom_name'       => array(
					'description' => __( 'Custom name for the item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'custom_singular_name'       => array(
					'description' => __( 'Custom singular name for the item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'dynamic_pricing'        => array(
					'description' => __( 'Whether the item allows a user to set their own price.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'minimum_price'        => array(
					'description' => __( 'For dynamic prices, this is the minimum price that a user can set.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'is_recurring'        => array(
					'description' => __( 'Whether the item is a subscription item.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'recurring_period'        => array(
					'description' => __( 'The recurring period for a recurring item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'enum'        => array( 'D', 'W', 'M', 'Y' ),
				),

				'recurring_interval'        => array(
					'description' => __( 'The recurring interval for a subscription item.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'recurring_limit'        => array(
					'description' => __( 'The maximum number of renewals for a subscription item.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'free_trial'        => array(
					'description' => __( 'Whether the item has a free trial period.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'trial_period'        => array(
					'description' => __( 'The trial period of a recurring item.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'enum'        => array( 'D', 'W', 'M', 'Y' ),
				),

				'trial_interval'        => array(
					'description' => __( 'The trial interval for a subscription item.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'editable'        => array(
					'description' => __( 'Whether or not the item is editable.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),

			),
		);

		// Add helpful links to the item schem.
		$schema['links'] = $this->get_schema_links();

		/**
		 * Filters the item schema for the REST API.
		 *
		 * Enables adding extra properties to items.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $schema    The item schema.
		 */
        $schema = apply_filters( "wpinv_rest_item_schema", $schema );

		//  Cache the item schema.
		$this->schema = $schema;
		
		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Retrieve Link Description Objects that should be added to the Schema for the invoices collection.
	 *
	 * @since 1.0.13
	 *
	 * @return array
	 */
	protected function get_schema_links() {

		$href = rest_url( "{$this->namespace}/{$this->rest_base}/{id}" );

		$links = array();

		$links[] = array(
			'rel'          => 'https://api.w.org/action-publish',
			'title'        => __( 'The current user can publish this item.' ),
			'href'         => $href,
			'targetSchema' => array(
				'type'       => 'object',
				'properties' => array(
					'status' => array(
						'type' => 'string',
						'enum' => array( 'publish', 'future' ),
					),
				),
			),
		);

		return $links;
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 1.0.13
	 *
	 * @param WPInv_Item $item Item Object.
	 * @return array Links for the given item.
	 */
	protected function prepare_links( $item ) {

		// Prepare the base REST API endpoint for items.
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $item->ID ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		/**
		 * Filters the returned item links for the REST API.
		 *
		 * Enables adding extra links to item API responses.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $links    Rest links.
		 */
		return apply_filters( "wpinv_rest_item_links", $links );

	}

	/**
	 * Get the link relations available for the post and current user.
	 *
	 * @since 1.0.13
	 *
	 * @param WPInv_Item   $item    Item object.
	 * @param WP_REST_Request $request Request object.
	 * @return array List of link relations.
	 */
	protected function get_available_actions( $item, $request ) {

		if ( 'edit' !== $request['context'] ) {
			return array();
		}

		$rels = array();

		// Retrieve the post type object.
		$post_type = get_post_type_object( $item->post_type );

		// Mark item as published.
		if ( current_user_can( $post_type->cap->publish_posts ) ) {
			$rels[] = 'https://api.w.org/action-publish';
		}

		/**
		 * Filters the available item link relations for the REST API.
		 *
		 * Enables adding extra link relation for the current user and request to item responses.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $rels    Available link relations.
		 */
		return apply_filters( "wpinv_rest_item_link_relations", $rels );
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

    
}