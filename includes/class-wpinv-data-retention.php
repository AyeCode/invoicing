<?php
/**
 * Data retention class.
 *
 * @package Invoicing
 * @since   2.8.22
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPInv_Data_Retention Class.
 *
 * Handles user data anonymization and deletion for GDPR compliance.
 *
 * @since 2.8.22
 */
class WPInv_Data_Retention {

	/**
	 * Flag to control whether user deletion should be handled.
	 *
	 * Prevents infinite recursion when deleting users programmatically.
	 *
	 * @var bool
	 */
	private $handle_user_deletion = true;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'wpinv_settings_misc', array( $this, 'add_data_retention_settings' ) );

		// Hook into user deletion to anonymize or block.
		add_action( 'delete_user', array( $this, 'maybe_handle_user_deletion' ), 1 );
		add_action( 'wpmu_delete_user', array( $this, 'maybe_handle_user_deletion' ), 1 );

		// Register GDPR personal data eraser.
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );

		// Automated cleanup via daily cron.
		add_action( 'getpaid_daily_maintenance', array( $this, 'perform_data_retention_cleanup' ) );
	}

	/**
	 * Adds data retention settings to the misc settings page.
	 *
	 * @param array $misc_settings Existing misc settings.
	 * @return array Updated misc settings.
	 */
	public function add_data_retention_settings( $misc_settings ) {
		$misc_settings['data_retention'] = array(
			'id'   => 'data_retention',
			'name' => '<h3>' . __( 'Data Retention', 'invoicing' ) . '</h3>',
			'type' => 'header',
		);

		$misc_settings['data_retention_method'] = array(
			'id'      => 'data_retention_method',
			'name'    => __( 'Data Handling', 'invoicing' ),
			'desc'    => __( 'Choose how to handle user data when deletion is required.', 'invoicing' ),
			'type'    => 'select',
			'options' => array(
				'anonymize' => __( 'Anonymize data', 'invoicing' ),
				'delete'    => __( 'Delete data without anonymization', 'invoicing' ),
			),
			'std'     => 'anonymize',
			'tooltip' => __( 'Anonymization replaces personal data with non-identifiable information while preserving invoices for record-keeping. Direct deletion removes all invoice data permanently.', 'invoicing' ),
		);

		$misc_settings['data_retention_period'] = array(
			'id'      => 'data_retention_period',
			'name'    => __( 'Retention Period', 'invoicing' ),
			'desc'    => __( 'How long to keep anonymized customer data before permanent deletion.', 'invoicing' ),
			'type'    => 'select',
			'options' => array(
				'never' => __( 'Never delete (retain indefinitely)', 'invoicing' ),
				'30'    => __( '30 days', 'invoicing' ),
				'90'    => __( '90 days', 'invoicing' ),
				'180'   => __( '6 months', 'invoicing' ),
				'365'   => __( '1 year', 'invoicing' ),
				'730'   => __( '2 years', 'invoicing' ),
				'1825'  => __( '5 years', 'invoicing' ),
				'3650'  => __( '10 years', 'invoicing' ),
			),
			'std'     => '3650',
			'tooltip' => __( 'Anonymized records will be permanently deleted after this period. This only applies when data handling is set to "Anonymize data".', 'invoicing' ),
		);

		return $misc_settings;
	}

	/**
	 * Conditionally handles user deletion based on the recursion guard flag.
	 *
	 * @param int $user_id The ID of the user being deleted.
	 */
	public function maybe_handle_user_deletion( $user_id ) {
		if ( ! $this->handle_user_deletion ) {
			return;
		}

		$this->process_user_deletion( $user_id );
	}

	/**
	 * Processes user deletion by anonymizing or deleting invoice data.
	 *
	 * @since 2.8.22
	 * @param int $user_id The ID of the user being deleted.
	 */
	private function process_user_deletion( $user_id ) {
		if ( $this->has_active_subscriptions( $user_id ) ) {
			$this->cancel_active_subscriptions( $user_id );
		}

		if ( ! $this->has_paid_invoices( $user_id ) ) {
			return;
		}

		$retention_method = wpinv_get_option( 'data_retention_method', 'anonymize' );

		if ( 'anonymize' === $retention_method ) {
			$this->anonymize_user_data( $user_id );
		} else {
			$this->delete_invoice_data( $user_id );
		}
	}

	/**
	 * Registers the GDPR personal data eraser.
	 *
	 * @since 2.8.22
	 * @param array $erasers Registered erasers.
	 * @return array Modified erasers.
	 */
	public function register_eraser( $erasers ) {
		$erasers['getpaid-data-retention'] = array(
			'eraser_friendly_name' => __( 'GetPaid Invoice Data', 'invoicing' ),
			'callback'             => array( $this, 'handle_erasure_request' ),
		);

		return $erasers;
	}

	/**
	 * Handles GDPR personal data erasure request.
	 *
	 * @since 2.8.22
	 * @param string $email_address The email address of the user being erased.
	 * @param int    $page          The current page (for batched processing).
	 * @return array The erasure response.
	 */
	public function handle_erasure_request( $email_address, $page = 1 ) {
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return $response;
		}

		$user_id = $user->ID;

		if ( $this->has_active_subscriptions( $user_id ) ) {
			$response['messages'][]    = __( 'User has active subscriptions. Subscription data has been retained. Please cancel subscriptions first.', 'invoicing' );
			$response['items_retained'] = true;
			return $response;
		}

		if ( ! $this->has_paid_invoices( $user_id ) ) {
			return $response;
		}

		$retention_method = wpinv_get_option( 'data_retention_method', 'anonymize' );

		if ( 'anonymize' === $retention_method ) {
			$this->anonymize_user_data( $user_id );
			$response['messages'][]     = __( 'Invoice data has been anonymized. Invoices are retained for record-keeping with personal data removed.', 'invoicing' );
			$response['items_retained'] = true;
		} else {
			$this->delete_invoice_data( $user_id );
			$response['messages'][]    = __( 'All invoice data has been permanently deleted.', 'invoicing' );
			$response['items_removed'] = true;
		}

		return $response;
	}

	/**
	 * Checks if user has active subscriptions.
	 *
	 * @since 2.8.22
	 * @param int $user_id The ID of the user being checked.
	 * @return bool True if user has active subscriptions, false otherwise.
	 */
	private function has_active_subscriptions( $user_id ) {
		$subscriptions = getpaid_get_subscriptions(
			array(
				'customer_in' => array( (int) $user_id ),
				'status'      => 'active',
			)
		);

		return ! empty( $subscriptions );
	}

	/**
	 * Cancels all active subscriptions for a user.
	 *
	 * @since 2.8.22
	 * @param int $user_id The ID of the user.
	 */
	private function cancel_active_subscriptions( $user_id ) {
		$subscriptions = getpaid_get_subscriptions(
			array(
				'customer_in' => array( (int) $user_id ),
				'status'      => 'active',
			)
		);

		foreach ( $subscriptions as $subscription ) {
			$subscription->cancel();
		}
	}

	/**
	 * Checks if user has paid invoices.
	 *
	 * @since 2.8.22
	 * @param int $user_id The ID of the user being checked.
	 * @return bool True if user has paid invoices, false otherwise.
	 */
	private function has_paid_invoices( $user_id ) {
		$invoices = wpinv_get_invoices(
			array(
				'user'   => (int) $user_id,
				'status' => 'publish',
			)
		);

		return ! empty( $invoices->total );
	}

	/**
	 * Anonymizes user data in the customers table and associated invoices.
	 *
	 * @since 2.8.22
	 * @param int $user_id The ID of the user to anonymize.
	 * @return bool True on success, false on failure.
	 */
	private function anonymize_user_data( $user_id ) {
		global $wpdb;

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$table_name      = $wpdb->prefix . 'getpaid_customers';
		$retention_period = wpinv_get_option( 'data_retention_period', '3650' );

		// Use the configured retention period for the deletion date.
		if ( 'never' === $retention_period ) {
			$deletion_date = null;
		} else {
			$deletion_date = gmdate( 'Y-m-d', strtotime( "+{$retention_period} days" ) );
		}

		$hashed_email = $this->hash_email( $user->user_email );

		$update_data = array(
			'is_anonymized' => 1,
			'email'         => $hashed_email,
			'email_cc'      => '',
			'phone'         => '',
		);

		if ( null !== $deletion_date ) {
			$update_data['deletion_date'] = $deletion_date;
		}

		$wpdb->update(
			$table_name,
			$update_data,
			array( 'user_id' => (int) $user->ID )
		);

		// Anonymize invoices.
		$this->anonymize_invoice_data( $user->ID );

		// Log the action without storing PII.
		$this->log_anonymization_action( $user->ID, 'anonymize' );

		/**
		 * Fires after user data has been anonymized.
		 *
		 * @since 2.8.22
		 * @param int $user_id The ID of the user that was anonymized.
		 */
		do_action( 'wpinv_after_anonymize_user_data', $user->ID );

		return true;
	}

	/**
	 * Anonymizes all invoice data for a user.
	 *
	 * Replaces PII fields with anonymized values while preserving
	 * financial data for record-keeping compliance.
	 *
	 * @since 2.8.22
	 * @param int $user_id The ID of the user whose invoices should be anonymized.
	 */
	private function anonymize_invoice_data( $user_id ) {
		$invoices = wpinv_get_invoices( array( 'user' => $user_id ) );

		if ( empty( $invoices->invoices ) ) {
			return;
		}

		foreach ( $invoices->invoices as $invoice ) {
			$invoice->set_email( $this->hash_email( $invoice->get_email() ) );
			$invoice->set_email_cc( '' );
			$invoice->set_phone( '' );
			$invoice->set_ip( wp_privacy_anonymize_data( 'ip', $invoice->get_ip() ) );
			$invoice->set_first_name( __( 'Anonymized', 'invoicing' ) );
			$invoice->set_last_name( '' );
			$invoice->set_company( '' );
			$invoice->set_vat_number( '' );
			$invoice->set_address( '' );
			$invoice->set_city( '' );
			$invoice->set_zip( '' );
			$invoice->set_is_anonymized( 1 );

			/**
			 * Fires when anonymizing additional invoice data.
			 *
			 * Allows plugins to anonymize custom fields stored on the invoice.
			 *
			 * @since 2.8.22
			 * @param WPInv_Invoice $invoice The invoice being anonymized.
			 */
			do_action( 'wpinv_anonymize_invoice_data', $invoice );

			$invoice->save();
		}
	}

	/**
	 * Deletes all invoice data for a user without anonymization.
	 *
	 * @since 2.8.22
	 * @param int $user_id The ID of the user whose invoices should be deleted.
	 */
	private function delete_invoice_data( $user_id ) {
		$invoices = wpinv_get_invoices( array( 'user' => $user_id ) );

		if ( empty( $invoices->invoices ) ) {
			return;
		}

		foreach ( $invoices->invoices as $invoice ) {
			$invoice->delete();
		}

		$this->log_anonymization_action( $user_id, 'delete' );

		/**
		 * Fires after all invoice data for a user has been deleted.
		 *
		 * @since 2.8.22
		 * @param int $user_id The ID of the user whose data was deleted.
		 */
		do_action( 'wpinv_after_delete_user_invoice_data', $user_id );
	}

	/**
	 * Logs the anonymization or deletion action without storing PII.
	 *
	 * @since 2.8.22
	 * @param int    $user_id The ID of the user being processed.
	 * @param string $action  The action performed (anonymize or delete).
	 * @return bool True on success, false on failure.
	 */
	private function log_anonymization_action( $user_id, $action ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'getpaid_anonymization_logs';

		$data = array(
			'user_id'         => (int) $user_id,
			'action'          => sanitize_text_field( $action ),
			'data_type'       => 'user_invoices',
			'timestamp'       => current_time( 'mysql' ),
			'additional_info' => wp_json_encode(
				array(
					'invoice_count'    => $this->get_user_invoice_count( $user_id ),
					'retention_method' => wpinv_get_option( 'data_retention_method', 'anonymize' ),
					'retention_period' => wpinv_get_option( 'data_retention_period', '3650' ),
				)
			),
		);

		$format = array( '%d', '%s', '%s', '%s', '%s' );

		// Try update first, then insert.
		$result = $wpdb->update(
			$table_name,
			$data,
			array(
				'user_id' => (int) $user_id,
				'action'  => sanitize_text_field( $action ),
			),
			$format,
			array( '%d', '%s' )
		);

		if ( 0 === $result ) {
			$result = $wpdb->insert( $table_name, $data, $format );
		}

		if ( false === $result ) {
			wpinv_error_log( sprintf( 'Failed to log anonymization action for user ID: %d. Error: %s', $user_id, $wpdb->last_error ) );
			return false;
		}

		return true;
	}

	/**
	 * Gets the total number of invoices for a user.
	 *
	 * @since 2.8.22
	 * @param int $user_id The user ID.
	 * @return int The invoice count.
	 */
	private function get_user_invoice_count( $user_id ) {
		$invoices = wpinv_get_invoices( array( 'user' => $user_id ) );

		return ! empty( $invoices->total ) ? (int) $invoices->total : 0;
	}

	/**
	 * Hashes an email address for anonymization.
	 *
	 * Generates a non-reversible anonymized email using the site domain.
	 *
	 * @since 2.8.22
	 * @param string $email The email to hash.
	 * @return string The hashed email address.
	 */
	private function hash_email( $email ) {
		if ( empty( $email ) ) {
			return '';
		}

		$domain = wp_parse_url( get_site_url(), PHP_URL_HOST );

		if ( empty( $domain ) ) {
			$domain = 'localhost';
		}

		$clean_email      = sanitize_email( strtolower( trim( $email ) ) );
		$hash             = substr( wp_hash( $clean_email ), 0, 20 );
		$anonymized_email = sprintf( '%s@%s', $hash, $domain );

		/**
		 * Filters the anonymized email before returning.
		 *
		 * @since 2.8.22
		 * @param string $anonymized_email The anonymized email address.
		 * @param string $email            The original email address.
		 */
		return apply_filters( 'wpinv_anonymized_email', $anonymized_email, $email );
	}

	/**
	 * Performs automated data retention cleanup via cron.
	 *
	 * Permanently deletes anonymized records that have exceeded
	 * the configured retention period.
	 *
	 * @since 2.8.22
	 */
	public function perform_data_retention_cleanup() {
		global $wpdb;

		$retention_period = wpinv_get_option( 'data_retention_period', '3650' );

		if ( 'never' === $retention_period ) {
			return;
		}

		$customers_table = $wpdb->prefix . 'getpaid_customers';

		// Find anonymized records whose deletion_date has passed.
		$expired_records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id FROM $customers_table WHERE is_anonymized = 1 AND deletion_date IS NOT NULL AND deletion_date < %s",
				current_time( 'mysql' )
			)
		);

		if ( empty( $expired_records ) ) {
			return;
		}

		/**
		 * Fires before the data retention cleanup process begins.
		 *
		 * @since 2.8.22
		 * @param array $expired_records Array of customer records to be processed.
		 */
		do_action( 'getpaid_data_retention_before_cleanup', $expired_records );

		// Disable our deletion handler to prevent recursion.
		$this->handle_user_deletion = false;

		foreach ( $expired_records as $record ) {
			$user_id = (int) $record->user_id;

			// Delete invoice data.
			$this->delete_invoice_data( $user_id );

			// Delete customer record.
			$wpdb->delete( $customers_table, array( 'user_id' => $user_id ), array( '%d' ) );

			// Delete the WordPress user if it still exists.
			if ( get_userdata( $user_id ) ) {
				if ( is_multisite() ) {
					wpmu_delete_user( $user_id );
				} else {
					wp_delete_user( $user_id );
				}
			}

			/**
			 * Fires after processing each expired record during cleanup.
			 *
			 * @since 2.8.22
			 * @param object $record The customer record being processed.
			 */
			do_action( 'getpaid_data_retention_process_record', $record );
		}

		// Re-enable our deletion handler.
		$this->handle_user_deletion = true;

		/**
		 * Fires after the data retention cleanup process is complete.
		 *
		 * @since 2.8.22
		 * @param array $expired_records Array of customer records that were processed.
		 */
		do_action( 'getpaid_data_retention_after_cleanup', $expired_records );
	}
}
