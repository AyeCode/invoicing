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
    protected $supports = array( 'subscription' );

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

        $this->title        = __( 'Manual Payment', 'invoicing' );
        $this->method_title = __( 'Manual Payment', 'invoicing' );

        add_action( 'wpinv_renew_manual_subscription_profile', array( $this, 'renew_manual_subscription_profile' ) );
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

        // (Maybe) set recurring hooks.
        $this->start_manual_subscription_profile( $invoice );

        // Send to the success page.
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

    }
    
    /**
	 * Starts a manual subscription profile.
	 *
	 *
	 * @param WPInv_Invoice $invoice Invoice.
	 */
	public function start_manual_subscription_profile( $invoice ) {

        // Retrieve the subscription.
        $subscription = wpinv_get_subscription( $invoice );
        if ( empty( $subscription ) ) {
            return;
        }

        // Schedule an action to run when the subscription expires.
        $action_id = as_schedule_single_action(
            strtotime( $subscription->expiration ),
            'wpinv_renew_manual_subscription_profile',
            array( $invoice->get_id() ),
            'invoicing'
        );

        // Use the action id as the subscription id.
        $subscription->update( 
            array(
                'profile_id' => $action_id, 
                'status'     => 'trialling' == $subscription->status ? 'trialling' : 'active'
            )
        );

    }

    /**
	 * Renews a manual subscription profile.
	 *
	 *
	 * @param int $invoice_id Invoice.
	 */
	public function renew_manual_subscription_profile( $invoice_id ) {

        // Retrieve the subscription.
        $subscription = wpinv_get_subscription( $invoice_id );
        if ( empty( $subscription ) ) {
            return;
        }

        // If we have not maxed out on bill times...
        $times_billed = $subscription->get_times_billed();
        $max_bills    = $subscription->bill_times;

        if ( empty( $max_bills ) || $max_bills > $times_billed ) {

            // Retrieve the invoice.
            $invoice = new WPInv_Invoice( $invoice_id );

            // Renew the subscription.
            $subscription->add_payment( array(
                'amount'         => $subscription->recurring_amount,
                'transaction_id' => $invoice->generate_key(),
                'gateway'        => $this->id
            ) );

        }

        // Renew/Complete the subscription.
        $subscription->renew();

        if ( 'completed' != $subscription->status ) {

            // Schedule an action to run when the subscription expires.
            $action_id = as_schedule_single_action(
                strtotime( $subscription->expiration ),
                'wpinv_renew_manual_subscription_profile',
                array( $invoice_id ),
                'invoicing'
            );

            $subscription->update( array( 'profile_id' => $action_id, ) );

        }

    }

}
