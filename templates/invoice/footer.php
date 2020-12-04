<?php
/**
 * Displays the invoice footer.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/footer.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

    <div class="border-top pt-4 bg-white">
        <div class="container pr-0 pl-0">

            <?php if ( $term_text = wpinv_get_terms_text() ) : ?>
                <div class="terms-text">
                    <?php echo wpautop( $term_text ); ?>
                </div>
            <?php endif; ?>

            <div class="footer-text d-print-none">
                <?php echo wpinv_get_business_footer(); ?>
            </div>

        </div>
    </div>

<?php
