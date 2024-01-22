<?php
/**
 * Template that generates the subscription active email.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/wpinv-email-subscription_active.php.
 *
 * @version 2.8.4
 * @var WPInv_Subscription $object
 */

defined( 'ABSPATH' ) || exit;

$invoice = $object->get_parent_payment();

// Print the email header.
do_action( 'wpinv_email_header', $email_heading, $invoice, $email_type, $sent_to_admin );

// Generate the custom message body.
echo wp_kses_post( $message_body );

// Print the email footer.
do_action( 'wpinv_email_footer', $invoice, $email_type, $sent_to_admin );
