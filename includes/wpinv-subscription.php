<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The Subscription Class
 *
 * @since  1.0.0
 */
class WPInv_Subscription {

	private $subs_db;

	public $id                = 0;
	public $customer_id       = 0;
	public $period            = '';
	public $initial_amount    = '';
	public $recurring_amount  = '';
	public $bill_times        = 0;
	public $transaction_id    = '';
	public $parent_payment_id = 0;
	public $product_id        = 0;
	public $created           = '0000-00-00 00:00:00';
	public $expiration        = '0000-00-00 00:00:00';
	public $trial_period      = '';
	public $status            = 'pending';
	public $profile_id        = '';
	public $gateway           = '';
	public $customer;

	/**
	 * Get us started
	 *
	 * @since  1.0.0
	 * @return void
	 */
	function __construct( $_id_or_object = 0, $_by_profile_id = false ) {

		$this->subs_db = new WPInv_Subscriptions_DB;

		if( $_by_profile_id ) {

			$_sub = $this->subs_db->get_by( 'profile_id', $_id_or_object );

			if( empty( $_sub ) ) {
				return false;
			}

			$_id_or_object = $_sub;

		}

		return $this->setup_subscription( $_id_or_object );
	}

	/**
	 * Setup the subscription object
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function setup_subscription( $id_or_object = 0 ) {

		if( empty( $id_or_object ) ) {
			return false;
		}

		if( is_numeric( $id_or_object ) ) {

			$sub = $this->subs_db->get( $id_or_object );

		} elseif( is_object( $id_or_object ) ) {

			$sub = $id_or_object;

		}

		if( empty( $sub ) ) {
			return false;
		}

		foreach( $sub as $key => $value ) {
			$this->$key = $value;
		}

		$this->customer = get_userdata( $this->customer_id );
		$this->gateway  = wpinv_get_payment_gateway( $this->parent_payment_id );

		do_action( 'wpinv_recurring_setup_subscription', $this );

		return $this;
	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @since 1.0.0
	 */
	public function __get( $key ) {

		if( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( array( $this, 'get_' . $key ) );

		} else {

			return new WP_Error( 'wpinv-subscription-invalid-property', sprintf( __( 'Can\'t get property %s', 'invoicing' ), $key ) );

		}

	}

	/**
	 * Creates a subscription
	 *
	 * @since  1.0.0
	 * @param  array  $data Array of attributes for a subscription
	 * @return mixed  false if data isn't passed and class not instantiated for creation
	 */
	public function create( $data = array() ) {

		if ( $this->id != 0 ) {
			return false;
		}

		$defaults = array(
			'customer_id'       => 0,
			'frequency'         => '',
			'period'            => '',
			'initial_amount'    => '',
			'recurring_amount'  => '',
			'bill_times'        => 0,
			'parent_payment_id' => 0,
			'product_id'        => 0,
			'created'           => '',
			'expiration'        => '',
			'status'            => '',
			'profile_id'        => '',
		);

		$args = wp_parse_args( $data, $defaults );

		if( $args['expiration'] && strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $args['expiration'], current_time( 'timestamp' ) ) ) {

			if( 'active' == $args['status'] || 'trialling' == $args['status'] ) {

				// Force an active subscription to expired if expiration date is in the past
				$args['status'] = 'expired';

			}
		}

		do_action( 'wpinv_subscription_pre_create', $args );

		$id = $this->subs_db->insert( $args, 'subscription' );

		do_action( 'wpinv_subscription_post_create', $id, $args );

