<?php
/**
 * Displays actions on the left side of the invoice header.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/header-left-actions.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 */

defined( 'ABSPATH' ) || exit;

?>

        <div class="getpaid-header-left-actions">

            <?php if ( $invoice->is_type( 'invoice' ) && $invoice->needs_payment() && ! $invoice->is_held() ): ?>
                <a class="btn btn-sm btn-primary m-1 d-inline-block invoice-action-pay" href="<?php echo esc_url( $invoice->get_checkout_payment_url() ); ?>">
                    <?php _e( 'Pay For Invoice', 'invoicing' ); ?>
                </a>
            <?php endif; ?>

            <?php if ( $invoice->is_type( 'invoice' ) && $invoice->is_paid() ): ?>
                <a class="btn btn-sm btn-info m-1 d-inline-block invoice-action-receipt" href="<?php echo esc_url( $invoice->get_receipt_url() ); ?>">
                    <?php _e( 'View Receipt', 'invoicing' ); ?>
                </a>
            <?php endif; ?>

            <?php do_action( 'wpinv_invoice_display_left_actions', $invoice ); ?>

        </div>

<?php
