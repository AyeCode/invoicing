<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wpinv_2co_cc_form', '__return_false' );
//add_filter( 'wpinv_2co_support_subscription', '__return_true' );

function wpinv_process_2co_payment( $purchase_data ) {
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
        'gateway'       => '2co',
        'status'        => 'pending'
    );

    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );
    
    if ( !empty( $invoice ) ) {
        $quantities_enabled = wpinv_item_quantities_enabled();
        
        $invoice_id = $invoice->ID;
        
        $params     = array();
        $params['sid']                  = wpinv_get_option( '2co_vendor_id', false );
        $params['mode']                 = '2CO';
        $params['currency_code']        = wpinv_get_currency();
        $params['merchant_order_id']    = $invoice_id;
        $params['total']                = wpinv_sanitize_amount( $invoice->get_total() );
        $params['key']                  = $invoice->get_key();
        
        $params['card_holder_name']     = $invoice->get_user_full_name();
        $params['email']                = $invoice->get_email();
        $params['street_address']       = wp_strip_all_tags( $invoice->get_address(), true );
        $params['country']              = $invoice->country;
        $params['state']                = $invoice->state;
        $params['city']                 = $invoice->city;
        $params['zip']                  = $invoice->zip;
        $params['phone']                = $invoice->phone;
        
        $i = 0;
        foreach ( $invoice->get_cart_details() as $item ) {            
            $quantity   = $quantities_enabled && !empty( $item['quantity'] ) && $item['quantity'] > 0 ? $item['quantity'] : 1;
            
            $params['li_' . $i . '_type']       = 'product';
            $params['li_' . $i . '_name']       = $item['name'];
            $params['li_' . $i . '_quantity']   = $quantity;
            $params['li_' . $i . '_price']      = wpinv_sanitize_amount( $item['item_price'] );
            $params['li_' . $i . '_tangible']   = 'N';
            $params['li_' . $i . '_product_id'] = $item['id'];
            $i++;
        }
        if ( wpinv_use_taxes() && $invoice->get_tax() > 0 ) {
            $params['li_' . $i . '_type']       = 'tax';
            $params['li_' . $i . '_name']       = __( 'Tax', 'invoicing' );
            $params['li_' . $i . '_quantity']   = 1;
            $params['li_' . $i . '_price']      = wpinv_sanitize_amount( $invoice->get_tax() );
            $params['li_' . $i . '_tangible']   = 'N';
            $params['li_' . $i . '_product_id'] = $i;
        }
        
        $params['purchase_step']        = 'payment-method';
        $params['x_receipt_link_url']   = wpinv_get_ipn_url( '2co' );
        
        $params     = apply_filters( 'wpinv_2co_form_extra_parameters', $params, $invoice );
        
        $redirect_text  = __( 'Redirecting to 2Checkout site, click on button if not redirected.', 'invoicing' );
        $redirect_text  = apply_filters( 'wpinv_2co_redirect_text', $redirect_text, $invoice );
        
        // Empty the shopping cart
        wpinv_empty_cart();
        ?>
<div class="wpi-2co-form" style="padding:20px;font-family:arial,sans-serif;text-align:center;color:#555">
<?php do_action( 'wpinv_worldpay_form_before', $invoice ); ?>
<h3><?php echo $redirect_text ;?></h3>
<form action="<?php echo wpinv_get_2co_redirect(); ?>" name="wpi_2co_form" method="POST">
    <?php foreach ( $params as $param => $value ) { ?>
    <input type="hidden" value="<?php echo esc_attr( $value );?>" name="<?php echo esc_attr( $param );?>">
    <?php } ?>
    <?php do_action( 'wpinv_2co_form_parameters', $invoice ); ?>
    <input type="submit" name="wpi_2co_submit" value="<?php esc_attr_e( 'Pay by Debit/Credit Card (2Checkout)', 'invoicing' ) ;?>">
</form>
<script type="text/javascript">document.wpi_2co_form.submit();</script>
<?php do_action( 'wpinv_2co_form_after', $invoice ); ?>
</div>
        <?php
    } else {
        wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed while processing a 2checkout payment. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
        // If errors are present, send the user back to the purchase page so they can be corrected
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    }
}
add_action( 'wpinv_gateway_2co', 'wpinv_process_2co_payment' );

function wpinv_get_2co_redirect() {
    $redirect = wpinv_is_test_mode( '2co' ) ? 'https://sandbox.2checkout.com/checkout/purchase' : 'https://www.2checkout.com/checkout/purchase';
    
    return apply_filters( 'wpinv_2co_redirect', $redirect );
}

