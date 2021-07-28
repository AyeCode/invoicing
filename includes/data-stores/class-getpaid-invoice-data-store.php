<?php

/**
 * GetPaid_Invoice_Data_Store class file.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invoice Data Store: Stored in CPT.
 *
 * @version  1.0.19
 */
class GetPaid_Invoice_Data_Store extends GetPaid_Data_Store_WP {

	/**
	 * Data stored in meta keys, but not considered "meta" for a discount.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_wpinv_subscr_profile_id',
		'_wpinv_subscription_id',
		'_wpinv_taxes',
		'_wpinv_fees',
		'_wpinv_discounts',
		'_wpinv_submission_id',
		'_wpinv_payment_form',
		'_wpinv_is_viewed',
		'_wpinv_phone',
		'_wpinv_company_id',
		'wpinv_email_cc',
		'wpinv_template',
		'wpinv_created_via'
	);

	/**
	 * A map of meta keys to data props.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $meta_key_to_props = array(
		'_wpinv_subscr_profile_id' => 'remote_subscription_id',
		'_wpinv_subscription_id'   => 'subscription_id',
		'_wpinv_taxes'             => 'taxes',
		'_wpinv_fees'              => 'fees',
		'_wpinv_discounts'         => 'discounts',
		'_wpinv_submission_id'     => 'submission_id',
		'_wpinv_payment_form'      => 'payment_form',
		'_wpinv_is_viewed'         => 'is_viewed',
		'wpinv_email_cc'           => 'email_cc',
		'wpinv_template'           => 'template',
		'wpinv_created_via'        => 'created_via',
		'_wpinv_phone'             => 'phone',
		'_wpinv_company_id'        => 'company_id',
	);

	/**
	 * A map of database fields to data props.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $database_fields_to_props = array(
		'post_id'            => 'id',
		'number'             => 'number',
		'currency'           => 'currency',
		'key'                => 'key',
		'type'               => 'type',
		'mode'               => 'mode',
		'user_ip'            => 'user_ip',
		'first_name'         => 'first_name',
		'last_name'          => 'last_name',
		'address'            => 'address',
		'city'               => 'city',
		'state'              => 'state',
		'country'            => 'country',
		'zip'                => 'zip',
		'zip'                => 'zip',
		'adddress_confirmed' => 'address_confirmed',
		'gateway'            => 'gateway',
		'transaction_id'     => 'transaction_id',
		'currency'           => 'currency',
		'subtotal'           => 'subtotal',
		'tax'                => 'total_tax',
		'fees_total'         => 'total_fees',
		'discount'           => 'total_discount',
		'total'              => 'total',
		'discount_code'      => 'discount_code',
		'disable_taxes'      => 'disable_taxes',
		'due_date'           => 'due_date',
		'completed_date'     => 'completed_date',
		'company'            => 'company',
		'vat_number'         => 'vat_number',
		'vat_rate'           => 'vat_rate',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new invoice in the database.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
	 */
	public function create( &$invoice ) {
		$invoice->set_version( WPINV_VERSION );
		$invoice->set_date_created( current_time('mysql') );

		// Create a new post.
		$id = wp_insert_post(
			apply_filters(
				'getpaid_new_invoice_data',
				array(
					'post_date'     => $invoice->get_date_created( 'edit' ),
					'post_type'     => $invoice->get_post_type( 'edit' ),
					'post_status'   => $this->get_post_status( $invoice ),
					'ping_status'   => 'closed',
					'post_author'   => $invoice->get_user_id( 'edit' ),
					'post_title'    => $invoice->get_title( 'edit' ),
					'post_excerpt'  => $invoice->get_description( 'edit' ),
					'post_parent'   => $invoice->get_parent_id( 'edit' ),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {

			// Update the new id and regenerate a title.
			$invoice->set_id( $id );

			$invoice->maybe_set_number();

			wp_update_post(
				array(
					'ID'         => $invoice->get_id(),
					'post_title' => $invoice->get_number( 'edit' ),
					'post_name'  => $invoice->get_path( 'edit' )
				)
			);

			// Save special fields and items.
			$this->save_special_fields( $invoice );
			$this->save_items( $invoice );

			// Update meta data.
			$this->update_post_meta( $invoice );
			$invoice->save_meta_data();

			// Apply changes.
			$invoice->apply_changes();
			$this->clear_caches( $invoice );

			// Fires after a new invoice is created.
			do_action( 'getpaid_new_invoice', $invoice );
			return true;
		}

		if ( is_wp_error( $id ) ) {
			$invoice->last_error = $id->get_error_message();
		}

		return false;
	}

	/**
	 * Method to read an invoice from the database.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
	 *
	 */
	public function read( &$invoice ) {

		$invoice->set_defaults();
		$invoice_object = get_post( $invoice->get_id() );

		if ( ! $invoice->get_id() || ! $invoice_object || ! getpaid_is_invoice_post_type( $invoice_object->post_type ) ) {
			$invoice->last_error = __( 'Invalid invoice.', 'invoicing' );
			$invoice->set_id( 0 );
			return false;
		}

		$invoice->set_props(
			array(
				'date_created'  => 0 < $invoice_object->post_date ? $invoice_object->post_date : null,
				'date_modified' => 0 < $invoice_object->post_modified ? $invoice_object->post_modified : null,
				'status'        => $invoice_object->post_status,
				'author'        => $invoice_object->post_author,
				'description'   => $invoice_object->post_excerpt,
				'parent_id'     => $invoice_object->post_parent,
				'name'          => $invoice_object->post_title,
				'path'          => $invoice_object->post_name,
				'post_type'     => $invoice_object->post_type,
			)
		);

		$invoice->set_type( $invoice_object->post_type );

		$this->read_object_data( $invoice, $invoice_object );
		$this->add_special_fields( $invoice );
		$this->add_items( $invoice );
		$invoice->read_meta_data();
		$invoice->set_object_read( true );
		do_action( 'getpaid_read_invoice', $invoice );

	}

	/**
	 * Method to update an invoice in the database.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
	 */
	public function update( &$invoice ) {
		$invoice->save_meta_data();
		$invoice->set_version( WPINV_VERSION );

		if ( null === $invoice->get_date_created( 'edit' ) ) {
			$invoice->set_date_created(  current_time('mysql') );
		}

		// Ensure both the key and number are set.
		$invoice->get_path();

		// Grab the current status so we can compare.
		$previous_status = get_post_status( $invoice->get_id() );

		$changes = $invoice->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'name', 'author', 'description', 'parent_id', 'post_excerpt', 'path' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => $invoice->get_date_created( 'edit' ),
				'post_date_gmt'     => $invoice->get_date_created_gmt( 'edit' ),
				'post_status'       => $invoice->get_status( 'edit' ),
				'post_title'        => $invoice->get_name( 'edit' ),
				'post_author'       => $invoice->get_user_id( 'edit' ),
				'post_modified'     => $invoice->get_date_modified( 'edit' ),
				'post_excerpt'      => $invoice->get_description( 'edit' ),
				'post_parent'       => $invoice->get_parent_id( 'edit' ),
				'post_name'         => $invoice->get_path( 'edit' ),
				'post_type'         => $invoice->get_post_type( 'edit' ),
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
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $invoice->get_id() ) );
				clean_post_cache( $invoice->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $invoice->get_id() ), $post_data ) );
			}
			$invoice->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}

		// Update meta data.
		$this->update_post_meta( $invoice );

		// Save special fields and items.
		$this->save_special_fields( $invoice );
		$this->save_items( $invoice );

		// Apply the changes.
		$invoice->apply_changes();

		// Clear caches.
		$this->clear_caches( $invoice );

		// Fire a hook depending on the status - this should be considered a creation if it was previously draft status.
		$new_status = $invoice->get_status( 'edit' );

		if ( $new_status !== $previous_status && in_array( $previous_status, array( 'new', 'auto-draft', 'draft' ), true ) ) {
			do_action( 'getpaid_new_invoice', $invoice );
		} else {
			do_action( 'getpaid_update_invoice', $invoice );
		}

	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
     * Retrieves special fields and adds to the invoice.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function add_special_fields( &$invoice ) {
		global $wpdb;

		// Maybe retrieve from the cache.
		$data   = wp_cache_get( $invoice->get_id(), 'getpaid_invoice_special_fields' );

		// If not found, retrieve from the db.
		if ( false === $data ) {
			$table =  $wpdb->prefix . 'getpaid_invoices';

			$data  = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM $table WHERE `post_id`=%d LIMIT 1", $invoice->get_id() ),
				ARRAY_A
			);

			// Update the cache with our data
			wp_cache_set( $invoice->get_id(), $data, 'getpaid_invoice_special_fields' );

		}

		// Abort if the data does not exist.
		if ( empty( $data ) ) {
			$invoice->set_object_read( true );
			$invoice->set_props( wpinv_get_user_address( $invoice->get_user_id() ) );
			return;
		}

		$props = array();

		foreach ( $this->database_fields_to_props as $db_field => $prop ) {
			
			if ( $db_field == 'post_id' ) {
				continue;
			}

			$props[ $prop ] = $data[ $db_field ];
		}

		$invoice->set_props( $props );

	}

	/**
	 * Gets a list of special fields that need updated based on change state
	 * or if they are present in the database or not.
	 *
	 * @param  WPInv_Invoice $invoice       The Invoice object.
	 * @return array                        A mapping of field keys => prop names, filtered by ones that should be updated.
	 */
	protected function get_special_fields_to_update( $invoice ) {
		$fields_to_update = array();
		$changed_props   = $invoice->get_changes();

		// Props should be updated if they are a part of the $changed array or don't exist yet.
		foreach ( $this->database_fields_to_props as $database_field => $prop ) {
			if ( array_key_exists( $prop, $changed_props ) ) {
				$fields_to_update[ $database_field ] = $prop;
			}
		}

		return $fields_to_update;
	}

	/**
	 * Helper method that updates all the database fields for an invoice based on it's settings in the WPInv_Invoice class.
	 *
	 * @param WPInv_Invoice $invoice WPInv_Invoice object.
	 * @since 1.0.19
	 */
	protected function update_special_fields( &$invoice ) {
		global $wpdb;

		$updated_props    = array();
		$fields_to_update = $this->get_special_fields_to_update( $invoice );

		foreach ( $fields_to_update as $database_field => $prop ) {
			$value = $invoice->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;
			$value = is_bool( $value ) ? ( int ) $value : $value;
			$updated_props[ $database_field ] = maybe_serialize( $value );
		}

		if ( ! empty( $updated_props ) ) {

			$table = $wpdb->prefix . 'getpaid_invoices';
			$wpdb->update( $table, $updated_props, array( 'post_id' => $invoice->get_id() ) );
			wp_cache_delete( $invoice->get_id(), 'getpaid_invoice_special_fields' );
			do_action( "getpaid_invoice_update_database_fields", $invoice, $updated_props );

		}

	}

	/**
	 * Helper method that inserts special fields to the database.
	 *
	 * @param WPInv_Invoice $invoice WPInv_Invoice object.
	 * @since 1.0.19
	 */
	protected function insert_special_fields( &$invoice ) {
		global $wpdb;

		$updated_props   = array();

		foreach ( $this->database_fields_to_props as $database_field => $prop ) {
			$value = $invoice->{"get_$prop"}( 'edit' );
			$value = is_string( $value ) ? wp_slash( $value ) : $value;
			$value = is_bool( $value ) ? ( int ) $value : $value;
			$updated_props[ $database_field ] = maybe_serialize( $value );
		}

		$table = $wpdb->prefix . 'getpaid_invoices';
		$wpdb->insert( $table, $updated_props );
		wp_cache_delete( $invoice->get_id(), 'getpaid_invoice_special_fields' );
		do_action( "getpaid_invoice_insert_database_fields", $invoice, $updated_props );

	}

	/**
     * Saves all special fields.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function save_special_fields( & $invoice ) {
		global $wpdb;

		// The invoices table.
		$table = $wpdb->prefix . 'getpaid_invoices';
		$id    = (int) $invoice->get_id();
		$invoice->maybe_set_key();

		if ( $wpdb->get_var( "SELECT `post_id` FROM $table WHERE `post_id`= $id" ) ) {

			$this->update_special_fields( $invoice );

		} else {

			$this->insert_special_fields( $invoice );

		}

	}

	/**
     * Set's up cart details.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function add_items( &$invoice ) {
		global $wpdb;

		// Maybe retrieve from the cache.
		$items = wp_cache_get( $invoice->get_id(), 'getpaid_invoice_cart_details' );

		// If not found, retrieve from the db.
		if ( false === $items ) {
			$table =  $wpdb->prefix . 'getpaid_invoice_items';

			$items = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM $table WHERE `post_id`=%d", $invoice->get_id() )
			);

			// Update the cache with our data
			wp_cache_set( $invoice->get_id(), $items, 'getpaid_invoice_cart_details' );

		}

		// Abort if no items found.
        if ( empty( $items ) ) {
            return;
		}

		$_items = array();
		foreach ( $items as $item_data ) {
			$item = new GetPaid_Form_Item( $item_data->item_id );

			// Set item data.
			$item->item_tax      = wpinv_sanitize_amount( $item_data->tax );
			$item->item_discount = wpinv_sanitize_amount( $item_data->discount );
			$item->set_name( $item_data->item_name );
			$item->set_description( $item_data->item_description );
			$item->set_price( $item_data->item_price );
			$item->set_quantity( $item_data->quantity );
			$item->set_item_meta( $item_data->meta );
			$_items[] = $item;
		}

		$invoice->set_items( $_items );
	}

	/**
     * Saves cart details.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function save_items( $invoice ) {

		// Delete previously existing items.
		$this->delete_items( $invoice );

		$table   =  $GLOBALS['wpdb']->prefix . 'getpaid_invoice_items';

		foreach ( $invoice->get_cart_details() as $item_data ) {
			$item_data = array_map( 'maybe_serialize', $item_data );
			$GLOBALS['wpdb']->insert( $table, $item_data );
		}

		wp_cache_delete( $invoice->get_id(), 'getpaid_invoice_cart_details' );
		do_action( "getpaid_invoice_save_items", $invoice );

	}

	/**
     * Deletes an invoice's cart details from the database.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function delete_items( $invoice ) {
		$table =  $GLOBALS['wpdb']->prefix . 'getpaid_invoice_items';
		return $GLOBALS['wpdb']->delete( $table, array( 'post_id' => $invoice->get_id() ) );
	}

	/**
     * Deletes an invoice's special fields from the database.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function delete_special_fields( $invoice ) {
		$table =  $GLOBALS['wpdb']->prefix . 'getpaid_invoices';
		return $GLOBALS['wpdb']->delete( $table, array( 'post_id' => $invoice->get_id() ) );
	}
	
	/**
	 * Get the status to save to the post object.
	 *
	 *
	 * @since 1.0.19
	 * @param  WPInv_Invoice $object GetPaid_Data object.
	 * @return string
	 */
	protected function get_post_status( $object ) {
		$object_status = $object->get_status( 'edit' );

		if ( ! $object_status ) {
			$object_status = $object->get_default_status();
		}

		return $object_status;
	}

}
