<?php
/**
 * Paypal payment gateway
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paypal Payment Gateway class.
 *
 */
class GetPaid_Paypal_Gateway extends GetPaid_Payment_Gateway {

    /**
	 * Payment method id.
	 *
	 * @var string
	 */
    public $id = 'paypal';

    /**
	 * An array of features that this gateway supports.
	 *
	 * @var array
	 */
    protected $supports = array( 'subscription', 'sandbox' );

    /**
	 * Payment method order.
	 *
	 * @var int
	 */
    public $order = 1;

    /**
	 * Stores line items to send to PayPal.
	 *
	 * @var array
	 */
    protected $line_items = array();

    /**
	 * Endpoint for requests from PayPal.
	 *
	 * @var string
	 */
	protected $notify_url;

	/**
	 * Endpoint for requests to PayPal.
	 *
	 * @var string
	 */
    protected $endpoint;
    
    /**
	 * Currencies this gateway is allowed for.
	 *
	 * @var array
	 */
	public $currencies = array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB', 'INR' );

    /**
	 * URL to view a transaction.
	 *
	 * @var string
	 */
    public $view_transaction_url = 'https://www.{sandbox}paypal.com/activity/payment/%s';

    /**
	 * URL to view a subscription.
	 *
	 * @var string
	 */
	public $view_subscription_url = 'https://www.{sandbox}paypal.com/cgi-bin/webscr?cmd=_profile-recurring-payments&encrypted_profile_id=%s';

    /**
	 * Class constructor.
	 */
	public function __construct() {

        $this->title                = __( 'PayPal Standard', 'invoicing' );
        $this->method_title         = __( 'PayPal Standard', 'invoicing' );
        $this->checkout_button_text = __( 'Proceed to PayPal', 'invoicing' );
        $this->notify_url           = wpinv_get_ipn_url( $this->id );

        add_filter( 'getpaid_paypal_args', array( $this, 'process_subscription' ), 10, 2 );
        add_filter( 'wpinv_gateway_description', array( $this, 'sandbox_notice' ), 10, 2 );

        parent::__construct();
    }

    /**
	 * Process Payment.
	 *
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @param array $submission_data Posted checkout fields.
	 * @param GetPaid_Payment_Form_Submission $submission Checkout submission.
	 * @return array
	 */
	public function process_payment( $invoice, $submission_data, $submission ) {

        // Get redirect url.
        $paypal_redirect = $this->get_request_url( $invoice );

        // Add a note about the request url.
        $invoice->add_note(
            sprintf(
                __( 'Redirecting to PayPal: %s', 'invoicing' ),
                esc_url( $paypal_redirect )
            ),
            false,
            false,
            true
        );

        // Redirect to PayPal
        wp_redirect( $paypal_redirect );
        exit;

    }

    /**
	 * Get the PayPal request URL for an invoice.
	 *
	 * @param  WPInv_Invoice $invoice Invoice object.
	 * @return string
	 */
	public function get_request_url( $invoice ) {

        // Endpoint for this request
		$this->endpoint    = $this->is_sandbox( $invoice ) ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?test_ipn=1&' : 'https://www.paypal.com/cgi-bin/webscr?';

        // Retrieve paypal args.
        $paypal_args       = map_deep( $this->get_paypal_args( $invoice ), 'urlencode' );

        if ( $invoice->is_recurring() ) {
            $paypal_args['bn'] = 'GetPaid_Subscribe_WPS_US';
        } else {
            $paypal_args['bn'] = 'GetPaid_ShoppingCart_WPS_US';
        }

        return add_query_arg( $paypal_args, $this->endpoint );

	}

    /**
	 * Get PayPal Args for passing to PP.
	 *
	 * @param  WPInv_Invoice $invoice Invoice object.
	 * @return array
	 */
	protected function get_paypal_args( $invoice ) {

        // Whether or not to send the line items as one item.
		$force_one_line_item = apply_filters( 'getpaid_paypal_force_one_line_item', false, $invoice );

		if ( $invoice->is_recurring() || ( wpinv_use_taxes() && wpinv_prices_include_tax() ) ) {
			$force_one_line_item = true;
		}

		$paypal_args = apply_filters(
			'getpaid_paypal_args',
			array_merge(
				$this->get_transaction_args( $invoice ),
				$this->get_line_item_args( $invoice, $force_one_line_item )
			),
			$invoice
		);

		return $this->fix_request_length( $invoice, $paypal_args );
    }

