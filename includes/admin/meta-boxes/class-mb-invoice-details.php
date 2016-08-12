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
<?php wp_nonce_field( 'wpinv_details', 'wpinv_details_nonce' ) ;?>
        <?php
    }
    
    public static function save( $post_id, $data, $post ) {
        $invoice = new WPInv_Invoice( $post_id );
        
        $curr_total     = (float)$invoice->get_total();
        
        $status         = sanitize_text_field( $data['wpinv_status'] );
        $number         = sanitize_text_field( $data['wpinv_number'] );
        //$tax            = (float)$data['wpinv_tax'];
        $discount       = (float)$data['wpinv_discount'];
        $discount_code  = sanitize_text_field( $data['wpinv_discount_code'] );
        $ip             = wpinv_get_ip();

        $invoice->set( 'status', $status );
        $invoice->set( 'number', $number );
        //$invoice->set( 'tax', $tax );
        $invoice->set( 'discount', $discount );
        $invoice->set( 'discount_code', $discount_code );
        $invoice->set( 'ip', $ip );
        
        // Check for payment notes
        if ( !empty( $data['invoice_note'] ) ) {
            $note               = wp_kses( $data['invoice_note'], array() );
            $note_type          = sanitize_text_field( $data['invoice_note_type'] );
            $is_customer_note   = $note_type == 'customer' ? 1 : 0;
        
            wpinv_insert_payment_note( $invoice->ID, $note, $is_customer_note );
        }
        $invoice->recalculate_total();
        $invoice->save();
    }
    
    public static function subscriptions( $post ) {
        global $wpi_mb_inboive;
        
        $invoice = $wpi_mb_inboive;
        
        if ( !empty( $invoice ) && $invoice->is_recurring() && !wpinv_is_subscription_payment( $invoice ) ) {
            $payments = $invoice->get_child_payments();
            
            ?>
            <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Subscription ID:</label> %s' ), $wpi_mb_inboive->get_subscription_id() ); ?></p>
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
            <?php }
        }
    }
    
    public static function renewals( $post ) {
        global $wpi_mb_inboive;
        
        if ( wpinv_is_subscription_payment( $wpi_mb_inboive ) ) {
            $parent_url = get_edit_post_link( $wpi_mb_inboive->parent_invoice );
            $parent_id  = wpinv_get_invoice_number( $wpi_mb_inboive->parent_invoice );
        ?>
        <p class="wpi-meta-row"><?php printf( __( '<label>Subscription ID:</label> %s', 'invoicing' ), $wpi_mb_inboive->get_subscription_id() ); ?></p>
        <p class="wpi-meta-row"><?php printf( __( '<label>Parent Invoice:</label> <a href="%s">%s</a>', 'invoicing' ), $parent_url, $parent_id ); ?></p>
        <?php
        }
    }
    
    public static function payment_meta( $post ) {
        global $wpi_mb_inboive;
        
        ?>
        <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Gateway:</label> %s', 'invoicing' ), wpinv_get_gateway_checkout_label( $wpi_mb_inboive->gateway ) ); ?></p>
        <?php if ( $wpi_mb_inboive->is_complete() ) { ?>
        <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Key:</label> %s', 'invoicing' ), $wpi_mb_inboive->get_key() ); ?></p>
        <p class="wpi-meta-row"><?php echo wp_sprintf( __( '<label>Transaction ID:</label> %s', 'invoicing' ), wpinv_payment_link_transaction_id( $wpi_mb_inboive ) ); ?></p>
        <?php } ?>
        <?php
    }
}
