<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wpinv_worldpay_cc_form', '__return_false' );

function wpinv_process_worldpay_payment( $purchase_data ) {
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
        'gateway'       => 'worldpay',
        'status'        => 'wpi-pending'
    );

    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );
    
    if ( !empty( $invoice ) ) {
        $quantities_enabled = wpinv_item_quantities_enabled();
        
        $instId     = wpinv_get_option( 'worldpay_instId', false );
        $cartId     = $invoice->get_number();
        $testMode   = wpinv_is_test_mode( 'worldpay' ) ? 100 : 0;
        $name       = $invoice->get_user_full_name();
        $address    = wp_strip_all_tags( $invoice->get_address(), true );
        $postcode   = $invoice->zip;
        $tel        = $invoice->phone;
        $email      = $invoice->get_email();
        $country    = $invoice->country;
        $amount     = wpinv_sanitize_amount( $invoice->get_total() );
        $currency   = wpinv_get_currency();
        
        $items      = array();
        foreach ( $invoice->get_cart_details() as $item ) {
            $item_desc  = $item['name'];
            $quantity   = !empty( $item['quantity'] ) && $item['quantity'] > 0 ? $item['quantity'] : 1;
            $item_desc .= ' (' . ( $quantities_enabled ? $quantity . 'x ' : '' ) . wpinv_price( wpinv_format_amount( $item['item_price'] ) ) . ')';
            
            $items[] = $item_desc;
        }
        
        $desc = implode( ', ', $items );
        if ( wpinv_use_taxes() && $invoice->get_tax() > 0 ) {
            $desc .= ', ' . wp_sprintf( __( 'Tax: %s', 'invoicing' ), $invoice->get_tax( true ) );
        }
        
        $extra_params                   = array();
        $extra_params['MC_description'] = $desc;
        $extra_params['MC_callback']    = wpinv_get_ipn_url( 'worldpay' );
        $extra_params['MC_key']         = $invoice->get_key();
        $extra_params['MC_invoice_id']  = $invoice->ID;
        $extra_params['address1']       = $address;
        $extra_params['town']           = $invoice->city;
        $extra_params['region']         = $invoice->state;
        $extra_params['amountString']   = $invoice->get_total( true );
        $extra_params['countryString']  = wpinv_country_name( $invoice->country );
        $extra_params['compName']       = $invoice->company;
        
        $extra_params   = apply_filters( 'wpinv_worldpay_form_extra_parameters', $extra_params, $invoice );
        
        $redirect_text  = __( 'Redirecting to Worldpay site, click on button if not redirected.', 'invoicing' );
        $redirect_text  = apply_filters( 'wpinv_worldpay_redirect_text', $redirect_text, $invoice );
        
        // Empty the shopping cart
        wpinv_empty_cart();
        ?>
<div class="wpi-worldpay-form" style="padding:20px;font-family:arial,sans-serif;text-align:center;color:#555">
<?php do_action( 'wpinv_worldpay_form_before', $invoice ); ?>
<h3><?php echo $redirect_text ;?></h3>
<form action="<?php echo wpinv_get_worldpay_redirect(); ?>" name="wpi_worldpay_form" method="POST">
    <input type="hidden" value="<?php echo $amount;?>" name="amount">
    <input type="hidden" value="<?php echo esc_attr( $cartId );?>" name="cartId">
    <input type="hidden" value="<?php echo $currency;?>" name="currency">
    <input type="hidden" value="<?php echo $instId;?>" name="instId">
    <input type="hidden" value="<?php echo $testMode;?>" name="testMode">
    <input type="hidden" value="<?php echo esc_attr( $name );?>" name="name">
    <input type="hidden" value="<?php echo esc_attr( $address );?>" name="address">
    <input type="hidden" value="<?php echo esc_attr( $postcode );?>" name="postcode">
    <input type="hidden" value="<?php echo esc_attr( $tel );?>" name="tel">
    <input type="hidden" value="<?php echo esc_attr( $email );?>" name="email">
    <input type="hidden" value="<?php echo esc_attr( $country );?>" name="country">
    <input type="hidden" value="<?php echo esc_attr( $desc );?>" name="desc">
    <?php foreach ( $extra_params as $param => $value ) { ?>
        <?php if ( !empty( $value !== false ) ) { ?>
    <input type="hidden" value="<?php echo esc_attr( $value );?>" name="<?php echo esc_attr( $param );?>">
        <?php } ?>
    <?php } ?>
    <?php do_action( 'wpinv_worldpay_form_parameters', $invoice ); ?>
    <input type="submit" name="wpi_worldpay_submit" value="<?php esc_attr_e( 'Pay by Debit/Credit Card (WorldPay)', 'invoicing' ) ;?>">
</form>
<script type="text/javascript">document.wpi_worldpay_form.submit();</script>
<?php do_action( 'wpinv_worldpay_form_after', $invoice ); ?>
</div>
        <?php
    } else {
        wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed while processing a worldpay payment. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
        // If errors are present, send the user back to the purchase page so they can be corrected
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    }
}
add_action( 'wpinv_gateway_worldpay', 'wpinv_process_worldpay_payment' );

function wpinv_get_worldpay_redirect() {
    $redirect = wpinv_is_test_mode( 'worldpay' ) ? 'https://secure-test.worldpay.com/wcc/purchase' : 'https://secure.worldpay.com/wcc/purchase';
    
    return apply_filters( 'wpinv_worldpay_redirect', $redirect );
}

function wpinv_process_worldpay_ipn() {
    $request = wpinv_get_post_data( 'post' );
    
    if ( !empty( $request['cartId'] ) && !empty( $request['transStatus'] ) && !empty( $request['installation'] ) && isset( $request['testMode'] ) && isset( $request['MC_invoice_id'] ) && isset( $request['MC_key'] ) ) {
        $invoice_id = $request['MC_invoice_id'];
        
        if ( $invoice_id == wpinv_get_invoice_id_by_key( $request['MC_key'] ) && $invoice = wpinv_get_invoice( $invoice_id ) ) {
            if ( $request['transStatus'] == 'Y' ) {                
                wpinv_update_payment_status( $invoice_id, 'publish' );
                wpinv_set_payment_transaction_id( $invoice_id, $request['transId'] );
                wpinv_insert_payment_note( $invoice_id, sprintf( __( 'Worldpay Transaction ID: %s', 'invoicing' ), $request['transId'] ) );
                return;
            } else if ( $request['transStatus'] == 'C' ) {
                wpinv_update_payment_status( $invoice_id, 'wpi-failed' );
                wpinv_insert_payment_note( $invoice_id, __( 'Payment transaction failed while processing Worldpay payment, kindly check IPN log.', 'invoicing' ) );
                
                wpinv_record_gateway_error( __( 'IPN Error', 'invoicing' ), sprintf( __( 'Payment transaction failed while processing Worldpay payment. IPN data: %s', 'invoicing' ), json_encode( $request ) ), $invoice_id );
                return;
            }
        }
    }
    return;
}
add_action( 'wpinv_verify_worldpay_ipn', 'wpinv_process_worldpay_ipn' );