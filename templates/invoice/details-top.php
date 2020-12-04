<?php
/**
 * Displays the invoice title, type etc.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/details-top.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

        <?php do_action( 'getpaid_before_invoice_details_top', $invoice ); ?>

        <div class="getpaid-invoice-details-top mb-5">
            <div class="row">
                <div class="col-12 col-sm-6 text-sm-left">
                    <?php do_action( 'getpaid_invoice_details_top_left', $invoice ); ?>
                </div>

                <div class="col-12 col-sm-6 text-sm-right">
                    <?php do_action( 'getpaid_invoice_details_top_right', $invoice ); ?>
                </div>
            </div>
        </div>

        <?php do_action( 'getpaid_after_invoice_details_top', $invoice ); ?>

<?php