		return $this->setup_subscription( $id );

	}

	/**
	 * Updates a subscription
	 *
	 * @since  1.0.0
	 * @param  array $args Array of fields to update
	 * @return bool
	 */
	public function update( $args = array() ) {

		$ret = $this->subs_db->update( $this->id, $args );

		do_action( 'wpinv_recurring_update_subscription', $this->id, $args, $this );

		return $ret;

	}

	/**
	 * Delete the subscription
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function delete() {
		return $this->subs_db->delete( $this->id );
	}

    /**
     * Retrieves the parent payment ID
     *
     * @since  1.0.0
     * @return int
     */
    public function get_original_payment_id() {
        return $this->parent_payment_id;
    }

    /**
     * Retrieve renewal payments for a subscription
     *
     * @since  1.0.0
     * @return array
     */
    public function get_child_payments() {
        $payments = get_posts( array(
            'post_parent'    => (int) $this->parent_payment_id,
            'posts_per_page' => '999',
            'post_status'    => array( 'publish', 'wpi-processing', 'wpi-renewal' ),
            'orderby'           => 'ID',
            'order'             => 'DESC',
            'post_type'      => 'wpi_invoice'
        ) );

        return $payments;
    }

    /**
     * Counts the number of payments made to the subscription
     *
     * @since  2.4
     * @return int
     */
    public function get_total_payments() {
        $child_payments = $this->get_child_payments();
        $total_payments = !empty( $child_payments ) ? count( $child_payments ) : 0;

        if ( 'pending' != $this->status ) {
                $total_payments++;
        }

        return $total_payments;
    }

    /**
     * Returns the number of times the subscription has been billed
     *
     * @since  1.0.2
     * @return int
     */
    public function get_times_billed() {
        $times_billed = (int)$this->get_total_payments();

        if ( ! empty( $this->trial_period ) && $times_billed > 0 ) {
            $times_billed--;
        }

        return $times_billed;
    }

    /**
     * Records a new payment on the subscription
     *
     * @since  2.4
     * @param  array $args Array of values for the payment, including amount and transaction ID
     * @return bool
     */
    public function add_payment( $args = array() ) {
        if ( ! $this->parent_payment_id ) {
            return false;
        }

        $args = wp_parse_args( $args, array(
            'amount'         => '',
            'transaction_id' => '',
            'gateway'        => ''
        ) );
        
        if ( empty( $args['transaction_id'] ) || $this->payment_exists( $args['transaction_id'] ) ) {
            return false;
        }
        
        $parent_invoice = wpinv_get_invoice( $this->parent_payment_id );
        if ( empty( $parent_invoice->ID ) ) {
            return false;
        }

        $invoice = new WPInv_Invoice();
        $invoice->set( 'post_type', 'wpi_invoice' );
        $invoice->set( 'parent_invoice', $this->parent_payment_id );
        $invoice->set( 'currency', $parent_invoice->get_currency() );
        $invoice->set( 'transaction_id', $args['transaction_id'] );
        $invoice->set( 'key', $parent_invoice->generate_key() );
        $invoice->set( 'ip', $parent_invoice->ip );
        $invoice->set( 'user_id', $parent_invoice->get_user_id() );
        $invoice->set( 'first_name', $parent_invoice->get_first_name() );
        $invoice->set( 'last_name', $parent_invoice->get_last_name() );
        $invoice->set( 'phone', $parent_invoice->phone );
        $invoice->set( 'address', $parent_invoice->address );
        $invoice->set( 'city', $parent_invoice->city );
        $invoice->set( 'country', $parent_invoice->country );
        $invoice->set( 'state', $parent_invoice->state );
        $invoice->set( 'zip', $parent_invoice->zip );
        $invoice->set( 'company', $parent_invoice->company );
        $invoice->set( 'vat_number', $parent_invoice->vat_number );
        $invoice->set( 'vat_rate', $parent_invoice->vat_rate );
        $invoice->set( 'adddress_confirmed', $parent_invoice->adddress_confirmed );

        if ( empty( $args['gateway'] ) ) {
            $invoice->set( 'gateway', $parent_invoice->get_gateway() );
        } else {
            $invoice->set( 'gateway', $args['gateway'] );
        }
        
        $recurring_details = $parent_invoice->get_recurring_details();

        // increase the earnings for each item in the subscription
        $items = $recurring_details['cart_details'];
        
        if ( $items ) {        
            $add_items      = array();
            $cart_details   = array();
            
            foreach ( $items as $item ) {
                $add_item             = array();
                $add_item['id']       = $item['id'];
                $add_item['quantity'] = $item['quantity'];
                
                $add_items[]    = $add_item;
                $cart_details[] = $item;
                break;
            }
            
            $invoice->set( 'items', $add_items );
            $invoice->cart_details = $cart_details;
        }
        
        $total = $args['amount'];
        
        $subtotal           = $recurring_details['subtotal'];
        $tax                = $recurring_details['tax'];
        $discount           = $recurring_details['discount'];
        
        if ( $discount > 0 ) {
            $invoice->set( 'discount_code', $parent_invoice->discount_code );
        }
        
        $invoice->subtotal = wpinv_round_amount( $subtotal );
        $invoice->tax      = wpinv_round_amount( $tax );
        $invoice->discount = wpinv_round_amount( $discount );
        $invoice->total    = wpinv_round_amount( $total );

        $invoice  = apply_filters( 'wpinv_subscription_add_payment_save', $invoice, $this, $args );

        $invoice->save();
        $invoice->update_meta( '_wpinv_subscription_id', $this->id );
        
        if ( !empty( $invoice->ID ) ) {
            wpinv_update_payment_status( $invoice->ID, 'publish' );
            sleep(1);
            wpinv_update_payment_status( $invoice->ID, 'wpi-renewal' );
            
            $invoice = wpinv_get_invoice( $invoice->ID );

			// Send email notifications.
			wpinv_completed_invoice_notification( $invoice->ID );

            do_action( 'wpinv_recurring_add_subscription_payment', $invoice, $this );
            do_action( 'wpinv_recurring_record_payment', $invoice->ID, $this->parent_payment_id, $args['amount'], $args['transaction_id'] );
            
            return $invoice->ID;
        }

        return false;
    }

	/**
	 * Retrieves the transaction ID from the subscription
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function get_transaction_id() {

		if( empty( $this->transaction_id ) ) {

			$txn_id = wpinv_get_payment_transaction_id( $this->parent_payment_id );

			if( ! empty( $txn_id ) && (int) $this->parent_payment_id !== (int) $txn_id ) {
				$this->set_transaction_id( $txn_id );
			}

		}

		return $this->transaction_id;

	}

	/**
	 * Stores the transaction ID for the subscription purchase
	 *
	 * @since  1.0.0.4
	 * @return bool
	 */
	public function set_transaction_id( $txn_id = '' ) {
		$this->update( array( 'transaction_id' => $txn_id ) );
		$this->transaction_id = $txn_id;
	}

	/**
	 * Renews a subscription
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function renew() {

		$expires = $this->get_expiration_time();


		// Determine what date to use as the start for the new expiration calculation
		if( $expires > current_time( 'timestamp' ) && $this->is_active() ) {

			$base_date  = $expires;

		} else {

			$base_date  = current_time( 'timestamp' );

		}

		$last_day = wpinv_cal_days_in_month( CAL_GREGORIAN, date( 'n', $base_date ), date( 'Y', $base_date ) );


		$frequency = isset($this->frequency) ? $this->frequency : 1;
		$expiration = date( 'Y-m-d H:i:s', strtotime( '+' . $frequency . ' ' . $this->period  . ' 23:59:59', $base_date ) );

		if( date( 'j', $base_date ) == $last_day && 'day' != $this->period ) {
			$expiration = date( 'Y-m-d H:i:s', strtotime( $expiration . ' +2 days' ) );
		}

		$expiration  = apply_filters( 'wpinv_subscription_renewal_expiration', $expiration, $this->id, $this );

		do_action( 'wpinv_subscription_pre_renew', $this->id, $expiration, $this );

		$this->status = 'active';
		$times_billed = $this->get_times_billed();

		// Complete subscription if applicable
		if ( $this->bill_times > 0 && $times_billed >= $this->bill_times ) {
			$this->complete();
			$this->status = 'completed';
		}

		$args = array(
			'expiration' => $expiration,
			'status'     => $this->status,
		);

        $this->subs_db->update( $this->id, $args );

		do_action( 'wpinv_subscription_post_renew', $this->id, $expiration, $this );
		do_action( 'wpinv_recurring_set_subscription_status', $this->id, $this->status, $this );

	}

	/**
	 * Marks a subscription as completed
	 *
	 * Subscription is completed when the number of payments matches the billing_times field
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function complete() {

		// Only mark a subscription as complete if it's not already cancelled.
		if ( 'cancelled' === $this->status ) {
			return;
		}

		$args = array(
			'status' => 'completed'
		);

		if( $this->subs_db->update( $this->id, $args ) ) {

			$this->status = 'completed';

			do_action( 'wpinv_subscription_completed', $this->id, $this );

		}

	}

	/**
	 * Marks a subscription as expired
	 *
	 * Subscription is completed when the billing times is reached
	 *
	 * @since  1.0.0
	 * @param  $check_expiration bool True if expiration date should be checked with merchant processor before expiring
	 * @return void
	 */
	public function expire( $check_expiration = false ) {

		$expiration = $this->expiration;

		if( $check_expiration ) {

			// check_expiration() updates $this->expiration so compare to $expiration above

			if( $expiration < $this->get_expiration() && current_time( 'timestamp' ) < $this->get_expiration_time() ) {

				return false; // Do not mark as expired since real expiration date is in the future
			}

		}

		$args = array(
			'status' => 'expired'
		);

		if( $this->subs_db->update( $this->id, $args ) ) {

			$this->status = 'expired';

			do_action( 'wpinv_subscription_expired', $this->id, $this );

		}

	}

	/**
	 * Marks a subscription as failing
	 *
	 * @since  2.4.2
	 * @return void
	 */
	public function failing() {

		$args = array(
			'status' => 'failing'
		);

		if( $this->subs_db->update( $this->id, $args ) ) {

			$this->status = 'failing';

			do_action( 'wpinv_subscription_failing', $this->id, $this );


		}

	}

    /**
     * Marks a subscription as cancelled
     *
     * @since  1.0.0
     * @return void
     */
    public function cancel() {
        if ( 'cancelled' === $this->status ) {
            return; // Already cancelled
        }

        $args = array(
            'status' => 'cancelled'
        );

        if ( $this->subs_db->update( $this->id, $args ) ) {
            if ( is_user_logged_in() ) {
                $userdata = get_userdata( get_current_user_id() );
                $user     = $userdata->display_name;
            } else {
                $user = __( 'gateway', 'invoicing' );
            }

            $note = sprintf( __( 'Subscription has been cancelled by %s', 'invoicing' ), $user );
            wpinv_insert_payment_note( $this->parent_payment_id, $note, '', '', true );

            $this->status = 'cancelled';

            do_action( 'wpinv_subscription_cancelled', $this->id, $this );
        }
    }

	/**
	 * Determines if subscription can be cancelled
	 *
	 * This method is filtered by payment gateways in order to return true on subscriptions
	 * that can be cancelled with a profile ID through the merchant processor
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function can_cancel() {
        $ret = false;
	    if( $this->gateway === 'manual' || in_array( $this->status, $this->get_cancellable_statuses() ) ) {
            $ret = true;
        }
		return apply_filters( 'wpinv_subscription_can_cancel', $ret, $this );
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

		$url = wp_nonce_url( add_query_arg( array( 'wpinv_action' => 'cancel_subscription', 'sub_id' => $this->id ) ), 'wpinv-recurring-cancel' );

		return apply_filters( 'wpinv_subscription_cancel_url', $url, $this );
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

		$url = wp_nonce_url( add_query_arg( array( 'wpinv_action' => 'renew_subscription', 'sub_id' => $this->id ) ), 'wpinv-recurring-renew' );

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
	 * @return void
	 */
	public function get_update_url() {

		$url = add_query_arg( array( 'action' => 'update', 'subscription_id' => $this->id ) );

		return apply_filters( 'wpinv_subscription_update_url', $url, $this );
	}

	/**
	 * Determines if subscription is active
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function is_active() {

		$ret = false;

		if( ! $this->is_expired() && ( $this->status == 'active' || $this->status == 'cancelled' || $this->status == 'trialling' ) ) {
			$ret = true;
		}

		return apply_filters( 'wpinv_subscription_is_active', $ret, $this->id, $this );

	}

	/**
	 * Determines if subscription is expired
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function is_expired() {

		$ret = false;

		if ( $this->status == 'expired' ) {

			$ret = true;

		} elseif( 'active' === $this->status || 'cancelled' === $this->status || $this->status == 'trialling'  ) {

			$ret        = false;
			$expiration = $this->get_expiration_time();

			if( $expiration && strtotime( 'NOW', current_time( 'timestamp' ) ) > $expiration ) {
				$ret = true;

				if ( 'active' === $this->status || $this->status == 'trialling'  ) {
					$this->expire();
				}
			}

		}

		return apply_filters( 'wpinv_subscription_is_expired', $ret, $this->id, $this );

	}

	/**
	 * Retrieves the expiration date
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_expiration() {
		return $this->expiration;
	}

	/**
	 * Retrieves the expiration date in a timestamp
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_expiration_time() {
		return strtotime( $this->expiration, current_time( 'timestamp' ) );
	}

	/**
	 * Retrieves the subscription status
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_status() {

		// Monitor for page load delays on pages with large subscription lists (IE: Subscriptions table in admin)
		$this->is_expired();
		return $this->status;
	}

	/**
	 * Retrieves the subscription status label
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_status_label() {

		switch( $this->get_status() ) {
			case 'active' :
				$status = __( 'Active', 'invoicing' );
				break;

			case 'cancelled' :
				$status = __( 'Cancelled', 'invoicing' );
				break;

			case 'expired' :
				$status = __( 'Expired', 'invoicing' );
				break;

			case 'pending' :
				$status = __( 'Pending', 'invoicing' );
				break;

			case 'failing' :
				$status = __( 'Failing', 'invoicing' );
				break;

			case 'trialling' :
				$status = __( 'Trialling', 'invoicing' );
				break;

			case 'completed' :
				$status = __( 'Completed', 'invoicing' );
				break;

			default:
				$status = ucfirst( $this->get_status() );
				break;
		}

		return $status;
	}

    /**
     * Retrieves the subscription status label
     *
     * @since  1.0.0
     * @return int
     */
    public function get_status_label_html() {

        switch( $get_status = $this->get_status() ) {
            case 'active' :
                $status = __( 'Active', 'invoicing' );
                $class = 'label-info';
                break;

            case 'cancelled' :
                $status = __( 'Cancelled', 'invoicing' );
                $class = 'label-danger';
                break;

            case 'expired' :
                $status = __( 'Expired', 'invoicing' );
                $class = 'label-default';
                break;

            case 'pending' :
                $status = __( 'Pending', 'invoicing' );
                $class = 'label-primary';
                break;

            case 'failing' :
                $status = __( 'Failing', 'invoicing' );
                $class = 'label-danger';
                break;

            case 'trialling' :
                $status = __( 'Trialling', 'invoicing' );
                $class = 'label-info';
                break;

            case 'completed' :
                $status = __( 'Completed', 'invoicing' );
                $class = 'label-success';
                break;

            default:
                $status = ucfirst( $this->get_status() );
                $class = 'label-default';
                break;
        }

        $label = '<span class="sub-status label label-sub-' . $get_status . ' ' . $class . '">' . $status . '</span>';

        return apply_filters( 'wpinv_subscription_status_label_html', $label, $get_status, $status );
    }

    /**
     * Determines if a payment exists with the specified transaction ID
     *
     * @since  2.4
     * @param  string $txn_id The transaction ID from the merchant processor
     * @return bool
     */
    public function payment_exists( $txn_id = '' ) {
        global $wpdb;

        if ( empty( $txn_id ) ) {
            return false;
        }

        $txn_id = esc_sql( $txn_id );

        $purchase = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpinv_transaction_id' AND meta_value = '{$txn_id}' LIMIT 1" );

        if ( $purchase != null ) {
            return true;
        }

        return false;
    }

}