    /**
	 * Get transaction args for paypal request.
	 *
	 * @param WPInv_Invoice $invoice Invoice object.
	 * @return array
	 */
	protected function get_transaction_args( $invoice ) {

		return array(
            'cmd'           => '_cart',
            'business'      => wpinv_get_option( 'paypal_email', false ),
            'no_shipping'   => '1',
            'shipping'      => '0',
            'no_note'       => '1',
            'charset'       => 'utf-8',
            'rm'            => is_ssl() ? 2 : 1,
            'upload'        => 1,
            'currency_code' => $invoice->get_currency(), // https://developer.paypal.com/docs/nvp-soap-api/currency-codes/#paypal
            'return'        => esc_url_raw( $this->get_return_url( $invoice ) ),
            'cancel_return' => esc_url_raw( $invoice->get_checkout_payment_url() ),
            'notify_url'    => getpaid_limit_length( $this->notify_url, 255 ),
            'invoice'       => getpaid_limit_length( $invoice->get_number(), 127 ),
            'custom'        => $invoice->get_id(),
            'first_name'    => getpaid_limit_length( $invoice->get_first_name(), 32 ),
            'last_name'     => getpaid_limit_length( $invoice->get_last_name(), 64 ),
            'country'       => getpaid_limit_length( $invoice->get_country(), 2 ),
            'email'         => getpaid_limit_length( $invoice->get_email(), 127 ),
            'cbt'           => get_bloginfo( 'name' )
        );

    }

    /**
	 * Get line item args for paypal request.
	 *
	 * @param  WPInv_Invoice $invoice Invoice object.
	 * @param  bool     $force_one_line_item Create only one item for this invoice.
	 * @return array
	 */
	protected function get_line_item_args( $invoice, $force_one_line_item = false ) {

        // Maybe send invoice as a single item.
		if ( $force_one_line_item ) {
            return $this->get_line_item_args_single_item( $invoice );
        }

        // Send each line item individually.
        $line_item_args = array();

        // Prepare line items.
        $this->prepare_line_items( $invoice );

        // Add taxes to the cart
        if ( wpinv_use_taxes() && $invoice->is_taxable() ) {
            $line_item_args['tax_cart'] = wpinv_sanitize_amount( (float) $invoice->get_total_tax(), 2 );
        }

        // Add discount.
        if ( $invoice->get_total_discount() > 0 ) {
            $line_item_args['discount_amount_cart'] = wpinv_sanitize_amount( (float) $invoice->get_total_discount(), 2 );
        }

		return array_merge( $line_item_args, $this->get_line_items() );

    }

    /**
	 * Get line item args for paypal request as a single line item.
	 *
	 * @param  WPInv_Invoice $invoice Invoice object.
	 * @return array
	 */
	protected function get_line_item_args_single_item( $invoice ) {
		$this->delete_line_items();

        $item_name = sprintf( __( 'Invoice #%s', 'invoicing' ), $invoice->get_number() );
		$this->add_line_item( $item_name, 1, wpinv_sanitize_amount( (float) $invoice->get_total(), 2 ), $invoice->get_id() );

		return $this->get_line_items();
    }

    /**
	 * Return all line items.
	 */
	protected function get_line_items() {
		return $this->line_items;
	}

    /**
	 * Remove all line items.
	 */
	protected function delete_line_items() {
		$this->line_items = array();
    }

    /**
	 * Prepare line items to send to paypal.
	 *
	 * @param  WPInv_Invoice $invoice Invoice object.
	 */
	protected function prepare_line_items( $invoice ) {
		$this->delete_line_items();

		// Items.
		foreach ( $invoice->get_items() as $item ) {
			$amount   = $invoice->get_template() == 'amount' ? $item->get_price() : $item->get_sub_total();
			$quantity = $invoice->get_template() == 'amount' ? 1 : $item->get_quantity();
			$this->add_line_item( $item->get_raw_name(), $quantity, $amount, $item->get_id() );
        }

        // Fees.
		foreach ( $invoice->get_fees() as $fee => $data ) {
            $this->add_line_item( $fee, 1, $data['amount'] );
        }

    }

