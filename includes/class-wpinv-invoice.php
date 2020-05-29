<?php
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

/**
 * Invoice class.
 */
class WPInv_Invoice {

    /**
     * @var int the invoice ID.
     */
    public $ID  = 0;

    /**
     * @var string the invoice title.
     */
    public $title;

    /**
     * @var string the invoice post type.
     */
    public $post_type;
    
    /**
     * @var array unsaved changes.
     */
    public $pending = array();

    /**
     * @var array Invoice items.
     */
    public $items = array();

    /**
     * @var array User info.
     */
    public $user_info = array();

    /**
     * @var array Payment meta.
     */
    public $payment_meta = array();
    
    /**
     * @var bool whether or not the invoice is saved.
     */
    public $new = false;

    /**
     * @var string Invoice number.
     */
    public $number = '';

    /**
     * @var string test or live mode.
     */
    public $mode = 'live';

    /**
     * @var string invoice key.
     */
    public $key = '';

    /**
     * @var float invoice total.
     */
    public $total = 0.00;

    /**
     * @var float invoice subtotal.
     */
    public $subtotal = 0;

    /**
     * @var int 0 = taxable, 1 not taxable.
     */
    public $disable_taxes = 0;

    /**
     * @var float invoice tax.
     */
    public $tax = 0;

    /**
     * @var array invoice fees.
     */
    public $fees = array();

    /**
     * @var float total fees.
     */
    public $fees_total = 0;

    /**
     * @var array invoice discounts.
     */
    public $discounts = '';

    /**
     * @var float total discount.
     */
    public $discount = 0;

    /**
     * @var string discount code.
     */
    public $discount_code = '';

    /**
     * @var string date created.
     */
    public $date = '';

    /**
     * @var string date due.
     */
    public $due_date = '';

    /**
     * @var string date it was completed.
     */
    public $completed_date = '';

    /**
     * @var string invoice status.
     */
    public $status = 'wpi-pending';

    /**
     * @var string invoice status.
     */
    public $post_status = 'wpi-pending';

    /**
     * @var string old invoice status.
     */
    public $old_status = '';

    /**
     * @var string formatted invoice status.
     */
    public $status_nicename = '';

    /**
     * @var int invoice user id.
     */
    public $user_id = 0;

    /**
     * @var string user first name.
     */
    public $first_name = '';

    /**
     * @var string user last name.
     */
    public $last_name = '';

    /**
     * @var string user email.
     */
    public $email = '';

    /**
     * @var string user phone number.
     */
    public $phone = '';

    /**
     * @var string user address.
     */
    public $address = '';

    /**
     * @var string user city.
     */
    public $city = '';

    /**
     * @var string user country.
     */
    public $country = '';

    /**
     * @var string user state.
     */
    public $state = '';

    /**
     * @var string user zip.
     */
    public $zip = '';

    /**
     * @var string transaction id.
     */
    public $transaction_id = '';

    /**
     * @var string user ip.
     */
    public $ip = '';

    /**
     * @var string gateway.
     */
    public $gateway = '';

    /**
     * @var string gateway title.
     */
    public $gateway_title = '';

    /**
     * @var string currency.
     */
    public $currency = '';

    /**
     * @var array cart_details.
     */
    public $cart_details = array();
    
    /**
     * @var string company.
     */
    public $company = '';

    /**
     * @var string vat number.
     */
    public $vat_number = '';

    /**
     * @var string vat rate.
     */
    public $vat_rate = '';

    /**
     * @var int whether or not the address is confirmed.
     */
    public $adddress_confirmed = '';

    /**
     * @var string full name.
     */
    public $full_name = '';

    /**
     * @var int parent.
     */
    public $parent_invoice = 0;

    /**
     * @param int|WPInv_Invoice|WP_Post $invoice The invoice.
     */
    public function __construct( $invoice = false ) {
        
        // Do we have an invoice?
        if ( empty( $invoice ) ) {
            return false;
        }

        $this->setup_invoice( $invoice );
    }

    /**
     * Retrieves an invoice key.
     */
    public function get( $key ) {
        if ( method_exists( $this, 'get_' . $key ) ) {
            $value = call_user_func( array( $this, 'get_' . $key ) );
        } else {
            $value = $this->$key;
        }

        return $value;
    }

     /**
     * Sets an invoice key.
     */
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

    /**
     * Checks if an invoice key is set.
     */
    public function _isset( $name ) {
        if ( property_exists( $this, $name) ) {
            return false === empty( $this->$name );
        } else {
            return null;
        }
    }

