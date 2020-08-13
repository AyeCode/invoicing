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
		'_wpinv_taxes',
		'_wpinv_fees',
		'_wpinv_discounts',
		'_wpinv_submission_id',
		'_wpinv_payment_form',
	);

	/**
	 * A map of meta keys to data props.
	 *
	 * @since 1.0.19
	 *
	 * @var array
	 */
	protected $meta_key_to_props = array(
		'_wpinv_subscr_profile_id' => 'subscription_id',
		'_wpinv_taxes'             => 'taxes',
		'_wpinv_fees'              => 'fees',
		'_wpinv_discounts'         => 'discounts',
		'_wpinv_submission_id'     => 'submission_id',
		'_wpinv_payment_form'      => 'payment_form',
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
					'post_title'    => $invoice->get_number( 'edit' ),
					'post_excerpt'  => $invoice->get_description( 'edit' ),
					'post_parent'   => $invoice->get_parent_id( 'edit' ),
					'post_name'     => $invoice->get_path( 'edit' ),
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$invoice->set_id( $id );
			$this->save_special_fields( $invoice );
			$this->update_post_meta( $invoice );
			$invoice->save_meta_data();
			$invoice->apply_changes();
			$this->clear_caches( $invoice );
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

		if ( ! $invoice->get_id() || ! $invoice_object || getpaid_is_invoice_post_type( $invoice_object->post_type ) ) {
			$invoice->last_error = __( 'Invalid invoice.', 'invoicing' );
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

		$this->read_object_data( $invoice, $invoice_object );
		$this->add_special_fields( $invoice );
		$invoice->read_meta_data();
		$invoice->set_object_read( true );

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

		$changes = $invoice->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'name', 'author', 'description', 'parent_id', 'post_excerpt', 'path' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => $invoice->get_date_created( 'edit' ),
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
		$this->update_post_meta( $invoice );
		$this->save_special_fields( $invoice );
		$invoice->apply_changes();
		$this->clear_caches( $invoice );
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

		$table =  $wpdb->prefix . 'getpaid_invoices';
        $data  = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table WHERE `post_id`=%d LIMIT 1", $this->ID ),
			ARRAY_A
		);

		// Abort if the data does not exist.
		if ( empty( $data ) ) {
			return;
		}

		$invoice->set_props( $data );

	}

	/**
	 * Gets a list of special fields that need updated based on change state
	 * or if they are present in the database or not.
	 *
	 * @param  WPInv_Invoice $invoice       The Invoice object.
	 * @param  array   $meta_key_to_props   A mapping of prop => value.
	 * @return array                        A mapping of field keys => prop names, filtered by ones that should be updated.
	 */
	protected function get_special_fields_to_update( $invoice, $special_fields ) {
		$props_to_update = array();
		$changed_props   = $invoice->get_changes();

		// Props should be updated if they are a part of the $changed array or don't exist yet.
		foreach ( $special_fields as $prop => $value ) {
			if ( array_key_exists( $prop, $changed_props ) ) {
				$props_to_update[ $prop ] = $value;
			}
		}

		return $props_to_update;
	}

	/**
     * Saves all special fields.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function save_special_fields( $invoice ) {
		global $wpdb;

		// Fields to update.
		$fields = array (
            'post_id'        => $invoice->get_id(),
            'number'         => $invoice->get_number( 'edit' ),
            'key'            => $invoice->get_key( 'edit' ),
            'type'           => $invoice->get_type( 'edit' ),
            'mode'           => $invoice->get_mode( 'edit' ),
            'user_ip'        => $invoice->get_user_ip( 'edit' ),
            'first_name'     => $invoice->get_first_name( 'edit' ),
            'last_name'      => $invoice->get_last_name( 'edit' ),
            'address'        => $invoice->get_address( 'edit' ),
            'city'           => $invoice->get_city( 'edit' ),
            'state'          => $invoice->get_state( 'edit' ),
            'country'        => $invoice->get_country( 'edit' ),
            'zip'            => $invoice->get_zip( 'edit' ),
            'address_confirmed' => (int) $invoice->get_address_confirmed( 'edit' ),
            'gateway'        => $invoice->get_gateway( 'edit' ),
            'transaction_id' => $invoice->get_transaction_id( 'edit' ),
            'currency'       => $invoice->get_currency( 'edit' ),
            'subtotal'       => $invoice->get_subtotal( 'edit' ),
            'total_tax'      => $invoice->get_total_tax( 'edit' ),
            'total_fees'     => $invoice->get_total_fees( 'edit' ),
            'total_discount' => $invoice->get_total_discount( 'edit' ),
            'discount_code'  => $invoice->get_discount_code( 'edit' ),
            'disable_taxes'  => (int) $invoice->get_disable_taxes( 'edit' ),
            'due_date'       => $invoice->get_due_date( 'edit' ),
            'completed_date' => $invoice->get_completed_date( 'edit' ),
            'company'        => $invoice->get_company( 'edit' ),
            'vat_number'     => $invoice->get_vat_number( 'edit' ),
            'vat_rate'       => $invoice->get_vat_rate( 'edit' ),
		);

		// The invoices table.
		$table = $wpdb->prefix . 'getpaid_invoices';
		$id    = (int) $invoice->get_id();
		if ( $wpdb->get_var( "SELECT `post_id` FROM $table WHERE `post_id`= $id" ) ) {

			$to_update = $this->get_special_fields_to_update( $invoice, $fields );

			if ( empty( $to_update ) ) {
				return;
			}

			$changes = array(
				'tax'                => 'total_tax',
				'fees_total'         => 'total_fees',
				'discount'           => 'total_discount',
				'adddress_confirmed' => 'address_confirmed',
			);

			foreach ( $changes as $to => $from ) {
				if ( isset( $changes[ $from ] ) ) {
					$changes[ $to ] = $changes[ $from ];
					unset( $changes[ $from ] );
				}
			}

			$changes['total'] = $invoice->get_total( 'edit' );
            $wpdb->update( $table, $fields, array( 'post_id' => $id ) );

        } else {

			$fields['tax'] = $fields['total_tax'];
			unset( $fields['total_tax'] );

			$fields['fees_total'] = $fields['total_fees'];
			unset( $fields['total_fees'] );

			$fields['discount'] = $fields['total_discount'];
			unset( $fields['total_discount'] );

			$fields['adddress_confirmed'] = $fields['address_confirmed'];
			unset( $fields['address_confirmed'] );
			
			$fields['total']   = $invoice->get_total( 'edit' );
			$fields['post_id'] = $id;
            $wpdb->insert( $table, $fields );

		}

	}

	/**
     * Set's up cart details.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function add_items( &$invoice ) {
		global $wpdb;

		$table =  $wpdb->prefix . 'getpaid_invoice_items';
        $items = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM $table WHERE `post_id`=%d", $this->ID )
        );

        if ( empty( $items ) ) {
            return;
		}

		foreach ( $items as $item_data ) {
			$item = new GetPaid_Form_Item( $item_data->item_id );

			// Set item data.
			$item->item_tax      = wpinv_sanitize_amount( $item_data->tax );
			$item->item_discount = wpinv_sanitize_amount( $item_data->tax );
			$item->set_name( $item_data->item_name );
			$item->set_description( $item_data->item_description );
			$item->set_price( $item_data->item_price );
			$item->set_quantity( $item_data->quantity );

			$invoice->add_item( $item );
		}

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
        $to_save = $invoice->get_cart_details();

		foreach ( $to_save as $item_data ) {
			$GLOBALS['wpdb']->insert( $table, $item_data );
		}

	}

	/**
     * Deletes an invoice's cart details from the database.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function delete_items( $invoice ) {
		$table =  $GLOBALS['wpdb']->prefix . 'getpaid_invoice_items';
		return $GLOBALS['wpdb']->delete( $table, array( 'post_id' => $invoice->ID ) );
    }

}
