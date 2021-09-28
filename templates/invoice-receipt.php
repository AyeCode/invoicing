<?php
/**
 * Template that prints the invoice receipt page.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice-receipt.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Fetch the invoice.
$invoice = new WPInv_Invoice( $invoice );

// @deprecated
do_action( 'wpinv_success_content_before', $invoice );
do_action( 'wpinv_before_receipt', $invoice );

wpinv_print_errors();

// Prepare header text.
if ( $invoice->is_paid() ) {

    $alert = aui()->alert(
        array(
            'type'    => 'success',
            'content' => __( 'Thank you for your payment!', 'invoicing' ),
        )
    );

} else if ( $invoice->is_refunded() ) {

    $alert = aui()->alert(
        array(
            'type'    => 'info',
            'content' => __( 'This invoice was refunded.', 'invoicing' ),
        )
    );

} else if ( $invoice->is_held() ) {

    $alert = aui()->alert(
        array(
            'type'    => 'info',
            'content' => __( 'This invoice will be processed as soon we verify your payment.', 'invoicing' ),
        )
    );

} else if ( $invoice->needs_payment() ) {

    if ( ! empty( $_GET['token'] ) ) {

        $alert = aui()->alert(
            array(
                'type'    => 'info',
                'content' => __( "Sometimes it takes a few minutes for us to verify your payment. We'll notify you as soon as we've verified the payment.", 'invoicing' ),
            )
        );

    } else if ( $invoice->is_due() ) {

        $alert = aui()->alert(
            array(
                'type'    => 'danger',
                'content' => sprintf(
                    __( 'This invoice was due on %.', 'invoicing' ),
                    getpaid_format_date_value( $invoice->get_due_date() )
                ),
            )
        );

    } else {

        $alert = aui()->alert(
            array(
                'type'    => 'warning',
                'content' => __( 'This invoice needs payment.', 'invoicing' ),
            )
        );

    }

}

// Invoice actions.
$actions = apply_filters(
    'wpinv_invoice_receipt_actions',
    array(

        'pay' => array(
            'url'   => $invoice->get_checkout_payment_url(),
            'name'  => __( 'Pay For Invoice', 'invoicing' ),
            'class' => 'btn-success',
        ),

        'view' => array(
            'url'   => $invoice->get_view_url(),
            'name'  => __( 'View Invoice', 'invoicing' ),
            'class' => 'btn-primary',
        ),

        'history' => array(
            'url'   => wpinv_get_history_page_uri(),
            'name'  => __( 'Invoice History', 'invoicing' ),
            'class' => 'btn-warning',
        ),

    ),
    $invoice

);

if ( ( ! $invoice->needs_payment() || $invoice->is_held() ) && isset( $actions['pay'] ) ) {
    unset( $actions['pay'] );
}

if ( ! is_user_logged_in() && isset( $actions['history'] ) ) {
    unset( $actions['history'] );
}

?>

    <div class="wpinv-receipt">

        <?php
        
            do_action( 'wpinv_receipt_start', $invoice );

            if ( ! empty( $actions ) ) {

                echo '<div class="wpinv-receipt-actions text-right mt-1 mb-4">';

                foreach ( $actions as $key => $action ) {

                    $key    = sanitize_html_class( $key );
                    $class  = empty( $action['class'] ) ? 'btn-dark' : sanitize_html_class( $action['class'] );
                    $url    = empty( $action['url'] ) ? '#' : esc_url( $action['url'] );
                    $attrs  = empty( $action['attrs'] ) ? '' : $action['attrs'];
                    $anchor = esc_html( $action['name'] );

                    echo "<a href='$url' class='btn btn-sm ml-1 $class $key' $attrs>$anchor</a>";
                }

                echo '</div>';

            }

            if ( ! empty( $alert ) ) {
                echo $alert;
            }

        ?>

        <div class="wpinv-receipt-details">

            <h4 class="wpinv-details-t mb-3 mt-3">
                <?php echo apply_filters( 'wpinv_receipt_details_title', __( 'Invoice Details', 'invoicing' ), $invoice ); ?>
            </h4>

            <?php getpaid_invoice_meta( $invoice ); ?>

        </div>

        <?php do_action( 'wpinv_receipt_end', $invoice ); ?>

    </div>

<?php

// @deprecated
do_action( 'wpinv_success_content_after', $invoice );
do_action( 'wpinv_after_receipt', $invoice );
