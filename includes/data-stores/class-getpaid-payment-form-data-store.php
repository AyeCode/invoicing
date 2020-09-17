<?php
/**
 * GetPaid_Payment_Form_Data_Store class file.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment Form Data Store: Stored in CPT.
 *
 * @version  1.0.19
 */
class GetPaid_Payment_Form_Data_Store extends GetPaid_Data_Store_WP {

	/**
	 * Data stored in meta keys, but not considered "meta" for a form.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'wpinv_form_elements',
		'wpinv_form_items',
		'wpinv_form_earned',
		'wpinv_form_refunded',
		'wpinv_form_cancelled',
		'wpinv_form_failed'
	);

	/**
	 * A map of meta keys to data props.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $meta_key_to_props = array(
		'wpinv_form_elements'  => 'elements',
		'wpinv_form_items'     => 'items',
		'wpinv_form_earned'    => 'earned',
		'wpinv_form_refunded'  => 'refunded',
		'wpinv_form_cancelled' => 'cancelled',
		'wpinv_form_failed'    => 'failed',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new form in the database.
	 *
	 * @param GetPaid_Payment_Form $form Form object.
	 */
	public function create( &$form ) {
		$form->set_version( WPINV_VERSION );
		$form->set_date_created( current_time('mysql') );

		// Create a new post.
		$id = wp_insert_post(
			apply_filters(
				'getpaid_new_payment_form_data',
				array(
					'post_date'     => $form->get_date_created( 'edit' ),
					'post_type'     => 'wpi_payment_form',
					'post_status'   => $this->get_post_status( $form ),
					'ping_status'   => 'closed',
					'post_author'   => $form->get_author( 'edit' ),
					'post_title'    => $form->get_name( 'edit' ),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$form->set_id( $id );
			$this->update_post_meta( $form );
			$form->save_meta_data();
			$form->apply_changes();
			$this->clear_caches( $form );
			do_action( 'getpaid_create_payment_form', $form );
			return true;
		}

		if ( is_wp_error( $id ) ) {
			$form->last_error = $id->get_error_message();
		}

		return false;
	}

	/**
	 * Method to read a form from the database.
	 *
	 * @param GetPaid_Payment_Form $form Form object.
	 *
	 */
	public function read( &$form ) {

		$form->set_defaults();
		$form_object = get_post( $form->get_id() );

		if ( ! $form->get_id() || ! $form_object || $form_object->post_type != 'wpi_payment_form' ) {
			$form->last_error = __( 'Invalid form.', 'invoicing' );
			$form->set_id( 0 );
			return false;
		}

		$form->set_props(
			array(
				'date_created'  => 0 < $form_object->post_date ? $form_object->post_date : null,
				'date_modified' => 0 < $form_object->post_modified ? $form_object->post_modified : null,
				'status'        => $form_object->post_status,
				'name'          => $form_object->post_title,
				'author'        => $form_object->post_author,
			)
		);

		$this->read_object_data( $form, $form_object );
		$form->read_meta_data();
		$form->set_object_read( true );
		do_action( 'getpaid_read_payment_form', $form );

	}

	/**
	 * Method to update a form in the database.
	 *
	 * @param GetPaid_Payment_Form $form Form object.
	 */
	public function update( &$form ) {
		$form->save_meta_data();
		$form->set_version( WPINV_VERSION );

		if ( null === $form->get_date_created( 'edit' ) ) {
			$form->set_date_created(  current_time('mysql') );
		}

		// Grab the current status so we can compare.
		$previous_status = get_post_status( $form->get_id() );

		$changes = $form->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'name', 'author' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => $form->get_date_created( 'edit' ),
				'post_status'       => $form->get_status( 'edit' ),
				'post_title'        => $form->get_name( 'edit' ),
				'post_author'       => $form->get_author( 'edit' ),
				'post_modified'     => $form->get_date_modified( 'edit' ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $form->get_id() ) );
				clean_post_cache( $form->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $form->get_id() ), $post_data ) );
			}
			$form->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $form );
		$form->apply_changes();
		$this->clear_caches( $form );

		// Fire a hook depending on the status - this should be considered a creation if it was previously draft status.
		$new_status = $form->get_status( 'edit' );

		if ( $new_status !== $previous_status && in_array( $previous_status, array( 'new', 'auto-draft', 'draft' ), true ) ) {
			do_action( 'getpaid_new_payment_form', $form );
		} else {
			do_action( 'getpaid_update_payment_form', $form );
		}

	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

}
