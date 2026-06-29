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

        // Warn admins when the gateway is active but notifications cannot be verified.
        add_action( 'admin_notices', array( $this, 'maybe_show_ipn_security_notice' ) );

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
                'form'   => $form,
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

        // Only process notifications when the Worldpay gateway is active.
        if ( ! wpinv_is_gateway_active( $this->id ) ) {
            wpinv_error_log( 'Aborting, the Worldpay gateway is not active.' );
            wp_die( 'Worldpay IPN Request Failure', 'Worldpay IPN', array( 'response' => 403 ) );
        }

        // Validate the IPN.
        if ( empty( $_POST ) || ! $this->validate_ipn() ) {
		    wp_die( 'Worldpay IPN Request Failure', 'Worldpay IPN', array( 'response' => 500 ) );
		}

        // Process the IPN.
        $posted = wp_kses_post_deep( wp_unslash( $_POST ) );

        // Retrieve the invoice using the same cart id that was validated above.
        $invoice_id = empty( $posted['cartId'] ) ? 0 : wpinv_get_id_by_invoice_number( wpinv_clean( $posted['cartId'] ) );
        $invoice    = empty( $invoice_id ) ? false : wpinv_get_invoice( $invoice_id );

        if ( $invoice && $this->id == $invoice->get_gateway() ) {

            $transaction_status = isset( $posted['transStatus'] ) ? $posted['transStatus'] : '';

            wpinv_error_log( 'Found invoice #' . $invoice->get_number() );
            wpinv_error_log( 'Payment status:' . $transaction_status );

            // Update the transaction id.
            if ( ! empty( $posted['transId'] ) ) {
                $invoice->set_transaction_id( wpinv_clean( $posted['transId'] ) );
            }

             // Update the ip address.
             if ( ! empty( $posted['ipAddress'] ) ) {
                $invoice->set_ip( wpinv_clean( $posted['ipAddress'] ) );
            }

            if ( 'Y' === $transaction_status ) {

                // Abort if the invoice has already been paid, to prevent reprocessing or replays.
                if ( $invoice->is_paid() ) {
                    wpinv_error_log( 'Aborting, the invoice #' . $invoice->get_number() . ' has already been paid for.' );
                    return;
                }

                if ( ! empty( $posted['transTime'] ) ) {
                    $invoice->set_completed_date( date( 'Y-m-d H:i:s', (int) $posted['transTime'] ) );
                }

                $invoice->mark_paid();
                return;
            }

            if ( 'C' === $transaction_status ) {
                $invoice->set_status( 'wpi-failed' );
                $invoice->add_note( __( 'Payment transaction failed while processing Worldpay payment.', 'invoicing' ), false, false, true );
                $invoice->save();
                return;
            }

            wpinv_error_log( 'Aborting, Invalid transaction status:' . $transaction_status );
            $invoice->save();

        }

        exit;

    }

    /**
	 * Check Worldpay IPN validity.
	 *
	 * @return bool
	 */
	public function validate_ipn() {

        wpinv_error_log( 'Validating Worldpay IPN response' );

        $data = wp_kses_post_deep( wp_unslash( $_POST ) );

        // Retrieve the associated invoice using the cart id.
        $invoice_id = empty( $data['cartId'] ) ? 0 : wpinv_get_id_by_invoice_number( wpinv_clean( $data['cartId'] ) );
        $invoice    = empty( $invoice_id ) ? false : wpinv_get_invoice( $invoice_id );

        if ( empty( $invoice ) ) {
            wpinv_error_log( 'Received invalid invoice number from Worldpay IPN' );
            return false;
        }

        // Validate the notification against the configured credentials and the invoice.
        $valid = self::validate_notification(
            $data,
            (string) wpinv_clean( $this->get_option( 'instId', '' ) ),
            (string) $this->get_option( 'callbackPW' ),
            (string) $this->get_option( 'md5_secret' ),
            (string) $invoice->get_total(),
            (string) $invoice->get_currency()
        );

        if ( ! $valid ) {
            wpinv_error_log( 'Received an invalid Worldpay IPN notification' );
        }

        return $valid;

    }

	/**
	 * Validates a Worldpay payment notification.
	 *
	 * @param array  $data             Sanitized, unslashed notification data.
	 * @param string $inst_id          Configured Worldpay installation id.
	 * @param string $password         Configured Payment Response password.
	 * @param string $md5_secret       Configured MD5 secret.
	 * @param string $invoice_total    Invoice total to verify the paid amount against.
	 * @param string $invoice_currency Invoice currency to verify the paid currency against.
	 * @return bool
	 */
	public static function validate_notification( $data, $inst_id, $password, $md5_secret, $invoice_total, $invoice_currency ) {

		// Verify the installation id (public value, sanity check only).
		if ( empty( $data['instId'] ) || ! hash_equals( (string) $inst_id, (string) $data['instId'] ) ) {
			return false;
		}

		$password   = (string) $password;
		$md5_secret = (string) $md5_secret;

		// No shared secret: fall back to the legacy behaviour.
		if ( '' === $password && '' === $md5_secret ) {
			return true;
		}

		// Verify the Payment Response password (fail closed when set).
		if ( '' !== $password && ( empty( $data['callbackPW'] ) || ! hash_equals( $password, (string) $data['callbackPW'] ) ) ) {
			return false;
		}

		// Verify the MD5 signature when set and returned by Worldpay (opportunistic).
		if ( '' !== $md5_secret && ! empty( $data['signature'] ) ) {
			$amount   = isset( $data['amount'] ) ? $data['amount'] : '';
			$currency = isset( $data['currency'] ) ? $data['currency'] : '';
			$cart_id  = isset( $data['cartId'] ) ? $data['cartId'] : '';

			$signature = md5(
				sprintf(
					'%s:%s:%s:%s:%s',
					$md5_secret,
					$data['instId'],
					$amount,
					$currency,
					$cart_id
				)
			);

			if ( ! hash_equals( $signature, (string) $data['signature'] ) ) {
				return false;
			}
		}

		// Verify the amount and currency against the invoice. Worldpay sends the authorised values in authAmount/authCurrency, falling back to amount/currency.
		$paid_amount   = isset( $data['authAmount'] ) ? $data['authAmount'] : ( isset( $data['amount'] ) ? $data['amount'] : null );
		$paid_currency = isset( $data['authCurrency'] ) ? $data['authCurrency'] : ( isset( $data['currency'] ) ? $data['currency'] : null );

		if ( null === $paid_amount || null === $paid_currency ) {
			return false;
		}

		if ( wpinv_round_amount( $invoice_total ) !== wpinv_round_amount( $paid_amount ) ) {
			return false;
		}

		return strtolower( trim( (string) $invoice_currency ) ) === strtolower( trim( (string) $paid_currency ) );
	}

    /**
     * Displays a notice on the checkout page if sandbox is enabled.
     */
    public function sandbox_notice( $description, $gateway ) {
        if ( 'worldpay' == $gateway && wpinv_is_test_mode( 'worldpay' ) ) {
            $description .= '<br>' . sprintf(
                __( 'SANDBOX ENABLED. See the %1$sWorldpay Sandbox Testing Guide%2$s for more details.', 'invoicing' ),
                '<a href="https://developer.worldpay.com/docs/wpg/directintegration/abouttesting">',
                '</a>'
            );
        }
        return $description;

    }

    /**
	 * Shows an admin notice when the gateway is active but no Payment Response
	 * password or MD5 secret has been configured to verify notifications.
	 *
	 * @return void
	 */
	public function maybe_show_ipn_security_notice() {

        // Only show to users who can act on it, and only when the gateway is active.
        if ( ! current_user_can( 'manage_options' ) || ! wpinv_is_gateway_active( $this->id ) ) {
            return;
        }

        // Bail if a shared secret has been configured.
        if ( '' !== (string) $this->get_option( 'callbackPW' ) || '' !== (string) $this->get_option( 'md5_secret' ) ) {
            return;
        }

        $url = esc_url( admin_url( 'admin.php?page=wpinv-settings&tab=gateways&section=worldpay' ) );

        echo wp_kses_post(
            sprintf(
                '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
                esc_html__( 'GetPaid Worldpay:', 'invoicing' ),
                esc_html__( 'Worldpay is active but has no Payment Response password set — without it, invoices can be marked as paid without a real payment.', 'invoicing' ),
                $url,
                esc_html__( 'Configure Worldpay', 'invoicing' )
            )
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
            'desc' => __( 'Recommended. Enter your MD5 secret here. Next, open your installation settings and ensure that your SignatureFields parameter is set to ', 'invoicing' ) . '<code>instId:amount:currency:cartId</code>',
        );

        $admin_settings['worldpay_callbackPW'] = array(
            'type'     => 'text',
            'id'       => 'worldpay_callbackPW',
            'name'     => __( 'Payment Response password', 'invoicing' ),
            'required' => true,
            'desc'     => __( 'Required for security. Enter your WorldPay Payment Response password so that payment notifications can be verified before invoices are marked as paid.', 'invoicing' ),
        );

        $admin_settings['worldpay_ipn_url'] = array(
            'type'     => 'ipn_url',
            'id'       => 'worldpay_ipn_url',
            'name'     => __( 'Payment Response URL', 'invoicing' ),
            'std'      => $this->notify_url,
            'desc'     => __( 'Login to your Worldpay Merchant Interface then enable Payment Response & Shopper Response. Next, go to the Payment Response URL field and enter the above URL.', 'invoicing' ),
            'custom'   => 'worldpay',
            'readonly' => true,
        );

		return $admin_settings;
	}

}
