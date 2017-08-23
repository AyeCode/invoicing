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
        return wpinv_get_invoice( $invoice_id );
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
    $invoice->set( 'transaction_id', $args['transaction_id'] );
    $invoice->set( 'key', $parent_invoice->generate_key() );
    $invoice->set( 'ip', $parent_invoice->ip );
    $invoice->set( 'user_id', $parent_invoice->get_user_id() );
    $invoice->set( 'first_name', $parent_invoice->get_first_name() );
    $invoice->set( 'last_name', $parent_invoice->get_last_name() );
    $invoice->set( 'phone', $parent_invoice->phone );
    $invoice->set( 'address', $parent_invoice->address );
    $invoice->set( 'city', $parent_invoice->city );
    $invoice->set( 'country', $parent_invoice->country );
    $invoice->set( 'state', $parent_invoice->state );
    $invoice->set( 'zip', $parent_invoice->zip );
    $invoice->set( 'company', $parent_invoice->company );
    $invoice->set( 'vat_number', $parent_invoice->vat_number );
    $invoice->set( 'vat_rate', $parent_invoice->vat_rate );
    $invoice->set( 'adddress_confirmed', $parent_invoice->adddress_confirmed );

    if ( empty( $args['gateway'] ) ) {
        $invoice->set( 'gateway', $parent_invoice->get_gateway() );
    } else {
        $invoice->set( 'gateway', $args['gateway'] );
    }
    
    $recurring_details = $parent_invoice->get_recurring_details();

    // increase the earnings for each item in the subscription
    $items = $recurring_details['cart_details'];
    
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
    
    $subtotal           = $recurring_details['subtotal'];
    $tax                = $recurring_details['tax'];
    $discount           = $recurring_details['discount'];
    
    if ( $discount > 0 ) {
        $invoice->set( 'discount_code', $parent_invoice->discount_code );
    }
    
    $invoice->subtotal = wpinv_round_amount( $subtotal );
    $invoice->tax      = wpinv_round_amount( $tax );
    $invoice->discount = wpinv_round_amount( $discount );
    $invoice->total    = wpinv_round_amount( $total );
    $invoice->save();
    
    wpinv_update_payment_status( $invoice->ID, 'publish' );
    sleep(1);
    wpinv_update_payment_status( $invoice->ID, 'wpi-renewal' );
    
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
        
    if ( $invoice->is_renewal() ) {
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

    return apply_filters( 'wpinv_payment_details_transaction_id-' . $invoice->gateway, $invoice->get_transaction_id(), $invoice->ID, $invoice );
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

function wpinv_subscription_initial_payment_desc( $amount, $period, $interval, $trial_period = '', $trial_interval = 0 ) {
    $interval   = (int)$interval > 0 ? (int)$interval : 1;
    
    if ( $trial_interval > 0 && !empty( $trial_period ) ) {
        $amount = __( 'Free', 'invoicing' );
        $interval = $trial_interval;
        $period = $trial_period;
    }
    
    $description = '';
    switch ( $period ) {
        case 'D' :
        case 'day' :
            $description = wp_sprintf( _n( '%s for the first day.', '%s for the first %d days.', $interval, 'invoicing' ), $amount, $interval );
            break;
        case 'W' :
        case 'week' :
            $description = wp_sprintf( _n( '%s for the first week.', '%s for the first %d weeks.', $interval, 'invoicing' ), $amount, $interval );
            break;
        case 'M' :
        case 'month' :
            $description = wp_sprintf( _n( '%s for the first month.', '%s for the first %d months.', $interval, 'invoicing' ), $amount, $interval );
            break;
        case 'Y' :
        case 'year' :
            $description = wp_sprintf( _n( '%s for the first year.', '%s for the first %d years.', $interval, 'invoicing' ), $amount, $interval );
            break;
    }

    return apply_filters( 'wpinv_subscription_initial_payment_desc', $description, $amount, $period, $interval, $trial_period, $trial_interval  );
}

function wpinv_subscription_recurring_payment_desc( $amount, $period, $interval, $bill_times = 0, $trial_period = '', $trial_interval = 0 ) {
    $interval   = (int)$interval > 0 ? (int)$interval : 1;
    $bill_times = (int)$bill_times > 0 ? (int)$bill_times : 0;
    
    $description = '';
    switch ( $period ) {
        case 'D' :
        case 'day' :            
            if ( (int)$bill_times > 0 ) {
                if ( $interval > 1 ) {
                    if ( $bill_times > 1 ) {
                        $description = wp_sprintf( __( '%s for each %d days, for %d installments.', 'invoicing' ), $amount, $interval, $bill_times );
                    } else {
                        $description = wp_sprintf( __( '%s for %d days.', 'invoicing' ), $amount, $interval );
                    }
                } else {
                    $description = wp_sprintf( _n( '%s for one day.', '%s for each day, for %d installments.', $bill_times, 'invoicing' ), $amount, $bill_times );
                }
            } else {
                $description = wp_sprintf( _n( '%s for each day.', '%s for each %d days.', $interval, 'invoicing'), $amount, $interval );
            }
            break;
        case 'W' :
        case 'week' :            
            if ( (int)$bill_times > 0 ) {
                if ( $interval > 1 ) {
                    if ( $bill_times > 1 ) {
                        $description = wp_sprintf( __( '%s for each %d weeks, for %d installments.', 'invoicing' ), $amount, $interval, $bill_times );
                    } else {
                        $description = wp_sprintf( __( '%s for %d weeks.', 'invoicing' ), $amount, $interval );
                    }
                } else {
                    $description = wp_sprintf( _n( '%s for one week.', '%s for each week, for %d installments.', $bill_times, 'invoicing' ), $amount, $bill_times );
                }
            } else {
                $description = wp_sprintf( _n( '%s for each week.', '%s for each %d weeks.', $interval, 'invoicing' ), $amount, $interval );
            }
            break;
        case 'M' :
        case 'month' :            
            if ( (int)$bill_times > 0 ) {
                if ( $interval > 1 ) {
                    if ( $bill_times > 1 ) {
                        $description = wp_sprintf( __( '%s for each %d months, for %d installments.', 'invoicing' ), $amount, $interval, $bill_times );
                    } else {
                        $description = wp_sprintf( __( '%s for %d months.', 'invoicing' ), $amount, $interval );
                    }
                } else {
                    $description = wp_sprintf( _n( '%s for one month.', '%s for each month, for %d installments.', $bill_times, 'invoicing' ), $amount, $bill_times );
                }
            } else {
                $description = wp_sprintf( _n( '%s for each month.', '%s for each %d months.', $interval, 'invoicing' ), $amount, $interval );
            }
            break;
        case 'Y' :
        case 'year' :            
            if ( (int)$bill_times > 0 ) {
                if ( $interval > 1 ) {
                    if ( $bill_times > 1 ) {
                        $description = wp_sprintf( __( '%s for each %d years, for %d installments.', 'invoicing' ), $amount, $interval, $bill_times );
                    } else {
                        $description = wp_sprintf( __( '%s for %d years.', 'invoicing'), $amount, $interval );
                    }
                } else {
                    $description = wp_sprintf( _n( '%s for one year.', '%s for each year, for %d installments.', $bill_times, 'invoicing' ), $amount, $bill_times );
                }
            } else {
                $description = wp_sprintf( _n( '%s for each year.', '%s for each %d years.', $interval, 'invoicing' ), $amount, $interval );
            }
            break;
    }

    return apply_filters( 'wpinv_subscription_recurring_payment_desc', $description, $amount, $period, $interval, $bill_times, $trial_period, $trial_interval );
}

function wpinv_subscription_payment_desc( $invoice ) {
    if ( empty( $invoice ) ) {
        return NULL;
    }
    
    $description = '';
    if ( $invoice->is_parent() && $item = $invoice->get_recurring( true ) ) {
        if ( $item->has_free_trial() ) {
            $trial_period = $item->get_trial_period();
            $trial_interval = $item->get_trial_interval();
        } else {
            $trial_period = '';
            $trial_interval = 0;
        }
        
        $description = wpinv_get_billing_cycle( $invoice->get_total(), $invoice->get_recurring_details( 'total' ), $item->get_recurring_period(), $item->get_recurring_interval(), $item->get_recurring_limit(), $trial_period, $trial_interval, $invoice->get_currency() );
    }
    
    return apply_filters( 'wpinv_subscription_payment_desc', $description, $invoice );
}

function wpinv_get_billing_cycle( $initial, $recurring, $period, $interval, $bill_times, $trial_period = '', $trial_interval = 0, $currency = '' ) {
    $initial_total      = wpinv_round_amount( $initial );
    $recurring_total    = wpinv_round_amount( $recurring );
    
    if ( $trial_interval > 0 && !empty( $trial_period ) ) {
        // Free trial
    } else {
        if ( $bill_times == 1 ) {
            $recurring_total = $initial_total;
        } else if ( $bill_times > 1 && $initial_total != $recurring_total ) {
            $bill_times--;
        }
    }
    
    $initial_amount     = wpinv_price( wpinv_format_amount( $initial_total ), $currency );
    $recurring_amount   = wpinv_price( wpinv_format_amount( $recurring_total ), $currency );
    
    $recurring          = wpinv_subscription_recurring_payment_desc( $recurring_amount, $period, $interval, $bill_times, $trial_period, $trial_interval );
        
    if ( $initial_total != $recurring_total ) {
        $initial        = wpinv_subscription_initial_payment_desc( $initial_amount, $period, $interval, $trial_period, $trial_interval );
        
        $description    = wp_sprintf( __( '%s Then %s', 'invoicing' ), $initial, $recurring );
    } else {
        $description    = $recurring;
    }
    
    return apply_filters( 'wpinv_get_billing_cycle', $description, $initial, $recurring, $period, $interval, $bill_times, $trial_period, $trial_interval, $currency );
}

function wpinv_recurring_send_payment_failed( $invoice ) {
    if ( !empty( $invoice->ID ) ) {
        wpinv_failed_invoice_notification( $invoice->ID );
    }
}
add_action( 'wpinv_recurring_payment_failed', 'wpinv_recurring_send_payment_failed', 10, 1 );