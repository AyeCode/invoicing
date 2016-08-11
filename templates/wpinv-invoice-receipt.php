<?php
/**
 * This template is used to display the purchase summary with [edd_receipt]
 */
global $wpinv_receipt_args;

$invoice   = get_post( $wpinv_receipt_args['id'] );

if( empty( $invoice ) ) {
    ?>
    <div class="wpinv_errors alert wpi-alert-error">
        <?php _e( 'The specified receipt ID appears to be invalid', 'invoicing' ); ?>
    </div>
    <?php
    return;
}
$invoice = wpinv_get_invoice( $invoice->ID );

global $ajax_cart_details;
$ajax_cart_details = $invoice->get_cart_details();
$cart_items        = $ajax_cart_details;

$invoice_id         = $invoice->ID;
$quantities_enabled = wpinv_item_quantities_enabled();
$use_taxes          = wpinv_use_taxes();
$zero_tax           = !(float)$invoice->get_tax() > 0 ? true : false;
$tax_label          = !$zero_tax && $use_taxes ? ( wpinv_prices_include_tax() ? __( '(Tax Incl.)', 'invoicing' ) : __( '(Tax Excl.)', 'invoicing' ) ) : '';
?>
<?php do_action( 'wpinv_before_receipt', $invoice ); ?>
<div class="wpinv-receipt">
    <?php do_action( 'wpinv_before_start', $invoice ); ?>
    <?php do_action( 'wpinv_before_receipt_details', $invoice ); ?>
    <div class="wpinv-receipt-details">
        <h3 class="wpinv-details-t"><?php echo apply_filters( 'wpinv_receipt_details_title', __( 'Invoice Details', 'invoicing' ) ); ?></h3>
        <?php wpinv_display_invoice_details( $invoice_id ); ?>
    </div>
    <?php do_action( 'wpinv_after_receipt_details', $invoice ); ?>
    <?php do_action( 'wpinv_before_receipt_billing', $invoice ); ?>
    <div class="wpinv-receipt-address">
        <h3 class="wpinv-address-t"><?php echo apply_filters( 'wpinv_receipt_billing_title', __( 'Billing Details', 'invoicing' ) ); ?></h3>
        <?php wpinv_receipt_billing_address( $invoice_id ); ?>
    </div>
    <?php do_action( 'wpinv_after_receipt_billing', $invoice ); ?>
    <?php do_action( 'wpinv_before_receipt_items', $invoice ); ?>
    <div class="wpinv-receipt-items">
        <h3 class="wpinv-items-t"><?php echo apply_filters( 'wpinv_receipt_items_title', __( 'Items', 'invoicing' ) ); ?></h3>
        <table id="wpinv_checkout_cart" class="table table-bordered table-hover">
            <thead>
                <tr class="wpinv_cart_header_row">
                    <?php do_action( 'wpinv_receipt_items_table_header_first' ); ?>
                    <th class="wpinv_cart_item_name text-left"><?php _e( 'Item Name', 'invoicing' ); ?></th>
                    <th class="wpinv_cart_item_price text-right"><?php _e( 'Item Price', 'invoicing' ); ?></th>
                    <?php if ( $quantities_enabled ) { ?>
                    <th class="wpinv_cart_item_qty text-right"><?php _e( 'Qty', 'invoicing' ); ?></th>
                    <?php } ?>
                    <?php if ( !$zero_tax && $use_taxes ) { ?>
                    <th class="wpinv_cart_item_tax text-right"><?php echo wp_sprintf( __( 'Tax %s', 'invoicing' ), '<span class="normal small">(%)</span>' ); ?></th>
                    <?php } ?>
                    <th class="wpinv_cart_item_subtotal text-right"><?php echo __( 'Item Total', 'invoicing' ) . ' <span class="normal small">' . $tax_label . '<span>'; ?></th>
                    <?php do_action( 'wpinv_receipt_items_table_header_last' ); ?>
                </tr>
            </thead>
            <tbody>
                <?php
                    do_action( 'wpinv_receipt_items_before' );
                    if ( $cart_items ) {
                        foreach ( $cart_items as $key => $item ) {
                            $wpi_item = $item['id'] ? new WPInv_Item( $item['id'] ) : NULL;
                        ?>
                        <tr class="wpinv_cart_item" id="wpinv_cart_item_<?php echo esc_attr( $key ) . '_' . esc_attr( $item['id'] ); ?>" data-item-id="<?php echo esc_attr( $item['id'] ); ?>">
                            <?php do_action( 'wpinv_receipt_items_table_body_first', $item ); ?>
                            <td class="wpinv_cart_item_name text-left">
                                <?php
                                    if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail( $item['id'] ) ) {
                                        echo '<div class="wpinv_cart_item_image">';
                                            echo get_the_post_thumbnail( $item['id'], apply_filters( 'wpinv_checkout_image_size', array( 25,25 ) ) );
                                        echo '</div>';
                                    }
                                    $item_title = wpinv_get_cart_item_name( $item );
                                    echo '<span class="wpinv_receipt_cart_item_title">' . esc_html( $item_title ) . '</span>';
                                    
                                    if ( !empty( $wpi_item ) && $wpi_item->is_package() && !empty( $item['meta']['post_id'] ) ) {
                                        $post_link = '<a href="' . get_permalink( $item['meta']['post_id'] ) .'" target="_blank">' . (!empty($item['meta']['invoice_title']) ? $item['meta']['invoice_title'] : get_the_title( $item['meta']['post_id']) ) . '</a>';
                                        $summary = wp_sprintf( __( '%s: %s', 'invoicing' ), $wpi_item->get_cpt_singular_name(), $post_link );
                                        echo '<small class="meta">' . wpautop( wp_kses_post( $summary ) ) . '</small>';
                                    }
        
                                    do_action( 'wpinv_receipt_cart_item_title_after', $item, $key );
                                ?>
                            </td>
                            <td class="wpinv_cart_item_price text-right">
                                <?php 
                                echo wpinv_cart_item_price( $item );
                                do_action( 'wpinv_receipt_cart_item_price_after', $item, $key );
                                ?>
                            </td>
                            <?php if ( $quantities_enabled ) { ?>
                            <td class="wpinv_cart_item_qty text-right">
                                <?php
                                echo wpinv_get_cart_item_quantity( $item );
                                do_action( 'wpinv_receipt_item_quantitiy', $item, $key );
                                ?>
                            </td>
                            <?php } ?>
                            <?php if ( !$zero_tax && $use_taxes ) { ?>
                            <td class="wpinv_cart_item_tax text-right">
                                <?php
                                echo wpinv_cart_item_tax( $item );
                                do_action( 'wpinv_receipt_item_tax', $item, $key );
                                ?>
                            </td>
                            <?php } ?>
                            <td class="wpinv_cart_item_subtotal text-right">
                                <?php
                                echo wpinv_cart_item_subtotal( $item );
                                do_action( 'wpinv_receipt_item_subtotal', $item, $key );
                                ?>
                            </td>
                            <?php do_action( 'wpinv_receipt_items_table_body_last', $item, $key ); ?>
                        </tr>
                    <?php } ?>
                <?php } ?>
                <?php do_action( 'wpinv_receipt_items_middle' ); ?>
                <?php do_action( 'wpinv_receipt_items_after' ); ?>
            </tbody>
            <tfoot>
                <?php $cart_columns = wpinv_checkout_cart_columns(); if ( $zero_tax && $use_taxes ) { $cart_columns--; } ?>
                <?php if ( has_action( 'wpinv_receipt_footer_buttons' ) ) { ?>
                    <tr class="wpinv_cart_footer_row">
                        <?php do_action( 'wpinv_receipt_items_table_buttons_first', $cart_items ); ?>
                        <td colspan="<?php echo ( $cart_columns ); ?>">
                            <?php do_action( 'wpinv_receipt_footer_buttons' ); ?>
                        </td>
                        <?php do_action( 'wpinv_receipt_items_table_buttons_first', $cart_items ); ?>
                    </tr>
                <?php } ?>

                <?php if ( !$zero_tax && $use_taxes && !wpinv_prices_include_tax() && wpinv_is_cart_taxed() ) { ?>
                    <tr class="wpinv_cart_footer_row wpinv_cart_subtotal_row">
                        <?php do_action( 'wpinv_receipt_items_table_subtotal_first', $cart_items ); ?>
                        <td colspan="<?php echo ( $cart_columns - 1 ); ?>" class="wpinv_cart_subtotal_label text-right">
                            <strong><?php _e( 'Sub-Total', 'invoicing' ); ?>:</strong>
                        </td>
                        <td class="wpinv_cart_subtotal text-right">
                            <span class="wpinv_cart_subtotal_amount bold"><?php echo $invoice->get_subtotal( true ); ?></span>
                        </td>
                        <?php do_action( 'wpinv_receipt_items_table_subtotal_last', $cart_items, $invoice ); ?>
                    </tr>
                <?php } ?>

                <?php if ( !$zero_tax && $use_taxes && wpinv_is_cart_taxed() ) { ?>
                    <tr class="wpinv_cart_footer_row wpinv_cart_tax_row">
                        <?php do_action( 'wpinv_receipt_items_table_tax_first', $cart_items, $invoice ); ?>
                        <td colspan="<?php echo ( $cart_columns - 1 ); ?>" class="wpinv_cart_tax_label text-right">
                            <strong><?php _e( 'Tax', 'invoicing' ); ?>:</strong>
                        </td>
                        <td class="wpinv_cart_tax text-right">
                            <span class="wpinv_cart_tax_amount"><?php echo $invoice->get_tax( true ); ?></span>
                        </td>
                        <?php do_action( 'wpinv_receipt_items_table_tax_last', $cart_items, $invoice ); ?>
                    </tr>
                <?php } ?>
                
                <tr class="wpinv_cart_footer_row wpinv_cart_discount_row" <?php if( !wpinv_cart_has_discounts( $cart_items ) )  echo ' style="display:none;"'; ?>>
                    <?php do_action( 'wpinv_receipt_items_table_discount_first', $invoice, $invoice ); ?>
                    <td colspan="<?php echo ( $cart_columns - 1 ); ?>" class="wpinv_cart_discount_label text-right">
                        <strong><?php _e( 'Discount', 'invoicing' ); ?><?php echo esc_html( wpinv_cart_discount_code( $cart_items ) ); ?>:</strong>
                    </td>
                    <td class="wpinv_cart_discount text-right">
                        <span class="wpinv_cart_discount_amount"><?php echo $invoice->get_discount( true ); ?></span>
                    </td>
                    <?php do_action( 'wpinv_receipt_items_table_discount_last', $cart_items, $invoice ); ?>
                </tr>

                <tr class="wpinv_cart_footer_row">
                    <?php do_action( 'wpinv_receipt_items_table_footer_first', $cart_items, $invoice ); ?>
                    <td colspan="<?php echo ( $cart_columns - 1 ); ?>" class="wpinv_cart_total_label text-right">
                        <strong><?php _e( 'Total', 'invoicing' ); ?>:</strong>
                    </td>
                    <td class="wpinv_cart_total text-right">
                        <span class="wpinv_cart_amount bold"><?php echo $invoice->get_total( true ); ?></span>
                    </td>
                    <?php do_action( 'wpinv_receipt_items_table_footer_last', $cart_items, $invoice ); ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php do_action( 'wpinv_after_receipt_items', $invoice ); ?>
    <?php do_action( 'wpinv_receipt_end', $invoice ); ?>
</div>
<?php do_action( 'wpinv_after_receipt', $invoice ); ?>