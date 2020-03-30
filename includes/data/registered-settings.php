<?php
/**
 * Registered settings.
 *
 * Returns an array of registered settings.
 *
 * @package Invoicing/data
 * @version 1.0.17
 */

defined( 'ABSPATH' ) || exit;

$pages = wpinv_get_pages( true );

// Prepage currency options.
$currencies            = wpinv_get_currencies();

$currency_code_options = array();
foreach ( $currencies as $code => $name ) {
    $currency_code_options[ $code ] = $code . ' - ' . $name . ' (' . wpinv_currency_symbol( $code ) . ')';
}

$due_payment_options       = array();
$due_payment_options[0]    = __( 'Now', 'invoicing' );
for ( $i = 1; $i <= 30; $i++ ) {
    $due_payment_options[$i] = $i;
}

$invoice_number_padd_options = array();
for ( $i = 0; $i <= 20; $i++ ) {
    $invoice_number_padd_options[$i] = $i;
}

$currency_symbol = wpinv_currency_symbol();

$last_number = $reset_number = '';
if ( $last_invoice_number = get_option( 'wpinv_last_invoice_number' ) ) {
    $last_invoice_number = is_numeric( $last_invoice_number ) ? $last_invoice_number : wpinv_clean_invoice_number( $last_invoice_number );

    if ( !empty( $last_invoice_number ) ) {
        $last_number = ' ' . wp_sprintf( __( "( Last Invoice's sequential number: <b>%s</b> )", 'invoicing' ), $last_invoice_number );
    }

    $nonce = wp_create_nonce('reset_invoice_count');
    $reset_number = '<a href="'.add_query_arg(array('reset_invoice_count' => 1, '_nonce' => $nonce)).'" class="btn button">'.__('Force Reset Sequence', 'invoicing' ). '</a>';
}

$alert_wrapper_start = '<p style="color: #F00">';
$alert_wrapper_close = '</p>';

