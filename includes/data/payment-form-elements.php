<?php

/**
 * Payment Form elements / blocks
 *
 * Returns an array blocks that can be added to a payment form.
 *
 * @package Invoicing/data
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

return array(

    array(
        'type'     => 'heading',
        'name'     => __( 'Heading', 'invoicing' ),
        'defaults' => array(
            'level' => 'h2',
            'text'  => __( 'Heading', 'invoicing' ),
        )
    ),

    array(
        'type' => 'paragraph',
        'name' => __( 'Paragraph', 'invoicing' ),
        'defaults'  => array(
            'text'  => __( 'Paragraph text', 'invoicing' ),
        )
    ),

    array( 
        'type' => 'alert',
        'name' => __( 'Alert', 'invoicing' ),
        'defaults'  => array(
            'value'        => '',
            'class'        => 'alert-warning',
            'text'         => __( 'Alert', 'invoicing' ),
            'dismissible'  => false,
        )
    ),

    array( 
        'type' => 'separator',
        'name' => __( 'Separator', 'invoicing' ),
        'defaults'  => array(
            'value'        => '',
        ),
    ),

    array(
        'type' => 'text',
        'name' => __( 'Text Input', 'invoicing' ),
        'defaults'  => array(
            'placeholder'  => __( 'Enter some text', 'invoicing' ),
            'value'        => '',
            'label'        => __( 'Field Label', 'invoicing' ),
            'description'  => '',
            'required'     => false,
        )
    ),

    array(
        'type' => 'textarea',
        'name' => __( 'Textarea', 'invoicing' ),
        'defaults'         => array(
            'placeholder'  => __( 'Enter your text here', 'invoicing' ),
            'value'        => '',
            'label'        => __( 'Textarea Label', 'invoicing' ),
            'description'  => '',
            'required'     => false,
        )
    ),

    array(
        'type' => 'select',
        'name' => __( 'Dropdown', 'invoicing' ),
        'defaults'         => array(
            'placeholder'  => __( 'Select a value', 'invoicing' ),
            'value'        => '',
            'label'        => __( 'Dropdown Label', 'invoicing' ),
            'description'  => '',
            'required'     => false,
            'options'      => array(
                esc_attr__( 'Option One', 'invoicing' ),
                esc_attr__( 'Option Two', 'invoicing' ),
                esc_attr__( 'Option Three', 'invoicing' )
            ),
        )
    ),

    array(
        'type' => 'checkbox',
        'name' => __( 'Checkbox', 'invoicing' ),
        'defaults'         => array(
            'value'        => '',
            'label'        => __( 'Checkbox Label', 'invoicing' ),
            'description'  => '',
            'required'     => false,
        )
    ),

    array( 
        'type' => 'radio',
        'name' => __( 'Radio', 'invoicing' ),
        'defaults'     => array(
            'label'    => __( 'Select one choice', 'invoicing' ),
            'options'  => array(
                esc_attr__( 'Choice One', 'invoicing' ),
                esc_attr__( 'Choice Two', 'invoicing' ),
                esc_attr__( 'Choice Three', 'invoicing' )
            ),
        )
    ),

    array( 
        'type' => 'date',
        'name' => __( 'Date', 'invoicing' ),
        'defaults' => array(
            'value'        => '',
            'label'        => __( 'Date', 'invoicing' ),
            'description'  => '',
            'single'       => 'single',
            'required'     => false,
        )
    ),

    array( 
        'type' => 'time',
        'name' => __( 'Time', 'invoicing' ),
        'defaults' => array(
            'value'        => '',
            'label'        => __( 'Time', 'invoicing' ),
            'description'  => '',
            'required'     => false,
        )
    ),

    array( 
        'type' => 'number',
        'name' => __( 'Number', 'invoicing' ),
        'defaults' => array(
            'placeholder'  => '',
            'value'        => '',
            'label'        => __( 'Number', 'invoicing' ),
            'description'  => '',
            'required'     => false,
        )
    ),

    array( 
        'type' => 'website',
        'name' => __( 'Website', 'invoicing' ),
        'defaults' => array(
            'placeholder'  => 'http://example.com',
            'value'        => '',
            'label'        => __( 'Website', 'invoicing' ),
            'description'  => '',
            'required'     => false,
        )
    ),

    array( 
        'type' => 'email',
        'name' => __( 'Email', 'invoicing' ),
        'defaults'  => array(
            'placeholder'  => 'jon@snow.com',
            'value'        => '',
            'label'        => __( 'Email Address', 'invoicing' ),
            'description'  => '',
            'required'     => false,
        )
    ),

    array(
        'type' => 'file_upload',
        'name' => __( 'File Upload', 'invoicing' ),
        'defaults'  => array(
            'value'         => '',
            'label'         => __( 'Upload File', 'invoicing' ),
            'description'   => '',
            'required'      => false,
            'max_file_num'  => 1,
            'file_types'    => array( 'jpg|jpeg|jpe', 'gif', 'png' ),
        )
    ),

    array( 
        'type' => 'address',
        'name' => __( 'Address', 'invoicing' ),
        'defaults'  => array(

            'address_type'            => 'billing',
            'billing_address_title'   => __( 'Billing Address', 'invoicing' ),
            'shipping_address_title'  => __( 'Shipping Address', 'invoicing' ),
            'shipping_address_toggle' => __( 'Same billing & shipping address.', 'invoicing' ),
            'fields'                  => array(
                array(
                    'placeholder'  => 'Jon',
                    'value'        => '',
                    'label'        => __( 'First Name', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => true,
                    'name'         => 'wpinv_first_name',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => 'Snow',
                    'value'        => '',
                    'label'        => __( 'Last Name', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => true,
                    'name'         => 'wpinv_last_name',
                    'grid_width'   => 'full',
                ),
            
                array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'Address', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => true,
                    'name'         => 'wpinv_address',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'City', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => true,
                    'name'         => 'wpinv_city',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => __( 'Select your country' ),
                    'value'        => '',
                    'label'        => __( 'Country', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => true,
                    'name'         => 'wpinv_country',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => __( 'Choose a state', 'invoicing' ),
                    'value'        => '',
                    'label'        => __( 'State / Province', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => true,
                    'name'         => 'wpinv_state',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'ZIP / Postcode', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => true,
                    'name'         => 'wpinv_zip',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'Phone', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => true,
                    'name'         => 'wpinv_phone',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'Company', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => false,
                    'name'         => 'wpinv_company',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'Company ID', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => false,
                    'name'         => 'wpinv_company_id',
                    'grid_width'   => 'full',
                ),

                array(
                    'placeholder'  => '',
                    'value'        => '',
                    'label'        => __( 'VAT Number', 'invoicing' ),
                    'description'  => '',
                    'required'     => false,
                    'visible'      => false,
                    'name'         => 'wpinv_vat_number',
                    'grid_width'   => 'full',
                )
            )
        )
    ),

    array( 
        'type' => 'billing_email',
        'name' => __( 'Billing Email', 'invoicing' ),
        'defaults'  => array(
            'placeholder'  => 'jon@snow.com',
            'value'        => '',
            'label'        => __( 'Billing Email', 'invoicing' ),
            'description'  => '',
            'premade'      => true,
        )
    ),

    array( 
        'type' => 'discount',
        'name' => __( 'Discount Input', 'invoicing' ),
        'defaults'  => array(
            'value'        => '',
            'input_label'  => __( 'Coupon Code', 'invoicing' ),
            'button_label' => __( 'Apply Coupon', 'invoicing' ),
            'description'  => __( 'Have a discount code? Enter it above.', 'invoicing' ),
        )
    ),

    array( 
        'type' => 'items',
        'name' => __( 'Items', 'invoicing' ),
        'defaults'  => array(
            'value'        => '',
            'items_type'   => 'total',
            'description'  => '',
            'premade'      => true,
            'hide_cart'    => false,
        )
    ),

    array( 
        'type' => 'price_input',
        'name' => __( 'Price Input', 'invoicing' ),
        'defaults'  => array(
            'placeholder'  => wpinv_format_amount(0),
            'value'        => wpinv_format_amount(0),
            'minimum'      => wpinv_format_amount(0),
            'label'        => __( 'Enter Amount', 'invoicing' ),
            'description'  => '',
        )
    ),

    array( 
        'type' => 'price_select',
        'name' => __( 'Price Select', 'invoicing' ),
        'defaults'  => array(
            'description'  => '',
            'label'        => __( 'Select Amount', 'invoicing' ),
            'options'      => 'Option 1|10, Option 2|20',
            'placeholder'  => '',
            'select_type'  => 'select',
        )
    ),

    array( 
        'type'       => 'pay_button',
        'name'       => __( 'Payment Button', 'invoicing' ),
        'defaults'   => array(
            'value'          => '',
            'class'          => 'btn-primary',
            'label'          => __( 'Pay %price% »', 'invoicing' ),
            'free'           => __( 'Continue »', 'invoicing' ),
            'description'    => __( 'By continuing with our payment, you are agreeing to our privacy policy and terms of service.', 'invoicing' ),
            'premade'        => true,
        )
    ),

    array(
        'type'       => 'gateway_select',
        'name'       => __( 'Gateway Select', 'invoicing' ),
        'defaults'   => array(
            'text'    => __( 'Select Payment Method', 'invoicing' ),
            'premade' => true,
        )
    ),

    array( 
        'type'       => 'total_payable',
        'name'       => __( 'Total Payable', 'invoicing' ),
        'defaults'   => array(
            'text' => __( 'Total to pay:', 'invoicing' ),
        )
    ),

    array( 
        'type'       => 'ip_address',
        'name'       => __( 'IP Address', 'invoicing' ),
        'defaults'   => array(
            'text' => __( 'Your IP address is:', 'invoicing' ),
        )
    )
);
