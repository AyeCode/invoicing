<?php
/**
 * Displays a billing email input in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/billing_email.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$value = '';
$class = '';

if ( ! empty( $form->invoice ) ) {
    $value   = sanitize_email( $form->invoice->get_email() );
} else if ( is_user_logged_in() ) {
    $user  = wp_get_current_user();
    $value = sanitize_email( $user->user_email );
}

if ( ! empty( $value ) && ! empty( $hide_billing_email ) ) {
    $class = 'd-none';
}

do_action( 'getpaid_before_payment_form_billing_email', $form );

echo "<span class='$class'>";

echo aui()->input(
    array(
        'name'       => 'billing_email',
        'id'         => esc_attr( $id ) . uniqid( '_' ),
        'placeholder'=> empty( $placeholder ) ? '' : esc_attr( $placeholder ),
        'required'   => ! empty( $required ),
        'label'      => empty( $label ) ? '' : wp_kses_post( $label ) . '<span class="text-danger"> *</span>',
        'label_type' => 'vertical',
        'help_text'  => empty( $description ) ? '' : wp_kses_post( $description ),
        'type'       => 'email',
        'value'      => $value,
        'class'      => 'wpinv_billing_email',
        'extra_attributes' => array(
            'autocomplete' => 'billing email',
        ),
    )
);

echo '</span>';

do_action( 'getpaid_after_payment_form_billing_email', $form );
