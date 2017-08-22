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

function wpinv_get_invoice_cart_id() {
    $wpinv_checkout = wpinv_get_checkout_session();
    
    if ( !empty( $wpinv_checkout['invoice_id'] ) ) {
        return $wpinv_checkout['invoice_id'];
    }
    
    return NULL;
}

function wpinv_insert_invoice( $invoice_data = array(), $wp_error = false ) {
    if ( empty( $invoice_data ) ) {
        return false;
    }
    
    if ( !( !empty( $invoice_data['cart_details'] ) && is_array( $invoice_data['cart_details'] ) ) ) {
        return $wp_error ? new WP_Error( 'wpinv_invalid_items', __( 'Invoice must have atleast on item.', 'invoicing' ) ) : 0;
    }
    
    if ( empty( $invoice_data['user_id'] ) ) {
        $invoice_data['user_id'] = get_current_user_id();
    }
    
    $invoice_data['invoice_id'] = !empty( $invoice_data['invoice_id'] ) ? (int)$invoice_data['invoice_id'] : 0;
    
    if ( empty( $invoice_data['status'] ) ) {
        $invoice_data['status'] = 'pending';
    }
    
    if ( empty( $invoice_data['ip'] ) ) {
        $invoice_data['ip'] = wpinv_get_ip();
    }

    // default invoice args, note that status is checked for validity in wpinv_create_invoice()
    $default_args = array(
        'invoice_id'    => (int)$invoice_data['invoice_id'],
        'user_id'       => (int)$invoice_data['user_id'],
        'status'        => $invoice_data['status'],
    );

    $invoice = wpinv_create_invoice( $default_args, $invoice_data, true );
    if ( is_wp_error( $invoice ) ) {
        return $wp_error ? $invoice : 0;
    }
    
    if ( empty( $invoice_data['invoice_id'] ) ) {
        //$invoice->add_note( wp_sprintf( __( 'Invoice is created with status %s.', 'invoicing' ), wpinv_status_nicename( $invoice->status ) ) );
    }
    
    // User info
    $default_user_info = array(
        'user_id'               => '',
        'first_name'            => '',
        'last_name'             => '',
        'email'                 => '',
        'company'               => '',
        'phone'                 => '',
        'address'               => '',
        'city'                  => '',
        'country'               => wpinv_get_default_country(),
        'state'                 => wpinv_get_default_state(),
        'zip'                   => '',
        'vat_number'            => '',
        'vat_rate'              => '',
        'adddress_confirmed'    => '',
        'discount'              => array(),
    );

    if ( $user_id = (int)$invoice->get_user_id() ) {
        if ( $user_address = wpinv_get_user_address( $user_id ) ) {
            $default_user_info = wp_parse_args( $user_address, $default_user_info );
        }
    }
    
    if ( empty( $invoice_data['user_info'] ) ) {
        $invoice_data['user_info'] = array();
    }
    
    $user_info = wp_parse_args( $invoice_data['user_info'], $default_user_info );
    
    if ( empty( $user_info['first_name'] ) ) {
        $user_info['first_name'] = $default_user_info['first_name'];
        $user_info['last_name'] = $default_user_info['last_name'];
    }
    
    if ( empty( $user_info['country'] ) ) {
        $user_info['country'] = $default_user_info['country'];
        $user_info['state'] = $default_user_info['state'];
        $user_info['city'] = $default_user_info['city'];
        $user_info['address'] = $default_user_info['address'];
        $user_info['zip'] = $default_user_info['zip'];
        $user_info['phone'] = $default_user_info['phone'];
    }
    
    if ( !empty( $user_info['discount'] ) && !is_array( $user_info['discount'] ) ) {
        $user_info['discount'] = (array)$user_info['discount'];
    }

    // Payment details
    $payment_details = array();
    if ( !empty( $invoice_data['payment_details'] ) ) {
        $default_payment_details = array(
            'gateway'           => 'manual',
            'gateway_title'     => '',
            'currency'          => geodir_get_currency_type(),
            'transaction_id'    => '',
        );
        
        $payment_details = wp_parse_args( $invoice_data['payment_details'], $default_payment_details );
        
        if ( empty( $payment_details['gateway'] ) ) {
            $payment_details['gateway'] = 'manual';
        }
        
        if ( empty( $payment_details['currency'] ) ) {
            $payment_details['currency'] = geodir_get_currency_type();
        }
        
        if ( empty( $payment_details['gateway_title'] ) ) {
            $payment_details['gateway_title'] = wpinv_get_gateway_checkout_label( $payment_details['gateway'] );
        }
    }
    
    $invoice->set( 'status', ( !empty( $invoice_data['status'] ) ? $invoice_data['status'] : 'wpi-pending' ) );
    
    if ( !empty( $payment_details ) ) {
        $invoice->set( 'currency', $payment_details['currency'] );
        $invoice->set( 'gateway', $payment_details['gateway'] );
        $invoice->set( 'gateway_title', $payment_details['gateway_title'] );
        $invoice->set( 'transaction_id', $payment_details['transaction_id'] );
    }
    
    $invoice->set( 'user_info', $user_info );
    $invoice->set( 'first_name', $user_info['first_name'] );
    $invoice->set( 'last_name', $user_info['last_name'] );
    $invoice->set( 'address', $user_info['address'] );
    $invoice->set( 'company', $user_info['company'] );
    $invoice->set( 'vat_number', $user_info['vat_number'] );
    $invoice->set( 'phone', $user_info['phone'] );
    $invoice->set( 'city', $user_info['city'] );
    $invoice->set( 'country', $user_info['country'] );
    $invoice->set( 'state', $user_info['state'] );
    $invoice->set( 'zip', $user_info['zip'] );
    $invoice->set( 'discounts', $user_info['discount'] );
    $invoice->set( 'ip', ( !empty( $invoice_data['ip'] ) ? $invoice_data['ip'] : wpinv_get_ip() ) );
    $invoice->set( 'mode', ( wpinv_is_test_mode() ? 'test' : 'live' ) );
    $invoice->set( 'parent_invoice', ( !empty( $invoice_data['parent'] ) ? absint( $invoice_data['parent'] ) : '' ) );
    
    if ( !empty( $invoice_data['cart_details'] ) && is_array( $invoice_data['cart_details'] ) ) {
        foreach ( $invoice_data['cart_details'] as $key => $item ) {
            $item_id        = !empty( $item['id'] ) ? $item['id'] : 0;
            $quantity       = !empty( $item['quantity'] ) ? $item['quantity'] : 1;
            $name           = !empty( $item['name'] ) ? $item['name'] : '';
            $item_price     = isset( $item['item_price'] ) ? $item['item_price'] : '';
            
            $post_item  = new WPInv_Item( $item_id );
            if ( !empty( $post_item ) ) {
                $name       = !empty( $name ) ? $name : $post_item->get_name();
                $item_price = $item_price !== '' ? $item_price : $post_item->get_price();
            } else {
                continue;
            }
            
            $args = array(
                'name'          => $name,
                'quantity'      => $quantity,
                'item_price'    => $item_price,
                'custom_price'  => isset( $item['custom_price'] ) ? $item['custom_price'] : '',
                'tax'           => !empty( $item['tax'] ) ? $item['tax'] : 0.00,
                'discount'      => isset( $item['discount'] ) ? $item['discount'] : 0,
                'meta'          => isset( $item['meta'] ) ? $item['meta'] : array(),
                'fees'          => isset( $item['fees'] ) ? $item['fees'] : array(),
            );

            $invoice->add_item( $item_id, $args );
        }
    }

    $invoice->increase_tax( wpinv_get_cart_fee_tax() );

    if ( isset( $invoice_data['post_date'] ) ) {
        $invoice->set( 'date', $invoice_data['post_date'] );
    }
    
    // Invoice due date
    if ( isset( $invoice_data['due_date'] ) ) {
        $invoice->set( 'due_date', $invoice_data['due_date'] );
    }
    
    $invoice->save();
    
    // Add notes
    if ( !empty( $invoice_data['private_note'] ) ) {
        $invoice->add_note( $invoice_data['private_note'] );
    }
    if ( !empty( $invoice_data['user_note'] ) ) {
        $invoice->add_note( $invoice_data['user_note'], true );
    }
    
    do_action( 'wpinv_insert_invoice', $invoice->ID, $invoice_data );

    if ( ! empty( $invoice->ID ) ) {
        global $wpi_userID, $wpinv_ip_address_country;
        
        $checkout_session = wpinv_get_checkout_session();
        
        $data_session                   = array();
        $data_session['invoice_id']     = $invoice->ID;
        $data_session['cart_discounts'] = $invoice->get_discounts( true );
        
        wpinv_set_checkout_session( $data_session );
        
        $wpi_userID         = (int)$invoice->get_user_id();
        
        $_POST['country']   = !empty( $invoice->country ) ? $invoice->country : wpinv_get_default_country();
        $_POST['state']     = $invoice->state;

        $invoice->set( 'country', sanitize_text_field( $_POST['country'] ) );
        $invoice->set( 'state', sanitize_text_field( $_POST['state'] ) );
        
        $wpinv_ip_address_country = $invoice->country;
        
        $invoice = $invoice->recalculate_totals( true );
        
        wpinv_set_checkout_session( $checkout_session );
                    
        return $invoice;
    }
    
    if ( $wp_error ) {
        if ( is_wp_error( $invoice ) ) {
            return $invoice;
        } else {
            return new WP_Error( 'wpinv_insert_invoice_error', __( 'Error in insert invoice.', 'invoicing' ) );
        }
    } else {
        return 0;
    }
}

