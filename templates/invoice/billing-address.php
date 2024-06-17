<?php
/**
 * Displays the billing address.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/billing-address.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$invoice     = new WPInv_Invoice( $invoice );
$address_row = wpinv_get_invoice_address_markup( $invoice->get_user_info() );
$phone       = $invoice->get_phone();
$email       = $invoice->get_email();
$vat_number  = $invoice->get_vat_number();
$company_id  = $invoice->get_company_id();
?>
    <div class="getpaid-billing-address form-group mb-3 text-break">

        <div class="row">


            <div class="invoice-billing-address-label col-2">
                <strong><?php esc_html_e( 'To:', 'invoicing' ); ?></strong>
            </div>


            <div class="invoice-billing-address-value col-10">

                <?php do_action( 'getpaid_billing_address_top' ); ?>

                <?php if ( ! empty( $address_row ) ) : ?>
                    <div class="billing-address">
                        <?php echo wp_kses_post( $address_row ); ?>
                    </div>
                <?php endif; ?>


                <?php if ( ! empty( $phone ) ) : ?>
                    <div class="billing-phone">
                        <?php echo wp_sprintf( esc_html__( 'Phone: %s', 'invoicing' ), esc_html( $phone ) ); ?>
                    </div>
                <?php endif; ?>


                <?php if ( ! empty( $email ) ) : ?>
                    <div class="billing-email">
                        <?php echo wp_sprintf( esc_html__( 'Email: %s', 'invoicing' ), esc_html( $email ) ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $vat_number ) && wpinv_use_taxes() ) : ?>
                    <div class="vat-number">
                        <?php echo wp_sprintf( esc_html__( 'Vat Number: %s', 'invoicing' ), esc_html( $vat_number ) ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $company_id ) ) : ?>
                    <div class="company-id">
                        <?php echo wp_sprintf( esc_html__( 'Company ID: %s', 'invoicing' ), esc_html( $company_id ) ); ?>
                    </div>
                <?php endif; ?>

                <?php do_action( 'getpaid_billing_address_bottom' ); ?>

            </div>

        </div>

    </div>
<?php
