<?php

/**
 * Abstract Data.
 *
 * Handles generic data interaction which is implemented by
 * the different data store classes.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract GetPaid Data Class
 *
 * Implemented by classes using the same CRUD(s) pattern.
 *
 * @version  1.0.19
 */
abstract class GetPaid_Data {

	/**
	 * ID for this object.
	 *
	 * @since 1.0.19
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Core data for this object. Name value pairs (name + default value).
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $data = array();

	/**
	 * Core data changes for this object.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $changes = array();

	/**
	 * This is false until the object is read from the DB.
	 *
	 * @since 1.0.19
	 * @var bool
	 */
	protected $object_read = false;

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.19
	 * @var string
	 */
	protected $object_type = 'data';

	/**
	 * Extra data for this object. Name value pairs (name + default value).
	 * Used as a standard way for sub classes (like item types) to add
	 * additional information to an inherited class.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $extra_data = array();

	/**
	 * Set to _data on construct so we can track and reset data if needed.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $default_data = array();

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.19
	 * @var GetPaid_Data_Store
	 */
	protected $data_store;

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.19
	 * @var string
	 */
	protected $cache_group = '';

	/**
	 * Stores the last error.
	 *
	 * @since 1.0.19
	 * @var string
	 */
	public $last_error = '';

	/**
	 * Stores additional meta data.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $meta_data = null;

	/**
	 * Default constructor.
	 *
	 * @param int|object|array|string $read ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $read = 0 ) {
		$this->data         = array_merge( $this->data, $this->extra_data );
		$this->default_data = $this->data;
	}

	/**
	 * Only store the object ID to avoid serializing the data object instance.
	 *
	 * @return array
	 */
	public function __sleep() {
		return array( 'id' );
	}

	/**
	 * Re-run the constructor with the object ID.
	 *
	 * If the object no longer exists, remove the ID.
	 */
	public function __wakeup() {
		$this->__construct( absint( $this->id ) );

		if ( ! empty( $this->last_error ) ) {
			$this->set_id( 0 );
		}

	}

	/**
	 * When the object is cloned, make sure meta is duplicated correctly.
	 *
	 * @since 1.0.19
	 */
	public function __clone() {
		$this->maybe_read_meta_data();
		if ( ! empty( $this->meta_data ) ) {
			foreach ( $this->meta_data as $array_key => $meta ) {
				$this->meta_data[ $array_key ] = clone $meta;
				if ( ! empty( $meta->id ) ) {
					$this->meta_data[ $array_key ]->id = null;
				}
			}
		}
	}

	/**
	 * Get the data store.
	 *
	 * @since  1.0.19
	 * @return object
	 */
	public function get_data_store() {
		return $this->data_store;
	}

	/**
	 * Get the object type.
	 *
	 * @since  1.0.19
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Returns the unique ID for this object.
	 *
	 * @since  1.0.19
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get form status.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
    }

	/**
	 * Delete an object, set the ID to 0, and return result.
	 *
	 * @since  1.0.19
	 * @param  bool $force_delete Should the data be deleted permanently.
	 * @return bool result
	 */
	public function delete( $force_delete = false ) {
		if ( $this->data_store && $this->exists() ) {
			$this->data_store->delete( $this, array( 'force_delete' => $force_delete ) );
			$this->set_id( 0 );
			return true;
		}
		return false;
	}

	/**
	 * Save should create or update based on object existence.
	 *
	 * @since  1.0.19
	 * @return int
	 */
	public function save() {
		if ( ! $this->data_store ) {
			return $this->get_id();
		}

		/**
		 * Trigger action before saving to the DB. Allows you to adjust object props before save.
		 *
		 * @param GetPaid_Data          $this The object being saved.
		 * @param GetPaid_Data_Store_WP $data_store The data store persisting the data.
		 */
		do_action( 'getpaid_before_' . $this->object_type . '_object_save', $this, $this->data_store );

		if ( $this->get_id() ) {
			$this->data_store->update( $this );
		} else {
			$this->data_store->create( $this );
		}

		/**
		 * Trigger action after saving to the DB.
		 *
		 * @param GetPaid_Data          $this The object being saved.
		 * @param GetPaid_Data_Store_WP $data_store The data store persisting the data.
		 */
		do_action( 'getpaid_after_' . $this->object_type . '_object_save', $this, $this->data_store );

		return $this->get_id();
	}

