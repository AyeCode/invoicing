<?php

/**
 * Item Data
 *
 * Display the item data meta box.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WPInv_Meta_Box_Items Class.
 */
class WPInv_Meta_Box_Items {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {
        global $wpinv_euvat, $ajax_cart_details;

        $post_id            = !empty( $post->ID ) ? $post->ID : 0;
        $invoice            = new WPInv_Invoice( $post_id );
        $ajax_cart_details  = $invoice->get_cart_details();
        $subtotal           = $invoice->get_subtotal( true );
        $discount_raw       = $invoice->get_discount();
        $discount           = wpinv_price( $discount_raw, $invoice->get_currency() );
        $discounts          = $discount_raw > 0 ? $invoice->get_discounts() : '';
        $tax                = $invoice->get_tax( true );
        $total              = $invoice->get_total( true );
        $item_quantities    = wpinv_item_quantities_enabled();
        $use_taxes          = wpinv_use_taxes();
        if ( !$use_taxes && (float)$invoice->get_tax() > 0 ) {
            $use_taxes = true;
        }
        $item_types         = apply_filters( 'wpinv_item_types_for_quick_add_item', wpinv_get_item_types(), $post );
        $is_recurring       = $invoice->is_recurring();
        $post_type_object   = get_post_type_object($invoice->post_type);
        $type_title         = $post_type_object->labels->singular_name;

        $cols = 5;
        if ( $item_quantities ) {
            $cols++;
        }
        if ( $use_taxes ) {
            $cols++;
        }
        $class = '';
        if ( $invoice->is_paid() ) {
            $class .= ' wpinv-paid';
        }
        if ( $invoice->is_refunded() ) {
            $class .= ' wpinv-refunded';
        }
        if ( $is_recurring ) {
            $class .= ' wpi-recurring';
        }
        ?>
        <div class="wpinv-items-wrap<?php echo $class; ?>" id="wpinv_items_wrap" data-status="<?php echo $invoice->status; ?>">
            <table id="wpinv_items" class="wpinv-items" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th class="id"><?php _e( 'ID', 'invoicing' );?></th>
                        <th class="title"><?php _e( 'Item', 'invoicing' );?></th>
                        <th class="price"><?php _e( 'Price', 'invoicing' );?></th>
                        <?php if ( $item_quantities ) { ?>
                        <th class="qty"><?php _e( 'Qty', 'invoicing' );?></th>
                        <?php } ?>
                        <th class="total"><?php _e( 'Total', 'invoicing' );?></th>
                        <?php if ( $use_taxes ) { ?>
                        <th class="tax"><?php _e( 'Tax (%)', 'invoicing' );?></th>
                        <?php } ?>
                        <th class="action"></th>
                    </tr>
                </thead>
                <tbody class="wpinv-line-items">
                    <?php echo wpinv_admin_get_line_items( $invoice ); ?>
                </tbody>
                <tfoot class="wpinv-totals">
                    <tr>
                        <td colspan="<?php echo $cols; ?>" style="padding:0;border:0">
                            <div id="wpinv-quick-add">
                                <table cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="id">
                                        </td>
                                        <td class="title">
                                            <input type="text" class="regular-text" placeholder="<?php _e( 'Item Name', 'invoicing' ); ?>" value="" name="_wpinv_quick[name]">
                                            <?php if ( $wpinv_euvat->allow_vat_rules() ) { ?>
                                            <div class="wp-clearfix">
                                                <label class="wpi-vat-rule">
                                                    <span class="title"><?php _e( 'VAT rule type', 'invoicing' );?></span>
                                                    <span class="input-text-wrap">
                                                        <?php echo wpinv_html_select( array(
                                                            'options'          => $wpinv_euvat->get_rules(),
                                                            'name'             => '_wpinv_quick[vat_rule]',
                                                            'id'               => '_wpinv_quick_vat_rule',
                                                            'show_option_all'  => false,
                                                            'show_option_none' => false,
                                                            'class'            => 'gdmbx2-text-medium wpinv-quick-vat-rule wpi_select2',
                                                        ) ); ?>
                                                    </span>
                                                </label>
                                            </div>
                                            <?php } if ( $wpinv_euvat->allow_vat_classes() ) { ?>
                                            <div class="wp-clearfix">
                                                <label class="wpi-vat-class">
                                                    <span class="title"><?php _e( 'VAT class', 'invoicing' );?></span>
                                                    <span class="input-text-wrap">
                                                        <?php echo wpinv_html_select( array(
                                                            'options'          => $wpinv_euvat->get_all_classes(),
                                                            'name'             => '_wpinv_quick[vat_class]',
                                                            'id'               => '_wpinv_quick_vat_class',
                                                            'show_option_all'  => false,
                                                            'show_option_none' => false,
                                                            'class'            => 'gdmbx2-text-medium wpinv-quick-vat-class wpi_select2',
                                                        ) ); ?>
                                                    </span>
                                                </label>
                                            </div>
                                            <?php } ?>
                                            <div class="wp-clearfix">
                                                <label class="wpi-item-type">
                                                    <span class="title"><?php _e( 'Item type', 'invoicing' );?></span>
                                                    <span class="input-text-wrap">
                                                        <?php echo wpinv_html_select( array(
                                                            'options'          => $item_types,
                                                            'name'             => '_wpinv_quick[type]',
                                                            'id'               => '_wpinv_quick_type',
                                                            'selected'         => 'custom',
                                                            'show_option_all'  => false,
                                                            'show_option_none' => false,
                                                            'class'            => 'gdmbx2-text-medium wpinv-quick-type wpi_select2',
                                                        ) ); ?>
                                                    </span>
                                                </label>
                                            </div>

