<?php
/**
 * Contains functions related to Invoicing plugin.
 *
 * @since 1.0.0
 * @package Invoicing
 */

// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_init_transactional_emails() {
    $email_actions = apply_filters( 'wpinv_email_actions', array(
        'wpinv_status_wpi-pending_to_wpi-processing',
        'wpinv_status_wpi-pending_to_publish',
        'wpinv_status_wpi-pending_to_wpi-cancelled',
        'wpinv_status_wpi-pending_to_wpi-failed',
        'wpinv_status_wpi-pending_to_wpi-onhold',
        'wpinv_status_wpi-failed_to_wpi-processing',
        'wpinv_status_wpi-failed_to_publish',
        'wpinv_status_wpi-failed_to_wpi-onhold',
        'wpinv_status_wpi-onhold_wpi-to_processing',
        'wpinv_status_wpi-onhold_to_wpi-cancelled',
        'wpinv_status_wpi-onhold_to_wpi-failed',
        'wpinv_status_publish_to_wpi-refunded',
        'wpinv_status_wpi-processing_to_wpi-refunded',
        'wpinv_status_publish',
        'wpinv_fully_refunded',
        'wpinv_partially_refunded',
        'wpinv_new_invoice_note'
    ) );

    foreach ( $email_actions as $action ) {
        add_action( $action, 'wpinv_send_transactional_email', 10, 10 );
    }
}
add_action( 'init', 'wpinv_init_transactional_emails' );

// New invoice email
add_action( 'wpinv_status_wpi-pending_to_wpi-processing_notification', 'wpinv_new_invoice_notification' );
add_action( 'wpinv_status_wpi-pending_to_publish_notification', 'wpinv_new_invoice_notification' );
add_action( 'wpinv_status_wpi-pending_to_wpi-onhold_notification', 'wpinv_new_invoice_notification' );
add_action( 'wpinv_status_wpi-failed_to_wpi-processing_notification', 'wpinv_new_invoice_notification' );
add_action( 'wpinv_status_wpi-failed_to_publish_notification', 'wpinv_new_invoice_notification' );
add_action( 'wpinv_status_wpi-failed_to_wpi-onhold_notification', 'wpinv_new_invoice_notification' );

// Cancelled invoice email
add_action( 'wpinv_status_wpi-pending_to_wpi-cancelled_notification', 'wpinv_cancelled_invoice_notification' );
add_action( 'wpinv_status_wpi-onhold_to_wpi-cancelled_notification', 'wpinv_cancelled_invoice_notification' );

// Failed invoice email
add_action( 'wpinv_status_wpi-pending_to_wpi-failed_notification', 'wpinv_failed_invoice_notification' );
add_action( 'wpinv_status_wpi-onhold_to_wpi-failed_notification', 'wpinv_failed_invoice_notification' );

// On hold invoice email
add_action( 'wpinv_status_wpi-pending_to_wpi-onhold_notification', 'wpinv_onhold_invoice_notification' );
add_action( 'wpinv_status_wpi-failed_to_wpi-onhold_notification', 'wpinv_onhold_invoice_notification' );

// Processing invoice email
add_action( 'wpinv_status_wpi-pending_to_wpi-processing_notification', 'wpinv_processing_invoice_notification' );

// Paid invoice email
add_action( 'wpinv_status_publish_notification', 'wpinv_completed_invoice_notification' );

// Refunded invoice email
add_action( 'wpinv_fully_refunded_notification', 'wpinv_fully_refunded_notification' );
add_action( 'wpinv_partially_refunded_notification', 'wpinv_partially_refunded_notification' );
add_action( 'wpinv_status_publish_to_wpi-refunded_notification', 'wpinv_fully_refunded_notification' );
add_action( 'wpinv_status_wpi-processing_to_wpi-refunded_notification', 'wpinv_fully_refunded_notification' );

// Invoice note
add_action( 'wpinv_new_invoice_note_notification', 'wpinv_new_invoice_note_notification' );

add_action( 'wpinv_email_header', 'wpinv_email_header' );
add_action( 'wpinv_email_footer', 'wpinv_email_footer' );
add_action( 'wpinv_email_invoice_details', 'wpinv_email_invoice_details', 10, 3 );
add_action( 'wpinv_email_invoice_items', 'wpinv_email_invoice_items', 10, 3 );
add_action( 'wpinv_email_billing_details', 'wpinv_email_billing_details', 10, 3 );

function wpinv_send_transactional_email() {
    $args       = func_get_args();
    $function   = current_filter() . '_notification';
    do_action_ref_array( $function, $args );
}

function wpinv_new_invoice_notification( $invoice_id, $new_status = '' ) {
    $email_type = 'new_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type, true );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => true,
            'plain_text'    => false,
            'message_body'  => $message_body,
        ) );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type, true );

    return $sent;
}

function wpinv_cancelled_invoice_notification( $invoice_id, $new_status = '' ) {
    $email_type = 'cancelled_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type, true );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => true,
            'plain_text'    => false,
            'message_body'  => $message_body,
        ) );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type, true );

    return $sent;
}

function wpinv_failed_invoice_notification( $invoice_id, $new_status = '' ) {
    $email_type = 'failed_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }
    
    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type, true );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );
    
    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => true,
            'plain_text'    => false,
            'message_body'  => $message_body,
        ) );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type, true );

    return $sent;
}

