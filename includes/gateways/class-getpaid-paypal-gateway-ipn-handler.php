<?php
/**
 * Paypal payment gateway IPN handler
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paypal Payment Gateway IPN handler class.
 *
 */
class GetPaid_Paypal_Gateway_IPN_Handler {

	/**
	 * Payment method id.
	 *
	 * @var string
	 */
	protected $id = 'paypal';

	/**
	 * Payment method object.
	 *
	 * @var GetPaid_Paypal_Gateway
	 */
	protected $gateway;

	/**
	 * Name of the database lock held while processing the IPN.
	 *
	 * @var string
	 */
	protected $lock_name = '';

	/**
	 * Class constructor.
	 *
	 * @param GetPaid_Paypal_Gateway $gateway
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
		$this->verify_ipn();
	}

	/**
	 * Processes ipns and marks payments as complete.
	 *
	 * @return void
	 */
	public function verify_ipn() {
		wpinv_error_log( 'GetPaid PayPal IPN Handler', false );

		// Validate the IPN.
		if ( empty( $_POST ) || ! $this->validate_ipn() ) {
			wp_die( 'PayPal IPN Request Failure', 500 );
		}

		// Process the IPN.
		$posted  = wp_unslash( $_POST );
		$invoice = $this->get_ipn_invoice( $posted );

		// PayPal may send several IPNs (e.g. subscr_signup and subscr_payment) at the same time.
		// Process them one at a time to prevent the invoice from being marked as paid twice.
		$this->acquire_invoice_lock( $invoice->get_id() );

		// Reload the invoice in case another IPN updated it while we were waiting.
		$invoice = $this->refresh_ipn_invoice( $invoice->get_id(), $posted );

		// Abort if it was not paid by our gateway.
		if ( $this->id != $invoice->get_gateway() ) {
			wpinv_error_log( 'Aborting, Invoice was not paid via PayPal', false );
			$this->release_invoice_lock();
			wp_die( 'Invoice not paid via PayPal', 200 );
		}

		$posted['payment_status'] = isset( $posted['payment_status'] ) && is_scalar( $posted['payment_status'] ) ? sanitize_key( strtolower( (string) $posted['payment_status'] ) ) : '';
		$posted['txn_type']       = isset( $posted['txn_type'] ) && is_scalar( $posted['txn_type'] ) ? sanitize_key( strtolower( (string) $posted['txn_type'] ) ) : '';

		wpinv_error_log( 'Payment status:' . $posted['payment_status'], false );
		wpinv_error_log( 'IPN Type:' . $posted['txn_type'], false );

		if ( method_exists( $this, 'ipn_txn_' . $posted['txn_type'] ) ) {
			call_user_func( array( $this, 'ipn_txn_' . $posted['txn_type'] ), $invoice, $posted );
			wpinv_error_log( 'Done processing IPN', false );
			$this->release_invoice_lock();
			wp_die( 'Processed', 200 );
		}

		wpinv_error_log( 'Aborting, Unsupported IPN type:' . $posted['txn_type'], false );
		$this->release_invoice_lock();
		wp_die( 'Unsupported IPN type', 200 );
	}

	/**
	 * Acquires a database lock for the invoice being processed.
	 *
	 * Ensures that simultaneous IPNs for the same invoice are processed one after the other.
	 *
	 * @since 2.8.62
	 *
	 * @param int $invoice_id Invoice ID.
	 * @return void
	 */
	protected function acquire_invoice_lock( $invoice_id ) {
		global $wpdb;

		$lock_name = 'getpaid_paypal_ipn_' . md5( DB_NAME . $wpdb->prefix . absint( $invoice_id ) );

		/**
		 * Filters the number of seconds to wait for another IPN of the same invoice to finish processing.
		 *
		 * @since 2.8.62
		 *
		 * @param int $timeout    Lock timeout in seconds.
		 * @param int $invoice_id Invoice ID.
		 */
		$timeout = absint( apply_filters( 'getpaid_paypal_ipn_lock_timeout', 20, $invoice_id ) );
		$locked  = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, %d )', $lock_name, $timeout ) );

