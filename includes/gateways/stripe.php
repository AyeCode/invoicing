<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once( WPINV_PLUGIN_DIR . 'includes/gateways/libraries/stripe-php/init.php' );

add_action( 'wpinv_stripe_cc_form', '__return_false' );
add_filter( 'wpinv_stripe_support_subscription', '__return_true' );

function wpinv_process_stripe_payment( $purchase_data ) {
    if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'wpi-gateway' ) ) {
        wp_die( __( 'Nonce verification has failed', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
    }
    
    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );
    
    if ( empty( $invoice ) ) {
        // Record the error
        wpinv_record_gateway_error( __( 'Payment Error', 'invoicing' ), sprintf( __( 'Payment creation failed before precessing the Stripe payment. Payment data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
        // Problems? send back
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    } else {
        $invoice_summary = '';
        
        if ( is_array( $purchase_data['cart_details'] ) && !empty( $purchase_data['cart_details'] ) ) {
            foreach ( $purchase_data['cart_details'] as $item ) {
                $invoice_summary .= $item['name'];
                $price_id = isset( $item['item_number']['options']['price_id'] ) ? absint( $item['item_number']['options']['price_id'] ) : false;
                
                if ( false !== $price_id ) {
                    $invoice_summary .= ' - ' . $item['item_number']['options']['price_id'];
                }
                
                $invoice_summary .= ', ';
            }

            $invoice_summary = rtrim( $invoice_summary, ', ' );
        } else {
            $invoice_summary = $invoice->title;
        }

        // make sure we don't have any left over errors present
        wpinv_clear_errors();

        if ( !isset( $_POST['wpi_stripe_token'] ) ) {
            // check for fallback mode
            if ( wpinv_get_option( 'stripe_js_fallback' ) ) {
                $card_data = wpinv_stripe_process_post_data( $purchase_data );
            } else {
                // no Stripe token
                wpinv_set_error( 'no_token', __( 'Missing Stripe token. Please contact support.', 'invoicing' ) );
                wpinv_record_gateway_error( __( 'Missing Stripe Token', 'invoicing' ), __( 'A Stripe token failed to be generated. Please check Stripe logs for more information', ' invoicing' ) );
            }
        } else {
            $card_data = $_POST['wpi_stripe_token'];
        }

        $errors = wpinv_get_errors();

        if ( !$errors ) {
            do_action( 'wpinv_stripe_pre_process_payment', $invoice );
            
            try {
                wpinv_stripe_set_api_key();

                // setup the payment details
                $payment_data = array(
                    'price'         => $purchase_data['price'],
                    'date'          => $purchase_data['date'],
                    'user_email'    => $purchase_data['user_email'],
                    'invoice_key'   => $purchase_data['invoice_key'],
                    'currency'      => wpinv_get_currency(),
                    'items'         => $purchase_data['items'],
                    'user_info'     => $purchase_data['user_info'],
                    'cart_details'  => $purchase_data['cart_details'],
                    'gateway'       => 'stripe',
                    'status'        => 'pending'
                );

                $customer_exists    = false;
                $customer           = wpinv_stripe_get_customer( $invoice->get_user_id(), $purchase_data['user_email'] );
                
                if ( !empty( $customer ) ) {
                    $customer_id        = is_array( $customer ) ? $customer['id'] : $customer->id;
                    $customer_exists    = true;
                }

                // Subscription payment.
                if ( $invoice->is_recurring() && $subscription_item_id = $invoice->get_recurring() ) {
                    if ( $customer_exists ) {
                        try {
                            $customer->source = $card_data;
                            $customer->save();
                        } catch ( Exception $e ) {
                            wpinv_set_error( 'wpinv_recurring_stripe_error', $e->getMessage() );
                            
                            wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                        }
                        
                        $subscription_item                          = new WPInv_Item( $subscription_item_id );
                        
                        $subscription_data                          = array();
                        $subscription_data['number']                = $invoice->get_number();
                        $subscription_data['id']                    = $subscription_item_id;
                        $subscription_data['name']                  = $invoice->get_subscription_name();
                        $subscription_data['initial_amount']        = wpinv_format_amount( $invoice->get_total() );
                        $subscription_data['recurring_amount']      = wpinv_format_amount( $invoice->get_recurring_details( 'total' ) );
                        $subscription_data['currency']              = $invoice->get_currency();
                        $subscription_data['period']                = $subscription_item->get_recurring_period( true );
                        $subscription_data['interval']              = $subscription_item->get_recurring_interval();
                        $subscription_data['bill_times']            = (int)$subscription_item->get_recurring_limit();
                        $subscription_data['trial_period_days']     = 0;
                        
                        $plan_id = wpinv_stripe_get_plan_id( $subscription_data, $invoice );
                        
                        if ( empty( $plan_id ) ) {
                            wpinv_set_error( 'wpinv_recurring_stripe_plan_error', __( 'The subscription plan in Stripe could not be created, please try again.', 'invoicing' ) );
                            
                            wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                        }
                        
                        /*
                         * If we have a signup fee or the recurring amount is different than the initial amount, we need to add it to the customer's
                         * balance so that the subscription invoice amount is adjusted properly.
                         *
                         * Example: if the subscription is $10 per month and the signup fee is $5, this will add $5 to the account balance.
                         *
                         * When account balances are negative, Stripe discounts that amount on the next invoice.
                         * When account balancces are positve, Stripe adds that amount to the next invoice.
                         */

                        $save_balance   = false;
                        $tax_percentage = 0;
                        
                        if ( $subscription_data['initial_amount'] > $subscription_data['recurring_amount'] ) {
                            $save_balance   = true;
                            $amount         = $subscription_data['initial_amount'] - $subscription_data['recurring_amount'];
                            $balance_amount = round( $customer->account_balance + ( $amount * 100 ), 0 ); // Add additional amount to initial payment (in cents)
                            $customer->account_balance = $balance_amount;
                        }

                        if ( $subscription_data['initial_amount'] < $subscription_data['recurring_amount'] ) {
                            $save_balance   = true;
                            $amount         = $subscription_data['recurring_amount'] - $subscription_data['initial_amount'];
                            $balance_amount = round( $customer->account_balance - ( $amount * 100 ), 0 ); // Add a discount to initial payment (in cents)
                            $customer->account_balance = $balance_amount;
                        }

                        if ( !empty( $save_balance ) ) {
                            $balance_changed = true;
                            $customer->save();
                        }
                        
                        try {
                            $subscription = $customer->subscriptions->create( array(
                                'plan'        => $plan_id,
                                'tax_percent' => $tax_percentage,
                                'metadata'    => array(
                                    'invoice_id'    => $invoice->ID,
                                    'bill_times'    => $subscription_data['bill_times']
                                )
                            ) );
                            
                            do_action( 'wpinv_recurring_post_create_subscription', $subscription, $invoice, 'stripe' );
                            
                            wpinv_stripe_subscription_record_signup( $subscription, $invoice );
                            
                            do_action( 'wpinv_recurring_post_record_signup', $subscription, $invoice, 'stripe' );
                            
                            wpinv_empty_cart();
                            wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
                        } catch ( Exception $e ) {
                            // Charging the customer failed so we need to undo the balance adjustment
                            if( ! empty( $balance_changed ) ) {
                                $customer->account_balance -= $balance_amount;
                                $customer->save();
                            }
                            wpinv_set_error( 'wpinv_recurring_stripe_error', $e->getMessage() );
                            
                            wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                        }
                    } else {
                        wpinv_record_gateway_error( __( 'Customer Creation Failed', 'invoicing' ), sprintf( __( 'Customer creation failed while processing a payment. Payment Data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
                        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                    }
                } else {
                    if ( $customer_exists ) {
                        if ( is_array( $card_data ) ) {
                            $card_data['object'] = 'card';
                        }

                        $card    = $customer->sources->create( array( 'source' => $card_data ) );
                        $card_id = $card->id;

                        // Process a normal one-time charge purchase
                        if ( !wpinv_get_option( 'stripe_preapprove_only' ) ) {
                            if ( wpinv_is_zero_decimal_currency( $payment_data['currency'] ) ) {
                                $amount = $purchase_data['price'];
                            } else {
                                $amount = $purchase_data['price'] * 100;
                            }

                            $unsupported_characters = array( '<', '>', '"', '\'' );

                            $statement_descriptor = apply_filters( 'wpinv_stripe_statement_descriptor', substr( $invoice_summary, 0, 22 ), $purchase_data );
                            $statement_descriptor = str_replace( $unsupported_characters, '', $statement_descriptor );

                            $args = array(
                                'amount'      => $amount,
                                'currency'    => $payment_data['currency'],
                                'customer'    => $customer_id,
                                'source'      => $card_id,
                                'description' => html_entity_decode( $invoice_summary, ENT_COMPAT, 'UTF-8' ),
                                'metadata'    => array(
                                    'email'   => $purchase_data['user_info']['email']
                                ),
                            );

                            if( ! empty( $statement_descriptor ) ) {
                                $args[ 'statement_descriptor' ] = $statement_descriptor;
                            }

                            $charge = \Stripe\Charge::create( $args );
                        }
                    } else {
                        wpinv_record_gateway_error( __( 'Customer Creation Failed', 'invoicing' ), sprintf( __( 'Customer creation failed while processing a payment. Payment Data: %s', 'invoicing' ), json_encode( $payment_data ) ), $invoice );
                    }

                    if ( $invoice && ( !empty( $customer_id ) || !empty( $charge ) ) ) {
                        if ( wpinv_get_option( 'stripe_preapprove_only' ) ) {
                            wpinv_update_payment_status( $invoice, 'preapproval' ); // TODO
                            add_post_meta( $invoice, wpinv_stripe_get_customer_key(), $customer_id );
                        } else {
                            wpinv_update_payment_status( $invoice, 'publish' );
                        }

                        // You should be using Stripe's API here to retrieve the invoice then confirming it's been paid
                        if ( !empty( $charge ) ) {
                            wpinv_insert_payment_note( $invoice, 'Stripe Charge ID: ' . $charge->id );

                            wpinv_set_payment_transaction_id( $invoice, $charge->id );
                        } elseif ( ! empty( $customer_id ) ) {
                            wpinv_insert_payment_note( $invoice, 'Stripe Customer ID: ' . $customer_id );
                        }

                        wpinv_empty_cart();
                        wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
                    } else {
                        wpinv_set_error( 'payment_not_recorded', __( 'Your payment could not be recorded, please contact the site administrator.', 'invoicing' ) );

                        // if errors are present, send the user back to the purchase page so they can be corrected
                        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                    }
                }
            } catch ( \Stripe\Error\Card $e ) {
                $body = $e->getJsonBody();
                $err  = $body['error'];
                
                if( isset( $err['message'] ) ) {
                    wpinv_set_error( 'payment_error', $err['message'] );
                } else {
                    wpinv_set_error( 'payment_error', __( 'There was an error processing your payment, please ensure you have entered your card number correctly.', 'invoicing' ) );
                }

                wpinv_record_gateway_error( __( 'Stripe Error', 'invoicing' ), sprintf( __( 'There was an error while processing a Stripe payment. Payment data: %s', 'invoicing' ), json_encode( $err ) ), 0 );

                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            } catch ( \Stripe\Error\ApiConnection $e ) {
                $body = $e->getJsonBody();
                $err  = $body['error'];

                wpinv_set_error( 'payment_error', __( 'There was an error processing your payment (Stripe\'s API is down), please try again', 'invoicing' ) );
                wpinv_record_gateway_error( __( 'Stripe Error', 'invoicing' ), sprintf( __( 'There was an error processing your payment (Stripe\'s API was down). Error: %s', 'invoicing' ), json_encode( $err['message'] ) ), 0 );

                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            } catch ( \Stripe\Error\InvalidRequest $e ) {
                $body = $e->getJsonBody();
                $err  = $body['error'];

                // Bad Request of some sort. Maybe Christoff was here ;)
                if( isset( $err['message'] ) ) {
                    wpinv_set_error( 'request_error', $err['message'] );
                } else {
                    wpinv_set_error( 'request_error', __( 'The Stripe API request was invalid, please try again', 'invoicing' ) );
                }

                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            } catch ( \Stripe\Error\Api $e ) {
                $body = $e->getJsonBody();
                $err  = $body['error'];

                if( isset( $err['message'] ) ) {
                    wpinv_set_error( 'request_error', $err['message'] );
                } else {
                    wpinv_set_error( 'request_error', __( 'The Stripe API request was invalid, please try again', 'invoicing' ) );
                }
                
                wpinv_set_error( 'request_error', sprintf( __( 'The Stripe API request was invalid, please try again. Error: %s', 'invoicing' ), json_encode( $err['message'] ) ) );

                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            } catch ( \Stripe\Error\Authentication $e ) {
                $body = $e->getJsonBody();
                $err  = $body['error'];

                // Authentication error. Stripe keys in settings are bad.
                if( isset( $err['message'] ) ) {
                    wpinv_set_error( 'request_error', $err['message'] );
                } else {
                    wpinv_set_error( 'api_error', __( 'The API keys entered in settings are incorrect', 'invoicing' ) );
                }

                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            } catch ( Exception $e ) {
                // some sort of other error
                $body = $e->getJsonBody();
                $err  = $body['error'];
                if( isset( $err['message'] ) ) {
                    wpinv_set_error( 'request_error', $err['message'] );
                } else {
                    wpinv_set_error( 'api_error', __( 'Something went wrong.', 'invoicing' ) );
                }

                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            }
        } else {
            wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
        }
    }
}
add_action( 'wpinv_gateway_stripe', 'wpinv_process_stripe_payment' );

// IPN event listener.
function wpinv_process_stripe_ipn() {    
    wpinv_stripe_set_api_key();
    
    // Retrieve the request's body and parse it as JSON.
    $body       = @file_get_contents( 'php://input' );
    
    $event_json = json_decode( $body );

    if ( isset( $event_json->id ) ) {
        status_header( 200 );
        
        $event  = \Stripe\Event::retrieve( $event_json->id );

        $data   = $event->data->object;
        
        if ( !empty( $data->subscription ) ) {
            wpinv_recurring_process_stripe_ipn( $event );
        }
        
        switch ( $event->type ) {
            case 'charge.refunded' :
                $charge = $data;
                
                if( $charge->refunded ) {
                    $invoice_id = wpinv_get_id_by_transaction_id( $charge->id );

                    if ( $invoice_id ) {
                        wpinv_update_payment_status( $invoice_id, 'refunded' );
                        wpinv_insert_payment_note( $invoice_id, __( 'Charge refunded in Stripe.', 'invoicing' ) );
                    }
                }

                break;
        }

        do_action( 'wpinv_stripe_event_' . $event->type, $event );

        die( '1' ); // Completed successfully
    } else {
        status_header( 500 );
        die( '-1' ); // Failed
    }
    
    die( '-2' ); // Failed
}
add_action( 'wpinv_verify_stripe_ipn', 'wpinv_process_stripe_ipn' );

function wpinv_recurring_process_stripe_ipn( $event ) {
    $data = $event->data->object;

    if ( empty( $data->subscription ) ) {
        return;
    }

    $subscription = wpinv_get_subscription( $data->subscription, true );

    if ( !$subscription || ( isset( $subscription->ID ) && $subscription->ID < 1 ) ) {
        $subscription = wpinv_stripe_get_subscription( $data->customer, $data->subscription );

        if ( !$subscription || $subscription->ID < 1 ) {
            return;
        }
    }

    switch ( $event->type ) {
        case 'invoice.payment_failed' :
            $subscription->failing_subscription();

            do_action( 'wpinv_recurring_payment_failed', $subscription );
            do_action( 'wpinv_recurring_stripe_event_' . $event->type, $event );
            break;
        case 'invoice.payment_succeeded' :
            $args = array();
            $args['amount']         = $data->total / 100;
            $args['transaction_id'] = $data->charge;
            $signup_date            = strtotime( $subscription->get_subscription_created() );
            $today                  = date( 'Y-n-d', $signup_date ) == date( 'Y-n-d', $data->date );

            // Look to see if payment is same day as signup and we have set the transaction ID on the parent payment yet
            if( $today ) {
                // This is the first signup payment
                wpinv_set_payment_transaction_id( $subscription->ID, $args['transaction_id'] );
            } else {
                // This is a renewal charge
                $invoice = wpinv_recurring_add_subscription_payment( $subscription->ID, $args );
                $invoice->renew_subscription();
            }

            do_action( 'wpinv_recurring_stripe_event_' . $event->type, $event );

            die( 'Invoicing Recurring: ' . $event->type );
            break;
        case 'customer.subscription.created' :
            do_action( 'wpinv_recurring_stripe_event_' . $event->type, $event );

            die( 'Invoicing Recurring: ' . $event->type );
            break;
        case 'customer.subscription.deleted' :
            $subscription->cancel_subscription();

            do_action( 'wpinv_recurring_stripe_event_' . $event->type, $event );

            die( 'Invoicing Recurring: ' . $event->type );
            break;
        case 'charge.refunded' :
            do_action( 'wpinv_recurring_stripe_event_' . $event->type, $event );
            break;
    }
}

function wpinv_stripe_js_vars( $vars ) {
    if ( wpinv_is_checkout() ) {
        $invoice = wpinv_get_invoice_cart();
        
        if ( !empty( $invoice ) ) {
            wp_register_script( 'stripe-checkout', 'https://checkout.stripe.com/checkout.js', array( 'jquery' ), WPINV_VERSION );
            wp_enqueue_script( 'stripe-checkout' );
        
            $publishable_key = wpinv_is_test_mode( 'stripe' ) ? wpinv_get_option( 'stripe_test_publishable_key' ) : wpinv_get_option( 'stripe_live_publishable_key' );
            
            $vars['stripePublishableKey']   = $publishable_key;
            $vars['stripeName']             = wpinv_get_business_name();
            $vars['stripeEmail']            = $invoice->get_email();
            $vars['stripeDescription']      = apply_filters( 'wpinv_stripe_purchase_description', $invoice->get_subscription_name() . ' ( ' . $invoice->get_number() . ' )' );
            $vars['stripeCurrency']         = $invoice->get_currency();
        }
    }
    
    return $vars;
}
add_filter( 'wpinv_front_js_localize', 'wpinv_stripe_js_vars', 10, 1 );

function wpinv_stripe_process_post_data( $purchase_data ) {
    if ( !isset( $_POST['card_name'] ) || strlen( trim( $_POST['card_name'] ) ) == 0 )
        wpinv_set_error( 'no_card_name', __( 'Please enter a name for the credit card.', 'invoicing' ) );

    if ( !isset( $_POST['card_number'] ) || strlen( trim( $_POST['card_number'] ) ) == 0 )
        wpinv_set_error( 'no_card_number', __( 'Please enter a credit card number.', 'invoicing' ) );

    if ( !isset( $_POST['card_cvc'] ) || strlen( trim( $_POST['card_cvc'] ) ) == 0 )
        wpinv_set_error( 'no_card_cvc', __( 'Please enter a CVC/CVV for the credit card.', 'invoicing' ) );

    if ( !isset( $_POST['card_exp_month'] ) || strlen( trim( $_POST['card_exp_month'] ) ) == 0 )
        wpinv_set_error( 'no_card_exp_month', __( 'Please enter a expiration month.', 'invoicing' ) );

    if ( !isset( $_POST['card_exp_year'] ) || strlen( trim( $_POST['card_exp_year'] ) ) == 0 )
        wpinv_set_error( 'no_card_exp_year', __( 'Please enter a expiration year.', 'invoicing' ) );

    $card_data = array(
        'number'          => $purchase_data['card_info']['card_number'],
        'name'            => $purchase_data['card_info']['card_name'],
        'exp_month'       => $purchase_data['card_info']['card_exp_month'],
        'exp_year'        => $purchase_data['card_info']['card_exp_year'],
        'cvc'             => $purchase_data['card_info']['card_cvc'],
        'address_line1'   => $purchase_data['card_info']['card_address'],
        'address_line2'   => $purchase_data['card_info']['card_address_2'],
        'address_city'    => $purchase_data['card_info']['card_city'],
        'address_zip'     => $purchase_data['card_info']['card_zip'],
        'address_state'   => $purchase_data['card_info']['card_state'],
        'address_country' => $purchase_data['card_info']['card_country']
    );

    return $card_data;
}

function wpinv_stripe_get_customer_key() {
    $key = '_wpi_stripe_customer_id';
    
    if ( wpinv_is_test_mode( 'stripe' ) ) {
        $key .= '_test';
    }
    
    return $key;
}

function wpinv_is_zero_decimal_currency( $currency = '' ) {
    $ret      = false;
    $currency = !empty( $currency ) ? wpinv_get_currency() : $currency;

    switch( $currency ) {
        case 'BIF' :
        case 'CLP' :
        case 'DJF' :
        case 'GNF' :
        case 'JPY' :
        case 'KMF' :
        case 'KRW' :
        case 'MGA' :
        case 'PYG' :
        case 'RWF' :
        case 'VND' :
        case 'VUV' :
        case 'XAF' :
        case 'XOF' :
        case 'XPF' :
            $ret = true;
            break;

    }

    return $ret;
}

function wpinv_stripe_process_refund( $invoice_id, $new_status, $old_status ) {
    if ( empty( $_POST['wpi_refund_in_stripe'] ) ) {
        return;
    }

    $should_process_refund = 'publish' != $old_status && 'revoked' != $old_status ? false : true;
    $should_process_refund = apply_filters( 'wpinv_stripe_should_process_refund', $should_process_refund, $invoice_id, $new_status, $old_status );

    if( false === $should_process_refund ) {
        return;
    }

    if ( 'refunded' != $new_status ) {
        return;
    }

    $charge_id = wpinv_get_payment_transaction_id( $invoice_id );

    if ( empty( $charge_id ) || $charge_id == $invoice_id ) {

        $notes = wpinv_get_invoice_notes( $invoice_id );
        foreach ( $notes as $note ) {
            if ( preg_match( '/^Stripe Charge ID: ([^\s]+)/', $note->comment_content, $match ) ) {
                $charge_id = $match[1];
                break;
            }
        }
    }

    // Bail if no charge ID was found
    if( empty( $charge_id ) ) {
        return;
    }

    wpinv_stripe_set_api_key();

    $ch = \Stripe\Charge::retrieve( $charge_id );

    try {
        $ch->refund();
        wpinv_insert_payment_note( $invoice_id, __( 'Charge refunded in Stripe', 'invoicing' ) );
    } catch ( Exception $e ) {

        // some sort of other error
        $body = $e->getJsonBody();
        $err  = $body['error'];

        if( isset( $err['message'] ) ) {
            $error = $err['message'];
        } else {
            $error = __( 'Something went wrong while refunding the Charge in Stripe.', 'invoicing' );
        }

        wp_die( $error, __( 'Error', 'invoicing' ) , array( 'response' => 400 ) );
    }

    do_action( 'wpinv_stripe_payment_refunded', $invoice_id );
}
add_action( 'wpinv_update_payment_status', 'wpinv_stripe_process_refund', 200, 3 );

function wpinv_stripe_get_payment_transaction_id( $invoice_id ) {
    $txn_id = '';
    $notes  = wpinv_get_invoice_notes( $invoice_id );

    foreach ( $notes as $note ) {
        if ( preg_match( '/^Stripe Charge ID: ([^\s]+)/', $note->comment_content, $match ) ) {
            $txn_id = $match[1];
            continue;
        }
    }

    return apply_filters( 'wpinv_stripe_set_payment_transaction_id', $txn_id, $invoice_id );
}
add_filter( 'wpinv_get_payment_transaction_id-stripe', 'wpinv_stripe_get_payment_transaction_id', 10, 1 );

function wpinv_stripe_link_transaction_id( $transaction_id, $invoice_id ) {
    $test = wpinv_is_test_mode( 'stripe' ) ? 'test/' : '';
    $url  = '<a href="https://dashboard.stripe.com/' . $test . 'payments/' . $transaction_id . '" target="_blank">' . $transaction_id . '</a>';

    return apply_filters( 'wpinv_stripe_link_payment_details_transaction_id', $url );
}
add_filter( 'wpinv_payment_details_transaction_id-stripe', 'wpinv_stripe_link_transaction_id', 10, 2 );

function wpinv_stripe_get_secret_key( $mode = '' ) {
    switch ( $mode ) {
        case 'test':
            $option = 'test';
        break;
        case 'live':
            $option = 'live';
        break;
        default:
            $option = wpinv_is_test_mode( 'stripe' ) ? 'test' : 'live';
        break;
    }
    
    return wpinv_get_option( 'stripe_' . $option . '_secret_key' );
}

function wpinv_stripe_get_subscription( $customer_id = '', $subscription_id = '' ) {
    $subscription = false;

    try {
        // Update the customer to ensure their card data is up to date
        $customer     = \Stripe\Customer::retrieve( $customer_id );
        $stripe_sub   = $customer->subscriptions->retrieve( $subscription_id );
        $plan_name    = $stripe_sub->plan->name;

        // Look up payment by email
        $invoices = wpinv_get_invoices( array(
            's'        => $customer->email,
            'status'   => 'publish',
            'number'   => 100,
            'output'   => 'payments'
        ) );

        if ( $invoices ) {
            foreach( $invoices as $invoice ) {
                if ( !is_array( $invoice->cart_details ) ) {
                    continue;
                }

                if ( !get_post_meta( $invoice->ID, '_wpi_subscription_payment', true ) ) {
                    continue;
                }

                foreach( $invoice->cart_details as $item ) {
                    $slug = get_post_field( 'post_name', $item['id'] );

                    if ( $slug != $plan_name ) {
                        continue;
                    }

                    // We have found a matching subscription, let's look up the sub record and fix it
                    $subs   = wpinv_get_subscriptions( array( 'parent_invoice_id' => $invoice->ID, 'numberposts' => 1 ) );
                    $sub    = reset( $subs );

                    if ( $sub && $sub->id > 0 ) {
                        $sub->update_subscription( array( 'profile_id' => $subscription_id ) );
                        $subscription = $sub;
                        break;
                    }
                }
            }
        }
        // No customer found
    } catch ( Exception $e ) {
    }

    return $subscription;
}

function wpinv_stripe_subscription_can_update( $ret, $subscription ) {
    if( $subscription->gateway === 'stripe' && ! empty( $subscription->profile_id ) && ( 'active' === $subscription->status || 'failing' === $subscription->status ) ) {
        return true;
    }
    return $ret;
}
add_filter( 'wpinv_subscription_can_update', 'wpinv_stripe_subscription_can_update', 10, 2 );

function wpinv_stripe_subscription_can_cancel( $ret, $subscription ) {
    if( $subscription->gateway === 'stripe' && ! empty( $subscription->profile_id ) && 'active' === $subscription->status ) {
        return true;
    }
    return $ret;
}
add_filter( 'wpinv_subscription_can_cancel', 'wpinv_stripe_subscription_can_cancel', 10, 2 );

function wpinv_stripe_set_api_key() {
    global $wpinv_api_load;
    
    if ( !empty( $wpinv_api_load ) ) {
        return true;
    }
    
    try {
        \Stripe\Stripe::setApiKey( wpinv_stripe_get_secret_key() );
        
        $wpinv_api_load = true;
        
        return true;
    } catch ( Exception $e ) {
        // some sort of other error
        $body = $e->getJsonBody();
        $err  = $body['error'];
        
        if ( isset( $err['message'] ) ) {
            wpinv_set_error( 'request_error', $err['message'] );
        } else {
            wpinv_set_error( 'api_error', __( 'Something went wrong.', 'invoicing' ) );
        }
    }
    
    return false;
}

function wpinv_stripe_get_customer( $user_id, $user_email = '' ) {
    wpinv_stripe_set_api_key();
    
    if ( $user_id && $user = get_user_by( 'id', $user_id ) ) {
        $user_id    = $user->ID;
        $user_email = $user->user_email;
    } else if ( $user_email && $user = get_user_by( 'email', $user_email ) ) {
        $user_id    = $user->ID;
        $user_email = $user->user_email;
    } else {
        return false;
    }
    
    // Look for a customer ID from the stand alone Stripe gateway
    $customer_id = get_user_meta( $user_id, wpinv_stripe_get_customer_key(), true );

    if ( empty( $customer_id ) ) {
        $customer = wpinv_stripe_create_customer( $user_email, $user_id );

        if ( $customer ) {
            $set_id      = true;
            $customer_id = $customer->id;
        } else {
            return false;
        }
    }

    try {
        // Update the customer to ensure their card data is up to date
        $customer = \Stripe\Customer::retrieve( $customer_id );

        if ( isset( $customer->deleted ) && $customer->deleted ) {
            // This customer was deleted so make a new one
            $set_id   = true;
            $customer = wpinv_stripe_create_customer( $user_email, $user_id );
        }
        // No customer found
    } catch ( Exception $e ) {
        $customer = false;
        wpinv_error_log( $e->getMessage(), __( 'Stripe customer create', 'invoicing' ) );
    }
    
    if ( $customer ) {
        // Ensure that one-time purchases through Stripe use the same customer ID
        update_user_meta( $user_id, wpinv_stripe_get_customer_key(), $customer->id );
    }

    return $customer;

}

function wpinv_stripe_create_customer( $user_email, $user_id = '' ) {
    try {
        wpinv_stripe_set_api_key();
        
        if ( $user_email && $user = get_user_by( 'email', $user_email ) ) {
            $user_id    = $user->ID;
            $user_email = $user->user_email;
        } else if ( $user_id && $user = get_user_by( 'id', $user_id ) ) {
            $user_id    = $user->ID;
            $user_email = $user->user_email;
        } else {
            return false;
        }
        
        // Create a customer first so we can retrieve them later for future payments
        $customer = \Stripe\Customer::create( array(
                'description' => $user_email,
                'email'       => $user_email,
                'metadata'    => array(
                    'wpi_user_id' => $user_id
                )
            )
        );

    } catch ( Exception $e ) {
        wpinv_error_log( $e->getMessage(), __( 'Stripe customer create', 'invoicing' ) );
        $customer = false;
    }

    return $customer;
}

function wpinv_stripe_create_plan( $args = array() ) {
    wpinv_stripe_set_api_key();
    
    try {
        if ( isset( $args['trial_period_days'] ) && empty( $args['trial_period_days'] ) ) {
            unset( $args['trial_period_days'] );
        }
        
        $plan    = \Stripe\Plan::create( $args );
        $plan_id = is_array( $plan ) ? $plan['id'] : $plan->id;
    } catch ( Exception $e ) {
        wpinv_error_log( $e->getMessage(), __( 'Stripe plan create', 'invoicing' ) );
        $plan_id = false;
    }

    return $plan_id;
}

function wpinv_stripe_get_plan_id( $subscription = array(), $invoice = array() ) {
    wpinv_stripe_set_api_key();

    $number             = $subscription['number'];
    $id                 = $subscription['id'];
    $name               = $subscription['name'];
    $period             = $subscription['period'];
    $interval           = $subscription['interval'];
    $bill_times         = $subscription['bill_times'];
    $initial_amount     = $subscription['initial_amount'];
    $recurring_amount   = $subscription['recurring_amount'];
    $currency           = $subscription['currency'];
    $trial_period_days  = absint( $subscription['trial_period_days'] );
    
    $plan_id            = sanitize_title( $name ) . '_' . $id . '_' . $recurring_amount . '_' . $period . '_' . $bill_times;
    if ( !empty( $trial_period_days ) ) {
        $plan_id        .= '_' . $trial_period_days;
    }
    
    $plan_id            = sanitize_key( $plan_id );
    try {
        $plan       = \Stripe\Plan::retrieve( $plan_id );
        $plan_id    = is_array( $plan ) ? $plan['id'] : $plan->id;
    } catch ( Exception $e ) {
        $args = array(
            'id'                => $plan_id,
            'name'              => $name,
            'amount'            => $recurring_amount * 100,
            'currency'          => $currency,
            'interval'          => $period,
            'interval_count'    => $interval,
            'trial_period_days' => $trial_period_days
        );

        $plan_id = wpinv_stripe_create_plan( $args );
    }
    
    return $plan_id;
}

function wpinv_stripe_subscription_record_signup( $subscription, $invoice ) {
    if ( empty( $invoice ) ) {
        return;
    }
    
    $invoice_id         = $invoice->ID;
    $customer_id        = $subscription->customer;
    $subscription_id    = $subscription->id;

    wpinv_update_payment_status( $invoice_id, 'publish' );
    wpinv_insert_payment_note( $invoice_id, sprintf( __( 'Stripe Subscription ID: %s', 'invoicing' ) , $subscription_id ) );
    
    // Set subscription_payment
    wpinv_update_invoice_meta( $invoice_id, '_wpi_subscription_payment', true );
    
    $subscription = wpinv_stripe_get_subscription( $customer_id, $subscription_id );
    if ( false === $subscription ) {
        return;
    }
    
    $subscription_item_id   = $invoice->get_recurring();
    $subscription_item      = new WPInv_Item( $invoice->get_recurring() );
    
    if ( !empty( $subscription_item ) ) {
        $args = array(
            'profile_id'        => $subscription_id,
            'item_id'           => $subscription_item_id,
            'initial_amount'    => $invoice->get_total(),
            'recurring_amount'  => $invoice->get_recurring_details( 'total' ),
            'period'            => $subscription_item->get_recurring_period(),
            'interval'          => $subscription_item->get_recurring_interval(),
            'bill_times'        => $subscription_item->get_recurring_limit(),
            'expiration'        => $invoice->get_new_expiration( $subscription_item_id ),
            'status'            => 'active',
            'created'           => current_time( 'mysql', 0 )
        );
        
        // Retrieve pending subscription from database and update it's status to active and set proper profile ID
        $subscription->update_subscription( $args );
    }
}