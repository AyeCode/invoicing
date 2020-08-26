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

    // Ensure that we have an invoice key.
    if ( empty( $_GET['invoice_key'] ) ) {
        return 0;
    }

    // Retrieve an invoice using the key.
    $invoice = new WPInv_Invoice( $_GET['invoice_key'] );

    // Compare the invoice key and the parsed key.
    if ( $invoice->get_id() != 0 && $invoice->get_key() == $_GET['invoice_key'] ) {
        return $invoice->get_id();
    }

    return 0;
}

/**
 * @deprecated
 */
function wpinv_get_invoice_cart() {
    return wpinv_get_invoice( wpinv_get_invoice_cart_id() );
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
    $discount = $invoice->get_total();

    if ( $currency ) {
        return wpinv_price( wpinv_format_amount( $discount ), $invoice->get_currency() );
    }

    return $discount;
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
function wpinv_set_checkout_session( $invoice_data = array() ) {
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
