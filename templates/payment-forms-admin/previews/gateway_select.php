<?php
/**
 * Displays a gateway preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/gateway_select.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

echo aui()->alert(
    array(
        'content'     => esc_html__( 'The gateway select box will appear here', 'invoicing' ),
        'type'        => 'info',
    )
);
