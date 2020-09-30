<?php
/**
 * Contains all deprecated functions.
 *
 * @since 1.0.19
 * @package Invoicing
 */

defined( 'ABSPATH' ) || exit;

/**
 * @deprecated
 */
function wpinv_get_invoice_cart_id() {
    return getpaid_get_current_invoice_id();
}

/**
 * @deprecated
 */
function wpinv_get_invoice_cart() {
    return wpinv_get_invoice( getpaid_get_current_invoice_id() );
}

/**
 * @deprecated
 */
function wpinv_get_invoice_description( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_description();
}

/**
 * @deprecated
 */
function wpinv_get_invoice_currency_code( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_currency();
}

/**
 * @deprecated
 */
function wpinv_get_payment_user_email( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_email();
}

/**
 * @deprecated
 */
function wpinv_get_user_id( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_user_id();
}

/**
 * @deprecated
 */
function wpinv_get_invoice_status( $invoice, $return_label = false ) {
    $invoice = new WPInv_Invoice( $invoice );
    
    if ( $return_label ) {
        return $invoice->get_status_nicename();
    }

    return $invoice->get_status();
}

/**
 * @deprecated
 */
function wpinv_get_payment_gateway( $invoice, $return_label = false ) {
    $invoice = new WPInv_Invoice( $invoice );

    if ( $return_label ) {
        return $invoice->get_gateway_title();
    }

    return $invoice->get_gateway();
}

/**
 * @deprecated
 */
function wpinv_get_payment_gateway_name( $invoice ) {
    return wpinv_get_payment_gateway( $invoice, true );
}

/**
 * @deprecated
 */
function wpinv_get_payment_transaction_id( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_transaction_id();
}

/**
 * @deprecated
 */
function wpinv_get_invoice_meta( $invoice_id = 0, $meta_key = '_wpinv_payment_meta', $single = true ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_meta( $meta_key, $single );
}

/**
 * @deprecated
 */
function wpinv_update_invoice_meta( $invoice_id = 0, $meta_key = '', $meta_value = '' ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->update_meta_data( $meta_key, $meta_value );
}

/**
 * @deprecated
 */
function wpinv_get_items( $invoice = 0 ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_items();
}

/**
 * @deprecated
 */
function wpinv_get_fees( $invoice = 0 ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_fees();
}

/**
 * @deprecated
 */
function wpinv_get_invoice_ip( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_ip();
}

/**
 * @deprecated
 */
function wpinv_get_invoice_user_info( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_user_info();
}

/**
 * @deprecated
 */
function wpinv_subtotal( $invoice = 0, $currency = false ) {
    $invoice  = new WPInv_Invoice( $invoice );
    $subtotal = $invoice->get_subtotal();

    if ( $currency ) {
        return wpinv_price( wpinv_format_amount( $subtotal ), $invoice->get_currency() );
    }

    return $subtotal;
}

/**
 * @deprecated
 */
function wpinv_tax( $invoice = 0, $currency = false ) {
    $invoice  = new WPInv_Invoice( $invoice );
    $tax      = $invoice->get_total_tax();

    if ( $currency ) {
        return wpinv_price( wpinv_format_amount( $tax ), $invoice->get_currency() );
    }

    return $tax;
}

/**
 * @deprecated
 */
function wpinv_discount( $invoice = 0, $currency = false, $deprecated ) {
    $invoice  = new WPInv_Invoice( $invoice );
    $discount = $invoice->get_total_discount();

    if ( $currency ) {
        return wpinv_price( wpinv_format_amount( $discount ), $invoice->get_currency() );
    }

    return $discount;
}

/**
 * @deprecated
 */
function wpinv_discount_code( $invoice = 0 ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_discount_code();
}

/**
 * @deprecated
 */
