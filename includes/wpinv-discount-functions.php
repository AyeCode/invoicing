<?php
/**
 * Contains functions related to Invoicing plugin.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_get_discount_types() {
    $discount_types = array(
                        'percent'   => __( 'Percentage', 'invoicing' ),
                        'flat'     => __( 'Flat Amount', 'invoicing' ),
                    );
    return (array)apply_filters( 'wpinv_discount_types', $discount_types );
}

function wpinv_get_discount_type_name( $type = '' ) {
    $types = wpinv_get_discount_types();
    return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

function wpinv_delete_discount( $data ) {
    if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'wpinv_discount_nonce' ) ) {
        wp_die( __( 'Trying to cheat or something?', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

    if( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to delete discount codes', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

    $discount_id = $data['discount'];
    wpinv_remove_discount( $discount_id );
}
add_action( 'wpinv_delete_discount', 'wpinv_delete_discount' );

function wpinv_activate_discount( $data ) {
    if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'wpinv_discount_nonce' ) ) {
        wp_die( __( 'Trying to cheat or something?', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

    if( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to edit discount codes', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

    $id = absint( $data['discount'] );
    wpinv_update_discount_status( $id, 'publish' );
}
add_action( 'wpinv_activate_discount', 'wpinv_activate_discount' );

function wpinv_deactivate_discount( $data ) {
    if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'wpinv_discount_nonce' ) ) {
        wp_die( __( 'Trying to cheat or something?', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }

    if( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have permission to create discount codes', 'invoicing' ), array( 'response' => 403 ) );
    }

    $id = absint( $data['discount'] );
    wpinv_update_discount_status( $id, 'pending' );
}
add_action( 'wpinv_deactivate_discount', 'wpinv_deactivate_discount' );

function wpinv_get_discounts( $args = array() ) {
    $defaults = array(
        'post_type'      => 'wpi_discount',
        'posts_per_page' => 20,
        'paged'          => null,
        'post_status'    => array( 'publish', 'pending', 'draft', 'expired' )
    );

    $args = wp_parse_args( $args, $defaults );

    $discounts = get_posts( $args );

    if ( $discounts ) {
        return $discounts;
    }

    if( ! $discounts && ! empty( $args['s'] ) ) {
        $args['meta_key']     = 'gd_discount_code';
        $args['meta_value']   = $args['s'];
        $args['meta_compare'] = 'LIKE';
        unset( $args['s'] );
        $discounts = get_posts( $args );
    }

    if( $discounts ) {
        return $discounts;
    }

    return false;
}

function wpinv_has_active_discounts() {
    $has_active = false;

    $discounts  = wpinv_get_discounts();

    if ( $discounts) {
        foreach ( $discounts as $discount ) {
            if ( wpinv_is_discount_active( $discount->ID ) ) {
                $has_active = true;
                break;
            }
        }
    }
    return $has_active;
}

function wpinv_get_discount( $discount_id = 0 ) {
    if( empty( $discount_id ) ) {
        return false;
    }
    
    if ( get_post_type( $discount_id ) != 'wpi_discount' ) {
        return false;
    }

    $discount = get_post( $discount_id );

    return $discount;
}

function wpinv_get_discount_by_code( $code = '' ) {
    if( empty( $code ) || ! is_string( $code ) ) {
        return false;
    }

    return wpinv_get_discount_by( 'code', $code );
}

function wpinv_get_discount_by( $field = '', $value = '' ) {
    if( empty( $field ) || empty( $value ) ) {
        return false;
    }

    if( ! is_string( $field ) ) {
        return false;
    }

    switch( strtolower( $field ) ) {

        case 'code':
            $meta_query     = array();
            $meta_query[]   = array(
                'key'     => '_wpi_discount_code',
                'value'   => $value,
                'compare' => '='
            );
            
            $discount = wpinv_get_discounts( array(
                'posts_per_page' => 1,
                'post_status'    => 'any',
                'meta_query'     => $meta_query,
            ) );
            
            if( $discount ) {
                $discount = $discount[0];
            }

            break;

        case 'id':
            $discount = wpinv_get_discount( $value );

            break;

        case 'name':
            $discount = get_posts( array(
                'post_type'      => 'wpi_discount',
                'name'           => $value,
                'posts_per_page' => 1,
                'post_status'    => 'any'
            ) );

            if( $discount ) {
                $discount = $discount[0];
            }

            break;

        default:
            return false;
    }

    if( ! empty( $discount ) ) {
        return $discount;
    }

    return false;
}

function wpinv_store_discount( $post_id, $data, $post, $update = false ) {
    $meta = array(
        'code'              => isset( $data['code'] )             ? sanitize_text_field( $data['code'] )              : '',
        'type'              => isset( $data['type'] )             ? sanitize_text_field( $data['type'] )              : 'percent',
        'amount'            => isset( $data['amount'] )           ? wpinv_sanitize_amount( $data['amount'] )          : '',
        'start'             => isset( $data['start'] )            ? sanitize_text_field( $data['start'] )             : '',
        'expiration'        => isset( $data['expiration'] )       ? sanitize_text_field( $data['expiration'] )        : '',
        'min_total'         => isset( $data['min_total'] )        ? wpinv_sanitize_amount( $data['min_total'] )       : '',
        'max_total'         => isset( $data['max_total'] )        ? wpinv_sanitize_amount( $data['max_total'] )       : '',
        'max_uses'          => isset( $data['max_uses'] )         ? absint( $data['max_uses'] )                       : '',
        'items'             => isset( $data['items'] )            ? $data['items']                                    : array(),
        'excluded_items'    => isset( $data['excluded_items'] )   ? $data['excluded_items']                           : array(),
        'is_recurring'      => isset( $data['recurring'] )        ? (bool)$data['recurring']                          : false,
        'is_single_use'     => isset( $data['single_use'] )       ? (bool)$data['single_use']                         : false,
        'uses'              => isset( $data['uses'] )             ? (int)$data['uses']                                : false,
    );
    
    $start_timestamp        = strtotime( $meta['start'] );

    if ( !empty( $meta['start'] ) ) {
        $meta['start']      = date( 'Y-m-d H:i:s', $start_timestamp );
    }
        
    if ( $meta['type'] == 'percent' && (float)$meta['amount'] > 100 ) {
        $meta['amount'] = 100;
    }

    if ( !empty( $meta['expiration'] ) ) {
        $meta['expiration'] = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d', strtotime( $meta['expiration'] ) ) . ' 23:59:59' ) );
        $end_timestamp      = strtotime( $meta['expiration'] );

        if ( !empty( $meta['start'] ) && $start_timestamp > $end_timestamp ) {
            $meta['expiration'] = $meta['start']; // Set the expiration date to the start date if start is later than expiration date.
        }
    }
    
    if ( $meta['uses'] === false ) {
        unset( $meta['uses'] );
    }
    
    if ( ! empty( $meta['items'] ) ) {
        foreach ( $meta['items'] as $key => $item ) {
            if ( 0 === intval( $item ) ) {
                unset( $meta['items'][ $key ] );
            }
        }
    }
    
    if ( ! empty( $meta['excluded_items'] ) ) {
        foreach ( $meta['excluded_items'] as $key => $item ) {
            if ( 0 === intval( $item ) ) {
                unset( $meta['excluded_items'][ $key ] );
            }
        }
    }
    
    $meta = apply_filters( 'wpinv_update_discount', $meta, $post_id, $post );
    
    do_action( 'wpinv_pre_update_discount', $meta, $post_id, $post );
    
    foreach( $meta as $key => $value ) {
        update_post_meta( $post_id, '_wpi_discount_' . $key, $value );
    }
    
    do_action( 'wpinv_post_update_discount', $meta, $post_id, $post );
    
    return $post_id;
}

function wpinv_remove_discount( $discount_id = 0 ) {
    do_action( 'wpinv_pre_delete_discount', $discount_id );

    wp_delete_post( $discount_id, true );

    do_action( 'wpinv_post_delete_discount', $discount_id );
}

function wpinv_update_discount_status( $code_id = 0, $new_status = 'publish' ) {
    $discount = wpinv_get_discount(  $code_id );

    if ( $discount ) {
        do_action( 'wpinv_pre_update_discount_status', $code_id, $new_status, $discount->post_status );

        wp_update_post( array( 'ID' => $code_id, 'post_status' => $new_status ) );

        do_action( 'wpinv_post_update_discount_status', $code_id, $new_status, $discount->post_status );

        return true;
    }

    return false;
}

function wpinv_discount_exists( $code_id ) {
    if ( wpinv_get_discount(  $code_id ) ) {
        return true;
    }

    return false;
}

function wpinv_is_discount_active( $code_id = null ) {
    $discount = wpinv_get_discount(  $code_id );
    $return   = false;

    if ( $discount ) {
        if ( wpinv_is_discount_expired( $code_id ) ) {
            if( defined( 'DOING_AJAX' ) ) {
                wpinv_set_error( 'wpinv-discount-error', __( 'This discount is expired.', 'invoicing' ) );
            }
        } elseif ( $discount->post_status == 'publish' ) {
            $return = true;
        } else {
            if( defined( 'DOING_AJAX' ) ) {
                wpinv_set_error( 'wpinv-discount-error', __( 'This discount is not active.', 'invoicing' ) );
            }
        }
    }

    return apply_filters( 'wpinv_is_discount_active', $return, $code_id );
}

function wpinv_get_discount_code( $code_id = null ) {
    $code = get_post_meta( $code_id, '_wpi_discount_code', true );

    return apply_filters( 'wpinv_get_discount_code', $code, $code_id );
}

function wpinv_get_discount_start_date( $code_id = null ) {
    $start_date = get_post_meta( $code_id, '_wpi_discount_start', true );

    return apply_filters( 'wpinv_get_discount_start_date', $start_date, $code_id );
}

function wpinv_get_discount_expiration( $code_id = null ) {
    $expiration = get_post_meta( $code_id, '_wpi_discount_expiration', true );

    return apply_filters( 'wpinv_get_discount_expiration', $expiration, $code_id );
}

function wpinv_get_discount_max_uses( $code_id = null ) {
    $max_uses = get_post_meta( $code_id, '_wpi_discount_max_uses', true );

    return (int) apply_filters( 'wpinv_get_discount_max_uses', $max_uses, $code_id );
}

function wpinv_get_discount_uses( $code_id = null ) {
    $uses = get_post_meta( $code_id, '_wpi_discount_uses', true );

    return (int) apply_filters( 'wpinv_get_discount_uses', $uses, $code_id );
}

function wpinv_get_discount_min_total( $code_id = null ) {
    $min_total = get_post_meta( $code_id, '_wpi_discount_min_total', true );

    return (float) apply_filters( 'wpinv_get_discount_min_total', $min_total, $code_id );
}

function wpinv_get_discount_max_total( $code_id = null ) {
    $max_total = get_post_meta( $code_id, '_wpi_discount_max_total', true );

    return (float) apply_filters( 'wpinv_get_discount_max_total', $max_total, $code_id );
}

function wpinv_get_discount_amount( $code_id = null ) {
    $amount = get_post_meta( $code_id, '_wpi_discount_amount', true );

    return (float) apply_filters( 'wpinv_get_discount_amount', $amount, $code_id );
}

function wpinv_get_discount_type( $code_id = null, $name = false ) {
    $type = strtolower( get_post_meta( $code_id, '_wpi_discount_type', true ) );
    
    if ( $name ) {
        $name = wpinv_get_discount_type_name( $type );
        
        return apply_filters( 'wpinv_get_discount_type_name', $name, $code_id );
    }

    return apply_filters( 'wpinv_get_discount_type', $type, $code_id );
}

function wpinv_discount_status( $status ) {
    switch( $status ){
        case 'expired' :
            $name = __( 'Expired', 'invoicing' );
            break;
        case 'publish' :
        case 'active' :
            $name = __( 'Active', 'invoicing' );
            break;
        default :
            $name = __( 'Inactive', 'invoicing' );
            break;
    }
    return $name;
}

function wpinv_get_discount_excluded_items( $code_id = null ) {
    $excluded_items = get_post_meta( $code_id, '_wpi_discount_excluded_items', true );

    if ( empty( $excluded_items ) || ! is_array( $excluded_items ) ) {
        $excluded_items = array();
    }

    return (array) apply_filters( 'wpinv_get_discount_excluded_items', $excluded_items, $code_id );
}

function wpinv_get_discount_item_reqs( $code_id = null ) {
    $item_reqs = get_post_meta( $code_id, '_wpi_discount_items', true );

    if ( empty( $item_reqs ) || ! is_array( $item_reqs ) ) {
        $item_reqs = array();
    }

    return (array) apply_filters( 'wpinv_get_discount_item_reqs', $item_reqs, $code_id );
}

function wpinv_get_discount_item_condition( $code_id = 0 ) {
    return get_post_meta( $code_id, '_wpi_discount_item_condition', true );
}

function wpinv_is_discount_not_global( $code_id = 0 ) {
    return (bool) get_post_meta( $code_id, '_wpi_discount_is_not_global', true );
}

function wpinv_is_discount_expired( $code_id = null ) {
    $discount = wpinv_get_discount(  $code_id );
    $return   = false;

    if ( $discount ) {
        $expiration = wpinv_get_discount_expiration( $code_id );
        if ( $expiration ) {
            $expiration = strtotime( $expiration );
            if ( $expiration < current_time( 'timestamp' ) ) {
                // Discount is expired
                wpinv_update_discount_status( $code_id, 'pending' );
                $return = true;
            }
        }
    }

    return apply_filters( 'wpinv_is_discount_expired', $return, $code_id );
}

function wpinv_is_discount_started( $code_id = null ) {
    $discount = wpinv_get_discount(  $code_id );
    $return   = false;

    if ( $discount ) {
        $start_date = wpinv_get_discount_start_date( $code_id );

        if ( $start_date ) {
            $start_date = strtotime( $start_date );

            if ( $start_date < current_time( 'timestamp' ) ) {
                // Discount has past the start date
                $return = true;
            } else {
                wpinv_set_error( 'wpinv-discount-error', __( 'This discount is not active yet.', 'invoicing' ) );
            }
        } else {
            // No start date for this discount, so has to be true
            $return = true;
        }
    }

    return apply_filters( 'wpinv_is_discount_started', $return, $code_id );
}

function wpinv_check_discount_dates( $code_id = null ) {
    $discount = wpinv_get_discount(  $code_id );
    $return   = false;

    if ( $discount ) {
        $start_date = wpinv_get_discount_start_date( $code_id );

        if ( $start_date ) {
            $start_date = strtotime( $start_date );

            if ( $start_date < current_time( 'timestamp' ) ) {
                // Discount has past the start date
                $return = true;
            } else {
                wpinv_set_error( 'wpinv-discount-error', __( 'This discount is not active yet.', 'invoicing' ) );
            }
        } else {
            // No start date for this discount, so has to be true
            $return = true;
        }
        
        if ( $return ) {
            $expiration = wpinv_get_discount_expiration( $code_id );
            
            if ( $expiration ) {
                $expiration = strtotime( $expiration );
                if ( $expiration < current_time( 'timestamp' ) ) {
                    // Discount is expired
                    wpinv_update_discount_status( $code_id, 'pending' );
                    $return = true;
                }
            }
        }
    }
    
    return apply_filters( 'wpinv_check_discount_dates', $return, $code_id );
}

function wpinv_is_discount_maxed_out( $code_id = null ) {
    $discount = wpinv_get_discount(  $code_id );
    $return   = false;

    if ( $discount ) {
        $uses = wpinv_get_discount_uses( $code_id );
        // Large number that will never be reached
        $max_uses = wpinv_get_discount_max_uses( $code_id );
        // Should never be greater than, but just in case
        if ( $uses >= $max_uses && ! empty( $max_uses ) ) {
            // Discount is maxed out
            wpinv_set_error( 'wpinv-discount-error', __( 'This discount has reached its maximum usage.', 'invoicing' ) );
            $return = true;
        }
    }

    return apply_filters( 'wpinv_is_discount_maxed_out', $return, $code_id );
}

function wpinv_discount_is_min_met( $code_id = null ) {
    $discount = wpinv_get_discount( $code_id );
    $return   = false;

    if ( $discount ) {
        $min         = (float)wpinv_get_discount_min_total( $code_id );
        $cart_amount = (float)wpinv_get_cart_discountable_subtotal( $code_id );

        if ( !$min > 0 || $cart_amount >= $min ) {
            // Minimum has been met
            $return = true;
        } else {
            wpinv_set_error( 'wpinv-discount-error', sprintf( __( 'Minimum invoice of %s not met.', 'invoicing' ), wpinv_price( wpinv_format_amount( $min ) ) ) );
        }
    }

    return apply_filters( 'wpinv_is_discount_min_met', $return, $code_id );
}

function wpinv_discount_is_max_met( $code_id = null ) {
    $discount = wpinv_get_discount( $code_id );
    $return   = false;

    if ( $discount ) {
        $max         = (float)wpinv_get_discount_max_total( $code_id );
        $cart_amount = (float)wpinv_get_cart_discountable_subtotal( $code_id );

        if ( !$max > 0 || $cart_amount <= $max ) {
            // Minimum has been met
            $return = true;
        } else {
            wpinv_set_error( 'wpinv-discount-error', sprintf( __( 'Maximum invoice of %s not met.', 'invoicing' ), wpinv_price( wpinv_format_amount( $max ) ) ) );
        }
    }

    return apply_filters( 'wpinv_is_discount_max_met', $return, $code_id );
}

function wpinv_discount_is_single_use( $code_id = 0 ) {
    $single_use = get_post_meta( $code_id, '_wpi_discount_is_single_use', true );
    return (bool) apply_filters( 'wpinv_is_discount_single_use', $single_use, $code_id );
}

function wpinv_discount_is_recurring( $code_id = 0, $code = false ) {
    if ( $code ) {
        $discount = wpinv_get_discount_by_code( $code_id );
        
        if ( !empty( $discount ) ) {
            $code_id = $discount->ID;
        }
    }
    
    $recurring = get_post_meta( $code_id, '_wpi_discount_is_recurring', true );
    
    return (bool) apply_filters( 'wpinv_is_discount_recurring', $recurring, $code_id, $code );
}

function wpinv_discount_item_reqs_met( $code_id = null ) {
    $item_reqs    = wpinv_get_discount_item_reqs( $code_id );
    $condition    = wpinv_get_discount_item_condition( $code_id );
    $excluded_ps  = wpinv_get_discount_excluded_items( $code_id );
    $cart_items   = wpinv_get_cart_contents();
    $cart_ids     = $cart_items ? wp_list_pluck( $cart_items, 'id' ) : null;
    $ret          = false;

    if ( empty( $item_reqs ) && empty( $excluded_ps ) ) {
        $ret = true;
    }

    // Normalize our data for item requirements, exclusions and cart data
    // First absint the items, then sort, and reset the array keys
    $item_reqs = array_map( 'absint', $item_reqs );
    asort( $item_reqs );
    $item_reqs = array_values( $item_reqs );

    $excluded_ps  = array_map( 'absint', $excluded_ps );
    asort( $excluded_ps );
    $excluded_ps  = array_values( $excluded_ps );

    $cart_ids     = array_map( 'absint', $cart_ids );
    asort( $cart_ids );
    $cart_ids     = array_values( $cart_ids );

    // Ensure we have requirements before proceeding
    if ( !$ret && ! empty( $item_reqs ) ) {
        switch( $condition ) {
            case 'all' :
                // Default back to true
                $ret = true;

                foreach ( $item_reqs as $item_id ) {
                    if ( !wpinv_item_in_cart( $item_id ) ) {
                        wpinv_set_error( 'wpinv-discount-error', __( 'The item requirements for this discount are not met.', 'invoicing' ) );
                        $ret = false;
                        break;
                    }
                }

                break;

            default : // Any
                foreach ( $item_reqs as $item_id ) {
                    if ( wpinv_item_in_cart( $item_id ) ) {
                        $ret = true;
                        break;
                    }
                }

                if( ! $ret ) {
                    wpinv_set_error( 'wpinv-discount-error', __( 'The item requirements for this discount are not met.', 'invoicing' ) );
                }

                break;
        }
    } else {
        $ret = true;
    }

    if( ! empty( $excluded_ps ) ) {
        // Check that there are items other than excluded ones in the cart
        if( $cart_ids == $excluded_ps ) {
            wpinv_set_error( 'wpinv-discount-error', __( 'This discount is not valid for the cart contents.', 'invoicing' ) );
            $ret = false;
        }
    }

    return (bool) apply_filters( 'wpinv_is_discount_item_req_met', $ret, $code_id, $condition );
}

function wpinv_is_discount_used( $code = null, $user = '', $code_id = 0 ) {
    global $wpi_checkout_id;
    
    $return = false;

    if ( empty( $code_id ) ) {
        $code_id = wpinv_get_discount_id_by_code( $code );
        
        if( empty( $code_id ) ) {
            return false; // No discount was found
        }
    }

    if ( wpinv_discount_is_single_use( $code_id ) ) {
        $payments = array();

        $user_id = 0;
        if ( is_int( $user ) ) {
            $user_id = absint( $user );
        } else if ( is_email( $user ) && $user_data = get_user_by( 'email', $user ) ) {
            $user_id = $user_data->ID;
        } else if ( $user_data = get_user_by( 'login', $user ) ) {
            $user_id = $user_data->ID;
        } else if ( absint( $user ) > 0 ) {
            $user_id = absint( $user );
        }

        if ( !empty( $user_id ) ) {
            $query    = array( 'user' => $user_id, 'limit' => false );
            $payments = wpinv_get_invoices( $query ); // Get all payments with matching user id
        }

        if ( $payments ) {
            foreach ( $payments as $payment ) {
                // Don't count discount used for current invoice chekcout.
                if ( !empty( $wpi_checkout_id ) && $wpi_checkout_id == $payment->ID ) {
                    continue;
                }
                
                if ( $payment->has_status( array( 'wpi-cancelled', 'wpi-failed' ) ) ) {
                    continue;
                }

                $discounts = $payment->get_discounts( true );
                if ( empty( $discounts ) ) {
                    continue;
                }

                $discounts = $discounts && !is_array( $discounts ) ? explode( ',', $discounts ) : $discounts;

                if ( !empty( $discounts ) && is_array( $discounts ) ) {
                    if ( in_array( strtolower( $code ), array_map( 'strtolower', $discounts ) ) ) {
                        wpinv_set_error( 'wpinv-discount-error', __( 'This discount has already been redeemed.', 'invoicing' ) );
                        $return = true;
                        break;
                    }
                }
            }
        }
    }

    return apply_filters( 'wpinv_is_discount_used', $return, $code, $user );
}

function wpinv_is_discount_valid( $code = '', $user = '', $set_error = true ) {
    $return      = false;
    $discount_id = wpinv_get_discount_id_by_code( $code );
    $user        = trim( $user );

    if ( wpinv_get_cart_contents() ) {
        if ( $discount_id ) {
            if (
                wpinv_is_discount_active( $discount_id ) &&
                wpinv_check_discount_dates( $discount_id ) &&
                !wpinv_is_discount_maxed_out( $discount_id ) &&
                !wpinv_is_discount_used( $code, $user, $discount_id ) &&
                wpinv_discount_is_min_met( $discount_id ) &&
                wpinv_discount_is_max_met( $discount_id ) &&
                wpinv_discount_item_reqs_met( $discount_id )
            ) {
                $return = true;
            }
        } elseif( $set_error ) {
            wpinv_set_error( 'wpinv-discount-error', __( 'This discount is invalid.', 'invoicing' ) );
        }
    }

    return apply_filters( 'wpinv_is_discount_valid', $return, $discount_id, $code, $user );
}

function wpinv_get_discount_id_by_code( $code ) {
    $discount = wpinv_get_discount_by_code( $code );
    if( $discount ) {
        return $discount->ID;
    }
    return false;
}

function wpinv_get_discounted_amount( $code, $base_price ) {
    $amount      = $base_price;
    $discount_id = wpinv_get_discount_id_by_code( $code );

    if( $discount_id ) {
        $type        = wpinv_get_discount_type( $discount_id );
        $rate        = wpinv_get_discount_amount( $discount_id );

        if ( $type == 'flat' ) {
            // Set amount
            $amount = $base_price - $rate;
            if ( $amount < 0 ) {
                $amount = 0;
            }

        } else {
            // Percentage discount
            $amount = $base_price - ( $base_price * ( $rate / 100 ) );
        }

    } else {

        $amount = $base_price;

    }

    return apply_filters( 'wpinv_discounted_amount', $amount );
}

function wpinv_increase_discount_usage( $code ) {

    $id   = wpinv_get_discount_id_by_code( $code );
    $uses = wpinv_get_discount_uses( $id );

    if ( $uses ) {
        $uses++;
    } else {
        $uses = 1;
    }

    update_post_meta( $id, '_wpi_discount_uses', $uses );

    do_action( 'wpinv_discount_increase_use_count', $uses, $id, $code );

    return $uses;

}

function wpinv_decrease_discount_usage( $code ) {

    $id   = wpinv_get_discount_id_by_code( $code );
    $uses = wpinv_get_discount_uses( $id );

    if ( $uses ) {
        $uses--;
    }

    if ( $uses < 0 ) {
        $uses = 0;
    }

    update_post_meta( $id, '_wpi_discount_uses', $uses );

    do_action( 'wpinv_discount_decrease_use_count', $uses, $id, $code );

    return $uses;

}

function wpinv_format_discount_rate( $type, $amount ) {
    if ( $type == 'flat' ) {
        return wpinv_price( wpinv_format_amount( $amount ) );
    } else {
        return $amount . '%';
    }
}

function wpinv_set_cart_discount( $code = '' ) {    
    if ( wpinv_multiple_discounts_allowed() ) {
        // Get all active cart discounts
        $discounts = wpinv_get_cart_discounts();
    } else {
        $discounts = false; // Only one discount allowed per purchase, so override any existing
    }

    if ( $discounts ) {
        $key = array_search( strtolower( $code ), array_map( 'strtolower', $discounts ) );
        if( false !== $key ) {
            unset( $discounts[ $key ] ); // Can't set the same discount more than once
        }
        $discounts[] = $code;
    } else {
        $discounts = array();
        $discounts[] = $code;
    }
    $discounts = array_values( $discounts );
    
    $data = wpinv_get_checkout_session();
    if ( empty( $data ) ) {
        $data = array();
    } else {
        if ( !empty( $data['invoice_id'] ) && $payment_meta = wpinv_get_invoice_meta( $data['invoice_id'] ) ) {
            $payment_meta['user_info']['discount']  = implode( ',', $discounts );
            update_post_meta( $data['invoice_id'], '_wpinv_payment_meta', $payment_meta );
        }
    }
    $data['cart_discounts'] = $discounts;
    
    wpinv_set_checkout_session( $data );
    
    return $discounts;
}

function wpinv_unset_cart_discount( $code = '' ) {    
    $discounts = wpinv_get_cart_discounts();

    if ( $code && !empty( $discounts ) && in_array( $code, $discounts ) ) {
        $key = array_search( $code, $discounts );
        unset( $discounts[ $key ] );
            
        $data = wpinv_get_checkout_session();
        $data['cart_discounts'] = $discounts;
        if ( !empty( $data['invoice_id'] ) && $payment_meta = wpinv_get_invoice_meta( $data['invoice_id'] ) ) {
            $payment_meta['user_info']['discount']  = !empty( $discounts ) ? implode( ',', $discounts ) : '';
            update_post_meta( $data['invoice_id'], '_wpinv_payment_meta', $payment_meta );
        }
        
        wpinv_set_checkout_session( $data );
    }

    return $discounts;
}

function wpinv_unset_all_cart_discounts() {
    $data = wpinv_get_checkout_session();
    
    if ( !empty( $data ) && isset( $data['cart_discounts'] ) ) {
        unset( $data['cart_discounts'] );
        
         wpinv_set_checkout_session( $data );
         return true;
    }
    
    return false;
}

function wpinv_get_cart_discounts( $items = array() ) {
    $session = wpinv_get_checkout_session();
    
    $discounts = !empty( $session['cart_discounts'] ) ? $session['cart_discounts'] : false;
    return $discounts;
}

function wpinv_cart_has_discounts( $items = array() ) {
    $ret = false;

    if ( wpinv_get_cart_discounts( $items ) ) {
        $ret = true;
    }
    
    /*
    $invoice = wpinv_get_invoice_cart();
    if ( !empty( $invoice ) && ( $invoice->get_discount() > 0 || $invoice->get_discount_code() ) ) {
        $ret = true;
    }
    */

    return apply_filters( 'wpinv_cart_has_discounts', $ret );
}