function wpinv_onhold_invoice_notification( $invoice_id, $new_status = '' ) {
    $email_type = 'onhold_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );
    
    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'message_body'  => $message_body,
        ) );
    
    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    
    if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
        $recipient  = wpinv_get_admin_email();
        $subject    .= ' - ADMIN BCC COPY';
        wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    }

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    return $sent;
}

function wpinv_processing_invoice_notification( $invoice_id, $new_status = '' ) {
    $email_type = 'processing_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $search                     = array();
    $search['invoice_number']   = '{invoice_number}';
    $search['invoice_date']     = '{invoice_date}';
    $search['name']             = '{name}';

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );
    
    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'message_body'  => $message_body,
        ) );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
        $recipient  = wpinv_get_admin_email();
        $subject    .= ' - ADMIN BCC COPY';
        wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    }

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    return $sent;
}

function wpinv_completed_invoice_notification( $invoice_id, $new_status = '' ) {
    $email_type = 'completed_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    if($invoice->is_renewal() && wpinv_email_is_enabled( 'completed_invoice_renewal' )){
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'message_body'  => $message_body,
        ) );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
        $recipient  = wpinv_get_admin_email();
        $subject    .= ' - ADMIN BCC COPY';
        wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    }

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    return $sent;
}

function wpinv_fully_refunded_notification( $invoice_id, $new_status = '' ) {
    $email_type = 'refunded_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'           => $invoice,
            'email_type'        => $email_type,
            'email_heading'     => $email_heading,
            'sent_to_admin'     => false,
            'plain_text'        => false,
            'partial_refund'    => false,
            'message_body'      => $message_body,
        ) );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
        $recipient  = wpinv_get_admin_email();
        $subject    .= ' - ADMIN BCC COPY';
        wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    }

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    return $sent;
}

function wpinv_partially_refunded_notification( $invoice_id, $new_status = '' ) {
    $email_type = 'refunded_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'           => $invoice,
            'email_type'        => $email_type,
            'email_heading'     => $email_heading,
            'sent_to_admin'     => false,
            'plain_text'        => false,
            'partial_refund'    => true,
            'message_body'      => $message_body,
        ) );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
        $recipient  = wpinv_get_admin_email();
        $subject    .= ' - ADMIN BCC COPY';
        wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    }

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    return $sent;
}

function wpinv_new_invoice_note_notification( $invoice_id, $new_status = '' ) {
}

function wpinv_user_invoice_notification( $invoice_id ) {
    $email_type = 'user_invoice';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return -1;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !("wpi_invoice" === $invoice->post_type) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );
    
    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'message_body'  => $message_body,
        ) );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    if ( wpinv_mail_admin_bcc_active( $email_type ) ) {
        $recipient  = wpinv_get_admin_email();
        $subject    .= ' - ADMIN BCC COPY';
        wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    }

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    if ( $sent ) {
        $note = __( 'Invoice has been emailed to the user.', 'invoicing' );
    } else {
        $note = __( 'Fail to send invoice to the user!', 'invoicing' );
    }

    $invoice->add_note( $note, '', '', true ); // Add system note.

    return $sent;
}

function wpinv_user_note_notification( $invoice_id, $args = array() ) {
    $email_type = 'user_note';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    $recipient      = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    $defaults = array(
        'user_note' => ''
    );

    $args = wp_parse_args( $args, $defaults );

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $message_body   = str_replace( '{customer_note}', $args['user_note'], $message_body );

    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'message_body'  => $message_body,
            'customer_note' => $args['user_note']
        ) );

    $content        = wpinv_email_format_text( $content, $invoice );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    return $sent;
}

function wpinv_mail_get_from_address() {
    $from_address = apply_filters( 'wpinv_mail_from_address', wpinv_get_option( 'email_from' ) );
    return sanitize_email( $from_address );
}

function wpinv_mail_get_from_name() {
    $from_name = apply_filters( 'wpinv_mail_from_name', wpinv_get_option( 'email_from_name' ) );
    return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
}

function wpinv_mail_admin_bcc_active( $mail_type = '' ) {
    $active = apply_filters( 'wpinv_mail_admin_bcc_active', wpinv_get_option( 'email_' . $mail_type . '_admin_bcc' ) );
    return ( $active ? true : false );
}
    
function wpinv_mail_get_content_type(  $content_type = 'text/html', $email_type = 'html' ) {
    $email_type = apply_filters( 'wpinv_mail_content_type', $email_type );

    switch ( $email_type ) {
        case 'html' :
            $content_type = 'text/html';
            break;
        case 'multipart' :
            $content_type = 'multipart/alternative';
            break;
        default :
            $content_type = 'text/plain';
            break;
    }

    return $content_type;
}
    
