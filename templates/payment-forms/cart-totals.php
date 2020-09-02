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

$tax       = 0;
$sub_total = 0;
$total     = 0;
$discount  = 0;

// Calculate totals.
foreach ( $form->get_items() as $item ) {

    if ( ! empty( $form->invoice ) && 'amount' == $form->invoice->get_template() ) {
        $amount = $item->get_price();
    } else {
        $amount = $item->get_sub_total();
    }


    // Include the tax.
    if ( wpinv_use_taxes() ) {
        $rate = wpinv_get_tax_rate( $country, false, $item->get_id() );

        if ( wpinv_prices_include_tax() ) {
            $pre_tax  = ( $amount - $amount * $rate * 0.01 );
            $item_tax = $amount - $pre_tax;
        } else {
            $pre_tax  = $amount;
            $item_tax = $amount * $rate * 0.01;
        }

        $tax       = $tax + $item_tax;
        $sub_total = $sub_total + $pre_tax;
        $total     = $sub_total + $tax;

    } else {
        $total  = $total + $amount;
    }

}

?>
<div class='border-top getpaid-payment-form-items-cart-totals'>
    <?php foreach ( $totals as $key => $label ) : ?>
        <div class="getpaid-form-cart-totals-col getpaid-form-cart-totals-<?php echo esc_attr( $key ); ?>">
            <div class="row">
                <div class="col-12 offset-sm-5 col-sm-4">
                    <?php echo sanitize_text_field( $label ); ?>
                </div>
                <div class="col-12 col-sm-3 getpaid-form-cart-totals-total-<?php echo esc_attr( $key ); ?>">
                    <?php
                        do_action( "getpaid_payment_form_cart_totals_$key", $form );

                        // Total tax.
                        if ( 'tax' == $key ) {
                            echo wpinv_price( wpinv_format_amount( $tax ), $currency );
                        }

                        // Total discount.
                        if ( 'discount' == $key ) {
                            echo wpinv_price( wpinv_format_amount( $discount ), $currency );
                        }

                        // Sub total.
                        if ( 'subtotal' == $key ) {
                            echo wpinv_price( wpinv_format_amount( $sub_total ), $currency );
                        }

                        // Total.
                        if ( 'total' == $key ) {
                            echo wpinv_price( wpinv_format_amount( $total ), $currency );
                        }
                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
do_action(  'getpaid_payment_form_cart_totals', $form, $totals );
