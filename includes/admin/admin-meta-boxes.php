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
    
    if ( !empty( $wpi_mb_invoice ) && $wpi_mb_invoice->is_recurring() && $wpi_mb_invoice->is_parent() ) {
        add_meta_box( 'wpinv-mb-subscriptions', __( 'Subscriptions', 'invoicing' ), 'WPInv_Meta_Box_Details::subscriptions', 'wpi_invoice', 'side', 'high' );
    }
    
    if ( wpinv_is_subscription_payment( $wpi_mb_invoice ) ) {
        add_meta_box( 'wpinv-mb-renewals', __( 'Renewal Payment', 'invoicing' ), 'WPInv_Meta_Box_Details::renewals', 'wpi_invoice', 'side', 'high' );
    }
    
    add_meta_box( 'wpinv-details', __( 'Invoice Details', 'invoicing' ), 'WPInv_Meta_Box_Details::output', 'wpi_invoice', 'side', 'default' );
    add_meta_box( 'wpinv-payment-meta', __( 'Payment Meta', 'invoicing' ), 'WPInv_Meta_Box_Details::payment_meta', 'wpi_invoice', 'side', 'default' );

    add_meta_box( 'wpinv-payment-form-design', __( 'Payment Form', 'invoicing' ), 'GetPaid_Meta_Box_Payment_Form::output', 'wpi_payment_form', 'normal' );
    add_meta_box( 'wpinv-payment-form-shortcode', __( 'Shortcode', 'invoicing' ), 'WPInv_Meta_Box_Payment_Form::output_shortcode', 'wpi_payment_form', 'side' );
   
    add_meta_box( 'wpinv-address', __( 'Billing Details', 'invoicing' ), 'WPInv_Meta_Box_Billing_Details::output', 'wpi_invoice', 'normal', 'high' );
    add_meta_box( 'wpinv-items', __( 'Invoice Items', 'invoicing' ), 'WPInv_Meta_Box_Items::output', 'wpi_invoice', 'normal', 'high' );
    add_meta_box( 'wpinv-notes', __( 'Invoice Notes', 'invoicing' ), 'WPInv_Meta_Box_Notes::output', 'wpi_invoice', 'normal', 'high' );
    
    if ( ! empty( $post->ID ) && get_post_meta( $post->ID, 'payment_form_data', true ) ) {
        add_meta_box( 'wpinv-invoice-payment-form-details', __( 'Payment Form Details', 'invoicing' ), 'WPInv_Meta_Box_Payment_Form::output_details', 'wpi_invoice', 'side', 'high' );
    }

	remove_meta_box('wpseo_meta', 'wpi_invoice', 'normal');
}
add_action( 'add_meta_boxes', 'wpinv_add_meta_boxes', 30, 2 );

/**
 * Saves meta boxes.
 */
function wpinv_save_meta_boxes( $post_id, $post ) {
    remove_action( 'save_post', __FUNCTION__ );

    // $post_id and $post are required.
    if ( empty( $post_id ) || empty( $post ) ) {
        return;
    }

    // Ensure that this user can edit the post.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Dont' save meta boxes for revisions or autosaves
    if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
        return;
    }

    // Do not save for ajax requests.
    if ( ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
        return;
    }

    $post_types_map = array(
        'wpi_invoice'      => 'WPInv_Meta_Box_Items',
        'wpi_quote'        => 'WPInv_Meta_Box_Items',
        'wpi_item'         => 'GetPaid_Meta_Box_Item_Details',
        'wpi_payment_form' => 'GetPaid_Meta_Box_Item_Details',
    );

    // Is this our post type?
    if ( empty( $post->post_type ) || ! isset( $post_types_map[ $post->post_type ] ) ) {
        return;
    }

    // Save the post.
    $class = $post_types_map[ $post->post_type ];
    $class::save( $post_id, $_POST, $post );

}
add_action( 'save_post', 'wpinv_save_meta_boxes', 10, 3 );

