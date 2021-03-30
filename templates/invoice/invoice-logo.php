<?php
/**
 * Displays left side of the invoice title.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/invoice-logo.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
<a target="_blank" class="logo-link text-dark" href="<?php echo esc_url( wpinv_get_business_website() ); ?>">

    <?php if ( $logo = wpinv_get_business_logo() ) {
        $logo_width = wpinv_get_option( 'logo_width' );
        $logo_height = wpinv_get_option( 'logo_height' );
        if ( ! empty( $logo_width ) && ! empty( $logo_height ) ) {
            echo sprintf(
               '<img class="logo" style="width:%spx; height:%spx" src="%s"/>',
               esc_attr( $logo_width ),
               esc_attr( $logo_height ),
               esc_url( $logo )
            );
        } else {
        ?>
        <img class="logo" style="max-width:100%;" src="<?php echo esc_url( $logo ); ?>">
    <?php } } else { ?>
        <h1 class="h3"><?php echo esc_html( wpinv_get_business_name() ); ?></h1>
    <?php } ?>

</a>
<?php