    /**
	 * Add PayPal Line Item.
	 *
	 * @param  string $item_name Item name.
	 * @param  int    $quantity Item quantity.
	 * @param  float  $amount Amount.
	 * @param  string $item_number Item number.
	 */
	protected function add_line_item( $item_name, $quantity = 1, $amount = 0.0, $item_number = '' ) {
		$index = ( count( $this->line_items ) / 4 ) + 1;

		$item = apply_filters(
			'getpaid_paypal_line_item',
			array(
				'item_name'   => html_entity_decode( getpaid_limit_length( $item_name ? wp_strip_all_tags( $item_name ) : __( 'Item', 'invoicing' ), 127 ), ENT_NOQUOTES, 'UTF-8' ),
				'quantity'    => (int) $quantity,
				'amount'      => wpinv_sanitize_amount( (float) $amount, 2 ),
				'item_number' => $item_number,
			),
			$item_name,
			$quantity,
			$amount,
			$item_number
		);

		$this->line_items[ 'item_name_' . $index ]   = getpaid_limit_length( $item['item_name'], 127 );
        $this->line_items[ 'quantity_' . $index ]    = $item['quantity'];
        
        // The price or amount of the product, service, or contribution, not including shipping, handling, or tax.
		$this->line_items[ 'amount_' . $index ]      = $item['amount'];
		$this->line_items[ 'item_number_' . $index ] = getpaid_limit_length( $item['item_number'], 127 );
    }

    /**
	 * If the default request with line items is too long, generate a new one with only one line item.
	 *
	 * https://support.microsoft.com/en-us/help/208427/maximum-url-length-is-2-083-characters-in-internet-explorer.
	 *
	 * @param WPInv_Invoice $invoice Invoice to be sent to Paypal.
	 * @param array    $paypal_args Arguments sent to Paypal in the request.
	 * @return array
	 */
	protected function fix_request_length( $invoice, $paypal_args ) {
		$max_paypal_length = 2083;
		$query_candidate   = http_build_query( $paypal_args, '', '&' );

		if ( strlen( $this->endpoint . $query_candidate ) <= $max_paypal_length ) {
			return $paypal_args;
		}

		return apply_filters(
			'getpaid_paypal_args',
			array_merge(
				$this->get_transaction_args( $invoice ),
				$this->get_line_item_args( $invoice, true )
			),
			$invoice
		);

    }
    