	/**
	 * Change data to JSON format.
	 *
	 * @since  1.0.19
	 * @return string Data in JSON format.
	 */
	public function __toString() {
		return wp_json_encode( $this->get_data() );
	}

	/**
	 * Returns all data for this object.
	 *
	 * @since  1.0.19
	 * @return array
	 */
	public function get_data() {
		return array_merge( array( 'id' => $this->get_id() ), $this->data, array( 'meta_data' => $this->get_meta_data() ) );
	}

	/**
	 * Returns array of expected data keys for this object.
	 *
	 * @since   1.0.19
	 * @return array
	 */
	public function get_data_keys() {
		return array_keys( $this->data );
	}

	/**
	 * Returns all "extra" data keys for an object (for sub objects like item types).
	 *
	 * @since  1.0.19
	 * @return array
	 */
	public function get_extra_data_keys() {
		return array_keys( $this->extra_data );
	}

	/**
	 * Filter null meta values from array.
	 *
	 * @since  1.0.19
	 * @param mixed $meta Meta value to check.
	 * @return bool
	 */
	protected function filter_null_meta( $meta ) {
		return ! is_null( $meta->value );
	}

	/**
	 * Get All Meta Data.
	 *
	 * @since 1.0.19
	 * @return array of objects.
	 */
	public function get_meta_data() {
		$this->maybe_read_meta_data();
		return array_values( array_filter( $this->meta_data, array( $this, 'filter_null_meta' ) ) );
	}

	/**
	 * Check if the key is an internal one.
	 *
	 * @since  1.0.19
	 * @param  string $key Key to check.
	 * @return bool   true if it's an internal key, false otherwise
	 */
	protected function is_internal_meta_key( $key ) {
		$internal_meta_key = ! empty( $key ) && $this->data_store && in_array( $key, $this->data_store->get_internal_meta_keys(), true );

		if ( ! $internal_meta_key ) {
			return false;
		}

		$has_setter_or_getter = is_callable( array( $this, 'set_' . $key ) ) || is_callable( array( $this, 'get_' . $key ) );

		if ( ! $has_setter_or_getter ) {
			return false;
		}

		/* translators: %s: $key Key to check */
		getpaid_doing_it_wrong( __FUNCTION__, sprintf( __( 'Generic add/update/get meta methods should not be used for internal meta data, including "%s". Use getters and setters.', 'invoicing' ), $key ), '1.0.19' );

		return true;
	}

	/**
	 * Magic method for setting data fields.
	 *
	 * This method does not update custom fields in the database.
	 *
	 * @since 1.0.19
	 * @access public
	 *
	 */
	public function __set( $key, $value ) {

		if ( 'id' == strtolower( $key ) ) {
			return $this->set_id( $value );
		}

		if ( method_exists( $this, "set_$key") ) {

			/* translators: %s: $key Key to set */
			getpaid_doing_it_wrong( __FUNCTION__, sprintf( __( 'Object data such as "%s" should not be accessed directly. Use getters and setters.', 'invoicing' ), $key ), '1.0.19' );

			call_user_func( array( $this, "set_$key" ), $value );
		} else {
			$this->set_prop( $key, $value );
		}

	}

	/**
     * Margic method for retrieving a property.
     */
    public function __get( $key ) {

        // Check if we have a helper method for that.
        if ( method_exists( $this, 'get_' . $key ) ) {

			if ( 'post_type' != $key ) {
				/* translators: %s: $key Key to set */
				getpaid_doing_it_wrong( __FUNCTION__, sprintf( __( 'Object data such as "%s" should not be accessed directly. Use getters and setters.', 'invoicing' ), $key ), '1.0.19' );
			}

            return call_user_func( array( $this, 'get_' . $key ) );
        }

        // Check if the key is in the associated $post object.
        if ( ! empty( $this->post ) && isset( $this->post->$key ) ) {
            return $this->post->$key;
        }

		return $this->get_prop( $key );

    }

