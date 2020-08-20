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

    <div class="wpinv-top-bar no-print">
        <div class="container">
            <div class="row">
                <div class="col text-left">
                    <?php do_action( 'getpaid_invoice_top_bar_left', $invoice );?>
                </div>
                <div class="col text-right">
                    <?php do_action( 'getpaid_invoice_top_bar_right', $invoice );?>
                </div>
            </div>
        </div>
    </div>

<?php
