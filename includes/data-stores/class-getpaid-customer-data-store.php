<?php

/**
 * GetPaid_Customer_Data_Store class file.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer Data Store: Stored in a custom table.
 *
 * @version  1.0.19
 */
class GetPaid_Customer_Data_Store {

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new customer in the database.
	 *
	 * @param GetPaid_Customer $customer customer object.
	 */
	public function create( &$customer ) {
		global $wpdb;

		$values  = array();
		$formats = array();

		$fields = self::get_database_fields();
		unset( $fields['id'] );

		foreach ( $fields as $key => $format ) {
			$values[ $key ] = $customer->get( $key, 'edit' );
			$formats[]      = $format;
		}

		$result = $wpdb->insert( $wpdb->prefix . 'getpaid_customers', $values, $formats );

		if ( $result ) {
			$customer->set_id( $wpdb->insert_id );
			$customer->apply_changes();
			$customer->clear_cache();
			do_action( 'getpaid_new_customer', $customer );
			return true;
		}

		return false;
	}

	/**
	 * Method to read a customer from the database.
	 *
	 * @param GetPaid_Customer $customer customer object.
	 *
	 */
	public function read( &$customer ) {
		global $wpdb;

		$customer->set_defaults();

		if ( ! $customer->get_id() ) {
			$customer->last_error = 'Invalid customer.';
			$customer->set_id( 0 );
			return false;
		}

		// Maybe retrieve from the cache.
		$raw_customer = wp_cache_get( $customer->get_id(), 'getpaid_customers' );

		// If not found, retrieve from the db.
		if ( false === $raw_customer ) {

			$raw_customer = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}getpaid_customers WHERE id = %d",
					$customer->get_id()
				)
			);

			// Update the cache with our data
			wp_cache_set( $customer->get_id(), $raw_customer, 'getpaid_customers' );

		}

		if ( ! $raw_customer ) {
			$raw_customer->last_error = 'Invalid customer.';
			return false;
		}

		// Loop through raw customer fields.
		foreach ( (array) $raw_customer as $key => $value ) {
			$customer->set( $key, $value );
		}

		$customer->set_object_read( true );
		do_action( 'getpaid_read_customer', $customer );

	}

	/**
	 * Method to update a customer in the database.
	 *
	 * @param GetPaid_Customer $customer Customer object.
	 */
	public function update( &$customer ) {
		global $wpdb;

		do_action( 'getpaid_before_update_customer', $customer, $customer->get_changes() );

		$changes = $customer->get_changes();
		$values  = array();
		$format  = array();

		foreach ( self::get_database_fields() as $key => $format ) {
			if ( array_key_exists( $key, $changes ) ) {
				$values[ $key ] = $customer->get( $key, 'edit' );
				$formats[]      = $format;
			}
		}

		if ( empty( $values ) ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . 'getpaid_customers',
			$values,
			array(
				'id' => $customer->get_id(),
			),
			$formats,
			'%d'
		);

		// Apply the changes.
		$customer->apply_changes();

		// Delete cache.
		$customer->clear_cache();

		// Fire a hook.
		do_action( 'getpaid_update_customer', $customer );

	}

	/**
	 * Method to delete a customer from the database.
	 *
	 * @param GetPaid_Customer $customer
	 */
	public function delete( &$customer ) {
		global $wpdb;

		do_action( 'getpaid_before_delete_customer', $customer );

		$wpdb->delete(
			$wpdb->prefix . 'getpaid_customers',
			array(
				'id' => $customer->get_id(),
			),
			'%d'
		);

		// Delete cache.
		$customer->clear_cache();

		// Fire a hook.
		do_action( 'getpaid_delete_customer', $customer );

		$customer->set_id( 0 );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/
	public static function get_database_fields() {

		$fields = array(
			'id'             => '%d',
			'user_id'        => '%d',
			'email'          => '%s',
			'email_cc'       => '%s',
			'status'         => '%s',
			'purchase_value' => '%f',
			'purchase_count' => '%d',
			'date_created'   => '%s',
			'date_modified'  => '%s',
			'uuid'           => '%s',
		);

		// Add address fields.
		foreach ( array_keys( getpaid_user_address_fields() ) as $field ) {

			// Skip id, user_id and email.
			if ( ! in_array( $field, array( 'id', 'user_id', 'email', 'purchase_value', 'purchase_count', 'date_created', 'date_modified', 'uuid' ), true ) ) {
				$fields[ $field ] = '%s';
			}
		}

		return $fields;
	}

}
