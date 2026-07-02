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
    protected $supports = array(
        'subscription',
        'addons',
        'single_subscription_group',
        'multiple_subscription_groups',
        'subscription_date_change',
        'subscription_bill_times_change',
    );

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

        add_action( 'getpaid_should_renew_subscription', array( $this, 'maybe_renew_subscription' ), 10, 2 );
        add_filter( 'wpinv_is_gateway_active', array( $this, 'maybe_restrict_to_admins' ), 10, 2 );
        add_filter( 'wpinv_enabled_payment_gateways', array( $this, 'maybe_hide_from_checkout' ) );
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

        if ( $this->is_admins_only() && ! wpinv_current_user_can_manage_invoicing() ) {
            throw new Exception( __( 'The selected payment method is not available.', 'invoicing' ) );
        }

        // Mark it as paid.
        $invoice->mark_paid();

        // (Maybe) activate subscriptions.
        $subscriptions = getpaid_get_invoice_subscriptions( $invoice );

        if ( ! empty( $subscriptions ) ) {
            $subscriptions = is_array( $subscriptions ) ? $subscriptions : array( $subscriptions );

            foreach ( $subscriptions as $subscription ) {
                if ( $subscription->exists() ) {
                    $duration = strtotime( $subscription->get_expiration() ) - strtotime( $subscription->get_date_created() );
                    $expiry   = gmdate( 'Y-m-d H:i:s', ( current_time( 'timestamp' ) + $duration ) );

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
	 * Checks if the gateway is restricted to admins.
	 *
	 * @since 2.8.55
	 * @return bool
	 */
	public function is_admins_only() {
		return (bool) wpinv_get_option( 'manual_admins_only', true );
	}

	/**
	 * Filters the gateway's active state for non-admins.
	 *
	 * @since 2.8.55
	 * @param bool   $is_active
	 * @param string $gateway
	 * @return bool
	 */
	public function maybe_restrict_to_admins( $is_active, $gateway ) {
		if ( $this->id === $gateway && $this->is_admins_only() && ! wpinv_current_user_can_manage_invoicing() ) {
			return false;
		}

		return $is_active;
	}

	/**
	 * Removes the gateway from the checkout for non-admins.
	 *
	 * @since 2.8.55
	 * @param array $gateways
	 * @return array
	 */
	public function maybe_hide_from_checkout( $gateways ) {
		if ( isset( $gateways[ $this->id ] ) && $this->is_admins_only() && ! wpinv_current_user_can_manage_invoicing() ) {
			unset( $gateways[ $this->id ] );
		}

		return $gateways;
	}

	/**
	 * Adds the gateway settings.
	 *
	 * @since 2.8.55
	 * @param array $admin_settings Gateway settings.
	 * @return array The modified gateway settings.
	 */
	public function admin_settings( $admin_settings ) {

		$restrict = array(
			'manual_admins_only' => array(
				'type' => 'checkbox',
				'id'   => 'manual_admins_only',
				'name' => __( 'Restrict to admins', 'invoicing' ),
				'desc' => __( 'When enabled, only admins can use the Test Gateway. Customers will not see it at checkout. Recommended once your site is live.', 'invoicing' ),
				'std'  => '1',
			),
		);

		// Insert the setting immediately after "Enable Test Gateway".
		$position = array_search( 'manual_active', array_keys( $admin_settings ), true );

		if ( false === $position ) {
			return array_merge( $admin_settings, $restrict );
		}

		return array_slice( $admin_settings, 0, $position + 1, true )
			+ $restrict
			+ array_slice( $admin_settings, $position + 1, null, true );
	}

	/**
	 * (Maybe) renews a manual subscription profile.
	 *
	 *
	 * @param WPInv_Subscription $subscription
	 */
	public function maybe_renew_subscription( $subscription, $parent_invoice ) {
		// Ensure its our subscription && it's active.
		if ( ! empty( $parent_invoice ) && $this->id === $parent_invoice->get_gateway() && $subscription->has_status( 'active trialling' ) ) {
			// Renew the subscription.
			$subscription->add_payment(
				array(
					'transaction_id' => $subscription->get_parent_payment()->generate_key(),
					'gateway'        => $this->id,
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
