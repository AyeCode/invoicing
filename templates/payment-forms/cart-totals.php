<?php
/**
 * Displays a cart totals in a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/cart-totals.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Totals rows.
$totals = apply_filters(
    'getpaid_payment_form_cart_table_totals',
    array(
        'subtotal' => __( 'Subtotal', 'invoicing' ),
        'tax'      => __( 'Tax', 'invoicing' ),
        'discount' => __( 'Discount', 'invoicing' ),
        'total'    => __( 'Total', 'invoicing' ),
    ),
    $form
);

$currency = $form->get_currency();
$country  = wpinv_get_default_country();

if ( ! empty( $form->invoice ) ) {
    $country  = $form->invoice->get_country();
}

if ( ! wpinv_use_taxes() && isset( $totals['tax'] ) ) {
    unset( $totals['tax'] );
}

do_action( 'getpaid_before_payment_form_cart_totals', $form, $totals );

?>
<div class='border-top getpaid-payment-form-items-cart-totals'>
    <?php foreach ( $totals as $key => $label ) : ?>
        <div class="getpaid-form-cart-totals-col px-3 py-2 getpaid-form-cart-totals-<?php echo esc_attr( $key ); ?>">
            <div class="row">
                <div class="col-12 offset-sm-5 col-sm-3">
                    <?php echo sanitize_text_field( $label ); ?>
                </div>
                <div class="col-12 col-sm-4 getpaid-form-cart-totals-total-<?php echo esc_attr( $key ); ?>">
                    <?php
                        do_action( "getpaid_payment_form_cart_totals_$key", $form );

                        // Total tax.
                        if ( in_array( $key, array( 'tax', 'discount', 'subtotal', 'total' ) ) ) {
                            echo wpinv_price( 0, $currency );
                        }

                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
do_action(  'getpaid_payment_form_cart_totals', $form, $totals );
