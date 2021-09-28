<?php
/**
 * Bank transfer payment gateway
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bank transfer Payment Gateway class.
 *
 */
class GetPaid_Bank_Transfer_Gateway extends GetPaid_Payment_Gateway {

    /**
	 * Payment method id.
	 *
	 * @var string
	 */
    public $id = 'bank_transfer';

	/**
	 * An array of features that this gateway supports.
	 *
	 * @var array
	 */
	protected $supports = array( 'subscription', 'addons', 'single_subscription_group', 'multiple_subscription_groups' );

    /**
	 * Payment method order.
	 *
	 * @var int
	 */
	public $order = 8;

    /**
	 * Class constructor.
	 */
	public function __construct() {
        parent::__construct();

        $this->title                = __( 'Direct bank transfer', 'invoicing' );
        $this->method_title         = __( 'Bank transfer', 'invoicing' );
        $this->checkout_button_text = __( 'Proceed', 'invoicing' );
        $this->instructions         = apply_filters( 'wpinv_bank_instructions', $this->get_option( 'info' ) );

		add_action( 'wpinv_receipt_end', array( $this, 'thankyou_page' ) );
		add_action( 'getpaid_invoice_line_items', array( $this, 'thankyou_page' ), 40 );
		add_action( 'wpinv_pdf_content_billing', array( $this, 'thankyou_page' ), 11 );
		add_action( 'wpinv_email_invoice_details', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'getpaid_should_renew_subscription', array( $this, 'maybe_renew_subscription' ) );
		add_action( 'getpaid_invoice_status_publish', array( $this, 'invoice_paid' ), 20 );

    }

    /**
	 * Process Payment.
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @param array $submission_data Posted checkout fields.
	 * @param GetPaid_Payment_Form_Submission $submission Checkout submission.
	 * @return array
	 */
	public function process_payment( $invoice, $submission_data, $submission ) {

        // Add a transaction id.
        $invoice->set_transaction_id( $invoice->generate_key('bt_') );

        // Set it as pending payment.
        if ( ! $invoice->needs_payment() ) {
            $invoice->mark_paid();
        } else if ( ! $invoice->is_paid() ) {
            $invoice->set_status( 'wpi-onhold' );
        }

        // Save it.
        $invoice->save();

        // Send to the success page.
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

    }

    /**
	 * Output for the order received page.
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 */
	public function thankyou_page( $invoice ) {

        if ( 'bank_transfer' === $invoice->get_gateway() && $invoice->needs_payment() ) {

			echo '<div class="mt-4 mb-2 getpaid-bank-transfer-details">' . PHP_EOL;

            if ( ! empty( $this->instructions ) ) {
                echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
			}

			$this->bank_details( $invoice );

			echo '</div>';

        }

	}

    /**
	 * Add content to the WPI emails.
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @param string     $email_type Email format: plain text or HTML.
	 * @param bool     $sent_to_admin Sent to admin.
	 */
	public function email_instructions( $invoice, $email_type, $sent_to_admin ) {

		if ( ! $sent_to_admin && 'bank_transfer' === $invoice->get_gateway() && $invoice->needs_payment() ) {

			echo '<div class="wpi-email-row getpaid-bank-transfer-details">';

			if ( $this->instructions ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
            }

			$this->bank_details( $invoice );

			echo '</div>';

		}

    }

    /**
	 * Get bank details and place into a list format.
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 */
	protected function bank_details( $invoice ) {

		// Get the invoice country and country $locale.
		$country = $invoice->get_country();
		$locale  = $this->get_country_locale();

		// Get sortcode label in the $locale array and use appropriate one.
		$sortcode = isset( $locale[ $country ]['sortcode']['label'] ) ? $locale[ $country ]['sortcode']['label'] : __( 'Sort code', 'invoicing' );

        $bank_fields = array(
            'ac_name'     => __( 'Account Name', 'invoicing' ),
            'ac_no'       => __( 'Account Number', 'invoicing' ),
            'bank_name'   => __( 'Bank Name', 'invoicing' ),
            'ifsc'        => __( 'IFSC code', 'invoicing' ),
            'iban'        => __( 'IBAN', 'invoicing' ),
            'bic'         => __( 'BIC/Swift code', 'invoicing' ),
            'sort_code'   => $sortcode,
        );

        $bank_info = array();

        foreach ( $bank_fields as $field => $label ) {
            $value = $this->get_option( $field );

            if ( ! empty( $value ) ) {
                $bank_info[$field] = array( 'label' => $label, 'value' => $value );
            }

        }

        $bank_info = apply_filters( 'wpinv_bank_info', $bank_info );

        if ( empty( $bank_info ) ) {
            return;
        }

		echo '<h3 class="getpaid-bank-transfer-title"> ' . apply_filters( 'wpinv_receipt_bank_details_title', __( 'Bank Details', 'invoicing' ) ) . '</h3>' . PHP_EOL;

		echo '<table class="table table-bordered getpaid-bank-transfer-details">' . PHP_EOL;

		foreach ( $bank_info as $key => $data ) {

			$key   = sanitize_html_class( $key );
			$label = wp_kses_post( $data['label'] );
			$value = wp_kses_post( wptexturize( $data['value'] ) );

			echo "<tr class='getpaid-bank-transfer-$key'><th class='font-weight-bold'>$label</th><td class='w-75'>$value</td></tr>" . PHP_EOL;
		}

		echo '</table>';

    }

