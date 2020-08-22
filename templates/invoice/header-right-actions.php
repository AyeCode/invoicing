<?php
/**
 * Displays right side of the invoice header.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/header-right-actions.php.
 *
 * @version 1.0.19
 */

defined( 'ABSPATH' ) || exit;

?>

    <div class="getpaid-header-right-actions">

        <?php if ( $invoice->is_type( 'invoice' ) ) : ?>

            <a class="btn btn-secondary invoice-action-print" onclick="window.print();" href="javascript:void(0)">
                <?php _e( 'Print Invoice', 'invoicing' ); ?>
            </a>

            <?php if ( is_user_logged_in() ) : ?>
                &nbsp;&nbsp;
                <a class="btn btn-secondary invoice-action-history" href="<?php echo esc_url( wpinv_get_history_page_uri() ); ?>">
                    <?php _e( 'Invoice History', 'invoicing' ); ?>
                </a>
            <?php endif; ?>

        <?php endif; ?>

        <?php do_action('wpinv_invoice_display_right_actions', $invoice ); ?>
    </div>

<?php
