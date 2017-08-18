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

function wpinv_is_checkout() {
    global $wp_query;

    $is_object_set    = isset( $wp_query->queried_object );
    $is_object_id_set = isset( $wp_query->queried_object_id );
    $is_checkout      = is_page( wpinv_get_option( 'checkout_page' ) );

    if ( !$is_object_set ) {
        unset( $wp_query->queried_object );
    }

    if ( !$is_object_id_set ) {
        unset( $wp_query->queried_object_id );
    }

    return apply_filters( 'wpinv_is_checkout', $is_checkout );
}

function wpinv_can_checkout() {
	$can_checkout = true; // Always true for now

	return (bool) apply_filters( 'wpinv_can_checkout', $can_checkout );
}

function wpinv_get_success_page_uri() {
	$page_id = wpinv_get_option( 'success_page', 0 );
	$page_id = absint( $page_id );

	return apply_filters( 'wpinv_get_success_page_uri', get_permalink( $page_id ) );
}

function wpinv_get_history_page_uri() {
	$page_id = wpinv_get_option( 'invoice_history_page', 0 );
	$page_id = absint( $page_id );

	return apply_filters( 'wpinv_get_history_page_uri', get_permalink( $page_id ) );
}

function wpinv_is_success_page() {
	$is_success_page = wpinv_get_option( 'success_page', false );
	$is_success_page = isset( $is_success_page ) ? is_page( $is_success_page ) : false;

	return apply_filters( 'wpinv_is_success_page', $is_success_page );
}

function wpinv_is_invoice_history_page() {
	$ret = wpinv_get_option( 'invoice_history_page', false );
	$ret = $ret ? is_page( $ret ) : false;
	return apply_filters( 'wpinv_is_invoice_history_page', $ret );
}

function wpinv_send_to_success_page( $args = null ) {
	$redirect = wpinv_get_success_page_uri();
    
    if ( !empty( $args ) ) {
        // Check for backward compatibility
        if ( is_string( $args ) )
            $args = str_replace( '?', '', $args );

        $args = wp_parse_args( $args );

        $redirect = add_query_arg( $args, $redirect );
    }

    $gateway = isset( $_REQUEST['wpi-gateway'] ) ? $_REQUEST['wpi-gateway'] : '';
    
    $redirect = apply_filters( 'wpinv_success_page_redirect', $redirect, $gateway, $args );
    wp_redirect( $redirect );
    exit;
}

function wpinv_send_to_failed_page( $args = null ) {
	$redirect = wpinv_get_failed_transaction_uri();
    
    if ( !empty( $args ) ) {
        // Check for backward compatibility
        if ( is_string( $args ) )
            $args = str_replace( '?', '', $args );

        $args = wp_parse_args( $args );

        $redirect = add_query_arg( $args, $redirect );
    }

    $gateway = isset( $_REQUEST['wpi-gateway'] ) ? $_REQUEST['wpi-gateway'] : '';
    
    $redirect = apply_filters( 'wpinv_failed_page_redirect', $redirect, $gateway, $args );
    wp_redirect( $redirect );
    exit;
}

function wpinv_get_checkout_uri( $args = array() ) {
	$uri = wpinv_get_option( 'checkout_page', false );
	$uri = isset( $uri ) ? get_permalink( $uri ) : NULL;

	if ( !empty( $args ) ) {
		// Check for backward compatibility
		if ( is_string( $args ) )
			$args = str_replace( '?', '', $args );

		$args = wp_parse_args( $args );

		$uri = add_query_arg( $args, $uri );
	}

	$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

	$ajax_url = admin_url( 'admin-ajax.php', $scheme );

	if ( ( ! preg_match( '/^https/', $uri ) && preg_match( '/^https/', $ajax_url ) ) || wpinv_is_ssl_enforced() ) {
		$uri = preg_replace( '/^http:/', 'https:', $uri );
	}

	return apply_filters( 'wpinv_get_checkout_uri', $uri );
}

function wpinv_send_back_to_checkout( $args = array() ) {
	$redirect = wpinv_get_checkout_uri();

	if ( ! empty( $args ) ) {
		// Check for backward compatibility
		if ( is_string( $args ) )
			$args = str_replace( '?', '', $args );

		$args = wp_parse_args( $args );

		$redirect = add_query_arg( $args, $redirect );
	}

	wp_redirect( apply_filters( 'wpinv_send_back_to_checkout', $redirect, $args ) );
	exit;
}

