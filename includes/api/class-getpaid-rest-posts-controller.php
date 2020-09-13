<?php
/**
 * GetPaid REST Posts controller class.
 *
 * Extends the GetPaid_REST_CRUD_Controller class to provide functionalities for endpoints
 * that store CRUD data using CPTs
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST Posts controller class.
 *
 * @package Invoicing
 */
class GetPaid_REST_Posts_Controller extends GetPaid_REST_CRUD_Controller {

    /**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * Controls visibility on frontend.
	 *
	 * @var string
	 */
	public $public = false;

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
			$namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);

	}

	/**
	 * Check permissions of items on REST API.
	 *
	 * @since 1.0.19
	 * @param string $context   Request context.
	 * @param int    $object_id Post ID.
	 * @return bool
	 */
	public function check_post_permissions( $context = 'read', $object_id = 0 ) {

		$contexts = array(
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
			'batch'  => 'edit_others_posts',
		);

		$cap              = $contexts[ $context ];
		$post_type_object = get_post_type_object( $this->post_type );
		$permission       = current_user_can( $post_type_object->cap->$cap, $object_id );

		return apply_filters( 'getpaid_rest_check_permissions', $permission, $context, $object_id, $this->post_type );
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		return $this->check_post_permissions() ? true : new WP_Error( 'rest_cannot_view', __( 'Sorry, you cannot list resources.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		return $this->check_post_permissions( 'create' ) ? true : new WP_Error( 'rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$post = get_post( (int) $request['id'] );

		if ( $post && ! $this->check_post_permissions( 'read', $post->ID ) ) {
			return new WP_Error( 'rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$post = get_post( (int) $request['id'] );

		if ( $post && ! $this->check_post_permissions( 'edit', $post->ID ) ) {
			return new WP_Error( 'rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$post = get_post( (int) $request['id'] );

		if ( $post && ! $this->check_post_permissions( 'delete', $post->ID ) ) {
			return new WP_Error( 'rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access batch create, update and delete items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return boolean|WP_Error
	 */
	public function batch_items_permissions_check( $request ) {
		return $this->check_post_permissions( 'batch' ) ? true : new WP_Error( 'rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * @deprecated
	 */
	public function get_post( $object_id ) {
		return $this->get_object( $object_id );
    }

	/**
	 * Get a single object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {

		// Fetch item.
		$response = parent::get_item( $request );

		// (Maybe) add a link to the html pagee.
		if ( $this->public && ! is_wp_error( $response ) ) {
			$response->link_header( 'alternate', get_permalink( $this->data_object->get_id() ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	/**
	 * Create a single object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		// Create item.
		$response = parent::create_item( $request );

		// Fire a hook after an item is created.
		if ( ! is_wp_error( $response ) ) {

			/**
			 * Fires after a single item is created or updated via the REST API.
			 *
			 * @param WP_Post         $post      Post object.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating item, false when updating.
			 */
			do_action( "getpaid_rest_insert_{$this->post_type}", $this->data_object, $request, true );

		}

		return $response;

	}

	/**
	 * Update a single object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {

		// Create item.
		$response = parent::update_item( $request );

		// Fire a hook after an item is created.
		if ( ! is_wp_error( $response ) ) {

			/**
			 * Fires after a single item is created or updated via the REST API.
			 *
			 * @param WP_Post         $post      Post object.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating item, false when updating.
			 */
			do_action( "getpaid_rest_insert_{$this->post_type}", $this->data_object, $request, false );

		}

		return $response;

	}

	/**
	 * Get a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$args                         = array();
		$args['offset']               = $request['offset'];
		$args['order']                = $request['order'];
		$args['orderby']              = $request['orderby'];
		$args['paged']                = $request['page'];
		$args['post__in']             = $request['include'];
		$args['post__not_in']         = $request['exclude'];
		$args['posts_per_page']       = $request['per_page'];
		$args['name']                 = $request['slug'];
		$args['post_parent__in']      = $request['parent'];
		$args['post_parent__not_in']  = $request['parent_exclude'];
		$args['s']                    = $request['search'];
		$args['post_status']          = wpinv_parse_list( $request['status'] );

		$args['date_query'] = array();

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Force the post_type & fields arguments, since they're not a user input variable.
		$args['post_type'] = $this->post_type;
		$args['fields']    = 'ids';

		// Filter the query arguments for a request.
		$args       = apply_filters( "getpaid_rest_{$this->post_type}_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

		$posts_query = new WP_Query();
		$query_result = $posts_query->query( $query_args );

		$posts = array();
		foreach ( $query_result as $post_id ) {
			if ( ! $this->check_post_permissions( 'read', $post_id ) ) {
				continue;
			}

			$data    = $this->prepare_item_for_response( $this->get_object( $post_id ), $request );
			$posts[] = $this->prepare_response_for_collection( $data );
		}

		$page        = (int) $query_args['paged'];
		$total_posts = $posts_query->found_posts;

		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );
			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		$max_pages = ceil( $total_posts / (int) $query_args['posts_per_page'] );

		$response = rest_ensure_response( $posts );
		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();
		$base = add_query_arg( $request_params, rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {

		// Fetch the item.
		$item = $this->get_object( $request['id'] );
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$supports_trash = EMPTY_TRASH_DAYS > 0;
		$force          = $supports_trash && (bool) $request['force'];

		if ( ! $this->check_post_permissions( 'delete', $item->ID ) ) {
			return new WP_Error( "cannot_delete", __( 'Sorry, you are not allowed to delete this resource.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $item, $request );

		if ( ! wp_delete_post( $item->ID, $force ) ) {
			return new WP_Error( 'rest_cannot_delete', sprintf( __( 'The resource cannot be deleted.', 'invoicing' ), $this->post_type ), array( 'status' => 500 ) );
		}

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param GetPaid_Data    $object GetPaid_Data object.
	 * @return array Links for the given object.
	 */
	protected function prepare_links( $object ) {

		$links = parent::prepare_links( $object );

		if ( is_callable( array( $object, 'get_user_id' ) ) ) {
			$links['user'] = array(
				'href'       => rest_url( 'wp/v2/users/' . call_user_func(  array( $object, 'get_user_id' )  ) ),
				'embeddable' => true,
			);
		}

		if ( is_callable( array( $object, 'get_owner' ) ) ) {
			$links['owner']  = array(
				'href'       => rest_url( 'wp/v2/users/' . call_user_func(  array( $object, 'get_owner' )  ) ),
				'embeddable' => true,
			);
		}

		if ( is_callable( array( $object, 'get_parent_id' ) ) && call_user_func(  array( $object, 'get_parent_id' )  ) ) {
			$links['parent']  = array(
				'href'       => rest_url( "$this->namespace/$this->rest_base/" . call_user_func(  array( $object, 'get_parent_id' )  ) ),
				'embeddable' => true,
			);
		}

		return $links;
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

		$valid_vars = array_flip( $this->get_allowed_query_vars() );
		$query_args = array();
		foreach ( $valid_vars as $var => $index ) {
			if ( isset( $prepared_args[ $var ] ) ) {
				$query_args[ $var ] = apply_filters( "getpaid_rest_query_var-{$var}", $prepared_args[ $var ], $index );
			}
		}

		$query_args['ignore_sticky_posts'] = true;

		if ( 'include' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'post__in';
		} elseif ( 'id' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'ID'; // ID must be capitalized.
		} elseif ( 'slug' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'name';
		}

		return apply_filters( 'getpaid_rest_prepare_items_query', $query_args, $request, $this );

	}

	/**
	 * Get all the WP Query vars that are allowed for the API request.
	 *
	 * @return array
	 */
	protected function get_allowed_query_vars() {
		global $wp;

		/**
		 * Filter the publicly allowed query vars.
		 *
		 * Allows adjusting of the default query vars that are made public.
		 *
		 * @param array  Array of allowed WP_Query query vars.
		 */
		$valid_vars = apply_filters( 'query_vars', $wp->public_query_vars );

		$post_type_obj = get_post_type_object( $this->post_type );
		if ( current_user_can( $post_type_obj->cap->edit_posts ) ) {
			$private = apply_filters( 'getpaid_rest_private_query_vars', $wp->private_query_vars );
			$valid_vars = array_merge( $valid_vars, $private );
		}

		// Define our own in addition to WP's normal vars.
		$rest_valid = array(
			'post_status',
			'date_query',
			'ignore_sticky_posts',
			'offset',
			'post__in',
			'post__not_in',
			'post_parent',
			'post_parent__in',
			'post_parent__not_in',
			'posts_per_page',
			'meta_query',
			'tax_query',
			'meta_key',
			'meta_value',
			'meta_compare',
			'meta_value_num',
		);
		$valid_vars = array_merge( $valid_vars, $rest_valid );

		// Filter allowed query vars for the REST API.
		$valid_vars = apply_filters( 'getpaid_rest_query_vars', $valid_vars, $this );

		return $valid_vars;
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {

		return array_merge(

			parent::get_collection_params(),

			array(
				'status' => array(
					'default'           => $this->get_post_statuses(),
					'description'       => __( 'Limit result set to resources assigned one or more statuses.', 'invoicing' ),
					'type'              => array( 'array', 'string' ),
					'items'             => array(
						'enum'          => $this->get_post_statuses(),
						'type'          => 'string',
					),
					'validate_callback' => 'rest_validate_request_arg',
					'sanitize_callback' => array( $this, 'sanitize_post_statuses' ),
				),
				'after' => array(
					'description'        => __( 'Limit response to resources created after a given ISO8601 compliant date.', 'invoicing' ),
					'type'               => 'string',
					'format'             => 'string',
					'validate_callback'  => 'rest_validate_request_arg',
					'sanitize_callback'  => 'sanitize_text_field',
				),
				'before' => array(
					'description'        => __( 'Limit response to resources created before a given ISO8601 compliant date.', 'invoicing' ),
					'type'               => 'string',
					'format'             => 'string',
					'validate_callback'  => 'rest_validate_request_arg',
					'sanitize_callback'  => 'sanitize_text_field',
				),
				'exclude' => array(
					'description'       => __( 'Ensure result set excludes specific IDs.', 'invoicing' ),
					'type'              => 'array',
					'items'             => array(
						'type'          => 'integer',
					),
					'default'           => array(),
					'sanitize_callback' => 'wp_parse_id_list',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'include' => array(
					'description'       => __( 'Limit result set to specific ids.', 'invoicing' ),
					'type'              => 'array',
					'items'             => array(
						'type'          => 'integer',
					),
					'default'           => array(),
					'sanitize_callback' => 'wp_parse_id_list',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'offset' => array(
					'description'        => __( 'Offset the result set by a specific number of items.', 'invoicing' ),
					'type'               => 'integer',
					'sanitize_callback'  => 'absint',
					'validate_callback'  => 'rest_validate_request_arg',
				),
				'order' => array(
					'description'        => __( 'Order sort attribute ascending or descending.', 'invoicing' ),
					'type'               => 'string',
					'default'            => 'desc',
					'enum'               => array( 'asc', 'desc' ),
					'validate_callback'  => 'rest_validate_request_arg',
				),
				'orderby' => array(
					'description'        => __( 'Sort collection by object attribute.', 'invoicing' ),
					'type'               => 'string',
					'default'            => 'date',
					'enum'               => array(
						'date',
						'id',
						'include',
						'title',
						'slug',
						'modified',
					),
					'validate_callback'  => 'rest_validate_request_arg',
				),
			)
		);
	}

	/**
	 * Retrieves the items's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.19
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {

		// Maybe retrieve the schema from cache.
		if ( ! empty( $this->schema ) ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$type   = str_replace( 'wpi_', '', $this->post_type );
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => wpinv_get_data( "$type-schema" ),
		);

		// Filters the invoice schema for the REST API.
        $schema = apply_filters( "wpinv_rest_{$type}_schema", $schema );

		// Cache the invoice schema.
		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
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
		return array_intersect( wp_parse_slug_list( $statuses ), $this->get_post_statuses() );
	}

	/**
	 * Retrieves a valid list of post statuses.
	 *
	 * @since 1.0.19
	 *
	 * @return array A list of registered item statuses.
	 */
	public function get_post_statuses() {
		return get_post_stati();
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
		$supports = parent::object_supports_field( $object, $field_key );
		return apply_filters( "getpaid_rest_{$this->post_type}_object_supports_key", $supports, $object, $field_key );
	}

}
