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

        $email_url = esc_url(
            add_query_arg(
                array(
                    'wpi_action'    => 'send_invoice',
                    'invoice_id'    => $invoice->get_id(),
                    'wpinv-message' => false,
                )
            )
        );

        $reminder_url = esc_url(
            wp_nonce_url(
                add_query_arg(
                    array(
                        'getpaid-admin-action' => 'send_invoice_reminder',
                        'invoice_id'           => $invoice->get_id()
                    )
                ),
                'getpaid-nonce',
                'getpaid-nonce'
            )
        );

        ?>
            <p class="wpi-meta-row wpi-resend-info"><?php _e( "This will send a copy of the invoice to the customer's email address.", 'invoicing' ); ?></p>
            <p class="wpi-meta-row wpi-resend-email"><a href="<?php echo $email_url; ?>" class="button button-secondary"><?php _e( 'Resend Invoice', 'invoicing' ); ?></a></p>
            <p class="wpi-meta-row wpi-send-reminder"><a title="<?php esc_attr_e( 'Send overdue reminder notification to customer', 'invoicing' ); ?>" href="<?php echo $reminder_url; ?>" class="button button-secondary"><?php esc_attr_e( 'Send Reminder', 'invoicing' ); ?></a></p>
        <?php

        do_action( 'wpinv_metabox_resend_invoice_after', $invoice );

    }

}
