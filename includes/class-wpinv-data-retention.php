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
 * Handles user data anonymization and deletion.
 *
 * @since 2.8.22
 */
class WPInv_Data_Retention {

    /**
     * Error message.
     *
     * @var string
     */
    private $error_message;

    /**
     * Flag to control whether user deletion should be handled.
     *
     * @var bool
     */
    private $handle_user_deletion = true;

    /**
     * Class constructor.
     */
    public function __construct() {
        add_filter( 'wpinv_settings_misc', array( $this, 'add_data_retention_settings' ) );

        add_action( 'wpmu_delete_user', array( $this, 'maybe_handle_user_deletion' ), 1 );
        add_action( 'delete_user', array( $this, 'maybe_handle_user_deletion' ), 1 );
        add_filter( 'wp_privacy_personal_data_erasure_request', array( $this, 'handle_erasure_request' ), 10, 2 );

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
            'tooltip' => __( 'Anonymization replaces personal data with non-identifiable information. Direct deletion removes all data permanently.', 'invoicing' ),
        );

        $misc_settings['data_retention_period'] = array(
            'id'      => 'data_retention_period',
            'name'    => __( 'Retention Period', 'invoicing' ),
            'desc'    => __( 'Specify how long to retain customer data after processing.', 'invoicing' ),
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
            'tooltip' => __( 'Choose how long to keep processed customer data before final action. This helps balance data minimization with business needs.', 'invoicing' ),
        );

