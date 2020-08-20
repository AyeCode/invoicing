<?php
/**
 * Displays the invoice details.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/details.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

do_action( 'wpinv_invoice_print_before_top_content', $invoice );

?>
        <div class="wpinv-top-content mt-3 mb-3">
            <div class="row">
                <div class="col">
                    <?php do_action( 'getpaid_invoice_details_left', $invoice ); ?>
                </div>

                <div class="col">
                    <?php do_action( 'getpaid_invoice_details_right', $invoice ); ?>
                </div>
            </div>
        </div>
<?php

do_action( 'wpinv_invoice_print_after_top_content', $invoice );