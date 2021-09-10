<?php
/**
 * Displays the company address.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/company-address.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Prepare the company name.
$company_name = wpinv_get_option( 'vat_company_name' );

if ( empty( $company_name ) ) {
    $company_name = wpinv_get_business_name();
}

// Prepare the VAT number.
$vat_number = wpinv_get_option( 'vat_number' );

?>
    <div class="getpaid-company-address form-group">

        <div class="row">

            <div class="invoice-company-address-label col-2">
                <strong><?php _e( 'From:', 'invoicing' ) ?></strong>
            </div>

            <div class="invoice-company-address-value col-10">

                <?php do_action( 'getpaid_company_address_top' ); ?>

                <div class="name">
                    <a target="_blank" class="text-dark" href="<?php echo esc_url( wpinv_get_business_website() ); ?>">
                        <?php echo esc_html( $company_name ); ?>
                    </a>
                </div>

                <?php if ( $address = wpinv_get_business_address() ) { ?>
                    <?php echo $address;?>
                <?php } ?>

                <?php if ( $email_from = wpinv_mail_get_from_address() ) { ?>
                    <div class="email_from">
                        <?php echo wp_sprintf( __( 'Email: %s', 'invoicing' ), $email_from );?>
                    </div>
                <?php } ?>

                <?php if ( ! empty( $vat_number ) ) { ?>
                    <div class="email_from">
                        <?php echo wp_sprintf( __( 'VAT Number: %s', 'invoicing' ), esc_html( $vat_number ) );?>
                    </div>
                <?php } ?>

                <?php do_action( 'getpaid_company_address_bottom' ); ?>

            </div>

        </div>

    </div>
<?php
