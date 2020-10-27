<?php
/**
 * Contains the main CLI class
 *
 * @since 1.0.13
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit;
}

/**
 * The main class responsible for registering CLI commands
 * @since 1.0.13
 */
class WPInv_CLI {

    /**
     * Creates a new invoice
     * 
     *  @param Array $args Arguments in array format.
     *  @param Array $assoc_args Key value arguments stored in associated array format.
     *  @since 1.0.13
     */
    public function insert_invoice( $args, $assoc_args ) {

        // Fetch invoice data from the args
        $invoice_data = wp_unslash( $assoc_args );

        // Abort if no invoice data is provided
        if( empty( $invoice_data ) ) {
            return WP_CLI::error( __( 'Invoice data not provided', 'invoicing' ) );
        }

        //Cart details
        if( !empty( $invoice_data['cart_details'] ) ) {
            $invoice_data['cart_details'] = json_decode( $invoice_data['cart_details'], true );
        }

        //User details
        if( !empty( $invoice_data['user_info'] ) ) {
            $invoice_data['user_info'] = json_decode( $invoice_data['user_info'], true );
        }

        //Payment info
        if( !empty( $invoice_data['payment_details'] ) ) {
            $invoice_data['payment_details'] = json_decode( $invoice_data['payment_details'], true );
        }

        // Try creating the invoice
        $invoice = wpinv_insert_invoice( $invoice_data, true );

        if ( is_wp_error( $invoice ) ) {
            return WP_CLI::error( $invoice->get_error_message() );
        }

        $message = sprintf( __( 'Invoice %s created', 'invoicing' ), $invoice->get_id() );
        WP_CLI::success( $message );
    }
    
    
}