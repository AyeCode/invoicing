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

if ( empty( $items_type ) ) {
    $items_type = 'total';
}

// Display the cart totals.
if ( ! empty( $hide_cart ) ) {
    echo '<div class="d-none">';
}

// Display the cart totals.
wpinv_get_template( 'payment-forms/cart.php', compact( 'form' ) );

if ( ! empty( $hide_cart ) ) {
    echo '</div>';
}