                                            <div class="wp-clearfix">
                                                <?php 
                                                    echo wpinv_html_textarea( array(
                                                        'name'  => '_wpinv_quick[excerpt]',
                                                        'id'    => '_wpinv_quick_excerpt',
                                                        'value' => '',
                                                        'class' => 'large-text',
                                                        'label' => __( 'Item description', 'invoicing' ),
                                                    ) ); 
                                                ?>
                                            </div>

                                            <div class="wp-clearfix">
                                                <label class="wpi-item-actions">
                                                    <span class="input-text-wrap">
                                                        <input type="button" value="<?php esc_attr_e( 'Add', 'invoicing' ); ?>" class="button button-primary" id="wpinv-save-item"><input type="button" value="Cancel" class="button button-secondary" id="wpinv-cancel-item">
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="price"><input type="text" placeholder="0.00" class="wpi-field-price wpi-price" name="_wpinv_quick[price]" /></td>
                                        <?php if ( $item_quantities ) { ?>
                                        <td class="qty"><input type="number" class="small-text" step="1" min="1" value="1" name="_wpinv_quick[qty]" /></td>
                                        <?php } ?>
                                        <td class="total"></td>
                                        <?php if ( $use_taxes ) { ?>
                                        <td class="tax"></td>
                                        <?php } ?>
                                        <td class="action"></td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr class="clear">
                        <td colspan="<?php echo $cols; ?>"></td>
                    </tr>
                    <tr class="totals">
                        <td colspan="<?php echo ( $cols - 4 ); ?>"></td>
                        <td colspan="4">
                            <table cellspacing="0" cellpadding="0">
                                <tr class="subtotal">
                                    <td class="name"><?php _e( 'Sub Total:', 'invoicing' );?></td>
                                    <td class="total"><?php echo $subtotal;?></td>
                                    <td class="action"></td>
                                </tr>
                                <tr class="discount">
                                    <td class="name"><?php wpinv_get_discount_label( wpinv_discount_code( $invoice->ID ) ); ?>:</td>
                                    <td class="total"><?php echo wpinv_discount( $invoice->ID, true, true ); ?></td>
                                    <td class="action"></td>
                                </tr>
                                <?php if ( $use_taxes ) { ?>
                                <tr class="tax">
                                    <td class="name"><?php _e( 'Tax:', 'invoicing' );?></td>
                                    <td class="total"><?php echo $tax;?></td>
                                    <td class="action"></td>
                                </tr>
                                <?php } ?>
                                <tr class="total">
                                    <td class="name"><?php echo apply_filters( 'wpinv_invoice_items_total_label', __( 'Invoice Total:', 'invoicing' ), $invoice );?></td>
                                    <td class="total"><?php echo $total;?></td>
                                    <td class="action"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tfoot>
            </table>
            <div class="wpinv-actions">
                <?php ob_start(); ?>
                <?php
                    if ( !$invoice->is_paid() && !$invoice->is_refunded() ) {
                        if ( !$invoice->is_recurring() ) {
                            echo wpinv_item_dropdown( array(
                                'name'             => 'wpinv_invoice_item',
                                'id'               => 'wpinv_invoice_item',
                                'show_recurring'   => true,
                                'class'            => 'wpi_select2',
                            ) );
                    ?>
                <input type="button" value="<?php echo sprintf(esc_attr__( 'Add item to %s', 'invoicing'), $type_title); ?>" class="button button-primary" id="wpinv-add-item"><input type="button" value="<?php esc_attr_e( 'Create new item', 'invoicing' );?>" class="button button-primary" id="wpinv-new-item"><?php } ?><input type="button" value="<?php esc_attr_e( 'Recalculate Totals', 'invoicing' );?>" class="button button-primary wpinv-flr" id="wpinv-recalc-totals">
                    <?php } ?>
                <?php do_action( 'wpinv_invoice_items_actions', $invoice ); ?>
                <?php $item_actions = ob_get_clean(); echo apply_filters( 'wpinv_invoice_items_actions_content', $item_actions, $invoice, $post ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display the items buy now shortcode.
     */
    public static function shortcode( $post ) {

        if ( ! is_numeric( $post ) ) {
            $post = $post->ID;
        }

        echo "<input type='text' style='min-width: 100%; font-size: small;' value='[getpaid item=$post]' disabled>";
    }

    public static function save( $post_id, $data, $post ) {
        $invoice        = new WPInv_Invoice( $post_id );

        // Billing
        $first_name     = sanitize_text_field( $data['wpinv_first_name'] );
        $last_name      = sanitize_text_field( $data['wpinv_last_name'] );
        $company        = sanitize_text_field( $data['wpinv_company'] );
        $vat_number     = sanitize_text_field( $data['wpinv_vat_number'] );
        $phone          = sanitize_text_field( $data['wpinv_phone'] );
        $address        = sanitize_text_field( $data['wpinv_address'] );
        $city           = sanitize_text_field( $data['wpinv_city'] );
        $zip            = sanitize_text_field( $data['wpinv_zip'] );
        $country        = sanitize_text_field( $data['wpinv_country'] );
        $state          = sanitize_text_field( $data['wpinv_state'] );

        // Details
        $status         = sanitize_text_field( $data['wpinv_status'] );
        $old_status     = !empty( $data['original_post_status'] ) ? sanitize_text_field( $data['original_post_status'] ) : $status;
        $number         = sanitize_text_field( $data['wpinv_number'] );
        $due_date       = isset( $data['wpinv_due_date'] ) ? sanitize_text_field( $data['wpinv_due_date'] ) : '';
        $date_created   = isset( $data['date_created'] ) ? sanitize_text_field( $data['date_created'] ) : '';
        //$discounts      = sanitize_text_field( $data['wpinv_discounts'] );
        //$discount       = sanitize_text_field( $data['wpinv_discount'] );

        $disable_taxes = 0;

        if ( ! empty( $data['disable_taxes'] ) ) {
            $disable_taxes = 1;
        }

        $ip             = $invoice->get_ip() ? $invoice->get_ip() : wpinv_get_ip();

        $invoice->set( 'due_date', $due_date );
        $invoice->set( 'first_name', $first_name );
        $invoice->set( 'last_name', $last_name );
        $invoice->set( 'company', $company );
        $invoice->set( 'vat_number', $vat_number );
        $invoice->set( 'phone', $phone );
        $invoice->set( 'address', $address );
        $invoice->set( 'city', $city );
        $invoice->set( 'zip', $zip );
        $invoice->set( 'country', $country );
        $invoice->set( 'state', $state );
        $invoice->set( 'status', $status );
        $invoice->set( 'set', $status );
        //$invoice->set( 'number', $number );
        //$invoice->set( 'discounts', $discounts );
        //$invoice->set( 'discount', $discount );
        $invoice->set( 'disable_taxes', $disable_taxes );
        $invoice->set( 'ip', $ip );
        $invoice->old_status = $_POST['original_post_status'];
        
        $currency = $invoice->get_currency();
        if ( ! empty( $data['wpinv_currency'] ) ) {
            $currency = sanitize_text_field( $data['wpinv_currency'] );
        }

        if ( empty( $currency ) ) {
            $currency = wpinv_get_currency();
        }

        if ( ! $invoice->is_paid() ) {
            $invoice->currency = $currency;
        }

        if ( !empty( $data['wpinv_gateway'] ) ) {
            $invoice->set( 'gateway', sanitize_text_field( $data['wpinv_gateway'] ) );
        }
        $saved = $invoice->save();

        $emails = '';
        if ( ! empty( $data['wpinv_cc'] ) ) {
            $emails = wpinv_clean( $data['wpinv_cc'] );
        }
        update_post_meta( $invoice->ID, 'wpinv_email_cc', $emails );

        if ( ! empty( $date_created ) && strtotime( $date_created, current_time( 'timestamp' ) ) ) {

            $time = strtotime( $date_created, current_time( 'timestamp' ) );
            $date = date( 'Y-m-d H:i:s', $time );
            $date_gmt = get_gmt_from_date( $date );

            wp_update_post(
                array(
                    'ID'            => $invoice->ID,
                    'post_date'     => $date,
                    'post_date_gmt' => $date_gmt,
                    'edit_date'     => true,
                )
            );

            $invoice->date = $date;
        }

        // Check for payment notes
        if ( !empty( $data['invoice_note'] ) ) {
            $note               = wp_kses( $data['invoice_note'], array() );
            $note_type          = sanitize_text_field( $data['invoice_note_type'] );
            $is_customer_note   = $note_type == 'customer' ? 1 : 0;

            wpinv_insert_payment_note( $invoice->ID, $note, $is_customer_note );
        }

        // Update user address if empty.
        if ( $saved && !empty( $invoice ) ) {
            if ( $user_id = $invoice->get_user_id() ) {
                $user_address = wpinv_get_user_address( $user_id, false );

                if (empty($user_address['first_name'])) {
                    update_user_meta( $user_id, '_wpinv_first_name', $first_name );
                    update_user_meta( $user_id, '_wpinv_last_name', $last_name );
                } else if (empty($user_address['last_name']) && $user_address['first_name'] == $first_name) {
                    update_user_meta( $user_id, '_wpinv_last_name', $last_name );
                }

                if (empty($user_address['address']) || empty($user_address['city']) || empty($user_address['state']) || empty($user_address['country'])) {
                    update_user_meta( $user_id, '_wpinv_address', $address );
                    update_user_meta( $user_id, '_wpinv_city', $city );
                    update_user_meta( $user_id, '_wpinv_state', $state );
                    update_user_meta( $user_id, '_wpinv_country', $country );
                    update_user_meta( $user_id, '_wpinv_zip', $zip );
                    update_user_meta( $user_id, '_wpinv_phone', $phone );
                }
            }

            do_action( 'wpinv_invoice_metabox_saved', $invoice );
        }

        return $saved;
    }
}
