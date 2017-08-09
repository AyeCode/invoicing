<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_before_note_details', $invoice, $email_type, $sent_to_admin, $customer_note );

do_action( 'wpinv_email_invoice_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_invoice_items', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

do_action( 'wpinv_email_after_note_details', $invoice, $email_type, $sent_to_admin, $customer_note );

do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );