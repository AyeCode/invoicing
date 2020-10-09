<?php
/**
 * Displays a checkbox preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/checkbox.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
<div class='form-check'>
    <input :for="form_element.id" type='checkbox' class='form-check-input' />
    <label class='form-check-label' :for="form_element.id" v-html="form_element.label"></label>
    <span class='text-danger' v-if='form_element.required'> *</span>
</div>
<small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
