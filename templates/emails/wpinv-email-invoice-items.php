<?php
/**
 * Displays line items in emails.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/wpinv-email-invoice-items.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$column_count = count( $columns );
?>

<?php do_action( 'wpinv_before_email_items', $invoice ); ?>


<div id="wpinv-email-items">

    <h3 class="wpinv-items-t">
        <?php echo apply_filters( 'wpinv_email_items_title', __( 'Items', 'invoicing' ) ); ?>
    </h3>

    <table id="wpinv_checkout_cart" class="table table-bordered table-hover">
    
        <thead>

            <tr class="wpinv_cart_header_row">

                <?php foreach ( $columns as $key => $label ) : ?>
                    <th class="<?php echo 'name' == $key ? 'text-left' : 'text-right' ?> wpinv_cart_item_<?php echo sanitize_html_class( $key ); ?>">
                        <?php echo sanitize_text_field( $label ); ?>
                    </th>
                <?php endforeach; ?>

            </tr>

        </thead>

        <tbody>

            <?php

                // Display the item totals.
                foreach ( $invoice->get_items() as $item ) {
                    wpinv_get_template( 'emails/invoice-item.php', compact( 'invoice', 'item', 'columns' ) );
                }

            ?>

        </tbody>

        <tfoot>
            <?php wpinv_get_template( 'emails/invoice-totals.php', compact( 'invoice', 'column_count' ) ); ?>
        </tfoot>
    
    </table>

</div>
