<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wpinv_paypal_cc_form', '__return_false' );
add_filter( 'wpinv_paypal_support_subscription', '__return_true' );

function wpinv_process_paypal_payment( $purchase_data ) {
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
        'gateway'       => 'paypal',
        'status'        => !empty( $purchase_data['buy_now'] ) ? 'private' : 'pending'
    );

    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );

    // Check payment
    if ( ! $invoice ) {
        // Record the error
        wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed before sending buyer to PayPal. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $payment );
        // Problems? send back
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    } else {
        // Only send to PayPal if the pending payment is created successfully
        $listener_url = wpinv_get_ipn_url( 'paypal' );

        // Get the success url
        $return_url = add_query_arg( array(
                'payment-confirm' => 'paypal',
                'invoice-id' => $invoice->ID
            ), get_permalink( wpinv_get_option( 'success_page', false ) ) );

        // Get the PayPal redirect uri
        $paypal_redirect = trailingslashit( wpinv_get_paypal_redirect() ) . '?';

        // Setup PayPal arguments
        $paypal_args = array(
            'business'      => wpinv_get_option( 'paypal_email', false ),
            'email'         => $invoice->get_email(),
            'first_name'    => $invoice->get_first_name(),
            'last_name'     => $invoice->get_last_name(),
            'invoice'       => $invoice->get_key(),
            'no_shipping'   => '1',
            'shipping'      => '0',
            'no_note'       => '1',
            'currency_code' => wpinv_get_currency(),
            'charset'       => get_bloginfo( 'charset' ),
            'custom'        => $invoice->ID,
            'rm'            => '2',
            'return'        => $return_url,
            'cancel_return' => wpinv_get_failed_transaction_uri( '?invoice-id=' . $invoice->ID ),
            'notify_url'    => $listener_url,
            'cbt'           => get_bloginfo( 'name' ),
            'bn'            => 'WPInvoicing_SP'
        );

        $paypal_args['address1'] = $invoice->get_address();
        $paypal_args['city']     = $invoice->user_info['city'];
        $paypal_args['state']    = $invoice->user_info['state'];
        $paypal_args['country']  = $invoice->user_info['country'];
        $paypal_args['zip']      = $invoice->user_info['zip'];

        $paypal_extra_args = array(
            'cmd'    => '_cart',
            'upload' => '1'
        );

        $paypal_args = array_merge( $paypal_extra_args, $paypal_args );

        // Add cart items
        $i = 1;
        if( is_array( $purchase_data['cart_details'] ) && ! empty( $purchase_data['cart_details'] ) ) {
            foreach ( $purchase_data['cart_details'] as $item ) {
                $item['quantity'] = $item['quantity'] > 0 ? $item['quantity'] : 1;
                $item_amount = round( $item['subtotal'] / $item['quantity'], 2 );

                if ( $item_amount <= 0 ) {
                    $item_amount = 0;
                }

                $paypal_args['item_number_' . $i ]      = $item['id'];
                $paypal_args['item_name_' . $i ]        = stripslashes_deep( html_entity_decode( wpinv_get_cart_item_name( $item ), ENT_COMPAT, 'UTF-8' ) );
                $paypal_args['quantity_' . $i ]         = $item['quantity'];
                $paypal_args['amount_' . $i ]           = $item_amount;
                $paypal_args['discount_amount_' . $i ]  = $item['discount'];

                $i++;
            }
        }

        // Add taxes to the cart
        if ( wpinv_use_taxes() ) {
            $paypal_args['tax_cart'] = wpinv_sanitize_amount( (float)$invoice->get_tax() );
        }

        $paypal_args = apply_filters( 'wpinv_paypal_args', $paypal_args, $purchase_data, $invoice );

        // Build query
        $paypal_redirect .= http_build_query( $paypal_args );

        // Fix for some sites that encode the entities
        $paypal_redirect = str_replace( '&amp;', '&', $paypal_redirect );

        // Get rid of cart contents
        wpinv_empty_cart();
        
        wpinv_error_log( $paypal_redirect, 'paypal_redirect', __FILE__, __LINE__ );
        // Redirect to PayPal
        wp_redirect( $paypal_redirect );
        exit;
    }
}
add_action( 'wpinv_gateway_paypal', 'wpinv_process_paypal_payment' );

function wpinv_get_paypal_recurring_args( $paypal_args, $purchase_data, $invoice ) {
    if ( $invoice->is_recurring() ) {
        $paypal_args['sra']     = '1';
        $paypal_args['src']     = '1';
        $paypal_args['cmd']     = '_xclick-subscriptions';
        
        // Add cart items
        $i = 1;
        if( is_array( $purchase_data['cart_details'] ) && ! empty( $purchase_data['cart_details'] ) ) {
            foreach ( $purchase_data['cart_details'] as $item ) {
                $item_info      = new WPInv_Item( $item['id'] );
                            
                $item_amount    = round( ( $item['subtotal'] / $item['quantity'] ) - ( $item['discount'] / $item['quantity'] ), 2 );

                if( $item_amount <= 0 ) {
                    $item_amount = 0;
                }
                
                $period       = $item_info->get_recurring_period(); // Set the recurring period
                $interval     = $item_info->get_recurring_interval(); // One period unit (every week, every month, etc)
                $limit        = (int)$item_info->get_recurring_limit();
                
                // Set item description
                $paypal_args['item_name']  = stripslashes_deep( html_entity_decode( wpinv_get_cart_item_name( $item ), ENT_COMPAT, 'UTF-8' ) );
                
                /*
                $paypal_args['a1']  = 0;
                $paypal_args['p1']  = $interval;
                $paypal_args['t1']  = $period;
                */
                
                $paypal_args['a3']  = $invoice->get_total();
                $paypal_args['p3']  = $interval;
                $paypal_args['t3']  = $period;
                
                if ( $limit > 1 ) {
                    // Make sure it's not over the max of 52
                    $paypal_args['srt'] = $limit;
                }
                
                // Set tax amount
                if ( isset( $paypal_args['tax_cart'] ) ) {
                    $paypal_args['tax'] = $paypal_args['tax_cart'];
                    unset( $paypal_args['tax_cart'] );
                }
                
                if ( isset( $paypal_args['item_number_' . $i] ) ) {
                    unset( $paypal_args['item_number_' . $i] );
                }
                if ( isset( $paypal_args['item_name_' . $i] ) ) {
                    unset( $paypal_args['item_name_' . $i] );
                }
                if ( isset( $paypal_args['quantity_' . $i] ) ) {
                    unset( $paypal_args['quantity_' . $i] );
                }
                if ( isset( $paypal_args['amount_' . $i] ) ) {
                    unset( $paypal_args['amount_' . $i] );
                }

                $i++;
            }
        }
        
        if ( isset( $paypal_args['upload'] ) ) {
            unset( $paypal_args['upload'] );
        }
        
        $paypal_args = apply_filters( 'wpinv_paypal_recurring_args', $paypal_args, $purchase_data, $invoice );
    }
    
    return $paypal_args;
}
add_filter( 'wpinv_paypal_args', 'wpinv_get_paypal_recurring_args', 10, 3 );

