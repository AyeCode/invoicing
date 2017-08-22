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

final class WPInv_Invoice {
    public $ID  = 0;
    public $title;
    public $post_type;
    
    public $pending;
    public $items = array();
    public $user_info = array();
    public $payment_meta = array();
    
    public $new = false;
    public $number = '';
    public $mode = 'live';
    public $key = '';
    public $total = 0.00;
    public $subtotal = 0;
    public $tax = 0;
    public $fees = array();
    public $fees_total = 0;
    public $discounts = '';
    public $discount = 0;
    public $discount_code = 0;
    public $date = '';
    public $due_date = '';
    public $completed_date = '';
    public $status      = 'wpi-pending';
    public $post_status = 'wpi-pending';
    public $old_status = '';
    public $status_nicename = '';
    public $user_id = 0;
    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $city = '';
    public $country = '';
    public $state = '';
    public $zip = '';
    public $transaction_id = '';
    public $ip = '';
    public $gateway = '';
    public $gateway_title = '';
    public $currency = '';
    public $cart_details = array();
    
    public $company = '';
    public $vat_number = '';
    public $vat_rate = '';
    public $adddress_confirmed = '';
    
    public $full_name = '';
    public $parent_invoice = 0;
    
    public function __construct( $invoice_id = false ) {
        if( empty( $invoice_id ) ) {
            return false;
        }

        $this->setup_invoice( $invoice_id );
    }

    public function get( $key ) {
        if ( method_exists( $this, 'get_' . $key ) ) {
            $value = call_user_func( array( $this, 'get_' . $key ) );
        } else {
            $value = $this->$key;
        }

        return $value;
    }

    public function set( $key, $value ) {
        $ignore = array( 'items', 'cart_details', 'fees', '_ID' );

        if ( $key === 'status' ) {
            $this->old_status = $this->status;
        }

        if ( ! in_array( $key, $ignore ) ) {
            $this->pending[ $key ] = $value;
        }

        if( '_ID' !== $key ) {
            $this->$key = $value;
        }
    }

    public function _isset( $name ) {
        if ( property_exists( $this, $name) ) {
            return false === empty( $this->$name );
        } else {
            return null;
        }
    }

    private function setup_invoice( $invoice_id ) {
        $this->pending = array();

        if ( empty( $invoice_id ) ) {
            return false;
        }

        $invoice = get_post( $invoice_id );

        if( !$invoice || is_wp_error( $invoice ) ) {
            return false;
        }

        if( !('wpi_invoice' == $invoice->post_type OR 'wpi_quote' == $invoice->post_type) ) {
            return false;
        }

        do_action( 'wpinv_pre_setup_invoice', $this, $invoice_id );
        
        // Primary Identifier
        $this->ID              = absint( $invoice_id );
        $this->post_type       = $invoice->post_type;
        
        // We have a payment, get the generic payment_meta item to reduce calls to it
        $this->payment_meta    = $this->get_meta();
        $this->date            = $invoice->post_date;
        $this->due_date        = $this->setup_due_date();
        $this->completed_date  = $this->setup_completed_date();
        $this->status          = $invoice->post_status;
        $this->post_status     = $this->status;
        $this->mode            = $this->setup_mode();
        $this->parent_invoice  = $invoice->post_parent;
        $this->post_name       = $this->setup_post_name( $invoice );
        $this->status_nicename = $this->setup_status_nicename($invoice->post_status);

        // Items
        $this->fees            = $this->setup_fees();
        $this->cart_details    = $this->setup_cart_details();
        $this->items           = $this->setup_items();

        // Currency Based
        $this->total           = $this->setup_total();
        $this->tax             = $this->setup_tax();
        $this->fees_total      = $this->get_fees_total();
        $this->subtotal        = $this->setup_subtotal();
        $this->currency        = $this->setup_currency();
        
        // Gateway based
        $this->gateway         = $this->setup_gateway();
        $this->gateway_title   = $this->setup_gateway_title();
        $this->transaction_id  = $this->setup_transaction_id();
        
        // User based
        $this->ip              = $this->setup_ip();
        $this->user_id         = !empty( $invoice->post_author ) ? $invoice->post_author : get_current_user_id();///$this->setup_user_id();
        $this->email           = get_the_author_meta( 'email', $this->user_id );
        
        $this->user_info       = $this->setup_user_info();
                
        $this->first_name      = $this->user_info['first_name'];
        $this->last_name       = $this->user_info['last_name'];
        $this->company         = $this->user_info['company'];
        $this->vat_number      = $this->user_info['vat_number'];
        $this->vat_rate        = $this->user_info['vat_rate'];
        $this->adddress_confirmed  = $this->user_info['adddress_confirmed'];
        $this->address         = $this->user_info['address'];
        $this->city            = $this->user_info['city'];
        $this->country         = $this->user_info['country'];
        $this->state           = $this->user_info['state'];
        $this->zip             = $this->user_info['zip'];
        $this->phone           = $this->user_info['phone'];
        
        $this->discounts       = $this->user_info['discount'];
            $this->discount        = $this->setup_discount();
            $this->discount_code   = $this->setup_discount_code();

        // Other Identifiers
        $this->key             = $this->setup_invoice_key();
        $this->number          = $this->setup_invoice_number();
        $this->title           = !empty( $invoice->post_title ) ? $invoice->post_title : $this->number;
        
        $this->full_name       = trim( $this->first_name . ' '. $this->last_name );
        
        // Allow extensions to add items to this object via hook
        do_action( 'wpinv_setup_invoice', $this, $invoice_id );

        return true;
    }
    
    private function setup_status_nicename($status) {
        $all_invoice_statuses  = wpinv_get_invoice_statuses();
        $status   = isset( $all_invoice_statuses[$status] ) ? $all_invoice_statuses[$status] : __( $status, 'invoicing' );

        return apply_filters( 'setup_status_nicename', $status );
    }
    
    private function setup_post_name( $post = NULL ) {
        global $wpdb;
        
        $post_name = '';
        
        if ( !empty( $post ) ) {
            if( !empty( $post->post_name ) ) {
                $post_name = $post->post_name;
            } else if ( !empty( $post->ID ) ) {
                $post_name = 'inv-' . $post->ID;

                $wpdb->update( $wpdb->posts, array( 'post_name' => 'inv-' . $post->ID ), array( 'ID' => $post->ID ) );
            }
        }

        $this->post_name = $post_name;
    }
    
    private function setup_due_date() {
        $due_date = $this->get_meta( '_wpinv_due_date' );
        
        if ( empty( $due_date ) ) {
            $overdue_time = strtotime( $this->date ) + ( DAY_IN_SECONDS * absint( wpinv_get_option( 'overdue_days' ) ) );
            $due_date = date_i18n( 'Y-m-d', $overdue_time );
        } else if ( $due_date == 'none' ) {
            $due_date = '';
        }
        
        return $due_date;
    }
    
    private function setup_completed_date() {
        $invoice = get_post( $this->ID );

        if ( 'wpi-pending' == $invoice->post_status || 'preapproved' == $invoice->post_status ) {
            return false; // This invoice was never paid
        }

        $date = ( $date = $this->get_meta( '_wpinv_completed_date', true ) ) ? $date : $invoice->modified_date;

        return $date;
    }
    
    private function setup_cart_details() {
        $cart_details = isset( $this->payment_meta['cart_details'] ) ? maybe_unserialize( $this->payment_meta['cart_details'] ) : array();
        return $cart_details;
    }
    
    public function array_convert() {
        return get_object_vars( $this );
    }
    
    private function setup_items() {
        $items = isset( $this->payment_meta['items'] ) ? maybe_unserialize( $this->payment_meta['items'] ) : array();
        return $items;
    }
    
    private function setup_fees() {
        $payment_fees = isset( $this->payment_meta['fees'] ) ? $this->payment_meta['fees'] : array();
        return $payment_fees;
    }
        
    private function setup_currency() {
        $currency = isset( $this->payment_meta['currency'] ) ? $this->payment_meta['currency'] : apply_filters( 'wpinv_currency_default', wpinv_get_currency(), $this );
        return $currency;
    }
    
    private function setup_discount() {
        //$discount = $this->get_meta( '_wpinv_discount', true );
        $discount = (float)$this->subtotal - ( (float)$this->total - (float)$this->tax - (float)$this->fees_total );
        if ( $discount < 0 ) {
            $discount = 0;
        }
        $discount = wpinv_round_amount( $discount );
        
        return $discount;
    }
    
    private function setup_discount_code() {
        $discount_code = !empty( $this->discounts ) ? $this->discounts : $this->get_meta( '_wpinv_discount_code', true );
        return $discount_code;
    }
    
    private function setup_tax() {
        $tax = $this->get_meta( '_wpinv_tax', true );

        // We don't have tax as it's own meta and no meta was passed
        if ( '' === $tax ) {            
            $tax = isset( $this->payment_meta['tax'] ) ? $this->payment_meta['tax'] : 0;
        }

        return $tax;
    }

    private function setup_subtotal() {
        $subtotal     = 0;
        $cart_details = $this->cart_details;

        if ( is_array( $cart_details ) ) {
            foreach ( $cart_details as $item ) {
                if ( isset( $item['subtotal'] ) ) {
                    $subtotal += $item['subtotal'];
                }
            }
        } else {
            $subtotal  = $this->total;
            $tax       = wpinv_use_taxes() ? $this->tax : 0;
            $subtotal -= $tax;
        }

        return $subtotal;
    }
    
    private function setup_discounts() {
        $discounts = ! empty( $this->payment_meta['user_info']['discount'] ) ? $this->payment_meta['user_info']['discount'] : array();
        return $discounts;
    }
    
    private function setup_total() {
        $amount = $this->get_meta( '_wpinv_total', true );

        if ( empty( $amount ) && '0.00' != $amount ) {
            $meta   = $this->get_meta( '_wpinv_payment_meta', true );
            $meta   = maybe_unserialize( $meta );

            if ( isset( $meta['amount'] ) ) {
                $amount = $meta['amount'];
            }
        }

        if($amount < 0){
            $amount = 0;
        }

        return $amount;
    }
    
    private function setup_mode() {
        return $this->get_meta( '_wpinv_mode' );
    }

    private function setup_gateway() {
        $gateway = $this->get_meta( '_wpinv_gateway' );
        
        if ( empty( $gateway ) && 'publish' === $this->status ) {
            $gateway = 'manual';
        }
        
        return $gateway;
    }
    
    private function setup_gateway_title() {
        $gateway_title = wpinv_get_gateway_checkout_label( $this->gateway );
        return $gateway_title;
    }

    private function setup_transaction_id() {
        $transaction_id = $this->get_meta( '_wpinv_transaction_id' );

        if ( empty( $transaction_id ) || (int) $transaction_id === (int) $this->ID ) {
            $gateway        = $this->gateway;
            $transaction_id = apply_filters( 'wpinv_get_invoice_transaction_id-' . $gateway, $this->ID );
        }

        return $transaction_id;
    }

    private function setup_ip() {
        $ip = $this->get_meta( '_wpinv_user_ip' );
        return $ip;
    }

    ///private function setup_user_id() {
        ///$user_id = $this->get_meta( '_wpinv_user_id' );
        ///return $user_id;
    ///}
        
    private function setup_first_name() {
        $first_name = $this->get_meta( '_wpinv_first_name' );
        return $first_name;
    }
    
    private function setup_last_name() {
        $last_name = $this->get_meta( '_wpinv_last_name' );
        return $last_name;
    }
    
