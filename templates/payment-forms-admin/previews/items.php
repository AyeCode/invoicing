<?php
/**
 * Displays an items preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/items.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div v-if='!is_default'>
    <div v-if='! canCheckoutSeveralSubscriptions(form_element)' class='alert alert-info' role='alert'><?php _e( 'Item totals will appear here. Click to set items.', 'invoicing' ) ?></div>
    <div v-if='canCheckoutSeveralSubscriptions(form_element)' class='alert alert-danger' role='alert'><?php _e( 'Your form allows customers to buy several recurring items. This is not supported and might lead to unexpected behaviour.', 'invoicing' ); ?></div>
</div>

<div v-if='is_default'>
    <div class='alert alert-info' role='alert'><?php _e( 'Item totals will appear here.', 'invoicing' ) ?></div>
</div>
