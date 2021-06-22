<?php
/**
 * Authorize.net payment gateway
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Authorize.net Payment Gateway class.
 *
 */
class GetPaid_Authorize_Net_Gateway extends GetPaid_Authorize_Net_Legacy_Gateway {

    /**
	 * Payment method id.
	 *
	 * @var string
	 */
    public $id = 'authorizenet';

    /**
	 * An array of features that this gateway supports.
	 *
	 * @var array
	 */
    protected $supports = array( 'subscription', 'sandbox', 'tokens', 'addons', 'single_subscription_group', 'multiple_subscription_groups' );

    /**
	 * Payment method order.
	 *
	 * @var int
	 */
    public $order = 4;

    /**
	 * Endpoint for requests from Authorize.net.
	 *
	 * @var string
	 */
	protected $notify_url;

	/**
	 * Endpoint for requests to Authorize.net.
	 *
	 * @var string
	 */
    protected $endpoint;

    /**
	 * Currencies this gateway is allowed for.
	 *
	 * @var array
	 */
	public $currencies = array( 'USD', 'CAD', 'GBP', 'DKK', 'NOK', 'PLN', 'SEK', 'AUD', 'EUR', 'NZD' );

    /**
	 * URL to view a transaction.
	 *
	 * @var string
	 */
    public $view_transaction_url = 'https://{sandbox}authorize.net/ui/themes/sandbox/Transaction/TransactionReceipt.aspx?transid=%s';

    /**
	 * Class constructor.
	 */
	public function __construct() {

        $this->title                = __( 'Credit Card / Debit Card', 'invoicing' );
        $this->method_title         = __( 'Authorize.Net', 'invoicing' );
        $this->notify_url           = getpaid_get_non_query_string_ipn_url( $this->id );

        add_action( 'getpaid_should_renew_subscription', array( $this, 'maybe_renew_subscription' ) );
        add_filter( 'getpaid_authorizenet_sandbox_notice', array( $this, 'sandbox_notice' ) );
        parent::__construct();
    }

    /**
	 * Displays the payment method select field.
	 *
	 * @param int $invoice_id 0 or invoice id.
	 * @param GetPaid_Payment_Form $form Current payment form.
	 */
    public function payment_fields( $invoice_id, $form ) {

        // Let the user select a payment method.
        echo $this->saved_payment_methods();

        // Show the credit card entry form.
        echo $this->new_payment_method_entry( $this->get_cc_form( true ) );
    }

    /**
	 * Creates a customer profile.
	 *
	 *
	 * @param WPInv_Invoice $invoice Invoice.
     * @param array $submission_data Posted checkout fields.
     * @param bool $save Whether or not to save the payment as a token.
     * @link https://developer.authorize.net/api/reference/index.html#customer-profiles-create-customer-profile
	 * @return string|WP_Error Payment profile id.
	 */
	public function create_customer_profile( $invoice, $submission_data, $save = true ) {

        // Remove non-digits from the number
        $submission_data['authorizenet']['cc_number'] = preg_replace('/\D/', '', $submission_data['authorizenet']['cc_number'] );

        // Generate args.
        $args = array(
            'createCustomerProfileRequest' => array(
                'merchantAuthentication'   => $this->get_auth_params(),
                'profile'                  => array(
                    'merchantCustomerId'   => getpaid_limit_length( $invoice->get_user_id(), 20 ),
                    'description'          => getpaid_limit_length( $invoice->get_full_name(), 255 ),
                    'email'                => getpaid_limit_length( $invoice->get_email(), 255 ),
                    'paymentProfiles'      => array(
                        'customerType'     => 'individual',

                        // Billing information.
                        'billTo'           => array(
                            'firstName'    => getpaid_limit_length( $invoice->get_first_name(), 50 ),
                            'lastName'     => getpaid_limit_length( $invoice->get_last_name(), 50 ),
                            'address'      => getpaid_limit_length( $invoice->get_address(), 60 ),
                            'city'         => getpaid_limit_length( $invoice->get_city(), 40 ),
                            'state'        => getpaid_limit_length( $invoice->get_state(), 40 ),
                            'zip'          => getpaid_limit_length( $invoice->get_zip(), 20 ),
                            'country'      => getpaid_limit_length( $invoice->get_country(), 60 ),
                        ),

                        // Payment information.
                        'payment'          => $this->get_payment_information( $submission_data['authorizenet'] ),
                    )
                ),
                'validationMode'           => $this->is_sandbox( $invoice ) ? 'testMode' : 'liveMode',
            )
        );

        $response = $this->post( apply_filters( 'getpaid_authorizenet_customer_profile_args', $args, $invoice ), $invoice );

        if ( is_wp_error( $response ) ) {

            // In case the payment profile already exists remotely.
            if ( 'dup_payment_profile' == $response->get_error_code() ) {
                $customer_profile_id = strtok( $response->get_error_message(), '.' );
                update_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), $customer_profile_id );
                return strtok( '.' );
            }