    private function setup_company() {
        $company = $this->get_meta( '_wpinv_company' );
        return $company;
    }
    
    private function setup_vat_number() {
        $vat_number = $this->get_meta( '_wpinv_vat_number' );
        return $vat_number;
    }
    
    private function setup_vat_rate() {
        $vat_rate = $this->get_meta( '_wpinv_vat_rate' );
        return $vat_rate;
    }
    
    private function setup_adddress_confirmed() {
        $adddress_confirmed = $this->get_meta( '_wpinv_adddress_confirmed' );
        return $adddress_confirmed;
    }
    
    private function setup_phone() {
        $phone = $this->get_meta( '_wpinv_phone' );
        return $phone;
    }
    
    private function setup_address() {
        $address = $this->get_meta( '_wpinv_address', true );
        return $address;
    }
    
    private function setup_city() {
        $city = $this->get_meta( '_wpinv_city', true );
        return $city;
    }
    
    private function setup_country() {
        $country = $this->get_meta( '_wpinv_country', true );
        return $country;
    }
    
    private function setup_state() {
        $state = $this->get_meta( '_wpinv_state', true );
        return $state;
    }
    
    private function setup_zip() {
        $zip = $this->get_meta( '_wpinv_zip', true );
        return $zip;
    }

    private function setup_user_info() {
        $defaults = array(
            'user_id'        => $this->user_id,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'email'          => get_the_author_meta( 'email', $this->user_id ),
            'phone'          => $this->phone,
            'address'        => $this->address,
            'city'           => $this->city,
            'country'        => $this->country,
            'state'          => $this->state,
            'zip'            => $this->zip,
            'company'        => $this->company,
            'vat_number'     => $this->vat_number,
            'vat_rate'       => $this->vat_rate,
            'adddress_confirmed' => $this->adddress_confirmed,
            'discount'       => $this->discounts,
        );
        
        $user_info = array();
        if ( isset( $this->payment_meta['user_info'] ) ) {
            $user_info = maybe_unserialize( $this->payment_meta['user_info'] );
            
            if ( !empty( $user_info ) && isset( $user_info['user_id'] ) && $post = get_post( $this->ID ) ) {
                $this->user_id = $post->post_author;
                $this->email = get_the_author_meta( 'email', $this->user_id );
                
                $user_info['user_id'] = $this->user_id;
                $user_info['email'] = $this->email;
                $this->payment_meta['user_id'] = $this->user_id;
                $this->payment_meta['email'] = $this->email;
            }
        }
        
        $user_info    = wp_parse_args( $user_info, $defaults );
        
        // Get the user, but only if it's been created
        $user = get_userdata( $this->user_id );
        
        if ( !empty( $user ) && $user->ID > 0 ) {
            if ( empty( $user_info ) ) {
                $user_info = array(
                    'user_id'    => $user->ID,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->user_email,
                    'discount'   => '',
                );
            } else {
                foreach ( $user_info as $key => $value ) {
                    if ( ! empty( $value ) ) {
                        continue;
                    }

                    switch( $key ) {
                        case 'user_id':
                            $user_info[ $key ] = $user->ID;
                            break;
                        case 'first_name':
                            $user_info[ $key ] = $user->first_name;
                            break;
                        case 'last_name':
                            $user_info[ $key ] = $user->last_name;
                            break;
                        case 'email':
                            $user_info[ $key ] = $user->user_email;
                            break;
                    }
                }
            }
        }

        return $user_info;
    }

    private function setup_invoice_key() {
        $key = $this->get_meta( '_wpinv_key', true );
        
        return $key;
    }

    private function setup_invoice_number() {
        $number = $this->get_meta( '_wpinv_number', true );

        if ( !$number ) {
            $number = $this->ID;

            if ( $this->status == 'auto-draft' ) {
                if ( wpinv_get_option( 'sequential_invoice_number' ) ) {
                    $next_number = wpinv_get_next_invoice_number();
                    $number      = $next_number;
                }
            }
            
            $number = wpinv_format_invoice_number( $number );
        }

        return $number;
    }
    
    private function insert_invoice() {
        global $wpdb;

        $invoice_number = $this->ID;
        if ( $number = $this->get_meta( '_wpinv_number', true ) ) {
            $invoice_number = $number;
        }

        if ( empty( $this->key ) ) {
            $this->key = self::generate_key();
            $this->pending['key'] = $this->key;
        }

        if ( empty( $this->ip ) ) {
            $this->ip = wpinv_get_ip();
            $this->pending['ip'] = $this->ip;
        }
        
        $payment_data = array(
            'price'        => $this->total,
            'date'         => $this->date,
            'user_email'   => $this->email,
            'invoice_key'  => $this->key,
            'currency'     => $this->currency,
            'items'        => $this->items,
            'user_info' => array(
                'user_id'    => $this->user_id,
                'email'      => $this->email,
                'first_name' => $this->first_name,
                'last_name'  => $this->last_name,
                'address'    => $this->address,
                'phone'      => $this->phone,
                'city'       => $this->city,
                'country'    => $this->country,
                'state'      => $this->state,
                'zip'        => $this->zip,
                'company'    => $this->company,
                'vat_number' => $this->vat_number,
                'discount'   => $this->discounts,
            ),
            'cart_details' => $this->cart_details,
            'status'       => $this->status,
            'fees'         => $this->fees,
        );

        $post_data = array(
                        'post_title'    => $invoice_number,
                        'post_status'   => $this->status,
                        'post_author'   => $this->user_id,
                        'post_type'     => $this->post_type,
                        'post_date'     => ! empty( $this->date ) && $this->date != '0000-00-00 00:00:00' ? $this->date : current_time( 'mysql' ),
                        'post_date_gmt' => ! empty( $this->date ) && $this->date != '0000-00-00 00:00:00' ? get_gmt_from_date( $this->date ) : current_time( 'mysql', 1 ),
                        'post_parent'   => $this->parent_invoice,
                    );
        $args = apply_filters( 'wpinv_insert_invoice_args', $post_data, $this );

        // Create a blank invoice
        if ( !empty( $this->ID ) ) {
            $args['ID']         = $this->ID;

            $invoice_id = wp_update_post( $args, true );
        } else {
            $invoice_id = wp_insert_post( $args, true );
        }

        if ( is_wp_error( $invoice_id ) ) {
            return false;
        }

        if ( !empty( $invoice_id ) ) {
            $this->ID  = $invoice_id;
            $this->_ID = $invoice_id;

            $this->payment_meta = apply_filters( 'wpinv_payment_meta', $this->payment_meta, $payment_data );
            if ( ! empty( $this->payment_meta['fees'] ) ) {
                $this->fees = array_merge( $this->fees, $this->payment_meta['fees'] );
                foreach( $this->fees as $fee ) {
                    $this->increase_fees( $fee['amount'] );
                }
            }

            $this->update_meta( '_wpinv_payment_meta', $this->payment_meta );            
            $this->new = true;
        }

        return $this->ID;
    }

    public function save( $setup = false ) {
        global $wpi_session;
        
        $saved = false;
        if ( empty( $this->items ) ) {
            return $saved; // Don't save empty invoice.
        }
        
        if ( empty( $this->key ) ) {
            $this->key = self::generate_key();
            $this->pending['key'] = $this->key;
        }
        
        if ( empty( $this->ID ) ) {
            $invoice_id = $this->insert_invoice();

            if ( false === $invoice_id ) {
                $saved = false;
            } else {
                $this->ID = $invoice_id;
            }
        }

        // If we have something pending, let's save it
        if ( !empty( $this->pending ) ) {
            $total_increase = 0;
            $total_decrease = 0;

            foreach ( $this->pending as $key => $value ) {
                switch( $key ) {
                    case 'items':
                        // Update totals for pending items
                        foreach ( $this->pending[ $key ] as $item ) {
                            switch( $item['action'] ) {
                                case 'add':
                                    $price = $item['price'];
                                    $taxes = $item['tax'];

                                    if ( 'publish' === $this->status ) {
                                        $total_increase += $price;
                                    }
                                    break;

                                case 'remove':
                                    if ( 'publish' === $this->status ) {
                                        $total_decrease += $item['price'];
                                    }
                                    break;
                            }
                        }
                        break;
                    case 'fees':
                        if ( 'publish' !== $this->status ) {
                            break;
                        }

                        if ( empty( $this->pending[ $key ] ) ) {
                            break;
                        }

                        foreach ( $this->pending[ $key ] as $fee ) {
                            switch( $fee['action'] ) {
                                case 'add':
                                    $total_increase += $fee['amount'];
                                    break;

                                case 'remove':
                                    $total_decrease += $fee['amount'];
                                    break;
                            }
                        }
                        break;
                    case 'status':
                        $this->update_status( $this->status );
                        break;
                    case 'gateway':
                        $this->update_meta( '_wpinv_gateway', $this->gateway );
                        break;
                    case 'mode':
                        $this->update_meta( '_wpinv_mode', $this->mode );
                        break;
                    case 'transaction_id':
                        $this->update_meta( '_wpinv_transaction_id', $this->transaction_id );
                        break;
                    case 'ip':
                        $this->update_meta( '_wpinv_user_ip', $this->ip );
                        break;
                    ///case 'user_id':
                        ///$this->update_meta( '_wpinv_user_id', $this->user_id );
                        ///$this->user_info['user_id'] = $this->user_id;
                        ///break;
                    case 'first_name':
                        $this->update_meta( '_wpinv_first_name', $this->first_name );
                        $this->user_info['first_name'] = $this->first_name;
                        break;
                    case 'last_name':
                        $this->update_meta( '_wpinv_last_name', $this->last_name );
                        $this->user_info['last_name'] = $this->last_name;
                        break;
                    case 'phone':
                        $this->update_meta( '_wpinv_phone', $this->phone );
                        $this->user_info['phone'] = $this->phone;
                        break;
                    case 'address':
                        $this->update_meta( '_wpinv_address', $this->address );
                        $this->user_info['address'] = $this->address;
                        break;
                    case 'city':
                        $this->update_meta( '_wpinv_city', $this->city );
                        $this->user_info['city'] = $this->city;
                        break;
                    case 'country':
                        $this->update_meta( '_wpinv_country', $this->country );
                        $this->user_info['country'] = $this->country;
                        break;
                    case 'state':
                        $this->update_meta( '_wpinv_state', $this->state );
                        $this->user_info['state'] = $this->state;
                        break;
                    case 'zip':
                        $this->update_meta( '_wpinv_zip', $this->zip );
                        $this->user_info['zip'] = $this->zip;
                        break;
                    case 'company':
                        $this->update_meta( '_wpinv_company', $this->company );
                        $this->user_info['company'] = $this->company;
                        break;
                    case 'vat_number':
                        $this->update_meta( '_wpinv_vat_number', $this->vat_number );
                        $this->user_info['vat_number'] = $this->vat_number;
                        
                        $vat_info = $wpi_session->get( 'user_vat_data' );
                        if ( $this->vat_number && !empty( $vat_info ) && isset( $vat_info['number'] ) && isset( $vat_info['valid'] ) && $vat_info['number'] == $this->vat_number ) {
                            $adddress_confirmed = isset( $vat_info['adddress_confirmed'] ) ? $vat_info['adddress_confirmed'] : false;
                            $this->update_meta( '_wpinv_adddress_confirmed', (bool)$adddress_confirmed );
                            $this->user_info['adddress_confirmed'] = (bool)$adddress_confirmed;
                        }
    
                        break;
                    case 'vat_rate':
                        $this->update_meta( '_wpinv_vat_rate', $this->vat_rate );
                        $this->user_info['vat_rate'] = $this->vat_rate;
                        break;
                    case 'adddress_confirmed':
                        $this->update_meta( '_wpinv_adddress_confirmed', $this->adddress_confirmed );
                        $this->user_info['adddress_confirmed'] = $this->adddress_confirmed;
                        break;
                    
                    case 'key':
                        $this->update_meta( '_wpinv_key', $this->key );
                        break;
                    case 'date':
                        $args = array(
                            'ID'        => $this->ID,
                            'post_date' => $this->date,
                            'edit_date' => true,
                        );

                        wp_update_post( $args );
                        break;
                    case 'due_date':
                        if ( empty( $this->due_date ) ) {
                            $this->due_date = 'none';
                        }
                        
                        $this->update_meta( '_wpinv_due_date', $this->due_date );
                        break;
                    case 'completed_date':
                        $this->update_meta( '_wpinv_completed_date', $this->completed_date );
                        break;
                    case 'discounts':
                        if ( ! is_array( $this->discounts ) ) {
                            $this->discounts = explode( ',', $this->discounts );
                        }

                        $this->user_info['discount'] = implode( ',', $this->discounts );
                        break;
                    case 'discount':
                        $this->update_meta( '_wpinv_discount', wpinv_round_amount( $this->discount ) );
                        break;
                    case 'discount_code':
                        $this->update_meta( '_wpinv_discount_code', $this->discount_code );
                        break;
                    case 'parent_invoice':
                        $args = array(
                            'ID'          => $this->ID,
                            'post_parent' => $this->parent_invoice,
                        );
                        wp_update_post( $args );
                        break;
                    default:
                        do_action( 'wpinv_save', $this, $key );
                        break;
                }
            }

            $this->update_meta( '_wpinv_subtotal', wpinv_round_amount( $this->subtotal ) );
            $this->update_meta( '_wpinv_total', wpinv_round_amount( $this->total ) );
            $this->update_meta( '_wpinv_tax', wpinv_round_amount( $this->tax ) );
            
            $this->items    = array_values( $this->items );
            
            $new_meta = array(
                'items'         => $this->items,
                'cart_details'  => $this->cart_details,
                'fees'          => $this->fees,
                'currency'      => $this->currency,
                'user_info'     => $this->user_info,
            );
            
            $meta        = $this->get_meta();
            $merged_meta = array_merge( $meta, $new_meta );

            // Only save the payment meta if it's changed
            if ( md5( serialize( $meta ) ) !== md5( serialize( $merged_meta) ) ) {
                $updated     = $this->update_meta( '_wpinv_payment_meta', $merged_meta );
                if ( false !== $updated ) {
                    $saved = true;
                }
            }

            $this->pending = array();
            $saved         = true;
        } else {
            $this->update_meta( '_wpinv_subtotal', wpinv_round_amount( $this->subtotal ) );
            $this->update_meta( '_wpinv_total', wpinv_round_amount( $this->total ) );
            $this->update_meta( '_wpinv_tax', wpinv_round_amount( $this->tax ) );
        }
        
        do_action( 'wpinv_invoice_save', $this, $saved );

        if ( true === $saved || $setup ) {
            $this->setup_invoice( $this->ID );
        }
        
        $this->refresh_item_ids();
        
        return $saved;
    }
    
