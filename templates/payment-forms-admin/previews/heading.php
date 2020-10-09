<?php
/**
 * Displays a heading preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/heading.php.
 *
 * @version 1.0.19
 */


defined( 'ABSPATH' ) || exit;

?>
<component :is='form_element.level' v-html='form_element.text'></component>