function wpinv_update_invoice( $invoice_data = array(), $wp_error = false ) {
    $invoice_ID = !empty( $invoice_data['ID'] ) ? absint( $invoice_data['ID'] ) : NULL;

    if ( !$invoice_ID ) {
        if ( $wp_error ) {
            return new WP_Error( 'invalid_invoice_id', __( 'Invalid invoice ID.', 'invoicing' ) );
        }
        return 0;
    }

    $invoice = wpinv_get_invoice( $invoice_ID );

    $recurring_item = $invoice->is_recurring() ? $invoice->get_recurring( true ) : NULL;

    if ( empty( $invoice->ID ) ) {
        if ( $wp_error ) {
            return new WP_Error( 'invalid_invoice', __( 'Invalid invoice.', 'invoicing' ) );
        }
        return 0;
    }

    if ( !$invoice->has_status( array( 'wpi-pending' ) ) ) {
        if ( $wp_error ) {
            return new WP_Error( 'invalid_invoice_status', __( 'Only invoice with pending payment is allowed to update.', 'invoicing' ) );
        }
        return 0;
    }

    // Invoice status
    if ( !empty( $invoice_data['status'] ) ) {
        $invoice->set( 'status', $invoice_data['status'] );
    }

    // Invoice date
    if ( !empty( $invoice_data['post_date'] ) ) {
        $invoice->set( 'date', $invoice_data['post_date'] );
    }

    // Invoice due date
    if ( isset( $invoice_data['due_date'] ) ) {
        $invoice->set( 'due_date', $invoice_data['due_date'] );
    }

    // Invoice IP address
    if ( !empty( $invoice_data['ip'] ) ) {
        $invoice->set( 'ip', $invoice_data['ip'] );
    }
    
    // User info
    if ( !empty( $invoice_data['user_info'] ) && is_array( $invoice_data['user_info'] ) ) {
        $user_info = wp_parse_args( $invoice_data['user_info'], $invoice->user_info );

        if ( $discounts = $invoice->get_discounts() ) {
            $set_discount = $discounts;
        } else {
            $set_discount = '';
        }

        // Manage discount
        if ( !empty( $invoice_data['user_info']['discount'] ) ) {
            // Remove discount
            if ( $invoice_data['user_info']['discount'] == 'none' ) {
                $set_discount = '';
            } else {
                $set_discount = $invoice_data['user_info']['discount'];
            }

            $invoice->set( 'discounts', $set_discount );
        }

        $user_info['discount'] = $set_discount;

        $invoice->set( 'user_info', $user_info );
    }

    if ( !empty( $invoice_data['cart_details'] ) && is_array( $invoice_data['cart_details'] ) && $cart_details = $invoice_data['cart_details'] ) {
        $remove_items = !empty( $cart_details['remove_items'] ) && is_array( $cart_details['remove_items'] ) ? $cart_details['remove_items'] : array();

        if ( !empty( $remove_items[0]['id'] ) ) {
            foreach ( $remove_items as $item ) {
                $item_id        = !empty( $item['id'] ) ? $item['id'] : 0;
                $quantity       = !empty( $item['quantity'] ) ? $item['quantity'] : 1;
                if ( empty( $item_id ) ) {
                    continue;
                }

                foreach ( $invoice->cart_details as $cart_index => $cart_item ) {
                    if ( $item_id == $cart_item['id'] ) {
                        $args = array(
                            'id'         => $item_id,
                            'quantity'   => $quantity,
                            'cart_index' => $cart_index
                        );

                        $invoice->remove_item( $item_id, $args );
                        break;
                    }
                }
            }
        }

        $add_items = !empty( $cart_details['add_items'] ) && is_array( $cart_details['add_items'] ) ? $cart_details['add_items'] : array();

        if ( !empty( $add_items[0]['id'] ) ) {
            foreach ( $add_items as $item ) {
                $item_id        = !empty( $item['id'] ) ? $item['id'] : 0;
                $post_item      = new WPInv_Item( $item_id );
                if ( empty( $post_item ) ) {
                    continue;
                }

                $valid_item = true;
                if ( !empty( $recurring_item ) ) {
                    if ( $recurring_item->ID != $item_id ) {
                        $valid_item = false;
                    }
                } else if ( wpinv_is_recurring_item( $item_id ) ) {
                    $valid_item = false;
                }
                
                if ( !$valid_item ) {
                    if ( $wp_error ) {
                        return new WP_Error( 'invalid_invoice_item', __( 'You can not add item to invoice because recurring item must be paid individually!', 'invoicing' ) );
                    }
                    return 0;
                }

                $quantity       = !empty( $item['quantity'] ) ? $item['quantity'] : 1;
                $name           = !empty( $item['name'] ) ? $item['name'] : $post_item->get_name();
                $item_price     = isset( $item['item_price'] ) ? $item['item_price'] : $post_item->get_price();

                $args = array(
                    'name'          => $name,
                    'quantity'      => $quantity,
                    'item_price'    => $item_price,
                    'custom_price'  => isset( $item['custom_price'] ) ? $item['custom_price'] : '',
                    'tax'           => !empty( $item['tax'] ) ? $item['tax'] : 0,
                    'discount'      => isset( $item['discount'] ) ? $item['discount'] : 0,
                    'meta'          => isset( $item['meta'] ) ? $item['meta'] : array(),
                    'fees'          => isset( $item['fees'] ) ? $item['fees'] : array(),
                );

                $invoice->add_item( $item_id, $args );
            }
        }
    }
    
    // Payment details
    if ( !empty( $invoice_data['payment_details'] ) && $payment_details = $invoice_data['payment_details'] ) {
        if ( !empty( $payment_details['gateway'] ) ) {
            $invoice->set( 'gateway', $payment_details['gateway'] );
        }

        if ( !empty( $payment_details['transaction_id'] ) ) {
            $invoice->set( 'transaction_id', $payment_details['transaction_id'] );
        }
    }

    do_action( 'wpinv_pre_update_invoice', $invoice->ID, $invoice_data );

    // Parent invoice
    if ( !empty( $invoice_data['parent'] ) ) {
        $invoice->set( 'parent_invoice', $invoice_data['parent'] );
    }

    // Save invoice data.
    $invoice->save();
    
    if ( empty( $invoice->ID ) || is_wp_error( $invoice ) ) {
        if ( $wp_error ) {
            if ( is_wp_error( $invoice ) ) {
                return $invoice;
            } else {
                return new WP_Error( 'wpinv_update_invoice_error', __( 'Error in update invoice.', 'invoicing' ) );
            }
        } else {
            return 0;
        }
    }

    // Add private note
    if ( !empty( $invoice_data['private_note'] ) ) {
        $invoice->add_note( $invoice_data['private_note'] );
    }

    // Add user note
    if ( !empty( $invoice_data['user_note'] ) ) {
        $invoice->add_note( $invoice_data['user_note'], true );
    }

    global $wpi_userID, $wpinv_ip_address_country;

    $checkout_session = wpinv_get_checkout_session();

    $data_session                   = array();
    $data_session['invoice_id']     = $invoice->ID;
    $data_session['cart_discounts'] = $invoice->get_discounts( true );

    wpinv_set_checkout_session( $data_session );

    $wpi_userID         = (int)$invoice->get_user_id();

    $_POST['country']   = !empty( $invoice->country ) ? $invoice->country : wpinv_get_default_country();
    $_POST['state']     = $invoice->state;

    $invoice->set( 'country', sanitize_text_field( $_POST['country'] ) );
    $invoice->set( 'state', sanitize_text_field( $_POST['state'] ) );

    $wpinv_ip_address_country = $invoice->country;

    $invoice = $invoice->recalculate_totals( true );

    do_action( 'wpinv_post_update_invoice', $invoice->ID, $invoice_data );

    wpinv_set_checkout_session( $checkout_session );

    return $invoice;
}

function wpinv_get_invoice( $invoice_id = 0, $cart = false ) {
    if ( $cart && empty( $invoice_id ) ) {
        $invoice_id = (int)wpinv_get_invoice_cart_id();
    }

    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice;
}

function wpinv_get_invoice_cart( $invoice_id = 0 ) {
    return wpinv_get_invoice( $invoice_id, true );
}

function wpinv_get_invoice_description( $invoice_id = 0 ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_description();
}

function wpinv_get_invoice_currency_code( $invoice_id = 0 ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_currency();
}

function wpinv_get_payment_user_email( $invoice_id ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_email();
}

function wpinv_get_user_id( $invoice_id ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_user_id();
}

function wpinv_get_invoice_status( $invoice_id, $return_label = false ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    
    return $invoice->get_status( $return_label );
}

function wpinv_get_payment_gateway( $invoice_id, $return_label = false ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    
    return $invoice->get_gateway( $return_label );
}

function wpinv_get_payment_gateway_name( $invoice_id ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    
    return $invoice->get_gateway_title();
}

function wpinv_get_payment_transaction_id( $invoice_id ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    
    return $invoice->get_transaction_id();
}

function wpinv_get_id_by_transaction_id( $key ) {
    global $wpdb;

    $invoice_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpinv_transaction_id' AND meta_value = %s LIMIT 1", $key ) );

    if ( $invoice_id != NULL )
        return $invoice_id;

    return 0;
}

function wpinv_get_invoice_meta( $invoice_id = 0, $meta_key = '_wpinv_payment_meta', $single = true ) {
    $invoice = new WPInv_Invoice( $invoice_id );

    return $invoice->get_meta( $meta_key, $single );
}

function wpinv_update_invoice_meta( $invoice_id = 0, $meta_key = '', $meta_value = '', $prev_value = '' ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    
    return $invoice->update_meta( $meta_key, $meta_value, $prev_value );
}

function wpinv_get_items( $invoice_id = 0 ) {
    $invoice            = wpinv_get_invoice( $invoice_id );
    
    $items              = $invoice->get_items();
    $invoice_currency   = $invoice->get_currency();

    if ( !empty( $items ) && is_array( $items ) ) {
        foreach ( $items as $key => $item ) {
            $items[$key]['currency'] = $invoice_currency;

            if ( !isset( $cart_item['subtotal'] ) ) {
                $items[$key]['subtotal'] = $items[$key]['amount'] * 1;
            }
        }
    }

    return apply_filters( 'wpinv_get_items', $items, $invoice_id );
}

