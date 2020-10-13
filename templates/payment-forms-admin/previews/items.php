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
    <div class='alert alert-info' role='alert'><?php _e( 'Item totals will appear here. Click to set items.', 'invoicing' ) ?></div>
</div>

<div v-if='is_default'>
    <div class='alert alert-info' role='alert'><?php _e( 'Item totals will appear here.', 'invoicing' ) ?></div>
</div>
