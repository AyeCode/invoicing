<?php
/**
 * Displays an address setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/address.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<draggable v-model='active_form_element.fields' group='address_fields'>
    <div class='wpinv-form-address-field-editor' v-for='(field, index) in active_form_element.fields' :class="[field.name, { 'visible' : field.visible }]" :key='field.name'>

        <div class='wpinv-form-address-field-editor-header' @click.prevent='toggleAddressPanel'>
            <span class='label'>{{field.label}}</span>
            <span class='toggle-visibility-icon' @click.stop='field.visible = !field.visible;'>
                <span class='dashicons dashicons-hidden'></span>
                    <span class='dashicons dashicons-visibility'></span>
                </span>
                <span class='toggle-icon'>
                    <span class='dashicons dashicons-arrow-down'></span>
                    <span class='dashicons dashicons-arrow-up'></span>
                </span>
        </div>

        <div class='wpinv-form-address-field-editor-editor-body'>
            <div class='p-2'>

                <div class='form-group'>
                    <label class="d-block">
                        <span><?php esc_html_e( 'Field Label', 'invoicing' ); ?></span>
                        <input v-model='field.label' class='form-control' type="text"/>
                    </label>
                </div>

                <div class='form-group'>
                    <label class="d-block">
                        <span><?php esc_html_e( 'Placeholder text', 'invoicing' ); ?></span>
                        <input v-model='field.placeholder' class='form-control' type="text"/>
                    </label>
                </div>

                <div class='form-group'>
                    <label class="d-block">
                        <span><?php esc_html_e( 'Width', 'invoicing' ) ?></span>
                        <select class='form-control custom-select' v-model='field.grid_width'>
                            <option value='full'><?php esc_html_e( 'Full Width', 'invoicing' ); ?></option>
                            <option value='half'><?php esc_html_e( 'Half Width', 'invoicing' ); ?></option>
                            <option value='third'><?php esc_html_e( '1/3 Width', 'invoicing' ); ?></option>
                        </select>
                    </label>
                </div>

                <div class='form-group'>
                    <label class="d-block">
                        <span><?php esc_html_e( 'Help Text', 'invoicing' ); ?></span>
                        <textarea placeholder='<?php esc_attr_e( 'Add some help text for this field', 'invoicing' ); ?>' v-model='field.description' class='form-control' rows='3'></textarea>
                        <small class="form-text text-muted"><?php _e( 'HTML is allowed', 'invoicing' ); ?></small>
                    </label>
                </div>

                <div class='form-group form-check'>
                    <input :id="active_form_element.id + '_edit_required' + index" v-model='field.required' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for="active_form_element.id + '_edit_required' + index"><?php esc_html_e( 'Is required', 'invoicing' ); ?></label>
                </div>

                <div class='form-group form-check'>
                    <input :id="active_form_element.id + '_edit_visible' + index" v-model='field.visible' type='checkbox' class='form-check-input' />
                    <label class='form-check-label' :for="active_form_element.id + '_edit_visible' + index"><?php esc_html_e( 'Is visible', 'invoicing' ); ?></label>
                </div>

            </div>
        </div>

    </div>
</draggable>

<div class="mt-4"></div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Address Type', 'invoicing' ) ?><span>
        <select class='form-control custom-select' v-model='active_form_element.address_type'>
            <option value='billing'><?php esc_html_e( 'Billing', 'invoicing' ); ?></option>
            <option value='shipping'><?php esc_html_e( 'Shipping', 'invoicing' ); ?></option>
            <option value='both'><?php esc_html_e( 'Both', 'invoicing' ); ?></option>
        </select>
    </label>
</div>

<div class='form-group' v-if="active_form_element.address_type == 'both'">
    <label class="d-block">
        <span><?php esc_html_e( 'Shipping Address Toggle', 'invoicing' ) ?><span>
        <input type="text" class='form-control custom-select' v-model='active_form_element.shipping_address_toggle' >
    </label>
</div>