    public function add_fee( $args, $global = true ) {
        $default_args = array(
            'label'       => '',
            'amount'      => 0,
            'type'        => 'fee',
            'id'          => '',
            'no_tax'      => false,
            'item_id'     => 0,
        );

        $fee = wp_parse_args( $args, $default_args );
        
        if ( !empty( $fee['label'] ) ) {
            return false;
        }
        
        $fee['id']  = sanitize_title( $fee['label'] );
        
        $this->fees[]               = $fee;
        
        $added_fee               = $fee;
        $added_fee['action']     = 'add';
        $this->pending['fees'][] = $added_fee;
        reset( $this->fees );

        $this->increase_fees( $fee['amount'] );
        return true;
    }

    public function remove_fee( $key ) {
        $removed = false;

        if ( is_numeric( $key ) ) {
            $removed = $this->remove_fee_by( 'index', $key );
        }

        return $removed;
    }

    public function remove_fee_by( $key, $value, $global = false ) {
        $allowed_fee_keys = apply_filters( 'wpinv_fee_keys', array(
            'index', 'label', 'amount', 'type',
        ) );

        if ( ! in_array( $key, $allowed_fee_keys ) ) {
            return false;
        }

        $removed = false;
        if ( 'index' === $key && array_key_exists( $value, $this->fees ) ) {
            $removed_fee             = $this->fees[ $value ];
            $removed_fee['action']   = 'remove';
            $this->pending['fees'][] = $removed_fee;

            $this->decrease_fees( $removed_fee['amount'] );

            unset( $this->fees[ $value ] );
            $removed = true;
        } else if ( 'index' !== $key ) {
            foreach ( $this->fees as $index => $fee ) {
                if ( isset( $fee[ $key ] ) && $fee[ $key ] == $value ) {
                    $removed_fee             = $fee;
                    $removed_fee['action']   = 'remove';
                    $this->pending['fees'][] = $removed_fee;

                    $this->decrease_fees( $removed_fee['amount'] );

                    unset( $this->fees[ $index ] );
                    $removed = true;

                    if ( false === $global ) {
                        break;
                    }
                }
            }
        }

        if ( true === $removed ) {
            $this->fees = array_values( $this->fees );
        }

        return $removed;
    }

    

    public function add_note( $note = '', $customer_type = false, $added_by_user = false, $system = false ) {
        // Bail if no note specified
        if( !$note ) {
            return false;
        }

        if ( empty( $this->ID ) )
            return false;
        
        if ( ( ( is_user_logged_in() && current_user_can( 'manage_options' ) ) || $added_by_user ) && !$system ) {
            $user                 = get_user_by( 'id', get_current_user_id() );
            $comment_author       = $user->display_name;
            $comment_author_email = $user->user_email;
        } else {
            $comment_author       = __( 'System', 'invoicing' );
            $comment_author_email = strtolower( __( 'System', 'invoicing' ) ) . '@';
            $comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) : 'noreply.com';
            $comment_author_email = sanitize_email( $comment_author_email );
        }

        do_action( 'wpinv_pre_insert_invoice_note', $this->ID, $note, $customer_type );

        $note_id = wp_insert_comment( wp_filter_comment( array(
            'comment_post_ID'      => $this->ID,
            'comment_content'      => $note,
            'comment_agent'        => 'GeoDirectory',
            'user_id'              => is_admin() ? get_current_user_id() : 0,
            'comment_date'         => current_time( 'mysql' ),
            'comment_date_gmt'     => current_time( 'mysql', 1 ),
            'comment_approved'     => 1,
            'comment_parent'       => 0,
            'comment_author'       => $comment_author,
            'comment_author_IP'    => wpinv_get_ip(),
            'comment_author_url'   => '',
            'comment_author_email' => $comment_author_email,
            'comment_type'         => 'wpinv_note'
        ) ) );

        do_action( 'wpinv_insert_payment_note', $note_id, $this->ID, $note );
        
        if ( $customer_type ) {
            add_comment_meta( $note_id, '_wpi_customer_note', 1 );

            do_action( 'wpinv_new_customer_note', array( 'invoice_id' => $this->ID, 'user_note' => $note ) );
        }