function wpinv_process_paypal_ipn() {
	// Check the request method is POST
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] != 'POST' ) {
		return;
	}
    wpinv_error_log( 'wpinv_process_paypal_ipn()', '', __FILE__, __LINE__ );

	// Set initial post data to empty string
	$post_data = '';

	// Fallback just in case post_max_size is lower than needed
	if ( ini_get( 'allow_url_fopen' ) ) {
		$post_data = file_get_contents( 'php://input' );
	} else {
		// If allow_url_fopen is not enabled, then make sure that post_max_size is large enough
		ini_set( 'post_max_size', '12M' );
	}
	// Start the encoded data collection with notification command
	$encoded_data = 'cmd=_notify-validate';

	// Get current arg separator
	$arg_separator = wpinv_get_php_arg_separator_output();

	// Verify there is a post_data
	if ( $post_data || strlen( $post_data ) > 0 ) {
		// Append the data
		$encoded_data .= $arg_separator.$post_data;
	} else {
		// Check if POST is empty
		if ( empty( $_POST ) ) {
			// Nothing to do
			return;
		} else {
			// Loop through each POST
			foreach ( $_POST as $key => $value ) {
				// Encode the value and append the data
				$encoded_data .= $arg_separator."$key=" . urlencode( $value );
			}
		}
	}

	// Convert collected post data to an array
	parse_str( $encoded_data, $encoded_data_array );

	foreach ( $encoded_data_array as $key => $value ) {
		if ( false !== strpos( $key, 'amp;' ) ) {
			$new_key = str_replace( '&amp;', '&', $key );
			$new_key = str_replace( 'amp;', '&' , $new_key );

			unset( $encoded_data_array[ $key ] );
			$encoded_data_array[ $new_key ] = $value;
		}
	}

	// Get the PayPal redirect uri
	$paypal_redirect = wpinv_get_paypal_redirect( true );

	if ( !wpinv_get_option( 'disable_paypal_verification', false ) ) {
		// Validate the IPN

		$remote_post_vars      = array(
			'method'           => 'POST',
			'timeout'          => 45,
			'redirection'      => 5,
			'httpversion'      => '1.1',
			'blocking'         => true,
			'headers'          => array(
				'host'         => 'www.paypal.com',
				'connection'   => 'close',
				'content-type' => 'application/x-www-form-urlencoded',
				'post'         => '/cgi-bin/webscr HTTP/1.1',

			),
			'sslverify'        => false,
			'body'             => $encoded_data_array
		);

		// Get response
		$api_response = wp_remote_post( wpinv_get_paypal_redirect(), $remote_post_vars );

		if ( is_wp_error( $api_response ) ) {
			wpinv_record_gateway_error( __( 'IPN Error', 'invoicing' ), sprintf( __( 'Invalid IPN verification response. IPN data: %s', 'invoicing' ), json_encode( $api_response ) ) );
			return; // Something went wrong
		}

		if ( $api_response['body'] !== 'VERIFIED' && wpinv_get_option( 'disable_paypal_verification', false ) ) {
			wpinv_record_gateway_error( __( 'IPN Error', 'invoicing' ), sprintf( __( 'Invalid IPN verification response. IPN data: %s', 'invoicing' ), json_encode( $api_response ) ) );
			return; // Response not okay
		}
	}

	// Check if $post_data_array has been populated
	if ( !is_array( $encoded_data_array ) && !empty( $encoded_data_array ) )
		return;

	$defaults = array(
		'txn_type'       => '',
		'payment_status' => ''
	);

	$encoded_data_array = wp_parse_args( $encoded_data_array, $defaults );
    
    wpinv_error_log( $encoded_data_array, 'ipn_data', __FILE__, __LINE__ );

	$invoice_id = isset( $encoded_data_array['custom'] ) ? absint( $encoded_data_array['custom'] ) : 0;

	if ( has_action( 'wpinv_paypal_' . $encoded_data_array['txn_type'] ) ) {
		// Allow PayPal IPN types to be processed separately
		do_action( 'wpinv_paypal_' . $encoded_data_array['txn_type'], $encoded_data_array, $invoice_id );
	} else {
		// Fallback to web accept just in case the txn_type isn't present
		do_action( 'wpinv_paypal_web_accept', $encoded_data_array, $invoice_id );
	}
	exit;
}
add_action( 'wpinv_verify_paypal_ipn', 'wpinv_process_paypal_ipn' );