function wpinv_get_cart_discounted_amount( $items = array(), $discounts = false ) {
    $amount = 0.00;
    $items  = !empty( $items ) ? $items : wpinv_get_cart_content_details();

    if ( $items ) {
        $discounts = wp_list_pluck( $items, 'discount' );

        if ( is_array( $discounts ) ) {
            $discounts = array_map( 'floatval', $discounts );
            $amount    = array_sum( $discounts );
        }
    }

    return apply_filters( 'wpinv_get_cart_discounted_amount', $amount );
}

function wpinv_get_cart_items_discount_amount( $items = array(), $discount = false ) {
    $items  = !empty( $items ) ? $items : wpinv_get_cart_content_details();
    
    if ( empty( $discount ) || empty( $items ) ) {
        return 0;
    }

    $amount = 0;
    
    foreach ( $items as $item ) {
        $amount += wpinv_get_cart_item_discount_amount( $item, $discount );
    }
    
    $amount = wpinv_round_amount( $amount );

    return $amount;
}

function wpinv_get_cart_item_discount_amount( $item = array(), $discount = false ) {
    global $wpinv_is_last_cart_item, $wpinv_flat_discount_total;
    
    $amount = 0;

    if ( empty( $item ) || empty( $item['id'] ) ) {
        return $amount;
    }

    if ( empty( $item['quantity'] ) ) {
        return $amount;
    }

    if ( empty( $item['options'] ) ) {
        $item['options'] = array();
    }

    $price            = wpinv_get_cart_item_price( $item['id'], $item, $item['options'] );
    $discounted_price = $price;

    $discounts = false === $discount ? wpinv_get_cart_discounts() : $discount;
    if ( empty( $discounts ) ) {
        return $amount;
    }

    if ( $discounts ) {
        if ( is_array( $discounts ) ) {
            $discounts = array_values( $discounts );
        } else {
            $discounts = explode( ',', $discounts );
        }
    }

    if( $discounts ) {
        foreach ( $discounts as $discount ) {
            $code_id = wpinv_get_discount_id_by_code( $discount );

            // Check discount exists
            if( ! $code_id ) {
                continue;
            }

            $reqs           = wpinv_get_discount_item_reqs( $code_id );
            $excluded_items = wpinv_get_discount_excluded_items( $code_id );

            // Make sure requirements are set and that this discount shouldn't apply to the whole cart
            if ( !empty( $reqs ) && wpinv_is_discount_not_global( $code_id ) ) {
                foreach ( $reqs as $item_id ) {
                    if ( $item_id == $item['id'] && ! in_array( $item['id'], $excluded_items ) ) {
                        $discounted_price -= $price - wpinv_get_discounted_amount( $discount, $price );
                    }
                }
            } else {
                // This is a global cart discount
                if ( !in_array( $item['id'], $excluded_items ) ) {
                    if ( 'flat' === wpinv_get_discount_type( $code_id ) ) {
                        $items_subtotal    = 0.00;
                        $cart_items        = wpinv_get_cart_contents();
                        
                        foreach ( $cart_items as $cart_item ) {
                            if ( ! in_array( $cart_item['id'], $excluded_items ) ) {
                                $options = !empty( $cart_item['options'] ) ? $cart_item['options'] : array();
                                $item_price      = wpinv_get_cart_item_price( $cart_item['id'], $cart_item, $options );
                                $items_subtotal += $item_price * $cart_item['quantity'];
                            }
                        }

                        $subtotal_percent  = ( ( $price * $item['quantity'] ) / $items_subtotal );
                        $code_amount       = wpinv_get_discount_amount( $code_id );
                        $discounted_amount = $code_amount * $subtotal_percent;
                        $discounted_price -= $discounted_amount;

                        $wpinv_flat_discount_total += round( $discounted_amount, wpinv_currency_decimal_filter() );

                        if ( $wpinv_is_last_cart_item && $wpinv_flat_discount_total < $code_amount ) {
                            $adjustment = $code_amount - $wpinv_flat_discount_total;
                            $discounted_price -= $adjustment;
                        }
                    } else {
                        $discounted_price -= $price - wpinv_get_discounted_amount( $discount, $price );
                    }
                }
            }
        }

        $amount = ( $price - apply_filters( 'wpinv_get_cart_item_discounted_amount', $discounted_price, $discounts, $item, $price ) );

        if ( 'flat' !== wpinv_get_discount_type( $code_id ) ) {
            $amount = $amount * $item['quantity'];
        }
    }

    return $amount;
}

