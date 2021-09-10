<?php
/**
 * Displays a text input in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/text.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Set the currency position.
$position = wpinv_currency_position();

if ( $position == 'left_space' ) {
    $position = 'left';
}

if ( $position == 'right_space' ) {
    $position = 'right';
}
$label       = empty( $label ) ? '' : esc_html( $label );
$label_class = sanitize_key( preg_replace( '/[^A-Za-z0-9_-]/', '-', $label ) );

echo aui()->input(
    array(
        'name'              => esc_attr( $id ),
        'id'                => esc_attr( $id ) . uniqid( '_' ),
        'placeholder'       => empty( $placeholder ) ? wpinv_format_amount(0) : wpinv_format_amount( $placeholder ),
        'value'             => empty( $value ) ? wpinv_format_amount(0) : wpinv_format_amount( $value ),
        'label'             => empty( $label ) ? '' : wp_kses_post( $label ),
        'label_type'        => 'vertical',
        'help_text'         => empty( $description ) ? '' : wp_kses_post( $description ),
        'input_group_right' => $position == 'right' ? wpinv_currency_symbol( $form->get_currency() ) : '',
        'input_group_left'  => $position == 'left' ? wpinv_currency_symbol( $form->get_currency() ) : '',
        'class'             => 'getpaid-refresh-on-change ' . $label_class,
    )
);
