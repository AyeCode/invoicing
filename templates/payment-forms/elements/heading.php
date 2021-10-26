<?php
/**
 * Displays a heading in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/heading.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$tag  = isset( $element['level'] ) ? trim( $element['level'] ) : 'h3';
$text = isset( $element['text'] ) ? wp_kses_post( trim( $element['text'] ) ) : '';

if ( ! empty( $text ) ) {
    echo "<$tag>$text</$tag>";
}