function wpinv_payment_total( $invoice = 0, $currency = false ) {
    $invoice  = new WPInv_Invoice( $invoice );
    $total = $invoice->get_total();

    if ( $currency ) {
        return wpinv_price( wpinv_format_amount( $total ), $invoice->get_currency() );
    }

    return $total;
}

/**
 * @deprecated
 */
function wpinv_get_date_created( $invoice = 0, $format = '' ) {
    $invoice = new WPInv_Invoice( $invoice );

    $format         = ! empty( $format ) ? $format : get_option( 'date_format' );
    $date_created   = $invoice->get_created_date();

    return empty( $date_created ) ? date_i18n( $format, strtotime( $date_created ) ) : '';
}

/**
 * @deprecated
 */
function wpinv_get_invoice_date( $invoice = 0, $format = '' ) {
    wpinv_get_date_created( $invoice, $format );
}

/**
 * @deprecated
 */
function wpinv_get_invoice_vat_number( $invoice = 0 ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_vat_number();
}

/**
 * @deprecated
 */
function wpinv_insert_payment_note( $invoice = 0, $note = '', $user_type = false, $added_by_user = false, $system = false ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->add_note( $note, $user_type, $added_by_user, $system );
}

/**
 * @deprecated
 */
function wpinv_get_payment_key( $invoice = 0 ) {
	$invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_key();
}

/**
 * @deprecated
 */
function wpinv_get_invoice_number( $invoice = 0 ) {
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->get_number();
}

/**
 * @deprecated
 */
function wpinv_get_cart_discountable_subtotal() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_get_cart_items_subtotal() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_get_cart_subtotal() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_cart_subtotal() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_get_cart_total() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_cart_total() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_get_cart_tax() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_cart_tax() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_get_cart_discount_code() {
    return '';
}

/**
 * @deprecated
 */
function wpinv_cart_discount_code() {
    return '';
}

/**
 * @deprecated
 */
function wpinv_get_cart_discount() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_cart_discount() {
    return '';
}

/**
 * @deprecated
 */
function wpinv_get_cart_fees() {
    return array();
}

/**
 * @deprecated
 */
function wpinv_get_cart_fee_total() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_get_cart_fee_tax() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_cart_has_recurring_item() {
    return false;
}

/**
 * @deprecated
 */
function wpinv_cart_has_free_trial() {
    return false;
}

/**
 * @deprecated
 */
function wpinv_get_cart_contents() {
    return array();
}

/**
 * @deprecated
 */
function wpinv_get_cart_content_details() {
    return array();
}

/**
 * @deprecated
 */
function wpinv_get_cart_details() {
    return array();
}

/**
 * @deprecated
 */
function wpinv_update_payment_status( $invoice, $new_status = 'publish' ) {    
    $invoice = new WPInv_Invoice( $invoice );
    return $invoice->update_status( $new_status );
}

/**
 * @deprecated
 */
function wpinv_cart_has_fees() {
    return false;
}

/**
 * @deprecated
 */
function wpinv_set_checkout_session() {
    return false;
}

/**
 * @deprecated
 */
function wpinv_get_checkout_session() {
	return false;
}

/**
 * @deprecated
 */
function wpinv_empty_cart() {
    return false;
}

/**
 * @deprecated
 */
function wpinv_display_invoice_totals() {}

/**
 * @deprecated
 */
function wpinv_checkout_billing_details() {
    return array();
}

/**
 * @deprecated
 */
function wpinv_register_post_types() {
    GetPaid_Post_Types::register_post_types();
}

/**
 * @deprecated
 */
function wpinv_set_payment_transaction_id( $invoice_id = 0, $transaction_id = '' ) {

    // Fetch the invoice.
    $invoice = new WPInv_Invoice( $invoice_id );

    if ( 0 ==  $invoice->get_id() ) {
        return false;
    }

    // Prepare the transaction id.
    if ( empty( $transaction_id ) ) {
        $transaction_id = $invoice_id;
    }

    // Set the transaction id;
    $invoice->set_transaction_id( apply_filters( 'wpinv_set_payment_transaction_id', $transaction_id, $invoice ) );

    // Save the invoice.
    return $invoice->save();
}