        return $note_id;
    }

    private function increase_subtotal( $amount = 0.00 ) {
        $amount          = (float) $amount;
        $this->subtotal += $amount;
        $this->subtotal  = wpinv_round_amount( $this->subtotal );

        $this->recalculate_total();
    }

    private function decrease_subtotal( $amount = 0.00 ) {
        $amount          = (float) $amount;
        $this->subtotal -= $amount;
        $this->subtotal  = wpinv_round_amount( $this->subtotal );

        if ( $this->subtotal < 0 ) {
            $this->subtotal = 0;
        }

        $this->recalculate_total();
    }

    private function increase_fees( $amount = 0.00 ) {
        $amount            = (float)$amount;
        $this->fees_total += $amount;
        $this->fees_total  = wpinv_round_amount( $this->fees_total );

        $this->recalculate_total();
    }

    private function decrease_fees( $amount = 0.00 ) {
        $amount            = (float) $amount;
        $this->fees_total -= $amount;
        $this->fees_total  = wpinv_round_amount( $this->fees_total );

        if ( $this->fees_total < 0 ) {
            $this->fees_total = 0;
        }

        $this->recalculate_total();
    }

    public function recalculate_total() {
        global $wpi_nosave;
        
        $this->total = $this->subtotal + $this->tax + $this->fees_total;
        $this->total = wpinv_round_amount( $this->total );
        
        do_action( 'wpinv_invoice_recalculate_total', $this, $wpi_nosave );
    }
    
    public function increase_tax( $amount = 0.00 ) {
        $amount       = (float) $amount;
        $this->tax   += $amount;

        $this->recalculate_total();
    }

    public function decrease_tax( $amount = 0.00 ) {
        $amount     = (float) $amount;
        $this->tax -= $amount;

        if ( $this->tax < 0 ) {
            $this->tax = 0;
        }

        $this->recalculate_total();
    }

    public function update_status( $new_status = false, $note = '', $manual = false ) {
        $old_status = ! empty( $this->old_status ) ? $this->old_status : get_post_status( $this->ID );
        
        if ( $old_status === $new_status && in_array( $new_status, array_keys( wpinv_get_invoice_statuses() ) ) ) {
            return false; // Don't permit status changes that aren't changes
        }

        $do_change = apply_filters( 'wpinv_should_update_invoice_status', true, $this->ID, $new_status, $old_status );
        $updated = false;

        if ( $do_change ) {
            do_action( 'wpinv_before_invoice_status_change', $this->ID, $new_status, $old_status );

            $update_post_data                   = array();
            $update_post_data['ID']             = $this->ID;
            $update_post_data['post_status']    = $new_status;
            $update_post_data['edit_date']      = current_time( 'mysql', 0 );
            $update_post_data['edit_date_gmt']  = current_time( 'mysql', 1 );
            
            $update_post_data = apply_filters( 'wpinv_update_invoice_status_fields', $update_post_data, $this->ID );

            $updated = wp_update_post( $update_post_data );     
           
            // Process any specific status functions
            switch( $new_status ) {
                case 'wpi-refunded':
                    $this->process_refund();
                    break;
                case 'wpi-failed':
                    $this->process_failure();
                    break;
                case 'wpi-pending':
                    $this->process_pending();
                    break;
            }
            
            // Status was changed.
            do_action( 'wpinv_status_' . $new_status, $this->ID, $old_status );
            do_action( 'wpinv_status_' . $old_status . '_to_' . $new_status, $this->ID, $old_status );
            do_action( 'wpinv_update_status', $this->ID, $new_status, $old_status );
        }

        return $updated;
    }

    public function refund() {
        $this->old_status        = $this->status;
        $this->status            = 'wpi-refunded';
        $this->pending['status'] = $this->status;

        $this->save();
    }

    public function update_meta( $meta_key = '', $meta_value = '', $prev_value = '' ) {
        if ( empty( $meta_key ) ) {
            return false;
        }

        if ( $meta_key == 'key' || $meta_key == 'date' ) {
            $current_meta = $this->get_meta();
            $current_meta[ $meta_key ] = $meta_value;

            $meta_key     = '_wpinv_payment_meta';
            $meta_value   = $current_meta;
        }

        $meta_value = apply_filters( 'wpinv_update_payment_meta_' . $meta_key, $meta_value, $this->ID );
        
        if ( $meta_key == '_wpinv_completed_date' && !empty( $meta_value ) ) {
            $args = array(
                'ID'                => $this->ID,
                'post_date'         => $meta_value,
                'edit_date'         => true,
                'post_date_gmt'     => get_gmt_from_date( $meta_value ),
                'post_modified'     => $meta_value,
                'post_modified_gmt' => get_gmt_from_date( $meta_value )
            );
            wp_update_post( $args );
        }
        
        return update_post_meta( $this->ID, $meta_key, $meta_value, $prev_value );
    }

    private function process_refund() {
        $process_refund = true;

        // If the payment was not in publish, don't decrement stats as they were never incremented
        if ( 'publish' != $this->old_status || 'wpi-refunded' != $this->status ) {
            $process_refund = false;
        }

        // Allow extensions to filter for their own payment types, Example: Recurring Payments
        $process_refund = apply_filters( 'wpinv_should_process_refund', $process_refund, $this );

        if ( false === $process_refund ) {
            return;
        }

        do_action( 'wpinv_pre_refund_invoice', $this );
        
        $decrease_store_earnings = apply_filters( 'wpinv_decrease_store_earnings_on_refund', true, $this );
        $decrease_customer_value = apply_filters( 'wpinv_decrease_customer_value_on_refund', true, $this );
        $decrease_purchase_count = apply_filters( 'wpinv_decrease_customer_purchase_count_on_refund', true, $this );
        
        do_action( 'wpinv_post_refund_invoice', $this );
    }

    private function process_failure() {
        $discounts = $this->discounts;
        if ( empty( $discounts ) ) {
            return;
        }

        if ( ! is_array( $discounts ) ) {
            $discounts = array_map( 'trim', explode( ',', $discounts ) );
        }

        foreach ( $discounts as $discount ) {
            wpinv_decrease_discount_usage( $discount );
        }
    }
    
    private function process_pending() {
        $process_pending = true;

        // If the payment was not in publish or revoked status, don't decrement stats as they were never incremented
        if ( ( 'publish' != $this->old_status && 'revoked' != $this->old_status ) || 'wpi-pending' != $this->status ) {
            $process_pending = false;
        }

        // Allow extensions to filter for their own payment types, Example: Recurring Payments
        $process_pending = apply_filters( 'wpinv_should_process_pending', $process_pending, $this );

        if ( false === $process_pending ) {
            return;
        }

        $decrease_store_earnings = apply_filters( 'wpinv_decrease_store_earnings_on_pending', true, $this );
        $decrease_customer_value = apply_filters( 'wpinv_decrease_customer_value_on_pending', true, $this );
        $decrease_purchase_count = apply_filters( 'wpinv_decrease_customer_purchase_count_on_pending', true, $this );

        $this->completed_date = '';
        $this->update_meta( '_wpinv_completed_date', '' );
    }
    
    // get data
    public function get_meta( $meta_key = '_wpinv_payment_meta', $single = true ) {
        $meta = get_post_meta( $this->ID, $meta_key, $single );

        if ( $meta_key === '_wpinv_payment_meta' ) {

            if(!is_array($meta)){$meta = array();} // we need this to be an array so make sure it is.

            if ( empty( $meta['key'] ) ) {
                $meta['key'] = $this->setup_invoice_key();
            }

            if ( empty( $meta['date'] ) ) {
                $meta['date'] = get_post_field( 'post_date', $this->ID );
            }
        }

        $meta = apply_filters( 'wpinv_get_invoice_meta_' . $meta_key, $meta, $this->ID );

        return apply_filters( 'wpinv_get_invoice_meta', $meta, $this->ID, $meta_key );
    }
    
    public function get_description() {
        $post = get_post( $this->ID );
        
        $description = !empty( $post ) ? $post->post_content : '';
        return apply_filters( 'wpinv_get_description', $description, $this->ID, $this );
    }
    
    public function get_status( $nicename = false ) {
        if ( !$nicename ) {
            $status = $this->status;
        } else {
            $status = $this->status_nicename;
        }
        
        return apply_filters( 'wpinv_get_status', $status, $nicename, $this->ID, $this );
    }
    
    public function get_cart_details() {
        return apply_filters( 'wpinv_cart_details', $this->cart_details, $this->ID, $this );
    }
    
    public function get_subtotal( $currency = false ) {
        $subtotal = wpinv_round_amount( $this->subtotal );
        
        if ( $currency ) {
            $subtotal = wpinv_price( wpinv_format_amount( $subtotal, NULL, !$currency ), $this->get_currency() );
        }
        
        return apply_filters( 'wpinv_get_invoice_subtotal', $subtotal, $this->ID, $this, $currency );
    }
    
    public function get_total( $currency = false ) {        
        if ( $this->is_free_trial() ) {
            $total = wpinv_round_amount( 0 );
        } else {
            $total = wpinv_round_amount( $this->total );
        }
        if ( $currency ) {
            $total = wpinv_price( wpinv_format_amount( $total, NULL, !$currency ), $this->get_currency() );
        }
        
        return apply_filters( 'wpinv_get_invoice_total', $total, $this->ID, $this, $currency );
    }
    
    public function get_recurring_details( $field = '', $currency = false ) {        
        $data                 = array();
        $data['cart_details'] = $this->cart_details;
        $data['subtotal']     = $this->get_subtotal();
        $data['discount']     = $this->get_discount();
        $data['tax']          = $this->get_tax();
        $data['total']        = $this->get_total();
    
        if ( !empty( $this->cart_details ) && ( $this->is_parent() || $this->is_renewal() ) ) {
            $is_free_trial = $this->is_free_trial();
            $discounts = $this->get_discounts( true );
            
            if ( $is_free_trial || !empty( $discounts ) ) {
                $first_use_only = false;
                
                if ( !empty( $discounts ) ) {
                    foreach ( $discounts as $key => $code ) {
                        if ( wpinv_discount_is_recurring( $code, true ) ) {
                            $first_use_only = true;
                            break;
                        }
                    }
                }
                    
                if ( !$first_use_only ) {
                    $data['subtotal'] = wpinv_round_amount( $this->subtotal );
                    $data['discount'] = wpinv_round_amount( $this->discount );
                    $data['tax']      = wpinv_round_amount( $this->tax );
                    $data['total']    = wpinv_round_amount( $this->total );
                } else {
                    $cart_subtotal   = 0;
                    $cart_discount   = 0;
                    $cart_tax        = 0;

                    foreach ( $this->cart_details as $key => $item ) {
                        $item_quantity  = $item['quantity'] > 0 ? absint( $item['quantity'] ) : 1;
                        $item_subtotal  = !empty( $item['subtotal'] ) ? $item['subtotal'] : $item['item_price'] * $item_quantity;
                        $item_discount  = 0;
                        $item_tax       = $item_subtotal > 0 && !empty( $item['vat_rate'] ) ? ( $item_subtotal * 0.01 * (float)$item['vat_rate'] ) : 0;
                        
                        if ( wpinv_prices_include_tax() ) {
                            $item_subtotal -= wpinv_round_amount( $item_tax );
                        }
                        
                        $item_total     = $item_subtotal - $item_discount + $item_tax;
                        // Do not allow totals to go negative
                        if ( $item_total < 0 ) {
                            $item_total = 0;
                        }
                        
                        $cart_subtotal  += (float)($item_subtotal);
                        $cart_discount  += (float)($item_discount);
                        $cart_tax       += (float)($item_tax);
                        
                        $data['cart_details'][$key]['discount']   = wpinv_round_amount( $item_discount );
                        $data['cart_details'][$key]['tax']        = wpinv_round_amount( $item_tax );
                        $data['cart_details'][$key]['price']      = wpinv_round_amount( $item_total );
                    }
                    
                    $data['subtotal'] = wpinv_round_amount( $cart_subtotal );
                    $data['discount'] = wpinv_round_amount( $cart_discount );
                    $data['tax']      = wpinv_round_amount( $cart_tax );
                    $data['total']    = wpinv_round_amount( $data['subtotal'] + $data['tax'] );
                }
            }
        }
        
        $data = apply_filters( 'wpinv_get_invoice_recurring_details', $data, $this, $field, $currency );

        if ( isset( $data[$field] ) ) {
            return ( $currency ? wpinv_price( $data[$field], $this->get_currency() ) : $data[$field] );
        }
        
        return $data;
    }
    
    public function get_final_tax( $currency = false ) {        
        $final_total = wpinv_round_amount( $this->tax );
        if ( $currency ) {
            $final_total = wpinv_price( wpinv_format_amount( $final_total, NULL, !$currency ), $this->get_currency() );
        }
        
        return apply_filters( 'wpinv_get_invoice_final_total', $final_total, $this, $currency );
    }
    
    public function get_discounts( $array = false ) {
        $discounts = $this->discounts;
        if ( $array && $discounts ) {
            $discounts = explode( ',', $discounts );
        }
        return apply_filters( 'wpinv_payment_discounts', $discounts, $this->ID, $this, $array );
    }
    
    public function get_discount( $currency = false, $dash = false ) {
        if ( !empty( $this->discounts ) ) {
            global $ajax_cart_details;
            $ajax_cart_details = $this->get_cart_details();
            
            if ( !empty( $ajax_cart_details ) && count( $ajax_cart_details ) == count( $this->items ) ) {
                $cart_items = $ajax_cart_details;
            } else {
                $cart_items = $this->items;
            }

            $this->discount = wpinv_get_cart_items_discount_amount( $cart_items , $this->discounts );
        }
        $discount   = wpinv_round_amount( $this->discount );
        $dash       = $dash && $discount > 0 ? '&ndash;' : '';
        
        if ( $currency ) {
            $discount = wpinv_price( wpinv_format_amount( $discount, NULL, !$currency ), $this->get_currency() );
        }
        
        $discount   = $dash . $discount;
        
        return apply_filters( 'wpinv_get_invoice_discount', $discount, $this->ID, $this, $currency, $dash );
    }
    
    public function get_discount_code() {
        return $this->discount_code;
    }
    
    public function get_tax( $currency = false ) {
        $tax = wpinv_round_amount( $this->tax );
        
        if ( $currency ) {
            $tax = wpinv_price( wpinv_format_amount( $tax, NULL, !$currency ), $this->get_currency() );
        }
        
        return apply_filters( 'wpinv_get_invoice_tax', $tax, $this->ID, $this, $currency );
    }
    
    public function get_fees( $type = 'all' ) {
        $fees    = array();

        if ( ! empty( $this->fees ) && is_array( $this->fees ) ) {
            foreach ( $this->fees as $fee ) {
                if( 'all' != $type && ! empty( $fee['type'] ) && $type != $fee['type'] ) {
                    continue;
                }

                $fee['label'] = stripslashes( $fee['label'] );
                $fee['amount_display'] = wpinv_price( $fee['amount'], $this->get_currency() );
                $fees[]    = $fee;
            }
        }

        return apply_filters( 'wpinv_get_invoice_fees', $fees, $this->ID, $this );
    }
    
    public function get_fees_total( $type = 'all' ) {
        $fees_total = (float) 0.00;

        $payment_fees = isset( $this->payment_meta['fees'] ) ? $this->payment_meta['fees'] : array();
        if ( ! empty( $payment_fees ) ) {
            foreach ( $payment_fees as $fee ) {
                $fees_total += (float) $fee['amount'];
            }
        }

        return apply_filters( 'wpinv_get_invoice_fees_total', $fees_total, $this->ID, $this );
        /*
        $fees = $this->get_fees( $type );

        $fees_total = 0;
        if ( ! empty( $fees ) && is_array( $fees ) ) {
            foreach ( $fees as $fee_id => $fee ) {
                if( 'all' != $type && !empty( $fee['type'] ) && $type != $fee['type'] ) {
                    continue;
                }

                $fees_total += $fee['amount'];
            }
        }

        return apply_filters( 'wpinv_get_invoice_fees_total', $fees_total, $this->ID, $this );
        */
    }

    public function get_user_id() {
        return apply_filters( 'wpinv_user_id', $this->user_id, $this->ID, $this );
    }
    
    public function get_first_name() {
        return apply_filters( 'wpinv_first_name', $this->first_name, $this->ID, $this );
    }
    
    public function get_last_name() {
        return apply_filters( 'wpinv_last_name', $this->last_name, $this->ID, $this );
    }
    
    public function get_user_full_name() {
        return apply_filters( 'wpinv_user_full_name', $this->full_name, $this->ID, $this );
    }
    
    public function get_user_info() {
        return apply_filters( 'wpinv_user_info', $this->user_info, $this->ID, $this );
    }
    
    public function get_email() {
        return apply_filters( 'wpinv_user_email', $this->email, $this->ID, $this );
    }
    
    public function get_address() {
        return apply_filters( 'wpinv_address', $this->address, $this->ID, $this );
    }
    
    public function get_phone() {
        return apply_filters( 'wpinv_phone', $this->phone, $this->ID, $this );
    }
    
    public function get_number() {
        return apply_filters( 'wpinv_number', $this->number, $this->ID, $this );
    }
    
    public function get_items() {
        return apply_filters( 'wpinv_payment_meta_items', $this->items, $this->ID, $this );
    }
    
    public function get_key() {
        return apply_filters( 'wpinv_key', $this->key, $this->ID, $this );
    }
    
    public function get_transaction_id() {
        return apply_filters( 'wpinv_get_invoice_transaction_id', $this->transaction_id, $this->ID, $this );
    }
    
    public function get_gateway() {
        return apply_filters( 'wpinv_gateway', $this->gateway, $this->ID, $this );
    }
    
    public function get_gateway_title() {
        $this->gateway_title = !empty( $this->gateway_title ) ? $this->gateway_title : wpinv_get_gateway_checkout_label( $this->gateway );
        
        return apply_filters( 'wpinv_gateway_title', $this->gateway_title, $this->ID, $this );
    }
    
    public function get_currency() {
        return apply_filters( 'wpinv_currency_code', $this->currency, $this->ID, $this );
    }
    
    public function get_created_date() {
        return apply_filters( 'wpinv_created_date', $this->date, $this->ID, $this );
    }
    
    public function get_due_date( $display = false ) {
        $due_date = apply_filters( 'wpinv_due_date', $this->due_date, $this->ID, $this );
        
        if ( !$display || empty( $due_date ) ) {
            return $due_date;
        }
        
        return date_i18n( get_option( 'date_format' ), strtotime( $due_date ) );
    }
    
    public function get_completed_date() {
        return apply_filters( 'wpinv_completed_date', $this->completed_date, $this->ID, $this );
    }
    
    public function get_invoice_date( $formatted = true ) {
        $date_completed = $this->completed_date;
        $invoice_date   = $date_completed != '' && $date_completed != '0000-00-00 00:00:00' ? $date_completed : '';
        
        if ( $invoice_date == '' ) {
            $date_created   = $this->date;
            $invoice_date   = $date_created != '' && $date_created != '0000-00-00 00:00:00' ? $date_created : '';
        }
        
        if ( $formatted && $invoice_date ) {
            $invoice_date   = date_i18n( get_option( 'date_format' ), strtotime( $invoice_date ) );
        }

        return apply_filters( 'wpinv_get_invoice_date', $invoice_date, $formatted, $this->ID, $this );
    }
    
    public function get_ip() {
        return apply_filters( 'wpinv_user_ip', $this->ip, $this->ID, $this );
    }
        
    public function has_status( $status ) {
        return apply_filters( 'wpinv_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
    }
    
    public function add_item( $item_id = 0, $args = array() ) {
        global $wpi_current_id, $wpi_item_id;
        
        $item = new WPInv_Item( $item_id );

        // Bail if this post isn't a item
        if( !$item || $item->post_type !== 'wpi_item' ) {
            return false;
        }
        
        $has_quantities = wpinv_item_quantities_enabled();

        // Set some defaults
        $defaults = array(
            'quantity'      => 1,
            'id'            => false,
            'name'          => $item->get_name(),
            'item_price'    => false,
            'custom_price'  => '',
            'discount'      => 0,
            'tax'           => 0.00,
            'meta'          => array(),
            'fees'          => array()
        );

        $args = wp_parse_args( apply_filters( 'wpinv_add_item_args', $args, $item->ID ), $defaults );
        $args['quantity']   = $has_quantities && $args['quantity'] > 0 ? absint( $args['quantity'] ) : 1;

        $wpi_current_id         = $this->ID;
        $wpi_item_id            = $item->ID;
        $discounts              = $this->get_discounts();
        
        $_POST['wpinv_country'] = $this->country;
        $_POST['wpinv_state']   = $this->state;
        
        $found_cart_key         = false;
        
        if ($has_quantities) {
            $this->cart_details = !empty( $this->cart_details ) ? array_values( $this->cart_details ) : $this->cart_details;
            
            foreach ( $this->items as $key => $cart_item ) {
                if ( (int)$item_id !== (int)$cart_item['id'] ) {
                    continue;
                }

                $this->items[ $key ]['quantity'] += $args['quantity'];
                break;
            }
            
            foreach ( $this->cart_details as $cart_key => $cart_item ) {
                if ( $item_id != $cart_item['id'] ) {
                    continue;
                }

                $found_cart_key = $cart_key;
                break;
            }
        }
        
        if ($has_quantities && $found_cart_key !== false) {
            $cart_item          = $this->cart_details[$found_cart_key];
            $item_price         = $cart_item['item_price'];
            $quantity           = !empty( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;
            $tax_rate           = !empty( $cart_item['vat_rate'] ) ? $cart_item['vat_rate'] : 0;
            
            $new_quantity       = $quantity + $args['quantity'];
            $subtotal           = $item_price * $new_quantity;
            
            $args['quantity']   = $new_quantity;
            $discount           = !empty( $args['discount'] ) ? $args['discount'] : 0;
            $tax                = $subtotal > 0 && $tax_rate > 0 ? ( ( $subtotal - $discount ) * 0.01 * (float)$tax_rate ) : 0;
            
            $discount_increased = $discount > 0 && $subtotal > 0 && $discount > (float)$cart_item['discount'] ? $discount - (float)$cart_item['discount'] : 0;
            $tax_increased      = $tax > 0 && $subtotal > 0 && $tax > (float)$cart_item['tax'] ? $tax - (float)$cart_item['tax'] : 0;
            // The total increase equals the number removed * the item_price
            $total_increased    = wpinv_round_amount( $item_price );
            
            if ( wpinv_prices_include_tax() ) {
                $subtotal -= wpinv_round_amount( $tax );
            }

            $total              = $subtotal - $discount + $tax;

            // Do not allow totals to go negative
            if( $total < 0 ) {
                $total = 0;
            }
            
            $cart_item['quantity']  = $new_quantity;
            $cart_item['subtotal']  = $subtotal;
            $cart_item['discount']  = $discount;
            $cart_item['tax']       = $tax;
            $cart_item['price']     = $total;
            
            $subtotal               = $total_increased - $discount_increased;
            $tax                    = $tax_increased;
            
            $this->cart_details[$found_cart_key] = $cart_item;
        } else {
            // Set custom price.
            if ( $args['custom_price'] !== '' ) {
                $item_price = $args['custom_price'];
            } else {
                // Allow overriding the price
                if ( false !== $args['item_price'] ) {
                    $item_price = $args['item_price'];
                } else {
                    $item_price = wpinv_get_item_price( $item->ID );
                }
            }

            // Sanitizing the price here so we don't have a dozen calls later
            $item_price = wpinv_sanitize_amount( $item_price );
            $subtotal   = wpinv_round_amount( $item_price * $args['quantity'] );
        
            $discount   = !empty( $args['discount'] ) ? $args['discount'] : 0;
            $tax_class  = !empty( $args['vat_class'] ) ? $args['vat_class'] : '';
            $tax_rate   = !empty( $args['vat_rate'] ) ? $args['vat_rate'] : 0;
            $tax        = $subtotal > 0 && $tax_rate > 0 ? ( ( $subtotal - $discount ) * 0.01 * (float)$tax_rate ) : 0;

            // Setup the items meta item
            $new_item = array(
                'id'       => $item->ID,
                'quantity' => $args['quantity'],
            );

            $this->items[]  = $new_item;

            if ( wpinv_prices_include_tax() ) {
                $subtotal -= wpinv_round_amount( $tax );
            }

            $total      = $subtotal - $discount + $tax;

            // Do not allow totals to go negative
            if( $total < 0 ) {
                $total = 0;
            }
        
            $this->cart_details[] = array(
                'name'          => !empty($args['name']) ? $args['name'] : $item->get_name(),
                'id'            => $item->ID,
                'item_price'    => wpinv_round_amount( $item_price ),
                'custom_price'  => ( $args['custom_price'] !== '' ? wpinv_round_amount( $args['custom_price'] ) : '' ),
                'quantity'      => $args['quantity'],
                'discount'      => $discount,
                'subtotal'      => wpinv_round_amount( $subtotal ),
                'tax'           => wpinv_round_amount( $tax ),
                'price'         => wpinv_round_amount( $total ),
                'vat_rate'      => $tax_rate,
                'vat_class'     => $tax_class,
                'meta'          => $args['meta'],
                'fees'          => $args['fees'],
            );
                        
            $subtotal = $subtotal - $discount;
        }
        
        $added_item = end( $this->cart_details );
        $added_item['action']  = 'add';
        
        $this->pending['items'][] = $added_item;
        
        $this->increase_subtotal( $subtotal );
        $this->increase_tax( $tax );

        return true;
    }
    
    public function remove_item( $item_id, $args = array() ) {
        // Set some defaults
        $defaults = array(
            'quantity'      => 1,
            'item_price'    => false,
            'custom_price'  => '',
            'cart_index'    => false,
        );
        $args = wp_parse_args( $args, $defaults );

        // Bail if this post isn't a item
        if ( get_post_type( $item_id ) !== 'wpi_item' ) {
            return false;
        }
        
        $this->cart_details = !empty( $this->cart_details ) ? array_values( $this->cart_details ) : $this->cart_details;

        foreach ( $this->items as $key => $item ) {
            if ( !empty($item['id']) && (int)$item_id !== (int)$item['id'] ) {
                continue;
            }

            if ( false !== $args['cart_index'] ) {
                $cart_index = absint( $args['cart_index'] );
                $cart_item  = ! empty( $this->cart_details[ $cart_index ] ) ? $this->cart_details[ $cart_index ] : false;

                if ( ! empty( $cart_item ) ) {
                    // If the cart index item isn't the same item ID, don't remove it
                    if ( !empty($cart_item['id']) && $cart_item['id'] != $item['id'] ) {
                        continue;
                    }
                }
            }

            $item_quantity = $this->items[ $key ]['quantity'];
            if ( $item_quantity > $args['quantity'] ) {
                $this->items[ $key ]['quantity'] -= $args['quantity'];
                break;
            } else {
                unset( $this->items[ $key ] );
                break;
            }
        }

        $found_cart_key = false;
        if ( false === $args['cart_index'] ) {
            foreach ( $this->cart_details as $cart_key => $item ) {
                if ( $item_id != $item['id'] ) {
                    continue;
                }

                if ( false !== $args['item_price'] ) {
                    if ( isset( $item['item_price'] ) && (float) $args['item_price'] != (float) $item['item_price'] ) {
                        continue;
                    }
                }

                $found_cart_key = $cart_key;
                break;
            }
        } else {
            $cart_index = absint( $args['cart_index'] );

            if ( ! array_key_exists( $cart_index, $this->cart_details ) ) {
                return false; // Invalid cart index passed.
            }

            if ( (int) $this->cart_details[ $cart_index ]['id'] > 0 && (int) $this->cart_details[ $cart_index ]['id'] !== (int) $item_id ) {
                return false; // We still need the proper Item ID to be sure.
            }

            $found_cart_key = $cart_index;
        }
        
        $cart_item  = $this->cart_details[$found_cart_key];
        $quantity   = !empty( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;
        
        if ( count( $this->cart_details ) == 1 && ( $quantity - $args['quantity'] ) < 1 ) {
            return false; // Invoice must contain at least one item.
        }
        
        $discounts  = $this->get_discounts();
        
        if ( $quantity > $args['quantity'] ) {
            $item_price         = $cart_item['item_price'];
            $tax_rate           = !empty( $cart_item['vat_rate'] ) ? $cart_item['vat_rate'] : 0;
            
            $new_quantity       = max( $quantity - $args['quantity'], 1);
            $subtotal           = $item_price * $new_quantity;
            
            $args['quantity']   = $new_quantity;
            $discount           = !empty( $cart_item['discount'] ) ? $cart_item['discount'] : 0;
            $tax                = $subtotal > 0 && $tax_rate > 0 ? ( ( $subtotal - $discount ) * 0.01 * (float)$tax_rate ) : 0;
            
            $discount_decrease  = (float)$cart_item['discount'] > 0 && $quantity > 0 ? wpinv_round_amount( ( (float)$cart_item['discount'] / $quantity ) ) : 0;
            $discount_decrease  = $discount > 0 && $subtotal > 0 && (float)$cart_item['discount'] > $discount ? (float)$cart_item['discount'] - $discount : $discount_decrease; 
            $tax_decrease       = (float)$cart_item['tax'] > 0 && $quantity > 0 ? wpinv_round_amount( ( (float)$cart_item['tax'] / $quantity ) ) : 0;
            $tax_decrease       = $tax > 0 && $subtotal > 0 && (float)$cart_item['tax'] > $tax ? (float)$cart_item['tax'] - $tax : $tax_decrease;
            
            // The total increase equals the number removed * the item_price
            $total_decrease     = wpinv_round_amount( $item_price );
            
            if ( wpinv_prices_include_tax() ) {
                $subtotal -= wpinv_round_amount( $tax );
            }

            $total              = $subtotal - $discount + $tax;

            // Do not allow totals to go negative
            if( $total < 0 ) {
                $total = 0;
            }
            
            $cart_item['quantity']  = $new_quantity;
            $cart_item['subtotal']  = $subtotal;
            $cart_item['discount']  = $discount;
            $cart_item['tax']       = $tax;
            $cart_item['price']     = $total;
            
            $added_item             = $cart_item;
            $added_item['id']       = $item_id;
            $added_item['price']    = $total_decrease;
            $added_item['quantity'] = $args['quantity'];
            
            $subtotal_decrease      = $total_decrease - $discount_decrease;
            
            $this->cart_details[$found_cart_key] = $cart_item;
            
            $remove_item = end( $this->cart_details );
        } else {
            $item_price     = $cart_item['item_price'];
            $discount       = !empty( $cart_item['discount'] ) ? $cart_item['discount'] : 0;
            $tax            = !empty( $cart_item['tax'] ) ? $cart_item['tax'] : 0;
        
            $subtotal_decrease  = ( $item_price * $quantity ) - $discount;
            $tax_decrease       = $tax;

            unset( $this->cart_details[$found_cart_key] );
            
            $remove_item             = $args;
            $remove_item['id']       = $item_id;
            $remove_item['price']    = $subtotal_decrease;
            $remove_item['quantity'] = $args['quantity'];
        }
        
        $remove_item['action']      = 'remove';
        $this->pending['items'][]   = $remove_item;
               
        $this->decrease_subtotal( $subtotal_decrease );
        $this->decrease_tax( $tax_decrease );
        
        return true;
    }
    
    public function update_items($temp = false) {
        global $wpinv_euvat, $wpi_current_id, $wpi_item_id, $wpi_nosave;
        
        if ( !empty( $this->cart_details ) ) {
            $wpi_nosave             = $temp;
            $cart_subtotal          = 0;
            $cart_discount          = 0;
            $cart_tax               = 0;
            $cart_details           = array();
            
            $_POST['wpinv_country'] = $this->country;
            $_POST['wpinv_state']   = $this->state;
            
            foreach ( $this->cart_details as $key => $item ) {
                $item_price = $item['item_price'];
                $quantity   = wpinv_item_quantities_enabled() && $item['quantity'] > 0 ? absint( $item['quantity'] ) : 1;
                $amount     = wpinv_round_amount( $item_price * $quantity );
                $subtotal   = $item_price * $quantity;
                
                $wpi_current_id         = $this->ID;
                $wpi_item_id            = $item['id'];
                
                $discount   = wpinv_get_cart_item_discount_amount( $item, $this->get_discounts() );
                
                $tax_rate   = wpinv_get_tax_rate( $this->country, $this->state, $wpi_item_id );
                $tax_class  = $wpinv_euvat->get_item_class( $wpi_item_id );
                $tax        = $item_price > 0 ? ( ( $subtotal - $discount ) * 0.01 * (float)$tax_rate ) : 0;

                if ( wpinv_prices_include_tax() ) {
                    $subtotal -= wpinv_round_amount( $tax );
                }

                $total      = $subtotal - $discount + $tax;

                // Do not allow totals to go negative
                if( $total < 0 ) {
                    $total = 0;
                }

                $cart_details[] = array(
                    'id'          => $item['id'],
                    'name'        => $item['name'],
                    'item_price'  => wpinv_round_amount( $item_price ),
                    'custom_price'=> ( isset( $item['custom_price'] ) ? $item['custom_price'] : '' ),
                    'quantity'    => $quantity,
                    'discount'    => $discount,
                    'subtotal'    => wpinv_round_amount( $subtotal ),
                    'tax'         => wpinv_round_amount( $tax ),
                    'price'       => wpinv_round_amount( $total ),
                    'vat_rate'    => $tax_rate,
                    'vat_class'   => $tax_class,
                    'meta'        => isset($item['meta']) ? $item['meta'] : array(),
                    'fees'        => isset($item['fees']) ? $item['fees'] : array(),
                );
                
                $cart_subtotal  += (float)($subtotal - $discount); // TODO
                $cart_discount  += (float)($discount);
                $cart_tax       += (float)($tax);
            }
            $this->subtotal = wpinv_round_amount( $cart_subtotal );
            $this->tax      = wpinv_round_amount( $cart_tax );
            $this->discount = wpinv_round_amount( $cart_discount );
            
            $this->recalculate_total();
            
            $this->cart_details = $cart_details;
        }

        return $this;
    }
    
    public function recalculate_totals($temp = false) {        
        $this->update_items($temp);
        $this->save( true );
        
        return $this;
    }
    
    public function needs_payment() {
        $valid_invoice_statuses = apply_filters( 'wpinv_valid_invoice_statuses_for_payment', array( 'wpi-pending' ), $this );

        if ( $this->has_status( $valid_invoice_statuses ) && ( $this->get_total() > 0 || $this->is_free_trial() || $this->is_free() ) ) {
            $needs_payment = true;
        } else {
            $needs_payment = false;
        }

        return apply_filters( 'wpinv_needs_payment', $needs_payment, $this, $valid_invoice_statuses );
    }
    
    public function get_checkout_payment_url( $with_key = false, $secret = false ) {
        $pay_url = wpinv_get_checkout_uri();

        if ( is_ssl() ) {
            $pay_url = str_replace( 'http:', 'https:', $pay_url );
        }
        
        $key = $this->get_key();

        if ( $with_key ) {
            $pay_url = add_query_arg( 'invoice_key', $key, $pay_url );
        } else {
            $pay_url = add_query_arg( array( 'wpi_action' => 'pay_for_invoice', 'invoice_key' => $key ), $pay_url );
        }
        
        if ( $secret ) {
            $pay_url = add_query_arg( array( '_wpipay' => md5( $this->get_user_id() . '::' . $this->get_email() . '::' . $key ) ), $pay_url );
        }

        return apply_filters( 'wpinv_get_checkout_payment_url', $pay_url, $this, $with_key, $secret );
    }
    
    public function get_view_url( $with_key = false ) {
        $invoice_url = get_permalink( $this->ID );

        if ( $with_key ) {
            $invoice_url = add_query_arg( 'invoice_key', $this->get_key(), $invoice_url );
        }

        return apply_filters( 'wpinv_get_view_url', $invoice_url, $this, $with_key );
    }
    
    public function generate_key( $string = '' ) {
        $auth_key  = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
        return strtolower( md5( $string . date( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'wpinv', true ) ) );  // Unique key
    }
    
    public function is_recurring() {
        if ( empty( $this->cart_details ) ) {
            return false;
        }
        
        $has_subscription = false;
        foreach( $this->cart_details as $cart_item ) {
            if ( !empty( $cart_item['id'] ) && wpinv_is_recurring_item( $cart_item['id'] )  ) {
                $has_subscription = true;
                break;
            }
        }
        
        if ( count( $this->cart_details ) > 1 ) {
            $has_subscription = false;
        }

        return apply_filters( 'wpinv_invoice_has_recurring_item', $has_subscription, $this->cart_details );
    }
    
    public function is_free_trial() {
        $is_free_trial = false;
        
        if ( $this->is_parent() && $item = $this->get_recurring( true ) ) {
            if ( !empty( $item ) && $item->has_free_trial() ) {
                $is_free_trial = true;
            }
        }

        return apply_filters( 'wpinv_invoice_is_free_trial', $is_free_trial, $this->cart_details );
    }
    
    public function get_recurring( $object = false ) {
        $item = NULL;
        
        if ( empty( $this->cart_details ) ) {
            return $item;
        }
        
        foreach( $this->cart_details as $cart_item ) {
            if ( !empty( $cart_item['id'] ) && wpinv_is_recurring_item( $cart_item['id'] )  ) {
                $item = $cart_item['id'];
                break;
            }
        }
        
        if ( $object ) {
            $item = $item ? new WPInv_Item( $item ) : NULL;
            
            apply_filters( 'wpinv_invoice_get_recurring_item', $item, $this );
        }

        return apply_filters( 'wpinv_invoice_get_recurring_item_id', $item, $this );
    }
    
    public function get_subscription_name() {
        $item = $this->get_recurring( true );
        
        if ( empty( $item ) ) {
            return NULL;
        }
        
        if ( !($name = $item->get_name()) ) {
            $name = $item->post_name;
        }

        return apply_filters( 'wpinv_invoice_get_subscription_name', $name, $this );
    }
        
    public function get_expiration() {
        $expiration = $this->get_meta( '_wpinv_subscr_expiration', true );
        return $expiration;
    }
    
    public function get_cancelled_date( $formatted = true ) {
        $cancelled_date = $this->get_subscription_status() == 'cancelled' ? $this->get_meta( '_wpinv_subscr_cancelled_on', true ) : '';
        
        if ( $formatted && $cancelled_date ) {
            $cancelled_date = date_i18n( get_option( 'date_format' ), strtotime( $cancelled_date ) );
        }
        
        return $cancelled_date;
    }
    
    public function get_trial_end_date( $formatted = true ) {
        if ( !$this->is_free_trial() || !$this->is_paid() ) {
            return NULL;
        }
        
        $trial_end_date = $this->get_subscription_status() == 'trialing' ? $this->get_meta( '_wpinv_subscr_trial_end', true ) : '';
        
        if ( empty( $trial_end_date ) ) {
            $trial_start_time = strtotime( $this->get_subscription_start() );
            $trial_start_time += ( wpinv_period_in_days( $this->get_subscription_trial_interval(), $this->get_subscription_trial_period() ) * DAY_IN_SECONDS ) ;
            
            $trial_end_date = date_i18n( 'Y-m-d H:i:s', $trial_start_time );
        }
        
        if ( $formatted && $trial_end_date ) {
            $trial_end_date = date_i18n( get_option( 'date_format' ), strtotime( $trial_end_date ) );
        }
        
        return $trial_end_date;
    }
    
    public function get_subscription_created( $default = true ) {
        $created = $this->get_meta( '_wpinv_subscr_created', true );
        
        if ( empty( $created ) && $default ) {
            $created = $this->date;
        }
        return $created;
    }
    
    public function get_subscription_start( $formatted = true ) {
        if ( !$this->is_paid() ) {
            return '-';
        }
        $start   = $this->get_subscription_created();
        
        if ( $formatted ) {
            $date = date_i18n( get_option( 'date_format' ), strtotime( $start ) );
        } else {
            $date = date_i18n( 'Y-m-d H:i:s', strtotime( $start ) );
        }

        return $date;
    }
    
    public function get_subscription_end( $formatted = true ) {
        if ( !$this->is_paid() ) {
            return '-';
        }
        $start          = $this->get_subscription_created();
        $interval       = $this->get_subscription_interval();
        $period         = $this->get_subscription_period( true );
        $bill_times     = (int)$this->get_bill_times();
        
        if ( $bill_times == 0 ) {
            return $formatted ? __( 'Until cancelled', 'invoicing' ) : $bill_times;
        }
        
        $total_period = $start . '+' . ( $interval * $bill_times ) . ' ' . $period;
        
        $end_time = strtotime( $start . '+' . ( $interval * $bill_times ) . ' ' . $period );
        
        if ( $this->is_free_trial() ) {
            $end_time += ( wpinv_period_in_days( $this->get_subscription_trial_interval(), $this->get_subscription_trial_period() ) * DAY_IN_SECONDS ) ;
        }
        
        if ( $formatted ) {
            $date = date_i18n( get_option( 'date_format' ), $end_time );
        } else {
            $date = date_i18n( 'Y-m-d H:i:s', $end_time );
        }

        return $date;
    }
    
    public function get_expiration_time() {
        return strtotime( $this->get_expiration(), current_time( 'timestamp' ) );
    }
    
    public function get_original_invoice_id() {        
        return $this->parent_invoice;
    }
    
    public function get_bill_times() {
        $subscription_data = $this->get_subscription_data();
        return $subscription_data['bill_times'];
    }

    public function get_child_payments( $self = false ) {
        $invoices = get_posts( array(
            'post_type'         => $this->post_type,
            'post_parent'       => (int)$this->ID,
            'posts_per_page'    => '999',
            'post_status'       => array( 'publish', 'wpi-processing', 'wpi-renewal' ),
            'orderby'           => 'ID',
            'order'             => 'DESC',
            'fields'            => 'ids'
        ) );
        
        if ( $this->is_free_trial() ) {
            $self = false;
        }
        
        if ( $self && $this->is_paid() ) {
            if ( !empty( $invoices ) ) {
                $invoices[] = (int)$this->ID;
            } else {
                $invoices = array( $this->ID );
            }
            
            $invoices = array_unique( $invoices );
        }

        return $invoices;
    }

    public function get_total_payments( $self = true ) {
        return count( $this->get_child_payments( $self ) );
    }
    
    public function get_subscriptions( $limit = -1 ) {
        $subscriptions = wpinv_get_subscriptions( array( 'parent_invoice_id' => $this->ID, 'numberposts' => $limit ) );

        return $subscriptions;
    }
    
    public function get_subscription_id() {
        $subscription_id = $this->get_meta( '_wpinv_subscr_profile_id', true );
        
        if ( empty( $subscription_id ) && !empty( $this->parent_invoice ) ) {
            $parent_invoice = wpinv_get_invoice( $this->parent_invoice );
            
            $subscription_id = $parent_invoice->get_meta( '_wpinv_subscr_profile_id', true );
        }
        
        return $subscription_id;
    }
    
    public function get_subscription_status() {
        $subscription_status = $this->get_meta( '_wpinv_subscr_status', true );

        if ( empty( $subscription_status ) ) {
            $status = 'pending';
            
            if ( $this->is_paid() ) {        
                $bill_times   = (int)$this->get_bill_times();
                $times_billed = (int)$this->get_total_payments();
                $expiration = $this->get_subscription_end( false );
                $expired = $bill_times != 0 && $expiration != '' && $expiration != '-' && strtotime( date_i18n( 'Y-m-d', strtotime( $expiration ) ) ) < strtotime( date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) ) ? true : false;
                
                if ( (int)$bill_times == 0 ) {
                    $status = $expired ? 'expired' : 'active';
                } else if ( $bill_times > 0 && $times_billed >= $bill_times ) {
                    $status = 'completed';
                } else if ( $expired ) {
                    $status = 'expired';
                } else if ( $bill_times > 0 ) {
                    $status = 'active';
                } else {
                    $status = 'pending';
                }
            }
            
            if ( $status && $status != $subscription_status ) {
                $subscription_status = $status;
                
                $this->update_meta( '_wpinv_subscr_status', $status );
            }
        }
        
        return $subscription_status;
    }
    
    public function get_subscription_status_label( $status = '' ) {
        $status = !empty( $status ) ? $status : $this->get_subscription_status();

        switch( $status ) {
            case 'active' :
                $status_label = __( 'Active', 'invoicing' );
                break;

            case 'cancelled' :
                $status_label = __( 'Cancelled', 'invoicing' );
                break;
                
            case 'completed' :
                $status_label = __( 'Completed', 'invoicing' );
                break;

            case 'expired' :
                $status_label = __( 'Expired', 'invoicing' );
                break;

            case 'pending' :
                $status_label = __( 'Pending', 'invoicing' );
                break;

            case 'failing' :
                $status_label = __( 'Failing', 'invoicing' );
                break;
                
            case 'stopped' :
                $status_label = __( 'Stopped', 'invoicing' );
                break;
                
            case 'trialing' :
                $status_label = __( 'Trialing', 'invoicing' );
                break;

            default:
                $status_label = $status;
                break;
        }

        return $status_label;
    }
    
    public function get_subscription_period( $full = false ) {
        $period = $this->get_meta( '_wpinv_subscr_period', true );
        
        // Fix period for old invoices
        if ( $period == 'day' ) {
            $period = 'D';
        } else if ( $period == 'week' ) {
            $period = 'W';
        } else if ( $period == 'month' ) {
            $period = 'M';
        } else if ( $period == 'year' ) {
            $period = 'Y';
        }
        
        if ( !in_array( $period, array( 'D', 'W', 'M', 'Y' ) ) ) {
            $period = 'D';
        }
        
        if ( $full ) {
            switch( $period ) {
                case 'D':
                    $period = 'day';
                break;
                case 'W':
                    $period = 'week';
                break;
                case 'M':
                    $period = 'month';
                break;
                case 'Y':
                    $period = 'year';
                break;
            }
        }
        
        return $period;
    }
    
    public function get_subscription_interval() {
        $interval = (int)$this->get_meta( '_wpinv_subscr_interval', true );
        
        if ( !$interval > 0 ) {
            $interval = 1;
        }
        
        return $interval;
    }
    
    public function get_subscription_trial_period( $full = false ) {
        if ( !$this->is_free_trial() ) {
            return '';
        }
        
        $period = $this->get_meta( '_wpinv_subscr_trial_period', true );
        
        // Fix period for old invoices
        if ( $period == 'day' ) {
            $period = 'D';
        } else if ( $period == 'week' ) {
            $period = 'W';
        } else if ( $period == 'month' ) {
            $period = 'M';
        } else if ( $period == 'year' ) {
            $period = 'Y';
        }
        
        if ( !in_array( $period, array( 'D', 'W', 'M', 'Y' ) ) ) {
            $period = 'D';
        }
        
        if ( $full ) {
            switch( $period ) {
                case 'D':
                    $period = 'day';
                break;
                case 'W':
                    $period = 'week';
                break;
                case 'M':
                    $period = 'month';
                break;
                case 'Y':
                    $period = 'year';
                break;
            }
        }
        
        return $period;
    }
    
    public function get_subscription_trial_interval() {
        if ( !$this->is_free_trial() ) {
            return 0;
        }
        
        $interval = (int)$this->get_meta( '_wpinv_subscr_trial_interval', true );
        
        if ( !$interval > 0 ) {
            $interval = 1;
        }
        
        return $interval;
    }
    
    public function failing_subscription() {
        $args = array(
            'status' => 'failing'
        );

        if ( $this->update_subscription( $args ) ) {
            do_action( 'wpinv_subscription_failing', $this->ID, $this );
            return true;
        }

        return false;
    }
    
    public function stop_subscription() {
        $args = array(
            'status' => 'stopped'
        );

        if ( $this->update_subscription( $args ) ) {
            do_action( 'wpinv_subscription_stopped', $this->ID, $this );
            return true;
        }

        return false;
    }
    
    public function restart_subscription() {
        $args = array(
            'status' => 'active'
        );

        if ( $this->update_subscription( $args ) ) {
            do_action( 'wpinv_subscription_restarted', $this->ID, $this );
            return true;
        }

        return false;
    }

    public function cancel_subscription() {
        $args = array(
            'status' => 'cancelled'
        );

        if ( $this->update_subscription( $args ) ) {
            if ( is_user_logged_in() ) {
                $userdata = get_userdata( get_current_user_id() );
                $user     = $userdata->user_login;
            } else {
                $user = __( 'gateway', 'invoicing' );
            }
            
            $subscription_id = $this->get_subscription_id();
            if ( !$subscription_id ) {
                $subscription_id = $this->ID;
            }

            $note = sprintf( __( 'Subscription %s has been cancelled by %s', 'invoicing' ), $subscription_id, $user );
            $this->add_note( $note );

            do_action( 'wpinv_subscription_cancelled', $this->ID, $this );
            return true;
        }

        return false;
    }

    public function can_cancel() {
        return apply_filters( 'wpinv_subscription_can_cancel', false, $this );
    }
    
    public function add_subscription( $data = array() ) {
        if ( empty( $this->ID ) ) {
            return false;
        }

        $defaults = array(
            'period'            => '',
            'initial_amount'    => '',
            'recurring_amount'  => '',
            'interval'          => 0,
            'trial_interval'    => 0,
            'trial_period'      => '',
            'bill_times'        => 0,
            'item_id'           => 0,
            'created'           => '',
            'expiration'        => '',
            'status'            => '',
            'profile_id'        => '',
        );

        $args = wp_parse_args( $data, $defaults );

        if ( $args['expiration'] && strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $args['expiration'], current_time( 'timestamp' ) ) ) {
            if ( 'active' == $args['status'] || $args['status'] == 'trialing' ) {
                $args['status'] = 'expired';
            }
        }

        do_action( 'wpinv_subscription_pre_create', $args, $data, $this );
        
        if ( !empty( $args ) ) {
            foreach ( $args as $key => $value ) {
                $this->update_meta( '_wpinv_subscr_' . $key, $value );
            }
        }

        do_action( 'wpinv_subscription_post_create', $args, $data, $this );

        return true;
    }
    
    public function update_subscription( $args = array() ) {
        if ( empty( $this->ID ) ) {
            return false;
        }

        if ( !empty( $args['expiration'] ) && $args['expiration'] && strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $args['expiration'], current_time( 'timestamp' ) ) ) {
            if ( !isset( $args['status'] ) || ( isset( $args['status'] ) && ( 'active' == $args['status'] || $args['status'] == 'trialing' ) ) ) {
                $args['status'] = 'expired';
            }
        }

        if ( isset( $args['status'] ) && $args['status'] == 'cancelled' && empty( $args['cancelled_on'] ) ) {
            $args['cancelled_on'] = date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
        }

        do_action( 'wpinv_subscription_pre_update', $args, $this );
        
        if ( !empty( $args ) ) {
            foreach ( $args as $key => $value ) {
                $this->update_meta( '_wpinv_subscr_' . $key, $value );
            }
        }

        do_action( 'wpinv_subscription_post_update', $args, $this );

        return true;
    }
    
    public function renew_subscription() {
        $parent_invoice = $this->get_parent_payment();
        $parent_invoice = empty( $parent_invoice ) ? $this : $parent_invoice;
        
        $current_time   = current_time( 'timestamp' );
        $start          = $this->get_subscription_created();
        $start          = $start ? strtotime( $start ) : $current_time;
        $expires        = $this->get_expiration_time();
        
        if ( !$expires ) {
            $expires    = strtotime( '+' . $parent_invoice->get_subscription_interval() . ' ' . $parent_invoice->get_subscription_period( true ), $start );
        }
        
        $expiration     = date_i18n( 'Y-m-d 23:59:59', $expires );
        $expiration     = apply_filters( 'wpinv_subscription_renewal_expiration', $expiration, $this->ID, $this );
        $bill_times     = $parent_invoice->get_bill_times();
        $times_billed   = $parent_invoice->get_total_payments();
        
        if ( $parent_invoice->get_subscription_status() == 'trialing' && ( $times_billed > 0 || strtotime( date_i18n( 'Y-m-d' ) ) < strtotime( $parent_invoice->get_trial_end_date( false ) ) ) ) {
            $args = array(
                'status'     => 'active',
            );

            $parent_invoice->update_subscription( $args );
        }
        
        do_action( 'wpinv_subscription_pre_renew', $this->ID, $expiration, $this );

        $status       = 'active';
        if ( $bill_times > 0 && $times_billed >= $bill_times ) {
            $this->complete_subscription();
            $status = 'completed';
        }

        $args = array(
            'expiration' => $expiration,
            'status'     => $status,
        );

        $this->update_subscription( $args );

        do_action( 'wpinv_subscription_post_renew', $this->ID, $expiration, $this );
        do_action( 'wpinv_recurring_set_subscription_status', $this->ID, $status, $this );
    }
    
    public function complete_subscription() {
        $args = array(
            'status' => 'completed'
        );

        if ( $this->update_subscription( $args ) ) {
            do_action( 'wpinv_subscription_completed', $this->ID, $this );
        }
    }
    
    public function expire_subscription() {
        $args = array(
            'status' => 'expired'
        );

        if ( $this->update_subscription( $args ) ) {
            do_action( 'wpinv_subscription_expired', $this->ID, $this );
        }
    }

    public function get_cancel_url() {
        $url = wp_nonce_url( add_query_arg( array( 'wpi_action' => 'cancel_subscription', 'sub_id' => $this->ID ) ), 'wpinv-recurring-cancel' );

        return apply_filters( 'wpinv_subscription_cancel_url', $url, $this );
    }

    public function can_update() {
        return apply_filters( 'wpinv_subscription_can_update', false, $this );
    }

    public function get_update_url() {
        $url = add_query_arg( array( 'action' => 'update', 'sub_id' => $this->ID ) );

        return apply_filters( 'wpinv_subscription_update_url', $url, $this );
    }

    public function is_parent() {
        $is_parent = empty( $this->parent_invoice ) ? true : false;

        return apply_filters( 'wpinv_invoice_is_parent', $is_parent, $this );
    }
    
    public function is_renewal() {
        $is_renewal = $this->parent_invoice && $this->parent_invoice != $this->ID ? true : false;

        return apply_filters( 'wpinv_invoice_is_renewal', $is_renewal, $this );
    }
    
    public function get_parent_payment() {
        $parent_payment = NULL;
        
        if ( $this->is_renewal() ) {
            $parent_payment = wpinv_get_invoice( $this->parent_invoice );
        }
        
        return $parent_payment;
    }
    
    public function is_subscription_active() {
        $ret = false;
        
        $subscription_status = $this->get_subscription_status();

        if( ! $this->is_subscription_expired() && ( $subscription_status == 'active' || $subscription_status == 'cancelled' || $subscription_status == 'trialing' ) ) {
            $ret = true;
        }

        return apply_filters( 'wpinv_subscription_is_active', $ret, $this->ID, $this );
    }

    public function is_subscription_expired() {
        $ret = false;
        $subscription_status = $this->get_subscription_status();

        if ( $subscription_status == 'expired' ) {
            $ret = true;
        } else if ( 'active' === $subscription_status || 'cancelled' === $subscription_status || 'trialing' == $subscription_status ) {
            $ret        = false;
            $expiration = $this->get_expiration_time();

            if ( $expiration && strtotime( 'NOW', current_time( 'timestamp' ) ) > $expiration ) {
                $ret = true;

                if ( 'active' === $subscription_status || 'trialing' === $subscription_status ) {
                    $this->expire_subscription();
                }
            }
        }

        return apply_filters( 'wpinv_subscription_is_expired', $ret, $this->ID, $this );
    }
    
    public function get_new_expiration( $item_id = 0, $trial = true ) {
        $item   = new WPInv_Item( $item_id );
        $interval = $item->get_recurring_interval();
        $period = $item->get_recurring_period( true );
        
        $expiration_time = strtotime( '+' . $interval . ' ' . $period );
        
        if ( $trial && $this->is_free_trial() && $item->has_free_trial() ) {
            $expiration_time += ( wpinv_period_in_days( $item->get_trial_interval(), $item->get_trial_period() ) * DAY_IN_SECONDS ) ;
        }

        return date_i18n( 'Y-m-d 23:59:59', $expiration_time );
    }
    
    public function get_subscription_data( $filed = '' ) {
        $fields = array( 'item_id', 'status', 'period', 'initial_amount', 'recurring_amount', 'interval', 'bill_times', 'trial_period', 'trial_interval', 'expiration', 'profile_id', 'created', 'cancelled_on' );
        
        $subscription_meta = array();
        foreach ( $fields as $field ) {
            $subscription_meta[ $field ] = $this->get_meta( '_wpinv_subscr_' . $field );
        }
        
        $item = $this->get_recurring( true );
        
        if ( !empty( $item ) ) {
            if ( empty( $subscription_meta['item_id'] ) ) {
                $subscription_meta['item_id'] = $item->ID;
            }
            if ( empty( $subscription_meta['period'] ) ) {
                $subscription_meta['period'] = $item->get_recurring_period();
            }
            if ( empty( $subscription_meta['interval'] ) ) {
                $subscription_meta['interval'] = $item->get_recurring_interval();
            }
            if ( $item->has_free_trial() ) {
                if ( empty( $subscription_meta['trial_period'] ) ) {
                    $subscription_meta['trial_period'] = $item->get_trial_period();
                }
                if ( empty( $subscription_meta['trial_interval'] ) ) {
                    $subscription_meta['trial_interval'] = $item->get_trial_interval();
                }
            } else {
                $subscription_meta['trial_period']      = '';
                $subscription_meta['trial_interval']    = 0;
            }
            if ( !$subscription_meta['bill_times'] && $subscription_meta['bill_times'] !== 0 ) {
                $subscription_meta['bill_times'] = $item->get_recurring_limit();
            }
            if ( $subscription_meta['initial_amount'] === '' || $subscription_meta['recurring_amount'] === '' ) {
                $subscription_meta['initial_amount']    = wpinv_round_amount( $this->get_total() );
                $subscription_meta['recurring_amount']  = wpinv_round_amount( $this->get_recurring_details( 'total' ) );
            }
        }
        
        if ( $filed === '' ) {
            return apply_filters( 'wpinv_get_invoice_subscription_data', $subscription_meta, $this );
        }
        
        $value = isset( $subscription_meta[$filed] ) ? $subscription_meta[$filed] : '';
        
        return apply_filters( 'wpinv_invoice_subscription_data_value', $value, $subscription_meta, $this );
    }
    
    public function is_paid() {
        if ( $this->has_status( array( 'publish', 'wpi-processing', 'wpi-renewal' ) ) ) {
            return true;
        }
        
        return false;
    }
    
    public function is_refunded() {
        $is_refunded = $this->has_status( array( 'wpi-refunded' ) );

        return apply_filters( 'wpinv_invoice_is_refunded', $is_refunded, $this );
    }
    
    public function is_free() {
        $is_free = false;
        
        if ( !( (float)wpinv_round_amount( $this->get_total() ) > 0 ) ) {
            if ( $this->is_parent() && $this->is_recurring() ) {
                $is_free = (float)wpinv_round_amount( $this->get_recurring_details( 'total' ) ) > 0 ? false : true;
            } else {
                $is_free = true;
            }
        }
        
        return apply_filters( 'wpinv_invoice_is_free', $is_free, $this );
    }
    
    public function has_vat() {
        global $wpinv_euvat, $wpi_country;
        
        $requires_vat = false;
        
        if ( $this->country ) {
            $wpi_country        = $this->country;
            
            $requires_vat       = $wpinv_euvat->requires_vat( $requires_vat, $this->get_user_id(), $wpinv_euvat->invoice_has_digital_rule( $this ) );
        }
        
        return apply_filters( 'wpinv_invoice_has_vat', $requires_vat, $this );
    }
    
    public function refresh_item_ids() {
        $item_ids = array();
        
        if ( !empty( $this->cart_details ) ) {
            foreach ( $this->cart_details as $key => $item ) {
                if ( !empty( $item['id'] ) ) {
                    $item_ids[] = $item['id'];
                }
            }
        }
        
        $item_ids = !empty( $item_ids ) ? implode( ',', array_unique( $item_ids ) ) : '';
        
        update_post_meta( $this->ID, '_wpinv_item_ids', $item_ids );
    }
    
    public function get_invoice_quote_type( $post_id ) {
        if ( empty( $post_id ) ) {
            return '';
        }

        $type = get_post_type( $post_id );

        if ( 'wpi_invoice' === $type ) {
            $post_type = __('Invoice', 'invoicing');
        } else{
            $post_type = __('Quote', 'invoicing');
        }

        return $post_type;
    }
}
