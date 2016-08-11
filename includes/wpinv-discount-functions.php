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
                        'fixed'     => __( 'Fixed', 'invoicing' ),
                        'percent'   => __( 'Percentage', 'invoicing' ),
                    );
    return (array)apply_filters( 'wpinv_discount_types', $discount_types );
}

function wpinv_get_discount_type_name( $type = '' ) {
    $types = wpinv_get_discount_types();
    return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

function wpinv_get_discounts( $args = array() ) {
    $defaults = array(
        'post_type'      => 'wpinv_discount',
        'posts_per_page' => 20,
        'paged'          => null,
        'post_status'    => array( 'active', 'inactive', 'expired' )
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

    $discount = get_post( $discount_id );

    if ( get_post_type( $discount_id ) != 'wpinv_discount' ) {
        return false;
    }

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
            $discount = wpinv_get_discounts( array(
                'meta_key'       => '_wpinv_discount_code',
                'meta_value'     => $value,
                'posts_per_page' => 1,
                'post_status'    => 'any'
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
                'post_type'      => 'wpinv_discount',
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

function wpinv_store_discount( $details, $discount_id = null ) {
    $meta = array(
        'code'              => isset( $details['code'] )             ? $details['code']              : '',
        'name'              => isset( $details['name'] )             ? $details['name']              : '',
        'status'            => isset( $details['status'] )           ? $details['status']            : 'active',
        'uses'              => isset( $details['uses'] )             ? $details['uses']              : '',
        'max_uses'          => isset( $details['max'] )              ? $details['max']               : '',
        'amount'            => isset( $details['amount'] )           ? $details['amount']            : '',
        'start'             => isset( $details['start'] )            ? $details['start']             : '',
        'expiration'        => isset( $details['expiration'] )       ? $details['expiration']        : '',
        'type'              => isset( $details['type'] )             ? $details['type']              : '',
        'min_price'         => isset( $details['min_price'] )        ? $details['min_price']         : '',
        'product_reqs'      => isset( $details['products'] )         ? $details['products']          : array(),
        'product_condition' => isset( $details['product_condition'] )? $details['product_condition'] : '',
        'excluded_products' => isset( $details['excluded-products'] )? $details['excluded-products'] : array(),
        'is_not_global'     => isset( $details['not_global'] )       ? $details['not_global']        : false,
        'is_single_use'     => isset( $details['use_once'] )         ? $details['use_once']          : false,
    );

    $start_timestamp        = strtotime( $meta['start'] );

    if( ! empty( $meta['start'] ) ) {
        $meta['start']      = date( 'm/d/Y H:i:s', $start_timestamp );
    }

    if( ! empty( $meta['expiration'] ) ) {
        $meta['expiration'] = date( 'm/d/Y H:i:s', strtotime( date( 'm/d/Y', strtotime( $meta['expiration'] ) ) . ' 23:59:59' ) );
        $end_timestamp      = strtotime( $meta['expiration'] );

        if( ! empty( $meta['start'] ) && $start_timestamp > $end_timestamp ) {
            // Set the expiration date to the start date if start is later than expiration
            $meta['expiration'] = $meta['start'];
        }
    }

    if( ! empty( $meta['product_reqs'] ) ) {
        foreach( $meta['product_reqs'] as $key => $product ) {
            if( 0 === intval( $product ) ) {
                unset( $meta['product_reqs'][ $key ] );
            }
        }
    }

    if( ! empty( $meta['excluded_products'] ) ) {
        foreach( $meta['excluded_products'] as $key => $product ) {
            if( 0 === intval( $product ) ) {
                unset( $meta['excluded_products'][ $key ] );
            }
        }
    }

    if ( !empty( $discount_id ) && wpinv_discount_exists( $discount_id ) ) {
        // Update an existing discount
        $meta = apply_filters( 'wpinv_update_discount', $meta, $discount_id );

        do_action( 'wpinv_pre_update_discount', $meta, $discount_id );

        wp_update_post( array(
            'ID'          => $discount_id,
            'post_title'  => $meta['name'],
            'post_status' => $meta['status']
        ) );

        foreach( $meta as $key => $value ) {
            update_post_meta( $discount_id, '_wpinv_discount_' . $key, $value );
        }

        do_action( 'wpinv_post_update_discount', $meta, $discount_id );

        // Discount code updated
        return $discount_id;

    } else {
        // Add the discount
        $meta = apply_filters( 'wpinv_insert_discount', $meta );

        do_action( 'wpinv_pre_insert_discount', $meta );

        $discount_id = wp_insert_post( array(
            'post_type'   => 'wpinv_discount',
            'post_title'  => $meta['name'],
            'post_status' => 'active'
        ) );

        foreach( $meta as $key => $value ) {
            update_post_meta( $discount_id, '_wpinv_discount_' . $key, $value );
        }

        do_action( 'wpinv_post_insert_discount', $meta, $discount_id );

        // Discount code created
        return $discount_id;
    }

}

function wpinv_remove_discount( $discount_id = 0 ) {
    do_action( 'wpinv_pre_delete_discount', $discount_id );

    wp_delete_post( $discount_id, true );

    do_action( 'wpinv_post_delete_discount', $discount_id );
}

function wpinv_update_discount_status( $code_id = 0, $new_status = 'active' ) {
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
                wpinv_set_error( 'wpinv-discount-error', __( 'This discount is expired.', 'wpinv_' ) );
            }
        } elseif ( $discount->post_status == 'active' ) {
            $return = true;
        } else {
            if( defined( 'DOING_AJAX' ) ) {
                wpinv_set_error( 'wpinv-discount-error', __( 'This discount is not active.', 'wpinv_' ) );
            }
        }
    }

    return apply_filters( 'wpinv_is_discount_active', $return, $code_id );
}

function wpinv_get_discount_code( $code_id = null ) {
    $code = get_post_meta( $code_id, '_wpinv_discount_code', true );

    return apply_filters( 'wpinv_get_discount_code', $code, $code_id );
}

function wpinv_get_discount_start_date( $code_id = null ) {
    $start_date = get_post_meta( $code_id, '_wpinv_discount_start', true );

    return apply_filters( 'wpinv_get_discount_start_date', $start_date, $code_id );
}

function wpinv_get_discount_expiration( $code_id = null ) {
    $expiration = get_post_meta( $code_id, '_wpinv_discount_expiration', true );

    return apply_filters( 'wpinv_get_discount_expiration', $expiration, $code_id );
}

function wpinv_get_discount_max_uses( $code_id = null ) {
    $max_uses = get_post_meta( $code_id, '_wpinv_discount_max_uses', true );

    return (int) apply_filters( 'wpinv_get_discount_max_uses', $max_uses, $code_id );
}

function wpinv_get_discount_uses( $code_id = null ) {
    $uses = get_post_meta( $code_id, '_wpinv_discount_uses', true );

    return (int) apply_filters( 'wpinv_get_discount_uses', $uses, $code_id );
}

function wpinv_get_discount_min_price( $code_id = null ) {
    $min_price = get_post_meta( $code_id, '_wpinv_discount_min_price', true );

    return (float) apply_filters( 'wpinv_get_discount_min_price', $min_price, $code_id );
}

function wpinv_get_discount_amount( $code_id = null ) {
    $amount = get_post_meta( $code_id, '_wpinv_discount_amount', true );

    return (float) apply_filters( 'wpinv_get_discount_amount', $amount, $code_id );
}

function wpinv_get_discount_type( $code_id = null, $name = false ) {
    $type = strtolower( get_post_meta( $code_id, '_wpinv_discount_type', true ) );
    
    if ( $name ) {
        $name = wpinv_get_discount_type_name( $type );
        
        return apply_filters( 'wpinv_get_discount_type_name', $name, $code_id );
    }

    return apply_filters( 'wpinv_get_discount_type', $type, $code_id, $title );
}

function wpinv_discount_status( $status ) {
    switch( $status ){
        case 'expired' :
            $name = __( 'Expired', 'invoicing' );
            break;
        case 'inactive' :
            $name = __( 'Inactive', 'invoicing' );
            break;
        case 'active' :
        default :
            $name = __( 'Active', 'invoicing' );
            break;
    }
    return $name;
}

function wpinv_get_discount_excluded_products( $code_id = null ) {
    $excluded_products = get_post_meta( $code_id, '_wpinv_discount_excluded_products', true );

    if ( empty( $excluded_products ) || ! is_array( $excluded_products ) ) {
        $excluded_products = array();
    }

    return (array) apply_filters( 'wpinv_get_discount_excluded_products', $excluded_products, $code_id );
}

function wpinv_get_discount_product_reqs( $code_id = null ) {
    $product_reqs = get_post_meta( $code_id, '_wpinv_discount_product_reqs', true );

    if ( empty( $product_reqs ) || ! is_array( $product_reqs ) ) {
        $product_reqs = array();
    }

    return (array) apply_filters( 'wpinv_get_discount_product_reqs', $product_reqs, $code_id );
}

function wpinv_get_discount_product_condition( $code_id = 0 ) {
    return get_post_meta( $code_id, '_wpinv_discount_product_condition', true );
}

function wpinv_is_discount_not_global( $code_id = 0 ) {
    return (bool) get_post_meta( $code_id, '_wpinv_discount_is_not_global', true );
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
                wpinv_update_discount_status( $code_id, 'inactive' );
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
                // Discount has pased the start date
                $return = true;
            } else {
                wpinv_set_error( 'wpinv-discount-error', __( 'This discount is not active yet.', 'wpinv_' ) );
            }
        } else {
            // No start date for this discount, so has to be true
            $return = true;
        }
    }

    return apply_filters( 'wpinv_is_discount_started', $return, $code_id );
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
            wpinv_set_error( 'wpinv-discount-error', __( 'This discount has reached its maximum usage.', 'wpinv_' ) );
            $return = true;
        }
    }

    return apply_filters( 'wpinv_is_discount_maxed_out', $return, $code_id );
}

