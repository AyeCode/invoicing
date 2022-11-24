<?php
/**
 * Displays a price input preview in the payment form editor
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms-admin/previews/price_input.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Set the currency position.
$position = wpinv_currency_position();

if ( $position == 'left_space' ) {
    $position = 'left';
}

if ( $position == 'right_space' ) {
    $position = 'right';
}

?>

<label v-if='form_element.label' v-html="form_element.label"></label>

<div class="input-group">

    <?php if ( $position == 'left' ) : ?>
        <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
            <div class="input-group-prepend ">
                <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
            </div>
        <?php else : ?>
            <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
        <?php endif; ?>
    <?php endif; ?>

    <input :placeholder='form_element.placeholder' class='form-control' type='text'>

    <?php if ( $position == 'right' ) : ?>
        <?php if ( empty( $GLOBALS['aui_bs5'] ) ) : ?>
            <div class="input-group-append ">
                <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
            </div>
        <?php else : ?>
            <span class="input-group-text"><?php echo wp_kses_post( wpinv_currency_symbol() ); ?></span>
        <?php endif; ?>
    <?php endif; ?>
</div>

<small v-if='form_element.description' class='form-text text-muted' v-html='form_element.description'></small>
