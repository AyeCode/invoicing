<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );
?>

<p><?php printf( __( 'You have received payment from %s. The invoice is as follows:', 'invoicing' ), $invoice->get_user_full_name() ); ?></p>

<?php
do_action( 'wpinv_email_invoice_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_invoice_items', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );