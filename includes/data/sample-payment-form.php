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
