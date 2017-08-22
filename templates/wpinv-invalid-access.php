<?php 
if ( !defined('ABSPATH') ) {
    exit;
}
do_action( 'wpinv_invalid_invoice_before_display' ); ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <title><?php wp_title() ?></title>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <?php do_action( 'wpinv_invalid_invoice_head' ); ?>
</head>
<body class="body wpinv wpinv-print wpinv-invalid-invoice">
    <?php do_action( 'wpinv_invalid_invoice_body_start' ); ?>
    <div class="container wpinv-wrap">
        <!-- ///// Start Header -->
        <htmlpageheader name="wpinv-pdf-header">
            <?php do_action( 'wpinv_invalid_invoice_before_header' ); ?>
            <div class="row wpinv-header">
                <div class="col-xs-12 wpinv-business">
                    <a target="_blank" href="<?php echo esc_url( wpinv_get_business_website() ); ?>">
                        <?php if ( $logo = wpinv_get_business_logo() ) { ?>
                        <img class="logo" src="<?php echo esc_url( $logo ); ?>">
                        <?php } else { ?>
                        <h1><?php echo esc_html( wpinv_get_business_name() ); ?></h1>
                        <?php } ?>
                    </a>
                </div>
            </div>
            <?php do_action( 'wpinv_invalid_invoice_after_header' ); ?>
        </htmlpageheader>
        <!-- End Header ///// -->
        
        <?php do_action( 'wpinv_invalid_invoice_before_content' ); ?>

        <?php do_action( 'wpinv_invalid_invoice_content' ); ?>
        
        <?php do_action( 'wpinv_invalid_invoice_after_content' ); ?>
        
        <!-- ///// Start Footer -->
        <htmlpagefooter name="wpinv-pdf-footer">
            <?php do_action( 'wpinv_invalid_invoice_before_footer' ); ?>
            <div class="row wpinv-footer">
                <div class="col-sm-12">
                    <div class="footer-text"><?php echo wpinv_get_business_footer(); ?></div>
                </div>
            </div>
            <?php do_action( 'wpinv_invalid_invoice_after_footer' ); ?>
        </htmlpagefooter>
        <!-- End Footer ///// -->
    </div>
    <?php do_action( 'wpinv_invalid_invoice_body_end' ); ?>
</body>
</html>