function wpinv_get_fees( $invoice_id = 0 ) {
    $invoice           = wpinv_get_invoice( $invoice_id );
    $fees              = $invoice->get_fees();

    return apply_filters( 'wpinv_get_fees', $fees, $invoice_id );
}

function wpinv_get_invoice_ip( $invoice_id ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_ip();
}

function wpinv_get_invoice_user_info( $invoice_id ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_user_info();
}

function wpinv_subtotal( $invoice_id = 0, $currency = false ) {
    $invoice = new WPInv_Invoice( $invoice_id );

    return $invoice->get_subtotal( $currency );
}

function wpinv_tax( $invoice_id = 0, $currency = false ) {
    $invoice = new WPInv_Invoice( $invoice_id );

    return $invoice->get_tax( $currency );
}

function wpinv_discount( $invoice_id = 0, $currency = false, $dash = false ) {
    $invoice = wpinv_get_invoice( $invoice_id );

    return $invoice->get_discount( $currency, $dash );
}

function wpinv_discount_code( $invoice_id = 0 ) {
    $invoice = new WPInv_Invoice( $invoice_id );

    return $invoice->get_discount_code();
}

function wpinv_payment_total( $invoice_id = 0, $currency = false ) {
    $invoice = new WPInv_Invoice( $invoice_id );

    return $invoice->get_total( $currency );
}

function wpinv_get_date_created( $invoice_id = 0 ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    
    $date_created   = $invoice->get_created_date();
    $date_created   = $date_created != '' && $date_created != '0000-00-00 00:00:00' ? date_i18n( get_option( 'date_format' ), strtotime( $date_created ) ) : '';

    return $date_created;
}

function wpinv_get_invoice_date( $invoice_id = 0, $format = '' ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    
    $format         = !empty( $format ) ? $format : get_option( 'date_format' );
    $date_completed = $invoice->get_completed_date();
    $invoice_date   = $date_completed != '' && $date_completed != '0000-00-00 00:00:00' ? date_i18n( $format, strtotime( $date_completed ) ) : '';
    if ( $invoice_date == '' ) {
        $date_created   = $invoice->get_created_date();
        $invoice_date   = $date_created != '' && $date_created != '0000-00-00 00:00:00' ? date_i18n( $format, strtotime( $date_created ) ) : '';
    }

    return $invoice_date;
}

function wpinv_get_invoice_vat_number( $invoice_id = 0 ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    
    return $invoice->vat_number;
}

function wpinv_insert_payment_note( $invoice_id = 0, $note = '', $user_type = false, $added_by_user = false ) {
    $invoice = new WPInv_Invoice( $invoice_id );

    return $invoice->add_note( $note, $user_type, $added_by_user );
}

function wpinv_get_invoice_notes( $invoice_id = 0, $type = '' ) {
    global $invoicing;
    
    if ( empty( $invoice_id ) ) {
        return NULL;
    }
    
    $notes = $invoicing->notes->get_invoice_notes( $invoice_id, $type );
    
    return apply_filters( 'wpinv_invoice_notes', $notes, $invoice_id, $type );
}

function wpinv_get_payment_key( $invoice_id = 0 ) {
	$invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_key();
}

function wpinv_get_invoice_number( $invoice_id = 0 ) {
    $invoice = new WPInv_Invoice( $invoice_id );
    return $invoice->get_number();
}

function wpinv_get_cart_discountable_subtotal( $code_id ) {
    $cart_items = wpinv_get_cart_content_details();
    $items      = array();

    $excluded_items = wpinv_get_discount_excluded_items( $code_id );

    if( $cart_items ) {

        foreach( $cart_items as $item ) {

            if( ! in_array( $item['id'], $excluded_items ) ) {
                $items[] =  $item;
            }
        }
    }

    $subtotal = wpinv_get_cart_items_subtotal( $items );

    return apply_filters( 'wpinv_get_cart_discountable_subtotal', $subtotal );
}

function wpinv_get_cart_items_subtotal( $items ) {
    $subtotal = 0.00;

    if ( is_array( $items ) && ! empty( $items ) ) {
        $prices = wp_list_pluck( $items, 'subtotal' );

        if( is_array( $prices ) ) {
            $subtotal = array_sum( $prices );
        } else {
            $subtotal = 0.00;
        }

        if( $subtotal < 0 ) {
            $subtotal = 0.00;
        }
    }

    return apply_filters( 'wpinv_get_cart_items_subtotal', $subtotal );
}

function wpinv_get_cart_subtotal( $items = array() ) {
    $items    = !empty( $items ) ? $items : wpinv_get_cart_content_details();
    $subtotal = wpinv_get_cart_items_subtotal( $items );

    return apply_filters( 'wpinv_get_cart_subtotal', $subtotal );
}

function wpinv_cart_subtotal( $items = array() ) {
    $price = wpinv_price( wpinv_format_amount( wpinv_get_cart_subtotal( $items ) ) );

    return $price;
}

function wpinv_get_cart_total( $items = array(), $discounts = false, $invoice = array() ) {
    $subtotal  = (float)wpinv_get_cart_subtotal( $items );
    $discounts = (float)wpinv_get_cart_discounted_amount( $items );
    $cart_tax  = (float)wpinv_get_cart_tax( $items );
    $fees      = (float)wpinv_get_cart_fee_total();
    if ( !empty( $invoice ) && $invoice->is_free_trial() ) {
        $total = 0;
    } else {
        $total     = $subtotal - $discounts + $cart_tax + $fees;
    }

    if ( $total < 0 ) {
        $total = 0.00;
    }
    
    $total = (float)apply_filters( 'wpinv_get_cart_total', $total, $items );

    return wpinv_sanitize_amount( $total );
}

function wpinv_cart_total( $cart_items = array(), $echo = true, $invoice = array() ) {
    global $cart_total;
    $total = wpinv_price( wpinv_format_amount( wpinv_get_cart_total( $cart_items, NULL, $invoice ) ) );
    $total = apply_filters( 'wpinv_cart_total', $total, $cart_items, $invoice );
    
    $cart_total = $total;

    if ( !$echo ) {
        return $total;
    }

    echo $total;
}

function wpinv_get_cart_tax( $items = array() ) {
    $cart_tax = 0;
    $items    = !empty( $items ) ? $items : wpinv_get_cart_content_details();

    if ( $items ) {
        $taxes = wp_list_pluck( $items, 'tax' );

        if( is_array( $taxes ) ) {
            $cart_tax = array_sum( $taxes );
        }
    }

    $cart_tax += wpinv_get_cart_fee_tax();

    return apply_filters( 'wpinv_get_cart_tax', wpinv_sanitize_amount( $cart_tax ) );
}

function wpinv_cart_tax( $items = array(), $echo = false ) {
    $cart_tax = wpinv_get_cart_tax( $items );
    $cart_tax = wpinv_price( wpinv_format_amount( $cart_tax ) );

    $tax = apply_filters( 'wpinv_cart_tax', $cart_tax, $items );

    if ( !$echo ) {
        return $tax;
    }

    echo $tax;
}

function wpinv_get_cart_discount_code( $items = array() ) {
    $invoice = wpinv_get_invoice_cart();
    $cart_discount_code = !empty( $invoice ) ? $invoice->get_discount_code() : '';
    
    return apply_filters( 'wpinv_get_cart_discount_code', $cart_discount_code );
}

function wpinv_cart_discount_code( $items = array(), $echo = false ) {
    $cart_discount_code = wpinv_get_cart_discount_code( $items );

    if ( $cart_discount_code != '' ) {
        $cart_discount_code = ' (' . $cart_discount_code . ')';
    }
    
    $discount_code = apply_filters( 'wpinv_cart_discount_code', $cart_discount_code, $items );

    if ( !$echo ) {
        return $discount_code;
    }

    echo $discount_code;
}

function wpinv_get_cart_discount( $items = array() ) {
    $invoice = wpinv_get_invoice_cart();
    $cart_discount = !empty( $invoice ) ? $invoice->get_discount() : 0;
    
    return apply_filters( 'wpinv_get_cart_discount', wpinv_sanitize_amount( $cart_discount ), $items );
}

function wpinv_cart_discount( $items = array(), $echo = false ) {
    $cart_discount = wpinv_get_cart_discount( $items );
    $cart_discount = wpinv_price( wpinv_format_amount( $cart_discount ) );

    $discount = apply_filters( 'wpinv_cart_discount', $cart_discount, $items );

    if ( !$echo ) {
        return $discount;
    }

    echo $discount;
}

function wpinv_get_cart_fees( $type = 'all', $item_id = 0 ) {
    $item = new WPInv_Item( $item_id );
    
    return $item->get_fees( $type, $item_id );
}

function wpinv_get_cart_fee_total() {
    $total  = 0;
    $fees = wpinv_get_cart_fees();
    
    if ( $fees ) {
        foreach ( $fees as $fee_id => $fee ) {
            $total += $fee['amount'];
        }
    }

    return apply_filters( 'wpinv_get_cart_fee_total', $total );
}

function wpinv_get_cart_fee_tax() {
    $tax  = 0;
    $fees = wpinv_get_cart_fees();

    if ( $fees ) {
        foreach ( $fees as $fee_id => $fee ) {
            if( ! empty( $fee['no_tax'] ) ) {
                continue;
            }

            $tax += wpinv_calculate_tax( $fee['amount'] );
        }
    }

    return apply_filters( 'wpinv_get_cart_fee_tax', $tax );
}

function wpinv_cart_has_recurring_item() {
    $cart_items = wpinv_get_cart_contents();
    
    if ( empty( $cart_items ) ) {
        return false;
    }
    
    $has_subscription = false;
    foreach( $cart_items as $cart_item ) {
        if ( !empty( $cart_item['id'] ) && wpinv_is_recurring_item( $cart_item['id'] )  ) {
            $has_subscription = true;
            break;
        }
    }
    
    return apply_filters( 'wpinv_cart_has_recurring_item', $has_subscription, $cart_items );
}

