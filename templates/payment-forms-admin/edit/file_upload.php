<?php
/**
 * Displays a file_upload setting in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/edit/file_upload.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Field Label', 'invoicing' ); ?></span>
        <input v-model="active_form_element.label" class="form-control" type="text"/>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Max File Number', 'invoicing' ); ?></span>
        <input v-model="active_form_element.max_file_num" class="form-control" min="1" type="number" />
        <small class="form-text text-muted"><?php _e( 'How many files can be uploaded?', 'invoicing' ); ?></small>
    </label>
</div>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Allowed File types', 'invoicing' ); ?></span>
        <gpselect2 class='form-control custom-select' v-model='active_form_element.file_types' multiple>
            <?php foreach ( getpaid_get_allowed_mime_types() as $ext_regex => $mime_type ) : ?>
                <option value="<?php echo esc_attr( $ext_regex ); ?>"><?php echo esc_html( "$mime_type" ) ?></option>
            <?php endforeach; ?>
        </gpselect2>
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

<div class='form-group form-check'>
    <input :id="active_form_element.id + '_edit'" v-model='active_form_element.required' type='checkbox' class='form-check-input' />
    <label class='form-check-label' :for="active_form_element.id + '_edit'"><?php esc_html_e( 'Is this field required?', 'invoicing' ); ?></label>
</div>

<div class='form-group form-check'>
    <input :id="active_form_element.id + '_add_meta'" v-model='active_form_element.add_meta' type='checkbox' class='form-check-input' />
    <label class='form-check-label' :for="active_form_element.id + '_add_meta'"><?php esc_html_e( 'Show this field in receipts and emails?', 'invoicing' ); ?></label>
</div>

<hr class='featurette-divider mt-4'>

<div class='form-group'>
    <label class="d-block">
        <span><?php esc_html_e( 'Email Merge Tag', 'invoicing' ); ?></span>
        <input :value='active_form_element.label | formatMergeTag' class='form-control bg-white' type="text" readonly onclick="this.select()" />
        <span class="form-text text-muted"><?php esc_html_e( 'You can use this merge tag in notification emails', 'invoicing' ); ?></span>
    </label>
</div>
