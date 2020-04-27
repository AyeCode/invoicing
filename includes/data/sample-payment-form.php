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

        'level' => 'h2',
        'text'  => __( 'Payment Form', 'invoicing' ),
        'id'    => 'uiylyczw',
        'name'  => 'uiylyczw',
        'type'  => 'heading'
    ),

    array(

        'text' => __( 'Fill the form below to place an order for my cool item', 'invoicing' ),
        'id'   => 'pcvqjj',
        'name' => 'pcvqjj',
        'type' => 'paragraph'

    ),

    array(

        'placeholder' => 'Jon',
        'value'       => '',
        'label'       => __( 'First Name', 'invoicing' ),
        'description' => '',
        'required'    => false,
        'id'          => 'ynkzkjyc',
        'name'        => 'ynkzkjyc',
        'type'        => 'text'

    ),

    array(

        'placeholder' => 'Snow',
        'value'       => '',
        'label'       => __( 'Last Name', 'invoicing' ),
        'description' => '',
        'required'    => false,
        'id'          => 'wfjcdmzox',
        'name'        => 'wfjcdmzox',
        'type'        => 'text'

    ),

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

        'value'       => '',
        'items_type'  => 'total',
        'description' => '',
        'id'          => 'kcicd',
        'name'        => 'kcicd',
        'type'        => 'items',
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
