<?php
/**
 * Displays a select setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/select.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Field Label', 'invoicing' ); ?></span>
        <input v-model='active_form_element.label' class='form-control' type="text"/>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Placeholder text', 'invoicing' ); ?></span>
        <input v-model='active_form_element.placeholder' class='form-control' type="text"/>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Help Text', 'invoicing' ); ?></span>
        <textarea placeholder='<?php esc_attr_e( 'Add some help text for this field', 'invoicing' ); ?>' v-model='active_form_element.description' class='form-control' rows='3'></textarea>
    </label>
</div>

<div class='form-group form-check'>
    <input :id="active_form_element.id + '_edit'" v-model='active_form_element.required' type='checkbox' class='form-check-input' />
    <label class='form-check-label' :for="active_form_element.id + '_edit'"><?php esc_html_e( 'Is this field required?', 'invoicing' ); ?></label>
</div>

<hr class='featurette-divider mt-4'>

<h5><?php esc_html_e( 'Available Options', 'invoicing' ); ?></h5>

<div class='form-group input-group' v-for='(option, index) in active_form_element.options'>
    <input type='text' class='form-control' v-model='active_form_element.options[index]'>
    <div class='input-group-append'>
        <button class='button button-secondary border' type='button' @click.prevent='active_form_element.options.splice(index, 1)'><span class='dashicons dashicons-trash'></span></button>
    </div>
</div>

<div class='form-group'>
    <button class='button button-secondary' type='button' @click.prevent='active_form_element.options.push("")'><?php esc_html_e( 'Add Option', 'invoicing' ); ?></button>
</div>
