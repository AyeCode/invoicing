<?php

/**
 * GetPaid_Subscription_Data_Store class file.
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscription Data Store: Stored in a custom table.
 *
 * @version  1.0.19
 */
class GetPaid_Subscription_Data_Store {

	/**
	 * A map of database fields to data types.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $database_fields_to_data_type = array(
		'id'                => '%d',
		'customer_id'       => '%d',
		'frequency'         => '%d',
		'period'            => '%s',
		'initial_amount'    => '%s',
		'recurring_amount'  => '%s',
		'bill_times'        => '%d',
		'transaction_id'    => '%s',
		'parent_payment_id' => '%d',
		'product_id'        => '%d',
		'created'           => '%s',
		'expiration'        => '%s',
		'trial_period'      => '%s',
		'status'            => '%s',
		'profile_id'        => '%s',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new subscription in the database.
	 *
	 * @param WPInv_Subscription $subscription Subscription object.
	 */
	public function create( &$subscription ) {
		global $wpdb;

		$values  = array();
		$formats = array();

		$fields = $this->database_fields_to_data_type;
		unset( $fields['id'] );

		foreach ( $fields as $key => $format ) {
			$method       = "get_$key";
			$values[$key] = $subscription->$method( 'edit' );
			$formats[]    = $format;
		}

		$result = $wpdb->insert( $wpdb->prefix . 'wpinv_subscriptions', $values, $formats );

		if ( $result ) {
			$subscription->set_id( $wpdb->insert_id );
			$subscription->apply_changes();
			$subscription->clear_cache();
			update_post_meta( $subscription->get_parent_invoice_id(), '_wpinv_subscription_id', $subscription->get_id() );
			do_action( 'getpaid_new_subscription', $subscription );
			return true;
		}

		return false;
	}

	/**
	 * Method to read a subscription from the database.
	 *
	 * @param WPInv_Subscription $subscription Subscription object.
	 *
	 */
	public function read( &$subscription ) {
		global $wpdb;

		$subscription->set_defaults();

		if ( ! $subscription->get_id() ) {
			$subscription->last_error = __( 'Invalid subscription ID.', 'invoicing' );
			$subscription->set_id( 0 );
			return false;
		}

		// Maybe retrieve from the cache.
		$raw_subscription = wp_cache_get( $subscription->get_id(), 'getpaid_subscriptions' );

		// If not found, retrieve from the db.
		if ( false === $raw_subscription ) {

			$raw_subscription = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}wpinv_subscriptions WHERE id = %d",
					$subscription->get_id()
				)
			);

			// Update the cache with our data
			wp_cache_set( $subscription->get_id(), $raw_subscription, 'getpaid_subscriptions' );

		}

		if ( ! $raw_subscription ) {
			$subscription->set_id( 0 );
			$subscription->last_error = __( 'Invalid subscription ID.', 'invoicing' );
			return false;
		}

		foreach ( array_keys( $this->database_fields_to_data_type ) as $key ) {
			$method     = "set_$key";
			$subscription->$method( $raw_subscription->$key );
		}

		$subscription->set_object_read( true );
		do_action( 'getpaid_read_subscription', $subscription );

	}

	/**
	 * Method to update a subscription in the database.
	 *
	 * @param WPInv_Subscription $subscription Subscription object.
	 */
	public function update( &$subscription ) {
		global $wpdb;

		$changes = $subscription->get_changes();
		$values  = array();
		$formats = array();

		foreach ( $this->database_fields_to_data_type as $key => $format ) {
			if ( array_key_exists( $key, $changes ) ) {
				$method       = "get_$key";
				$values[$key] = $subscription->$method( 'edit' );
				$formats[]    = $format;
			}
		}

		if ( empty( $values ) ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . 'wpinv_subscriptions',
			$values,
			array(
				'id' => $subscription->get_id(),
			),
			$formats,
			'%d'
		);

		// Apply the changes.
		$subscription->apply_changes();

		// Delete cache.
		$subscription->clear_cache();

		update_post_meta( $subscription->get_parent_invoice_id(), '_wpinv_subscr_profile_id', $subscription->get_profile_id() );
		update_post_meta( $subscription->get_parent_invoice_id(), '_wpinv_subscription_id', $subscription->get_id() );

		// Fire a hook.
		do_action( 'getpaid_update_subscription', $subscription );

	}

	/**
	 * Method to delete a subscription from the database.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function delete( &$subscription ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wpinv_subscriptions
				WHERE id = %d",
				$subscription->get_id()
			)
		);

		delete_post_meta( $subscription->get_parent_invoice_id(), '_wpinv_subscr_profile_id' );
		delete_post_meta( $subscription->get_parent_invoice_id(), '_wpinv_subscription_id' );

		// Delete cache.
		$subscription->clear_cache();

		// Fire a hook.
		do_action( 'getpaid_delete_subscription', $subscription );

		$subscription->set_id( 0 );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/
}
