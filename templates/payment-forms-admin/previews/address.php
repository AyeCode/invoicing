<?php
/**
 * Displays an address preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/address.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class='wpinv-address-wrapper'>
    <div class='form-group address-field-preview wpinv-payment-form-field-preview' v-for='(field, index) in visible_fields( form_element.fields )' :key='field.name'>
        <label class="d-block w-100">
            <span>{{field.label}}<span class='text-danger' v-if='field.required'> *</span><span>
            <input v-if='field.name !== "wpinv_country" && field.name !== "wpinv_state"' class='form-control' type='text' :placeholder='field.placeholder'>
            <select v-else class='form-control'>
                <option v-if='field.placeholder'>{{field.placeholder}}</option>
            </select>
            <small v-if='field.description' class='form-text text-muted' v-html='field.description'></small>
        </label>
    </div>
</div>
