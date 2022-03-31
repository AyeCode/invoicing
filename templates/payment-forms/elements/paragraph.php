<?php
/**
 * Displays a paragraph in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/paragraph.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( ! empty( $element['text'] ) ) {
    echo '<p>' . wp_kses_post( trim( $element['text'] ) ) . '</p>';
}
