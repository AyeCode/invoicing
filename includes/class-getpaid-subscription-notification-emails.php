<?php
/**
 * Contains the subscriptions notification emails management class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class handles subscription notificaiton emails.
 *
 */
class GetPaid_Subscription_Notification_Emails {

    /**
	 * The array of subscription email actions.
	 *
	 * @param array
	 */
	public $subscription_actions;

    /**
	 * Class constructor
     *
	 */
	public function __construct() {

		$this->subscription_actions = apply_filters(
			'getpaid_notification_email_subscription_triggers',
			array(
				'getpaid_subscription_trialling' => 'subscription_trial',
				'getpaid_subscription_cancelled' => 'subscription_cancelled',
				'getpaid_subscription_expired'   => 'subscription_expired',
				'getpaid_subscription_completed' => 'subscription_complete',
				'getpaid_daily_maintenance'      => 'renewal_reminder',
			)
		);

		$this->init_hooks();

    }

    /**
	 * Registers email hooks.
	 */
	public function init_hooks() {

		add_filter( 'getpaid_get_email_merge_tags', array( $this, 'subscription_merge_tags' ), 10, 2 );
		foreach ( $this->subscription_actions as $hook => $email_type ) {

			$email = new GetPaid_Notification_Email( $email_type );

			if ( ! $email->is_active() ) {
				continue;
			}

			if ( method_exists( $this, $email_type ) ) {
				add_action( $hook, array( $this, $email_type ), 100, 2 );
				continue;
			}

			do_action( 'getpaid_subscription_notification_email_register_hook', $email_type, $hook );

		}

	}

	/**
	 * Filters subscription merge tags.
	 *
	 * @param array $merge_tags
	 * @param mixed|WPInv_Invoice|WPInv_Subscription $object
	 */
	public function subscription_merge_tags( $merge_tags, $object ) {

		if ( is_a( $object, 'WPInv_Subscription' ) ) {
			$merge_tags = array_merge(
				$merge_tags,
				$this->get_subscription_merge_tags( $object )
			);
		}

		return $merge_tags;

	}

	/**
	 * Generates subscription merge tags.
	 *
	 * @param WPInv_Subscription $subscription
	 * @return array
	 */
	public function get_subscription_merge_tags( $subscription ) {

		// Abort if it does not exist.
		if ( ! $subscription->get_id() ) {
			return array();
		}

		$invoice    = $subscription->get_parent_invoice();
		return array(
			'{subscription_renewal_date}'     => getpaid_format_date_value( $subscription->get_next_renewal_date(), __( 'Never', 'invoicing' ) ),
			'{subscription_created}'          => getpaid_format_date_value( $subscription->get_date_created() ),
			'{subscription_status}'           => sanitize_text_field( $subscription->get_status_label() ),
			'{subscription_profile_id}'       => sanitize_text_field( $subscription->get_profile_id() ),
			'{subscription_id}'               => absint( $subscription->get_id() ),
			'{subscription_recurring_amount}' => sanitize_text_field( wpinv_price( $subscription->get_recurring_amount(), $invoice->get_currency() ) ),
			'{subscription_initial_amount}'   => sanitize_text_field( wpinv_price( $subscription->get_initial_amount(), $invoice->get_currency() ) ),
			'{subscription_recurring_period}' => getpaid_get_subscription_period_label( $subscription->get_period(), $subscription->get_frequency(), '' ),
			'{subscription_bill_times}'       => $subscription->get_bill_times(),
			'{subscription_url}'              => esc_url( $subscription->get_view_url() ),
		);

	}

	/**
	 * Checks if we should send a notification for a subscription.
	 *
	 * @param WPInv_Invoice $invoice
	 * @return bool
	 */
	public function should_send_notification( $invoice ) {
		return 0 != $invoice->get_id();
	}

