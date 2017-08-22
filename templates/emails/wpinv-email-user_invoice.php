<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

if ( $invoice->needs_payment() ) {
    ?>
    <p><?php printf( __( 'An invoice has been created for you on %s. To pay for this invoice please use the following link: %s', 'invoicing' ), wpinv_get_business_name(), '<a href="' . esc_url( $invoice->get_view_url( true ) ) . '">' . __( 'Pay Now', 'invoicing' ) . '</a>' ); ?></p>
    <?php 
}

do_action( 'wpinv_email_invoice_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_invoice_items', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );