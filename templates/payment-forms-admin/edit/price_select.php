<?php
/**
 * Displays a price select setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/price_select.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<small class='form-text text-muted mb-2'>
    <?php esc_html_e( 'This amount will be added to the total amount for this form', 'invoicing' ); ?>
</small>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Field Label', 'invoicing' ); ?></span>
        <input v-model='active_form_element.label' class='form-control' />
    </label>
</div>

<div class='form-group' v-if="active_form_element.select_type=='select'">
    <label class="d-block">
        <span><?php esc_html_e( 'Placeholder text', 'invoicing' ); ?></span>
        <input v-model='active_form_element.placeholder' class='form-control' />
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Select Type', 'invoicing' ); ?></span>
        <select class='form-control custom-select' v-model='active_form_element.select_type'>
            <option value='select'><?php esc_html_e( 'Dropdown', 'invoicing' ) ?></option>
            <option value='checkboxes'><?php esc_html_e( 'Checkboxes', 'invoicing' ) ?></option>
            <option value='radios'><?php esc_html_e( 'Radio Buttons', 'invoicing' ) ?></option>
            <option value='buttons'><?php esc_html_e( 'Buttons', 'invoicing' ) ?></option>
            <option value='circles'><?php esc_html_e( 'Circles', 'invoicing' ) ?></option>
        </select>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Options', 'invoicing' ); ?></span>
        <textarea placeholder='Basic|10,Pro|99,Business|199' v-model='active_form_element.options' class='form-control' rows='3'></textarea>
        <small class='form-text text-muted mb-2'><?php esc_html_e( 'Use commas to separate options and pipes to separate a label and its price. Do not include a currency symbol in the price.', 'invoicing' ); ?></small>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Help Text', 'invoicing' ); ?></span>
        <textarea placeholder='<?php esc_attr_e( 'Add some help text for this field', 'invoicing' ); ?>' v-model='active_form_element.description' class='form-control' rows='3'></textarea>
        <small class="form-text text-muted"><?php _e( 'HTML is allowed', 'invoicing' ); ?></small>
    </label>
</div>

<hr class='featurette-divider mt-4'>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Email Merge Tag', 'invoicing' ); ?></span>
        <input :value='active_form_element.label | formatMergeTag' class='form-control bg-white' type="text" readonly onclick="this.select()" />
        <span class="form-text text-muted"><?php esc_html_e( 'You can use this merge tag in notification emails', 'invoicing' ); ?></span>
    </label>
</div>

<hr class='featurette-divider mt-4'>
