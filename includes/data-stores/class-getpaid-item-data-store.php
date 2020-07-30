<?php
/**
 * GetPaid_Item_Data_Store class file.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract item Data Store: Stored in CPT.
 *
 * @version  1.0.19
 */
class GetPaid_Item_Data_Store extends GetPaid_Data_Store_WP {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @since 1.0.19
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Data stored in meta keys, but not considered "meta" for an item.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_wpinv_price',
		'_wpinv_vat_rule',
		'_wpinv_vat_class',
		'_wpinv_type',
		'_wpinv_custom_id',
		'_wpinv_custom_name',
		'_wpinv_custom_singular_name',
		'_wpinv_editable',
		'_wpinv_dynamic_pricing',
		'_minimum_price',
		'_wpinv_is_recurring',
		'_wpinv_recurring_period',
		'_wpinv_recurring_interval',
		'_wpinv_recurring_limit',
		'_wpinv_free_trial',
		'_wpinv_trial_period',
		'_wpinv_trial_interval'
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new item in the database.
	 *
	 * @param WPInv_Item $item Item object.
	 */
	public function create( &$item ) {
		$item->set_version( WPINV_VERSION );
		$item->set_date_created( current_time('mysql') );

		// Create a new post.
		$id = wp_insert_post(
			apply_filters(
				'getpaid_new_item_data',
				array(
					'post_date'     => $item->get_date_created( 'edit' ),
					'post_type'     => 'wpi_item',
					'post_status'   => $this->get_post_status( $item ),
					'ping_status'   => 'closed',
					'post_author'   => $item->get_author( 'edit' ),
					'post_title'    => $item->get_name( 'edit' ),
					'post_parent'   => 0,
					'post_excerpt'  => $item->get_description( 'edit' ),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$item->set_id( $id );
			$this->update_post_meta( $item );
			$item->save_meta_data();
			$item->apply_changes();
			$this->clear_caches( $item );
			return true;
		}

		if ( is_wp_error( $id ) ) {
			$item->last_error = $id->get_error_message();
		}
		
		return false;
	}

	/**
	 * Method to read an item from the database.
	 *
	 * @param WPInv_Item $item Item object.
	 *
	 */
	public function read( &$item ) {

		$item->set_defaults();
		$item_object = get_post( $item->get_id() );

		if ( ! $item->get_id() || ! $item_object || $item_object->post_type != 'wpi_item' ) {
			$item->last_error = __( 'Invalid item.', 'invoicing' );
			return false;
		}

		$item->set_props(
			array(
				'parent_id'     => $item_object->post_parent,
				'date_created'  => 0 < $item_object->post_date_gmt ? $item_object->post_date_gmt : null,
				'date_modified' => 0 < $item_object->post_modified_gmt ? $item_object->post_modified_gmt : null,
				'status'        => $item_object->post_status,
				'name'          => $item_object->post_title,
				'description'   => $item_object->post_excerpt,
				'author'        => $item_object->post_author,
			)
		);

		$this->read_item_data( $item, $item_object );
		$item->read_meta_data();
		$item->set_object_read( true );

	}

	/**
	 * Method to update an item in the database.
	 *
	 * @param WPInv_Item $item Order object.
	 */
	public function update( &$item ) {
		$item->save_meta_data();
		$item->set_version( WPINV_VERSION );

		if ( null === $item->get_date_created( 'edit' ) ) {
			$item->set_date_created(  current_time('mysql') );
		}

		$changes = $item->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'parent_id', 'post_excerpt' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => $item->get_date_created( 'edit' ),
				'post_status'       => $item->get_status( $item ),
				'post_parent'       => $item->get_parent_id(),
				'post_excerpt'      => $item->get_description(),
				'post_modified'     => $item->get_date_modified( 'edit' ),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $item->get_id() ) );
				clean_post_cache( $item->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $item->get_id() ), $post_data ) );
			}
			$item->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $item );
		$item->apply_changes();
		$this->clear_caches( $item );
	}

	/**
	 * Method to delete an item from the database.
	 *
	 * @param WPInv_Item $item Item object.
	 * @param array    $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$item, $args = array() ) {
		$id   = $item->get_id();
		$args = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$item->set_id( 0 );
			do_action( 'getpaid_delete_item', $id );
		} else {
			wp_trash_post( $id );
			$item->set_status( 'trash' );
			do_action( 'getpaid_trash_item', $id );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the status to save to the post object.
	 *
	 *
	 * @since 1.0.19
	 * @param  WPInv_item $item Item object.
	 * @return string
	 */
	protected function get_post_status( $item ) {
		$item_status = $item->get_status( 'edit' );

		if ( ! $item_status ) {
			$item_status = apply_filters( 'getpaid_default_item_status', 'draft' );
		}

		return $item_status;
	}

	/**
	 * Read item data.
	 *
	 * @param WPInv_Item $item Item object.
	 * @param WP_Post   $post_object Post object.
	 * @since 1.0.19
	 */
	protected function read_item_data( &$item, $post_object ) {
		$id = $item->get_id();

		// Set item properties.
		$item->set_props(
			array(
				'price'                => get_post_meta( $id, '_wpinv_price', true ),
				'vat_rule'             => get_post_meta( $id, '_wpinv_vat_rule', true ),
				'vat_class'            => get_post_meta( $id, '_wpinv_vat_class', true ),
				'type'                 => get_post_meta( $id, '_wpinv_type', true ),
				'custom_id'            => get_post_meta( $id, '_wpinv_custom_id', true ),
				'custom_name'          => get_post_meta( $id, '_wpinv_custom_name', true ),
				'custom_singular_name' => get_post_meta( $id, '_wpinv_custom_singular_name', true ),
				'is_editable'          => get_post_meta( $id, '_wpinv_editable', true ),
				'is_dynamic_pricing'   => get_post_meta( $id, '_wpinv_dynamic_pricing', true ),
				'minimum_price'        => get_post_meta( $id, '_minimum_price', true ),
				'is_recurring'         => get_post_meta( $id, '_wpinv_is_recurring', true ),
				'recurring_period'     => get_post_meta( $id, '_wpinv_recurring_period', true ),
				'recurring_interval'   => get_post_meta( $id, '_wpinv_recurring_interval', true ),
				'recurring_limit'      => get_post_meta( $id, '_wpinv_recurring_limit', true ),
				'is_free_trial'        => get_post_meta( $id, '_wpinv_free_trial', true ),
				'trial_period'         => get_post_meta( $id, '_wpinv_trial_period', true ),
				'trial_interval'       => get_post_meta( $id, '_wpinv_trial_interval', true ),
				'version'              => get_post_meta( $id, '_wpinv_version', true ),
			)
		);

		// Gets extra data associated with the item if needed.
		foreach ( $item->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $item, $function ) ) ) {
				$item->{$function}( get_post_meta( $item->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for an item based on it's settings in the WPInv_Item class.
	 *
	 * @param WPInv_Item $item Item object.
	 * @since 1.0.19
	 */
	protected function update_post_meta( &$item ) {
		$updated_props     = array();

		$meta_key_to_props = array(
			'_wpinv_price'                => 'price',
			'_wpinv_vat_rule'             => 'vat_rule',
			'_wpinv_vat_class'            => 'vat_class',
			'_wpinv_type'                 => 'type',
			'_wpinv_custom_id'            => 'custom_id',
			'_wpinv_custom_name'          => 'custom_name',
			'_wpinv_custom_singular_name' => 'custom_singular_name',
			'_wpinv_editable'             => 'is_editable',
			'_wpinv_dynamic_pricing'      => 'is_dynamic_pricing',
			'_minimum_price'              => 'minimum_price',
			'_wpinv_custom_name'          => 'custom_name',
			'_wpinv_is_recurring'         => 'is_recurring',
			'_wpinv_recurring_period'     => 'recurring_period',
			'_wpinv_recurring_interval'   => 'recurring_interval',
			'_wpinv_recurring_limit'      => 'recurring_limit',
			'_wpinv_free_trial'           => 'is_free_trial',
			'_wpinv_trial_period'         => 'trial_period',
			'_wpinv_trial_interval'       => 'trial_interval',
		);

		$props_to_update = $this->get_props_to_update( $item, $meta_key_to_props );

		// Ensure that we have a custom id.
        if ( ! $item->get_custom_id() ) {
            $item->set_custom_id( $item->get_id() );
        }

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $item->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;

			$updated = $this->update_or_delete_post_meta( $item, $meta_key, $value );

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		do_action( 'getpaid_item_object_updated_props', $item, $updated_props );
	}

	/**
	 * Clear any caches.
	 *
	 * @param WPInv_Item $item Item object.
	 * @since 1.0.19
	 */
	protected function clear_caches( &$item ) {
		clean_post_cache( $item->get_id() );
	}

}