function wpinv_process_paypal_web_accept_and_cart( $data, $invoice_id ) {
	if ( $data['txn_type'] != 'web_accept' && $data['txn_type'] != 'cart' && $data['payment_status'] != 'Refunded' ) {
		return;
	}

	if( empty( $invoice_id ) ) {
		return;
	}

	// Collect payment details
	$purchase_key   = isset( $data['invoice'] ) ? $data['invoice'] : $data['item_number'];
	$paypal_amount  = $data['mc_gross'];
	$payment_status = strtolower( $data['payment_status'] );
	$currency_code  = strtolower( $data['mc_currency'] );
	$business_email = isset( $data['business'] ) && is_email( $data['business'] ) ? trim( $data['business'] ) : trim( $data['receiver_email'] );
	$payment_meta   = wpinv_get_invoice_meta( $invoice_id );

	if ( wpinv_get_payment_gateway( $invoice_id ) != 'paypal' ) {
		return; // this isn't a PayPal standard IPN
	}

	// Verify payment recipient
	if ( strcasecmp( $business_email, trim( wpinv_get_option( 'paypal_email', false ) ) ) != 0 ) {
		wpinv_record_gateway_error( __( 'IPN Error', 'invoicing' ), sprintf( __( 'Invalid business email in IPN response. IPN data: %s', 'invoicing' ), json_encode( $data ) ), $invoice_id );
		wpinv_update_payment_status( $invoice_id, 'failed' );
		wpinv_insert_payment_note( $invoice_id, __( 'Payment failed due to invalid PayPal business email.', 'invoicing' ) );
		return;
	}

	// Verify payment currency
	if ( $currency_code != strtolower( $payment_meta['currency'] ) ) {
		wpinv_record_gateway_error( __( 'IPN Error', 'invoicing' ), sprintf( __( 'Invalid currency in IPN response. IPN data: %s', 'invoicing' ), json_encode( $data ) ), $invoice_id );
		wpinv_update_payment_status( $invoice_id, 'failed' );
		wpinv_insert_payment_note( $invoice_id, __( 'Payment failed due to invalid currency in PayPal IPN.', 'invoicing' ) );
		return;
	}

	if ( !wpinv_get_payment_user_email( $invoice_id ) ) {
		// This runs when a Buy Now purchase was made. It bypasses checkout so no personal info is collected until PayPal
		// No email associated with purchase, so store from PayPal
		wpinv_update_invoice_meta( $invoice_id, '_wpinv_email', $data['payer_email'] );

		// Setup and store the customers's details
		$user_info = array(
			'user_id'    => '-1',
			'email'      => sanitize_text_field( $data['payer_email'] ),
			'first_name' => sanitize_text_field( $data['first_name'] ),
			'last_name'  => sanitize_text_field( $data['last_name'] ),
			'discount'   => '',
		);
		$user_info['address'] = ! empty( $data['address_street']       ) ? sanitize_text_field( $data['address_street'] )       : false;
		$user_info['city']    = ! empty( $data['address_city']         ) ? sanitize_text_field( $data['address_city'] )         : false;
		$user_info['state']   = ! empty( $data['address_state']        ) ? sanitize_text_field( $data['address_state'] )        : false;
		$user_info['country'] = ! empty( $data['address_country_code'] ) ? sanitize_text_field( $data['address_country_code'] ) : false;
		$user_info['zip']     = ! empty( $data['address_zip']          ) ? sanitize_text_field( $data['address_zip'] )          : false;

		$payment_meta['user_info'] = $user_info;
		wpinv_update_invoice_meta( $invoice_id, '_wpinv_payment_meta', $payment_meta );
	}

	if ( $payment_status == 'refunded' || $payment_status == 'reversed' ) {
		// Process a refund
		wpinv_process_paypal_refund( $data, $invoice_id );
	} else {
		if ( get_post_status( $invoice_id ) == 'publish' ) {
			return; // Only complete payments once
		}

		// Retrieve the total purchase amount (before PayPal)
		$payment_amount = wpinv_payment_total( $invoice_id );

		if ( number_format( (float) $paypal_amount, 2 ) < number_format( (float) $payment_amount, 2 ) ) {
			// The prices don't match
			wpinv_record_gateway_error( __( 'IPN Error', 'invoicing' ), sprintf( __( 'Invalid payment amount in IPN response. IPN data: %s', 'invoicing' ), json_encode( $data ) ), $invoice_id );
			wpinv_update_payment_status( $invoice_id, 'failed' );
			wpinv_insert_payment_note( $invoice_id, __( 'Payment failed due to invalid amount in PayPal IPN.', 'invoicing' ) );
			return;
		}
		if ( $purchase_key != wpinv_get_payment_key( $invoice_id ) ) {
			// Purchase keys don't match
			wpinv_record_gateway_error( __( 'IPN Error', 'invoicing' ), sprintf( __( 'Invalid purchase key in IPN response. IPN data: %s', 'invoicing' ), json_encode( $data ) ), $invoice_id );
			wpinv_update_payment_status( $invoice_id, 'failed' );
			wpinv_insert_payment_note( $invoice_id, __( 'Payment failed due to invalid purchase key in PayPal IPN.', 'invoicing' ) );
			return;
		}

		if ( 'completed' == $payment_status || wpinv_is_test_mode( 'paypal' ) ) {
			wpinv_insert_payment_note( $invoice_id, sprintf( __( 'PayPal Transaction ID: %s', 'invoicing' ) , $data['txn_id'] ) );
			wpinv_set_payment_transaction_id( $invoice_id, $data['txn_id'] );
			wpinv_update_payment_status( $invoice_id, 'publish' );
		} else if ( 'pending' == $payment_status && isset( $data['pending_reason'] ) ) {
			// Look for possible pending reasons, such as an echeck
			$note = '';

			switch( strtolower( $data['pending_reason'] ) ) {
				case 'echeck' :
					$note = __( 'Payment made via eCheck and will clear automatically in 5-8 days', 'invoicing' );
					break;
				
                case 'address' :
					$note = __( 'Payment requires a confirmed customer address and must be accepted manually through PayPal', 'invoicing' );
					break;
				
                case 'intl' :
					$note = __( 'Payment must be accepted manually through PayPal due to international account regulations', 'invoicing' );
					break;
				
                case 'multi-currency' :
					$note = __( 'Payment received in non-shop currency and must be accepted manually through PayPal', 'invoicing' );
					break;
				
                case 'paymentreview' :
                case 'regulatory_review' :
					$note = __( 'Payment is being reviewed by PayPal staff as high-risk or in possible violation of government regulations', 'invoicing' );
					break;
				
                case 'unilateral' :
					$note = __( 'Payment was sent to non-confirmed or non-registered email address.', 'invoicing' );
					break;
				
                case 'upgrade' :
					$note = __( 'PayPal account must be upgraded before this payment can be accepted', 'invoicing' );
					break;
				
                case 'verify' :
					$note = __( 'PayPal account is not verified. Verify account in order to accept this payment', 'invoicing' );
					break;

				case 'other' :
					$note = __( 'Payment is pending for unknown reasons. Contact PayPal support for assistance', 'invoicing' );
					break;
			}

			if ( ! empty( $note ) ) {
				wpinv_insert_payment_note( $invoice_id, $note );
			}
		}
	}
}
add_action( 'wpinv_paypal_web_accept', 'wpinv_process_paypal_web_accept_and_cart', 10, 2 );

