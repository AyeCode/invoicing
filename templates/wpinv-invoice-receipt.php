<?php
/**
 * This template is used to display the purchase summary with [wpinv_receipt]
 */
global $wpinv_receipt_args;

$invoice   = get_post( $wpinv_receipt_args['id'] );

if( empty( $invoice ) ) {
    ?>
    <div class="wpinv_errors alert wpi-alert-error">
        <?php _e( 'The specified receipt ID appears to be invalid', 'invoicing' ); ?>
    </div>
    <?php
    return;
}
$invoice = wpinv_get_invoice( $invoice->ID );

global $ajax_cart_details;
$ajax_cart_details = $invoice->get_cart_details();
$cart_items        = $ajax_cart_details;

$invoice_id         = $invoice->ID;
$quantities_enabled = wpinv_item_quantities_enabled();
$use_taxes          = wpinv_use_taxes();
$zero_tax           = !(float)$invoice->get_tax() > 0 ? true : false;
$tax_label          = !$zero_tax && $use_taxes ? ( wpinv_prices_include_tax() ? __( '(Tax Incl.)', 'invoicing' ) : __( '(Tax Excl.)', 'invoicing' ) ) : '';
?>
<?php do_action( 'wpinv_before_receipt', $invoice ); ?>
<div class="wpinv-receipt">
    <?php do_action( 'wpinv_receipt_start', $invoice ); ?>
    <div class="wpinv-receipt-message"><?php _e( 'Thank you for your payment!', 'invoicing' ); ?></div>
    <?php do_action( 'wpinv_before_receipt_details', $invoice ); ?>
    <div class="wpinv-receipt-details">
        <h3 class="wpinv-details-t"><?php echo apply_filters( 'wpinv_receipt_details_title', __( 'Invoice Details', 'invoicing' ) ); ?></h3>
        <?php wpinv_display_invoice_details( $invoice ); ?>
    </div>
    <?php do_action( 'wpinv_after_receipt_details', $invoice ); ?>
    <?php do_action( 'wpinv_receipt_end', $invoice ); ?>
</div>
<?php do_action( 'wpinv_after_receipt', $invoice ); ?>