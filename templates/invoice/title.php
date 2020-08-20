<?php
/**
 * Displays the invoice title.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/title.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

    <!-- ///// Start PDF header -->
    <htmlpageheader name="wpinv-pdf-header">
        <?php do_action( 'wpinv_invoice_print_before_header', $invoice ); ?>
        <div class="wpinv-header mt-3 mb-3">
            <div class="row">
                <div class="col text-left">
                    <?php do_action( 'getpaid_invoice_title_left', $invoice ); ?>
                </div>

                <div class="col text-right">
                    <?php do_action( 'getpaid_invoice_title_right', $invoice ); ?>
                </div>
            </div>
        </div>
        <?php do_action( 'wpinv_invoice_print_after_header', $invoice ); ?>
    </htmlpageheader>
    <!-- End PDF header ///// -->

<?php
