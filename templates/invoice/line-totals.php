<?php

/**
 * Displays invoice cart totals 
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/line-totals.php.
 *
 * @version 1.0.19
 */

use Svg\Tag\Rect;

defined( 'ABSPATH' ) || exit;

// Totals rows.
$totals = getpaid_invoice_totals_rows( $invoice );

do_action( 'getpaid_before_invoice_line_totals', $invoice, $totals );

?>
<div class='border-top getpaid-invoice-line-totals'>

    <?php foreach ( $totals as $key => $label ) : ?>

        <div class="getpaid-invoice-line-totals-col <?php echo sanitize_html_class( $key ); ?>">

            <div class="row">

                <div class="col-12 offset-sm-6 col-sm-4 getpaid-invoice-line-totals-label">
                    <?php echo sanitize_text_field( $label ); ?>
                </div>

                <div class="col-12 col-sm-2 getpaid-invoice-line-totals-value">

                    <?php

                        // Total tax.
                        if ( 'tax' == $key ) {
                            echo wpinv_price( wpinv_format_amount( $invoice->get_total_tax() ), $invoice->get_currency() );
                        }

                        // Total discount.
                        if ( 'discount' == $key ) {
                            echo wpinv_price( wpinv_format_amount( $invoice->get_total_discount() ), $invoice->get_currency() );
                        }

                        // Sub total.
                        if ( 'subtotal' == $key ) {
                            echo wpinv_price( wpinv_format_amount( $invoice->get_subtotal() ), $invoice->get_currency() );
                        }

                        // Total.
                        if ( 'total' == $key ) {
                            echo wpinv_price( wpinv_format_amount( $invoice->get_total() ), $invoice->get_currency() );
                        }

                        // Fires when printing a cart total.
                        do_action( "getpaid_invoice_cart_totals_$key", $invoice );

                    ?>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div> <!-- end .getpaid-invoice-line-totals -->

<?php do_action(  'getpaid_after_invoice_line_totals', $invoice, $totals ); ?>
