<?php
/**
 * Template that generates the PayPal renewal payment failed email.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/wpinv-email-paypal_payment_failed.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$invoice = $object->get_parent_payment();

// Print the email header.
do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

// Generate the custom message body.
echo wp_kses( $message_body, getpaid_allowed_html() );

// Print the billing details.
do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

// Print the email footer.
do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );
