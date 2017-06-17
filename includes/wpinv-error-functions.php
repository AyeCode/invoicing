<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_print_errors() {
    $errors = wpinv_get_errors();

    if ( $errors ) {
        $classes = apply_filters( 'wpinv_error_class', array(
            'wpinv_errors', 'wpinv-alert', 'wpinv-alert-error'
        ) );
        echo '<div class="' . implode( ' ', $classes ) . '">';
            // Loop error codes and display errors
           foreach ( $errors as $error_id => $error ) {
                echo '<p class="wpinv_error" id="wpinv_error_' . $error_id . '"><strong>' . __( 'Error', 'invoicing' ) . '</strong>: ' . $error . '</p>';
           }
        echo '</div>';
        wpinv_clear_errors();
    }
}
add_action( 'wpinv_purchase_form_before_submit', 'wpinv_print_errors' );
add_action( 'wpinv_ajax_checkout_errors', 'wpinv_print_errors' );

function wpinv_get_errors() {
    global $wpi_session;
    
    return $wpi_session->get( 'wpinv_errors' );
}

function wpinv_set_error( $error_id, $error_message ) {
    global $wpi_session;
    
    $errors = wpinv_get_errors();

    if ( ! $errors ) {
        $errors = array();
    }

    $errors[ $error_id ] = $error_message;
    $wpi_session->set( 'wpinv_errors', $errors );
}

function wpinv_clear_errors() {
    global $wpi_session;
    
    $wpi_session->set( 'wpinv_errors', null );
}

function wpinv_unset_error( $error_id ) {
    global $wpi_session;
    
    $errors = wpinv_get_errors();

    if ( $errors ) {
        unset( $errors[ $error_id ] );
        $wpi_session->set( 'wpinv_errors', $errors );
    }
}

function wpinv_die_handler() {
    die();
}

function wpinv_die( $message = '', $title = '', $status = 400 ) {
    add_filter( 'wp_die_ajax_handler', 'wpinv_die_handler', 10, 3 );
    add_filter( 'wp_die_handler', 'wpinv_die_handler', 10, 3 );
    wp_die( $message, $title, array( 'response' => $status ));
}
