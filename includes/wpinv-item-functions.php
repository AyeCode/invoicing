<?php
/**
 * Contains item functions.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
defined( 'ABSPATH' ) || exit;

/**
 * Retrieves an item by it's ID.
 * 
 * @param int the item ID to retrieve.
 * @return WPInv_Item|false
 */
function wpinv_get_item_by_id( $id ) {
    $item = wpinv_get_item( $id );
    return empty( $item ) || $id != $item->get_id() ? false : $item;
}

/**
 * Retrieves an item by it's ID, Name, Slug or custom id.
 * 
 * @return WPInv_Item|false
 */
function wpinv_get_item_by( $field = '', $value = '', $type = '' ) {

    if ( 'id' == strtolower( $field ) ) {
        return wpinv_get_item_by_id( $field );
    }

    $id = WPInv_Item::get_item_id_by_field( $value, strtolower( $field ), $type );
    return $id ? wpinv_get_item( $id ) : false;

}

/**
 * Retrieves an item by it's ID, name or custom_name.
 * 
 * @param int|WPInv_Item the item to retrieve.
 * @return WPInv_Item|false
 */
function wpinv_get_item( $item = 0 ) {
    
    if ( empty( $item ) ) {
        return false;
    }

    $item = new WPInv_Item( $item );
    return $item->get_id() ? $item : false;

}

function wpinv_get_all_items( $args = array() ) {

    $args = wp_parse_args( $args, array(
        'status'         => array( 'publish' ),
        'limit'          => get_option( 'posts_per_page' ),
        'page'           => 1,
        'exclude'        => array(),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'type'           => wpinv_item_types(),
        'meta_query'     => array(),
        'return'         => 'objects',
        'paginate'       => false,
    ) );

    $wp_query_args = array(
        'post_type'      => 'wpi_item',
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
        $wp_query_args['s'] = $args['search'];
    }

    if ( ! empty( $args['type'] ) && $args['type'] !== wpinv_item_types() ) {
        $types = wpinv_parse_list( $args['type'] );
        $wp_query_args['meta_query'][] = array(
            'key'     => '_wpinv_type',
            'value'   => implode( ',', $types ),
            'compare' => 'IN',
        );
    }

    $wp_query_args = apply_filters('wpinv_get_items_args', $wp_query_args, $args);

    // Get results.
    $items = new WP_Query( $wp_query_args );

    if ( 'objects' === $args['return'] ) {
        $return = array_map( 'wpinv_get_item_by_id', $items->posts );
    } elseif ( 'self' === $args['return'] ) {
        return $items;
    } else {
        $return = $items->posts;
    }

    if ( $args['paginate' ] ) {
        return (object) array(
            'items'      => $return,
            'total'         => $items->found_posts,
            'max_num_pages' => $items->max_num_pages,
        );
    } else {
        return $return;
    }

}

function wpinv_is_free_item( $item_id = 0 ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );
    
    return $item->is_free();
}

/**
 * Checks whether an item is editable.
 * 
 * @param WP_Post|WPInv_Item|Int $item The item to check for.
 */
function wpinv_item_is_editable( $item = 0 ) {

    // Fetch the item.
    $item = new WPInv_Item( $item );

    // Check if it is editable.
    return $item->is_editable();
}

function wpinv_get_item_price( $item_id = 0 ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );
    
    return $item->get_price();
}

function wpinv_is_recurring_item( $item_id = 0 ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );
    
    return $item->is_recurring();
}

function wpinv_item_price( $item_id = 0 ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $price = wpinv_get_item_price( $item_id );
    $price = wpinv_price( wpinv_format_amount( $price ) );
    
    return apply_filters( 'wpinv_item_price', $price, $item_id );
}

function wpinv_item_show_price( $item_id = 0, $echo = true ) {
    if ( empty( $item_id ) ) {
        $item_id = get_the_ID();
    }

    $price = wpinv_item_price( $item_id );

    $price           = apply_filters( 'wpinv_item_price', wpinv_sanitize_amount( $price ), $item_id );
    $formatted_price = '<span class="wpinv_price" id="wpinv_item_' . $item_id . '">' . $price . '</span>';
    $formatted_price = apply_filters( 'wpinv_item_price_after_html', $formatted_price, $item_id, $price );

    if ( $echo ) {
        echo $formatted_price;
    } else {
        return $formatted_price;
    }
}

