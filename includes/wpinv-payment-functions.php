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
function wpinv_recurring_add_subscription_payment( $parent_invoice_id, $subscription_args = array() ) {    
    $args = wp_parse_args( $subscription_args, array(
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
    ///$invoice->set( 'user_id', $parent_invoice->get_user_id() );
    $invoice->set( 'first_name', $parent_invoice->get_first_name() );
    $invoice->set( 'last_name', $parent_invoice->get_last_name() );
    ///$invoice->set( 'email', $parent_invoice->get_email() );
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

    // increase the earnings for each item in the subscription
    $items          = $parent_invoice->get_cart_details();
    if ( $items ) {        
        $add_items      = array();
        $cart_details   = array();
        
        foreach ( $items as $item ) {
            $add_item             = array();
            $add_item['id']       = $item['id'];
            $add_item['quantity'] = $item['quantity'];
            
            $add_items[]    = $add_item;
            $cart_details[] = $item;
            break;
        }
        
        $invoice->set( 'items', $add_items );
        $invoice->cart_details = $cart_details;
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
    $invoice->save();
    
    wpinv_update_payment_status( $invoice->ID, 'publish' );
    wpinv_update_payment_status( $invoice->ID, 'renewal' );
    
    $invoice = wpinv_get_invoice( $invoice->ID );
    
    $subscription_data                      = wpinv_payment_subscription_data( $parent_invoice );
    $subscription_data['recurring_amount']  = $invoice->get_total();
    $subscription_data['created']           = current_time( 'mysql', 0 );
    $subscription_data['expiration']        = $invoice->get_new_expiration( $subscription_data['item_id'] );
    
    // Retrieve pending subscription from database and update it's status to active and set proper profile ID
    $invoice->update_subscription( $subscription_data );

    do_action( 'wpinv_recurring_add_subscription_payment', $invoice, $parent_invoice, $subscription_args );
    do_action( 'wpinv_recurring_record_payment', $invoice->ID, $parent_invoice_id, $subscription_args );

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

function wpinv_is_subscription_payment( $invoice = '' ) {
    if ( empty( $invoice ) ) {
        return false;
    }
    
    if ( !is_object( $invoice ) && is_scalar( $invoice ) ) {
        $invoice = wpinv_get_invoice( $invoice );
    }
    
    if ( empty( $invoice ) ) {
        return false;
    }
        
    if ( $invoice->parent_invoice && $invoice->parent_invoice != $invoice->ID ) {
        return true;
    }

    return false;
}

function wpinv_payment_subscription_data( $invoice = '' ) {
    if ( empty( $invoice ) ) {
        return false;
    }
    
    if ( !is_object( $invoice ) && is_scalar( $invoice ) ) {
        $invoice = wpinv_get_invoice( $invoice );
    }
    
    if ( empty( $invoice ) ) {
        return false;
    }    

    return $invoice->get_subscription_data();
}

function wpinv_payment_link_transaction_id( $invoice = '' ) {
    if ( empty( $invoice ) ) {
        return false;
    }
    
    if ( !is_object( $invoice ) && is_scalar( $invoice ) ) {
        $invoice = wpinv_get_invoice( $invoice );
    }
    
    if ( empty( $invoice ) ) {
        return false;
    }

    return apply_filters( 'wpinv_payment_details_transaction_id-' . $invoice->gateway, $invoice->get_transaction_id(), $invoice->ID );
}

function wpinv_get_pretty_subscription_period( $period ) {
    $frequency = '';
    //Format period details
    switch ( $period ) {
        case 'D' :
        case 'day' :
            $frequency = __( 'Daily', 'invoicing' );
            break;
        case 'W' :
        case 'week' :
            $frequency = __( 'Weekly', 'invoicing' );
            break;
        case 'M' :
        case 'month' :
            $frequency = __( 'Monthly', 'invoicing' );
            break;
        case 'Y' :
        case 'year' :
            $frequency = __( 'Yearly', 'invoicing' );
            break;
        default :
            $frequency = apply_filters( 'wpinv_pretty_subscription_period', $frequency, $period );
            break;
    }

    return $frequency;
}

function wpinv_get_pretty_subscription_period_name( $period ) {
    $frequency = '';
    //Format period details
    switch ( $period ) {
        case 'D' :
        case 'day' :
            $frequency = __( 'Day', 'invoicing' );
            break;
        case 'W' :
        case 'week' :
            $frequency = __( 'Week', 'invoicing' );
            break;
        case 'M' :
        case 'month' :
            $frequency = __( 'Month', 'invoicing' );
            break;
        case 'Y' :
        case 'year' :
            $frequency = __( 'Year', 'invoicing' );
            break;
        default :
            $frequency = apply_filters( 'wpinv_pretty_subscription_period_name', $frequency, $period );
            break;
    }

    return $frequency;
}