    /**
	 * Processes recurring invoices.
	 *
	 * @param  array $paypal_args PayPal args.
	 * @param  WPInv_Invoice    $invoice Invoice object.
	 */
	public function process_subscription( $paypal_args, $invoice ) {

        // Make sure this is a subscription.
        if ( ! $invoice->is_recurring() || ! $subscription = wpinv_get_subscription( $invoice ) ) {
            return $paypal_args;
        }

        // It's a subscription
        $paypal_args['cmd'] = '_xclick-subscriptions';

        // Subscription name.
        $paypal_args['item_name'] = sprintf( __( 'Invoice #%s', 'invoicing' ), $invoice->get_number() );

        // Get subscription args.
        $period                 = strtoupper( substr( $subscription->period, 0, 1) );
        $interval               = (int) $subscription->frequency;
        $bill_times             = (int) $subscription->bill_times;
        $initial_amount         = (float) wpinv_sanitize_amount( $invoice->get_initial_total(), 2 );
        $recurring_amount       = (float) wpinv_sanitize_amount( $invoice->get_recurring_total(), 2 );
        $subscription_item      = $invoice->get_recurring( true );

        if ( $subscription_item->has_free_trial() ) {

            $paypal_args['a1'] = 0 == $initial_amount ? 0 : $initial_amount;

			// Trial period length.
			$paypal_args['p1'] = $subscription_item->get_trial_interval();

			// Trial period.
			$paypal_args['t1'] = $subscription_item->get_trial_period();

        } else if ( $initial_amount != $recurring_amount ) {

            // No trial period, but initial amount includes a sign-up fee and/or other items, so charge it as a separate period.

            if ( 1 == $bill_times ) {
                $param_number = 3;
            } else {
                $param_number = 1;
            }

            $paypal_args[ 'a' . $param_number ] = $initial_amount ? 0 : $initial_amount;

            // Sign Up interval
            $paypal_args[ 'p' . $param_number ] = $interval;

            // Sign Up unit of duration
            $paypal_args[ 't' . $param_number ] = $period;

        }

        // We have a recurring payment
		if ( ! isset( $param_number ) || 1 == $param_number ) {

			// Subscription price
			$paypal_args['a3'] = $recurring_amount;

			// Subscription duration
			$paypal_args['p3'] = $interval;

			// Subscription period
			$paypal_args['t3'] = $period;

        }
        
        // Recurring payments
		if ( 1 == $bill_times || ( $initial_amount != $recurring_amount && ! $subscription_item->has_free_trial() && 2 == $bill_times ) ) {

			// Non-recurring payments
			$paypal_args['src'] = 0;

		} else {

			$paypal_args['src'] = 1;

			if ( $bill_times > 0 ) {

				// An initial period is being used to charge a sign-up fee
				if ( $initial_amount != $recurring_amount && ! $subscription_item->has_free_trial() ) {
					$bill_times--;
				}

                // Make sure it's not over the max of 52
                $paypal_args['srt'] = ( $bill_times <= 52 ? absint( $bill_times ) : 52 );

			}
        }
        
        // Force return URL so that order description & instructions display
        $paypal_args['rm'] = 2;
        
        // Get rid of redudant items.
        foreach ( array( 'item_name_1', 'quantity_1', 'amount_1', 'item_number_1' ) as $arg ) {

            if ( isset( $paypal_args[ $arg ] ) ) {
                unset( $paypal_args[ $arg ] );
            }

        }

        return apply_filters(
			'getpaid_paypal_subscription_args',
			$paypal_args,
			$invoice
        );

    }

    /**
	 * Processes ipns and marks payments as complete.
	 *
	 * @return void
	 */
	public function verify_ipn() {

        // Validate the IPN.
        if ( empty( $_POST ) || ! $this->validate_ipn() ) {
		    wp_die( 'PayPal IPN Request Failure', 'PayPal IPN', array( 'response' => 500 ) );
		}

        // Process the IPN.
        $posted  = wp_unslash( $_POST );
        $invoice = wpinv_get_invoice( $posted['custom'] );

        if ( $invoice && $this->id == $invoice->get_gateway() ) {

            $posted['payment_status'] = sanitize_key( strtolower( $posted['payment_status'] ) );
            $posted['txn_type']       = sanitize_key( strtolower( $posted['txn_type'] ) );

            wpinv_error_log( 'Found invoice #' . $invoice->get_number() );
            wpinv_error_log( 'Payment status:' . $posted['payment_status'] );

			if ( method_exists( $this, 'ipn_txn_' . $posted['txn_type'] ) ) {
				call_user_func( array( $this, 'ipn_txn_' . $posted['txn_type'] ), $invoice, $posted );
            } else {
                wpinv_error_log( 'Aborting, Invalid type:' . $posted['txn_type'] );
            }

        }

        exit;

    }

    /**
	 * Check PayPal IPN validity.
	 */
	public function validate_ipn() {

		wpinv_error_log( 'Validating PayPal IPN response' );

		// Get received values from post data.
		$validate_ipn        = wp_unslash( $_POST );
		$validate_ipn['cmd'] = '_notify-validate';

		// Send back post vars to paypal.
		$params = array(
			'body'        => $validate_ipn,
			'timeout'     => 60,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false,
			'user-agent'  => 'GetPaid/' . WPINV_VERSION,
		);

		// Post back to get a response.
		$response = wp_safe_remote_post( $this->is_sandbox() ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $params );

        // Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr( $response['body'], 'VERIFIED' ) ) {
            wpinv_error_log( $response['body'], 'Received valid response from PayPal IPN' );
			return true;
        }

        if ( is_wp_error( $response ) ) {
            wpinv_error_log( $response->get_error_message(), 'Received invalid response from PayPal IPN' );
        } else {
            wpinv_error_log( $response['body'], 'Received invalid response from PayPal IPN' );
        }

