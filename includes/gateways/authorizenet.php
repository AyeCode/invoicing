<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpinv_authorizenet_cc_form( $invoice_id ) {
    $invoice = wpinv_get_invoice( $invoice_id );
    $cc_owner = !empty( $invoice ) ? esc_attr( $invoice->get_user_full_name() ) : '';
    ?>
    <div id="authorizenet_cc_form" class="form-horizontal wpi-cc-form panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><?php _e( 'Card Details', 'invoicing' ) ;?></h3></div>
        <div class="panel-body">
            <div class="form-group required">
              <label for="auth-input-cc-owner" class="col-sm-4 control-label"><?php _e( 'Card Owner', 'invoicing' ) ;?></label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="auth-input-cc-owner" placeholder="<?php esc_attr_e( 'Card Owner', 'invoicing' ) ;?>" value="<?php echo $cc_owner;?>" name="authorizenet[cc_owner]">
              </div>
            </div>
            <div class="form-group required">
              <label for="auth-input-cc-number" class="col-sm-4 control-label"><?php _e( 'Card Number', 'invoicing' ) ;?></label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="auth-input-cc-number" placeholder="<?php esc_attr_e( 'Card Number', 'invoicing' ) ;?>" value="" name="authorizenet[cc_number]">
              </div>
            </div>
            <div class="form-group required">
              <label for="auth-input-cc-expire-date" class="col-sm-4 control-label"><?php _e( 'Card Expiry Date', 'invoicing' ) ;?></label>
              <div class="col-sm-2">
                <select class="form-control" id="auth-input-cc-expire-date" name="authorizenet[cc_expire_month]">
                    <?php for ( $i = 1; $i <= 12; $i++ ) { $value = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
                    <option value="<?php echo $value;?>"><?php echo $value;?></option>
                    <?php } ?>
                </select>
               </div>
               <div class="col-sm-2">
                <select class="form-control" name="authorizenet[cc_expire_year]">
                    <?php $year = date( 'Y' ); for ( $i = $year; $i <= ( $year + 10 ); $i++ ) { ?>
                    <option value="<?php echo $i;?>"><?php echo $i;?></option>
                    <?php } ?>
                </select>
              </div>
            </div>
            <div class="form-group required">
              <label for="auth-input-cc-cvv2" class="col-sm-4 control-label"><?php _e( 'Card Security Code (CVV2)', 'invoicing' ) ;?></label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="auth-input-cc-cvv2" placeholder="<?php esc_attr_e( 'Card Security Code (CVV2)', 'invoicing' ) ;?>" value="" name="authorizenet[cc_cvv2]"">
              </div>
            </div>
      </div>
    </div>
    <?php
}
add_action( 'wpinv_authorizenet_cc_form', 'wpinv_authorizenet_cc_form', 10, 1 );

