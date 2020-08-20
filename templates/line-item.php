<?php
/**
 * Displays a single line item in an invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/line-item.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$item_id = (int) $item->get_id();

?>
<tr class="item-<?php echo $item_id; ?> <?php echo sanitize_html_class( $item->get_type() ); ?>" id="wpinv-line-item-<?php echo $item_id; ?>">
    <?php foreach ( array_keys( $columns ) as $column ): ?>

        <?php do_action( 'getpaid_line_items_col_body_before_' . $column, $invoice, $item ); ?>
        <td class='wpinv-line-item-<?php echo esc_attr($column) ?> <?php echo esc_attr($column) ?>' id='wpinv-line-item-<?php echo esc_attr($column) . $item_id ?>'>

            <?php

                do_action( "getpaid_line_item_before_$column", $item, $invoice );

                // Item name.
                if ( 'name' == $column ) {
                    echo sanitize_text_field( $item->get_name() );
                    $description = $item->get_description();

                    if ( ! empty( $description ) ) {
                        $description = wp_kses_post( $description );
                        echo "<small class='form-text text-muted pr-2 m-0'>$description</small>";
                    }

                }

                // Item price.
                if ( 'price' == $column ) {
                    echo wpinv_price( wpinv_format_amount( $item->get_sub_total() ), $item->get_initial_price() );
                }

                // Item quantity.
                if ( 'quantity' == $column ) {
                    echo (int) $item->get_qantity();
                }

                // Item sub total.
                if ( 'subtotal' == $key ) {
                    echo wpinv_price( wpinv_format_amount( $item->get_sub_total() ), $invoice->get_currency() );
                }

                do_action( "getpaid_line_item_$column", $item, $invoice );

                do_action( "getpaid_line_item_after_$column", $item, $invoice );

            ?>
        </td>
        <?php do_action( 'getpaid_line_items_col_body_before_' . $column, $invoice, $item ); ?>
    <?php endforeach; ?>
</tr>
<?php