// Process PayPal subscription sign ups
add_action( 'wpinv_paypal_subscr_signup', 'wpinv_process_paypal_subscr_signup' );

// Process PayPal subscription payments
add_action( 'wpinv_paypal_subscr_payment', 'wpinv_process_paypal_subscr_payment' );

// Process PayPal subscription cancellations
add_action( 'wpinv_paypal_subscr_cancel', 'wpinv_process_paypal_subscr_cancel' );

// Process PayPal subscription end of term notices
add_action( 'wpinv_paypal_subscr_eot', 'wpinv_process_paypal_subscr_eot' );

// Process PayPal payment failed
add_action( 'wpinv_paypal_subscr_failed', 'wpinv_process_paypal_subscr_failed' );

/**
 * Process the subscription payment received IPN.
 */
function wpinv_process_paypal_subscr_payment( $ipn_data ) {
    $parent_invoice_id = absint( $ipn_data['custom'] );
    wpinv_error_log( $parent_invoice_id, 'wpinv_process_paypal_subscr_payment()', __FILE__, __LINE__ );
    
    $subscription = wpinv_get_paypal_subscription( $ipn_data );
    wpinv_error_log( $subscription, 'subscription', __FILE__, __LINE__ );
    if ( false === $subscription ) {
        return;
    }
    
    $transaction_id = wpinv_get_payment_transaction_id( $parent_invoice_id );
    wpinv_error_log( $transaction_id, 'transaction_id', __FILE__, __LINE__ );
    $signup_date    = strtotime( $subscription->get_subscription_created() );
    wpinv_error_log( $signup_date, 'signup_date', __FILE__, __LINE__ );
    $today          = date_i18n( 'Y-n-d', $signup_date ) == date_i18n( 'Y-n-d', strtotime( $ipn_data['payment_date'] ) );
    wpinv_error_log( $today, 'today', __FILE__, __LINE__ );

    // Look to see if payment is same day as signup and we have set the transaction ID on the parent payment yet
    if ( $today && ( !$transaction_id || $transaction_id == $parent_invoice_id ) ) {
        // This is the very first payment
        wpinv_set_payment_transaction_id( $parent_invoice_id, $ipn_data['txn_id'] );
        return;
    }
    wpinv_error_log( $transaction_id, 'transaction_id', __FILE__, __LINE__ );
    if ( wpinv_get_id_by_transaction_id( $ipn_data['txn_id'] ) ) {
        return; // Payment alreay recorded
    }

    $currency_code = strtolower( $ipn_data['mc_currency'] );
    wpinv_error_log( $currency_code, 'currency_code', __FILE__, __LINE__ );
    // verify details
    if ( $currency_code != strtolower( wpinv_get_currency() ) ) {
        // the currency code is invalid
        wpinv_record_gateway_error( __( 'IPN Error', 'invoicing' ), sprintf( __( 'Invalid currency in IPN response. IPN data: ', 'invoicing' ), json_encode( $ipn_data ) ) );
        return;
    }

    $args = array(
        'amount'         => $ipn_data['mc_gross'],
        'transaction_id' => $ipn_data['txn_id']
    );
    
    $invoice = wpinv_recurring_add_subscription_payment( $parent_invoice_id, $args );
    
    if ( !empty( $invoice ) ) {
        sleep(1);
        wpinv_insert_payment_note( $invoice->ID, sprintf( __( 'PayPal Transaction ID: %s', 'invoicing' ) , $ipn_data['txn_id'] ) );

        $invoice->renew_subscription();
    }
}

