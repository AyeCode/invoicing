<?php
/**
 * Displays left side of the invoice title.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/title-left.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>
<a target="_blank" href="<?php echo esc_url( wpinv_get_business_website() ); ?>">

    <?php if ( $logo = wpinv_get_business_logo() ) { ?>
        <img class="logo" style="max-width:100%;" src="<?php echo esc_url( $logo ); ?>">
    <?php } else { ?>
        <h1><?php echo esc_html( wpinv_get_business_name() ); ?></h1>
    <?php } ?>

</a>
<?php