return array(
    'general' => apply_filters( 'wpinv_settings_general',
        array(
            'main' => array(
                'location_settings' => array(
                    'id'   => 'location_settings',
                    'name' => '<h3>' . __( 'Default Location', 'invoicing' ) . '</h3>',
                    'desc' => '',
                    'type' => 'header',
                ),
                'default_country' => array(
                    'id'      => 'default_country',
                    'name'    => __( 'Default Country', 'invoicing' ),
                    'desc'    => __( 'Where does your store operate from?', 'invoicing' ),
                    'type'    => 'select',
                    'options' => wpinv_get_country_list(),
                    'std'     => 'GB',
                    'class'   => 'wpi_select2',
                    'placeholder' => __( 'Select a country', 'invoicing' ),
                ),
                'default_state' => array(
                    'id'      => 'default_state',
                    'name'    => __( 'Default State / Province', 'invoicing' ),
                    'desc'    => __( 'What state / province does your store operate from?', 'invoicing' ),
                    'type'    => 'country_states',
                    'class'   => 'wpi_select2',
                    'placeholder' => __( 'Select a state', 'invoicing' ),
                ),
                'store_name' => array(
                    'id'   => 'store_name',
                    'name' => __( 'Store Name', 'invoicing' ),
                    'desc' => __( 'Store name to print on invoices.', 'invoicing' ),
                    'std'     => get_option('blogname'),
                    'type' => 'text',
                ),
                'logo' => array(
                    'id'   => 'logo',
                    'name' => __( 'Logo URL', 'invoicing' ),
                    'desc' => __( 'Store logo to print on invoices.', 'invoicing' ),
                    'type' => 'text',
                ),
                'store_address' => array(
                    'id'   => 'store_address',
                    'name' => __( 'Store Address', 'invoicing' ),
                    'desc' => __( 'Enter the store address to display on invoice', 'invoicing' ),
                    'type' => 'textarea',
                ),
                'page_settings' => array(
                    'id'   => 'page_settings',
                    'name' => '<h3>' . __( 'Page Settings', 'invoicing' ) . '</h3>',
                    'desc' => '',
                    'type' => 'header',
                ),
                'checkout_page' => array(
                    'id'          => 'checkout_page',
                    'name'        => __( 'Checkout Page', 'invoicing' ),
                    'desc'        => __( 'This is the checkout page where buyers will complete their payments. The <b>[wpinv_checkout]</b> short code must be on this page.', 'invoicing' ),
                    'type'        => 'select',
                    'options'     => $pages,
                    'class'       => 'wpi_select2',
                    'placeholder' => __( 'Select a page', 'invoicing' ),
                ),
                'tandc_page' => array(
                    'id'          => 'tandc_page',
                    'name'        => __( 'Terms & Conditions', 'invoicing' ),
                    'desc'        => __( 'If you select a "Terms & Conditions" page here the customer will be asked to accept them on checkout.', 'invoicing' ),
                    'type'        => 'select',
                    'options'     => wpinv_get_pages( true,  __( 'Select a page', 'invoicing' )),
                    'class'       => 'wpi_select2',
                    'placeholder' => __( 'Select a page', 'invoicing' ),
                ),
                'success_page' => array(
                    'id'          => 'success_page',
                    'name'        => __( 'Success Page', 'invoicing' ),
                    'desc'        => __( 'This is the page buyers are sent to after completing their payments. The <b>[wpinv_receipt]</b> short code should be on this page.', 'invoicing' ),
                    'type'        => 'select',
                    'options'     => $pages,
                    'class'       => 'wpi_select2',
                    'placeholder' => __( 'Select a page', 'invoicing' ),
                ),
                'failure_page' => array(
                    'id'          => 'failure_page',
                    'name'        => __( 'Failed Transaction Page', 'invoicing' ),
                    'desc'        => __( 'This is the page buyers are sent to if their transaction is cancelled or fails.', 'invoicing' ),
                    'type'        => 'select',
                    'options'     => $pages,
                    'class'       => 'wpi_select2',
                    'placeholder' => __( 'Select a page', 'invoicing' ),
                ),
                'invoice_history_page' => array(
                    'id'          => 'invoice_history_page',
                    'name'        => __( 'Invoice History Page', 'invoicing' ),
                    'desc'        => __( 'This page shows an invoice history for the current user. The <b>[wpinv_history]</b> short code should be on this page.', 'invoicing' ),
                    'type'        => 'select',
                    'options'     => $pages,
                    'class'       => 'wpi_select2',
                    'placeholder' => __( 'Select a page', 'invoicing' ),
                ),
                'invoice_subscription_page' => array(
                    'id'          => 'invoice_subscription_page',
                    'name'        => __( 'Invoice Subscriptions Page', 'invoicing' ),
                    'desc'        => __( 'This page shows subscriptions history for the current user. The <b>[wpinv_subscriptions]</b> short code should be on this page.', 'invoicing' ),
                    'type'        => 'select',
                    'options'     => $pages,
                    'class'       => 'wpi_select2',
                    'placeholder' => __( 'Select a page', 'invoicing' ),
                ),
            ),
            'currency_section' => array(
                'currency_settings' => array(
                    'id'   => 'currency_settings',
                    'name' => '<h3>' . __( 'Currency Settings', 'invoicing' ) . '</h3>',
                    'desc' => '',
                    'type' => 'header',
                ),
                'currency' => array(
                    'id'      => 'currency',
                    'name'    => __( 'Currency', 'invoicing' ),
                    'desc'    => __( 'Choose your currency. Note that some payment gateways have currency restrictions.', 'invoicing' ),
                    'type'    => 'select',
                    'class'       => 'wpi_select2',
                    'options' => $currency_code_options,
                ),
                'currency_position' => array(
                    'id'      => 'currency_position',
                    'name'    => __( 'Currency Position', 'invoicing' ),
                    'desc'    => __( 'Choose the location of the currency sign.', 'invoicing' ),
                    'type'    => 'select',
                    'class'   => 'wpi_select2',
                    'options'  => array(
                        'left'        => __( 'Left', 'invoicing' ) . ' (' . $currency_symbol . wpinv_format_amount( '99.99' ) . ')',
                        'right'       => __( 'Right', 'invoicing' ) . ' ('. wpinv_format_amount( '99.99' ) . $currency_symbol . ')',
                        'left_space'  => __( 'Left with space', 'invoicing' ) . ' (' . $currency_symbol . ' ' . wpinv_format_amount( '99.99' ) . ')',
                        'right_space' => __( 'Right with space', 'invoicing' ) . ' (' . wpinv_format_amount( '99.99' ) . ' ' . $currency_symbol . ')'
                    )
                ),
                'thousands_separator' => array(
                    'id'   => 'thousands_separator',
                    'name' => __( 'Thousands Separator', 'invoicing' ),
                    'desc' => __( 'The symbol (usually , or .) to separate thousands', 'invoicing' ),
                    'type' => 'text',
                    'size' => 'small',
                    'std'  => ',',
                ),
                'decimal_separator' => array(
                    'id'   => 'decimal_separator',
                    'name' => __( 'Decimal Separator', 'invoicing' ),
                    'desc' => __( 'The symbol (usually , or .) to separate decimal points', 'invoicing' ),
                    'type' => 'text',
                    'size' => 'small',
                    'std'  => '.',
                ),
                'decimals' => array(
                    'id'   => 'decimals',
                    'name' => __( 'Number of Decimals', 'invoicing' ),
                    'desc' => __( 'This sets the number of decimal points shown in displayed prices.', 'invoicing' ),
                    'type' => 'number',
                    'size' => 'small',
                    'std'  => '2',
                    'min'  => '0',
                    'max'  => '10',
                    'step' => '1'
                ),
            ),
            'labels' => array(
                'labels' => array(
                    'id'   => 'labels_settings',
                    'name' => '<h3>' . __( 'Invoice Labels', 'invoicing' ) . '</h3>',
                    'desc' => '',
                    'type' => 'header',
                ),
                'vat_name' => array(
                    'id' => 'vat_name',
                    'name' => __( 'VAT Name', 'invoicing' ),
                    'desc' => __( 'Enter the VAT name', 'invoicing' ),
                    'type' => 'text',
                    'size' => 'regular',
                    'std' => 'VAT'
                ),
                'vat_invoice_notice_label' => array(
                    'id' => 'vat_invoice_notice_label',
                    'name' => __( 'Invoice Notice Label', 'invoicing' ),
                    'desc' => __( 'Use this to add an invoice notice section (label) to your invoices', 'invoicing' ),
                    'type' => 'text',
                    'size' => 'regular',
                ),
                'vat_invoice_notice' => array(
                    'id' => 'vat_invoice_notice',
                    'name' => __( 'Invoice notice', 'invoicing' ),
                    'desc' =>   __( 'Use this to add an invoice notice section (description) to your invoices', 'invoicing' ),
                    'type' => 'text',
                    'size' => 'regular',
                ),
                'name_your_price' => array(
                    'id'   => 'name_your_price_settings',
                    'name' => '<h3>' . __( 'Name Your Price', 'invoicing' ) . '</h3>',
                    'desc' => '',
                    'type' => 'header',
                ),
                'suggested_price_text' => array(
                    'id'   => 'suggested_price_text',
                    'name' => __( 'Suggested Price Text', 'invoicing' ),
                    'desc' => __( "The label used to indicate an item's suggested price", 'invoicing' ),
                    'type' => 'text',
                    'size' => 'regular',
                    'std'  => __( 'Suggested Price:', 'invoicing' ),
                ),
                'minimum_price_text' => array(
                    'id'   => 'minimum_price_text',
                    'name' => __( 'Minimum Price Text', 'invoicing' ),
                    'desc' => __( "The label used to indicate an item's minimum price", 'invoicing' ),
                    'type' => 'text',
                    'size' => 'regular',
                    'std'  => __( 'Minimum Price:', 'invoicing' ),
                ),
                'name_your_price_text' => array(
                    'id'   => 'name_your_price_text',
                    'name' => __( 'Name Your Price Text', 'invoicing' ),
                    'type' => 'text',
                    'size' => 'regular',
                    'std'  => __( 'Name Your Price', 'invoicing' ),
                ),
            )
        )
    ),
    'gateways' => apply_filters('wpinv_settings_gateways',
        array(
            'main' => array(
                'gateway_settings' => array(
                    'id'   => 'api_header',
                    'name' => '<h3>' . __( 'Gateway Settings', 'invoicing' ) . '</h3>',
                    'desc' => '',
                    'type' => 'header',
                ),
                'gateways' => array(
                    'id'      => 'gateways',
                    'name'    => __( 'Payment Gateways', 'invoicing' ),
                    'desc'    => __( 'Choose the payment gateways you want to enable.', 'invoicing' ),
                    'type'    => 'gateways',
                    'std'     => array('manual'=>1),
                    'options' => wpinv_get_payment_gateways(),
                ),
                'default_gateway' => array(
                    'id'      => 'default_gateway',
                    'name'    => __( 'Default Gateway', 'invoicing' ),
                    'desc'    => __( 'This gateway will be loaded automatically with the checkout page.', 'invoicing' ),
                    'type'    => 'gateway_select',
                    'std'     => 'manual',
                    'class'   => 'wpi_select2',
                    'options' => wpinv_get_payment_gateways(),
                ),
            ),
        )
    ),

    'checkout' => apply_filters('wpinv_settings_checkout',
        array(
            'main' => array(
                'checkout_settings' => array(
                    'id'   => 'api_header',
                    'name' => '<h3>' . __( 'Checkout Fields', 'invoicing' ) . '</h3>',
                    'desc' => '',
                    'type' => 'header',
                ),
                'checkout_fields' => array(
                    'id'      => 'checkout_fields',
                    'type'    => 'checkout_fields',
                ),

            ),
        )
    ),

    /** Taxes Settings */
    'taxes' => apply_filters('wpinv_settings_taxes',
        array(
            'main' => array(
                'tax_settings' => array(
                    'id'   => 'tax_settings',
                    'name' => '<h3>' . __( 'Tax Settings', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'enable_taxes' => array(
                    'id'   => 'enable_taxes',
                    'name' => __( 'Enable Taxes', 'invoicing' ),
                    'desc' => __( 'Check this to enable taxes on invoices.', 'invoicing' ),
                    'type' => 'checkbox',
                ),
                'tax_rate' => array(
                    'id'   => 'tax_rate',
                    'name' => __( 'Fallback Tax Rate', 'invoicing' ),
                    'desc' => __( 'Enter a percentage, such as 6.5. Customers not in a specific rate will be charged this rate.', 'invoicing' ),
                    'type' => 'number',
                    'size' => 'small',
                    'min'  => '0',
                    'max'  => '99',
                    'step' => 'any',
                    'std'  => '20'
                ),
            ),
            'rates' => array(
                'tax_rates' => array(
                    'id'   => 'tax_rates',
                    'name' => '<h3>' . __( 'Tax Rates', 'invoicing' ) . '</h3>',
                    'desc' => __( 'Enter tax rates for specific regions.', 'invoicing' ),
                    'type' => 'tax_rates',
                ),
            )
        )
    ),
    /** Emails Settings */
    'emails' => apply_filters('wpinv_settings_emails',
        array(
            'main' => array(
                'email_settings_header' => array(
                    'id'   => 'email_settings_header',
                    'name' => '<h3>' . __( 'Email Sender Options', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'email_from_name' => array(
                    'id'   => 'email_from_name',
                    'name' => __( 'From Name', 'invoicing' ),
                    'desc' => __( 'Enter the sender\'s name appears in outgoing invoice emails. This should be your site name.', 'invoicing' ),
                    'std' => esc_attr( get_bloginfo( 'name', 'display' ) ),
                    'type' => 'text',
                ),
                'email_from' => array(
                    'id'   => 'email_from',
                    'name' => __( 'From Email', 'invoicing' ),
                    'desc' => sprintf (__( 'Email address to send invoice emails from. This will act as the "from" and "reply-to" address. %s If emails are not being sent it may be that your hosting prevents emails being sent if the email domains do not match.%s', 'invoicing' ), $alert_wrapper_start, $alert_wrapper_close),
                    'std' => get_option( 'admin_email' ),
                    'type' => 'text',
                ),
                'overdue_settings_header' => array(
                    'id'   => 'overdue_settings_header',
                    'name' => '<h3>' . __( 'Due Date Settings', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'overdue_active' => array(
                    'id'   => 'overdue_active',
                    'name' => __( 'Enable Due Date', 'invoicing' ),
                    'desc' => __( 'Check this to enable due date option for invoices.', 'invoicing' ),
                    'type' => 'checkbox',
                    'std'  => false,
                ),
                'overdue_days' => array(
                    'id'          => 'overdue_days',
                    'name'        => __( 'Default Due Date', 'invoicing' ),
                    'desc'        => __( 'Number of days each Invoice is due after the created date. This will automatically set the date in the "Due Date" field. Can be overridden on individual Invoices.', 'invoicing' ),
                    'type'        => 'select',
                    'options'     => $due_payment_options,
                    'std'         => 0,
                    'placeholder' => __( 'Select a page', 'invoicing' ),
                ),
                'email_template_header' => array(
                    'id'   => 'email_template_header',
                    'name' => '<h3>' . __( 'Email Template', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'email_header_image' => array(
                    'id'   => 'email_header_image',
                    'name' => __( 'Header Image', 'invoicing' ),
                    'desc' => __( 'URL to an image you want to show in the email header. Upload images using the media uploader (Admin > Media).', 'invoicing' ),
                    'std' => '',
                    'type' => 'text',
                ),
                'email_footer_text' => array(
                    'id'   => 'email_footer_text',
                    'name' => __( 'Footer Text', 'invoicing' ),
                    'desc' => __( 'The text to appear in the footer of all invoice emails.', 'invoicing' ),
                    'std' => get_bloginfo( 'name', 'display' ) . ' - ' . __( 'Powered by GeoDirectory', 'invoicing' ),
                    'type' => 'textarea',
                    'class' => 'regular-text',
                    'rows' => 2,
                    'cols' => 37
                ),
                'email_base_color' => array(
                    'id'   => 'email_base_color',
                    'name' => __( 'Base Color', 'invoicing' ),
                    'desc' => __( 'The base color for invoice email template. Default <code>#557da2</code>.', 'invoicing' ),
                    'std' => '#557da2',
                    'type' => 'color',
                ),
                'email_background_color' => array(
                    'id'   => 'email_background_color',
                    'name' => __( 'Background Color', 'invoicing' ),
                    'desc' => __( 'The background color of email template. Default <code>#f5f5f5</code>.', 'invoicing' ),
                    'std' => '#f5f5f5',
                    'type' => 'color',
                ),
                'email_body_background_color' => array(
                    'id'   => 'email_body_background_color',
                    'name' => __( 'Body Background Color', 'invoicing' ),
                    'desc' => __( 'The main body background color of email template. Default <code>#fdfdfd</code>.', 'invoicing' ),
                    'std' => '#fdfdfd',
                    'type' => 'color',
                ),
                'email_text_color' => array(
                    'id'   => 'email_text_color',
                    'name' => __( 'Body Text Color', 'invoicing' ),
                    'desc' => __( 'The main body text color. Default <code>#505050</code>.', 'invoicing' ),
                    'std' => '#505050',
                    'type' => 'color',
                ),
                'email_settings' => array(
                    'id'   => 'email_settings',
                    'name' => '',
                    'desc' => '',
                    'type' => 'hook',
                ),
            ),
        )
    ),
    /** Privacy Settings */
    'privacy' => apply_filters('wpinv_settings_privacy',
        array(
            'main' => array(
                'invoicing_privacy_policy_settings' => array(
                    'id'   => 'invoicing_privacy_policy_settings',
                    'name' => '<h3>' . __( 'Privacy Policy', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'privacy_page' => array(
                    'id'          => 'privacy_page',
                    'name'        => __( 'Privacy Page', 'invoicing' ),
                    'desc'        => __( 'If no privacy policy page set in Settings->Privacy default settings, this page will be used on checkout page.', 'invoicing' ),
                    'type'        => 'select',
                    'options'     => wpinv_get_pages( true,  __( 'Select a page', 'invoicing' )),
                    'class'       => 'wpi_select2',
                    'placeholder' => __( 'Select a page', 'invoicing' ),
                ),
                'invoicing_privacy_checkout_message' => array(
                    'id' => 'invoicing_privacy_checkout_message',
                    'name' => __( 'Checkout privacy policy', 'invoicing' ),
                    'desc' => __( 'Optionally add privacy policy message which will display on checkout page.', 'invoicing' ),
                    'type' => 'textarea',
                    'class'=> 'regular-text',
                    'rows' => 4,
                    'std'  => sprintf( __( 'Your personal data will be used to process your invoice, payment and for other purposes described in our %s.', 'invoicing' ), '[wpinv_privacy_policy]' ),
                ),
            ),
        )
    ),
    /** Misc Settings */
    'misc' => apply_filters('wpinv_settings_misc',
        array(
            'main' => array(
                'invoice_number_format_settings' => array(
                    'id'   => 'invoice_number_format_settings',
                    'name' => '<h3>' . __( 'Invoice Number', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'sequential_invoice_number' => array(
                    'id'   => 'sequential_invoice_number',
                    'name' => __( 'Sequential Invoice Numbers', 'invoicing' ),
                    'desc' => __('Check this box to enable sequential invoice numbers.', 'invoicing' ) . $reset_number,
                    'type' => 'checkbox',
                ),
                'invoice_sequence_start' => array(
                    'id'   => 'invoice_sequence_start',
                    'name' => __( 'Sequential Starting Number', 'invoicing' ),
                    'desc' => __( 'The number at which the invoice number sequence should begin.', 'invoicing' ) . $last_number,
                    'type' => 'number',
                    'size' => 'small',
                    'std'  => '1',
                    'class'=> 'w100'
                ),
                'invoice_number_padd' => array(
                    'id'      => 'invoice_number_padd',
                    'name'    => __( 'Minimum Digits', 'invoicing' ),
                    'desc'    => __( 'If the invoice number has less digits than this number, it is left padded with 0s. Ex: invoice number 108 will padded to 00108 if digits set to 5. The default 0 means no padding.', 'invoicing' ),
                    'type'    => 'select',
                    'options' => $invoice_number_padd_options,
                    'std'     => 5,
                    'class'   => 'wpi_select2',
                ),
                'invoice_number_prefix' => array(
                    'id' => 'invoice_number_prefix',
                    'name' => __( 'Invoice Number Prefix', 'invoicing' ),
                    'desc' => __( 'Prefix for all invoice numbers. Ex: WPINV-', 'invoicing' ),
                    'type' => 'text',
                    'size' => 'regular',
                    'std' => 'WPINV-',
                    'placeholder' => 'WPINV-',
                ),
                'invoice_number_postfix' => array(
                    'id' => 'invoice_number_postfix',
                    'name' => __( 'Invoice Number Postfix', 'invoicing' ),
                    'desc' => __( 'Postfix for all invoice numbers.', 'invoicing' ),
                    'type' => 'text',
                    'size' => 'regular',
                    'std' => ''
                ),
                'checkout_settings' => array(
                    'id'   => 'checkout_settings',
                    'name' => '<h3>' . __( 'Checkout Settings', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'login_to_checkout' => array(
                    'id'   => 'login_to_checkout',
                    'name' => __( 'Require Login To Checkout', 'invoicing' ),
                    'desc' => __( 'If ticked then user needs to be logged in to view or pay invoice, can only view or pay their own invoice. If unticked then anyone can view or pay the invoice.', 'invoicing' ),
                    'type' => 'checkbox',
                ),
                'uninstall_settings' => array(
                    'id'   => 'uninstall_settings',
                    'name' => '<h3>' . __( 'Uninstall Settings', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'remove_data_on_unistall' => array(
                    'id'   => 'remove_data_on_unistall',
                    'name' => __( 'Remove Data on Uninstall?', 'invoicing' ),
                    'desc' => __( 'Check this box if you would like Invoicing plugin to completely remove all of its data when the plugin is deleted/uninstalled.', 'invoicing' ),
                    'type' => 'checkbox',
                    'std'  => ''
                ),
            ),
            'fields' => array(
                'force_show_company' => array(
                    'id'   => 'force_show_company',
                    'name' => __( 'Force show company name at checkout.', 'invoicing' ),
                    'type' => 'checkbox',
                    'std'  => false,
                ),
                'address_autofill_settings' => array(
                    'id'   => 'address_autofill_settings',
                    'name' => '<h3>' . __( 'Google Address Auto Complete', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'address_autofill_active' => array(
                    'id'   => 'address_autofill_active',
                    'name' => __( 'Enable/Disable', 'invoicing' ),
                    'desc' => __( 'Enable google address auto complete', 'invoicing' ),
                    'type' => 'checkbox',
                    'std'  => 0
                ),
                'address_autofill_api' => array(
                    'id' => 'address_autofill_api',
                    'name' => __( 'Google Place API Key', 'invoicing' ),
                    'desc' => wp_sprintf(__( 'Enter google place API key. For more information go to google place API %sdocumenation%s', 'invoicing' ), '<a href="https://developers.google.com/maps/documentation/javascript/places-autocomplete" target="_blank">', '</a>' ),
                    'type' => 'text',
                    'size' => 'regular',
                    'std' => ''
                ),
            ),
            'custom-css' => array(
                'css_settings' => array(
                    'id'   => 'css_settings',
                    'name' => '<h3>' . __( 'Custom CSS', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),
                'template_custom_css' => array(
                    'id' => 'template_custom_css',
                    'name' => __( 'Invoice Template CSS', 'invoicing' ),
                    'desc' => __( 'Add CSS to modify appearance of the print invoice page.', 'invoicing' ),
                    'type' => 'textarea',
                    'class'=> 'regular-text',
                    'rows' => 10,
                ),
            ),
        )
    ),
    /** Tools Settings */
    'tools' => apply_filters('wpinv_settings_tools',
        array(
            'main' => array(
                'tool_settings' => array(
                    'id'   => 'tool_settings',
                    'name' => '<h3>' . __( 'Diagnostic Tools', 'invoicing' ) . '</h3>',
                    'desc' => __( 'Invoicing diagnostic tools', 'invoicing' ),
                    'type' => 'tools',
                ),
            ),
        )
    )
);
