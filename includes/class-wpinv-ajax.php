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

class WPInv_Ajax {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
        add_action( 'template_redirect', array( __CLASS__, 'do_wpinv_ajax' ), 0 );
        self::add_ajax_events();
    }

    public static function define_ajax() {
        if ( !empty( $_GET['wpinv-ajax'] ) ) {
            if ( ! defined( 'DOING_AJAX' ) ) {
                define( 'DOING_AJAX', true );
            }
            if ( ! defined( 'WC_DOING_AJAX' ) ) {
                define( 'WC_DOING_AJAX', true );
            }
            // Turn off display_errors during AJAX events to prevent malformed JSON
            if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
                /** @scrutinizer ignore-unhandled */ @ini_set( 'display_errors', 0 );
            }
            $GLOBALS['wpdb']->hide_errors();
        }
    }
    
    public static function do_wpinv_ajax() {
        global $wp_query;

        if ( !empty( $_GET['wpinv-ajax'] ) ) {
            $wp_query->set( 'wpinv-ajax', sanitize_text_field( $_GET['wpinv-ajax'] ) );
        }

        if ( $action = $wp_query->get( 'wpinv-ajax' ) ) {
            self::wpinv_ajax_headers();
            do_action( 'wpinv_ajax_' . sanitize_text_field( $action ) );
            die();
        }
    }
    
    private static function wpinv_ajax_headers() {
        send_origin_headers();
        /** @scrutinizer ignore-unhandled */ @header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
        /** @scrutinizer ignore-unhandled */ @header( 'X-Robots-Tag: noindex' );
        send_nosniff_header();
        nocache_headers();
        status_header( 200 );
    }
    
    public static function add_ajax_events() {
        $ajax_events = array(
            'add_note' => false,
            'delete_note' => false,
            'get_states_field' => true,
            'checkout' => false,
            'payment_form_get_taxes' => true,
            'payment_form'     => true,
            'payment_form_discount' => true,
            'get_payment_form' => true,
            'get_payment_form_states_field' => true,
            'add_invoice_item' => false,
            'remove_invoice_item' => false,
            'create_invoice_item' => false,
            'get_billing_details' => false,
            'admin_recalculate_totals' => false,
            'admin_apply_discount' => false,
            'admin_remove_discount' => false,
            'check_email' => false,
            'run_tool' => false,
            'apply_discount' => true,
            'remove_discount' => true,
            'buy_items' => true,
        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_wpinv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            add_action( 'wp_ajax_getpaid_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            
            if ( !defined( 'WPI_AJAX_' . strtoupper( $nopriv ) ) ) {
                define( 'WPI_AJAX_' . strtoupper( $nopriv ), 1 );
            }

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_wpinv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
                add_action( 'wp_ajax_nopriv_getpaid_' . $ajax_event, array( __CLASS__, $ajax_event ) );

                add_action( 'wpinv_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }
    
    public static function add_note() {
        check_ajax_referer( 'add-invoice-note', '_nonce' );

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }

        $post_id   = absint( $_POST['post_id'] );
        $note      = wp_kses_post( trim( stripslashes( $_POST['note'] ) ) );
        $note_type = sanitize_text_field( $_POST['note_type'] );

        $is_customer_note = $note_type == 'customer' ? 1 : 0;

        if ( $post_id > 0 ) {
            $note_id = wpinv_insert_payment_note( $post_id, $note, $is_customer_note );

            if ( $note_id > 0 && !is_wp_error( $note_id ) ) {
                wpinv_get_invoice_note_line_item( $note_id );
            }
        }

        die();
    }

    public static function delete_note() {
        check_ajax_referer( 'delete-invoice-note', '_nonce' );

        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }

        $note_id = (int)$_POST['note_id'];

        if ( $note_id > 0 ) {
            wp_delete_comment( $note_id, true );
        }

        die();
    }
    
    public static function get_states_field() {
        echo wpinv_get_states_field();
        
        die();
    }
    
    public static function checkout() {
        if ( ! defined( 'WPINV_CHECKOUT' ) ) {
            define( 'WPINV_CHECKOUT', true );
        }

        wpinv_process_checkout();
        die(0);
    }
    
    public static function add_invoice_item() {
        global $wpi_userID, $wpinv_ip_address_country;
        check_ajax_referer( 'invoice-item', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $item_id    = sanitize_text_field( $_POST['item_id'] );
        $invoice_id = absint( $_POST['invoice_id'] );
        
        if ( !is_numeric( $invoice_id ) || !is_numeric( $item_id ) ) {
            die();
        }
        
        $invoice    = wpinv_get_invoice( $invoice_id );
        if ( empty( $invoice ) ) {
            die();
        }
        
        if ( $invoice->is_paid() || $invoice->is_refunded() ) {
            die(); // Don't allow modify items for paid invoice.
        }
        
        if ( !empty( $_POST['user_id'] ) ) {
            $wpi_userID = absint( $_POST['user_id'] ); 
        }

        $item = new WPInv_Item( $item_id );
        if ( !( !empty( $item ) && $item->post_type == 'wpi_item' ) ) {
            die();
        }
        
        // Validate item before adding to invoice because recurring item must be paid individually.
        if ( !empty( $invoice->cart_details ) ) {
            $valid = true;
            
            if ( $recurring_item = $invoice->get_recurring() ) {
                if ( $recurring_item != $item_id ) {
                    $valid = false;
                }
            } else if ( wpinv_is_recurring_item( $item_id ) ) {
                $valid = false;
            }
            
            if ( !$valid ) {
                $response               = array();
                $response['success']    = false;
                $response['msg']        = __( 'You can not add item because recurring item must be paid individually!', 'invoicing' );
                wp_send_json( $response );
            }
        }
        
        $checkout_session = wpinv_get_checkout_session();
        
        $data                   = array();
        $data['invoice_id']     = $invoice_id;
        $data['cart_discounts'] = $invoice->get_discounts( true );
        
        wpinv_set_checkout_session( $data );
        
        $quantity = wpinv_item_quantities_enabled() && !empty($_POST['qty']) && (int)$_POST['qty'] > 0 ? (int)$_POST['qty'] : 1;

        $args = array(
            'id'            => $item_id,
            'quantity'      => $quantity,
            'item_price'    => $item->get_price(),
            'custom_price'  => '',
            'tax'           => 0.00,
            'discount'      => 0,
            'meta'          => array(),
            'fees'          => array()
        );

        $invoice->add_item( $item_id, $args );
        $invoice->save();
        
        if ( empty( $_POST['country'] ) ) {
            $_POST['country'] = !empty($invoice->country) ? $invoice->country : wpinv_get_default_country();
        }
        if ( empty( $_POST['state'] ) ) {
            $_POST['state'] = $invoice->state;
        }
         
        $invoice->country   = sanitize_text_field( $_POST['country'] );
        $invoice->state     = sanitize_text_field( $_POST['state'] );
        
        $invoice->set( 'country', sanitize_text_field( $_POST['country'] ) );
        $invoice->set( 'state', sanitize_text_field( $_POST['state'] ) );
        
        $wpinv_ip_address_country = $invoice->country;

        $invoice->recalculate_totals(true);
        
        $response                       = array();
        $response['success']            = true;
        $response['data']['items']      = wpinv_admin_get_line_items( $invoice );
        $response['data']['subtotal']   = $invoice->get_subtotal();
        $response['data']['subtotalf']  = $invoice->get_subtotal(true);
        $response['data']['tax']        = $invoice->get_tax();
        $response['data']['taxf']       = $invoice->get_tax(true);
        $response['data']['discount']   = $invoice->get_discount();
        $response['data']['discountf']  = $invoice->get_discount(true);
        $response['data']['total']      = $invoice->get_total();
        $response['data']['totalf']     = $invoice->get_total(true);
        
        wpinv_set_checkout_session($checkout_session);
        
        wp_send_json( $response );
    }


    public static function remove_invoice_item() {
        global $wpi_userID, $wpinv_ip_address_country;
        
        check_ajax_referer( 'invoice-item', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $item_id    = sanitize_text_field( $_POST['item_id'] );
        $invoice_id = absint( $_POST['invoice_id'] );
        $cart_index = isset( $_POST['index'] ) && $_POST['index'] >= 0 ? $_POST['index'] : false;
        
        if ( !is_numeric( $invoice_id ) || !is_numeric( $item_id ) ) {
            die();
        }

        $invoice    = wpinv_get_invoice( $invoice_id );
        if ( empty( $invoice ) ) {
            die();
        }
        
        if ( $invoice->is_paid() || $invoice->is_refunded() ) {
            die(); // Don't allow modify items for paid invoice.
        }
        
        if ( !empty( $_POST['user_id'] ) ) {
            $wpi_userID = absint( $_POST['user_id'] ); 
        }

        $item       = new WPInv_Item( $item_id );
        if ( !( !empty( $item ) && $item->post_type == 'wpi_item' ) ) {
            die();
        }
        
        $checkout_session = wpinv_get_checkout_session();
        
        $data                   = array();
        $data['invoice_id']     = $invoice_id;
        $data['cart_discounts'] = $invoice->get_discounts( true );
        
        wpinv_set_checkout_session( $data );

        $args = array(
            'id'         => $item_id,
            'quantity'   => 1,
            'cart_index' => $cart_index
        );

        $invoice->remove_item( $item_id, $args );
        $invoice->save();
        
        if ( empty( $_POST['country'] ) ) {
            $_POST['country'] = !empty($invoice->country) ? $invoice->country : wpinv_get_default_country();
        }
        if ( empty( $_POST['state'] ) ) {
            $_POST['state'] = $invoice->state;
        }
         
        $invoice->country   = sanitize_text_field( $_POST['country'] );
        $invoice->state     = sanitize_text_field( $_POST['state'] );
        
        $invoice->set( 'country', sanitize_text_field( $_POST['country'] ) );
        $invoice->set( 'state', sanitize_text_field( $_POST['state'] ) );
        
        $wpinv_ip_address_country = $invoice->country;
        
        $invoice->recalculate_totals(true);
        
        $response                       = array();
        $response['success']            = true;
        $response['data']['items']      = wpinv_admin_get_line_items( $invoice );
        $response['data']['subtotal']   = $invoice->get_subtotal();
        $response['data']['subtotalf']  = $invoice->get_subtotal(true);
        $response['data']['tax']        = $invoice->get_tax();
        $response['data']['taxf']       = $invoice->get_tax(true);
        $response['data']['discount']   = $invoice->get_discount();
        $response['data']['discountf']  = $invoice->get_discount(true);
        $response['data']['total']      = $invoice->get_total();
        $response['data']['totalf']     = $invoice->get_total(true);
        
        wpinv_set_checkout_session($checkout_session);
        
        wp_send_json( $response );
    }
    
    public static function create_invoice_item() {
        check_ajax_referer( 'invoice-item', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $invoice_id = absint( $_POST['invoice_id'] );

        // Find the item
        if ( !is_numeric( $invoice_id ) ) {
            die();
        }        
        
        $invoice     = wpinv_get_invoice( $invoice_id );
        if ( empty( $invoice ) ) {
            die();
        }
        
        // Validate item before adding to invoice because recurring item must be paid individually.
        if ( !empty( $invoice->cart_details ) && $invoice->get_recurring() ) {
            $response               = array();
            $response['success']    = false;
            $response['msg']        = __( 'You can not add item because recurring item must be paid individually!', 'invoicing' );
            wp_send_json( $response );
        }        
        
        $save_item = wp_unslash( $_POST['_wpinv_quick'] );
        
        $meta               = array();
        $meta['type']       = !empty($save_item['type']) ? sanitize_text_field($save_item['type']) : 'custom';
        $meta['price']      = !empty($save_item['price']) ? wpinv_sanitize_amount( $save_item['price'] ) : 0;
        $meta['vat_rule']   = !empty($save_item['vat_rule']) ? sanitize_text_field($save_item['vat_rule']) : 'digital';
        $meta['vat_class']  = !empty($save_item['vat_class']) ? sanitize_text_field($save_item['vat_class']) : '_standard';
        
        $data                   = array();
        $data['post_title']     = sanitize_text_field($save_item['name']);
        $data['post_status']    = 'publish';
        $data['post_excerpt']   = ! empty( $save_item['excerpt'] ) ? wp_kses_post( $save_item['excerpt'] ) : '';
        $data['meta']           = $meta;
        
        $item = new WPInv_Item();
        $item->create( $data );
        
        if ( !empty( $item ) ) {
            $_POST['item_id']   = $item->ID;
            $_POST['qty']       = !empty($save_item['qty']) && $save_item['qty'] > 0 ? (int)$save_item['qty'] : 1;
            
            self::add_invoice_item();
        }
        die();
    }
    
    public static function get_billing_details() {
        check_ajax_referer( 'get-billing-details', '_nonce' );
        
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }

        $user_id            = (int)$_POST['user_id'];
        $billing_details    = wpinv_get_user_address($user_id);
        $billing_details    = apply_filters( 'wpinv_fill_billing_details', $billing_details, $user_id );
        
        if (isset($billing_details['user_id'])) {
            unset($billing_details['user_id']);
        }
        
        if (isset($billing_details['email'])) {
            unset($billing_details['email']);
        }

        $response                               = array();
        $response['success']                    = true;
        $response['data']['billing_details']    = $billing_details;
        
        wp_send_json( $response );
    }
    
    public static function admin_recalculate_totals() {
        global $wpi_userID, $wpinv_ip_address_country;
        
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $invoice_id = absint( $_POST['invoice_id'] );        
        $invoice    = wpinv_get_invoice( $invoice_id );
        if ( empty( $invoice ) ) {
            die();
        }

        $checkout_session = wpinv_get_checkout_session();

        $data                   = array();
        $data['invoice_id']     = $invoice_id;
        $data['cart_discounts'] = $invoice->get_discounts( true );

        wpinv_set_checkout_session( $data );
        
        if ( !empty( $_POST['user_id'] ) ) {
            $wpi_userID = absint( $_POST['user_id'] ); 
        }
        
        if ( empty( $_POST['country'] ) ) {
            $_POST['country'] = !empty($invoice->country) ? $invoice->country : wpinv_get_default_country();
        }

        $disable_taxes = 0;
        if ( ! empty( $_POST['disable_taxes'] ) ) {
            $disable_taxes = 1;
        }
        $invoice->set( 'disable_taxes', $disable_taxes );

        $invoice->country = sanitize_text_field( $_POST['country'] );
        $invoice->set( 'country', sanitize_text_field( $_POST['country'] ) );
        if ( isset( $_POST['state'] ) ) {
            $invoice->state = sanitize_text_field( $_POST['state'] );
            $invoice->set( 'state', sanitize_text_field( $_POST['state'] ) );
        }
        
        $wpinv_ip_address_country = $invoice->country;
        
        $invoice = $invoice->recalculate_totals(true);
        
        $response                       = array();
        $response['success']            = true;
        $response['data']['items']      = wpinv_admin_get_line_items( $invoice );
        $response['data']['subtotal']   = $invoice->get_subtotal();
        $response['data']['subtotalf']  = $invoice->get_subtotal(true);
        $response['data']['tax']        = $invoice->get_tax();
        $response['data']['taxf']       = $invoice->get_tax(true);
        $response['data']['discount']   = $invoice->get_discount();
        $response['data']['discountf']  = $invoice->get_discount(true);
        $response['data']['total']      = $invoice->get_total();
        $response['data']['totalf']     = $invoice->get_total(true);
        
        wpinv_set_checkout_session($checkout_session);

        wp_send_json( $response );
    }
    
    public static function admin_apply_discount() {
        global $wpi_userID;
        
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $invoice_id = absint( $_POST['invoice_id'] );
        $discount_code = sanitize_text_field( $_POST['code'] );
        if ( empty( $invoice_id ) || empty( $discount_code ) ) {
            die();
        }
        
        $invoice = wpinv_get_invoice( $invoice_id );
        if ( empty( $invoice ) || ( !empty( $invoice ) && ( $invoice->is_paid() || $invoice->is_refunded() ) ) ) {
            die();
        }
        
        $checkout_session = wpinv_get_checkout_session();
        
        $data                   = array();
        $data['invoice_id']     = $invoice_id;
        $data['cart_discounts'] = $invoice->get_discounts( true );
        
        wpinv_set_checkout_session( $data );
        
        $response               = array();
        $response['success']    = false;
        $response['msg']        = __( 'This discount is invalid.', 'invoicing' );
        $response['data']['code'] = $discount_code;
        
        if ( wpinv_is_discount_valid( $discount_code, $invoice->get_user_id() ) ) {
            $discounts = wpinv_set_cart_discount( $discount_code );
            
            $response['success'] = true;
            $response['msg'] = __( 'Discount has been applied successfully.', 'invoicing' );
        }  else {
            $errors = wpinv_get_errors();
            if ( !empty( $errors['wpinv-discount-error'] ) ) {
                $response['msg'] = $errors['wpinv-discount-error'];
            }
            wpinv_unset_error( 'wpinv-discount-error' );
        }
        
        wpinv_set_checkout_session($checkout_session);
        
        wp_send_json( $response );
    }
    
    public static function admin_remove_discount() {
        global $wpi_userID;
        
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $invoice_id = absint( $_POST['invoice_id'] );
        $discount_code = sanitize_text_field( $_POST['code'] );
        if ( empty( $invoice_id ) || empty( $discount_code ) ) {
            die();
        }
        
        $invoice = wpinv_get_invoice( $invoice_id );
        if ( empty( $invoice ) || ( !empty( $invoice ) && ( $invoice->is_paid() || $invoice->is_refunded() ) ) ) {
            die();
        }
        
        $checkout_session = wpinv_get_checkout_session();
        
        $data                   = array();
        $data['invoice_id']     = $invoice_id;
        $data['cart_discounts'] = $invoice->get_discounts( true );
        
        wpinv_set_checkout_session( $data );
        
        $response               = array();
        $response['success']    = false;
        $response['msg']        = NULL;
        
        $discounts  = wpinv_unset_cart_discount( $discount_code );
        $response['success'] = true;
        $response['msg'] = __( 'Discount has been removed successfully.', 'invoicing' );
        
        wpinv_set_checkout_session($checkout_session);
        
        wp_send_json( $response );
    }
    
    public static function check_email() {
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $email = sanitize_text_field( $_POST['email'] );
        
        $response = array();
        if ( is_email( $email ) && email_exists( $email ) && $user_data = get_user_by( 'email', $email ) ) {
            $user_id            = $user_data->ID;
            $user_login         = $user_data->user_login;
            $display_name       = $user_data->display_name ? $user_data->display_name : $user_login;
            $billing_details    = wpinv_get_user_address($user_id);
            $billing_details    = apply_filters( 'wpinv_fill_billing_details', $billing_details, $user_id );
            
            if (isset($billing_details['user_id'])) {
                unset($billing_details['user_id']);
            }
            
            if (isset($billing_details['email'])) {
                unset($billing_details['email']);
            }
            
            $response['success']                    = true;
            $response['data']['id']                 = $user_data->ID;
            $response['data']['name']               = $user_data->user_email;
            $response['data']['billing_details']    = $billing_details;
        }
        
        wp_send_json( $response );
    }
    
    public static function run_tool() {
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        if ( !wpinv_current_user_can_manage_invoicing() ) {
            die(-1);
        }
        
        $tool = sanitize_text_field( $_POST['tool'] );
        
        do_action( 'wpinv_run_tool' );
        
        if ( !empty( $tool ) ) {
            do_action( 'wpinv_tool_' . $tool );
        }
    }
    
    public static function apply_discount() {
        global $wpi_userID;
        
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        
        $response = array();
        
        if ( isset( $_POST['code'] ) ) {
            $discount_code = sanitize_text_field( $_POST['code'] );

            $response['success']        = false;
            $response['msg']            = '';
            $response['data']['code']   = $discount_code;
            
            $invoice = wpinv_get_invoice_cart();
            if ( empty( $invoice->ID ) ) {
                $response['msg'] = __( 'Invalid checkout request.', 'invoicing' );
                wp_send_json( $response );
            }

            $wpi_userID = $invoice->get_user_id();

            if ( wpinv_is_discount_valid( $discount_code, $wpi_userID ) ) {
                $discount       = wpinv_get_discount_by_code( $discount_code );
                $discounts      = wpinv_set_cart_discount( $discount_code );
                $amount         = wpinv_format_discount_rate( wpinv_get_discount_type( $discount->ID ), wpinv_get_discount_amount( $discount->ID ) );
                $total          = wpinv_get_cart_total( null, $discounts );
                $cart_totals    = wpinv_recalculate_tax( true );
            
                if ( !empty( $cart_totals ) ) {
                    $response['success']        = true;
                    $response['data']           = $cart_totals;
                    $response['data']['code']   = $discount_code;
                } else {
                    $response['success']        = false;
                }
            } else {
                $errors = wpinv_get_errors();
                $response['msg']  = $errors['wpinv-discount-error'];
                wpinv_unset_error( 'wpinv-discount-error' );
            }

            // Allow for custom discount code handling
            $response = apply_filters( 'wpinv_ajax_discount_response', $response );
        }
        
        wp_send_json( $response );
    }
    
    public static function remove_discount() {
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        
        $response = array();
        
        if ( isset( $_POST['code'] ) ) {
            $discount_code  = sanitize_text_field( $_POST['code'] );
            $discounts      = wpinv_unset_cart_discount( $discount_code );
            $total          = wpinv_get_cart_total( null, $discounts );
            $cart_totals    = wpinv_recalculate_tax( true );
            
            if ( !empty( $cart_totals ) ) {
                $response['success']        = true;
                $response['data']           = $cart_totals;
                $response['data']['code']   = $discount_code;
            } else {
                $response['success']        = false;
            }
            
            // Allow for custom discount code handling
            $response = apply_filters( 'wpinv_ajax_discount_response', $response );
        }
        
        wp_send_json( $response );
    }

    /**
     * Retrieves the markup for a payment form.
     */
    public static function get_payment_form() {

        // Check nonce.
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'getpaid_ajax_form' ) ) {
            _e( 'Error: Reload the page and try again.', 'invoicing' );
            exit;
        }

        // Is the request set up correctly?
		if ( empty( $_GET['form'] ) && empty( $_GET['item'] ) ) {
			echo aui()->alert(
				array(
					'type'    => 'warning',
					'content' => __( 'No payment form or item provided', 'invoicing' ),
				)
            );
            exit;
        }

        // Payment form or button?
		if ( ! empty( $_GET['form'] ) ) {
            echo getpaid_display_payment_form( $_GET['form'] );
		} else if( $_GET['invoice'] ) {
		    echo getpaid_display_invoice_payment_form( $_GET['invoice'] );
        } else {
			$items = getpaid_convert_items_to_array( $_GET['item'] );
		    echo getpaid_display_item_payment_form( $items );
        }
        
        exit;

    }

    /**
     * Payment forms.
     *
     * @since 1.0.18
     */
    public static function payment_form() {
        global $invoicing, $wpi_checkout_id, $cart_total;

        // Check nonce.
        if ( ! isset( $_POST['wpinv_payment_form'] ) || ! wp_verify_nonce( $_POST['wpinv_payment_form'], 'wpinv_payment_form' ) ) {
            wp_send_json_error( __( 'Security checks failed.', 'invoicing' ) );
        }

        // Prepare submitted data...
        $data = wp_unslash( $_POST );

        // ... form fields...
        if ( empty( $data['form_id'] ) || 'publish' != get_post_status( $data['form_id'] ) ) {
            wp_send_json_error( __( 'This payment form is no longer active.', 'invoicing' ) );
        }

        if ( empty( $data['billing_email'] ) || ! is_email( $data['billing_email'] ) ) {
            wp_send_json_error( __( 'Provide a valid billing email.', 'invoicing' ) );
        }

        $prepared = array(
            'billing_email'                    => sanitize_email( $data['billing_email'] ),
            __( 'Billing Email', 'invoicing' ) => sanitize_email( $data['billing_email'] ),
            __( 'Form Id', 'invoicing' )       => absint( $data['form_id'] ),
        );

        // Do we have a discount?
        $discount = 0;
        if ( ! empty( $data['discount'] ) ) {

            // Validate discount.
            $discount = self::payment_form_validate_discount( $data );

            if ( is_string( $discount ) ){
                wp_send_json_error( $discount );
            }

            if ( is_array( $discount ) ){
                $discount = $discount[ 'discount' ];
            }

            if ( ! $discount ) {
                $discount = 0;
            }

        }

        $fields = $invoicing->form_elements->get_form_elements( $data['form_id'] );

        // ... and form items.
        if ( ! empty( $data['invoice_id'] ) ) {
            $invoice = wpinv_get_invoice( $data['invoice_id'] );

            if ( empty( $invoice ) ) {
                wp_send_json_error( __( 'Invalid invoice.', 'invoicing' ) );
            }

            if ( $invoice->is_paid() ) {
                wp_send_json_error( __( 'This invoice has already been paid.', 'invoicing' ) );
            }

            $items   = $invoicing->form_elements->convert_checkout_items( $invoice->cart_details, $invoice );

        } else {

            if ( isset( $data['form_items'] ) ) {
                $items = getpaid_convert_items_to_array( $data['form_items'] );
                $items = $invoicing->form_elements->convert_normal_items( $items );
            } else {
                $items = $invoicing->form_elements->get_form_items( $data['form_id'] );
            }

            $invoice = 0;
        }

        $prepared_items = array();
        $address_fields = array();

        if ( ! empty( $data['wpinv-items'] ) ) {

            $selected_items = wpinv_clean( $data['wpinv-items'] );

            foreach ( $items as $item ) {

                if ( ! empty( $item['required'] ) && ! isset( $selected_items[ $item['id'] ] ) ) {
                    wp_send_json_error( __( 'A required item is missing.', 'invoicing' ) );
                }

                if ( ! isset( $selected_items[ $item['id'] ] ) ) {
                    continue;
                }

                $quantity = empty( $item['quantity'] ) ? 1 : absint( $item['quantity'] );

                if ( ! empty( $item['allow_quantities'] ) && ! empty( $data["wpinv-item-{$item['id']}-quantity"] ) ) {

                    $_quantity = intval( $data["wpinv-item-{$item['id']}-quantity"] );

                    if ( ! empty( $_quantity ) ) {
                        $quantity = $_quantity;
                    }
                }

                // Custom pricing.
                if ( ! empty( $item['custom_price'] ) ) {

                    $minimum_price = wpinv_sanitize_amount( $item['minimum_price'] );
                    $set_price     = wpinv_sanitize_amount( $selected_items[ $item['id'] ] );

                    if ( $set_price < $minimum_price ) {
                        wp_send_json_error( __( 'The provided amount is less than the minimum allowed value.', 'invoicing' ) );
                    }

                    $prepared_items[] = array(
                        'id'           =>$item['id'],
                        'item_price'   => wpinv_sanitize_amount( $item['price'] ),
                        'custom_price' => $set_price,
                        'name'         => $item['title'],
                        'quantity'     => $quantity,
                    );

                } else {

                    $prepared_items[] = array(
                        'id'           => $item['id'],
                        'item_price'   => wpinv_sanitize_amount( $item['price'] ),
                        'custom_price' => wpinv_sanitize_amount( $item['price'] ),
                        'name'         => $item['title'],
                        'quantity'     => $quantity,
                    );

                }

            }

        } else {

            wp_send_json_error( __( 'You have not selected any items.', 'invoicing' ) );

        }

        // Are all required fields provided?
        foreach ( $fields as $field ) {

            if ( ! empty( $field['premade'] ) ) {
                continue;
            }

            if ( ! empty( $field['required'] ) && empty( $data[ $field['id'] ] ) ) {
                wp_send_json_error( __( 'Some required fields have not been filled.', 'invoicing' ) );
            }

            if ( $field['type'] == 'address' ) {

                foreach ( $field['fields'] as $address_field ) {

                    if ( empty( $address_field['visible'] ) ) {
                        continue;
                    }

                    if ( ! empty( $address_field['required'] ) && empty( $data[ $address_field['name'] ] ) ) {
                        wp_send_json_error( __( 'Some required fields have not been filled.', 'invoicing' ) );
                    }

                    if ( isset( $data[ $address_field['name'] ] ) ) {
                        $label = str_replace( 'wpinv_', '', $address_field['name'] );
                        $address_fields[ $label ] = wpinv_clean( $data[ $address_field['name'] ] );
                    }

                }

            } else if ( isset( $data[ $field['id'] ] ) ) {
                $label = $field['id'];

                if ( isset( $field['label'] ) ) {
                    $label = $field['label'];
                }

                $prepared[ wpinv_clean( $label ) ] = wpinv_clean( $data[ $field['id'] ] );
            }

        }

        $user = get_user_by( 'email', $prepared['billing_email'] );

        if ( empty( $user ) ) {
            $user = wpinv_create_user( $prepared['billing_email'] );
        }

        if ( is_wp_error( $user ) ) {
            wp_send_json_error( $user->get_error_message() );
        }

        if ( is_numeric( $user ) ) {
            $user = get_user_by( 'id', $user );
        }

        if ( $discount ) {
            $address_fields['discount'] = array( $data['discount'] );
        }

        // Create the invoice.
        if ( empty( $invoice ) ) {

            $invoice = wpinv_insert_invoice(
                array(
                    'status'        => 'wpi-pending',
                    'created_via'   => 'payment_form',
                    'user_id'       => $user->ID,
                    'cart_details'  => $prepared_items,
                    'user_info'     => $address_fields,
                ),
                true
            );

        } else {

            $invoice = wpinv_update_invoice(
                array(
                    'ID'            => $invoice->ID,
                    'status'        => 'wpi-pending',
                    'cart_details'  => $prepared_items,
                    'user_info'     => $address_fields,
                ),
                true
            );

        }
        

        if ( is_wp_error( $invoice ) ) {
            wp_send_json_error( $invoice->get_error_message() );
        }

        if ( empty( $invoice ) ) {
            wp_send_json_error( __( 'Could not create your invoice.', 'invoicing' ) );
        }

        unset( $prepared['billing_email'] );
        update_post_meta( $invoice->ID, 'payment_form_data', $prepared );

        $wpi_checkout_id = $invoice->ID;
        $cart_total = wpinv_price(
            wpinv_format_amount(
                wpinv_get_cart_total( $invoice->get_cart_details(), NULL, $invoice ) ),
                $invoice->get_currency()
        );

        $data                   = array();
        $data['invoice_id']     = $invoice->ID;
        $data['cart_discounts'] = $invoice->get_discounts( true );

        wpinv_set_checkout_session( $data );
        add_filter( 'wp_redirect', array( $invoicing->form_elements, 'send_redirect_response' ) );
        add_action( 'wpinv_pre_send_back_to_checkout', array( $invoicing->form_elements, 'checkout_error' ) );
        
        if ( ! defined( 'WPINV_CHECKOUT' ) ) {
            define( 'WPINV_CHECKOUT', true );
        }

        wpinv_process_checkout();

        $invoicing->form_elements->checkout_error();

        exit;
    }

    /**
     * Payment forms.
     *
     * @since 1.0.18
     */
    public static function get_payment_form_states_field() {
        global $invoicing;

        if ( empty( $_GET['country'] ) || empty( $_GET['form'] ) ) {
            exit;
        }

        $elements = $invoicing->form_elements->get_form_elements( $_GET['form'] );

        if ( empty( $elements ) ) {
            exit;
        }

        $address_fields = array();
        foreach ( $elements as $element ) {
            if ( 'address' === $element['type'] ) {
                $address_fields = $element;
                break;
            }
        }

        if ( empty( $address_fields ) ) {
            exit;
        }

        foreach( $address_fields['fields'] as $address_field ) {

            if ( 'wpinv_state' == $address_field['name'] ) {

                $label = $address_field['label'];

                if ( ! empty( $address_field['required'] ) ) {
                    $label .= "<span class='text-danger'> *</span>";
                }

                $states = wpinv_get_country_states( $_GET['country'] );

                if ( ! empty( $states ) ) {

                    $html = aui()->select(
                            array(
                                'options'          => $states,
                                'name'             => esc_attr( $address_field['name'] ),
                                'id'               => esc_attr( $address_field['name'] ),
                                'placeholder'      => esc_attr( $address_field['placeholder'] ),
                                'required'         => (bool) $address_field['required'],
                                'no_wrap'          => true,
                                'label'            => wp_kses_post( $label ),
                                'select2'          => false,
                            )
                        );

                } else {

                    $html = aui()->input(
                            array(
                                'name'       => esc_attr( $address_field['name'] ),
                                'id'         => esc_attr( $address_field['name'] ),
                                'required'   => (bool) $address_field['required'],
                                'label'      => wp_kses_post( $label ),
                                'no_wrap'    => true,
                                'type'       => 'text',
                            )
                        );

                }

                wp_send_json_success( str_replace( 'sr-only', '', $html ) );
                exit;

            }

        }
    
        exit;
    }

    /**
     * Apply taxes.
     *
     * @since 1.0.18
     */
    public static function payment_form_get_taxes() {
        global $invoicing;

        // Check nonce.
        check_ajax_referer( 'wpinv_payment_form', 'wpinv_payment_form' );

        // Prepare submitted data...
        $data = wp_unslash( $_POST );

        // ... form fields...
        if ( empty( $data['form_id'] ) || 'publish' != get_post_status( $data['form_id'] ) ) {
            exit;
        }

        // Do we have a discount?
        if ( ! empty( $data['discount'] ) ) {

            // Validate discount.
            $discount = self::payment_form_validate_discount( $data );

            if ( is_array( $discount ) ){
                $discount = array_map( 'wpinv_format_amount', $discount );
                $discount = array_map( 'wpinv_price', $discount );
                wp_send_json_success( $discount );
            }

        }        

        // For existing invoices.
        if ( ! empty( $data['invoice_id'] ) ) {
            $invoice = wpinv_get_invoice( $data['invoice_id'] );

            if ( empty( $invoice ) ) {
                exit;
            }

            $items   = $invoicing->form_elements->convert_checkout_items( $invoice->cart_details, $invoice );
            $country = $invoice->country;
            $state   = $invoice->state;

        } else {

            if ( isset( $data['form_items'] ) ) {
                $items = getpaid_convert_items_to_array( $data['form_items'] );
                $items = $invoicing->form_elements->convert_normal_items( $items );
            } else {
                $items = $invoicing->form_elements->get_form_items( $data['form_id'] );
            }

            $country   = wpinv_default_billing_country();
            $state     = false;
        }

        // What we will calculate.
        $total     = 0;
        $tax       = 0;
        $sub_total = 0;

        if ( ! empty( $data['wpinv_country'] ) ) {
            $country = $data['wpinv_country'];
        }

        if ( ! empty( $data['wpinv_state'] ) ) {
            $state = $data['wpinv_state'];
        }

        if ( ! empty( $data['wpinv-items'] ) ) {

            $selected_items = wpinv_clean( $data['wpinv-items'] );

            foreach ( $items as $item ) {

                if ( ! isset( $selected_items[ $item['id'] ] ) ) {
                    continue;
                }

                $quantity = empty( $item['quantity'] ) ? 1 : absint( $item['quantity'] );

                if ( ! empty( $item['allow_quantities'] ) && ! empty( $data["wpinv-item-{$item['id']}-quantity"] ) ) {

                    $quantity = intval( $data["wpinv-item-{$item['id']}-quantity"] );

                    if ( 1 > $quantity ) {
                        $quantity = 1;
                    }

                }

                // Custom pricing.
                $price = wpinv_sanitize_amount( $item['price'] );
                if ( ! empty( $item['custom_price'] ) ) {

                    $minimum_price = wpinv_sanitize_amount( $item['minimum_price'] );
                    $set_price     = wpinv_sanitize_amount( $selected_items[ $item['id'] ] );

                    if ( $set_price < $minimum_price ) {
                        $set_price = $minimum_price;
                    }

                    $price = wpinv_sanitize_amount( $set_price );

                }

                $price  = $quantity * floatval( $price );

                if ( wpinv_use_taxes() ) {

                    $rate = wpinv_get_tax_rate( $country, $state, (int) $item['id'] );

                    if ( wpinv_prices_include_tax() ) {
                        $pre_tax  = ( $price - $price * $rate * 0.01 );
                        $item_tax = $price - $pre_tax;
                    } else {
                        $pre_tax  = $price;
                        $item_tax = $price * $rate * 0.01;
                    }

                    $tax       = $tax + $item_tax;
                    $sub_total = $sub_total + $pre_tax;
                    $total     = $sub_total + $tax;

                } else {
                    $total  = $total + $price;
                }

            }

        }

        wp_send_json_success( array(
            'total'     => wpinv_price( wpinv_format_amount( $total ) ),
            'tax'       => wpinv_price( wpinv_format_amount( $tax ) ),
            'sub_total' => wpinv_price( wpinv_format_amount( $sub_total ) ),
            'discount'  => false,
        ));
        exit;
    }

    /**
     * Apply discounts.
     *
     * @since 1.0.19
     */
    public static function payment_form_discount() {

        // Check nonce.
        check_ajax_referer( 'wpinv_payment_form', 'wpinv_payment_form' );

        // Prepare submitted data...
        $data = wp_unslash( $_POST );

        // ... form fields...
        if ( empty( $data['form_id'] ) || 'publish' != get_post_status( $data['form_id'] ) ) {
            exit;
        }

        // Do we have a discount?
        if ( empty( $data['discount'] ) ) {
            _e( 'Please enter your discount code', 'invoicing' );
            exit;
        }

        // Validate discount.
        $data = self::payment_form_validate_discount( $data );

        if ( false === $data ) {
            _e( 'There was an error applying your discount code', 'invoicing' );
            exit;
        }

        if ( is_string( $data ) ) {
            echo $data;
            exit;
        }

        $data = array_map( 'wpinv_format_amount', $data );
        $data = array_map( 'wpinv_price', $data );
        wp_send_json_success( $data );
        exit;

    }

    /**
     * Validates discounts.
     *
     * @since 1.0.19
     */
    public static function payment_form_validate_discount( $data ) {
        global $invoicing;

        // Do we have a discount?
        if ( empty( $data['discount'] ) ) {
            return false;
        }

        // If yes, ensure that it exists.
        $discount = wpinv_get_discount_obj( $data['discount'] );

        // Ensure it is active.
        if ( ! $discount->exists() || ! $discount->is_active() || ! $discount->has_started() || $discount->is_expired() ) {
            return __( 'This discount code is not valid', 'invoicing' );
        }

        // If it can only be used once per user...
        if ( $discount->is_single_use ) {

            if ( empty( $data['billing_email'] ) ) {
                return __( 'Please enter your billing email before applying this discount', 'invoicing' );
            }

            if ( ! $discount->is_valid_for_user( $data['billing_email'] ) ) {
                return __( 'You have already used this discount', 'invoicing' );
            }

        }

        // Prepare items.
        if ( ! empty( $data['invoice_id'] ) ) {
            $invoice = wpinv_get_invoice( $data['invoice_id'] );

            if ( empty( $invoice ) ) {
                return false;
            }

            $country = $invoice->country;
            $state   = $invoice->state;
            $items   = $invoicing->form_elements->convert_checkout_items( $invoice->cart_details, $invoice );

        } else {

            if ( isset( $data['form_items'] ) ) {
                $items = getpaid_convert_items_to_array( $data['form_items'] );
                $items = $invoicing->form_elements->convert_normal_items( $items );
            } else {
                $items = $invoicing->form_elements->get_form_items( $data['form_id'] );
            }

            $country   = wpinv_default_billing_country();
            $state     = false;

        }

        // What we will calculate.
        $total     = 0;
        $tax       = 0;
        $sub_total = 0;

        if ( ! empty( $data['wpinv_country'] ) ) {
            $country = $data['wpinv_country'];
        }

        if ( ! empty( $data['wpinv_state'] ) ) {
            $state = $data['wpinv_state'];
        }

        if ( ! empty( $data['wpinv-items'] ) ) {

            $selected_items = wpinv_clean( $data['wpinv-items'] );

            // Check if it is valid for the selected items.
            if ( ! $discount->is_valid_for_items( array_keys( $selected_items ) ) ) {
                return __( 'This discount is not valid for the items in your cart', 'invoicing' );
            }

            foreach ( $items as $item ) {

                if ( ! isset( $selected_items[ $item['id'] ] ) ) {
                    continue;
                }

                $quantity = empty( $item['quantity'] ) ? 1 : absint( $item['quantity'] );

                if ( ! empty( $item['allow_quantities'] ) && ! empty( $data["wpinv-item-{$item['id']}-quantity"] ) ) {

                    $quantity = intval( $data["wpinv-item-{$item['id']}-quantity"] );

                    if ( 1 > $quantity ) {
                        $quantity = 1;
                    }

                }

                // Custom pricing.
                $price = wpinv_sanitize_amount( $item['price'] );
                if ( ! empty( $item['custom_price'] ) ) {

                    $minimum_price = wpinv_sanitize_amount( $item['minimum_price'] );
                    $set_price     = wpinv_sanitize_amount( $selected_items[ $item['id'] ] );

                    if ( $set_price < $minimum_price ) {
                        $set_price = $minimum_price;
                    }

                    $price = wpinv_sanitize_amount( $set_price );

                }

                $price  = $quantity * floatval( $price );

                if ( wpinv_use_taxes() ) {

                    $rate = wpinv_get_tax_rate( $country, $state, (int) $item['id'] );

                    if ( wpinv_prices_include_tax() ) {
                        $pre_tax  = ( $price - $price * $rate * 0.01 );
                        $item_tax = $price - $pre_tax;
                    } else {
                        $pre_tax  = $price;
                        $item_tax = $price * $rate * 0.01;
                    }

                    $tax       = $tax + $item_tax;
                    $sub_total = $sub_total + $pre_tax;
                    $total     = $sub_total + $tax;

                } else {
                    $total  = $total + $price;
                }

            }

        }

        if ( ! $discount->is_minimum_amount_met( $total ) ) {
            $min = wpinv_price( wpinv_format_amount( $discount->min_total ) );
            return sprintf( __( 'The minimum total for using this discount is %s', 'invoicing' ), $min );
        }

        if ( ! $discount->is_maximum_amount_met( $total ) ) {
            $max = wpinv_price( wpinv_format_amount( $discount->max_total ) );
            return sprintf( __( 'The maximum total for using this discount is %s', 'invoicing' ), $max );
        }

        $discount = $discount->get_discounted_amount( $total );
        $total    = $total - $discount;
        return compact( 'total', 'tax', 'sub_total', 'discount' );

    }

    /**
     * Lets users buy items via ajax.
     *
     * @since 1.0.0
     */
    public static function buy_items() {
        $user_id = get_current_user_id();

        if ( empty( $user_id ) ) { // If not logged in then lets redirect to the login page
            wp_send_json( array(
                'success' => wp_login_url( wp_get_referer() )
            ) );
        } else {
            // Only check nonce if logged in as it could be cached when logged out.
            if ( ! isset( $_POST['wpinv_buy_nonce'] ) || ! wp_verify_nonce( $_POST['wpinv_buy_nonce'], 'wpinv_buy_items' ) ) {
                wp_send_json( array(
                    'error' => __( 'Security checks failed.', 'invoicing' )
                ) );
                wp_die();
            }

            // allow to set a custom price through post_id
            $items = $_POST['items'];
            $related_post_id = isset( $_POST['post_id'] ) ? (int)$_POST['post_id'] : 0;
            $custom_item_price = $related_post_id ? abs( get_post_meta( $related_post_id, '_wpi_custom_price', true ) ) : 0;

            $cart_items = array();
            if ( $items ) {
                $items = explode( ',', $items );

                foreach( $items as $item ) {
                    $item_id = $item;
                    $quantity = 1;

                    if ( strpos( $item, '|' ) !== false ) {
                        $item_parts = explode( '|', $item );
                        $item_id = $item_parts[0];
                        $quantity = $item_parts[1];
                    }

                    if ( $item_id && $quantity ) {
                        $cart_items_arr = array(
                            'id'            => (int)$item_id,
                            'quantity'      => (int)$quantity
                        );

                        // If there is a related post id then add it to meta
                        if ( $related_post_id ) {
                            $cart_items_arr['meta'] = array(
                                'post_id'   => $related_post_id
                            );
                        }

                        // If there is a custom price then set it.
                        if ( $custom_item_price ) {
                            $cart_items_arr['custom_price'] = $custom_item_price;
                        }

                        $cart_items[] = $cart_items_arr;
                    }
                }
            }

            /**
             * Filter the wpinv_buy shortcode cart items on the fly.
             *
             * @param array $cart_items The cart items array.
             * @param int $related_post_id The related post id if any.
             * @since 1.0.0
             */
            $cart_items = apply_filters( 'wpinv_buy_cart_items', $cart_items, $related_post_id );

            // Make sure its not in the cart already, if it is then redirect to checkout.
            $cart_invoice = wpinv_get_invoice_cart();

            if ( isset( $cart_invoice->items ) && !empty( $cart_invoice->items ) && !empty( $cart_items ) && serialize( $cart_invoice->items ) == serialize( $cart_items ) ) {
                wp_send_json( array(
                    'success' =>  $cart_invoice->get_checkout_payment_url()
                ) );
                wp_die();
            }

            // Check if user has invoice with same items waiting to be paid.
            $user_invoices = wpinv_get_users_invoices( $user_id , 10 , false , 'wpi-pending' );
            if ( !empty( $user_invoices ) ) {
                foreach( $user_invoices as $user_invoice ) {
                    $user_cart_details = array();
                    $invoice  = wpinv_get_invoice( $user_invoice->ID );
                    $cart_details = $invoice->get_cart_details();

                    if ( !empty( $cart_details ) ) {
                        foreach ( $cart_details as $invoice_item ) {
                            $ii_arr = array();
                            $ii_arr['id'] = (int)$invoice_item['id'];
                            $ii_arr['quantity'] = (int)$invoice_item['quantity'];

                            if (isset( $invoice_item['meta'] ) && !empty( $invoice_item['meta'] ) ) {
                                $ii_arr['meta'] = $invoice_item['meta'];
                            }

                            if ( isset( $invoice_item['custom_price'] ) && !empty( $invoice_item['custom_price'] ) ) {
                                $ii_arr['custom_price'] = $invoice_item['custom_price'];
                            }

                            $user_cart_details[] = $ii_arr;
                        }
                    }

                    if ( !empty( $user_cart_details ) && serialize( $cart_items ) == serialize( $user_cart_details ) ) {
                        wp_send_json( array(
                            'success' =>  $invoice->get_checkout_payment_url()
                        ) );
                        wp_die();
                    }
                }
            }

            // Create invoice and send user to checkout
            if ( !empty( $cart_items ) ) {
                $invoice_data = array(
                    'status'        =>  'wpi-pending',
                    'created_via'   =>  'wpi',
                    'user_id'       =>  $user_id,
                    'cart_details'  =>  $cart_items,
                );

                $invoice = wpinv_insert_invoice( $invoice_data, true );

                if ( !empty( $invoice ) && isset( $invoice->ID ) ) {
                    wp_send_json( array(
                        'success' =>  $invoice->get_checkout_payment_url()
                    ) );
                } else {
                    wp_send_json( array(
                        'error' => __( 'Invoice failed to create', 'invoicing' )
                    ) );
                }
            } else {
                wp_send_json( array(
                    'error' => __( 'Items not valid.', 'invoicing' )
                ) );
            }
        }

        wp_die();
    }
}

WPInv_Ajax::init();