function wpinv_discount_is_min_met( $code_id = null ) {
    $discount = wpinv_get_discount( $code_id );
    $return   = false;

    if ( $discount ) {
        $min         = wpinv_get_discount_min_price( $code_id );
        $cart_amount = wpinv_get_cart_discountable_subtotal( $code_id );

        if ( (float) $cart_amount >= (float) $min ) {
            // Minimum has been met
            $return = true;
        } else {
            wpinv_set_error( 'wpinv-discount-error', sprintf( __( 'Minimum order of %s not met.', 'wpinv_' ), wpinv_currency_filter( wpinv_format_amount( $min ) ) ) );
        }
    }

    return apply_filters( 'wpinv_is_discount_min_met', $return, $code_id );
}

function wpinv_discount_is_single_use( $code_id = 0 ) {
    $single_use = get_post_meta( $code_id, '_wpinv_discount_is_single_use', true );
    return (bool) apply_filters( 'wpinv_is_discount_single_use', $single_use, $code_id );
}

function wpinv_discount_product_reqs_met( $code_id = null ) {
    $product_reqs = wpinv_get_discount_product_reqs( $code_id );
    $condition    = wpinv_get_discount_product_condition( $code_id );
    $excluded_ps  = wpinv_get_discount_excluded_products( $code_id );
    $cart_items   = wpinv_get_cart_contents();
    $cart_ids     = $cart_items ? wp_list_pluck( $cart_items, 'id' ) : null;
    $ret          = false;

    if ( empty( $product_reqs ) && empty( $excluded_ps ) ) {
        $ret = true;
    }

    // Normalize our data for product requiremetns, exlusions and cart data
    // First absint the items, then sort, and reset the array keys
    $product_reqs = array_map( 'absint', $product_reqs );
    asort( $product_reqs );
    $product_reqs = array_values( $product_reqs );

    $excluded_ps  = array_map( 'absint', $excluded_ps );
    asort( $excluded_ps );
    $excluded_ps  = array_values( $excluded_ps );

    $cart_ids     = array_map( 'absint', $cart_ids );
    asort( $cart_ids );
    $cart_ids     = array_values( $cart_ids );

    // Ensure we have requirements before proceeding
    if ( !$ret && ! empty( $product_reqs ) ) {
        switch( $condition ) {
            case 'all' :
                // Default back to true
                $ret = true;

                foreach ( $product_reqs as $download_id ) {
                    if ( !wpinv_item_in_cart( $download_id ) ) {
                        wpinv_set_error( 'wpinv-discount-error', __( 'The product requirements for this discount are not met.', 'wpinv_' ) );
                        $ret = false;
                        break;
                    }
                }

                break;

            default : // Any
                foreach ( $product_reqs as $download_id ) {
                    if ( wpinv_item_in_cart( $download_id ) ) {
                        $ret = true;
                        break;
                    }
                }

                if( ! $ret ) {
                    wpinv_set_error( 'wpinv-discount-error', __( 'The product requirements for this discount are not met.', 'wpinv_' ) );
                }

                break;
        }
    } else {
        $ret = true;
    }

    if( ! empty( $excluded_ps ) ) {
        // Check that there are products other than excluded ones in the cart
        if( $cart_ids == $excluded_ps ) {
            wpinv_set_error( 'wpinv-discount-error', __( 'This discount is not valid for the cart contents.', 'wpinv_' ) );
            $ret = false;
        }
    }

    return (bool) apply_filters( 'wpinv_is_discount_products_req_met', $ret, $code_id, $condition );
}

