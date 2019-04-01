<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'wpinv_authorizenet_support_subscription', '__return_true' );

function wpinv_authorizenet_cc_form( $invoice_id ) {
    $invoice = wpinv_get_invoice( $invoice_id );
    $cc_owner = !empty( $invoice ) ? esc_attr( $invoice->get_user_full_name() ) : '';
    ?>
    <div id="authorizenet_cc_form" class="form-horizontal wpi-cc-form panel panel-default">
        <div class="panel-heading"><h3 class="panel-title"><?php _e( 'Card Details', 'invoicing' ) ;?></h3></div>
        <div class="panel-body">
            <div class="form-group required">
              <label for="auth-input-cc-owner" class="col-sm-3 control-label"><?php _e( 'Card Owner', 'invoicing' ) ;?></label>
              <div class="col-sm-5">
                <input type="text" class="form-control" id="auth-input-cc-owner" placeholder="<?php esc_attr_e( 'Card Owner', 'invoicing' ) ;?>" value="<?php echo $cc_owner;?>" name="authorizenet[cc_owner]">
              </div>
            </div>
            <div class="form-group required">
              <label for="auth-input-cc-number" class="col-sm-3 control-label"><?php _e( 'Card Number', 'invoicing' ) ;?></label>
              <div class="col-sm-5">
                <input type="text" class="form-control" id="auth-input-cc-number" placeholder="<?php esc_attr_e( 'Card Number', 'invoicing' ) ;?>" value="" name="authorizenet[cc_number]">
              </div>
            </div>
            <div class="form-group required">
              <label for="auth-input-cc-expire-date" class="col-sm-3 control-label"><?php _e( 'Card Expiry Date', 'invoicing' ) ;?></label>
              <div class="col-sm-2">
                <select class="form-control" id="auth-input-cc-expire-date" name="authorizenet[cc_expire_month]">
                    <?php for ( $i = 1; $i <= 12; $i++ ) { $value = str_pad( $i, 2, '0', STR_PAD_LEFT ); ?>
                    <option value="<?php echo $value;?>"><?php echo $value;?></option>
                    <?php } ?>
                </select>
               </div>
               <div class="col-sm-3">
                <select class="form-control" name="authorizenet[cc_expire_year]">
                    <?php $year = date( 'Y' ); for ( $i = $year; $i <= ( $year + 10 ); $i++ ) { ?>
                    <option value="<?php echo $i;?>"><?php echo $i;?></option>
                    <?php } ?>
                </select>
              </div>
            </div>
            <div class="form-group required">
              <label for="auth-input-cc-cvv2" class="col-sm-3 control-label"><?php _e( 'Card Security Code (CVV2)', 'invoicing' ) ;?></label>
              <div class="col-sm-5">
                <input type="text" class="form-control" id="auth-input-cc-cvv2" placeholder="<?php esc_attr_e( 'Card Security Code (CVV2)', 'invoicing' ) ;?>" value="" name="authorizenet[cc_cvv2]"">
              </div>
            </div>
      </div>
    </div>
    <?php
}
add_action( 'wpinv_authorizenet_cc_form', 'wpinv_authorizenet_cc_form', 10, 1 );

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
        'status'        => 'wpi-pending'
    );

    // Record the pending payment
    $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );

    if ( !empty( $invoice ) ) {
        $authorizenet_card  = !empty( $_POST['authorizenet'] ) ? $_POST['authorizenet'] : array();
        $card_defaults      = array(
            'cc_owner'          => $invoice->get_user_full_name(),
            'cc_number'         => false,
            'cc_expire_month'   => false,
            'cc_expire_year'    => false,
            'cc_cvv2'           => false,
        );
        $authorizenet_card = wp_parse_args( $authorizenet_card, $card_defaults );

        if ( empty( $authorizenet_card['cc_owner'] ) ) {
            wpinv_set_error( 'empty_card_name', __( 'You must enter the name on your card!', 'invoicing'));
        }
        if ( empty( $authorizenet_card['cc_number'] ) ) {
            wpinv_set_error( 'empty_card', __( 'You must enter a card number!', 'invoicing'));
        }
        if ( empty( $authorizenet_card['cc_expire_month'] ) ) {
            wpinv_set_error( 'empty_month', __( 'You must enter an card expiration month!', 'invoicing'));
        }
        if ( empty( $authorizenet_card['cc_expire_year'] ) ) {
            wpinv_set_error( 'empty_year', __( 'You must enter an card expiration year!', 'invoicing'));
        }
        if ( empty( $authorizenet_card['cc_cvv2'] ) ) {
            wpinv_set_error( 'empty_cvv2', __( 'You must enter a valid CVV2!', 'invoicing' ) );
        }

        $errors = wpinv_get_errors();

        if ( empty( $errors ) ) {
            $invoice_id = $invoice->ID;
            $quantities_enabled = wpinv_item_quantities_enabled();
            $use_taxes          = wpinv_use_taxes();

            $authorizeAIM = wpinv_authorizenet_AIM();
            $authorizeAIM->first_name       = wpinv_utf8_substr( $invoice->get_first_name(), 0, 50 );
            $authorizeAIM->last_name        = wpinv_utf8_substr( $invoice->get_last_name(), 0, 50 );
            $authorizeAIM->company          = wpinv_utf8_substr( $invoice->company, 0, 50 );
            $authorizeAIM->address          = wpinv_utf8_substr( wp_strip_all_tags( $invoice->get_address(), true ), 0, 60 );
            $authorizeAIM->city             = wpinv_utf8_substr( $invoice->city, 0, 40 );
            $authorizeAIM->state            = wpinv_utf8_substr( $invoice->state, 0, 40 );
            $authorizeAIM->zip              = wpinv_utf8_substr( $invoice->zip, 0, 40 );
            $authorizeAIM->country          = wpinv_utf8_substr( $invoice->country, 0, 60 );
            $authorizeAIM->phone            = wpinv_utf8_substr( $invoice->phone, 0, 25 );
            $authorizeAIM->email            = wpinv_utf8_substr( $invoice->get_email(), 0, 255 );
            $authorizeAIM->amount           = wpinv_sanitize_amount( $invoice->get_total() );
            $authorizeAIM->card_num         = str_replace( ' ', '', sanitize_text_field( $authorizenet_card['cc_number'] ) );
            $authorizeAIM->exp_date         = sanitize_text_field( $authorizenet_card['cc_expire_month'] ) . sanitize_text_field( $authorizenet_card['cc_expire_year'] );
            $authorizeAIM->card_code        = sanitize_text_field( $authorizenet_card['cc_cvv2'] );
            $authorizeAIM->invoice_num      = $invoice->ID;

            $item_desc = array();
            foreach ( $invoice->get_cart_details() as $item ) {            
                $quantity       = $quantities_enabled && !empty( $item['quantity'] ) && $item['quantity'] > 0 ? $item['quantity'] : 1;
                $item_name      = wpinv_utf8_substr( $item['name'], 0, 31 );
                $item_desc[]    = $item_name . ' (' . $quantity . 'x ' . wpinv_price( wpinv_format_amount( $item['item_price'] ) ) . ')';

                $authorizeAIM->addLineItem( $item['id'], $item_name, '', $quantity, $item['item_price'], ( $use_taxes && !empty( $item['tax'] ) && $item['tax'] > 0 ? 'Y' : 'N' ) );
            }

            $item_desc = '#' . $invoice->get_number() . ': ' . implode( ', ', $item_desc );

            if ( $use_taxes && $invoice->get_tax() > 0 ) {
                $authorizeAIM->tax  = $invoice->get_tax();

                $item_desc .= ', ' . wp_sprintf( __( 'Tax: %s', 'invoicing' ), $invoice->get_tax( true ) );
            }

            if ( $invoice->get_discount() > 0 ) {
                $item_desc .= ', ' . wp_sprintf( __( 'Discount: %s', 'invoicing' ), $invoice->get_discount( true ) );
            }

            $item_description = wpinv_utf8_substr( $item_desc, 0, 255 );
            $item_description = html_entity_decode( $item_desc , ENT_QUOTES, 'UTF-8' );

            $authorizeAIM->description  = wpinv_utf8_substr( $item_description, 0, 255 );

            $is_recurring = $invoice->is_recurring(); // Recurring payment.

            if ( $is_recurring ) {
                $authorizeAIM->recurring_billing = true;
            }

            try {

                if ( $is_recurring ) {
                    $trx_type = wpinv_get_option('authorizenet_transaction_type_recurring', 'authorize_only');
                    if('authorize_capture' == $trx_type){
                        $response = $authorizeAIM->authorizeAndCapture();
                    } else {
                        $response = $authorizeAIM->authorizeOnly();
                    }
                } else {
                    $trx_type = wpinv_get_option('authorizenet_transaction_type', 'authorize_capture');
                    if('authorize_capture' == $trx_type){
                        $response = $authorizeAIM->authorizeAndCapture();
                    } else {
                        $response = $authorizeAIM->authorizeOnly();
                    }
                }

                if ( $response->approved || $response->held ) {
                    if ( $response->approved ) {
                        wpinv_update_payment_status( $invoice_id, 'publish' );
                    }
                    wpinv_set_payment_transaction_id( $invoice_id, $response->transaction_id );

                    wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Authorize.Net payment response: %s', 'invoicing' ), $response->response_reason_text ), '', '', true );
                    wpinv_insert_payment_note( $invoice_id, wp_sprintf( __( 'Authorize.Net payment: Transaction ID %s, Transaction Type %s, Authorization Code %s', 'invoicing' ), $response->transaction_id, strtoupper( $response->transaction_type ), $response->authorization_code ), '', '', true );

                    do_action( 'wpinv_authorizenet_handle_response', $response, $invoice, $authorizenet_card );

                    wpinv_clear_errors();
                    wpinv_empty_cart();

                    wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );
                } else {
                    if ( !empty( $response->response_reason_text ) ) {
                        $error = __( $response->response_reason_text, 'invoicing' );
                    } else if ( !empty( $response->error_message ) ) {
                        $error = __( $response->error_message, 'invoicing' );
                    } else {
                        $error = wp_sprintf( __( 'Error data: %s', 'invoicing' ), print_r( $response, true ) );
                    } 

                    $error = wp_sprintf( __( 'Authorize.Net payment error occurred. %s', 'invoicing' ), $error );

                    wpinv_set_error( 'payment_error', $error );
                    wpinv_record_gateway_error( $error, $response );
                    wpinv_insert_payment_note( $invoice_id, $error, '', '', true );

                    wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                }
            } catch ( AuthorizeNetException $e ) {
                wpinv_set_error( 'request_error', $e->getMessage() );
                wpinv_record_gateway_error( wp_sprintf( __( 'Authorize.Net payment error occurred. %s', 'invoicing' ), $e->getMessage() ) );
                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            }
        } else {
            wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
        }
    } else {
        wpinv_record_gateway_error( wp_sprintf( __( 'Authorize.Net payment error occurred. Payment creation failed while processing a Authorize.Net payment. Payment data: %s', 'invoicing' ), print_r( $payment_data, true ) ), $invoice );
        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
    }
}
add_action( 'wpinv_gateway_authorizenet', 'wpinv_process_authorizenet_payment' );