function wpinv_get_item_final_price( $item_id = 0, $amount_override = null ) {
    if ( is_null( $amount_override ) ) {
        $original_price = get_post_meta( $item_id, '_wpinv_price', true );
    } else {
        $original_price = $amount_override;
    }
    
    $price = $original_price;

    return apply_filters( 'wpinv_get_item_final_price', $price, $item_id );
}

function wpinv_item_custom_singular_name( $item_id ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );
    
    return $item->get_custom_singular_name();
}

function wpinv_get_item_types() {
    $item_types = array(
            'custom'    => __( 'Standard', 'invoicing' ),
            'fee'       => __( 'Fee', 'invoicing' ),
        );
    return apply_filters( 'wpinv_get_item_types', $item_types );
}

function wpinv_item_types() {
    $item_types = wpinv_get_item_types();
    
    return ( !empty( $item_types ) ? array_keys( $item_types ) : array() );
}

function wpinv_get_item_type( $item_id ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );
    
    return $item->get_type();
}

function wpinv_item_type( $item_id ) {
    $item_types = wpinv_get_item_types();
    
    $item_type = wpinv_get_item_type( $item_id );
    
    if ( empty( $item_type ) ) {
        $item_type = '-';
    }
    
    $item_type = isset( $item_types[$item_type] ) ? $item_types[$item_type] : __( $item_type, 'invoicing' );

    return apply_filters( 'wpinv_item_type', $item_type, $item_id );
}

function wpinv_record_item_in_log( $item_id = 0, $file_id, $user_info, $ip, $invoice_id ) {
    global $wpinv_logs;
    
    if ( empty( $wpinv_logs ) ) {
        return false;
    }

    $log_data = array(
        'post_parent'	=> $item_id,
        'log_type'		=> 'wpi_item'
    );

    $user_id = isset( $user_info['user_id'] ) ? $user_info['user_id'] : (int) -1;

    $log_meta = array(
        'user_info'	=> $user_info,
        'user_id'	=> $user_id,
        'file_id'	=> (int)$file_id,
        'ip'		=> $ip,
        'invoice_id'=> $invoice_id,
    );

    $wpinv_logs->insert_log( $log_data, $log_meta );
}

function wpinv_remove_item_logs_on_delete( $item_id = 0 ) {
    if ( 'wpi_item' !== get_post_type( $item_id ) )
        return;

    global $wpinv_logs;
    
    if ( empty( $wpinv_logs ) ) {
        return false;
    }

    // Remove all log entries related to this item
    $wpinv_logs->delete_logs( $item_id );
}
add_action( 'delete_post', 'wpinv_remove_item_logs_on_delete' );

function wpinv_get_random_item( $post_ids = true ) {
    wpinv_get_random_items( 1, $post_ids );
}

function wpinv_get_random_items( $num = 3, $post_ids = true ) {
    if ( $post_ids ) {
        $args = array( 'post_type' => 'wpi_item', 'orderby' => 'rand', 'post_count' => $num, 'fields' => 'ids' );
    } else {
        $args = array( 'post_type' => 'wpi_item', 'orderby' => 'rand', 'post_count' => $num );
    }
    
    $args  = apply_filters( 'wpinv_get_random_items', $args );
    
    return get_posts( $args );
}

