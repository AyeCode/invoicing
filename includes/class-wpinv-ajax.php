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
                @ini_set( 'display_errors', 0 );
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
        @header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
        @header( 'X-Robots-Tag: noindex' );
        send_nosniff_header();
        nocache_headers();
        status_header( 200 );
    }
    
    public static function add_ajax_events() {
        $ajax_events = array(
            'add_note' => false,
            'delete_note' => false,
            'get_states_field' => false,
            'checkout' => false,
            'add_invoice_item' => false,
            'remove_invoice_item' => false,
            'create_invoice_item' => false,
            'get_billing_details' => false,
            'admin_recalculate_totals' => false,
            'admin_apply_discount' => false,
            'admin_remove_discount' => false,
            'check_email' => false,
            'run_tool' => false,
            'apply_discount' => false,
            'remove_discount' => false,
        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_wpinv_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            
            if ( !defined( 'WPI_AJAX_' . strtoupper( $nopriv ) ) ) {
                define( 'WPI_AJAX_' . strtoupper( $nopriv ), 1 );
            }

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_wpinv_' . $ajax_event, array( __CLASS__, $ajax_event ) );

                add_action( 'wpinv_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }
    
    public static function add_note() {
        check_ajax_referer( 'add-invoice-note', '_nonce' );

        if ( !current_user_can( 'manage_options' ) ) {
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

        if ( !current_user_can( 'manage_options' ) ) {
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
        if ( !current_user_can( 'manage_options' ) ) {
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
        
        if ( $invoice->is_paid() ) {
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
                $response['msg']        = __( 'You can not add item to invoice because recurring item must be paid individually!', 'invoicing' );
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
        if ( !current_user_can( 'manage_options' ) ) {
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
        
        if ( $invoice->is_paid() ) {
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
        if ( !current_user_can( 'manage_options' ) ) {
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
            $response['msg']        = __( 'You can not add item to invoice because recurring item must be paid individually!', 'invoicing' );
            wp_send_json( $response );
        }        
        
        $save_item = $_POST['_wpinv_quick'];
        
        $meta               = array();
        $meta['type']       = !empty($save_item['type']) ? sanitize_text_field($save_item['type']) : 'custom';
        $meta['price']      = !empty($save_item['price']) ? wpinv_sanitize_amount( $save_item['price'] ) : 0;
        $meta['vat_rule']   = !empty($save_item['vat_rule']) ? sanitize_text_field($save_item['vat_rule']) : 'digital';
        $meta['vat_class']  = !empty($save_item['vat_class']) ? sanitize_text_field($save_item['vat_class']) : '_standard';
        
        $data                   = array();
        $data['post_title']     = sanitize_text_field($save_item['name']);
        $data['post_status']    = 'publish';
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
        
        if ( !current_user_can( 'manage_options' ) ) {
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
        if ( !current_user_can( 'manage_options' ) ) {
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
        if ( !current_user_can( 'manage_options' ) ) {
            die(-1);
        }
        
        $invoice_id = absint( $_POST['invoice_id'] );
        $discount_code = sanitize_text_field( $_POST['code'] );
        if ( empty( $invoice_id ) || empty( $discount_code ) ) {
            die();
        }
        
        $invoice = wpinv_get_invoice( $invoice_id );
        if ( empty( $invoice ) || ( !empty( $invoice ) && $invoice->is_paid() ) ) {
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
        if ( !current_user_can( 'manage_options' ) ) {
            die(-1);
        }
        
        $invoice_id = absint( $_POST['invoice_id'] );
        $discount_code = sanitize_text_field( $_POST['code'] );
        if ( empty( $invoice_id ) || empty( $discount_code ) ) {
            die();
        }
        
        $invoice = wpinv_get_invoice( $invoice_id );
        if ( empty( $invoice ) || ( !empty( $invoice ) && $invoice->is_paid() ) ) {
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
        if ( !current_user_can( 'manage_options' ) ) {
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
        if ( !current_user_can( 'manage_options' ) ) {
            die(-1);
        }
        
        $tool = sanitize_text_field( $_POST['tool'] );
        
        do_action( 'wpinv_run_tool' );
        
        if ( !empty( $tool ) ) {
            do_action( 'wpinv_tool_' . $tool );
        }
    }
    
    public static function apply_discount() {
        check_ajax_referer( 'wpinv-nonce', '_nonce' );
        
        $response = array();
        
        if ( isset( $_POST['code'] ) ) {
            $discount_code = sanitize_text_field( $_POST['code'] );

            $response['success']        = false;
            $response['msg']            = '';
            $response['data']['code']   = $discount_code;
            
            $user = is_user_logged_in() ? get_current_user_id() : '';

            if ( wpinv_is_discount_valid( $discount_code, $user ) ) {
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
}

WPInv_Ajax::init();