    /**
     * @param int|WPInv_Invoice|WP_Post $invoice The invoice.
     */
    private function setup_invoice( $invoice ) {
        global $wpdb;
        $this->pending = array();

        if ( empty( $invoice ) ) {
            return false;
        }

        if ( is_a( $invoice, 'WPInv_Invoice' ) ) {
            foreach ( get_object_vars( $invoice ) as $prop => $value ) {
                $this->$prop = $value;
            }
            return true;
        }

        // Retrieve post object.
        $invoice      = get_post( $invoice );

        if( ! $invoice || is_wp_error( $invoice ) ) {
            return false;
        }

        if( ! ( 'wpi_invoice' == $invoice->post_type OR 'wpi_quote' == $invoice->post_type ) ) {
            return false;
        }

        // Retrieve post data.
        $table = $wpdb->prefix . 'getpaid_invoices';
        $data  = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE post_id=%d", $invoice->ID )
        );

        do_action( 'wpinv_pre_setup_invoice', $this, $invoice->ID, $data );

        // Primary Identifier
        $this->ID              = absint( $invoice->ID );
        $this->post_type       = $invoice->post_type;

        $this->date            = $invoice->post_date;
        $this->status          = $invoice->post_status;

        if ( 'future' == $this->status ) {
            $this->status = 'publish';
        }

        $this->post_status     = $this->status;
        $this->parent_invoice  = $invoice->post_parent;
        $this->post_name       = $this->setup_post_name( $invoice );
        $this->status_nicename = $this->setup_status_nicename( $invoice->post_status );

        $this->setup_invoice_data( $data );

        // Other Identifiers
        $this->title           = ! empty( $invoice->post_title ) ? $invoice->post_title : $this->number;

        // Allow extensions to add items to this object via hook
        do_action( 'wpinv_setup_invoice', $this, $invoice->ID, $data );

