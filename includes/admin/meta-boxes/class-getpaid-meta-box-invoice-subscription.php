<?php
/**
 * Invoice Subscription Details
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Subscription Class.
 */
class GetPaid_Meta_Box_Invoice_Subscription {

    /**
	 * Output the subscription metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( $post );

        // Fetch the subscription.
        $subscription = getpaid_get_invoice_subscription( $invoice );

        echo '<div class="bsui">';
        getpaid_admin_subscription_details_metabox( /** @scrutinizer ignore-type */$subscription );
        echo '</div>';

    }

    /**
	 * Output the subscription invoices.
	 *
	 * @param WP_Post $post
	 */
    public static function output_invoices( $post ) {

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( $post );

        // Fetch the subscription.
        $subscription = getpaid_get_invoice_subscription( $invoice );

        echo '<div class="bsui">';
        getpaid_admin_subscription_invoice_details_metabox( /** @scrutinizer ignore-type */$subscription, false );
        echo '</div>';

    }

    /**
	 * Outputs related subscriptions.
	 *
	 * @param WP_Post $post
	 */
    public static function output_related( $post ) {

        // Fetch the invoice.
        $invoice = new WPInv_Invoice( $post );

        // Fetch the subscription.
        $subscription = getpaid_get_invoice_subscription( $invoice );

        echo '<div class="bsui">';
        getpaid_admin_subscription_related_subscriptions_metabox( /** @scrutinizer ignore-type */$subscription, false );
        echo '</div>';

    }

}
