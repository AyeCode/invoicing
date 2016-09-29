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
        $tax                = $invoice->get_tax();
        $discount           = $invoice->get_discount();
        $discount_code      = $discount > 0 ? $invoice->get_discount_code() : '';
        $invoice_number     = $invoice->get_number();
        
        $tax                = $tax > 0 ? wpinv_format_amount( $tax ) : '';
        $discount           = $discount > 0 ? wpinv_format_amount( $discount ) : '';
        $date_created       = $invoice->get_created_date();
        $date_created       = $date_created != '' && $date_created != '0000-00-00 00:00:00' ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date_created ) ) : '';
        $date_completed     = $invoice->get_completed_date();
        $date_completed     = $date_completed != '' && $date_completed != '0000-00-00 00:00:00' ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date_completed ) ) : 'n/a';
        ?>
<div class="gdmbx2-wrap form-table">
    <div class="gdmbx2-metabox gdmbx-field-list" id="gdmbx2-metabox-wpinv_details">
        <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-date-created">
            <div class="gdmbx-th"><label><?php _e( 'Date Created:', 'invoicing' );?></label></div>
            <div class="gdmbx-td"><?php echo $date_created;?></div>
        </div>
        <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-date-completed">
            <div class="gdmbx-th"><label><?php _e( 'Date Completed:', 'invoicing' );?></label></div>
            <div class="gdmbx-td"><?php echo $date_completed;?></div>
        </div>
        <div class="gdmbx-row gdmbx-type-select gdmbx2-id-wpinv-status">
            <div class="gdmbx-th"><label for="wpinv_status"><?php _e( 'Invoice Status:', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <select required="required" id="wpinv_status" name="wpinv_status" class="gdmbx2_select">
                    <?php foreach ( $statuses as $value => $label ) { ?>
                    <option value="<?php echo $value;?>" <?php selected( $status, $value );?>><?php echo $label;?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx2-id-wpinv-number table-layout">
            <div class="gdmbx-th"><label for="wpinv_number"><?php _e( 'Invoice Number:', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" placeholder="<?php esc_attr_e( 'WPINV-0001', 'invoicing' );?>" value="<?php echo esc_attr( $invoice_number );?>" id="wpinv_number" name="wpinv_number" class="regular-text">
            </div>
        </div>
        <?php do_action( 'wpinv_meta_box_details_inner', $post_id ); ?>
        <div class="gdmbx-row gdmbx-type-text gdmbx2-id-wpinv-discount-code table-layout">
            <div class="gdmbx-th"><label for="wpinv_discount_code"><?php _e( 'Discount Code:', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" maxlength="20" value="<?php echo esc_attr( $discount_code );?>" id="wpinv_discount_code" name="wpinv_discount_code" class="regular-text">
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx2-id-wpinv-discount table-layout">
            <div class="gdmbx-th"><label for="wpinv_dicount"><?php echo wp_sprintf( __( 'Discount (%s):', 'invoicing' ), $currency_symbol );?></label></div>
            <div class="gdmbx-td">
                <input type="text" maxlength="12" placeholder="0.00" value="<?php echo $discount;?>" id="wpinv_discount" name="wpinv_discount" class="regular-text wpi-price">
            </div>
        </div>
        <?php /* ?>
        <div class="gdmbx-row gdmbx-type-text gdmbx2-id-wpinv-tax table-layout">
            <div class="gdmbx-th"><label for="wpinv_tax"><?php echo wp_sprintf( __( 'Tax (%s):', 'invoicing' ), $currency_symbol );?></label></div>
            <div class="gdmbx-td">
                <input type="text" maxlength="12" placeholder="0.00" value="<?php echo $tax;?>" id="wpinv_tax" name="wpinv_tax" class="regular-text">
            </div>
        </div>
        <?php */ ?>
    </div>
</div>
<div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-save-send table-layout">
    <p class="wpi-meta-row wpi-save-send"><label for="wpi_save_send"><?php _e( 'Send Invoice:', 'invoicing' ); ?></label>
        <select id="wpi_save_send" name="wpi_save_send">
            <option value="1"><?php _e( 'Yes', 'invoicing' ); ?></option>
            <option value="" selected="selected"><?php _e( 'No', 'invoicing' ); ?></option>
        </select>
    </p>
    <p class="wpi-meta-row wpi-send-info"><?php esc_attr_e( 'After save invoice this will send a copy of the invoice to the user&#8217;s email address.', 'invoicing' ); ?></p>
</div>
<?php wp_nonce_field( 'wpinv_details', 'wpinv_details_nonce' ) ;?>
        <?php
    }
    
    public static function resend_invoice( $post ) {
        global $wpi_mb_invoice;
        
        if ( empty( $wpi_mb_invoice ) ) {
            return;
        }
        
        do_action( 'wpinv_metabox_resend_invoice_before', $wpi_mb_invoice );
        
        if ( $email = $wpi_mb_invoice->get_email() ) {
            $email_url = add_query_arg( array( 'wpi_action' => 'send_invoice', 'invoice_id' => $post->ID ) );
        ?>
        <p class="wpi-meta-row wpi-resend-info"><?php esc_attr_e( 'This will send a copy of the invoice to the user&#8217;s email address.', 'invoicing' ); ?></p>
        <p class="wpi-meta-row wpi-resend-email"><a title="<?php esc_attr_e( 'Send invoice to customer', 'invoicing' ); ?>" href="<?php echo esc_url( $email_url ); ?>" class="button button-secondary"><?php esc_attr_e( 'Resend Invoice', 'invoicing' ); ?></a></p>
        <?php
        }
        
        do_action( 'wpinv_metabox_resend_invoice_after', $wpi_mb_invoice );
    }
    
    public static function subscriptions( $post ) {
        global $wpi_mb_invoice;
        
        $invoice = $wpi_mb_invoice;
        
        if ( !empty( $invoice ) && $invoice->is_recurring() && !wpinv_is_subscription_payment( $invoice ) ) {
            $payments = $invoice->get_child_payments();
            
            $total_payments = (int)$invoice->get_total_payments();
            $bill_times     = (int)$invoice->get_bill_times();
            
            $subscription   = $invoice->get_subscription_data();
            $period         = wpinv_get_pretty_subscription_period( $subscription['period'] );
            $billing        = wpinv_price( wpinv_format_amount( $subscription['recurring_amount'] ), $invoice->get_currency() ) . ' / ' . $period;
            $initial        = wpinv_price( wpinv_format_amount( $subscription['initial_amount'] ), $invoice->get_currency() );
            
            if ( $billing != $billing ) {
                $billing_cycle  = wp_sprintf( _x( '%s then %s', 'Inital subscription amount then billing cycle and amount', 'invoicing' ), $initial, $billing );
            } else {
                $billing_cycle  = $billing;
            }
            $times_billed   = $total_payments . ' / ' . ( ( $bill_times == 0 ) ? __( 'Until cancelled', 'invoicing' ) : $bill_times );
            ?>
            <p class="wpi-meta-row wpi-sub-id"><label><?php _e( 'Subscription ID:', 'invoicing' );?> </label><?php echo $wpi_mb_invoice->get_subscription_id(); ?></p>
            <?php if ( !empty( $payments ) ) { ?>
                <p class="wpi-meta-row wpi-bill-cycle"><label><?php _e( 'Billing Cycle:', 'invoicing' );?> </label><?php echo $billing_cycle; ?></p>
                <p class="wpi-meta-row wpi-billed-times"><label><?php _e( 'Times Billed:', 'invoicing' );?> </label><?php echo $times_billed; ?></p>
                <p class="wpi-meta-row wpi-start-date"><label><?php _e( 'Start Date:', 'invoicing' );?> </label><?php echo $invoice->get_subscription_start(); ?></p>
                <p class="wpi-meta-row wpi-end-date"><label><?php _e( 'Expiration Date:', 'invoicing' );?> </label><?php echo $invoice->get_subscription_end(); ?></p>
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
            <?php }
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
        
        ?>
        <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Gateway:</label> %s', 'invoicing' ), wpinv_get_gateway_checkout_label( $wpi_mb_invoice->gateway ) ); ?></p>
        <?php if ( $wpi_mb_invoice->is_complete() ) { ?>
        <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Key:</label> %s', 'invoicing' ), $wpi_mb_invoice->get_key() ); ?></p>
        <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Transaction ID:</label> %s', 'invoicing' ), wpinv_payment_link_transaction_id( $wpi_mb_invoice ) ); ?></p>
        <?php } ?>
        <?php
    }
}
