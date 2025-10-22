<?php

/**
 * Displays invoice cart totals
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/line-totals.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 */

defined( 'ABSPATH' ) || exit;

// Totals rows.
$totals = getpaid_invoice_totals_rows( $invoice );

do_action( 'getpaid_before_invoice_line_totals', $invoice, $totals );

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
<div class='getpaid-invoice-line-totals'>
	<div class="row">
		<div class="col-12 offset-sm-6 col-sm-6 border-sm-left pl-sm-0 ps-sm-0">
			<?php foreach ( $totals as $key => $label ) : ?>
				<div class="getpaid-invoice-line-totals-col <?php echo esc_attr( $key ); ?>">
					<div class="form-row row">
						<div class="col-<?php echo (int) $cols_label; ?> getpaid-invoice-line-totals-label text-right text-end">
							<?php echo esc_html( $label ); ?>
						</div>
						<div class="col-<?php echo (int) $cols_value; ?> getpaid-invoice-line-totals-value">
						<?php
							// Total tax.
							if ( 'tax' === $key ) {
								wpinv_the_price( $invoice->get_total_tax(), $invoice->get_currency() );

								if ( wpinv_use_taxes() && ! $invoice->get_disable_taxes() ) {
									$taxes = $invoice->get_total_tax();
									if ( empty( $taxes ) && GetPaid_Payment_Form_Submission_Taxes::is_eu_transaction( $invoice->get_country() ) ) {
										echo ' <em class="text-muted small">';
										_x( '(Reverse charged)', 'This is a legal term for reverse charging tax in the EU', 'invoicing' );
										echo '</em>';
									}
								}
							}

							// Check if field starts with tax__.
							if ( 0 === strpos( $key, 'tax__' ) ) {
								$tax_amount = $invoice->get_tax_total_by_name( str_replace( 'tax__', '', $key ) );
								wpinv_the_price( $tax_amount, $invoice->get_currency() );

								if ( wpinv_use_taxes() && ! $invoice->get_disable_taxes() ) {
									if ( empty( $tax_amount ) && GetPaid_Payment_Form_Submission_Taxes::is_eu_transaction( $invoice->get_country() ) ) {
										echo ' <em class="text-muted small">';
										_x( '(Reverse charged)', 'This is a legal term for reverse charging tax in the EU', 'invoicing' );
										echo '</em>';
									}
								}
							}

							// Total Fee.
							if ( 'fee' === $key ) {
								wpinv_the_price( $invoice->get_total_fees(), $invoice->get_currency() );
							}

							// Total discount.
							if ( 'discount' === $key ) {
								wpinv_the_price( $invoice->get_total_discount(), $invoice->get_currency() );
							}

							// Shipping.
							if ( 'shipping' === $key ) {
								wpinv_the_price( $invoice->get_shipping(), $invoice->get_currency() );
							}

							// Sub total.
							if ( 'subtotal' === $key ) {
								wpinv_the_price( $invoice->get_subtotal(), $invoice->get_currency() );
							}

							// Total.
							if ( 'total' === $key ) {
								wpinv_the_price( $invoice->get_total(), $invoice->get_currency() );
							}

							// Fires when printing a cart total.
							do_action( "getpaid_invoice_cart_totals_$key", $invoice );
						?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div> <!-- end .getpaid-invoice-line-totals -->
<?php do_action( 'getpaid_after_invoice_line_totals', $invoice, $totals ); ?>
