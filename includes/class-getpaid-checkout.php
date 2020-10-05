<?php
/**
 * Contains the Main Checkout Class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Checkout Class.
 *
 */
class GetPaid_Checkout {

	/**
	 * @var GetPaid_Payment_Form_Submission
	 */
	protected $payment_form_submission;

	/**
	 * Class constructor.
	 * 
	 * @param GetPaid_Payment_Form_Submission $submission
	 */
	public function __construct( $submission ) {
		$this->payment_form_submission = $submission;
	}

	/**
	 * Processes the checkout.
	 *
	 */
	public function process_checkout() {

		// Validate the submission.
		$this->validate_submission();

		// Get the items and invoice.
		$items      = $this->get_submission_items();
		$invoice    = $this->get_submission_invoice();
		$invoice    = $this->process_submission_invoice( $invoice, $items );
		$prepared   = $this->prepare_submission_data_for_saving( $invoice );

		// Save the invoice.
		$invoice->recalculate_total();
        $invoice->save();

		// Send to the gateway.
		$this->post_process_submission( $invoice, $prepared );
	}

	/**
	 * Validates the submission.
	 *
	 */
	protected function validate_submission() {

		$submission = $this->payment_form_submission;
		$data       = $submission->get_data();

		// Do we have an error?
        if ( ! empty( $submission->last_error ) ) {
			wp_send_json_error( $submission->last_error );
        }

		// We need a billing email.
        if ( ! $submission->has_billing_email() || ! is_email( $submission->get_billing_email() ) ) {
            wp_send_json_error( __( 'Provide a valid billing email.', 'invoicing' ) );
		}

		// Non-recurring gateways should not be allowed to process recurring invoices.
		if ( $submission->has_recurring && ! wpinv_gateway_support_subscription( $data['wpi-gateway'] ) ) {
			wp_send_json_error( __( 'The selected payment gateway does not support subscription payment.', 'invoicing' ) );
		}
	
		// Ensure the gateway is active.
		if ( ! wpinv_is_gateway_active( $data['wpi-gateway'] ) ) {
			wpinv_set_error( 'invalid_gateway', __( 'The selected payment gateway is not active', 'invoicing' ) );
		}

		// Clear any existing errors.
		wpinv_clear_errors();
		
		// Allow themes and plugins to hook to errors
		do_action( 'getpaid_checkout_error_checks', $submission );
		
		// Do we have any errors?
        if ( wpinv_get_errors() ) {
            wp_send_json_error( getpaid_get_errors_html() );
		}

	}

	/**
	 * Retrieves submission items.
	 *
	 * @return GetPaid_Form_Item[]
	 */
	protected function get_submission_items() {

		$items = $this->payment_form_submission->get_items();

        // Ensure that we have items.
        if ( empty( $items ) && 0 == count( $this->payment_form_submission->get_fees() ) ) {
            wp_send_json_error( __( 'Please select at least one item.', 'invoicing' ) );
		}

		return $items;
	}

	/**
	 * Retrieves submission invoice.
	 *
	 * @return WPInv_Invoice
	 */
	protected function get_submission_invoice() {
		$submission = $this->payment_form_submission;

		if ( ! $submission->has_invoice() ) {
			return new WPInv_Invoice();
        }

		$invoice = $submission->get_invoice();

		// Make sure that it is neither paid or refunded.
		if ( $invoice->is_paid() || $invoice->is_refunded() ) {
			wp_send_json_error( __( 'This invoice has already been paid for.', 'invoicing' ) );
		}

		return $invoice;
	}

	/**
	 * Processes the submission invoice.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param GetPaid_Form_Item[] $items
	 * @return WPInv_Invoice
	 */
	protected function process_submission_invoice( $invoice, $items ) {

		$submission = $this->payment_form_submission;
		$data       = $submission->get_data();

		// Set-up the invoice details.
		$invoice->set_email( sanitize_email( $submission->get_billing_email() ) );
		$invoice->set_user_id( $this->get_submission_customer() );
		$invoice->set_payment_form( absint( $submission->get_payment_form()->get_id() ) );
        $invoice->set_items( $items );
        $invoice->set_fees( $submission->get_fees() );
        $invoice->set_taxes( $submission->get_taxes() );
		$invoice->set_discounts( $submission->get_discounts() );
		$invoice->set_gateway( $data['wpi-gateway'] );

		if ( $submission->has_discount_code() ) {
            $invoice->set_discount_code( $submission->get_discount_code() );
		}

		getpaid_maybe_add_default_address( $invoice );
		return $invoice;
	}

	/**
	 * Retrieves the submission's customer.
	 *
	 * @return int The customer id.
	 */
	protected function get_submission_customer() {
		$submission = $this->payment_form_submission;

		// If this is an existing invoice...
		if ( $submission->has_invoice() ) {
			return $submission->get_invoice()->get_user_id();
		}

		// (Maybe) create the user.
        $user = get_current_user_id();

        if ( empty( $user ) ) {
            $user = get_user_by( 'email', $submission->get_billing_email() );
        }

        if ( empty( $user ) ) {
            $user = wpinv_create_user( $submission->get_billing_email() );
        }

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( $user->get_error_message() );
        }

        if ( is_numeric( $user ) ) {
            return $user;
		}

