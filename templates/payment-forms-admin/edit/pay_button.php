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
        <span><?php esc_html_e( 'Button Text', 'invoicing' ); ?></span>
        <input v-model='active_form_element.label' class='form-control' type="text"/>
        <small class="form-text text-muted"><?php _e( '%price% will be replaced by the total payable amount', 'invoicing' ); ?></small>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Free Checkout Text', 'invoicing' ); ?></span>
        <input v-model='active_form_element.free' class='form-control' type="text"/>
        <small class="form-text text-muted"><?php _e( 'The text to display if the total payable amount is zero', 'invoicing' ); ?></small>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Help Text', 'invoicing' ); ?></span>
        <textarea placeholder='<?php esc_attr_e( 'Add some help text for this field', 'invoicing' ); ?>' v-model='active_form_element.description' class='form-control' rows='3'></textarea>
        <small class="form-text text-muted"><?php _e( 'HTML is allowed', 'invoicing' ); ?></small>
    </label>
</div>

<div class='form-group'>
    <label :for="active_form_element.id + '_edit_type'"><?php esc_html_e( 'Button Type', 'invoicing' ) ?></label>
    <select class='form-control custom-select' :id="active_form_element.id + '_edit_type'" v-model='active_form_element.class'>
        <option value='btn-primary'><?php esc_html_e( 'Primary', 'invoicing' ); ?></option>
        <option value='btn-secondary'><?php esc_html_e( 'Secondary', 'invoicing' ); ?></option>
        <option value='btn-success'><?php esc_html_e( 'Success', 'invoicing' ); ?></option>
        <option value='btn-danger'><?php esc_html_e( 'Danger', 'invoicing' ); ?></option>
        <option value='btn-warning'><?php esc_html_e( 'Warning', 'invoicing' ); ?></option>
        <option value='btn-info'><?php esc_html_e( 'Info', 'invoicing' ); ?></option>
        <option value='btn-light'><?php esc_html_e( 'Light', 'invoicing' ); ?></option>
        <option value='btn-dark'><?php esc_html_e( 'Dark', 'invoicing' ); ?></option>
        <option value='btn-link'><?php esc_html_e( 'Link', 'invoicing' ); ?></option>
    </select>
</div>
