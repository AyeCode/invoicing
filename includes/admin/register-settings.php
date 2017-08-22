<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_get_option( $key = '', $default = false ) {
    global $wpinv_options;

    $value = isset( $wpinv_options[ $key ] ) ? $wpinv_options[ $key ] : $default;
    $value = apply_filters( 'wpinv_get_option', $value, $key, $default );

    return apply_filters( 'wpinv_get_option_' . $key, $value, $key, $default );
}

function wpinv_update_option( $key = '', $value = false ) {
    // If no key, exit
    if ( empty( $key ) ) {
        return false;
    }

    if ( empty( $value ) ) {
        $remove_option = wpinv_delete_option( $key );
        return $remove_option;
    }

    // First let's grab the current settings
    $options = get_option( 'wpinv_settings' );

    // Let other plugin alter the value
    $value = apply_filters( 'wpinv_update_option', $value, $key );

    // Next let's try to update the value
    $options[ $key ] = $value;
    $did_update = update_option( 'wpinv_settings', $options );

    // If it's updated, let's update the global variable
    if ( $did_update ) {
        global $wpinv_options;
        $wpinv_options[ $key ] = $value;
    }

    return $did_update;
}

function wpinv_delete_option( $key = '' ) {
    // If no key, exit
    if ( empty( $key ) ) {
        return false;
    }

    // First let's grab the current settings
    $options = get_option( 'wpinv_settings' );

    // Next let's try to update the value
    if( isset( $options[ $key ] ) ) {
        unset( $options[ $key ] );
    }

    $did_update = update_option( 'wpinv_settings', $options );

    // If it updated, let's update the global variable
    if ( $did_update ){
        global $wpinv_options;
        $wpinv_options = $options;
    }

    return $did_update;
}

function wpinv_get_settings() {
    $settings = get_option( 'wpinv_settings' );

    if ( empty( $settings ) ) {
        // Update old settings with new single option
        $general_settings   = is_array( get_option( 'wpinv_settings_general' ) )    ? get_option( 'wpinv_settings_general' )    : array();
        $gateways_settings  = is_array( get_option( 'wpinv_settings_gateways' ) )   ? get_option( 'wpinv_settings_gateways' )   : array();
        $email_settings     = is_array( get_option( 'wpinv_settings_emails' ) )     ? get_option( 'wpinv_settings_emails' )     : array();
        $tax_settings       = is_array( get_option( 'wpinv_settings_taxes' ) )      ? get_option( 'wpinv_settings_taxes' )      : array();
        $misc_settings      = is_array( get_option( 'wpinv_settings_misc' ) )       ? get_option( 'wpinv_settings_misc' )       : array();
        $tool_settings      = is_array( get_option( 'wpinv_settings_tools' ) )      ? get_option( 'wpinv_settings_tools' )      : array();

        $settings = array_merge( $general_settings, $gateways_settings, $tax_settings, $tool_settings );

        update_option( 'wpinv_settings', $settings );

    }
    return apply_filters( 'wpinv_get_settings', $settings );
}

