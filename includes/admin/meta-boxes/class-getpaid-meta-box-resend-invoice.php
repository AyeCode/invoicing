<?php
/**
 * Invoice Resend
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Resend_Invoice Class.
 */
class GetPaid_Meta_Box_Resend_Invoice {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( $post );

        do_action( 'wpinv_metabox_resend_invoice_before', $invoice );

        $invoice_actions = array(
            'resend-email' => array(
                'url' => wp_nonce_url(
                    add_query_arg(
                        array(
                            'getpaid-admin-action' => 'send_invoice',
                            'invoice_id'           => $invoice->get_id()
                        )
                    ),
                    'getpaid-nonce',
                    'getpaid-nonce'
                ),
                'label' => __( 'Resend Invoice', 'invoicing' ),
            )
        );

        if ( $invoice->needs_payment() ) {

            $invoice_actions['send-reminder'] = array(
                'url' => wp_nonce_url(
                    add_query_arg(
                        array(
                            'getpaid-admin-action' => 'send_invoice_reminder',
                            'invoice_id'           => $invoice->get_id()
                        )
                    ),
                    'getpaid-nonce',
                    'getpaid-nonce'
                ),
                'label' => __( 'Send Reminder', 'invoicing' ),
            );

        }

        $invoice_actions = apply_filters( 'getpaid_edit_invoice_actions', $invoice_actions, $invoice );

        foreach ( $invoice_actions as $key => $action ) {

            if ( 'resend-email' === $key ) {
                echo wpautop( __( "This will send a copy of the invoice to the customer's email address.", 'invoicing' ) );
            }

            printf(
                '<p class="wpi-meta-row wpi-%s"><a href="%s" class="button button-secondary">%s</a>',
                esc_attr( $key ),
                esc_url( $action['url'] ),
                esc_html( $action['label'] )
            );

        }

        do_action( 'wpinv_metabox_resend_invoice_after', $invoice );

    }

}