        return $misc_settings;
    }

    /**
     * Conditionally handles user deletion based on the flag.
     *
     * @param int $user_id The ID of the user being deleted.
     */
    public function maybe_handle_user_deletion( $user_id ) {
        if ( ! $this->handle_user_deletion ) {
            return;
        }

        if ( current_user_can( 'manage_options' ) ) {
            $this->handle_admin_user_deletion( $user_id );
        } else {
            $this->handle_self_account_deletion( $user_id );
        }
    }

    /**
     * Handles admin-initiated user deletion process.
     *
     * @since 2.8.22
     * @param int $user_id The ID of the user being deleted.
     */
    public function handle_admin_user_deletion( $user_id ) {
        if ( $this->has_active_subscriptions( $user_id ) ) {
            $this->prevent_user_deletion( $user_id, 'active_subscriptions' );
            return;
        }

        if ( $this->has_paid_invoices( $user_id ) ) {
            $retention_method = wpinv_get_option( 'data_retention_method', 'anonymize' );
            if ( 'anonymize' === $retention_method ) {
                $this->anonymize_user_data( $user_id );
                $this->prevent_user_deletion( $user_id, 'paid_invoices' );
            } else {
                $this->delete_user_data( $user_id );
            }
        }
    }

    /**
     * Handles user account self-deletion.
     *
     * @since 2.8.22
     * @param int $user_id The ID of the user being deleted.
     */
    public function handle_self_account_deletion( $user_id ) {
        $this->cancel_active_subscriptions( $user_id );

        if ( $this->has_paid_invoices( $user_id ) ) {
            $retention_method = wpinv_get_option( 'data_retention_method', 'anonymize' );

            if ( 'anonymize' === $retention_method ) {
                $user = get_userdata( $user_id );

                $this->anonymize_user_data( $user_id );

                $message = apply_filters( 'uwp_get_account_deletion_message', '', $user );
                do_action( 'uwp_send_account_deletion_emails', $user, $message );

                $this->end_user_session();
            }
        }
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
     * Prevents user deletion by setting an error message and stopping execution.
     *
     * @since 2.8.22
     * @param int    $user_id The ID of the user being deleted.
     * @param string $reason  The reason for preventing deletion.
     */
    private function prevent_user_deletion( $user_id, $reason ) {
        $user = get_userdata( $user_id );

        if ( 'active_subscriptions' === $reason ) {
            $this->error_message = sprintf(
                /* translators: %s: user login */
                esc_html__( 'User deletion for %s has been halted. All active subscriptions should be cancelled first.', 'invoicing' ),
                $user->user_login
            );
        } else {
            $this->error_message = sprintf(
                /* translators: %s: user login */
                esc_html__( 'User deletion for %s has been halted due to paid invoices. Data will be anonymized instead.', 'invoicing' ),
                $user->user_login
            );
        }

        wp_die( $this->error_message, esc_html__( 'User Deletion Halted', 'invoicing' ), array( 'response' => 403 ) );
    }

    /**
     * Anonymizes user data.
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

        $table_name    = $wpdb->prefix . 'getpaid_customers';
        $deletion_date = gmdate( 'Y-m-d', strtotime( '+10 years' ) );
        $hashed_email  = $this->hash_email( $user->user_email );

        $updated = $wpdb->update(
            $table_name,
            array(
                'is_anonymized' => 1,
                'deletion_date' => $deletion_date,
                'email'         => $hashed_email,
                'email_cc'      => $hashed_email,
                'phone'         => '',
            ),
            array( 'user_id' => (int) $user->ID )
        );

        if ( false === $updated ) {
            return false;
        }

        wp_update_user(
            array(
                'ID'         => (int) $user->ID,
                'user_email' => $hashed_email,
            )
        );

        /**
         * Fires when anonymizing user meta fields.
         *
         * @since 2.8.22
         * @param int $user_id The ID of the user being anonymized.
         */
        do_action( 'wpinv_anonymize_user_meta_data', $user->ID );

        $user_meta_data = array(
            'nickname',
			'description',
			'rich_editing',
			'syntax_highlighting',
			'comment_shortcuts',
            'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'locale',
			'wp_capabilities',
            'wp_user_level',
			'dismissed_wp_pointers',
			'show_welcome_panel',
        );

        /**
         * Filters the user meta fields to be anonymized.
         *
         * @since 2.8.22
         * @param array $user_meta_data The meta fields to be anonymized.
         * @param int   $user_id          The ID of the user being anonymized.
         */
        $user_meta_data = apply_filters( 'wpinv_user_meta_data_to_anonymize', $user_meta_data, $user->ID );

        foreach ( $user_meta_data as $meta_key ) {
            delete_user_meta( $user->ID, $meta_key );
        }

        return $this->ensure_invoice_anonymization( $user->ID, 'anonymize' );
    }

    /**
     * Deletes user data without anonymization.
     *
     * @param int $user_id The ID of the user to delete.
     * @return bool True on success, false on failure.
     */
    private function delete_user_data( $user_id ) {
        // Delete associated invoices.
        $this->ensure_invoice_anonymization( $user_id, 'delete' );

        // Delete the user.
        if ( is_multisite() ) {
            wpmu_delete_user( $user_id );
        } else {
            wp_delete_user( $user_id );
        }

        /**
         * Fires after deleting user data without anonymization.
         *
         * @since 2.8.22
         * @param int $user_id The ID of the user being deleted.
         */
        do_action( 'wpinv_delete_user_data', $user_id );

        return true;
    }

    /**
     * Ensures invoice data remains anonymized.
     *
     * @since 2.8.22
     * @param int    $user_id The ID of the user whose invoices should be checked.
     * @param string $action  The action to perform (anonymize or delete).
     * @return bool True on success, false on failure.
     */
    public function ensure_invoice_anonymization( $user_id, $action = 'anonymize' ) {
        $invoices = wpinv_get_invoices( array( 'user' => $user_id ) );

        /**
         * Filters the invoice meta fields to be anonymized.
         *
         * @since 2.8.22
         * @param array $inv_meta_data The meta fields to be anonymized.
         * @param int   $user_id         The ID of the user being processed.
         */
        $inv_meta_data = apply_filters( 'wpinv_invoice_meta_data_to_anonymize', array(), $user_id );

        foreach ( $invoices->invoices as $invoice ) {
            foreach ( $inv_meta_data as $meta_key ) {
                delete_post_meta( $invoice->get_id(), $meta_key );
            }

            if ( 'anonymize' === $action ) {
                $hashed_inv_email    = $this->hash_email( $invoice->get_email() );
                $hashed_inv_email_cc = $this->hash_email( $invoice->get_email_cc() );

                $invoice->set_email( $hashed_inv_email );
                $invoice->set_email_cc( $hashed_inv_email_cc );
                $invoice->set_phone( '' );
                $invoice->set_ip( $this->anonymize_data( $invoice->get_ip() ) );
                $invoice->set_is_anonymized( 1 );

                /**
                 * Fires when anonymizing additional invoice data.
                 *
                 * @since 2.8.22
                 * @param WPInv_Invoice $invoice The invoice being anonymized.
                 * @param string        $action  The action being performed (anonymize or delete).
                 */
                do_action( 'wpinv_anonymize_invoice_data', $invoice, $action );

                $invoice->save();
            } else {
                $invoice->delete();
            }
        }

        return $this->log_deletion_action( $user_id, $invoices->invoices, $action );
    }

    /**
     * Logs the deletion or anonymization action for a user and their invoices.
     *
     * @since 2.8.22
     * @param int    $user_id  The ID of the user being processed.
     * @param array  $invoices An array of invoice objects being processed.
     * @param string $action   The action being performed (anonymize or delete).
     * @return bool True on success, false on failure.
     */
    private function log_deletion_action( $user_id, $invoices, $action ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'getpaid_anonymization_logs';
        $user_data  = get_userdata( $user_id );

        $additional_info = array(
            'Username'      => $user_data ? $user_data->user_login : 'N/A',
            'User Roles'    => $user_data ? implode(', ', $user_data->roles) : 'N/A',
            'Email'         => $user_data ? $user_data->user_email : 'N/A',
            'First Name'    => $user_data ? $user_data->first_name : 'N/A',
            'Last Name'     => $user_data ? $user_data->last_name : 'N/A',
            'Registered'    => $user_data ? $user_data->user_registered : 'N/A',
            'invoice_count' => count( $invoices ),
        );


        /**
         * Filters the additional info before logging.
         *
         * @since 2.8.22
         * @param array  $additional_info The additional information to be logged.
         * @param int    $user_id         The ID of the user being processed.
         * @param array  $invoices        The invoices being processed.
         * @param string $action          The action being performed (anonymize or delete).
         */
        $additional_info = apply_filters( 'wpinv_anonymization_log_additional_info', $additional_info, $user_id, $invoices, $action );

        $data = array(
            'user_id'         => $user_id,
            'action'          => sanitize_text_field( $action ),
            'data_type'       => 'User Invoices',
            'timestamp'       => current_time( 'mysql' ),
            'additional_info' => wp_json_encode( $additional_info ),
        );

        $format = array(
            '%d',  // user_id
            '%s',  // action
            '%s',  // data_type
            '%s',  // timestamp
            '%s',  // additional_info
        );

        if ( ! empty( $user_id ) && ! empty( $action ) ) {
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

            if ( false === $result ) {
                // If update fails, try to insert.
                $result = $wpdb->insert( $table_name, $data, $format );
            }

            if ( false === $result ) {
                wpinv_error_log( sprintf( 'Failed to log anonymization action for user ID: %d. Error: %s', $user_id, $wpdb->last_error ) );
                return false;
            }
        }

        /**
         * Fires after logging a deletion or anonymization action.
         *
         * @since 2.8.22
         * @param int    $user_id  The ID of the user being processed.
         * @param array  $invoices An array of invoice objects being processed.
         * @param string $action   The action being performed (anonymize or delete).
         * @param array  $data     The data that was inserted into the log.
         */
        do_action( 'wpinv_after_log_deletion_action', $user_id, $invoices, $action, $data );

        return true;
    }

    /**
     * Handles GDPR personal data erasure request.
     *
     * @since 2.8.22
     * @param array $response The default response.
     * @param int   $user_id  The ID of the user being erased.
     * @return array The modified response.
     */
    public function handle_erasure_request( $response, $user_id ) {
        if ( $this->has_active_subscriptions( $user_id ) ) {
            $response['messages'][]    = esc_html__( 'User has active subscriptions. Data cannot be erased at this time.', 'invoicing' );
            $response['items_removed'] = false;
        } elseif ( $this->has_paid_invoices( $user_id ) ) {
            $retention_method = wpinv_get_option( 'data_retention_method', 'anonymize' );
            if ( 'anonymize' === $retention_method ) {
                $this->anonymize_user_data( $user_id );
                $response['messages'][]     = esc_html__( 'User data has been anonymized due to existing paid invoices.', 'invoicing' );
                $response['items_removed']  = false;
                $response['items_retained'] = true;
            } else {
                $this->delete_user_data( $user_id );
                $response['messages'][]     = esc_html__( 'User data has been deleted.', 'invoicing' );
                $response['items_removed']  = true;
                $response['items_retained'] = false;
            }
        }

        return $response;
    }

    /**
     * Hashes email for anonymization.
     *
     * @since 2.8.22
     * @param string $email The email to hash.
     * @return string The hashed email.
     */
    private function hash_email( $email ) {
        $site_url = get_site_url();
        $domain   = wp_parse_url( $site_url, PHP_URL_HOST );

        if ( empty( $domain ) ) {
            return $email;
        }

        $clean_email     = sanitize_email( strtolower( trim( $email ) ) );
        $hash            = wp_hash( $clean_email );
        $hash            = substr( $hash, 0, 20 );
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
     * Anonymizes a given piece of data.
     *
     * @since 2.8.22
     * @param string $data The data to anonymize.
     * @return string The anonymized data.
     */
    private function anonymize_data( $data ) {
        if ( empty( $data ) ) {
            return '';
        }

        return wp_privacy_anonymize_data( 'text', $data );
    }

    /**
     * Performs data retention cleanup.
     *
     * This method is responsible for cleaning up anonymized user data
     * that has exceeded the retention period.
     *
     * @since 2.8.22
     */
    public function perform_data_retention_cleanup() {
        global $wpdb;

        $retention_period = wpinv_get_option( 'data_retention_period', '3650' );

        // If retention period is set to 'never', exit the function.
        if ( 'never' === $retention_period ) {
            return;
        }

        $customers_table = $wpdb->prefix . 'getpaid_customers';

        // Calculate the cutoff date for data retention.
        $cutoff_date = gmdate( 'Y-m-d', strtotime( "-$retention_period days" ) );

        $expired_records = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $customers_table WHERE deletion_date < %s AND is_anonymized = 1",
                $cutoff_date
            )
        );

        /**
         * Fires before the data retention cleanup process begins.
         *
         * @since 2.8.22
         * @param array $expired_records Array of customer records to be processed.
         */
        do_action( 'getpaid_data_retention_before_cleanup', $expired_records );

        if ( ! empty( $expired_records ) ) {
            // Disable our custom user deletion handling.
            $this->handle_user_deletion = false;

            foreach ( $expired_records as $record ) {
                // Delete associated invoices.
                $this->ensure_invoice_anonymization( (int) $record->user_id, 'delete' );

                // Delete the user.
                wp_delete_user( (int) $record->user_id );

                /**
                 * Fires after processing each expired record during cleanup.
                 *
                 * @since 2.8.22
                 * @param object $record The customer record being processed.
                 */
                do_action( 'getpaid_data_retention_process_record', $record );
            }

            // Re-enable our custom user deletion handling.
            $this->handle_user_deletion = true;

            /**
             * Fires after the data retention cleanup process is complete.
             *
             * @since 2.8.22
             * @param array $expired_records Array of customer records that were processed.
             */
            do_action( 'getpaid_data_retention_after_cleanup', $expired_records );
        }

        /**
         * Fires after the data retention cleanup attempt, regardless of whether records were processed.
         *
         * @since 2.8.22
         * @param int $retention_period The current retention period in years.
         * @param string $cutoff_date The cutoff date used for identifying expired records.
         */
        do_action( 'getpaid_data_retention_cleanup_complete', $retention_period, $cutoff_date );
    }

    /**
     * Ends the user's current session.
     *
     * @since 2.8.22
     */
    private function end_user_session() {
        wp_logout();

        // Redirect after deletion.
        $redirect_page = home_url();
        wp_safe_redirect( $redirect_page );
        exit();
    }
}