function wpinv_register_item_meta_boxes() {    
    global $wpinv_euvat;

    // Item details metabox.
    add_meta_box( 'wpinv_item_details', __( 'Item Details', 'invoicing' ), 'GetPaid_Meta_Box_Item_Details::output', 'wpi_item', 'normal', 'high' );

    // If taxes are enabled, register the tax metabox.
    if ( $wpinv_euvat->allow_vat_rules() || $wpinv_euvat->allow_vat_classes() ) {
        add_meta_box( 'wpinv_item_vat', __( 'VAT / Tax', 'invoicing' ), 'GetPaid_Meta_Box_Item_VAT::output', 'wpi_item', 'normal', 'high' );
    }

    // Item info.
    add_meta_box( 'wpinv_field_item_info', __( 'Item info', 'invoicing' ), 'GetPaid_Meta_Box_Item_Info::output', 'wpi_item', 'side', 'core' );
    
}

function wpinv_register_discount_meta_boxes() {
    add_meta_box( 'wpinv_discount_fields', __( 'Discount Details', 'invoicing' ), 'wpinv_discount_metabox_details', 'wpi_discount', 'normal', 'high' );
}

function wpinv_discount_metabox_details( $post ) {
    $discount_id    = $post->ID;
    $discount       = wpinv_get_discount( $discount_id );
    
    $type               = wpinv_get_discount_type( $discount_id );
    $item_reqs          = wpinv_get_discount_item_reqs( $discount_id );
    $excluded_items     = wpinv_get_discount_excluded_items( $discount_id );
    $min_total          = wpinv_get_discount_min_total( $discount_id );
    $max_total          = wpinv_get_discount_max_total( $discount_id );
    $max_uses           = wpinv_get_discount_max_uses( $discount_id );
    $single_use         = wpinv_discount_is_single_use( $discount_id );
    $recurring          = (bool)wpinv_discount_is_recurring( $discount_id );
    $start_date         = wpinv_get_discount_start_date( $discount_id );
    $expiration_date    = wpinv_get_discount_expiration( $discount_id );
    
    if ( ! empty( $start_date ) && strpos( $start_date, '0000' ) === false ) {
        $start_time         = strtotime( $start_date );
        $start_h            = date_i18n( 'H', $start_time );
        $start_m            = date_i18n( 'i', $start_time );
        $start_date         = date_i18n( 'Y-m-d', $start_time );
    } else {
        $start_h            = '00';
        $start_m            = '00';
    }

    if ( ! empty( $expiration_date ) && strpos( $expiration_date, '0000' ) === false ) {
        $expiration_time    = strtotime( $expiration_date );
        $expiration_h       = date_i18n( 'H', $expiration_time );
        $expiration_m       = date_i18n( 'i', $expiration_time );
        $expiration_date    = date_i18n( 'Y-m-d', $expiration_time );
    } else {
        $expiration_h       = '23';
        $expiration_m       = '59';
    }
    
    $min_total          = $min_total > 0 ? $min_total : '';
    $max_total          = $max_total > 0 ? $max_total : '';
    $max_uses           = $max_uses > 0 ? $max_uses : '';
?>
<?php do_action( 'wpinv_discount_form_top', $post ); ?>
<?php wp_nonce_field( 'wpinv_discount_metabox_nonce', 'wpinv_discount_metabox_nonce' ); ;?>
<table class="form-table wpi-form-table">
    <tbody>
        <?php do_action( 'wpinv_discount_form_first', $post ); ?>
        <?php do_action( 'wpinv_discount_form_before_code', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_code"><?php _e( 'Discount Code', 'invoicing' ); ?></label>
            </th>
            <td>
                <input type="text" name="code" id="wpinv_discount_code" class="medium-text" value="<?php echo esc_attr( wpinv_get_discount_code( $discount_id ) ); ?>" required>
                <p class="description"><?php _e( 'Enter a code for this discount, such as 10OFF', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_type', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_type"><?php _e( 'Discount Type', 'invoicing' ); ?></label>
            </th>
            <td>
                <select id="wpinv_discount_type" name="type" class="medium-text wpi_select2">
                    <?php foreach ( wpinv_get_discount_types() as $value => $label ) { ?>
                    <option value="<?php echo $value ;?>" <?php selected( $type, $value ); ?>><?php echo $label; ?></option>
                    <?php } ?>
                </select>
                <p class="description"><?php _e( 'The kind of discount to apply for this discount.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_amount', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_amount"><?php _e( 'Amount', 'invoicing' ); ?></label>
            </th>
            <td>
                <input type="text" name="amount" id="wpinv_discount_amount" class="wpi-field-price wpi-price" value="<?php echo esc_attr( wpinv_get_discount_amount( $discount_id ) ); ?>" required> <font class="wpi-discount-p">%</font><font class="wpi-discount-f" style="display:none;"><?php echo wpinv_currency_symbol() ;?></font>
                <p style="display:none;" class="description"><?php _e( 'Enter the discount amount in USD', 'invoicing' ); ?></p>
                <p class="description"><?php _e( 'Enter the discount value. Ex: 10', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_items', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_items"><?php _e( 'Items', 'invoicing' ); ?></label>
            </th>
            <td>
                <p><?php echo wpinv_item_dropdown( array(
                        'name'              => 'items[]',
                        'id'                => 'items',
                        'selected'          => $item_reqs,
                        'multiple'          => true,
                        'class'             => 'medium-text wpi_select2',
                        'placeholder'       => __( 'Select one or more Items', 'invoicing' ),
                        'show_recurring'    => true,
                    ) ); ?>
                </p>
                <p class="description"><?php _e( 'Items which need to be in the cart to use this discount or, for "Item Discounts", which items are discounted. If left blank, this discount can be used on any item.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_excluded_items', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_excluded_items"><?php _e( 'Excluded Items', 'invoicing' ); ?></label>
            </th>
            <td>
                <p><?php echo wpinv_item_dropdown( array(
                        'name'              => 'excluded_items[]',
                        'id'                => 'excluded_items',
                        'selected'          => $excluded_items,
                        'multiple'          => true,
                        'class'             => 'medium-text wpi_select2',
                        'placeholder'       => __( 'Select one or more Items', 'invoicing' ),
                        'show_recurring'    => true,
                    ) ); ?>
                </p>
                <p class="description"><?php _e( 'Items which are NOT allowed to use this discount.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_start', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_start"><?php _e( 'Start Date', 'invoicing' ); ?></label>
            </th>
            <td>
                <input type="text" class="w120 wpiDatepicker" id="wpinv_discount_start" data-dateFormat="yy-mm-dd" name="start" value="<?php echo esc_attr( $start_date ); ?>"> @ <select id="wpinv_discount_start_h" name="start_h">
                    <?php for ( $i = 0; $i <= 23; $i++ ) { $value = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
                    <option value="<?php echo $value;?>" <?php selected( $value, $start_h ); ?>><?php echo $value;?></option>
                    <?php } ?>
                </select> : <select id="wpinv_discount_start_m" name="start_m">
                    <?php for ( $i = 0; $i <= 59; $i++ ) { $value = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
                    <option value="<?php echo $value;?>" <?php selected( $value, $start_m ); ?>><?php echo $value;?></option>
                    <?php } ?>
                </select>
                <p class="description"><?php _e( 'Enter the start date for this discount code in the format of yyyy-mm-dd. For no start date, leave blank. If entered, the discount can only be used after or on this date.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_expiration', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_expiration"><?php _e( 'Expiration Date', 'invoicing' ); ?></label>
            </th>
            <td>
                <input type="text" class="w120 wpiDatepicker" id="wpinv_discount_expiration" data-dateFormat="yy-mm-dd" name="expiration" value="<?php echo esc_attr( $expiration_date ); ?>"> @ <select id="wpinv_discount_expiration_h" name="expiration_h">
                    <?php for ( $i = 0; $i <= 23; $i++ ) { $value = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
                    <option value="<?php echo $value;?>" <?php selected( $value, $expiration_h ); ?>><?php echo $value;?></option>
                    <?php } ?>
                </select> : <select id="wpinv_discount_expiration_m" name="expiration_m">
                    <?php for ( $i = 0; $i <= 59; $i++ ) { $value = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
                    <option value="<?php echo $value;?>" <?php selected( $value, $expiration_m ); ?>><?php echo $value;?></option>
                    <?php } ?>
                </select>
                <p class="description"><?php _e( 'Enter the expiration date for this discount code in the format of yyyy-mm-dd. Leave blank for no expiration.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_min_total', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_min_total"><?php _e( 'Minimum Amount', 'invoicing' ); ?></label>
            </th>
            <td>
                <input type="text" name="min_total" id="wpinv_discount_min_total" class="wpi-field-price wpi-price" value="<?php echo $min_total; ?>">
                <p class="description"><?php _e( 'This allows you to set the minimum amount (subtotal, including taxes) allowed when using the discount.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_max_total', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_max_total"><?php _e( 'Maximum Amount', 'invoicing' ); ?></label>
            </th>
            <td>
                <input type="text" name="max_total" id="wpinv_discount_max_total" class="wpi-field-price wpi-price" value="<?php echo $max_total; ?>">
                <p class="description"><?php _e( 'This allows you to set the maximum amount (subtotal, including taxes) allowed when using the discount.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_recurring', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_recurring"><?php _e( 'For recurring apply to', 'invoicing' ); ?></label>
            </th>
            <td>
                <select id="wpinv_discount_recurring" name="recurring" class="medium-text wpi_select2">
                    <option value="0" <?php selected( false, $recurring ); ?>><?php _e( 'First payment only', 'invoicing' ); ?></option>
                    <option value="1" <?php selected( true, $recurring ); ?>><?php _e( 'All payments', 'invoicing' ); ?></option>
                </select>
                <p class="description"><?php _e( '<b>All payments:</b> Apply this discount to all recurring payments of the recurring invoice. <br><b>First payment only:</b> Apply this discount to only first payment of the recurring invoice.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_max_uses', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_max_uses"><?php _e( 'Max Uses', 'invoicing' ); ?></label>
            </th>
            <td>
                <input type="number" min="0" step="1" id="wpinv_discount_max_uses" name="max_uses" class="medium-text" value="<?php echo $max_uses; ?>">
                <p class="description"><?php _e( 'The maximum number of times this discount can be used. Leave blank for unlimited.', 'invoicing' ); ?></p>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_before_single_use', $post ); ?>
        <tr>
            <th valign="top" scope="row">
                <label for="wpinv_discount_single_use"><?php _e( 'Use Once Per User', 'invoicing' ); ?></label>
            </th>
            <td>
                <input type="checkbox" value="1" name="single_use" id="wpinv_discount_single_use" <?php checked( true, $single_use ); ?>>
                <span class="description"><?php _e( 'Limit this discount to a single use per user?', 'invoicing' ); ?></span>
            </td>
        </tr>
        <?php do_action( 'wpinv_discount_form_last', $post ); ?>
    </tbody>
</table>
<?php do_action( 'wpinv_discount_form_bottom', $post ); ?>
    <?php
}

function wpinv_discount_metabox_save( $post_id, $post, $update = false ) {
    $post_type = !empty( $post ) ? $post->post_type : '';
    
    if ( $post_type != 'wpi_discount' ) {
        return;
    }
    
    if ( !isset( $_POST['wpinv_discount_metabox_nonce'] ) || ( isset( $_POST['wpinv_discount_metabox_nonce'] ) && !wp_verify_nonce( $_POST['wpinv_discount_metabox_nonce'], 'wpinv_discount_metabox_nonce' ) ) ) {
        return;
    }
    
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
        return;
    }
    
    if ( !current_user_can( wpinv_get_capability(), $post_id ) ) {
        return;
    }
    
    if ( !empty( $_POST['start'] ) && isset( $_POST['start_h'] ) && isset( $_POST['start_m'] ) && $_POST['start_h'] !== '' && $_POST['start_m'] !== '' ) {
        $_POST['start'] = $_POST['start'] . ' ' . $_POST['start_h'] . ':' . $_POST['start_m'];
    }

    if ( !empty( $_POST['expiration'] ) && isset( $_POST['expiration_h'] ) && isset( $_POST['expiration_m'] ) ) {
        $_POST['expiration'] = $_POST['expiration'] . ' ' . $_POST['expiration_h'] . ':' . $_POST['expiration_m'];
    }
    
    return /** @scrutinizer ignore-call */ wpinv_store_discount( $post_id, $_POST, $post, $update );
}
add_action( 'save_post', 'wpinv_discount_metabox_save', 10, 3 );

/**
 * Remove trash link from the default form.
 */
function getpaid_remove_action_link( $actions, $post ) {
    $post = get_post( $post );
    if ( 'wpi_payment_form' == $post->post_type && $post->ID == wpinv_get_default_payment_form() ) {
        unset( $actions['trash'] );
        unset( $actions['inline hide-if-no-js'] );
    }
    return $actions;
}
add_filter( 'post_row_actions', 'getpaid_remove_action_link', 10, 2 );
