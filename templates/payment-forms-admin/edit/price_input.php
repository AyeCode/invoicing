<?php
/**
 * Displays a price input setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/price_input.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<small class='form-text text-muted mb-2'>
    <?php esc_html_e( 'The amount that users add to this field will be added to the total amount for this form', 'invoicing' ); ?>
</small>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Field Label', 'invoicing' ); ?></span>
        <input v-model='active_form_element.label' class='form-control' type="text"/>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Default Amount', 'invoicing' ); ?></span>
        <input v-model='active_form_element.value' class='form-control' type="text"/>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Minimum Amount', 'invoicing' ); ?></span>
        <input v-model='active_form_element.minimum' class='form-control' type="text"/>
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