function wpinv_cart_has_free_trial() {
    $invoice = wpinv_get_invoice_cart();
    
    $free_trial = false;
    
    if ( !empty( $invoice ) && $invoice->is_free_trial() ) {
        $free_trial = true;
    }
    
    return apply_filters( 'wpinv_cart_has_free_trial', $free_trial, $invoice );
}

function wpinv_get_cart_contents() {
    $cart_details = wpinv_get_cart_details();
    
    return apply_filters( 'wpinv_get_cart_contents', $cart_details );
}

function wpinv_get_cart_content_details() {
    global $wpinv_euvat, $wpi_current_id, $wpi_item_id, $wpinv_is_last_cart_item, $wpinv_flat_discount_total;
    $cart_items = wpinv_get_cart_contents();
    
    if ( empty( $cart_items ) ) {
        return false;
    }
    $invoice = wpinv_get_invoice_cart();

    $details = array();
    $length  = count( $cart_items ) - 1;
    
    if ( empty( $_POST['country'] ) ) {
        $_POST['country'] = $invoice->country;
    }
    if ( !isset( $_POST['state'] ) ) {
        $_POST['state'] = $invoice->state;
    }

    foreach( $cart_items as $key => $item ) {
        $item_id            = isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '';
        if ( empty( $item_id ) ) {
            continue;
        }
        
        $wpi_current_id         = $invoice->ID;
        $wpi_item_id            = $item_id;
        
        if ( isset( $item['custom_price'] ) && $item['custom_price'] !== '' ) {
            $item_price = $item['custom_price'];
        } else {
            if ( isset( $item['item_price'] ) && $item['item_price'] !== '' && $item['item_price'] !== false ) {
                $item_price = $item['item_price'];
            } else {
                $item_price = wpinv_get_item_price( $item_id );
            }
        }
        $discount           = wpinv_get_cart_item_discount_amount( $item );
        $discount           = apply_filters( 'wpinv_get_cart_content_details_item_discount_amount', $discount, $item );
        $quantity           = wpinv_get_cart_item_quantity( $item );
        $fees               = wpinv_get_cart_fees( 'fee', $item_id );
        
        $subtotal           = $item_price * $quantity;
        $tax_rate           = wpinv_get_tax_rate( $_POST['country'], $_POST['state'], $wpi_item_id );
        $tax_class          = $wpinv_euvat->get_item_class( $item_id );
        $tax                = wpinv_get_cart_item_tax( $item_id, $subtotal - $discount );
        
        if ( wpinv_prices_include_tax() ) {
            $subtotal -= wpinv_round_amount( $tax );
        }
        
        $total              = $subtotal - $discount + $tax;
        
        // Do not allow totals to go negatve
        if( $total < 0 ) {
            $total = 0;
        }
        
        $details[ $key ]  = array(
            'id'                => $item_id,
            'name'              => !empty($item['name']) ? $item['name'] : get_the_title( $item_id ),
            'item_price'        => wpinv_round_amount( $item_price ),
            'custom_price'      => isset( $item['custom_price'] ) ? $item['custom_price'] : '',
            'quantity'          => $quantity,
            'discount'          => wpinv_round_amount( $discount ),
            'subtotal'          => wpinv_round_amount( $subtotal ),
            'tax'               => wpinv_round_amount( $tax ),
            'price'             => wpinv_round_amount( $total ),
            'vat_rates_class'   => $tax_class,
            'vat_rate'          => $tax_rate,
            'meta'              => isset( $item['meta'] ) ? $item['meta'] : array(),
            'fees'              => $fees,
        );
        
        if ( $wpinv_is_last_cart_item ) {
            $wpinv_is_last_cart_item   = false;
            $wpinv_flat_discount_total = 0.00;
        }
    }
    
    return $details;
}

function wpinv_get_cart_details( $invoice_id = 0 ) {
    global $ajax_cart_details;

    $invoice      = wpinv_get_invoice_cart( $invoice_id );
    $cart_details = !empty( $ajax_cart_details ) ? $ajax_cart_details : $invoice->cart_details;

    $invoice_currency = $invoice->currency;

    if ( ! empty( $cart_details ) && is_array( $cart_details ) ) {
        foreach ( $cart_details as $key => $cart_item ) {
            $cart_details[ $key ]['currency'] = $invoice_currency;

            if ( ! isset( $cart_item['subtotal'] ) ) {
                $cart_details[ $key ]['subtotal'] = $cart_item['price'];
            }
        }
    }

    return apply_filters( 'wpinv_get_cart_details', $cart_details, $invoice_id );
}

function wpinv_record_status_change( $invoice_id, $new_status, $old_status ) {
    if ( 'wpi_invoice' != get_post_type( $invoice_id ) ) {
        return;
    }

    $invoice    = wpinv_get_invoice( $invoice_id );
    
    $old_status = wpinv_status_nicename( $old_status );
    $new_status = wpinv_status_nicename( $new_status );

    $status_change = sprintf( __( 'Invoice status changed from %s to %s', 'invoicing' ), $old_status, $new_status );
    
    // Add note
    return $invoice->add_note( $status_change, false, false, true );
}
add_action( 'wpinv_update_status', 'wpinv_record_status_change', 100, 3 );

function wpinv_complete_payment( $invoice_id, $new_status, $old_status ) {
    global $wpi_has_free_trial;
    
    $wpi_has_free_trial = false;
    
    if ( $old_status == 'publish' ) {
        return; // Make sure that payments are only paid once
    }

    // Make sure the payment completion is only processed when new status is paid
    if ( $new_status != 'publish' ) {
        return;
    }

    $invoice = new WPInv_Invoice( $invoice_id );
    if ( empty( $invoice ) ) {
        return;
    }

    $wpi_has_free_trial = $invoice->is_free_trial();
    $completed_date = $invoice->completed_date;
    $cart_details   = $invoice->cart_details;

    do_action( 'wpinv_pre_complete_payment', $invoice_id );

    if ( is_array( $cart_details ) ) {
        // Increase purchase count and earnings
        foreach ( $cart_details as $cart_index => $item ) {
            // Ensure these actions only run once, ever
            if ( empty( $completed_date ) ) {
                do_action( 'wpinv_complete_item_payment', $item['id'], $invoice_id, $item, $cart_index );
            }
        }
    }
    
    // Check for discount codes and increment their use counts
    if ( $discounts = $invoice->get_discounts( true ) ) {
        if( ! empty( $discounts ) ) {
            foreach( $discounts as $code ) {
                wpinv_increase_discount_usage( $code );
            }
        }
    }
    
    // Ensure this action only runs once ever
    if( empty( $completed_date ) ) {
        // Save the completed date
        $invoice->set( 'completed_date', current_time( 'mysql', 0 ) );
        $invoice->save();

        do_action( 'wpinv_complete_payment', $invoice_id );
    }

    // Empty the shopping cart
    wpinv_empty_cart();
}
add_action( 'wpinv_update_status', 'wpinv_complete_payment', 100, 3 );

function wpinv_update_payment_status( $invoice_id, $new_status = 'publish' ) {    
    $invoice = !empty( $invoice_id ) && is_object( $invoice_id ) ? $invoice_id : wpinv_get_invoice( (int)$invoice_id );
    
    if ( empty( $invoice ) ) {
        return false;
    }
    
    return $invoice->update_status( $new_status );
}

function wpinv_cart_has_fees( $type = 'all' ) {
    return false;
}

function wpinv_validate_checkout_fields() {    
    // Check if there is $_POST
    if ( empty( $_POST ) ) {
        return false;
    }
    
    // Start an array to collect valid data
    $valid_data = array(
        'gateway'          => wpinv_checkout_validate_gateway(), // Gateway fallback
        'discount'         => wpinv_checkout_validate_discounts(), // Set default discount
        'cc_info'          => wpinv_checkout_validate_cc() // Credit card info
    );
    
    // Validate agree to terms
    if ( wpinv_get_option( 'show_agree_to_terms', false ) ) {
        wpinv_checkout_validate_agree_to_terms();
    }
    
    $valid_data['invoice_user'] = wpinv_checkout_validate_invoice_user();
    $valid_data['current_user'] = wpinv_checkout_validate_current_user();
    
    // Return collected data
    return $valid_data;
}

function wpinv_checkout_validate_gateway() {
    $gateway = wpinv_get_default_gateway();
    
    $invoice = wpinv_get_invoice_cart();
    $has_subscription = $invoice->is_recurring();
    if ( empty( $invoice ) ) {
        wpinv_set_error( 'invalid_invoice', __( 'Your cart is empty.', 'invoicing' ) );
        return $gateway;
    }

    // Check if a gateway value is present
    if ( !empty( $_REQUEST['wpi-gateway'] ) ) {
        $gateway = sanitize_text_field( $_REQUEST['wpi-gateway'] );

        if ( $invoice->is_free() ) {
            $gateway = 'manual';
        } elseif ( !wpinv_is_gateway_active( $gateway ) ) {
            wpinv_set_error( 'invalid_gateway', __( 'The selected payment gateway is not enabled', 'invoicing' ) );
        } elseif ( $has_subscription && !wpinv_gateway_support_subscription( $gateway ) ) {
            wpinv_set_error( 'invalid_gateway', __( 'The selected payment gateway doesnot support subscription payment', 'invoicing' ) );
        }
    }

    if ( $has_subscription && count( wpinv_get_cart_contents() ) > 1 ) {
        wpinv_set_error( 'subscription_invalid', __( 'Only one subscription may be purchased through payment per checkout.', 'invoicing' ) );
    }

    return $gateway;
}

function wpinv_checkout_validate_discounts() {
    global $wpi_cart;
    
    // Retrieve the discount stored in cookies
    $discounts = wpinv_get_cart_discounts();
    
    $error = false;
    // If we have discounts, loop through them
    if ( ! empty( $discounts ) ) {
        foreach ( $discounts as $discount ) {
            // Check if valid
            if (  !wpinv_is_discount_valid( $discount, (int)$wpi_cart->get_user_id() ) ) {
                // Discount is not valid
                $error = true;
            }
        }
    } else {
        // No discounts
        return NULL;
    }

    if ( $error && !wpinv_get_errors() ) {
        wpinv_set_error( 'invalid_discount', __( 'Discount code you entered is invalid', 'invoicing' ) );
    }

    return implode( ',', $discounts );
}

