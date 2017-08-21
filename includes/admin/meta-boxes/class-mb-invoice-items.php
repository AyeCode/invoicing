<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Meta_Box_Items {
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
        $item_types         = wpinv_get_item_types();
        $is_recurring       = $invoice->is_recurring();
        $post_type_object   = get_post_type_object($invoice->post_type);
        $type_title         = $post_type_object->labels->singular_name;
        
        if (isset($item_types['package'])) {
            unset($item_types['package']);
        }
        
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
                                            <input type="text" class="regular-text" placeholder="Item name" value="" name="_wpinv_quick[name]">
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
                                                            'class'            => 'gdmbx2-text-medium wpinv-quick-vat-rule',
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
                                                            'class'            => 'gdmbx2-text-medium wpinv-quick-vat-class',
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
                                                            'class'            => 'gdmbx2-text-medium wpinv-quick-type',
                                                        ) ); ?>
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="wp-clearfix">
                                                <label class="wpi-item-actions">
                                                    <span class="input-text-wrap">
                                                        <input type="button" value="Save" class="button button-primary" id="wpinv-save-item"><input type="button" value="Cancel" class="button button-secondary" id="wpinv-cancel-item">
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
                <?php
                    if ( !$invoice->is_paid() && !$invoice->is_refunded() ) {
                    if ( !$invoice->is_recurring() ) {
                    echo wpinv_item_dropdown( array(
                        'name'             => 'wpinv_invoice_item',
                        'id'               => 'wpinv_invoice_item',
                        'with_packages'    => false,
                        'show_recurring'   => true,
                    ) );
                    ?>
                <input type="button" value="<?php echo sprintf(esc_attr__( 'Add item to %s', 'invoicing'), $type_title); ?>" class="button button-primary" id="wpinv-add-item"><input type="button" value="<?php esc_attr_e( 'Create new item', 'invoicing' );?>" class="button button-primary" id="wpinv-new-item"><?php } ?><input type="button" value="<?php esc_attr_e( 'Recalculate Totals', 'invoicing' );?>" class="button button-primary wpinv-flr" id="wpinv-recalc-totals">
                    <?php } ?>
                <?php do_action( 'wpinv_invoice_items_actions', $invoice ); ?>
            </div>
        </div>
        <?php
    }
    
    public static function prices( $post ) {        
        $symbol         = wpinv_currency_symbol();
        $position       = wpinv_currency_position();
        $item           = new WPInv_Item( $post->ID );
        
        $price          = $item->get_price();
        $is_recurring   = $item->is_recurring();
        $period         = $item->get_recurring_period();
        $interval       = absint( $item->get_recurring_interval() );
        $times          = absint( $item->get_recurring_limit() );
        $free_trial     = $item->has_free_trial();
        $trial_interval = $item->get_trial_interval();
        $trial_period   = $item->get_trial_period();
        
        $intervals      = array();
        for ( $i = 1; $i <= 90; $i++ ) {
            $intervals[$i] = $i;
        }
        
        $interval       = $interval > 0 ? $interval : 1;
        
        $class = $is_recurring ? 'wpinv-recurring-y' : 'wpinv-recurring-n';
        ?>
        <p class="wpinv-row-prices"><?php echo ( $position != 'right' ? $symbol . '&nbsp;' : '' );?><input type="text" maxlength="12" placeholder="<?php echo wpinv_sanitize_amount( 0 ); ?>" value="<?php echo $price;?>" id="wpinv_item_price" name="wpinv_item_price" class="medium-text wpi-field-price wpi-price" <?php disabled( $item->is_package(), true ); ?> /><?php echo ( $position == 'right' ? '&nbsp;' . $symbol : '' );?><input type="hidden" name="wpinv_vat_meta_box_nonce" value="<?php echo wp_create_nonce( 'wpinv_item_meta_box_save' ) ;?>" />
        <?php if ( $item->is_package() ) { ?>
        <span class="description"><?php _e( 'GD package item price can be edited only from GD payment manager.', 'invoicing' ); ?></span>
        <?php } ?>
        </p>
        <p class="wpinv-row-is-recurring">
            <label for="wpinv_is_recurring">
                <input type="checkbox" name="wpinv_is_recurring" id="wpinv_is_recurring" value="1" <?php checked( 1, $is_recurring ); ?> />
                <?php echo apply_filters( 'wpinv_is_recurring_toggle_text', __( 'Is Recurring Item?', 'invoicing' ) ); ?>
            </label>
        </p>
        <p class="wpinv-row-recurring-fields <?php echo $class;?>">
                <label class="wpinv-period" for="wpinv_recurring_period"><?php _e( 'Recurring', 'invoicing' );?> <select class="wpinv-select " id="wpinv_recurring_period" name="wpinv_recurring_period"><option value="D" data-text="<?php esc_attr_e( 'day(s)', 'invoicing' ); ?>" <?php selected( 'D', $period );?>><?php _e( 'Daily', 'invoicing' ); ?></option><option value="W" data-text="<?php esc_attr_e( 'week(s)', 'invoicing' ); ?>" <?php selected( 'W', $period );?>><?php _e( 'Weekly', 'invoicing' ); ?></option><option value="M" data-text="<?php esc_attr_e( 'month(s)', 'invoicing' ); ?>" <?php selected( 'M', $period );?>><?php _e( 'Monthly', 'invoicing' ); ?></option><option value="Y" data-text="<?php esc_attr_e( 'year(s)', 'invoicing' ); ?>" <?php selected( 'Y', $period );?>><?php _e( 'Yearly', 'invoicing' ); ?></option></select></label>
                <label class="wpinv-interval" for="wpinv_recurring_interval"> <?php _e( 'at every', 'invoicing' );?> <?php echo wpinv_html_select( array(
                    'options'          => $intervals,
                    'name'             => 'wpinv_recurring_interval',
                    'id'               => 'wpinv_recurring_interval',
                    'selected'         => $interval,
                    'show_option_all'  => false,
                    'show_option_none' => false
                ) ); ?> <span id="wpinv_interval_text"><?php _e( 'day(s)', 'invoicing' );?></span></label>
                <label class="wpinv-times" for="wpinv_recurring_limit"> <?php _e( 'for', 'invoicing' );?> <input class="small-text" type="number" value="<?php echo $times;?>" size="4" id="wpinv_recurring_limit" name="wpinv_recurring_limit" step="1" min="0"> <?php _e( 'time(s) <i>(select 0 for recurring forever until cancelled</i>)', 'invoicing' );?></label>
                <span class="clear wpi-trial-clr"></span>
                <label class="wpinv-free-trial" for="wpinv_free_trial">
                    <input type="checkbox" name="wpinv_free_trial" id="wpinv_free_trial" value="1" <?php checked( true, (bool)$free_trial ); ?> /> 
                    <?php echo __( 'Offer free trial for', 'invoicing' ); ?>
                </label>
                <label class="wpinv-trial-interval" for="wpinv_trial_interval">
                    <input class="small-text" type="number" value="<?php echo $trial_interval;?>" size="4" id="wpinv_trial_interval" name="wpinv_trial_interval" step="1" min="1"> <select class="wpinv-select" id="wpinv_trial_period" name="wpinv_trial_period"><option value="D" <?php selected( 'D', $trial_period );?>><?php _e( 'day(s)', 'invoicing' ); ?></option><option value="W" <?php selected( 'W', $trial_period );?>><?php _e( 'week(s)', 'invoicing' ); ?></option><option value="M" <?php selected( 'M', $trial_period );?>><?php _e( 'month(s)', 'invoicing' ); ?></option><option value="Y" <?php selected( 'Y', $trial_period );?>><?php _e( 'year(s)', 'invoicing' ); ?></option></select>
                </label>
        </p>
        <input type="hidden" id="_wpi_current_type" value="<?php echo wpinv_get_item_type( $post->ID ); ?>" />
        <?php do_action( 'wpinv_item_price_field', $post->ID ); ?>
        <?php
    }
    
    public static function vat_rules( $post ) {
        global $wpinv_euvat;
        
        $rule_type = $wpinv_euvat->get_item_rule( $post->ID );
        ?>
        <p><label for="wpinv_vat_rules"><strong><?php _e( 'Select how VAT rules will be applied:', 'invoicing' );?></strong></label>&nbsp;&nbsp;&nbsp;
        <?php echo wpinv_html_select( array(
                    'options'          => $wpinv_euvat->get_rules(),
                    'name'             => 'wpinv_vat_rules',
                    'id'               => 'wpinv_vat_rules',
                    'selected'         => $rule_type,
                    'show_option_all'  => false,
                    'show_option_none' => false,
                    'class'            => 'gdmbx2-text-medium wpinv-vat-rules',
                ) ); ?>
        </p>
        <p class="wpi-m0"><?php _e( 'When you select physical product rules, only consumers and businesses in your country will be charged VAT.  The VAT rate used will be the rate in your country.', 'invoicing' ); ?></p>
        <p class="wpi-m0"><?php _e( 'If you select Digital product rules, VAT will be charged at the rate that applies in the country of the consumer.  Only businesses in your country will be charged VAT.', 'invoicing' ); ?></p>
        <?php
    }
    
    public static function vat_classes( $post ) {
        global $wpinv_euvat;
        
        $vat_class = $wpinv_euvat->get_item_class( $post->ID );
        ?>
        <p><?php echo wpinv_html_select( array(
                    'options'          => $wpinv_euvat->get_all_classes(),
                    'name'             => 'wpinv_vat_class',
                    'id'               => 'wpinv_vat_class',
                    'selected'         => $vat_class,
                    'show_option_all'  => false,
                    'show_option_none' => false,
                    'class'            => 'gdmbx2-text-medium wpinv-vat-class',
                ) ); ?>
        </p>
        <p class="wpi-m0"><?php _e( 'Select the VAT rate class to use for this invoice item.', 'invoicing' ); ?></p>
        <?php
    }
    
    public static function item_info( $post ) {
        $item_type = wpinv_get_item_type( $post->ID );
        do_action( 'wpinv_item_info_metabox_before', $post );
        ?>
        <p><label for="wpinv_item_type"><strong><?php _e( 'Type:', 'invoicing' );?></strong></label>&nbsp;&nbsp;&nbsp;
        <?php echo wpinv_html_select( array(
                    'options'          => wpinv_get_item_types(),
                    'name'             => 'wpinv_item_type',
                    'id'               => 'wpinv_item_type',
                    'selected'         => $item_type,
                    'show_option_all'  => false,
                    'show_option_none' => false,
                    'class'            => 'gdmbx2-text-medium wpinv-item-type',
                    //'disabled'         => $item_type == 'package' ? true : false,
                ) ); ?>
        </p>
        <p class="wpi-m0"><?php _e( 'Select item type.', 'invoicing' );?><br><?php _e( '<b>Standard:</b> Standard item type', 'invoicing' );?><br><?php _e( '<b>Fee:</b> Like Registration Fee, Sign up Fee etc.', 'invoicing' );?></p>
        <?php
        do_action( 'wpinv_item_info_metabox_after', $post );
    }
    
    public static function meta_values( $post ) {
        $meta_keys = apply_filters( 'wpinv_show_meta_values_for_keys', array(
            'type',
            'custom_id'
        ) );
        
        if ( empty( $meta_keys ) ) {
            return;
        }
        
        do_action( 'wpinv_meta_values_metabox_before', $post );
        
        foreach ( $meta_keys as $meta_key ) {
            ?>
            <p class="wpi-mtb05"><label><strong><?php echo $meta_key; ?></strong>: <?php echo get_post_meta( $post->ID, '_wpinv_' . $meta_key, true ); ?></label></p>
            <?php 
        }
        
        do_action( 'wpinv_meta_values_metabox_after', $post );
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
        //$discounts      = sanitize_text_field( $data['wpinv_discounts'] );
        //$discount       = sanitize_text_field( $data['wpinv_discount'] );
        
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
        //$invoice->set( 'number', $number );
        //$invoice->set( 'discounts', $discounts );
        //$invoice->set( 'discount', $discount );
        $invoice->set( 'ip', $ip );
        $invoice->old_status = $_POST['original_post_status'];
        $invoice->currency = wpinv_get_currency();
        if ( !empty( $data['wpinv_gateway'] ) ) {
            $invoice->set( 'gateway', sanitize_text_field( $data['wpinv_gateway'] ) );
        }
        $saved = $invoice->save();
        
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
