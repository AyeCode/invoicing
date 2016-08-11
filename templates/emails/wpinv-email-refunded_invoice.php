<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );
?>

<p><?php
    if ( !empty( $partial_refund ) ) {
        printf( __( 'Hi there. Your invoice on %s has been partially refunded.', 'invoicing' ), wpinv_get_business_name() );
    }
    else {
        printf( __( 'Hi there. Your invoice on %s has been refunded.', 'invoicing' ), wpinv_get_business_name() );
    }
?></p>

<?php
do_action( 'wpinv_email_invoice_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_invoice_items', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );