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
    $checkout_page    = wpinv_get_option( 'checkout_page' );
    $is_checkout      = ! empty( $checkout_page ) && is_page( $checkout_page );

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
	$is_success_page = ! empty( $is_success_page ) ? is_page( $is_success_page ) : false;

	return apply_filters( 'wpinv_is_success_page', $is_success_page );
}

function wpinv_is_invoice_history_page() {
	$ret = wpinv_get_option( 'invoice_history_page', false );
	$ret = $ret ? is_page( $ret ) : false;
	return apply_filters( 'wpinv_is_invoice_history_page', $ret );
}

function wpinv_is_subscriptions_history_page() {
    $ret = wpinv_get_option( 'invoice_subscription_page', false );
    $ret = $ret ? is_page( $ret ) : false;
    return apply_filters( 'wpinv_is_subscriptions_history_page', $ret );
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

    do_action( 'wpinv_pre_send_back_to_checkout', $args );
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
    $admin_email = wpinv_get_option( 'admin_email', get_option( 'admin_email' ) );
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
		if ( (bool)wpinv_get_option( 'fname_mandatory' ) ) {
			$required_fields['first_name'] = array(
				'error_id' => 'invalid_first_name',
				'error_message' => __( 'Please enter your first name', 'invoicing' )
			);
		}
		if ( (bool)wpinv_get_option( 'address_mandatory' ) ) {
			$required_fields['address'] = array(
				'error_id' => 'invalid_address',
				'error_message' => __( 'Please enter your address', 'invoicing' )
			);
		}
		if ( (bool)wpinv_get_option( 'city_mandatory' ) ) {
			$required_fields['city'] = array(
				'error_id' => 'invalid_city',
				'error_message' => __( 'Please enter your billing city', 'invoicing' )
			);
		}
		if ( (bool)wpinv_get_option( 'state_mandatory' ) ) {
			$required_fields['state'] = array(
				'error_id' => 'invalid_state',
				'error_message' => __( 'Please enter billing state / province', 'invoicing' )
			);
		}
		if ( (bool)wpinv_get_option( 'country_mandatory' ) ) {
			$required_fields['country'] = array(
				'error_id' => 'invalid_country',
				'error_message' => __( 'Please select your billing country', 'invoicing' )
			);
		}
    }

    return apply_filters( 'wpinv_checkout_required_fields', $required_fields );
}

function wpinv_is_ssl_enforced() {
    $ssl_enforced = wpinv_get_option( 'enforce_ssl', false );
    return (bool) apply_filters( 'wpinv_is_ssl_enforced', $ssl_enforced );
}

/**
 * Checks if the current user cna view an invoice.
 */
function wpinv_user_can_view_invoice( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );

    // Abort if the invoice does not exist.
    if ( 0 == $invoice->get_id() ) {
        return false;
    }

    // Don't allow trash, draft status
    if ( $invoice->is_draft() ) {
        return false;
    }

    // If users are not required to login to check out, compare the invoice keys.
    if ( ! wpinv_require_login_to_checkout() && isset( $_GET['invoice_key'] ) && trim( $_GET['invoice_key'] ) == $invoice->get_key() ) {
        return true;
    }

    // Always enable for admins..
    if ( wpinv_current_user_can_manage_invoicing() || current_user_can( 'view_invoices', $invoice->ID ) ) { // Admin user
        return true;
    }

    // Else, ensure that this is their invoice.
    if ( is_user_logged_in() && $invoice->get_user_id() == get_current_user_id() ) {
        return true;
    }

    return apply_filters( 'wpinv_current_user_can_view_invoice', false, $invoice );
}

function wpinv_schedule_events() {

    // Get the timestamp for the next event.
    $timestamp = wp_next_scheduled( 'wpinv_register_schedule_event_twicedaily' );

    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'wpinv_register_schedule_event_twicedaily' );
    }

    if ( ! wp_next_scheduled( 'wpinv_register_schedule_event_daily' ) ) {
        wp_schedule_event( current_time( 'timestamp' ), 'daily', 'wpinv_register_schedule_event_daily' );
    }
}
add_action( 'wp', 'wpinv_schedule_events' );

function wpinv_schedule_event_twicedaily() {
    wpinv_email_payment_reminders();
    wpinv_email_renewal_reminders();
}
add_action( 'wpinv_register_schedule_event_daily', 'wpinv_schedule_event_twicedaily' );

function wpinv_require_login_to_checkout() {
    $return = wpinv_get_option( 'login_to_checkout', false );
    return (bool) apply_filters( 'wpinv_require_login_to_checkout', $return );
}

function wpinv_sequential_number_active( $type = '' ) {
    $check = apply_filters( 'wpinv_pre_check_sequential_number_active', null, $type );
    if ( null !== $check ) {
        return $check;
    }
    
    return wpinv_get_option( 'sequential_invoice_number' );
}

function wpinv_switch_to_locale( $locale = NULL ) {
    global $invoicing, $wpi_switch_locale;

    if ( ! empty( $invoicing ) && function_exists( 'switch_to_locale' ) ) {
        $locale = empty( $locale ) ? get_locale() : $locale;

        switch_to_locale( $locale );

        $wpi_switch_locale = $locale;

        add_filter( 'plugin_locale', 'get_locale' );

        $invoicing->load_textdomain();

        do_action( 'wpinv_switch_to_locale', $locale );
    }
}

function wpinv_restore_locale() {
    global $invoicing, $wpi_switch_locale;
    
    if ( ! empty( $invoicing ) && function_exists( 'restore_previous_locale' ) && $wpi_switch_locale ) {
        restore_previous_locale();

        $wpi_switch_locale = NULL;

        remove_filter( 'plugin_locale', 'get_locale' );

        $invoicing->load_textdomain();

        do_action( 'wpinv_restore_locale' );
    }
}

/**
 * Returns the default form's id.
 */
function wpinv_get_default_payment_form() {
    $form = get_option( 'wpinv_default_payment_form' );

    if ( empty( $form ) || 'publish' != get_post_status( $form ) ) {
        $form = wp_insert_post(
            array(
                'post_type'   => 'wpi_payment_form',
                'post_title'  => __( 'Checkout (default)', 'invoicing' ),
                'post_status' => 'publish',
                'meta_input'  => array(
                    'wpinv_form_elements' => wpinv_get_data( 'default-payment-form' ),
                    'wpinv_form_items'    => array(),
                )
            )
        );

        update_option( 'wpinv_default_payment_form', $form );
    }

    return $form;
}