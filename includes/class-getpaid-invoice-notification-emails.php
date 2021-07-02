<?php
/**
 * Contains the invoice notification emails management class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class handles invoice notificaiton emails.
 *
 */
class GetPaid_Invoice_Notification_Emails {

	/**
	 * The array of invoice email actions.
	 *
	 * @param array
	 */
	public $invoice_actions;

	/**
	 * Class constructor
	 *
	 */
	public function __construct() {

		$this->invoice_actions = apply_filters(
			'getpaid_notification_email_invoice_triggers',
			array(
				'getpaid_new_invoice'                   => array( 'new_invoice', 'user_invoice' ),
				'getpaid_invoice_status_wpi-cancelled'  => 'cancelled_invoice',
				'getpaid_invoice_status_wpi-failed'     => 'failed_invoice',
				'getpaid_invoice_status_wpi-onhold'     => 'onhold_invoice',
				'getpaid_invoice_status_wpi-processing' => 'processing_invoice',
				'getpaid_invoice_status_publish'        => 'completed_invoice',
				'getpaid_invoice_status_wpi-renewal'    => 'completed_invoice',
				'getpaid_invoice_status_wpi-refunded'   => 'refunded_invoice',
				'getpaid_new_customer_note'             => 'user_note',
				'getpaid_daily_maintenance'             => 'overdue',
			)
		);

		$this->init_hooks();

	}

	/**
	 * Registers email hooks.
	 */
	public function init_hooks() {

		add_filter( 'getpaid_get_email_merge_tags', array( $this, 'invoice_merge_tags' ), 10, 2 );
		add_filter( 'getpaid_invoice_email_recipients', array( $this, 'filter_email_recipients' ), 10, 2 );

		foreach ( $this->invoice_actions as $hook => $email_type ) {
			$this->init_email_type_hook( $hook, $email_type );
		}
	}

	/**
	 * Registers an email hook for an invoice action.
	 * 
	 * @param string $hook
	 * @param string|array $email_type
	 */
	public function init_email_type_hook( $hook, $email_type ) {

		$email_type = wpinv_parse_list( $email_type );

		foreach ( $email_type as $type ) {

			$email = new GetPaid_Notification_Email( $type );

			// Abort if it is not active.
			if ( ! $email->is_active() ) {
				continue;
			}

			if ( method_exists( $this, $type ) ) {
				add_action( $hook, array( $this, $type ), 100, 2 );
				continue;
			}

			do_action( 'getpaid_invoice_init_email_type_hook', $type, $hook );
		}

	}

	/**
	 * Filters invoice merge tags.
	 *
	 * @param array $merge_tags
	 * @param mixed|WPInv_Invoice|WPInv_Subscription $object
	 */
	public function invoice_merge_tags( $merge_tags, $object ) {

		if ( is_a( $object, 'WPInv_Invoice' ) ) {
			return array_merge(
				$merge_tags,
				$this->get_invoice_merge_tags( $object )
			);
		}

		if ( is_a( $object, 'WPInv_Subscription' ) ) {
			return array_merge(
				$merge_tags,
				$this->get_invoice_merge_tags( $object->get_parent_payment() )
			);
		}

		return $merge_tags;

	}

	/**
	 * Generates invoice merge tags.
	 *
	 * @param WPInv_Invoice $invoice
	 * @return array
	 */
	public function get_invoice_merge_tags( $invoice ) {

		// Abort if it does not exist.
		if ( ! $invoice->get_id() ) {
			return array();
		}

		$merge_tags = array(
			'{name}'                => sanitize_text_field( $invoice->get_user_full_name() ),
			'{full_name}'           => sanitize_text_field( $invoice->get_user_full_name() ),
			'{first_name}'          => sanitize_text_field( $invoice->get_first_name() ),
			'{last_name}'           => sanitize_text_field( $invoice->get_last_name() ),
			'{email}'               => sanitize_email( $invoice->get_email() ),
			'{invoice_number}'      => sanitize_text_field( $invoice->get_number() ),
			'{invoice_currency}'    => sanitize_text_field( $invoice->get_currency() ),
			'{invoice_total}'       => sanitize_text_field( wpinv_price( $invoice->get_total(), $invoice->get_currency() ) ),
			'{invoice_link}'        => esc_url( $invoice->get_view_url() ),
			'{invoice_pay_link}'    => esc_url( $invoice->get_checkout_payment_url() ),
			'{invoice_receipt_link}'=> esc_url( $invoice->get_receipt_url() ),
			'{invoice_date}'        => getpaid_format_date_value( $invoice->get_date_created() ),
			'{invoice_due_date}'    => getpaid_format_date_value( $invoice->get_due_date(), __( 'on receipt', 'invoicing' ) ),
			'{invoice_quote}'       => sanitize_text_field( strtolower( $invoice->get_label() ) ),
			'{invoice_label}'       => sanitize_text_field( ucfirst( $invoice->get_label() ) ),
			'{invoice_description}' => wp_kses_post( $invoice->get_description() ),
			'{subscription_name}'   => wp_kses_post( $invoice->get_subscription_name() ),
			'{is_was}'              => strtotime( $invoice->get_due_date() ) < current_time( 'timestamp' ) ? __( 'was', 'invoicing' ) : __( 'is', 'invoicing' ),
		);

		$payment_form_data = $invoice->get_meta( 'payment_form_data', true );

		if ( is_array( $payment_form_data ) ) {

			foreach ( $payment_form_data as $label => $value ) {

				$label = preg_replace( '/[^a-z0-9]+/', '_', strtolower( $label ) );
				$value = is_array( $value ) ? implode( ', ', $value ) : $value;

				if ( is_scalar ( $value ) ) {
					$merge_tags[ "{{$label}}" ] = wp_kses_post( $value );
				}

			}

		}

		return apply_filters( 'getpaid_invoice_email_merge_tags', $merge_tags, $invoice );
	}

