<?php
/**
 * Displays invoice details in emails.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/emails/invoice-details.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 * @var array $columns
 */
defined( 'ABSPATH' ) || exit;

$meta_data = getpaid_get_invoice_meta( $invoice );

if ( isset( $meta_data['status'] ) ) {
    $meta_data['status']['value'] = $invoice->get_status_nicename();
}

do_action( 'wpinv_email_before_invoice_details', $invoice, $sent_to_admin );

?>

<div id="wpinv-email-details">

    <h3 class="invoice-details-title">
        <?php echo sprintf( esc_html__( '%s Details', 'invoicing' ), ucfirst( $invoice->get_invoice_quote_type() )); ?>
    </h3>

    <table class="table table-bordered table-sm">

        <?php foreach ( $meta_data as $key => $data ) : ?>

            <?php if ( ! empty( $data['value'] ) ) : ?>

                <?php do_action( "getpaid_before_email_details_$key", $invoice, $data ); ?>

                <tr class="getpaid-email-details-<?php echo sanitize_html_class( $key ); ?>">

                    <td class="getpaid-lable-td">
                        <?php echo esc_html( $data['label'] ); ?>
                    </td>

                    <td class="getpaid-value-td">
                        <span class="getpaid-invoice-meta-<?php echo sanitize_html_class( $key ); ?>-value"><?php echo wp_kses_post( $data['value'] ); ?></span>
                    </td>

                </tr>

                <?php do_action( "getpaid_after_email_details_$key", $invoice, $data ); ?>

            <?php endif; ?>

        <?php endforeach; ?>

    </table>

</div>

<?php do_action( 'wpinv_email_after_invoice_details', $invoice, $sent_to_admin ); ?>
