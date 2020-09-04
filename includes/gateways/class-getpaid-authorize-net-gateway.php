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
class GetPaid_Authorize_Net_Gateway extends GetPaid_Payment_Gateway {

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
    protected $supports = array( 'subscription', 'sandbox', 'tokens' );

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
        $this->notify_url           = wpinv_get_ipn_url( $this->id );

        add_filter( 'wpinv_renew_authorizenet_subscription_profile', array( $this, 'renew_subscription' ) );
        parent::__construct();
    }

    /**
	 * Displays the credit card entry field.
	 * 
	 * @param int $invoice_id 0 or invoice id.
	 * @param GetPaid_Payment_Form $form Current payment form.
	 */
    public function payment_fields( $invoice_id, $form ) {
        $id_prefix = esc_attr( uniqid( 'authorizenet' ) );

        $months = array(
            '01' => __( 'January', 'invoicing' ),
            '02' => __( 'February', 'invoicing' ),
            '03' => __( 'March', 'invoicing' ),
            '04' => __( 'April', 'invoicing' ),
            '05' => __( 'May', 'invoicing' ),
            '06' => __( 'June', 'invoicing' ),
            '07' => __( 'July', 'invoicing' ),
            '08' => __( 'August', 'invoicing' ),
            '09' => __( 'September', 'invoicing' ),
            '10' => __( 'October', 'invoicing' ),
            '11' => __( 'November', 'invoicing' ),
            '12' => __( 'December', 'invoicing' ),
        );

        $year  = (int) date( 'Y', current_time( 'timestamp' ) );
        $years = array();

        for ( $i = 0; $i <= 20; $i++ ) {
            $years[ $year + $i ] = $year + $i;
        }

        ?>
            <div class="authorizenet-cc-form card  mt-4">

                <div class="card-header"><?php _e( 'Card Details', 'invoicing' ) ;?></div>

                <div class="card-body">

                    <?php

                        echo aui()->input(
                            array(
                                'type'              => 'text',
                                'name'              => 'authorizenet[cc_owner]',
                                'id'                => "$id_prefix-cc-owner",
                                'label'             => __( 'Full name (on the card)', 'invoicing' ),
                                'label_type'        => 'vertical',
                                'input_group_left'  => '<span class="input-group-text"><i class="fa fa-user"></i></span>',
                            )
                        );

                        echo aui()->input(
                            array(
                                'name'              => 'authorizenet[cc_number]',
                                'id'                => "$id_prefix-cc-number",
                                'label'             => __( 'Card number', 'invoicing' ),
                                'label_type'        => 'vertical',
                                'input_group_left'  => '<span class="input-group-text"><i class="fa fa-credit-card"></i></span>',
                            )
                        );
                    ?>

                    <div class="row">

                        <div class="col-sm-8">
                            <div class="form-group">
                                <label>
                                    <span class="hidden-xs"><?php _e( 'Expiration', 'invoicing' ); ?></span>
                                </label>
                                <div class="form-inline">

                                    <select class="form-control" style="width:45%" name="authorizenet[cc_expire_month]">
                                        <option disabled><?php _e( 'MM', 'invoicing' ); ?></option>

                                        <?php
                                            foreach ( $months as $key => $month ) {
                                                $key   = esc_attr( $key );
                                                $month = wpinv_clean( $month );
                                                echo "<option value='$key'>$month</option>" . PHP_EOL;
                                            }
                                        ?>
                                    
                                    </select>

                                    <span style="width:10%; text-align: center"> / </span>
            
                                    <select class="form-control" style="width:45%" name="authorizenet[cc_expire_year]">
                                        <option disabled><?php _e( 'YY', 'invoicing' ); ?></option>

                                        <?php
                                            foreach ( $years as $key => $year ) {
                                                $key   = esc_attr( $key );
                                                $year  = wpinv_clean( $year );
                                                echo "<option value='$key'>$year</option>" . PHP_EOL;
                                            }
                                        ?>

                                    </select>
            
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <?php
                                echo aui()->input(
                                    array(
                                        'name'              => 'authorizenet[cc_cvv2]',
                                        'id'                => "$id_prefix-cc-cvv2",
                                        'label'             => __( 'CCV', 'invoicing' ),
                                        'label_type'        => 'vertical',
                                    )
                                );
                            ?>
                        </div>

                    </div>

                </div>
            </div>
        <?php

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
                            'address'      => getpaid_limit_length( $invoice->get_last_name(), 60 ),
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
log_noptin_message( $args );
        $response = $this->post( apply_filters( 'getpaid_authorizenet_customer_profile_args', $args, $invoice ), $invoice );

        if ( is_wp_error( $response ) ) {
            return $response;
        }
        log_noptin_message( $response );
        update_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), $response->customerProfileId );

        // Save the payment token.
        if ( $save ) {
            $this->save_token(
                array(
                    'id'      => $response->customerPaymentProfileIdList[0],
                    'name'    => $this->get_card_name( $submission_data['authorizenet']['cc_number'] ) . '&middot;&middot;&middot;&middot;' . substr( $submission_data['authorizenet']['cc_number'], -4 ),
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
                        'address'      => getpaid_limit_length( $invoice->get_last_name(), 60 ),
                        'city'         => getpaid_limit_length( $invoice->get_city(), 40 ),
                        'state'        => getpaid_limit_length( $invoice->get_state(), 40 ),
                        'zip'          => getpaid_limit_length( $invoice->get_zip(), 20 ),
                        'country'      => getpaid_limit_length( $invoice->get_country(), 60 ),
                    ),

                    // Payment information.
                    'payment'          => $this->get_payment_information( $submission_data['authorizenet'] )
                ),
                'validationMode'       => $this->is_sandbox( $invoice ) ? 'testMode' : 'liveMode', 
            )
        );

        $response = $this->post( apply_filters( 'getpaid_authorizenet_create_customer_payment_profile_args', $args, $invoice ), $invoice );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Save the payment token.
        if ( $save ) {
            $this->save_token(
                array(
                    'id'      => $response->customerPaymentProfileId,
                    'name'    => $this->get_card_name( $submission_data['authorizenet']['cc_number'] ) . '&middot;&middot;&middot;&middot;' . substr( $submission_data['authorizenet']['cc_number'], -4 ),
                    'default' => true
                )
            );
        }

        // Add a note about the validation response.
        $invoice->add_note(
            sprintf( __( 'Saved Authorize.NET payment profile: %s', 'invoicing' ), $response->validationDirectResponse ),
            false,
            false,
            true
        );
        

        return $response->customerPaymentProfileId;
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

            'getCustomerPaymentProfileRequest' => array(

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
                    'lineItems'                => $this->get_line_items( $invoice ),
                    'tax'                      => array(
                        'amount'               => $invoice->get_total_tax(),
                        'name'                 => getpaid_tax()->get_vat_name(),
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
        log_noptin_message( $args );
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

        switch ( (int) $result->transactionResponse->responseCode ) {

            case 1:
            case 4:
                $invoice->set_transaction_id( $result->transactionResponse->transId );

                if ( 1 == (int) $result->transactionResponse->responseCode ) {
                    $invoice->mark_paid();
                } else {
                    $invoice->set_status( 'wpi-onhold' );
                    $invoice->add_note( 
                        sprintf(
                            __( 'Held for review: %s', 'invoicing' ),
                            $result->transactionResponse->messages->message[0]->description
                        )
                    );
                }

                $invoice->add_note( sprintf( __( 'Authentication code: %s (%s).', 'invoicing' ), $result->transactionResponse->authCode, $result->transactionResponse->accountNumber ), false, false, true );
                $invoice->save();

                return;

            case 2:
            case 3:
                wpinv_set_error( 'card_declined', __( 'Credit card declined.', 'invoicing' ) );

                if ( ! empty( $result->transactionResponse->errors ) ) {
                    $errors = (object) $result->transactionResponse->errors;
                    wpinv_set_error( $errors->error[0]->errorCode, $errors->error[0]->errorText );
                }

                return;

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
	 * Returns the card name.
	 *
	 *
	 * @param string $card_number Card number.
	 * @return string
	 */
	public function get_card_name( $card_number ) {

        switch( $card_number ) {

            case( preg_match ( '/^4/', $card_number ) >= 1 ):
                return __( 'Visa', 'invoicing' );

            case( preg_match ( '/^5[1-5]/', $card_number ) >= 1 ):
                return __( 'Mastercard', 'invoicing' );

            case( preg_match ( '/^3[47]/', $card_number ) >= 1 ):
                return __( 'Amex', 'invoicing' );

            case( preg_match ( '/^3(?:0[0-5]|[68])/', $card_number ) >= 1 ):
                return __( 'Diners Club', 'invoicing' );

            case( preg_match ( '/^6(?:011|5)/', $card_number ) >= 1 ):
                return __( 'Discover', 'invoicing' );

            case( preg_match ( '/^(?:2131|1800|35\d{3})/', $card_number ) >= 1 ):
                return __( 'JCB', 'invoicing' );

            default:
            return __( 'Card', 'invoicing' );
                break;
        }

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
	 * Returns the API URL.
	 *
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @return string
	 */
	public function get_api_url( $invoice ) {
        return $this->is_sandbox( $invoice ) ? 'https://apitest.authorize.net/xml/v1/request.api' : 'https://api.authorize.net/xml/v1/request.api';
    }

    /**
	 * Returns the API authentication params.
	 *
	 *
	 * @return array
	 */
	public function get_auth_params() {

        return array(
            'name'           => $this->get_option( 'login_id' ),
            'transactionKey' => $this->get_option( 'transaction_key' ),
        );

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
            return new WP_Error( 'invalid_settings', __( 'This gateway has not been set up.', 'invoicing') );
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

            $items[] = array(
                'itemId'      => getpaid_limit_length( $item->get_id(), 31 ),
                'name'        => getpaid_limit_length( $item->get_raw_name(), 31 ),
                'description' => getpaid_limit_length( $item->get_description(), 255 ),
                'quantity'    => (int) $invoice->get_template() == 'amount' ? 1 : $item->get_quantity(),
                'unitPrice'   => (float) $item->get_price(),
                'taxable'     => wpinv_use_taxes() && $invoice->is_taxable() && 'tax-exempt' != $item->get_vat_rule(),
            );

        }

        return $items;
    }

    /**
	 * Communicates with authorize.net
	 *
	 *
	 * @param array $post Data to post.
     * @param WPInv_Invoice $invoice Invoice.
	 * @return stdClass|WP_Error
	 */
    public function post( $post, $invoice ){

        $url      = $this->get_api_url( $invoice );
        $response = wp_remote_post(
            $url,
            array(
                'headers'          => array(
                    'Content-Type' => 'application/json; charset=utf-8'
                ),
                'body'             => json_encode( $post ),
                'method'           => 'POST'
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response = json_decode( wp_remote_retrieve_body( $response ) );

        if ( empty( $response ) ) {
            return new WP_Error( 'invalid_reponse', __( 'Invalid response', 'invoicing' ) );
        }

        if ( $response->messages->resultCode == 'Error' ) {
            return new WP_Error( $response->messages->message[0]->code, $response->messages->message[0]->text );
        }

        return $response;

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
            wpinv_send_back_to_checkout();
        }

        // Save the payment method to the order.
        update_post_meta( $invoice->get_id(), 'getpaid_authorizenet_profile_id', $payment_profile_id );

        // Check if this is a subscription or not.
        if ( $invoice->is_recurring() && $subscription = wpinv_get_subscription( $invoice ) ) {
            $this->process_subscription( $invoice, $subscription );
        }

        // If it is free, send to the success page.
        if ( ! $invoice->needs_payment() ) {
            $invoice->mark_paid();
            wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
        }

        // Charge the payment profile.
        $customer_profile = get_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), true );
        $result           = $this->charge_customer_payment_profile( $customer_profile, $payment_profile_id, $invoice );

        // Do we have an error?
        if ( is_wp_error( $result ) ) {
            wpinv_set_error( $result->get_error_code(), $result->get_error_message() );
            wpinv_send_back_to_checkout();
        }
        log_noptin_message( $result );
        // Process the response.
        $this->process_charge_response( $result, $invoice );

        if ( wpinv_get_errors() ) {
            wpinv_send_back_to_checkout();
        }

        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

        exit;

    }

    /**
	 * Processes recurring payments.
	 *
     * @param WPInv_Invoice $invoice Invoice.
     * @param WPInv_Subscription $subscription Subscription.
	 */
	public function process_subscription( $invoice, $subscription ) {

        // Check if there is an initial amount to charge.
        if ( (float) $invoice->get_total() > 0 ) {

            // Retrieve the payment method.
            $payment_profile_id = get_post_meta( $invoice->get_id(), 'getpaid_authorizenet_profile_id', true );
            $customer_profile   = get_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), true );
            $result             = $this->charge_customer_payment_profile( $customer_profile, $payment_profile_id, $invoice );

            // Do we have an error?
            if ( is_wp_error( $result ) ) {
                wpinv_set_error( $result->get_error_code(), $result->get_error_message() );
                wpinv_send_back_to_checkout();
            }

            // Process the response.
            $this->process_charge_response( $result, $invoice );

            if ( wpinv_get_errors() ) {
                wpinv_send_back_to_checkout();
            }

        }

        // Recalculate the new subscription expiry.
        $duration = strtotime( $subscription->expiration ) - strtotime( $subscription->created );
        $expiry   = date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $duration ) );

        // Schedule an action to run when the subscription expires.
        $action_id = as_schedule_single_action(
            strtotime( $expiry ),
            'wpinv_renew_authorizenet_subscription_profile',
            array( $invoice->get_id() ),
            'invoicing'
        );

        // Update the subscription.
        $subscription->update( 
            array(
                'profile_id' => $action_id,
                'status'     => 'trialling' == $subscription->status ? 'trialling' : 'active',
                'created'    => current_time( 'mysql' ),
                'expiration' => $expiry,
            )
        );

        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

    }

    /**
	 * Renews a subscription.
	 *
     * @param int $invoice Invoice id.
	 */
	public function renew_subscription( $invoice ) {

        // Retrieve the subscription.
        $subscription = wpinv_get_subscription( $invoice );
        if ( empty( $subscription ) ) {
            return;
        }

        // Abort if it is canceled or complete.
        if ( $subscription->status == 'completed' || $subscription->status == 'cancelled' ) {
            return;
        }

        // Retrieve the invoice.
        $invoice = new WPInv_Invoice( $invoice );

        // If we have not maxed out on bill times...
        $times_billed = $subscription->get_times_billed();
        $max_bills    = $subscription->bill_times;

        if ( empty( $max_bills ) || $max_bills > $times_billed ) {

            $new_invoice = $subscription->create_payment();

            if ( empty( $new_invoice ) ) {
                $invoice->add_note( __( 'Error generating a renewal invoice.', 'invoicing' ), false, false, false );
                $subscription->failing();
                return;
            }

            // retrieve the payment method.
            $payment_profile_id = get_post_meta( $invoice->get_id(), 'getpaid_authorizenet_profile_id', true );
            $customer_profile   = get_user_meta( $invoice->get_user_id(), $this->get_customer_profile_meta_name( $invoice ), true );
            $result             = $this->charge_customer_payment_profile( $customer_profile, $payment_profile_id, $new_invoice );

            // Do we have an error?
            if ( is_wp_error( $result ) ) {
                $invoice->add_note(
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

                $invoice->add_note(
                    sprintf( __( 'Error renewing subscription : ( %s ).', 'invoicing' ), getpaid_get_errors_html() ),
                    true,
                    false,
                    true
                );
                $subscription->failing();
                return;

            }

            // Renew the subscription.
            $subscription->add_payment(
                array(
                    'transaction_id' => $new_invoice->get_transaction_id(),
                    'gateway'        => $this->id
                ),
                $new_invoice
            );

            // Renew/Complete the subscription.
            $subscription->renew();

            if ( 'completed' != $subscription->status ) {

                // Schedule an action to run when the subscription expires.
                $action_id = as_schedule_single_action(
                    strtotime( $subscription->expiration ),
                    'wpinv_renew_authorizenet_subscription_profile',
                    array( $invoice->get_id() ),
                    'invoicing'
                );
    
                $subscription->update( array( 'profile_id' => $action_id, ) );
    
            }

        }
    }

    /**
	 * Cancels a subscription remotely
	 *
	 *
	 * @param WPInv_Subscription $subscription Subscription.
     * @param WPInv_Invoice $invoice Invoice.
	 */
	public function cancel_subscription( $subscription, $invoice ) {

        if ( as_unschedule_action( 'wpinv_renew_authorizenet_subscription_profile', array( $invoice->get_id() ), 'invoicing' ) ) {
            return;
        }

        // Backwards compatibility.
        $this->post(
            array(
                'ARBCancelSubscriptionRequest' => array(
                    'merchantAuthentication'   => $this->get_auth_params(),
                    'subscriptionId'           => $subscription->profile_id,
                )
            ),
            $invoice
        );

    }

    /**
	 * Processes ipns.
	 *
	 * @return void
	 */
	public function verify_ipn() {

        $this->maybe_process_old_ipn();

        // Validate the IPN.
        if ( empty( $_POST ) || ! $this->validate_ipn() ) {
		    wp_die( 'Authorize.NET IPN Request Failure', 'Authorize.NET IPN', array( 'response' => 500 ) );
        }

        // Event type.
        $posted = json_decode( file_get_contents('php://input') );
        if ( empty( $posted ) ) {
            wp_die( 'Invalid JSON', 'Authorize.NET IPN', array( 'response' => 500 ) );
        }

        // Process the IPN.
        $posted = (object) wp_unslash( $posted );

        // Process refunds.
        if ( 'net.authorize.payment.refund.created' == $posted->eventType ) {
            $invoice = new WPInv_Invoice( $posted->payload->merchantReferenceId );

            if ( $invoice->get_id() && $posted->payload->id == $invoice->get_transaction_id() ) {
                $invoice->refund();
            }

        }

        // Held funds approved.
        if ( 'net.authorize.payment.fraud.approved' == $posted->eventType ) {
            $invoice = new WPInv_Invoice( $posted->payload->id );

            if ( $invoice->get_id() && $posted->payload->id == $invoice->get_transaction_id() ) {
                $invoice->mark_paid( false, __( 'Payment released', 'invoicing' ));
            }

        }

        // Held funds declined.
        if ( 'net.authorize.payment.fraud.declined' == $posted->eventType ) {
            $invoice = new WPInv_Invoice( $posted->payload->id );

            if ( $invoice->get_id() && $posted->payload->id == $invoice->get_transaction_id() ) {
                $invoice->set_status( 'wpi-failed', __( 'Payment desclined', 'invoicing' ) );
                $invoice->save();
            }

        }

        exit;

    }

    /**
	 * Backwards compatibility.
	 *
	 * @return void
	 */
	public function maybe_process_old_ipn() {

        // Ensure that we are using the old subscriptions.
        if ( empty( $_POST['x_subscription_id'] ) ) {
            return;
        }

        // Check validity.
        $signature = $this->get_option( 'signature_key' );
        if ( ! empty( $signature ) ) {
            $login_id  = $this->get_option( 'login_id' );
            $trans_id  = $_POST['x_trans_id'];
            $amount    = $_POST['x_amount'];
            $hash      = hash_hmac ( 'sha512', "^$login_id^$trans_id^$amount^", hex2bin( $signature ) );

            if ( ! hash_equals( $hash, $_POST['x_SHA2_Hash'] ) ) {
                exit;
            }

        }

        // Fetch the associated subscription.
        $subscription = new WPInv_Subscription( $_POST['x_subscription_id'], true );

        // Abort if it is missing or completed.
        if ( empty( $subscription->id ) || $subscription->status == 'completed' ) {
            return;
        }

        // Payment status.
        if ( 1 == $_POST['x_response_code'] ) {

            $args = array(
                'transaction_id' => wpinv_clean( $_POST['x_trans_id'] ),
                'gateway'        => $this->id
            );

            $subscription->add_payment( $args );
            $subscription->renew();

        } else {
            $subscription->failing();
        }

        exit;

    }

    /**
	 * Check Authorize.NET IPN validity.
	 */
	public function validate_ipn() {

        wpinv_error_log( 'Validating Authorize.NET IPN response' );

        if ( empty( $_SERVER['HTTP_X_ANET_SIGNATURE'] ) ) {
            return false;
        }

        $signature = $this->get_option( 'signature_key' );

        if ( empty( $signature ) ) {
            wpinv_error_log( 'Error: You have not set a signature key' );
            return false;
        }

        $hash  = hash_hmac ( 'sha512', file_get_contents('php://input'), hex2bin( $signature ) );

        if ( hash_equals( $hash, $_SERVER['HTTP_X_ANET_SIGNATURE'] ) ) {
            wpinv_error_log( 'Successfully validated the IPN' );
            return true;
        }

        wpinv_error_log( 'IPN hash is not valid' );
        wpinv_error_log(  $_SERVER['HTTP_X_ANET_SIGNATURE']  );
        return false;

    }

    /**
     * Displays a notice on the checkout page if sandbox is enabled.
     */
    public function sandbox_notice( $description, $gateway ) {

        if ( $this->id == $gateway && wpinv_is_test_mode( $this->id ) ) {
            $description .= '<br>' . sprintf(
                __( 'SANDBOX ENABLED. You can use sandbox testing details only. See the %sAuthorize.NET Sandbox Testing Guide%s for more details.', 'invoicing' ),
                '<a href="https://developer.authorize.net/hello_world/testing_guide.html">',
                '</a>'
            );
        }
        return $description;

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

        $admin_settings['authorizenet_active']['desc'] .= $admin_settings['authorizenet_active']['desc'] . " ($currencies)";
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