function wpinv_process_2co_ipn() {
    $request = wpinv_get_post_data( 'post' );

    if ( empty( $request['message_type'] ) ) {
        die( '-2' );
    }
    
    if ( empty( $request['vendor_id'] ) ) {
        die( '-3' );
    }
    
    if ( empty( $request['invoice_status'] ) ) {
        die( '-4' );
    }
    
    $vendor_order_id    = sanitize_text_field( $request['vendor_order_id'] );
    $invoice            = wpinv_get_invoice( $vendor_order_id );
    if ( empty( $invoice ) ) {
        die( '-6' );
    }
    $invoice_id         = $invoice->ID;
    $invoice_status     = $request['invoice_status'];
    
    wpinv_insert_payment_note( $invoice_id, sprintf( __( '2Checkout Payment Message: %s', 'invoicing' ) , $request['message_description'] ) );
    
    switch( strtoupper( $request['message_type'] ) ) {
        case 'ORDER_CREATED' :
        case 'INVOICE_STATUS_CHANGED' :
            if ($invoice_status == 'approved' || $invoice_status == 'deposited') {
                wpinv_update_payment_status( $invoice_id, 'publish' );
                wpinv_set_payment_transaction_id( $invoice_id, $request['sale_id'] );
                wpinv_insert_payment_note( $invoice_id, sprintf( __( '2Checkout Sale ID: %s', 'invoicing' ) , $request['sale_id'] ) );
            } else if ($invoice_status == 'declined') {
                wpinv_update_payment_status( $invoice_id, 'failed' );
                wpinv_record_gateway_error( __( '2CHECKOUT IPN ERROR', 'invoicing' ), __( 'Payment failed due to invalid purchase found.', 'invoicing' ), $invoice_id );
            } else if ($invoice_status == 'pending') {
                wpinv_update_payment_status( $invoice_id, 'pending' );
            }
            break;

        case 'REFUND_ISSUED' :
            // Process a refund
            wpinv_process_2co_refund( $request, $invoice_id );
            $redirect = 1;
            break;

        case 'RECURRING_INSTALLMENT_SUCCESS' :
            break;

        case 'RECURRING_INSTALLMENT_FAILED' :
            break;

        case 'RECURRING_STOPPED' :
            break;

        case 'RECURRING_COMPLETE' :
            break;

        case 'RECURRING_RESTARTED' :
            break;

        case 'FRAUD_STATUS_CHANGED' :
            switch ( $request['fraud_status'] ) {
                case 'pass':
                    if ($invoice_status == 'approved' || $invoice_status == 'deposited') {
                        wpinv_update_payment_status( $invoice_id, 'publish' );
                        wpinv_set_payment_transaction_id( $invoice_id, $request['sale_id'] );
                        wpinv_insert_payment_note( $invoice_id, sprintf( __( '2Checkout Sale ID: %s', 'invoicing' ) , $request['sale_id'] ) );
                    } else if ($invoice_status == 'declined') {
                        wpinv_update_payment_status( $invoice_id, 'failed' );
                        wpinv_record_gateway_error( __( '2CHECKOUT IPN ERROR', 'invoicing' ), __( 'Payment failed due to invalid purchase found.', 'invoicing' ), $invoice_id );
                    } else if ($invoice_status == 'pending') {
                        wpinv_update_payment_status( $invoice_id, 'pending' );
                    }
                    break;
                case 'fail':
                    wpinv_update_payment_status( $invoice_id, 'failed' );
                    wpinv_record_gateway_error( __( '2CHECKOUT IPN ERROR', 'invoicing' ), __( 'Payment flagged as fraudulent in 2Checkout', 'invoicing' ), $invoice_id );
                    break;
                case 'wait':
                    break;
            }

            break;
    }
    do_action( 'wpinv_process_2co_ipn_type_' . strtolower( $request['message_type'] ), $request, $invoice );
    
    return;
}
add_action( 'wpinv_verify_2co_ipn', 'wpinv_process_2co_ipn' );

function wpinv_2co_valid_ipn( $md5_hash, $sale_id, $vendor_id, $invoice_id ) {
    if ( empty( $md5_hash ) ) {
        return false;
    }
    
    $secret_word = wpinv_get_option( '2co_secret_word' );
    if ( empty( $secret_word ) ) {
        return true;
    }    
    
    $key = strtoupper( md5( $sale_id . $vendor_id . $invoice_id . $secret_word ) );
    
    // verify if the key is accurate
    return ( $md5_hash === $key );
}

function wpinv_process_2co_refund( $data, $invoice_id = 0 ) {
    // Collect payment details
    if( empty( $invoice_id ) ) {
        return;
    }

    if ( get_post_status( $invoice_id ) == 'refunded' ) {
        return; // Only refund payments once
    }

    $payment_amount = wpinv_payment_total( $invoice_id );
    $refund_amount  = 0;
    for ( $i = 1; $i <= (int)$data['item_count']; $i++ ) {
        $refund_amount = $data['item_cust_amount_' . $i];
    }

    if ( number_format( (float) $refund_amount, 2 ) < number_format( (float) $payment_amount, 2 ) ) {
        wpinv_insert_payment_note( $invoice_id, sprintf( __( 'Partial 2Checkout refund processed: %s', 'invoicing' ), $data['sale_id'] ) );
        return; // This is a partial refund
    }

    wpinv_update_payment_status( $invoice_id, 'refunded' );
    wpinv_insert_payment_note( $invoice_id, sprintf( __( '2Checkout Payment #%s Refunded for reason: %s', 'invoicing' ), $data['sale_id'], $data['message_description'] ) );
    wpinv_insert_payment_note( $invoice_id, sprintf( __( '2Checkout Refund Sale ID: %s', 'invoicing' ), $data['sale_id'] ) );
}