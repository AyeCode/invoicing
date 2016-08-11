<?php
function wpinv_get_subscriptions( $args = array() ) {
    if ( empty( $args['parent_invoice_id'] ) ) {
        return false;
    }
    
    $defaults = array(
        'post_type'    => 'wpi_invoice',
        'numberposts'  => 20,
        'post_parent'  => -1,
        'orderby'      => 'ID',
        'order'        => 'DESC',
        'fields'       => 'ids'
    );

    $args['post_parent']    = $args['parent_invoice_id'];
    $args                   = wp_parse_args( $args, $defaults );

    if( $args['numberposts'] < 1 ) {
        $args['numberposts'] = 999999999999;
    }

    $posts          = get_posts( $args );
    $subscriptions  = array();
    if ( !empty( $posts ) ) {
        foreach ( $posts as $post ) {
            if ( !empty( $post->ID ) ) {
                $subscriptions[] = wpinv_get_invoice( $post->ID );
            }
        }
    }
    //$subscriptions[] = wpinv_get_invoice( $args['post_parent'] );
    
    return $subscriptions;
}
function wpinv_get_subscription( $id = 0, $by_profile_id = false ) {
    global $wpdb;

    if ( empty( $id ) ) {
        return false;
    }

    $id = esc_sql( $id );

    $invoice_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpinv_profile_id' AND meta_value = '{$id}' LIMIT 1" );

    if ( $invoice_id != null ) {
        return wpinv_get_invoice( $invoice_id );;
    }

    return false;
}

/**
 * Records a new payment on the subscription
 * 
 */
function wpinv_recurring_add_subscription_payment( $parent_invoice_id, $args = array() ) {
    $args = wp_parse_args( $args, array(
        'amount'         => '',
        'transaction_id' => '',
        'gateway'        => ''
    ) );

    if ( wpinv_payment_exists( $args['transaction_id'] ) ) {
        return false;
    }
    
    $parent_invoice = wpinv_get_invoice( $parent_invoice_id );
    if ( empty( $parent_invoice ) ) {
        return;
    }

    $invoice = new WPInv_Invoice();
    $invoice->set( 'parent_invoice', $parent_invoice_id );
    $invoice->set( 'currency', $parent_invoice->get_currency() );
    //$invoice->set( 'status', 'publish' );
    $invoice->set( 'transaction_id', $args['transaction_id'] );
    $invoice->set( 'key', $parent_invoice->get_key() );
    
    $invoice->set( 'ip', $parent_invoice->ip );
    $invoice->set( 'user_id', $parent_invoice->get_user_id() );
    $invoice->set( 'first_name', $parent_invoice->get_first_name() );
    $invoice->set( 'last_name', $parent_invoice->get_last_name() );
    $invoice->set( 'email', $parent_invoice->get_email() );
    $invoice->set( 'phone', $parent_invoice->phone );
    $invoice->set( 'address', $parent_invoice->address );
    $invoice->set( 'city', $parent_invoice->city );
    $invoice->set( 'country', $parent_invoice->country );
    $invoice->set( 'state', $parent_invoice->state );
    $invoice->set( 'zip', $parent_invoice->zip );
    $invoice->set( 'company', $parent_invoice->company );
    $invoice->set( 'vat_number', $parent_invoice->vat_number );
    $invoice->set( 'vat_rate', $parent_invoice->vat_rate );
    $invoice->set( 'self_certified', $parent_invoice->self_certified );

    if ( empty( $args['gateway'] ) ) {
        $invoice->set( 'gateway', $parent_invoice->get_gateway() );
    } else {
        $invoice->set( 'gateway', $args['gateway'] );
    }

    // increase the earnings for each product in the subscription
    $items = $parent_invoice->get_cart_details();
    if ( $items ) {        
        $add_items = array();
        
        foreach ( $items as $item ) {
            $add_item           = $item;
            $add_item['action'] = 'add';
            
            $add_items[] = $add_item;
            break;
        }
        
        $invoice->set( 'items', $add_items );
    }
    
    $total = $args['amount'];
    
    $subtotal   = 0;
    $tax        = 0;
    $discount   = 0;
    if ( (float)$total > 0 ) {
        $subtotal   = $total - $parent_invoice->tax + $parent_invoice->discount;
        $tax        = $parent_invoice->tax;
        $discount   = $parent_invoice->discount;
    }
    
    $subtotal   = $subtotal > 0 ? $subtotal : 0;
    $tax        = $tax > 0 ? $tax : 0;
    $discount   = $discount > 0 ? $discount : 0;
    
    if ( $discount > 0 ) {
        $invoice->set( 'discount_code', $parent_invoice->discount_code );
    }
    
    $invoice->subtotal = wpinv_format_amount( $subtotal, NULL, true );
    $invoice->tax      = wpinv_format_amount( $tax, NULL, true );
    $invoice->discount = wpinv_format_amount( $discount, NULL, true );
    $invoice->total    = wpinv_format_amount( $total, NULL, true );
    wpinv_error_log( $invoice, 'before save()', __FILE__, __LINE__ );
    $invoice->save();
    wpinv_error_log( $invoice, 'after save()', __FILE__, __LINE__ );

    do_action( 'wpinv_recurring_add_subscription_payment', $invoice, $parent_invoice );
    do_action( 'wpinv_recurring_record_payment', $invoice->ID, $parent_invoice_id, $args['amount'], $args['transaction_id'] );

    return $invoice;
}

function wpinv_payment_exists( $txn_id = '' ) {
    global $wpdb;

    if ( empty( $txn_id ) ) {
        return false;
    }

    $txn_id = esc_sql( $txn_id );

    $invoice = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpinv_transaction_id' AND meta_value = '{$txn_id}' LIMIT 1" );

    if ( $invoice != null ) {
        return true;
    }

    return false;
}