function wpinv_is_discount_used( $code = null, $user = '', $code_id = 0 ) {
    $return = false;

    if ( empty( $code_id ) ) {
        $code_id = wpinv_get_discount_id_by_code( $code );
        if( empty( $code_id ) ) {
            return false; // No discount was found
        }
    }

    if ( wpinv_discount_is_single_use( $code_id ) ) {
        $payments = array();

        /*if (  WPInv()->customers->installed() ) {
            $by_user_id = is_email( $user ) ? false : true;
            $customer = new WPInv_Customer( $user, $by_user_id );

            $payments = explode( ',', $customer->payment_ids );
        } else {*/
            $user_found = false;

            if ( is_email( $user ) ) {
                $user_found = true; // All we need is the email
                $key        = 'wpinv_payment_user_email';
                $value      = $user;
            } else {
                $user_data = get_user_by( 'login', $user );

                if ( $user_data ) {
                    $user_found = true;
                    $key        = 'wpinv_payment_user_id';
                    $value      = $user_data->ID;
                }
            }

            if ( $user_found ) {
                $query_args = array(
                    'post_type'       => 'wpinv_payment',
                    'meta_query'      => array(
                        array(
                            'key'     => $key,
                            'value'   => $value,
                            'compare' => '='
                        )
                    ),
                    'fields'          => 'ids'
                );

                $payments = get_posts( $query_args ); // Get all payments with matching email
            }
        //}

        if ( $payments ) {
            foreach ( $payments as $payment ) {
                $payment = new WPInv_Invoice( $payment );

                if( empty( $payment->discounts ) ) {
                    continue;
                }

                if( in_array( $payment->status, array( 'cancelled', 'failed' ) ) ) {
                    continue;
                }

                $discounts = explode( ',', $payment->discounts );

                if( is_array( $discounts ) ) {
                    if( in_array( strtolower( $code ), $discounts ) ) {
                        wpinv_set_error( 'wpinv-discount-error', __( 'This discount has already been redeemed.', 'wpinv_' ) );
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

    if( wpinv_get_cart_contents() ) {

        if ( $discount_id ) {
            if (
                wpinv_is_discount_active( $discount_id ) &&
                wpinv_is_discount_started( $discount_id ) &&
                !wpinv_is_discount_maxed_out( $discount_id ) &&
                !wpinv_is_discount_used( $code, $user, $discount_id ) &&
                wpinv_discount_is_min_met( $discount_id ) &&
                wpinv_discount_product_reqs_met( $discount_id )
            ) {
                $return = true;
            }
        } elseif( $set_error ) {
            wpinv_set_error( 'wpinv-discount-error', __( 'This discount is invalid.', 'wpinv_' ) );
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

    update_post_meta( $id, '_wpinv_discount_uses', $uses );

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

    update_post_meta( $id, '_wpinv_discount_uses', $uses );

    do_action( 'wpinv_discount_decrease_use_count', $uses, $id, $code );

    return $uses;

}

function wpinv_format_discount_rate( $type, $amount ) {
    if ( $type == 'flat' ) {
        return wpinv_currency_filter( wpinv_format_amount( $amount ) );
    } else {
        return $amount . '%';
    }
}

function wpinv_set_cart_discount( $code = '' ) {
    global $wpi_session;
    
    if( wpinv_multiple_discounts_allowed() ) {
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

    $wpi_session->set( 'cart_discounts', implode( '|', $discounts ) );

    return $discounts;
}

function wpinv_unset_cart_discount( $code = '' ) {
    global $wpi_session;
    
    $discounts = wpinv_get_cart_discounts();

    if ( $discounts ) {
        $key = array_search( $code, $discounts );
        unset( $discounts[ $key ] );
        $discounts = implode( '|', array_values( $discounts ) );
        // update the active discounts
        $wpi_session->set( 'cart_discounts', $discounts );
    }

    return $discounts;
}

function wpinv_unset_all_cart_discounts() {
    global $wpi_session;
    
    $wpi_session->set( 'cart_discounts', null );
}

function wpinv_get_cart_discounts( $items = array() ) {
    global $wpi_session;
    
    $discounts = $wpi_session->get( 'cart_discounts' );
    $discounts = ! empty( $discounts ) ? explode( '|', $discounts ) : false;
    return $discounts;
}

function wpinv_cart_has_discounts( $items = array() ) {
    $ret = false;

    /*
    if ( wpinv_get_cart_discounts( $items ) ) {
        $ret = true;
    }
    */
    
    $invoice = wpinv_get_invoice_cart();
    if ( !empty( $invoice ) && ( $invoice->get_discount() > 0 || $invoice->get_discount_code() ) ) {
        $ret = true;
    }

    return apply_filters( 'wpinv_cart_has_discounts', $ret );
}

function wpinv_get_cart_discounted_amount( $items = array(), $discounts = false ) {
    $amount = wpinv_get_cart_discount();// wpinv_discount();

    return apply_filters( 'wpinv_get_cart_discounted_amount', $amount, $items );
}

function wpinv_get_cart_item_discount_amount( $item = array() ) {
    global $wpinv_is_last_cart_item, $wpinv_flat_discount_total;

    if ( empty( $item ) || empty( $item['id'] ) ) {
        return 0;
    }

    if ( empty( $item['quantity'] ) ) {
        return 0;
    }

    if ( !isset( $item['options'] ) ) {
        $item['options'] = array();
    }

    $amount           = 0;
    $price            = wpinv_get_cart_item_price( $item['id'], $item['options'] );
    $discounted_price = $price;

    $discounts = wpinv_get_cart_discounts();

    if( $discounts ) {
        foreach ( $discounts as $discount ) {
            $code_id = wpinv_get_discount_id_by_code( $discount );

            // Check discount exists
            if( ! $code_id ) {
                continue;
            }

            $reqs              = wpinv_get_discount_product_reqs( $code_id );
            $excluded_products = wpinv_get_discount_excluded_products( $code_id );

            // Make sure requirements are set and that this discount shouldn't apply to the whole cart
            if ( !empty( $reqs ) && wpinv_is_discount_not_global( $code_id ) ) {
                foreach ( $reqs as $download_id ) {

                    if ( $download_id == $item['id'] && ! in_array( $item['id'], $excluded_products ) ) {
                        $discounted_price -= $price - wpinv_get_discounted_amount( $discount, $price );
                    }
                }
            } else {

                // This is a global cart discount
                if( ! in_array( $item['id'], $excluded_products ) ) {
                    if( 'flat' === wpinv_get_discount_type( $code_id ) ) {
                        $items_subtotal    = 0.00;
                        $cart_items        = wpinv_get_cart_contents();
                        foreach( $cart_items as $cart_item ) {
                            if( ! in_array( $cart_item['id'], $excluded_products ) ) {
                                $item_price      = wpinv_get_cart_item_price( $cart_item['id'], $cart_item['options'] );
                                $items_subtotal += $item_price * $cart_item['quantity'];
                            }
                        }

                        $subtotal_percent  = ( ( $price * $item['quantity'] ) / $items_subtotal );
                        $code_amount       = wpinv_get_discount_amount( $code_id );
                        $discounted_amount = $code_amount * $subtotal_percent;
                        $discounted_price -= $discounted_amount;

                        $wpinv_flat_discount_total += round( $discounted_amount, wpinv_decimal_seperator() );

                        if( $wpinv_is_last_cart_item && $wpinv_flat_discount_total < $code_amount ) {
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

        if( 'flat' !== wpinv_get_discount_type( $code_id ) ) {
            $amount = $amount * $item['quantity'];
        }
    }

    return $amount;
}

function wpinv_cart_discounts_html( $items = array() ) {
    echo wpinv_get_cart_discounts_html( $items );
}

function wpinv_get_cart_discounts_html( $items = array(), $discounts = false ) {
    if ( !$discounts ) {
        $discounts = wpinv_get_cart_discounts( $items );
    }

    if ( !$discounts ) {
        return;
    }

    $html = '';

    foreach ( $discounts as $discount ) {
        $discount_id  = wpinv_get_discount_id_by_code( $discount );
        $rate         = wpinv_format_discount_rate( wpinv_get_discount_type( $discount_id ), wpinv_get_discount_amount( $discount_id ) );

        $remove_url   = add_query_arg(
            array(
                'wpinv_action'    => 'remove_cart_discount',
                'discount_id'   => $discount_id,
                'discount_code' => $discount
            ),
            wpinv_get_checkout_uri()
        );

        $html .= "<span class=\"wpinv_discount\">\n";
            $html .= "<span class=\"wpinv_discount_rate\">$discount&nbsp;&ndash;&nbsp;$rate</span>\n";
            $html .= "<a href=\"$remove_url\" data-code=\"$discount\" class=\"wpinv_discount_remove\"></a>\n";
        $html .= "</span>\n";
    }

    return apply_filters( 'wpinv_get_cart_discounts_html', $html, $discounts, $rate, $remove_url );
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
