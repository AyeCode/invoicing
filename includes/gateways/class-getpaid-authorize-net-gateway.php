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
    protected $supports = array(
        'subscription',
        'sandbox',
        'tokens',
        'addons',
        'single_subscription_group',
        'multiple_subscription_groups',
        'subscription_date_change',
        'subscription_bill_times_change',
    );

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
	 * Currencies ACH payments are allowed for.
	 *
	 * @var array
	 */
	public $ach_currencies = array( 'USD' );

	/**
	 * ACH account types.
	 *
	 * @var array
	 */
	protected $ach_account_types = array(
		'checking'         => 'Checking',
		'savings'          => 'Savings',
		'businessChecking' => 'Business Checking',
	);

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

        add_action( 'getpaid_should_renew_subscription', array( $this, 'maybe_renew_subscription' ), 11, 2 );
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

		$ach_enabled       = wpinv_get_option( 'authorizenet_enable_ach' );
		$show_type_selector = $ach_enabled && $this->is_ach_available();

		// Payment type selector (CC vs ACH).
		if ( $show_type_selector ) {
			$this->render_payment_type_selector();
		}

		// Credit Card Section.
		echo '<div class="getpaid-authorizenet-cc-section">';
		$this->saved_payment_methods_by_type( 'card' );
		$this->new_payment_method_entry( $this->get_cc_form( true ) );
		echo '</div>';

		// ACH Section.
		if ( $show_type_selector ) {
			echo '<div class="getpaid-authorizenet-ach-section" style="display:none;">';
			$this->saved_payment_methods_by_type( 'ach' );
			$this->new_payment_method_entry( $this->get_ach_form( true ) );
			echo '</div>';
		}
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

		// Determine payment type.
		$is_ach_payment = $this->is_ach_payment( $submission_data['authorizenet'] );

		// Remove non-digits from the card/account number.
		if ( $is_ach_payment ) {
			$submission_data['authorizenet']['ach_routing_number'] = preg_replace( '/\D/', '', $submission_data['authorizenet']['ach_routing_number'] );
			$submission_data['authorizenet']['ach_account_number'] = preg_replace( '/\D/', '', $submission_data['authorizenet']['ach_account_number'] );
		} else {
			$submission_data['authorizenet']['cc_number'] = preg_replace( '/\D/', '', $submission_data['authorizenet']['cc_number'] );
		}

        // Generate args.
        $args = array(
            'createCustomerProfileRequest' => array(
                'merchantAuthentication' => $this->get_auth_params(),
                'profile'                => array(
                    'merchantCustomerId' => getpaid_limit_length( $invoice->get_user_id(), 20 ),
                    'description'        => getpaid_limit_length( $invoice->get_full_name(), 255 ),
                    'email'              => getpaid_limit_length( $invoice->get_email(), 255 ),
                    'paymentProfiles'    => array(
                        'customerType' => 'individual',

                        // Billing information.
                        'billTo'       => array(
                            'firstName' => getpaid_limit_length( $invoice->get_first_name(), 50 ),
                            'lastName'  => getpaid_limit_length( $invoice->get_last_name(), 50 ),
                            'address'   => getpaid_limit_length( $invoice->get_address(), 60 ),
                            'city'      => getpaid_limit_length( $invoice->get_city(), 40 ),
                            'state'     => getpaid_limit_length( $invoice->get_state(), 40 ),
                            'zip'       => getpaid_limit_length( $invoice->get_zip(), 20 ),
                            'country'   => getpaid_limit_length( $invoice->get_country(), 60 ),
                        ),

                        // Payment information.
                        'payment'      => $this->get_payment_information( $submission_data['authorizenet'] ),
                    ),
                ),
                'validationMode'         => $this->is_sandbox( $invoice ) ? 'testMode' : 'liveMode',
            ),
        );

        $response = $this->post( apply_filters( 'getpaid_authorizenet_customer_profile_args', $args, $invoice ), $invoice );

        if ( is_wp_error( $response ) ) {

            // In case the payment profile already exists remotely.
            if ( 'dup_payment_profile' === $response->get_error_code() ) {
                $customer_profile_id = strtok( $response->get_error_message(), '.' );
                update_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), $customer_profile_id );
                return strtok( '.' );
            }

            // In case the customer profile already exists remotely.
            if ( 'E00039' === $response->get_error_code() ) {
                $customer_profile_id = str_replace( 'A duplicate record with ID ', '', $response->get_error_message() );
                $customer_profile_id = str_replace( ' already exists.', '', $customer_profile_id );
                return $this->create_customer_payment_profile( trim( $customer_profile_id ), $invoice, $submission_data, $save );
            }

            return $response;
        }

        update_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), $response->customerProfileId );

        // Save the payment token.
        if ( $save ) {
			if ( $is_ach_payment ) {
				$token_name = $this->get_ach_token_name( $submission_data['authorizenet'] );
			} else {
				$token_name = getpaid_get_card_name( $submission_data['authorizenet']['cc_number'] ) . '&middot;&middot;&middot;&middot;' . substr( $submission_data['authorizenet']['cc_number'], -4 );
			}

            $this->save_token(
                array(
                    'id'             => $response->customerPaymentProfileIdList[0],
                    'name'           => $token_name,
                    'default'        => true,
                    'type'           => $this->is_sandbox( $invoice ) ? 'sandbox' : 'live',
					'payment_method' => $is_ach_payment ? 'ach' : 'card',
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
            'getCustomerProfileRequest' => array(
                'merchantAuthentication' => $this->get_auth_params(),
                'customerProfileId'      => $profile_id,
            ),
        );

        return $this->post( $args, false );

    }

    /**
	 * Creates a customer payment profile.
	 *
	 *
     * @param string $profile_id profile id.
	 * @param WPInv_Invoice $invoice Invoice.
     * @param array $submission_data Posted checkout fields.
     * @param bool $save Whether or not to save the payment as a token.
     * @link https://developer.authorize.net/api/reference/index.html#customer-profiles-create-customer-payment-profile
	 * @return string|WP_Error Profile id.
	 */
	public function create_customer_payment_profile( $customer_profile, $invoice, $submission_data, $save ) {

		// Determine payment type.
		$is_ach_payment = $this->is_ach_payment( $submission_data['authorizenet'] );

		// Remove non-digits from the card/account number.
		if ( $is_ach_payment ) {
			$submission_data['authorizenet']['ach_routing_number'] = preg_replace( '/\D/', '', $submission_data['authorizenet']['ach_routing_number'] );
			$submission_data['authorizenet']['ach_account_number'] = preg_replace( '/\D/', '', $submission_data['authorizenet']['ach_account_number'] );
		} else {
			$submission_data['authorizenet']['cc_number'] = preg_replace( '/\D/', '', $submission_data['authorizenet']['cc_number'] );
		}

        // Prepare payment details.
        $payment_information = $this->get_payment_information( $submission_data['authorizenet'] );

        // Authorize.NET does not support saving the same payment method twice.
        $cached_information = $this->retrieve_payment_profile_from_cache( $payment_information, $customer_profile, $invoice );

        if ( $cached_information ) {
            return $cached_information;
        }

        // Generate args.
        $args = array(
            'createCustomerPaymentProfileRequest' => array(
                'merchantAuthentication' => $this->get_auth_params(),
                'customerProfileId'      => $customer_profile,
                'paymentProfile'         => array(

                    // Billing information.
                    'billTo'  => array(
                        'firstName' => getpaid_limit_length( $invoice->get_first_name(), 50 ),
                        'lastName'  => getpaid_limit_length( $invoice->get_last_name(), 50 ),
                        'address'   => getpaid_limit_length( $invoice->get_address(), 60 ),
                        'city'      => getpaid_limit_length( $invoice->get_city(), 40 ),
                        'state'     => getpaid_limit_length( $invoice->get_state(), 40 ),
                        'zip'       => getpaid_limit_length( $invoice->get_zip(), 20 ),
                        'country'   => getpaid_limit_length( $invoice->get_country(), 60 ),
                    ),

                    // Payment information.
                    'payment' => $payment_information,
                ),
                'validationMode'         => $this->is_sandbox( $invoice ) ? 'testMode' : 'liveMode',
            ),
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
			if ( $is_ach_payment ) {
				$token_name = $this->get_ach_token_name( $submission_data['authorizenet'] );
			} else {
				$token_name = getpaid_get_card_name( $submission_data['authorizenet']['cc_number'] ) . ' &middot;&middot;&middot;&middot; ' . substr( $submission_data['authorizenet']['cc_number'], -4 );
			}

            $this->save_token(
                array(
                    'id'             => $response->customerPaymentProfileId,
                    'name'           => $token_name,
                    'default'        => true,
                    'type'           => $this->is_sandbox( $invoice ) ? 'sandbox' : 'live',
					'payment_method' => $is_ach_payment ? 'ach' : 'card',
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
            ),
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
                'merchantAuthentication'   => $this->get_auth_params(),
                'customerProfileId'        => $customer_profile_id,
                'customerPaymentProfileId' => $payment_profile_id,
            ),
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

            'createTransactionRequest' => array(

                'merchantAuthentication' => $this->get_auth_params(),
                'refId'                  => $invoice->get_id(),
                'transactionRequest'     => array(
                    'transactionType' => 'authCaptureTransaction',
                    'amount'          => $invoice->get_total(),
                    'currencyCode'    => $invoice->get_currency(),
                    'profile'         => array(
                        'customerProfileId' => $customer_profile_id,
                        'paymentProfile'    => array(
                            'paymentProfileId' => $payment_profile_id,
                        ),
                    ),
                    'order'           => array(
                        'invoiceNumber' => getpaid_limit_length( $invoice->get_number(), 20 ),
                    ),
                    'lineItems'       => array( 'lineItem' => $this->get_line_items( $invoice ) ),
                    'tax'             => array(
                        'amount' => $invoice->get_total_tax(),
                        'name'   => __( 'TAX', 'invoicing' ),
                    ),
                    'poNumber'        => getpaid_limit_length( $invoice->get_number(), 25 ),
                    'customer'        => array(
                        'id'    => getpaid_limit_length( $invoice->get_user_id(), 25 ),
                        'email' => getpaid_limit_length( $invoice->get_email(), 25 ),
                    ),
                    'customerIP'      => $invoice->get_ip(),
                ),
            ),
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

        $invoice->add_note( 'Transaction Response: ' . print_r( $result->transactionResponse, true ), false, false, true );

		// Succeeded.
		if ( 1 == $response_code || 4 == $response_code ) {

			// Maybe set a transaction id.
			if ( ! empty( $result->transactionResponse->transId ) ) {
				$invoice->set_transaction_id( $result->transactionResponse->transId );
			}

			$invoice->add_note( sprintf( __( 'Authentication code: %1$s (%2$s).', 'invoicing' ), $result->transactionResponse->authCode, $result->transactionResponse->accountNumber ), false, false, true );

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

        wpinv_set_error( 'card_declined' );

        if ( ! empty( $result->transactionResponse->errors ) ) {
            $errors = (object) $result->transactionResponse->errors;
            wpinv_set_error( $errors->error[0]->errorCode, esc_html( $errors->error[0]->errorText ) );
        }

    }

    /**
	 * Returns payment information.
	 *
	 *
	 * @param array $data Payment form data (card or ACH details).
	 * @return array
	 */
	public function get_payment_information( $data ) {
		// Check if this is an ACH payment.
		if ( $this->is_ach_payment( $data ) ) {
			return $this->get_ach_payment_information( $data );
		}

		// Default to credit card.
		return array(
			'creditCard' => array(
				'cardNumber'     => $data['cc_number'],
				'expirationDate' => $data['cc_expire_year'] . '-' . $data['cc_expire_month'],
				'cardCode'       => $data['cc_cvv2'],
			),
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
	 * Checks if ACH payments are available for the current currency.
	 *
	 * @return bool
	 */
	public function is_ach_available() {
		$currency = wpinv_get_currency();
		return in_array( $currency, $this->ach_currencies, true );
	}

	/**
	 * Checks if the payment data is for an ACH payment.
	 *
	 * @param array $data Payment data.
	 * @return bool
	 */
	public function is_ach_payment( $data ) {
		return isset( $data['payment_type'] ) && 'ach' === $data['payment_type'];
	}

	/**
	 * Generates a display name for an ACH token.
	 *
	 * @param array $data ACH form data.
	 * @return string
	 */
	public function get_ach_token_name( $data ) {
		$account_type   = isset( $data['ach_account_type'] ) ? $data['ach_account_type'] : 'checking';
		$account_number = preg_replace( '/\D/', '', isset( $data['ach_account_number'] ) ? $data['ach_account_number'] : '' );
		$last_four      = substr( $account_number, -4 );

		$type_labels = array(
			'checking'         => __( 'Checking', 'invoicing' ),
			'savings'          => __( 'Savings', 'invoicing' ),
			'businessChecking' => __( 'Business Checking', 'invoicing' ),
		);

		$type_label = isset( $type_labels[ $account_type ] ) ? $type_labels[ $account_type ] : __( 'Bank Account', 'invoicing' );

		return $type_label . ' &middot;&middot;&middot;&middot;' . $last_four;
	}

	/**
	 * Returns the ACH/eCheck form HTML.
	 *
	 * @param bool $save Whether to display the save checkbox.
	 * @return string
	 */
	public function get_ach_form( $save = false ) {
		ob_start();

		$id_prefix = esc_attr( uniqid( $this->id . '_ach_' ) );
		?>
		<div class="<?php echo esc_attr( $this->id ); ?>-ach-form getpaid-ach-form mt-1">
			<div class="getpaid-ach-inner">

				<!-- Account Type -->
				<div class="form-group mb-3">
					<label for="<?php echo esc_attr( $id_prefix . '-account-type' ); ?>">
						<?php esc_html_e( 'Account Type', 'invoicing' ); ?>
					</label>
					<select name="<?php echo esc_attr( $this->id . '[ach_account_type]' ); ?>"
							id="<?php echo esc_attr( $id_prefix . '-account-type' ); ?>"
							class="form-control form-control-sm">
						<?php foreach ( $this->ach_account_types as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>">
								<?php echo esc_html( __( $label, 'invoicing' ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Routing Number -->
				<div class="form-group mb-3">
					<label for="<?php echo esc_attr( $id_prefix . '-routing-number' ); ?>">
						<?php esc_html_e( 'Routing Number', 'invoicing' ); ?>
					</label>
					<input type="text"
						   name="<?php echo esc_attr( $this->id . '[ach_routing_number]' ); ?>"
						   id="<?php echo esc_attr( $id_prefix . '-routing-number' ); ?>"
						   class="form-control form-control-sm getpaid-ach-routing-number"
						   autocomplete="off"
						   maxlength="9"
						   placeholder="<?php esc_attr_e( '9-digit routing number', 'invoicing' ); ?>">
				</div>

				<!-- Account Number -->
				<div class="form-group mb-3">
					<label for="<?php echo esc_attr( $id_prefix . '-account-number' ); ?>">
						<?php esc_html_e( 'Account Number', 'invoicing' ); ?>
					</label>
					<input type="text"
						   name="<?php echo esc_attr( $this->id . '[ach_account_number]' ); ?>"
						   id="<?php echo esc_attr( $id_prefix . '-account-number' ); ?>"
						   class="form-control form-control-sm getpaid-ach-account-number"
						   autocomplete="off"
						   maxlength="17"
						   placeholder="<?php esc_attr_e( 'Bank account number', 'invoicing' ); ?>">
				</div>

				<!-- Name on Account -->
				<div class="form-group mb-3">
					<label for="<?php echo esc_attr( $id_prefix . '-name-on-account' ); ?>">
						<?php esc_html_e( 'Name on Account', 'invoicing' ); ?>
					</label>
					<input type="text"
						   name="<?php echo esc_attr( $this->id . '[ach_name_on_account]' ); ?>"
						   id="<?php echo esc_attr( $id_prefix . '-name-on-account' ); ?>"
						   class="form-control form-control-sm"
						   autocomplete="off"
						   maxlength="22"
						   placeholder="<?php esc_attr_e( 'Name as it appears on account', 'invoicing' ); ?>">
				</div>

				<?php if ( $save ) : ?>
					<?php $this->save_payment_method_checkbox(); ?>
				<?php endif; ?>

				<!-- ACH Authorization Notice -->
				<div class="alert alert-info mt-2 mb-0 small p-2">
					<?php esc_html_e( 'By providing your bank account information, you authorize us to debit your account for the amount shown.', 'invoicing' ); ?>
				</div>

			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Renders the payment type selector (Credit Card vs Bank Account).
	 */
	public function render_payment_type_selector() {
		?>
		<div class="getpaid-authorizenet-payment-type-selector mb-3">
			<div class="form-group mb-2">
				<label class="d-block mb-2 font-weight-bold"><?php esc_html_e( 'Payment Type', 'invoicing' ); ?></label>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="<?php echo esc_attr( $this->id ); ?>[payment_type]"
						   id="<?php echo esc_attr( $this->id ); ?>-payment-type-card" value="card" checked>
					<label class="form-check-label" for="<?php echo esc_attr( $this->id ); ?>-payment-type-card">
						<?php esc_html_e( 'Credit/Debit Card', 'invoicing' ); ?>
					</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="<?php echo esc_attr( $this->id ); ?>[payment_type]"
						   id="<?php echo esc_attr( $this->id ); ?>-payment-type-ach" value="ach">
					<label class="form-check-label" for="<?php echo esc_attr( $this->id ); ?>-payment-type-ach">
						<?php esc_html_e( 'Bank Account (ACH)', 'invoicing' ); ?>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Returns ACH payment information for the API.
	 *
	 * @param array $data Payment data.
	 * @return array
	 */
	public function get_ach_payment_information( $data ) {
		$bank_account = array(
			'accountType'   => sanitize_text_field( isset( $data['ach_account_type'] ) ? $data['ach_account_type'] : 'checking' ),
			'routingNumber' => preg_replace( '/\D/', '', isset( $data['ach_routing_number'] ) ? $data['ach_routing_number'] : '' ),
			'accountNumber' => preg_replace( '/\D/', '', isset( $data['ach_account_number'] ) ? $data['ach_account_number'] : '' ),
			'nameOnAccount' => getpaid_limit_length( sanitize_text_field( isset( $data['ach_name_on_account'] ) ? $data['ach_name_on_account'] : '' ), 22 ),
			'echeckType'    => 'WEB',
		);

		return array( 'bankAccount' => $bank_account );
	}

	/**
	 * Validates ACH form fields.
	 *
	 * @param array $data ACH form data.
	 * @return true|WP_Error
	 */
	public function validate_ach_fields( $data ) {
		// Routing number validation (9 digits).
		$routing = preg_replace( '/\D/', '', isset( $data['ach_routing_number'] ) ? $data['ach_routing_number'] : '' );
		if ( strlen( $routing ) !== 9 ) {
			return new WP_Error( 'invalid_routing', __( 'Please enter a valid 9-digit routing number.', 'invoicing' ) );
		}

		// Validate routing number checksum.
		if ( ! $this->validate_routing_number_checksum( $routing ) ) {
			return new WP_Error( 'invalid_routing', __( 'The routing number appears to be invalid.', 'invoicing' ) );
		}

		// Account number validation (1-17 digits).
		$account = preg_replace( '/\D/', '', isset( $data['ach_account_number'] ) ? $data['ach_account_number'] : '' );
		if ( empty( $account ) || strlen( $account ) > 17 ) {
			return new WP_Error( 'invalid_account', __( 'Please enter a valid account number.', 'invoicing' ) );
		}

		// Name on account validation.
		$name = trim( isset( $data['ach_name_on_account'] ) ? $data['ach_name_on_account'] : '' );
		if ( empty( $name ) ) {
			return new WP_Error( 'invalid_name', __( 'Please enter the name on the account.', 'invoicing' ) );
		}

		// Account type validation.
		$valid_types = array_keys( $this->ach_account_types );
		$account_type = isset( $data['ach_account_type'] ) ? $data['ach_account_type'] : '';
		if ( ! in_array( $account_type, $valid_types, true ) ) {
			return new WP_Error( 'invalid_account_type', __( 'Please select a valid account type.', 'invoicing' ) );
		}

		return true;
	}

	/**
	 * Validates a routing number using the ABA checksum algorithm.
	 *
	 * @param string $routing The 9-digit routing number.
	 * @return bool
	 */
	public function validate_routing_number_checksum( $routing ) {
		if ( strlen( $routing ) !== 9 ) {
			return false;
		}

		$checksum = (
			3 * ( (int) $routing[0] + (int) $routing[3] + (int) $routing[6] ) +
			7 * ( (int) $routing[1] + (int) $routing[4] + (int) $routing[7] ) +
			1 * ( (int) $routing[2] + (int) $routing[5] + (int) $routing[8] )
		);

		return $checksum % 10 === 0;
	}

	/**
	 * Displays saved payment methods filtered by type.
	 *
	 * @param string $payment_type 'card' or 'ach'.
	 */
	public function saved_payment_methods_by_type( $payment_type = 'card' ) {
		$tokens = $this->get_tokens( $this->is_sandbox() );

		// Filter tokens by payment method type.
		$filtered_tokens = array();
		foreach ( $tokens as $token ) {
			$token_type = isset( $token['payment_method'] ) ? $token['payment_method'] : 'card';
			if ( $token_type === $payment_type ) {
				$filtered_tokens[] = $token;
			}
		}

		// For cards, if no tokens have payment_method set, assume they're all cards (backwards compatibility).
		if ( empty( $filtered_tokens ) && 'card' === $payment_type ) {
			foreach ( $tokens as $token ) {
				if ( ! isset( $token['payment_method'] ) || 'card' === $token['payment_method'] ) {
					$filtered_tokens[] = $token;
				}
			}
		}

		$input_name = 'ach' === $payment_type ? 'getpaid-authorizenet-ach-payment-method' : 'getpaid-authorizenet-payment-method';
		$new_label  = 'ach' === $payment_type ? __( 'Use a new bank account', 'invoicing' ) : __( 'Use a new card', 'invoicing' );

		echo '<ul class="getpaid-saved-payment-methods list-unstyled m-0 mt-2" data-count="' . esc_attr( count( $filtered_tokens ) ) . '">';

		foreach ( $filtered_tokens as $token ) {
			printf(
				'<li class="getpaid-payment-method form-group mb-3">
					<label>
						<input name="%1$s" type="radio" value="%2$s" data-currency="%5$s" style="width:auto;" class="getpaid-saved-payment-method-token-input" %4$s />
						<span>%3$s</span>
					</label>
				</li>',
				esc_attr( $input_name ),
				esc_attr( $token['id'] ),
				esc_html( $token['name'] ),
				checked( ! empty( $token['default'] ), true, false ),
				empty( $token['currency'] ) ? 'none' : esc_attr( $token['currency'] )
			);
		}

		printf(
			'<li class="getpaid-new-payment-method">
				<label>
					<input name="%1$s" type="radio" data-currency="none" value="new" style="width:auto;" />
					<span>%2$s</span>
				</label>
			</li>',
			esc_attr( $input_name ),
			esc_html( $new_label )
		);

		echo '</ul>';
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
            return new WP_Error( 'invalid_settings', __( 'Please set-up your login id and transaction key before using this gateway.', 'invoicing' ) );
        }

		// Determine if this is an ACH payment.
		$is_ach_payment    = $this->is_ach_payment( isset( $submission_data['authorizenet'] ) ? $submission_data['authorizenet'] : array() );
		$payment_method_key = $is_ach_payment ? 'getpaid-authorizenet-ach-payment-method' : 'getpaid-authorizenet-payment-method';

        // Validate the payment method.
        if ( empty( $submission_data[ $payment_method_key ] ) ) {
			$error_message = $is_ach_payment
				? __( 'Please select a different payment method or add a new bank account.', 'invoicing' )
				: __( 'Please select a different payment method or add a new card.', 'invoicing' );
            return new WP_Error( 'invalid_payment_method', $error_message );
        }

        // Are we adding a new payment method?
        if ( 'new' != $submission_data[ $payment_method_key ] ) {
            return $submission_data[ $payment_method_key ];
        }

		// Validate ACH fields if this is a new ACH payment.
		if ( $is_ach_payment ) {
			$ach_validation = $this->validate_ach_fields( $submission_data['authorizenet'] );
			if ( is_wp_error( $ach_validation ) ) {
				return $ach_validation;
			}
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
	public function maybe_renew_subscription( $subscription, $parent_invoice ) {
		// Ensure its our subscription && it's active.
		if ( ! empty( $parent_invoice ) && $this->id === $parent_invoice->get_gateway() && $subscription->has_status( 'active trialling' ) ) {
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

        if ( ! $new_invoice->needs_payment() ) {
            $subscription->renew();
            $subscription->after_add_payment( $new_invoice );
        } else {
            $subscription->failing();
        }
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
            __( 'SANDBOX ENABLED. You can use sandbox testing details only. See the %1$sAuthorize.NET Sandbox Testing Guide%2$s for more details.', 'invoicing' ),
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
            'desc' => '<a href="https://support.authorize.net/knowledgebase/Knowledgearticle/?code=000001271"><em>' . __( 'How do I obtain my API Login ID and Transaction Key?', 'invoicing' ) . '</em></a>',
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
            'desc' => '<a href="https://support.authorize.net/knowledgebase/Knowledgearticle/?code=000001271"><em>' . __( 'Learn more.', 'invoicing' ) . '</em></a>',
        );

        $admin_settings['authorizenet_ipn_url'] = array(
            'type'     => 'ipn_url',
            'id'       => 'authorizenet_ipn_url',
            'name'     => __( 'Webhook URL', 'invoicing' ),
            'std'      => $this->notify_url,
            'desc'     => __( 'Create a new webhook using this URL as the endpoint URL and set it to receive all payment events.', 'invoicing' ) . ' <a href="https://support.authorize.net/knowledgebase/Knowledgearticle/?code=000001542"><em>' . __( 'Learn more.', 'invoicing' ) . '</em></a>',
            'custom'   => 'authorizenet',
            'readonly' => true,
        );

		// ACH/eCheck Settings.
		$admin_settings['authorizenet_ach_header'] = array(
			'type' => 'header',
			'id'   => 'authorizenet_ach_header',
			'name' => '<h3>' . __( 'ACH/eCheck Settings', 'invoicing' ) . '</h3>',
		);

		$admin_settings['authorizenet_enable_ach'] = array(
			'type' => 'checkbox',
			'id'   => 'authorizenet_enable_ach',
			'name' => __( 'Enable ACH Payments', 'invoicing' ),
			'desc' => sprintf(
				__( 'Allow customers to pay via bank account (ACH/eCheck). Only available for USD transactions. %1$sLearn more about ACH payments%2$s.', 'invoicing' ),
				'<a href="https://developer.authorize.net/api/reference/features/echeck.html" target="_blank">',
				'</a>'
			),
		);

		$admin_settings['authorizenet_ach_description'] = array(
			'type' => 'text',
			'id'   => 'authorizenet_ach_description',
			'name' => __( 'ACH Description', 'invoicing' ),
			'desc' => __( 'Description shown to customers when they select ACH payment.', 'invoicing' ),
			'std'  => __( 'Pay directly from your bank account.', 'invoicing' ),
		);

		return $admin_settings;
	}

}