function wpinv_get_item_token( $url = '' ) {
    $args    = array();
    $hash    = apply_filters( 'wpinv_get_url_token_algorithm', 'sha256' );
    $secret  = apply_filters( 'wpinv_get_url_token_secret', hash( $hash, wp_salt() ) );

    $parts   = parse_url( $url );
    $options = array();

    if ( isset( $parts['query'] ) ) {
        wp_parse_str( $parts['query'], $query_args );

        if ( ! empty( $query_args['o'] ) ) {
            $options = explode( ':', rawurldecode( $query_args['o'] ) );

            if ( in_array( 'ip', $options ) ) {
                $args['ip'] = wpinv_get_ip();
            }

            if ( in_array( 'ua', $options ) ) {
                $ua = wpinv_get_user_agent();
                $args['user_agent'] = rawurlencode( $ua );
            }
        }
    }

    $args = apply_filters( 'wpinv_get_url_token_args', $args, $url, $options );

    $args['secret'] = $secret;
    $args['token']  = false;

    $url   = add_query_arg( $args, $url );
    $parts = parse_url( $url );

    if ( ! isset( $parts['path'] ) ) {
        $parts['path'] = '';
    }

    $token = md5( $parts['path'] . '?' . $parts['query'] );

    return $token;
}

function wpinv_validate_url_token( $url = '' ) {
    $ret   = false;
    $parts = parse_url( $url );

    if ( isset( $parts['query'] ) ) {
        wp_parse_str( $parts['query'], $query_args );

        $allowed = apply_filters( 'wpinv_url_token_allowed_params', array(
            'item',
            'ttl',
            'token'
        ) );

        $remove = array();

        foreach( $query_args as $key => $value ) {
            if( false === in_array( $key, $allowed ) ) {
                $remove[] = $key;
            }
        }

        if( ! empty( $remove ) ) {
            $url = remove_query_arg( $remove, $url );
        }

        if ( isset( $query_args['ttl'] ) && current_time( 'timestamp' ) > $query_args['ttl'] ) {
            wp_die( apply_filters( 'wpinv_item_link_expired_text', __( 'Sorry but your item link has expired.', 'invoicing' ) ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
        }

        if ( isset( $query_args['token'] ) && $query_args['token'] == wpinv_get_item_token( $url ) ) {
            $ret = true;
        }

    }

    return apply_filters( 'wpinv_validate_url_token', $ret, $url, $query_args );
}

function wpinv_item_in_cart( $item_id = 0, $options = array() ) {
    $cart_items = wpinv_get_cart_contents();

    $ret = false;

    if ( is_array( $cart_items ) ) {
        foreach ( $cart_items as $item ) {
            if ( $item['id'] == $item_id ) {
                $ret = true;
                break;
            }
        }
    }

    return (bool) apply_filters( 'wpinv_item_in_cart', $ret, $item_id, $options );
}

function wpinv_get_cart_item_tax( $item_id = 0, $subtotal = '', $options = array() ) {
    $tax = 0;
    if ( ! wpinv_item_is_tax_exclusive( $item_id ) ) {
        $country = !empty( $_POST['country'] ) ? $_POST['country'] : false;
        $state   = isset( $_POST['state'] ) ? $_POST['state'] : '';

        $tax = wpinv_calculate_tax( $subtotal, $country, $state, $item_id );
    }

    return apply_filters( 'wpinv_get_cart_item_tax', $tax, $item_id, $subtotal, $options );
}

function wpinv_cart_item_price( $item, $currency = '' ) {

    if( empty( $currency ) ) {
        $currency = wpinv_get_currency();
    }

    $item_id    = isset( $item['id'] ) ? $item['id'] : 0;
    $price      = isset( $item['item_price'] ) ? wpinv_round_amount( $item['item_price'] ) : 0;
    $tax        = wpinv_price( wpinv_format_amount( $item['tax'] ) );
    
    if ( !wpinv_is_free_item( $item_id ) && !wpinv_item_is_tax_exclusive( $item_id ) ) {
        if ( wpinv_prices_show_tax_on_checkout() && !wpinv_prices_include_tax() ) {
            $price += $tax;
        }
        
        if( !wpinv_prices_show_tax_on_checkout() && wpinv_prices_include_tax() ) {
            $price -= $tax;
        }        
    }

    $price = wpinv_price( wpinv_format_amount( $price ), $currency );

    return apply_filters( 'wpinv_cart_item_price_label', $price, $item );
}

function wpinv_cart_item_subtotal( $item, $currency = '' ) {

    if( empty( $currency ) ) {
        $currency = wpinv_get_currency();
    }

    $subtotal   = isset( $item['subtotal'] ) ? $item['subtotal'] : 0;
    $subtotal   = wpinv_price( wpinv_format_amount( $subtotal ), $currency );

    return apply_filters( 'wpinv_cart_item_subtotal_label', $subtotal, $item );
}

function wpinv_cart_item_tax( $item, $currency = '' ) {
    $tax        = '';
    $tax_rate   = '';

    if( empty( $currency ) ) {
        $currency = wpinv_get_currency();
    }
    
    if ( isset( $item['tax'] ) && $item['tax'] > 0 && $item['subtotal'] > 0 ) {
        $tax      = wpinv_price( wpinv_format_amount( $item['tax'] ), $currency );
        $tax_rate = !empty( $item['vat_rate'] ) ? $item['vat_rate'] : ( $item['tax'] / $item['subtotal'] ) * 100;
        $tax_rate = $tax_rate > 0 ? (float)wpinv_round_amount( $tax_rate, 4 ) : '';
        $tax_rate = $tax_rate != '' ? ' <small class="tax-rate normal small">(' . $tax_rate . '%)</small>' : '';
    }
    
    $tax        = $tax . $tax_rate;
    
    if ( $tax === '' ) {
        $tax = 0; // Zero tax
    }

    return apply_filters( 'wpinv_cart_item_tax_label', $tax, $item );
}

function wpinv_get_cart_item_price( $item_id = 0, $cart_item = array(), $options = array(), $remove_tax_from_inclusive = false ) {
    $price = 0;
    
    // Set custom price
    if ( isset( $cart_item['custom_price'] ) && $cart_item['custom_price'] !== '' ) {
        $price = $cart_item['custom_price'];
    } else {
        $variable_prices = wpinv_has_variable_prices( $item_id );

        if ( $variable_prices ) {
            $prices = wpinv_get_variable_prices( $item_id );

            if ( $prices ) {
                if( ! empty( $options ) ) {
                    $price = isset( $prices[ $options['price_id'] ] ) ? $prices[ $options['price_id'] ]['amount'] : false;
                } else {
                    $price = false;
                }
            }
        }

        if( ! $variable_prices || false === $price ) {
            if($cart_item['item_price'] > 0){
                $price = $cart_item['item_price'];
            } else {
                // Get the standard Item price if not using variable prices
                $price = wpinv_get_item_price( $item_id );
            }
        }
    }

    if ( $remove_tax_from_inclusive && wpinv_prices_include_tax() ) {
        $price -= wpinv_get_cart_item_tax( $item_id, $price, $options );
    }

    return apply_filters( 'wpinv_cart_item_price', $price, $item_id, $cart_item, $options, $remove_tax_from_inclusive );
}

function wpinv_get_cart_item_price_id( $item = array() ) {
    if( isset( $item['item_number'] ) ) {
        $price_id = isset( $item['item_number']['options']['price_id'] ) ? $item['item_number']['options']['price_id'] : null;
    } else {
        $price_id = isset( $item['options']['price_id'] ) ? $item['options']['price_id'] : null;
    }
    return $price_id;
}

function wpinv_get_cart_item_price_name( $item = array() ) {
    $price_id = (int)wpinv_get_cart_item_price_id( $item );
    $prices   = wpinv_get_variable_prices( $item['id'] );
    $name     = ! empty( $prices[ $price_id ] ) ? $prices[ $price_id ]['name'] : '';
    return apply_filters( 'wpinv_get_cart_item_price_name', $name, $item['id'], $price_id, $item );
}

function wpinv_get_cart_item_name( $item = array() ) {
    $item_title = !empty( $item['name'] ) ? $item['name'] : get_the_title( $item['id'] );

    if ( empty( $item_title ) ) {
        $item_title = $item['id'];
    }

    /*
    if ( wpinv_has_variable_prices( $item['id'] ) && false !== wpinv_get_cart_item_price_id( $item ) ) {
        $item_title .= ' - ' . wpinv_get_cart_item_price_name( $item );
    }
    */

    return apply_filters( 'wpinv_get_cart_item_name', $item_title, $item['id'], $item );
}

function wpinv_has_variable_prices( $item_id = 0 ) {
    return false;
}

function wpinv_get_item_position_in_cart( $item_id = 0, $options = array() ) {
    $cart_items = wpinv_get_cart_contents();

    if ( !is_array( $cart_items ) ) {
        return false; // Empty cart
    } else {
        foreach ( $cart_items as $position => $item ) {
            if ( $item['id'] == $item_id ) {
                if ( isset( $options['price_id'] ) && isset( $item['options']['price_id'] ) ) {
                    if ( (int) $options['price_id'] == (int) $item['options']['price_id'] ) {
                        return $position;
                    }
                } else {
                    return $position;
                }
            }
        }
    }

    return false; // Not found
}

function wpinv_get_cart_item_quantity( $item ) {
    if ( wpinv_item_quantities_enabled() ) {
        $quantity = !empty( $item['quantity'] ) && (int)$item['quantity'] > 0 ? absint( $item['quantity'] ) : 1;
    } else {
        $quantity = 1;
    }
    
    if ( $quantity < 1 ) {
        $quantity = 1;
    }
    
    return apply_filters( 'wpinv_get_cart_item_quantity', $quantity, $item );
}

function wpinv_get_item_suffix( $item, $html = true ) {
    if ( empty( $item ) ) {
        return NULL;
    }
    
    if ( is_int( $item ) ) {
        $item = new WPInv_Item( $item );
    }
    
    if ( !( is_object( $item ) && is_a( $item, 'WPInv_Item' ) ) ) {
        return NULL;
    }
    
    $suffix = $item->is_recurring() ? ' <span class="wpi-suffix">' . __( '(r)', 'invoicing' ) . '</span>' : '';
    
    if ( !$html && $suffix ) {
        $suffix = strip_tags( $suffix );
    }
    
    return apply_filters( 'wpinv_get_item_suffix', $suffix, $item, $html );
}

function wpinv_remove_item( $item = 0, $force_delete = false ) {
    if ( empty( $item ) ) {
        return NULL;
    }
    
    if ( is_int( $item ) ) {
        $item = new WPInv_Item( $item );
    }
    
    if ( !( is_object( $item ) && is_a( $item, 'WPInv_Item' ) ) ) {
        return NULL;
    }
    
    do_action( 'wpinv_pre_delete_item', $item );

    wp_delete_post( $item->ID, $force_delete );

    do_action( 'wpinv_post_delete_item', $item );
}

function wpinv_can_delete_item( $post_id ) {
    $return = wpinv_current_user_can_manage_invoicing() ? true : false;
    
    if ( $return && wpinv_item_in_use( $post_id ) ) {
        $return = false; // Don't delete item already use in invoices.
    }
    
    return apply_filters( 'wpinv_can_delete_item', $return, $post_id );
}

function wpinv_admin_action_delete() {
    $screen = get_current_screen();
    
    if ( !empty( $screen->post_type ) && $screen->post_type == 'wpi_item' && !empty( $_REQUEST['post'] ) && is_array( $_REQUEST['post'] ) ) {
        $post_ids = array();
        
        foreach ( $_REQUEST['post'] as $post_id ) {
            if ( !wpinv_can_delete_item( $post_id ) ) {
                continue;
            }
            
            $post_ids[] = $post_id;
        }
        
        $_REQUEST['post'] = $post_ids;
    }
}
add_action( 'admin_action_trash', 'wpinv_admin_action_delete', -10 );
add_action( 'admin_action_delete', 'wpinv_admin_action_delete', -10 );

function wpinv_check_delete_item( $check, $post, $force_delete ) {
    if ( $post->post_type == 'wpi_item' ) {
        if ( $force_delete && !wpinv_can_delete_item( $post->ID ) ) {
            return true;
        }
    }
    
    return $check;
}
add_filter( 'pre_delete_post', 'wpinv_check_delete_item', 10, 3 );

function wpinv_item_in_use( $item_id ) {
    global $wpdb, $wpi_items_in_use;
    
    if ( !$item_id > 0 ) {
        return false;
    }
    
    if ( !empty( $wpi_items_in_use ) ) {
        if ( isset( $wpi_items_in_use[$item_id] ) ) {
            return $wpi_items_in_use[$item_id];
        }
    } else {
        $wpi_items_in_use = array();
    }
    
    $statuses   = array_keys( wpinv_get_invoice_statuses( true, true ) );
    
    $query  = "SELECT p.ID FROM " . $wpdb->posts . " AS p INNER JOIN " . $wpdb->postmeta . " AS pm ON p.ID = pm.post_id WHERE p.post_type = 'wpi_invoice' AND p.post_status IN( '" . implode( "','", $statuses ) . "' ) AND pm.meta_key = '_wpinv_item_ids' AND FIND_IN_SET( '" . (int)$item_id . "', pm.meta_value )";
    $in_use = $wpdb->get_var( $query ) > 0 ? true : false;
    
    $wpi_items_in_use[$item_id] = $in_use;
    
    return $in_use;
}

/**
 * Create/Update an item.
 * 
 * @param array $args an array of arguments to create the item.
 * 
 *    Here are all the args (with defaults) that you can set/modify.
 *    array(
 *		  'ID'                   => 0,           - If specified, the item with that ID will be updated.
 *        'parent_id'            => 0,           - Int. Parent item ID.
 *		  'status'               => 'draft',     - String. Item status - either draft, pending or publish.
 *		  'date_created'         => null,        - String. strtotime() compatible string.
 *        'date_modified'        => null,        - String. strtotime() compatible string.
 *        'name'                 => '',          - String. Required. Item name.
 *        'description'          => '',          - String. Item description.
 *        'author'               => 1,           - int. Owner of the item.
 *        'price'                => 0,           - float. Item price.
 *        'vat_rule'             => 'digital',   - string. VAT rule.
 *        'vat_class'            => '_standard', - string. VAT class.
 *        'type'                 => 'custom',    - string. Item type.
 *        'custom_id'            => null,        - string. Custom item id.
 *        'custom_name'          => null,        - string. Custom item name.
 *        'custom_singular_name' => null,        - string. Custom item singular name.
 *        'is_editable'          => 1,           - int|bool. Whether or not the item is editable.
 *        'is_dynamic_pricing'   => 0,           - int|bool. Whether or not users can update the item price.
 *        'minimum_price'        => 0,           - float. If dynamic, set the minimum price that a user can set..
 *        'is_recurring'         => 0,           - int|bool. Whether or not this item is recurring.
 *        'recurring_period'     => 'D',         - string. If recurring, set the recurring period as either days (D), weeks (W), months (M) or years (Y).
 *        'recurring_interval'   => 1,           - int. The recurring interval.
 *        'recurring_limit'      => 0,           - int. The recurring limit. Enter 0 for unlimited.
 *        'is_free_trial'        => false,       - int|bool. Whether or not this item has a free trial.
 *        'trial_period'         => 'D',         - string. If it has a free trial, set the trial period as either days (D), weeks (W), months (M) or years (Y).
 *        'trial_interval'       => 1,           - int. The trial interval.
 *    );
 * @param bool $wp_error whether or not to return a WP_Error on failure.
 * @return bool|WP_Error|WPInv_Item
 */
function wpinv_create_item( $args = array(), $wp_error = false ) {

    // Prepare the item.
    if ( ! empty( $args['custom_id'] ) && empty( $args['ID'] ) ) {
        $type = empty( $args['type'] ) ? 'custom' : $args['type'];
        $item = wpinv_get_item_by( 'custom_id', $args['custom_id'], $type );

        if ( ! empty( $item ) ) {
            $args['ID'] = $item->get_id();
        }

    }

    // Do we have an item?
    if ( ! empty( $args['ID'] ) ) {
        $item = new WPInv_Item( $args['ID'] );
    } else {
        $item = new WPInv_Item();
    }

    // Do we have an error?
    if ( ! empty( $item->last_error ) ) {
        return $wp_error ? new WP_Error( 'invalid_item', $item->last_error ) : false;
    }

    // Update item props.
    $item->set_props( $args );

    // Save the item.
    $item->save();

    // Do we have an error?
    if ( ! empty( $item->last_error ) ) {
        return $wp_error ? new WP_Error( 'not_saved', $item->last_error ) : false;
    }

    // Was the item saved?
    if ( ! $item->get_id() ) {
        return $wp_error ? new WP_Error( 'not_saved', __( 'An error occured while saving the item', 'invoicing' ) ) : false;
    }

    return $item;

}

/**
 * Updates an item.
 * 
 * @see wpinv_create_item()
 */
function wpinv_update_item( $args = array(), $wp_error = false ) {
    return wpinv_create_item( $args, $wp_error );
}

/**
 * Sanitizes a recurring period
 */
function getpaid_sanitize_recurring_period( $period, $full = false ) {

    $periods = array(
        'D' => 'day',
        'W' => 'week',
        'M' => 'month',
        'Y' => 'year',
    );

    if ( ! isset( $periods[ $period ] ) ) {
        $period = 'D';
    }

    return $full ? $periods[ $period ] : $period;

}

/**
 * Retrieves recurring price description.
 * 
 * @param WPInv_Item|GetPaid_Form_Item $item
 */
function getpaid_item_recurring_price_help_text( $item, $currency = '' ) {

    // Abort if it is not recurring.
    if ( ! $item->is_recurring() ) {
        return '';
    }

    $initial_price   = wpinv_price( $item->get_initial_price(), $currency );
    $recurring_price = wpinv_price( $item->get_recurring_price(), $currency );
    $period          = getpaid_get_subscription_period_label( $item->get_recurring_period(), $item->get_recurring_interval(), '' );
    $initial_class   = 'getpaid-item-initial-price';
    $recurring_class = 'getpaid-item-recurring-price';

    if ( $item instanceof GetPaid_Form_Item ) {
        $initial_price   = wpinv_price( $item->get_sub_total(), $currency );
        $recurring_price = wpinv_price( $item->get_recurring_sub_total(), $currency );
    }

    // For free trial items.
    if ( $item->has_free_trial() ) {
        $trial_period = getpaid_get_subscription_period_label( $item->get_trial_period(), $item->get_trial_interval() );

        if ( 0 == $item->get_initial_price() ) {

            return sprintf(

                // translators: $1: is the trial period, $2: is the recurring price, $3: is the susbcription period
                _x( 'Free for %1$s then %2$s / %3$s', 'Item subscription amount. (e.g.: Free for 1 month then $120 / year)', 'invoicing' ),
                $trial_period,
                "<span class='$recurring_class'>$recurring_price</span>",
                $period

            );

        }

        return sprintf(

            // translators: $1: is the initial price, $2: is the trial period, $3: is the recurring price, $4: is the susbcription period
            _x( '%1$s for %2$s then %3$s / %4$s', 'Item subscription amount. (e.g.: $7 for 1 month then $120 / year)', 'invoicing' ),
            "<span class='$initial_class'>$initial_price</span>",
            $trial_period,
            "<span class='$recurring_class'>$recurring_price</span>",
            $period

        );

    }

    if ( $initial_price == $recurring_price ) {

        return sprintf(

            // translators: $1: is the recurring price, $2: is the susbcription period
            _x( '%1$s / %2$s', 'Item subscription amount. (e.g.: $120 / year)', 'invoicing' ),
            "<span class='$recurring_class'>$recurring_price</span>",
            $period

        );

    }

    return sprintf(

        // translators: $1: is the initial price, $2: is the recurring price, $3: is the susbcription period
        _x( 'Initial payment of %1$s then %2$s / %3$s', 'Item subscription amount. (e.g.: Initial payment of $7 then $120 / year)', 'invoicing' ),
        "<span class='$initial_class'>$initial_price</span>",
        "<span class='$recurring_class'>$recurring_price</span>",
        $period

    );

}
