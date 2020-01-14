<?php
/**
 * REST API Invoice controller
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
 * REST API invoices controller class.
 *
 * @package Invoicing
 */
class WPInv_REST_Invoice_Controller extends WP_REST_Posts_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
    protected $post_type = 'wpi_invoice';

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
        $this->rest_base = 'invoices';
		
    }
    
    /**
	 * Checks if a given request has access to read invoices.
     * 
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
	
        $post_type = get_post_type_object( $this->post_type );

		if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit invoices.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
		}

		// Read checks will be evaluated on a per invoice basis

		return true;

    }
    
    /**
	 * Retrieves a collection of invoices.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		
		// Retrieve the list of registered invoice query parameters.
        $registered = $this->get_collection_params();
        
        $args       = array();

        foreach( array_keys( $registered ) as $key ) {

            if( isset( $request[ $key] ) ) {
                $args[ $key ] = $request[ $key];
            }

        }

		/**
		 * Filters the wpinv_get_invoices arguments for invoices requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
        $args       = apply_filters( "wpinv_rest_get_invoices_arguments", $args, $request, $this );
		
		// Special args
		$args[ 'return' ]   = 'objects';
		$args[ 'paginate' ] = true;

        // Run the query.
		$query = wpinv_get_invoices( $args );
		
		// Prepare the retrieved invoices
		$invoices = array();
		foreach( $query->invoices as $invoice ) {

			if ( ! $this->check_read_permission( $invoice ) ) {
				continue;
			}

			$data       = $this->prepare_item_for_response( $invoice, $request );
			$invoices[] = $this->prepare_response_for_collection( $data );

		}

		// Prepare the response.
		$response = rest_ensure_response( $invoices );
		$response->header( 'X-WP-Total', (int) $query->total );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		/**
		 * Filters the responses for invoices requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param arrWP_REST_Response $response    Response object.
		 * @param WP_REST_Request     $request The request used.
         * @param array               $args Array of args used to retrieve the invoices
		 */
        $response       = apply_filters( "rest_wpinv_invoices_response", $response, $request, $args );

        return rest_ensure_response( $response );
        
    }

    /**
	 * Get the post, if the ID is valid.
	 *
	 * @since 1.0.13
	 *
	 * @param int $invoice_id Supplied ID.
	 * @return WPInv_Invoice|WP_Error Invoice object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $invoice_id ) {
		
		$error     = new WP_Error( 'rest_invoice_invalid_id', __( 'Invalid invoice ID.', 'invoicing' ), array( 'status' => 404 ) );

        // Ids start from 1
        if ( (int) $invoice_id <= 0 ) {
			return $error;
		}

		$invoice = wpinv_get_invoice( (int) $invoice_id );
		if ( empty( $invoice ) ) {
			return $error;
        }

        return $invoice;

    }

    /**
	 * Checks if a given request has access to read an invoice.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the invoice, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {

        // Retrieve the invoice object.
        $invoice = $this->get_post( $request['id'] );
        
        // Ensure it is valid.
		if ( is_wp_error( $invoice ) ) {
			return $invoice;
		}

		if ( $invoice ) {
			return $this->check_read_permission( $invoice );
		}

		return true;
    }
    
    /**
	 * Checks if an invoice can be read.
	 * 
	 * An invoice can be read by site admins and owners of the invoice
	 *
	 *
	 * @since 1.0.13
	 *
	 * @param WPInv_Invoice $invoice WPInv_Invoice object.
	 * @return bool Whether the post can be read.
	 */
	public function check_read_permission( $invoice ) {

		// An invoice can be read by an admin...
		if ( current_user_can( 'manage_options' ) ||  current_user_can( 'view_invoices' ) ) {
			return true;
		}

        // ... and the owner of the invoice
		if( get_current_user_id() ===(int) $invoice->get_user_id() ) {
			return true;
		}

		return false;
    }
    
    /**
	 * Retrieves a single invoice.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {

        // Fetch the invoice.
        $invoice = $this->get_post( $request['id'] );
        
        // Abort early if it does not exist
		if ( is_wp_error( $invoice ) ) {
			return $invoice;
		}

		// Prepare the response
		$response = $this->prepare_item_for_response( $invoice, $request );
		$response->link_header( 'alternate', esc_url( $invoice->get_view_url() ), array( 'type' => 'text/html' ) );

		/**
		 * Filters the responses for single invoice requests.
		 *
		 *
		 * @since 1.0.13
		 * @var WP_HTTP_Response
		 *
		 * @param WP_HTTP_Response $response Response.
		 * @param WP_REST_Request  $request The request used.
		 */
        $response       = apply_filters( "rest_wpinv_get_invoice_response", $response, $request );

        return rest_ensure_response( $response );

    }
    
    /**
	 * Checks if a given request has access to create an invoice.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
	
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'rest_invoice_exists', __( 'Cannot create existing invoice.', 'invoicing' ), array( 'status' => 400 ) );
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( ! current_user_can( $post_type->cap->create_posts ) ) {
			return new WP_Error( 
                'rest_cannot_create', 
                __( 'Sorry, you are not allowed to create invoices as this user.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
        }

		return true;
    }
    
    /**
	 * Creates a single invoice.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {

		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'rest_invoice_exists', __( 'Cannot create existing invoice.', 'invoicing' ), array( 'status' => 400 ) );
		}

		$request->set_param( 'context', 'edit' );

		// Prepare the updated data.
		$invoice_data = wp_unslash( $this->prepare_item_for_database( $request ) );

		if ( is_wp_error( $invoice_data ) ) {
			return $invoice_data;
		}

		// Try creating the invoice
        $invoice = wpinv_insert_invoice( $invoice_data, true );

		if ( is_wp_error( $invoice ) ) {
            return $invoice;
		}

		// Prepare the response
		$response = $this->prepare_item_for_response( $invoice, $request );

		/**
		 * Fires after a single invoice is created or updated via the REST API.
		 *
		 * @since 1.0.13
		 *
		 * @param WPinv_Invoice   $invoice  Inserted or updated invoice object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a post, false when updating.
		 */
		do_action( "wpinv_rest_insert_invoice", $invoice, $request, true );

		/**
		 * Filters the responses for creating single invoice requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $invoice_data Invoice properties.
		 * @param WP_REST_Request $request The request used.
		 */
        $response       = apply_filters( "rest_wpinv_create_invoice_response", $response, $request );

        return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to update an invoice.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {

		// Retrieve the invoice.
		$invoice = $this->get_post( $request['id'] );
		if ( is_wp_error( $invoice ) ) {
			return $invoice;
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( ! current_user_can(  $post_type->cap->edit_post, $invoice->ID  ) ) {
			return new WP_Error( 
                'rest_cannot_edit', 
                __( 'Sorry, you are not allowed to update this invoice.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
        }

		return true;
	}

	/**
	 * Updates a single invoice.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		
		// Ensure the invoice exists.
        $valid_check = $this->get_post( $request['id'] );
        
        // Abort early if it does not exist
		if ( is_wp_error( $valid_check ) ) {
			return $valid_check;
		}

		$request->set_param( 'context', 'edit' );

		// Prepare the updated data.
		$data_to_update = wp_unslash( $this->prepare_item_for_database( $request ) );

		if ( is_wp_error( $data_to_update ) ) {
			return $data_to_update;
		}

		// Abort if no invoice data is provided
        if( empty( $data_to_update ) ) {
            return new WP_Error( 'missing_data', __( 'An update request cannot be empty.', 'invoicing' ) );
        }

		// Include the invoice ID
		$data_to_update['ID'] = $request['id'];

		// Update the invoice
		$updated_invoice = wpinv_update_invoice( $data_to_update, true );

		// Incase the update operation failed...
		if ( is_wp_error( $updated_invoice ) ) {
			return $updated_invoice;
		}

		// Prepare the response
		$response = $this->prepare_item_for_response( $updated_invoice, $request );

		/** This action is documented in includes/class-wpinv-rest-invoice-controller.php */
		do_action( "wpinv_rest_insert_invoice", $updated_invoice, $request, false );

		/**
		 * Filters the responses for updating single invoice requests.
		 *
		 *
		 * @since 1.0.13
		 *
		 *
		 * @param array           $invoice_data Invoice properties.
		 * @param WP_REST_Request $request The request used.
		 */
        $response       = apply_filters( "wpinv_rest_update_invoice_response", $response, $request );

        return rest_ensure_response( $response );
	}

	/**
	 * Checks if a given request has access to delete an invoice.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to delete the invoice, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {

		// Retrieve the invoice.
		$invoice = $this->get_post( $request['id'] );
		if ( is_wp_error( $invoice ) ) {
			return $invoice;
		}

		// Ensure the current user can delete invoices
		if ( current_user_can( 'manage_options' ) ||  current_user_can( 'delete_invoices' ) ) {
			return new WP_Error( 
                'rest_cannot_delete', 
                __( 'Sorry, you are not allowed to delete this invoice.', 'invoicing' ), 
                array( 
                    'status' => rest_authorization_required_code(),
                )
            );
		}

		return true;
	}

	/**
	 * Deletes a single invoice.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		
		// Retrieve the invoice.
		$invoice = $this->get_post( $request['id'] );
		if ( is_wp_error( $invoice ) ) {
			return $invoice;
		}

		$request->set_param( 'context', 'edit' );

		// Prepare the invoice id
		$id    = $invoice->ID;

		// Prepare the response
		$response = $this->prepare_item_for_response( $invoice, $request );

		// Check if the user wants to bypass the trash...
		$force_delete = (bool) $request['force'];

		// Try deleting the invoice.
		$deleted = wp_delete_post( $id, $force_delete );

		// Abort early if we can't delete the invoice.
		if ( ! $deleted ) {
			return new WP_Error( 'rest_cannot_delete', __( 'The invoice cannot be deleted.', 'invoicing' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires immediately after a single invoice is deleted or trashed via the REST API.
		 *
		 *
		 * @since 1.0.13
		 *
		 * @param WPInv_Invoice    $invoice  The deleted or trashed invoice.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "wpinv_rest_delete_invoice", $invoice, $request );

		return $response;

	}
    
    
    /**
	 * Retrieves the query params for the invoices collection.
	 *
	 * @since 1.0.13
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
        
        $query_params               = array(

            // Invoice status.
            'status'                => array(
                'default'           => 'publish',
                'description'       => __( 'Limit result set to invoices assigned one or more statuses.', 'invoicing' ),
                'type'              => 'array',
                'items'             => array(
                    'enum'          => array_keys( wpinv_get_invoice_statuses( true, true ) ),
                    'type'          => 'string',
                ),
                'sanitize_callback' => array( $this, 'sanitize_post_statuses' ),
            ),

            // User.
            'user'                  => array(
				'description'       => __( 'Limit result set to invoices for a specif user.', 'invoicing' ),
				'type'              => 'integer',
            ),
            
            // Number of results per page
            'limit'                 => array(
				'description'       => __( 'Number of invoices to fetch.', 'invoicing' ),
				'type'              => 'integer',
				'default'           => (int) get_option( 'posts_per_page' ),
            ),

            // Pagination
            'page'     => array(
				'description'       => __( 'Current page to fetch.', 'invoicing' ),
				'type'              => 'integer',
				'default'           => 1,
            ),

            // Exclude certain invoices
            'exclude'  => array(
                'description' => __( 'Ensure result set excludes specific IDs.', 'invoicing' ),
                'type'        => 'array',
                'items'       => array(
                    'type' => 'integer',
                ),
                'default'     => array(),
            ),

            // Order invoices by
            'orderby'  => array(
                'description' => __( 'Sort invoices by object attribute.', 'invoicing' ),
                'type'        => 'string',
                'default'     => 'date',
                'enum'        => array(
                    'author',
                    'date',
                    'id',
                    'modified',
                    'title',
                ),
            ),

            // How to order
            'order'    => array(
                'description' => __( 'Order sort attribute ascending or descending.', 'invoicing' ),
                'type'        => 'string',
                'default'     => 'DESC',
                'enum'        => array( 'ASC', 'DESC' ),
            ),
        );

		/**
		 * Filter collection parameters for the invoices controller.
		 *
		 *
		 * @since 1.0.13
		 *
		 * @param array        $query_params JSON Schema-formatted collection parameters.
		 * @param WP_Post_Type $post_type    Post type object.
		 */
		return apply_filters( "rest_invoices_collection_params", $query_params, 'wpi-invoice' );
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
	 * Prepares a single invoice for create or update.
	 *
	 * @since 1.0.13
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Invoice Properties or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_invoice = new stdClass();

		// Post ID.
		if ( isset( $request['id'] ) ) {
			$existing_invoice = $this->get_post( $request['id'] );
			if ( is_wp_error( $existing_invoice ) ) {
				return $existing_invoice;
			}

			$prepared_invoice->ID 		  = $existing_invoice->ID;
			$prepared_invoice->invoice_id = $existing_invoice->ID;
		}

		$schema = $this->get_item_schema();

		// Invoice owner.
		if ( ! empty( $schema['properties']['user_id'] ) && isset( $request['user_id'] ) ) {
			$prepared_invoice->user_id = (int) $request['user_id'];
		}

		// Cart details.
		if ( ! empty( $schema['properties']['cart_details'] ) && isset( $request['cart_details'] ) ) {
			$prepared_invoice->cart_details = (array) $request['cart_details'];
		}

		// Invoice status.
		if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) ) {

			if ( in_array( $request['status'], array_keys( wpinv_get_invoice_statuses( true, true ) ), true ) ) {
				$prepared_invoice->status = $request['status'];
			}

		}

		// User info
		if ( ! empty( $schema['properties']['user_info'] ) && isset( $request['user_info'] ) ) {
			$prepared_invoice->user_info = array();
			$user_info = (array) $request['user_info'];

			foreach( $user_info as $prop => $value ) {

				if ( ! empty( $schema['properties']['user_info']['properties'][$prop] ) ) {

					$prepared_invoice->user_info[$prop] = $value;
		
				}

			}

		}

		// IP
		if ( ! empty( $schema['properties']['ip'] ) && isset( $request['ip'] ) ) {
			$prepared_invoice->ip = $request['ip'];
		}

		// Payment details
		$prepared_invoice->payment_details = array();

		if ( ! empty( $schema['properties']['gateway'] ) && isset( $request['gateway'] ) ) {
			$prepared_invoice->payment_details['gateway'] = $request['gateway'];
		}

		if ( ! empty( $schema['properties']['gateway_title'] ) && isset( $request['gateway_title'] ) ) {
			$prepared_invoice->payment_details['gateway_title'] = $request['gateway_title'];
		}

		if ( ! empty( $schema['properties']['currency'] ) && isset( $request['currency'] ) ) {
			$prepared_invoice->payment_details['currency'] = $request['currency'];
		}

		if ( ! empty( $schema['properties']['transaction_id'] ) && isset( $request['transaction_id'] ) ) {
			$prepared_invoice->payment_details['transaction_id'] = $request['transaction_id'];
		}

		// Dates
		if ( ! empty( $schema['properties']['date'] ) && isset( $request['date'] ) ) {
			$post_date = rest_get_date_with_gmt( $request['date'] );

			if ( ! empty( $post_date ) ) {
				$prepared_invoice->post_date = $post_date[0];
			}
			
		}

		if ( ! empty( $schema['properties']['due_date'] ) && isset( $request['due_date'] ) ) {
			$due_date = rest_get_date_with_gmt( $request['due_date'] );

			if ( ! empty( $due_date ) ) {
				$prepared_invoice->due_date = $due_date[0];
			}

		}

		$invoice_data = (array) $prepared_invoice;

		/**
		 * Filters an invoice before it is inserted via the REST API.
		 *
		 * @since 1.0.13
		 *
		 * @param array        $invoice_data An array of invoice data
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "wpinv_rest_pre_insert_invoice", $invoice_data, $request );

	}

	/**
	 * Prepares a single invoice output for response.
	 *
	 * @since 1.0.13
	 *
	 * @param WPInv_Invoice   $invoice    Invoice object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $invoice, $request ) {

		$GLOBALS['post'] = get_post( $invoice->ID );

		setup_postdata( $invoice->ID );

		// Fetch the fields to include in this response.
		$fields = $this->get_fields_for_response( $request );

		// Base fields for every invoice.
		$data = array();

		// Set up ID
		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $invoice->ID;
		}


		// Basic properties
		$invoice_properties = array(
			'title', 'email', 'ip', 
			'key', 'number', 'transaction_id', 'mode',
			'gateway', 'gateway_title',
			'total', 'discount', 'discount_code', 
			'tax', 'fees_total', 'subtotal', 'currency',
			'status', 'status_nicename', 'post_type'
		);

		foreach( $invoice_properties as $property ) {

			if ( rest_is_field_included( $property, $fields ) ) {
				$data[$property] = $invoice->get( $property );
			}

		}

		// Cart details
		if ( rest_is_field_included( 'cart_details', $fields ) ) {
			$data['cart_details'] = $invoice->get( 'cart_details' );
		}

		//Dates
		$invoice_properties = array( 'date', 'due_date', 'completed_date' );

		foreach( $invoice_properties as $property ) {

			if ( rest_is_field_included( $property, $fields ) ) {
				$data[$property] = $this->prepare_date_response( '0000-00-00 00:00:00', $invoice->get( $property ) );
			}

		}

		// User id
		if ( rest_is_field_included( 'user_id', $fields ) ) {
			$data['user_id'] = (int) $invoice->get( 'user_id' );
		}

		// User info
		$user_info = array( 'first_name', 'last_name', 'company', 'vat_number', 'vat_rate', 'address', 'city', 'country', 'state', 'zip', 'phone' );

		foreach( $user_info as $property ) {

			if ( rest_is_field_included( "user_info.$property", $fields ) ) {
				$data['user_info'][$property] = $invoice->get( $property );
			}

		}

		// Slug
		if ( rest_is_field_included( 'slug', $fields ) ) {
			$data['slug'] = $invoice->get( 'post_name' );
		}

		// View invoice link
		if ( rest_is_field_included( 'link', $fields ) ) {
			$data['link'] = esc_url( $invoice->get_view_url() );
		}


		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $invoice );
		$response->add_links( $links );

		if ( ! empty( $links['self']['href'] ) ) {
			$actions = $this->get_available_actions( $invoice, $request );

			$self = $links['self']['href'];

			foreach ( $actions as $rel ) {
				$response->add_link( $rel, $self );
			}
		}

		/**
		 * Filters the invoice data for a response.
		 *
		 * @since 1.0.13
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WPInv_Invoice    $invoice  The invoice object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "wpinv_rest_prepare_invoice", $response, $invoice, $request );
	}

	/**
	 * Retrieves the invoice's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.13
	 *
	 * @return array Invoice schema data.
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

			// Base properties for every Invoice.
			'properties' 		  => array(

				'title'			  => array(
					'description' => __( 'The title for the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'user_id'		  => array(
					'description' => __( 'The ID of the owner of the invoice.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'email'		  	  => array(
					'description' => __( 'The email of the owner of the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'ip'			  => array(
					'description' => __( 'The IP of the owner of the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'user_info'       => array(
					'description' => __( 'Information about the owner of the invoice.', 'invoicing' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'properties'  => array(

						'first_name'      => array(
							'description' => __( 'The first name of the owner of the invoice.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'last_name'       => array(
							'description' => __( 'The last name of the owner of the invoice.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'company'         => array(
							'description' => __( 'The company of the owner of the invoice.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'vat_number'      => array(
							'description' => __( 'The VAT number of the owner of the invoice.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'vat_rate'        => array(
							'description' => __( 'The VAT rate applied on the invoice.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'address'        => array(
							'description' => __( 'The address of the invoice owner.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'city'            => array(
							'description' => __( 'The city of the invoice owner.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'country'         => array(
							'description' => __( 'The country of the invoice owner.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'state'           => array(
							'description' => __( 'The state of the invoice owner.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'zip'             => array(
							'description' => __( 'The zip code of the invoice owner.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),

						'phone'             => array(
							'description' => __( 'The phone number of the invoice owner.', 'invoicing' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
						),
					),
				),

				'id'           => array(
					'description' => __( 'Unique identifier for the invoice.', 'invoicing' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'key'			  => array(
					'description' => __( 'A unique key for the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'number'		  => array(
					'description' => __( 'The invoice number.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'transaction_id'  => array(
					'description' => __( 'The transaction id of the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'gateway'		  => array(
					'description' => __( 'The gateway used to process the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'gateway_title'	  => array(
					'description' => __( 'The title of the gateway used to process the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'total'	  		  => array(
					'description' => __( 'The total amount of the invoice.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'discount'		  => array(
					'description' => __( 'The discount applied to the invoice.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'discount_code'	  => array(
					'description' => __( 'The discount code applied to the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'tax'	  		  => array(
					'description' => __( 'The tax applied to the invoice.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'fees_total'	  => array(
					'description' => __( 'The total fees applied to the invoice.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'subtotal'	  	  => array(
					'description' => __( 'The sub-total for the invoice.', 'invoicing' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'currency'	  	  => array(
					'description' => __( 'The currency used to process the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'cart_details'	  => array(
					'description' => __( 'The cart details for invoice.', 'invoicing' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit', 'embed' ),
					'required'	  => true,
				),

				'date'         => array(
					'description' => __( "The date the invoice was published, in the site's timezone.", 'invoicing' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'due_date'     => array(
					'description' => __( 'The due date for the invoice.', 'invoicing' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),

				'completed_date'  => array(
					'description' => __( 'The completed date for the invoice.', 'invoicing' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				
				'link'         => array(
					'description' => __( 'URL to the invoice.', 'invoicing' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'mode'       	  => array(
					'description' => __( 'The mode used to process the invoice.', 'invoicing' ),
					'type'        => 'string',
					'enum'        => array( 'live', 'test' ),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),

				'slug'       	  => array(
					'description' => __( 'An alphanumeric identifier for the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
					'readonly'    => true,
				),

				'status'       	  => array(
					'description' => __( 'A named status for the invoice.', 'invoicing' ),
					'type'        => 'string',
					'enum'        => array_keys( wpinv_get_invoice_statuses( true, true ) ),
					'context'     => array( 'view', 'edit' ),
					'default'	  => 'wpi-pending',
				),

				'status_nicename' => array(
					'description' => __( 'A human-readable status name for the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'embed' ),
					'readonly'    => true,
				),

				'post_type'       => array(
					'description' => __( 'The post type for the invoice.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		// Add helpful links to the invoice schem.
		$schema['links'] = $this->get_schema_links();

		/**
		 * Filters the invoice schema for the REST API.
		 *
		 * Enables adding extra properties to invoices.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $schema    The invoice schema.
		 */
        $schema = apply_filters( "wpinv_rest_invoice_schema", $schema );

		// Cache the invoice schema.
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
			'title'        => __( 'The current user can mark this invoice as completed.', 'invoicing' ),
			'href'         => $href,
			'targetSchema' => array(
				'type'       => 'object',
				'properties' => array(
					'status' => array(
						'type' => 'string',
						'enum' => array( 'publish', 'wpi-renewal' ),
					),
				),
			),
		);

		$links[] = array(
			'rel'          => 'https://api.w.org/action-assign-author',
			'title'        => __( 'The current user can change the owner of this invoice.', 'invoicing' ),
			'href'         => $href,
			'targetSchema' => array(
				'type'       => 'object',
				'properties'   => array(
					'user_id'  => array(
						'type' => 'integer',
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
	 * @param WPInv_Invoice $invoice Invoice Object.
	 * @return array Links for the given invoice.
	 */
	protected function prepare_links( $invoice ) {

		// Prepare the base REST API endpoint for invoices.
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $invoice->ID ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		if ( ! empty( $invoice->user_id ) ) {
			$links['user'] = array(
				'href'       => rest_url( 'wp/v2/users/' . $invoice->user_id ),
				'embeddable' => true,
			);
		}

		/**
		 * Filters the returned invoice links for the REST API.
		 *
		 * Enables adding extra links to invoice API responses.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $links    Rest links.
		 */
		return apply_filters( "wpinv_invoice_rest_links", $links );

	}

	/**
	 * Get the link relations available for the post and current user.
	 *
	 * @since 1.0.13
	 *
	 * @param WPInv_Invoice   $invoice    Invoice object.
	 * @param WP_REST_Request $request Request object.
	 * @return array List of link relations.
	 */
	protected function get_available_actions( $invoice, $request ) {

		if ( 'edit' !== $request['context'] ) {
			return array();
		}

		$rels = array();

		// Retrieve the post type object.
		$post_type = get_post_type_object( $invoice->post_type );

		// Mark invoice as completed.
		if ( current_user_can( $post_type->cap->publish_posts ) ) {
			$rels[] = 'https://api.w.org/action-publish';
		}

		// Change the owner of the invoice.
		if ( current_user_can( $post_type->cap->edit_others_posts ) ) {
			$rels[] = 'https://api.w.org/action-assign-author';
		}

		/**
		 * Filters the available invoice link relations for the REST API.
		 *
		 * Enables adding extra link relation for the current user and request to invoice responses.
		 *
		 * @since 1.0.13
		 *
		 * @param array   $rels    Available link relations.
		 */
		return apply_filters( "wpinv_invoice_rest_link_relations", $rels );
	}

	/**
	 * Sanitizes and validates the list of post statuses.
	 *
	 * @since 1.0.13
	 *
	 * @param string|array    $statuses  One or more post statuses.
	 * @param WP_REST_Request $request   Full details about the request.
	 * @param string          $parameter Additional parameter to pass to validation.
	 * @return array|WP_Error A list of valid statuses, otherwise WP_Error object.
	 */
	public function sanitize_post_statuses( $statuses, $request, $parameter ) {

		$statuses 	  = wp_parse_slug_list( $statuses );
		$valid_statuses = array_keys( wpinv_get_invoice_statuses( true, true ) );
		return array_intersect( $statuses, $valid_statuses );
		
	}
    
}