function wpinv_mail_send( $to, $subject, $message, $headers, $attachments ) {
    add_filter( 'wp_mail_from', 'wpinv_mail_get_from_address' );
    add_filter( 'wp_mail_from_name', 'wpinv_mail_get_from_name' );
    add_filter( 'wp_mail_content_type', 'wpinv_mail_get_content_type' );

    $message = wpinv_email_style_body( $message );
    $message = apply_filters( 'wpinv_mail_content', $message );

    $sent  = wp_mail( $to, $subject, $message, $headers, $attachments );

    if ( !$sent ) {
        $log_message = wp_sprintf( __( "\nTime: %s\nTo: %s\nSubject: %s\n", 'invoicing' ), date_i18n( 'F j Y H:i:s', current_time( 'timestamp' ) ), ( is_array( $to ) ? implode( ', ', $to ) : $to ), $subject );
        wpinv_error_log( $log_message, __( "Email from Invoicing plugin failed to send", 'invoicing' ), __FILE__, __LINE__ );
    }

    remove_filter( 'wp_mail_from', 'wpinv_mail_get_from_address' );
    remove_filter( 'wp_mail_from_name', 'wpinv_mail_get_from_name' );
    remove_filter( 'wp_mail_content_type', 'wpinv_mail_get_content_type' );

    return $sent;
}
    
