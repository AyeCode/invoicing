<?php
/**
 * Displays a pay button preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/pay_button.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<button class='form-control btn submit-button' :class='form_element.class' type='submit' @click.prevent=''>{{form_element.label}}</button>
<small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
