<?php
/**
 * Displays a paragraph in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/paragraph.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$text = isset( $element['text'] ) ? wp_kses_post( trim( $element['text'] ) ) : '';

if ( ! empty( $text ) ) {
    echo "<p>$text</p>";
}
