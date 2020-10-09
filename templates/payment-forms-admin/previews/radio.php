<?php
/**
 * Displays a radio preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/radio.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<legend class='col-form-label' v-if='form_element.label'>
    <span v-html="form_element.label"></span>
    <span class='text-danger' v-if='form_element.required'> *</span>
</legend>

<div class='form-check' v-for='(option, index) in form_element.options'>
    <input class='form-check-input' type='radio'>
    <label class='form-check-label'>{{option}}</label>
</div>

<small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