function wpinv_cart_discounts_html( $items = array() ) {
    echo wpinv_get_cart_discounts_html( $items );
}

function wpinv_get_cart_discounts_html( $items = array(), $discounts = false ) {
    global $wpi_cart_columns;
    
    $items  = !empty( $items ) ? $items : wpinv_get_cart_content_details();
    
    if ( !$discounts ) {
        $discounts = wpinv_get_cart_discounts( $items );
    }

    if ( !$discounts ) {
        return;
    }
    
    $discounts = is_array( $discounts ) ? $discounts : array( $discounts );
    
    $html = '';

    foreach ( $discounts as $discount ) {
        $discount_id    = wpinv_get_discount_id_by_code( $discount );
        $discount_value = wpinv_get_discount_amount( $discount_id );
        $rate           = wpinv_format_discount_rate( wpinv_get_discount_type( $discount_id ), $discount_value );
        $amount         = wpinv_get_cart_items_discount_amount( $items, $discount );
        $remove_btn     = '<a title="' . esc_attr__( 'Remove discount', 'invoicing' ) . '" data-code="' . $discount . '" data-value="' . $discount_value . '" class="wpi-discount-remove" href="javascript:void(0);">[<i class="fa fa-times" aria-hidden="true"></i>]</a> ';
        
        $html .= '<tr class="wpinv_cart_footer_row wpinv_cart_discount_row">';
        ob_start();
        do_action( 'wpinv_checkout_table_discount_first', $items );
        $html .= ob_get_clean();
        $html .= '<td class="wpinv_cart_discount_label text-right" colspan="' . $wpi_cart_columns . '">' . $remove_btn . '<strong>' . wpinv_cart_discount_label( $discount, $rate, false ) . '</strong></td><td class="wpinv_cart_discount text-right"><span data-discount="' . $amount . '" class="wpinv_cart_discount_amount">&ndash;' . wpinv_price( wpinv_format_amount( $amount ) ) . '</span></td>';
        ob_start();
        do_action( 'wpinv_checkout_table_discount_last', $items );
        $html .= ob_get_clean();
        $html .= '</tr>';
    }

    return apply_filters( 'wpinv_get_cart_discounts_html', $html, $discounts, $rate );
}

