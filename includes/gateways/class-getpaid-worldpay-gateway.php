<?php
/**
 * Worldpay payment gateway
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Worldpay Payment Gateway class.
 *
 */
class GetPaid_Worldpay_Gateway extends GetPaid_Payment_Gateway {

    /**
	 * Payment method id.
	 *
	 * @var string
	 */
    public $id = 'worldpay';

    /**
	 * Payment method order.
	 *
	 * @var int
	 */
    public $order = 5;

    /**
	 * Endpoint for requests from Worldpay.
	 *
	 * @var string
	 */
	protected $notify_url;

	/**
	 * Endpoint for requests to Worldpay.
	 *
	 * @var string
	 */
    protected $endpoint;

    /**
	 * An array of features that this gateway supports.
	 *
	 * @var array
	 */
    protected $supports = array( 'sandbox' );
    
    /**
	 * Currencies this gateway is allowed for.
	 *
	 * @var array
	 */
	public $currencies = array( 'AUD', 'ARS', 'CAD', 'CHF', 'DKK', 'EUR', 'HKD', 'MYR', 'GBP', 'NZD', 'NOK', 'SGD', 'LKR', 'SEK', 'TRY', 'USD', 'ZAR' );

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

        $this->method_title         = __( 'Worldpay', 'invoicing' );
        $this->title                = __( 'Worldpay - Credit Card / Debit Card', 'invoicing' );
        $this->checkout_button_text = __( 'Proceed to Worldpay', 'invoicing' );
        $this->notify_url           = wpinv_get_ipn_url( $this->id );

        add_filter( 'wpinv_gateway_description', array( $this, 'sandbox_notice' ), 10, 2 );
        add_filter( 'getpaid_worldpay_args', array( $this, 'hash_args' ) );

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
        $worldpay_redirect = esc_url( $this->get_request_url( $invoice ) );

        // Get submission args.
        $worldpay_args     = $this->get_worldpay_args( $invoice );

        $form = "<form action='$worldpay_redirect' name='wpi_worldpay_form' method='POST'>";

        foreach ( $worldpay_args as $key => $value ) {

            if ( false === $value || '' === trim( $value ) ) {
                continue;
            }

            $value = esc_attr( $value );
            $key   = wpinv_clean( $key );
            $form .= "<input type='hidden' name='$key' value='$value'>";
        }

        $form .= '</form>';

        wp_send_json_success(
            array(
                'action' => 'auto_submit_form',
                'form'   => $form
            )
        );