function wpinv_register_settings() {
    if ( false == get_option( 'wpinv_settings' ) ) {
        add_option( 'wpinv_settings' );
    }
    
    $register_settings = wpinv_get_registered_settings();
    
    foreach ( $register_settings as $tab => $sections ) {
        foreach ( $sections as $section => $settings) {
            // Check for backwards compatibility
            $section_tabs = wpinv_get_settings_tab_sections( $tab );
            if ( ! is_array( $section_tabs ) || ! array_key_exists( $section, $section_tabs ) ) {
                $section = 'main';
                $settings = $sections;
            }

            add_settings_section(
                'wpinv_settings_' . $tab . '_' . $section,
                __return_null(),
                '__return_false',
                'wpinv_settings_' . $tab . '_' . $section
            );

            foreach ( $settings as $option ) {
                // For backwards compatibility
                if ( empty( $option['id'] ) ) {
                    continue;
                }

                $name = isset( $option['name'] ) ? $option['name'] : '';

                add_settings_field(
                    'wpinv_settings[' . $option['id'] . ']',
                    $name,
                    function_exists( 'wpinv_' . $option['type'] . '_callback' ) ? 'wpinv_' . $option['type'] . '_callback' : 'wpinv_missing_callback',
                    'wpinv_settings_' . $tab . '_' . $section,
                    'wpinv_settings_' . $tab . '_' . $section,
                    array(
                        'section'     => $section,
                        'id'          => isset( $option['id'] )          ? $option['id']          : null,
                        'desc'        => ! empty( $option['desc'] )      ? $option['desc']        : '',
                        'name'        => isset( $option['name'] )        ? $option['name']        : null,
                        'size'        => isset( $option['size'] )        ? $option['size']        : null,
                        'options'     => isset( $option['options'] )     ? $option['options']     : '',
                        'selected'    => isset( $option['selected'] )    ? $option['selected']    : null,
                        'std'         => isset( $option['std'] )         ? $option['std']         : '',
                        'min'         => isset( $option['min'] )         ? $option['min']         : null,
                        'max'         => isset( $option['max'] )         ? $option['max']         : null,
                        'step'        => isset( $option['step'] )        ? $option['step']        : null,
                        'chosen'      => isset( $option['chosen'] )      ? $option['chosen']      : null,
                        'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
                        'allow_blank' => isset( $option['allow_blank'] ) ? $option['allow_blank'] : true,
                        'readonly'    => isset( $option['readonly'] )    ? $option['readonly']    : false,
                        'faux'        => isset( $option['faux'] )        ? $option['faux']        : false,
                        'onchange'    => !empty( $option['onchange'] )   ? $option['onchange']    : '',
                        'custom'      => !empty( $option['custom'] )     ? $option['custom']      : '',
                        'class'       =>  !empty( $option['class'] )     ? $option['class']      : '',
                        'cols'        => !empty( $option['cols'] ) && (int)$option['cols'] > 0 ? (int)$option['cols'] : 50,
                        'rows'        => !empty( $option['rows'] ) && (int)$option['rows'] > 0 ? (int)$option['rows'] : 5,
                    )
                );
            }
        }
    }

    // Creates our settings in the options table
    register_setting( 'wpinv_settings', 'wpinv_settings', 'wpinv_settings_sanitize' );
}
add_action( 'admin_init', 'wpinv_register_settings' );