function wpinv_get_emails() {
    $overdue_days_options       = array();
    $overdue_days_options[0]    = __( 'On the Due Date', 'invoicing' );
    $overdue_days_options[1]    = __( '1 day after Due Date', 'invoicing' );

    for ( $i = 2; $i <= 10; $i++ ) {
        $overdue_days_options[$i]   = wp_sprintf( __( '%d days after Due Date', 'invoicing' ), $i );
    }

    // Default, built-in gateways
    $emails = array(
            'new_invoice' => array(
            'email_new_invoice_header' => array(
                'id'   => 'email_new_invoice_header',
                'name' => '<h3>' . __( 'New Invoice', 'invoicing' ) . '</h3>',
                'desc' => __( 'New invoice emails are sent to admin when a new invoice is received.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_new_invoice_active' => array(
                'id'   => 'email_new_invoice_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_new_invoice_subject' => array(
                'id'   => 'email_new_invoice_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] New payment invoice ({invoice_number}) - {invoice_date}', 'invoicing' ),
                'size' => 'large'
            ),
            'email_new_invoice_heading' => array(
                'id'   => 'email_new_invoice_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'New payment invoice', 'invoicing' ),
                'size' => 'large'
            ),
            'email_new_invoice_body' => array(
                'id'   => 'email_new_invoice_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi Admin,</p><p>You have received payment invoice from {name}.</p>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
        ),
        'cancelled_invoice' => array(
            'email_cancelled_invoice_header' => array(
                'id'   => 'email_cancelled_invoice_header',
                'name' => '<h3>' . __( 'Cancelled Invoice', 'invoicing' ) . '</h3>',
                'desc' => __( 'Cancelled invoice emails are sent to admin when invoices have been marked cancelled.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_cancelled_invoice_active' => array(
                'id'   => 'email_cancelled_invoice_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_cancelled_invoice_subject' => array(
                'id'   => 'email_cancelled_invoice_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Cancelled invoice ({invoice_number})', 'invoicing' ),
                'size' => 'large'
            ),
            'email_cancelled_invoice_heading' => array(
                'id'   => 'email_cancelled_invoice_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'Cancelled invoice', 'invoicing' ),
                'size' => 'large'
            ),
            'email_cancelled_invoice_body' => array(
                'id'   => 'email_cancelled_invoice_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi Admin,</p><p>The invoice #{invoice_number} from {site_title} has been cancelled.</p>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
        ),
        'failed_invoice' => array(
            'email_failed_invoice_header' => array(
                'id'   => 'email_failed_invoice_header',
                'name' => '<h3>' . __( 'Failed Invoice', 'invoicing' ) . '</h3>',
                'desc' => __( 'Failed invoice emails are sent to admin when invoices have been marked failed (if they were previously processing or on-hold).', 'invoicing' ),
                'type' => 'header',
            ),
            'email_failed_invoice_active' => array(
                'id'   => 'email_failed_invoice_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_failed_invoice_subject' => array(
                'id'   => 'email_failed_invoice_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Failed invoice ({invoice_number})', 'invoicing' ),
                'size' => 'large'
            ),
            'email_failed_invoice_heading' => array(
                'id'   => 'email_failed_invoice_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'Failed invoice', 'invoicing' ),
                'size' => 'large'
            ),
            'email_failed_invoice_body' => array(
                'id'   => 'email_failed_invoice_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi Admin,</p><p>Payment for invoice #{invoice_number} from {site_title} has been failed.</p>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
        ),
        'onhold_invoice' => array(
            'email_onhold_invoice_header' => array(
                'id'   => 'email_onhold_invoice_header',
                'name' => '<h3>' . __( 'On Hold Invoice', 'invoicing' ) . '</h3>',
                'desc' => __( 'This is an invoice notification sent to users containing invoice details after an invoice is placed on-hold.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_onhold_invoice_active' => array(
                'id'   => 'email_onhold_invoice_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_onhold_invoice_subject' => array(
                'id'   => 'email_onhold_invoice_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Your invoice receipt from {invoice_date}', 'invoicing' ),
                'size' => 'large'
            ),
            'email_onhold_invoice_heading' => array(
                'id'   => 'email_onhold_invoice_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'Thank you for your invoice', 'invoicing' ),
                'size' => 'large'
            ),
            'email_onhold_invoice_admin_bcc' => array(
                'id'   => 'email_onhold_invoice_admin_bcc',
                'name' => __( 'Enable Admin BCC', 'invoicing' ),
                'desc' => __( 'Check if you want to send this notification email to site Admin.', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_onhold_invoice_body' => array(
                'id'   => 'email_onhold_invoice_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi {name},</p><p>Your invoice is on-hold until we confirm your payment has been received.</p>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
        ),
        'processing_invoice' => array(
            'email_processing_invoice_header' => array(
                'id'   => 'email_processing_invoice_header',
                'name' => '<h3>' . __( 'Processing Invoice', 'invoicing' ) . '</h3>',
                'desc' => __( 'This is an invoice notification sent to users containing invoice details after payment.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_processing_invoice_active' => array(
                'id'   => 'email_processing_invoice_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_processing_invoice_subject' => array(
                'id'   => 'email_processing_invoice_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Your invoice receipt from {invoice_date}', 'invoicing' ),
                'size' => 'large'
            ),
            'email_processing_invoice_heading' => array(
                'id'   => 'email_processing_invoice_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'Thank you for your invoice', 'invoicing' ),
                'size' => 'large'
            ),
            'email_processing_invoice_admin_bcc' => array(
                'id'   => 'email_processing_invoice_admin_bcc',
                'name' => __( 'Enable Admin BCC', 'invoicing' ),
                'desc' => __( 'Check if you want to send this notification email to site Admin.', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_processing_invoice_body' => array(
                'id'   => 'email_processing_invoice_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi {name},</p><p>Your invoice has been received at {site_title} and is now being processed.</p>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
        ),
        'completed_invoice' => array(
            'email_completed_invoice_header' => array(
                'id'   => 'email_completed_invoice_header',
                'name' => '<h3>' . __( 'Paid Invoice', 'invoicing' ) . '</h3>',
                'desc' => __( 'Invoice paid emails are sent to users when their invoices are marked paid and usually indicate that their payment has been done.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_completed_invoice_active' => array(
                'id'   => 'email_completed_invoice_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_completed_invoice_renewal_active' => array(
                'id'   => 'email_completed_invoice_renewal_active',
                'name' => __( 'Enable renewal notification', 'invoicing' ),
                'desc' => __( 'Enable renewal invoice email notification. This notification will be sent on renewal.', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 0
            ),
            'email_completed_invoice_subject' => array(
                'id'   => 'email_completed_invoice_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Your invoice from {invoice_date} has been paid', 'invoicing' ),
                'size' => 'large'
            ),
            'email_completed_invoice_heading' => array(
                'id'   => 'email_completed_invoice_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'Your invoice has been paid', 'invoicing' ),
                'size' => 'large'
            ),
            'email_completed_invoice_admin_bcc' => array(
                'id'   => 'email_completed_invoice_admin_bcc',
                'name' => __( 'Enable Admin BCC', 'invoicing' ),
                'desc' => __( 'Check if you want to send this notification email to site Admin.', 'invoicing' ),
                'type' => 'checkbox',
            ),
            'email_completed_invoice_body' => array(
                'id'   => 'email_completed_invoice_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi {name},</p><p>Your recent invoice on {site_title} has been paid.</p>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
            'std'  => 1
        ),
        'refunded_invoice' => array(
            'email_refunded_invoice_header' => array(
                'id'   => 'email_refunded_invoice_header',
                'name' => '<h3>' . __( 'Refunded Invoice', 'invoicing' ) . '</h3>',
                'desc' => __( 'Invoice refunded emails are sent to users when their invoices are marked refunded.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_refunded_invoice_active' => array(
                'id'   => 'email_refunded_invoice_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_refunded_invoice_subject' => array(
                'id'   => 'email_refunded_invoice_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Your invoice from {invoice_date} has been refunded', 'invoicing' ),
                'size' => 'large'
            ),
            'email_refunded_invoice_heading' => array(
                'id'   => 'email_refunded_invoice_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'Your invoice has been refunded', 'invoicing' ),
                'size' => 'large'
            ),
            'email_refunded_invoice_admin_bcc' => array(
                'id'   => 'email_refunded_invoice_admin_bcc',
                'name' => __( 'Enable Admin BCC', 'invoicing' ),
                'desc' => __( 'Check if you want to send this notification email to site Admin.', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_refunded_invoice_body' => array(
                'id'   => 'email_refunded_invoice_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi {name},</p><p>Your invoice on {site_title} has been refunded.</p>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
        ),
        'user_invoice' => array(
            'email_user_invoice_header' => array(
                'id'   => 'email_user_invoice_header',
                'name' => '<h3>' . __( 'Customer Invoice', 'invoicing' ) . '</h3>',
                'desc' => __( 'Customer invoice emails can be sent to customers containing their invoice information and payment links.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_user_invoice_active' => array(
                'id'   => 'email_user_invoice_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_user_invoice_subject' => array(
                'id'   => 'email_user_invoice_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Your invoice from {invoice_date}', 'invoicing' ),
                'size' => 'large'
            ),
            'email_user_invoice_heading' => array(
                'id'   => 'email_user_invoice_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'Your invoice {invoice_number} details', 'invoicing' ),
                'size' => 'large'
            ),
            'email_user_invoice_admin_bcc' => array(
                'id'   => 'email_user_invoice_admin_bcc',
                'name' => __( 'Enable Admin BCC', 'invoicing' ),
                'desc' => __( 'Check if you want to send this notification email to site Admin.', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_user_invoice_body' => array(
                'id'   => 'email_user_invoice_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi {name},</p><p>An invoice has been created for you on {site_title}. To view / pay for this invoice please use the following link: <a class="btn btn-success" href="{invoice_link}">View / Pay</a></p>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
        ),
        'user_note' => array(
            'email_user_note_header' => array(
                'id'   => 'email_user_note_header',
                'name' => '<h3>' . __( 'Customer Note', 'invoicing' ) . '</h3>',
                'desc' => __( 'Customer note emails are sent when you add a note to an invoice/quote.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_user_note_active' => array(
                'id'   => 'email_user_note_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_user_note_subject' => array(
                'id'   => 'email_user_note_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Note added to your {invoice_label} #{invoice_number} from {invoice_date}', 'invoicing' ),
                'size' => 'large'
            ),
            'email_user_note_heading' => array(
                'id'   => 'email_user_note_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'A note has been added to your {invoice_label}', 'invoicing' ),
                'size' => 'large'
            ),
            'email_user_note_body' => array(
                'id'   => 'email_user_note_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email (wildcards and HTML are allowed).', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi {name},</p><p>Following note has been added to your {invoice_label}:</p><blockquote class="wpinv-note">{customer_note}</blockquote>', 'invoicing' ),
                'class' => 'large',
                'size' => '10'
            ),
        ),
        'overdue' => array(
            'email_overdue_header' => array(
                'id'   => 'email_overdue_header',
                'name' => '<h3>' . __( 'Payment Reminder', 'invoicing' ) . '</h3>',
                'desc' => __( 'Payment reminder emails are sent to user automatically.', 'invoicing' ),
                'type' => 'header',
            ),
            'email_overdue_active' => array(
                'id'   => 'email_overdue_active',
                'name' => __( 'Enable/Disable', 'invoicing' ),
                'desc' => __( 'Enable this email notification', 'invoicing' ),
                'type' => 'checkbox',
                'std'  => 1
            ),
            'email_due_reminder_days' => array(
                'id'        => 'email_due_reminder_days',
                'name'      => __( 'When to Send', 'invoicing' ),
                'desc'      => __( 'Check when you would like payment reminders sent out.', 'invoicing' ),
                'default'   => '',
                'type'      => 'multicheck',
                'options'   => $overdue_days_options,
            ),
            'email_overdue_subject' => array(
                'id'   => 'email_overdue_subject',
                'name' => __( 'Subject', 'invoicing' ),
                'desc' => __( 'Enter the subject line for the invoice receipt email.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( '[{site_title}] Payment Reminder', 'invoicing' ),
                'size' => 'large'
            ),
            'email_overdue_heading' => array(
                'id'   => 'email_overdue_heading',
                'name' => __( 'Email Heading', 'invoicing' ),
                'desc' => __( 'Enter the main heading contained within the email notification.', 'invoicing' ),
                'type' => 'text',
                'std'  => __( 'Payment reminder for your invoice', 'invoicing' ),
                'size' => 'large'
            ),
            'email_overdue_body' => array(
                'id'   => 'email_overdue_body',
                'name' => __( 'Email Content', 'invoicing' ),
                'desc' => __( 'The content of the email.', 'invoicing' ),
                'type' => 'rich_editor',
                'std'  => __( '<p>Hi {full_name},</p><p>This is just a friendly reminder that your invoice <a href="{invoice_link}">#{invoice_number}</a> {is_was} due on {invoice_due_date}.</p><p>The total of this invoice is {invoice_total}</p><p>To view / pay now for this invoice please use the following link: <a class="btn btn-success" href="{invoice_link}">View / Pay</a></p>', 'invoicing' ),
                'class' => 'large',
                'size'  => 10,
            ),
        ),
    );

    return apply_filters( 'wpinv_get_emails', $emails );
}

function wpinv_settings_emails( $settings = array() ) {
    $emails = wpinv_get_emails();

    if ( !empty( $emails ) ) {
        foreach ( $emails as $key => $email ) {
            $settings[$key] = $email;
        }
    }

    return apply_filters( 'wpinv_settings_get_emails', $settings );
}
add_filter( 'wpinv_settings_emails', 'wpinv_settings_emails', 10, 1 );

function wpinv_settings_sections_emails( $settings ) {
    $emails = wpinv_get_emails();

    if (!empty($emails)) {
        foreach  ($emails as $key => $email) {
            $settings[$key] = !empty( $email['email_' . $key . '_header']['name'] ) ? strip_tags( $email['email_' . $key . '_header']['name'] ) : $key;
        }
    }

    return $settings;    
}
add_filter( 'wpinv_settings_sections_emails', 'wpinv_settings_sections_emails', 10, 1 );

function wpinv_email_is_enabled( $email_type ) {
    $emails = wpinv_get_emails();
    $enabled = isset( $emails[$email_type] ) && wpinv_get_option( 'email_'. $email_type . '_active', 0 ) ? true : false;

    return apply_filters( 'wpinv_email_is_enabled', $enabled, $email_type );
}

function wpinv_email_get_recipient( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    switch ( $email_type ) {
        case 'new_invoice':
        case 'cancelled_invoice':
        case 'failed_invoice':
            $recipient  = wpinv_get_admin_email();
        break;
        default:
            $invoice    = !empty( $invoice ) && is_object( $invoice ) ? $invoice : ( $invoice_id > 0 ? wpinv_get_invoice( $invoice_id ) : NULL );
            $recipient  = !empty( $invoice ) ? $invoice->get_email() : '';
        break;
    }

    return apply_filters( 'wpinv_email_recipient', $recipient, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_subject( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $subject    = wpinv_get_option( 'email_' . $email_type . '_subject' );
    $subject    = __( $subject, 'invoicing' );

    $subject    = wpinv_email_format_text( $subject, $invoice );

    return apply_filters( 'wpinv_email_subject', $subject, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_heading( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $email_heading = wpinv_get_option( 'email_' . $email_type . '_heading' );
    $email_heading = __( $email_heading, 'invoicing' );

    $email_heading = wpinv_email_format_text( $email_heading, $invoice );

    return apply_filters( 'wpinv_email_heading', $email_heading, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_content( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $content    = wpinv_get_option( 'email_' . $email_type . '_body' );
    $content    = __( $content, 'invoicing' );

    $content    = wpinv_email_format_text( $content, $invoice );

    return apply_filters( 'wpinv_email_content', $content, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_headers( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $from_name = wpinv_mail_get_from_address();
    $from_email = wpinv_mail_get_from_address();
    
    $invoice    = !empty( $invoice ) && is_object( $invoice ) ? $invoice : ( $invoice_id > 0 ? wpinv_get_invoice( $invoice_id ) : NULL );
    
    $headers    = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
    $headers    .= "Reply-To: ". $from_email . "\r\n";
    $headers    .= "Content-Type: " . wpinv_mail_get_content_type() . "\r\n";
    
    return apply_filters( 'wpinv_email_headers', $headers, $email_type, $invoice_id, $invoice );
}

function wpinv_email_get_attachments( $email_type = '', $invoice_id = 0, $invoice = array() ) {
    $attachments = array();
    
    return apply_filters( 'wpinv_email_attachments', $attachments, $email_type, $invoice_id, $invoice );
}

function wpinv_email_format_text( $content, $invoice ) {
    $replace_array = array(
        '{site_title}'      => wpinv_get_blogname(),
        '{date}'            => date_i18n( get_option( 'date_format' ), (int) current_time( 'timestamp' ) ),
    );
    
    if ( !empty( $invoice->ID ) ) {
        $replace_array = array_merge(
            $replace_array, 
            array(
                '{name}'            => $invoice->get_user_full_name(),
                '{full_name}'       => $invoice->get_user_full_name(),
                '{first_name}'      => $invoice->get_first_name(),
                '{last_name}'       => $invoice->get_last_name(),
                '{email}'           => $invoice->get_email(),
                '{invoice_number}'  => $invoice->get_number(),
                '{invoice_total}'   => $invoice->get_total( true ),
                '{invoice_link}'    => $invoice->get_view_url( true ),
                '{invoice_pay_link}'=> $invoice->get_view_url( true ),
                '{invoice_date}'    => $invoice->get_invoice_date( true ),
                '{invoice_due_date}'=> $invoice->get_due_date( true ),
                '{invoice_quote}'   => $invoice->get_invoice_quote_type( $invoice->ID ),
                '{invoice_label}'   => $invoice->get_invoice_quote_type( $invoice->ID ),
                '{is_was}'          => strtotime( $invoice->get_due_date() ) < strtotime( date_i18n( 'Y-m-d' ) ) ? __( 'was', 'invoicing' ) : __( 'is', 'invoicing' ),
            )
        );
    }

    $replace_array = apply_filters( 'wpinv_email_format_text', $replace_array, $content, $invoice );

    foreach ( $replace_array as $key => $value ) {
        $content = str_replace( $key, $value, $content );
    }

    return apply_filters( 'wpinv_email_content_replace', $content );
}

function wpinv_email_style_body( $content ) {
    // make sure we only inline CSS for html emails
    if ( in_array( wpinv_mail_get_content_type(), array( 'text/html', 'multipart/alternative' ) ) && class_exists( 'DOMDocument' ) ) {
        ob_start();
        wpinv_get_template( 'emails/wpinv-email-styles.php' );
        $css = apply_filters( 'wpinv_email_styles', ob_get_clean() );

        // apply CSS styles inline for picky email clients
        try {
            $emogrifier = new Emogrifier( $content, $css );
            $content    = $emogrifier->emogrify();
        } catch ( Exception $e ) {
            wpinv_error_log( $e->getMessage(), 'emogrifier' );
        }
    }
    return $content;
}

function wpinv_email_header( $email_heading = '', $invoice = array(), $email_type = '', $sent_to_admin = false ) {
    wpinv_get_template( 'emails/wpinv-email-header.php', array( 'email_heading' => $email_heading, 'invoice' => $invoice, 'email_type' => $email_type, 'sent_to_admin' => $sent_to_admin ) );
}

/**
 * Get the email footer.
 */
function wpinv_email_footer( $invoice = array(), $email_type = '', $sent_to_admin = false ) {
    wpinv_get_template( 'emails/wpinv-email-footer.php', array( 'invoice' => $invoice, 'email_type' => $email_type, 'sent_to_admin' => $sent_to_admin ) );
}

function wpinv_email_wrap_message( $message ) {
    // Buffer
    ob_start();

    do_action( 'wpinv_email_header' );

    echo wpautop( wptexturize( $message ) );

    do_action( 'wpinv_email_footer' );

    // Get contents
    $message = ob_get_clean();

    return $message;
}

function wpinv_email_invoice_details( $invoice, $email_type = '', $sent_to_admin = false ) {
    wpinv_get_template( 'emails/wpinv-email-invoice-details.php', array( 'invoice' => $invoice, 'email_type' => $email_type, 'sent_to_admin' => $sent_to_admin ) );
}

function wpinv_email_invoice_items( $invoice, $email_type = '', $sent_to_admin = false ) {
    wpinv_get_template( 'emails/wpinv-email-invoice-items.php', array( 'invoice' => $invoice, 'email_type' => $email_type, 'sent_to_admin' => $sent_to_admin ) );
}

function wpinv_email_billing_details( $invoice, $email_type = '', $sent_to_admin = false ) {
    wpinv_get_template( 'emails/wpinv-email-billing-details.php', array( 'invoice' => $invoice, 'email_type' => $email_type, 'sent_to_admin' => $sent_to_admin ) );
}

function wpinv_send_customer_invoice( $data = array() ) {
    $invoice_id = !empty( $data['invoice_id'] ) ? absint( $data['invoice_id'] ) : NULL;

    if ( empty( $invoice_id ) ) {
        return;
    }

    if ( !wpinv_current_user_can_manage_invoicing() ) {
        wp_die( __( 'You do not have permission to send invoice notification', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }
    
    $sent = wpinv_user_invoice_notification( $invoice_id );

    if ( -1 === $sent ) {
        $status = 'email_disabled';
    } elseif ( $sent ) {
        $status = 'email_sent';
    } else {
        $status = 'email_fail';
    }

    $redirect = add_query_arg( array( 'wpinv-message' => $status, 'wpi_action' => false, 'invoice_id' => false ) );
    wp_redirect( $redirect );
    exit;
}
add_action( 'wpinv_send_invoice', 'wpinv_send_customer_invoice' );

function wpinv_send_overdue_reminder( $data = array() ) {
    $invoice_id = !empty( $data['invoice_id'] ) ? absint( $data['invoice_id'] ) : NULL;

    if ( empty( $invoice_id ) ) {
        return;
    }

    if ( !wpinv_current_user_can_manage_invoicing() ) {
        wp_die( __( 'You do not have permission to send reminder notification', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

    $sent = wpinv_send_payment_reminder_notification( $invoice_id );
    
    $status = $sent ? 'email_sent' : 'email_fail';

    $redirect = add_query_arg( array( 'wpinv-message' => $status, 'wpi_action' => false, 'invoice_id' => false ) );
    wp_redirect( $redirect );
    exit;
}
add_action( 'wpinv_send_reminder', 'wpinv_send_overdue_reminder' );

function wpinv_send_customer_note_email( $data ) {
    $invoice_id = !empty( $data['invoice_id'] ) ? absint( $data['invoice_id'] ) : NULL;

    if ( empty( $invoice_id ) ) {
        return;
    }

    $sent = wpinv_user_note_notification( $invoice_id, $data );
}
add_action( 'wpinv_new_customer_note', 'wpinv_send_customer_note_email', 10, 1 );

function wpinv_add_notes_to_invoice_email( $invoice, $email_type, $sent_to_admin ) {
    if ( !empty( $invoice ) && $email_type == 'user_invoice' && $invoice_notes = wpinv_get_invoice_notes( $invoice->ID, true ) ) {
        $date_format = get_option( 'date_format' );
        $time_format = get_option( 'time_format' );
        ?>
        <div id="wpinv-email-notes">
            <h3 class="wpinv-notes-t"><?php echo apply_filters( 'wpinv_email_invoice_notes_title', __( 'Invoice Notes', 'invoicing' ) ); ?></h3>
            <ol class="wpinv-notes-lists">
        <?php
        foreach ( $invoice_notes as $note ) {
            $note_time = strtotime( $note->comment_date );
            ?>
            <li class="comment wpinv-note">
            <p class="wpinv-note-date meta"><?php printf( __( '%2$s at %3$s', 'invoicing' ), $note->comment_author, date_i18n( $date_format, $note_time ), date_i18n( $time_format, $note_time ), $note_time ); ?></p>
            <div class="wpinv-note-desc description"><?php echo wpautop( wptexturize( $note->comment_content ) ); ?></div>
            </li>
            <?php
        }
        ?>  </ol>
        </div>
        <?php
    }
}
add_action( 'wpinv_email_billing_details', 'wpinv_add_notes_to_invoice_email', 10, 3 );

function wpinv_email_payment_reminders() {
    global $wpi_auto_reminder;
    if ( !wpinv_get_option( 'email_overdue_active' ) ) {
        return;
    }

    if ( $reminder_days = wpinv_get_option( 'email_due_reminder_days' ) ) {
        $reminder_days  = is_array( $reminder_days ) ? array_values( $reminder_days ) : '';

        if ( empty( $reminder_days ) ) {
            return;
        }
        $reminder_days  = array_unique( array_map( 'absint', $reminder_days ) );

        $args = array(
            'post_type'     => 'wpi_invoice',
            'post_status'   => 'wpi-pending',
            'fields'        => 'ids',
            'numberposts'   => '-1',
            'meta_query'    => array(
                array(
                    'key'       =>  '_wpinv_due_date',
                    'value'     =>  array( '', 'none' ),
                    'compare'   =>  'NOT IN',
                )
            ),
            'meta_key'      => '_wpinv_due_date',
            'orderby'       => 'meta_value',
            'order'         => 'ASC',
        );

        $invoices = get_posts( $args );

        if ( empty( $invoices ) ) {
            return;
        }

        $date_to_send   = array();

        foreach ( $invoices as $id ) {
            $due_date = get_post_meta( $id, '_wpinv_due_date', true );

            foreach ( $reminder_days as $key => $days ) {
                if ( $days !== '' ) {
                    $date_to_send[$id][] = date_i18n( 'Y-m-d', strtotime( $due_date ) + ( $days * DAY_IN_SECONDS ) );
                }
            }
        }

        $today              = date_i18n( 'Y-m-d' );
        $wpi_auto_reminder  = true;

        foreach ( $date_to_send as $id => $values ) {
            if ( in_array( $today, $values ) ) {
                $sent = get_post_meta( $id, '_wpinv_reminder_sent', true );

                if ( isset( $sent ) && !empty( $sent ) ) {
                    if ( !in_array( $today, $sent ) ) {
                        do_action( 'wpinv_send_payment_reminder_notification', $id );
                    }
                } else {
                    do_action( 'wpinv_send_payment_reminder_notification', $id );
                }
            }
        }

        $wpi_auto_reminder  = false;
    }
}

function wpinv_send_payment_reminder_notification( $invoice_id ) {
    $email_type = 'overdue';
    if ( !wpinv_email_is_enabled( $email_type ) ) {
        return false;
    }

    $invoice    = wpinv_get_invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    if ( !$invoice->needs_payment() ) {
        return false;
    }

    $recipient  = wpinv_email_get_recipient( $email_type, $invoice_id, $invoice );
    if ( !is_email( $recipient ) ) {
        return false;
    }

    do_action( 'wpinv_pre_send_invoice_notification', $invoice, $email_type );

    $subject        = wpinv_email_get_subject( $email_type, $invoice_id, $invoice );
    $email_heading  = wpinv_email_get_heading( $email_type, $invoice_id, $invoice );
    $headers        = wpinv_email_get_headers( $email_type, $invoice_id, $invoice );
    $message_body   = wpinv_email_get_content( $email_type, $invoice_id, $invoice );
    $attachments    = wpinv_email_get_attachments( $email_type, $invoice_id, $invoice );

    $content        = wpinv_get_template_html( 'emails/wpinv-email-' . $email_type . '.php', array(
            'invoice'       => $invoice,
            'email_type'    => $email_type,
            'email_heading' => $email_heading,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'message_body'  => $message_body
        ) );

    $content        = wpinv_email_format_text( $content, $invoice );

    $sent = wpinv_mail_send( $recipient, $subject, $content, $headers, $attachments );
    if ( $sent ) {
        do_action( 'wpinv_payment_reminder_sent', $invoice_id, $invoice );
    }

    do_action( 'wpinv_post_send_invoice_notification', $invoice, $email_type );

    return $sent;
}
add_action( 'wpinv_send_payment_reminder_notification', 'wpinv_send_payment_reminder_notification', 10, 1 );

function wpinv_payment_reminder_sent( $invoice_id, $invoice ) {
    global $wpi_auto_reminder;

    $sent = get_post_meta( $invoice_id, '_wpinv_reminder_sent', true );

    if ( empty( $sent ) ) {
        $sent = array();
    }
    $sent[] = date_i18n( 'Y-m-d' );

    update_post_meta( $invoice_id, '_wpinv_reminder_sent', $sent );

    if ( $wpi_auto_reminder ) { // Auto reminder note.
        $note = __( 'Automated reminder sent to the user.', 'invoicing' );
        $invoice->add_note( $note, false, false, true );
    } else { // Menual reminder note.
        $note = __( 'Manual reminder sent to the user.', 'invoicing' );
        $invoice->add_note( $note );
    }
}
add_action( 'wpinv_payment_reminder_sent', 'wpinv_payment_reminder_sent', 10, 2 );

function wpinv_invoice_notification_set_locale( $invoice, $email_type, $site = false ) {
    if ( empty( $invoice ) ) {
        return;
    }

    if ( is_int( $invoice ) ) {
        $invoice = wpinv_get_invoice( $invoice );
    }

    if ( ! empty( $invoice ) && is_object( $invoice ) ) {
        if ( ! $site && function_exists( 'get_user_locale' ) ) {
            $locale = get_user_locale( $invoice->get_user_id() );
        } else {
            $locale = get_locale();
        }

        wpinv_switch_to_locale( $locale );
    }
}
add_action( 'wpinv_pre_send_invoice_notification', 'wpinv_invoice_notification_set_locale', 10, 3 );

function wpinv_invoice_notification_restore_locale( $invoice, $email_type, $site = false ) {
    if ( empty( $invoice ) ) {
        return;
    }

    wpinv_restore_locale();
}
add_action( 'wpinv_post_send_invoice_notification', 'wpinv_invoice_notification_restore_locale', 10, 3 );