function wpinv_display_cart_discount( $formatted = false, $echo = false ) {
    $discounts = wpinv_get_cart_discounts();

    if ( empty( $discounts ) ) {
        return false;
    }

    $discount_id  = wpinv_get_discount_id_by_code( $discounts[0] );
    $amount       = wpinv_format_discount_rate( wpinv_get_discount_type( $discount_id ), wpinv_get_discount_amount( $discount_id ) );

    if ( $echo ) {
        echo $amount;
    }

    return $amount;
}

function wpinv_remove_cart_discount() {
    if ( !isset( $_GET['discount_id'] ) || ! isset( $_GET['discount_code'] ) ) {
        return;
    }

    do_action( 'wpinv_pre_remove_cart_discount', absint( $_GET['discount_id'] ) );

    wpinv_unset_cart_discount( urldecode( $_GET['discount_code'] ) );

    do_action( 'wpinv_post_remove_cart_discount', absint( $_GET['discount_id'] ) );

    wp_redirect( wpinv_get_checkout_uri() ); wpinv_die();
}
add_action( 'wpinv_remove_cart_discount', 'wpinv_remove_cart_discount' );

function wpinv_maybe_remove_cart_discount( $cart_key = 0 ) {
    $discounts = wpinv_get_cart_discounts();

    if ( !$discounts ) {
        return;
    }

    foreach ( $discounts as $discount ) {
        if ( !wpinv_is_discount_valid( $discount ) ) {
            wpinv_unset_cart_discount( $discount );
        }
    }
}
add_action( 'wpinv_post_remove_from_cart', 'wpinv_maybe_remove_cart_discount' );