function wpinv_get_registered_settings() {
    $pages = wpinv_get_pages( true );
    
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
    
    $alert_wrapper_start = '<p style="color: #F00">';
    $alert_wrapper_close = '</p>';
    $wpinv_settings = array(
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
                        'chosen'  => true,
                        'placeholder' => __( 'Select a country', 'invoicing' ),
                    ),
                    'default_state' => array(
                        'id'      => 'default_state',
                        'name'    => __( 'Default State / Province', 'invoicing' ),
                        'desc'    => __( 'What state / province does your store operate from?', 'invoicing' ),
                        'type'    => 'country_states',
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
                        'chosen'      => true,
                        'placeholder' => __( 'Select a page', 'invoicing' ),
                    ),
                    'success_page' => array(
                        'id'          => 'success_page',
                        'name'        => __( 'Success Page', 'invoicing' ),
                        'desc'        => __( 'This is the page buyers are sent to after completing their payments. The <b>[wpinv_receipt]</b> short code should be on this page.', 'invoicing' ),
                        'type'        => 'select',
                        'options'     => $pages,
                        'chosen'      => true,
                        'placeholder' => __( 'Select a page', 'invoicing' ),
                    ),
                    'failure_page' => array(
                        'id'          => 'failure_page',
                        'name'        => __( 'Failed Transaction Page', 'invoicing' ),
                        'desc'        => __( 'This is the page buyers are sent to if their transaction is cancelled or fails', 'invoicing' ),
                        'type'        => 'select',
                        'options'     => $pages,
                        'chosen'      => true,
                        'placeholder' => __( 'Select a page', 'invoicing' ),
                    ),
                    'invoice_history_page' => array(
                        'id'          => 'invoice_history_page',
                        'name'        => __( 'Invoice History Page', 'invoicing' ),
                        'desc'        => __( 'This page shows an invoice history for the current user', 'invoicing' ),
                        'type'        => 'select',
                        'options'     => $pages,
                        'chosen'      => true,
                        'placeholder' => __( 'Select a page', 'invoicing' ),
                    )
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
                        'options' => wpinv_get_currencies(),
                        'chosen'  => true,
                    ),
                    'currency_position' => array(
                        'id'      => 'currency_position',
                        'name'    => __( 'Currency Position', 'invoicing' ),
                        'desc'    => __( 'Choose the location of the currency sign.', 'invoicing' ),
                        'type'    => 'select',
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
                    )
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
                        'options' => wpinv_get_payment_gateways(),
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
                        'chosen'      => true,
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
        /** Misc Settings */
        'misc' => apply_filters('wpinv_settings_misc',
            array(
                'main' => array(
                    'fields_settings' => array(
                        'id'   => 'fields_settings',
                        'name' => '<h3>' . __( 'Fields Settings', 'invoicing' ) . '</h3>',
                        'desc' => __( 'Tick fields which are mandatory in invoice address fields.', 'invoicing' ),
                        'type' => 'header',
                    ),
                    'fname_mandatory' => array(
                        'id'   => 'fname_mandatory',
                        'name' => __( 'First Name', 'invoicing' ),
                        'type' => 'checkbox',
                        'std'  => true,
                    ),
                    'lname_mandatory' => array(
                        'id'   => 'lname_mandatory',
                        'name' => __( 'Last Name', 'invoicing' ),
                        'type' => 'checkbox',
                        'std'  => true,
                    ),
                    'address_mandatory' => array(
                        'id'   => 'address_mandatory',
                        'name' => __( 'Address', 'invoicing' ),
                        'type' => 'checkbox',
                        'std'  => true,
                    ),
                    'city_mandatory' => array(
                        'id'   => 'city_mandatory',
                        'name' => __( 'City', 'invoicing' ),
                        'type' => 'checkbox',
                        'std'  => true,
                    ),
                    'country_mandatory' => array(
                        'id'   => 'country_mandatory',
                        'name' => __( 'Country', 'invoicing' ),
                        'type' => 'checkbox',
                        'std'  => true,
                    ),
                    'state_mandatory' => array(
                        'id'   => 'state_mandatory',
                        'name' => __( 'State / Province', 'invoicing' ),
                        'type' => 'checkbox',
                        'std'  => true,
                    ),
                    'zip_mandatory' => array(
                        'id'   => 'zip_mandatory',
                        'name' => __( 'ZIP / Postcode', 'invoicing' ),
                        'type' => 'checkbox',
                        'std'  => true,
                    ),
                    'phone_mandatory' => array(
                        'id'   => 'phone_mandatory',
                        'name' => __( 'Phone Number', 'invoicing' ),
                        'type' => 'checkbox',
                        'std'  => true,
                    ),
                    'invoice_number_format_settings' => array(
                        'id'   => 'invoice_number_format_settings',
                        'name' => '<h3>' . __( 'Invoice Number', 'invoicing' ) . '</h3>',
                        'type' => 'header',
                    ),
                    'sequential_invoice_number' => array(
                        'id'   => 'sequential_invoice_number',
                        'name' => __( 'Sequential Invoice Numbers', 'invoicing' ),
                        'desc' => __( 'Check this box to enable sequential invoice numbers.', 'invoicing' ),
                        'type' => 'checkbox',
                    ),
                    'invoice_sequence_start' => array(
                        'id'   => 'invoice_sequence_start',
                        'name' => __( 'Sequential Starting Number', 'easy-digital-downloads' ),
                        'desc' => __( 'The number at which the invoice number sequence should begin.', 'invoicing' ),
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
                        'chosen'  => true,
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
            )
        ),
        /** Misc Settings */
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

    return apply_filters( 'wpinv_registered_settings', $wpinv_settings );
}

function wpinv_settings_sanitize( $input = array() ) {
    global $wpinv_options;

    if ( empty( $_POST['_wp_http_referer'] ) ) {
        return $input;
    }

    parse_str( $_POST['_wp_http_referer'], $referrer );

    $settings = wpinv_get_registered_settings();
    $tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';
    $section  = isset( $referrer['section'] ) ? $referrer['section'] : 'main';

    $input = $input ? $input : array();
    $input = apply_filters( 'wpinv_settings_tab_' . $tab . '_sanitize', $input );
    $input = apply_filters( 'wpinv_settings_' . $tab . '-' . $section . '_sanitize', $input );

    // Loop through each setting being saved and pass it through a sanitization filter
    foreach ( $input as $key => $value ) {
        // Get the setting type (checkbox, select, etc)
        $type = isset( $settings[ $tab ][ $key ]['type'] ) ? $settings[ $tab ][ $key ]['type'] : false;

        if ( $type ) {
            // Field type specific filter
            $input[$key] = apply_filters( 'wpinv_settings_sanitize_' . $type, $value, $key );
        }

        // General filter
        $input[ $key ] = apply_filters( 'wpinv_settings_sanitize', $input[ $key ], $key );
    }

    // Loop through the whitelist and unset any that are empty for the tab being saved
    $main_settings    = $section == 'main' ? $settings[ $tab ] : array(); // Check for extensions that aren't using new sections
    $section_settings = ! empty( $settings[ $tab ][ $section ] ) ? $settings[ $tab ][ $section ] : array();

    $found_settings = array_merge( $main_settings, $section_settings );

    if ( ! empty( $found_settings ) ) {
        foreach ( $found_settings as $key => $value ) {

            // settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
            if ( is_numeric( $key ) ) {
                $key = $value['id'];
            }

            if ( empty( $input[ $key ] ) ) {
                unset( $wpinv_options[ $key ] );
            }
        }
    }

    // Merge our new settings with the existing
    $output = array_merge( $wpinv_options, $input );

    add_settings_error( 'wpinv-notices', '', __( 'Settings updated.', 'invoicing' ), 'updated' );

    return $output;
}

function wpinv_settings_sanitize_misc_accounting( $input ) {
    global $wpinv_options, $wpi_session;

    if ( !current_user_can( 'manage_options' ) ) {
        return $input;
    }

    if( ! empty( $input['enable_sequential'] ) && !wpinv_get_option( 'enable_sequential' ) ) {
        // Shows an admin notice about upgrading previous order numbers
        $wpi_session->set( 'upgrade_sequential', '1' );
    }

    return $input;
}
add_filter( 'wpinv_settings_misc-accounting_sanitize', 'wpinv_settings_sanitize_misc_accounting' );

function wpinv_settings_sanitize_tax_rates( $input ) {
    if( !current_user_can( 'manage_options' ) ) {
        return $input;
    }

    $new_rates = !empty( $_POST['tax_rates'] ) ? array_values( $_POST['tax_rates'] ) : array();

    $tax_rates = array();

    if ( !empty( $new_rates ) ) {
        foreach ( $new_rates as $rate ) {
            if ( isset( $rate['country'] ) && empty( $rate['country'] ) && empty( $rate['state'] ) ) {
                continue;
            }
            
            $rate['rate'] = wpinv_sanitize_amount( $rate['rate'], 4 );
            
            $tax_rates[] = $rate;
        }
    }

    update_option( 'wpinv_tax_rates', $tax_rates );

    return $input;
}
add_filter( 'wpinv_settings_taxes-rates_sanitize', 'wpinv_settings_sanitize_tax_rates' );

function wpinv_sanitize_text_field( $input ) {
    return trim( $input );
}
add_filter( 'wpinv_settings_sanitize_text', 'wpinv_sanitize_text_field' );

function wpinv_get_settings_tabs() {
    $tabs             = array();
    $tabs['general']  = __( 'General', 'invoicing' );
    $tabs['gateways'] = __( 'Payment Gateways', 'invoicing' );
    $tabs['taxes']    = __( 'Taxes', 'invoicing' );
    $tabs['emails']   = __( 'Emails', 'invoicing' );
    $tabs['misc']     = __( 'Misc', 'invoicing' );
    $tabs['tools']    = __( 'Tools', 'invoicing' );

    return apply_filters( 'wpinv_settings_tabs', $tabs );
}

function wpinv_get_settings_tab_sections( $tab = false ) {
    $tabs     = false;
    $sections = wpinv_get_registered_settings_sections();

    if( $tab && ! empty( $sections[ $tab ] ) ) {
        $tabs = $sections[ $tab ];
    } else if ( $tab ) {
        $tabs = false;
    }

    return $tabs;
}

function wpinv_get_registered_settings_sections() {
    static $sections = false;

    if ( false !== $sections ) {
        return $sections;
    }

    $sections = array(
        'general' => apply_filters( 'wpinv_settings_sections_general', array(
            'main' => __( 'General Settings', 'invoicing' ),
            'currency_section' => __( 'Currency Settings', 'invoicing' ),
            'labels' => __( 'Label Texts', 'invoicing' ),
        ) ),
        'gateways' => apply_filters( 'wpinv_settings_sections_gateways', array(
            'main' => __( 'Gateway Settings', 'invoicing' ),
        ) ),
        'taxes' => apply_filters( 'wpinv_settings_sections_taxes', array(
            'main' => __( 'Tax Settings', 'invoicing' ),
            'rates' => __( 'Tax Rates', 'invoicing' ),
        ) ),
        'emails' => apply_filters( 'wpinv_settings_sections_emails', array(
            'main' => __( 'Email Settings', 'invoicing' ),
        ) ),
        'misc' => apply_filters( 'wpinv_settings_sections_misc', array(
            'main' => __( 'Misc Settings', 'invoicing' ),
        ) ),
        'tools' => apply_filters( 'wpinv_settings_sections_tools', array(
            'main' => __( 'Diagnostic Tools', 'invoicing' ),
        ) ),
    );

    $sections = apply_filters( 'wpinv_settings_sections', $sections );

    return $sections;
}

function wpinv_get_pages( $with_slug = false, $default_label = NULL ) {
	$pages_options = array();

	if( $default_label !== NULL && $default_label !== false ) {
		$pages_options = array( '' => $default_label ); // Blank option
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$title = $with_slug ? $page->post_title . ' (' . $page->post_name . ')' : $page->post_title;
            $pages_options[ $page->ID ] = $title;
		}
	}

	return $pages_options;
}

