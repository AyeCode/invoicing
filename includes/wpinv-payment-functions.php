<?php
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
    
    $initial_amount     = wpinv_price( $initial_total, $currency );
    $recurring_amount   = wpinv_price( $recurring_total, $currency );
    
    $recurring          = wpinv_subscription_recurring_payment_desc( $recurring_amount, $period, $interval, $bill_times, $trial_period, $trial_interval );
        
    if ( $initial_total != $recurring_total ) {
        $initial        = wpinv_subscription_initial_payment_desc( $initial_amount, $period, $interval, $trial_period, $trial_interval );
        
        $description    = wp_sprintf( __( '%s Then %s', 'invoicing' ), $initial, $recurring );
    } else {
        $description    = $recurring;
    }
    
    return apply_filters( 'wpinv_get_billing_cycle', $description, $initial, $recurring, $period, $interval, $bill_times, $trial_period, $trial_interval, $currency );
}

/**
 * Calculates the card name form a card number.
 *
 *
 * @param string $card_number Card number.
 * @return string
 */
function getpaid_get_card_name( $card_number ) {

    // Known regexes.
    $regexes = array(
        '/^4/'                     => __( 'Visa', 'invoicing' ),
        '/^5[1-5]/'                => __( 'Mastercard', 'invoicing' ),
        '/^3[47]/'                 => __( 'Amex', 'invoicing' ),
        '/^3(?:0[0-5]|[68])/'      => __( 'Diners Club', 'invoicing' ),
        '/^6(?:011|5)/'            => __( 'Discover', 'invoicing' ),
        '/^(?:2131|1800|35\d{3})/' => __( 'JCB', 'invoicing' ),
    );

    // Confirm if one matches.
    foreach ( $regexes as $regex => $card ) {
        if ( preg_match ( $regex, $card_number ) >= 1 ) {
            return $card;
        }
    }

    // None matched.
    return __( 'Card', 'invoicing' );

}

/**
 * Sends an error response during checkout.
 * 
 * @param WPInv_Invoice|int|null $invoice
 */
function wpinv_send_back_to_checkout( $invoice = null ) {
    $response = array( 'success' => false );
    $invoice  = wpinv_get_invoice( $invoice );

    // Was an invoice created?
    if ( ! empty( $invoice ) ) {
        $invoice             = is_scalar( $invoice ) ? new WPInv_Invoice( $invoice ) : $invoice;
        $response['invoice'] = $invoice->get_id();
        do_action( 'getpaid_checkout_invoice_exception', $invoice );
    }

	// Do we have any errors?
    if ( wpinv_get_errors() ) {
        $response['data'] = getpaid_get_errors_html( true, false );
    } else {
        $response['data'] = __( 'An error occured while processing your payment. Please try again.', 'invoicing' );
    }

    wp_send_json( $response );
}