function wpinv_get_success_page_url( $query_string = null ) {
	$success_page = wpinv_get_option( 'success_page', 0 );
	$success_page = get_permalink( $success_page );

	if ( $query_string )
		$success_page .= $query_string;

	return apply_filters( 'wpinv_success_page_url', $success_page );
}

function wpinv_get_failed_transaction_uri( $extras = false ) {
	$uri = wpinv_get_option( 'failure_page', '' );
	$uri = ! empty( $uri ) ? trailingslashit( get_permalink( $uri ) ) : home_url();

	if ( $extras )
		$uri .= $extras;

	return apply_filters( 'wpinv_get_failed_transaction_uri', $uri );
}

function wpinv_is_failed_transaction_page() {
	$ret = wpinv_get_option( 'failure_page', false );
	$ret = isset( $ret ) ? is_page( $ret ) : false;

	return apply_filters( 'wpinv_is_failure_page', $ret );
}

function wpinv_transaction_query( $type = 'start' ) {
    global $wpdb;

    $wpdb->hide_errors();

    if ( ! defined( 'WPINV_USE_TRANSACTIONS' ) ) {
        define( 'WPINV_USE_TRANSACTIONS', true );
    }

    if ( WPINV_USE_TRANSACTIONS ) {
        switch ( $type ) {
            case 'commit' :
                $wpdb->query( 'COMMIT' );
                break;
            case 'rollback' :
                $wpdb->query( 'ROLLBACK' );
                break;
            default :
                $wpdb->query( 'START TRANSACTION' );
            break;
        }
    }
}

function wpinv_create_invoice( $args = array(), $data = array(), $wp_error = false ) {
    $default_args = array(
        'status'        => '',
        'user_id'       => null,
        'user_note'     => null,
        'invoice_id'    => 0,
        'created_via'   => '',
        'parent'        => 0
    );

    $args           = wp_parse_args( $args, $default_args );
    $invoice_data   = array();

    if ( $args['invoice_id'] > 0 ) {
        $updating           = true;
        $invoice_data['post_type']  = 'wpi_invoice';
        $invoice_data['ID']         = $args['invoice_id'];
    } else {
        $updating                       = false;
        $invoice_data['post_type']      = 'wpi_invoice';
        $invoice_data['post_status']    = apply_filters( 'wpinv_default_invoice_status', 'wpi-pending' );
        $invoice_data['ping_status']    = 'closed';
        $invoice_data['post_author']    = !empty( $args['user_id'] ) ? $args['user_id'] : get_current_user_id();
        $invoice_data['post_title']     = wpinv_format_invoice_number( '0' );
        $invoice_data['post_parent']    = absint( $args['parent'] );
        if ( !empty( $args['created_date'] ) ) {
            $invoice_data['post_date']      = $args['created_date'];
            $invoice_data['post_date_gmt']  = get_gmt_from_date( $args['created_date'] );
        }
    }

    if ( $args['status'] ) {
        if ( ! in_array( $args['status'], array_keys( wpinv_get_invoice_statuses() ) ) ) {
            return new WP_Error( 'wpinv_invalid_invoice_status', wp_sprintf( __( 'Invalid invoice status: %s', 'invoicing' ), $args['status'] ) );
        }
        $invoice_data['post_status']    = $args['status'];
    }

    if ( ! is_null( $args['user_note'] ) ) {
        $invoice_data['post_excerpt']   = $args['user_note'];
    }

    if ( $updating ) {
        $invoice_id = wp_update_post( $invoice_data, true );
    } else {
        $invoice_id = wp_insert_post( apply_filters( 'wpinv_new_invoice_data', $invoice_data ), true );
    }

    if ( is_wp_error( $invoice_id ) ) {
        return $wp_error ? $invoice_id : 0;
    }
    
    $invoice = wpinv_get_invoice( $invoice_id );

    if ( !$updating ) {
        update_post_meta( $invoice_id, '_wpinv_key', apply_filters( 'wpinv_generate_invoice_key', uniqid( 'wpinv_' ) ) );
        update_post_meta( $invoice_id, '_wpinv_currency', wpinv_get_currency() );
        update_post_meta( $invoice_id, '_wpinv_include_tax', get_option( 'wpinv_prices_include_tax' ) );
        update_post_meta( $invoice_id, '_wpinv_user_ip', wpinv_get_ip() );
        update_post_meta( $invoice_id, '_wpinv_user_agent', wpinv_get_user_agent() );
        update_post_meta( $invoice_id, '_wpinv_created_via', sanitize_text_field( $args['created_via'] ) );
        
        // Add invoice note
        $invoice->add_note( wp_sprintf( __( 'Invoice is created with status %s.', 'invoicing' ), wpinv_status_nicename( $invoice->status ) ) );
    }

    update_post_meta( $invoice_id, '_wpinv_version', WPINV_VERSION );

    return $invoice;
}