function wpinv_multiple_discounts_allowed() {
    $ret = wpinv_get_option( 'allow_multiple_discounts', false );
    return (bool) apply_filters( 'wpinv_multiple_discounts_allowed', $ret );
}

function wpinv_listen_for_cart_discount() {
    global $wpi_session;
    
    if ( empty( $_REQUEST['discount'] ) || is_array( $_REQUEST['discount'] ) ) {
        return;
    }

    $code = preg_replace('/[^a-zA-Z0-9-_]+/', '', $_REQUEST['discount'] );

    $wpi_session->set( 'preset_discount', $code );
}
//add_action( 'init', 'wpinv_listen_for_cart_discount', 0 );

function wpinv_apply_preset_discount() {
    global $wpi_session;
    
    $code = $wpi_session->get( 'preset_discount' );

    if ( !$code ) {
        return;
    }

    if ( !wpinv_is_discount_valid( $code, '', false ) ) {
        return;
    }
    
    $code = apply_filters( 'wpinv_apply_preset_discount', $code );

    wpinv_set_cart_discount( $code );

    $wpi_session->set( 'preset_discount', null );
}
//add_action( 'init', 'wpinv_apply_preset_discount', 999 );

function wpinv_get_discount_label( $code, $echo = true ) {
    $label = wp_sprintf( __( 'Discount%1$s', 'invoicing' ), ( $code != '' && $code != 'none' ? ' (<code>' . $code . '</code>)': '' ) );
    $label = apply_filters( 'wpinv_get_discount_label', $label, $code );

    if ( $echo ) {
        echo $label;
    } else {
        return $label;
    }
}

