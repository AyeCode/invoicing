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

/**
 * This class is used to migrate invoices to the new store.
 */
final class WPInv_Legacy_Invoice {

    /**
     * Invoice id.
     */
    public $ID  = 0;

    /**
     * The title of the invoice. Usually the invoice number.
     */
    public $title;

    /**
     * The post type - either wpi_quote or wpi_invoice
     */
    public $post_type;

    /**
     * An array of pending changes.
     */
    public $pending;

    /**
     * An array of invoice items.
     */
    public $items = array();

    /**
     * Information on the invoice client.
     */
    public $user_info = array();

    /**
     * Payment information for the invoice.
     */
    public $payment_meta = array();

    /**
     * Whether this invoice exists in the db or not.
     */
    public $new = false;

    /**
     * The invoice number.
     */
    public $number = '';

    /**
     * Whether an actual payment occurred (live) or the transaction
     * happened in a sandbox environment (test). 
     */
    public $mode = 'live';

    /**
     * A unique key for this invoice.
     */
    public $key = '';

    /**
     * The invoice total.
     */
    public $total = 0.00;

    /**
     * The invoice subtotal.
     */
    public $subtotal = 0;

    /**
     * 1 to disable taxes and 0 otherwise.
     */
    public $disable_taxes = 0;

    /**
     * Total tax for this invoice.
     */
    public $tax = 0;

    /**
     * Other fees for the invoice.
     */
    public $fees = array();

    /**
     * Total amount of the fees.
     */
    public $fees_total = 0;

    /**
     * A comma separated array of discount codes.
     */
    public $discounts = '';

    /**
     * Total discount.
     */
    public $discount = 0;

    /**
     * Main discount code.
     */
    public $discount_code = 0;

    /**
     * The date the invoice was created.
     */
    public $date = '';

    /**
     * The date that the invoice will be due.
     */
    public $due_date = '';

    /**
     * The date the invoice was paid for.
     */
    public $completed_date = '';

    /**
     * The invoice status.
     */
    public $status      = 'wpi-pending';

    /**
     * Same as self::$status.
     */
    public $post_status = 'wpi-pending';

    /**
     * The old invoice status (used when transitioning statuses).
     */
    public $old_status = '';

    /**
     * A human readable status name.
     */
    public $status_nicename = '';

    /**
     * The user id of the invoice client.
     */
    public $user_id = 0;

    /**
     * The first name of the invoice client.
     */
    public $first_name = '';

    /**
     * The last name of the invoice client.
     */
    public $last_name = '';

    /**
     * The email address of the invoice client.
     */
    public $email = '';

    /**
     * The phone number of the invoice client.
     */
    public $phone = '';

    /**
     * The street address of the invoice client.
     */
    public $address = '';

    /**
     * The city of the invoice client.
     */
    public $city = '';

    /**
     * The country of the invoice client.
     */
    public $country = '';

    /**
     * The state of the invoice client.
     */
    public $state = '';

    /**
     * The postal code of the invoice client.
     */
    public $zip = '';

    /**
     * The transaction id of the invoice.
     */
    public $transaction_id = '';

    /**
     * The ip address of the invoice client.
     */
    public $ip = '';

    /**
     * The gateway used to pay the invoice.
     */
    public $gateway = '';

    /**
     * The title of the gateway used to pay for the invoice.
     */
    public $gateway_title = '';

    /**
     * The currency of the invoice.
     */
    public $currency = '';

    /**
     * The cart details of the invoice.
     */
    public $cart_details = array();

    /**
     * The company of the client.
     */
    public $company = '';

    /**
     * The vat number of the client.
     */
    public $vat_number = '';

    /**
     * The vat rate used on the invoice.
     */
    public $vat_rate = '';

    /**
     * Whether or not the client confirmed the address
     */
    public $adddress_confirmed = '';
    
    /**
     * The full name of the client.
     */
    public $full_name = '';

