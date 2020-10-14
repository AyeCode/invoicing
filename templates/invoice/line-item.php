<?php
/**
 * Displays a single line item in an invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/line-item.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

do_action( 'getpaid_before_invoice_line_item', $invoice, $item );

?>

<div class='getpaid-invoice-item item-<?php echo (int) $item->get_id(); ?> item-type-<?php echo sanitize_html_class( $item->get_type() ); ?> border-bottom'>

    <div class="form-row">

        <?php foreach ( array_keys( $columns ) as $column ): ?>

            <div class="<?php echo 'name' == $column ? 'col-12 col-sm-6' : 'col-12 col-sm' ?> getpaid-invoice-item-<?php echo sanitize_html_class( $column ); ?>">

                <?php

                    // Fires before printing a line item column.
                    do_action( "getpaid_invoice_line_item_before_$column", $item, $invoice );

                    // Item name.
                    if ( 'name' == $column ) {

                        // Display the name.
                        echo '<div class="mb-1">' . sanitize_text_field( $item->get_name() ) . '</div>';

                        // And an optional description.
                        $description = $item->get_description();

                        if ( ! empty( $description ) ) {
                            $description = wp_kses_post( $description );
                            echo "<small class='form-text text-muted pr-2 m-0'>$description</small>";
                        }

                        $actions = apply_filters( 'getpaid-invoice-page-line-item-actions', array(), $item, $invoice );

                        if ( ! empty( $actions ) ) {

                            $sanitized  = array();
                            foreach ( $actions as $key => $action ) {
                                $key         = sanitize_html_class( $key );
                                $action      = wp_kses_post( $action );
                                $sanitized[] = "<span class='$key'>$action</span>";
                            }

                            echo "<small class='form-text getpaid-line-item-actions'>";
                            echo implode( ' | ', $sanitized );
                            echo '</small>';

                        }

                    }

                    // Item price.
                    if ( 'price' == $column ) {

                        // Display the item price (or recurring price if this is a renewal invoice)
                        if ( $invoice->is_recurring() && $invoice->is_renewal() ) {
                            echo wpinv_price( wpinv_format_amount( $item->get_price() ), $invoice->get_currency() );
                        } else {
                            echo wpinv_price( wpinv_format_amount( $item->get_initial_price() ), $invoice->get_currency() );
                        }

                    }

                    // Item quantity.
                    if ( 'quantity' == $column ) {
                        echo (int) $item->get_qantity();
                    }

                    // Item sub total.
                    if ( 'subtotal' == $column ) {
                        echo wpinv_price( wpinv_format_amount( $item->get_sub_total() ), $invoice->get_currency() );
                    }

                    // Fires when printing a line item column.
                    do_action( "getpaid_invoice_line_item_$column", $item, $invoice );

                    // Fires after printing a line item column.
                    do_action( "getpaid_invoice_line_item_after_$column", $item, $invoice );

                ?>

            </div>
    
        <?php endforeach; ?>

    </div>

</div>
<?php
