<?php 
if ( !defined('ABSPATH') ) {
    exit;
}
global $post;
$invoice_id = $post->ID;
do_action( 'wpinv_before_invoice_display' ); ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <title><?php wp_title() ?></title>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">

    <?php do_action('wpinv_head'); ?>
</head>

<?php do_action( 'wpinv_before_body' ); ?>

<body class="body wpinv">
<?php do_action( 'wpinv_after_body' ); ?>
    <div class="container wpinv-wrap">
    <?php if ( $watermark = wpinv_watermark( $invoice_id ) ) { ?>
        <div class="watermark no-print"><p><?php echo esc_html( $watermark ) ?></p></div>
    <?php } ?>
    <!-- ///// Start PDF header -->
    <htmlpageheader name="wpinv-pdf-header">
        <div class="row wpinv-header">
            <div class="col-xs-12 col-sm-6 wpinv-business">
                <a target="_blank" href="<?php echo esc_url( wpinv_get_business_website() ); ?>">
                    <?php if ( $logo = wpinv_get_business_logo() ) { ?>
                    <img class="logo" src="<?php echo esc_url( $logo ); ?>">
                    <?php } else { ?>
                    <h1><?php echo esc_html( wpinv_get_business_name() ); ?></h1>
                    <?php } ?>
                </a>
            </div>

            <div class="col-xs-12 col-sm-6 wpinv-title">
                <h2><?php echo esc_html( _e( 'Invoice', 'invoicing' ) ); ?></h2>
            </div>
        </div><!-- END row -->
    </htmlpageheader>
    <!-- End PDF header ///// -->
        
        <div class="row wpinv-upper">
            <div class="col-xs-12 col-sm-6">
                <div class="row col-xs-12 col-sm-12 wpinv-from-address wpinv-address">
                    <?php wpinv_display_from_address(); ?>
                </div>
                <div class="row col-xs-12 col-sm-12 wpinv-to-address wpinv-address wpinv-middle">
                    <?php wpinv_display_to_address( $invoice_id ); ?>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="col-xs-12 col-sm-10 wpinv-details">
                    <?php wpinv_display_invoice_details( $invoice_id ); ?>
                </div>
            </div>
        </div><!-- END row -->

        <?php if ( $description = wpinv_get_invoice_description( $invoice_id ) ) { ?>
            <div class="row wpinv-lower">
                <div class="col-sm-12 wpinv-description">
                    <?php echo wpautop( $description ); ?>
                </div>
            </div><!-- END row -->
        <?php } ?>
        
        <div class="row wpinv-items">
            <div class="col-sm-12 wpinv-line-items">
                <div class="table-responsive">
                    <?php wpinv_display_line_items( $invoice_id ); ?>
                </div>
                <div class="col-xs-12 col-sm-5 wpinv-totals">
                    <?php wpinv_display_invoice_totals( $invoice_id ); ?>
                </div>
            </div>
        </div><!-- END row -->

        <?php  if ( $payments_info = wpinv_display_payments_info( $invoice_id, false ) ) { ?>
        <div class="row wpinv-payments">
            <div class="col-sm-12">
                <?php echo $payments_info; ?>
            </div>
        </div><!-- END row -->
        <?php } ?>
        
        <!-- ///// Start PDF footer -->
        <htmlpagefooter name="wpinv-pdf-footer">
            <div class="row wpinv-footer">
                <div class="col-sm-12">
                    <?php if ( $term_text = wpinv_get_terms_text() ) { ?>
                    <div class="terms-text"><?php echo wpautop( $term_text ); ?></div>
                    <?php } ?>
                    <div class="footer-text"><?php echo wpinv_get_business_footer(); ?></div>
                    <div class="print-only"><?php _e( 'Page ', 'invoicing' ) ?> {PAGENO}/{nbpg}</div>
                </div>
            </div><!-- END row -->
        </htmlpagefooter>
        <!-- End PDF footer ///// -->
    </div> <!-- END wpinv-wrap -->

<?php do_action( 'wpinv_footer' ); ?>
<?php do_action( 'wpinv_template_footer' ); ?>
</body>
</html>