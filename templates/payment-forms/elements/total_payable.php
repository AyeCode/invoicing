<?php
/**
 * Displays a total payable text in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/total_payable.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $text ) ) {
    $text = __( 'Total to pay:', 'invoicing' );
}
?>
<div class="form-group mt-4">
    <strong><?php echo esc_html( $text ); ?></strong>
    <span class="getpaid-checkout-total-payable"></span>
</div>
