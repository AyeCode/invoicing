<?php
/**
 * Contains the notification emails management class.
 *
 */

use function SimplePay\Core\Payments\Payment_Confirmation\get_content;

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
				'getpaid_new_invoice'                   => 'new_invoice',
				'getpaid_invoice_status_wpi-cancelled'  => 'cancelled_invoice',
				'getpaid_invoice_status_wpi-failed'     => 'failed_invoice',
				'getpaid_invoice_status_wpi-onhold'     => 'onhold_invoice',
				'getpaid_invoice_status_wpi-processing' => 'processing_invoice',
				'getpaid_invoice_status_publish'        => 'completed_invoice',
				'getpaid_invoice_status_wpi-renewal'    => 'completed_invoice',
				'getpaid_invoice_status_wpi-refunded'   => 'refunded_invoice',
				'getpaid_new_invoice'                   => 'user_invoice',
				'getpaid_new_customer_note'             => 'user_note',
				'getpaid_subscriptions_daily_cron'      => 'overdue',

			)
		);

    }

    /**
	 * Registers email hooks.
	 */
	public function init_hooks() {

		add_filter( 'getpaid_get_email_merge_tags', array( $this, 'invoice_merge_tags' ), 10, 3 );
		add_filter( 'getpaid_invoice_email_recipients', array( $this, 'filter_email_recipients' ), 10, 2 );
		foreach ( $this->invoice_actions as $hook => $email_type ) {

			$email = new GetPaid_Notification_Email( $email_type );

			if ( $email->is_active() && method_exists( $this, $email_type ) ) {
				add_action( $hook, array( $this, $email_type ), 100, 2 );
			} else {
				do_action( 'getpaid_hook_invoice_notification_email_invoice_trigger', $email );
			}

		}

	}

	/**
	 * Filters invoice merge tags.
	 *
	 * @param array $merge_tags
	 * @param string $email_type
	 * @param mixed|WPInv_Invoice|WPInv_Subscription $object
	 */
	public function invoice_merge_tags( $merge_tags, $email_type, $object ) {

		if ( is_a( $object, 'WPInv_Invoice' ) ) {
			$merge_tags = array_merge(
				$merge_tags,
				$this->get_invoice_merge_tags( $object )
			);
		}

		if ( is_a( $object, 'WPInv_Subscription' ) ) {
			$merge_tags = array_merge(
				$merge_tags,
				$this->get_invoice_merge_tags( $object->get_parent_payment() )
			);
		}

		return apply_filters( 'getpaid_invoice_notification_merge_tags', $merge_tags, $object, $email_type, $this );

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

		return array(
			'{name}'                => sanitize_text_field( $invoice->get_user_full_name() ),
			'{full_name}'           => sanitize_text_field( $invoice->get_user_full_name() ),
			'{first_name}'          => sanitize_text_field( $invoice->get_first_name() ),
			'{last_name}'           => sanitize_text_field( $invoice->get_last_name() ),
			'{email}'               => sanitize_email( $invoice->get_email() ),
			'{invoice_number}'      => sanitize_text_field( $invoice->get_number() ),
			'{invoice_total}'       => wpinv_price( wpinv_format_amount( $invoice->get_total() ) ),
			'{invoice_link}'        => esc_url( $invoice->get_view_url() ),
			'{invoice_pay_link}'    => esc_url( $invoice->get_checkout_payment_url() ),
			'{invoice_receipt_link}'=> esc_url( $invoice->get_receipt_url() ),
			'{invoice_date}'        => date( get_option( 'date_format' ), strtotime( $invoice->get_date_created(), current_time( 'timestamp' ) ) ),
			'{invoice_due_date}'    => date( get_option( 'date_format' ), strtotime( $invoice->get_due_date(), current_time( 'timestamp' ) ) ),
			'{invoice_quote}'       => sanitize_text_field( $invoice->get_type() ),
			'{invoice_label}'       => sanitize_text_field( ucfirst( $invoice->get_type() ) ),
			'{invoice_description}' => wp_kses_post( $invoice->get_description() ),
			'{subscription_name}'   => wp_kses_post( $invoice->get_subscription_name() ),
			'{is_was}'              => strtotime( $invoice->get_due_date() ) < current_time( 'timestamp' ) ? __( 'was', 'invoicing' ) : __( 'is', 'invoicing' ),
		);

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

		if ( ! $result ) {
			$invoice->add_note( sprintf( __( 'Failed sending %s notification email.', 'invoicing' ), sanitize_key( $type ) ), false, false, true );
		}

		do_action( 'getpaid_after_send_invoice_notification', $type, $invoice, $email );

	}

	/**
	 * Also send emails to any cc users.
	 *
	 * @param array $recipients
	 * @param GetPaid_Notification_Email $email
	 */
	public function filter_email_recipients( $recipients, $email ) {

		if ( ! $email->is_admin_email() ) {
			$cc = $email->object->get_email_cc();

			if ( ! empty( $cc ) ) {
				$cc = array_map( 'sanitize_email', wpinv_parse_list( $cc ) );
				$recipients = array_filter( array_unique( array_merge( $recipients, $cc ) ) );
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

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = wpinv_get_admin_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient );

	}

	/**
	 * Sends a cancelled invoice notification.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function cancelled_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = wpinv_get_admin_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient );

	}

	/**
	 * Sends a failed invoice notification.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function failed_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = wpinv_get_admin_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient );

	}

	/**
	 * Sends a notification whenever an invoice is put on hold.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function onhold_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = $invoice->get_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient );

	}

	/**
	 * Sends a notification whenever an invoice is marked as processing payment.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function processing_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = $invoice->get_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient );

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

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = $invoice->get_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient );

	}

	/**
	 * Sends a notification whenever an invoice is refunded.
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function refunded_invoice( $invoice ) {

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = $invoice->get_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient );

	}

	/**
	 * Notifies a user about new invoices
	 *
	 * @param WPInv_Invoice $invoice
	 */
	public function user_invoice( $invoice ) {

		// Only send this email for invoices created via the admin page.
		$payment_form = $invoice->get_payment_form();

		if ( 1 != $payment_form && wpinv_get_default_payment_form() != $payment_form ) {
			return;
		}

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = $invoice->get_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient );

	}

	/**
	 * Notifies admin about new invoice notes
	 *
	 * @param WPInv_Invoice $invoice
	 * @param string $note
	 */
	public function user_note( $invoice, $note ) {

		$email     = new GetPaid_Notification_Email( __METHOD__, $invoice );
		$recipient = $invoice->get_email();

		$this->send_email( $invoice, $email, __METHOD__, $recipient, array( 'customer_note' => $note ) );

	}

}
