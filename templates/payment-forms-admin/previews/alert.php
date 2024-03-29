<?php
/**
 * Displays an alert preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/alert.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

<div class='alert mb-0' :class='form_element.class' role='alert'>
    <span v-html='form_element.text'></span>
    <button v-if='form_element.dismissible' type='button' class='close btn-close' @click.prevent='' style="margin-top: -4px;">
        <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
            <span aria-hidden="true">×</span>
        <?php endif; ?>
    </button>
</div>
