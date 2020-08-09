<?php
/**
 * Displays a gateway select input in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/gateway_select.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $text ) ) {
    $text = __( 'Select Payment Method', 'invoicing' );
}

?>
<div class='mt-4 mb-4'>
    <?php do_action( 'wpinv_payment_mode_select', $text, $form ); ?>
</div>