function wpinv_process_authorizenet_payment_old( $purchase_data ) {
    if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'wpi-gateway' ) ) {
        wp_die( __( 'Nonce verification has failed', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }
    
    // Collect payment data
    $payment_data = array(
        'price'         => $purchase_data['price'],
        'date'          => $purchase_data['date'],
        'user_email'    => $purchase_data['user_email'],
        'invoice_key'   => $purchase_data['invoice_key'],
        'currency'      => wpinv_get_currency(),
        'items'         => $purchase_data['items'],
        'user_info'     => $purchase_data['user_info'],
        'cart_details'  => $purchase_data['cart_details'],
        'gateway'       => 'authorizenet',
        'status'        => 'pending'
    );

    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );
    
    if ( !empty( $invoice ) ) {
        $invoice_id = $invoice->ID;
        $quantities_enabled = wpinv_item_quantities_enabled();
        $use_taxes          = wpinv_use_taxes();
        
        $authorizenet_card  = !empty( $_POST['authorizenet'] ) ? $_POST['authorizenet'] : array();
        $card_defaults      = array(
            'cc_owner'          => $invoice->get_user_full_name(),
            'cc_number'         => false,
            'cc_expire_month'   => false,
            'cc_expire_year'    => false,
            'cc_cvv2'           => false,
        );
        $authorizenet_card = wp_parse_args( $authorizenet_card, $card_defaults );
        
        $data = array();
        $data['x_login']            = wpinv_get_option( 'authorizenet_login_id' );
        $data['x_tran_key']         = wpinv_get_option( 'authorizenet_transaction_key' );
        $data['x_version']          = '3.1';
        $data['x_delim_data']       = 'true';
        $data['x_delim_char']       = '|';
        $data['x_encap_char']       = '"';
        $data['x_relay_response']   = 'false';
        $data['x_first_name']       = $invoice->get_first_name();
        $data['x_last_name']        = $invoice->get_last_name();
        $data['x_company']          = $invoice->company;
        $data['x_address']          = wp_strip_all_tags( $invoice->get_address(), true );
        $data['x_city']             = $invoice->city;
        $data['x_state']            = $invoice->state;
        $data['x_zip']              = $invoice->zip;
        $data['x_country']          = $invoice->country;
        $data['x_phone']            = $invoice->phone;
        $data['x_customer_ip']      = wpinv_get_ip();
        $data['x_email']            = $invoice->get_email();
        $data['x_amount']           = wpinv_sanitize_amount( $invoice->get_total() );
        $data['x_currency_code']    = wpinv_get_currency();
        // Card details
        $data['x_method']           = 'CC';
        $data['x_type']             = 'AUTH_CAPTURE'; // AUTH_CAPTURE or AUTH_ONLY
        $data['x_card_num']         = str_replace( ' ', '', $authorizenet_card['cc_number'] );
        $data['x_exp_date']         = $authorizenet_card['cc_expire_month'] . $authorizenet_card['cc_expire_year'];
        $data['x_card_code']        = $authorizenet_card['cc_cvv2'];
        $data['x_invoice_num']      = $invoice->ID;
        
        if ( wpinv_is_test_mode( 'authorizenet' ) ) {
            $data['x_test_request'] = 'true';
            $data['x_solution_id']  = 'AAA100302';
        }
        if ( $use_taxes && $invoice->get_tax() > 0 ) {
            $data['x_tax']  = $invoice->get_tax();
        }
        
        $line_items      = array();
        $item_desc       = array();
        foreach ( $invoice->get_cart_details() as $item ) {            
            $quantity       = $quantities_enabled && !empty( $item['quantity'] ) && $item['quantity'] > 0 ? $item['quantity'] : 1;
            $line_items[]   = $item['id'] . '<|>' . $item['name'] . '<|><|>' . $quantity . '<|>' . $item['item_price'] . '<|>' . ( $use_taxes && !empty( $item['tax'] ) && $item['tax'] > 0 ? 'Y' : 'N' );
            $item_desc[]    = $item['name'] . ' (' . $quantity . 'x ' . wpinv_price( wpinv_format_amount( $item['item_price'] ) ) . ')';
        }
        $item_desc = '#' . $invoice->get_number() . ': ' . implode( ', ', $item_desc );
        if ( $use_taxes && $invoice->get_tax() > 0 ) {
            $item_desc .= ', ' . wp_sprintf( __( 'Tax: %s', 'invoicing' ), $invoice->get_tax( true ) );
        }
        $data['x_description']  = html_entity_decode( $item_desc , ENT_QUOTES, 'UTF-8' );
        
        // Send payment request
        $response   = wpinv_authorizenet_send_request( $data, $line_items );
        
        // Empty the shopping cart
        wpinv_empty_cart();
        
        if ( !empty( $response ) ) {
            if ( !empty( $response['response_code'] ) ) {
                switch ( (int)$response['response_code'] ) {
                    case 1: // Approved 
                        $message = array();
                        if ( isset( $response['authorization_code'] ) ) {
                            $message[] = wp_sprintf( __( 'Authorization Code: %s', 'invoicing' ), $response['authorization_code'] );
                        }
                        if ( isset( $response['avs_response'] ) ) {
                            $message[] = wp_sprintf( __( 'AVS Response: %s', 'invoicing' ), $response['avs_response'] );
                        }
                        if ( isset( $response['transaction_id'] ) ) {
                            $message[] = wp_sprintf( __( 'Transaction ID: %s', 'invoicing' ), $response['transaction_id'] );
                        }
                        if ( isset( $response['card_code_response'] ) ) {
                            $message[] = wp_sprintf( __( 'Card Code Response: %s', 'invoicing' ), $response['card_code_response'] );
                        }
                        if ( isset( $response_info['cavv_response'] ) ) {
                            $message[] = wp_sprintf( __( 'Cardholder Authentication Verification Response: %s', 'invoicing' ), $response_info['cavv_response'] );
                        }
                        
                        wpinv_update_payment_status( $invoice_id, 'publish' );
                        wpinv_set_payment_transaction_id( $invoice_id, $response['transaction_id'] );
                        wpinv_insert_payment_note( $invoice_id, sprintf( __( 'AUTHORIZE.NET PAYMENT: %s', 'invoicing' ) , implode( "<br>", $message ) ) );
                        // Redirect
                        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
                    break;
                    case 2: // Declined
                        wpinv_record_gateway_error( __( 'Payment cancelled', 'invoicing' ), $response['error_message'], $invoice_id );
                        wpinv_update_payment_status( $invoice_id, 'cancelled' );
                        wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Payment cancelled %s', 'invoicing' ), $response['error_message'] ) );
                    break;
                    case 4: // Held for Review 
                        wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Payment pending %s', 'invoicing' ), $response['error_message'] ) );
                        // Redirect
                        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
                    break;
                    case 3: // Error  
                    default:
                        wpinv_record_gateway_error( __( 'Payment failed', 'invoicing' ), $response['error_message'], $invoice_id );
                        wpinv_update_payment_status( $invoice_id, 'failed' );
                        wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Payment failed %s', 'invoicing' ), $response['error_message'] ) );
                    break;
                }
            }
        }
        
        wpinv_send_to_failed_page( '?invoice-id=' . $invoice_id );
    } else {
        wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed while processing a Authorize.net payment. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
        // If errors are present, send the user back to the purchase page so they can be corrected
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    }
}

function wpinv_process_authorizenet_payment( $purchase_data ) {
    if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'wpi-gateway' ) ) {
        wp_die( __( 'Nonce verification has failed', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }
    
    // Collect payment data
    $payment_data = array(
        'price'         => $purchase_data['price'],
        'date'          => $purchase_data['date'],
        'user_email'    => $purchase_data['user_email'],
        'invoice_key'   => $purchase_data['invoice_key'],
        'currency'      => wpinv_get_currency(),
        'items'         => $purchase_data['items'],
        'user_info'     => $purchase_data['user_info'],
        'cart_details'  => $purchase_data['cart_details'],
        'gateway'       => 'authorizenet',
        'status'        => 'pending'
    );

    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );
    
    $errors = wpinv_get_errors();
    
    if ( empty( $errors ) ) {
        if ( !empty( $invoice ) ) {
            $invoice_id = $invoice->ID;
            $quantities_enabled = wpinv_item_quantities_enabled();
            $use_taxes          = wpinv_use_taxes();
            
            $authorizenet_card  = !empty( $_POST['authorizenet'] ) ? $_POST['authorizenet'] : array();
            $card_defaults      = array(
                'cc_owner'          => $invoice->get_user_full_name(),
                'cc_number'         => false,
                'cc_expire_month'   => false,
                'cc_expire_year'    => false,
                'cc_cvv2'           => false,
            );
            $authorizenet_card = wp_parse_args( $authorizenet_card, $card_defaults );
            
            $authorizeAIM = wpinv_authorizenet_AIM();
            $authorizeAIM->first_name       = $invoice->get_first_name();
            $authorizeAIM->last_name        = $invoice->get_last_name();
            $authorizeAIM->company          = $invoice->company;
            $authorizeAIM->address          = wp_strip_all_tags( $invoice->get_address(), true );
            $authorizeAIM->city             = $invoice->city;
            $authorizeAIM->state            = $invoice->state;
            $authorizeAIM->zip              = $invoice->zip;
            $authorizeAIM->country          = $invoice->country;
            $authorizeAIM->phone            = $invoice->phone;
            $authorizeAIM->email            = $invoice->get_email();
            $authorizeAIM->amount           = wpinv_sanitize_amount( $invoice->get_total() );
            $authorizeAIM->card_num         = str_replace( ' ', '', sanitize_text_field( $authorizenet_card['cc_number'] ) );
            $authorizeAIM->exp_date         = sanitize_text_field( $authorizenet_card['cc_expire_month'] ) . '/' . sanitize_text_field( $authorizenet_card['cc_expire_year'] );
            $authorizeAIM->card_code        = sanitize_text_field( $authorizenet_card['cc_cvv2'] );
            $authorizeAIM->invoice_num      = $invoice->ID;
            
            if ( $use_taxes && $invoice->get_tax() > 0 ) {
                $authorizeAIM->tax  = $invoice->get_tax(); // TODO
            }
            
            $line_items      = array();
            $item_desc       = array();
            
            foreach ( $invoice->get_cart_details() as $item ) {            
                $quantity       = $quantities_enabled && !empty( $item['quantity'] ) && $item['quantity'] > 0 ? $item['quantity'] : 1;
                $line_items[]   = $item['id'] . '<|>' . $item['name'] . '<|><|>' . $quantity . '<|>' . $item['item_price'] . '<|>' . ( $use_taxes && !empty( $item['tax'] ) && $item['tax'] > 0 ? 'Y' : 'N' );
                $item_desc[]    = $item['name'] . ' (' . $quantity . 'x ' . wpinv_price( wpinv_format_amount( $item['item_price'] ) ) . ')';
            }
            
            $item_desc = '#' . $invoice->get_number() . ': ' . implode( ', ', $item_desc );
            
            if ( $use_taxes && $invoice->get_tax() > 0 ) {
                $item_desc .= ', ' . wp_sprintf( __( 'Tax: %s', 'invoicing' ), $invoice->get_tax( true ) );
            }
            
            $authorizeAIM->description  = html_entity_decode( $item_desc , ENT_QUOTES, 'UTF-8' );
            
            // Send payment request
            $response   = wpinv_authorizenet_send_request( $data, $line_items );
            
            try {
                $response = $transaction->authorizeAndCapture();
                
                if ( !empty( $response ) && $response->approved ) {
                    // Empty the shopping cart
                    wpinv_empty_cart();
            
                    wpinv_update_payment_status( $invoice_id, 'publish' );
                    wpinv_set_payment_transaction_id( $invoice_id, $response->transaction_id );
                    
                    $message = array();
                    if ( isset( $response->authorization_code ) ) {
                        $message[] = wp_sprintf( __( 'Authorization Code: %s', 'invoicing' ), $response->authorization_code );
                    }
                    if ( isset( $response->avs_response ) ) {
                        $message[] = wp_sprintf( __( 'AVS Response: %s', 'invoicing' ), $response->avs_response );
                    }
                    if ( isset( $response->transaction_id ) ) {
                        $message[] = wp_sprintf( __( 'Transaction ID: %s', 'invoicing' ), $response->transaction_id );
                    }
                    if ( isset( $response->card_code_response ) ) {
                        $message[] = wp_sprintf( __( 'Card Code Response: %s', 'invoicing' ), $response->card_code_response );
                    }
                    if ( isset( $response->cavv_response ) ) {
                        $message[] = wp_sprintf( __( 'Cardholder Authentication Verification Response: %s', 'invoicing' ), $response->cavv_response );
                    }
                            
                    wpinv_insert_payment_note( $invoice_id, sprintf( __( 'AUTHORIZE.NET PAYMENT: %s', 'invoicing' ) , implode( "<br>", $message ) ) );
                    
                    wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
                } else {
                    if ( isset( $response->response_reason_text ) ) {
                        $error = $response->response_reason_text;
                    } elseif ( isset( $response->error_message ) ) {
                        $error = $response->error_message;
                    } else {
                        $error = '';
                    }
                    
                    $errorMsg = '';
                    if ( strpos( strtolower( $error ), 'the credit card number is invalid' ) !== false ) {
                        $errorMsg = __( 'Your card number is invalid!', 'invoicing' );
                        wpinv_set_error( 'invalid_card', $errorMsg );
                    } elseif( strpos( strtolower( $error ), 'this transaction has been declined' ) !== false ) {
                        $errorMsg = __( 'Your card has been declined!', 'invoicing' );
                        wpinv_set_error( 'invalid_card', $errorMsg );
                    } elseif( isset( $response->response_reason_text ) ) {
                        $errorMsg = __( $response->response_reason_text, 'invoicing' );
                        wpinv_set_error( 'api_error', $errorMsg );
                    } elseif( isset( $response->error_message ) ) {
                        $errorMsg = __( $response->error_message, 'invoicing' );
                        wpinv_set_error( 'api_error', $errorMsg );
                    } else {
                        $errorMsg = sprintf( __( 'An error occurred. Error data: %s!', 'invoicing' ), print_r( $response, true ) );
                        wpinv_set_error( 'api_error', $errorMsg );
                    }
                    
                    wpinv_record_gateway_error( __( 'Payment error:', 'invoicing' ), $errorMsg );
                    wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Payment error: %s', 'invoicing' ), $errorMsg ) );
                    
                    wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                }
            } catch ( AuthorizeNetException $e ) {
                wpinv_set_error( 'request_error', $e->getMessage() );
                wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), $e->getMessage() );            
                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            }
            
            if ( !empty( $response ) ) {
                if ( !empty( $response['response_code'] ) ) {
                    switch ( (int)$response['response_code'] ) {
                        case 1: // Approved 
                            $message = array();
                            if ( isset( $response['authorization_code'] ) ) {
                                $message[] = wp_sprintf( __( 'Authorization Code: %s', 'invoicing' ), $response['authorization_code'] );
                            }
                            if ( isset( $response['avs_response'] ) ) {
                                $message[] = wp_sprintf( __( 'AVS Response: %s', 'invoicing' ), $response['avs_response'] );
                            }
                            if ( isset( $response['transaction_id'] ) ) {
                                $message[] = wp_sprintf( __( 'Transaction ID: %s', 'invoicing' ), $response['transaction_id'] );
                            }
                            if ( isset( $response['card_code_response'] ) ) {
                                $message[] = wp_sprintf( __( 'Card Code Response: %s', 'invoicing' ), $response['card_code_response'] );
                            }
                            if ( isset( $response_info['cavv_response'] ) ) {
                                $message[] = wp_sprintf( __( 'Cardholder Authentication Verification Response: %s', 'invoicing' ), $response_info['cavv_response'] );
                            }
                            
                            wpinv_update_payment_status( $invoice_id, 'publish' );
                            wpinv_set_payment_transaction_id( $invoice_id, $response['transaction_id'] );
                            wpinv_insert_payment_note( $invoice_id, sprintf( __( 'AUTHORIZE.NET PAYMENT: %s', 'invoicing' ) , implode( "<br>", $message ) ) );
                            // Redirect
                            wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
                        break;
                        case 2: // Declined
                            wpinv_record_gateway_error( __( 'Payment cancelled', 'invoicing' ), $response['error_message'], $invoice_id );
                            wpinv_update_payment_status( $invoice_id, 'cancelled' );
                            wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Payment cancelled %s', 'invoicing' ), $response['error_message'] ) );
                        break;
                        case 4: // Held for Review 
                            wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Payment pending %s', 'invoicing' ), $response['error_message'] ) );
                            // Redirect
                            wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
                        break;
                        case 3: // Error  
                        default:
                            wpinv_record_gateway_error( __( 'Payment failed', 'invoicing' ), $response['error_message'], $invoice_id );
                            wpinv_update_payment_status( $invoice_id, 'failed' );
                            wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Payment failed %s', 'invoicing' ), $response['error_message'] ) );
                        break;
                    }
                }
            }
            
            wpinv_send_to_failed_page( '?invoice-id=' . $invoice_id );
        } else {
            wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed while processing a Authorize.net payment. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
            // If errors are present, send the user back to the purchase page so they can be corrected
            wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
        }
    } else {
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    }
}
add_action( 'wpinv_gateway_authorizenet', 'wpinv_process_authorizenet_payment' );

function wpinv_authorizenet_send_request( $data = array(), $line_items = array() ) {
    if ( wpinv_is_test_mode( 'authorizenet' ) ) {
        $url = 'https://test.authorize.net/gateway/transact.dll';
    } else {
        $url = 'https://secure.authorize.net/gateway/transact.dll';
    }
    
    $curl = curl_init( $url );
    
    $authorizenet_postfields = http_build_query( $data, '', '&' );
    if ( !empty( $line_items ) ) {
        foreach ($line_items as $key => $value) {
            $authorizenet_postfields .= "&x_line_item=" . urlencode( $value );
        }
    }
    
    $authorizenet_postfields  = apply_filters( 'wpinv_authorizenet_postfields', $authorizenet_postfields, $data, $line_items );

    curl_setopt( $curl, CURLOPT_POSTFIELDS, $authorizenet_postfields );
    curl_setopt( $curl, CURLOPT_HEADER, 0 );
    curl_setopt( $curl, CURLOPT_TIMEOUT, 45 );
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 2 );
    
    $response = curl_exec( $curl );
    
    $result = array();
    if ( curl_error( $curl ) ) {
        $result['approved']         = false;
        $result['error']            = true;
        $result['error_message']    = wp_sprintf( __( 'AUTHORIZE.NET AIM CURL ERROR: %s', 'invoicing' ), __( curl_errno( $curl ) . '::' . curl_error( $curl ), 'invoicing' ) );
        
        curl_close( $curl );
    } else {
        curl_close( $curl );
        
        $result = wpinv_authorizenet_handle_response( $response );
    }
    
    return $result;
}

