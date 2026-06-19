<?php
/**
 * Authorize.net legacy payment gateway
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Authorize.net Legacy Payment Gateway class.
 *
 * As from version 1.0.19, subscriptions are now managed by WPI instead of Authorize.NET.
 * This class adds support for legacy subscriptions.
 */
abstract class GetPaid_Authorize_Net_Legacy_Gateway extends GetPaid_Payment_Gateway {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		parent::__construct();
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
	 * Communicates with authorize.net
	 *
	 *
	 * @param array $post Data to post.
	 * @param WPInv_Invoice $invoice Invoice.
	 * @return stdClass|WP_Error
	 */
	public function post( $post, $invoice ) {
		$url      = $this->get_api_url( $invoice );
		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => json_encode( $post ),
				'method'  => 'POST',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = wp_unslash( wp_remote_retrieve_body( $response ) );
		$response = preg_replace( '/\xEF\xBB\xBF/', '', $response ); // https://community.developer.authorize.net/t5/Integration-and-Testing/JSON-issues/td-p/48851
		$response = json_decode( $response );

		if ( empty( $response ) ) {
			return new WP_Error( 'invalid_response', __( 'Invalid gateway response', 'invoicing' ) );
		}

		if ( $response->messages->resultCode == 'Error' ) {
			if ( $this->is_sandbox( $invoice ) ) {
				wpinv_error_log( $response );
			}

			if ( $response->messages->message[0]->code == 'E00039' && ! empty( $response->customerProfileId ) && ! empty( $response->customerPaymentProfileId ) ) {
				return new WP_Error( 'dup_payment_profile', $response->customerProfileId . '.' . $response->customerPaymentProfileId );
			}

			if ( ! empty( $response->transactionResponse ) && ! empty( $response->transactionResponse->errors ) ) {
				$error = $response->transactionResponse->errors[0];
				return new WP_Error( $error->errorCode, $error->errorText );
			}

			return new WP_Error( $response->messages->message[0]->code, $response->messages->message[0]->text );
		}

		return $response;
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
	 * Cancels a subscription remotely
	 *
	 *
	 * @param WPInv_Subscription $subscription Subscription.
	 * @param WPInv_Invoice $invoice Invoice.
	 */
	public function cancel_subscription( $subscription, $invoice ) {
		// Backwards compatibility. New version do not use authorize.net subscriptions.
		$this->post(
			array(
				'ARBCancelSubscriptionRequest' => array(
					'merchantAuthentication' => $this->get_auth_params(),
					'subscriptionId'         => $subscription->profile_id,
				),
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
		// Process old webhook requests.
		$this->maybe_process_old_ipn();

		$body = @file_get_contents( 'php://input' );

		if ( empty( $body ) ) {
			wpinv_error_log( 'Webhook response data is empty.', wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
			return false;
		}

		// Validate the IPN.
		if ( ! $this->validate_ipn( $body ) ) {
			wpinv_error_log( 'Signature mismatch. Webhook validation failed.', wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
			wp_die( 'Signature mismatch. Webhook validation failed.', 'Authorize.NET Webhook', array( 'response' => 200 ) );
		}

		$posted = @json_decode( $body );

		if ( empty( $posted ) ) {
			wp_die( 'Webhook response data is empty.', 'Authorize.NET Webhook', array( 'response' => 200 ) );
		};

		wpinv_error_log( wp_sprintf( __( 'eventType - %s', 'invoicing' ), $posted->eventType ), wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );

		$payload_id = ! empty( $posted->payload->id ) ? sanitize_text_field( $posted->payload->id ) : 0;

		if ( empty( $payload_id ) ) {
			return;
		}

		$invoiceNumber = ! empty( $posted->payload->invoiceNumber ) ? sanitize_text_field( $posted->payload->invoiceNumber ) : 0;

		if ( ! empty( $invoiceNumber ) ) { 
			wpinv_error_log( wp_sprintf( __( 'Invoice Number: %s', 'invoicing' ), $invoiceNumber ), wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
		}

		$merchantReferenceId = ! empty( $posted->payload->merchantReferenceId ) ? sanitize_text_field( $posted->payload->merchantReferenceId ) : 0;
		$ref_id              = ! empty( $merchantReferenceId ) ? $merchantReferenceId : $payload_id;

		$invoice = new WPInv_Invoice( $payload_id );

		if ( ! ( ! empty( $invoice ) && $invoice->get_id() ) && $merchantReferenceId ) {
			$invoice = new WPInv_Invoice( $merchantReferenceId );
		}

		if ( ! ( ! empty( $invoice ) && $invoice->get_id() ) && $invoiceNumber ) {
			$invoice = new WPInv_Invoice( $invoiceNumber );
		}

		$invoice_id = ! empty( $invoice ) ? (int) $invoice->get_id() : 0;

		if ( $invoice_id ) {
			$invoice->add_note( wp_sprintf( __( '[Authorize.NET %s] Webhook: Event - %s', 'invoicing' ), $this->get_mode_name(), $posted->eventType ), false, false, true );
		}

		// Process refunds.
		if ( 'net.authorize.payment.refund.created' == $posted->eventType ) {
			wpinv_error_log( wp_sprintf( __( 'Refund event detected for GetPaid. Payload ID: %s, MerchantReferenceId: %s', 'invoicing' ), $payload_id, $merchantReferenceId ), wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );

			if ( ! $invoice_id ) {
				wpinv_error_log( wp_sprintf( __( 'GetPaid Refund Failure: No Invoice found for Reference ID %s', 'invoicing' ), $ref_id ), wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
			} else {
				$refunded_total   = (float) get_post_meta( $invoice_id, '_gp_anet_refund', true );
				$refunded_amount  = ! empty( $posted->payload->authAmount ) ? $posted->payload->authAmount : 0;
				$refunded_total   = $refunded_total + (float) $refunded_amount;
				$_refunded_amount = wpinv_price( (float) $refunded_amount, $invoice->get_currency() );
				$payment_amount   = $invoice->get_total();

				update_post_meta( $invoice_id, '_gp_anet_refund', $refunded_total );

				// Check for partial refund;
				if ( floatval( $refunded_amount ) > 0 && floatval( $refunded_amount ) < floatval( $payment_amount ) ) {
					wpinv_error_log( wp_sprintf( __( 'Partial refund of %s processed.', 'invoicing' ), $_refunded_amount ), wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );

					$invoice->add_note( wp_sprintf( __( '[Authorize.NET %s] Webhook: Partial refund of %s processed.', 'invoicing' ), $this->get_mode_name(), $_refunded_amount ), false, false, true );

					if ( floatval( $refunded_total ) < floatval( $payment_amount ) ) {
						return;
					}
				}

				$invoice->refund();

				wpinv_error_log( wp_sprintf( __( 'Refund successfully processed for Invoice #%s', 'invoicing' ), $invoice->get_number() ), wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );

				$message         = $refunded_amount && floatval( $refunded_amount ) == floatval( $refunded_total ) ? wp_sprintf( __( '[Authorize.NET %s] Webhook: Refund of %s processed.', 'invoicing' ), $this->get_mode_name(), $_refunded_amount ) : wp_sprintf( __( '[Authorize.NET %s] Webhook: Refund processed.', 'invoicing' ), $this->get_mode_name() );

				$invoice->add_note( $message, false, false, true );
			}
		}

		// Held funds approved.
		if ( 'net.authorize.payment.fraud.approved' == $posted->eventType ) {
			$this->validate_ipn_invoice( $invoice, $posted->payload );
			$invoice->mark_paid( false, __( 'Payment released', 'invoicing' ) );
		}

		// Held funds declined.
		if ( 'net.authorize.payment.fraud.declined' == $posted->eventType ) {
			$this->validate_ipn_invoice( $invoice, $posted->payload );
			$invoice->set_status( 'wpi-failed', __( 'Payment declined', 'invoicing' ) );
			$invoice->save();
		}

		wp_die( 'Processed', 200 );
	}

	/**
	 * Check Authorize.NET IPN validity.
	 */
	public function validate_ipn( $posted ) {
		wpinv_error_log( 'Validating webhook response.', wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );

		$signature_key = $this->get_option( 'signature_key' );

		if ( empty( $signature_key ) ) {
			wpinv_error_log( 'Signature key is missing.', wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
			return false;
		}

		$incoming_hash = '';

		if ( isset( $_SERVER['HTTP_X_ANET_SIGNATURE'] ) ) {
			$incoming_hash = $_SERVER['HTTP_X_ANET_SIGNATURE'];
		} elseif ( isset( $_SERVER['X_ANET_SIGNATURE'] ) ) {
			$incoming_hash = $_SERVER['X_ANET_SIGNATURE'];
		} else {
			$headers       = array_change_key_case( getallheaders(), CASE_LOWER );
			$incoming_hash = $headers['x-anet-signature'] ?? '';
		}

		if ( empty( $incoming_hash ) ) {
			wpinv_error_log( 'Missing or empty X-Anet-Signature authentication header.', wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
			return false;
		}

		// Normal Signature Key.
		$match_hash = 'sha512=' . hash_hmac( 'sha512', $posted, $signature_key );

		if ( hash_equals( strtolower( $incoming_hash ), strtolower( $match_hash ) ) ) {
			wpinv_error_log( 'Webhook successfully validated.', wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );

			return true;
		}

		// Hexadecimal Signature Key.
		$match_hash = 'sha512=' . hash_hmac( 'sha512', $posted, hex2bin( $signature_key ) );

		if ( hash_equals( strtolower( $incoming_hash ), strtolower( $match_hash ) ) ) {
			wpinv_error_log( 'Webhook successfully validated.', wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );

			return true;
		}

		return false;
	}

	/**
	 * Validates IPN invoices.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param object $payload
	 * @return void
	 */
	public function validate_ipn_invoice( $invoice, $payload ) {
		if ( ! $invoice->exists() || $payload->id != $invoice->get_transaction_id() ) {
			wpinv_error_log( __( 'Invoice not found.', 'invoicing' ), wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );

			wp_die( 'Invoice not found.', 'Authorize.NET Webhook', array( 'response' => 200 ) );
		}
	}

	/**
	 * Process subscription IPNS.
	 *
	 * @return void
	 */
	public function maybe_process_old_ipn() {
		$data = ! empty( $_POST ) ? $_POST : array();

		if ( empty( $data ) ) {
			return;
		}

		// Only process subscriptions subscriptions.
		if ( empty( $data['x_subscription_id'] ) ) {
			return;
		}

		// Check validity.
		$this->validate_old_ipn_signature( $data );

		// Fetch the associated subscription.
		$subscription_id = WPInv_Subscription::get_subscription_id_by_field( sanitize_text_field( $data['x_subscription_id'] ) );
		$subscription    = new WPInv_Subscription( $subscription_id );

		// Abort if it is missing or completed.
		if ( ! $subscription->get_id() || $subscription->has_status( 'completed' ) ) {
			return;
		}

		// Payment status.
		if ( 1 == $data['x_response_code'] ) {
			// Renew the subscription.
			$subscription->add_payment(
				array(
					'transaction_id' => sanitize_text_field( $data['x_trans_id'] ),
					'gateway'        => $this->id,
				)
			);
			$subscription->renew();
		} else {
			$subscription->failing();
		}

		exit;
	}

	/**
	 * Validates the old IPN signature.
	 *
	 * @param array $posted
	 */
	public function validate_old_ipn_signature( $posted ) {
		$signature_key     = $this->get_option( 'signature_key' );

		if ( empty( $signature_key ) ) {
			wpinv_error_log( "Legacy IPN rejected: Signature key is not configured.", wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
			wp_die( 'Authorize.NET Webhook Request Failure: Signature key is missing.', 'Authorize.NET Webhook Error', array( 'response' => 403 ) );
		}

		$incoming_hash = ! empty( $posted['x_SHA2_Hash'] ) ? sanitize_text_field( $posted['x_SHA2_Hash'] ) : '';

		if ( empty( $incoming_hash ) ) {
			wpinv_error_log( "Missing authentication hash.", wp_sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
			wp_die( 'Authorize.NET Webhook Request Failure: Missing authentication hash.', 'Authorize.NET Webhook Error', array( 'response' => 200 ) );
		}

		$login_id      = $this->get_option( 'login_id' );
		$trans_id      = ! empty( $posted['x_trans_id'] ) ? wpinv_clean( $posted['x_trans_id'] ) : '';
		$amount        = ! empty( $posted['x_amount'] ) ? wpinv_clean( $posted['x_amount'] ) : '';

		// Live mode gives Hexadecimal Signature Key.
		$secret_key    = $this->is_sandbox() ?  $signature_key : hex2bin( $signature_key );
		$match_hash    = hash_hmac( 'sha512', "^$login_id^$trans_id^$amount^", $secret_key );

		if ( ! hash_equals( strtolower( $incoming_hash ), strtolower( $match_hash ) ) ) {
			wpinv_error_log( "Authorize.NET Signature Verification Failed. Trans ID: {$trans_id} | Amount: {$amount}", sprintf( '[Authorize.NET %s] Webhook:', $this->get_mode_name() ), false );
			wp_die( 'Authorize.NET Webhook Request Failure: Signature mismatch.', 'Authorize.NET Webhook Error', array( 'response' => 200 ) );
		}
	}

	public function get_mode_name() {
		return $this->is_sandbox() ? 'Sandbox' : 'Live';
	}
}
