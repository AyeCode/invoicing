<?php
/**
 * Displays the line items header in an invoice.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/line-item-header.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
<tr class="<?php echo sanitize_html_class( $invoice->get_template() ); ?>" id="wpinv-line-items-header-row">
    <?php foreach ( $columns as $key => $label ): ?>

        <?php do_action( 'getpaid_line_items_col_header_before_' . $key, $invoice ); ?>
        <th class='wpinv-line-item-<?php echo esc_attr($key) ?> <?php echo esc_attr($key) ?>' id='wpinv-line-item-<?php echo esc_attr($key) ?>'>
            <?php
                $label = sanitize_text_field( $label );
                echo "<strong>$label</strong>";
                do_action( 'getpaid_line_items_col_header_' . $key, $invoice );
            ?>
        </th>
        <?php do_action( 'getpaid_line_items_col_header_after_' . $key, $invoice ); ?>
    <?php endforeach; ?>
</tr>
<?php
