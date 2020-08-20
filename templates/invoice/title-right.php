<?php
/**
 * Displays right side of the invoice title.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/title-right.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="p-3">
    <h2>
        <?php echo apply_filters( 'getpaid_invoice_type_label', ucfirst( $invoice->get_type() ), $invoice ); ?>
    </h2>
</div>

<?php
