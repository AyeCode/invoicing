<?php
/**
 * Displays the cart in a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/cart.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Cart table columns.
$columns = array(
    'name'     => __( 'Item', 'invoicing' ),
    'price'    => __( 'Price', 'invoicing' ),
    'quantity' => __( 'Qty', 'invoicing' ),
    'subtotal' => __( 'Subtotal', 'invoicing' ),
);

if ( ! empty( $form->invoice ) ) {
    $columns = getpaid_invoice_item_columns( $form->invoice );
}

if ( isset( $columns['tax_rate'] ) ) {
    unset( $columns['tax_rate'] );
}

$columns = apply_filters( 'getpaid_payment_form_cart_table_columns', $columns, $form );

do_action( 'getpaid_before_payment_form_cart', $form );

?>
    <div class="getpaid-payment-form-items-cart border form-group">

        <div class="getpaid-payment-form-items-cart-header font-weight-bold bg-light border-bottom py-2 px-3">
            <div class="form-row">
                <?php foreach ( $columns as $key => $label ) : ?>
                    <div class="<?php echo 'name' == $key ? 'col-6' : 'col' ?> <?php echo ( in_array( $key, array( 'subtotal', 'quantity', 'tax_rate' ) ) ) ? 'd-none d-sm-block' : '' ?> getpaid-form-cart-item-<?php echo sanitize_html_class( $key ); ?>">
                        <span><?php echo esc_html( $label ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php

            // Display the item totals.
            foreach ( $form->get_items() as $item ) {
                wpinv_get_template( 'payment-forms/cart-item.php', compact( 'form', 'item', 'columns' ) );
            }

            // Display the cart totals.
            wpinv_get_template( 'payment-forms/cart-totals.php', compact( 'form' ) );

        ?>
    </div>

<?php 

do_action( 'getpaid_after_payment_form_cart', $form );
