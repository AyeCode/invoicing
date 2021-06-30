<?php
/**
 * Displays a checkbox in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/checkbox.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$label       = empty( $label ) ? '' : wp_kses_post( $label );
$label_class = sanitize_key( preg_replace( '/[^A-Za-z0-9_-]/', '-', $label ) );

if ( ! empty( $required ) ) {
    $label .= "<span class='text-danger'> *</span>";
}

echo aui()->input(
    array(
        'type'       => 'checkbox',
        'name'       => esc_attr( $id ),
        'id'         => esc_attr( $id ) . uniqid( '_' ),
        'required'   => ! empty( $required ),
        'label'      => $label,
        'value'      => esc_attr__( 'Yes', 'invoicing' ),
        'help_text'  => empty( $description ) ? '' : wp_kses_post( $description ),
        'class'      => 'w-auto ' . $label_class,
    )
);
