<?php
/**
 * Displays a line items in an invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/line-items.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 */

defined( 'ABSPATH' ) || exit;

?>

<?php do_action( 'getpaid_invoice_before_line_items', $invoice ); ?>

    <h2 class="mt-5 mb-1 h4"><?php echo sprintf( esc_html__( '%s Items', 'invoicing' ), ucfirst( $invoice->get_invoice_quote_type() )); ?></h2>
    <div class="getpaid-invoice-items mb-4 border">


        <div class="getpaid-invoice-items-header <?php echo sanitize_html_class( $invoice->get_template() ); ?>">
            <div class="form-row">
                <?php foreach ( $columns as $key => $label ) : ?>
                    <div class="<?php echo 'name' == $key ? 'col-12 col-sm-6' : 'col-12 col-sm' ?> getpaid-invoice-line-item-col-<?php echo esc_attr( $key ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>


        <?php

            // Display the item totals.
            foreach ( $invoice->get_items() as $item ) {
                wpinv_get_template( 'invoice/line-item.php', compact( 'invoice', 'item', 'columns' ) );
            }

            // Display the fee totals.
            foreach ( $invoice->get_fees() as $fee ) {
                wpinv_get_template( 'invoice/fee-item.php', compact( 'invoice', 'fee', 'columns' ) );
            }

            // Display the cart totals.
            wpinv_get_template( 'invoice/line-totals.php', compact( 'invoice' ) );

        ?>

    </div>

<?php do_action( 'getpaid_invoice_after_line_items', $invoice ); ?>
