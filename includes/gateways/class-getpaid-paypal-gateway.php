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
    protected $supports = array( 'subscription', 'sandbox', 'single_subscription_group' );

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
        add_filter( 'getpaid_paypal_sandbox_notice', array( $this, 'sandbox_notice' ) );
		add_filter( 'getpaid_get_paypal_connect_url', array( $this, 'maybe_get_connect_url' ), 10, 2 );
		add_action( 'getpaid_authenticated_admin_action_connect_paypal', array( $this, 'connect_paypal' ) );

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
		$force_one_line_item = apply_filters( 'getpaid_paypal_force_one_line_item', true, $invoice );

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

		$email = $this->is_sandbox( $invoice ) ? wpinv_get_option( 'paypal_sandbox_email', wpinv_get_option( 'paypal_email', '' ) ) : wpinv_get_option( 'paypal_email', '' );
		return array(
            'cmd'           => '_cart',
            'business'      => $email,
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
		$this->add_line_item( $item_name, 1, wpinv_round_amount( (float) $invoice->get_total(), 2, true ), $invoice->get_id() );

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
			$amount   = $item->get_price();
			$quantity = $invoice->get_template() == 'amount' ? 1 : $item->get_quantity();
			$this->add_line_item( $item->get_raw_name(), $quantity, $amount, $item->get_id() );
        }

        // Fees.
		foreach ( $invoice->get_fees() as $fee => $data ) {
            $this->add_line_item( $fee, 1, wpinv_sanitize_amount( $data['initial_fee'] ) );
        }

    }

    /**
	 * Add PayPal Line Item.
	 *
	 * @param  string $item_name Item name.
	 * @param  float    $quantity Item quantity.
	 * @param  float  $amount Amount.
	 * @param  string $item_number Item number.
	 */
	protected function add_line_item( $item_name, $quantity = 1, $amount = 0.0, $item_number = '' ) {
		$index = ( count( $this->line_items ) / 4 ) + 1;

		$item = apply_filters(
			'getpaid_paypal_line_item',
			array(
				'item_name'   => html_entity_decode( getpaid_limit_length( $item_name ? wp_strip_all_tags( $item_name ) : __( 'Item', 'invoicing' ), 127 ), ENT_NOQUOTES, 'UTF-8' ),
				'quantity'    => (float) $quantity,
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
		$this->line_items[ 'amount_' . $index ]      = $item['amount'] * $item['quantity'];
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
        if ( ! $invoice->is_recurring() || ! $subscription = getpaid_get_invoice_subscription( $invoice ) ) {
            return $paypal_args;
        }

        // It's a subscription
        $paypal_args['cmd'] = '_xclick-subscriptions';

        // Subscription name.
        $paypal_args['item_name'] = sprintf( __( 'Invoice #%s', 'invoicing' ), $invoice->get_number() );

        // Get subscription args.
        $period                 = strtoupper( substr( $subscription->get_period(), 0, 1) );
        $interval               = (int) $subscription->get_frequency();
        $bill_times             = (int) $subscription->get_bill_times();
        $initial_amount         = (float) wpinv_sanitize_amount( $invoice->get_initial_total(), 2 );
        $recurring_amount       = (float) wpinv_sanitize_amount( $invoice->get_recurring_total(), 2 );
        $subscription_item      = $invoice->get_recurring( true );

		// Convert 365 days to 1 year.
		if ( 'D' == $period && 365 == $interval ) {
			$period = 'Y';
			$interval = 1;
		}

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

            $paypal_args[ 'a' . $param_number ] = $initial_amount ? $initial_amount : 0;

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
        new GetPaid_Paypal_Gateway_IPN_Handler( $this );
    }

    /**
     * Returns a sandbox notice.
     */
    public function sandbox_notice() {

        return sprintf(
			__( 'SANDBOX ENABLED. You can use sandbox testing accounts only. See the %sPayPal Sandbox Testing Guide%s for more details.', 'invoicing' ),
			'<a href="https://developer.paypal.com/docs/classic/lifecycle/ug_sandbox/">',
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

        $admin_settings['paypal_active']['desc'] .= " ($currencies)";
        $admin_settings['paypal_desc']['std']     = __( 'Pay via PayPal: you can pay with your credit card if you don\'t have a PayPal account.', 'invoicing' );

		// Access tokens.
		$live_email      = wpinv_get_option( 'paypal_email' );
		$sandbox_email   = wpinv_get_option( 'paypal_sandbox_email' );

		$admin_settings['paypal_connect'] = array(
			'type'       => 'raw_html',
			'id'         => 'paypal_connect',
			'name'       => __( 'Connect to PayPal', 'invoicing' ),
			'desc'       => sprintf(
				'<div class="wpinv-paypal-connect-live"><a class="button button-primary" href="%s">%s</a></div><div class="wpinv-paypal-connect-sandbox"><a class="button button-primary" href="%s">%s</a></div>%s',
				esc_url( self::get_connect_url( false ) ),
				__( 'Connect to PayPal', 'invoicing' ),
				esc_url( self::get_connect_url( true ) ),
				__( 'Connect to PayPal Sandox', 'invoicing' ),
				$this->get_js()
			),
		);

        $admin_settings['paypal_email'] = array(
            'type'  => 'text',
			'class' => 'live-auth-data',
            'id'    => 'paypal_email',
            'name'  => __( 'Live Email Address', 'invoicing' ),
            'desc'  => __( 'The email address of your PayPal account.', 'invoicing' ),
        );

		$admin_settings['paypal_sandbox_email'] = array(
            'type'  => 'text',
			'class' => 'sandbox-auth-data',
            'id'    => 'paypal_sandbox_email',
            'name'  => __( 'Sandbox Email Address', 'invoicing' ),
            'desc'  => __( 'The email address of your sandbox PayPal account.', 'invoicing' ),
			'std'   => wpinv_get_option( 'paypal_email', '' ),
        );

        $admin_settings['paypal_ipn_url'] = array(
            'type'     => 'ipn_url',
            'id'       => 'paypal_ipn_url',
            'name'     => __( 'IPN Url', 'invoicing' ),
            'std'      => $this->notify_url,
            'desc'     => __( "If you've not enabled IPNs in your paypal account, use the above URL to enable them.", 'invoicing' ) . ' <a href="https://developer.paypal.com/docs/api-basics/notifications/ipn/"><em>' . __( 'Learn more.', 'invoicing' ) . '</em></a>',
            'readonly' => true,
        );

		return $admin_settings;
	}

	/**
	 * Retrieves the PayPal connect URL when using the setup wizzard.
	 *
	 *
     * @param array $data
     * @return string
	 */
	public static function maybe_get_connect_url( $url = '', $data = array() ) {
		return self::get_connect_url( false, urldecode( $data['redirect'] ) );
	}

	/**
	 * Retrieves the PayPal connect URL.
	 *
	 *
     * @param bool $is_sandbox
	 * @param string $redirect
     * @return string
	 */
	public static function get_connect_url( $is_sandbox, $redirect = '' ) {

        $redirect_url = add_query_arg(
            array(
                'getpaid-admin-action' => 'connect_paypal',
                'page'                 => 'wpinv-settings',
                'live_mode'            => (int) empty( $is_sandbox ),
                'tab'                  => 'gateways',
                'section'              => 'paypal',
                'getpaid-nonce'        => wp_create_nonce( 'getpaid-nonce' ),
				'redirect'             => urlencode( $redirect ),
            ),
            admin_url( 'admin.php' )
        );

        return add_query_arg(
            array(
                'live_mode'    => (int) empty( $is_sandbox ),
                'redirect_url' => urlencode( str_replace( '&amp;', '&', $redirect_url ) )
            ),
            'https://ayecode.io/oauth/paypal'
        );

    }

	/**
	 * Generates settings page js.
	 *
     * @return void
	 */
	public static function get_js() {
        ob_start();
        ?>
            <script>
                jQuery(document).ready(function() {

                    jQuery( '#wpinv-settings-paypal_sandbox' ).on ( 'change', function( e ) {

						jQuery( '.wpinv-paypal-connect-live, .live-auth-data' ).toggle( ! this.checked )
						jQuery( '.wpinv-paypal-connect-sandbox, .sandbox-auth-data' ).toggle( this.checked )

						if ( this.checked ) {

							if ( jQuery('#wpinv-settings-paypal_sandbox_email').val().length > 0 ) {
								jQuery('.wpinv-paypal-connect-sandbox').closest('tr').hide()
							} else {
								jQuery('.wpinv-paypal-connect-sandbox').closest('tr').show()
							}
						} else {
							if ( jQuery('#wpinv-settings-paypal_email').val().length > 0 ) {
								jQuery('.wpinv-paypal-connect-live').closest('tr').hide()
							} else {
								jQuery('.wpinv-paypal-connect-live').closest('tr').show()
							}
						}
                    })

                    // Set initial state.
                    jQuery( '#wpinv-settings-paypal_sandbox' ).trigger( 'change' )

                });
            </script>
        <?php
        return ob_get_clean();
    }

	/**
	 * Connects to PayPal.
	 *
	 * @param array $data Connection data.
	 * @return void
	 */
	public function connect_paypal( $data ) {

		$sandbox      = $this->is_sandbox();
		$data         = wp_unslash( $data );
		$access_token = empty( $data['access_token'] ) ? '' : sanitize_text_field( $data['access_token'] );

		if ( isset( $data['live_mode'] ) ) {
			$sandbox = empty( $data['live_mode'] );
		}

		wpinv_update_option( 'paypal_sandbox', (int) $sandbox );
		wpinv_update_option( 'paypal_active', 1 );

		if ( ! empty( $data['error_description'] ) ) {
			getpaid_admin()->show_error( wp_kses_post( urldecode( $data['error_description'] ) ) );
		} else {

			// Retrieve the user info.
			$user_info = wp_remote_get(
				! $sandbox ? 'https://api-m.paypal.com/v1/identity/oauth2/userinfo?schema=paypalv1.1' : 'https://api-m.sandbox.paypal.com/v1/identity/oauth2/userinfo?schema=paypalv1.1',
				array(

					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-type'  => 'application/json',
					)

				)
			);

			if ( is_wp_error( $user_info ) ) {
				getpaid_admin()->show_error( wp_kses_post( $user_info->get_error_message() ) );
			} else {

				// Create application.
				$user_info = json_decode( wp_remote_retrieve_body( $user_info ) );

				if ( $sandbox ) {
					wpinv_update_option( 'paypal_sandbox_email', sanitize_email( $user_info->emails[0]->value ) );
					wpinv_update_option( 'paypal_sandbox_refresh_token', sanitize_text_field( urldecode( $data['refresh_token'] ) ) );
					set_transient( 'getpaid_paypal_sandbox_access_token', sanitize_text_field( urldecode( $data['access_token'] ) ), (int) $data['expires_in'] );
					getpaid_admin()->show_success( __( 'Successfully connected your PayPal sandbox account', 'invoicing' ) );
				} else {
					wpinv_update_option( 'paypal_email', sanitize_email( $user_info->emails[0]->value ) );
					wpinv_update_option( 'paypal_refresh_token', sanitize_text_field( urldecode( $data['refresh_token'] ) ) );
					set_transient( 'getpaid_paypal_access_token', sanitize_text_field( urldecode( $data['access_token'] ) ), (int) $data['expires_in'] );
					getpaid_admin()->show_success( __( 'Successfully connected your PayPal account', 'invoicing' ) );
				}

			}

		}

		$redirect = empty( $data['redirect'] ) ? admin_url( 'admin.php?page=wpinv-settings&tab=gateways&section=paypal' ) : urldecode( $data['redirect'] );

		if ( isset( $data['step'] ) ) {
			$redirect = add_query_arg( 'step', $data['step'], $redirect );
		}
		wp_redirect( $redirect );
		exit;
	}

}
