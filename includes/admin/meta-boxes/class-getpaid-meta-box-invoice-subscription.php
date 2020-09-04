<?php
/**
 * Invoice Subscription Details
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * GetPaid_Meta_Box_Invoice_Subscription Class.
 */
class GetPaid_Meta_Box_Invoice_Subscription {

    /**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
    public static function output( $post ) {

        //Fetch the invoice.
        $invoice = new WPInv_Invoice( $post );

        // Ensure that it is recurring.
        if ( ! $invoice->is_recurring() ) {
            return;
        }

        // Fetch the subscription.
        $subscription = wpinv_get_subscription( $invoice );

        ?>
            <?php if ( empty( $subscription ) ): ?>
                <p class="wpi-meta-row">
                    <?php
                        echo
                            wp_sprintf(
                                __( 'A new subscription will be created when the customer checks out and pays the invoice. %sView all subscriptions%s', 'invoicing' ),
                                '<a href="' . admin_url( 'admin.php?page=wpinv-subscriptions' ).'">',
                                '</a>'
                            );
                    ?>
                </p>
            <?php
                return; // If no subscription.
                endif;

                $frequency = WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency( $subscription->period, $subscription->frequency );
                $billing   = wpinv_price(wpinv_format_amount( $subscription->recurring_amount ), wpinv_get_invoice_currency_code( $subscription->parent_payment_id ) ) . ' / ' . $frequency;
                $initial   = wpinv_price(wpinv_format_amount( $subscription->initial_amount ), wpinv_get_invoice_currency_code( $subscription->parent_payment_id ) );
                $exipired  = strtotime( $subscription->expiration, current_time( 'timestamp' ) ) < current_time( 'timestamp' );
            ?>

            <p class="wpi-meta-row wpi-sub-label <?php echo 'status-' . esc_attr( $subscription->status ); ?>">
                <?php echo $invoice->is_renewal() ? _e('Renewal Invoice', 'invoicing') : _e('Recurring Invoice', 'invoicing'); ?>
            </p>

            <?php if ( ! empty( $subscription->id ) ) : ?>
                <p class="wpi-meta-row wpi-sub-id">
                    <label><?php _e( 'Subscription ID:', 'invoicing' ); ?></label>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpinv-subscriptions&id=' . $subscription->id ) ); ?>" title="<?php esc_attr_e( 'View or edit subscription', 'invoicing' ); ?>" target="_blank"><?php echo $subscription->id; ?></a>
                </p>
            <?php endif; ?>

            <p class="wpi-meta-row wpi-bill-cycle">
                <label><?php _e( 'Billing Cycle:', 'invoicing'); ?> </label>
                <?php

                    if ( $subscription->recurring_amount != $subscription->initial_amount ) {
                        printf(
                            _x( '%s then %s', 'Initial subscription amount then billing cycle and amount', 'invoicing' ),
                            $initial,
                            $billing
                        );
                    } else {
                        echo $billing;
                    }

                ?>
            </p>

            <p class="wpi-meta-row wpi-billed-times">
                <label><?php _e( 'Times Billed:', 'invoicing' ); ?></label>
                <?php echo $subscription->get_times_billed() . ' / ' . ( ( $subscription->bill_times == 0 ) ? __( 'Until Cancelled', 'invoicing' ) : $subscription->bill_times ); ?>
            </p>

            <p class="wpi-meta-row wpi-start-date">
                <label><?php _e( 'Start Date:', 'invoicing' ); ?></label>
                <?php echo date_i18n( get_option( 'date_format' ), strtotime( $subscription->created, current_time( 'timestamp' ) ) ); ?>
            </p>

            <p class="wpi-meta-row wpi-end-date">
                <label><?php echo $exipired ? __( 'Expired On:', 'invoicing' ) : __( 'Renews On:', 'invoicing' ); ?></label>
                <?php echo date_i18n( get_option( 'date_format' ), strtotime( $subscription->expiration, current_time( 'timestamp' ) ) ); ?>
            </p>

            <?php if ( $subscription->status ) { ?>
                <p class="wpi-meta-row wpi-sub-status">
                    <label><?php _e( 'Subscription Status:', 'invoicing'); ?> </label><?php echo $subscription->get_status_label(); ?>
                </p>
            <?php } ?>

            <?php if ( $invoice->is_renewal() ) { ?>
                <p class="wpi-meta-row wpi-invoice-parent">
                    <label><?php _e( 'Parent Invoice:', 'invoicing'); ?></label>
                    <?php
                        $parent = $invoice->get_parent_payment();

                        if ( $parent->get_id() ) {
                            $parent_url = esc_url( get_edit_post_link( $parent->get_id() ) );
                            $parent_id  = $parent->get_number();
                            echo "<a href='$parent_url'>$parent_id</a>";
                        } else {
                            echo '<del>' . __( 'Deleted', 'invoicing' ) . '</del>';
                        }
                    ?>

                </p>
            <?php } ?>

        <?php

    }

}
