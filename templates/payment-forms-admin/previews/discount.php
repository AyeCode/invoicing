<?php
/**
 * Displays a discount preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/discount.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="discount_field_inner d-flex flex-column flex-md-row">
    <input :placeholder="form_element.input_label" class="form-control mr-2" style="flex: 1;" type="text">
    <button class="btn btn-secondary submit-button" type="submit" @click.prevent="">{{form_element.button_label}}</button>
</div>

<small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
