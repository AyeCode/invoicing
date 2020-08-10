<?php
/**
 * Displays a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/form.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Make sure that the form is active.
if ( ! $form->is_active() ) {
    echo aui()->alert(
        array(
            'type'    => 'warning',
            'content' => __( 'This payment form is no longer active', 'invoicing' ),
        )
    );
    return;
}

// Display the payment form.
do_action( 'getpaid_before_payment_form', $form );

?>
<form class='getpaid-payment-form' method='POST'>
    <?php do_action( 'getpaid_payment_form_top', $form ); ?>
    <?php wp_nonce_field( 'vat_validation', '_wpi_nonce' ); ?>
    <input type='hidden' name='form_id' value='<?php echo $form->get_id(); ?>'/>
    <input type='hidden' name='getpaid_payment_form_submission' value='1'/>
    <?php do_action( 'getpaid_payment_form_before_elements', $form ); ?>
    <?php
        foreach ( $form->get_elements() as $element ) {
            if ( isset( $element['type'] ) ) {
                do_action( 'getpaid_payment_form_element', $element, $form );
                do_action( "getpaid_payment_form_element_{$element['type']}_template", $element, $form );
            }
        }
    ?>
    <?php do_action( 'getpaid_payment_form_after_elements', $form ); ?>
    <div class='getpaid-payment-form-errors alert alert-danger d-none'></div>
    <?php do_action( 'getpaid_payment_form_bottom', $form ); ?>
</form>
<?php
do_action( 'getpaid_payment_form', $form );
