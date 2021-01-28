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
     * This is the name of this object type.
     *
     * @var string
     */
    public $name = 'GetPaid';

    /**
     * Init - hook into events.
     */
    public function __construct() {

        // Init hooks.
        $this->init();

        // This hook registers Invoicing data exporters.
        $this->add_exporter( 'wpinv-customer-invoices', __( 'Customer Invoices', 'invoicing' ), array( 'WPInv_Privacy_Exporters', 'customer_invoice_data_exporter' ) );
    }

    /**
     * Add privacy policy content for the privacy policy page.
     *
     * @since 1.4.0
     */
    public function get_privacy_message() {

        $content = '<div class="wp-suggested-text">' .
                   '<h2>' . __( 'Invoices and checkout', 'invoicing' ) . '</h2>' .
                   '<p class="privacy-policy-tutorial">' . __( 'Example privacy texts.', 'invoicing' ) . '</p>' .
                   '<p>' . __( 'We collect information about you during the checkout process on our site. This information may include, but is not limited to, your name, email address, phone number, address, IP and any other details that might be requested from you for the purpose of processing your payment and retaining your invoice details for legal reasons.', 'invoicing' ) . '</p>' .
                   '<p>' . __( 'Handling this data also allows us to:', 'invoicing' ) . '</p>' .
                   '<ul>' .
                   '<li>' . __( '- Send you important account/invoice/service information.', 'invoicing' ) . '</li>' .
                   '<li>' . __( '- Estimate taxes based on your location.', 'invoicing' ) . '</li>' .
                   '<li>' . __( '- Respond to your queries or complaints.', 'invoicing' ) . '</li>' .
                   '<li>' . __( '- Process payments and to prevent fraudulent transactions. We do this on the basis of our legitimate business interests.', 'invoicing' ) . '</li>' .
                   '<li>' . __( '- Retain historical payment and invoice history. We do this on the basis of legal obligations.', 'invoicing' ) . '</li>' .
                   '<li>' . __( '- Set up and administer your account, provide technical and/or customer support, and to verify your identity. We do this on the basis of our legitimate business interests.', 'invoicing' ) . '</li>' .
                   '</ul>' .
                   '<p>' . __( 'In addition to collecting information at checkout we may also use and store your contact details when manually creating invoices for require payments relating to prior contractual agreements or agreed terms.', 'invoicing' ) . '</p>' .
                   '<h2>' . __( 'What we share with others', 'invoicing' ) . '</h2>' .
                   '<p>' . __( 'We share information with third parties who help us provide our payment and invoicing services to you; for example --', 'invoicing' ) . '</p>' .
                   '<p class="privacy-policy-tutorial">' . __( 'In this subsection you should list which third party payment processors you’re using to take payments since these may handle customer data. We’ve included PayPal as an example, but you should remove this if you’re not using PayPal.', 'invoicing' ) . '</p>' .
                   '<p>' . __( 'We accept payments through PayPal. When processing payments, some of your data will be passed to PayPal, including information required to process or support the payment, such as the purchase total and billing information.', 'invoicing' ) . '</p>' .
                   '<p>' . __( 'Please see the <a href="https://www.paypal.com/us/webapps/mpp/ua/privacy-full">PayPal Privacy Policy</a> for more details.', 'invoicing' ) . '</p>' .
                   '</div>';

        return apply_filters( 'wpinv_privacy_policy_content', $content );
    }

}

new WPInv_Privacy();
