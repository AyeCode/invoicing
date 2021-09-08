<?php
/**
 * Displays item totals in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/items.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $form->get_items() ) ) {
    return;
}

if ( ! empty( $GLOBALS['getpaid_force_checkbox'] ) ) {
    $items_type = 'checkbox';
}

if ( empty( $items_type ) ) {
    $items_type = 'total';
}

switch( $items_type ) {
    case 'radio':
        wpinv_get_template( 'payment-forms/variations/radio.php', compact( 'form', 'items_type' ) );
        break;
    case 'checkbox':
        wpinv_get_template( 'payment-forms/variations/checkbox.php', compact( 'form', 'items_type' ) );
        break;
    case 'select':
        wpinv_get_template( 'payment-forms/variations/select.php', compact( 'form', 'items_type' ) );
        break;
}

// Display the cart totals.
if ( ! empty( $hide_cart ) ) {
    echo '<div class="d-none">';
}

// Display the cart totals.
wpinv_get_template( 'payment-forms/cart.php', compact( 'form', 'items_type' ) );

if ( ! empty( $hide_cart ) ) {
    echo '</div>';
}