	/**
	 * Helper function to send an email.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param GetPaid_Notification_Email $email
	 * @param string $type
	 * @param string|array $recipients
	 * @param array $extra_args Extra template args.
	 */
	public function send_email( $invoice, $email, $type, $recipients, $extra_args = array() ) {

		do_action( 'getpaid_before_send_invoice_notification', $type, $invoice, $email );

		$skip = $invoice->is_free() && wpinv_get_option( 'skip_email_free_invoice' );
		if ( apply_filters( 'getpaid_skip_invoice_email', $skip, $type, $invoice ) ) {
			return;
		}

		$mailer     = new GetPaid_Notification_Email_Sender();
		$merge_tags = $email->get_merge_tags();

		$result = $mailer->send(
			apply_filters( 'getpaid_invoice_email_recipients', wpinv_parse_list( $recipients ), $email ),
			$email->add_merge_tags( $email->get_subject(), $merge_tags ),
			$email->get_content( $merge_tags, $extra_args ),
			$email->get_attachments()
		);

		// Maybe send a copy to the admin.
		if ( $email->include_admin_bcc() ) {
			$mailer->send(
				wpinv_get_admin_email(),
				$email->add_merge_tags( $email->get_subject() . __( ' - ADMIN BCC COPY', 'invoicing' ), $merge_tags ),
				$email->get_content( $merge_tags ),
				$email->get_attachments()
			);
		}

		if ( $result ) {
			$invoice->add_system_note(
				sprintf(
					__( 'Successfully sent %s notification email to %s.', 'invoicing' ),
					sanitize_key( $type ),
					$email->is_admin_email() ? __( 'admin' ) : __( 'the customer' )
				)
			);
		} else {
			$invoice->add_system_note(
				sprintf(
					__( 'Failed sending %s notification email to %s.', 'invoicing' ),
					sanitize_key( $type ),
					$email->is_admin_email() ? __( 'admin' ) : __( 'the customer' )
				)
			);	
		}

		do_action( 'getpaid_after_send_invoice_notification', $type, $invoice, $email );

		return $result;
	}

	/**
	 * Also send emails to any cc users.
	 *
	 * @param array $recipients
	 * @param GetPaid_Notification_Email $email
	 */
	public function filter_email_recipients( $recipients, $email ) {

		if ( ! $email->is_admin_email() ) {
			$cc   = $email->object->get_email_cc();
			$cc_2 = get_user_meta( $email->object->get_user_id(), '_wpinv_email_cc', true );

			if ( ! empty( $cc ) ) {
				$cc = array_map( 'sanitize_email', wpinv_parse_list( $cc ) );
				$recipients = array_filter( array_unique( array_merge( $recipients, $cc ) ) );
			}

			if ( ! empty( $cc_2 ) ) {
				$cc_2 = array_map( 'sanitize_email', wpinv_parse_list( $cc_2 ) );
				$recipients = array_filter( array_unique( array_merge( $recipients, $cc_2 ) ) );
			}

		}

		return $recipients;

	}

