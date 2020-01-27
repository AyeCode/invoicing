<?php
/**
 * REST API Discounts controller
 *
 * Handles requests to the invoices endpoint.
 *
 * @package  Invoicing
 * @since    1.0.13
 */

if ( !defined( 'WPINC' ) ) {
    exit;
}

/**
 * REST API discounts controller class.
 *
 * @package Invoicing
 */
class WPInv_REST_Discounts_Controller extends WP_REST_Posts_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wpi_discount';
	
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
        $this->rest_base = 'discounts';
		
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
			'/' . $this->rest_base . '/discount-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_discount_types' ),
				),
			)
		);

	}

    /**
	 * Checks if a given request has access to read discounts.
     * 
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
	
		if ( wpinv_current_user_can_manage_invoicing() ) {
			return true;
		}

		return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view invoice discounts.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );

    }
    
    /**
	 * Retrieves a collection of discounts.
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
		 * Filters the wpinv_get_all_discounts arguments for discounts rest requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
        $args       = apply_filters( "wpinv_rest_get_discounts_arguments", $args, $request, $this );
		
		// Special args
		$args[ 'return' ]   = 'objects';
		$args[ 'paginate' ] = true;

        // Run the query.
		$query = wpinv_get_all_discounts( $args );
		
		// Prepare the retrieved discounts
		$discounts = array();
		foreach( $query->discounts as $discount ) {

			$data       = $this->prepare_item_for_response( $discount, $request );
			$discounts[]    = $this->prepare_response_for_collection( $data );

		}

		// Prepare the response.
		$response = rest_ensure_response( $discounts );
		$response->header( 'X-WP-Total', (int) $query->total );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		/**
		 * Filters the responses for discount requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param arrWP_REST_Response $response    Response object.
		 * @param WP_REST_Request     $request The request used.
         * @param array               $args Array of args used to retrieve the discounts
		 */
        $response       = apply_filters( "wpinv_rest_discounts_response", $response, $request, $args );

        return rest_ensure_response( $response );
        
    }

    /**
	 * Get the post, if the ID is valid.
	 *
	 * @since 1.0.13
	 *
	 * @param int $discount_id Supplied ID.
	 * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $discount_id ) {
		
		$error     = new WP_Error( 'rest_item_invalid_id', __( 'Invalid discount ID.', 'invoicing' ), array( 'status' => 404 ) );

        // Ids start from 1
        if ( (int) $discount_id <= 0 ) {
			return $error;
		}

		$discount = wpinv_get_discount( (int) $discount_id );
		if ( empty( $discount ) ) {
			return $error;
        }

        return $discount;

    }

    /**
	 * Checks if a given request has access to read a discount.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the invoice item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {

        // Retrieve the discount object.
        $discount = $this->get_post( $request['id'] );
        
        // Ensure it is valid.
		if ( is_wp_error( $discount ) ) {
			return $discount;
		}

		if ( ! wpinv_current_user_can_manage_invoicing() ) {
			return new WP_Error(
                'rest_cannot_view', 
                __( 'Sorry, you are not allowed to view this discount.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
        }

		return true;
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

        // Fetch the discount.
        $discount = $this->get_post( $request['id'] );
        
        // Abort early if it does not exist
		if ( is_wp_error( $discount ) ) {
			return $discount;
		}

		// Prepare the response
		$response = $this->prepare_item_for_response( $discount, $request );

		/**
		 * Filters the responses for single discount requests.
		 *
		 *
		 * @since 1.0.13
		 * @var WP_HTTP_Response
		 *
		 * @param WP_HTTP_Response $response Response.
		 * @param WP_REST_Request  $request The request used.
		 */
        $response       = apply_filters( "wpinv_rest_get_discount_response", $response, $request );

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

		if ( wpinv_current_user_can_manage_invoicing() ) {
			return true;
		}

		$post_type = get_post_type_object( $this->post_type );
		if ( ! current_user_can( $post_type->cap->create_posts ) ) {
			return new WP_Error( 
                'rest_cannot_create', 
                __( 'Sorry, you are not allowed to create discounts as this user.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
        }

		return true;
    }
    
    /**
	 * Creates a single discount.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'rest_item_exists', __( 'Cannot create existing discount.', 'invoicing' ), array( 'status' => 400 ) );
		}

		$request->set_param( 'context', 'edit' );

		// Prepare the updated data.
		$discount_data = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $discount_data ) ) {
			return $discount_data;
		}

		$discount_data['post_type'] = $this->post_type;

		// Try creating the discount.
        $discount = wp_insert_post( $discount_data, true );

		if ( is_wp_error( $discount ) ) {
            return $discount;
		}

		// Prepare the response
		$response = $this->prepare_item_for_response( $discount, $request );

		/**
		 * Fires after a single discount is created or updated via the REST API.
		 *
		 * @since 1.0.13
		 *
		 * @param WP_Post   $discount  Inserted or updated discount object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a post, false when updating.
		 */
		do_action( "wpinv_rest_insert_discount", $discount, $request, true );

		/**
		 * Filters the responses for creating single item requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $response Invoice properties.
		 * @param WP_REST_Request $request The request used.
		 */
        $response       = apply_filters( "wpinv_rest_create_discount_response", $response, $request );

        return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to update a discount.
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
			__( 'Sorry, you are not allowed to update this discount.', 'invoicing' ), 
			array( 
				'status' => rest_authorization_required_code(),
			)
		);

	}

	/**
	 * Updates a single discount.
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

		if( empty( $data_to_update['meta_input'] ) ) {
			unset( $data_to_update['meta_input'] );
		}

		// Abort if no item data is provided
        if( empty( $data_to_update ) ) {
            return new WP_Error( 'missing_data', __( 'An update request cannot be empty.', 'invoicing' ) );
		}
		
		// post_status
		if( ! empty( $data_to_update['post_status'] ) ) {
			wpinv_update_discount_status( $request['id'], $data_to_update['post_status'] );
			unset( $data_to_update['post_status'] );
		}

		// Update the item
		if( ! empty( $data_to_update ) ) {

			// Include the item ID
			$data_to_update['ID'] = $request['id'];

			$updated = wp_update_post( $data_to_update, true );

			// Incase the update operation failed...
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

		}

		$updated_discount = get_post( $request['id'] );

		// Prepare the response
		$response = $this->prepare_item_for_response( $updated_discount, $request );

		/** This action is documented in includes/class-wpinv-rest-item-controller.php */
		do_action( "wpinv_rest_insert_discount", $updated_discount, $request, false );

		/**
		 * Filters the responses for updating single discount requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $data_to_update Discount properties.
		 * @param WP_REST_Request $request The request used.
		 */
        $response       = apply_filters( "wpinv_rest_update_discount_response", $response,  $data_to_update, $request );

        return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to delete a discount.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to delete the discount, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {

		// Retrieve the discount.
		$discount = $this->get_post( $request['id'] );
		if ( is_wp_error( $discount ) ) {
			return $discount;
		} 

		// Ensure the current user can delete the discount
		if (! wpinv_current_user_can_manage_invoicing() ) {
			return new WP_Error( 
                'rest_cannot_delete', 
                __( 'Sorry, you are not allowed to delete this discount.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
		}

		return true;
	}

	/**
	 * Deletes a single discount.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		
		// Retrieve the discount.
		$discount = $this->get_post( $request['id'] );
		if ( is_wp_error( $discount ) ) {
			return $discount;
		}

		$request->set_param( 'context', 'edit' );

		// Prepare the discount id
		$id    = $discount->ID;

		// Prepare the response
		$response = $this->prepare_item_for_response( $discount, $request );

		// Delete the discount...
		wpinv_remove_discount( $id );

		/**
		 * Fires immediately after a single discount is deleted via the REST API.
		 *
		 *
		 * @since 1.0.13
		 *
		 * @param WP_POST    $discount  The deleted discount.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "wpinv_rest_delete_discount", $discount, $request );

		return $response;

	}
    
    
    /**
	 * Retrieves the query params for the discount collection.
	 *
	 * @since 1.0.13
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
        
        $query_params               = array(

            // Discount status.
            'status'                => array(
                'default'           => 'publish',
                'description'       => __( 'Limit result set to discounts assigned one or more statuses.', 'invoicing' ),
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_post_statuses' ),
            ),
            
            // Discount types
            'type'                  => array(
				'description'       => __( 'Type of discounts to fetch.', 'invoicing' ),
				'type'              => 'array',
				'default'           => array_keys( wpinv_get_discount_types() ),
				'items'             => array(
                    'enum'          => array_keys( wpinv_get_discount_types() ),
                    'type'          => 'string',
                ),
			),
			
			// Number of results per page
            'limit'                 => array(
				'description'       => __( 'Number of discounts to fetch.', 'invoicing' ),
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

            // Order discounts by
            'orderby'  => array(
                'description' => __( 'Sort discounts by object attribute.', 'invoicing' ),
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
				'description'       => __( 'Return discounts that match the search term.', 'invoicing' ),
				'type'              => 'string',
            ),
        );

		/**
		 * Filter collection parameters for the discounts controller.
		 *
		 *
		 * @since 1.0.13
		 *
		 * @param array        $query_params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( "wpinv_rest_discounts_collection_params", $query_params );
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
	 * @return array|WP_Error Discount Properties or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_item 		 = new stdClass();
		$prepared_item->meta_input = array();

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
		if ( ! empty( $schema['properties']['title'] ) && isset( $request['title'] ) ) {
			$prepared_item->post_title = sanitize_text_field( $request['title'] );
		}

		// item status.
		if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) && in_array( $request['status'], array_keys( get_post_stati( array( 'internal' => false ) ) ) ) ) {
			$prepared_item->post_status = sanitize_text_field( $request['status'] );
		}

		// Code.
		if ( ! empty( $schema['properties']['code'] ) && isset( $request['code'] ) ) {
			$prepared_item->meta_input['_wpi_discount_code'] = trim( $request['code'] );
		}

		// Type.
		if ( ! empty( $schema['properties']['type'] ) && isset( $request['type'] )  && in_array( $request['type'], array_keys( wpinv_get_discount_types() ) ) ) {
			$prepared_item->meta_input['_wpi_discount_type'] = trim( $request['type'] );
		}

		// Amount.
		if ( ! empty( $schema['properties']['amount'] ) && isset( $request['amount'] ) ) {
			$prepared_item->meta_input['_wpi_discount_amount'] = floatval( $request['amount'] );
		}

		// Items.
		if ( ! empty( $schema['properties']['items'] ) && isset( $request['items'] ) ) {
			$prepared_item->meta_input['_wpi_discount_items'] = wpinv_parse_list( $request['items'] );
		}

		// Excluded Items.
		if ( ! empty( $schema['properties']['exclude_items'] ) && isset( $request['exclude_items'] ) ) {
			$prepared_item->meta_input['_wpi_discount_excluded_items'] = wpinv_parse_list( $request['exclude_items'] );
		}

		// Start date.
		if ( ! empty( $schema['properties']['start_date'] ) && isset( $request['start_date'] ) ) {
			$prepared_item->meta_input['_wpi_discount_start'] = trim( $request['start_date'] );
		}

		// End date.
		if ( ! empty( $schema['properties']['end_date'] ) && isset( $request['end_date'] ) ) {
			$prepared_item->meta_input['_wpi_discount_expiration'] = trim( $request['end_date'] );
		}

		// Minimum amount.
		if ( ! empty( $schema['properties']['minimum_amount'] ) && isset( $request['minimum_amount'] ) ) {
			$prepared_item->meta_input['_wpi_discount_min_total'] = floatval( $request['minimum_amount'] );
		}

		// Maximum amount.
		if ( ! empty( $schema['properties']['maximum_amount'] ) && isset( $request['maximum_amount'] ) ) {
			$prepared_item->meta_input['_wpi_discount_max_total'] = floatval( $request['maximum_amount'] );
		}

		// Recurring.
		if ( ! empty( $schema['properties']['recurring'] ) && isset( $request['recurring'] ) ) {
			$prepared_item->meta_input['_wpi_discount_is_recurring'] = empty( (int) $request['recurring'] ) ? 0 : 1;
		}

		// Maximum uses.
		if ( ! empty( $schema['properties']['max_uses'] ) && isset( $request['max_uses'] ) ) {
			$prepared_item->meta_input['_wpi_discount_max_uses'] = intval( $request['max_uses'] );
		}

		// Single use.
		if ( ! empty( $schema['properties']['single_use'] ) && isset( $request['single_use'] ) ) {
			$prepared_item->meta_input['_wpi_discount_is_single_use'] = empty( (int) $request['single_use'] ) ? 0 : 1;
		}

		$discount_data = (array) wp_unslash( $prepared_item );

		/**
		 * Filters an item before it is inserted via the REST API.
		 *
		 * @since 1.0.13
		 *
		 * @param array        $discount_data An array of discount data
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "wpinv_rest_pre_insert_discount", $discount_data, $request );

	}

	/**
	 * Prepares a single discount output for response.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_Post   $discount    WP_Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $discount, $request ) {

		$GLOBALS['post'] = get_post( $discount->ID );

		setup_postdata( $discount->ID );

		// Fetch the fields to include in this response.
		$fields = $this->get_fields_for_response( $request );

		// Base fields for every discount.
		$data = array();

		// Set up ID.
		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $discount->ID;
		}

		// Title.
		if ( rest_is_field_included( 'title', $fields ) ) {
			$data['title'] = get_the_title( $discount->ID );
		}

		// Code.
		if ( rest_is_field_included( 'code', $fields ) ) {
			$data['code'] = wpinv_get_discount_code( $discount->ID );
		}

		// Type.
		if ( rest_is_field_included( 'type', $fields ) ) {
			$data['type'] = wpinv_get_discount_type( $discount->ID );
		}

		// Amount.
		if ( rest_is_field_included( 'amount', $fields ) ) {
			$data['amount'] = wpinv_get_discount_amount( $discount->ID );
		}

		// Status.
		if ( rest_is_field_included( 'status', $fields ) ) {
			$data['status'] = get_post_status( $discount->ID );
		}

		// Items.
		if ( rest_is_field_included( 'items', $fields ) ) {
			$data['items'] = wpinv_get_discount_item_reqs( $discount->ID );
		}

		// Excluded Items.
		if ( rest_is_field_included( 'exclude_items', $fields ) ) {
			$data['exclude_items'] = wpinv_get_discount_excluded_items( $discount->ID );
		}

		// Start date.
		if ( rest_is_field_included( 'start_date', $fields ) ) {
			$data['start_date'] = wpinv_get_discount_start_date( $discount->ID );
		}

		// End date.
		if ( rest_is_field_included( 'end_date', $fields ) ) {
			$data['end_date'] = wpinv_get_discount_expiration( $discount->ID );
		}

		// Minimum amount.
		if ( rest_is_field_included( 'minimum_amount', $fields ) ) {
			$data['minimum_amount'] = wpinv_get_discount_min_total( $discount->ID );
		}

		// Maximum amount.
		if ( rest_is_field_included( 'maximum_amount', $fields ) ) {
			$data['maximum_amount'] = wpinv_get_discount_max_total( $discount->ID );
		}

		// Recurring.
		if ( rest_is_field_included( 'recurring', $fields ) ) {
			$data['recurring'] = wpinv_discount_is_recurring( $discount->ID );
		}

		// Maximum uses.
		if ( rest_is_field_included( 'max_uses', $fields ) ) {
			$data['max_uses'] = wpinv_get_discount_max_uses( $discount->ID );
		}

		// Single use.
		if ( rest_is_field_included( 'single_use', $fields ) ) {
			$data['single_use'] = wpinv_discount_is_single_use( $discount->ID );
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $discount );
		$response->add_links( $links );

		if ( ! empty( $links['self']['href'] ) ) {
			$actions = $this->get_available_actions( $discount, $request );

			$self = $links['self']['href'];

			foreach ( $actions as $rel ) {
				$response->add_link( $rel, $self );
			}
		}

		/**
		 * Filters the discount data for a response.
		 *
		 * @since 1.0.13
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post    $discount  The discount post object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "wpinv_rest_prepare_discount", $response, $discount, $request );
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
	 * Retrieves the discount's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.13
	 *
	 * @return array Discount schema data.
	 */
	public function get_item_schema() {

		// Maybe retrieve the schema from cache.
		if (  empty( $this->schema ) ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',

			// Base properties for every Item.
			'properties' 		  => array(

				'id'           => array(
					'description' => __( 'Unique identifier for the discount.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'title'			  => array(
					'description' => __( 'The title for the discount.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				'code'        => array(
					'description' => __( 'The discount code.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'required'	  => true,
				),

				'type'        => array(
					'description' => __( 'The type of discount.', 'invoicing' ),
					'type'        => 'string',
					'enum'        => array_keys( wpinv_get_discount_types() ),
					'context'     => array( 'view', 'edit', 'embed' ),
					'default'	  => 'percentage',
				),

				'amount'        => array(
					'description' => __( 'The discount value.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
					'required'	  => true,
				),

				'status'       => array(
					'description' => __( 'A named status for the discount.', 'invoicing' ),
					'type'        => 'string',
					'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					'context'     => array( 'view', 'edit' ),
				),

				'items'       => array(
					'description' => __( 'Items which need to be in the cart to use this discount or, for "Item Discounts", which items are discounted. If left blank, this discount will be used on any item.', 'invoicing' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),

				'exclude_items'   => array(
					'description' => __( 'Items which are NOT allowed to use this discount.', 'invoicing' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),

				'start_date'       => array(
					'description' => __( 'The start date for the discount in the format of yyyy-mm-dd hh:mm:ss  . If provided, the discount can only be used after or on this date.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),

				'end_date'        => array(
					'description' => __( 'The expiration date for the discount.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				
				'minimum_amount'       => array(
					'description' => __( 'Minimum amount needed to use this invoice.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'maximum_amount'       => array(
					'description' => __( 'Maximum amount needed to use this invoice.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'recurring'       => array(
					'description' => __( 'Whether the discount is applied to all recurring payments or only the first recurring payment.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'max_uses'        => array(
					'description' => __( 'The maximum number of times this discount code can be used.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'single_use'       => array(
					'description' => __( 'Whether or not this discount can only be used once per user.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				)

			),
		);

		// Add helpful links to the discount schem.
		$schema['links'] = $this->get_schema_links();

		/**
		 * Filters the discount schema for the REST API.
		 *
		 * Enables adding extra properties to discounts.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $schema    The discount schema.
		 */
        $schema = apply_filters( "wpinv_rest_discount_schema", $schema );

		//  Cache the discount schema.
		$this->schema = $schema;
		
		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Retrieve Link Description Objects that should be added to the Schema for the discounts collection.
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
			'title'        => __( 'The current user can publish this discount.', 'invoicing' ),
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
	 * @param WP_Post $discount Post Object.
	 * @return array Links for the given discount.
	 */
	protected function prepare_links( $discount ) {

		// Prepare the base REST API endpoint for discounts.
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $discount->ID ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		/**
		 * Filters the returned discount links for the REST API.
		 *
		 * Enables adding extra links to discount API responses.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $links    Rest links.
		 */
		return apply_filters( "wpinv_rest_discount_links", $links );

	}

	/**
	 * Get the link relations available for the post and current user.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_Post   $discount    WP_Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return array List of link relations.
	 */
	protected function get_available_actions( $discount, $request ) {

		if ( 'edit' !== $request['context'] ) {
			return array();
		}

		$rels = array();

		// Retrieve the post type object.
		$post_type = get_post_type_object( $discount->post_type );

		// Mark discount as published.
		if ( current_user_can( $post_type->cap->publish_posts ) ) {
			$rels[] = 'https://api.w.org/action-publish';
		}

		/**
		 * Filters the available discount link relations for the REST API.
		 *
		 * Enables adding extra link relation for the current user and request to discount responses.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $rels    Available link relations.
		 */
		return apply_filters( "wpinv_rest_discount_link_relations", $rels );
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
    
}