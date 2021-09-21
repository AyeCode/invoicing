<?php
/**
 * Email settings
 *
 * Returns an array of email settings.
 *
 * @package Invoicing/data
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Prepare the due date reminder options.
$overdue_days_options       = array();
$overdue_days_options['0']  = __( 'On the Due Date', 'invoicing' );
$overdue_days_options['1']  = __( '1 day after Due Date', 'invoicing' );

for ( $i = 2; $i <= 10; $i++ ) {
    $overdue_days_options["$i"] = wp_sprintf( __( '%d days after Due Date', 'invoicing' ), $i );
}

// Prepare up coming renewal reminder options.
$renewal_days_options       = array();
$renewal_days_options['0']  = __( 'On the renewal date', 'invoicing' );
$renewal_days_options['1']  = __( '1 day before the renewal date', 'invoicing' );

for ( $i = 2; $i <= 10; $i++ ) {
    $renewal_days_options["$i"]   = wp_sprintf( __( '%d days before the renewal date', 'invoicing' ), $i );
}

// Default, built-in gateways
return array(
    'new_invoice' => array(

        'email_new_invoice_header' => array(
            'id'       => 'email_new_invoice_header',
            'name'     => '<h3>' . __( 'New Invoice', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent to the site admin whenever there is a new invoice.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_new_invoice_active' => array(
            'id'       => 'email_new_invoice_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_new_invoice_subject' => array(
            'id'       => 'email_new_invoice_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] New invoice ({invoice_number}) for {invoice_total} {invoice_currency}', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_new_invoice_heading' => array(
            'id'       => 'email_new_invoice_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'New invoice', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_new_invoice_body' => array(
            'id'       => 'email_new_invoice_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>A new invoice <a href="{invoice_link}">({invoice_number})</a> to {name} for {invoice_total} {invoice_currency} has been created on your site. <a class="btn btn-success" href="{invoice_link}">View / Print Invoice</a></p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),
    ),

    'cancelled_invoice' => array(

        'email_cancelled_invoice_header' => array(
            'id'       => 'email_cancelled_invoice_header',
            'name'     => '<h3>' . __( 'Cancelled Invoice', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent to the site admin whenever invoices are cancelled.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_cancelled_invoice_active' => array(
            'id'       => 'email_cancelled_invoice_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_cancelled_invoice_subject' => array(
            'id'       => 'email_cancelled_invoice_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Invoice ({invoice_number}) Cancelled', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_cancelled_invoice_heading' => array(
            'id'       => 'email_cancelled_invoice_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Invoice Cancelled', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_cancelled_invoice_body' => array(
            'id'       => 'email_cancelled_invoice_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>The invoice <a href="{invoice_link}">#{invoice_number}</a> created for {name} on {site_title} has been cancelled. <a class="btn btn-success" href="{invoice_link}">View / Print Invoice</a></p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),

    ),

    'failed_invoice' => array(

        'email_failed_invoice_header' => array(
            'id'       => 'email_failed_invoice_header',
            'name'     => '<h3>' . __( 'Failed Invoice', 'invoicing' ) . '</h3>',
            'desc'     => __( 'Failed invoice emails are sent to the site admin when invoice payments fail.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_failed_invoice_active' => array(
            'id'       => 'email_failed_invoice_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_failed_invoice_subject' => array(
            'id'       => 'email_failed_invoice_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Invoice ({invoice_number}) Payment Failed', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_failed_invoice_heading' => array(
            'id'       => 'email_failed_invoice_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Invoice Payment Failed', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_failed_invoice_body' => array(
            'id'       => 'email_failed_invoice_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Payment for the invoice <a href="{invoice_link}">#{invoice_number}</a> on {site_title} has failed to go through. <a class="btn btn-success" href="{invoice_link}">View / Print Invoice</a></p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),
    ),

    'onhold_invoice' => array(

        'email_onhold_invoice_header' => array(
            'id'       => 'email_onhold_invoice_header',
            'name'     => '<h3>' . __( 'On Hold Invoice', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent to customers whenever their invoices are held.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_onhold_invoice_active' => array(
            'id'       => 'email_onhold_invoice_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_onhold_invoice_admin_bcc' => array(
            'id'       => 'email_onhold_invoice_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send this notification email to site Admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_onhold_invoice_subject' => array(
            'id'       => 'email_onhold_invoice_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Your invoice is on hold', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_onhold_invoice_heading' => array(
            'id'       => 'email_onhold_invoice_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Your invoice is on hold', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_onhold_invoice_body' => array(
            'id'       => 'email_onhold_invoice_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {name},</p><p>Your invoice is on-hold and will be processed when we receive your payment. <a class="btn btn-success" href="{invoice_link}">View / Print Invoice</a></p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),

    ),

    'processing_invoice' => array(

        'email_processing_invoice_header' => array(
            'id'       => 'email_processing_invoice_header',
            'name'     => '<h3>' . __( 'Processing Invoice', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent to users whenever payments for their invoices are processing.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_processing_invoice_active' => array(
            'id'       => 'email_processing_invoice_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_processing_invoice_admin_bcc' => array(
            'id'       => 'email_processing_invoice_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_processing_invoice_subject' => array(
            'id'       => 'email_processing_invoice_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Your payment is being processed', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_processing_invoice_heading' => array(
            'id'       => 'email_processing_invoice_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Your payment is being processed', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_processing_invoice_body' => array(
            'id'       => 'email_processing_invoice_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {name},</p><p>I would like to let you know that we have received and are currently processing your payment for the invoice <a href="{invoice_link}">#{invoice_number}</a> on {site_title}. <a class="btn btn-success" href="{invoice_link}">View / Print Invoice</a></p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),

    ),

    'completed_invoice' => array(

        'email_completed_invoice_header' => array(
            'id'       => 'email_completed_invoice_header',
            'name'     => '<h3>' . __( 'Paid Invoice', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent to customers when their invoices are marked as paid.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_completed_invoice_active' => array(
            'id'       => 'email_completed_invoice_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_completed_invoice_renewal_active' => array(
            'id'       => 'email_completed_invoice_renewal_active',
            'name'     => __( 'Enable renewal notification', 'invoicing' ),
            'desc'     => __( 'Should this email be sent for renewals too?', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_completed_invoice_admin_bcc' => array(
            'id'       => 'email_completed_invoice_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1,
        ),

        'email_completed_invoice_subject' => array(
            'id'       => 'email_completed_invoice_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Your invoice from {invoice_date} has been paid', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_completed_invoice_heading' => array(
            'id'       => 'email_completed_invoice_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Your invoice has been paid', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_completed_invoice_body' => array(
            'id'       => 'email_completed_invoice_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {name},</p><p>Your recent invoice on {site_title} has been paid. <a class="btn btn-success" href="{invoice_link}">View / Print Invoice</a></p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),

    ),

    'refunded_invoice' => array(

        'email_refunded_invoice_header' => array(
            'id'       => 'email_refunded_invoice_header',
            'name'     => '<h3>' . __( 'Refunded Invoice', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent to users when their invoices are marked as refunded.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_refunded_invoice_active' => array(
            'id'       => 'email_refunded_invoice_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_refunded_invoice_admin_bcc' => array(
            'id'       => 'email_refunded_invoice_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_refunded_invoice_subject' => array(
            'id'       => 'email_refunded_invoice_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Your invoice from {invoice_date} has been refunded', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_refunded_invoice_heading' => array(
            'id'       => 'email_refunded_invoice_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Your invoice has been refunded', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_refunded_invoice_body' => array(
            'id'       => 'email_refunded_invoice_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {name},</p><p>Your invoice on {site_title} has been refunded. <a class="btn btn-success" href="{invoice_link}">View / Print Invoice</a></p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),

    ),

    'user_invoice' => array(

        'email_user_invoice_header' => array(
            'id'       => 'email_user_invoice_header',
            'name'     => '<h3>' . __( 'Customer Invoice', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent to customers containing their invoice information and payment links.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_user_invoice_active' => array(
            'id'       => 'email_user_invoice_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_user_invoice_admin_bcc' => array(
            'id'       => 'email_user_invoice_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 0
        ),

        'email_user_invoice_subject' => array(
            'id'       => 'email_user_invoice_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Your invoice from {invoice_date}', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_user_invoice_heading' => array(
            'id'       => 'email_user_invoice_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Your invoice {invoice_number} details', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_user_invoice_body' => array(
            'id'       => 'email_user_invoice_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {name},</p><p>An invoice of {invoice_total} has been created for you on {site_title}. You can <a href="{invoice_link}">view</a> or <a href="{invoice_pay_link}">pay</a> the invoice. Please reply to this email if you have any questions about the invoice.', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),
    ),

    'user_note' => array(

        'email_user_note_header' => array(
            'id'       => 'email_user_note_header',
            'name'     => '<h3>' . __( 'Customer Note', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent when you add a customer note to an invoice/quote.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_user_note_active' => array(
            'id'       => 'email_user_note_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_user_note_admin_bcc' => array(
            'id'       => 'email_user_note_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 0
        ),

        'email_user_note_subject' => array(
            'id'       => 'email_user_note_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Note added to your {invoice_label} #{invoice_number} from {invoice_date}', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_user_note_heading' => array(
            'id'       => 'email_user_note_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'A note has been added to your {invoice_label}', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_user_note_body' => array(
            'id'       => 'email_user_note_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {name},</p><p>The following note has been added to your {invoice_label} <a href="{invoice_link}">#{invoice_number}</a>:</p><blockquote class="wpinv-note">{customer_note}</blockquote><a class="btn btn-success" href="{invoice_link}">View / Print Invoice</a>', 'invoicing' ),
            'class'    => 'large',
            'size'     => '10'
        ),

    ),
    'overdue' => array(

        'email_overdue_header' => array(
            'id'       => 'email_overdue_header',
            'name'     => '<h3>' . __( 'Payment Reminder', 'invoicing' ) . '</h3>',
            'desc'     => __( 'Payment reminder emails are sent to customers whenever their invoices are due.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_overdue_active' => array(
            'id'       => 'email_overdue_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_overdue_admin_bcc' => array(
            'id'       => 'email_overdue_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 0
        ),

        'email_overdue_days' => array(
            'id'       => 'email_overdue_days',
            'name'     => __( 'When to Send', 'invoicing' ),
            'desc'     => __( 'Check when you would like payment reminders sent out.', 'invoicing' ),
            'help-tip' => true,
            'std'      => array( '1' ),
            'type'     => 'multicheck',
            'options'  => $overdue_days_options,
        ),

        'email_overdue_subject' => array(
            'id'       => 'email_overdue_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Payment Reminder', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_overdue_heading' => array(
            'id'       => 'email_overdue_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Payment reminder for your invoice', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_overdue_body' => array(
            'id'       => 'email_overdue_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text(),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {full_name},</p><p>This is just a friendly reminder that your invoice <a href="{invoice_link}">#{invoice_number}</a> {is_was} due on {invoice_due_date}.</p><p>The total of this invoice is {invoice_total}</p><p>To view / pay now for this invoice please use the following link: <a class="btn btn-success" href="{invoice_link}">View / Pay</a></p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => 10,
        ),

    ),

    'renewal_reminder' => array(

        'email_renewal_reminder_header' => array(
            'id'       => 'email_renewal_reminder_header',
            'name'     => '<h3>' . __( 'Renewal Reminder', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent to customers whenever their subscription is about to renew.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_renewal_reminder_active' => array(
            'id'       => 'email_renewal_reminder_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 0
        ),

        'email_renewal_reminder_admin_bcc' => array(
            'id'       => 'email_renewal_reminder_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 0
        ),

        'email_renewal_reminder_days' => array(
            'id'       => 'email_renewal_reminder_days',
            'name'     => __( 'When to Send', 'invoicing' ),
            'desc'     => __( 'Check when you would like renewal reminders sent out.', 'invoicing' ),
            'help-tip' => true,
            'std'      => array( '1', '5', '10' ),
            'type'     => 'multicheck',
            'options'  => $renewal_days_options,
        ),

        'email_renewal_reminder_subject' => array(
            'id'       => 'email_renewal_reminder_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Renewal Reminder', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_renewal_reminder_heading' => array(
            'id'       => 'email_renewal_reminder_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Upcoming renewal reminder', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_renewal_reminder_body' => array(
            'id'       => 'email_renewal_reminder_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text( true ),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {full_name},</p><p>This is just a friendly reminder that your subscription for invoice <a href="{invoice_link}">#{invoice_number}</a> will renew on {subscription_renewal_date}.</p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => 10,
        ),

    ),

    'subscription_trial' => array(

        'email_subscription_trial_header' => array(
            'id'       => 'email_subscription_trial_header',
            'name'     => '<h3>' . __( 'Trial Started', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent when a customer starts a subscription trial.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_subscription_trial_active' => array(
            'id'       => 'email_subscription_trial_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 0
        ),

        'email_subscription_trial_admin_bcc' => array(
            'id'       => 'email_subscription_trial_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 0
        ),

        'email_subscription_trial_subject' => array(
            'id'       => 'email_subscription_trial_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the subscription trial email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Trial Started', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_subscription_trial_heading' => array(
            'id'       => 'email_subscription_trial_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading of this email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Trial Started', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_subscription_trial_body' => array(
            'id'       => 'email_subscription_trial_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text( true ),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {first_name},</p><p>Your trial for {subscription_name} is now active and will renew on {subscription_renewal_date}.</p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => 10,
        ),
    ),

    'subscription_cancelled' => array(

        'email_subscription_cancelled_header' => array(
            'id'       => 'email_subscription_cancelled_header',
            'name'     => '<h3>' . __( 'Subscription Cancelled', 'invoicing' ) . '</h3>',
            'desc'     => __( 'These emails are sent when a customer cancels their subscription.', 'invoicing' ),
            'type'     => 'header',
        ),

        'email_subscription_cancelled_active' => array(
            'id'       => 'email_subscription_cancelled_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_subscription_cancelled_admin_bcc' => array(
            'id'       => 'email_subscription_cancelled_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_subscription_cancelled_subject' => array(
            'id'       => 'email_subscription_cancelled_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the subscription cancelled email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Subscription Cancelled', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_subscription_cancelled_heading' => array(
            'id'       => 'email_subscription_cancelled_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading of this email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Subscription Cancelled', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_subscription_cancelled_body' => array(
            'id'       => 'email_subscription_cancelled_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text( true ),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {first_name},</p><p>Your subscription for {subscription_name} has been cancelled and will no longer renew.</p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => 10,
        ),
    ),

    'subscription_expired' => array(

        'email_subscription_expired_header' => array(
            'id'       => 'email_subscription_expired_header',
            'name'     => '<h3>' . __( 'Subscription Expired', 'invoicing' ) . '</h3>',
            'desc'     => __( "These emails are sent when a customer's subscription expires and automatic renewal fails.", 'invoicing' ),
            'type'     => 'header',
        ),

        'email_subscription_expired_active' => array(
            'id'       => 'email_subscription_expired_active',
            'name'     => __( 'Enable/Disable', 'invoicing' ),
            'desc'     => __( 'Enable this email notification', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_subscription_expired_admin_bcc' => array(
            'id'       => 'email_subscription_expired_admin_bcc',
            'name'     => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'     => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'     => 'checkbox',
            'std'      => 1
        ),

        'email_subscription_expired_subject' => array(
            'id'       => 'email_subscription_expired_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the subscription expired email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Subscription Expired', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_subscription_expired_heading' => array(
            'id'       => 'email_subscription_expired_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading of this email.', 'invoicing' ),
            'type'     => 'text',
            'std'      => __( 'Subscription Expired', 'invoicing' ),
            'help-tip' => true,
            'size'     => 'large'
        ),

        'email_subscription_expired_body' => array(
            'id'       => 'email_subscription_expired_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text( true ),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {first_name},</p><p>Your subscription for {subscription_name} has expired.</p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => 10,
        ),
    ),

    'subscription_complete' => array(

        'email_subscription_complete_header' => array(
            'id'     => 'email_subscription_complete_header',
            'name'   => '<h3>' . __( 'Subscription Complete', 'invoicing' ) . '</h3>',
            'desc'   => __( 'These emails are sent when a customer completes their subscription.', 'invoicing' ),
            'type'   => 'header',
        ),

        'email_subscription_complete_active' => array(
            'id'      => 'email_subscription_complete_active',
            'name'    => __( 'Enable/Disable', 'invoicing' ),
            'desc'    => __( 'Enable this email notification', 'invoicing' ),
            'type'    => 'checkbox',
            'std'     => 1
        ),

        'email_subscription_complete_admin_bcc' => array(
            'id'      => 'email_subscription_complete_admin_bcc',
            'name'    => __( 'Enable Admin BCC', 'invoicing' ),
            'desc'    => __( 'Check if you want to send a copy of this notification email to the site admin.', 'invoicing' ),
            'type'    => 'checkbox',
            'std'     => 1
        ),

        'email_subscription_complete_subject' => array(
            'id'       => 'email_subscription_complete_subject',
            'name'     => __( 'Subject', 'invoicing' ),
            'desc'     => __( 'Enter the subject line for the subscription complete email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( '[{site_title}] Subscription Complete', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_subscription_complete_heading' => array(
            'id'       => 'email_subscription_complete_heading',
            'name'     => __( 'Email Heading', 'invoicing' ),
            'desc'     => __( 'Enter the main heading of this email.', 'invoicing' ),
            'help-tip' => true,
            'type'     => 'text',
            'std'      => __( 'Subscription Complete', 'invoicing' ),
            'size'     => 'large'
        ),

        'email_subscription_complete_body' => array(
            'id'       => 'email_subscription_complete_body',
            'name'     => __( 'Email Content', 'invoicing' ),
            'desc'     => wpinv_get_merge_tags_help_text( true ),
            'type'     => 'rich_editor',
            'std'      => __( '<p>Hi {first_name},</p><p>Your subscription for {subscription_name} is now complete.</p>', 'invoicing' ),
            'class'    => 'large',
            'size'     => 10,
        ),
    ),

);