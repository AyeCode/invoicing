<?php
/**
 * Displays single line items in emails.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/invoice-item.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 * @var GetPaid_Form_Item $item
 * @var array $columns
 */

defined( 'ABSPATH' ) || exit;

?>

<?php do_action( 'getpaid_before_email_line_item', $invoice, $item ); ?>

<tr class="wpinv_cart_item item-type-<?php echo sanitize_html_class( $item->get_type() ); ?>">

    <?php foreach ( array_keys( $columns ) as $column ): ?>

        <td class="<?php echo 'name' == $column ? 'text-left' : 'text-right' ?> wpinv_cart_item_<?php echo sanitize_html_class( $column ); ?>">
            
            <?php

                // Fires before printing a line item column.
                do_action( "getpaid_email_line_item_before_$column", $item, $invoice );

                // Item name.
                if ( 'name' == $column ) {

                    // Display the name.
                    echo '<div class="wpinv_email_cart_item_title">' . esc_html( $item->get_name() ) . '</div>';

                    // And an optional description.
                    $description = $item->get_description();

                    if ( ! empty( $description ) ) {
                        $description = wp_kses_post( $description );
                        echo "<p class='small'>$description</p>";
                    }

                }

                // Item price.
                if ( 'price' == $column ) {

                    // Display the item price (or recurring price if this is a renewal invoice)
                    $price = $invoice->is_renewal() ? $item->get_price() : $item->get_initial_price();
                    echo wpinv_price( $price, $invoice->get_currency() );

                }

                // Item quantity.
                if ( 'quantity' == $column ) {
                    echo (float) $item->get_quantity();
                }

                // Tax rate.
                if ( 'tax_rate' == $column ) {
                    echo round( getpaid_get_invoice_tax_rate( $invoice, $item ), 2 ) . '%';
                }

                // Item sub total.
                if ( 'subtotal' == $column ) {
                    $subtotal = $invoice->is_renewal() ? $item->get_recurring_sub_total() : $item->get_sub_total();
                    echo wpinv_price( $subtotal, $invoice->get_currency() );
                }

                // Fires when printing a line item column.
                do_action( "getpaid_email_line_item_$column", $item, $invoice );

            ?>

        </td>

    <?php endforeach; ?>

</tr>

<?php do_action( 'getpaid_after_email_line_item', $invoice, $item ); ?>