        return false;

    }

    /**
	 * Check currency from IPN matches the invoice.
	 *
	 * @param WPInv_Invoice $invoice          Invoice object.
	 * @param string   $currency currency to validate.
	 */
	protected function validate_ipn_currency( $invoice, $currency ) {
		if ( strtolower( $invoice->get_currency() ) !== strtolower( $currency ) ) {
            wpinv_record_gateway_error( 'IPN Error', "Currencies do not match: {$currency} instead of {$invoice->get_currency()}" );

			/* translators: %s: currency code. */
			$invoice->update_status( 'wpi-processing', sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', 'invoicing' ), $currency ) );
			exit;
		}
	}

	/**
	 * Check payment amount from IPN matches the invoice.
	 *
	 * @param WPInv_Invoice $invoice          Invoice object.
	 * @param float   $amount amount to validate.
	 */
	protected function validate_ipn_amount( $invoice, $amount ) {
		if ( number_format( $invoice->get_total(), 2, '.', '' ) !== number_format( $amount, 2, '.', '' ) ) {
            wpinv_record_gateway_error( 'IPN Error', "Amounts do not match: {$amount} instead of {$invoice->get_total()}" );

			/* translators: %s: Amount. */
			$invoice->update_status( 'wpi-processing', sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'invoicing' ), $amount ) );
			exit;
		}
	}

	/**
	 * Verify receiver email from PayPal.
	 *
	 * @param WPInv_Invoice $invoice          Invoice object.
	 * @param string   $receiver_email Email to validate.
	 */
	protected function validate_ipn_receiver_email( $invoice, $receiver_email ) {
        $paypal_email = wpinv_get_option( 'paypal_email' );

		if ( strcasecmp( trim( $receiver_email ), trim( $paypal_email ) ) !== 0 ) {
            wpinv_record_gateway_error( 'IPN Error', "IPN Response is for another account: {$receiver_email}. Your email is {$paypal_email}" );

			/* translators: %s: email address . */
			$invoice->update_status( 'wpi-processing', sprintf( __( 'Validation error: PayPal IPN response from a different email address (%s).', 'invoicing' ), $receiver_email ) );
			exit;
        }

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
        $business_email = isset( $posted['business'] ) && is_email( $posted['business'] ) ? trim( $posted['business'] ) : trim( $posted['receiver_email'] );

        $this->validate_ipn_receiver_email( $invoice, $business_email );
        $this->validate_ipn_currency( $invoice, $posted['mc_currency'] );

        // Update the transaction id.
        if ( ! empty( $posted['txn_id'] ) ) {
            $invoice->set_transaction_id( wpinv_clean( $posted['txn_id'] ) );
            $invoice->save();
        }

        // Process a refund.
        if ( $payment_status == 'refunded' || $payment_status == 'reversed' ) {

            update_post_meta( $invoice->get_id(), 'refunded_remotely', 1 );

            if ( ! $invoice->is_refunded() ) {
                $invoice->update_status( 'wpi-refunded', $posted['reason_code'] );
            }

            exit;
        }

        // Process payments.
        if ( $payment_status == 'completed' ) {

            if ( $invoice->is_paid() && 'wpi_processing' != $invoice->get_status() ) {
                wpinv_error_log( 'Aborting, Invoice #' . $invoice->get_number() . ' is already complete.' );
                exit;
            }

            $this->validate_ipn_amount( $invoice, $posted['mc_gross'] );

            if ( 'completed' === $payment_status || 'pending' === $payment_status ) {

                $note = '';

                if ( ! empty( $posted['mc_fee'] ) ) {
                    $note = sprintf( __( 'PayPal Transaction Fee %s', 'invoicing' ), wpinv_clean( $posted['mc_fee'] ) );
                }

                $invoice->mark_paid( ( ! empty( $posted['txn_id'] ) ? wpinv_clean( $posted['txn_id'] ) : '' ), $note );

            } else {

                /* translators: %s: pending reason. */
                $invoice->update_status( 'wpi_processing', sprintf( __( 'Payment pending (%s).', 'invoicing' ), $posted['pending_reason'] ) );

            }

            exit;

        }

        // Process failures.

        /* translators: %s: payment status. */
		$invoice->update_status( 'wpi-failed', sprintf( __( 'Payment %s via IPN.', 'invoicing' ), wpinv_clean( $posted['payment_status'] ) ) );

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

        // Make sure the invoice has a subscription.
        $subscription = wpinv_get_subscription( $invoice );

        if ( empty( $subscription ) ) {
            wpinv_error_log( 'Aborting, Subscription for the invoice ' . $invoice->get_id() . ' not found' );
        }

        // Update the subscription ids.
        $subscription->update(
            array(
                'profile_id' => sanitize_text_field( $posted['subscr_id'] ),
            )
        );

        // Set the transaction id.
        if ( ! empty( $posted['txn_id'] ) ) {
            $invoice->set_transaction_id( $posted['txn_id'] );
        }

        // Update the payment status.
        $invoice->mark_paid();

        $invoice->add_note( sprintf( __( 'PayPal Subscription ID: %s', 'invoicing' ) , $posted['subscr_id'] ), false, false, true );

        // Update the subscription duration.
        $duration = strtotime( $subscription->expiration ) - strtotime( $subscription->created );
        $subscription->update( 
            array(
                'status'     => 'trialling' == $subscription->status ? 'trialling' : 'active',
                'created'    => current_time( 'mysql' ),
                'expiration' => date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $duration ) ),
            )
        );

    }

    /**
	 * Handles subscription renewals.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_subscr_payment( $invoice, $posted ) {

        // Make sure the invoice has a subscription.
        $subscription = wpinv_get_subscription( $invoice );

        if ( empty( $subscription ) ) {
            wpinv_error_log( 'Aborting, Subscription for the invoice ' . $invoice->get_id() . ' not found' );
        }

        $this->validate_ipn_currency( $invoice, $posted['mc_currency'] );

        // Abort if the payment is already recorded.
        if ( wpinv_get_id_by_transaction_id( $posted['txn_id'] ) ) {
            return;
        }

        // Abort if this is the first payment.
        if ( date( 'Ynd', $subscription->created ) == date( 'Ynd', strtotime( $posted['payment_date'] ) ) ) {
            $invoice->set_transaction_id( $posted['txn_id'] );
            $invoice->save();
            return;
        }

        $args = array(
            'amount'         => $posted['mc_gross'],
            'transaction_id' => $posted['txn_id'],
            'gateway'        => $this->id,
        );

        $invoice = wpinv_get_invoice( $subscription->add_payment( $args ) );

        if ( empty( $invoice ) ) {
            return;
        }

        $invoice->add_note( wp_sprintf( __( 'PayPal Transaction ID: %s', 'invoicing' ) , $posted['txn_id'] ), false, false, true );
        $invoice->add_note( wp_sprintf( __( 'PayPal Subscription ID: %s', 'invoicing' ) , $posted['subscr_id'] ), false, false, true );

        $subscription->renew();

    }

    /**
	 * Handles subscription cancelations.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_subscr_cancel( $invoice, $posted ) {

        if ( $subscription = wpinv_get_subscription( $invoice ) ) {
            $subscription->cancel();
        }

    }

    /**
	 * Handles subscription completions.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_subscr_eot( $invoice, $posted ) {

        if ( $subscription = wpinv_get_subscription( $invoice ) ) {
            $subscription->complete();
        }

    }

    /**
	 * Handles subscription fails.
	 *
	 * @param WPInv_Invoice $invoice  Invoice object.
	 * @param array    $posted Posted data.
	 */
	protected function ipn_txn_subscr_failed( $invoice, $posted ) {

        if ( $subscription = wpinv_get_subscription( $invoice ) ) {
            $subscription->failing();
        }

    }

    /**
     * Displays a notice on the checkout page if sandbox is enabled.
     */
    public function sandbox_notice( $description, $gateway ) {
        if ( 'paypal' == $gateway && wpinv_is_test_mode( 'paypal' ) ) {
            $description .= '<br>' . sprintf(
                __( 'SANDBOX ENABLED. You can use sandbox testing accounts only. See the %sPayPal Sandbox Testing Guide%s for more details.', 'invoicing' ),
                '<a href="https://developer.paypal.com/docs/classic/lifecycle/ug_sandbox/">',
                '</a>'
            );
        }
        return $description;

    }

}