    /**
	 * Get country locale if localized.
	 *
	 * @return array
	 */
	public function get_country_locale() {

		if ( empty( $this->locale ) ) {

			// Locale information to be used - only those that are not 'Sort Code'.
			$this->locale = apply_filters(
				'getpaid_get_bank_transfer_locale',
				array(
					'AU' => array(
						'sortcode' => array(
							'label' => __( 'BSB', 'invoicing' ),
						),
					),
					'CA' => array(
						'sortcode' => array(
							'label' => __( 'Bank transit number', 'invoicing' ),
						),
					),
					'IN' => array(
						'sortcode' => array(
							'label' => __( 'IFSC', 'invoicing' ),
						),
					),
					'IT' => array(
						'sortcode' => array(
							'label' => __( 'Branch sort', 'invoicing' ),
						),
					),
					'NZ' => array(
						'sortcode' => array(
							'label' => __( 'Bank code', 'invoicing' ),
						),
					),
					'SE' => array(
						'sortcode' => array(
							'label' => __( 'Bank code', 'invoicing' ),
						),
					),
					'US' => array(
						'sortcode' => array(
							'label' => __( 'Routing number', 'invoicing' ),
						),
					),
					'ZA' => array(
						'sortcode' => array(
							'label' => __( 'Branch code', 'invoicing' ),
						),
					),
				)
			);

		}

		return $this->locale;

	}

	/**
	 * Filters the gateway settings.
	 *
	 * @param array $admin_settings
	 */
	public function admin_settings( $admin_settings ) {

        $admin_settings['bank_transfer_desc']['std']    = __( "Make your payment directly into our bank account. Please use your Invoice Number as the payment reference. Your invoice won't be processed until the funds have cleared in our account.", 'invoicing' );
		$admin_settings['bank_transfer_active']['desc'] = __( 'Enable bank transfer', 'invoicing' );

		$locale  = $this->get_country_locale();

		// Get sortcode label in the $locale array and use appropriate one.
		$country  = wpinv_default_billing_country();
		$sortcode = isset( $locale[ $country ]['sortcode']['label'] ) ? $locale[ $country ]['sortcode']['label'] : __( 'Sort code', 'invoicing' );

		$admin_settings['bank_transfer_ac_name'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_ac_name',
            'name' => __( 'Account Name', 'invoicing' ),
		);

		$admin_settings['bank_transfer_ac_no'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_ac_no',
            'name' => __( 'Account Number', 'invoicing' ),
		);

		$admin_settings['bank_transfer_bank_name'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_bank_name',
            'name' => __( 'Bank Name', 'invoicing' ),
		);

		$admin_settings['bank_transfer_ifsc'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_ifsc',
            'name' => __( 'IFSC Code', 'invoicing' ),
		);

		$admin_settings['bank_transfer_iban'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_iban',
            'name' => __( 'IBAN', 'invoicing' ),
		);

		$admin_settings['bank_transfer_bic'] = array(
            'type' => 'text',
            'id'   => 'bank_transfer_bic',
            'name' => __( 'BIC/Swift Code', 'invoicing' ),
		);

		$admin_settings['bank_transfer_sort_code'] = array(
			'type' => 'text',
			'id'   => 'bank_transfer_sort_code',
			'name' => $sortcode,
		);

		$admin_settings['bank_transfer_info'] = array(
            'id'   => 'bank_transfer_info',
            'name' => __( 'Instructions', 'invoicing' ),
            'desc' => __( 'Instructions that will be added to the thank you page and emails.', 'invoicing' ),
            'type' => 'textarea',
            'std'  => __( "Make your payment directly into our bank account. Please use your Invoice Number as the payment reference. Your invoice won't be processed until the funds have cleared in our account.", 'invoicing' ),
            'cols' => 50,
            'rows' => 5
        );

		return $admin_settings;
	}

	/**
	 * Processes invoice addons.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param GetPaid_Form_Item[] $items
	 * @return WPInv_Invoice
	 */
	public function process_addons( $invoice, $items ) {

        foreach ( $items as $item ) {
            $invoice->add_item( $item );
        }

        $invoice->recalculate_total();
        $invoice->save();
	}

	/**
	 * (Maybe) renews a bank transfer subscription profile.
	 *
	 *
     * @param WPInv_Subscription $subscription
	 */
	public function maybe_renew_subscription( $subscription ) {

        // Ensure its our subscription && it's active.
        if ( $this->id == $subscription->get_gateway() && $subscription->has_status( 'active trialling' ) ) {
			$subscription->create_payment();
        }

    }

	/**
	 * Process a bank transfer payment.
	 *
	 *
     * @param WPInv_Invoice $invoice
	 */
	public function invoice_paid( $invoice ) {

		// Abort if not paid by bank transfer.
		if ( $this->id !== $invoice->get_gateway() || ! $invoice->is_recurring() ) {
			return;
		}

		// Is it a parent payment?
		if ( 0 == $invoice->get_parent_id() ) {

			// (Maybe) activate subscriptions.
			$subscriptions = getpaid_get_invoice_subscriptions( $invoice );

			if ( ! empty( $subscriptions ) ) {
				$subscriptions = is_array( $subscriptions ) ? $subscriptions : array( $subscriptions );

				foreach ( $subscriptions as $subscription ) {
					if ( $subscription->exists() ) {
						$duration = strtotime( $subscription->get_expiration() ) - strtotime( $subscription->get_date_created() );
						$expiry   = date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $duration ) );

						$subscription->set_next_renewal_date( $expiry );
						$subscription->set_date_created( current_time( 'mysql' ) );
						$subscription->set_profile_id( 'bt_sub_' . $invoice->get_id() . '_' . $subscription->get_id() );
						$subscription->activate();
					}
				}

			}

		} else {

			$subscription = getpaid_get_subscription( $invoice->get_subscription_id() );

			// Renew the subscription.
			if ( $subscription && $subscription->exists() ) {
				$subscription->add_payment( array(), $invoice );
				$subscription->renew();
			}

		}

    }

}
