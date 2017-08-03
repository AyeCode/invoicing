<?php
/**
 *  This template is used to display the Checkout page when items are in the cart
 */

global $wpinv_euvat, $post, $ajax_cart_details, $wpi_cart_columns, $wpi_session;
$invoice            = wpinv_get_invoice_cart();
$cart_items         = !empty( $ajax_cart_details ) ? $ajax_cart_details : wpinv_get_cart_content_details();
$quantities_enabled = wpinv_item_quantities_enabled();
$use_taxes          = wpinv_use_taxes();
$tax_label          = $wpinv_euvat->tax_label();
$tax_title          = $use_taxes ? ( wpinv_prices_include_tax() ? wp_sprintf( __( '(%s Incl.)', 'invoicing' ), $tax_label ) : wp_sprintf( __( '(%s Excl.)', 'invoicing' ), $tax_label ) ) : '';
?>
<table id="wpinv_checkout_cart" class="table table-bordered table-hover">
    <thead>
        <tr class="wpinv_cart_header_row">
            <?php do_action( 'wpinv_checkout_table_header_first' ); ?>
            <th class="wpinv_cart_item_name text-left"><?php _e( 'Item Name', 'invoicing' ); ?></th>
            <th class="wpinv_cart_item_price text-right"><?php _e( 'Item Price', 'invoicing' ); ?></th>
            <?php if ( $quantities_enabled ) { ?>
            <th class="wpinv_cart_item_qty text-right"><?php _e( 'Qty', 'invoicing' ); ?></th>
            <?php } ?>
            <?php if ( $use_taxes ) { ?>
            <th class="wpinv_cart_item_tax text-right"><?php echo $tax_label . ' <span class="normal small">(%)</span>'; ?></th>
            <?php } ?>
            <th class="wpinv_cart_item_subtotal text-right"><?php echo __( 'Item Total', 'invoicing' ) . ' <span class="normal small">' . $tax_title . '<span>'; ?></th>
            <?php do_action( 'wpinv_checkout_table_header_last' ); ?>
        </tr>
    </thead>
    <tbody>
        <?php
            do_action( 'wpinv_cart_items_before' );
            
            if ( $cart_items ) {
                foreach ( $cart_items as $key => $item ) {
                    $wpi_item = !empty( $item['id'] ) ? new WPInv_Item( $item['id'] ) : NULL;
                ?>
                <tr class="wpinv_cart_item" id="wpinv_cart_item_<?php echo esc_attr( $key ) . '_' . esc_attr( $item['id'] ); ?>" data-item-id="<?php echo esc_attr( $item['id'] ); ?>">
                    <?php do_action( 'wpinv_checkout_table_body_first', $item ); ?>
                    <td class="wpinv_cart_item_name text-left">
                        <?php
                            if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail( $item['id'] ) ) {
                                echo '<div class="wpinv_cart_item_image">';
                                    echo get_the_post_thumbnail( $item['id'], apply_filters( 'wpinv_checkout_image_size', array( 25,25 ) ) );
                                echo '</div>';
                            }
                            $item_title = esc_html( wpinv_get_cart_item_name( $item ) ) . wpinv_get_item_suffix( $wpi_item );
                            echo '<span class="wpinv_checkout_cart_item_title">' . $item_title . '</span>';
                            if ( !empty( $wpi_item ) && $wpi_item->is_package() && !empty( $item['meta']['post_id'] ) ) {
                                $post_link = !empty( $item['meta']['invoice_title'] ) ? $item['meta']['invoice_title'] : get_the_title( $item['meta']['post_id'] );
                                $summary = wp_sprintf( __( '%s: %s', 'invoicing' ), $wpi_item->get_custom_singular_name(), $post_link );
                                echo '<small class="meta">' . wpautop( wp_kses_post( $summary ) ) . '</small>';
                            }
                            do_action( 'wpinv_checkout_cart_item_title_after', $item, $key );
                        ?>
                    </td>
                    <td class="wpinv_cart_item_price text-right">
                        <?php 
                        echo wpinv_cart_item_price( $item );
                        do_action( 'wpinv_checkout_cart_item_price_after', $item, $key );
                        ?>
                    </td>
                    <?php if ( $quantities_enabled ) { ?>
                    <td class="wpinv_cart_item_qty text-right">
                        <?php
                        echo wpinv_get_cart_item_quantity( $item );
                        do_action( 'wpinv_cart_item_quantitiy', $item, $key );
                        ?>
                    </td>
                    <?php } ?>
                    <?php if ( $use_taxes ) { ?>
                    <td class="wpinv_cart_item_tax text-right">
                        <?php
                        echo wpinv_cart_item_tax( $item );
                        //echo wpinv_get_cart_item_tax( $wpi_item->ID, $subtotal = '', $options = array() );
                        do_action( 'wpinv_cart_item_tax', $item, $key );
                        ?>
                    </td>
                    <?php } ?>
                    <td class="wpinv_cart_item_subtotal text-right">
                        <?php
                        echo wpinv_cart_item_subtotal( $item );
                        do_action( 'wpinv_cart_item_subtotal', $item, $key );
                        ?>
                    </td>
                    <?php do_action( 'wpinv_checkout_table_body_last', $item, $key ); ?>
                </tr>
            <?php } ?>
        <?php } ?>
        <?php do_action( 'wpinv_cart_items_middle' ); ?>
        <?php do_action( 'wpinv_cart_items_after' ); ?>
    </tbody>
    <tfoot>
        <?php $cart_columns = wpinv_checkout_cart_columns(); ?>
        <?php if ( has_action( 'wpinv_cart_footer_buttons' ) ) { ?>
            <tr class="wpinv_cart_footer_row">
                <?php do_action( 'wpinv_checkout_table_buttons_first', $cart_items ); ?>
                <td colspan="<?php echo ( $cart_columns ); ?>">
                    <?php do_action( 'wpinv_cart_footer_buttons' ); ?>
                </td>
                <?php do_action( 'wpinv_checkout_table_buttons_first', $cart_items ); ?>
            </tr>
        <?php } ?>

        <?php if ( $use_taxes && !wpinv_prices_include_tax() ) { ?>
            <tr class="wpinv_cart_footer_row wpinv_cart_subtotal_row"<?php if ( !wpinv_is_cart_taxed() ) echo ' style="display:none;"'; ?>>
                <?php do_action( 'wpinv_checkout_table_subtotal_first', $cart_items ); ?>
                <td colspan="<?php echo ( $cart_columns - 1 ); ?>" class="wpinv_cart_subtotal_label text-right">
                    <strong><?php _e( 'Sub-Total', 'invoicing' ); ?>:</strong>
                </td>
                <td class="wpinv_cart_subtotal text-right">
                    <span class="wpinv_cart_subtotal_amount bold"><?php echo wpinv_cart_subtotal( $cart_items ); ?></span>
                </td>
                <?php do_action( 'wpinv_checkout_table_subtotal_last', $cart_items ); ?>
            </tr>
        <?php } ?>
        
        <?php $wpi_cart_columns = $cart_columns - 1; wpinv_cart_discounts_html( $cart_items ); ?>

        <?php if ( $use_taxes ) { ?>
            <tr class="wpinv_cart_footer_row wpinv_cart_tax_row"<?php if( !wpinv_is_cart_taxed() ) echo ' style="display:none;"'; ?>>
                <?php do_action( 'wpinv_checkout_table_tax_first' ); ?>
                <td colspan="<?php echo ( $cart_columns - 1 ); ?>" class="wpinv_cart_tax_label text-right">
                    <strong><?php echo $tax_label; ?>:</strong>
                </td>
                <td class="wpinv_cart_tax text-right">
                    <span class="wpinv_cart_tax_amount" data-tax="<?php echo wpinv_get_cart_tax( $cart_items ); ?>"><?php echo esc_html( wpinv_cart_tax( $cart_items ) ); ?></span>
                </td>
                <?php do_action( 'wpinv_checkout_table_tax_last' ); ?>
            </tr>
        <?php } ?>

        <tr class="wpinv_cart_footer_row wpinv_cart_total_row">
            <?php do_action( 'wpinv_checkout_table_footer_first' ); ?>
            <td colspan="<?php echo ( $cart_columns - 1 ); ?>" class="wpinv_cart_total_label text-right">
                <?php echo apply_filters( 'wpinv_cart_total_label', '<strong>' . __( 'Total', 'invoicing' ) . ':</strong>', $invoice ); ?>
            </td>
            <td class="wpinv_cart_total text-right">
                <span class="wpinv_cart_amount bold" data-subtotal="<?php echo wpinv_get_cart_total( $cart_items ); ?>" data-total="<?php echo wpinv_get_cart_total( NULL, NULL, $invoice ); ?>"><?php wpinv_cart_total( $cart_items, true, $invoice ); ?></span>
            </td>
            <?php do_action( 'wpinv_checkout_table_footer_last' ); ?>
        </tr>
    </tfoot>
</table>
