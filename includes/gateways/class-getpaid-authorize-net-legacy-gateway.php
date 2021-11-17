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

        $response = wp_unslash( wp_remote_retrieve_body( $response ) );
        $response = preg_replace('/\xEF\xBB\xBF/', '', $response); // https://community.developer.authorize.net/t5/Integration-and-Testing/JSON-issues/td-p/48851
        $response = json_decode( $response );

        if ( empty( $response ) ) {
            return new WP_Error( 'invalid_reponse', __( 'Invalid gateway response', 'invoicing' ) );
        }

        if ( $response->messages->resultCode == 'Error' ) {

            if ( $this->is_sandbox( $invoice ) ) {
                wpinv_error_log( $response );
            }

            if ( $response->messages->message[0]->code == 'E00039' && ! empty( $response->customerProfileId )  && ! empty( $response->customerPaymentProfileId ) ) {
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
		    wp_die( 'Authorize.NET IPN Request Failure', 'Authorize.NET IPN', array( 'response' => 200 ) );
        }

        // Event type.
        $posted = json_decode( file_get_contents( 'php://input' ) );
        if ( empty( $posted ) ) {
            wp_die( 'Invalid JSON', 'Authorize.NET IPN', array( 'response' => 200 ) );
        }

        // Process the IPN.
        $posted = (object) wp_unslash( $posted );

        // Process refunds.
        if ( 'net.authorize.payment.refund.created' == $posted->eventType ) {
            $invoice = new WPInv_Invoice( $posted->payload->merchantReferenceId );
            $this->validate_ipn_invoice( $invoice, $posted->payload );
            $invoice->refund();
        }

        // Held funds approved.
        if ( 'net.authorize.payment.fraud.approved' == $posted->eventType ) {
            $invoice = new WPInv_Invoice( $posted->payload->id );
            $this->validate_ipn_invoice( $invoice, $posted->payload );
            $invoice->mark_paid( false, __( 'Payment released', 'invoicing' ) );
        }

        // Held funds declined.
        if ( 'net.authorize.payment.fraud.declined' == $posted->eventType ) {
            $invoice = new WPInv_Invoice( $posted->payload->id );
            $this->validate_ipn_invoice( $invoice, $posted->payload );
            $invoice->set_status( 'wpi-failed', __( 'Payment declined', 'invoicing' ) );
            $invoice->save();
        }

        exit;

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
            exit;
        }
    }

    /**
	 * Process subscriptio IPNS.
	 *
	 * @return void
	 */
	public function maybe_process_old_ipn() {

        $data = wp_kses_post_deep( wp_unslash( $_POST ) );

        // Only process subscriptions subscriptions.
        if ( empty( $data['x_subscription_id'] ) ) {
            return;
        }

        // Check validity.
        $this->validate_old_ipn_signature( $data );

        // Fetch the associated subscription.
        $subscription_id = WPInv_Subscription::get_subscription_id_by_field( $data['x_subscription_id'] );
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
                    'gateway'        => $this->id
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

        $signature = $this->get_option( 'signature_key' );
        if ( ! empty( $signature ) ) {
            $login_id  = $this->get_option( 'login_id' );
            $trans_id  = wpinv_clean( $_POST['x_trans_id'] );
            $amount    = wpinv_clean( $_POST['x_amount'] );
            $hash      = hash_hmac ( 'sha512', "^$login_id^$trans_id^$amount^", hex2bin( $signature ) );

            if ( ! hash_equals( $hash, $posted['x_SHA2_Hash'] ) ) {
                wpinv_error_log( $posted['x_SHA2_Hash'], "Invalid signature. Expected $hash" );
                exit;
            }

        }

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

        $hash  = hash_hmac ( 'sha512', file_get_contents( 'php://input' ), hex2bin( $signature ) );

        if ( hash_equals( $hash, $_SERVER['HTTP_X_ANET_SIGNATURE'] ) ) {
            wpinv_error_log( 'Successfully validated the IPN' );
            return true;
        }

        wpinv_error_log( 'IPN hash is not valid' );
        wpinv_error_log(  $_SERVER['HTTP_X_ANET_SIGNATURE']  );
        return false;

    }

}
