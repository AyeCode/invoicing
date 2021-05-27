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

/**
 * Retrieves the invoice/quote history page URL.
 * 
 * @param string $post_type The post type or invoice type.
 * @return string The history page URL.
 */
function wpinv_get_history_page_uri( $post_type = 'wpi_invoice' ) {
    $post_type = sanitize_key( str_replace( 'wpi_', '', $post_type ) );
	$page_id   = wpinv_get_option( "{$post_type}_history_page", 0 );
	$page_id   = absint( $page_id );
	return apply_filters( 'wpinv_get_history_page_uri', get_permalink( $page_id ), $post_type );
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

/**
 * Redirects a user the success page.
 */
function wpinv_send_to_success_page( $args = array() ) {

    $redirect = add_query_arg(
        wp_parse_args( $args ),
        wpinv_get_success_page_uri()
    );

    $redirect = apply_filters( 'wpinv_send_to_success_page_url', $redirect, $args );

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
    $name = wpinv_get_option( 'store_name', wpinv_get_blogname() );

    if ( empty( $name ) ) {
        $name = wpinv_get_blogname();
    }

    return apply_filters( 'wpinv_get_business_name', $name );
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

function wpinv_schedule_event_twicedaily() {
    wpinv_email_payment_reminders();
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

    // WPML support.
    $form = apply_filters( 'wpml_object_id', $form, 'wpi_payment_form', TRUE  );
    return $form;
}

/**
 * Retrieves a given payment form's elements.
 * 
 * @param int $payment_form
 */
function getpaid_get_payment_form_elements( $payment_form ) {

    if ( empty( $payment_form ) ) {
        return wpinv_get_data( 'sample-payment-form' );
    }

    $form_elements = get_post_meta( $payment_form, 'wpinv_form_elements', true );

    if ( is_array( $form_elements ) ) {
        return $form_elements;
    }

    return wpinv_get_data( 'sample-payment-form' );

}

/**
 * Returns an array of items for the given form.
 * 
 * @param int $payment_form
 */
function gepaid_get_form_items( $id ) {
    $form = new GetPaid_Payment_Form( $id );

    // Is this a default form?
    if ( $form->is_default() ) {
        return array();
    }

    return $form->get_items( 'view', 'arrays' );
}

/**
 * Trims each line in a paragraph.
 * 
 */
function gepaid_trim_lines( $content ) {
    return implode( "\n", array_map( 'trim', explode( "\n", $content ) ) );
}
