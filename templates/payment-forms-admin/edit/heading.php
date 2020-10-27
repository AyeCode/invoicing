<?php
/**
 * Displays a heading setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/heading.php.
 *
 * @version 1.0.19
 */


defined( 'ABSPATH' ) || exit;

?>

<div class='form-group'>
    <label :for="active_form_element.id + '_edit_heading'"><?php esc_html_e( 'Heading', 'invoicing' ) ?></label>
    <input :id="active_form_element.id + '_edit_heading'" v-model='active_form_element.text' class='form-control' type='text' />
</div>

<div class='form-group'>
    <label :for="active_form_element.id + '_edit_level'"><?php esc_html_e( 'Select Heading Level', 'invoicing' ) ?></label>
    <select class='form-control custom-select' :id="active_form_element.id + '_edit_level'" v-model='active_form_element.level'>
        <option value='h1'><?php esc_html_e( 'H1', 'invoicing' ); ?></option>
        <option value='h2'><?php esc_html_e( 'H2', 'invoicing' ); ?></option>
        <option value='h3'><?php esc_html_e( 'H3', 'invoicing' ); ?></option>
        <option value='h4'><?php esc_html_e( 'H4', 'invoicing' ); ?></option>
        <option value='h5'><?php esc_html_e( 'H5', 'invoicing' ); ?></option>
        <option value='h6'><?php esc_html_e( 'H6', 'invoicing' ); ?></option>
    </select>
</div>
