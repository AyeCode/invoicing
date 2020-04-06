<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wpinv_manual_cc_form', '__return_false' );
add_filter( 'wpinv_manual_support_subscription', '__return_true' );

function wpinv_process_manual_payment( $purchase_data ) {
    if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'wpi-gateway' ) ) {
        wp_die( __( 'Nonce verification has failed', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

    /*
    * Purchase data comes in like this
    *
    $purchase_data = array(
        'items' => array of item IDs,
        'price' => total price of cart contents,
        'invoice_key' =>  // Random key
        'user_email' => $user_email,
        'date' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'post_data' => $_POST,
        'user_info' => array of user's information and used discount code
        'cart_details' => array of cart details,
        'gateway' => payment gateway,
    );
    */
    
    // Collect payment data
    $payment_data = array(
        'price'         => $purchase_data['price'],
        'date'          => $purchase_data['date'],
        'user_email'    => $purchase_data['user_email'],
        'invoice_key'   => $purchase_data['invoice_key'],
        'currency'      => wpinv_get_currency(),
        'items'         => $purchase_data['items'],
        'user_info'     => $purchase_data['user_info'],
        'cart_details'  => $purchase_data['cart_details'],
        'gateway'       => 'manual',
        'status'        => 'wpi-pending'
    );

    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );
    
    if ( !empty( $invoice ) ) {        
        wpinv_set_payment_transaction_id( $invoice->ID, $invoice->generate_key() );
        wpinv_update_payment_status( $invoice, 'publish' );
        
        // Empty the shopping cart
        wpinv_empty_cart();

        // (Maybe) set recurring hooks.
        wpinv_start_manual_subscription_profile( $purchase_data['invoice_id'] );
        
        do_action( 'wpinv_send_to_success_page', $invoice->ID, $payment_data );
        
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
    } else {
        wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed while processing a manual (free or test) purchase. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
        // If errors are present, send the user back to the purchase page so they can be corrected
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    }
}
add_action( 'wpinv_gateway_manual', 'wpinv_process_manual_payment' );

/**
 * Starts a manual subscription profile.
 */
function wpinv_start_manual_subscription_profile( $invoice_id ) {

    // Retrieve the subscription.
    $subscription = wpinv_get_subscription( $invoice_id );
    if ( empty( $subscription ) ) {
        return;
    }

    // Schedule an action to run when the subscription expires.
    $action_id = as_schedule_single_action(
        strtotime( $subscription->expiration ),
        'wpinv_renew_manual_subscription_profile',
        array( $invoice_id ),
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
 */
function wpinv_renew_manual_subscription_profile( $invoice_id ) {

    // Retrieve the subscription.
    $subscription = wpinv_get_subscription( $invoice_id );
    if ( empty( $subscription ) ) {
        return;
    }

    $times_billed = $subscription->get_times_billed();
    $max_bills    = $subscription->bill_times;

    // If we have not maxed out on bill times...
    if ( empty( $bill_times ) || $times_billed > $max_bills ) {

        // Renew the subscription.
        $subscription->add_payment( array(
            'amount'         => $subscription->recurring_amount,
            'transaction_id' => time(),
            'gateway'        => 'manual'
        ) );

        // Calculate the new expiration.
        $new_expiration = strtotime( "+ {$subscription->frequency} {$subscription->period}", strtotime( $subscription->expiration ) );

        // Schedule an action to run when the subscription expires.
        $action_id = as_schedule_single_action(
            $new_expiration,
            'wpinv_renew_manual_subscription_profile',
            array( $invoice_id ),
            'invoicing'
        );

        $subscription->update(
            array(
                'expiration' => date_i18n( 'Y-m-d H:i:s', $new_expiration ),
                'status'     => 'active',
                'profile_id' => $action_id, 
            )
        );

    } else {

        // This subscription is complete. Let's mark it as such.
        $subscription->update(
            array(
                'status' => 'completed'
            )
        );

    }

}
add_action( 'wpinv_renew_manual_subscription_profile', 'wpinv_renew_manual_subscription_profile' );
