<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_add_meta_boxes( $post_type, $post ) {
    global $wpi_mb_invoice;
    if ( $post_type == 'wpi_invoice' && !empty( $post->ID ) ) {
        $wpi_mb_invoice = wpinv_get_invoice( $post->ID );
    }
    
    if ( !empty( $wpi_mb_invoice ) && !$wpi_mb_invoice->has_status( array( 'draft', 'auto-draft' ) ) ) {
        add_meta_box( 'wpinv-mb-resend-invoice', __( 'Resend Invoice', 'invoicing' ), 'WPInv_Meta_Box_Details::resend_invoice', 'wpi_invoice', 'side', 'high' );
    }
    
    if ( !empty( $wpi_mb_invoice ) && $wpi_mb_invoice->is_recurring() && !wpinv_is_subscription_payment( $wpi_mb_invoice ) ) {
        add_meta_box( 'wpinv-mb-subscriptions', __( 'Subscriptions', 'invoicing' ), 'WPInv_Meta_Box_Details::subscriptions', 'wpi_invoice', 'side', 'high' );
    }
    
    if ( wpinv_is_subscription_payment( $wpi_mb_invoice ) ) {
        add_meta_box( 'wpinv-mb-renewals', __( 'Renewal Payments', 'invoicing' ), 'WPInv_Meta_Box_Details::renewals', 'wpi_invoice', 'side', 'high' );
    }
    
    add_meta_box( 'wpinv-details', __( 'Invoice Details', 'invoicing' ), 'WPInv_Meta_Box_Details::output', 'wpi_invoice', 'side', 'default' );
    add_meta_box( 'wpinv-payment-meta', __( 'Payment Meta', 'invoicing' ), 'WPInv_Meta_Box_Details::payment_meta', 'wpi_invoice', 'side', 'default' );
   
    add_meta_box( 'wpinv-address', __( 'Billing Details', 'invoicing' ), 'WPInv_Meta_Box_Billing_Details::output', 'wpi_invoice', 'normal', 'high' );
    add_meta_box( 'wpinv-items', __( 'Invoice Items', 'invoicing' ), 'WPInv_Meta_Box_Items::output', 'wpi_invoice', 'normal', 'high' );
    add_meta_box( 'wpinv-notes', __( 'Invoice Notes', 'invoicing' ), 'WPInv_Meta_Box_Notes::output', 'wpi_invoice', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'wpinv_add_meta_boxes', 30, 2 );

function wpinv_save_meta_boxes( $post_id, $post, $update = false ) {
    // $post_id and $post are required
    if ( empty( $post_id ) || empty( $post ) ) {
        return;
    }
        
    if ( !current_user_can( 'edit_post', $post_id ) || empty( $post->post_type ) ) {
        return;
    }
    
    // Dont' save meta boxes for revisions or autosaves
    if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
        return;
    }
        
    if ( $post->post_type == 'wpi_invoice' ) {
        if ( ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
            return;
        }
    
        if ( !empty( $_POST['invoice_items'] ) && isset( $_POST['wpinv_items_nonce'] ) && wp_verify_nonce( $_POST['wpinv_items_nonce'], 'wpinv_items' ) ) {
            WPInv_Meta_Box_Items::save( $post_id, $_POST, $post );
        }
        
        if ( isset( $_POST['wpinv_details_nonce'] ) && wp_verify_nonce( $_POST['wpinv_details_nonce'], 'wpinv_details' ) ) {
            WPInv_Meta_Box_Details::save( $post_id, $_POST, $post );
        }
        
        if ( isset( $_POST['wpinv_billing_details_nonce'] ) && wp_verify_nonce( $_POST['wpinv_billing_details_nonce'], 'wpinv_billing_details' ) ) {
            WPInv_Meta_Box_Billing_Details::save( $post_id, $_POST, $post );
        }
    } else if ( $post->post_type == 'wpi_item' ) {
        // verify nonce
        if ( isset( $_POST['wpinv_vat_meta_box_nonce'] ) && wp_verify_nonce( $_POST['wpinv_vat_meta_box_nonce'], 'wpinv_item_meta_box_save' ) ) {
            $fields                                 = array();
            $fields['_wpinv_price']              = 'wpinv_item_price';
            $fields['_wpinv_vat_class']          = 'wpinv_vat_class';
            $fields['_wpinv_vat_rule']           = 'wpinv_vat_rules';
            $fields['_wpinv_type']               = 'wpinv_item_type';
            $fields['_wpinv_is_recurring']       = 'wpinv_is_recurring';
            $fields['_wpinv_recurring_period']   = 'wpinv_recurring_period';
            $fields['_wpinv_recurring_interval'] = 'wpinv_recurring_interval';
            $fields['_wpinv_recurring_limit']    = 'wpinv_recurring_limit';
            
            foreach ( $fields as $field => $name ) {
                if ( isset( $_POST[ $name ] ) ) {
                    if ( $field == '_wpinv_price' ) {
                        $value = wpinv_sanitize_amount( $_POST[ $name ] );
                    } else {
                        $value = is_string( $_POST[ $name ] ) ? sanitize_text_field( $_POST[ $name ] ) : $_POST[ $name ];
                    }
                    
                    $value = apply_filters( 'wpinv_item_metabox_save_' . $field, $value, $name );
                    
                    update_post_meta( $post_id, $field, $value );
                }
            }
        }
    }
}
add_action( 'save_post', 'wpinv_save_meta_boxes', 10, 3 );

