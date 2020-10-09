<?php
/**
 * Displays a time preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/time.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<label class="d-block w-100">
    <span v-html="form_element.label"></span>
    <input class='form-control' type='time'>
    <small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
</label>
