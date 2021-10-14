<?php
/**
 * Displays a file_upload preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/file_upload.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<label><span v-html="form_element.label"></span></label>
<div class="d-flex w-100 flex-column align-items-center justify-content-center p-2" style="height: 200px; border: 3px dashed rgb(136, 136, 136); cursor: pointer;">
    <div class="h5 text-dark">
        <span v-if="form_element.max_file_num > 1"><?php _e( 'Drag files to this area or click to upload', 'invoicing' ); ?></span>
        <span v-if="form_element.max_file_num < 2"><?php _e( 'Drag your file to this area or click to upload', 'invoicing' ); ?></span>
    </div>
    <small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
</div>