    /**
     * The parent invoice id of this invoice.
     */
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

        $invoice = get_post( $invoice_id );

        if ( ! $invoice || is_wp_error( $invoice ) ) {
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

        if ( 'future' == $this->status ) {
            $this->status = 'publish';
        }

        $this->post_status     = $this->status;
        $this->mode            = $this->setup_mode();
        $this->parent_invoice  = $invoice->post_parent;
        $this->post_name       = $this->setup_post_name( $invoice );
        $this->status_nicename = $this->setup_status_nicename( $invoice->post_status );

        // Items
        $this->fees            = $this->setup_fees();
        $this->cart_details    = $this->setup_cart_details();
        $this->items           = $this->setup_items();

        // Currency Based
        $this->total           = $this->setup_total();
        $this->disable_taxes   = $this->setup_is_taxable();
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

    private function setup_status_nicename( $status ) {
        return $status;
    }

    private function setup_post_name( $post ) {
        $this->post_name = $post->post_name;
    }
    
    private function setup_due_date() {
        $due_date = $this->get_meta( '_wpinv_due_date' );
        
        if ( empty( $due_date ) ) {
            $overdue_time = strtotime( $this->date ) + ( DAY_IN_SECONDS * absint( wpinv_get_option( 'overdue_days', 0 ) ) );
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
        
        if ( $tax < 0 || ! $this->is_taxable() ) {
            $tax = 0;
        }

        return $tax;
    }

    /**
     * If taxes are enabled, allow users to enable/disable taxes per invoice.
     */
    private function setup_is_taxable() {
        return (int) $this->get_meta( '_wpinv_disable_taxes', true );
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
                if ( wpinv_sequential_number_active( $this->post_type ) ) {
                    $next_number = wpinv_get_next_invoice_number( $this->post_type );
                    $number      = $next_number;
                }
            }
            
            $number = wpinv_format_invoice_number( $number, $this->post_type );
        }

        return $number;
    }

    public function save() {}
    
    public function add_fee( $args ) {
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

    public function update_meta() {}

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

    public function get_recurring_details() {}

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
    
    public function get_fees_total() {
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
    
    public function get_gateway_title() {}
    
    public function get_currency() {
        return apply_filters( 'wpinv_currency_code', $this->currency, $this->ID, $this );
    }
    
    public function get_created_date() {
        return apply_filters( 'wpinv_created_date', $this->date, $this->ID, $this );
    }
    
    public function get_due_date( $display = false ) {
        $due_date = apply_filters( 'wpinv_due_date', $this->due_date, $this->ID, $this );

        if ( ! $display ) {
            return $due_date;
        }
        
        return getpaid_format_date( $this->due_date );
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
            $invoice_date   = getpaid_format_date( $invoice_date );
        }

        return apply_filters( 'wpinv_get_invoice_date', $invoice_date, $formatted, $this->ID, $this );
    }
    
    public function get_ip() {
        return apply_filters( 'wpinv_user_ip', $this->ip, $this->ID, $this );
    }
        
    public function has_status( $status ) {
        return apply_filters( 'wpinv_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
    }

    public function add_item() {}

    public function remove_item() {}
    
    public function update_items() {}

    public function recalculate_totals() {}

    public function needs_payment() {}

    public function get_checkout_payment_url() {}

    public function get_view_url() {}

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

    public function is_initial_free() {}

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

    public function get_subscription_name() {}

    public function get_subscription_id() {}

    public function is_parent() {
        return ! empty( $this->parent_invoice );
    }

    public function is_renewal() {}

    public function get_parent_payment() {}

    public function is_paid() {}

    public function is_quote() {}

    public function is_refunded() {}

    public function is_free() {
        $total = (float) wpinv_round_amount( $this->get_total() );
        return $total > 0 && ! $this->is_recurring();
    }

    public function has_vat() {}

    public function refresh_item_ids() {}

    public function get_invoice_quote_type() {}

}
