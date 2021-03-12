<?php
/**
 * Displays a radio item-select box in a payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/variations/radio.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Prepare the selectable items.
$selectable = array();
foreach ( $form->get_items() as $item ) {
    if ( ! $item->is_required ) {
        $selectable[$item->get_id()] = $item->get_name() . ' &mdash; ' . wpinv_price( $item->get_initial_price() );
    }
}

if ( empty( $selectable ) ) {
    return;
}

echo '<div class="getpaid-payment-form-items-radio form-group">';

// Display the selectable items.
echo aui()->radio(
    array(
        'name'       => 'getpaid-payment-form-selected-item',
        'id'         => 'getpaid-payment-form-selected-item' . uniqid( '_' ),
        'required'   => true,
        'label'      => __( 'Select Item', 'invoicing' ),
        'label_type' => 'vertical',
        'class'      => 'w-auto',
        'inline'     => false,
        'options'    => $selectable,
    )
);

echo '</div>';