function wpinv_checkout_validate_cc() {
    $card_data = wpinv_checkout_get_cc_info();

    // Validate the card zip
    if ( !empty( $card_data['wpinv_zip'] ) ) {
        if ( !wpinv_checkout_validate_cc_zip( $card_data['wpinv_zip'], $card_data['wpinv_country'] ) ) {
            wpinv_set_error( 'invalid_cc_zip', __( 'The zip / postcode you entered for your billing address is invalid', 'invoicing' ) );
        }
    }

    // This should validate card numbers at some point too
    return $card_data;
}

function wpinv_checkout_get_cc_info() {
	$cc_info = array();
	$cc_info['card_name']      = isset( $_POST['card_name'] )       ? sanitize_text_field( $_POST['card_name'] )       : '';
	$cc_info['card_number']    = isset( $_POST['card_number'] )     ? sanitize_text_field( $_POST['card_number'] )     : '';
	$cc_info['card_cvc']       = isset( $_POST['card_cvc'] )        ? sanitize_text_field( $_POST['card_cvc'] )        : '';
	$cc_info['card_exp_month'] = isset( $_POST['card_exp_month'] )  ? sanitize_text_field( $_POST['card_exp_month'] )  : '';
	$cc_info['card_exp_year']  = isset( $_POST['card_exp_year'] )   ? sanitize_text_field( $_POST['card_exp_year'] )   : '';
	$cc_info['card_address']   = isset( $_POST['wpinv_address'] )  ? sanitize_text_field( $_POST['wpinv_address'] ) : '';
	$cc_info['card_city']      = isset( $_POST['wpinv_city'] )     ? sanitize_text_field( $_POST['wpinv_city'] )    : '';
	$cc_info['card_state']     = isset( $_POST['wpinv_state'] )    ? sanitize_text_field( $_POST['wpinv_state'] )   : '';
	$cc_info['card_country']   = isset( $_POST['wpinv_country'] )  ? sanitize_text_field( $_POST['wpinv_country'] ) : '';
	$cc_info['card_zip']       = isset( $_POST['wpinv_zip'] )      ? sanitize_text_field( $_POST['wpinv_zip'] )     : '';

	// Return cc info
	return $cc_info;
}

function wpinv_checkout_validate_cc_zip( $zip = 0, $country_code = '' ) {
    $ret = false;

    if ( empty( $zip ) || empty( $country_code ) )
        return $ret;

    $country_code = strtoupper( $country_code );

    $zip_regex = array(
        "AD" => "AD\d{3}",
        "AM" => "(37)?\d{4}",
        "AR" => "^([A-Z]{1}\d{4}[A-Z]{3}|[A-Z]{1}\d{4}|\d{4})$",
        "AS" => "96799",
        "AT" => "\d{4}",
        "AU" => "^(0[289][0-9]{2})|([1345689][0-9]{3})|(2[0-8][0-9]{2})|(290[0-9])|(291[0-4])|(7[0-4][0-9]{2})|(7[8-9][0-9]{2})$",
        "AX" => "22\d{3}",
        "AZ" => "\d{4}",
        "BA" => "\d{5}",
        "BB" => "(BB\d{5})?",
        "BD" => "\d{4}",
        "BE" => "^[1-9]{1}[0-9]{3}$",
        "BG" => "\d{4}",
        "BH" => "((1[0-2]|[2-9])\d{2})?",
        "BM" => "[A-Z]{2}[ ]?[A-Z0-9]{2}",
        "BN" => "[A-Z]{2}[ ]?\d{4}",
        "BR" => "\d{5}[\-]?\d{3}",
        "BY" => "\d{6}",
        "CA" => "^[ABCEGHJKLMNPRSTVXY]{1}\d{1}[A-Z]{1} *\d{1}[A-Z]{1}\d{1}$",
        "CC" => "6799",
        "CH" => "^[1-9][0-9][0-9][0-9]$",
        "CK" => "\d{4}",
        "CL" => "\d{7}",
        "CN" => "\d{6}",
        "CR" => "\d{4,5}|\d{3}-\d{4}",
        "CS" => "\d{5}",
        "CV" => "\d{4}",
        "CX" => "6798",
        "CY" => "\d{4}",
        "CZ" => "\d{3}[ ]?\d{2}",
        "DE" => "\b((?:0[1-46-9]\d{3})|(?:[1-357-9]\d{4})|(?:[4][0-24-9]\d{3})|(?:[6][013-9]\d{3}))\b",
        "DK" => "^([D-d][K-k])?( |-)?[1-9]{1}[0-9]{3}$",
        "DO" => "\d{5}",
        "DZ" => "\d{5}",
        "EC" => "([A-Z]\d{4}[A-Z]|(?:[A-Z]{2})?\d{6})?",
        "EE" => "\d{5}",
        "EG" => "\d{5}",
        "ES" => "^([1-9]{2}|[0-9][1-9]|[1-9][0-9])[0-9]{3}$",
        "ET" => "\d{4}",
        "FI" => "\d{5}",
        "FK" => "FIQQ 1ZZ",
        "FM" => "(9694[1-4])([ \-]\d{4})?",
        "FO" => "\d{3}",
        "FR" => "^(F-)?((2[A|B])|[0-9]{2})[0-9]{3}$",
        "GE" => "\d{4}",
        "GF" => "9[78]3\d{2}",
        "GL" => "39\d{2}",
        "GN" => "\d{3}",
        "GP" => "9[78][01]\d{2}",
        "GR" => "\d{3}[ ]?\d{2}",
        "GS" => "SIQQ 1ZZ",
        "GT" => "\d{5}",
        "GU" => "969[123]\d([ \-]\d{4})?",
        "GW" => "\d{4}",
        "HM" => "\d{4}",
        "HN" => "(?:\d{5})?",
        "HR" => "\d{5}",
        "HT" => "\d{4}",
        "HU" => "\d{4}",
        "ID" => "\d{5}",
        "IE" => "((D|DUBLIN)?([1-9]|6[wW]|1[0-8]|2[024]))?",
        "IL" => "\d{5}",
        "IN"=> "^[1-9][0-9][0-9][0-9][0-9][0-9]$", //india
        "IO" => "BBND 1ZZ",
        "IQ" => "\d{5}",
        "IS" => "\d{3}",
        "IT" => "^(V-|I-)?[0-9]{5}$",
        "JO" => "\d{5}",
        "JP" => "\d{3}-\d{4}",
        "KE" => "\d{5}",
        "KG" => "\d{6}",
        "KH" => "\d{5}",
        "KR" => "\d{3}[\-]\d{3}",
        "KW" => "\d{5}",
        "KZ" => "\d{6}",
        "LA" => "\d{5}",
        "LB" => "(\d{4}([ ]?\d{4})?)?",
        "LI" => "(948[5-9])|(949[0-7])",
        "LK" => "\d{5}",
        "LR" => "\d{4}",
        "LS" => "\d{3}",
        "LT" => "\d{5}",
        "LU" => "\d{4}",
        "LV" => "\d{4}",
        "MA" => "\d{5}",
        "MC" => "980\d{2}",
        "MD" => "\d{4}",
        "ME" => "8\d{4}",
        "MG" => "\d{3}",
        "MH" => "969[67]\d([ \-]\d{4})?",
        "MK" => "\d{4}",
        "MN" => "\d{6}",
        "MP" => "9695[012]([ \-]\d{4})?",
        "MQ" => "9[78]2\d{2}",
        "MT" => "[A-Z]{3}[ ]?\d{2,4}",
        "MU" => "(\d{3}[A-Z]{2}\d{3})?",
        "MV" => "\d{5}",
        "MX" => "\d{5}",
        "MY" => "\d{5}",
        "NC" => "988\d{2}",
        "NE" => "\d{4}",
        "NF" => "2899",
        "NG" => "(\d{6})?",
        "NI" => "((\d{4}-)?\d{3}-\d{3}(-\d{1})?)?",
        "NL" => "^[1-9][0-9]{3}\s?([a-zA-Z]{2})?$",
        "NO" => "\d{4}",
        "NP" => "\d{5}",
        "NZ" => "\d{4}",
        "OM" => "(PC )?\d{3}",
        "PF" => "987\d{2}",
        "PG" => "\d{3}",
        "PH" => "\d{4}",
        "PK" => "\d{5}",
        "PL" => "\d{2}-\d{3}",
        "PM" => "9[78]5\d{2}",
        "PN" => "PCRN 1ZZ",
        "PR" => "00[679]\d{2}([ \-]\d{4})?",
        "PT" => "\d{4}([\-]\d{3})?",
        "PW" => "96940",
        "PY" => "\d{4}",
        "RE" => "9[78]4\d{2}",
        "RO" => "\d{6}",
        "RS" => "\d{5}",
        "RU" => "\d{6}",
        "SA" => "\d{5}",
        "SE" => "^(s-|S-){0,1}[0-9]{3}\s?[0-9]{2}$",
        "SG" => "\d{6}",
        "SH" => "(ASCN|STHL) 1ZZ",
        "SI" => "\d{4}",
        "SJ" => "\d{4}",
        "SK" => "\d{3}[ ]?\d{2}",
        "SM" => "4789\d",
        "SN" => "\d{5}",
        "SO" => "\d{5}",
        "SZ" => "[HLMS]\d{3}",
        "TC" => "TKCA 1ZZ",
        "TH" => "\d{5}",
        "TJ" => "\d{6}",
        "TM" => "\d{6}",
        "TN" => "\d{4}",
        "TR" => "\d{5}",
        "TW" => "\d{3}(\d{2})?",
        "UA" => "\d{5}",
        "UK" => "^(GIR|[A-Z]\d[A-Z\d]??|[A-Z]{2}\d[A-Z\d]??)[ ]??(\d[A-Z]{2})$",
        "US" => "^\d{5}([\-]?\d{4})?$",
        "UY" => "\d{5}",
        "UZ" => "\d{6}",
        "VA" => "00120",
        "VE" => "\d{4}",
        "VI" => "008(([0-4]\d)|(5[01]))([ \-]\d{4})?",
        "WF" => "986\d{2}",
        "YT" => "976\d{2}",
        "YU" => "\d{5}",
        "ZA" => "\d{4}",
        "ZM" => "\d{5}"
    );

    if ( ! isset ( $zip_regex[ $country_code ] ) || preg_match( "/" . $zip_regex[ $country_code ] . "/i", $zip ) )
        $ret = true;

    return apply_filters( 'wpinv_is_zip_valid', $ret, $zip, $country_code );
}

