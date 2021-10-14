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

		// Prepare the invoice.
		$items      = $this->get_submission_items();
		$invoice    = $this->get_submission_invoice();
		$invoice    = $this->process_submission_invoice( $invoice, $items );
		$prepared   = $this->prepare_submission_data_for_saving();

		$this->prepare_billing_info( $invoice );

		$shipping   = $this->prepare_shipping_info( $invoice );

		// Save the invoice.
		$invoice->set_is_viewed( true );
		$invoice->recalculate_total();
        $invoice->save();

		do_action( 'getpaid_checkout_invoice_updated', $invoice );

		// Send to the gateway.
		$this->post_process_submission( $invoice, $prepared, $shipping );
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
        if ( ! $submission->has_billing_email() ) {
            wp_send_json_error( __( 'Provide a valid billing email.', 'invoicing' ) );
		}

		// Non-recurring gateways should not be allowed to process recurring invoices.
		if ( $submission->should_collect_payment_details() && $submission->has_recurring && ! wpinv_gateway_support_subscription( $data['wpi-gateway'] ) ) {
			wp_send_json_error( __( 'The selected payment gateway does not support subscription payments.', 'invoicing' ) );
		}

		// Ensure the gateway is active.
		if ( $submission->should_collect_payment_details() && ! wpinv_is_gateway_active( $data['wpi-gateway'] ) ) {
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
        if ( empty( $items ) && ! $this->payment_form_submission->has_fees() ) {
            wp_send_json_error( __( 'Please provide at least one item or amount.', 'invoicing' ) );
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
			$invoice = new WPInv_Invoice();
			$invoice->set_created_via( 'payment_form' );
			return $invoice;
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

		// Set-up the invoice details.
		$invoice->set_email( sanitize_email( $submission->get_billing_email() ) );
		$invoice->set_user_id( $this->get_submission_customer() );
		$invoice->set_payment_form( absint( $submission->get_payment_form()->get_id() ) );
        $invoice->set_items( $items );
        $invoice->set_fees( $submission->get_fees() );
        $invoice->set_taxes( $submission->get_taxes() );
		$invoice->set_discounts( $submission->get_discounts() );
		$invoice->set_gateway( $submission->get_field( 'wpi-gateway' ) );
		$invoice->set_currency( $submission->get_currency() );

		$address_confirmed = $submission->get_field( 'confirm-address' );
		$invoice->set_address_confirmed( ! empty( $address_confirmed ) );

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

			// (Maybe) send new user notification.
			$should_send_notification = wpinv_get_option( 'disable_new_user_emails' );
			if ( ! empty( $user ) && is_numeric( $user ) && apply_filters( 'getpaid_send_new_user_notification', empty( $should_send_notification ), $user ) ) {
				wp_send_new_user_notifications( $user, 'user' );
			}

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
	 * @return array
     */
    public function prepare_submission_data_for_saving() {

		$submission = $this->payment_form_submission;

		// Prepared submission details.
        $prepared = array(
			'all'  => array(),
			'meta' => array(),
		);

        // Raw submission details.
		$data     = $submission->get_data();

		// Loop through the submitted details.
        foreach ( $submission->get_payment_form()->get_elements() as $field ) {

			// Skip premade fields.
            if ( ! empty( $field['premade'] ) ) {
                continue;
            }

			// Ensure address is provided.
			if ( $field['type'] == 'address' ) {
                $address_type = isset( $field['address_type'] ) && 'shipping' === $field['address_type'] ? 'shipping' : 'billing';

				foreach ( $field['fields'] as $address_field ) {

					if ( ! empty( $address_field['visible'] ) && ! empty( $address_field['required'] ) && '' === trim( $_POST[ $address_type ][ $address_field['name'] ] ) ) {
						wp_send_json_error( __( 'Please fill all required fields.', 'invoicing' ) );
					}

				}

            }

            // If it is required and not set, abort.
            if ( ! $submission->is_required_field_set( $field ) ) {
                wp_send_json_error( __( 'Please fill all required fields.', 'invoicing' ) );
            }

            // Handle misc fields.
            if ( isset( $data[ $field['id'] ] ) ) {

				// Uploads.
				if ( $field['type'] == 'file_upload' ) {
					$max_file_num = empty( $field['max_file_num'] ) ? 1 : absint( $field['max_file_num'] );

					if ( count( $data[ $field['id'] ] ) > $max_file_num ) {
						wp_send_json_error( __( 'Maximum number of allowed files exceeded.', 'invoicing' ) );
					}

					$value = array();

					foreach ( $data[ $field['id'] ] as $url => $name ) {
						$value[] = sprintf(
							'<a href="%s" target="_blank">%s</a>',
							esc_url_raw( $url ),
							esc_html( $name )
						);
					}

					$value = implode( ' | ', $value );

				} else if ( $field['type'] == 'checkbox' ) {
					$value = isset( $data[ $field['id'] ] ) ? __( 'Yes', 'invoicing' ) : __( 'No', 'invoicing' );
				} else {
					$value = wp_kses_post( $data[ $field['id'] ] );
				}

                $label = $field['id'];

                if ( isset( $field['label'] ) ) {
                    $label = $field['label'];
                }

				if ( ! empty( $field['add_meta'] ) ) {
					$prepared['meta'][ wpinv_clean( $label ) ] = wp_kses_post_deep( $value );
				}
				$prepared['all'][ wpinv_clean( $label ) ] = wp_kses_post_deep( $value );

            }

		}

		return $prepared;

	}

	/**
     * Retrieves address details.
     *
	 * @return array
	 * @param WPInv_Invoice $invoice
	 * @param string $type
     */
    public function prepare_address_details( $invoice, $type = 'billing' ) {

		$data     = $this->payment_form_submission->get_data();
		$type     = sanitize_key( $type );
		$address  = array();
		$prepared = array();

		if ( ! empty( $data[ $type ] ) ) {
			$address = $data[ $type ];
		}

		// Clean address details.
		foreach ( $address as $key => $value ) {
			$key             = sanitize_key( $key );
			$key             = str_replace( 'wpinv_', '', $key );
			$value           = wpinv_clean( $value );
			$prepared[ $key] = apply_filters( "getpaid_checkout_{$type}_address_$key", $value, $this->payment_form_submission, $invoice );
		}

		// Filter address details.
		$prepared = apply_filters( "getpaid_checkout_{$type}_address", $prepared, $this->payment_form_submission, $invoice );

		// Remove non-whitelisted values.
		return array_filter( $prepared, 'getpaid_is_address_field_whitelisted', ARRAY_FILTER_USE_KEY );

	}

	/**
     * Prepares the billing details.
     *
	 * @return array
	 * @param WPInv_Invoice $invoice
     */
    protected function prepare_billing_info( &$invoice ) {

		$billing_address = $this->prepare_address_details( $invoice, 'billing' );

		// Update the invoice with the billing details.
		$invoice->set_props( $billing_address );

	}

	/**
     * Prepares the shipping details.
     *
	 * @return array
	 * @param WPInv_Invoice $invoice
     */
    protected function prepare_shipping_info( $invoice ) {

		$data = $this->payment_form_submission->get_data();

		if ( empty( $data['same-shipping-address'] ) ) {
			return $this->prepare_address_details( $invoice, 'shipping' );
		}

		return $this->prepare_address_details( $invoice, 'billing' );

	}

	/**
	 * Confirms the submission is valid and send users to the gateway.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param array $prepared_payment_form_data
	 * @param array $shipping
	 */
	protected function post_process_submission( $invoice, $prepared_payment_form_data, $shipping ) {

		// Ensure the invoice exists.
        if ( ! $invoice->exists() ) {
            wp_send_json_error( __( 'An error occured while saving your invoice. Please try again.', 'invoicing' ) );
        }

		// Save payment form data.
		$prepared_payment_form_data = apply_filters( 'getpaid_prepared_payment_form_data', $prepared_payment_form_data, $invoice );
        delete_post_meta( $invoice->get_id(), 'payment_form_data' );
		delete_post_meta( $invoice->get_id(), 'additional_meta_data' );
		if ( ! empty( $prepared_payment_form_data ) ) {

			if ( ! empty( $prepared_payment_form_data['all'] ) ) {
				update_post_meta( $invoice->get_id(), 'payment_form_data', $prepared_payment_form_data['all'] );
			}

			if ( ! empty( $prepared_payment_form_data['meta'] ) ) {
				update_post_meta( $invoice->get_id(), 'additional_meta_data', $prepared_payment_form_data['meta'] );
			}

		}

		// Save payment form data.
        if ( ! empty( $shipping ) ) {
            update_post_meta( $invoice->get_id(), 'shipping_address', $shipping );
		}

		// Backwards compatibility.
        add_filter( 'wp_redirect', array( $this, 'send_redirect_response' ) );

		$this->process_payment( $invoice );

        // If we are here, there was an error.
		wpinv_send_back_to_checkout( $invoice );

	}

	/**
	 * Processes the actual payment.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	protected function process_payment( $invoice ) {

		// Clear any checkout errors.
		wpinv_clear_errors();

		// No need to send free invoices to the gateway.
		if ( $invoice->is_free() ) {
			$this->process_free_payment( $invoice );
		}

		$submission = $this->payment_form_submission;

		// Fires before sending to the gateway.
		do_action( 'getpaid_checkout_before_gateway', $invoice, $submission );

		// Allow the sumission data to be modified before it is sent to the gateway.
		$submission_data    = $submission->get_data();
		$submission_gateway = apply_filters( 'getpaid_gateway_submission_gateway', $invoice->get_gateway(), $submission, $invoice );
		$submission_data    = apply_filters( 'getpaid_gateway_submission_data', $submission_data, $submission, $invoice );

		// Validate the currency.
		if ( ! apply_filters( "getpaid_gateway_{$submission_gateway}_is_valid_for_currency", true, $invoice->get_currency() ) ) {
			wpinv_set_error( 'invalid_currency', __( 'The chosen payment gateway does not support this currency', 'invoicing' ) );
		}

		// Check to see if we have any errors.
		if ( wpinv_get_errors() ) {
			wpinv_send_back_to_checkout( $invoice );
		}

		// Send info to the gateway for payment processing
		do_action( "getpaid_gateway_$submission_gateway", $invoice, $submission_data, $submission );

		// Backwards compatibility.
		wpinv_send_to_gateway( $submission_gateway, $invoice );

	}

	/**
	 * Marks the invoice as paid in case the checkout is free.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	protected function process_free_payment( $invoice ) {

		$invoice->set_gateway( 'none' );
		$invoice->add_note( __( "This is a free invoice and won't be sent to the payment gateway", 'invoicing' ), false, false, true );
		$invoice->mark_paid();
		wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

	}

	/**
     * Sends a redrect response to payment details.
     *
     */
    public function send_redirect_response( $url ) {
        $url = urlencode( $url );
        wp_send_json_success( $url );
    }

}
