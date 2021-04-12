<?php
/**
 * Template that generates the trialling subscription email.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/wpinv-email-subscription_trial.php.
 *
 * @version 1.0.19
 * @var WPInv_Subscription $object
 */

defined( 'ABSPATH' ) || exit;

$invoice = $object->get_parent_payment();

// Print the email header.
do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

// Generate the custom message body.
echo $message_body;

// Print the billing details.
do_action( 'wpinv_email_billing_details', $invoice, $email_type, $sent_to_admin );

// Print the email footer.
do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );
