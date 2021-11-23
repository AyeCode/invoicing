<?php
/**
 * Displays a date input in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/date.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$label       = empty( $label ) ? '' : wp_kses_post( $label );
$label_class = sanitize_key( preg_replace( '/[^A-Za-z0-9_-]/', '-', $label ) );
if ( ! empty( $required ) ) {
    $label .= "<span class='text-danger'> *</span>";
}

$options = array(
    'data-default-date' => empty( 'default_date' ) ? false : $default_date,
    'data-min-date'     => empty( 'min_date' ) ? false : $min_date,
    'data-max-date'     => empty( 'max_date' ) ? false : $max_date,
    'data-mode'         => empty( 'mode' ) ? 'single' : $mode,
    'data-alt-format'   => get_option( 'date_format', 'F j, Y' ),
    'data-date-format'  => 'Y-m-d',
    'data-alt-input'    => 'true',
);

echo aui()->input(
    array(
        'name'             => esc_attr( $id ),
        'id'               => esc_attr( $id ) . uniqid( '_' ),
        'placeholder'      => empty( $placeholder ) ? '' : esc_attr( $placeholder ),
        'required'         => ! empty( $required ),
        'label'            => $label,
        'label_type'       => 'vertical',
        'help_text'        => empty( $description ) ? '' : wp_kses_post( $description ),
        'type'             => 'datepicker',
        'class'            => $label_class,
        'extra_attributes' => array_filter( apply_filters( 'getpaid_date_field_attributes', $options ) ),
    )
);