/**
 * Process the subscription started IPN.
 */
function wpinv_process_paypal_subscr_signup( $ipn_data ) {
    $parent_invoice_id = absint( $ipn_data['custom'] );
    wpinv_error_log( $parent_invoice_id, 'wpinv_process_paypal_subscr_signup()', __FILE__, __LINE__ );
    if( empty( $parent_invoice_id ) ) {
        return;
    }

    $invoice = wpinv_get_invoice( $parent_invoice_id );
    if ( empty( $invoice ) ) {
        return;
    }

    //wpinv_set_payment_transaction_id( $invoice->ID, $ipn_data['txn_id'] );
    wpinv_update_payment_status( $parent_invoice_id, 'publish' );
    wpinv_insert_payment_note( $parent_invoice_id, sprintf( __( 'PayPal Subscription ID: %s', 'invoicing' ) , $ipn_data['subscr_id'] ) );
    
    $subscription = wpinv_get_paypal_subscription( $ipn_data );
    wpinv_error_log( $subscription, 'subscription', __FILE__, __LINE__ );
    if ( false === $subscription ) {
        return;
    }

    $cart_details   = $invoice->cart_details;

    if ( !empty( $cart_details ) ) {
        foreach ( $cart_details as $cart_item ) {
            $item = new WPInv_Item( $cart_item['id'] );
            
            $args = array(
                'item_id'           => $cart_item['id'],
                'status'            => 'active',
                'period'            => $item->get_recurring_period(),
                'initial_amount'    => wpinv_payment_total( $parent_invoice_id ),
                'recurring_amount'  => $invoice->get_total(),
                'interval'          => $item->get_recurring_interval(),
                'bill_times'        => $item->get_recurring_limit(),
                'expiration'        => $invoice->get_new_expiration( $cart_item['id'] ),
                'profile_id'        => $ipn_data['subscr_id'],
                'created'           => current_time( 'mysql', 0 )
            );
            
            // Retrieve pending subscription from database and update it's status to active and set proper profile ID
            $subscription->update_subscription( $args );
        }
    }
}