        return true;
    }

    /**
     * @param stdClass $data The invoice data.
     */
    private function setup_invoice_data( $data ) {

        if ( empty( $data ) ) {
            $this->number = $this->setup_invoice_number( $data );
            return;
        }

        $data = map_deep( $data, 'maybe_unserialize' );

        $this->payment_meta    = $data->custom_meta;
        $this->due_date        = $data->due_date;
        $this->completed_date  = $data->completed_date;
        $this->mode            = $data->mode;

        // Items
        $this->fees            = $this->setup_fees();
        $this->cart_details    = ! empty( $this->payment_meta['cart_details'] ) ? $this->payment_meta['cart_details'] : array();
        $this->items           = ! empty( $this->payment_meta['items'] ) ? $this->payment_meta['items'] : array();

        // Currency Based
        $this->total           = $data->total;
        $this->disable_taxes   = (int) $data->disable_taxes;
        $this->tax             = $data->tax;
        $this->fees_total      = $data->fees_total;
        $this->subtotal        = $data->subtotal;
        $this->currency        = $data->currency;

        // Gateway based
        $this->gateway         = $data->gateway;
        $this->gateway_title   = $this->setup_gateway_title();
        $this->transaction_id  = $data->transaction_id;

        // User based
        $this->ip              = $data->user_ip;
        $this->user_info       = ! empty( $this->payment_meta['user_info'] ) ? $this->payment_meta['user_info'] : array();

        $this->first_name      = $data->first_name;
        $this->last_name       = $data->last_name;
        $this->company         = $data->company;
        $this->vat_number      = $data->vat_number;
        $this->vat_rate        = $data->vat_rate;
        $this->adddress_confirmed  = (int) $data->adddress_confirmed;
        $this->address         = $data->address;
        $this->city            = $data->city;
        $this->country         = $data->country;
        $this->state           = $data->state;
        $this->zip             = $data->zip;
        $this->phone           = ! empty( $this->user_info['phone'] ) ? $this->user_info['phone'] : '';

        $this->discounts       = ! empty( $this->user_info['discount'] ) ? $this->user_info['discount'] : '';
        $this->discount        = $data->discount;
        $this->discount_code   = $data->discount_code;

        // Other Identifiers
        $this->key             = $data->key;
        $this->number          = $this->setup_invoice_number( $data );

        $this->full_name       = trim( $this->first_name . ' '. $this->last_name );


        return true;
    }


    /**
     * Sets up the status nice name.
     */
    private function setup_status_nicename( $status ) {
        $all_invoice_statuses  = wpinv_get_invoice_statuses( true, true, $this );

        if ( $this->is_quote() && class_exists( 'Wpinv_Quotes_Shared' ) ) {
            $all_invoice_statuses  = Wpinv_Quotes_Shared::wpinv_get_quote_statuses();
        }
        $status   = isset( $all_invoice_statuses[$status] ) ? $all_invoice_statuses[$status] : __( $status, 'invoicing' );

        return apply_filters( 'setup_status_nicename', $status );
    }

    /**
     * Set's up the invoice number.
     */
    private function setup_invoice_number( $data ) {

        if ( ! empty( $data ) && ! empty( $data->number ) ) {
            return $data->number;
        }

        $number = $this->ID;

        if ( $this->status == 'auto-draft' && wpinv_sequential_number_active( $this->post_type ) ) {
            $next_number = wpinv_get_next_invoice_number( $this->post_type );
            $number      = $next_number;
        }
        
        return wpinv_format_invoice_number( $number, $this->post_type );

    }

    /**
     * Invoice's post name.
     */
    private function setup_post_name( $post = NULL ) {
        global $wpdb;
        
        $post_name = '';

        if ( !empty( $post ) ) {
            if( !empty( $post->post_name ) ) {
                $post_name = $post->post_name;
            } else if ( !empty( $post->ID ) ) {
                $post_name = wpinv_generate_post_name( $post->ID );

                $wpdb->update( $wpdb->posts, array( 'post_name' => $post_name ), array( 'ID' => $post->ID ) );
            }
        }

        $this->post_name = $post_name;
    }
    
    /**
     * Convert this to an array.
     */
    public function array_convert() {
        return get_object_vars( $this );
    }
    
    private function setup_fees() {
        $payment_fees = isset( $this->payment_meta['fees'] ) ? $this->payment_meta['fees'] : array();
        return $payment_fees;
    }

    private function setup_gateway_title() {
        $gateway_title = wpinv_get_gateway_checkout_label( $this->gateway );
        return $gateway_title;
    }
    
    private function insert_invoice() {

        if ( empty( $this->post_type ) ) {
            if ( !empty( $this->ID ) && $post_type = get_post_type( $this->ID ) ) {
                $this->post_type = $post_type;
            } else if ( !empty( $this->parent_invoice ) && $post_type = get_post_type( $this->parent_invoice ) ) {
                $this->post_type = $post_type;
            } else {
                $this->post_type = 'wpi_invoice';
            }
        }

        $invoice_number = $this->ID;
        if ( $number = $this->number ) {
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

        if ( ! empty( $invoice_id ) ) {
            $this->ID  = $invoice_id;
            $this->_ID = $invoice_id;

            $this->payment_meta = apply_filters( 'wpinv_payment_meta', $this->payment_meta, $payment_data );
            if ( ! empty( $this->payment_meta['fees'] ) ) {
                $this->fees = array_merge( $this->fees, $this->payment_meta['fees'] );
                foreach( $this->fees as $fee ) {
                    $this->increase_fees( $fee['amount'] );
                }
            }

            $this->pending['payment_meta'] = $this->payment_meta;
            $this->save();
        }

        return $this->ID;
    }

    /**
     * Saves special fields in our custom table.
     */
    public function save_special() {
        global $wpdb;

        $fields = array (
            'post_id'        => $this->ID,
            'number'         => $this->get_number(),
            'key'            => $this->get_key(),
            'type'           => str_replace( 'wpi_', '', $this->post_type ),
            'mode'           => $this->mode,
            'user_ip'        => $this->get_ip(),
            'first_name'     => $this->get_first_name(),
            'last_name'      => $this->get_last_name(),
            'address'        => $this->get_address(),
            'city'           => $this->city,
            'state'          => $this->state,
            'country'        => $this->country,
            'zip'            => $this->zip,
            'adddress_confirmed' => (int) $this->adddress_confirmed,
            'gateway'        => $this->get_gateway(),
            'transaction_id' => $this->get_transaction_id(),
            'currency'       => $this->get_currency(),
            'subtotal'       => $this->get_subtotal(),
            'tax'            => $this->get_tax(),
            'fees_total'     => $this->get_fees_total(),
            'total'          => $this->get_total(),
            'discount'       => $this->get_discount(),
            'discount_code'  => $this->get_discount_code(),
            'disable_taxes'  => $this->disable_taxes,
            'due_date'       => $this->get_due_date(),
            'completed_date' => $this->get_completed_date(),
            'company'        => $this->company,
            'vat_number'     => $this->vat_number,
            'vat_rate'       => $this->vat_rate,
            'custom_meta'    => $this->payment_meta
        );

        $table =  $wpdb->prefix . 'getpaid_invoices';

        $id = (int) $this->ID;

        if ( empty( $id ) ) {
            return;
        }

        if ( $wpdb->get_var( "SELECT `post_id` FROM $table WHERE `post_id`=$id" ) ) {

            $wpdb->update( $table, $fields, array( 'post_id' => $id ) );

        } else {

            $wpdb->insert( $table, $fields );

        }

        $table =  $wpdb->prefix . 'getpaid_invoice_items';
        $wpdb->delete( $table, array( 'post_id' => $id ) );

        foreach ( $this->get_cart_details() as $details ) {
            $fields = array(
                'post_id'          => $this->$id,
                'item_id'          => $details['id'],
                'item_name'        => $details['name'],
                'item_description' => empty( $details['meta']['description'] ) ? '' : $details['meta']['description'],
                'vat_rate'         => $details['vat_rate'],
                'vat_class'        => empty( $details['vat_class'] ) ? '_standard' : $details['vat_class'],
                'tax'              => $details['tax'],
                'item_price'       => $details['item_price'],
                'custom_price'     => $details['custom_price'],
                'quantity'         => $details['quantity'],
                'discount'         => $details['discount'],
                'subtotal'         => $details['subtotal'],
                'price'            => $details['price'],
                'meta'             => $details['meta'],
                'fees'             => $details['fees'],
            );

            $item_columns = array_keys ( $fields );

            foreach ( $fields as $key => $val ) {
                if ( is_null( $val ) ) {
                    $val = '';
                }
                $val = maybe_serialize( $val );
                $fields[ $key ] = $wpdb->prepare( '%s', $val );
            }

            $fields = implode( ', ', $fields );
            $item_rows[] = "($fields)";
        }

        $item_rows    = implode( ', ', $item_rows );
        $item_columns = implode( ', ', $item_columns );
        $wpdb->query( "INSERT INTO $table ($item_columns) VALUES $item_rows" );
    }

    public function save( $setup = false ) {
        global $wpi_session;
        
        $saved = false;
        if ( empty( $this->items ) ) {
            return $saved;
        }

        if ( empty( $this->key ) ) {
            $this->key = self::generate_key();
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
        if ( ! empty( $this->pending ) ) {
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
                    case 'first_name':
                        $this->user_info['first_name'] = $this->first_name;
                        break;
                    case 'last_name':
                        $this->user_info['last_name'] = $this->last_name;
                        break;
                    case 'phone':
                        $this->user_info['phone'] = $this->phone;
                        break;
                    case 'address':
                        $this->user_info['address'] = $this->address;
                        break;
                    case 'city':
                        $this->user_info['city'] = $this->city;
                        break;
                    case 'country':
                        $this->user_info['country'] = $this->country;
                        break;
                    case 'state':
                        $this->user_info['state'] = $this->state;
                        break;
                    case 'zip':
                        $this->user_info['zip'] = $this->zip;
                        break;
                    case 'company':
                        $this->user_info['company'] = $this->company;
                        break;
                    case 'vat_number':
                        $this->user_info['vat_number'] = $this->vat_number;
                        
                        $vat_info = $wpi_session->get( 'user_vat_data' );
                        if ( $this->vat_number && !empty( $vat_info ) && isset( $vat_info['number'] ) && isset( $vat_info['valid'] ) && $vat_info['number'] == $this->vat_number ) {
                            $adddress_confirmed = isset( $vat_info['adddress_confirmed'] ) ? $vat_info['adddress_confirmed'] : false;
                            $this->update_meta( '_wpinv_adddress_confirmed', (bool)$adddress_confirmed );
                            $this->user_info['adddress_confirmed'] = (bool)$adddress_confirmed;
                            $this->adddress_confirmed = (bool)$adddress_confirmed;
                        }
    
                        break;
                    case 'vat_rate':
                        $this->user_info['vat_rate'] = $this->vat_rate;
                        break;
                    case 'adddress_confirmed':
                        $this->user_info['adddress_confirmed'] = $this->adddress_confirmed;
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
                        break;
                    case 'discounts':
                        if ( ! is_array( $this->discounts ) ) {
                            $this->discounts = explode( ',', $this->discounts );
                        }

                        $this->user_info['discount'] = implode( ',', $this->discounts );
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

            $this->items    = array_values( $this->items );
            
            $new_meta = array(
                'items'         => $this->items,
                'cart_details'  => $this->cart_details,
                'fees'          => $this->fees,
                'currency'      => $this->currency,
                'user_info'     => $this->user_info,
            );

            $this->payment_meta = array_merge( $this->payment_meta, $new_meta );
            $this->pending      = array();
            $saved              = true;
        }

        $this->save_special();
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
        
        if ( empty( $fee['label'] ) ) {
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
        
        if ( ( ( is_user_logged_in() && wpinv_current_user_can_manage_invoicing() ) || $added_by_user ) && !$system ) {
            $user                 = get_user_by( 'id', get_current_user_id() );
            $comment_author       = $user->display_name;
            $comment_author_email = $user->user_email;
        } else {
            $comment_author       = 'System';
            $comment_author_email = 'system@';
            $comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) : 'noreply.com';
            $comment_author_email = sanitize_email( $comment_author_email );
        }

        do_action( 'wpinv_pre_insert_invoice_note', $this->ID, $note, $customer_type );

        $note_id = wp_insert_comment( wp_filter_comment( array(
            'comment_post_ID'      => $this->ID,
            'comment_content'      => $note,
            'comment_agent'        => 'WPInvoicing',
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

        if ( $old_status === $new_status && in_array( $new_status, array_keys( wpinv_get_invoice_statuses( true ) ) ) ) {
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
        
        // Do not update created date on invoice marked as paid.
        /*if ( $meta_key == '_wpinv_completed_date' && !empty( $meta_value ) ) {
            $args = array(
                'ID'                => $this->ID,
                'post_date'         => $meta_value,
                'edit_date'         => true,
                'post_date_gmt'     => get_gmt_from_date( $meta_value ),
                'post_modified'     => $meta_value,
                'post_modified_gmt' => get_gmt_from_date( $meta_value )
            );
            wp_update_post( $args );
        }*/
        
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
                        if ( wpinv_discount_is_recurring( $code, true ) && !$this->is_renewal() ) {
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
                    $cart_discount   = $this->discount;
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

	                $total = $data['subtotal'] - $data['discount'] + $data['tax'];
	                if ( $total < 0 ) {
		                $total = 0;
	                }

                    $data['subtotal'] = wpinv_round_amount( $cart_subtotal );
                    $data['discount'] = wpinv_round_amount( $cart_discount );
                    $data['tax']      = wpinv_round_amount( $cart_tax );
                    $data['total']    = wpinv_round_amount( $total );
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

    // Checks if the invoice is taxable. Does not check if taxes are enabled on the site.
    public function is_taxable() {
        return (int) $this->disable_taxes === 0;
    }

    public function get_tax( $currency = false ) {
        $tax = wpinv_round_amount( $this->tax );

        if ( $currency ) {
            $tax = wpinv_price( wpinv_format_amount( $tax, NULL, !$currency ), $this->get_currency() );
        }

        if ( ! $this->is_taxable() ) {
            $tax = wpinv_round_amount( 0.00 );
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
            //return false; // Invoice must contain at least one item.
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

        if ( ! empty( $this->cart_details ) ) {
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

                if ( ! $this->is_taxable() ) {
                    $tax = 0;
                }

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
            if ( $cart_subtotal < 0 ) {
                $cart_subtotal = 0;
            }
            if ( $cart_tax < 0 ) {
                $cart_tax = 0;
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

        if ( $this->has_status( $valid_invoice_statuses ) && ( $this->get_total() > 0 || $this->is_free_trial() || $this->is_free() || $this->is_initial_free() ) ) {
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

        return apply_filters( 'wpinv_invoice_is_free_trial', $is_free_trial, $this->cart_details, $this );
    }

    public function is_initial_free() {
        $is_initial_free = false;
        
        if ( ! ( (float)wpinv_round_amount( $this->get_total() ) > 0 ) && $this->is_parent() && $this->is_recurring() && ! $this->is_free_trial() && ! $this->is_free() ) {
            $is_initial_free = true;
        }

        return apply_filters( 'wpinv_invoice_is_initial_free', $is_initial_free, $this->cart_details );
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

    public function get_subscription_id() {
        $subscription_id = $this->get_meta( '_wpinv_subscr_profile_id', true );

        if ( empty( $subscription_id ) && !empty( $this->parent_invoice ) ) {
            $parent_invoice = wpinv_get_invoice( $this->parent_invoice );

            $subscription_id = $parent_invoice->get_meta( '_wpinv_subscr_profile_id', true );
        }
        
        return $subscription_id;
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
    
    public function is_paid() {
        $is_paid = $this->has_status( array( 'publish', 'wpi-processing', 'wpi-renewal' ) );

        return apply_filters( 'wpinv_invoice_is_paid', $is_paid, $this );
    }

    /**
     * Checks if this is a quote object.
     * 
     * @since 1.0.15
     */
    public function is_quote() {
        return 'wpi_quote' === $this->post_type;
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
        
        if ( ! empty( $this->cart_details ) ) {
            foreach ( array_keys( $this->cart_details ) as $item ) {
                if ( ! empty( $item['id'] ) ) {
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

        return apply_filters('get_invoice_type_label', $post_type, $post_id);
    }
}
