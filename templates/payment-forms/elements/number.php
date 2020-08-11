<?php
/**
 * Displays a number input in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/number.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

echo aui()->input(
    array(
        'name'       => esc_attr( $id ),
        'id'         => esc_attr( $id ) . uniqid( '_' ),
        'placeholder'=> empty( $placeholder ) ? '' : esc_attr( $placeholder ),
        'required'   => ! empty( $required ),
        'label'      => empty( $label ) ? '' : wp_kses_post( $label ),
        'label_type' => 'vertical',
        'help_text'  => empty( $description ) ? '' : wp_kses_post( $description ),
        'type'       => 'number',
    )
);
