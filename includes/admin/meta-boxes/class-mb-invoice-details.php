<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Meta_Box_Details {
    
    public static function resend_invoice( $post ) {
        global $wpi_mb_invoice;
        
        if ( empty( $wpi_mb_invoice ) ) {
            return;
        }
        
        $text = array(
            'message'       => esc_attr__( 'This will send a copy of the invoice to the customer&#8217;s email address.', 'invoicing' ),
            'button_text'   =>  __( 'Resend Invoice', 'invoicing' ),
        );
            
        $text = apply_filters('wpinv_resend_invoice_metabox_text', $text);
        do_action( 'wpinv_metabox_resend_invoice_before', $wpi_mb_invoice );

        if ( $email = $wpi_mb_invoice->get_email() ) {
            $email_actions = array();
            $email_actions['email_url']      = remove_query_arg( 'wpinv-message', add_query_arg( array( 'wpi_action' => 'send_invoice', 'invoice_id' => $post->ID ) ) );
            $email_actions['reminder_url']   = add_query_arg( array( 'wpi_action' => 'send_reminder', 'invoice_id' => $post->ID ) );
            
            $email_actions = apply_filters('wpinv_resend_invoice_email_actions', $email_actions );
        ?>
        <p class="wpi-meta-row wpi-resend-info"><?php echo $text['message']; ?></p>
        <p class="wpi-meta-row wpi-resend-email"><a href="<?php echo esc_url( $email_actions['email_url'] ); ?>" class="button button-secondary"><?php echo $text['button_text']; ?></a></p>
        <?php if ( wpinv_get_option( 'overdue_active' ) && "wpi_invoice" === $wpi_mb_invoice->post_type && $wpi_mb_invoice->needs_payment() && ( $due_date = $wpi_mb_invoice->get_due_date() ) ) { ?>
        <p class="wpi-meta-row wpi-send-reminder"><a title="<?php esc_attr_e( 'Send overdue reminder notification to customer', 'invoicing' ); ?>" href="<?php echo esc_url( $email_actions['reminder_url'] ); ?>" class="button button-secondary"><?php esc_attr_e( 'Send Reminder', 'invoicing' ); ?></a></p>
        <?php } ?>
        <?php
        }
        
        do_action( 'wpinv_metabox_resend_invoice_after', $wpi_mb_invoice );
    }
    
    public static function subscriptions( $post ) {
        $invoice = wpinv_get_invoice( $post->ID );

        if ( ! empty( $invoice ) && $invoice->is_recurring() && $invoice->is_parent() ) {
            $subscription = wpinv_get_subscription( $invoice );

            if ( empty( $subscription ) ) {
                ?>
                <p class="wpi-meta-row"><?php echo wp_sprintf( __( 'New Subscription will be created when customer will checkout and pay the invoice. Go to: %sSubscriptions%s', 'invoicing' ), '<a href="' . admin_url( 'admin.php?page=wpinv-subscriptions' ).'">', '</a>' ); ?></p>
                <?php
                return;
            }
            $frequency = WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency( $subscription->period, $subscription->frequency );
            $billing = wpinv_price(wpinv_format_amount( $subscription->recurring_amount ), wpinv_get_invoice_currency_code( $subscription->parent_payment_id ) ) . ' / ' . $frequency;
            $initial = wpinv_price(wpinv_format_amount( $subscription->initial_amount ), wpinv_get_invoice_currency_code( $subscription->parent_payment_id ) );
            $payments = $subscription->get_child_payments();
            ?>
            <p class="wpi-meta-row wpi-sub-label <?php echo 'status-' . $subscription->status; ?>"><?php _e('Recurring Payment', 'invoicing'); ?></p>
            <?php if ( ! empty( $subscription ) && ! empty( $subscription->id ) ) { ?>
                <p class="wpi-meta-row wpi-sub-id">
                    <label><?php _e( 'Subscription ID:', 'invoicing' ); ?> </label><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpinv-subscriptions&id=' . $subscription->id ) ); ?>" title="<?php esc_attr_e( 'View or edit subscription', 'invoicing' ); ?>" target="_blank"><?php echo $subscription->id; ?></a></p>
            <?php } ?>
            <p class="wpi-meta-row wpi-bill-cycle">
                <label><?php _e( 'Billing Cycle:', 'invoicing'); ?> </label><?php printf( _x( '%s then %s', 'Initial subscription amount then billing cycle and amount', 'invoicing' ), $initial, $billing ); ?>
            </p>
            <p class="wpi-meta-row wpi-billed-times">
                <label><?php _e( 'Times Billed:', 'invoicing' ); ?> </label><?php echo $subscription->get_times_billed() . ' / ' . ( ( $subscription->bill_times == 0 ) ? 'Until Cancelled' : $subscription->bill_times ); ?>
            </p>
            <p class="wpi-meta-row wpi-start-date">
                <label><?php _e( 'Start Date:', 'invoicing' ); ?> </label><?php echo date_i18n( get_option( 'date_format' ), strtotime( $subscription->created, current_time( 'timestamp' ) ) ); ?>
            </p>
            <p class="wpi-meta-row wpi-end-date">
                <label><?php echo ( 'trialling' == $subscription->status ? __( 'Trialling Until:', 'invoicing' ) : __( 'Expiration Date:', 'invoicing' ) ); ?> </label><?php echo date_i18n( get_option( 'date_format' ), strtotime( $subscription->expiration, current_time( 'timestamp' ) ) ); ?>
            </p>
            <?php if ( $subscription->status ) { ?>
                <p class="wpi-meta-row wpi-sub-status">
                    <label><?php _e( 'Subscription Status:', 'invoicing'); ?> </label><?php echo $subscription->get_status_label(); ?>
                </p>
            <?php } ?>
            <?php if ( !empty( $payments ) ) { ?>
                <p><strong><?php _e( 'Renewal Payments:', 'invoicing' ); ?></strong></p>
                <ul id="wpi-sub-payments">
                <?php foreach ( $payments as $payment ) {
                    $invoice_id = $payment->ID;
                    ?>
                    <li>
                        <a href="<?php echo esc_url( get_edit_post_link( $invoice_id ) ); ?>"><?php echo wpinv_get_invoice_number( $invoice_id ); ?></a>&nbsp;&ndash;&nbsp;
                        <span><?php echo wpinv_get_invoice_date( $invoice_id ); ?>&nbsp;&ndash;&nbsp;</span>
                        <span><?php echo wpinv_payment_total( $invoice_id, true ); ?></span>
                    </li>
                <?php } ?>
                </ul>
            <?php }
        }
    }
    
    public static function renewals( $post ) {
        $invoice = wpinv_get_invoice( $post->ID );
        
        if ( wpinv_is_subscription_payment( $invoice ) ) {
            $parent_url = get_edit_post_link( $invoice->parent_invoice );
            $parent_id  = wpinv_get_invoice_number( $invoice->parent_invoice );
            $subscription = wpinv_get_subscription( $invoice );
        ?>
        <?php if ( ! empty( $subscription ) ) { ?><p class="wpi-meta-row wpi-sub-id"><label><?php _e('Subscription ID:', 'invoicing'); ?> </label><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpinv-subscriptions&id=' . $subscription->id ) ); ?>" title="<?php esc_attr_e( 'View or edit subscription', 'invoicing' ); ?>" target="_blank"><?php echo $subscription->id; ?></a></p><?php } ?>
        <p class="wpi-meta-row wpi-parent-id"><label><?php _e( 'Parent Invoice:', 'invoicing' );?> </label><a href="<?php echo esc_url( $parent_url ); ?>"><?php echo $parent_id; ?></a></p>
        <?php
        }
    }

}
