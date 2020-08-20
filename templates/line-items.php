<?php
/**
 * Displays a line items in an invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/line-items.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$invoice = new WPInv_Invoice( $invoice );

// Fires before printing the line items.
do_action( 'getpaid_before_invoice_line_items', $invoice );

// Line item columns.
$columns = apply_filters(
    'getpaid_invoice_line_items_table_columns',
    array(
        'name'     => __( 'Item', 'invoicing' ),
        'price'    => __( 'Price', 'invoicing' ),
        'quantity' => __( 'Quantity', 'invoicing' ),
        'subtotal' => __( 'Subtotal', 'invoicing' ),
    ),
    $form
);

// Quantities.
if ( isset( $columns[ 'quantity' ] ) ) {

    if ( 'amount' == $invoice->get_template() ) {
        unset( $columns[ 'quantity' ] );
    }

    if ( 'hours' == $invoice->get_template() ) {
        $columns[ 'quantity' ] = __( 'Hours', 'invoicing' );
    }

    if ( ! wpinv_item_quantities_enabled() ) {
        unset( $columns[ 'quantity' ] );
    }

}

// Price.
if ( isset( $columns[ 'price' ] ) ) {

    if ( 'amount' == $invoice->get_template() ) {
        $columns[ 'price' ] = __( 'Amount', 'invoicing' );
    }

    if ( 'hours' == $invoice->get_template() ) {
        $columns[ 'price' ] = __( 'Rate', 'invoicing' );
    }

}

// Sub total.
if ( isset( $columns[ 'subtotal' ] ) ) {

    if ( 'amount' == $invoice->get_template() ) {
        unset( $columns[ 'subtotal' ] );
    }

}

?>

<table class="table table-sm table-bordered">
    <thead>
        <?php wpinv_get_template( 'line-item-header.php', compact( 'item', 'invoice', 'columns' ) );?>
    </thead>
    <tbody>
        <?php foreach ( $invoice->get_items() as $item_id => $item ) : ?>
            <?php wpinv_get_template( 'line-item.php', compact( 'item', 'invoice', 'columns' ) ); ?>
        <?php endforeach; ?>
    </tbody>
</table>