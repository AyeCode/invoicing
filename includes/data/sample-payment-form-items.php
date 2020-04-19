<?php
/**
 * Sample Payment form items
 *
 * Returns an array of fields for the sample payment form.
 *
 * @package Invoicing/data
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

return array(

    array(

        'title'       => __( 'My Cool Item', 'invoicing' ),
        'id'          => 'fxhnagzi',
        'price'       => '999.00',
        'recurring'   => false,
        'description' => ''
        
    ),

    array(

        'title'       => __( 'Shipping Fee', 'invoicing' ),
        'id'          => 'rxnymibri',
        'price'       => '19.99',
        'recurring'   => false,
        'description' => ''

    )
);
