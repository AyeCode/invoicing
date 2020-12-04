<?php
/**
 * Displays the invoice header.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/header.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

    <div class="d-print-none border-bottom pt-3 pb-3 bg-white">
        <div class="container">
            <div class="row">

                <div class="col-12 col-sm-6 text-sm-left pl-sm-0">
                    <?php do_action( 'getpaid_invoice_header_left', $invoice );?>
                </div>

                <div class="col-12 col-sm-6 text-sm-right pr-sm-0">
                    <?php do_action( 'getpaid_invoice_header_right', $invoice );?>
                </div>

            </div>
        </div>
    </div>

<?php