	/**
	 * Returns notification recipients.
	 *
	 * @param WPInv_Invoice $invoice
	 * @return array
	 */
	public function get_recipients( $invoice ) {
		$recipients = array( $invoice->get_email() );

		$cc = $invoice->get_email_cc();

		if ( ! empty( $cc ) ) {
			$cc = array_map( 'sanitize_email', wpinv_parse_list( $cc ) );
			$recipients = array_filter( array_unique( array_merge( $recipients, $cc ) ) );
		}

		return $recipients;
	}

	/**
	 * Helper function to send an email.
	 *
	 * @param WPInv_Subscription $subscription
	 * @param GetPaid_Notification_Email $email
	 * @param string $type
	 * @param array $extra_args Extra template args.
	 */
	public function send_email( $subscription, $email, $type, $extra_args = array() ) {

		// Abort in case the parent invoice does not exist.
		$invoice = $subscription->get_parent_invoice();
		if ( ! $this->should_send_notification( $invoice ) ) {
			return;
		}

		if ( apply_filters( 'getpaid_skip_subscription_email', false, $type, $subscription ) ) {
			return;
		}

		do_action( 'getpaid_before_send_subscription_notification', $type, $subscription, $email );

		$recipients  = $this->get_recipients( $invoice );
		$mailer      = new GetPaid_Notification_Email_Sender();
		$merge_tags  = $email->get_merge_tags();
		$content     = $email->get_content( $merge_tags, $extra_args );
		$subject     = $email->add_merge_tags( $email->get_subject(), $merge_tags );
		$attachments = $email->get_attachments();

		$result = $mailer->send(
			apply_filters( 'getpaid_subscription_email_recipients', wpinv_parse_list( $recipients ), $email ),
			$subject,
			$content,
			$attachments
		);

		// Maybe send a copy to the admin.
		if ( $email->include_admin_bcc() ) {
			$mailer->send(
				wpinv_get_admin_email(),
				$subject . __( ' - ADMIN BCC COPY', 'invoicing' ),
				$content,
				$attachments
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

		do_action( 'getpaid_after_send_subscription_notification', $type, $subscription, $email );

	}

    /**
	 * Sends a new trial notification.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function subscription_trial( $subscription ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $subscription );
		$this->send_email( $subscription, $email, __FUNCTION__ );

	}

	/**
	 * Sends a cancelled subscription notification.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function subscription_cancelled( $subscription ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $subscription );
		$this->send_email( $subscription, $email, __FUNCTION__ );

	}

	/**
	 * Sends a subscription expired notification.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function subscription_expired( $subscription ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $subscription );
		$this->send_email( $subscription, $email, __FUNCTION__ );

	}

	/**
	 * Sends a completed subscription notification.
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function subscription_complete( $subscription ) {

		$email     = new GetPaid_Notification_Email( __FUNCTION__, $subscription );
		$this->send_email( $subscription, $email, __FUNCTION__ );

	}

	/**
	 * Sends a subscription renewal reminder notification.
	 *
	 */
	public function renewal_reminder() {

		$email = new GetPaid_Notification_Email( __FUNCTION__ );

		// Fetch reminder days.
		$reminder_days = array_unique( wp_parse_id_list( $email->get_option( 'days' ) ) );

		// Abort if non is set.
		if ( empty( $reminder_days ) ) {
			return;
		}

		// Fetch matching subscriptions.
        $args  = array(
            'number'             => -1,
			'count_total'        => false,
			'status'             => 'trialling active',
            'date_expires_query' => array(
				'relation'  => 'OR'
            ),
		);

		foreach ( $reminder_days as $days ) {
			$date = date_parse( date( 'Y-m-d', strtotime( "+$days days", current_time( 'timestamp' ) ) ) );

			$args['date_expires_query'][] = array(
				'year'  => $date['year'],
				'month' => $date['month'],
				'day'   => $date['day'],
			);

		}

		$subscriptions = new GetPaid_Subscriptions_Query( $args );

        foreach ( $subscriptions as $subscription ) {

			// Skip packages.
			if ( get_post_meta( $subscription->get_product_id(), '_wpinv_type', true ) != 'package' ) {
				$email->object = $subscription;
            	$this->send_email( $subscription, $email, __FUNCTION__ );
			}

		}

	}

}
