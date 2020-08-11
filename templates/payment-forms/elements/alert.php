<?php
/**
 * Displays an alert in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/alert.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $text ) ) {
    return;
}

echo aui()->alert(
    array(
        'content'     => wp_kses_post( $text ),
        'dismissible' => ! empty( $dismissible ),
        'type'        => empty( $class ) ? 'info' : str_replace( 'alert-', '', $class ),
    )
);