		return $user->ID;

	}

	/**
     * Prepares submission data for saving to the database.
     *
	 * @param WPInv_Invoice $invoice
     */
    public function prepare_submission_data_for_saving( &$invoice ) {

		$submission = $this->payment_form_submission;

		// Prepared submission details.
        $prepared = array();

        // Raw submission details.
		$data = $submission->get_data();
		
		// Loop throught the submitted details.
        foreach ( $submission->get_payment_form()->get_elements() as $field ) {

			// Skip premade fields.
            if ( ! empty( $field['premade'] ) ) {
                continue;
            }

            // If it is required and not set, abort.
            if ( ! $submission->is_required_field_set( $field ) ) {
                wp_send_json_error( __( 'Some required fields are not set.', 'invoicing' ) );
            }

            // Handle address fields.
            if ( $field['type'] == 'address' ) {

                foreach ( $field['fields'] as $address_field ) {

                    // skip if it is not visible.
                    if ( empty( $address_field['visible'] ) ) {
                        continue;
                    }

                    // If it is required and not set, abort
                    if ( ! empty( $address_field['required'] ) && empty( $data[ $address_field['name'] ] ) ) {
                        wp_send_json_error( __( 'Some required fields are not set.', 'invoicing' ) );
                    }

                    if ( isset( $data[ $address_field['name'] ] ) ) {
                        $name   = str_replace( 'wpinv_', '', $address_field['name'] );
                        $method = "set_$name";
                        $invoice->$method( wpinv_clean( $data[ $address_field['name'] ] ) );
                    }

                }

            } else if ( isset( $data[ $field['id'] ] ) ) {
                $label = $field['id'];

                if ( isset( $field['label'] ) ) {
                    $label = $field['label'];
                }

                $prepared[ wpinv_clean( $label ) ] = wpinv_clean( $data[ $field['id'] ] );
            }

		}
		
		return $prepared;

	}

	/**
	 * Confirms the submission is valid and send users to the gateway.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param array $prepared_payment_form_data
	 */
	protected function post_process_submission( $invoice, $prepared_payment_form_data ) {

		// Ensure the invoice exists.
        if ( $invoice->get_id() == 0 ) {
            wp_send_json_error( __( 'An error occured while saving your invoice.', 'invoicing' ) );
        }

		// Was this invoice created via the payment form?
        if ( ! $this->payment_form_submission->has_invoice() ) {
            update_post_meta( $invoice->get_id(), 'wpinv_created_via', 'payment_form' );
        }

        // Save payment form data.
        if ( ! empty( $prepared_payment_form_data ) ) {
            update_post_meta( $invoice->get_id(), 'payment_form_data', $prepared_payment_form_data );
		}

		// Backwards compatibility.
        add_filter( 'wp_redirect', array( $this, 'send_redirect_response' ) );
		add_action( 'wpinv_pre_send_back_to_checkout', array( $this, 'checkout_error' ) );

		$this->process_payment( $invoice );

        // If we are here, there was an error.
		$this->checkout_error();

	}

	/**
	 * Processes the actual payment.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	protected function process_payment( $invoice ) {

		$submission = $this->payment_form_submission;

		// No need to send free invoices to the gateway.
		if ( $invoice->is_free() ) {
			$invoice->set_gateway( 'none' );
			$invoice->add_note( __( "This is a free invoice and won't be sent to the payment gateway", 'invoicing' ), false, false, true );
			$invoice->mark_paid();
			wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
		}

		// Clear any checkout errors.
		wpinv_clear_errors();

		// Fires before sending to the gateway.
		do_action( 'getpaid_checkout_before_gateway', $invoice, $submission );

		// Allow the sumission data to be modified before it is sent to the gateway.
		$submission_data    = $submission->get_data();
		$submission_gateway = apply_filters( 'getpaid_gateway_submission_gateway', $invoice->get_gateway(), $submission, $invoice );
		$submission_data    = apply_filters( 'getpaid_gateway_submission_data', $submission_data, $submission, $invoice );

		// Validate the currency.
		if ( ! apply_filters( "getpaid_gateway_{$submission_gateway}_is_valid_for_currency", true, $invoice->get_currency() ) ) {
			wpinv_set_error( 'invalid_currency', __( 'The chosen payment gateway does not support the invoice currency', 'invoicing' ) );
		}

		// Check to see if we have any errors.
		if ( wpinv_get_errors() ) {
			wpinv_send_back_to_checkout();
		}

		// Send info to the gateway for payment processing
		do_action( "getpaid_gateway_$submission_gateway", $invoice, $submission_data, $submission );

		// Backwards compatibility.
		wpinv_send_to_gateway( $submission_gateway, $invoice );

	}

	/**
     * Sends a redrect response to payment details.
     *
     */
    public function send_redirect_response( $url ) {
        $url = urlencode( $url );
        wp_send_json_success( $url );
    }

    /**
     * Fired when a checkout error occurs
     *
     */
    public function checkout_error() {

        // Do we have any errors?
        if ( wpinv_get_errors() ) {
            wp_send_json_error( getpaid_get_errors_html() );
		}

        wp_send_json_error( __( 'An error occured while processing your payment. Please try again.', 'invoicing' ) );

	}

}
