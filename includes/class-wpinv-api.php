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
            if ( ! isset( $data['invoice'] ) ) {
                throw new WPInv_API_Exception( 'wpinv_api_missing_invoice_data', sprintf( __( 'No %1$s data specified to create %1$s', 'invoicing' ), 'invoice' ), 400 );
            }

            $data = $data['invoice'];

            // permission check
            //if ( ! current_user_can( 'manage_options' ) ) {
                //throw new WPInv_API_Exception( 'wpinv_api_user_cannot_create_invoice', __( 'You do not have permission to create invoices', 'invoicing' ), 401 );
            //}

            $data = apply_filters( 'wpinv_api_create_invoice_data', $data, $this );

            $invoice = wpinv_insert_invoice( $data );
            if ( is_wp_error( $invoice ) ) {
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
    
    public function create_invoice( $data ) {
        global $wpdb;
        //wpinv_transaction_query( 'start' );

        try {
            if ( ! isset( $data['invoice'] ) ) {
                throw new WPInv_API_Exception( 'wpinv_api_missing_invoice_data', sprintf( __( 'No %1$s data specified to create %1$s', 'invoicing' ), 'invoice' ), 400 );
            }

            $data = $data['invoice'];

            // permission check
            //if ( ! current_user_can( 'manage_options' ) ) {
                //throw new WPInv_API_Exception( 'wpinv_api_user_cannot_create_invoice', __( 'You do not have permission to create invoices', 'invoicing' ), 401 );
            //}

            $data = apply_filters( 'wpinv_api_create_invoice_data', $data, $this );

            // default invoice args, note that status is checked for validity in wpinv_create_invoice()
            $default_invoice_args = array(
                'status'        => isset( $data['status'] ) ? $data['status'] : '',
                'user_note'     => isset( $data['note'] ) ? $data['note'] : null,
                'invoice_id'    => isset( $data['invoice_id'] ) ? (int)$data['invoice_id'] : 0,
            );

            // if creating invoice for existing user
            if ( ! empty( $data['user_id'] ) ) {
                // make sure user exists
                if ( false === get_user_by( 'id', $data['user_id'] ) ) {
                    throw new WPInv_API_Exception( 'wpinv_api_invalid_user_id', __( 'User ID is invalid', 'invoicing' ), 400 );
                }

                $default_invoice_args['user_id'] = $data['user_id'];
            }

            // create the pending invoice
            $invoice = $this->create_base_invoice( $default_invoice_args, $data );

            if ( is_wp_error( $invoice ) ) {
                throw new WPInv_API_Exception( 'wpinv_api_cannot_create_invoice', sprintf( __( 'Cannot create invoice: %s', 'invoicing' ), implode( ', ', $invoice->get_error_messages() ) ), 400 );
            }
            
            // Add note
            if ( !empty( $data['user_note'] ) ) {
                $invoice->add_note( $data['user_note'], true );
            }
            
            if ( !empty( $data['private_note'] ) ) {
                $invoice->add_note( $data['private_note'] );
            }

            // billing address
            $invoice = $this->set_billing_details( $invoice, $data );
            
            // items
            $invoice = $this->set_discount( $invoice, $data );

            // items
            $invoice = $this->set_items( $invoice, $data );

            // payment method (and payment_complete() if `paid` == true)
            if ( isset( $data['payment_details'] ) && is_array( $data['payment_details'] ) ) {
                // method ID & title are required
                if ( empty( $data['payment_details']['method_id'] ) || empty( $data['payment_details']['method_title'] ) ) {
                    throw new WPInv_API_Exception( 'wpinv_invalid_payment_details', __( 'Payment method ID and title are required', 'invoicing' ), 400 );
                }
                
                 // set invoice currency
                if ( isset( $data['payment_details']['currency'] ) ) {
                    if ( ! array_key_exists( $data['payment_details']['currency'], wpinv_get_currencies() ) ) {
                        throw new WPInv_API_Exception( 'wpinv_invalid_invoice_currency', __( 'Provided invoice currency is invalid', 'invoicing' ), 400 );
                    }

                    update_post_meta( $invoice->ID, '_wpinv_currency', $data['payment_details']['currency'] );
                    
                    $invoice->currency = $data['payment_details']['currency'];
                }
                
                update_post_meta( $invoice->ID, '_wpinv_gateway', $data['payment_details']['method_id'] );
                update_post_meta( $invoice->ID, '_wpinv_gateway_title', $data['payment_details']['method_title'] );
                
                $invoice->gateway = $data['payment_details']['method_id'];
                $invoice->gateway_title = $data['payment_details']['method_title'];

                // mark as paid if set
                if ( isset( $data['payment_details']['paid'] ) && true === $data['payment_details']['paid'] ) {
                    //$invoice->payment_complete( isset( $data['payment_details']['transaction_id'] ) ? $data['payment_details']['transaction_id'] : $invoice->ID );
                }
            }
          
            // set invoice meta
            if ( isset( $data['invoice_meta'] ) && is_array( $data['invoice_meta'] ) ) {
                $this->set_invoice_meta( $invoice->ID, $data['invoice_meta'] );
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
    
    protected function create_base_invoice( $args, $data ) {
        return wpinv_create_invoice( $args, $data );
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
            $invoice->set( 'discount', wpinv_format_amount( $data['discount'], NULL, true ) );
            
            update_post_meta( $invoice->ID, '_wpinv_discount', wpinv_format_amount( $data['discount'], NULL, true ) );
            
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
                $amount             = isset( $item['amount'] ) ? wpinv_format_amount( $item['amount'], NULL, true ) : 0;
                
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
                    'amount'            => $amount > 0 ? wpinv_format_amount( $amount, NULL, true ) : 0,
                    'subtotal'          => $amount > 0 ? wpinv_format_amount( $amount, NULL, true ) : 0,
                    'vat_rates_class'   => $vat_rates_class,
                    'vat_rate'          => $vat_rate > 0 ? wpinv_format_amount( $vat_rate, NULL, true ) : 0,
                    'tax'               => $tax > 0 ? wpinv_format_amount( $tax, NULL, true ) : 0,
                );
            }

            update_post_meta( $invoice->ID, '_wpinv_tax', wpinv_format_amount( $total_tax, NULL, true ) );
            $invoice->set( 'tax', wpinv_format_amount( $total_tax, NULL, true ) );
            
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