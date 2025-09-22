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
$cart_totals = apply_filters(
	'getpaid_payment_form_cart_table_totals',
	array(
		'subtotal' => __( 'Subtotal', 'invoicing' ),
		'tax'      => __( 'Tax', 'invoicing' ),
		'fees'     => __( 'Fee', 'invoicing' ),
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

if ( ! wpinv_use_taxes() && isset( $cart_totals['tax'] ) ) {
	unset( $cart_totals['tax'] );
}

do_action( 'getpaid_before_payment_form_cart_totals', $form, $cart_totals );

$cols_label = 8;
$cols_value = 4;
if ( ! empty( $columns ) ) {
	if ( count( $columns ) == 5 ) {
		$cols_label = 9;
		$cols_value = 3;
	} else if ( count( $columns ) == 3 ) {
		$cols_label = 6;
		$cols_value = 6;
	}
}
?>
<style>
@media screen and (min-width: 576px) {
	.bsui .border-sm-left {
		border-left: 1px solid #dee2e6 !important;
	}
}
</style>
<div class='getpaid-payment-form-items-cart-totals'>
	<div class="row">
		<div class="col-12 offset-sm-6 col-sm-6 pl-sm-0 ps-sm-0">
			<?php foreach ( $cart_totals as $key => $label ) : ?>
				<div class="getpaid-form-cart-totals-col getpaid-form-cart-totals-<?php echo esc_attr( $key ); ?> font-weight-bold border-left border-start py-2 px-3 <?php echo ( 'subtotal' === $key || 'total' === $key ) ? 'bg-light' : 'border-bottom'; ?> <?php echo 'tax' === $key && wpinv_display_individual_tax_rates() ? 'getpaid-tax-template d-none' : ''; ?>">
					<div class="form-row row">
						<div class="col-<?php echo (int) $cols_label; ?> getpaid-payment-form-line-totals-label text-right text-end">
							<?php echo esc_html( $label ); ?>
						</div>
						<div class="col-<?php echo (int) $cols_value; ?> getpaid-payment-form-line-totals-value getpaid-form-cart-totals-total-<?php echo esc_attr( $key ); ?>">
							<?php
								// Total tax.
								if ( in_array( $key, array( 'tax', 'discount', 'subtotal', 'total', 'fees' ), true ) ) {
									wpinv_the_price( 0, $currency );
								}

								do_action( "getpaid_payment_form_cart_totals_$key", $form );
							?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<?php
do_action( 'getpaid_payment_form_cart_totals', $form, $cart_totals );
