<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_add_meta_boxes() {
    add_meta_box( 'wpinv-details', __( 'Invoice Details', 'invoicing' ), 'WPInv_Meta_Box_Details::output', 'wpi_invoice', 'side', 'default' );
    add_meta_box( 'wpinv-items', __( 'Invoice Items', 'invoicing' ), 'WPInv_Meta_Box_Items::output', 'wpi_invoice', 'normal', 'high' );
    add_meta_box( 'wpinv-address', __( 'Billing Details', 'invoicing' ), 'WPInv_Meta_Box_Billing_Details::output', 'wpi_invoice', 'normal', 'high' );
    add_meta_box( 'wpinv-notes', __( 'Invoice Notes', 'invoicing' ), 'WPInv_Meta_Box_Notes::output', 'wpi_invoice', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'wpinv_add_meta_boxes', 30 );

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