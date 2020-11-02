<?php
/**
 * Displays actions on the left side of the invoice header.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/header-left-actions.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

        <div class="getpaid-header-left-actions">

            <?php if ( $invoice->is_type( 'invoice' ) && $invoice->needs_payment() && ! $invoice->is_held() ): ?>
                <a class="btn btn-sm btn-primary invoice-action-pay" href="<?php echo esc_url( $invoice->get_checkout_payment_url() ); ?>">
                    <?php _e( 'Pay For Invoice', 'invoicing' ); ?>
                </a>
            <?php endif; ?>

            <?php do_action( 'wpinv_invoice_display_left_actions', $invoice ); ?>

        </div>

<?php