        exit;

    }

    /**
	 * Get the Worldpay request URL for an invoice.
	 *
	 * @param  WPInv_Invoice $invoice Invoice object.
	 * @return string
	 */
	public function get_request_url( $invoice ) {

        // Endpoint for this request
		$this->endpoint = $this->is_sandbox( $invoice ) ? 'https://secure-test.worldpay.com/wcc/purchase' : 'https://secure.worldpay.com/wcc/purchase';

        return $this->endpoint;

	}

    /**
	 * Get Worldpay Args for passing to Worldpay.
	 *
	 * @param  WPInv_Invoice $invoice Invoice object.
	 * @return array
	 */
	protected function get_worldpay_args( $invoice ) {

		return apply_filters(
			'getpaid_worldpay_args',
			array(
                'amount'         => wpinv_sanitize_amount( $invoice->get_total() ), // mandatory
                'cartId'         => wpinv_clean( $invoice->get_number() ), // mandatory reference for the item purchased
                'currency'       => wpinv_clean( $invoice->get_currency() ), // mandatory
                'instId'         => wpinv_clean( $this->get_option( 'instId', '' ) ), // mandatory
                'testMode'       => $this->is_sandbox( $invoice ) ? 100 : 0, // mandatory
                'name'           => wpinv_clean( $invoice->get_full_name() ),
                'address'        => wpinv_clean( $invoice->get_address() ),
                'postcode'       => wpinv_clean( $invoice->get_zip() ),
                'tel'            => wpinv_clean( $invoice->get_phone() ),
                'email'          => sanitize_email( $invoice->get_email() ),
                'country'        => wpinv_clean( $invoice->get_country() ),
                'desc'           => sprintf( __( 'Payment for invoice %s.', 'invoicing' ), wpinv_clean( $invoice->get_number() ) ),
                'MC_description' => sprintf( __( 'Payment for invoice %s.', 'invoicing' ), wpinv_clean( $invoice->get_number() ) ),
                'MC_callback'    => esc_url_raw( $this->notify_url ),
                'resultfile'     => esc_url_raw( $this->get_return_url( $invoice ) ),
                'MC_key'         => wpinv_clean( $invoice->get_key() ),
                'MC_invoice_id'  => $invoice->get_id(),
                'address1'       => wpinv_clean( $invoice->get_address() ),
                'town'           => wpinv_clean( $invoice->get_city() ),
                'region'         => wpinv_clean( $invoice->get_state() ),
                'amountString'   => wpinv_price( $invoice->get_total(), $invoice->get_currency() ),
                'countryString'  => wpinv_clean( wpinv_country_name( $invoice->get_country() ) ),
                'compName'       => wpinv_clean( $invoice->get_company() ),
            ),
			$invoice
		);

    }

    /**
	 * Secures worldpay args with an md5 hash.
	 *
	 * @param  array $args Gateway args.
	 * @return array
	 */
	public function hash_args( $args ) {

        $md5_secret = $this->get_option( 'md5_secret' );

        // Abort if there is no secret.
        if ( empty( $md5_secret ) ) {
            return $args;
        }

        // Hash the args.
        $args['signature'] = md5( "$md5_secret:{$args['instId']}:{$args['amount']}:{$args['currency']}:{$args['cartId']}" );

        return $args;
    }

    /**
	 * Processes ipns and marks payments as complete.
	 *
	 * @return void
	 */
	public function verify_ipn() {

        // Validate the IPN.
        if ( empty( $_POST ) || ! $this->validate_ipn() ) {
		    wp_die( 'Worldpay IPN Request Failure', 'Worldpay IPN', array( 'response' => 500 ) );
		}

        // Process the IPN.
        $posted  = wp_kses_post_deep( wp_unslash( $_POST ) );
        $invoice = wpinv_get_invoice( $posted['MC_invoice_id'] );

        if ( $invoice && $this->id == $invoice->get_gateway() ) {

            wpinv_error_log( 'Found invoice #' . $invoice->get_number() );
            wpinv_error_log( 'Payment status:' . $posted['transStatus'] );

            // Update the transaction id.
            if ( ! empty( $posted['transId'] ) ) {
                $invoice->set_transaction_id( wpinv_clean( $posted['transId'] ) );
            }

             // Update the ip address.
             if ( ! empty( $posted['ipAddress'] ) ) {
                $invoice->set_ip( wpinv_clean( $posted['ipAddress'] ) );
            }

            if ( $posted['transStatus'] == 'Y' ) {
                $invoice->set_completed_date( date( 'Y-m-d H:i:s', $posted['transTime'] ) );
                $invoice->mark_paid();
                return;
            }

            if ( $posted['transStatus'] == 'C' ) {
                $invoice->set_status( 'wpi-failed' );
                $invoice->add_note( __( 'Payment transaction failed while processing Worldpay payment.', 'invoicing' ), false, false, true );
                $invoice->save();
                return;
            }

            wpinv_error_log( 'Aborting, Invalid transaction status:' . $posted['transStatus'] );
            $invoice->save();

        }

        exit;

    }

    /**
	 * Check Worldpay IPN validity.
	 */
	public function validate_ipn() {

        wpinv_error_log( 'Validating Worldpay IPN response' );

        $data = wp_kses_post_deep( wp_unslash( $_POST ) );

        // Verify installation.
        if ( empty( $data['instId'] ) || $data['instId'] != wpinv_clean( $this->get_option( 'instId', '' ) ) ) {
            wpinv_error_log( 'Received invalid installation ID from Worldpay IPN' );
            return false;
        }

        // Verify invoice.
        if ( empty( $data['cartId'] ) || !  wpinv_get_id_by_invoice_number( $data['cartId'] ) ) {
            wpinv_error_log( 'Received invalid invoice number from Worldpay IPN' );
            return false;
        }
        
        // (maybe) verify password.
        $password = $this->get_option( 'callback_password' );

        if ( ! empty( $password ) && ( empty( $data['callbackPW'] ) || $password != $data['callbackPW'] ) ) {
            wpinv_error_log( 'Received invalid invoice number from Worldpay IPN' );
            return false;
        }

        return true;

    }

    /**
     * Displays a notice on the checkout page if sandbox is enabled.
     */
    public function sandbox_notice( $description, $gateway ) {
        if ( 'worldpay' == $gateway && wpinv_is_test_mode( 'worldpay' ) ) {
            $description .= '<br>' . sprintf(
                __( 'SANDBOX ENABLED. See the %sWorldpay Sandbox Testing Guide%s for more details.', 'invoicing' ),
                '<a href="https://developer.worldpay.com/docs/wpg/directintegration/abouttesting">',
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

        $admin_settings['worldpay_active']['desc'] = $admin_settings['worldpay_active']['desc'] . " ($currencies)";
        $admin_settings['worldpay_desc']['std']    = __( 'Pay securely via Worldpay using your PayPal account, credit or debit card.', 'invoicing' );

        $admin_settings['worldpay_instId'] = array(
            'type' => 'text',
            'id'   => 'worldpay_instId',
            'name' => __( 'Installation Id', 'invoicing' ),
            'desc' => __( 'Your installation id. Ex: 211616', 'invoicing' ),
        );

        $admin_settings['worldpay_md5_secret'] = array(
            'type' => 'text',
            'id'   => 'worldpay_md5_secret',
            'name' => __( 'MD5 secret', 'invoicing' ),
            'desc' => __( 'Optionally enter your MD5 secret here. Next, open your installation settings and ensure that your SignatureFields parameter is set to ', 'invoicing' ) . '<code>instId:amount:currency:cartId</code>',
        );

        $admin_settings['worldpay_callbackPW'] = array(
            'type' => 'text',
            'id'   => 'worldpay_callbackPW',
            'name' => __( 'Payment Response password', 'invoicing' ),
            'desc' => __( 'Recommended. Enter your WorldPay response password to validate payment notifications.', 'invoicing' ),
        );

        $admin_settings['worldpay_ipn_url'] = array(
            'type'     => 'ipn_url',
            'id'       => 'worldpay_ipn_url',
            'name'     => __( 'Payment Response URL', 'invoicing' ),
            'std'      => $this->notify_url,
            'desc'     => __( 'Login to your Worldpay Merchant Interface then enable Payment Response & Shopper Response. Next, go to the Payment Response URL field and enter the above URL.', 'invoicing' ),
            'custom'   => 'worldpay',
            'readonly' => true
        );

		return $admin_settings;
	}

}
