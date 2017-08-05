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

class WPInv_API {
    protected $post_type = 'wpi_invoice';
    
    public function __construct( $params = array() ) {
    }
    public function insert_invoice( $data ) {
        global $wpdb;
        //wpinv_transaction_query( 'start' );

        try {
            if ( empty( $data['invoice'] ) ) {
                throw new WPInv_API_Exception( 'wpinv_api_missing_invoice_data', sprintf( __( 'No %1$s data specified to create %1$s', 'invoicing' ), 'invoice' ), 400 );
            }

            $data = apply_filters( 'wpinv_api_create_invoice_data', $data['invoice'], $this );

            $invoice = wpinv_insert_invoice( $data, true );
            if ( empty( $invoice->ID ) || is_wp_error( $invoice ) ) {
                throw new WPInv_API_Exception( 'wpinv_api_cannot_create_invoice', sprintf( __( 'Cannot create invoice: %s', 'invoicing' ), implode( ', ', $invoice->get_error_messages() ) ), 400 );
            }

            // HTTP 201 Created
            $this->send_status( 201 );

            do_action( 'wpinv_api_create_invoice', $invoice->ID, $data, $this );

            //wpinv_transaction_query( 'commit' );

            return wpinv_get_invoice( $invoice->ID );
        } catch ( WPInv_API_Exception $e ) {
            //wpinv_transaction_query( 'rollback' );

            return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
        }
    }
    
    public function send_status( $code ) {
        status_header( $code );
    }
    
    protected function set_billing_details( $invoice, $data ) {
        $address_fields = array(
            'user_id',
            'first_name',
            'last_name',
            'company',
            'vat_number',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'country',
            'zip',
        );

        $billing_details = array();
        $user_id = $invoice->get_user_id();
        
        foreach ( $address_fields as $field ) {
            if ( isset( $data['billing_details'][ $field ] ) ) {
                $value = sanitize_text_field( $data['billing_details'][ $field ] );
                
                if ( $field == 'country' && empty( $value ) ) {
                    if ( !empty( $invoice->country ) ) {
                        $value = $invoice->country;
                    } else {
                        $value = wpinv_default_billing_country( '', $user_id );
                    }
                }
                
                if ( $field == 'state' && empty( $value ) ) {
                    if ( !empty( $invoice->state ) ) {
                        $value = $invoice->state;
                    } else {
                        $value = wpinv_get_default_state();
                    }
                }
                
                $invoice->set( $field, $value );
                
                update_post_meta( $invoice->ID, '_wpinv_' . $field, $value );
            }
        }
        
        return $invoice;
    }
    
    protected function set_discount( $invoice, $data ) {
        if ( isset( $data['discount'] ) ) {
            $invoice->set( 'discount', wpinv_round_amount( $data['discount'] ) );
            
            update_post_meta( $invoice->ID, '_wpinv_discount', wpinv_round_amount( $data['discount'] ) );
            
            if ( isset( $data['discount_code'] ) ) {
                $invoice->set( 'discount_code', $data['discount_code'] );
                
                update_post_meta( $invoice->ID, '_wpinv_discount_code', $data['discount_code'] );
            }
        }
        
        return $invoice;
    }
    
    protected function set_items( $invoice, $data ) {
        if ( !empty( $data['items'] ) && is_array( $data['items'] ) ) {
            $items_array = array();
           
            if ( !empty( $invoice->country ) ) {
                $country = $invoice->country;
            } else if ( !empty( $data['billing_details']['country'] ) ) {
                $country = $data['billing_details']['country'];
            } else {
                $country = wpinv_default_billing_country( '', $invoice->get_user_id() );
            }
            
            if ( !empty( $invoice->state ) ) {
                $state = $invoice->state;
            } else if ( !empty( $data['billing_details']['state'] ) ) {
                $state = $data['billing_details']['state'];
            } else {
                $state = wpinv_get_default_state();
            }
            
            $_POST['country']   = $country;
            $_POST['state']     = $state;
            
            $rate = wpinv_get_tax_rate( $country, $state, 'global' );
            
            $total_tax = 0;
            foreach ( $data['items'] as $item ) {
                $id                 = isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '';
                $title              = isset( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
                $desc               = isset( $item['description'] ) ? sanitize_text_field( $item['description'] ) : '';
                $amount             = isset( $item['amount'] ) ? wpinv_round_amount( $item['amount'] ) : 0;
                
                if ( !empty( $item['vat_rates_class'] ) ) {
                    $vat_rates_class = $item['vat_rates_class'];
                } else {
                    $vat_rates_class = '_standard';
                }
                $vat_rate = wpinv_get_tax_rate( $country, $state, $id );
                
                $tax = $amount > 0 ? ( $amount * 0.01 * (float)$vat_rate ) : 0;
                $total_tax += $tax;
                
                $items_array[] = array(
                    'id'                => $id,
                    'title'             => esc_html( $title ),
                    'description'       => esc_html( $desc ),
                    'amount'            => $amount > 0 ? wpinv_round_amount( $amount ) : 0,
                    'subtotal'          => $amount > 0 ? wpinv_round_amount( $amount ) : 0,
                    'vat_rates_class'   => $vat_rates_class,
                    'vat_rate'          => $vat_rate,
                    'tax'               => $tax > 0 ? wpinv_round_amount( $tax ) : 0,
                );
            }

            update_post_meta( $invoice->ID, '_wpinv_tax', wpinv_round_amount( $total_tax ) );
            $invoice->set( 'tax', wpinv_round_amount( $total_tax ) );
            
            $items_array = apply_filters( 'wpinv_save_invoice_items', $items_array, $data['items'], $invoice );
            
            $invoice->set( 'items', $items_array );
            update_post_meta( $invoice->ID, '_wpinv_items', $items_array );
        }
        
        return $invoice;
    }
    
    protected function set_invoice_meta( $invoice_id, $invoice_meta ) {
        foreach ( $invoice_meta as $meta_key => $meta_value ) {

            if ( is_string( $meta_key) && ! is_protected_meta( $meta_key ) && is_scalar( $meta_value ) ) {
                update_post_meta( $invoice_id, $meta_key, $meta_value );
            }
        }
    }
}


class WPInv_API_Exception extends Exception {
    protected $error_code;

    public function __construct( $error_code, $error_message, $http_status_code ) {
        $this->error_code = $error_code;
        parent::__construct( $error_message, $http_status_code );
    }

    public function getErrorCode() {
        return $this->error_code;
    }
}