function wpinv_header_callback( $args ) {
	if ( !empty( $args['desc'] ) ) {
        echo $args['desc'];
    }
}

function wpinv_hidden_callback( $args ) {
	global $wpinv_options;

	if ( isset( $args['set_value'] ) ) {
		$value = $args['set_value'];
	} elseif ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value = isset( $args['std'] ) ? $args['std'] : '';
		$name  = '';
	} else {
		$name = 'name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$html = '<input type="hidden" id="wpinv_settings[' . wpinv_sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '" />';
    
	echo $html;
}

function wpinv_checkbox_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$name = '';
	} else {
		$name = 'name="wpinv_settings[' . $sanitize_id . ']"';
	}

	$checked = isset( $wpinv_options[ $args['id'] ] ) ? checked( 1, $wpinv_options[ $args['id'] ], false ) : '';
	$html = '<input type="checkbox" id="wpinv_settings[' . $sanitize_id . ']"' . $name . ' value="1" ' . $checked . '/>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_multicheck_callback( $args ) {
	global $wpinv_options;
	
	$sanitize_id = wpinv_sanitize_key( $args['id'] );
	
	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ):
			$sanitize_key = wpinv_sanitize_key( $key );
			if ( isset( $wpinv_options[$args['id']][$sanitize_key] ) ) { 
				$enabled = $sanitize_key;
			} else { 
				$enabled = NULL; 
			}
			echo '<input name="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" id="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" type="checkbox" value="' . esc_attr( $sanitize_key ) . '" ' . checked( $sanitize_key, $enabled, false ) . '/>&nbsp;';
			echo '<label for="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']">' . wp_kses_post( $option ) . '</label><br/>';
		endforeach;
		echo '<p class="description">' . $args['desc'] . '</p>';
	}
}

