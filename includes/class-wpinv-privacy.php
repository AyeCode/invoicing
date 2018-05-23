<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 */

defined( 'ABSPATH' ) || exit;

/**
 * WPInv_Privacy Class.
 */
class WPInv_Privacy extends WPInv_Abstract_Privacy {

    /**
     * Init - hook into events.
     */
    public function __construct() {
        parent::__construct( __( 'Invoicing', 'invoicing' ) );

        // Include supporting classes.
        include_once 'class-wpinv-privacy-exporters.php';

        // This hook registers Invoicing data exporters.
        $this->add_exporter( 'wpinv-customer-invoices', __( 'Customer Invoices', 'invoicing' ), array( 'WPInv_Privacy_Exporters', 'customer_invoice_data_exporter' ) );
    }

    /**
     * Add privacy policy content for the privacy policy page.
     *
     * @since 3.4.0
     */
    public function get_privacy_message() {
        $content = '
			<div contenteditable="false">' .
            '<p class="wp-policy-help">' .
            __( 'Invoicing uses the following privacy.', 'invoicing' ) .
            '</p>' .
            '</div>';

        return apply_filters( 'wpinv_privacy_policy_content', $content );
    }

}

new WPInv_Privacy();
