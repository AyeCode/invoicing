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
<h2 class="h3 text-dark">
    <?php echo esc_html( apply_filters( 'getpaid_invoice_type_label', esc_html( ucfirst( $invoice->get_invoice_quote_type() ) ), $invoice ) ); ?>
</h2>

<?php
