<?php
/**
 * Displays a heading in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/heading.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$heading_tag  = isset( $element['level'] ) ? trim( sanitize_key( $element['level'] ) ) : 'h3';
$text = isset( $element['text'] ) ? trim( $element['text'] ) : '';

if ( ! empty( $text ) ) {
    echo wp_kses_post( "<$heading_tag>$text</$heading_tag>" );
}