function wpinv_authorizenet_handle_response( $response ) {
    $result = array();
    
    if ( empty( $response ) ) {
        $result['approved']         = false;
        $result['error']            = true;
        $result['error_message']    = wp_sprintf( __( 'AUTHORIZE.NET ERROR: %s', 'invoicing' ), __( 'Error connecting to Authorize.Net', 'invoicing' ) );
        return $result;
    }
    
    $encap_char = '"';
    $delimiter  = "|";
        
    if ( $encap_char ) {
        $response_array = explode( $encap_char . $delimiter . $encap_char, substr( $response, 1, -1 ) );
    } else {
        $response_array = explode( $delimiter, $response );
    }
    
    /**
     * If AuthorizeNet doesn't return a delimited response.
     */
    if ( count( $response_array ) < 10 ) {
        $result['approved']         = false;
        $result['error']            = true;
        $result['error_message']    = wp_sprintf( __( 'Unrecognized response from Authorize.Net: %s', 'invoicing' ), $response );
        $result['error_message']    = wp_sprintf( __( 'AUTHORIZE.NET ERROR: %s', 'invoicing' ), $result['error_message'] );
        return $result;
    }
    
     // Set all fields
    $result['response_code']        = $response_array[0];
    $result['response_subcode']     = $response_array[1];
    $result['response_reason_code'] = $response_array[2];
    $result['response_reason_text'] = $response_array[3];
    $result['authorization_code']   = $response_array[4];
    $result['avs_response']         = $response_array[5];
    $result['transaction_id']       = $response_array[6];
    $result['invoice_number']       = $response_array[7];
    $result['description']          = $response_array[8];
    $result['amount']               = $response_array[9];
    $result['method']               = $response_array[10];
    $result['transaction_type']     = $response_array[11];
    $result['customer_id']          = $response_array[12];
    $result['first_name']           = $response_array[13];
    $result['last_name']            = $response_array[14];
    $result['company']              = $response_array[15];
    $result['address']              = $response_array[16];
    $result['city']                 = $response_array[17];
    $result['state']                = $response_array[18];
    $result['zip_code']             = $response_array[19];
    $result['country']              = $response_array[20];
    $result['phone']                = $response_array[21];
    $result['fax']                  = $response_array[22];
    $result['email_address']        = $response_array[23];
    $result['ship_to_first_name']   = $response_array[24];
    $result['ship_to_last_name']    = $response_array[25];
    $result['ship_to_company']      = $response_array[26];
    $result['ship_to_address']      = $response_array[27];
    $result['ship_to_city']         = $response_array[28];
    $result['ship_to_state']        = $response_array[29];
    $result['ship_to_zip_code']     = $response_array[30];
    $result['ship_to_country']      = $response_array[31];
    $result['tax']                  = $response_array[32];
    $result['duty']                 = $response_array[33];
    $result['freight']              = $response_array[34];
    $result['tax_exempt']           = $response_array[35];
    $result['purchase_order_number']= $response_array[36];
    $result['md5_hash']             = $response_array[37];
    $result['card_code_response']   = $response_array[38];
    $result['cavv_response']        = $response_array[39];
    $result['account_number']       = $response_array[50];
    $result['card_type']            = $response_array[51];
    $result['split_tender_id']      = $response_array[52];
    $result['requested_amount']     = $response_array[53];
    $result['balance_on_card']      = $response_array[54];
    
    $result['approved'] = ( $result['response_code'] == 1 );
    $result['declined'] = ( $result['response_code'] == 2 );
    $result['error']    = ( $result['response_code'] == 3 );
    $result['held']     = ( $result['response_code'] == 4 );
    
    if ( $result['error'] ) {
        $error_message              = wp_sprintf( __( 'Response Code: %s, Response Subcode: %s, Response Reason Code: %s, Response Reason Text: %s', 'invoicing' ), $result['response_code'], $result['response_subcode'], $result['response_reason_code'], $result['response_reason_text'] );
        $result['error_message']    = wp_sprintf( __( 'AUTHORIZE.NET ERROR: %s', 'invoicing' ), $error_message );
    }
    
    return $result;
}

