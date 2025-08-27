<?php
/**
 * Displays a select in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/select.php.
 *
 * @version 2.8.32
 */

defined( 'ABSPATH' ) || exit;

$label = empty( $label ) ? '' : wp_kses_post( $label );
$label_class = sanitize_key( preg_replace( '/[^A-Za-z0-9_-]/', '-', $label ) );

if ( ! empty( $required ) ) {
    $label .= "<span class='text-danger'> *</span>";
}

aui()->select(
    array(
        'name'        => esc_attr( $id ),
        'id'          => esc_attr( $element_id ),
        'placeholder' => empty( $placeholder ) ? '' : esc_attr( $placeholder ),
        'required'    => ! empty( $required ),
        'label'       => $label,
        'label_type'  => 'vertical',
        'help_text'   => empty( $description ) ? '' : wp_kses_post( $description ),
        'options'     => getpaid_parse_field_options( $options ),
        'class'       => $label_class,
        'value'       => $query_value,
    ),
    true
);