	/**
	 * Get Meta Data by Key.
	 *
	 * @since  1.0.19
	 * @param  string $key Meta Key.
	 * @param  bool   $single return first found meta with key, or all with $key.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	public function get_meta( $key = '', $single = true, $context = 'view' ) {

		// Check if this is an internal meta key.
		$_key = str_replace( '_wpinv', '', $key );
		$_key = str_replace( 'wpinv', '', $_key );
		if ( $this->is_internal_meta_key( $key ) ) {
			$function = 'get_' . $_key;

			if ( is_callable( array( $this, $function ) ) ) {
				return $this->{$function}();
			}
		}

		// Read the meta data if not yet read.
		$this->maybe_read_meta_data();
		$meta_data  = $this->get_meta_data();
		$array_keys = array_keys( wp_list_pluck( $meta_data, 'key' ), $key, true );
		$value      = $single ? '' : array();

		if ( ! empty( $array_keys ) ) {
			// We don't use the $this->meta_data property directly here because we don't want meta with a null value (i.e. meta which has been deleted via $this->delete_meta_data()).
			if ( $single ) {
				$value = $meta_data[ current( $array_keys ) ]->value;
			} else {
				$value = array_intersect_key( $meta_data, array_flip( $array_keys ) );
			}
		}

		if ( 'view' === $context ) {
			$value = apply_filters( $this->get_hook_prefix() . $key, $value, $this );
		}

		return $value;
	}

	/**
	 * See if meta data exists, since get_meta always returns a '' or array().
	 *
	 * @since  1.0.19
	 * @param  string $key Meta Key.
	 * @return boolean
	 */
	public function meta_exists( $key = '' ) {
		$this->maybe_read_meta_data();
		$array_keys = wp_list_pluck( $this->get_meta_data(), 'key' );
		return in_array( $key, $array_keys, true );
	}

	/**
	 * Set all meta data from array.
	 *
	 * @since 1.0.19
	 * @param array $data Key/Value pairs.
	 */
	public function set_meta_data( $data ) {
		if ( ! empty( $data ) && is_array( $data ) ) {
			$this->maybe_read_meta_data();
			foreach ( $data as $meta ) {
				$meta = (array) $meta;
				if ( isset( $meta['key'], $meta['value'], $meta['id'] ) ) {
					$this->meta_data[] = new GetPaid_Meta_Data(
						array(
							'id'    => $meta['id'],
							'key'   => $meta['key'],
							'value' => $meta['value'],
						)
					);
				}
			}
		}
	}

	/**
	 * Add meta data.
	 *
	 * @since 1.0.19
	 *
	 * @param string       $key Meta key.
	 * @param string|array $value Meta value.
	 * @param bool         $unique Should this be a unique key?.
	 */
	public function add_meta_data( $key, $value, $unique = false ) {
		if ( $this->is_internal_meta_key( $key ) ) {
			$function = 'set_' . $key;

			if ( is_callable( array( $this, $function ) ) ) {
				return $this->{$function}( $value );
			}
		}

		$this->maybe_read_meta_data();
		if ( $unique ) {
			$this->delete_meta_data( $key );
		}
		$this->meta_data[] = new GetPaid_Meta_Data(
			array(
				'key'   => $key,
				'value' => $value,
			)
		);

		$this->save();
	}

	/**
	 * Update meta data by key or ID, if provided.
	 *
	 * @since  1.0.19
	 *
	 * @param  string       $key Meta key.
	 * @param  string|array $value Meta value.
	 * @param  int          $meta_id Meta ID.
	 */
	public function update_meta_data( $key, $value, $meta_id = 0 ) {
		if ( $this->is_internal_meta_key( $key ) ) {
			$function = 'set_' . $key;

			if ( is_callable( array( $this, $function ) ) ) {
				return $this->{$function}( $value );
			}
		}

		$this->maybe_read_meta_data();

		$array_key = false;

		if ( $meta_id ) {
			$array_keys = array_keys( wp_list_pluck( $this->meta_data, 'id' ), $meta_id, true );
			$array_key  = $array_keys ? current( $array_keys ) : false;
		} else {
			// Find matches by key.
			$matches = array();
			foreach ( $this->meta_data as $meta_data_array_key => $meta ) {
				if ( $meta->key === $key ) {
					$matches[] = $meta_data_array_key;
				}
			}

			if ( ! empty( $matches ) ) {
				// Set matches to null so only one key gets the new value.
				foreach ( $matches as $meta_data_array_key ) {
					$this->meta_data[ $meta_data_array_key ]->value = null;
				}
				$array_key = current( $matches );
			}
		}

		if ( false !== $array_key ) {
			$meta        = $this->meta_data[ $array_key ];
			$meta->key   = $key;
			$meta->value = $value;
		} else {
			$this->add_meta_data( $key, $value, true );
		}
	}

