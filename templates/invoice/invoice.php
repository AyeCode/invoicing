<?php
/**
 * Displays an invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/invoice.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="bsui">

    <div class="d-flex flex-column min-vh-100 bg-light">

        <?php

            // Fires when printing the header.
            do_action( 'getpaid_invoice_header', $invoice );

            // Print the opening wrapper.
            echo '<div class="container bg-white border mt-4 mb-4 p-4 position-relative flex-grow-1">';

            // Print notifications.
            wpinv_print_errors();

            // Fires when printing the invoice details.
            do_action( 'getpaid_invoice_details', $invoice );

            // Fires when printing the invoice line items.
            do_action( 'getpaid_invoice_line_items', $invoice );

            // Print the closing wrapper.
            echo '</div>';

            // Fires when printing the invoice footer.
            do_action( 'getpaid_invoice_footer', $invoice );

        ?>

    </div>
</div>
