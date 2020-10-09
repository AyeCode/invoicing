<?php
/**
 * Displays a billing email preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/billing_email.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<label class="d-block w-100">
    <span v-html="form_element.label"></span>
    <input :placeholder='form_element.placeholder' class='form-control' type='email'>
    <small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
</label>
