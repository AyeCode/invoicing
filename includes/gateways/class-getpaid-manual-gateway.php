<?php
/**
 * Manual payment gateway
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manual Payment Gateway class.
 *
 */
class GetPaid_Manual_Gateway extends GetPaid_Payment_Gateway {

    /**
	 * Payment method id.
	 *
	 * @var string
	 */
    public $id = 'manual';

    /**
	 * An array of features that this gateway supports.
	 *
	 * @var array
	 */
    protected $supports = array( 'subscription', 'addons', 'single_subscription_group', 'multiple_subscription_groups' );

    /**
	 * Payment method order.
	 *
	 * @var int
	 */
	public $order = 11;

    /**
	 * Class constructor.
	 */
	public function __construct() {
        parent::__construct();

        $this->title        = __( 'Test Gateway', 'invoicing' );
        $this->method_title = __( 'Test Gateway', 'invoicing' );

        add_action( 'getpaid_should_renew_subscription', array( $this, 'maybe_renew_subscription' ) );
    }

    /**
	 * Process Payment.
	 *
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 * @param array $submission_data Posted checkout fields.
	 * @param GetPaid_Payment_Form_Submission $submission Checkout submission.
	 * @return array
	 */
	public function process_payment( $invoice, $submission_data, $submission ) {

        // Mark it as paid.
        $invoice->mark_paid();

        // (Maybe) activate subscriptions.
        $subscriptions = getpaid_get_invoice_subscriptions( $invoice );

        if ( ! empty( $subscriptions ) ) {
            $subscriptions = is_array( $subscriptions ) ? $subscriptions : array( $subscriptions );

            foreach ( $subscriptions as $subscription ) {
                if ( $subscription->exists() ) {
                    $duration = strtotime( $subscription->get_expiration() ) - strtotime( $subscription->get_date_created() );
                    $expiry   = date( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $duration ) );

                    $subscription->set_next_renewal_date( $expiry );
                    $subscription->set_date_created( current_time( 'mysql' ) );
                    $subscription->set_profile_id( $invoice->generate_key( 'manual_sub_' . $invoice->get_id() . '_' . $subscription->get_id() ) );
                    $subscription->activate();
                }
            }

        }

        // Send to the success page.
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

    }

    /**
	 * (Maybe) renews a manual subscription profile.
	 *
	 *
     * @param WPInv_Subscription $subscription
	 */
	public function maybe_renew_subscription( $subscription ) {

        // Ensure its our subscription && it's active.
        if ( $this->id == $subscription->get_gateway() && $subscription->has_status( 'active trialling' ) ) {

            // Renew the subscription.
            $subscription->add_payment(
                array(
                    'transaction_id' => $subscription->get_parent_payment()->generate_key(),
                    'gateway'        => $this->id
                )
            );

            $subscription->renew();

        }

    }

    /**
	 * Processes invoice addons.
	 *
	 * @param WPInv_Invoice $invoice
	 * @param GetPaid_Form_Item[] $items
	 * @return WPInv_Invoice
	 */
	public function process_addons( $invoice, $items ) {

        foreach ( $items as $item ) {
            $invoice->add_item( $item );
        }

        $invoice->recalculate_total();
        $invoice->save();
    }

}