	/**
	 * Delete meta data.
	 *
	 * @since 1.0.19
	 * @param string $key Meta key.
	 */
	public function delete_meta_data( $key ) {
		$this->maybe_read_meta_data();
		$array_keys = array_keys( wp_list_pluck( $this->meta_data, 'key' ), $key, true );

		if ( $array_keys ) {
			foreach ( $array_keys as $array_key ) {
				$this->meta_data[ $array_key ]->value = null;
			}
		}
	}

	/**
	 * Delete meta data.
	 *
	 * @since 1.0.19
	 * @param int $mid Meta ID.
	 */
	public function delete_meta_data_by_mid( $mid ) {
		$this->maybe_read_meta_data();
		$array_keys = array_keys( wp_list_pluck( $this->meta_data, 'id' ), (int) $mid, true );

		if ( $array_keys ) {
			foreach ( $array_keys as $array_key ) {
				$this->meta_data[ $array_key ]->value = null;
			}
		}
	}

	/**
	 * Read meta data if null.
	 *
	 * @since 1.0.19
	 */
	protected function maybe_read_meta_data() {
		if ( is_null( $this->meta_data ) ) {
			$this->read_meta_data();
		}
	}

	/**
	 * Read Meta Data from the database. Ignore any internal properties.
	 * Uses it's own caches because get_metadata does not provide meta_ids.
	 *
	 * @since 1.0.19
	 * @param bool $force_read True to force a new DB read (and update cache).
	 */
	public function read_meta_data( $force_read = false ) {

		// Reset meta data.
		$this->meta_data = array();

		// Maybe abort early.
		if ( ! $this->get_id() || ! $this->data_store ) {
			return;
		}

		// Only read from cache if the cache key is set.
		$cache_key = null;
		if ( ! $force_read && ! empty( $this->cache_group ) ) {
			$cache_key     = GetPaid_Cache_Helper::get_cache_prefix( $this->cache_group ) . GetPaid_Cache_Helper::get_cache_prefix( 'object_' . $this->get_id() ) . 'object_meta_' . $this->get_id();
			$raw_meta_data = wp_cache_get( $cache_key, $this->cache_group );
		}

		// Should we force read?
		if ( empty( $raw_meta_data ) ) {
			$raw_meta_data = $this->data_store->read_meta( $this );

			if ( ! empty( $cache_key ) ) {
				wp_cache_set( $cache_key, $raw_meta_data, $this->cache_group );
			}

		}

		// Set meta data.
		if ( is_array( $raw_meta_data ) ) {

			foreach ( $raw_meta_data as $meta ) {
				$this->meta_data[] = new GetPaid_Meta_Data(
					array(
						'id'    => (int) $meta->meta_id,
						'key'   => $meta->meta_key,
						'value' => maybe_unserialize( $meta->meta_value ),
					)
				);
			}

		}

	}

	/**
	 * Update Meta Data in the database.
	 *
	 * @since 1.0.19
	 */
	public function save_meta_data() {
		if ( ! $this->data_store || is_null( $this->meta_data ) ) {
			return;
		}
		foreach ( $this->meta_data as $array_key => $meta ) {
			if ( is_null( $meta->value ) ) {
				if ( ! empty( $meta->id ) ) {
					$this->data_store->delete_meta( $this, $meta );
					unset( $this->meta_data[ $array_key ] );
				}
			} elseif ( empty( $meta->id ) ) {
				$meta->id = $this->data_store->add_meta( $this, $meta );
				$meta->apply_changes();
			} else {
				if ( $meta->get_changes() ) {
					$this->data_store->update_meta( $this, $meta );
					$meta->apply_changes();
				}
			}
		}
		if ( ! empty( $this->cache_group ) ) {
			$cache_key = GetPaid_Cache_Helper::get_cache_prefix( $this->cache_group ) . GetPaid_Cache_Helper::get_cache_prefix( 'object_' . $this->get_id() ) . 'object_meta_' . $this->get_id();
			wp_cache_delete( $cache_key, $this->cache_group );
		}
	}

	/**
	 * Set ID.
	 *
	 * @since 1.0.19
	 * @param int $id ID.
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * Sets item status.
	 *
	 * @since 1.0.19
	 * @param string $status New status.
	 * @return array details of change.
	 */
	public function set_status( $status ) {
        $old_status = $this->get_status();

		$this->set_prop( 'status', $status );

		return array(
			'from' => $old_status,
			'to'   => $status,
		);
    }

