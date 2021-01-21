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
    protected $supports = array( 'subscription', 'addons' );

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

        add_filter( 'getpaid_daily_maintenance_should_expire_subscription', array( $this, 'maybe_renew_subscription' ), 10, 2 );
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

        // (Maybe) activate subscription.
        getpaid_activate_invoice_subscription( $invoice );

        // Send to the success page.
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

    }

    /**
	 * (Maybe) renews a manual subscription profile.
	 *
	 *
	 * @param bool $should_expire
     * @param WPInv_Subscription $subscription
	 */
	public function maybe_renew_subscription( $should_expire, $subscription ) {

        // Ensure its our subscription && it's active.
        if ( 'manual' != $subscription->get_gateway() || ! $subscription->has_status( 'active trialling' ) ) {
            return $should_expire;
        }

        // If this is the last renewal, complete the subscription.
        if ( $subscription->is_last_renewal() ) {
            $subscription->complete();
            return false;
        }

        // Renew the subscription.
        $subscription->add_payment(
            array(
                'transaction_id' => $subscription->get_parent_payment()->generate_key(),
                'gateway'        => $this->id
            )
        );

        $subscription->renew();

        return false;

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
