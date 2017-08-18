<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wpinv_bank_transfer_cc_form', '__return_false' );

function wpinv_process_bank_transfer_payment( $purchase_data ) {
    if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'wpi-gateway' ) ) {
        wp_die( __( 'Nonce verification has failed', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

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
        'gateway'       => 'bank_transfer',
        'status'        => 'wpi-pending'
    );

    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );
    
    if ( !empty( $invoice ) ) {
        wpinv_set_payment_transaction_id( $invoice->ID, $invoice->generate_key() );
        wpinv_update_payment_status( $invoice, 'wpi-pending' );
        
        // Empty the shopping cart
        wpinv_empty_cart();
        
        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
    } else {
        wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed while processing a bank transfer payment. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
        // If errors are present, send the user back to the purchase page so they can be corrected
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    }
}
add_action( 'wpinv_gateway_bank_transfer', 'wpinv_process_bank_transfer_payment' );

function wpinv_show_bank_info( $invoice ) {
    if ( !empty( $invoice ) && $invoice->gateway == 'bank_transfer' && $invoice->status == 'wpi-pending' ) {
        $bank_info = wpinv_get_bank_info( true );
        ?>
        <div class="wpinv-bank-details">
            <?php if ( $instructions = wpinv_get_bank_instructions() ) { ?>
            <div class="alert bg-info"><?php echo wpautop( wp_kses_post( $instructions ) ); ?></div>
            <?php } ?>
            <?php if ( !empty( $bank_info ) ) { ?>
            <h3 class="wpinv-bank-t"><?php echo apply_filters( 'wpinv_receipt_bank_details_title', __( 'Our Bank Details', 'invoicing' ) ); ?></h3>
            <table class="table table-bordered table-sm wpi-bank-details">
                <?php foreach ( $bank_info as $key => $info ) { ?>
                <tr class="wpi-<?php echo sanitize_html_class( $key );?>"><th class="text-left"><?php echo $info['label'] ;?></th><td><?php echo $info['value'] ;?></td></tr>
                <?php } ?>
            </table>
            <?php } ?>
        </div>
        <?php
    }
}
add_action( 'wpinv_before_receipt_details', 'wpinv_show_bank_info', 10, 1 );

function wpinv_invoice_print_bank_info( $invoice ) {
    if ( !empty( $invoice ) && $invoice->gateway == 'bank_transfer' && $invoice->status == 'wpi-pending' ) {
        ?>
        <div class="row wpinv-bank-info">
            <?php echo wpinv_show_bank_info( $invoice ); ?>
        </div>
        <?php
    }
}
add_action( 'wpinv_invoice_print_after_top_content', 'wpinv_invoice_print_bank_info', 10, 1 );