	/**
	 * Set all props to default values.
	 *
	 * @since 1.0.19
	 */
	public function set_defaults() {
		$this->data    = $this->default_data;
		$this->changes = array();
		$this->set_object_read( false );
	}

	/**
	 * Set object read property.
	 *
	 * @since 1.0.19
	 * @param boolean $read Should read?.
	 */
	public function set_object_read( $read = true ) {
		$this->object_read = (bool) $read;
	}

	/**
	 * Get object read property.
	 *
	 * @since  1.0.19
	 * @return boolean
	 */
	public function get_object_read() {
		return (bool) $this->object_read;
	}

	/**
	 * Set a collection of props in one go, collect any errors, and return the result.
	 * Only sets using public methods.
	 *
	 * @since  1.0.19
	 *
	 * @param array  $props Key value pairs to set. Key is the prop and should map to a setter function name.
	 * @param string $context In what context to run this.
	 *
	 * @return bool|WP_Error
	 */
	public function set_props( $props, $context = 'set' ) {
		$errors = false;

		$props = wp_unslash( $props );
		foreach ( $props as $prop => $value ) {
			try {
				/**
				 * Checks if the prop being set is allowed, and the value is not null.
				 */
				if ( is_null( $value ) || in_array( $prop, array( 'prop', 'date_prop', 'meta_data' ), true ) ) {
					continue;
				}
				$setter = "set_$prop";

				if ( is_callable( array( $this, $setter ) ) ) {
					$this->{$setter}( $value );
				}
			} catch ( Exception $e ) {
				if ( ! $errors ) {
					$errors = new WP_Error();
				}
				$errors->add( $e->getCode(), $e->getMessage() );
				$this->last_error = $e->getMessage();
			}
		}

		return $errors && count( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * Sets a prop for a setter method.
	 *
	 * This stores changes in a special array so we can track what needs saving
	 * the the DB later.
	 *
	 * @since 1.0.19
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	protected function set_prop( $prop, $value ) {
		if ( array_key_exists( $prop, $this->data ) ) {
			if ( true === $this->object_read ) {
				if ( $value !== $this->data[ $prop ] || array_key_exists( $prop, $this->changes ) ) {
					$this->changes[ $prop ] = $value;
				}
			} else {
				$this->data[ $prop ] = $value;
			}
		}
	}

	/**
	 * Return data changes only.
	 *
	 * @since 1.0.19
	 * @return array
	 */
	public function get_changes() {
		return $this->changes;
	}

	/**
	 * Merge changes with data and clear.
	 *
	 * @since 1.0.19
	 */
	public function apply_changes() {
		$this->data    = array_replace( $this->data, $this->changes );
		$this->changes = array();
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  1.0.19
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'wpinv_get_' . $this->object_type . '_';
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * Gets the value from either current pending changes, or the data itself.
	 * Context controls what happens to the value before it's returned.
	 *
	 * @since  1.0.19
	 * @param  string $prop Name of prop to get.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	protected function get_prop( $prop, $context = 'view' ) {
		$value = null;

		if ( array_key_exists( $prop, $this->data ) ) {
			$value = array_key_exists( $prop, $this->changes ) ? $this->changes[ $prop ] : $this->data[ $prop ];

			if ( 'view' === $context ) {
				$value = apply_filters( $this->get_hook_prefix() . $prop, $value, $this );
			}
		}

		return $value;
	}

	/**
	 * Sets a date prop whilst handling formatting and datetime objects.
	 *
	 * @since 1.0.19
	 * @param string         $prop Name of prop to set.
	 * @param string|integer $value Value of the prop.
	 */
	protected function set_date_prop( $prop, $value ) {

		if ( empty( $value ) ) {
			$this->set_prop( $prop, null );
			return;
		}
		$this->set_prop( $prop, $value );

	}

	/**
	 * When invalid data is found, throw an exception unless reading from the DB.
	 *
	 * @since 1.0.19
	 * @param string $code             Error code.
	 * @param string $message          Error message.
	 */
	protected function error( $code, $message ) {
		$this->last_error = $message;
	}

	/**
	 * Checks if the object is saved in the database
	 *
	 * @since 1.0.19
	 * @return bool
	 */
	public function exists() {
		$id = $this->get_id();
		return ! empty( $id );
	}

}
