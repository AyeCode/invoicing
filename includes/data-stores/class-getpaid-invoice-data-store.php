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
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$invoice->set_id( $id );
			$invoice->save_special();
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
	 * Method to save special invoice fields in the database.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
	 */
	public function save_special( &$invoice ) {
		$invoice->set_version( WPINV_VERSION );
		$invoice->set_date_created( current_time('mysql') );


		if ( $id && ! is_wp_error( $id ) ) {
			$invoice->set_id( $id );
			$invoice->save_special();
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

		if ( ! $invoice->get_id() || ! $invoice_object || $invoice_object->post_type != 'wpi_invoice' ) {
			$invoice->last_error = __( 'Invalid invoice.', 'invoicing' );
			return false;
		}

		$invoice->set_props(
			array(
				'date_created'  => 0 < $discount_object->post_date_gmt ? $discount_object->post_date_gmt : null,
				'date_modified' => 0 < $discount_object->post_modified_gmt ? $discount_object->post_modified_gmt : null,
				'status'        => $discount_object->post_status,
				'author'        => $discount_object->post_author,
				'description'   => $discount_object->post_excerpt,
			)
		);

		$this->read_object_data( $discount, $discount_object );
		$discount->read_meta_data();
		$discount->set_object_read( true );

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
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/
	/**
     * Returns a list of all special fields.
	 * 
	 * @param WPInv_Invoice $invoice Invoice object.
     */
    public function get_special_fields( $invoice ) {

        return array (
            'post_id'        => $invoice->get_id(),
            'number'         => $invoice->get_number(),
            'key'            => $invoice->get_key(),
            'type'           => str_replace( 'wpi_', '', $invoice->get_post_type() ),
            'mode'           => $invoice->get_mode(),
            'user_ip'        => $invoice->get_user_ip(),
            'first_name'     => $invoice->get_first_name(),
            'last_name'      => $invoice->get_last_name(),
            'address'        => $invoice->get_address(),
            'city'           => $invoice->get_city(),
            'state'          => $invoice->get_state(),
            'country'        => $invoice->get_country(),
            'zip'            => $invoice->get_zip(),
            'adddress_confirmed' => (int) $this->get_adddress_confirmed(),
            'gateway'        => $invoice->get_gateway(),
            'transaction_id' => $invoice->get_transaction_id(),
            'currency'       => $invoice->get_currency(),
            'subtotal'       => $invoice->get_subtotal(),
            'tax'            => $invoice->get_tax(),
            'fees_total'     => $invoice->get_fees_total(),
            'total'          => $invoice->get_total(),
            'discount'       => $invoice->get_discount(),
            'discount_code'  => $invoice->get_discount_code(),
            'disable_taxes'  => (int) $invoice->get_disable_taxes(),
            'due_date'       => $invoice->get_due_date(),
            'completed_date' => $invoice->get_completed_date(),
            'company'        => $invoice->get_company(),
            'vat_number'     => $invoice->get_vat_number(),
            'vat_rate'       => $invoice->get_vat_rate(),
            'custom_meta'    => $invoice->get_payment_meta(),
        );

    }

}
