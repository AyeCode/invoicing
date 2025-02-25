<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * PayPal API handler.
 *
 * @since 1.0.0
 */
class GetPaid_PayPal_API {

	/**
	 * Retrieves the bearer token.
	 *
     * @return string|\WP_Error
	 */
	public static function get_token( $mode = 'live' ) {

		$token = get_transient( 'getpaid_paypal_' . $mode . '_token' );

		if ( $token ) {
			return $token;
		}

		$client_id  = 'live' === $mode ? wpinv_get_option( 'paypal_client_id' ) : wpinv_get_option( 'paypal_sandbox_client_id' );
		$secret_key = 'live' === $mode ? wpinv_get_option( 'paypal_secret' ) : wpinv_get_option( 'paypal_sandbox_secret' );
		$url        = self::get_api_url( 'v1/oauth2/token?grant_type=client_credentials', $mode );

        if ( empty( $client_id ) || empty( $secret_key ) ) {
            return new \WP_Error( 'invalid_request', 'Missing client id or secret key.', array( 'status' => 400 ) );
        }

		$args   = array(
			'method'  => 'POST',
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret_key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
		);

		$response = self::response_or_error( wp_remote_post( $url, $args ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! isset( $response->access_token ) ) {
			return new \WP_Error( 'invalid_request', 'Could not create token.', array( 'status' => 400 ) );
		}

		set_transient( 'getpaid_paypal_' . $mode . '_token', $response->access_token, $response->expires_in - 600 );
		return $response->access_token;
	}

	/**
	 * Retrieves the PayPal API URL.
	 *
	 * @param string $endpoint
	 * @return string
	 */
	public static function get_api_url( $endpoint = '', $mode = 'live'  ) {
		$endpoint = ltrim( $endpoint, '/' );
		return 'live' === $mode ? 'https://api-m.paypal.com/' . $endpoint : 'https://api-m.sandbox.paypal.com/' . $endpoint;
	}

	/**
	 * Handles a post request.
	 *
	 * @param string $path The path to the endpoint.
	 * @param mixed $data The data to send.
	 * @param string $method The method to use.
	 *
	 * @return true|\WP_Error
	 */
	public static function post( $path, $data, $mode = 'live', $method = 'POST' ) {

		$access_token = self::get_token( $mode );

		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$url  = self::get_api_url( $path, $mode );
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			),
		);

		if( ! empty( $data )) {
			$args['body'] = wp_json_encode( $data );
		}

		return self::response_or_error( wp_remote_post( $url, $args ) );
	}

	/**
	 * Handles a get request.
	 *
	 * @param string $path The path to the endpoint.
	 * @param string $method
	 * @return object|\WP_Error
	 */
	public static function get( $path, $mode = 'live', $method = 'GET' ) {

		$access_token = self::get_token( $mode );

		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		$url  = self::get_api_url( $path, $mode );
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
		);

		return self::response_or_error( wp_remote_get( $url, $args ) );
	}

	/**
	 * Returns the response body
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param \WP_Error|array $response
	 * @return \WP_Error|object
	 */
	public static function response_or_error( $response ) {

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'paypal_error', __( 'There was a problem connecting to the PayPal API endpoint.', 'invoicing' ) );
		}

		if ( empty( $response['body'] ) ) {
			return true;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( wp_remote_retrieve_response_code( $response ) > 299 ) {

			// Normal errors.
			if ( $response_body && isset( $response_body->message ) ) {
				$error_message = $response_body->message;

			// Identity errors.
			} elseif ( $response_body && isset( $response_body->error_description ) ) {
				$error_message = $response_body->error_description;
				return new \WP_Error( 'paypal_error', wp_kses_post( $response_body->error_description ) );
			} else {
				$error_message = __( 'There was an error connecting to the PayPal API endpoint.', 'invoicing' );
			}

			return new \WP_Error( 'paypal_error', $error_message );
		}

		return $response_body;
	}

	/**
	 * Fetches an order.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $order_id
	 * @link https://developer.paypal.com/docs/api/orders/v2/#orders_get
	 * @return \WP_Error|object
	 */
	public static function get_order( $order_id, $mode = 'live' ) {
		return self::get( '/v2/checkout/orders/' . $order_id, $mode );
	}

	/**
	 * Fetches a subscription.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $subscription_id
	 * @link https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_get
	 * @return \WP_Error|object
	 */
	public static function get_subscription( $subscription_id, $mode = 'live' ) {
		return self::get( '/v1/billing/subscriptions/' . $subscription_id, $mode );
	}

	/**
	 * Fetches a subscription's latest transactions (limits search to last one day).
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $subscription_id
	 * @link https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_transactions
	 * @return \WP_Error|object
	 */
	public static function get_subscription_transaction( $subscription_id, $mode = 'live' ) {
		$start_time = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '-1 day' ) );
		$end_time   = gmdate( 'Y-m-d\TH:i:s\Z' );
		return self::get( "/v1/billing/subscriptions/$subscription_id/transactions?start_time=$start_time&end_time=$end_time", $mode );
	}

	/**
	 * Refunds a capture.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $capture_id
	 * @param array  $args
	 * @link https://developer.paypal.com/docs/api/payments/v2/#captures_refund
	 * @return \WP_Error|object
	 */
	public static function refund_capture( $capture_id, $args = array(), $mode = 'live' ) {
		return self::post( '/v2/payments/captures/' . $capture_id . '/refund', $args, $mode );
	}

	/**
	 * Cancels a subscription.
	 *
	 * @since 2.8.24
	 * @version 2.8.24
	 * @param string $subscription_id
	 * @param array  $args
	 * @link https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_cancel
	 * @return \WP_Error|object
	 */
	public static function cancel_subscription( $subscription_id, $args = array(), $mode = 'live' ) {
		return self::post( '/v1/billing/subscriptions/' . $subscription_id . '/cancel', $args, $mode );
	}
}
