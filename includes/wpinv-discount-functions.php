<?php
/**
 * Contains discount functions.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of discount type.
 * 
 * @return array
 */
function wpinv_get_discount_types() {
    return apply_filters(
        'wpinv_discount_types',
        array(
            'percent'   => __( 'Percentage', 'invoicing' ),
            'flat'     => __( 'Flat Amount', 'invoicing' ),
        )
    );
}

/**
 * Returns the name of a discount type.
 * 
 * @return string
 */
function wpinv_get_discount_type_name( $type = '' ) {
    $types = wpinv_get_discount_types();
    return isset( $types[ $type ] ) ? $types[ $type ] : $type;
}

/**
 * Deletes a discount via the admin page.
 * 
 * @return string
 */
function wpinv_delete_discount( $data ) {

    if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'wpinv_discount_nonce' ) ) {
        exit;
    }

    if( ! wpinv_current_user_can_manage_invoicing() ) {
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

    if( ! wpinv_current_user_can_manage_invoicing() ) {
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

    if( ! wpinv_current_user_can_manage_invoicing() ) {
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
        $args['meta_key']     = '_wpi_discount_code';
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

function wpinv_get_all_discounts( $args = array() ) {

    $args = wp_parse_args( $args, array(
        'status'         => array( 'publish' ),
        'limit'          => get_option( 'posts_per_page' ),
        'page'           => 1,
        'exclude'        => array(),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'type'           => array_keys( wpinv_get_discount_types() ),
        'meta_query'     => array(),
        'return'         => 'objects',
        'paginate'       => false,
    ) );

    $wp_query_args = array(
        'post_type'      => 'wpi_discount',
        'post_status'    => $args['status'],
        'posts_per_page' => $args['limit'],
        'meta_query'     => $args['meta_query'],
        'fields'         => 'ids',
        'orderby'        => $args['orderby'],
        'order'          => $args['order'],
        'paged'          => absint( $args['page'] ),
    );

    if ( ! empty( $args['exclude'] ) ) {
        $wp_query_args['post__not_in'] = array_map( 'absint', $args['exclude'] );
    }

    if ( ! $args['paginate' ] ) {
        $wp_query_args['no_found_rows'] = true;
    }

    if ( ! empty( $args['search'] ) ) {

        $wp_query_args['meta_query'][] = array(
            'key'     => '_wpi_discount_code',
            'value'   => $args['search'],
            'compare' => 'LIKE',
        );

    }

    if ( ! empty( $args['type'] ) ) {
        $types = wpinv_parse_list( $args['type'] );
        $wp_query_args['meta_query'][] = array(
            'key'     => '_wpi_discount_type',
            'value'   => implode( ',', $types ),
            'compare' => 'IN',
        );
    }

    $wp_query_args = apply_filters('wpinv_get_discount_args', $wp_query_args, $args);

    // Get results.
    $discounts = new WP_Query( $wp_query_args );

    if ( 'objects' === $args['return'] ) {
        $return = array_map( 'get_post', $discounts->posts );
    } elseif ( 'self' === $args['return'] ) {
        return $discounts;
    } else {
        $return = $discounts->posts;
    }

    if ( $args['paginate' ] ) {
        return (object) array(
            'discounts'      => $return,
            'total'         => $discounts->found_posts,
            'max_num_pages' => $discounts->max_num_pages,
        );
    } else {
        return $return;
    }

}

function wpinv_has_active_discounts() {
    $has_active = false;

    $discounts  = wpinv_get_discounts();

    if ( $discounts) {
        foreach ( $discounts as $discount ) {
            if ( wpinv_is_discount_active( $discount->ID, true ) ) {
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

/**
 * Fetches a discount object.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @since 1.0.15
 * @return WPInv_Discount
 */
function wpinv_get_discount_obj( $discount = 0 ) {
    return new WPInv_Discount( $discount );
}

/**
 * Fetch a discount from the db/cache using its discount code.
 *
 * @param string $code The discount code.
 * @return bool|WPInv_Discount
 */
function wpinv_get_discount_by_code( $code = '' ) {
    return wpinv_get_discount_by( null, $code );
}

/**
 * Fetch a discount from the db/cache using a given field.
 *
 * @param string $deprecated deprecated
 * @param string|int $value The field value
 * @return bool|WPInv_Discount
 */
function wpinv_get_discount_by( $deprecated = null, $value = '' ) {
    $discount = new WPInv_Discount( $value );

    if ( $discount->get_id() != 0 ) {
        return $discount;
    }

    return  false;
}

/**
 * Updates a discount in the database.
 *
 * @param int $post_id The discount's ID.
 * @param array $data The discount's properties.
 * @return bool
 */
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

    if ( $meta['type'] == 'percent' && (float)$meta['amount'] > 100 ) {
        $meta['amount'] = 100;
    }

    if ( !empty( $meta['start'] ) ) {
        $meta['start']      = date_i18n( 'Y-m-d H:i:s', strtotime( $meta['start'] ) );
    }

    if ( !empty( $meta['expiration'] ) ) {
        $meta['expiration'] = date_i18n( 'Y-m-d H:i:s', strtotime( $meta['expiration'] ) );

        if ( !empty( $meta['start'] ) && strtotime( $meta['start'] ) > strtotime( $meta['expiration'] ) ) {
            $meta['expiration'] = $meta['start'];
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

/**
 * Delectes a discount from the database.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return bool
 */
function wpinv_remove_discount( $discount = 0 ) {

    $discount = wpinv_get_discount_obj( $discount );
    if( ! $discount->exists() ) {
        return false;
    }

    $discount->remove();
    return true;
}

/**
 * Updates a discount status.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param string $new_status
 * @return bool
 */
function wpinv_update_discount_status( $discount = 0, $new_status = 'publish' ) {
    $discount = wpinv_get_discount_obj( $discount );
    return $discount->update_status( $new_status );
}

/**
 * Checks if a discount exists.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return bool
 */
function wpinv_discount_exists( $discount ) {
    $discount = wpinv_get_discount_obj( $discount );
    return $discount->exists();
}

function wpinv_is_discount_active( $code_id = null, $silent = false ) {
    $discount = wpinv_get_discount(  $code_id );
    $return   = false;

    if ( $discount ) {
        if ( wpinv_is_discount_expired( $code_id, $silent ) ) {
            if( defined( 'DOING_AJAX' ) && ! $silent ) {
                wpinv_set_error( 'wpinv-discount-error', __( 'This discount is expired.', 'invoicing' ) );
            }
        } elseif ( $discount->post_status == 'publish' ) {
            $return = true;
        } else {
            if( defined( 'DOING_AJAX' ) && ! $silent ) {
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

/**
 * Returns the number of maximum number of times a discount can been used.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return int
 */
function wpinv_get_discount_max_uses( $discount = array() ) {
    $discount = wpinv_get_discount_obj( $discount );
    return (int) $discount->max_uses;
}

/**
 * Returns the number of times a discount has been used.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return int
 */
function wpinv_get_discount_uses( $discount = array() ) {
    $discount = wpinv_get_discount_obj( $discount );
    return (int) $discount->uses;
}

/**
 * Returns the minimum invoice amount required to use a discount.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return float
 */
function wpinv_get_discount_min_total( $discount = array() ) {
    $discount = wpinv_get_discount_obj( $discount );
    return (float) $discount->min_total;
}

/**
 * Returns the maximum invoice amount required to use a discount.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return float
 */
function wpinv_get_discount_max_total( $discount = array() ) {
    $discount = wpinv_get_discount_obj( $discount );
    return (float) $discount->max_total;
}

/**
 * Returns a discount's amount.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return float
 */
function wpinv_get_discount_amount( $discount = array() ) {
    $discount = wpinv_get_discount_obj( $discount );
    return (float) $discount->amount;
}

/**
 * Returns a discount's type.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param bool $name
 * @return string
 */
function wpinv_get_discount_type( $discount = array(), $name = false ) {
    $discount = wpinv_get_discount_obj( $discount );

    // Are we returning the name or just the type.
    if( $name ) {
        return $discount->type_name;
    }

    return $discount->type;
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

/**
 * Returns a discount's excluded items.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return array
 */
function wpinv_get_discount_excluded_items( $discount = array() ) {
    $discount = wpinv_get_discount_obj( $discount );
    return $discount->excluded_items;
}

/**
 * Returns a discount's required items.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return array
 */
function wpinv_get_discount_item_reqs( $discount = array() ) {
    $discount = wpinv_get_discount_obj( $discount );
    return $discount->items;
}

function wpinv_get_discount_item_condition( $code_id = 0 ) {
    return get_post_meta( $code_id, '_wpi_discount_item_condition', true );
}

function wpinv_is_discount_not_global( $code_id = 0 ) {
    return (bool) get_post_meta( $code_id, '_wpi_discount_is_not_global', true );
}

/**
 * Checks if a given discount has expired.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return bool
 */
function wpinv_is_discount_expired( $discount = array(), $silent = false ) {
    $discount = wpinv_get_discount_obj( $discount );

    if ( $discount->is_expired() ) {
        $discount->update_status( 'pending' );

        if( empty( $silent ) ) {
            wpinv_set_error( 'wpinv-discount-error', __( 'This discount has expired.', 'invoicing' ) );
        }
        return true;
    }

    return false;
}

/**
 * Checks if a given discount has started.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return bool
 */
function wpinv_is_discount_started( $discount = array() ) {
    $discount = wpinv_get_discount_obj( $discount );
    $started  = $discount->has_started();

    if( empty( $started ) ) {
        wpinv_set_error( 'wpinv-discount-error', __( 'This discount is not active yet.', 'invoicing' ) );
    }

    return $started;
}

/**
 * Checks discount dates.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return bool
 */
function wpinv_check_discount_dates( $discount ) {
    $discount = wpinv_get_discount_obj( $discount );
    $return   = wpinv_is_discount_started( $discount ) && ! wpinv_is_discount_expired( $discount );
    return apply_filters( 'wpinv_check_discount_dates', $return, $discount->ID, $discount, $discount->code );
}

/**
 * Checks if a discount is maxed out.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return bool
 */
function wpinv_is_discount_maxed_out( $discount ) {
    $discount    = wpinv_get_discount_obj( $discount );
    $maxed_out   = $discount->has_exceeded_limit();

    if ( $maxed_out ) {
        wpinv_set_error( 'wpinv-discount-error', __( 'This discount has reached its maximum usage.', 'invoicing' ) );
    }

    return $maxed_out;
}

/**
 * Checks if an amount meets a discount's minimum amount.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param float $amount The amount to check for.
 * @return bool
 */
function wpinv_discount_is_min_met( $discount, $amount = 0 ) {
    $discount = wpinv_get_discount_obj( $discount );
    return $discount->is_minimum_amount_met( $amount );
}

/**
 * Checks if an amount meets a discount's maximum amount.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return bool
 */
function wpinv_discount_is_max_met( $discount ) {
    $discount    = wpinv_get_discount_obj( $discount );
    $cart_amount = (float)wpinv_get_cart_discountable_subtotal( $discount->ID );
    $max_met     = $discount->is_maximum_amount_met( $cart_amount );

    if ( ! $max_met ) {
        wpinv_set_error( 'wpinv-discount-error', sprintf( __( 'Maximum invoice amount should be %s', 'invoicing' ), wpinv_price( wpinv_format_amount( $discount->max_total ) ) ) );
    }

    return $max_met;
}

/**
 * Checks if a discount can only be used once per user.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @return bool
 */
function wpinv_discount_is_single_use( $discount ) {
    $discount    = wpinv_get_discount_obj( $discount );
    return $discount->is_single_use;
}

/**
 * Checks if a discount is recurring.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param int|array|string|WPInv_Discount $code discount data, object, ID or code.
 * @return bool
 */
function wpinv_discount_is_recurring( $discount = 0, $code = 0 ) {

    if( ! empty( $discount ) ) {
        $discount    = wpinv_get_discount_obj( $discount );
    } else {
        $discount    = wpinv_get_discount_obj( $code );
    }

    return $discount->get_is_recurring();
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

/**
 * Checks if a discount has already been used by the user.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param int|string $user The user id, login or email
 * @param int|array|string|WPInv_Discount $code_id discount data, object, ID or code.
 * @return bool
 */
function wpinv_is_discount_used( $discount = array(), $user = '', $code_id = array() ) {

    if( ! empty( $discount ) ) {
        $discount = wpinv_get_discount_obj( $discount );
    } else {
        $discount = wpinv_get_discount_obj( $code_id );
    }

    $is_used = ! $discount->is_valid_for_user( $user );
    $is_used = apply_filters( 'wpinv_is_discount_used', $is_used, $discount->code, $user, $discount->ID, $discount );

    if( $is_used ) {
        wpinv_set_error( 'wpinv-discount-error', __( 'This discount has already been redeemed.', 'invoicing' ) );
    }

    return $is_used;
}

function wpinv_is_discount_valid( $code = '', $user = '', $set_error = true ) {

    // Abort early if there is no discount code.
    if ( empty( $code ) ) {
        return false;
    }

    $return      = false;
    $discount_id = wpinv_get_discount_id_by_code( $code );
    $user        = trim( $user );

    if ( wpinv_get_cart_contents() ) {
        if ( $discount_id !== false ) {
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

/**
 * Given a discount code, this function returns the discount's id.
 * 
 * @param string $code
 * @return bool|false
 */
function wpinv_get_discount_id_by_code( $code ) {
    $discount = wpinv_get_discount_by_code( $code );
    if ( $discount ) {
        return $discount->get_id();
    }
    return false;
}

/**
 * Calculates the discounted amount.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param float $base_price The number of usages to increase by
 * @return float
 */
function wpinv_get_discounted_amount( $discount, $base_price ) {
    $discount = wpinv_get_discount_obj( $discount );
    return $discount->get_discounted_amount( $base_price );
}

/**
 * Increases a discount's usage count.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param int $by The number of usages to increase by.
 * @return int the new number of uses.
 */
function wpinv_increase_discount_usage( $discount, $by = 1 ) {
    $discount   = wpinv_get_discount_obj( $discount );
    return $discount->increase_usage( $by );
}

/**
 * Decreases a discount's usage count.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param int $by The number of usages to decrease by.
 * @return int the new number of uses.
 */
function wpinv_decrease_discount_usage( $discount, $by = 1 ) {
    $discount   = wpinv_get_discount_obj( $discount );
    return $discount->increase_usage( 0 - $by );
}

function wpinv_format_discount_rate( $type, $amount ) {
    if ( $type == 'flat' ) {
        $rate = wpinv_price( wpinv_format_amount( $amount ) );
    } else {
        $rate = $amount . '%';
    }

    return apply_filters( 'wpinv_format_discount_rate', $rate, $type, $amount );
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

function wpinv_get_cart_discounts() {
    $session = wpinv_get_checkout_session();
    return empty( $session['cart_discounts'] ) ? false : $session['cart_discounts'];
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
    $label = wp_sprintf( __( 'Discount: %s', 'invoicing' ), $code );
    $label = apply_filters( 'wpinv_cart_discount_label', $label, $code, $rate );

    if ( $echo ) {
        echo $label;
    } else {
        return $label;
    }
}

function wpinv_check_delete_discount( $check, $post ) {
    if ( $post->post_type == 'wpi_discount' && wpinv_get_discount_uses( $post->ID ) > 0 ) {
        return true;
    }

    return $check;
}

function wpinv_discount_amount() {
    $output = 0.00;

    return apply_filters( 'wpinv_discount_amount', $output );
}
