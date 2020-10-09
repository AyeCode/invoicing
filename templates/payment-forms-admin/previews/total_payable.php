<?php
/**
 * Displays the total payable preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/total_payable.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

echo aui()->alert(
    array(
        'content'     => esc_html__( 'The total payable amount will appear here', 'invoicing' ),
        'type'        => 'info',
    )
);
