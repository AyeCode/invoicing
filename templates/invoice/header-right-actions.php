<?php
/**
 * Displays right side of the invoice header.
 *
 * This template can be overridden by copying it to yourtheme/invoicing/invoice/header-right-actions.php.
 *
 * @version 1.0.19
 * @var WPInv_Invoice $invoice
 */

defined( 'ABSPATH' ) || exit;

?>

    <div class="getpaid-header-right-actions">

        <?php

            $actions   = array();

            $actions[] = sprintf(
                '<a href="javascript:void(0)" class="btn btn-sm m-1 d-inline-block btn-secondary invoice-action-print d-none d-lg-inline-block" onclick="window.print();">%s</a>',
                sprintf(
                    __( 'Print %s', 'invoicing' ),
                    ucfirst( $invoice->get_invoice_quote_type() )
                )
            );

            if ( is_user_logged_in() ) {

                $actions[] = sprintf(
                    '<a href="%s" class="btn btn-sm btn-secondary m-1 d-inline-block invoice-action-history">%s</a>',
                    esc_url( wpinv_get_history_page_uri( $invoice->get_post_type() ) ),
                    sprintf(
                        __( '%s History', 'invoicing' ),
                        ucfirst( $invoice->get_invoice_quote_type() )
                    )
                );

            }

            if ( wpinv_current_user_can_manage_invoicing() ) {

                $actions[] = sprintf(
                    '<a href="%s" class="btn btn-sm btn-secondary m-1 d-inline-block invoice-action-edit">%s</a>',
                    esc_url( get_edit_post_link( $invoice->get_id() ) ),
                    sprintf(
                        __( 'Edit %s', 'invoicing' ),
                        ucfirst( $invoice->get_invoice_quote_type() )
                    )
                );

            }

            $actions = apply_filters( 'getpaid_invoice_header_right_actions_array', $actions, $invoice );
            echo implode( '', $actions );

        ?>

        <?php do_action('wpinv_invoice_display_right_actions', $invoice ); ?>
    </div>

<?php
