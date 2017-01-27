<?php
// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

do_action( 'wpinv_email_before_billing_details', $invoice ); ?>
<div id="wpinv-email-billing">
    <h3 class="wpinv-address-t"><?php echo apply_filters( 'wpinv_email_billing_title', __( 'Billing Details', 'invoicing' ) ); ?></h3>
    <?php 
    $address_row = '';
    if ( $address = $invoice->get_address() ) {
        $address_row .= wpautop( wp_kses_post( $address ) );
    }
    
    $address_fields = array();
    if ( !empty( $invoice->city ) ) {
        $address_fields[] = $invoice->city;
    }
    
    $country_code = !empty( $invoice->country ) ? $invoice->country : '';
    if ( !empty( $invoice->state ) ) {
        $address_fields[] = wpinv_state_name( $invoice->state, $country_code );
    }
    
    if ( !empty( $address_fields ) ) {
        $address_fields = implode( ", ", $address_fields );
        $address_row .= wpautop( wp_kses_post( $address_fields ) );
    }
    
    if ( !empty( $country_code ) ) {
        $country = wpinv_country_name( $country_code );
        $address_row .= wpautop( wp_kses_post( trim( $country . '(' . $country_code . ')' . ' ' . $invoice->zip ) ) );
    }
    
    ?>
    <table class="table table-bordered table-sm wpi-billing-details">
        <tbody>
            <?php do_action( 'wpinv_email_billing_fields_first', $invoice ); ?>
            <tr class="wpi-receipt-name">
                <th class="text-left"><?php _e( 'Name', 'invoicing' ); ?></th>
                <td><?php if ( $sent_to_admin && $invoice->user_id ) { ?><a href="<?php echo esc_url( add_query_arg( 'user_id', $invoice->get_user_id(), self_admin_url( 'user-edit.php' ) ) ) ;?>"><?php echo esc_html( $invoice->get_user_full_name() ); ?></a><?php } else { echo esc_html( $invoice->get_user_full_name() ); } ?></td>
            </tr>
            <tr class="wpi-receipt-email">
                <th class="text-left"><?php _e( 'Email', 'invoicing' ); ?></th>
                <td><?php echo $invoice->get_email() ;?></td>
            </tr>
            <?php if ( $invoice->company ) { ?>
            <tr class="wpi-receipt-company">
                <th class="text-left"><?php _e( 'Company', 'invoicing' ); ?></th>
                <td><?php echo esc_html( $invoice->company ) ;?></td>
            </tr>
            <?php } ?>
            <tr class="wpi-receipt-address">
                <th class="text-left"><?php _e( 'Address', 'invoicing' ); ?></th>
                <td><?php echo $address_row ;?></td>
            </tr>
            <?php if ( $invoice->phone ) { ?>
            <tr class="wpi-receipt-phone">
                <th class="text-left"><?php _e( 'Phone', 'invoicing' ); ?></th>
                <td><?php echo esc_html( $invoice->phone ) ;?></td>
            </tr>
            <?php } ?>
            <?php do_action( 'wpinv_email_billing_fields_last', $invoice ); ?>
        </tbody>
    </table>
</div>
<?php do_action( 'wpinv_email_after_billing_details', $invoice ); ?>