		if ( '1' === (string) $locked ) {
			$this->lock_name = $lock_name;
			return;
		}

		// Timed out waiting for the other IPN, ask PayPal to resend this one later.
		if ( '0' === (string) $locked ) {
			wpinv_error_log( 'Aborting, Another IPN for the invoice ' . $invoice_id . ' is being processed', false );
			wp_die( 'Another IPN for this invoice is being processed', 503 );
		}

		// Locks are not supported by the database, process without a lock.
		wpinv_error_log( 'Could not acquire the IPN lock for the invoice ' . $invoice_id, false );
	}

	/**
	 * Releases the invoice database lock.
	 *
	 * @since 2.8.62
	 *
	 * @return void
	 */
	protected function release_invoice_lock() {
		global $wpdb;

		if ( empty( $this->lock_name ) ) {
			return;
		}

		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $this->lock_name ) );
		$this->lock_name = '';
	}

	/**
	 * Clears the invoice caches and reloads the IPN invoice from the database.
	 *
	 * @since 2.8.62
	 *
	 * @param int   $invoice_id Invoice ID.
	 * @param array $posted     Posted data.
	 * @return WPInv_Invoice
	 */
	protected function refresh_ipn_invoice( $invoice_id, $posted ) {
		clean_post_cache( $invoice_id );
		wp_cache_delete( $invoice_id, 'getpaid_invoice_special_fields' );
		wp_cache_delete( $invoice_id, 'getpaid_invoice_cart_details' );

		return $this->get_ipn_invoice( $posted );
	}

	/**
	 * Retrieves IPN Invoice.
	 *
	 * @param array $posted
	 * @return WPInv_Invoice
	 */
	protected function get_ipn_invoice( $posted ) {
		wpinv_error_log( 'Retrieving PayPal IPN Response Invoice', false );

		if ( ! empty( $posted['custom'] ) ) {
			$invoice = new WPInv_Invoice( $posted['custom'] );

			if ( $invoice->exists() ) {
				wpinv_error_log( 'Found invoice #' . $invoice->get_number(), false );
				return $invoice;
			}
		}

		wpinv_error_log( 'Could not retrieve the associated invoice.', false );
		wp_die( 'Could not retrieve the associated invoice.', 200 );
	}

	/**
	 * Check PayPal IPN validity.
	 */
	protected function validate_ipn() {
		wpinv_error_log( 'Validating PayPal IPN response', false );

		// Retrieve the associated invoice.
		$posted  = wp_unslash( $_POST );
		$invoice = $this->get_ipn_invoice( $posted );

		if ( $this->gateway->is_sandbox( $invoice ) ) {
			wpinv_error_log( $posted, 'Invoice was processed in sandbox hence logging the posted data', false );
		}

		// Validate the IPN.
		$posted['cmd'] = '_notify-validate';

		// Send back post vars to paypal.
		$params = array(
			'body'        => $posted,
			'timeout'     => 60,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false,
			'user-agent'  => 'GetPaid/' . WPINV_VERSION,
		);

		// Post back to get a response.
		$response = wp_safe_remote_post( $this->gateway->is_sandbox( $invoice ) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $params );

		// Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && $response['response']['code'] < 300 && strstr( $response['body'], 'VERIFIED' ) ) {
			$invoice->add_note( 'Received valid response from PayPal IPN: ' . $response['body'], false, false, true );
			wpinv_error_log( 'Received valid response from PayPal IPN: ' . $response['body'], false );
			return true;
		}

		$invoice->add_note( 'IPN message:' . wp_json_encode( $posted ), false, false, true );

		if ( is_wp_error( $response ) ) {
			$invoice->add_note( 'Received invalid response from PayPal IPN: ' . $response->get_error_message(), false, false, true );
			wpinv_error_log( $response->get_error_message(), 'Received invalid response from PayPal IPN' );
			return false;
		}

		$invoice->add_note( 'Received invalid response from PayPal IPN: ' . $response['body'], false, false, true );
		wpinv_error_log( $response['body'], 'Received invalid response from PayPal IPN' );
		return false;
	}

	/**
	 * Retrieves the merchant email address from an IPN.
	 *
	 * @since 2.8.62
	 *
	 * @param array $posted Posted data.
	 * @return string
	 */
	protected function get_ipn_business_email( $posted ) {
		if ( isset( $posted['business'] ) && is_email( $posted['business'] ) ) {
			return trim( $posted['business'] );
		}

		return isset( $posted['receiver_email'] ) ? trim( $posted['receiver_email'] ) : '';
	}

	/**
	 * Check currency from IPN matches the invoice.
	 *
	 * @param WPInv_Invoice $invoice          Invoice object.
	 * @param string   $currency currency to validate.
	 */
	protected function validate_ipn_currency( $invoice, $currency ) {
		$currency = sanitize_text_field( (string) $currency );

		if ( strtolower( $invoice->get_currency() ) !== strtolower( $currency ) ) {

			/* translators: %s: currency code. */
			$invoice->update_status( 'wpi-onhold', wp_sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', 'invoicing' ), $currency ) );
			$invoice->add_note( wp_sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', 'invoicing' ), $currency ), false, false, true );

			wpinv_error_log( "Currencies do not match: {$currency} instead of {$invoice->get_currency()}", 'IPN Error', false );
			return false;
		}

		wpinv_error_log( $currency, 'Validated IPN Currency', false );
		return true;
	}

	/**
	 * Check payment amount from IPN matches the invoice.
	 *
	 * @param WPInv_Invoice $invoice          Invoice object.
	 * @param float   $amount amount to validate.
	 */
	protected function validate_ipn_amount( $invoice, $amount ) {
		$amount = (float) $amount;

		if ( number_format( (float) $invoice->get_total(), 2, '.', '' ) !== number_format( $amount, 2, '.', '' ) ) {
			/* translators: %s: Amount. */
			$invoice->update_status( 'wpi-onhold', wp_sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'invoicing' ), $amount ) );
			$invoice->add_note( wp_sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'invoicing' ), $amount ), false, false, true );

			wpinv_error_log( "Amounts do not match: {$amount} instead of {$invoice->get_total()}", 'IPN Error', __FILE__, __LINE__, true );
			return false;
		}

		wpinv_error_log( $amount, 'Validated IPN Amount', false );
		return true;
	}

	/**
	 * Verify receiver email from PayPal.
	 *
	 * @param WPInv_Invoice $invoice          Invoice object.
	 * @param string   $receiver_email Email to validate.
	 */
	protected function validate_ipn_receiver_email( $invoice, $receiver_email ) {
		$paypal_email   = $this->gateway->is_sandbox( $invoice ) ? wpinv_get_option( 'paypal_sandbox_email', wpinv_get_option( 'paypal_email', '' ) ) : wpinv_get_option( 'paypal_email', '' );
		$receiver_email = sanitize_email( (string) $receiver_email );

		if ( ! empty( $paypal_email ) && strcasecmp( trim( $receiver_email ), trim( $paypal_email ) ) !== 0 ) {
			wpinv_record_gateway_error( 'IPN Error', "IPN Response is for another account: {$receiver_email}. Your PayPal email is {$paypal_email}." );

			/* translators: %s: email address . */
			$invoice->update_status( 'wpi-onhold', wp_sprintf( __( 'Validation error: PayPal IPN response from a different email address (%s). Your PayPal email is %s.', 'invoicing' ), $receiver_email, $paypal_email ) );
			$invoice->add_note( wp_sprintf( __( 'Validation error: PayPal IPN response from a different email address (%s). Your PayPal email is %s.', 'invoicing' ), $receiver_email, $paypal_email ), false, false, true );

			wpinv_error_log( "IPN Response is for another account: {$receiver_email}. Your email PayPal is {$paypal_email}.", 'IPN Error', false );
			return false;
		}

		wpinv_error_log( 'Validated PayPal Email', false );
		return true;
	}

	/**
	 * Handles one time payments.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_web_accept( $invoice, $posted ) {
		// Collect payment details
		$payment_status = strtolower( $posted['payment_status'] );
		$business_email = $this->get_ipn_business_email( $posted );
		$currency       = isset( $posted['mc_currency'] ) ? $posted['mc_currency'] : '';

		// Abort (and keep the invoice on hold) if the IPN is not for this merchant or currency.
		if ( ! $this->validate_ipn_receiver_email( $invoice, $business_email ) || ! $this->validate_ipn_currency( $invoice, $currency ) ) {
			return;
		}

		// Update the transaction id.
		if ( ! empty( $posted['txn_id'] ) ) {
			$invoice->set_transaction_id( wpinv_clean( $posted['txn_id'] ) );
			$invoice->save();
		}

		$invoice->add_system_note( __( 'Processing invoice IPN', 'invoicing' ) );

		// Process a refund.
		if ( 'refunded' === $payment_status || 'reversed' === $payment_status ) {
			update_post_meta( $invoice->get_id(), 'refunded_remotely', 1 );

			if ( ! $invoice->is_refunded() ) {
				$invoice->update_status( 'wpi-refunded', isset( $posted['reason_code'] ) ? sanitize_text_field( $posted['reason_code'] ) : '' );
			}

			wpinv_error_log( isset( $posted['reason_code'] ) ? sanitize_text_field( $posted['reason_code'] ) : '', false );
			return;
		}

		// Process payments.
		if ( 'completed' === $payment_status ) {
			if ( $invoice->is_paid() && 'wpi-processing' != $invoice->get_status() ) {
				wpinv_error_log( 'Aborting, Invoice #' . $invoice->get_number() . ' is already paid.', false );
				return;
			}

			// Do not mark the invoice as paid if the paid amount does not match the invoice total.
			if ( ! $this->validate_ipn_amount( $invoice, isset( $posted['mc_gross'] ) ? $posted['mc_gross'] : 0 ) ) {
				return;
			}

			$note = '';

			if ( ! empty( $posted['mc_fee'] ) ) {
				$note = sprintf( __( 'PayPal Transaction Fee %s.', 'invoicing' ), sanitize_text_field( $posted['mc_fee'] ) );
			}

			if ( ! empty( $posted['payer_status'] ) ) {
				$note = ' ' . sprintf( __( 'Buyer status %s.', 'invoicing' ), sanitize_text_field( $posted['payer_status'] ) );
			}

			$invoice->mark_paid( ( ! empty( $posted['txn_id'] ) ? sanitize_text_field( $posted['txn_id'] ) : '' ), trim( $note ) );
			wpinv_error_log( 'Invoice marked as paid.', false );
			return;
		}

		// Pending payments.
		if ( 'pending' === $payment_status ) {
			/* translators: %s: pending reason. */
			$pending_reason = isset( $posted['pending_reason'] ) ? sanitize_text_field( $posted['pending_reason'] ) : '';
			$invoice->update_status( 'wpi-onhold', wp_sprintf( __( 'Payment pending (%s).', 'invoicing' ), $pending_reason ) );
			$invoice->add_note( wp_sprintf( __( 'Payment pending (%s).', 'invoicing' ), $pending_reason ), false, false, true );

			wpinv_error_log( 'Invoice marked as "payment held".', false );
			return;
		}

		/* translators: %s: payment status. */
		$invoice->update_status( 'wpi-failed', sprintf( __( 'Payment %s via IPN.', 'invoicing' ), sanitize_text_field( $posted['payment_status'] ) ) );
	}

	/**
	 * Handles one time payments.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_cart( $invoice, $posted ) {
		$this->ipn_txn_web_accept( $invoice, $posted );
	}

	/**
	 * Handles subscription sign ups.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_subscr_signup( $invoice, $posted ) {
		wpinv_error_log( 'Processing subscription signup', false );

		// Make sure the invoice has a subscription.
		$subscription = getpaid_get_invoice_subscription( $invoice );

		if ( empty( $subscription ) ) {
			return wpinv_error_log( 'Aborting, Subscription for the invoice ' . $invoice->get_id() . ' not found', false );
		}

		wpinv_error_log( 'Found subscription #' . $subscription->get_id(), false );

		// Validate the IPN.
		$business_email = $this->get_ipn_business_email( $posted );
		$currency       = isset( $posted['mc_currency'] ) ? $posted['mc_currency'] : '';
																   

		if ( ! $this->validate_ipn_receiver_email( $invoice, $business_email ) || ! $this->validate_ipn_currency( $invoice, $currency ) ) {
			return;
		}

		// Activate the subscription.
		$duration = strtotime( $subscription->get_expiration() ) - strtotime( $subscription->get_date_created() );
		$subscription->set_date_created( current_time( 'mysql' ) );
		$subscription->set_expiration( date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $duration ) ) );
		$subscription->set_profile_id( sanitize_text_field( $posted['subscr_id'] ) );
		$subscription->activate();

		// Set the transaction id.
		if ( ! empty( $posted['txn_id'] ) ) {
			$invoice->add_note( sprintf( __( 'PayPal Transaction ID: %s', 'invoicing' ), $posted['txn_id'] ), false, false, true );
			$invoice->set_transaction_id( $posted['txn_id'] );
		}

		// Update the payment status.
		$invoice->mark_paid();

		$invoice->add_note( sprintf( __( 'PayPal Subscription ID: %s', 'invoicing' ), $posted['subscr_id'] ), false, false, true );

		wpinv_error_log( 'Subscription started.', false );
	}

	/**
	 * Handles subscription renewals.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_subscr_payment( $invoice, $posted ) {
		// Make sure the invoice has a subscription.
		$subscription = getpaid_subscriptions()->get_invoice_subscription( $invoice );

		if ( empty( $subscription ) ) {
			return wpinv_error_log( 'Aborting, Subscription for the invoice ' . $invoice->get_id() . ' not found', false );
		}

		wpinv_error_log( 'Found subscription #' . $subscription->get_id(), false );

		// Validate the IPN.
		$business_email = $this->get_ipn_business_email( $posted );
		$currency       = isset( $posted['mc_currency'] ) ? $posted['mc_currency'] : '';

		if ( ! $this->validate_ipn_receiver_email( $invoice, $business_email ) || ! $this->validate_ipn_currency( $invoice, $currency ) ) {
			return;
		}

		// PayPal sends a subscr_payment for the first payment too.
		$date_completed = getpaid_format_date( $invoice->get_date_completed() );
		$date_created   = getpaid_format_date( $invoice->get_date_created() );
		$today_date     = getpaid_format_date( current_time( 'mysql' ) );
		$payment_date   = getpaid_format_date( isset( $posted['payment_date'] ) ? $posted['payment_date'] : '' );
		$subscribe_date = getpaid_format_date( $subscription->get_date_created() );
		$dates          = array_filter( compact( 'date_completed', 'date_created', 'subscribe_date' ) );

		foreach ( $dates as $date ) {

			if ( $date !== $today_date && $date !== $payment_date ) {
				continue;
			}

			if ( ! empty( $posted['txn_id'] ) ) {
				$invoice->set_transaction_id( sanitize_text_field( $posted['txn_id'] ) );
				$invoice->add_note( wp_sprintf( __( 'PayPal Transaction ID: %s', 'invoicing' ), sanitize_text_field( $posted['txn_id'] ) ), false, false, true );
			}

			return $invoice->mark_paid();

		}

		wpinv_error_log( 'Processing subscription renewal payment for the invoice ' . $invoice->get_id(), false );

		// Abort if there is no transaction id or the payment is already recorded.
		if ( empty( $posted['txn_id'] ) ) {
			return wpinv_error_log( 'Aborting, missing transaction id', false );
		}

		$posted['txn_id'] = sanitize_text_field( $posted['txn_id'] );

		if ( wpinv_get_id_by_transaction_id( $posted['txn_id'] ) ) {
			return wpinv_error_log( 'Aborting, Transaction ' . $posted['txn_id'] . ' has already been processed', false );
		}

		$args = array(
			'transaction_id' => $posted['txn_id'],
			'gateway'        => $this->id,
		);

		$invoice = wpinv_get_invoice( $subscription->add_payment( $args ) );

		if ( empty( $invoice ) ) {
			return;
		}

		$invoice->add_note( wp_sprintf( __( 'PayPal Transaction ID: %s', 'invoicing' ), $posted['txn_id'] ), false, false, true );
		$invoice->add_note( wp_sprintf( __( 'PayPal Subscription ID: %s', 'invoicing' ), $posted['subscr_id'] ), false, false, true );

		$subscription->renew();
		wpinv_error_log( 'Subscription renewed.', false );
	}

	/**
	 * Handles subscription cancelations.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 */
	protected function ipn_txn_subscr_cancel( $invoice ) {
		// Make sure the invoice has a subscription.
		$subscription = getpaid_subscriptions()->get_invoice_subscription( $invoice );

		if ( empty( $subscription ) ) {
			return wpinv_error_log( 'Aborting, Subscription for the invoice ' . $invoice->get_id() . ' not found', false );
		}

		wpinv_error_log( 'Processing subscription cancellation for the invoice ' . $invoice->get_id(), false );
		$subscription->cancel();
		wpinv_error_log( 'Subscription cancelled.', false );
	}

	/**
	 * Handles subscription completions.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_subscr_eot( $invoice ) {
		// Make sure the invoice has a subscription.
		$subscription = getpaid_subscriptions()->get_invoice_subscription( $invoice );

		if ( empty( $subscription ) ) {
			return wpinv_error_log( 'Aborting, Subscription for the invoice ' . $invoice->get_id() . ' not found', false );
		}

		wpinv_error_log( 'Processing subscription end of life for the invoice ' . $invoice->get_id(), false );

		// PayPal sends end of term whenever a subscription's paid period runs out: after its last planned payment, but
		// also after the customer cancelled or payments failed. Only the first is a completed (fully paid) subscription.
		if ( $subscription->has_status( array( 'cancelled', 'completed' ) ) ) {
			wpinv_error_log( 'Subscription was already ' . $subscription->get_status() . ', leaving it as is.', false );
		} elseif ( $subscription->is_last_renewal() ) {
			$subscription->complete();
			wpinv_error_log( 'Subscription completed.', false );
		} else {
			$subscription->expire();
			wpinv_error_log( 'Subscription expired.', false );
		}
	}

	/**
	 * Handles subscription fails.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_subscr_failed( $invoice ) {
		// Make sure the invoice has a subscription.
		$subscription = getpaid_subscriptions()->get_invoice_subscription( $invoice );

		if ( empty( $subscription ) ) {
			return wpinv_error_log( 'Aborting, Subscription for the invoice ' . $invoice->get_id() . ' not found', false );
		}

		wpinv_error_log( 'Processing subscription payment failure for the invoice ' . $invoice->get_id(), false );
		$subscription->failing();
		wpinv_error_log( 'Subscription marked as failing.', false );
	}

	/**
	 * Handles subscription suspensions.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_recurring_payment_suspended_due_to_max_failed_payment( $invoice ) {
		// Make sure the invoice has a subscription.
		$subscription = getpaid_subscriptions()->get_invoice_subscription( $invoice );

		if ( empty( $subscription ) ) {
			return wpinv_error_log( 'Aborting, Subscription for the invoice ' . $invoice->get_id() . ' not found', false );
		}

		wpinv_error_log( 'Processing subscription cancellation due to max failed payment for the invoice ' . $invoice->get_id(), false );
		$subscription->cancel();
		wpinv_error_log( 'Subscription cancelled.', false );
	}
}