function wpinv_payment_icons_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ) {
            $sanitize_key = wpinv_sanitize_key( $key );
            
			if( isset( $wpinv_options[$args['id']][$key] ) ) {
				$enabled = $option;
			} else {
				$enabled = NULL;
			}

			echo '<label for="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" style="margin-right:10px;line-height:16px;height:16px;display:inline-block;">';

				echo '<input name="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" id="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" type="checkbox" value="' . esc_attr( $option ) . '" ' . checked( $option, $enabled, false ) . '/>&nbsp;';

				if ( wpinv_string_is_image_url( $key ) ) {
					echo '<img class="payment-icon" src="' . esc_url( $key ) . '" style="width:32px;height:24px;position:relative;top:6px;margin-right:5px;"/>';
				} else {
					$card = strtolower( str_replace( ' ', '', $option ) );

					if ( has_filter( 'wpinv_accepted_payment_' . $card . '_image' ) ) {
						$image = apply_filters( 'wpinv_accepted_payment_' . $card . '_image', '' );
					} else {
						$image       = wpinv_locate_template( 'images' . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . $card . '.gif', false );
						$content_dir = WP_CONTENT_DIR;

						if ( function_exists( 'wp_normalize_path' ) ) {
							// Replaces backslashes with forward slashes for Windows systems
							$image = wp_normalize_path( $image );
							$content_dir = wp_normalize_path( $content_dir );
						}

						$image = str_replace( $content_dir, content_url(), $image );
					}

					echo '<img class="payment-icon" src="' . esc_url( $image ) . '" style="width:32px;height:24px;position:relative;top:6px;margin-right:5px;"/>';
				}
			echo $option . '</label>';
		}
		echo '<p class="description" style="margin-top:16px;">' . wp_kses_post( $args['desc'] ) . '</p>';
	}
}

function wpinv_radio_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );
    
    foreach ( $args['options'] as $key => $option ) :
		$sanitize_key = wpinv_sanitize_key( $key );
        
        $checked = false;

		if ( isset( $wpinv_options[ $args['id'] ] ) && $wpinv_options[ $args['id'] ] == $key )
			$checked = true;
		elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $wpinv_options[ $args['id'] ] ) )
			$checked = true;

		echo '<input name="wpinv_settings[' . $sanitize_id . ']" id="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" type="radio" value="' . $sanitize_key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
		echo '<label for="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']">' . esc_html( $option ) . '</label><br/>';
	endforeach;

	echo '<p class="description">' . wp_kses_post( $args['desc'] ) . '</p>';
}

