<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Meta_Box_Billing_Details {
    public static function output( $post ) {
        global $user_ID;
        $post_id    = !empty( $post->ID ) ? $post->ID : 0;
        $invoice    = new WPInv_Invoice( $post_id );
?>
<div class="gdmbx2-wrap form-table">
    <div id="gdmbx2-metabox-wpinv_address" class="gdmbx2-metabox gdmbx-field-list wpinv-address gdmbx-row">
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-user-id table-layout">
            <div class="gdmbx-th">
                <label for="post_author_override"><?php _e( 'Customer', 'invoicing' );?></label>
            </div>
            <div class="gdmbx-td gdmbx-customer-div">
            <?php wpinv_dropdown_users( array(
                            'name' => 'post_author_override',
                            'selected' => empty($post->ID) ? $user_ID : $post->post_author,
                            'include_selected' => true,
                            'show' => 'user_email',
                            'orderby' => 'user_email',
                            'class' => 'gdmbx2-text-large'
                        ) ); ?>
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-email table-layout" style="display:none">
            <div class="gdmbx-th"><label for="wpinv_email"><?php _e( 'Email', 'invoicing' );?> <span class="required">*</span></label>
            </div>
            <div class="gdmbx-td">
                <input type="hidden" id="wpinv_new_user" name="wpinv_new_user" value="" />
                <input type="email" class="gdmbx2-text-large" name="wpinv_email" id="wpinv_email" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-btns table-layout">
            <div class="gdmbx-th"><label><?php _e( 'Actions', 'invoicing' );?></label>
            </div>
            <?php if($invoice->has_status(array('auto-draft', 'wpi-pending', 'wpi-quote-pending'))){ ?>
                <div class="gdmbx-td">
                    <a id="wpinv-fill-user-details" class="button button-small button-secondary" title="<?php esc_attr_e( 'Fill User Details', 'invoicing' );?>" href="javascript:void(0)"><i aria-hidden="true" class="fa fa-refresh"></i><?php _e( 'Fill User Details', 'invoicing' );?></a>
                    <a class="wpinv-new-user button button-small button-secondary" href="javascript:void(0)"><i aria-hidden="true" class="fa fa-plus"></i><?php _e( 'Add New User', 'invoicing' );?></a>
                    <a style="display:none" class="wpinv-new-cancel button button-small button-secondary" href="javascript:void(0)"><i aria-hidden="true" class="fa fa-close"></i><?php _e( 'Cancel', 'invoicing' );?> </a>
                </div>
            <?php } ?>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-first-name table-layout">
            <div class="gdmbx-th"><label for="wpinv_first_name"><?php _e( 'First Name', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" name="wpinv_first_name" id="wpinv_first_name" value="<?php echo esc_attr( $invoice->first_name );?>" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-last-name table-layout">
            <div class="gdmbx-th"><label for="wpinv_last_name"><?php _e( 'Last Name', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" name="wpinv_last_name" id="wpinv_last_name" value="<?php echo esc_attr( $invoice->last_name );?>" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-company table-layout">
            <div class="gdmbx-th"><label for="wpinv_company"><?php _e( 'Company', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" name="wpinv_company" id="wpinv_company" value="<?php echo esc_attr( $invoice->company );?>" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-vat-number table-layout">
            <div class="gdmbx-th"><label for="wpinv_vat_number"><?php _e( 'Vat Number', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" name="wpinv_vat_number" id="wpinv_vat_number" value="<?php echo esc_attr( $invoice->vat_number );?>" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-address table-layout">
            <div class="gdmbx-th"><label for="wpinv_address"><?php _e( 'Address', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" name="wpinv_address" id="wpinv_address" value="<?php echo esc_attr( $invoice->address );?>" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-city table-layout">
            <div class="gdmbx-th"><label for="wpinv_city"><?php _e( 'City', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" name="wpinv_city" id="wpinv_city" value="<?php echo esc_attr( $invoice->city );?>" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-select gdmbx-wpinv-country table-layout">
            <div class="gdmbx-th"><label for="wpinv_country"><?php _e( 'Country', 'invoicing' );?> <span class="wpi-loader"><i class="fa fa-refresh fa-spin"></i></span></label></div>
            <div class="gdmbx-td">
                <?php
                echo wpinv_html_select( array(
                    'options'          => array_merge( array( '' => __( 'Choose a country', 'invoicing' ) ), wpinv_get_country_list() ),
                    'name'             => 'wpinv_country',
                    'id'               => 'wpinv_country',
                    'selected'         => $invoice->country,
                    'show_option_all'  => false,
                    'show_option_none' => false,
                    'class'            => 'gdmbx2-text-large',
                    'chosen'           => false,
                    'placeholder'      => __( 'Choose a country', 'invoicing' ),
                    'required'         => false,
                ) );
                ?>
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-state table-layout">
            <div class="gdmbx-th"><label for="wpinv_state"><?php _e( 'State', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <?php
                $states = wpinv_get_country_states( $invoice->country );
                if( !empty( $states ) ) {
                    echo wpinv_html_select( array(
                        'options'          => array_merge( array( '' => __( 'Choose a state', 'invoicing' ) ), $states ),
                        'name'             => 'wpinv_state',
                        'id'               => 'wpinv_state',
                        'selected'         => $invoice->state,
                        'show_option_all'  => false,
                        'show_option_none' => false,
                        'class'            => 'gdmbx2-text-large',
                        'chosen'           => false,
                        'placeholder'      => __( 'Choose a state', 'invoicing' ),
                        'required'         => false,
                    ) );
                } else {
                    echo wpinv_html_text( array(
                        'name'  => 'wpinv_state',
                        'value' => ! empty( $invoice->state ) ? $invoice->state : '',
                        'id'    => 'wpinv_state',
                        'class' => 'gdmbx2-text-large',
                        'required' => false,
                    ) );
                }
                ?>
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-zip table-layout">
            <div class="gdmbx-th"><label for="wpinv_zip"><?php _e( 'Zipcode', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" name="wpinv_zip" id="wpinv_zip" value="<?php echo esc_attr( $invoice->zip );?>" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-phone table-layout">
            <div class="gdmbx-th"><label for="wpinv_phone"><?php _e( 'Phone', 'invoicing' );?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" name="wpinv_phone" id="wpinv_phone" value="<?php echo esc_attr( $invoice->phone );?>" />
            </div>
        </div>
        <div class="gdmbx-row gdmbx-type-text gdmbx-wpinv-ip table-layout">
            <div class="gdmbx-th"><label for="wpinv_ip"><?php _e( 'IP Address', 'invoicing' );?><?php if ($invoice->ip) { ?>
                &nbsp;&nbsp;<a href="<?php echo admin_url( 'admin-ajax.php?action=wpinv_ip_geolocation&ip=' . $invoice->ip ); ?>" title="<?php esc_attr_e( 'View IP information', 'invoicing' );?>" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a>
                <?php } ?></label></div>
            <div class="gdmbx-td">
                <input type="text" class="gdmbx2-text-large" value="<?php echo esc_attr( $invoice->ip );?>" readonly />
            </div>
        </div>
    </div>
</div>
<?php wp_nonce_field( 'wpinv_save_invoice', 'wpinv_save_invoice' ) ;?>
<?php
    }
}
