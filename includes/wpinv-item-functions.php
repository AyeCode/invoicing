<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpinv_get_item_by( $field = '', $value = '', $type = '' ) {
    if( empty( $field ) || empty( $value ) ) {
        return false;
    }
    
    $posts = array();

    switch( strtolower( $field ) ) {
        case 'id':
            $item = new WPInv_Item( $value );

            if ( !empty( $item ) && $item->post_type == 'wpi_item' ) {
                return $item;
            }
            return false;

            break;

        case 'slug':
        case 'name':
            $posts = get_posts( array(
                'post_type'      => 'wpi_item',
                'name'           => $value,
                'posts_per_page' => 1,
                'post_status'    => 'any'
            ) );

            break;
        case 'custom_id':
            if ( empty( $value ) || empty( $type ) ) {
                return false;
            }
            
            $meta_query = array();
            $meta_query[] = array(
                'key'   => '_wpinv_type',
                'value' => $type,
            );
            $meta_query[] = array(
                'key'   => '_wpinv_custom_id',
                'value' => $value,
            );
            
            $args = array(
                'post_type'      => 'wpi_item',
                'posts_per_page' => 1,
                'post_status'    => 'any',
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'meta_query'     => array( $meta_query )
            );
            
            $posts = get_posts( $args );

            break;

        default:
            return false;
    }
    
    if ( !empty( $posts[0] ) ) {
        $item = new WPInv_Item( $posts[0]->ID );

        if ( !empty( $item ) && $item->post_type == 'wpi_item' ) {
            return $item;
        }
    }

    return false;
}

function wpinv_get_item( $item = 0 ) {
    if ( is_numeric( $item ) ) {
        $item = get_post( $item );
        if ( ! $item || 'wpi_item' !== $item->post_type )
            return null;
        return $item;
    }

    $args = array(
        'post_type'   => 'wpi_item',
        'name'        => $item,
        'numberposts' => 1
    );

    $item = get_posts($args);

    if ( $item ) {
        return $item[0];
    }

    return null;
}

function wpinv_is_free_item( $item_id = 0 ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );
    
    return $item->is_free();
}

