<?php
/**
 * 
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}


function wpinv_get_default_labels() {
    $defaults = array(
       'singular' => __( 'Invoice', 'invoicing' ),
       'plural'   => __( 'Invoices', 'invoicing' )
    );
    
    return apply_filters( 'wpinv_default_invoices_name', $defaults );
}

function wpinv_get_label_singular( $lowercase = false ) {
    $defaults = wpinv_get_default_labels();
    
    return ($lowercase) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
}

function wpinv_get_label_plural( $lowercase = false ) {
    $defaults = wpinv_get_default_labels();
    
    return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
}

function wpinv_change_default_title( $title ) {
     if ( !is_admin() ) {
        $label = wpinv_get_label_singular();
        $title = sprintf( __( 'Enter %s name here', 'invoicing' ), $label );
        return $title;
     }

     $screen = get_current_screen();

     if ( 'wpi_invoice' == $screen->post_type ) {
        $label = wpinv_get_label_singular();
        $title = sprintf( __( 'Enter %s name here', 'invoicing' ), $label );
     }

     return $title;
}
add_filter( 'enter_title_here', 'wpinv_change_default_title' );
