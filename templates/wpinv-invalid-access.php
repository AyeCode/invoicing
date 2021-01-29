<?php
/**
 * Template that prints the invoice page.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/wpinv-invalid-access.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

// Fetch the invoice.
if ( empty( $invoice ) ) {
    $invoice = new WPInv_Invoice( $GLOBALS['post'] );
}

?><!DOCTYPE html>

<html <?php language_attributes(); ?> class="bsui">


    <head>

		<meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" >

        <meta name="robots" content="noindex,nofollow">

		<link rel="profile" href="https://gmpg.org/xfn/11">

        <title><?php _e( 'Invalid Access', 'invoicing' ); ?></title>

        <?php do_action( 'wpinv_invoice_print_head', $invoice ); ?>

    </head>


    <body class="body wpinv wpinv-print" style="font-weight: 400;">

        <?php

            if ( ! $invoice->exists() || $invoice->is_draft() ) {
                $error = __( 'This invoice was deleted or is not visible.', 'invoicing' );
            } else {

                $user_id = get_current_user_id();
                if ( wpinv_require_login_to_checkout() && empty( $user_id ) ) {
                    $error  = __( 'You must be logged in to view this invoice.', 'invoicing' );
                    $error .= sprintf(
                        ' <a href="%s">%s</a>',
                        wp_login_url( $invoice->get_view_url() ),
                        __( 'Login.', 'invoicing' )
                    );
                } else {
                    $error = __( 'This invoice is only viewable by clicking on the invoice link that was sent to you via email.', 'invoicing' );
                }

            }

        ?>

        <div class="container">
            <div class="alert alert-danger m-5" role="alert">
                <h4 class="alert-heading"><?php _e( 'Access Denied', 'invoicing' ); ?></h4>
                <p><?php echo $error; ?></p>
            </div>
        </div>

    </body>


</html>