function wpinv_authorizenet_cancel_subscription( $subscription_id = '' ) {
    if ( empty( $subscription_id ) ) {
        return false;
    }

    try {
        $authnetXML = wpinv_authorizenet_XML();
        $authnetXML->ARBCancelSubscriptionRequest( array( 'subscriptionId' => $subscription_id ) );
        return $authnetXML->isSuccessful();
    } catch( Exception $e ) {
        wpinv_error_log( $e->getMessage(), __( 'Authorize.Net cancel subscription', 'invoicing' ) );
    }

    return false;
}

function wpinv_recurring_cancel_authorizenet_subscription( $subscription, $valid = false ) {
    if ( ! empty( $valid ) && ! empty( $subscription->profile_id ) ) {
        return wpinv_authorizenet_cancel_subscription( $subscription->profile_id );
    }
    
    return false;
}
add_action( 'wpinv_recurring_cancel_authorizenet_subscription', 'wpinv_recurring_cancel_authorizenet_subscription', 10, 2 );

function wpinv_authorizenet_valid_ipn( $md5_hash, $transaction_id, $amount ) {
    $authorizenet_md5_hash = wpinv_get_option( 'authorizenet_md5_hash' );
    if ( empty( $authorizenet_md5_hash ) ) {
        return true;
    }

    $compare_md5 = strtoupper( md5( $authorizenet_md5_hash . $transaction_id . $amount ) );

    return hash_equals( $compare_md5, $md5_hash );
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

function wpinv_authorizenet_XML() {
    if ( !class_exists( 'AuthnetXML' ) ) {
        require_once plugin_dir_path( WPINV_PLUGIN_FILE ) . 'includes/gateways/authorizenet/Authorize.Net-XML/AuthnetXML.class.php';
    }
    
    $authnetXML = new AuthnetXML( wpinv_get_option( 'authorizenet_login_id' ), wpinv_get_option( 'authorizenet_transaction_key' ), (bool)wpinv_is_test_mode( 'authorizenet' ) );
    
    return $authnetXML;
}

function wpinv_authorizenet_handle_response( $response, $invoice, $card_info = array() ) {
    if ( empty( $response ) || empty( $invoice ) ) {
        return false;
    }

    if ( $invoice->is_recurring() && !empty( $response->approved ) ) {
        $subscription = wpinv_authorizenet_create_new_subscription( $invoice, $response, $card_info );
        $success = false;
        if ( wpinv_is_test_mode( 'authorizenet' ) ) {
            $success = true;
        } else {
            $success = $subscription->isSuccessful();
        }

        if ( !empty( $subscription ) && $success ) {
            do_action( 'wpinv_recurring_post_create_subscription', $subscription, $invoice, 'authorizenet' );

            wpinv_authorizenet_subscription_record_signup( $subscription, $invoice );

            do_action( 'wpinv_recurring_post_record_signup', $subscription, $invoice, 'authorizenet' );
        } else {
            if ( isset( $subscription->messages->message ) ) {
                $error = $subscription->messages->message->code . ': ' . $subscription->messages->message->text;
                wpinv_set_error( 'wpinv_authorize_recurring_error', $error, 'invoicing' );
            } else {
                $error = __( 'Your subscription cannot be created due to an error.', 'invoicing' );
                wpinv_set_error( 'wpinv_authorize_recurring_error', $error );
            }

            wpinv_record_gateway_error( $error, $subscription );

            wpinv_insert_payment_note( $invoice->ID, wp_sprintf( __( 'Authorize.Net subscription error occurred. %s', 'invoicing' ), $error ), '', '', true );
        }
    }
}
add_action( 'wpinv_authorizenet_handle_response', 'wpinv_authorizenet_handle_response', 10, 3 );

function wpinv_authorizenet_create_new_subscription( $invoice, $response = array(), $card_info = array() ) {
    if ( empty( $invoice ) ) {
        return false;
    }

    $params = wpinv_authorizenet_generate_subscription_params( $invoice, $card_info, $response );

    try {
        $authnetXML = wpinv_authorizenet_XML();
        $authnetXML->ARBCreateSubscriptionRequest( $params );
    } catch( Exception $e ) {
        $authnetXML = array();
        wpinv_error_log( $e->getMessage(), __( 'Authorize.Net cancel subscription', 'invoicing' ) );
    }

    return $authnetXML;
}

function wpinv_authorizenet_generate_subscription_params( $invoice, $card_info = array(), $response = array() ) {
    if ( empty( $invoice ) ) {
        return false;
    }

    $subscription_item = $invoice->get_recurring( true );
    if ( empty( $subscription_item->ID ) ) {
        return false;
    }

    $item = $invoice->get_recurring( true );

    if ( empty( $item ) ) {
        $name = '';
    }

    if ( !( $name = $item->get_name() ) ) {
        $name = $item->post_name;
    }

    $card_details       = wpinv_authorizenet_generate_card_info( $card_info );
    $subscription_name  = $invoice->get_subscription_name();
    $initial_amount     = wpinv_round_amount( $invoice->get_total() );
    $recurring_amount   = wpinv_round_amount( $invoice->get_recurring_details( 'total' ) );
    $interval           = $subscription_item->get_recurring_interval();
    $period             = $subscription_item->get_recurring_period();
    $bill_times         = (int)$subscription_item->get_recurring_limit();
    $bill_times         = $bill_times > 0 ? $bill_times : 9999;

    $time_period        = wpinv_authorizenet_get_time_period( $interval, $period );
    $interval           = $time_period['interval'];
    $period             = $time_period['period'];

    $current_tz = date_default_timezone_get();
    date_default_timezone_set( 'America/Denver' ); // Set same timezone as Authorize's server (Mountain Time) to prevent conflicts.
    $today = date( 'Y-m-d' );
    date_default_timezone_set( $current_tz );

    $free_trial = $invoice->is_free_trial();
    if ( $free_trial && $subscription_item->has_free_trial() ) {
        $trial_interval    = $subscription_item->get_trial_interval();
        $trial_period      = $subscription_item->get_trial_period( true );
    }

    $subscription = array();
    $subscription['name'] = $subscription_name;

    $subscription['paymentSchedule'] = array(
        'interval'         => array( 'length' => $interval, 'unit' => $period ),
        'startDate'        => $today,
        'totalOccurrences' => $bill_times,
        'trialOccurrences' => $free_trial || ( $initial_amount != $recurring_amount ) ? 1 : 0,
    );

    $subscription['amount'] = $recurring_amount;
    $subscription['trialAmount'] = $initial_amount;
    $subscription['payment'] = array( 'creditCard' => $card_details );
    $subscription['order'] = array( 'invoiceNumber' => $invoice->ID, 'description' => '#' . $invoice->get_number() );
    $subscription['customer'] = array( 'id' => $invoice->get_user_id(), 'email' => $invoice->get_email(), 'phoneNumber' => $invoice->phone );

    $subscription['billTo'] = array(
        'firstName' => $invoice->get_first_name(),
        'lastName'  => $invoice->get_last_name(),
        'company'   => $invoice->company,
        'address'   => wp_strip_all_tags( $invoice->get_address(), true ),
        'city'      => $invoice->city,
        'state'     => $invoice->state,
        'zip'       => $invoice->zip,
        'country'   => $invoice->country,
    );

    $params = array( 'subscription' => $subscription );

    return apply_filters( 'wpinv_authorizenet_generate_subscription_params', $params, $invoice, $card_info, $response );
}

function wpinv_authorizenet_generate_card_info( $card_info = array() ) {
    $card_defaults      = array(
        'cc_owner'          => null,
        'cc_number'         => null,
        'cc_expire_month'   => null,
        'cc_expire_year'    => null,
        'cc_cvv2'           => null,
    );
    $card_info = wp_parse_args( $card_info, $card_defaults );

    $card_details = array(
        'cardNumber'     => str_replace( ' ', '', sanitize_text_field( $card_info['cc_number'] ) ),
        'expirationDate' => sanitize_text_field( $card_info['cc_expire_month'] ) . sanitize_text_field( $card_info['cc_expire_year'] ),
        'cardCode'       => sanitize_text_field( $card_info['cc_cvv2'] ),
    );

    return $card_details;
}

function wpinv_authorizenet_subscription_record_signup( $subscription, $invoice ) {
    $parent_invoice_id = absint( $invoice->ID );

    if( empty( $parent_invoice_id ) ) {
        return;
    }

    $invoice = wpinv_get_invoice( $parent_invoice_id );
    if ( empty( $invoice ) ) {
        return;
    }

    $subscriptionId     = (array)$subscription->subscriptionId;
    $subscription_id    = !empty( $subscriptionId[0] ) ? $subscriptionId[0] : $parent_invoice_id;

    $subscription = wpinv_get_authorizenet_subscription( $subscription, $parent_invoice_id );

    if ( false === $subscription ) {
        return;
    }

    // Set payment to complete
    wpinv_update_payment_status( $subscription->parent_payment_id, 'publish' );
    sleep(1);
    wpinv_insert_payment_note( $parent_invoice_id, sprintf( __( 'Authorize.Net Subscription ID: %s', 'invoicing' ) , $subscription_id ), '', '', true );
    update_post_meta($parent_invoice_id,'_wpinv_subscr_profile_id', $subscription_id);

    $status     = 'trialling' == $subscription->status ? 'trialling' : 'active';
    $diff_days  = absint( ( ( strtotime( $subscription->expiration ) - strtotime( $subscription->created ) ) / DAY_IN_SECONDS ) );
    $created    = date_i18n( 'Y-m-d H:i:s' );
    $expiration = date_i18n( 'Y-m-d 23:59:59', ( strtotime( $created ) + ( $diff_days * DAY_IN_SECONDS ) ) );

    // Retrieve pending subscription from database and update it's status to active and set proper profile ID
    $subscription->update( array( 'profile_id' => $subscription_id, 'status' => $status, 'created' => $created, 'expiration' => $expiration ) );
}

function wpinv_authorizenet_validate_checkout( $valid_data, $post ) {
    if ( !empty( $post['wpi-gateway'] ) && $post['wpi-gateway'] == 'authorizenet' ) {
        $error = false;
        
        if ( empty( $post['authorizenet']['cc_owner'] ) ) {
            $error = true;
            wpinv_set_error( 'empty_card_name', __( 'You must enter the name on your card!', 'invoicing'));
        }
        if ( empty( $post['authorizenet']['cc_number'] ) ) {
            $error = true;
            wpinv_set_error( 'empty_card', __( 'You must enter a card number!', 'invoicing'));
        }
        if ( empty( $post['authorizenet']['cc_expire_month'] ) ) {
            $error = true;
            wpinv_set_error( 'empty_month', __( 'You must enter an card expiration month!', 'invoicing'));
        }
        if ( empty( $post['authorizenet']['cc_expire_year'] ) ) {
            $error = true;
            wpinv_set_error( 'empty_year', __( 'You must enter an card expiration year!', 'invoicing'));
        }
        if ( empty( $post['authorizenet']['cc_cvv2'] ) ) {
            $error = true;
            wpinv_set_error( 'empty_cvv2', __( 'You must enter a valid CVV2!', 'invoicing' ) );
        }

        if ( $error ) {
            return;
        }

        $invoice = wpinv_get_invoice_cart();

        if ( !empty( $invoice ) && $subscription_item = $invoice->get_recurring( true ) ) {
            $subscription_item = $invoice->get_recurring( true );

            $interval   = $subscription_item->get_recurring_interval();
            $period     = $subscription_item->get_recurring_period();

            if ( $period == 'D' && ( $interval < 7 || $interval > 365 ) ) {
                wpinv_set_error( 'authorizenet_subscription_error', __( 'Interval Length must be a value from 7 through 365 for day based subscriptions.', 'invoicing' ) );
            }
        }
    }
}
add_action( 'wpinv_checkout_error_checks', 'wpinv_authorizenet_validate_checkout', 11, 2 );

function wpinv_authorizenet_get_time_period( $subscription_interval, $subscription_period ) {
    $subscription_interval = absint( $subscription_interval );

    switch( $subscription_period ) {
        case 'W':
        case 'week':
        case 'weeks':
            $interval = $subscription_interval * 7;
            $period   = 'days';
            break;
        case 'M':
        case 'month':
        case 'months':
            if ( $subscription_interval > 12 ) {
                $subscription_interval = 12;
            }

            $interval = $subscription_interval;
            $period   = 'months';
            
            if ( !( $subscription_interval === 1 || $subscription_interval === 2 || $subscription_interval === 3 || $subscription_interval === 6 || $subscription_interval === 12 ) ) {
                $interval = $subscription_interval * 30;
                $period   = 'days';
            }
            break;
        case 'Y':
        case 'year':
        case 'years':
            $interval = 12;
            $period   = 'months';
            break;
        default :
            $interval = $subscription_interval;
            $period   = 'days';
            break;
    }

    return compact( 'interval', 'period' );
}

function wpinv_authorizenet_process_ipn() {
    if ( !( !empty( $_REQUEST['wpi-gateway'] ) && $_REQUEST['wpi-gateway'] == 'authorizenet' ) ) {
        return;
    }

    $subscription_id = !empty( $_POST['x_subscription_id'] ) ? intval( $_POST['x_subscription_id'] ) : false;

    if ( $subscription_id ) {
        $response_code  = intval( $_POST['x_response_code'] );
        $reason_code    = intval( $_POST['x_response_reason_code'] );

        $subscription = new WPInv_Subscription( $subscription_id, true );

        if ( !$subscription->id ) {
            return;
        }

        if ( 1 == $response_code ) {
            // Approved
            $transaction_id = sanitize_text_field( $_POST['x_trans_id'] );
            $renewal_amount = sanitize_text_field( $_POST['x_amount'] );

            $args = array(
                'amount'         => $renewal_amount,
                'transaction_id' => $transaction_id,
                'gateway'        => 'authorizenet'
            );

            $subscription->add_payment( $args );
            $subscription->renew();

            do_action( 'wpinv_recurring_authorizenet_silent_post_payment', $subscription );
            do_action( 'wpinv_authorizenet_renewal_payment', $subscription );
        } else if ( 2 == $response_code ) {
            // Declined
            $subscription->failing();
            do_action( 'wpinv_authorizenet_renewal_payment_failed', $subscription );
            do_action( 'wpinv_authorizenet_renewal_error', $subscription );
        } else if ( 3 == $response_code || 8 == $reason_code ) {
            // An expired card
            $subscription->failing();
            do_action( 'wpinv_authorizenet_renewal_payment_failed', $subscription );
            do_action( 'wpinv_authorizenet_renewal_error', $subscription );
        } else {
            // Other Error
            do_action( 'wpinv_authorizenet_renewal_payment_error', $subscription );
        }

        exit;
    }
}
add_action( 'wpinv_verify_authorizenet_ipn', 'wpinv_authorizenet_process_ipn' );

/**
 * Retrieve the subscription
 */
function wpinv_get_authorizenet_subscription( $subscription_data = array(), $invoice_id ) {
    $parent_invoice_id = absint( $invoice_id );

    if ( empty( $subscription_data ) ) {
        return false;
    }

    if ( empty( $parent_invoice_id ) ) {
        return false;
    }

    $invoice = wpinv_get_invoice( $parent_invoice_id );
    if ( empty( $invoice ) ) {
        return false;
    }

    $subscriptionId     = (array)$subscription_data->subscriptionId;
    $subscription_id    = !empty( $subscriptionId[0] ) ? $subscriptionId[0] : $parent_invoice_id;

    $subscription = new WPInv_Subscription( $subscription_id, true );

    if ( ! $subscription || $subscription->id < 1 ) {
        $subs_db      = new WPInv_Subscriptions_DB;
        $subs         = $subs_db->get_subscriptions( array( 'parent_payment_id' => $parent_invoice_id, 'number' => 1 ) );
        $subscription = reset( $subs );

        if ( $subscription && $subscription->id > 0 ) {
            // Update the profile ID so it is set for future renewals
            $subscription->update( array( 'profile_id' => sanitize_text_field( $subscription_id ) ) );
        } else {
            // No subscription found with a matching payment ID, bail
            return false;
        }
    }

    return $subscription;
}

function wpinv_is_authorizenet_valid_for_use() {
    return in_array( wpinv_get_currency(), apply_filters( 'wpinv_authorizenet_supported_currencies', array( 'AUD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'JPY', 'NOK', 'NZD', 'PLN', 'SEK', 'USD', 'ZAR' ) ) );
}
function wpinv_check_authorizenet_currency_support( $gateway_list ) {
    if ( isset( $gateway_list['authorizenet'] ) && ! wpinv_is_authorizenet_valid_for_use() ) {
        unset( $gateway_list['authorizenet'] );
    }
    return $gateway_list;
}
add_filter( 'wpinv_enabled_payment_gateways', 'wpinv_check_authorizenet_currency_support', 10, 1 );

function wpinv_authorizenet_link_transaction_id( $transaction_id, $invoice_id, $invoice ) {
    if ( $transaction_id == $invoice_id ) {
        $link = $transaction_id;
    } else {
        if ( ! empty( $invoice ) && ! empty( $invoice->mode ) ) {
            $mode = $invoice->mode;
        } else {
            $mode = wpinv_is_test_mode( 'authorizenet' ) ? 'test' : 'live';
        }

        $url = $mode == 'test' ? 'https://sandbox.authorize.net/' : 'https://authorize.net/';
        $url .= 'ui/themes/sandbox/Transaction/TransactionReceipt.aspx?transid=' . $transaction_id;

        $link = '<a href="' . esc_url( $url ) . '" target="_blank">' . $transaction_id . '</a>';
    }

    return apply_filters( 'wpinv_authorizenet_link_payment_details_transaction_id', $link, $transaction_id, $invoice );
}
add_filter( 'wpinv_payment_details_transaction_id-authorizenet', 'wpinv_authorizenet_link_transaction_id', 10, 3 );

function wpinv_authorizenet_transaction_id_link( $transaction_id, $subscription ) {
    if ( ! empty( $transaction_id ) && ! empty( $subscription ) && ( $invoice_id = $subscription->get_original_payment_id() ) ) {
        $invoice = wpinv_get_invoice( $invoice_id );

        if ( ! empty( $invoice ) ) {
            return wpinv_authorizenet_link_transaction_id( $transaction_id, $invoice_id, $invoice );
        }        
    }
    
    return $transaction_id;
}
add_filter( 'wpinv_subscription_transaction_link_authorizenet', 'wpinv_authorizenet_transaction_id_link', 10, 2 );

function wpinv_authorizenet_profile_id_link( $profile_id, $subscription ) {
    $link = $profile_id;

    if ( ! empty( $profile_id ) && ! empty( $subscription ) && ( $invoice_id = $subscription->get_original_payment_id() ) ) {
        $invoice = wpinv_get_invoice( $invoice_id );

        if ( ! empty( $invoice ) && ! empty( $invoice->mode ) ) {
            $mode = $invoice->mode;
        } else {
            $mode = wpinv_is_test_mode( 'authorizenet' ) ? 'test' : 'live';
        }

        $url = $mode == 'test' ? 'https://sandbox.authorize.net/' : 'https://authorize.net/';
        $url .= 'ui/themes/sandbox/ARB/SubscriptionDetail.aspx?SubscrID=' . $profile_id;

        $link = '<a href="' . esc_url( $url ) . '" target="_blank">' . $profile_id . '</a>';
    }
    
    return apply_filters( 'wpinv_authorizenet_profile_id_link', $link, $profile_id, $subscription );
}
add_filter( 'wpinv_subscription_profile_link_authorizenet', 'wpinv_authorizenet_profile_id_link', 10, 2 );