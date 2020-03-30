<?php
/**
 * Default checkout fields.
 *
 * Returns an array of the default checkout fields.
 *
 * @package Invoicing/data
 * @version 1.0.17
 */

defined( 'ABSPATH' ) || exit;

return array(

    array(
        'field_type'         => 'first_name',
        'name'               => 'wpinv_first_name',
        'placeholder'        => __( 'First name', 'invoicing' ),
        'field_label'        => __( 'First name', 'invoicing' ),
        'field_required'     => (bool) wpinv_get_option( 'fname_mandatory', true ),
        'field_required_msg' => __( 'Please enter your first name', 'invoicing' ),
        'show_in'            => array( 'email', 'checkout', 'details', 'quick_checkout' ),
    ),

    array(
        'field_type'         => 'last_name',
        'name'               => 'wpinv_last_name',
        'placeholder'        => __( 'Last name', 'invoicing' ),
        'field_label'        => __( 'Last name', 'invoicing' ),
        'field_required'     => (bool) wpinv_get_option( 'lname_mandatory', true ),
        'field_required_msg' => __( 'Please enter your last name', 'invoicing' ),
        'show_in'            => array( 'email', 'checkout', 'details', 'quick_checkout' ),
    ),

    array(
        'field_type'         => 'address',
        'name'               => 'wpinv_address',
        'placeholder'        => __( 'Address', 'invoicing' ),
        'field_label'        => __( 'Address', 'invoicing' ),
        'field_required'     => (bool) wpinv_get_option( 'address_mandatory', true ),
        'field_required_msg' => __( 'Please enter your address', 'invoicing' ),
    ),

    array(
        'field_type'         => 'city',
        'name'               => 'wpinv_city',
        'placeholder'        => __( 'City', 'invoicing' ),
        'field_label'        => __( 'City', 'invoicing' ),
        'field_required'     => (bool) wpinv_get_option( 'city_mandatory', true ),
        'field_required_msg' => __( 'Please enter your billing city', 'invoicing' ),
    ),

    array(
        'name'               => 'wpinv_country',
        'show_option_all'    => false,
        'show_option_none'   => false,
        'field_label'        => __( 'Country', 'invoicing' ),
        'placeholder'        => __( 'Choose a country', 'invoicing' ),
        'field_required'     => (bool) wpinv_get_option( 'country_mandatory', true ),
        'field_type'         => 'country',
        'input_class'        => 'wpi-input form-control wpi_select2',
        'field_required_msg' => __( 'Please chose a country', 'invoicing' ),
    ),

    array(
        'field_type'         => 'state',
        'field_required'     => (bool) wpinv_get_option( 'state_mandatory', true ),
        'field_label'        => __( 'State / Province', 'invoicing' ),
        'placeholder'        => __( 'Choose a state', 'invoicing' ),
        'name'               => 'wpinv_state',
        'field_required_msg' => __( 'Please set your state or province', 'invoicing' ),
    ),

    array(
        'field_type'         => 'zip',
        'name'               => 'wpinv_zip',
        'placeholder'        => __( 'ZIP / Postcode', 'invoicing' ),
        'field_label'        => __( 'ZIP / Postcode', 'invoicing' ),
        'field_required'     => (bool) wpinv_get_option( 'zip_mandatory', true ),
        'field_required_msg' => __( 'Please enter your postcode', 'invoicing' ),
    ),

    array(
        'name'               => 'wpinv_phone',
        'placeholder'        => __( 'Phone', 'invoicing' ),
        'field_label'        => __( 'Phone', 'invoicing' ),
        'field_required'     => (bool) wpinv_get_option( 'phone_mandatory', true ),
        'field_type'         => 'phone',
        'field_required_msg' => __( 'Please enter your billing phone', 'invoicing' ),
    ),

);