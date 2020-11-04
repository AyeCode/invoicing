<?php
/**
 * Displays an address in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/address.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $fields ) ) {
    return;
}

// Prepare the user's country.
$country = is_user_logged_in() ? get_user_meta( get_current_user_id(), '_wpinv_country', true ) : '';
$country = empty( $country ) ? wpinv_get_default_country() : $country;

// A prefix for all ids (so that a form can be included in the same page multiple times).
$uniqid = uniqid( '_' );

echo "<div class='row'>";
foreach ( $fields as $address_field ) {

    // Skip if it is hidden.
    if ( empty( $address_field['visible'] ) ) {
        continue;
    }

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
                'name'        => 'wpinv_country',
                'id'          => 'wpinv_country' . $uniqid,
                'value'       => sanitize_text_field( $country ),
                'placeholder' => $placeholder,
                'required'    => ! empty( $address_field['required'] ),
                'label'       => wp_kses_post( $label ),
                'label_type'  => 'vertical',
                'help_text'   => $description,
                'class'       => 'getpaid-address-field wpinv_country',
                'wrap_class'  => "$wrap_class getpaid-address-field-wrapper__country",
                'label_class' => 'getpaid-address-field-label getpaid-address-field-label__country',
            )
        );
        continue;

    }

    // Display the state.
    if ( 'wpinv_state' == $address_field['name'] ) {

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
            $wrap_class
        );

        continue;
    }

    $key = str_replace( 'wpinv_', '', $address_field['name'] );
    $key = esc_attr( str_replace( '_', '-', $key ) );
    echo aui()->input(
        array(
            'name'        => esc_attr( $address_field['name'] ),
            'id'          => esc_attr( $address_field['name'] ) . $uniqid,
            'required'    => ! empty( $address_field['required'] ),
            'placeholder' => $placeholder,
            'label'       => wp_kses_post( $label ),
            'label_type'  => 'vertical',
            'help_text'   => $description,
            'type'        => 'text',
            'value'       => $value,
            'class'       => 'getpaid-address-field ' . esc_attr( $address_field['name'] ),
            'wrap_class'  => "$wrap_class getpaid-address-field-wrapper__$key",
            'label_class' => 'getpaid-address-field-label getpaid-address-field-label__' . $key,
        )
    );

}
echo "</div>";
