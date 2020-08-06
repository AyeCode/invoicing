<?php
/**
 * Displays a payment button in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/pay_button.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $gateway_select ) ) {
    $gateway_select = __( 'Select Payment Method', 'invoicing' );
}

?>
<div class='mt-4 mb-4'>
    <?php do_action( 'wpinv_payment_mode_select', $gateway_select ); ?>
</div>

<?php
    $class = empty( $class ) ? 'btn-primary' : sanitize_html_class( $class );
    echo aui()->input(
        array(
            'name'       => esc_attr( $id ),
            'id'         => esc_attr( $id ) . uniqid( '_' ),
            'value'      => empty( $label ) ? __( 'Proceed to Pay »', 'invoicing' ) : esc_attr( $label ),
            'help_text'  => empty( $description ) ? '' : wp_kses_post( $description ),
            'type'       => 'submit',
            'class'      => 'getpaid-payment-form-submit btn btn-block submit-button ' . $class,
        )
    );