function wpinv_checkout_validate_agree_to_terms() {
    // Validate agree to terms
    if ( ! isset( $_POST['wpi_agree_to_terms'] ) || $_POST['wpi_agree_to_terms'] != 1 ) {
        // User did not agree
        wpinv_set_error( 'agree_to_terms', apply_filters( 'wpinv_agree_to_terms_text', __( 'You must agree to the terms of use', 'invoicing' ) ) );
    }
}

function wpinv_checkout_validate_invoice_user() {
    global $wpi_cart;
    
    $valid_user_data = array(
        'user_id' => -1
    );
    
    // Verify there is a user_ID
    if ( $user_ID = (int)$wpi_cart->get_user_id() ) {
        // Get the logged in user data
        $user_data = get_userdata( $user_ID );
        $required_fields  = wpinv_checkout_required_fields();

        // Loop through required fields and show error messages
         if ( !empty( $required_fields ) ) {
            foreach ( $required_fields as $field_name => $value ) {
                if ( in_array( $value, $required_fields ) && empty( $_POST[ 'wpinv_' . $field_name ] ) ) {
                    wpinv_set_error( $value['error_id'], $value['error_message'] );
                }
            }
        }

        // Verify data
        if ( $user_data ) {
            // Collected logged in user data
            $valid_user_data = array(
                'user_id'     => $user_ID,
                'email'       => isset( $_POST['wpinv_email'] ) ? sanitize_email( $_POST['wpinv_email'] ) : $user_data->user_email,
                'first_name'  => isset( $_POST['wpinv_first_name'] ) && ! empty( $_POST['wpinv_first_name'] ) ? sanitize_text_field( $_POST['wpinv_first_name'] ) : $user_data->first_name,
                'last_name'   => isset( $_POST['wpinv_last_name'] ) && ! empty( $_POST['wpinv_last_name']  ) ? sanitize_text_field( $_POST['wpinv_last_name']  ) : $user_data->last_name,
            );

            if ( !empty( $_POST[ 'wpinv_email' ] ) && !is_email( $_POST[ 'wpinv_email' ] ) ) {
                wpinv_set_error( 'invalid_email', __( 'Please enter a valid email address', 'invoicing' ) );
            }
        } else {
            // Set invalid user error
            wpinv_set_error( 'invalid_user', __( 'The user billing information is invalid', 'invoicing' ) );
        }
    } else {
        // Set invalid user error
        wpinv_set_error( 'invalid_user_id', __( 'The invalid invoice user id', 'invoicing' ) );
    }

    // Return user data
    return $valid_user_data;
}

function wpinv_checkout_validate_current_user() {
    global $wpi_cart;

    $data = array();
    
    if ( is_user_logged_in() ) {
        if ( !wpinv_require_login_to_checkout() || ( wpinv_require_login_to_checkout() && (int)$wpi_cart->get_user_id() === (int)get_current_user_id() ) ) {
            $data['user_id'] = (int)get_current_user_id();
        } else {
            wpinv_set_error( 'logged_in_only', __( 'You are not allowed to pay for this invoice', 'invoicing' ) );
        }
    } else {
        // If guest checkout allowed
        if ( !wpinv_require_login_to_checkout() ) {
            $data['user_id'] = 0;
        } else {
            wpinv_set_error( 'logged_in_only', __( 'You must be logged in to pay for this invoice', 'invoicing' ) );
        }
    }

    return $data;
}

function wpinv_checkout_form_get_user( $valid_data = array() ) {
    // Initialize user
    $user    = false;
    $is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

    if ( empty( $valid_data['current_user'] ) ) {
        $user = false;
    } else {
        // Set the valid invoice user
        $user = $valid_data['invoice_user'];
    }

    // Verify invoice have an user
    if ( false === $user || empty( $user ) ) {
        return false;
    }

    $address_fields = array(
        'first_name',
        'last_name',
        'company',
        'vat_number',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip',
    );
    
    foreach ( $address_fields as $field ) {
        $user[$field]  = !empty( $_POST['wpinv_' . $field] ) ? sanitize_text_field( $_POST['wpinv_' . $field] ) : false;
        
        if ( !empty( $user['user_id'] ) && $valid_data['current_user'] == $user['user_id'] ) {
            update_user_meta( $user['user_id'], '_wpinv_' . $field, $user[$field] );
        }
    }

    // Return valid user
    return $user;
}

function wpinv_set_checkout_session( $invoice_data = array() ) {
    global $wpi_session;
    
    return $wpi_session->set( 'wpinv_checkout', $invoice_data );
}

function wpinv_get_checkout_session() {
	global $wpi_session;
    
    return $wpi_session->get( 'wpinv_checkout' );
}

function wpinv_empty_cart() {
    global $wpi_session;

    // Remove cart contents
    $wpi_session->set( 'wpinv_checkout', NULL );

    // Remove all cart fees
    $wpi_session->set( 'wpi_cart_fees', NULL );

    do_action( 'wpinv_empty_cart' );
}

function wpinv_process_checkout() {
    global $wpinv_euvat, $wpi_checkout_id, $wpi_cart;
    
    wpinv_clear_errors();
    
    $invoice = wpinv_get_invoice_cart();
    $wpi_cart = $invoice;
    
    $wpi_checkout_id = $invoice->ID;
    
    do_action( 'wpinv_pre_process_checkout' );
    
    if ( !wpinv_get_cart_contents() ) { // Make sure the cart isn't empty
        $valid_data = false;
        wpinv_set_error( 'empty_cart', __( 'Your cart is empty', 'invoicing' ) );
    } else {
        // Validate the form $_POST data
        $valid_data = wpinv_validate_checkout_fields();
        
        // Allow themes and plugins to hook to errors
        do_action( 'wpinv_checkout_error_checks', $valid_data, $_POST );
    }
    
    $is_ajax    = defined( 'DOING_AJAX' ) && DOING_AJAX;
    
    // Validate the user
    $user = wpinv_checkout_form_get_user( $valid_data );

    // Let extensions validate fields after user is logged in if user has used login/registration form
    do_action( 'wpinv_checkout_user_error_checks', $user, $valid_data, $_POST );
    
    if ( false === $valid_data || wpinv_get_errors() || ! $user ) {
        if ( $is_ajax ) {
            do_action( 'wpinv_ajax_checkout_errors' );
            die();
        } else {
            return false;
        }
    }

    if ( $is_ajax ) {
        // Save address fields.
        $address_fields = array( 'first_name', 'last_name', 'phone', 'address', 'city', 'country', 'state', 'zip', 'company' );
        foreach ( $address_fields as $field ) {
            if ( isset( $user[$field] ) ) {
                $invoice->set( $field, $user[$field] );
            }
            
            $invoice->save();
        }

        $response['success']            = true;
        $response['data']['subtotal']   = $invoice->get_subtotal();
        $response['data']['subtotalf']  = $invoice->get_subtotal( true );
        $response['data']['discount']   = $invoice->get_discount();
        $response['data']['discountf']  = $invoice->get_discount( true );
        $response['data']['tax']        = $invoice->get_tax();
        $response['data']['taxf']       = $invoice->get_tax( true );
        $response['data']['total']      = $invoice->get_total();
        $response['data']['totalf']     = $invoice->get_total( true );
        
        wp_send_json( $response );
    }
    
    $user_info = array(
        'user_id'        => $user['user_id'],
        'first_name'     => $user['first_name'],
        'last_name'      => $user['last_name'],
        'email'          => $user['email'],
        'company'        => $user['company'],
        'phone'          => $user['phone'],
        'address'        => $user['address'],
        'city'           => $user['city'],
        'country'        => $user['country'],
        'state'          => $user['state'],
        'zip'            => $user['zip'],
    );
    
    $cart_items = wpinv_get_cart_contents();
    $discounts  = wpinv_get_cart_discounts();
    
    // Setup invoice information
    $invoice_data = array(
        'invoice_id'        => !empty( $invoice ) ? $invoice->ID : 0,
        'items'             => $cart_items,
        'cart_discounts'    => $discounts,
        'fees'              => wpinv_get_cart_fees(),        // Any arbitrary fees that have been added to the cart
        'subtotal'          => wpinv_get_cart_subtotal( $cart_items ),    // Amount before taxes and discounts
        'discount'          => wpinv_get_cart_items_discount_amount( $cart_items, $discounts ), // Discounted amount
        'tax'               => wpinv_get_cart_tax( $cart_items ),               // Taxed amount
        'price'             => wpinv_get_cart_total( $cart_items, $discounts ),    // Amount after taxes
        'invoice_key'       => $invoice->get_key() ? $invoice->get_key() : $invoice->generate_key(),
        'user_email'        => $user['email'],
        'date'              => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
        'user_info'         => stripslashes_deep( $user_info ),
        'post_data'         => $_POST,
        'cart_details'      => $cart_items,
        'gateway'           => $valid_data['gateway'],
        'card_info'         => $valid_data['cc_info']
    );
    
    $vat_info   = $wpinv_euvat->current_vat_data();
    if ( is_array( $vat_info ) ) {
        $invoice_data['user_info']['vat_number']        = $vat_info['number'];
        $invoice_data['user_info']['vat_rate']          = wpinv_get_tax_rate($invoice_data['user_info']['country'], $invoice_data['user_info']['state']);
        $invoice_data['user_info']['adddress_confirmed']    = isset($vat_info['adddress_confirmed']) ? $vat_info['adddress_confirmed'] : false;

        // Add the VAT rate to each item in the cart
        foreach( $invoice_data['cart_details'] as $key => $item_data) {
            $rate = wpinv_get_tax_rate($invoice_data['user_info']['country'], $invoice_data['user_info']['state'], $item_data['id']);
            $invoice_data['cart_details'][$key]['vat_rate'] = wpinv_round_amount( $rate, 4 );
        }
    }
    
    // Save vat fields.
    $address_fields = array( 'vat_number', 'vat_rate', 'adddress_confirmed' );
    foreach ( $address_fields as $field ) {
        if ( isset( $invoice_data['user_info'][$field] ) ) {
            $invoice->set( $field, $invoice_data['user_info'][$field] );
        }
        
        $invoice->save();
    }

    // Add the user data for hooks
    $valid_data['user'] = $user;
    
    // Allow themes and plugins to hook before the gateway
    do_action( 'wpinv_checkout_before_gateway', $_POST, $user_info, $valid_data );
    
    // If the total amount in the cart is 0, send to the manual gateway. This emulates a free invoice
    if ( !$invoice_data['price'] ) {
        // Revert to manual
        $invoice_data['gateway'] = 'manual';
        $_POST['wpi-gateway'] = 'manual';
    }
    
    // Allow the invoice data to be modified before it is sent to the gateway
    $invoice_data = apply_filters( 'wpinv_data_before_gateway', $invoice_data, $valid_data );
    
    // Setup the data we're storing in the purchase session
    $session_data = $invoice_data;
    // Make sure credit card numbers are never stored in sessions
    if ( !empty( $session_data['card_info']['card_number'] ) ) {
        unset( $session_data['card_info']['card_number'] );
    }
    
    // Used for showing item links to non logged-in users after purchase, and for other plugins needing purchase data.
    wpinv_set_checkout_session( $invoice_data );
    
    // Set gateway
    $invoice->update_meta( '_wpinv_gateway', $invoice_data['gateway'] );
    $invoice->update_meta( '_wpinv_mode', ( wpinv_is_test_mode( $invoice_data['gateway'] ) ? 'test' : 'live' ) );
    $invoice->update_meta( '_wpinv_checkout', true );
    
    do_action( 'wpinv_checkout_before_send_to_gateway', $invoice, $invoice_data );

    // Send info to the gateway for payment processing
    wpinv_send_to_gateway( $invoice_data['gateway'], $invoice_data );
    die();
}
add_action( 'wpinv_payment', 'wpinv_process_checkout' );