function wpinv_authorizenet_valid_ipn( $md5_hash, $transaction_id, $amount ) {
    $authorizenet_md5_hash = wpinv_get_option( 'authorizenet_md5_hash' );
    if ( empty( $authorizenet_md5_hash ) ) {
        return true;
    }
    
    $authorizenet_login_id  = wpinv_get_option( 'authorizenet_login_id' );
    $amount                 = $amount ? $amount : '0.00';
    
    return ( $md5_hash == strtoupper( md5( $authorizenet_md5_hash . $authorizenet_login_id . $transaction_id . $amount ) ) );
}

function wpinv_authorizenet_AIM() {
    if ( !class_exists( 'AuthorizeNetException' ) ) {
        require_once plugin_dir_path( WPINV_PLUGIN_FILE ) . 'includes/gateways/authorizenet/anet_php_sdk/AuthorizeNet.php';
    }
    
    $authorizeAIM = new AuthorizeNetAIM( wpinv_get_option( 'authorizenet_login_id' ), wpinv_get_option( 'authorizenet_transaction_key' ) );
    
    if ( wpinv_is_test_mode( 'authorizenet' ) ) {
        $authorizeAIM->setSandbox( true );
    } else {
        $authorizeAIM->setSandbox( false );
    }
    
    $authorizeAIM->customer_ip = wpinv_get_ip();
    
    return $authorizeAIM;
}