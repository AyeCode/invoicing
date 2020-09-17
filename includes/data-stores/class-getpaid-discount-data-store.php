<?php

/**
 * GetPaid_Discount_Data_Store class file.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discount Data Store: Stored in CPT.
 *
 * @version  1.0.19
 */
class GetPaid_Discount_Data_Store extends GetPaid_Data_Store_WP {

	/**
	 * Data stored in meta keys, but not considered "meta" for a discount.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_wpi_discount_code',
		'_wpi_discount_amount',
		'_wpi_discount_start',
		'_wpi_discount_expiration',
		'_wpi_discount_type',
		'_wpi_discount_uses',
		'_wpi_discount_is_single_use',
		'_wpi_discount_items',
		'_wpi_discount_excluded_items',
		'_wpi_discount_max_uses',
		'_wpi_discount_is_recurring',
		'_wpi_discount_min_total',
		'_wpi_discount_max_total',
	);

	/**
	 * A map of meta keys to data props.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $meta_key_to_props = array(
		'_wpi_discount_code'           => 'code',
		'_wpi_discount_amount'         => 'amount',
		'_wpi_discount_start'          => 'start',
		'_wpi_discount_expiration'     => 'expiration',
		'_wpi_discount_type'           => 'type',
		'_wpi_discount_uses'           => 'uses',
		'_wpi_discount_is_single_use'  => 'is_single_use',
		'_wpi_discount_items'          => 'items',
		'_wpi_discount_excluded_items' => 'excluded_items',
		'_wpi_discount_max_uses'       => 'max_uses',
		'_wpi_discount_is_recurring'   => 'is_recurring',
		'_wpi_discount_min_total'      => 'min_total',
		'_wpi_discount_max_total'      => 'max_total',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new discount in the database.
	 *
	 * @param WPInv_Discount $discount Discount object.
	 */
	public function create( &$discount ) {
		$discount->set_version( WPINV_VERSION );
		$discount->set_date_created( current_time('mysql') );

		// Create a new post.
		$id = wp_insert_post(
			apply_filters(
				'getpaid_new_discount_data',
				array(
					'post_date'     => $discount->get_date_created( 'edit' ),
					'post_type'     => 'wpi_discount',
					'post_status'   => $this->get_post_status( $discount ),
					'ping_status'   => 'closed',
					'post_author'   => $discount->get_author( 'edit' ),
					'post_title'    => $discount->get_name( 'edit' ),
					'post_excerpt'  => $discount->get_description( 'edit' ),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$discount->set_id( $id );
			$this->update_post_meta( $discount );
			$discount->save_meta_data();
			$discount->apply_changes();
			$this->clear_caches( $discount );
			do_action( 'getpaid_new_discount', $discount );
			return true;
		}

		if ( is_wp_error( $id ) ) {
			$discount->last_error = $id->get_error_message();
		}

		return false;
	}

	/**
	 * Method to read a discount from the database.
	 *
	 * @param WPInv_Discount $discount Discount object.
	 *
	 */
	public function read( &$discount ) {

		$discount->set_defaults();
		$discount_object = get_post( $discount->get_id() );

		if ( ! $discount->get_id() || ! $discount_object || $discount_object->post_type != 'wpi_discount' ) {
			$discount->last_error = __( 'Invalid discount.', 'invoicing' );
			$discount->set_id( 0 );
			return false;
		}

		$discount->set_props(
			array(
				'date_created'  => 0 < $discount_object->post_date ? $discount_object->post_date : null,
				'date_modified' => 0 < $discount_object->post_modified ? $discount_object->post_modified : null,
				'status'        => $discount_object->post_status,
				'name'          => $discount_object->post_title,
				'author'        => $discount_object->post_author,
				'description'   => $discount_object->post_excerpt,
			)
		);

		$this->read_object_data( $discount, $discount_object );
		$discount->read_meta_data();
		$discount->set_object_read( true );
		do_action( 'getpaid_read_discount', $discount );

	}

	/**
	 * Method to update a discount in the database.
	 *
	 * @param WPInv_Discount $discount Discount object.
	 */
	public function update( &$discount ) {
		$discount->save_meta_data();
		$discount->set_version( WPINV_VERSION );

		if ( null === $discount->get_date_created( 'edit' ) ) {
			$discount->set_date_created(  current_time('mysql') );
		}

		// Grab the current status so we can compare.
		$previous_status = get_post_status( $discount->get_id() );

		$changes = $discount->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'name', 'author', 'post_excerpt' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => $discount->get_date_created( 'edit' ),
				'post_status'       => $discount->get_status( 'edit' ),
				'post_title'        => $discount->get_name( 'edit' ),
				'post_author'       => $discount->get_author( 'edit' ),
				'post_modified'     => $discount->get_date_modified( 'edit' ),
				'post_excerpt'      => $discount->get_description( 'edit' ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $discount->get_id() ) );
				clean_post_cache( $discount->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $discount->get_id() ), $post_data ) );
			}
			$discount->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $discount );
		$discount->apply_changes();
		$this->clear_caches( $discount );

		// Fire a hook depending on the status - this should be considered a creation if it was previously draft status.
		$new_status = $discount->get_status( 'edit' );

		if ( $new_status !== $previous_status && in_array( $previous_status, array( 'new', 'auto-draft', 'draft' ), true ) ) {
			do_action( 'getpaid_new_discount', $discount );
		} else {
			do_action( 'getpaid_update_discount', $discount );
		}

	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

}
