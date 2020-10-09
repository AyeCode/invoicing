<?php
/**
 * Displays a paragraph preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/paragraph.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<p v-html='form_element.text' class="mb-0" style='font-size: 16px;'></p>