function wpinv_item_is_editable( $item = 0 ) {
    if ( !empty( $item ) && is_a( $item, 'WP_Post' ) ) {
        $item = $item->ID;
    }
        
    if ( empty( $item ) ) {
        return true;
    }

    $item = new WPInv_Item( $item );
    
    return (bool) $item->is_editable();
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

function wpinv_cart_item_price( $item ) {
    $use_taxes  = wpinv_use_taxes();
    $item_id    = isset( $item['id'] ) ? $item['id'] : 0;
    $price      = isset( $item['item_price'] ) ? wpinv_round_amount( $item['item_price'] ) : 0;
    $options    = isset( $item['options'] ) ? $item['options'] : array();
    $price_id   = isset( $options['price_id'] ) ? $options['price_id'] : false;
    $tax        = wpinv_price( wpinv_format_amount( $item['tax'] ) );
    
    if ( !wpinv_is_free_item( $item_id, $price_id ) && !wpinv_item_is_tax_exclusive( $item_id ) ) {
        if ( wpinv_prices_show_tax_on_checkout() && !wpinv_prices_include_tax() ) {
            $price += $tax;
        }
        
        if( !wpinv_prices_show_tax_on_checkout() && wpinv_prices_include_tax() ) {
            $price -= $tax;
        }        
    }

    $price = wpinv_price( wpinv_format_amount( $price ) );

    return apply_filters( 'wpinv_cart_item_price_label', $price, $item );
}

function wpinv_cart_item_subtotal( $item ) {
    $subtotal   = isset( $item['subtotal'] ) ? $item['subtotal'] : 0;
    $subtotal   = wpinv_price( wpinv_format_amount( $subtotal ) );

    return apply_filters( 'wpinv_cart_item_subtotal_label', $subtotal, $item );
}

function wpinv_cart_item_tax( $item ) {
    $tax        = '';
    $tax_rate   = '';
    
    if ( isset( $item['tax'] ) && $item['tax'] > 0 && $item['subtotal'] > 0 ) {
        $tax      = wpinv_price( wpinv_format_amount( $item['tax'] ) );
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
            // Get the standard Item price if not using variable prices
            $price = wpinv_get_item_price( $item_id );
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
    $return = current_user_can( 'manage_options' ) ? true : false;
    
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
    
    $statuses   = array_keys( wpinv_get_invoice_statuses( true ) );
    
    $query  = "SELECT p.ID FROM " . $wpdb->posts . " AS p INNER JOIN " . $wpdb->postmeta . " AS pm ON p.ID = pm.post_id WHERE p.post_type = 'wpi_invoice' AND p.post_status IN( '" . implode( "','", $statuses ) . "' ) AND pm.meta_key = '_wpinv_item_ids' AND FIND_IN_SET( '" . (int)$item_id . "', pm.meta_value )";
    $in_use = $wpdb->get_var( $query ) > 0 ? true : false;
    
    $wpi_items_in_use[$item_id] = $in_use;
    
    return $in_use;
}

function wpinv_create_item( $args = array(), $wp_error = false, $force_update = false ) {
    // Set some defaults
    $defaults = array(
        'type'                 => 'custom',                                                // Optional. Item type. Default 'custom'.
        'title'                => '',                                                      // Required. Item title.
        'custom_id'            => 0,                                                       // Optional. Any integer or non numeric id. Must be unique within item type.
        'price'                => '0.00',                                                  // Optional. Item price. Default '0.00'.
        'status'               => 'pending',                                               // Optional. pending, publish
        'custom_name'          => '',                                                      // Optional. Plural sub title for item.
        'custom_singular_name' => '',                                                      // Optional. Singular sub title for item.
        'vat_rule'             => 'digital',                                               // Optional. digital => Digital item, physical => Physical item
        'editable'             => true,                                                    // Optional. Item editable from Items list page? Default true.
        'excerpt'              => '',                                                      // Optional. Item short description
        /* Recurring item fields */
        'is_recurring'         => 0,                                                       // Optional. 1 => Allow recurring or 0 => Don't allow recurring
        'recurring_period'     => 'M',                                                     // Optional. D => Daily, W => Weekly, M => Monthly, Y => Yearly
        'recurring_interval'   => 0,                                                       // Optional. Integer value between 1 - 90.
        'recurring_limit'      => 0,                                                       // Optional. Any integer number. 0 for recurring forever until cancelled.
        'free_trial'           => 0,                                                       // Optional. 1 => Allow free trial or 0 => Don't free trial
        'trial_period'         => 'M',                                                     // Optional. D => Daily, W => Weekly, M => Monthly, Y => Yearly
        'trial_interval'       => 0,                                                       // Optional. Any integer number.
    );

    $data = wp_parse_args( $args, $defaults );

    if ( empty( $data['type'] ) ) {
        $data['type'] = 'custom';
    }

    if ( !empty( $data['custom_id'] ) ) {
        $item = wpinv_get_item_by( 'custom_id', $data['custom_id'], $data['type'] );
    } else {
        $item = NULL;
    }

    if ( !empty( $item ) ) {
        if ( $force_update ) {
            if ( empty( $args['ID'] ) ) {
                $args['ID'] = $item->ID;
            }
            return wpinv_update_item( $args, $wp_error );
        }

        return $item;
    }

    $meta                           = array();
    $meta['type']                   = $data['type'];
    $meta['custom_id']              = $data['custom_id'];
    $meta['custom_singular_name']   = $data['custom_singular_name'];
    $meta['custom_name']            = $data['custom_name'];
    $meta['price']                  = wpinv_round_amount( $data['price'] );
    $meta['editable']               = (int)$data['editable'];
    $meta['vat_rule']               = $data['vat_rule'];
    $meta['vat_class']              = '_standard';
    
    if ( !empty( $data['is_recurring'] ) ) {
        $meta['is_recurring']       = $data['is_recurring'];
        $meta['recurring_period']   = $data['recurring_period'];
        $meta['recurring_interval'] = absint( $data['recurring_interval'] );
        $meta['recurring_limit']    = absint( $data['recurring_limit'] );
        $meta['free_trial']         = $data['free_trial'];
        $meta['trial_period']       = $data['trial_period'];
        $meta['trial_interval']     = absint( $data['trial_interval'] );
    } else {
        $meta['is_recurring']       = 0;
        $meta['recurring_period']   = '';
        $meta['recurring_interval'] = '';
        $meta['recurring_limit']    = '';
        $meta['free_trial']         = 0;
        $meta['trial_period']       = '';
        $meta['trial_interval']     = '';
    }
    
    $post_data  = array( 
        'post_title'    => $data['title'],
        'post_excerpt'  => $data['excerpt'],
        'post_status'   => $data['status'],
        'meta'          => $meta
    );

    $item = new WPInv_Item();
    $return = $item->create( $post_data, $wp_error );

    if ( $return && !empty( $item ) && !is_wp_error( $return ) ) {
        return $item;
    }

    if ( $wp_error && is_wp_error( $return ) ) {
        return $return;
    }
    return 0;
}

function wpinv_update_item( $args = array(), $wp_error = false ) {
    $item = !empty( $args['ID'] ) ? new WPInv_Item( $args['ID'] ) : NULL;

    if ( empty( $item ) || !( !empty( $item->post_type ) && $item->post_type == 'wpi_item' ) ) {
        if ( $wp_error ) {
            return new WP_Error( 'wpinv_invalid_item', __( 'Invalid item.', 'invoicing' ) );
        }
        return 0;
    }
    
    if ( !empty( $args['custom_id'] ) ) {
        $item_exists = wpinv_get_item_by( 'custom_id', $args['custom_id'], ( !empty( $args['type'] ) ? $args['type'] : $item->type ) );
        
        if ( !empty( $item_exists ) && $item_exists->ID != $args['ID'] ) {
            if ( $wp_error ) {
                return new WP_Error( 'wpinv_invalid_custom_id', __( 'Item with custom id already exists.', 'invoicing' ) );
            }
            return 0;
        }
    }

    $meta_fields = array( 'type', 'custom_id', 'custom_singular_name', 'custom_name', 'price', 'editable', 'vat_rule', 'vat_class', 'is_recurring', 'recurring_period', 'recurring_interval', 'recurring_limit', 'free_trial', 'trial_period', 'trial_interval' );

    $post_data = array();
    if ( isset( $args['title'] ) ) { 
        $post_data['post_title'] = $args['title'];
    }
    if ( isset( $args['excerpt'] ) ) { 
        $post_data['post_excerpt'] = $args['excerpt'];
    }
    if ( isset( $args['status'] ) ) { 
        $post_data['post_status'] = $args['status'];
    }
    
    foreach ( $meta_fields as $meta_field ) {
        if ( isset( $args[ $meta_field ] ) ) { 
            $value = $args[ $meta_field ];

            switch ( $meta_field ) {
                case 'price':
                    $value = wpinv_round_amount( $value );
                break;
                case 'recurring_interval':
                case 'recurring_limit':
                case 'trial_interval':
                    $value = absint( $value );
                break;
            }

            $post_data['meta'][ $meta_field ] = $value;
        };
    }

    if ( empty( $post_data ) ) {
        if ( $wp_error ) {
            return new WP_Error( 'wpinv_invalid_item_data', __( 'Invalid item data.', 'invoicing' ) );
        }
        return 0;
    }
    $post_data['ID'] = $args['ID'];

    $return = $item->update( $post_data, $wp_error );

    if ( $return && !empty( $item ) && !is_wp_error( $return ) ) {
        return $item;
    }

    if ( $wp_error && is_wp_error( $return ) ) {
        return $return;
    }
    return 0;
}