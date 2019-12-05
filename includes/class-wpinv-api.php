<?php
/**
 * Contains the main API class
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit;
}

/**
 * The main API class
 */
class WPInv_API {

    /**
     * @param string A prefix for our REST routes
     */
    protected $api_namespace    = '';
    
    /**
     * Class constructor. 
     * 
     * @since 1.0.13
     * Sets the API namespace and inits hooks
     */
    public function __construct( $api_namespace = 'invoicing/v1' ) {
        $this->api_namespace = apply_filters( 'invoicing_api_namespace', $api_namespace );

        //Register REST routes
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }


	/**
	 * Registers routes
	 *
     * @since 1.0.13
	 */
	public function register_rest_routes() {
		
		//Invoices
		register_rest_route(
			$this->api_namespace,
			'/invoices',
			array(

				//Create a single invoice
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'insert_invoice' ),
					'permission_callback' => array( $this, 'can_manage_options' ),
                ),
				
			)
        );
        
    }
    
    /**
     * Checks if the current user can manage options
     * 
     * @since 1.0.13
     * @param WP_REST_Request $request
     */
    public function can_manage_options( $request ) {
		return current_user_can( 'manage_options' );
    }

    /**
     * Creates a new invoice
     * 
     *  @param WP_REST_Request $request
     *  @return mixed WP_Error or invoice data
     */
    public function insert_invoice( $request ) {
        
        // Fetch invoice data from the request
        $invoice_data = wp_unslash( $request->get_params() );

        // Abort if no invoice data is provided
        if( empty( $invoice_data ) ) {
            return new WP_Error( 'missing_data', __( 'Invoice data not provided', 'invoicing' ) );
        }

        // Try creating the invoice
        $invoice = wpinv_insert_invoice( $invoice_data, true );

        if ( is_wp_error( $invoice ) ) {
            return $invoice;
        }

        // Fetch invoice data ...
        $invoice_data = get_object_vars( $invoice );

        // ... and formart some of it
        foreach( $invoice_data as $key => $value ) {
            $invoice_data[ $key ] = $invoice->get( $key );
        }

        //Return the invoice data
        return rest_ensure_response( $invoice_data );

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