function wpinv_get_invoices( $args ) {
    $args = wp_parse_args( $args, array(
        'status'   => array_keys( wpinv_get_invoice_statuses() ),
        'type'     => 'wpi_invoice',
        'parent'   => null,
        'user'     => null,
        'email'    => '',
        'limit'    => get_option( 'posts_per_page' ),
        'offset'   => null,
        'page'     => 1,
        'exclude'  => array(),
        'orderby'  => 'date',
        'order'    => 'DESC',
        'return'   => 'objects',
        'paginate' => false,
    ) );
    
    // Handle some BW compatibility arg names where wp_query args differ in naming.
    $map_legacy = array(
        'numberposts'    => 'limit',
        'post_type'      => 'type',
        'post_status'    => 'status',
        'post_parent'    => 'parent',
        'author'         => 'user',
        'posts_per_page' => 'limit',
        'paged'          => 'page',
    );

    foreach ( $map_legacy as $from => $to ) {
        if ( isset( $args[ $from ] ) ) {
            $args[ $to ] = $args[ $from ];
        }
    }

    if ( get_query_var( 'paged' ) )
        $args['page'] = get_query_var('paged');
    else if ( get_query_var( 'page' ) )
        $args['page'] = get_query_var( 'page' );
    else if ( !empty( $args[ 'page' ] ) )
        $args['page'] = $args[ 'page' ];
    else
        $args['page'] = 1;

    /**
     * Generate WP_Query args. This logic will change if orders are moved to
     * custom tables in the future.
     */
    $wp_query_args = array(
        'post_type'      => 'wpi_invoice',
        'post_status'    => $args['status'],
        'posts_per_page' => $args['limit'],
        'meta_query'     => array(),
        'date_query'     => !empty( $args['date_query'] ) ? $args['date_query'] : array(),
        'fields'         => 'ids',
        'orderby'        => $args['orderby'],
        'order'          => $args['order'],
    );
    
    if ( !empty( $args['user'] ) ) {
        $wp_query_args['author'] = absint( $args['user'] );
    }

    if ( ! is_null( $args['parent'] ) ) {
        $wp_query_args['post_parent'] = absint( $args['parent'] );
    }

    if ( ! is_null( $args['offset'] ) ) {
        $wp_query_args['offset'] = absint( $args['offset'] );
    } else {
        $wp_query_args['paged'] = absint( $args['page'] );
    }

    if ( ! empty( $args['exclude'] ) ) {
        $wp_query_args['post__not_in'] = array_map( 'absint', $args['exclude'] );
    }

    if ( ! $args['paginate' ] ) {
        $wp_query_args['no_found_rows'] = true;
    }

    // Get results.
    $invoices = new WP_Query( $wp_query_args );

    if ( 'objects' === $args['return'] ) {
        $return = array_map( 'wpinv_get_invoice', $invoices->posts );
    } elseif ( 'self' === $args['return'] ) {
        return $invoices;
    } else {
        $return = $invoices->posts;
    }

    if ( $args['paginate' ] ) {
        return (object) array(
            'invoices'      => $return,
            'total'         => $invoices->found_posts,
            'max_num_pages' => $invoices->max_num_pages,
        );
    } else {
        return $return;
    }
}

function wpinv_get_user_invoices_columns() {
    $columns = array(
            'invoice-number'  => array( 'title' => __( 'ID', 'invoicing' ), 'class' => 'text-left' ),
            'invoice-date'    => array( 'title' => __( 'Date', 'invoicing' ), 'class' => 'text-left' ),
            'invoice-status'  => array( 'title' => __( 'Status', 'invoicing' ), 'class' => 'text-center' ),
            'invoice-total'   => array( 'title' => __( 'Total', 'invoicing' ), 'class' => 'text-right' ),
            'invoice-actions' => array( 'title' => '&nbsp;', 'class' => 'text-center' ),
        );

    return apply_filters( 'wpinv_user_invoices_columns', $columns );
}

function wpinv_payment_receipt( $atts, $content = null ) {
    global $wpinv_receipt_args;

    $wpinv_receipt_args = shortcode_atts( array(
        'error'           => __( 'Sorry, trouble retrieving payment receipt.', 'invoicing' ),
        'price'           => true,
        'discount'        => true,
        'items'           => true,
        'date'            => true,
        'notes'           => true,
        'invoice_key'     => false,
        'payment_method'  => true,
        'invoice_id'      => true
    ), $atts, 'wpinv_receipt' );

    $session = wpinv_get_checkout_session();
    if ( isset( $_GET['invoice_key'] ) ) {
        $invoice_key = urldecode( $_GET['invoice_key'] );
    } else if ( $session && isset( $session['invoice_key'] ) ) {
        $invoice_key = $session['invoice_key'];
    } elseif ( isset( $wpinv_receipt_args['invoice_key'] ) && $wpinv_receipt_args['invoice_key'] ) {
        $invoice_key = $wpinv_receipt_args['invoice_key'];
    } else if ( isset( $_GET['invoice-id'] ) ) {
        $invoice_key = wpinv_get_payment_key( (int)$_GET['invoice-id'] );
    }

    // No key found
    if ( ! isset( $invoice_key ) ) {
        return '<p class="alert alert-error">' . $wpinv_receipt_args['error'] . '</p>';
    }

    $invoice_id    = wpinv_get_invoice_id_by_key( $invoice_key );
    $user_can_view = wpinv_can_view_receipt( $invoice_key );
    if ( $user_can_view && isset( $_GET['invoice-id'] ) ) {
        $invoice_id     = (int)$_GET['invoice-id'];
        $user_can_view  = $invoice_key == wpinv_get_payment_key( (int)$_GET['invoice-id'] ) ? true : false;
    }

    // Key was provided, but user is logged out. Offer them the ability to login and view the receipt
    if ( ! $user_can_view && ! empty( $invoice_key ) && ! is_user_logged_in() ) {
        // login redirect
        return '<p class="alert alert-error">' . __( 'You are not allowed to access this section', 'invoicing' ) . '</p>';
    }

    if ( ! apply_filters( 'wpinv_user_can_view_receipt', $user_can_view, $wpinv_receipt_args ) ) {
        return '<p class="alert alert-error">' . $wpinv_receipt_args['error'] . '</p>';
    }

    ob_start();

    wpinv_get_template_part( 'wpinv-invoice-receipt' );

    $display = ob_get_clean();

    return $display;
}

function wpinv_get_invoice_id_by_key( $key ) {
	global $wpdb;

	$invoice_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpinv_key' AND meta_value = %s LIMIT 1", $key ) );

	if ( $invoice_id != NULL )
		return $invoice_id;

	return 0;
}

function wpinv_can_view_receipt( $invoice_key = '' ) {
	$return = false;

	if ( empty( $invoice_key ) ) {
		return $return;
	}

	global $wpinv_receipt_args;

	$wpinv_receipt_args['id'] = wpinv_get_invoice_id_by_key( $invoice_key );
	if ( isset( $_GET['invoice-id'] ) ) {
		$wpinv_receipt_args['id'] = $invoice_key == wpinv_get_payment_key( (int)$_GET['invoice-id'] ) ? (int)$_GET['invoice-id'] : 0;
	}

	if ( empty( $wpinv_receipt_args['id'] ) ) {
		return $return;
	}

	$invoice = wpinv_get_invoice( $wpinv_receipt_args['id'] );
	if ( !( !empty( $invoice->ID ) && $invoice->get_key() === $invoice_key ) ) {
		return $return;
	}

	if ( is_user_logged_in() ) {
		if ( (int)$invoice->get_user_id() === (int) get_current_user_id() ) {
			$return = true;
		}
	}

	$session = wpinv_get_checkout_session();
	if ( isset( $_GET['invoice_key'] ) || ( $session && isset( $session['invoice_key'] ) ) ) {
		$check_key = isset( $_GET['invoice_key'] ) ? $_GET['invoice_key'] : $session['invoice_key'];

		if ( wpinv_require_login_to_checkout() ) {
			$return = $return && $check_key === $invoice_key;
		} else {
			$return = $check_key === $invoice_key;
		}
	}

	return (bool) apply_filters( 'wpinv_can_view_receipt', $return, $invoice_key );
}

