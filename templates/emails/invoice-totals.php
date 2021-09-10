<?php
/**
 * Displays invoice totals in emails.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/invoice-totals.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 */

defined( 'ABSPATH' ) || exit;

// Totals rows.
$totals = getpaid_invoice_totals_rows( $invoice );

do_action( 'getpaid_before_email_line_totals', $invoice, $totals );

?>


<?php if ( has_action( 'wpinv_email_footer_buttons' ) ) : ?>

    <tr class="wpinv_cart_footer_row">

        <td colspan="<?php echo ( (int) $column_count ); ?>">
            <?php do_action( 'wpinv_email_footer_buttons' ); ?>
        </td>

    </tr>

<?php endif; ?>


<?php foreach ( $totals as $key => $label ) : ?>

    <tr class="wpinv_cart_footer_row wpinv_cart_<?php echo sanitize_html_class( $key ); ?>_row">

        <td colspan="<?php echo ( $column_count - 1 ); ?>" class="wpinv_cart_<?php echo sanitize_html_class( $key ); ?>_label text-right">
            <strong><?php echo esc_html( $label ); ?>:</strong>
        </td>

        <td class="wpinv_cart_<?php echo sanitize_html_class( $key ); ?> text-right">

            <?php

                // Total tax.
                if ( 'tax' == $key ) {
                    echo wpinv_price( $invoice->get_total_tax(), $invoice->get_currency() );
                }

                if ( 'fee' == $key ) {
                    echo wpinv_price( $invoice->get_total_fees(), $invoice->get_currency() );
                }

                // Total discount.
                if ( 'discount' == $key ) {
                    echo wpinv_price( $invoice->get_total_discount(), $invoice->get_currency() );
                }

                // Sub total.
                if ( 'subtotal' == $key ) {
                    echo wpinv_price( $invoice->get_subtotal(), $invoice->get_currency() );
                }

                // Total.
                if ( 'total' == $key ) {
                    echo wpinv_price( $invoice->get_total(), $invoice->get_currency() );
                }

                // Fires when printing a cart total in an email.
                do_action( "getpaid_email_cart_totals_$key", $invoice );

            ?>

        </td>

    </tr>

<?php endforeach; ?>

<?php

    do_action( 'getpaid_after_email_line_totals', $invoice, $totals );
