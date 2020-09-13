<?php
/**
 * GetPaid REST Posts controller class.
 *
 * Extends the GetPaid_REST_Controller class to provide functionalities for endpoints
 * that store data using CPTs
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST Posts controller class.
 *
 * @package Invoicing
 */
class GetPaid_REST_Posts_Controller extends GetPaid_REST_Controller {

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
	 * Contains this controller's class name.
	 *
	 * @var string
	 */
	public $crud_class;

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

		if ( 'revision' === $this->post_type ) {
			$permission = false;
		} else {
			$cap              = $contexts[ $context ];
			$post_type_object = get_post_type_object( $this->post_type );
			$permission       = current_user_can( $post_type_object->cap->$cap, $object_id );
		}

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
	 * Returns the item's object.
	 *
	 * Child classes must implement this method.
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

		return $object->get_id() ? $object : new WP_Error( 'rest_object_invalid_id', __( 'Invalid ID.', 'invoicing' ), array( 'status' => 404 ) );

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
		remove_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10, 3 );

		// Fetch the item.
		$object = $this->get_object( $request['id'] );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		// Generate a response.
		$data     = $this->prepare_item_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		// (Maybe) add a link to the html pagee.
		if ( $this->public && ! is_wp_error( $response ) ) {
			$response->link_header( 'alternate', get_permalink( $object->get_id() ), array( 'type' => 'text/html' ) );
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
		remove_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10, 3 );

		// Can not create an existing item.
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new WP_Error( "getpaid_rest_{$this->post_type}_exists", __( 'Cannot create existing resource.', 'invoicing' ), array( 'status' => 400 ) );
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

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param WP_Post         $post      Post object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "getpaid_rest_insert_{$this->post_type}", $object, $request, true );

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
		remove_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10, 3 );

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

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param GetPaid_Data    $object    GetPaid_Data object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "getpaid_rest_insert_{$this->post_type}", $object, $request, false );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $object, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Get a collection of objects.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		remove_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10, 3 );

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
		remove_filter( 'rest_post_dispatch', 'rest_filter_response_fields', 10, 3 );

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

		$links = array(
			'self'       => array(
				'href'   => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href'   => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

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
				$query_args[ $var ] = apply_filters( "getpaid_rest_query_var-{$var}", $prepared_args[ $var ] );
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
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['status'] = array(
			'default'           => $this->get_post_statuses(),
			'description'       => __( 'Limit result set to resources assigned one or more statuses.', 'invoicing' ),
			'type'              => array( 'array', 'string' ),
			'items'             => array(
				'enum'          => $this->get_post_statuses(),
				'type'          => 'string',
			),
			'validate_callback' => 'rest_validate_request_arg',
			'sanitize_callback' => array( $this, 'sanitize_post_statuses' ),
		);

		$params['after'] = array(
			'description'        => __( 'Limit response to resources created after a given ISO8601 compliant date.', 'invoicing' ),
			'type'               => 'string',
			'format'             => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
			'sanitize_callback'  => 'sanitize_text_field',
		);
		$params['before'] = array(
			'description'        => __( 'Limit response to resources created before a given ISO8601 compliant date.', 'invoicing' ),
			'type'               => 'string',
			'format'             => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
			'sanitize_callback'  => 'sanitize_text_field',
		);
		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'invoicing' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['include'] = array(
			'description'       => __( 'Limit result set to specific ids.', 'invoicing' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['offset'] = array(
			'description'        => __( 'Offset the result set by a specific number of items.', 'invoicing' ),
			'type'               => 'integer',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['order'] = array(
			'description'        => __( 'Order sort attribute ascending or descending.', 'invoicing' ),
			'type'               => 'string',
			'default'            => 'desc',
			'enum'               => array( 'asc', 'desc' ),
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
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
		);

		$post_type_obj = get_post_type_object( $this->post_type );

		if ( isset( $post_type_obj->hierarchical ) && $post_type_obj->hierarchical ) {
			$params['parent'] = array(
				'description'       => __( 'Limit result set to those of particular parent IDs.', 'invoicing' ),
				'type'              => 'array',
				'items'             => array(
					'type'          => 'integer',
				),
				'sanitize_callback' => 'wp_parse_id_list',
				'default'           => array(),
			);
			$params['parent_exclude'] = array(
				'description'       => __( 'Limit result set to all items except those of a particular parent ID.', 'invoicing' ),
				'type'              => 'array',
				'items'             => array(
					'type'          => 'integer',
				),
				'sanitize_callback' => 'wp_parse_id_list',
				'default'           => array(),
			);
		}

		return $params;
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
		if ( $this->schema ) {
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
	 * Only return writable props from schema.
	 *
	 * @param  array $schema Schema.
	 * @return bool
	 */
	public function filter_writable_props( $schema ) {
		return empty( $schema['readonly'] );
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
		return apply_filters( "getpaid_rest_pre_insert_{$this->post_type}_object", $object, $request );
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
		return apply_filters( "getpaid_rest_{$this->post_type}_object_supports_key", true, $object, $field_key );
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
		return apply_filters( "getpaid_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

}
