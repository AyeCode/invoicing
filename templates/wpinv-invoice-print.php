<?php 
if ( !defined('ABSPATH') ) {
    exit;
}
global $post;
$invoice_id = $post->ID;
$invoice = wpinv_get_invoice( $invoice_id );
if ( empty( $invoice ) ) {
    exit;
}
$type = $post->post_type == 'wpi_invoice' ? __( 'Invoice', 'invoicing' ): __( 'Quotation', 'invoicing' );
do_action( 'wpinv_invoice_print_before_display', $invoice ); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <title><?php wp_title() ?></title>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">

    <?php do_action( 'wpinv_invoice_print_head', $invoice ); ?>
</head>
<body class="body wpinv wpinv-print bsui">
    <?php do_action( 'getpaid_invoice_top_bar', $invoice ); ?>
    <?php do_action( 'wpinv_invoice_print_body_start', $invoice ); ?>
    <div class="container wpinv-wrap">
        <?php if ( $watermark = wpinv_watermark( $invoice_id ) ) { ?>
            <div class="watermark no-print"><p><?php echo esc_html( $watermark ) ?></p></div>
        <?php } ?>

        <?php do_action( 'getpaid_invoice_title', $invoice ); ?>
        <?php do_action( 'getpaid_invoice_details', $invoice ); ?>
        <?php do_action( 'wpinv_invoice_print_middle', $invoice ); ?>
        
        <?php do_action( 'wpinv_invoice_print_before_line_items', $invoice ); ?>
        <div class="row wpinv-items">
            <div class="col-sm-12 wpinv-line-items">
                <?php wpinv_display_line_items( $invoice_id ); ?>
            </div>
        </div>
        <?php do_action( 'wpinv_invoice_print_after_line_items', $invoice ); ?>
        
        <!-- ///// Start PDF footer -->
        <htmlpagefooter name="wpinv-pdf-footer">
            <?php do_action( 'wpinv_invoice_print_before_footer', $invoice ); ?>
            <div class="row wpinv-footer">
                <div class="col-sm-12">
                    <?php if ( $term_text = wpinv_get_terms_text() ) { ?>
                    <div class="terms-text"><?php echo wpautop( $term_text ); ?></div>
                    <?php } ?>
                    <div class="footer-text"><?php echo wpinv_get_business_footer(); ?></div>
                    <div class="print-only"><?php _e( 'Page ', 'invoicing' ) ?> {PAGENO}/{nbpg}</div>
                </div>
            </div>
            <?php do_action( 'wpinv_invoice_print_after_footer', $invoice ); ?>
        </htmlpagefooter>
        <!-- End PDF footer ///// -->
    </div><!-- END wpinv-wrap -->
    <?php do_action( 'wpinv_invoice_print_body_end', $invoice ); ?>
</body>
</html>