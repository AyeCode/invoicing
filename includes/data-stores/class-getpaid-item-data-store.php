<?php
/**
 * GetPaid_Item_Data_Store class file.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Item Data Store: Stored in CPT.
 *
 * @version  1.0.19
 */
class GetPaid_Item_Data_Store extends GetPaid_Data_Store_WP {

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

	/**
	 * A map of meta keys to data props.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $meta_key_to_props = array(
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
		'_wpinv_version'              => 'version',
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
			do_action( 'getpaid_new_item', $item );
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
			$item->set_id( 0 );
			return false;
		}

		$item->set_props(
			array(
				'parent_id'     => $item_object->post_parent,
				'date_created'  => 0 < $item_object->post_date ? $item_object->post_date : null,
				'date_modified' => 0 < $item_object->post_modified ? $item_object->post_modified : null,
				'status'        => $item_object->post_status,
				'name'          => $item_object->post_title,
				'description'   => $item_object->post_excerpt,
				'author'        => $item_object->post_author,
			)
		);

		$this->read_object_data( $item, $item_object );
		$item->read_meta_data();
		$item->set_object_read( true );
		do_action( 'getpaid_read_item', $item );

	}

	/**
	 * Method to update an item in the database.
	 *
	 * @param WPInv_Item $item Item object.
	 */
	public function update( &$item ) {
		$item->save_meta_data();
		$item->set_version( WPINV_VERSION );

		if ( null === $item->get_date_created( 'edit' ) ) {
			$item->set_date_created(  current_time('mysql') );
		}

		// Grab the current status so we can compare.
		$previous_status = get_post_status( $item->get_id() );

		$changes = $item->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'parent_id', 'description', 'name', 'author' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => $item->get_date_created( 'edit' ),
				'post_status'       => $item->get_status( 'edit' ),
				'post_parent'       => $item->get_parent_id( 'edit' ),
				'post_excerpt'      => $item->get_description( 'edit' ),
				'post_modified'     => $item->get_date_modified( 'edit' ),
				'post_title'        => $item->get_name( 'edit' ),
				'post_author'       => $item->get_author( 'edit' ),
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

		// Fire a hook depending on the status - this should be considered a creation if it was previously draft status.
		$new_status = $item->get_status( 'edit' );

		if ( $new_status !== $previous_status && in_array( $previous_status, array( 'new', 'auto-draft', 'draft' ), true ) ) {
			do_action( 'getpaid_new_item', $item );
		} else {
			do_action( 'getpaid_update_item', $item );
		}

	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Helper method that updates all the post meta for an item based on it's settings in the WPInv_Item class.
	 *
	 * @param WPInv_Item $item WPInv_Item object.
	 * @since 1.0.19
	 */
	protected function update_post_meta( &$item ) {

		// Ensure that we have a custom id.
        if ( ! $item->get_custom_id() ) {
            $item->set_custom_id( $item->get_id() );
		}

		parent::update_post_meta( $item );
	}

}
