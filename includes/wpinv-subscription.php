<?php
/**
 * Contains the subscription class.
 *
 * @since 1.0.19
 * @package Invoicing
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Subscription Class
 *
 * @since  1.0.0
 */
class WPInv_Subscription extends GetPaid_Data {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'subscription';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'subscription';

	/**
	 * Item Data array. This is the core item data exposed in APIs.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	protected $data = array(
		'customer_id'       => 0,
		'frequency'         => 1,
		'period'            => 'D',
		'initial_amount'    => null,
		'recurring_amount'  => null,
		'bill_times'        => 0,
		'transaction_id'    => '',
		'parent_payment_id' => null,
		'product_id'        => 0,
		'created'           => '0000-00-00 00:00:00',
		'expiration'        => '0000-00-00 00:00:00',
		'trial_period'      => '',
		'status'            => 'pending',
		'profile_id'        => '',
		'gateway'           => '',
		'customer'          => '',
	);

	/**
	 * Stores the status transition information.
	 *
	 * @since 1.0.19
	 * @var bool
	 */
	protected $status_transition = false;

	/**
	 * Get the subscription if ID is passed, otherwise the subscription is new and empty.
	 *
	 * @param  int|string|object|WPInv_Subscription $subscription Subscription id, profile_id, or object to read.
	 * @param  bool $deprecated
	 */
	function __construct( $subscription = 0, $deprecated = false ) {

		parent::__construct( $subscription );

		if ( ! $deprecated && ! empty( $subscription ) && is_numeric( $subscription ) ) {
			$this->set_id( $subscription );
		} elseif ( $subscription instanceof self ) {
			$this->set_id( $subscription->get_id() );
		} elseif ( $deprecated && $subscription_id = self::get_subscription_id_by_field( $subscription, 'profile_id' ) ) {
			$this->set_id( $subscription_id );
		} elseif ( ! empty( $subscription->id ) ) {
			$this->set_id( $subscription->id );
		} else {
			$this->set_object_read( true );
		}

		// Load the datastore.
		$this->data_store = GetPaid_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}

	}

	/**
	 * Given an invoice id, profile id, transaction id, it returns the subscription's id.
	 *
	 *
	 * @static
	 * @param string $value
	 * @param string $field Either invoice_id, transaction_id or profile_id.
	 * @since 1.0.19
	 * @return int
	 */
	public static function get_subscription_id_by_field( $value, $field = 'profile_id' ) {
        global $wpdb;

		// Trim the value.
		$value = trim( $value );

		if ( empty( $value ) ) {
			return 0;
		}

		if ( 'invoice_id' == $field ) {
			$field = 'parent_payment_id';
		}

        // Valid fields.
        $fields = array(
			'parent_payment_id',
			'transaction_id',
			'profile_id'
		);

		// Ensure a field has been passed.
		if ( empty( $field ) || ! in_array( $field, $fields ) ) {
			return 0;
		}

		// Maybe retrieve from the cache.
		$subscription_id   = wp_cache_get( $value, "getpaid_subscription_{$field}s_to_subscription_ids" );
		if ( ! empty( $subscription_id ) ) {
			return $subscription_id;
		}

        // Fetch from the db.
        $table            = $wpdb->prefix . 'wpinv_subscriptions';
        $subscription_id  = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT `id` FROM $table WHERE `$field`=%s LIMIT 1", $value )
        );

		if ( empty( $subscription_id ) ) {
			return 0;
		}

		// Update the cache with our data.
		wp_cache_set( $value, $subscription_id, "getpaid_subscription_{$field}s_to_subscription_ids" );

		return $subscription_id;
	}

	/**
     * Clears the subscription's cache.
     */
    public function clear_cache() {
		wp_cache_delete( $this->get_parent_payment_id(), 'getpaid_subscription_parent_payment_ids_to_subscription_ids' );
		wp_cache_delete( $this->get_transaction_id(), 'getpaid_subscription_transaction_ids_to_subscription_ids' );
		wp_cache_delete( $this->get_profile_id(), 'getpaid_subscription_profile_ids_to_subscription_ids' );
		wp_cache_delete( $this->get_id(), 'getpaid_subscriptions' );
	}

	/**
     * Checks if a subscription key is set.
     */
    public function _isset( $key ) {
        return isset( $this->data[$key] ) || method_exists( $this, "get_$key" );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete subscriptions from the database.
	|
    */

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get customer id.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_customer_id( $context = 'view' ) {
		return (int) $this->get_prop( 'customer_id', $context );
	}

	/**
	 * Get customer information.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return WP_User|false WP_User object on success, false on failure.
	 */
	public function get_customer( $context = 'view' ) {
		return get_userdata( $this->get_customer_id( $context ) );
	}

	/**
	 * Get parent invoice id.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_parent_invoice_id( $context = 'view' ) {
		return (int) $this->get_prop( 'parent_payment_id', $context );
	}

	/**
	 * Alias for self::get_parent_invoice_id().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
    public function get_parent_payment_id( $context = 'view' ) {
        return $this->get_parent_invoice_id( $context );
	}

	/**
     * Alias for self::get_parent_invoice_id().
     *
     * @since  1.0.0
     * @return int
     */
    public function get_original_payment_id( $context = 'view' ) {
        return $this->get_parent_invoice_id( $context );
    }

	/**
	 * Get parent invoice.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return WPInv_Invoice
	 */
	public function get_parent_invoice( $context = 'view' ) {
		return new WPInv_Invoice( $this->get_parent_invoice_id( $context ) );
	}

	/**
	 * Alias for self::get_parent_invoice().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return WPInv_Invoice
	 */
    public function get_parent_payment( $context = 'view' ) {
        return $this->get_parent_invoice( $context );
	}

	/**
	 * Get subscription's product id.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_product_id( $context = 'view' ) {
		return (int) $this->get_prop( 'product_id', $context );
	}

	/**
	 * Get the subscription product.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return WPInv_Item
	 */
	public function get_product( $context = 'view' ) {
		return new WPInv_Item( $this->get_product_id( $context ) );
	}

	/**
	 * Get parent invoice's gateway.
	 *
	 * Here for backwards compatibility.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_gateway( $context = 'view' ) {
		return $this->get_parent_invoice( $context )->get_gateway();
	}

	/**
	 * Get the period of a renewal.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_period( $context = 'view' ) {
		return $this->get_prop( 'period', $context );
	}

	/**
	 * Get number of periods each renewal is valid for.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_frequency( $context = 'view' ) {
		return (int) $this->get_prop( 'frequency', $context );
	}

	/**
	 * Get the initial amount for the subscription.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_initial_amount( $context = 'view' ) {
		return (float) wpinv_sanitize_amount( $this->get_prop( 'initial_amount', $context ) );
	}

	/**
	 * Get the recurring amount for the subscription.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return float
	 */
	public function get_recurring_amount( $context = 'view' ) {
		return (float) wpinv_sanitize_amount( $this->get_prop( 'recurring_amount', $context ) );
	}

	/**
	 * Get number of times that this subscription can be renewed.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return int
	 */
	public function get_bill_times( $context = 'view' ) {
		return (int) $this->get_prop( 'bill_times', $context );
	}

	/**
	 * Get transaction id of this subscription's parent invoice.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_transaction_id( $context = 'view' ) {
		return $this->get_prop( 'transaction_id', $context );
	}

	/**
	 * Get the date that the subscription was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_created( $context = 'view' ) {
		return $this->get_prop( 'created', $context );
	}

	/**
	 * Alias for self::get_created().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_created( $context );
	}

	/**
	 * Retrieves the creation date in a timestamp
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_time_created() {
		$created = $this->get_date_created();
		return empty( $created ) ? current_time( 'timestamp' ) : strtotime( $created, current_time( 'timestamp' ) );
	}

	/**
	 * Get GMT date when the subscription was created.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_date_created_gmt( $context = 'view' ) {
        $date = $this->get_date_created( $context );

        if ( $date ) {
            $date = get_gmt_from_date( $date );
        }
		return $date;
	}

	/**
	 * Get the date that the subscription will renew.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_next_renewal_date( $context = 'view' ) {
		return $this->get_prop( 'expiration', $context );
	}

	/**
	 * Alias for self::get_next_renewal_date().
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_expiration( $context = 'view' ) {
		return $this->get_next_renewal_date( $context );
	}

	/**
	 * Retrieves the expiration date in a timestamp
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_expiration_time() {
		$expiration = $this->get_expiration();

		if ( empty( $expiration ) || '0000-00-00 00:00:00' == $expiration ) {
			return current_time( 'timestamp' );
		}

		$expiration = strtotime( $expiration, current_time( 'timestamp' ) );
		return $expiration < current_time( 'timestamp' ) ? current_time( 'timestamp' ) : $expiration;
	}

	/**
	 * Get GMT date when the subscription will renew.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_next_renewal_date_gmt( $context = 'view' ) {
        $date = $this->get_next_renewal_date( $context );

        if ( $date ) {
            $date = get_gmt_from_date( $date );
        }
		return $date;
	}

	/**
	 * Get the subscription's trial period.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_trial_period( $context = 'view' ) {
		return $this->get_prop( 'trial_period', $context );
	}

	/**
	 * Get the subscription's status.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get the subscription's profile id.
	 *
	 * @since 1.0.19
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_profile_id( $context = 'view' ) {
		return $this->get_prop( 'profile_id', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set customer id.
	 *
	 * @since 1.0.19
	 * @param  int $value The customer's id.
	 */
	public function set_customer_id( $value ) {
		$this->set_prop( 'customer_id', (int) $value );
	}

	/**
	 * Set parent invoice id.
	 *
	 * @since 1.0.19
	 * @param  int $value The parent invoice id.
	 */
	public function set_parent_invoice_id( $value ) {
		$this->set_prop( 'parent_payment_id', (int) $value );
	}

	/**
	 * Alias for self::set_parent_invoice_id().
	 *
	 * @since 1.0.19
	 * @param  int $value The parent invoice id.
	 */
    public function set_parent_payment_id( $value ) {
        $this->set_parent_invoice_id( $value );
	}

	/**
     * Alias for self::set_parent_invoice_id().
     *
     * @since 1.0.19
	 * @param  int $value The parent invoice id.
     */
    public function set_original_payment_id( $value ) {
        $this->set_parent_invoice_id( $value );
	}

	/**
	 * Set subscription's product id.
	 *
	 * @since 1.0.19
	 * @param  int $value The subscription product id.
	 */
	public function set_product_id( $value ) {
		$this->set_prop( 'product_id', (int) $value );
	}

	/**
	 * Set the period of a renewal.
	 *
	 * @since 1.0.19
	 * @param  string $value The renewal period.
	 */
	public function set_period( $value ) {
		$this->set_prop( 'period', $value );
	}

	/**
	 * Set number of periods each renewal is valid for.
	 *
	 * @since 1.0.19
	 * @param  int $value The subscription frequency.
	 */
	public function set_frequency( $value ) {
		$value = empty( $value ) ? 1 : (int) $value;
		$this->set_prop( 'frequency', absint( $value ) );
	}

	/**
	 * Set the initial amount for the subscription.
	 *
	 * @since 1.0.19
	 * @param  float $value The initial subcription amount.
	 */
	public function set_initial_amount( $value ) {
		$this->set_prop( 'initial_amount', wpinv_sanitize_amount( $value ) );
	}

	/**
	 * Set the recurring amount for the subscription.
	 *
	 * @since 1.0.19
	 * @param  float $value The recurring subcription amount.
	 */
	public function set_recurring_amount( $value ) {
		$this->set_prop( 'recurring_amount', wpinv_sanitize_amount( $value ) );
	}

	/**
	 * Set number of times that this subscription can be renewed.
	 *
	 * @since 1.0.19
	 * @param  int $value Bill times.
	 */
	public function set_bill_times( $value ) {
		$this->set_prop( 'bill_times', (int) $value );
	}

	/**
	 * Get transaction id of this subscription's parent invoice.
	 *
	 * @since 1.0.19
	 * @param string $value Bill times.
	 */
	public function set_transaction_id( $value ) {
		$this->set_prop( 'transaction_id', sanitize_text_field( $value ) );
	}

	/**
	 * Set date when this subscription started.
	 *
	 * @since 1.0.19
	 * @param string $value strtotime compliant date.
	 */
	public function set_created( $value ) {
        $date = strtotime( $value );

        if ( $date && $value !== '0000-00-00 00:00:00' ) {
            $this->set_prop( 'created', date( 'Y-m-d H:i:s', $date ) );
            return;
        }

		$this->set_prop( 'created', '' );

	}

	/**
	 * Alias for self::set_created().
	 *
	 * @since 1.0.19
	 * @param string $value strtotime compliant date.
	 */
	public function set_date_created( $value ) {
		$this->set_created( $value );
    }

	/**
	 * Set the date that the subscription will renew.
	 *
	 * @since 1.0.19
	 * @param string $value strtotime compliant date.
	 */
	public function set_next_renewal_date( $value ) {
		$date = strtotime( $value );

        if ( $date && $value !== '0000-00-00 00:00:00' ) {
            $this->set_prop( 'expiration', date( 'Y-m-d H:i:s', $date ) );
            return;
		}

		$this->set_prop( 'expiration', '' );

	}

	/**
	 * Alias for self::set_next_renewal_date().
	 *
	 * @since 1.0.19
	 * @param string $value strtotime compliant date.
	 */
	public function set_expiration( $value ) {
		$this->set_next_renewal_date( $value );
    }

	/**
	 * Set the subscription's trial period.
	 *
	 * @since 1.0.19
	 * @param string $value trial period e.g 1 year.
	 */
	public function set_trial_period( $value ) {
		$this->set_prop( 'trial_period', $value );
	}

	/**
	 * Set the subscription's status.
	 *
	 * @since 1.0.19
	 * @param string $new_status    New subscription status.
	 */
	public function set_status( $new_status ) {

		// Abort if this is not a valid status;
		if ( ! array_key_exists( $new_status, getpaid_get_subscription_statuses() ) ) {
			return;
		}


		$old_status = ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $this->get_status();
		if ( true === $this->object_read && $old_status !== $new_status ) {
			$this->status_transition = array(
				'from'   => $old_status,
				'to'     => $new_status,
			);
		}

		$this->set_prop( 'status', $new_status );
	}

	/**
	 * Set the subscription's (remote) profile id.
	 *
	 * @since 1.0.19
	 * @param  string $value the remote profile id.
	 */
	public function set_profile_id( $value ) {
		$this->set_prop( 'profile_id', sanitize_text_field( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Boolean methods
	|--------------------------------------------------------------------------
	|
	| Return true or false.
	|
	*/

	/**
     * Checks if the subscription has a given status.
	 *
	 * @param string|array String or array of strings to check for.
	 * @return bool
     */
    public function has_status( $status ) {
        return in_array( $this->get_status(), wpinv_clean( wpinv_parse_list( $status ) ) );
	}

	/**
     * Checks if the subscription has a trial period.
	 *
	 * @return bool
     */
    public function has_trial_period() {
		$period = $this->get_trial_period();
        return ! empty( $period );
	}

	/**
	 * Is the subscription active?
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->has_status( 'active trialling' ) && ! $this->is_expired();
	}

	/**
	 * Is the subscription expired?
	 *
	 * @return bool
	 */
	public function is_expired() {
		return $this->has_status( 'expired' ) || ( $this->has_status( 'active cancelled trialling' ) && $this->get_expiration_time() < current_time( 'mysql' ) );
	}

	/**
	 * Is this the last renewals?
	 *
	 * @return bool
	 */
	public function is_last_renewal() {
		$max_bills = $this->get_bill_times();
		return ! empty( $max_bills ) && $max_bills <= $this->get_times_billed();
	}

	/*
	|--------------------------------------------------------------------------
	| Additional methods
	|--------------------------------------------------------------------------
	|
	| Calculating subscription details.
	|
	*/

	/**
	 * Backwards compatibilty.
	 */
	public function create( $data = array() ) {

		// Set the properties.
		if ( is_array( $data ) ) {
			$this->set_props( $data );
		}

		// Save the item.
		return $this->save();

	}

	/**
	 * Backwards compatibilty.
	 */
	public function update( $args = array() ) {
		return $this->create( $args );
	}

    /**
     * Retrieve renewal payments for a subscription
     *
     * @since  1.0.0
     * @return WP_Post[]
     */
    public function get_child_payments( $hide_pending = true ) {

		$statuses = array( 'publish', 'wpi-processing', 'wpi-renewal' );

		if ( ! $hide_pending ) {
			$statuses = array_keys( wpinv_get_invoice_statuses() );
		}

        return get_posts(
			array(
            	'post_parent'    => $this->get_parent_payment_id(),
            	'numberposts'    => -1,
            	'post_status'    => $statuses,
            	'orderby'        => 'ID',
            	'order'          => 'ASC',
            	'post_type'      => 'wpi_invoice'
			)
		);
    }

    /**
     * Counts the number of invoices generated for the subscription.
     *
     * @since  1.0.0
     * @return int
     */
    public function get_total_payments() {
		return getpaid_count_subscription_invoices( $this->get_parent_invoice_id(), $this->get_id() );
    }

    /**
     * Counts the number of payments for the subscription.
     *
     * @since  1.0.2
     * @return int
     */
    public function get_times_billed() {
        $times_billed = $this->get_total_payments();

        if ( (float) $this->get_initial_amount() == 0 && $times_billed > 0 ) {
            $times_billed--;
        }

        return (int) $times_billed;
    }

    /**
     * Records a new payment on the subscription
     *
     * @since  2.4
     * @param  array $args Array of values for the payment, including amount and transaction ID
	 * @param  WPInv_Invoice $invoice If adding an existing invoice.
     * @return bool
     */
    public function add_payment( $args = array(), $invoice = false ) {

		// Process each payment once.
        if ( ! empty( $args['transaction_id'] ) && $this->payment_exists( $args['transaction_id'] ) ) {
            return false;
        }

		// Are we creating a new invoice?
		if ( empty( $invoice ) ) {
			$invoice = $this->create_payment();

			if ( empty( $invoice ) ) {
				return false;
			}

		}

		$invoice->set_status( 'wpi-renewal' );

		// Maybe set a transaction id.
		if ( ! empty( $args['transaction_id'] ) ) {
			$invoice->set_transaction_id( $args['transaction_id'] );
		}

		// Set the completed date.
		$invoice->set_completed_date( current_time( 'mysql' ) );

		// And the gateway.
		if ( ! empty( $args['gateway'] ) ) {
			$invoice->set_gateway( $args['gateway'] );
		}

		$invoice->save();

		if ( ! $invoice->exists() ) {
			return false;
		}

		do_action( 'getpaid_after_create_subscription_renewal_invoice', $invoice, $this );
		do_action( 'wpinv_recurring_add_subscription_payment', $invoice, $this );
        do_action( 'wpinv_recurring_record_payment', $invoice->get_id(), $this->get_parent_invoice_id(), $invoice->get_recurring_total(), $invoice->get_transaction_id() );

        update_post_meta( $invoice->get_id(), '_wpinv_subscription_id', $this->id );

        return $invoice->get_id();
	}

	/**
     * Creates a new invoice and returns it.
     *
     * @since  1.0.19
     * @return WPInv_Invoice|bool
     */
    public function create_payment() {

		$parent_invoice = $this->get_parent_payment();

		if ( ! $parent_invoice->exists() ) {
			return false;
		}

		// Duplicate the parent invoice.
		$invoice = getpaid_duplicate_invoice( $parent_invoice );
		$invoice->set_parent_id( $parent_invoice->get_id() );
		$invoice->set_subscription_id( $this->get_id() );
		$invoice->set_remote_subscription_id( $this->get_profile_id() );

		// Set invoice items.
		$subscription_group = getpaid_get_invoice_subscription_group( $parent_invoice->get_id(), $this->get_id() );
		$allowed_items      = empty( $subscription_group ) ? array( $this->get_product_id() ) : array_keys( $subscription_group['items'] );
		$invoice_items      = array();

		foreach ( $invoice->get_items() as $item ) {
			if ( in_array( $item->get_id(), $allowed_items ) ) {
				$invoice_items[] = $item;
			}
		}

		$invoice->set_items( $invoice_items );

		if ( ! empty( $subscription_group['fees'] ) ) {
			$invoice->set_fees( $subscription_group['fees'] );
		}

		// Maybe recalculate discount (Pre-GetPaid Fix).
		$discount = new WPInv_Discount( $invoice->get_discount_code() );
		if ( $discount->exists() && $discount->is_recurring() && 0 == $invoice->get_total_discount() ) {
			$invoice->add_discount( getpaid_calculate_invoice_discount( $invoice, $discount ) );
		}

		$invoice->recalculate_total();
		$invoice->set_status( 'wpi-pending' );
		$invoice->save();

		return $invoice->exists() ? $invoice : false;
    }

	/**
	 * Renews or completes a subscription
	 *
	 * @since  1.0.0
	 * @return int The subscription's id
	 */
	public function renew() {

		// Complete subscription if applicable
		if ( $this->is_last_renewal() ) {
			return $this->complete();
		}

		// Calculate new expiration
		$frequency      = $this->get_frequency();
		$period         = $this->get_period();
		$new_expiration = strtotime( "+ $frequency $period", $this->get_expiration_time() );

		$this->set_expiration( date( 'Y-m-d H:i:s',$new_expiration ) );
		$this->set_status( 'active' );
		$this->save();

		do_action( 'getpaid_subscription_renewed', $this );

		return $this->get_id();
	}

	/**
	 * Marks a subscription as completed
	 *
	 * Subscription is completed when the number of payments matches the billing_times field
	 *
	 * @since  1.0.0
	 * @return int|bool Subscription id or false if the subscription is cancelled.
	 */
	public function complete() {

		// Only mark a subscription as complete if it's not already cancelled.
		if ( $this->has_status( 'cancelled' ) ) {
			return false;
		}

		$this->set_status( 'completed' );
		return $this->save();

	}

	/**
	 * Marks a subscription as expired
	 *
	 * @since  1.0.0
	 * @param  bool $check_expiration
	 * @return int|bool Subscription id or false if $check_expiration is true and expiration date is in the future.
	 */
	public function expire( $check_expiration = false ) {

		if ( $check_expiration && $this->get_expiration_time() > current_time( 'timestamp' ) ) {
			// Do not mark as expired since real expiration date is in the future
			return false;
		}

		$this->set_status( 'expired' );
		return $this->save();

	}

	/**
	 * Marks a subscription as failing
	 *
	 * @since  2.4.2
	 * @return int Subscription id.
	 */
	public function failing() {
		$this->set_status( 'failing' );
		return $this->save();
	}

    /**
     * Marks a subscription as cancelled
     *
     * @since  1.0.0
     * @return int Subscription id.
     */
    public function cancel() {
		$this->set_status( 'cancelled' );
		return $this->save();
    }

	/**
	 * Determines if a subscription can be cancelled both locally and with a payment processor.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function can_cancel() {
		return apply_filters( 'wpinv_subscription_can_cancel', $this->has_status( $this->get_cancellable_statuses() ), $this );
	}

    /**
     * Returns an array of subscription statuses that can be cancelled
     *
     * @access      public
     * @since       1.0.0
     * @return      array
     */
    public function get_cancellable_statuses() {
        return apply_filters( 'wpinv_recurring_cancellable_statuses', array( 'active', 'trialling', 'failing' ) );
    }

	/**
	 * Retrieves the URL to cancel subscription
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_cancel_url() {
		$url = getpaid_get_authenticated_action_url( 'subscription_cancel', $this->get_view_url() );
		return apply_filters( 'wpinv_subscription_cancel_url', $url, $this );
	}

	/**
	 * Retrieves the URL to view a subscription
	 *
	 * @since  1.0.19
	 * @return string
	 */
	public function get_view_url() {

		$url = getpaid_get_tab_url( 'gp-subscriptions', get_permalink( (int) wpinv_get_option( 'invoice_subscription_page' ) ) );
		$url = add_query_arg( 'subscription', $this->get_id(), $url );

		return apply_filters( 'getpaid_get_subscription_view_url', $url, $this );
	}

	/**
	 * Determines if subscription can be manually renewed
	 *
	 * This method is filtered by payment gateways in order to return true on subscriptions
	 * that can be renewed manually
	 *
	 * @since  2.5
	 * @return bool
	 */
	public function can_renew() {
		return apply_filters( 'wpinv_subscription_can_renew', true, $this );
	}

	/**
	 * Retrieves the URL to renew a subscription
	 *
	 * @since  2.5
	 * @return string
	 */
	public function get_renew_url() {
		$url = wp_nonce_url( add_query_arg( array( 'getpaid-action' => 'renew_subscription', 'sub_id' => $this->get_id ) ), 'getpaid-nonce' );
		return apply_filters( 'wpinv_subscription_renew_url', $url, $this );
	}

	/**
	 * Determines if subscription can have their payment method updated
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function can_update() {
		return apply_filters( 'wpinv_subscription_can_update', false, $this );
	}

	/**
	 * Retrieves the URL to update subscription
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_update_url() {
		$url = add_query_arg( array( 'action' => 'update', 'subscription_id' => $this->get_id() ) );
		return apply_filters( 'wpinv_subscription_update_url', $url, $this );
	}

	/**
	 * Retrieves the subscription status label
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_status_label() {
		return getpaid_get_subscription_status_label( $this->get_status() );
	}

	/**
	 * Retrieves the subscription status class
	 *
	 * @since  1.0.19
	 * @return string
	 */
	public function get_status_class() {
		$statuses = getpaid_get_subscription_status_classes();
		return isset( $statuses[ $this->get_status() ] ) ? $statuses[ $this->get_status() ] : 'badge-dark';
	}

    /**
     * Retrieves the subscription status label
     *
     * @since  1.0.0
     * @return string
     */
    public function get_status_label_html() {

		$status_label = sanitize_text_field( $this->get_status_label() );
		$class        = esc_attr( $this->get_status_class() );
		$status       = sanitize_html_class( $this->get_status() );

		return "<span class='bsui'><span class='badge $class $status'>$status_label</span></span>";
    }

    /**
     * Determines if a payment exists with the specified transaction ID
     *
     * @since  2.4
     * @param  string $txn_id The transaction ID from the merchant processor
     * @return bool
     */
    public function payment_exists( $txn_id = '' ) {
		$invoice_id = WPInv_Invoice::get_invoice_id_by_field( $txn_id, 'transaction_id' );
        return ! empty( $invoice_id );
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			try {

				// Fire a hook for the status change.
				do_action( 'wpinv_subscription_' . $status_transition['to'], $this->get_id(), $this, $status_transition );
				do_action( 'getpaid_subscription_' . $status_transition['to'], $this, $status_transition );

				if ( ! empty( $status_transition['from'] ) ) {

					/* translators: 1: old subscription status 2: new subscription status */
					$transition_note = sprintf( __( 'Subscription status changed from %1$s to %2$s.', 'invoicing' ), getpaid_get_subscription_status_label( $status_transition['from'] ), getpaid_get_subscription_status_label( $status_transition['to'] ) );

					// Note the transition occurred.
					$this->get_parent_payment()->add_note( $transition_note, false, false, true );

					// Fire another hook.
					do_action( 'getpaid_subscription_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_id(), $this );
					do_action( 'getpaid_subscription_status_changed', $this, $status_transition['from'], $status_transition['to'] );

				} else {
					/* translators: %s: new invoice status */
					$transition_note = sprintf( __( 'Subscription status set to %s.', 'invoicing' ), getpaid_get_subscription_status_label( $status_transition['to'] ) );

					// Note the transition occurred.
					$this->get_parent_payment()->add_note( $transition_note, false, false, true );

				}
			} catch ( Exception $e ) {
				$this->get_parent_payment()->add_note( __( 'Error during subscription status transition.', 'invoicing' ) . ' ' . $e->getMessage() );
			}
		}

	}

	/**
	 * Save data to the database.
	 *
	 * @since 1.0.19
	 * @return int subscription ID
	 */
	public function save() {
		parent::save();
		$this->status_transition();
		return $this->get_id();
	}

	/**
	 * Activates a subscription.
	 *
	 * @since 1.0.19
	 * @return int subscription ID
	 */
	public function activate() {
		$status = 'trialling' == $this->get_status() ? 'trialling' : 'active';
		$this->set_status( $status );
		return $this->save();
	}

}
