<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Meta_Box_Details {
    public static function output( $post ) {
        $currency_symbol    = wpinv_currency_symbol();
        $statuses           = wpinv_get_invoice_statuses();
        
        $post_id            = !empty( $post->ID ) ? $post->ID : 0;
        $invoice            = new WPInv_Invoice( $post_id );
        
        $status             = $invoice->get_status( false ); // Current status    
        $discount           = $invoice->get_discount();
        $discount_code      = $discount > 0 ? $invoice->get_discount_code() : '';
        $invoice_number     = $invoice->get_number();
        
        $date_created       = $invoice->get_created_date();
        $datetime_created   = strtotime( $date_created );
        $date_created       = $date_created != '' && $date_created != '0000-00-00 00:00:00' ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $datetime_created ) : '';
        $date_completed     = $invoice->get_completed_date();
        $date_completed     = $date_completed != '' && $date_completed != '0000-00-00 00:00:00' ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date_completed ) ) : 'n/a';
        $title['status'] = __( 'Invoice Status:', 'invoicing' );
        $title['number'] = __( 'Invoice Number:', 'invoicing' );
        $mail_notice = esc_attr__( 'After saving invoice, this will send a copy of the invoice to the user&#8217;s email address.', 'invoicing' );
        
        $title = apply_filters('wpinv_details_metabox_titles', $title, $invoice);
        $statuses = apply_filters('wpinv_invoice_statuses', $statuses, $invoice);
        $mail_notice = apply_filters('wpinv_metabox_mail_notice', $mail_notice, $invoice);
        $post_obj = get_post_type_object($invoice->post_type);
        ?>
