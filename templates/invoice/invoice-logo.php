<?php
/**
 * Displays left side of the invoice title.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/invoice-logo.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

$logo_width  = wpinv_get_option( 'logo_width' );
$logo_height = wpinv_get_option( 'logo_height' );

?>
<a target="_blank" class="logo-link text-dark" href="<?php echo esc_url( wpinv_get_business_website() ); ?>">

    <?php if ( $logo = wpinv_get_business_logo() ) : ?>

        <?php if ( ! empty( $logo_width ) && ! empty( $logo_height ) ) : ?>
            <img class="logo" style="max-width:100%; width:<?php echo absint( $logo_width ); ?>px; height:<?php echo absint( $logo_height ); ?>px;" src="<?php echo esc_url( $logo ); ?>">
        <?php else: ?>
            <img class="logo" style="max-width:100%;" src="<?php echo esc_url( $logo ); ?>">
        <?php endif; ?>

    <?php else: ?>
        <h1 class="h3"><?php echo esc_html( wpinv_get_business_name() ); ?></h1>
    <?php endif; ?>

</a>
<?php