/**
 * Process the subscription canceled IPN.
 */
function wpinv_process_paypal_subscr_cancel( $ipn_data ) {
    $subscription = wpinv_get_paypal_subscription( $ipn_data );

    if( false === $subscription ) {
        return;
    }

    $subscription->cancel_subscription();
}

/**
 * Process the subscription expired IPN.
 */
function wpinv_process_paypal_subscr_eot( $ipn_data ) {
    $subscription = wpinv_get_paypal_subscription( $ipn_data );

    if( false === $subscription ) {
        return;
    }

    $subscription->complete_subscription();
}

/**
 * Process the subscription payment failed IPN.
 */
function wpinv_process_paypal_subscr_failed( $ipn_data ) {
    $subscription = wpinv_get_paypal_subscription( $ipn_data );

    if( false === $subscription ) {
        return;
    }

    $subscription->failing_subscription();

    do_action( 'wpinv_recurring_payment_failed', $subscription );
}

/**
 * Retrieve the subscription this IPN notice is for.
 */
function wpinv_get_paypal_subscription( $ipn_data = array() ) {
    $parent_invoice_id = absint( $ipn_data['custom'] );

    if( empty( $parent_invoice_id ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $parent_invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    $subscription = wpinv_get_subscription( $ipn_data['subscr_id'], true );

    if ( empty( $subscription ) ) {
        $subs         = wpinv_get_subscriptions( array( 'parent_invoice_id' => $parent_invoice_id, 'numberposts' => 1 ) );
        $subscription = reset( $subs );

        if ( $subscription && $subscription->ID > 0 ) {
            // Update the profile ID so it is set for future renewals
            $subscription->update_subscription( array( 'profile_id' => sanitize_text_field( $ipn_data['subscr_id'] ) ) );
        } else {
            $subscription = $invoice;
            $subscription->update_subscription( array( 'profile_id' => sanitize_text_field( $ipn_data['subscr_id'] ) ) );
            // No subscription found with a matching payment ID, bail
            //return false;
        }
    }

    return $subscription;

}

function wpinv_process_paypal_refund( $data, $invoice_id = 0 ) {
	// Collect payment details

	if( empty( $invoice_id ) ) {
		return;
	}

	if ( get_post_status( $invoice_id ) == 'refunded' ) {
		return; // Only refund payments once
	}

	$payment_amount = wpinv_payment_total( $invoice_id );
	$refund_amount  = $data['mc_gross'] * -1;

	if ( number_format( (float) $refund_amount, 2 ) < number_format( (float) $payment_amount, 2 ) ) {
		wpinv_insert_payment_note( $invoice_id, sprintf( __( 'Partial PayPal refund processed: %s', 'invoicing' ), $data['parent_txn_id'] ) );
		return; // This is a partial refund
	}

	wpinv_insert_payment_note( $invoice_id, sprintf( __( 'PayPal Payment #%s Refunded for reason: %s', 'invoicing' ), $data['parent_txn_id'], $data['reason_code'] ) );
	wpinv_insert_payment_note( $invoice_id, sprintf( __( 'PayPal Refund Transaction ID: %s', 'invoicing' ), $data['txn_id'] ) );
	wpinv_update_payment_status( $invoice_id, 'refunded' );
}

function wpinv_get_paypal_redirect( $ssl_check = false ) {
    if ( is_ssl() || ! $ssl_check ) {
        $protocal = 'https://';
    } else {
        $protocal = 'http://';
    }

    // Check the current payment mode
    if ( wpinv_is_test_mode( 'paypal' ) ) {
        // Test mode
        $paypal_uri = $protocal . 'www.sandbox.paypal.com/cgi-bin/webscr';
    } else {
        // Live mode
        $paypal_uri = $protocal . 'www.paypal.com/cgi-bin/webscr';
    }

    return apply_filters( 'wpinv_paypal_uri', $paypal_uri );
}

function wpinv_paypal_success_page_content( $content ) {
    global $wpi_invoice;
    
    $session = wpinv_get_checkout_session();

    if ( empty( $_GET['invoice-id'] ) && empty( $session['invoice_key'] )  ) {
        return $content;
    }

    $invoice_id = !empty( $_GET['invoice-id'] ) ? absint( $_GET['invoice-id'] ) : wpinv_get_invoice_id_by_key( $session['invoice_key'] );

    if ( empty(  $invoice_id ) ) {
        return $content;
    }

    $wpi_invoice = wpinv_get_invoice( $invoice_id );
    
    if ( !empty( $wpi_invoice ) && 'pending' == $wpi_invoice->status ) {
        // Payment is still pending so show processing indicator to fix the Race Condition, issue #
        ob_start();
        wpinv_get_template_part( 'wpinv-payment-processing' );
        $content = ob_get_clean();
    }

    return $content;
}
add_filter( 'wpinv_payment_confirm_paypal', 'wpinv_paypal_success_page_content' );

function wpinv_paypal_success_page_redirect() { 
    global $wpi_invoice;
    
    $session = wpinv_get_checkout_session();

    if ( empty( $_GET['invoice-id'] ) && empty( $session['invoice_key'] )  ) {
        return;
    }

    $invoice_id = !empty( $_GET['invoice-id'] ) ? absint( $_GET['invoice-id'] ) : wpinv_get_invoice_id_by_key( $session['invoice_key'] );

    if ( empty(  $invoice_id ) ) {
        return;
    }

    $wpi_invoice = wpinv_get_invoice( $invoice_id );
    
    if ( !empty( $wpi_invoice ) && 'pending' != $wpi_invoice->status && 'paypal' == $wpi_invoice->get_gateway() ) {
        wp_redirect( $wpi_invoice->get_view_invoice_url() );
        exit;
    }

    return;
}
add_action( 'template_redirect', 'wpinv_paypal_success_page_redirect' );

function wpinv_paypal_get_transaction_id( $invoice_id ) {
    $transaction_id = '';
    $notes = wpinv_get_invoice_notes( $invoice_id );

    foreach ( $notes as $note ) {
        if ( preg_match( '/^PayPal Transaction ID: ([^\s]+)/', $note->comment_content, $match ) ) {
            $transaction_id = $match[1];
            continue;
        }
    }

    return apply_filters( 'wpinv_paypal_set_transaction_id', $transaction_id, $invoice_id );
}
add_filter( 'wpinv_payment_get_transaction_id-paypal', 'wpinv_paypal_get_transaction_id', 10, 1 );

function wpinv_paypal_link_transaction_id( $transaction_id, $invoice_id ) {
    $sandbox = wpinv_is_test_mode( 'paypal' ) ? '.sandbox' : '';
    
    $paypal_base_url = 'https://www' . $sandbox . '.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=';
    $transaction_url = '<a href="' . esc_url( $paypal_base_url . $transaction_id ) . '" target="_blank">' . $transaction_id . '</a>';

    return apply_filters( 'wpinv_paypal_link_payment_details_transaction_id', $transaction_url );
}
add_filter( 'wpinv_payment_details_transaction_id-paypal', 'wpinv_paypal_link_transaction_id', 10, 2 );