function wpinv_get_prefix() {
    $invoice_prefix = 'INV-';
    
    return apply_filters( 'wpinv_get_prefix', $invoice_prefix );
}

function wpinv_get_business_logo() {
    $business_logo = wpinv_get_option( 'logo' );
    return apply_filters( 'wpinv_get_business_logo', $business_logo );
}

function wpinv_get_business_name() {
    $business_name = wpinv_get_option('store_name');
    return apply_filters( 'wpinv_get_business_name', $business_name );
}

function wpinv_get_blogname() {
    return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
}

function wpinv_get_admin_email() {
    $admin_email = get_option( 'admin_email' );
    return apply_filters( 'wpinv_admin_email', $admin_email );
}

function wpinv_get_business_website() {
    $business_website = home_url( '/' );
    return apply_filters( 'wpinv_get_business_website', $business_website );
}

function wpinv_get_terms_text( $invoice_id = 0 ) {
    $terms_text = '';
    return apply_filters( 'wpinv_get_terms_text', $terms_text, $invoice_id );
}

function wpinv_get_business_footer() {
    $site_link = '<a target="_blank" href="' . esc_url( wpinv_get_business_website() ) . '">' . esc_html( wpinv_get_business_name() ) . '</a>';
    $business_footer = wp_sprintf( __( 'Thanks for using %s', 'invoicing' ), $site_link );
    return apply_filters( 'wpinv_get_business_footer', $business_footer );
}

function wpinv_checkout_required_fields() {
    $required_fields = array();
    
    // Let payment gateways and other extensions determine if address fields should be required
    $require_billing_details = apply_filters( 'wpinv_checkout_required_billing_details', wpinv_use_taxes() );
    
    if ( $require_billing_details ) {
        ///$required_fields['email'] = array(
                ///'error_id' => 'invalid_email',
                ///'error_message' => __( 'Please enter a valid email address', 'invoicing' )
            ///);
        $required_fields['first_name'] = array(
                'error_id' => 'invalid_first_name',
                'error_message' => __( 'Please enter your first name', 'invoicing' )
            );
        $required_fields['address'] = array(
                'error_id' => 'invalid_address',
                'error_message' => __( 'Please enter your address', 'invoicing' )
            );
        $required_fields['city'] = array(
                'error_id' => 'invalid_city',
                'error_message' => __( 'Please enter your billing city', 'invoicing' )
            );
        $required_fields['state'] = array(
                'error_id' => 'invalid_state',
                'error_message' => __( 'Please enter billing state / province', 'invoicing' )
            );
        $required_fields['country'] = array(
                'error_id' => 'invalid_country',
                'error_message' => __( 'Please select your billing country', 'invoicing' )
            );
    }

    return apply_filters( 'wpinv_checkout_required_fields', $required_fields );
}

function wpinv_is_ssl_enforced() {
    $ssl_enforced = wpinv_get_option( 'enforce_ssl', false );
    return (bool) apply_filters( 'wpinv_is_ssl_enforced', $ssl_enforced );
}

function wpinv_user_can_print_invoice( $post ) {
    $allow = false;
    
    if ( !( $user_id = get_current_user_id() ) ) {
        return $allow;
    }
    
    if ( is_int( $post ) ) {
        $post = get_post( $post );
    }
    
    // Allow to owner.
    if ( is_object( $post ) && !empty( $post->post_author ) && $post->post_author == $user_id ) {
        $allow = true;
    }
    
    // Allow to admin user.
    if ( current_user_can( 'manage_options' ) ) {
        $allow = true;
    }
    
    return apply_filters( 'wpinv_can_print_invoice', $allow, $post );
}

function wpinv_schedule_events() {
    // hourly, daily and twicedaily
    if ( !wp_next_scheduled( 'wpinv_register_schedule_event_twicedaily' ) ) {
        wp_schedule_event( current_time( 'timestamp' ), 'twicedaily', 'wpinv_register_schedule_event_twicedaily' );
    }
}
add_action( 'wp', 'wpinv_schedule_events' );

function wpinv_schedule_event_twicedaily() {
    wpinv_email_payment_reminders();
}
add_action( 'wpinv_register_schedule_event_twicedaily', 'wpinv_schedule_event_twicedaily' );