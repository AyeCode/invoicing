<?php
/**
 * Contains the customer class
 *
 * @since   1.0.15
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customer class.
 *
 * @since 1.0.15
 *
 */
class GetPaid_Customer extends GetPaid_Data {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
    protected $data_store_name = 'customer';

    /**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'customer';

	/**
	 * Get the customer if ID is passed, otherwise the customer is new and empty.
	 *
	 * @param int|string|GetPaid_Customer|object $customer customer id, object, or email.
	 */
	public function __construct( $customer = 0 ) {

        // Setup default customer data.
        $this->setup_default_data();

		if ( is_numeric( $customer ) ) {
			$this->set_id( $customer );
		} elseif ( $customer instanceof self ) {
			$this->set_id( $customer->get_id() );
		} elseif ( is_string( $customer ) && $customer_id = self::get_customer_id_by( $customer, 'email' ) ) {
			$this->set_id( $customer_id );
		} elseif ( ! empty( $customer->id ) ) {
			$this->set_id( $customer->id );
		}

        // Load the datastore.
		$this->data_store = GetPaid_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
        }

        $this->set_object_read( true );
	}

    /**
	 * Sets up default customer data.
	 */
	private function setup_default_data() {

        $this->data = array(
			'user_id'        => 0,
			'email'          => '',
			'email_cc'       => '',
			'status'         => 'active',
			'purchase_value' => 0,
			'purchase_count' => 0,
			'date_created'   => current_time( 'mysql' ),
			'date_modified'  => current_time( 'mysql' ),
			'uuid'           => wp_generate_uuid4(),
		);

        // Add address fields.
		foreach ( array_keys( getpaid_user_address_fields() ) as $field ) {

            if ( isset( $this->data[ $field ] ) ) {
                continue;
            }

            // Country.
            if ( 'country' === $field ) {
                $this->data[ $field ] = wpinv_get_default_country();
                continue;
            }

            // State.
            if ( 'state' === $field ) {
                $this->data[ $field ] = wpinv_get_default_state();
                continue;
            }

			$this->data[ $field ] = '';
		}

        $this->default_data = $this->data;
	}

	/**
	 * Given a customer email or user id, it returns a customer id.
	 *
	 * @static
	 * @param string $value
	 * @since 1.0.15
	 * @return int
	 */
	public static function get_customer_id_by( $value, $by = 'email' ) {
		global $wpdb;

        // Prepare value.
        if ( 'email' === $by ) {
            $value = sanitize_email( $value );
        } elseif ( 'user_id' === $by ) {
            $value = absint( $value );
        } else {
            return 0;
        }

        if ( empty( $value ) ) {
            return 0;
        }

		// Maybe retrieve from the cache.
        $cache_key   = 'getpaid_customer_ids_by_' . $by;
		$customer_id = wp_cache_get( $value, $cache_key );
		if ( false !== $customer_id ) {
			return $customer_id;
		}

        if ( 'email' === $by ) {
            $customer_id = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}getpaid_customers WHERE email=%s LIMIT 1", $value )
            );
        } elseif ( 'user_id' === $by ) {
            $customer_id = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}getpaid_customers WHERE user_id=%d LIMIT 1", $value )
            );
        }

		// Update the cache with our data
		wp_cache_set( $value, $customer_id, $cache_key );

		return $customer_id;

	}

	/**
     * Clears the customer's cache.
     */
    public function clear_cache() {
        wp_cache_delete( $this->get( 'email' ), 'getpaid_customer_ids_by_email' );
        wp_cache_delete( $this->get( 'user_id' ), 'getpaid_customer_ids_by_user_id' );
		wp_cache_delete( $this->get_id(), 'getpaid_customers' );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete discounts from the database.
	|
    */

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

    /**
     * Margic method for retrieving a property.
     *
     * @param  string $key The key to fetch.
     * @param  string $context View or edit context.
     */
    public function get( $key, $context = 'view' ) {

        // Maybe strip _wpinv_ prefix from key.
        $key = str_replace( '_wpinv_', '', $key );

        // Check if we have a helper method for that.
        if ( method_exists( $this, 'get_' . $key ) ) {
            return call_user_func( array( $this, 'get_' . $key ), $context );
        }

		return $this->get_prop( $key, $context );

    }

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting customer data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

    /**
     * Margic method for setting a property.
     *
     * @param string $key The key to fetch.
     * @param mixed $value The new value.
     */
    public function set( $key, $value ) {

        // Check if we have a helper method for that.
        if ( method_exists( $this, 'set_' . $key ) ) {
            return call_user_func( array( $this, 'set_' . $key ), $value );
        }

		return $this->set_prop( $key, $value );

    }

	/**
	 * Sets customer status.
	 *
	 * @since 1.0.0
	 * @param  string $status New status.
	 */
	public function set_status( $status ) {

		if ( in_array( $status, array( 'active', 'inactive', 'blocked' ), true ) ) {
			return $this->set_prop( 'status', $status );
		}

		$this->set_prop( 'status', 'inactive' );
	}

	/**
	 * Sets the purchase value.
	 *
	 * @since 1.0.0
	 * @param float $purchase_value.
	 */
	public function set_purchase_value( $purchase_value ) {
		$this->set_prop( 'purchase_value', (float) $purchase_value );
	}

    /**
	 * Sets the purchase count.
	 *
	 * @since 1.0.0
	 * @param int $purchase_count.
	 */
	public function set_purchase_count( $purchase_count ) {
		$this->set_prop( 'purchase_count', absint( $purchase_count ) );
	}

    /**
	 * Sets the user id.
	 *
	 * @since 1.0.0
	 * @param int $user_id.
	 */
	public function set_user_id( $user_id ) {
		$this->set_prop( 'user_id', absint( $user_id ) );
	}

    /**
	 * Sets the email.
	 *
	 * @since 1.0.0
	 * @param string $email.
	 */
	public function set_email( $email ) {
        $email = is_string( $email ) ? sanitize_email( $email ) : '';
		$this->set_prop( 'email', $email );
	}

    /**
	 * Sets the email cc.
	 *
	 * @since 1.0.0
	 * @param string $email_cc.
	 */
	public function set_email_cc( $email_cc ) {
        $email_cc = implode( ', ', wp_parse_list( $email_cc ) );
		$this->set_prop( 'email_cc', $email_cc );
	}

    /**
	 * Sets the created date.
	 *
	 * @since 1.0.0
	 * @param  string $date_created date created.
	 */
	public function set_date_created( $date_created ) {

		$date = strtotime( $date_created );

        if ( $date && $date_created !== '0000-00-00 00:00:00'  && $date_created !== '0000-00-00 00:00' ) {
            $this->set_prop( 'date_created', gmdate( 'Y-m-d H:i:s', $date ) );
            return;
		}

		$this->set_prop( 'date_created', null );
	}

    /**
	 * Sets the created date.
	 *
	 * @since 1.0.0
	 * @param  string $date_modified date created.
	 */
	public function set_date_modified( $date_modified ) {

		$date = strtotime( $date_modified );

        if ( $date && $date_modified !== '0000-00-00 00:00:00'  && $date_modified !== '0000-00-00 00:00' ) {
            $this->set_prop( 'date_modified', gmdate( 'Y-m-d H:i:s', $date ) );
            return;
		}

		$this->set_prop( 'date_modified', null );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional methods
	|--------------------------------------------------------------------------
	|
	| This method help you manipulate a customer.
	|
	*/

	/**
	 * Saves the customer.
	 *
	 * @since 1.0.0
	 */
	public function save() {

        $maybe_set = array(
            'uuid'         => wp_generate_uuid4(),
            'date_created' => current_time( 'mysql' ),
        );

        foreach ( $maybe_set as $key => $value ) {
            $current_value = $this->get( $key );

            if ( empty( $current_value ) ) {
                $this->set( $key, $value );
            }
        }

        $this->set( 'date_modified', current_time( 'mysql' ) );

		return parent::save();
	}

    /**
	 * Helper method to clone a customer from a user ID.
	 *
	 * @since 1.0.0
	 * @param int $user_id.
	 */
	public function clone_user( $user_id ) {
        $user = get_userdata( $user_id );

        if ( empty( $user ) ) {
            return;
        }

		$this->set_user_id( $user->ID );
        $this->set_email( $user->user_email );
        $this->set_purchase_value( getpaid_get_user_total_spend( $user->ID ) );
        $this->set_purchase_count( getpaid_count_user_invoices( $user->ID ) );
        $this->set( 'first_name', $user->first_name );
        $this->set( 'last_name', $user->last_name );
		$this->set_date_created( $user->user_registered );

        // Fetch extra data from WC or old GetPaid.
        $prefixes = array(
            '_wpinv_',
            'billing_',
            '',
        );

        foreach ( array_keys( getpaid_user_address_fields() ) as $field ) {

            foreach ( $prefixes as $prefix ) {

                // Meta table.
                $value = get_user_meta( $user_id, $prefix . $field, true );

                // UWP table.
                $value = ( empty( $value ) && function_exists( 'uwp_get_usermeta' ) ) ? uwp_get_usermeta( $user_id, $prefix . $field ) : $value;

                if ( ! empty( $value ) ) {
                    $this->set( $field, $value );
                    continue;
                }
            }
		}
	}

    /**
	 * Helper method to migrate an existing user ID to the new customers table.
	 *
	 * @since 1.0.0
	 * @param int $user_id.
	 */
	public function migrate_from_user( $user_id ) {
        $this->clone_user( $user_id );
        do_action( 'getpaid_customer_migrated_from_user', $this, $user_id );
        $this->save();
	}
}
