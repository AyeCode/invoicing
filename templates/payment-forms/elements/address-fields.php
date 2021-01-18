<?php
/**
 * Displays an address field in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/address-fields.php.
 *
 * @version 1.0.19
 * @var array $fields
 * @var string $field_type Either billing or shipping
 * @var string $uniqid A unique prefix for all ids
 * @var string $country The current user's country
 */

defined( 'ABSPATH' ) || exit;


$field_type = sanitize_key( $field_type );

echo "<div class='row $field_type'>";

foreach ( $fields as $address_field ) {

    // Skip if it is hidden.
    if ( empty( $address_field['visible'] ) ) {
        continue;
    }

    do_action( 'getpaid_payment_form_address_field_before_' . $address_field['name'], $field_type, $address_field );

    // Prepare variables.
    $field_name  = $address_field['name'];
    $field_name  = "{$field_type}[$field_name]";
    $wrap_class  = getpaid_get_form_element_grid_class( $address_field );
    $wrap_class  = esc_attr( "$wrap_class getpaid-address-field-wrapper" );
    $placeholder = empty( $address_field['placeholder'] ) ? '' : esc_attr( $address_field['placeholder'] );
    $description = empty( $address_field['description'] ) ? '' : wp_kses_post( $address_field['description'] );
    $value       = is_user_logged_in() ? get_user_meta( get_current_user_id(), '_' . $address_field['name'], true ) : '';
    $label       = empty( $address_field['label'] ) ? '' : wp_kses_post( $address_field['label'] );

    if ( ! empty( $address_field['required'] ) ) {
        $label .= "<span class='text-danger'> *</span>";
    }

    // Display the country.
    if ( 'wpinv_country' == $address_field['name'] ) {

        echo aui()->select(
            array(
                'options'     => wpinv_get_country_list(),
                'name'        => esc_attr( $field_name ),
                'id'          => sanitize_html_class( $field_name ) . $uniqid,
                'value'       => sanitize_text_field( $country ),
                'placeholder' => $placeholder,
                'required'    => ! empty( $address_field['required'] ),
                'label'       => wp_kses_post( $label ),
                'label_type'  => 'vertical',
                'help_text'   => $description,
                'class'       => 'getpaid-address-field wpinv_country',
                'wrap_class'  => "$wrap_class getpaid-address-field-wrapper__country",
                'label_class' => 'getpaid-address-field-label getpaid-address-field-label__country',
                'extra_attributes' => array(
                    'autocomplete' => "$field_type country",
                ),
            )
        );

    }

    // Display the state.
    else if ( 'wpinv_state' == $address_field['name'] ) {

        if ( empty( $value ) ) {
            $value = wpinv_get_default_state();
        }

        echo getpaid_get_states_select_markup (
            $country,
            $value,
            $placeholder,
            $label,
            $description,
            ! empty( $address_field['required'] ),
            $wrap_class,
            $field_name
        );

    } else {

        $key      = str_replace( 'wpinv_', '', $address_field['name'] );
        $key      = esc_attr( str_replace( '_', '-', $key ) );
        $autocomplete = '';
        $replacements = array(
            'zip'        => 'postal-code',
            'first_name' => 'given-name',
            'last_name'  => 'family-name',
            'company'    => 'organization',
            'address'    => 'street-address',
            'phone'      => 'tel',
            'city'       => 'address-level2',
        );


        if ( isset( $replacements[ $key ] ) ) {
            $autocomplete = array(
                'autocomplete' => "$field_type {$replacements[ $key ]}",
            );
        }

        echo aui()->input(
            array(
                'name'        => esc_attr( $field_name ),
                'id'          => sanitize_html_class( $field_name ) . $uniqid,
                'required'    => ! empty( $address_field['required'] ),
                'placeholder' => $placeholder,
                'label'       => wp_kses_post( $label ),
                'label_type'  => 'vertical',
                'help_text'   => $description,
                'type'        => 'text',
                'value'       => sanitize_text_field( $value ),
                'class'       => 'getpaid-address-field ' . esc_attr( $address_field['name'] ),
                'wrap_class'  => "$wrap_class getpaid-address-field-wrapper__$key",
                'label_class' => 'getpaid-address-field-label getpaid-address-field-label__' . $key,
                'extra_attributes' => $autocomplete,
            )
        );

    }

    do_action( 'getpaid_payment_form_address_field_after_' . $address_field['name'], $field_type, $address_field );
}

echo "</div>";
