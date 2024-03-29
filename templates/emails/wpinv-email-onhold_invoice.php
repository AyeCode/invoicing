<?php
/**
 * Template that generated the onhold invoice email.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/wpinv-email-onhold_invoice.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Print the email header.
do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

// Generate the custom message body.
echo wp_kses_post( $message_body );

// Print invoice details.
do_action( 'wpinv_email_invoice_details', $invoice, $email_type, $sent_to_admin );

// Print invoice items.
do_action( 'wpinv_email_invoice_items', $invoice, $email_type, $sent_to_admin );

// Print the billing details.
do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

// Print the email footer.
do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );
