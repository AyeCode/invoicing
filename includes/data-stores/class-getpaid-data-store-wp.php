<?php
/**
 * Shared logic for WP based data stores.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Data_Store_WP class.
 * 
 * Datastores that extend this class use CPTs to store data.
 */
class GetPaid_Data_Store_WP {

	/**
	 * Meta type. This should match up with
	 * the types available at https://developer.wordpress.org/reference/functions/add_metadata/.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 *
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * This only needs set if you are using a custom metadata type.
	 *
	 * @var string
	 */
	protected $object_id_field_for_meta = '';

	/**
	 * Data stored in meta keys, but not considered "meta" for an object.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array();

	/**
	 * Meta data which should exist in the DB, even if empty.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $must_exist_meta_keys = array();

	/**
	 * A map of meta keys to data props.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $meta_key_to_props = array();

	/**
	 * Returns an array of meta for an object.
	 *
	 * @since  1.0.19
	 * @param  GetPaid_Data $object GetPaid_Data object.
	 * @return array
	 */
	public function read_meta( &$object ) {
		global $wpdb;
		$db_info       = $this->get_db_info();
		$raw_meta_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$db_info['meta_id_field']} as meta_id, meta_key, meta_value
				FROM {$db_info['table']}
				WHERE {$db_info['object_id_field']} = %d
				ORDER BY {$db_info['meta_id_field']}",
				$object->get_id()
			)
		);

		$this->internal_meta_keys = array_merge( array_map( array( $this, 'prefix_key' ), $object->get_data_keys() ), $this->internal_meta_keys );
		$meta_data                = array_filter( $raw_meta_data, array( $this, 'exclude_internal_meta_keys' ) );
		return apply_filters( "getpaid_data_store_wp_{$this->meta_type}_read_meta", $meta_data, $object, $this );
	}

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @since  1.0.19
	 * @param  GetPaid_Data  $object GetPaid_Data object.
	 * @param  stdClass $meta (containing at least ->id).
	 */
	public function delete_meta( &$object, $meta ) {
		delete_metadata_by_mid( $this->meta_type, $meta->id );
	}

	/**
	 * Add new piece of meta.
	 *
	 * @since  1.0.19
	 * @param  GetPaid_Data  $object GetPaid_Data object.
	 * @param  stdClass $meta (containing ->key and ->value).
	 * @return int meta ID
	 */
	public function add_meta( &$object, $meta ) {
		return add_metadata( $this->meta_type, $object->get_id(), $meta->key, is_string( $meta->value ) ? wp_slash( $meta->value ) : $meta->value, false );
	}

	/**
	 * Update meta.
	 *
	 * @since  1.0.19
	 * @param  GetPaid_Data  $object GetPaid_Data object.
	 * @param  stdClass $meta (containing ->id, ->key and ->value).
	 */
	public function update_meta( &$object, $meta ) {
		update_metadata_by_mid( $this->meta_type, $meta->id, $meta->value, $meta->key );
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @since  1.0.19
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field = 'meta_id'; // users table calls this umeta_id so we need to track this as well.
		$table         = $wpdb->prefix;

		// If we are dealing with a type of metadata that is not a core type, the table should be prefixed.
		if ( ! in_array( $this->meta_type, array( 'post', 'user', 'comment', 'term' ), true ) ) {
			$table .= 'getpaid_';
		}

		$table          .= $this->meta_type . 'meta';
		$object_id_field = $this->meta_type . '_id';

		// Figure out our field names.
		if ( 'user' === $this->meta_type ) {
			$meta_id_field = 'umeta_id';
			$table         = $wpdb->usermeta;
		}

		if ( ! empty( $this->object_id_field_for_meta ) ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data. This is in
	 * addition to all data props with _ prefix.
	 *
	 * @since 1.0.19
	 *
	 * @param string $key Prefix to be added to meta keys.
	 * @return string
	 */
	protected function prefix_key( $key ) {
		return '_' === substr( $key, 0, 1 ) ? $key : '_' . $key;
	}

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta Meta object to check if it should be excluded or not.
	 * @return bool
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		return ! in_array( $meta->meta_key, $this->internal_meta_keys, true ) && 0 !== stripos( $meta->meta_key, 'wp_' );
	}

	/**
	 * Gets a list of props and meta keys that need updated based on change state
	 * or if they are present in the database or not.
	 *
	 * @param  GetPaid_Data $object         The GetPaid_Data object.
	 * @param  array   $meta_key_to_props   A mapping of meta keys => prop names.
	 * @param  string  $meta_type           The internal WP meta type (post, user, etc).
	 * @return array                        A mapping of meta keys => prop names, filtered by ones that should be updated.
	 */
	protected function get_props_to_update( $object, $meta_key_to_props, $meta_type = 'post' ) {
		$props_to_update = array();
		$changed_props   = $object->get_changes();

		// Props should be updated if they are a part of the $changed array or don't exist yet.
		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( array_key_exists( $prop, $changed_props ) || ! metadata_exists( $meta_type, $object->get_id(), $meta_key ) ) {
				$props_to_update[ $meta_key ] = $prop;
			}
		}

		return $props_to_update;
	}

	/**
	 * Read object data.
	 *
	 * @param GetPaid_Data $object GetPaid_Data object.
	 * @param WP_Post   $post_object Post object.
	 * @since 1.0.19
	 */
	protected function read_object_data( &$object, $post_object ) {
		$id    = $object->get_id();
		$props = array();

		foreach ( $this->meta_key_to_props as $meta_key => $prop ) {
			$props[ $prop ] = get_post_meta( $id, $meta_key, true );
		}

		// Set object properties.
		$object->set_props( $props );

		// Gets extra data associated with the object if needed.
		foreach ( $object->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $object, $function ) ) ) {
				$object->{$function}( get_post_meta( $object->get_id(), $key, true ) );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for an object based on it's settings in the GetPaid_Data class.
	 *
	 * @param GetPaid_Data $object GetPaid_Data object.
	 * @since 1.0.19
	 */
	protected function update_post_meta( &$object ) {

		$updated_props   = array();
		$props_to_update = $this->get_props_to_update( $object, $this->meta_key_to_props );
		$object_type     = $object->get_object_type();

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $object->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_post_meta( $object, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		do_action( "getpaid_{$object_type}_object_updated_props", $object, $updated_props );
	}

	/**
	 * Update meta data in, or delete it from, the database.
	 *
	 * Avoids storing meta when it's either an empty string or empty array or null.
	 * Other empty values such as numeric 0 should still be stored.
	 * Data-stores can force meta to exist using `must_exist_meta_keys`.
	 *
	 * Note: WordPress `get_metadata` function returns an empty string when meta data does not exist.
	 *
	 * @param GetPaid_Data $object The GetPaid_Data object.
	 * @param string  $meta_key Meta key to update.
	 * @param mixed   $meta_value Value to save.
	 *
	 * @since 1.0.19 Added to prevent empty meta being stored unless required.
	 *
	 * @return bool True if updated/deleted.
	 */
	protected function update_or_delete_post_meta( $object, $meta_key, $meta_value ) {
		if ( in_array( $meta_value, array( array(), '', null ), true ) && ! in_array( $meta_key, $this->must_exist_meta_keys, true ) ) {
			$updated = delete_post_meta( $object->get_id(), $meta_key );
		} else {
			$updated = update_post_meta( $object->get_id(), $meta_key, $meta_value );
		}

		return (bool) $updated;
	}

	/**
	 * Return list of internal meta keys.
	 *
	 * @since 1.0.19
	 * @return array
	 */
	public function get_internal_meta_keys() {
		return $this->internal_meta_keys;
	}

	/**
	 * Clear any caches.
	 *
	 * @param GetPaid_Data $object GetPaid_Data object.
	 * @since 1.0.19
	 */
	protected function clear_caches( &$object ) {
		clean_post_cache( $object->get_id() );
	}

	/**
	 * Method to delete a data object from the database.
	 *
	 * @param GetPaid_Data $object GetPaid_Data object.
	 * @param array    $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$object, $args = array() ) {
		$id          = $object->get_id();
		$object_type = $object->get_object_type();

		if ( 'invoice' == $object_type ) {
			$object_type = $object->get_type();
		}

		$args        = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			do_action( "getpaid_delete_$object_type", $object );
			wp_delete_post( $id, true );
			$object->set_id( 0 );
		} else {
			do_action( "getpaid_trash_$object_type", $object );
			wp_trash_post( $id );
			$object->set_status( 'trash' );
		}
	}

	/**
	 * Get the status to save to the post object.
	 *
	 *
	 * @since 1.0.19
	 * @param  GetPaid_Data $object GetPaid_Data object.
	 * @return string
	 */
	protected function get_post_status( $object ) {
		$object_status = $object->get_status( 'edit' );
		$object_type   = $object->get_object_type();

		if ( ! $object_status ) {
			$object_status = apply_filters( "getpaid_default_{$object_type}_status", 'draft' );
		}

		return $object_status;
	}

}
