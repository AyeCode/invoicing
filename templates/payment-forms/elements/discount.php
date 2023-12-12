<?php
/**
 * Displays a discount field in payment form
 *
 * This template can be overridden by copying it to yourtheme/invoicing/payment-forms/elements/discount.php.
 *
 * @version 1.0.19
 * @var GetPaid_Payment_Form $form The current payment form
 */

defined( 'ABSPATH' ) || exit;

if ( ! getpaid_has_published_discount() ) {
    return;
}

if ( ! empty( $description ) ) {
    $description = "<small class='form-text text-muted'>$description</small>";
} else {
    $description = '';
}

$discount_code = '';
if ( ! empty( $form->invoice ) ) {
    $discount_code = $form->invoice->get_discount_code();
}

$class = empty( $class ) ? 'btn-secondary' : sanitize_html_class( $class );
?>

<div class="form-group mb-3">
    <div class="getpaid-discount-field  border rounded p-3">
        <div class="getpaid-discount-field-inner d-flex flex-column flex-md-row">
            <input name="discount" placeholder="<?php echo esc_attr( $input_label ); ?>" value="<?php echo esc_attr( $discount_code ); ?>" class="form-control mr-2 mb-2 getpaid-discount-field-input" style="flex: 1;" type="text">
            <a href="#" class="btn <?php echo esc_attr( $class ); ?> submit-button mb-2 getpaid-discount-button"><?php echo esc_html( $button_label ); ?></a>
        </div>
        <?php echo wp_kses_post( $description ); ?>
        <div class="getpaid-custom-payment-form-errors alert alert-danger d-none"></div>
        <div class="getpaid-custom-payment-form-success alert alert-success d-none"><?php esc_html_e( 'Discount code applied!', 'invoicing' ); ?></div>
    </div>
</div>

<?php