function wpinv_cart_discount_label( $code, $rate, $echo = true ) {
    $label = wp_sprintf( __( '%1$s Discount: %2$s', 'invoicing' ), $rate, $code );
    $label = apply_filters( 'wpinv_cart_discount_label', $label, $code, $rate );

    if ( $echo ) {
        echo $label;
    } else {
        return $label;
    }
}

function wpinv_check_delete_discount( $check, $post, $force_delete ) {
    if ( $post->post_type == 'wpi_discount' && wpinv_get_discount_uses( $post->ID ) > 0 ) {
        return true;
    }
    
    return $check;
}
add_filter( 'pre_delete_post', 'wpinv_check_delete_discount', 10, 3 );

function wpinv_checkout_form_validate_discounts() {
    $discounts = wpinv_get_cart_discounts();
    
    if ( !empty( $discounts ) ) {
        $invalid = false;
        
        foreach ( $discounts as $key => $code ) {
            if ( !wpinv_is_discount_valid( $code, get_current_user_id() ) ) {
                $invalid = true;
                
                wpinv_unset_cart_discount( $code );
            }
        }
        
        if ( $invalid ) {
            $errors = wpinv_get_errors();
            $error  = !empty( $errors['wpinv-discount-error'] ) ? $errors['wpinv-discount-error'] . ' ' : '';
            $error  .= __( 'The discount has been removed from cart.', 'invoicing' );
            wpinv_set_error( 'wpinv-discount-error', $error );
            
            wpinv_recalculate_tax( true );
        }
    }
}
add_action( 'wpinv_before_checkout_form', 'wpinv_checkout_form_validate_discounts', -10 );

function wpinv_discount_amount() {
    $output = 0.00;
    
    return apply_filters( 'wpinv_discount_amount', $output );
}