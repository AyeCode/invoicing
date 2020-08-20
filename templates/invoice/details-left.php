<?php
/**
 * Displays left side of the invoice details.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/details-left.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
    <div class="wpinv-from-address">
        <?php wpinv_display_from_address(); ?>
    </div>
    <div class="wpinv-to-address">
        <?php wpinv_display_to_address( $invoice ); ?>
    </div>
<?php