            // In case the customer profile already exists remotely.
            if ( 'E00039' == $response->get_error_code() ) {
                $customer_profile_id = str_replace( 'A duplicate record with ID ', '', $response->get_error_message() );
                $customer_profile_id = str_replace( ' already exists.', '', $customer_profile_id );
                return $this->create_customer_payment_profile( trim( $customer_profile_id ), $invoice, $submission_data, $save );
            }

            return $response;
        }

        update_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), $response->customerProfileId );

        // Save the payment token.
        if ( $save ) {
            $this->save_token(
                array(
                    'id'      => $response->customerPaymentProfileIdList[0],
                    'name'    => getpaid_get_card_name( $submission_data['authorizenet']['cc_number'] ) . '&middot;&middot;&middot;&middot;' . substr( $submission_data['authorizenet']['cc_number'], -4 ),
                    'default' => true,
                    'type'    => $this->is_sandbox( $invoice ) ? 'sandbox' : 'live',
                )
            );
        }

        // Add a note about the validation response.
        $invoice->add_note(
            sprintf( __( 'Created Authorize.NET customer profile: %s', 'invoicing' ), $response->validationDirectResponseList[0] ),
            false,
            false,
            true
        );

        return $response->customerPaymentProfileIdList[0];
    }

    /**
	 * Retrieves a customer profile.
	 *
	 *
	 * @param string $profile_id profile id.
	 * @return string|WP_Error Profile id.
     * @link https://developer.authorize.net/api/reference/index.html#customer-profiles-get-customer-profile
	 */
	public function get_customer_profile( $profile_id ) {

        // Generate args.
        $args = array(
            'getCustomerProfileRequest'  => array(
                'merchantAuthentication' => $this->get_auth_params(),
                'customerProfileId'      => $profile_id,
            )
        );

        return $this->post( $args, false );

    }

    /**
	 * Creates a customer profile.
	 *
	 *
     * @param string $profile_id profile id.
	 * @param WPInv_Invoice $invoice Invoice.
     * @param array $submission_data Posted checkout fields.
     * @param bool $save Whether or not to save the payment as a token.
     * @link https://developer.authorize.net/api/reference/index.html#customer-profiles-create-customer-profile
	 * @return string|WP_Error Profile id.
	 */
	public function create_customer_payment_profile( $customer_profile, $invoice, $submission_data, $save ) {

        // Remove non-digits from the number
        $submission_data['authorizenet']['cc_number'] = preg_replace('/\D/', '', $submission_data['authorizenet']['cc_number'] );

        // Prepare card details.
        $payment_information                          = $this->get_payment_information( $submission_data['authorizenet'] );

        // Authorize.NET does not support saving the same card twice.
        $cached_information                           = $this->retrieve_payment_profile_from_cache( $payment_information, $customer_profile, $invoice );

        if ( $cached_information ) {
            return $cached_information;
        }

        // Generate args.
        $args = array(
            'createCustomerPaymentProfileRequest' => array(
                'merchantAuthentication'   => $this->get_auth_params(),
                'customerProfileId'        => $customer_profile,
                'paymentProfile'           => array(

                    // Billing information.
                    'billTo'           => array(
                        'firstName'    => getpaid_limit_length( $invoice->get_first_name(), 50 ),
                        'lastName'     => getpaid_limit_length( $invoice->get_last_name(), 50 ),
                        'address'      => getpaid_limit_length( $invoice->get_address(), 60 ),
                        'city'         => getpaid_limit_length( $invoice->get_city(), 40 ),
                        'state'        => getpaid_limit_length( $invoice->get_state(), 40 ),
                        'zip'          => getpaid_limit_length( $invoice->get_zip(), 20 ),
                        'country'      => getpaid_limit_length( $invoice->get_country(), 60 ),
                    ),

                    // Payment information.
                    'payment'          => $payment_information
                ),
                'validationMode'       => $this->is_sandbox( $invoice ) ? 'testMode' : 'liveMode',
            )
        );

        $response = $this->post( apply_filters( 'getpaid_authorizenet_create_customer_payment_profile_args', $args, $invoice ), $invoice );

        if ( is_wp_error( $response ) ) {

            // In case the payment profile already exists remotely.
            if ( 'dup_payment_profile' == $response->get_error_code() ) {
                $customer_profile_id = strtok( $response->get_error_message(), '.' );
                $payment_profile_id  = strtok( '.' );
                update_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), $customer_profile_id );

                // Cache payment profile id.
                $this->add_payment_profile_to_cache( $payment_information, $payment_profile_id );

                return $payment_profile_id;
            }

            return $response;
        }

        // Save the payment token.
        if ( $save ) {
            $this->save_token(
                array(
                    'id'      => $response->customerPaymentProfileId,
                    'name'    => getpaid_get_card_name( $submission_data['authorizenet']['cc_number'] ) . ' &middot;&middot;&middot;&middot; ' . substr( $submission_data['authorizenet']['cc_number'], -4 ),
                    'default' => true,
                    'type'    => $this->is_sandbox( $invoice ) ? 'sandbox' : 'live',
                )
            );
        }

        // Cache payment profile id.
        $this->add_payment_profile_to_cache( $payment_information, $response->customerPaymentProfileId );

        // Add a note about the validation response.
        $invoice->add_note(
            sprintf( __( 'Saved Authorize.NET payment profile: %s', 'invoicing' ), $response->validationDirectResponse ),
            false,
            false,
            true
        );

        update_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), $customer_profile );

        return $response->customerPaymentProfileId;
    }

    /**
	 * Retrieves payment details from cache.
	 *
	 *
     * @param array $payment_details.
	 * @return array|false Profile id.
	 */
	public function retrieve_payment_profile_from_cache( $payment_details, $customer_profile, $invoice ) {

        $cached_information = get_option( 'getpaid_authorize_net_cached_profiles', array() );
        $payment_details    = hash_hmac( 'sha256', json_encode( $payment_details ), SECURE_AUTH_KEY );

        if ( ! is_array( $cached_information ) || ! array_key_exists( $payment_details, $cached_information ) ) {
            return false;
        }

        // Generate args.
        $args = array(
            'getCustomerPaymentProfileRequest' => array(
                'merchantAuthentication'   => $this->get_auth_params(),
                'customerProfileId'        => $customer_profile,
                'customerPaymentProfileId' => $cached_information[ $payment_details ],
            )
        );

        $response = $this->post( $args, $invoice );

        return is_wp_error( $response ) ? false : $cached_information[ $payment_details ];

    }

    /**
	 * Securely adds payment details to cache.
	 *
	 *
     * @param array $payment_details.
     * @param string $payment_profile_id.
	 */
	public function add_payment_profile_to_cache( $payment_details, $payment_profile_id ) {

        $cached_information = get_option( 'getpaid_authorize_net_cached_profiles', array() );
        $cached_information = is_array( $cached_information ) ? $cached_information : array();
        $payment_details    = hash_hmac( 'sha256', json_encode( $payment_details ), SECURE_AUTH_KEY );

        $cached_information[ $payment_details ] = $payment_profile_id;
        update_option( 'getpaid_authorize_net_cached_profiles', $cached_information );

    }

    /**
	 * Retrieves a customer payment profile.
	 *
	 *
	 * @param string $customer_profile_id customer profile id.
     * @param string $payment_profile_id payment profile id.
	 * @return string|WP_Error Profile id.
     * @link https://developer.authorize.net/api/reference/index.html#customer-profiles-get-customer-payment-profile
	 */
	public function get_customer_payment_profile( $customer_profile_id, $payment_profile_id ) {

        // Generate args.
        $args = array(
            'getCustomerPaymentProfileRequest' => array(
                'merchantAuthentication'       => $this->get_auth_params(),
                'customerProfileId'            => $customer_profile_id,
                'customerPaymentProfileId'     => $payment_profile_id,
            )
        );

        return $this->post( $args, false );

    }

    /**
	 * Charges a customer payment profile.
	 *
     * @param string $customer_profile_id customer profile id.
     * @param string $payment_profile_id payment profile id.
	 * @param WPInv_Invoice $invoice Invoice.
     * @link https://developer.authorize.net/api/reference/index.html#payment-transactions-charge-a-customer-profile
	 * @return WP_Error|object
	 */
	public function charge_customer_payment_profile( $customer_profile_id, $payment_profile_id, $invoice ) {

        // Generate args.
        $args = array(

            'createTransactionRequest'         => array(

                'merchantAuthentication'       => $this->get_auth_params(),
                'refId'                        => $invoice->get_id(),
                'transactionRequest'           => array(
                    'transactionType'          => 'authCaptureTransaction',
                    'amount'                   => $invoice->get_total(),
                    'currencyCode'             => $invoice->get_currency(),
                    'profile'                  => array(
                        'customerProfileId'    => $customer_profile_id,
                        'paymentProfile'       => array(
                            'paymentProfileId' => $payment_profile_id,
                        )
                    ),
                    'order'                    => array(
                        'invoiceNumber'        => getpaid_limit_length( $invoice->get_number(), 20 ),
                    ),
                    'lineItems'                => array( 'lineItem' => $this->get_line_items( $invoice ) ),
                    'tax'                      => array(
                        'amount'               => $invoice->get_total_tax(),
                        'name'                 => __( 'TAX', 'invoicing' ),
                    ),
                    'poNumber'                 => getpaid_limit_length( $invoice->get_number(), 25 ),
                    'customer'                 => array(
                        'id'                   => getpaid_limit_length( $invoice->get_user_id(), 25 ),
                        'email'                => getpaid_limit_length( $invoice->get_email(), 25 ),
                    ),
                    'customerIP'               => $invoice->get_ip(),
                )
            )
        );

        if ( 0 == $invoice->get_total_tax() ) {
            unset( $args['createTransactionRequest']['transactionRequest']['tax'] );
        }

        return $this->post( apply_filters( 'getpaid_authorizenet_charge_customer_payment_profile_args', $args, $invoice ), $invoice );

    }

    /**
	 * Processes a customer charge.
	 *
     * @param stdClass $result Api response.
	 * @param WPInv_Invoice $invoice Invoice.
	 */
	public function process_charge_response( $result, $invoice ) {

        wpinv_clear_errors();
		$response_code = (int) $result->transactionResponse->responseCode;

		// Succeeded.
		if ( 1 == $response_code || 4 == $response_code ) {

			// Maybe set a transaction id.
			if ( ! empty( $result->transactionResponse->transId ) ) {
				$invoice->set_transaction_id( $result->transactionResponse->transId );
			}

			$invoice->add_note( sprintf( __( 'Authentication code: %s (%s).', 'invoicing' ), $result->transactionResponse->authCode, $result->transactionResponse->accountNumber ), false, false, true );

			if ( 1 == $response_code ) {
				return $invoice->mark_paid();
			}

			$invoice->set_status( 'wpi-onhold' );
        	$invoice->add_note(
                sprintf(
                    __( 'Held for review: %s', 'invoicing' ),
                    $result->transactionResponse->messages->message[0]->description
                )
			);

			return $invoice->save();

		}

        wpinv_set_error( 'card_declined', __( 'Credit card declined.', 'invoicing' ) );

        if ( ! empty( $result->transactionResponse->errors ) ) {
            $errors = (object) $result->transactionResponse->errors;
            wpinv_set_error( $errors->error[0]->errorCode, esc_html( $errors->error[0]->errorText ) );
        }

    }

    /**
	 * Returns payment information.
	 *
	 *
	 * @param array $card Card details.
	 * @return array
	 */
	public function get_payment_information( $card ) {
        return array(

            'creditCard'         => array (
                'cardNumber'     => $card['cc_number'],
                'expirationDate' => $card['cc_expire_year'] . '-' . $card['cc_expire_month'],
                'cardCode'       => $card['cc_cvv2'],
            )

        );
    }

    /**
	 * Returns the customer profile meta name.
	 *
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @return string
	 */
	public function get_customer_profile_meta_name( $invoice ) {
        return $this->is_sandbox( $invoice ) ? 'getpaid_authorizenet_sandbox_customer_profile_id' : 'getpaid_authorizenet_customer_profile_id';
    }

    /**
	 * Validates the submitted data.
	 *
	 *
	 * @param array $submission_data Posted checkout fields.
     * @param WPInv_Invoice $invoice
	 * @return WP_Error|string The payment profile id
	 */
	public function validate_submission_data( $submission_data, $invoice ) {

        // Validate authentication details.
        $auth = $this->get_auth_params();

        if ( empty( $auth['name'] ) || empty( $auth['transactionKey'] ) ) {
            return new WP_Error( 'invalid_settings', __( 'Please set-up your login id and transaction key before using this gateway.', 'invoicing') );
        }

        // Validate the payment method.
        if ( empty( $submission_data['getpaid-authorizenet-payment-method'] ) ) {
            return new WP_Error( 'invalid_payment_method', __( 'Please select a different payment method or add a new card.', 'invoicing') );
        }

        // Are we adding a new payment method?
        if ( 'new' != $submission_data['getpaid-authorizenet-payment-method'] ) {
            return $submission_data['getpaid-authorizenet-payment-method'];
        }

        // Retrieve the customer profile id.
        $profile_id = get_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), true );

        // Create payment method.
        if ( empty( $profile_id ) ) {
            return $this->create_customer_profile( $invoice, $submission_data, ! empty( $submission_data['getpaid-authorizenet-new-payment-method'] ) );
        }

        return $this->create_customer_payment_profile( $profile_id, $invoice, $submission_data, ! empty( $submission_data['getpaid-authorizenet-new-payment-method'] ) );

    }

    /**
	 * Returns invoice line items.
	 *
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @return array
	 */
	public function get_line_items( $invoice ) {
        $items = array();

        foreach ( $invoice->get_items() as $item ) {

            $amount  = $invoice->is_renewal() ? $item->get_price() : $item->get_initial_price();
            $items[] = array(
                'itemId'      => getpaid_limit_length( $item->get_id(), 31 ),
                'name'        => getpaid_limit_length( $item->get_raw_name(), 31 ),
                'description' => getpaid_limit_length( $item->get_description(), 255 ),
                'quantity'    => (string) ( $invoice->get_template() == 'amount' ? 1 : $item->get_quantity() ),
                'unitPrice'   => (float) $amount,
                'taxable'     => wpinv_use_taxes() && $invoice->is_taxable() && 'tax-exempt' != $item->get_vat_rule(),
            );

        }

        foreach ( $invoice->get_fees() as $fee_name => $fee ) {

            $amount  = $invoice->is_renewal() ? $fee['recurring_fee'] : $fee['initial_fee'];

            if ( $amount > 0 ) {
                $items[] = array(
                    'itemId'      => getpaid_limit_length( $fee_name, 31 ),
                    'name'        => getpaid_limit_length( $fee_name, 31 ),
                    'description' => getpaid_limit_length( $fee_name, 255 ),
                    'quantity'    => '1',
                    'unitPrice'   => (float) $amount,
                    'taxable'     => false,
                );
            }

        }

        return $items;
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

        // Validate the submitted data.
        $payment_profile_id = $this->validate_submission_data( $submission_data, $invoice );

        // Do we have an error?
        if ( is_wp_error( $payment_profile_id ) ) {
            wpinv_set_error( $payment_profile_id->get_error_code(), $payment_profile_id->get_error_message() );
            wpinv_send_back_to_checkout( $invoice );
        }

        // Save the payment method to the order.
        update_post_meta( $invoice->get_id(), 'getpaid_authorizenet_profile_id', $payment_profile_id );

        // Check if this is a subscription or not.
        $subscriptions = getpaid_get_invoice_subscriptions( $invoice );
        if ( ! empty( $subscriptions ) ) {
            $this->process_subscription( $invoice, $subscriptions );
        }

        // If it is free, send to the success page.
        if ( ! $invoice->needs_payment() ) {
            $invoice->mark_paid();
            wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
        }

        // Charge the payment profile.
        $this->process_initial_payment( $invoice );

        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

        exit;

	}
	
	/**
	 * Processes the initial payment.
	 *
     * @param WPInv_Invoice $invoice Invoice.
	 */
	protected function process_initial_payment( $invoice ) {

		$payment_profile_id = get_post_meta( $invoice->get_id(), 'getpaid_authorizenet_profile_id', true );
        $customer_profile   = get_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), true );
		$result             = $this->charge_customer_payment_profile( $customer_profile, $payment_profile_id, $invoice );

		// Do we have an error?
		if ( is_wp_error( $result ) ) {
			wpinv_set_error( $result->get_error_code(), $result->get_error_message() );
			wpinv_send_back_to_checkout( $invoice );
		}

		// Process the response.
		$this->process_charge_response( $result, $invoice );

		if ( wpinv_get_errors() ) {
			wpinv_send_back_to_checkout( $invoice );
		}

	}

    /**
	 * Processes recurring payments.
	 *
     * @param WPInv_Invoice $invoice Invoice.
     * @param WPInv_Subscription[]|WPInv_Subscription $subscriptions Subscriptions.
	 */
	public function process_subscription( $invoice, $subscriptions ) {

        // Check if there is an initial amount to charge.
        if ( (float) $invoice->get_total() > 0 ) {
			$this->process_initial_payment( $invoice );
        }

        // Activate the subscriptions.
        $subscriptions = is_array( $subscriptions ) ? $subscriptions : array( $subscriptions );

        foreach ( $subscriptions as $subscription ) {
            if ( $subscription->exists() ) {
                $duration = strtotime( $subscription->get_expiration() ) - strtotime( $subscription->get_date_created() );
                $expiry   = date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $duration ) );

                $subscription->set_next_renewal_date( $expiry );
                $subscription->set_date_created( current_time( 'mysql' ) );
                $subscription->set_profile_id( $invoice->generate_key( 'authnet_sub_' . $invoice->get_id() . '_' . $subscription->get_id() ) );
                $subscription->activate();
            }
        }

		// Redirect to the success page.
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

    }

	/**
	 * (Maybe) renews an authorize.net subscription profile.
	 *
	 *
     * @param WPInv_Subscription $subscription
	 */
	public function maybe_renew_subscription( $subscription ) {

        // Ensure its our subscription && it's active.
        if ( $this->id == $subscription->get_gateway() && $subscription->has_status( 'active trialling' ) ) {
            $this->renew_subscription( $subscription );
        }

	}

    /**
	 * Renews a subscription.
	 *
     * @param WPInv_Subscription $subscription
	 */
	public function renew_subscription( $subscription ) {

		// Generate the renewal invoice.
		$new_invoice = $subscription->create_payment();
		$old_invoice = $subscription->get_parent_payment();

        if ( empty( $new_invoice ) ) {
            $old_invoice->add_note( __( 'Error generating a renewal invoice.', 'invoicing' ), false, false, false );
            $subscription->failing();
            return;
        }

        // Charge the payment method.
		$payment_profile_id = get_post_meta( $old_invoice->get_id(), 'getpaid_authorizenet_profile_id', true );
		$customer_profile   = get_user_meta( $old_invoice->get_user_id(), $this->get_customer_profile_meta_name( $old_invoice ), true );
		$result             = $this->charge_customer_payment_profile( $customer_profile, $payment_profile_id, $new_invoice );

		// Do we have an error?
		if ( is_wp_error( $result ) ) {

			$old_invoice->add_note(
				sprintf( __( 'Error renewing subscription : ( %s ).', 'invoicing' ), $result->get_error_message() ),
				true,
				false,
				true
			);
			$subscription->failing();
			return;

		}

		// Process the response.
		$this->process_charge_response( $result, $new_invoice );

		if ( wpinv_get_errors() ) {

			$old_invoice->add_note(
				sprintf( __( 'Error renewing subscription : ( %s ).', 'invoicing' ), getpaid_get_errors_html() ),
				true,
				false,
				true
			);
			$subscription->failing();
			return;

        }

        $subscription->add_payment( array(), $new_invoice );
        $subscription->renew();
    }

    /**
	 * Processes invoice addons.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param GetPaid_Form_Item[] $items
	 * @return WPInv_Invoice
	 */
	public function process_addons( $invoice, $items ) {

        global $getpaid_authorize_addons;

        $getpaid_authorize_addons = array();
        foreach ( $items as $item ) {

            if ( is_null( $invoice->get_item( $item->get_id() ) ) && ! is_wp_error( $invoice->add_item( $item ) ) ) {
                $getpaid_authorize_addons[] = $item;
            }

        }

        if ( empty( $getpaid_authorize_addons ) ) {
            return;
        }

        $invoice->recalculate_total();

        $payment_profile_id = get_post_meta( $invoice->get_id(), 'getpaid_authorizenet_profile_id', true );
		$customer_profile   = get_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), true );

        add_filter( 'getpaid_authorizenet_charge_customer_payment_profile_args', array( $this, 'filter_addons_request' ), 10, 2 );
        $result = $this->charge_customer_payment_profile( $customer_profile, $payment_profile_id, $invoice );
        remove_filter( 'getpaid_authorizenet_charge_customer_payment_profile_args', array( $this, 'filter_addons_request' ) );

        if ( is_wp_error( $result ) ) {
            wpinv_set_error( $result->get_error_code(), $result->get_error_message() );
            return;
        }

        $invoice->save();
    }

    /**
	 * Processes invoice addons.
	 *
     * @param array $args
	 * @return array
	 */
    public function filter_addons_request( $args ) {

        global $getpaid_authorize_addons;
        $total = 0;

        foreach ( $getpaid_authorize_addons as $addon ) {
            $total += $addon->get_sub_total();
        }

        $args['createTransactionRequest']['transactionRequest']['amount'] = $total;

        if ( isset( $args['createTransactionRequest']['transactionRequest']['tax'] ) ) {
            unset( $args['createTransactionRequest']['transactionRequest']['tax'] );
        }

        return $args;

    }

    /**
     * Displays a notice on the checkout page if sandbox is enabled.
     */
    public function sandbox_notice() {

        return sprintf(
            __( 'SANDBOX ENABLED. You can use sandbox testing details only. See the %sAuthorize.NET Sandbox Testing Guide%s for more details.', 'invoicing' ),
            '<a href="https://developer.authorize.net/hello_world/testing_guide.html">',
            '</a>'
        );

    }

    /**
	 * Filters the gateway settings.
	 *
	 * @param array $admin_settings
	 */
	public function admin_settings( $admin_settings ) {

        $currencies = sprintf(
            __( 'Supported Currencies: %s', 'invoicing' ),
            implode( ', ', $this->currencies )
        );

        $admin_settings['authorizenet_active']['desc'] .= " ($currencies)";
        $admin_settings['authorizenet_desc']['std']     = __( 'Pay securely using your credit or debit card.', 'invoicing' );

        $admin_settings['authorizenet_login_id'] = array(
            'type' => 'text',
            'id'   => 'authorizenet_login_id',
            'name' => __( 'API Login ID', 'invoicing' ),
            'desc' => '<a href="https://support.authorize.net/s/article/How-do-I-obtain-my-API-Login-ID-and-Transaction-Key"><em>' . __( 'How do I obtain my API Login ID and Transaction Key?', 'invoicing' ) . '</em></a>',
        );

        $admin_settings['authorizenet_transaction_key'] = array(
            'type' => 'text',
            'id'   => 'authorizenet_transaction_key',
            'name' => __( 'Transaction Key', 'invoicing' ),
        );

        $admin_settings['authorizenet_signature_key'] = array(
            'type' => 'text',
            'id'   => 'authorizenet_signature_key',
            'name' => __( 'Signature Key', 'invoicing' ),
            'desc' => '<a href="https://support.authorize.net/s/article/What-is-a-Signature-Key"><em>' . __( 'Learn more.', 'invoicing' ) . '</em></a>',
        );

        $admin_settings['authorizenet_ipn_url'] = array(
            'type'     => 'ipn_url',
            'id'       => 'authorizenet_ipn_url',
            'name'     => __( 'Webhook URL', 'invoicing' ),
            'std'      => $this->notify_url,
            'desc'     => __( 'Create a new webhook using this URL as the endpoint URL and set it to receive all payment events.', 'invoicing' ) . ' <a href="https://support.authorize.net/s/article/How-do-I-add-edit-Webhook-notification-end-points"><em>' . __( 'Learn more.', 'invoicing' ) . '</em></a>',
            'custom'   => 'authorizenet',
            'readonly' => true,
        );

		return $admin_settings;
	}

}
