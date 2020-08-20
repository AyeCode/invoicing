<?php
/**
 * Displays right side of the invoice details.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/details-right.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
    <div class="wpinv-details">
        <?php do_action( 'wpinv_invoice_print_before_details', $invoice ); ?>
        <?php wpinv_display_invoice_details( $invoice ); ?>
        <?php do_action( 'wpinv_invoice_print_after_details', $invoice ); ?>
    </div>
<?php
