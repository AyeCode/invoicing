<?php
/**
 * Displays single fee items in emails.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/fee-item.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 * @var array $fee
 * @var array $columns
 */

defined( 'ABSPATH' ) || exit;

?>

<?php do_action( 'getpaid_before_email_fee_item', $invoice, $fee ); ?>

<tr class="wpinv_cart_item item-fee">

    <?php foreach ( array_keys( $columns ) as $column ): ?>

        <td class="<?php echo 'name' == $column ? 'text-left' : 'text-right' ?> wpinv_cart_item_<?php echo sanitize_html_class( $column ); ?>">
            
            <?php

                // Fires before printing a fee item column.
                do_action( "getpaid_email_fee_item_before_$column", $fee, $invoice );

                // Item name.
                if ( 'name' == $column ) {

                    // Display the name.
                    echo '<div class="wpinv_email_cart_item_title">' . esc_html( $fee['name'] ) . '</div>';

                    // And an optional description.
                    $description = empty( $fee['description'] ) ? esc_html__( 'Fee', 'invoicing' ) : esc_html( $fee['description'] );
                    echo "<p class='small'>$description</p>";

                }

                // Item price.
                if ( 'price' == $column ) {

                    // Display the item price (or recurring price if this is a renewal invoice)
                    if ( $invoice->is_recurring() && $invoice->is_renewal() ) {
                        echo wpinv_price( $fee['recurring_fee'], $invoice->get_currency() );
                    } else {
                        echo wpinv_price( $fee['initial_fee'], $invoice->get_currency() );
                    }

                }

                // Item quantity.
                if ( 'quantity' == $column ) {
                    echo "&mdash;";
                }

                // Item tax.
                if ( 'tax_rate' == $column ) {
                    echo "&mdash;";
                }

                // Item sub total.
                if ( 'subtotal' == $column ) {
                    if ( $invoice->is_recurring() && $invoice->is_renewal() ) {
                        echo wpinv_price( $fee['recurring_fee'], $invoice->get_currency() );
                    } else {
                        echo wpinv_price( $fee['initial_fee'], $invoice->get_currency() );
                    }
                }

                // Fires when printing a line item column.
                do_action( "getpaid_email_fee_item_$column", $fee, $invoice );

            ?>

        </td>

    <?php endforeach; ?>

</tr>

<?php do_action( 'getpaid_after_email_fee_item', $invoice, $fee ); ?>
