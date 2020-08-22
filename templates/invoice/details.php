<?php
/**
 * Displays the invoice details.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/details.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

        <?php do_action( 'getpaid_before_invoice_details_main', $invoice ); ?>

        <div class="getpaid-invoice-details mt-3 mb-3">
            <div class="row">

                <div class="col-12 col-sm-6">
                    <?php do_action( 'getpaid_invoice_details_left', $invoice ); ?>
                </div>

                <div class="col-12 col-sm-6">
                    <?php do_action( 'getpaid_invoice_details_right', $invoice ); ?>
                </div>

            </div>
        </div>

        <?php do_action( 'getpaid_after_invoice_details_main', $invoice ); ?>

<?php