/**
 * @deprecated
 */
function wpinv_send_to_gateway( $gateway, $payment_data ) {
    $payment_data['gateway_nonce'] = wp_create_nonce('wpi-gateway');
    do_action( 'wpinv_gateway_' . $gateway, $payment_data );
}

/**
 * @deprecated
 */
function wpinv_die_handler() {
    die();
}

/**
 * @deprecated
 */
function wpinv_die( $message = '', $title = '', $status = 400 ) {
    add_filter( 'wp_die_ajax_handler', 'wpinv_die_handler', 10, 3 );
    add_filter( 'wp_die_handler', 'wpinv_die_handler', 10, 3 );
    wp_die( $message, $title, array( 'response' => $status ));
}

/**
 * @deprecated
 */
function wpinv_checkout_cart_columns() {
    return 4;
}

/**
 * @deprecated
 */
function wpinv_set_cart_discount() {
    return array();
}

/**
 * @deprecated
 */
function wpinv_unset_cart_discount() {
    return array();
}

/**
 * @deprecated
 */
function wpinv_cart_discounts_html() {}

/**
 * @deprecated
 */
function wpinv_get_cart_discounts_html() {}

/**
 * @deprecated
 */
function wpinv_display_cart_discount() {}

/**
 * @deprecated
 */
function wpinv_multiple_discounts_allowed() {
    return false;
}

/**
 * @deprecated
 */
function wpinv_get_cart_items_discount_amount() {
    return 0;
}

/**
 * @deprecated
 */
function wpinv_get_cart_item_discount_amount() {
    return 0;
}

/**
 * @deprecated.
 */
function wpinv_new_invoice_notification() {}

/**
 * @deprecated.
 */
function wpinv_cancelled_invoice_notification() {}

/**
 * @deprecated.
 */
function wpinv_failed_invoice_notification() {}

/**
 * @deprecated.
 */
function wpinv_onhold_invoice_notification() {}

/**
 * @deprecated.
 */
function wpinv_processing_invoice_notification() {}

/**
 * @deprecated.
 */
function wpinv_completed_invoice_notification() {}

/**
 * @deprecated.
 */
function wpinv_fully_refunded_notification() {}

/**
 * @deprecated.
 */
function wpinv_partially_refunded_notification() {}

/**
 * @deprecated
 */
function wpinv_new_invoice_note_notification() {}

/**
 * @deprecated
 */
function wpinv_user_invoice_notification() {}

/**
 * @deprecated
 */
function wpinv_user_note_notification() {}

/**
 * @deprecated
 */
function wpinv_invoice_status_label( $status, $status_display = '' ) {
    return empty( $status_display ) ? sanitize_text_field( $status ) : sanitize_text_field( $status_display );
}

/**
 * @deprecated
 */
function wpinv_clean_invoice_number( $number ) {
    return $number;
}

/**
 * @deprecated
 */
function wpinv_update_invoice_number() {}

/**
 * @deprecated
 */
function wpinv_invoice_subscription_details() {}

/**
 * @deprecated
 */
function wpinv_schedule_events() {}

/**
 * @deprecated
 */
function wpinv_email_payment_reminders() {}

/**
 * @deprecated
 */
function wpinv_send_overdue_reminder() {}

/**
 * @deprecated
 */
function wpinv_send_customer_note_email() {}

/**
 * @deprecated
 */
function wpinv_send_payment_reminder_notification() {}

/**
 * @deprecated
 */
function wpinv_payment_reminder_sent() {}

/**
 * @deprecated
 */
function wpinv_send_pre_payment_reminder_notification() {}

/**
 * @deprecated
 */
function wpinv_email_renewal_reminders() {}

function wpinv_send_customer_invoice() {}