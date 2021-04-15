<?php
/**
 * Sample Payment form
 *
 * Returns an array of fields for the sample payment form.
 *
 * @package Invoicing/data
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

return array(

    array(

        'placeholder' => 'jon@snow.com',
        'value'       => '',
        'label'       => __( 'Billing Email', 'invoicing' ),
        'description' => '',
        'required'    => true,
        'id'          => 'mmdwqzpox',
        'name'        => 'mmdwqzpox',
        'type'        => 'billing_email',
        'premade'     => true
    ),

    array(

        'type'   => 'address',
        'id'     => 'mmdwqzpoxadd',
        'name'   => 'mmdwqzpoxadd',
        'fields' => array(
            array(
                'placeholder'  => 'Jon',
                'value'        => '',
                'label'        => __( 'First Name', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_first_name',
            ),

            array(
                'placeholder'  => 'Snow',
                'value'        => '',
                'label'        => __( 'Last Name', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_last_name',
            ),
        
            array(
                'placeholder'  => '',
                'value'        => '',
                'label'        => __( 'Address', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_address',
            ),

            array(
                'placeholder'  => '',
                'value'        => '',
                'label'        => __( 'City', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_city',
            ),

            array(
                'placeholder'  => __( 'Select your country' ),
                'value'        => '',
                'label'        => __( 'Country', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_country',
            ),

            array(
                'placeholder'  => __( 'Choose a state', 'invoicing' ),
                'value'        => '',
                'label'        => __( 'State / Province', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_state',
            ),

            array(
                'placeholder'  => '',
                'value'        => '',
                'label'        => __( 'ZIP / Postcode', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_zip',
            ),

            array(
                'placeholder'  => '',
                'value'        => '',
                'label'        => __( 'Phone', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_phone',
            ),

            array(
                'placeholder'  => '',
                'value'        => '',
                'label'        => __( 'Company', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_company',
            ),

            array(
                'placeholder'  => '',
                'value'        => '',
                'label'        => __( 'VAT Number', 'invoicing' ),
                'description'  => '',
                'required'     => false,
                'visible'      => true,
                'name'         => 'wpinv_vat_number',
            )
        )
    ),

    array(

        'value'        => '',
        'input_label'  => __( 'Coupon Code', 'invoicing' ),
        'button_label' => __( 'Apply Coupon', 'invoicing' ),
        'description'  => __( 'Have a discount code? Enter it above.', 'invoicing' ),
        'id'           => 'kcicdiscount',
        'name'         => 'kcicdiscount',
        'type'         => 'discount',

    ),

    array(

        'value'       => '',
        'items_type'  => 'total',
        'description' => '',
        'id'          => 'kcicd',
        'name'        => 'kcicd',
        'type'        => 'items',
        'premade'     => true

    ),

    array(
        'text'    => __( 'Select Payment Method', 'invoicing' ),
        'id'          => 'gtscicd',
        'name'        => 'gtscicd',
        'type'        => 'gateway_select',
        'premade'     => true

    ),

    array(

        'value'       =>'',
        'class'       => 'btn-primary',
        'label'       => __( 'Pay Now »', 'invoicing' ),
        'description' => __( 'By continuing with your payment, you are agreeing to our privacy policy and terms of service.', 'invoicing' ),
        'id'          => 'rtqljyy',
        'name'        => 'rtqljyy',
        'type'        => 'pay_button',
        'premade'     => true,
    )
);
