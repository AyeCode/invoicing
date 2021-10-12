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

// Prepare id.
$id = esc_attr( $id );

$select_type = empty( $select_type ) ? 'select' : $select_type;

// Item select;
if ( $select_type == 'select' ) {
    echo aui()->select(
        array(
            'name'       => $id,
            'id'         => $id . uniqid( '_' ),
            'placeholder'=> empty( $placeholder ) ? '' : esc_attr( $placeholder ),
            'value'      => $value,
            'label'      => empty( $label ) ? '' : esc_html( $label ),
            'label_type' => 'vertical',
            'class'      => 'getpaid-price-select-dropdown getpaid-refresh-on-change',
            'help_text'  => empty( $description ) ? '' : wp_kses_post( $description ),
            'options'    => $options,
        )
    );
    return;
}

// Item radios;
if ( $select_type == 'radios' ) {
    echo aui()->radio(
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
        )
    );
    return;
}


// Display the label.
if ( ! empty( $label ) ) {
    $label = esc_html( $label );
    echo "<label>$label</label>";
}

// Item buttons;
if ( $select_type == 'buttons' || $select_type == 'circles' ) {

    $class = 'getpaid-price-buttons';

    if ( $select_type == 'circles' ) {
        $class .= ' getpaid-price-circles';
    }
    echo "<div class='$class'>";

    foreach ( $options as $price => $label ) {
        $label   = esc_html( $label );
        $price   = esc_attr( $price );
        $_id     = $id . uniqid( '_' );
        $checked = checked( $price, $value, false );

        $class = 'rounded';

        if ( $select_type == 'circles' ) {
            $class = '';
        }
        echo "
            <span class='d-inline-block'>
                <input type='radio' class='getpaid-price-select-button getpaid-refresh-on-change w-auto' id='$_id' value='$price' name='$id' $checked />
                <label for='$_id' class='$class'><span>$label</span></label>
            </span>
            ";
    }

    echo '</div>';

}

// Item checkboxes;
if ( $select_type == 'checkboxes' ) {
    echo '<div class="form-group">';

    foreach ( $options as $price => $label ) {
        $label   = esc_html( $label );
        $price   = esc_attr( $price );
        $checked = checked( $price, $value, false );
        echo "
            <label class='d-block'>
                <input type='checkbox' class='getpaid-price-select-checkbox getpaid-refresh-on-change w-auto' name='{$id}[]' value='$price' $checked />
                <span>$label</span>
            </label>
            ";
    }

    echo '</div>';

}

if ( ! empty( $description ) ) {
    echo "<small class='form-text text-muted'>$description</small>";
}
