<?php
/**
 * Field Types
 *
 * Returns an array of field types usable on the checkout pages.
 *
 * @package Invoicing/data
 * @version 1.0.17
 */

defined( 'ABSPATH' ) || exit;

return array(
    'text' => array(
        'name'        => __( 'Input Text', 'invoicing' ),
        'render_cb'   => 'wpinv_html_text',
    ),

    'textarea' => array(
        'name'      => __( 'Paragraph Text', 'invoicing' ),
        'render_cb' => 'wpinv_html_textarea',
    ),

    'first_name' => array(
        'name'        => __( 'First Name', 'invoicing' ),
        'render_cb'   => 'wpinv_html_text',
        'predefined'  => true,
        'defaults'    => array(
            'field_required_msg' => __( 'Please enter your first name', 'invoicing' ),
            'field_label'        => __( 'First Name', 'invoicing' ),
            'name'               => 'wpinv_first_name',
            'id'                 => 'wpinv_first_name',
            'key'                => 'first_name',
            'placeholder'        => __( 'Enter your first name', 'invoicing' ),
            'field_required'     => true,
        )
    ),

    'last_name' => array(
        'name'        => __( 'Last Name', 'invoicing' ),
        'render_cb'   => 'wpinv_html_text',
        'predefined'  => true,
        'defaults'    => array(
            'field_required_msg' => __( 'Please enter your last name', 'invoicing' ),
            'field_label'        => __( 'Last Name', 'invoicing' ),
            'name'               => 'wpinv_last_name',
            'id'                 => 'wpinv_last_name',
            'key'                => 'last_name',
            'placeholder'        => __( 'Enter your last name', 'invoicing' ),
            'field_required'     => true,
        )
    ),

    'address' => array(
        'name'        => __( 'Address', 'invoicing' ),
        'render_cb'   => 'wpinv_html_text',
        'predefined'  => true,
        'defaults'    => array(
            'field_required_msg' => __( 'Please enter your address', 'invoicing' ),
            'field_label'        => __( 'Address', 'invoicing' ),
            'name'               => 'wpinv_address',
            'id'                 => 'wpinv_address',
            'key'                => 'address',
            'placeholder'        => __( 'Enter your address', 'invoicing' ),
            'field_required'     => true,
        )
    ),

    'city' => array(
        'name'        => __( 'City', 'invoicing' ),
        'render_cb'   => 'wpinv_html_text',
        'predefined'  => true,
        'defaults'    => array(
            'field_required_msg' => __( 'Please enter your city', 'invoicing' ),
            'field_label'        => __( 'City', 'invoicing' ),
            'name'               => 'wpinv_city',
            'id'                 => 'wpinv_city',
            'key'                => 'city',
            'placeholder'        => __( 'Enter your city', 'invoicing' ),
            'field_required'     => true,
        )
    ),

    'country' => array(
        'name'        => __( 'Country', 'invoicing' ),
        'render_cb'   => 'wpinv_html_country_select',
        'predefined'  => true,
        'defaults'    => array(
            'field_required_msg' => __( 'Please select your country', 'invoicing' ),
            'field_label'        => __( 'Country', 'invoicing' ),
            'name'               => 'wpinv_country',
            'id'                 => 'wpinv_country',
            'key'                => 'country',
            'placeholder'        => __( 'Select your country', 'invoicing' ),
            'field_required'     => true,
            'input_class'        => 'wpi-input form-control wpi_select2',
        )
    ),

    'state' => array(
        'name'        => __( 'State / Province', 'invoicing' ),
        'render_cb'   => 'wpinv_html_state_select',
        'predefined'  => true,
        'defaults'    => array(
            'field_required_msg' => __( 'Please select your state', 'invoicing' ),
            'field_label'        => __( 'State / Province', 'invoicing' ),
            'name'               => 'wpinv_state',
            'id'                 => 'wpinv_state',
            'key'                => 'state',
            'placeholder'        => '',
            'field_required'     => true,
        )
    ),

    'zip' => array(
        'name'        => __( 'Postcode', 'invoicing' ),
        'render_cb'   => 'wpinv_html_text',
        'predefined'  => true,
        'defaults'    => array(
            'field_required_msg' => __( 'Please enter your zip code', 'invoicing' ),
            'field_label'        => __( 'ZIP / Postcode', 'invoicing' ),
            'name'               => 'wpinv_zip',
            'id'                 => 'wpinv_zip',
            'key'                => 'zip',
            'placeholder'        => __( 'ZIP / Postcode', 'invoicing' ),
            'field_required'     => true,
        )
    ),

    'phone' => array(
        'name'        => __( 'Billing Phone', 'invoicing' ),
        'render_cb'   => 'wpinv_html_text',
        'predefined'  => true,
        'defaults'    => array(
            'field_required_msg' => __( 'Please enter your phone number', 'invoicing' ),
            'field_label'        => __( 'Phone', 'invoicing' ),
            'name'               => 'wpinv_phone',
            'id'                 => 'wpinv_phone',
            'key'                => 'phone',
            'placeholder'        => __( 'Phone', 'invoicing' ),
            'field_required'     => true,
        )
    ),

);