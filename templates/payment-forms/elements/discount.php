<?php
/**
 * Displays a discount field in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/discount.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$placeholder = esc_attr( $input_label );
$label       = sanitize_text_field( $button_label );

if ( ! empty( $description ) ) {
    $description = "<small class='form-text text-muted'>$description</small>";
} else {
    $description = '';
}
?>

<div class="form-group">
    <div class="getpaid-discount-field  border rounded p-3">
        <div class="getpaid-discount-field-inner d-flex flex-column flex-md-row">
            <input name="discount" placeholder="<?php echo $placeholder; ?>" class="form-control mr-2 mb-2 getpaid-discount-field-input" style="flex: 1;" type="text">
            <a href="#" class="btn btn-secondary submit-button mb-2 getpaid-discount-button"><?php echo $label; ?></a>
        </div>
        <?php echo $description ?>
    </div>
</div>

<?php