<div class="gdmbx2-wrap form-table">
    <div class="gdmbx2-metabox gdmbx-field-list" id="gdmbx2-metabox-wpinv_details">
        <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-date-created">
            <div class="gdmbx-th"><label><?php _e( 'Date Created:', 'invoicing' );?></label></div>
            <div class="gdmbx-td"><?php echo $date_created;?></div>
        </div>
        <?php if ( wpinv_get_option( 'overdue_active' ) && ( $invoice->needs_payment() || $invoice->has_status( array( 'auto-draft' ) ) ) ) { ?>
        <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-date-overdue">
            <div class="gdmbx-th"><label for="wpinv_due_date"><?php _e( 'Due Date:', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" placeholder="<?php esc_attr_e( 'Y-m-d', 'invoicing' );?>" value="<?php echo esc_attr( $invoice->get_due_date() );?>" id="wpinv_due_date" name="wpinv_due_date" class="regular-text wpiDatepicker" data-minDate="<?php echo esc_attr( date_i18n( 'Y-m-d', $datetime_created ) );?>" data-dateFormat="yy-mm-dd">
            </div>
        </div>
        <?php } ?>
        <?php if ( $date_completed && $date_completed != 'n/a' ) { ?>
        <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-date-completed">
            <div class="gdmbx-th"><label><?php _e( 'Payment Date:', 'invoicing' );?></label></div>
            <div class="gdmbx-td"><?php echo $date_completed;?></div>
        </div>
        <?php } ?>
        <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-status">
            <div class="gdmbx-th"><label for="wpinv_status"><?php echo $title['status']; ?></label></div>
            <div class="gdmbx-td">
                <select required="required" id="wpinv_status" name="wpinv_status" class="gdmbx2_select">
                    <?php foreach ( $statuses as $value => $label ) { ?>
                    <option value="<?php echo $value;?>" <?php selected( $status, $value );?>><?php echo $label;?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx2-id-wpinv-number table-layout">
            <div class="gdmbx-th"><label for="wpinv_number"><?php echo $title['number']; ?></label></div>
            <div class="gdmbx-td">
                <input type="text" value="<?php echo esc_attr( $invoice_number );?>" id="wpinv_number" name="wpinv_number" class="regular-text" readonly>
            </div>
        </div>
        <?php do_action( 'wpinv_meta_box_details_inner', $post_id ); ?>
        <?php if ( !( $is_paid = ( $invoice->is_paid() || $invoice->is_refunded() ) ) || $discount_code ) { ?>
        <div class="gdmbx-row gdmbx-type-text gdmbx2-id-wpinv-discount-code table-layout">
            <div class="gdmbx-th"><label for="wpinv_discount_code"><?php _e( 'Discount Code:', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" value="<?php echo esc_attr( $discount_code ); ?>" id="wpinv_discount" class="medium-text" <?php echo ( $discount_code ? 'readonly' : '' ); ?> /><?php if ( !$is_paid ) { ?><input value="<?php echo esc_attr_e( 'Apply', 'invoicing' ); ?>" class="button button-small button-primary <?php echo ( $discount_code ? 'wpi-hide' : 'wpi-inlineb' ); ?>" id="wpinv-apply-code" type="button" /><input value="<?php echo esc_attr_e( 'Remove', 'invoicing' ); ?>" class="button button-small button-primary <?php echo ( $discount_code ? 'wpi-inlineb' : 'wpi-hide' ); ?>" id="wpinv-remove-code" type="button" /><?php } ?>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-save-send table-layout">
    <p class="wpi-meta-row wpi-save-send"><label for="wpi_save_send"><?php echo sprintf(__( 'Send %s:', 'invoicing' ),$post_obj->labels->singular_name) ; ?></label>
        <select id="wpi_save_send" name="wpi_save_send">
            <option value="1"><?php _e( 'Yes', 'invoicing' ); ?></option>
            <option value="" selected="selected"><?php _e( 'No', 'invoicing' ); ?></option>
        </select>
    </p>
    <p class="wpi-meta-row wpi-send-info"><?php echo $mail_notice; ?></p>
</div>
<?php wp_nonce_field( 'wpinv_details', 'wpinv_details_nonce' ) ;?>
        <?php
    }
    
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
            $email_actions['email_url']      = add_query_arg( array( 'wpi_action' => 'send_invoice', 'invoice_id' => $post->ID ) );
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
        global $wpi_mb_invoice;
        
        $invoice = $wpi_mb_invoice;
        
        if ( !empty( $invoice ) && $invoice->is_recurring() && $invoice->is_parent() ) {
            $payments       = $invoice->get_child_payments();
            
            $total_payments = (int)$invoice->get_total_payments();
            $subscription   = $invoice->get_subscription_data();

            $billing_cycle  = wpinv_get_billing_cycle( $subscription['initial_amount'], $subscription['recurring_amount'], $subscription['period'], $subscription['interval'], $subscription['bill_times'], $subscription['trial_period'], $subscription['trial_interval'], $invoice->get_currency() );
            $times_billed   = $total_payments . ' / ' . ( ( (int)$subscription['bill_times'] == 0 ) ? __( 'Until cancelled', 'invoicing' ) : $subscription['bill_times'] );
            $subscription_status = $invoice->get_subscription_status();
            ?>
            <p class="wpi-meta-row wpi-sub-label"><?php _e( 'Recurring Payment', 'invoicing' );?></p>
            <?php if ( $subscription_id = $invoice->get_subscription_id() ) { ?>
            <p class="wpi-meta-row wpi-sub-id"><label><?php _e( 'Subscription ID:', 'invoicing' );?> </label><?php echo $subscription_id; ?></p>
            <?php } ?>
            <p class="wpi-meta-row wpi-bill-cycle"><label><?php _e( 'Billing Cycle:', 'invoicing' );?> </label><?php echo $billing_cycle; ?></p>
            <p class="wpi-meta-row wpi-billed-times"><label><?php _e( 'Times Billed:', 'invoicing' );?> </label><?php echo $times_billed; ?></p>
            <?php if ( !empty( $payments ) || $invoice->is_paid() ) { ?>
                <p class="wpi-meta-row wpi-start-date"><label><?php _e( 'Start Date:', 'invoicing' );?> </label><?php echo $invoice->get_subscription_start(); ?></p>
                <p class="wpi-meta-row wpi-end-date"><label><?php _e( 'Expiration Date:', 'invoicing' );?> </label><?php echo $invoice->get_subscription_end(); ?></p>
                <?php if ( $status_label = $invoice->get_subscription_status_label( $subscription_status ) ) { ?>
                <p class="wpi-meta-row wpi-sub-status"><label><?php _e( 'Subscription Status:', 'invoicing' );?> </label><?php echo $status_label; ?></p>
                <?php } ?>
                <?php if ( $subscription_status == 'trialing' && $trial_end_date = $invoice->get_trial_end_date() ) { ?>
                <p class="wpi-meta-row wpi-trial-date"><label><?php _e( 'Trial Until:', 'invoicing' );?> </label><?php echo $trial_end_date; ?></p>
                <?php } ?>
                <?php if ( $cancelled_date = $invoice->get_cancelled_date() ) { ?>
                <p class="wpi-meta-row wpi-cancel-date"><label><?php _e( 'Cancelled On:', 'invoicing' );?> </label><?php echo $cancelled_date; ?></p>
                <?php } ?>
                <?php if ( !empty( $payments ) ) { ?>
                <p><strong><?php _e( 'Renewal Payments:', 'invoicing' ); ?></strong></p>
                <ul id="wpi-sub-payments">
                <?php foreach ( $payments as $invoice_id ) { ?>
                    <li>
                        <a href="<?php echo esc_url( get_edit_post_link( $invoice_id ) ); ?>"><?php echo wpinv_get_invoice_number( $invoice_id ); ?></a>&nbsp;&ndash;&nbsp;
                        <span><?php echo wpinv_get_invoice_date( $invoice_id ); ?>&nbsp;&ndash;&nbsp;</span>
                        <span><?php echo wpinv_payment_total( $invoice_id, true ); ?></span>
                    </li>
                <?php } ?>
                </ul>
            <?php } }
        }
    }
    
    public static function renewals( $post ) {
        global $wpi_mb_invoice;
        
        if ( wpinv_is_subscription_payment( $wpi_mb_invoice ) ) {
            $parent_url = get_edit_post_link( $wpi_mb_invoice->parent_invoice );
            $parent_id  = wpinv_get_invoice_number( $wpi_mb_invoice->parent_invoice );
        ?>
        <p class="wpi-meta-row wpi-sub-id"><label><?php _e( 'Subscription ID:', 'invoicing' );?> </label><?php echo $wpi_mb_invoice->get_subscription_id(); ?></p>
        <p class="wpi-meta-row wpi-parent-id"><label><?php _e( 'Parent Invoice:', 'invoicing' );?> </label><a href="<?php echo esc_url( $parent_url ); ?>"><?php echo $parent_id; ?></a></p>
        <?php
        }
    }
    
    public static function payment_meta( $post ) {
        global $wpi_mb_invoice;

        $set_dateway = empty( $wpi_mb_invoice->gateway ) ? true : false;
        if ( !$set_dateway && !$wpi_mb_invoice->get_meta( '_wpinv_checkout', true ) && !$wpi_mb_invoice->is_paid() && !$wpi_mb_invoice->is_refunded() ) {
            $set_dateway = true;
        }
        
        ?>
        <p class="wpi-meta-row">
        <?php if ( $set_dateway ) { $gateways = wpinv_get_enabled_payment_gateways( true ); ?>
            <label for="wpinv_gateway"><?php _e( 'Gateway:', 'invoicing' ) ; ?></label>
            <select required="required" id="wpinv_gateway" name="wpinv_gateway">
                <?php foreach ( $gateways as $name => $gateway ) {
                    if ( $wpi_mb_invoice->is_recurring() && !wpinv_gateway_support_subscription( $name ) ) {
                        continue;
                    }
                    ?>
                <option value="<?php echo $name;?>" <?php selected( $wpi_mb_invoice->gateway, $name );?>><?php echo !empty( $gateway['admin_label'] ) ? $gateway['admin_label'] : $gateway['checkout_label']; ?></option>
                <?php } ?>
            </select>
        <?php } else { 
            echo wp_sprintf( __( '<label>Gateway:</label> %s', 'invoicing' ), wpinv_get_gateway_checkout_label( $wpi_mb_invoice->gateway ) );
        } ?>
        </p>
        <?php if ( $key = $wpi_mb_invoice->get_key() ) { ?>
        <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Key:</label> %s', 'invoicing' ), $key ); ?></p>
        <?php } ?>
        <?php if ( $wpi_mb_invoice->is_paid() || $wpi_mb_invoice->is_refunded() ) { ?>
        <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Transaction ID:</label> %s', 'invoicing' ), wpinv_payment_link_transaction_id( $wpi_mb_invoice ) ); ?></p>
        <?php } ?>
        <?php
    }
}
