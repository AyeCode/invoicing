<?php
/**
 * Displays line items in emails.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/invoice-items.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 */

defined( 'ABSPATH' ) || exit;

$column_count = count( $columns );
?>

<?php do_action( 'wpinv_before_email_items', $invoice ); ?>


<div id="wpinv-email-items">

    <h3 class="invoice-items-title">
        <?php echo sprintf( esc_html__( '%s Items', 'invoicing' ), ucfirst( $invoice->get_invoice_quote_type() )); ?>
    </h3>

    <table class="table table-bordered table-hover">
    
        <thead>

            <tr class="wpinv_cart_header_row">

                <?php foreach ( $columns as $key => $label ) : ?>
                    <th class="<?php echo 'name' == $key ? 'text-left' : 'text-right' ?> wpinv_cart_item_<?php echo sanitize_html_class( $key ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </th>
                <?php endforeach; ?>

            </tr>

        </thead>

        <tbody>

            <?php

                // Display the item totals.
                foreach ( $invoice->get_items() as $item ) {
                    wpinv_get_template( 'emails/invoice-item.php', compact( 'invoice', 'item', 'columns' ) );
                }

                // Display the fee totals.
                foreach ( $invoice->get_fees() as $fee ) {
                    wpinv_get_template( 'emails/fee-item.php', compact( 'invoice', 'fee', 'columns' ) );
                }

            ?>

        </tbody>

        <tfoot>
            <?php wpinv_get_template( 'emails/invoice-totals.php', compact( 'invoice', 'column_count' ) ); ?>
        </tfoot>
    
    </table>

</div>