function wpinv_pay_for_invoice() {
    global $wpinv_euvat;
    
    if ( isset( $_GET['invoice_key'] ) ) {
        $checkout_uri   = wpinv_get_checkout_uri();
        $invoice_key    = sanitize_text_field( $_GET['invoice_key'] );
        
        if ( empty( $invoice_key ) ) {
            wpinv_set_error( 'invalid_invoice', __( 'Invoice not found', 'invoicing' ) );
            wp_redirect( $checkout_uri );
            wpinv_die();
        }
        
        do_action( 'wpinv_check_pay_for_invoice', $invoice_key );

        $invoice_id    = wpinv_get_invoice_id_by_key( $invoice_key );
        $user_can_view = wpinv_can_view_receipt( $invoice_key );
        if ( $user_can_view && isset( $_GET['invoice-id'] ) ) {
            $invoice_id     = (int)$_GET['invoice-id'];
            $user_can_view  = $invoice_key == wpinv_get_payment_key( (int)$_GET['invoice-id'] ) ? true : false;
        }
        
        if ( $invoice_id && $user_can_view && ( $invoice = wpinv_get_invoice( $invoice_id ) ) ) {
            if ( $invoice->needs_payment() ) {
                $data                   = array();
                $data['invoice_id']     = $invoice_id;
                $data['cart_discounts'] = $invoice->get_discounts( true );
                
                wpinv_set_checkout_session( $data );
                
                if ( wpinv_get_option( 'vat_ip_country_default' ) ) {
                    $_POST['country']   = $wpinv_euvat->get_country_by_ip();
                    $_POST['state']     = $_POST['country'] == $invoice->country ? $invoice->state : '';
                    
                    wpinv_recalculate_tax( true );
                }
                
            } else {
                $checkout_uri = $invoice->get_view_url();
            }
        } else {
            wpinv_set_error( 'invalid_invoice', __( 'You are not allowed to view this invoice', 'invoicing' ) );
            
            $checkout_uri = is_user_logged_in() ? wpinv_get_history_page_uri() : wp_login_url( get_permalink() );
        }
        
        wp_redirect( $checkout_uri );
        wpinv_die();
    }
}
add_action( 'wpinv_pay_for_invoice', 'wpinv_pay_for_invoice' );

function wpinv_handle_pay_via_invoice_link( $invoice_key ) {
    if ( !empty( $invoice_key ) && !empty( $_REQUEST['_wpipay'] ) && !is_user_logged_in() && $invoice_id = wpinv_get_invoice_id_by_key( $invoice_key ) ) {
        if ( $invoice = wpinv_get_invoice( $invoice_id ) ) {
            $user_id = $invoice->get_user_id();
            $secret = sanitize_text_field( $_GET['_wpipay'] );
            
            if ( $secret === md5( $user_id . '::' . $invoice->get_email() . '::' . $invoice_key ) ) { // valid invoice link
                $redirect_to = remove_query_arg( '_wpipay', get_permalink() );
                
                wpinv_guest_redirect( $redirect_to, $user_id );
                wpinv_die();
            }
        }
    }
}
add_action( 'wpinv_check_pay_for_invoice', 'wpinv_handle_pay_via_invoice_link' );

function wpinv_set_payment_transaction_id( $invoice_id = 0, $transaction_id = '' ) {
    $invoice_id = is_object( $invoice_id ) && !empty( $invoice_id->ID ) ? $invoice_id : $invoice_id;
    
    if ( empty( $invoice_id ) && $invoice_id > 0 ) {
        return false;
    }
    
    if ( empty( $transaction_id ) ) {
        $transaction_id = $invoice_id;
    }

    $transaction_id = apply_filters( 'wpinv_set_payment_transaction_id', $transaction_id, $invoice_id );
    
    return wpinv_update_invoice_meta( $invoice_id, '_wpinv_transaction_id', $transaction_id );
}

function wpinv_invoice_status_label( $status, $status_display = '' ) {
    if ( empty( $status_display ) ) {
        $status_display = wpinv_status_nicename( $status );
    }
    
    switch ( $status ) {
        case 'publish' :
        case 'wpi-renewal' :
            $class = 'label-success';
        break;
        case 'wpi-pending' :
            $class = 'label-primary';
        break;
        case 'wpi-processing' :
            $class = 'label-warning';
        break;
        case 'wpi-onhold' :
            $class = 'label-info';
        break;
        case 'wpi-cancelled' :
        case 'wpi-failed' :
            $class = 'label-danger';
        break;
        default:
            $class = 'label-default';
        break;
    }
    
    $label = '<span class="label label-inv-' . $status . ' ' . $class . '">' . $status_display . '</span>';
    
    return apply_filters( 'wpinv_invoice_status_label', $label, $status, $status_display );
}

function wpinv_format_invoice_number( $number ) {
    $padd  = wpinv_get_option( 'invoice_number_padd' );
    
    // TODO maintain old invoice numbers if invoice number settings not saved. Should be removed before stable release.
    if ( $padd === '' || $padd === false || $padd === NULL ) {
        return wp_sprintf( __( 'WPINV-%d', 'invoicing' ), $number );
    }
    
    $prefix  = wpinv_get_option( 'invoice_number_prefix' );
    $postfix = wpinv_get_option( 'invoice_number_postfix' );
    
    $padd = absint( $padd );
    $formatted_number = absint( $number );
    
    if ( $padd > 0 ) {
        $formatted_number = zeroise( $formatted_number, $padd );
    }    

    $formatted_number = $prefix . $formatted_number . $postfix;

    return apply_filters( 'wpinv_format_invoice_number', $formatted_number, $number, $prefix, $postfix, $padd );
}

function wpinv_get_next_invoice_number() {
    if ( ! wpinv_get_option( 'sequential_invoice_number' ) ) {
        return false;
    }

    $number           = get_option( 'wpinv_last_invoice_number' );
    $start            = wpinv_get_option( 'invoice_sequence_start', 1 );
    $increment_number = true;

    if ( false !== $number ) {
        if ( empty( $number ) ) {
            $number = $start;
            $increment_number = false;
        }
    } else {
        $last_invoice = wpinv_get_invoices( array( 'limit' => 1, 'order' => 'DESC', 'orderby' => 'ID', 'return' => 'posts', 'fields' => 'ids', 'status' => 'any' ) );

        if ( ! empty( $last_invoice[0] ) ) {
            $number = wpinv_get_invoice_number( $last_invoice[0] );
        }

        if ( ! empty( $number ) && ! empty( $last_invoice[0] ) && $number !== (int) $last_invoice[0] ) {
            $number = wpinv_clean_invoice_number( $number );
        } else {
            $number = $start;
            $increment_number = false;
        }
    }

    $increment_number = apply_filters( 'wpinv_increment_payment_number', $increment_number, $number );

    if ( $increment_number ) {
        $number++;
    }

    return apply_filters( 'wpinv_get_next_invoice_number', $number );
}

function wpinv_clean_invoice_number( $number ) {
    $prefix  = wpinv_get_option( 'invoice_number_prefix' );
    $postfix = wpinv_get_option( 'invoice_number_postfix' );

    $number = preg_replace( '/' . $prefix . '/', '', $number, 1 );

    $length      = strlen( $number );
    $postfix_pos = strrpos( $number, $postfix );
    
    if ( false !== $postfix_pos ) {
        $number      = substr_replace( $number, '', $postfix_pos, $length );
    }

    $number = intval( $number );

    return apply_filters( 'wpinv_clean_invoice_number', $number, $prefix, $postfix );
}

function wpinv_save_number_post_saved( $post_ID, $post, $update ) {
    global $wpdb;

    if ( !$update && !get_post_meta( $post_ID, '_wpinv_number', true ) ) {
        wpinv_update_invoice_number( $post_ID, $post->post_status != 'auto-draft' );
    }

    if ( !$update ) {
        $wpdb->update( $wpdb->posts, array( 'post_name' => 'inv-' . $post_ID ), array( 'ID' => $post_ID ) );
        clean_post_cache( $post_ID );
    }
}
add_action( 'save_post_wpi_invoice', 'wpinv_save_number_post_saved', 1, 3 );

function wpinv_save_number_post_updated( $post_ID, $post_after, $post_before ) {
    if ( !empty( $post_after->post_type ) && $post_after->post_type == 'wpi_invoice' && $post_before->post_status == 'auto-draft' && $post_after->post_status != $post_before->post_status ) {
        wpinv_update_invoice_number( $post_ID, true );
    }
}
add_action( 'post_updated', 'wpinv_save_number_post_updated', 1, 3 );

function wpinv_update_invoice_number( $post_ID, $save_sequential = false ) {
    global $wpdb;

    if ( wpinv_get_option( 'sequential_invoice_number' ) ) {
        $number = wpinv_get_next_invoice_number();

        if ( $save_sequential ) {
            update_option( 'wpinv_last_invoice_number', $number );
        }
    } else {
        $number = $post_ID;
    }

    $number = wpinv_format_invoice_number( $number );

    update_post_meta( $post_ID, '_wpinv_number', $number );

    $wpdb->update( $wpdb->posts, array( 'post_title' => $number ), array( 'ID' => $post_ID ) );

    clean_post_cache( $post_ID );

    return $number;
}