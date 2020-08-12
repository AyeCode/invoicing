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
    add_meta_box( 'wpinv-payment-form-info', __( 'Details', 'invoicing' ), 'GetPaid_Meta_Box_Payment_Form_Info::output', 'wpi_payment_form', 'side' );
   
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
        'wpi_payment_form' => 'GetPaid_Meta_Box_Payment_Form',
        'wpi_discount'     => 'GetPaid_Meta_Box_Discount_Details',
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
    add_meta_box( 'wpinv_discount_details', __( 'Discount Details', 'invoicing' ), 'GetPaid_Meta_Box_Discount_Details::output', 'wpi_discount', 'normal', 'high' );
}

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
