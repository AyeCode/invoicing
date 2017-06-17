<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );
?>

<p><?php printf( __( "Hi there. Your recent invoice on %s has been paid. Your invoice details are shown below for your reference:", 'invoicing' ), wpinv_get_business_name() ); ?></p>

<?php
do_action( 'wpinv_email_invoice_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_invoice_items', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );