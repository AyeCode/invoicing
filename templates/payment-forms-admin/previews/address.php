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

<div class='wpinv-address-wrapper row'>

    <h4 v-if="form_element.address_type == 'both'" class="col-12 mb-3"><?php _e( 'Billing / Shipping Address', 'invoicing' ); ?></h4>

    <div class='form-group address-field-preview wpinv-payment-form-field-preview' v-for='(field, index) in visible_fields( form_element.fields )' :class='grid_class( field )' :key='field.name'>
        <label class="d-block w-100">
            <span>{{field.label}}<span class='text-danger' v-if='field.required'> *</span><span>
            <input v-if='field.name !== "wpinv_country" && field.name !== "wpinv_state"' class='form-control' type='text' :placeholder='field.placeholder'>
            <select v-else class='form-control'>
                <option v-if='field.placeholder'>{{field.placeholder}}</option>
            </select>
            <small v-if='field.description' class='form-text text-muted' v-html='field.description'></small>
        </label>
    </div>

    <div class="col-12 mb-3">
        <div class='form-check' v-if="form_element.address_type == 'both'">
            <input type='checkbox' class='form-check-input' />
            <label class='form-check-label' v-html="form_element.shipping_address_toggle"></label>
        </div>
    </div>

</div>