function wpinv_bulk_and_quick_edit_save( $post_id, $post, $update = false ) {
    if ( !( !empty( $_POST['action'] ) && $_POST['action'] == 'inline-save' ) ) {
        return;
    }
    
    // $post_id and $post are required
    if ( empty( $post_id ) || empty( $post ) ) {
        return;
    }
        
    if ( !current_user_can( 'edit_post', $post_id ) || empty( $post->post_type ) ) {
        return;
    }
    
    // Dont' save meta boxes for revisions or autosaves
    if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
        return;
    }

    if ( $post->post_type == 'wpi_item' ) {
        // verify nonce
        if ( isset( $_POST['_wpinv_item_price'] ) ) {
            update_post_meta( $post_id, '_wpinv_price', wpinv_sanitize_amount( $_POST['_wpinv_item_price'] ) );
        }
        
        if ( isset( $_POST['_wpinv_vat_class'] ) ) {
            update_post_meta( $post_id, '_wpinv_vat_class', sanitize_text_field( $_POST['_wpinv_vat_class'] ) );
        }

        if ( isset( $_POST['_wpinv_vat_rules'] ) ) {
            update_post_meta( $post_id, '_wpinv_vat_rule', sanitize_text_field( $_POST['_wpinv_vat_rules'] ) );
        }
        
        if ( isset( $_POST['_wpinv_item_type'] ) ) {
            update_post_meta( $post_id, '_wpinv_type', sanitize_text_field( $_POST['_wpinv_item_type'] ) );
        }
    }
}
add_action( 'save_post', 'wpinv_bulk_and_quick_edit_save', 10, 3 );

function wpinv_register_item_meta_boxes() {    
    add_meta_box( 'wpinv_field_prices', __( 'Item Price', 'invoicing' ), 'WPInv_Meta_Box_Items::prices', 'wpi_item', 'normal', 'high' );

    if ( wpinv_allow_vat_rules() ) {
        add_meta_box( 'wpinv_field_vat_rules', __( 'VAT rules type to use', 'invoicing' ), 'WPInv_Meta_Box_Items::vat_rules', 'wpi_item', 'normal', 'high' );
    }
    
    if ( wpinv_allow_vat_classes() ) {
        add_meta_box( 'wpinv_field_vat_classes', __( 'VAT rates class to use', 'invoicing' ), 'WPInv_Meta_Box_Items::vat_classes', 'wpi_item', 'normal', 'high' );
    }
    
    add_meta_box( 'wpinv_field_item_info', __( 'Item info', 'invoicing' ), 'WPInv_Meta_Box_Items::item_info', 'wpi_item', 'side', 'core' );
}

function wpinv_register_discount_meta_boxes() {
    add_meta_box( 'wpinv_discount_fields', __( 'Discount Details', 'invoicing' ), 'wpinv_discount_metabox_details', 'wpi_discount', 'normal', 'high' );
}

function wpinv_discount_metabox_details( $post ) {
    ?>
<table class="form-table">
    <tbody>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_code">Discount Code</label>
            </th>
            <td>
                <input type="text" style="width: 300px;" value="" name="wpinv_discount_code" id="wpinv_discount_code">
                <p class="description">Enter a code for this discount, such as 10PERCENT</p>
            </td>
        </tr>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_type">Type</label>
            </th>
            <td>
                <select id="wpinv_discount_type" name="wpinv_discount_type">
                <option value="percent">Percentage</option>
                <option value="fixed">Flat amount</option>
                </select>
                <p class="description">The kind of discount to apply for this discount.</p>
            </td>
        </tr>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_amount">Amount</label>
            </th>
            <td>
                <input type="text" style="width: 40px;" value="" name="wpinv_discount_amount" id="wpinv_discount_amount">
                <p style="display:none;" class="description edd-amount-description">Enter the discount amount in USD</p>
                <p class="description edd-amount-description">Enter the discount percentage. 10 = 10%</p>
            </td>
        </tr>
        <tr>
            <th valign="top" scope="row">
                <label for="edd-start">Start date</label>
            </th>
            <td>
                <input type="text" class="edd_datepicker hasDatepicker" style="width: 300px;" value="" id="edd-start" name="start">
                <p class="description">Enter the start date for this discount code in the format of mm/dd/yyyy. For no start date, leave blank. If entered, the discount can only be used after or on this date.</p>
            </td>
        </tr>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_expiration">Expiration date</label>
            </th>
            <td>
                <input type="text" class="edd_datepicker hasDatepicker" style="width: 300px;" id="wpinv_discount_expiration" name="wpinv_discount_expiration">
                <p class="description">Enter the expiration date for this discount code in the format of mm/dd/yyyy. For no expiration, leave blank</p>
            </td>
        </tr>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_min_total">Minimum Amount</label>
            </th>
            <td>
                <input type="text" style="width:40px;" value="" name="min_price" id="wpinv_discount_min_total">
                <p class="description">The minimum invoice total before this discount can be used. Leave blank for no minimum.</p>
            </td>
        </tr>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_max_uses">Max Uses</label>
            </th>
            <td>
                <input type="text" style="width:40px;" value="" name="wpinv_discount_max_uses" id="wpinv_discount_max_uses">
                <p class="description">The maximum number of times this discount can be used. Leave blank for unlimited.</p>
            </td>
        </tr>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_use_once">Use Once Per User</label>
            </th>
            <td>
                <input type="checkbox" value="1" name="wpinv_discount_use_once" id="wpinv_discount_use_once">
                <span class="description">Limit this discount to a single-use per user?</span>
            </td>
        </tr>
    </tbody>
</table>
    <?php
}