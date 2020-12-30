<?php
/**
 * REST API Setting Options controller
 *
 * Handles requests to the /settings and /settings/$setting_id endpoints.
 *
 * @package GetPaid
 * @subpackage REST API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid REST Setting controller class.
 *
 * @package Invoicing
 */
class GetPaid_REST_Settings_Controller extends GetPaid_REST_Controller {

	/**
	 * An array of available settings.
	 *
	 * @var string
	 */
	protected $settings;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 2.0.0
	 *
	 * @see register_rest_route()
	 */
	public function register_namespace_routes( $namespace ) {

		// List all registered tabs.
		register_rest_route(
			$namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tabs' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => '__return_empty_array',
			)
		);

		// View/Update a single setting.
		register_rest_route(
			$namespace,
			$this->rest_base . '/setting/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id'    => array(
						'description' => __( 'Unique identifier for the setting.', 'invoicing' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// List registered sections for a given tab.
		register_rest_route(
			$namespace,
			$this->rest_base . '/(?P<tab>[\w-]+)',
			array(
				'args'   => array(
					'tab'    => array(
						'description' => __( 'Unique identifier for the tab whose sections should be retrieved.', 'invoicing' ),
						'type'        => 'string',
						'required'    => true,
						'enum'        => array_keys( wpinv_get_settings_tabs() ),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sections' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => '__return_empty_array',
			)
		);

		// List all registered settings for a given tab.
		register_rest_route(
			$namespace,
			$this->rest_base . '/(?P<tab>[\w-]+)/(?P<section>[\w-]+)',
			array(
				'args'   => array(
					'tab'    => array(
						'description' => __( 'Unique identifier for the tab whose settings should be retrieved.', 'invoicing' ),
						'type'        => 'string',
						'required'    => true,
						'enum'        => array_keys( wpinv_get_settings_tabs() ),
					),
					'section'    => array(
						'description' => __( 'The section in the tab whose settings should be retrieved.', 'invoicing' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $this->rest_base . '/batch',
			array(
				'args'   => array(
					'id'              => array(
						'description' => __( 'Setting ID.', 'invoicing' ),
						'type'        => 'string',
					),
				),
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
	 * Return all settings.
	 *
	 * @since  2.0.0
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {

		$settings = $this->get_settings();

		if ( ! isset( $settings[ $request['tab'] ] ) ) {
			return new WP_Error( 'rest_invalid_tab', __( 'Invalid tab.', 'invoicing' ), array( 'status' => 400 ) );
		}

		if ( ! isset( $settings[ $request['tab'] ][ $request['section'] ] ) ) {
			return new WP_Error( 'rest_invalid_section', __( 'Invalid section.', 'invoicing' ), array( 'status' => 400 ) );
		}

		$settings = $settings[ $request['tab'] ][ $request['section'] ];
		$prepared = array();

		foreach ( $settings as $setting ) {

			$setting      = $this->sanitize_setting( $setting );
			$setting_data = $this->prepare_item_for_response( $setting, $request );
			$setting_data = $this->prepare_response_for_collection( $setting_data );

			if ( $this->is_setting_type_valid( $setting['type'] ) ) {
				$prepared[]   = $setting_data;
			}

		}

		return rest_ensure_response( $prepared );
	}

	/**
	 * Return a single setting.
	 *
	 * @since  2.0.0
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$setting  = $this->get_setting( $request['id'] );

		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		$setting  = $this->sanitize_setting( $setting );
		$response = $this->prepare_item_for_response( $setting, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Update a single setting.
	 *
	 * @since  2.0.0
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$setting = $this->get_setting( $request['id'] );

		if ( is_wp_error( $setting ) ) {
			return $setting;
		}

		if ( is_callable( array( $this, 'validate_setting_' . $setting['type'] . '_field' ) ) ) {
			$value = $this->{'validate_setting_' . $setting['type'] . '_field'}( $request['value'], $setting );
		} else {
			$value = $this->validate_setting_text_field( $request['value'], $setting );
		}

		if ( is_wp_error( $value ) ) {
			return $value;
		}

		wpinv_update_option( $request['id'], $value );
		$setting['value'] = $value;
		$setting          = $this->sanitize_setting( $setting );
		$response         = $this->prepare_item_for_response( $setting, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Makes sure the current user has access to READ the settings APIs.
	 *
	 * @since  2.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wpinv_current_user_can_manage_invoicing() ) {
			return new WP_Error( 'rest_cannot_view', __( 'Sorry, you cannot list resources.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Makes sure the current user has access to WRITE the settings APIs.
	 *
	 * @since  2.0.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function update_items_permissions_check( $request ) {
		if ( ! wpinv_current_user_can_manage_invoicing() ) {
			return new WP_Error( 'rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
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
		return wpinv_current_user_can_manage_invoicing() ? true : new WP_Error( 'rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', 'invoicing' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $setting_id Setting ID.
	 * @return array Links for the given setting.
	 */
	protected function prepare_links( $setting_id ) {

		$links = array(
			'self'       => array(
				'href'   => rest_url( sprintf( '/%s/%s/setting/%s', $this->namespace, $this->rest_base, $setting_id ) ),
			),
			'collection' => array(
				'href'   => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Prepare a settings object for serialization.
	 *
	 * @since  2.0.0
	 * @param array           $item Setting object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item['id'] ) );

		return $response;
	}

	/**
	 * Filters out bad values from the settings array/filter so we
	 * only return known values via the API.
	 *
	 * @since 2.0.0
	 * @param  array $setting Setting.
	 * @return array
	 */
	public function filter_setting( $setting ) {
		return array_intersect_key(
			$setting,
			array_flip( array_filter( array_keys( $setting ), array( $this, 'allowed_setting_keys' ) ) )
		);
	}

	/**
	 * Callback for allowed keys for each setting response.
	 *
	 * @param  string $key Key to check.
	 * @return boolean
	 */
	public function allowed_setting_keys( $key ) {
		return in_array( $key, array_keys( $this->setting_defaults() ), true );
	}

	/**
	 * Returns default options for a setting. null means the field is required.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	protected function setting_defaults() {
		return array(
			'id'          => null,
			'name'        => null,
			'desc'        => '',
			'options'     => array(),
			'std'         => false,
			'value'       => false,
			'placeholder' => '',
			'readonly'    => false,
			'faux'        => false,
			'section'     => 'main',
			'tab'         => 'general',
			'type'        => 'text',
		);
	}

	/**
	 * Sanitizes a setting's field.
	 *
	 * @param  array $setting The setting to sanitize.
	 * @return array
	 */
	public function sanitize_setting( $setting ) {
		
		$setting          = wp_parse_args( $setting, $this->setting_defaults() );
		$setting['value'] = wpinv_get_option( $setting['id'], $setting['std'] );
		return $this->filter_setting( $setting );

	}

	/**
	 * Get setting data.
	 *
	 * @since  2.0.0
	 * @param string $setting_id Setting ID.
	 * @return array|WP_Error
	 */
	public function get_setting( $setting_id ) {

		if ( empty( $setting_id ) ) {
			return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'invoicing' ), array( 'status' => 404 ) );
		}

		$settings  = $this->get_settings();

		foreach ( $settings as $tabs ) {

			foreach ( $tabs as $sections ) {

				if ( isset( $sections[ $setting_id ] ) ) {
					if ( ! $this->is_setting_type_valid( $sections[ $setting_id ]['type'] ) ) {
						return new WP_Error( 'rest_setting_setting_type_invalid', __( 'Invalid setting type.', 'invoicing' ), array( 'status' => 404 ) );
					}

					return $sections[ $setting_id ];
				}

			}

		}

		return new WP_Error( 'rest_setting_setting_invalid', __( 'Invalid setting.', 'invoicing' ), array( 'status' => 404 ) );
	}

	/**
	 * Get all tabs.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return array
	 */
	public function get_tabs( $request ) {
		$tabs     = wpinv_get_settings_tabs();
		$prepared = array();

		foreach ( $tabs as $id => $tab ) {

			$_request        = $request;
			$_request['tab'] = sanitize_title( $id );
			$data            = array(
				'id'       => sanitize_title( $id ),
				'label'    => sanitize_text_field( $tab ),
				'sections' => $this->get_sections( $_request ),
			);

			$data     = $this->add_additional_fields_to_object( $data, $request );
			$response = rest_ensure_response( $data );

			if ( ! is_wp_error( $response ) ) {
				$links = array(
					'sections'   => array(
						'href'   => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $id ) ),
					),
					'collection' => array(
						'href'   => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
					),
				);
				$response->add_links( $links );
				$response = $this->prepare_response_for_collection( $response );
			}

			$prepared[] = $response;

		}

		return rest_ensure_response( $prepared );
	}

	/**
	 * Get all sections.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return array
	 */
	public function get_sections( $request ) {

		$tab      = sanitize_title( $request['tab'] );
		$sections = wpinv_get_settings_tab_sections( $tab );
		$prepared = array();

		foreach ( $sections as $id => $section ) {

			$data            = array(
				'id'       => sanitize_title( $id ),
				'label'    => sanitize_text_field( $section ),
			);

			$data     = $this->add_additional_fields_to_object( $data, $request );
			$response = rest_ensure_response( $data );

			if ( ! is_wp_error( $response ) ) {
				$links = array(
					'settings'   => array(
						'href'   => rest_url( sprintf( '/%s/%s/%s/%s', $this->namespace, $this->rest_base, $tab, $id ) ),
					),
					'collection' => array(
						'href'   => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $tab ) ),
					),
					'tabs'       => array(
						'href'   => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
					),
				);
				$response->add_links( $links );
				$response = $this->prepare_response_for_collection( $response );
			}

			$prepared[] = $response;

		}

		return rest_ensure_response( $prepared );
	}

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public function get_settings() {

		if ( empty( $this->settings ) ) {
			$this->settings = wpinv_get_registered_settings();
		}

		return $this->settings;

	}

	/**
	 * Boolean for if a setting type is a valid supported setting type.
	 *
	 * @since  2.0.0
	 * @param  string $type Type.
	 * @return bool
	 */
	public function is_setting_type_valid( $type ) {

		return in_array(
			$type, array(
				'text',         // Validates with validate_setting_text_field.
				'email',        // Validates with validate_setting_text_field.
				'number',       // Validates with validate_setting_text_field.
				'color',        // Validates with validate_setting_text_field.
				'password',     // Validates with validate_setting_text_field.
				'textarea',     // Validates with validate_setting_textarea_field.
				'select',       // Validates with validate_setting_select_field.
				'multiselect',  // Validates with validate_setting_multiselect_field.
				'radio',        // Validates with validate_setting_radio_field (-> validate_setting_select_field).
				'checkbox',     // Validates with validate_setting_checkbox_field.
				'header',       // Validates with validate_setting_text_field.
			)
		);

	}

	/**
	 * Get the settings schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		// Maybe retrieve the schema from cache.
		if ( ! empty( $this->schema ) ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'setting',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique identifier for the setting.', 'invoicing' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'tab'         => array(
					'description' => __( 'An identifier for the tab this setting belongs to.', 'invoicing' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'section'     => array(
					'description' => __( 'An identifier for the section this setting belongs to.', 'invoicing' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'       => array(
					'description' => __( 'A human readable label for the setting used in interfaces.', 'invoicing' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'desc'        => array(
					'description' => __( 'A human readable description for the setting used in interfaces.', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'value'       => array(
					'description' => __( 'The current value of this setting.', 'invoicing' ),
					'type'        => 'mixed',
					'context'     => array( 'view', 'edit' ),
				),
				'default'     => array(
					'description' => __( 'Default value for the setting.', 'invoicing' ),
					'type'        => 'mixed',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'placeholder' => array(
					'description' => __( 'Placeholder text to be displayed in text inputs.', 'invoicing' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'type'        => array(
					'description' => __( 'Type of setting.', 'invoicing' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'text', 'email', 'number', 'color', 'password', 'textarea', 'select', 'multiselect', 'radio', 'image_width', 'checkbox', 'raw_html' ),
					'readonly'    => true,
				),
				'options'     => array(
					'description' => __( 'Array of options (key value pairs) for inputs such as select, multiselect, and radio buttons.', 'invoicing' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'readonly'        => array(
					'description' => __( 'Whether or not this setting is readonly', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'faux'            => array(
					'description' => __( 'Whether or not this setting is readonly/faux', 'invoicing' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		// Filters the settings schema for the REST API.
        $schema = apply_filters( 'getpaid_rest_settings_schema', $schema );

		// Cache the settings schema.
		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );

	}

	/**
	 * Validate a text value for a text based setting.
	 *
	 * @since 2.0.0
	 * @param string $value Value.
	 * @param array  $setting Setting.
	 * @return string
	 */
	public function validate_setting_text_field( $value ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

	/**
	 * Validate select based settings.
	 *
	 * @since 2.0.0
	 * @param string $value Value.
	 * @param array  $setting Setting.
	 * @return string|WP_Error
	 */
	public function validate_setting_select_field( $value, $setting ) {
		if ( array_key_exists( $value, $setting['options'] ) ) {
			return $value;
		} else {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'invoicing' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Validate multiselect based settings.
	 *
	 * @since 2.0.0
	 * @param array $values Values.
	 * @param array $setting Setting.
	 * @return array|WP_Error
	 */
	public function validate_setting_multiselect_field( $values, $setting ) {
		if ( empty( $values ) ) {
			return array();
		}

		if ( ! is_array( $values ) ) {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'invoicing' ), array( 'status' => 400 ) );
		}

		$final_values = array();
		foreach ( $values as $value ) {
			if ( array_key_exists( $value, $setting['options'] ) ) {
				$final_values[] = $value;
			}
		}

		return $final_values;
	}

	/**
	 * Validate radio based settings.
	 *
	 * @since 2.0.0
	 * @param string $value Value.
	 * @param array  $setting Setting.
	 * @return string|WP_Error
	 */
	public function validate_setting_radio_field( $value, $setting ) {
		return $this->validate_setting_select_field( $value, $setting );
	}

	/**
	 * Validate checkbox based settings.
	 *
	 * @since 2.0.0
	 * @param string $value Value.
	 * @return int
	 */
	public function validate_setting_checkbox_field( $value ) {
		return (int) ! empty( $value );
	}

	/**
	 * Validate textarea based settings.
	 *
	 * @since 2.0.0
	 * @param string $value Value.
	 * @return string
	 */
	public function validate_setting_textarea_field( $value ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses(
			trim( stripslashes( $value ) ),
			array_merge(
				array(
					'iframe' => array(
						'src'   => true,
						'style' => true,
						'id'    => true,
						'class' => true,
					),
				),
				wp_kses_allowed_html( 'post' )
			)
		);
	}

}
