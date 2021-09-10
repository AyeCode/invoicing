<?php
/**
 * Displays a single fee item in an invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/fee-item.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 * @var array $fee
 */

defined( 'ABSPATH' ) || exit;

do_action( 'getpaid_before_invoice_fee_item', $invoice, $fee );

?>

<div class='getpaid-invoice-item item-fee border-bottom'>

    <div class="form-row">

        <?php foreach ( array_keys( $columns ) as $column ): ?>

            <div class="<?php echo 'name' == $column ? 'col-12 col-sm-6' : 'col-12 col-sm' ?> getpaid-invoice-item-<?php echo sanitize_html_class( $column ); ?>">

                <?php

                    // Fires before printing a fee item column.
                    do_action( "getpaid_invoice_fee_item_before_$column", $fee, $invoice );

                    // Item name.
                    if ( 'name' == $column ) {

                        // Display the name.
                        echo '<div class="mb-1">' . esc_html( $fee['name'] ) . '</div>';

                        // And an optional description.
                        $description = empty( $fee['description'] ) ? esc_html__( 'Fee', 'invoicing' ) : esc_html( $fee['description'] );
                        echo "<small class='form-text text-muted pr-2 m-0'>$description</small>";

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

                        // Display the item price (or recurring price if this is a renewal invoice)
                        if ( $invoice->is_recurring() && $invoice->is_renewal() ) {
                            echo wpinv_price( $fee['recurring_fee'], $invoice->get_currency() );
                        } else {
                            echo wpinv_price( $fee['initial_fee'], $invoice->get_currency() );
                        }

                    }

                    // Fires when printing a fee item column.
                    do_action( "getpaid_invoice_fee_item_$column", $fee, $invoice );

                    // Fires after printing a fee item column.
                    do_action( "getpaid_invoice_fee_item_after_$column", $fee, $invoice );

                ?>

            </div>
    
        <?php endforeach; ?>

    </div>

</div>
<?php