function wpinv_gateways_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	foreach ( $args['options'] as $key => $option ) :
		$sanitize_key = wpinv_sanitize_key( $key );
        
        if ( isset( $wpinv_options['gateways'][ $key ] ) )
			$enabled = '1';
		else
			$enabled = null;

		echo '<input name="wpinv_settings[' . esc_attr( $args['id'] ) . '][' . $sanitize_key . ']" id="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']" type="checkbox" value="1" ' . checked('1', $enabled, false) . '/>&nbsp;';
		echo '<label for="wpinv_settings[' . $sanitize_id . '][' . $sanitize_key . ']">' . esc_html( $option['admin_label'] ) . '</label><br/>';
	endforeach;
}

function wpinv_gateway_select_callback($args) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	echo '<select name="wpinv_settings[' . $sanitize_id . ']"" id="wpinv_settings[' . $sanitize_id . ']">';

	foreach ( $args['options'] as $key => $option ) :
		if ( isset( $args['selected'] ) && $args['selected'] !== null && $args['selected'] !== false ) {
            $selected = selected( $key, $args['selected'], false );
        } else {
            $selected = isset( $wpinv_options[ $args['id'] ] ) ? selected( $key, $wpinv_options[$args['id']], false ) : '';
        }
		echo '<option value="' . wpinv_sanitize_key( $key ) . '"' . $selected . '>' . esc_html( $option['admin_label'] ) . '</option>';
	endforeach;

	echo '</select>';
	echo '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';
}

function wpinv_text_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value = isset( $args['std'] ) ? $args['std'] : '';
		$name  = '';
	} else {
		$name = 'name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"';
	}
	$class = !empty( $args['class'] ) ? sanitize_html_class( $args['class'] ) : '';

	$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
	$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html     = '<input type="text" class="' . sanitize_html_class( $size ) . '-text ' . $class . '" id="wpinv_settings[' . $sanitize_id . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $readonly . '/>';
	$html    .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_number_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value = isset( $args['std'] ) ? $args['std'] : '';
		$name  = '';
	} else {
		$name = 'name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;
	$class = !empty( $args['class'] ) ? sanitize_html_class( $args['class'] ) : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . sanitize_html_class( $size ) . '-text ' . $class . '" id="wpinv_settings[' . $sanitize_id . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_textarea_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
    
    $size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
    $class = ( isset( $args['class'] ) && ! is_null( $args['class'] ) ) ? $args['class'] : 'large-text';

	$html = '<textarea class="' . sanitize_html_class( $class ) . ' txtarea-' . sanitize_html_class( $size ) . ' wpi-' . esc_attr( sanitize_html_class( $sanitize_id ) ) . ' " cols="' . $args['cols'] . '" rows="' . $args['rows'] . '" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_password_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . sanitize_html_class( $size ) . '-text" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_missing_callback($args) {
	printf(
		__( 'The callback function used for the %s setting is missing.', 'invoicing' ),
		'<strong>' . $args['id'] . '</strong>'
	);
}

