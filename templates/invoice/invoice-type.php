<?php
/**
 * Displays right side of the type of invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/invoice-type.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="p-3">
    <h2 class="h1 text-dark">
        <?php echo apply_filters( 'getpaid_invoice_type_label', ucfirst( $invoice->get_type() ), $invoice ); ?>
    </h2>
</div>

<?php
