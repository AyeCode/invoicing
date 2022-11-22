<?php
/**
 * Displays a radio in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/price_select.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Ensure that we have options.
if ( empty( $options ) ) {
    return;
}

// Prepare price options.
$options = getpaid_convert_price_string_to_options( $options );
$keys    = array_keys( $options );
$value   = empty( $options ) ? '' : $keys[0];

if ( ! empty( $query_value ) ) {
    $value = $query_value;
}

$select_type = empty( $select_type ) ? 'select' : $select_type;

// Item select;
if ( $select_type == 'select' ) {
    aui()->select(
        array(
            'name'        => $id,
            'id'          => esc_attr( $element_id ),
            'placeholder' => empty( $placeholder ) ? '' : esc_attr( $placeholder ),
            'value'       => $value,
            'label'       => empty( $label ) ? '' : esc_html( $label ),
            'label_type'  => 'vertical',
            'class'       => 'getpaid-price-select-dropdown getpaid-refresh-on-change',
            'help_text'   => empty( $description ) ? '' : wp_kses_post( $description ),
            'options'     => $options,
        ),
        true
    );
    return;
}

// Item radios;
if ( $select_type == 'radios' ) {
    aui()->radio(
        array(
            'name'       => esc_attr( $id ),
            'id'         => esc_attr( $id ) . uniqid( '_' ),
            'label'      => empty( $label ) ? '' : esc_html( $label ),
            'label_type' => 'vertical',
            'class'      => 'getpaid-price-select-radio getpaid-refresh-on-change w-100',
            'value'      => $value,
            'inline'     => false,
            'options'    => $options,
            'help_text'  => empty( $description ) ? '' : wp_kses_post( $description ),
        ),
        true
    );
    return;
}


// Display the label.
if ( ! empty( $label ) ) {
    echo '<label class="form-label">' . esc_html( $label ) . '</label>';
}

// Item buttons;
if ( $select_type == 'buttons' || $select_type == 'circles' ) {

    $class = 'getpaid-price-buttons';

    if ( $select_type == 'circles' ) {
        $class .= ' getpaid-price-circles';
    }
    echo "<div class='" . esc_attr( $class ) . "'>";

    foreach ( $options as $price => $label ) {
        $_id = $id . uniqid( '_' );

        $class = 'rounded';

        if ( $select_type == 'circles' ) {
            $class = '';
        }
        echo "
            <span class='d-inline-block'>
                <input type='radio' class='getpaid-price-select-button getpaid-refresh-on-change w-auto' id='" . esc_attr( $_id ) . "' value='" . esc_attr( $price ) . "' name='" . esc_attr( $id ) . "' " . checked( $price, $value, false ) . " />
                <label for='" . esc_attr( $_id ) . "' class='" . esc_attr( $class ) . "'><span>" . esc_html( $label ) . '</span></label>
            </span>
            ';
    }

    echo '</div>';

}

// Item checkboxes;
if ( $select_type == 'checkboxes' ) {
    echo '<div class="form-group mb-3">';

    foreach ( $options as $price => $label ) {
        echo "
            <label class='form-label d-block'>
                <input type='checkbox' class='getpaid-price-select-checkbox getpaid-refresh-on-change w-auto' name='" . esc_attr( $id ) . "[]' value='" . esc_attr( $price ) . "' " . checked( $price, $value, false ) . ' />
                <span>' . esc_html( $label ) . '</span>
            </label>
            ';
    }

    echo '</div>';

}

if ( ! empty( $description ) ) {
    echo "<small class='form-text text-muted'>" . wp_kses_post( $description ) . '</small>';
}
