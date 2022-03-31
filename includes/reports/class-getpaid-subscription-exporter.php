<?php
/**
 * Contains the class that exports subscriptions.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Subscription_Exporter Class.
 */
class GetPaid_Subscription_Exporter extends GetPaid_Graph_Downloader {

	/**
	 * Retrieves subscription query args.
	 *
	 * @param array $args Args to search for.
	 * @return array
	 */
	public function get_subscription_query_args( $args ) {

		$query_args = array(
			'status'      => 'all',
			'number'      => -1,
			'count_total' => false,
			'fields'      => 'all',
		);

		if ( ! empty( $args['status'] ) && in_array( $args['status'], array_keys( getpaid_get_subscription_statuses() ), true ) ) {
			$query_args['status'] = wpinv_clean( wpinv_parse_list( $args['status'] ) );
		}

		$date_query = array();
		if ( ! empty( $args['to_date'] ) ) {
			$date_query['before'] = wpinv_clean( $args['to_date'] );
		}

		if ( ! empty( $args['from_date'] ) ) {
			$date_query['after'] = wpinv_clean( $args['from_date'] );
		}

		if ( ! empty( $date_query ) ) {
			$date_query['inclusive']          = true;
			$query_args['date_created_query'] = array( $date_query );
		}

		return $query_args;
	}

	/**
	 * Retrieves subscriptions.
	 *
	 * @param array $query_args GetPaid_Subscriptions_Query args.
	 * @return WPInv_Subscription[]
	 */
	public function get_subscriptions( $query_args ) {

		// Get subscriptions.
		$subscriptions = new GetPaid_Subscriptions_Query( $query_args );

		// Prepare the results.
		return $subscriptions->get_results();

	}

	/**
	 * Handles the actual download.
	 *
	 */
	public function export( $post_type, $args ) {

		$subscriptions = $this->get_subscriptions( $this->get_subscription_query_args( $args ) );
		$stream        = $this->prepare_output();
		$headers       = $this->get_export_fields();
		$file_type     = $this->prepare_file_type( 'subscriptions' );

		if ( 'csv' == $file_type ) {
			$this->download_csv( $subscriptions, $stream, $headers );
		} elseif ( 'xml' == $file_type ) {
			$this->download_xml( $subscriptions, $stream, $headers );
		} else {
			$this->download_json( $subscriptions, $stream, $headers );
		}

		fclose( $stream );
		exit;
	}

	/**
	 * Prepares a single subscription for download.
	 *
	 * @param WPInv_Subscription $subscription The subscription to prepare..
	 * @param array $fields The fields to stream.
	 * @since       1.0.19
	 * @return array
	 */
	public function prepare_row( $subscription, $fields ) {

		$prepared      = array();
		$amount_fields = $this->get_amount_fields();
		$invoice       = $subscription->get_parent_payment();

		foreach ( $fields as $field ) {

			$value  = '';
			$method = "get_$field";

			if ( 0 === stripos( $field, 'customer' ) || 'currency' === $field ) {

				if ( method_exists( $invoice, $method ) ) {
					$value  = $invoice->$method();
				}
} elseif ( method_exists( $subscription, $method ) ) {
				$value  = $subscription->$method();
			}

			if ( in_array( $field, $amount_fields ) ) {
				$value  = wpinv_round_amount( wpinv_sanitize_amount( $value ) );
			}

			$prepared[ $field ] = wpinv_clean( $value );

		}

		return $prepared;
	}

	/**
	 * Retrieves export fields.
	 *
	 * @since       1.0.19
	 * @return array
	 */
	public function get_export_fields() {

		$fields = array(
			'id',
			'currency',
			'initial_amount',
			'recurring_amount',
			'trial_period',
			'frequency',
			'period',
			'bill_times',
			'parent_payment_id',
			'profile_id',
			'product_id',
			'status',
			'date_created',
			'date_expires',

			'customer_id',
			'customer_first_name',
			'customer_last_name',
			'customer_phone',
			'customer_email',
			'customer_country',
			'customer_city',
			'customer_state',
			'customer_zip',
			'customer_company',
			'customer_vat_number',
			'customer_address',

    	);

		return apply_filters( 'getpaid_subscription_exporter_get_fields', $fields );
	}

	/**
	 * Retrieves amount fields.
	 *
	 * @since       1.0.19
	 * @return array
	 */
	public function get_amount_fields() {

		$fields = array(
			'initial_amount',
			'recurring_amount',
    	);

		return apply_filters( 'getpaid_subscription_exporter_get_amount_fields', $fields );
	}

}