	/**
	 * Sends a new invoice notification.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function new_invoice( $invoice ) {

		// Only send this email for invoices created via the admin page.
		if ( ! $invoice->is_type( 'invoice' ) || $this->is_payment_form_invoice( $invoice->get_id() ) ) {
			return;
		}

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = wpinv_get_admin_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient );

	}

	/**
	 * Sends a cancelled invoice notification.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function cancelled_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = wpinv_get_admin_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient );

	}

	/**
	 * Sends a failed invoice notification.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function failed_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = wpinv_get_admin_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient );

	}

	/**
	 * Sends a notification whenever an invoice is put on hold.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function onhold_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = $invoice->get_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient );

	}

	/**
	 * Sends a notification whenever an invoice is marked as processing payment.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function processing_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = $invoice->get_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient );

	}

	/**
	 * Sends a notification whenever an invoice is paid.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function completed_invoice( $invoice ) {

		// (Maybe) abort if it is a renewal invoice.
		if ( $invoice->is_renewal() && ! wpinv_get_option( 'email_completed_invoice_renewal_active', false ) ) {
			return;
		}

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = $invoice->get_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient );

	}

	/**
	 * Sends a notification whenever an invoice is refunded.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function refunded_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = $invoice->get_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient );

	}

	/**
	 * Notifies a user about new invoices
	 *
	 * @param WPInv_Invoice $invoice
	 * @param bool $force
	 */
	public function user_invoice( $invoice, $force = false ) {

		if ( ! empty( $GLOBALS['wpinv_skip_invoice_notification'] ) ) {
			return;
		}

		// Only send this email for invoices created via the admin page.
		if ( ! $invoice->is_type( 'invoice' ) || ( empty( $force ) && $this->is_payment_form_invoice( $invoice->get_id() ) ) ) {
			return;
		}

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = $invoice->get_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient );

	}

	/**
	 * Checks if an invoice is a payment form invoice.
	 *
	 * @param int $invoice
	 * @return bool
	 */
	public function is_payment_form_invoice( $invoice ) {
		$is_payment_form_invoice = empty( $_GET['getpaid-admin-action'] ) && ( 'payment_form' == get_post_meta( $invoice, 'wpinv_created_via', true ) || 'geodirectory' == get_post_meta( $invoice, 'wpinv_created_via', true ) );
		return apply_filters( 'getpaid_invoice_notifications_is_payment_form_invoice', $is_payment_form_invoice, $invoice );
	}

	/**
	 * Notifies admin about new invoice notes
	 *
	 * @param WPInv_Invoice $invoice
	 * @param string $note
	 */
	public function user_note( $invoice, $note ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $invoice );
		$recipient = $invoice->get_email();

		return $this->send_email( $invoice, $email, __FUNCTION__, $recipient, array( 'customer_note' => $note ) );

	}

	/**
	 * (Force) Sends overdue notices.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function force_send_overdue_notice( $invoice ) {
		$email = new GetPaid_Notification_Email( 'overdue', $invoice );
		return $this->send_email( $invoice, $email, 'overdue', $invoice->get_email() );
	}

	/**
	 * Sends overdue notices.
	 *
	 * @TODO: Create an invoices query class.
	 */
	public function overdue() {
		global $wpdb;

		$email = new GetPaid_Notification_Email( __FUNCTION__ );

		// Fetch reminder days.
		$reminder_days = array_unique( wp_parse_id_list( $email->get_option( 'days' ) ) );

		// Abort if non is set.
		if ( empty( $reminder_days ) ) {
			return;
		}

		// Retrieve date query.
		$date_query = $this->get_date_query( $reminder_days );

		// Invoices table.
		$table = $wpdb->prefix . 'getpaid_invoices';

		// Fetch invoices.
		$invoices  = $wpdb->get_col(
			"SELECT posts.ID FROM $wpdb->posts as posts
			LEFT JOIN $table as invoices ON invoices.post_id = posts.ID
			WHERE posts.post_type = 'wpi_invoice' AND posts.post_status = 'wpi-pending' $date_query");

		foreach ( $invoices as $invoice ) {

			// Only send this email for invoices created via the admin page.
			if ( ! $this->is_payment_form_invoice( $invoice ) ) {
				$invoice       = new WPInv_Invoice( $invoice );
				$email->object = $invoice;

				if ( $invoice->needs_payment() ) {
					$this->send_email( $invoice, $email, __FUNCTION__, $invoice->get_email() );
				}

			}

		}

	}

	/**
	 * Calculates the date query for an invoices query
	 *
	 * @param array $reminder_days
	 * @return string
	 */
	public function get_date_query( $reminder_days ) {

		$date_query = array(
			'relation'  => 'OR'
		);

		foreach ( $reminder_days as $days ) {
			$date = date_parse( date( 'Y-m-d', strtotime( "-$days days", current_time( 'timestamp' ) ) ) );

			$date_query[] = array(
				'year'  => $date['year'],
				'month' => $date['month'],
				'day'   => $date['day'],
			);

		}

		$date_query = new WP_Date_Query( $date_query, 'invoices.due_date' );

		return $date_query->get_sql();

	}

}
