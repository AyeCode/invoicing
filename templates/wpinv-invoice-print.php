<?php
/**
 * Template that prints the invoice page.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Fetch the invoice.
if ( empty( $invoice ) ) {
    $invoice = new WPInv_Invoice( $GLOBALS['post'] );
}

// Abort if it does not exist.
if ( $invoice->get_id() == 0 ) {
    exit;
}

// Fires before printing an invoice.
do_action( 'wpinv_invoice_print_before_display', $invoice );

?><!DOCTYPE html>

<html <?php language_attributes(); ?> class="bsui">


    <head>

		<meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" >

        <meta name="robots" content="noindex,nofollow">

		<link rel="profile" href="https://gmpg.org/xfn/11">

        <title>#<?php echo esc_html( $invoice->get_number() ); ?></title>

        <?php do_action( 'wpinv_invoice_print_head', $invoice ); ?>

    </head>


    <body class="body wpinv wpinv-print" style="font-weight: 400;">

        <?php do_action( 'getpaid_invoice', $invoice ); ?>
        <?php do_action( 'wpinv_invoice_print_body_end', $invoice ); ?>

    </body>


</html>
