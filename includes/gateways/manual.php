<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wpinv_manual_cc_form', '__return_false' );

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
        
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
    } else {
        wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed while processing a manual (free or test) purchase. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
        // If errors are present, send the user back to the purchase page so they can be corrected
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    }
}
add_action( 'wpinv_gateway_manual', 'wpinv_process_manual_payment' );