function wpinv_select_callback($args) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}
    
    if ( isset( $args['selected'] ) && $args['selected'] !== null && $args['selected'] !== false ) {
        $value = $args['selected'];
    }

	if ( isset( $args['placeholder'] ) ) {
		$placeholder = $args['placeholder'];
	} else {
		$placeholder = '';
	}

	if ( isset( $args['chosen'] ) ) {
		$chosen = 'class="wpinv-chosen"';
	} else {
		$chosen = '';
	}
    
    if( !empty( $args['onchange'] ) ) {
        $onchange = ' onchange="' . esc_attr( $args['onchange'] ) . '"';
    } else {
        $onchange = '';
    }

	$html = '<select id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" ' . $chosen . 'data-placeholder="' . esc_html( $placeholder ) . '"' . $onchange . ' />';

	foreach ( $args['options'] as $option => $name ) {
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_color_select_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = '<select id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"/>';

	foreach ( $args['options'] as $option => $color ) {
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $color['label'] ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_rich_editor_callback( $args ) {
	global $wpinv_options, $wp_version;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];

		if( empty( $args['allow_blank'] ) && empty( $value ) ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$rows = isset( $args['size'] ) ? $args['size'] : 20;

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		ob_start();
		wp_editor( stripslashes( $value ), 'wpinv_settings_' . esc_attr( $args['id'] ), array( 'textarea_name' => 'wpinv_settings[' . esc_attr( $args['id'] ) . ']', 'textarea_rows' => absint( $rows ) ) );
		$html = ob_get_clean();
	} else {
		$html = '<textarea class="large-text" rows="10" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" class="wpi-' . esc_attr( sanitize_html_class( $args['id'] ) ) . '">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_upload_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[$args['id']];
	} else {
		$value = isset($args['std']) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . sanitize_html_class( $size ) . '-text" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="wpinv_settings_upload_button button-secondary" value="' . __( 'Upload File', 'invoicing' ) . '"/></span>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_color_callback( $args ) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $wpinv_options[ $args['id'] ] ) ) {
		$value = $wpinv_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<input type="text" class="wpinv-color-picker" id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_country_states_callback($args) {
	global $wpinv_options;
    
    $sanitize_id = wpinv_sanitize_key( $args['id'] );

	if ( isset( $args['placeholder'] ) ) {
		$placeholder = $args['placeholder'];
	} else {
		$placeholder = '';
	}

	$states = wpinv_get_country_states();

	$chosen = ( $args['chosen'] ? ' wpinv-chosen' : '' );
	$class = empty( $states ) ? ' class="wpinv-no-states' . $chosen . '"' : 'class="' . $chosen . '"';
	$html = '<select id="wpinv_settings[' . $sanitize_id . ']" name="wpinv_settings[' . esc_attr( $args['id'] ) . ']"' . $class . 'data-placeholder="' . esc_html( $placeholder ) . '"/>';

	foreach ( $states as $option => $name ) {
		$selected = isset( $wpinv_options[ $args['id'] ] ) ? selected( $option, $wpinv_options[$args['id']], false ) : '';
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="wpinv_settings[' . $sanitize_id . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo $html;
}

function wpinv_tax_rates_callback($args) {
	global $wpinv_options;
	$rates = wpinv_get_tax_rates();
	ob_start(); ?>
    </td><tr>
    <td colspan="2" class="wpinv_tax_tdbox">
	<p><?php echo $args['desc']; ?></p>
	<table id="wpinv_tax_rates" class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th scope="col" class="wpinv_tax_country"><?php _e( 'Country', 'invoicing' ); ?></th>
				<th scope="col" class="wpinv_tax_state"><?php _e( 'State / Province', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv_tax_global" title="<?php esc_attr_e( 'Apply rate to whole country, regardless of state / province', 'invoicing' ); ?>"><?php _e( 'Country Wide', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv_tax_rate"><?php _e( 'Rate %', 'invoicing' ); ?></th> 
                <th scope="col" class="wpinv_tax_name"><?php _e( 'Tax Name', 'invoicing' ); ?></th>
				<th scope="col" class="wpinv_tax_action"><?php _e( 'Remove', 'invoicing' ); ?></th>
			</tr>
		</thead>
        <tbody>
		<?php if( !empty( $rates ) ) : ?>
			<?php foreach( $rates as $key => $rate ) : ?>
            <?php 
            $sanitized_key = wpinv_sanitize_key( $key );
            ?>
			<tr>
				<td class="wpinv_tax_country">
					<?php
					echo wpinv_html_select( array(
						'options'          => wpinv_get_country_list( true ),
						'name'             => 'tax_rates[' . $sanitized_key . '][country]',
                        'id'               => 'tax_rates[' . $sanitized_key . '][country]',
						'selected'         => $rate['country'],
						'show_option_all'  => false,
						'show_option_none' => false,
						'class'            => 'wpinv-tax-country',
						'chosen'           => false,
						'placeholder'      => __( 'Choose a country', 'invoicing' )
					) );
					?>
				</td>
				<td class="wpinv_tax_state">
					<?php
					$states = wpinv_get_country_states( $rate['country'] );
					if( !empty( $states ) ) {
						echo wpinv_html_select( array(
							'options'          => array_merge( array( '' => '' ), $states ),
							'name'             => 'tax_rates[' . $sanitized_key . '][state]',
                            'id'               => 'tax_rates[' . $sanitized_key . '][state]',
							'selected'         => $rate['state'],
							'show_option_all'  => false,
							'show_option_none' => false,
							'chosen'           => false,
							'placeholder'      => __( 'Choose a state', 'invoicing' )
						) );
					} else {
						echo wpinv_html_text( array(
							'name'  => 'tax_rates[' . $sanitized_key . '][state]', $rate['state'],
							'value' => ! empty( $rate['state'] ) ? $rate['state'] : '',
                            'id'    => 'tax_rates[' . $sanitized_key . '][state]',
						) );
					}
					?>
				</td>
				<td class="wpinv_tax_global">
					<input type="checkbox" name="tax_rates[<?php echo $sanitized_key; ?>][global]" id="tax_rates[<?php echo $sanitized_key; ?>][global]" value="1"<?php checked( true, ! empty( $rate['global'] ) ); ?>/>
					<label for="tax_rates[<?php echo $sanitized_key; ?>][global]"><?php _e( 'Apply to whole country', 'invoicing' ); ?></label>
				</td>
				<td class="wpinv_tax_rate"><input type="number" class="small-text" step="any" min="0" max="99" name="tax_rates[<?php echo $sanitized_key; ?>][rate]" value="<?php echo esc_html( $rate['rate'] ); ?>"/></td>
                <td class="wpinv_tax_name"><input type="text" class="regular-text" name="tax_rates[<?php echo $sanitized_key; ?>][name]" value="<?php echo esc_html( $rate['name'] ); ?>"/></td>
				<td class="wpinv_tax_action"><span class="wpinv_remove_tax_rate button-secondary"><?php _e( 'Remove Rate', 'invoicing' ); ?></span></td>
			</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td class="wpinv_tax_country">
					<?php
					echo wpinv_html_select( array(
						'options'          => wpinv_get_country_list( true ),
						'name'             => 'tax_rates[0][country]',
						'show_option_all'  => false,
						'show_option_none' => false,
						'class'            => 'wpinv-tax-country',
						'chosen'           => false,
						'placeholder'      => __( 'Choose a country', 'invoicing' )
					) ); ?>
				</td>
				<td class="wpinv_tax_state">
					<?php echo wpinv_html_text( array(
						'name' => 'tax_rates[0][state]'
					) ); ?>
				</td>
				<td class="wpinv_tax_global">
					<input type="checkbox" name="tax_rates[0][global]" id="tax_rates[0][global]" value="1"/>
					<label for="tax_rates[0][global]"><?php _e( 'Apply to whole country', 'invoicing' ); ?></label>
				</td>
				<td class="wpinv_tax_rate"><input type="number" class="small-text" step="any" min="0" max="99" name="tax_rates[0][rate]" placeholder="<?php echo (float)wpinv_get_option( 'tax_rate', 0 ) ;?>" value="<?php echo (float)wpinv_get_option( 'tax_rate', 0 ) ;?>"/></td>
                <td class="wpinv_tax_name"><input type="text" class="regular-text" name="tax_rates[0][name]" /></td>
				<td><span class="wpinv_remove_tax_rate button-secondary"><?php _e( 'Remove Rate', 'invoicing' ); ?></span></td>
			</tr>
		<?php endif; ?>
        </tbody>
        <tfoot><tr><td colspan="5"></td><td class="wpinv_tax_action"><span class="button-secondary" id="wpinv_add_tax_rate"><?php _e( 'Add Tax Rate', 'invoicing' ); ?></span></td></tr></tfoot>
	</table>
	<?php
	echo ob_get_clean();
}

function wpinv_tools_callback($args) {
    global $wpinv_options;
    ob_start(); ?>
    </td><tr>
    <td colspan="2" class="wpinv_tools_tdbox">
    <?php if ( $args['desc'] ) { ?><p><?php echo $args['desc']; ?></p><?php } ?>
    <?php do_action( 'wpinv_tools_before' ); ?>
    <table id="wpinv_tools_table" class="wp-list-table widefat fixed posts">
        <thead>
            <tr>
                <th scope="col" class="wpinv-th-tool"><?php _e( 'Tool', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv-th-desc"><?php _e( 'Description', 'invoicing' ); ?></th>
                <th scope="col" class="wpinv-th-action"><?php _e( 'Action', 'invoicing' ); ?></th>
            </tr>
        </thead>
            <?php do_action( 'wpinv_tools_row' ); ?>
        <tbody>
        </tbody>
    </table>
    <?php do_action( 'wpinv_tools_after' ); ?>
    <?php
    echo ob_get_clean();
}

function wpinv_descriptive_text_callback( $args ) {
	echo wp_kses_post( $args['desc'] );
}

function wpinv_hook_callback( $args ) {
	do_action( 'wpinv_' . $args['id'], $args );
}

function wpinv_set_settings_cap() {
	return 'manage_options';
}
add_filter( 'option_page_capability_wpinv_settings', 'wpinv_set_settings_cap' );

function wpinv_settings_sanitize_input( $value, $key ) {
    if ( $key == 'tax_rate' || $key == 'eu_fallback_rate' ) {
        $value = wpinv_sanitize_amount( $value, 4 );
        $value = $value >= 100 ? 99 : $value;
    }
        
    return $value;
}
add_filter( 'wpinv_settings_sanitize', 'wpinv_settings_sanitize_input', 10, 2 );