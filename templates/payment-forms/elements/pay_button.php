<?php
/**
 * Displays a payment button in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/pay_button.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$class = empty( $class ) ? 'btn-primary' : sanitize_html_class( $class );
$label = empty( $label ) ? esc_attr__( 'Proceed to Pay »', 'invoicing' ) : esc_attr( $label );
$free  = empty( $free ) ? esc_attr__( 'Continue »', 'invoicing' ) : esc_attr( $free );

echo aui()->input(
    array(
        'name'             => esc_attr( $id ),
        'id'               => esc_attr( $id ) . uniqid( '_' ),
        'value'            => empty( $label ) ? __( 'Proceed to Pay »', 'invoicing' ) : esc_attr( $label ),
        'help_text'        => empty( $description ) ? '' : wp_kses_post( $description ),
        'type'             => 'submit',
        'class'            => 'getpaid-payment-form-submit btn btn-block submit-button ' . $class,
        'extra_attributes' => array(
            'data-free' => $free,
            'data-pay'  => $label,
        )
    )
);
