<?php
/**
 * Displays the invoice title, type etc.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/details-top.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$watermark = wpinv_watermark( $invoice->get_id() )

?>

        <?php do_action( 'getpaid_before_invoice_details_top', $invoice ); ?>

        <?php if ( ! empty( $watermark ) ) : ?>

            <div class="getpaid-watermark no-print">
                <p><?php echo sanitize_text_field( $watermark ) ?></p>
            </div>

        <?php endif; ?>

        <div class="getpaid-invoice-details-top mt-3 mb-3">
            <div class="row">
                <div class="col-12 col-sm-6 text-left">
                    <?php do_action( 'getpaid_invoice_details_top_left', $invoice ); ?>
                </div>

                <div class="col-12 col-sm-6 text-right">
                    <?php do_action( 'getpaid_invoice_details_top_right', $invoice ); ?>
                </div>
            </div>
        </div>

        <?php do_action( 'getpaid_after_invoice_details_top', $invoice ); ?>

<?php
