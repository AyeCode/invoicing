<?php
/**
 * Displays a payment button setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/pay_button.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Alert Text', 'invoicing' ); ?></span>
        <textarea v-model='active_form_element.text' class='form-control' rows='3'></textarea>
    </label>
</div>

<div class='form-group form-check'>
    <input :id="active_form_element.id + '_edit_dismissible'" v-model='active_form_element.dismissible' type='checkbox' class='form-check-input' />
    <label class='form-check-label' :for="active_form_element.id + '_edit_dismissible'"><?php esc_html_e( 'Is Dismissible?', 'invoicing' ); ?></label>
</div>

<div class='form-group'>
    <label :for="active_form_element.id + '_edit_type'"><?php esc_html_e( 'Alert Type', 'invoicing' ) ?></label>
    <select class='form-control custom-select' :id="active_form_element.id + '_edit_type'" v-model='active_form_element.class'>
        <option value='alert-primary'><?php esc_html_e( 'Primary', 'invoicing' ); ?></option>
        <option value='alert-secondary'><?php esc_html_e( 'Secondary', 'invoicing' ); ?></option>
        <option value='alert-success'><?php esc_html_e( 'Success', 'invoicing' ); ?></option>
        <option value='alert-danger'><?php esc_html_e( 'Danger', 'invoicing' ); ?></option>
        <option value='alert-warning'><?php esc_html_e( 'Warning', 'invoicing' ); ?></option>
        <option value='alert-info'><?php esc_html_e( 'Info', 'invoicing' ); ?></option>
        <option value='alert-light'><?php esc_html_e( 'Light', 'invoicing' ); ?></option>
        <option value='alert-dark'><?php esc_html_e( 'Dark', 'invoicing' ); ?></option>
        <option value='alert-link'><?php esc_html_e( 'Link', 'invoicing' ); ?></option>
    </select>
</div>
