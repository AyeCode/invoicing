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

if ( ! wpinv_use_taxes() && isset( $totals['tax'] ) ) {
	unset( $totals['tax'] );
}

do_action( 'getpaid_before_payment_form_cart_totals', $form, $totals );

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
		<div class="col-12 offset-sm-6 col-sm-6 border-sm-left pl-sm-0">

			<?php foreach ( $totals as $key => $label ) : ?>

				<div class="getpaid-form-cart-totals-col getpaid-form-cart-totals-<?php echo esc_attr( $key ); ?> font-weight-bold py-2 px-3 <?php echo 'total' == $key ? 'bg-light' : 'border-bottom' ?>">

					<div class="form-row">

						<div class="col-8 pl-sm-0 getpaid-payment-form-line-totals-label">
							<?php echo esc_html( $label ); ?>
						</div>

						<div class="col-4 getpaid-payment-form-line-totals-value getpaid-form-cart-totals-total-<?php echo esc_attr( $key ); ?>">

							<?php

								// Total tax.
								if ( in_array( $key, array( 'tax', 'discount', 'subtotal', 'total', 'fees' ) ) ) {
									echo wpinv_price( 0, $currency );
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
do_action(  'getpaid_payment_form_cart_totals', $form, $totals );
