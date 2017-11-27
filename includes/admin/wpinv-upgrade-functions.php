<?php
/**
 * Upgrade related functions.
 *
 * @since 1.0.0
 */

/**
 * Perform automatic upgrades when necessary.
 *
 * @since 1.0.0
*/
function wpinv_automatic_upgrade() {
    $wpi_version = get_option( 'wpinv_version' );
    
    if ( $wpi_version == WPINV_VERSION ) {
        return;
    }
    
    if ( version_compare( $wpi_version, '0.0.5', '<' ) ) {
        wpinv_v005_upgrades();
    }
    
    update_option( 'wpinv_version', WPINV_VERSION );
}
add_action( 'admin_init', 'wpinv_automatic_upgrade' );

function wpinv_v005_upgrades() {
    global $wpdb;
    
    // Invoices status
    $results = $wpdb->get_results( "SELECT ID FROM " . $wpdb->posts . " WHERE post_type = 'wpi_invoice' AND post_status IN( 'pending', 'processing', 'onhold', 'refunded', 'cancelled', 'failed', 'renewal' )" );
    if ( !empty( $results ) ) {
        $wpdb->query( "UPDATE " . $wpdb->posts . " SET post_status = CONCAT( 'wpi-', post_status ) WHERE post_type = 'wpi_invoice' AND post_status IN( 'pending', 'processing', 'onhold', 'refunded', 'cancelled', 'failed', 'renewal' )" );
        
        // Clean post cache
        foreach ( $results as $row ) {
            clean_post_cache( $row->ID );
        }
    }
    
    // Item meta key changes
    $query = "SELECT DISTINCT post_id FROM " . $wpdb->postmeta . " WHERE meta_key IN( '_wpinv_item_id', '_wpinv_package_id', '_wpinv_post_id', '_wpinv_cpt_name', '_wpinv_cpt_singular_name' )";
    $results = $wpdb->get_results( $query );
    
    if ( !empty( $results ) ) {
        $wpdb->query( "UPDATE " . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_id' WHERE meta_key IN( '_wpinv_item_id', '_wpinv_package_id', '_wpinv_post_id' )" );
        $wpdb->query( "UPDATE " . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_name' WHERE meta_key = '_wpinv_cpt_name'" );
        $wpdb->query( "UPDATE " . $wpdb->postmeta . " SET meta_key = '_wpinv_custom_singular_name' WHERE meta_key = '_wpinv_cpt_singular_name'" );
        
        foreach ( $results as $row ) {
            clean_post_cache( $row->post_id );
        }
    }

    wpinv_add_admin_caps();
}

if ( get_option('wpinv_db_version') != WPINV_VERSION ) {
    add_action('plugins_loaded', 'wpinv_upgrade_all', 10);
}

function wpinv_upgrade_all(){

        // Add Subscription tables
        $db = new WPInv_Subscriptions_DB;
        @$db->create_table();

        convert_old_subscriptions();
}

function convert_old_subscriptions(){

    global $wpdb;

    $query = "SELECT ". $wpdb->posts .".ID FROM ". $wpdb->posts ." INNER JOIN ". $wpdb->postmeta ." ON ( ". $wpdb->posts .".ID = ". $wpdb->postmeta .".post_id ) WHERE 1=1  AND ". $wpdb->postmeta .".meta_key = '_wpinv_subscr_status' AND (". $wpdb->postmeta .".meta_value = 'pending' OR ". $wpdb->postmeta .".meta_value = 'active' OR ". $wpdb->postmeta .".meta_value = 'cancelled' OR ". $wpdb->postmeta .".meta_value = 'completed' OR ". $wpdb->postmeta .".meta_value = 'expired' OR ". $wpdb->postmeta .".meta_value = 'trialing' OR ". $wpdb->postmeta .".meta_value = 'failing') AND ". $wpdb->posts .".post_type = 'wpi_invoice' GROUP BY ". $wpdb->posts .".ID ORDER BY ". $wpdb->posts .".post_date ASC";

    $results = $wpdb->get_results($query);

    foreach ( $results as $row ) {

        $invoice = new WPInv_Invoice($row->ID);

        $item_id = $invoice->get_meta( '_wpinv_item_ids', true );
        $item = new WPInv_Item( $item_id );

        if($invoice->has_status('wpi-renewal') || !$item->is_recurring()){
            continue;
        }

        $period             = $item->get_recurring_period(true);
        $interval           = $item->get_recurring_interval();
        $bill_times         = (int)$item->get_recurring_limit();
        $initial_amount     = wpinv_sanitize_amount( $invoice->get_total(), 2 );
        $recurring_amount   = wpinv_sanitize_amount( $invoice->get_recurring_details( 'total' ), 2 );
        $subscription_status = $invoice->get_meta( '_wpinv_subscr_status', true );
        $status             = empty($subscription_status) ? 'pending' : $subscription_status;
        $expiration         = date( 'Y-m-d H:i:s', strtotime( '+' . $interval . ' ' . $period  . ' 23:59:59', strtotime($invoice->date) ) );

        $trial_period = '';
        if ( $invoice->is_free_trial() && $item->has_free_trial() ) {
            $trial_period       = $item->get_trial_period(true);
            $free_trial         = $item->get_free_trial();
            $trial_period       = ! empty( $invoice->is_free_trial() ) ? $free_trial . ' ' . $trial_period : '';
            $expiration         = date( 'Y-m-d H:i:s', strtotime( '+' . $trial_period . ' 23:59:59', strtotime($invoice->date) ) );
            $status             = "trialing" === $status ? 'trialling' : $status;
        }

        $args = array(
            'product_id'        => $item_id,
            'customer_id'       => $invoice->user_id,
            'parent_payment_id' => $invoice->ID,
            'status'            => $status,
            'frequency'         => $interval,
            'period'            => $period,
            'initial_amount'    => $initial_amount,
            'recurring_amount'  => $recurring_amount,
            'bill_times'        => $bill_times,
            'created'           => date( 'Y-m-d H:i:s', strtotime($invoice->date) ),
            'expiration'        => $expiration,
            'trial_period'      => $trial_period,
            'profile_id'        => $invoice->ID,
            'transaction_id'    => $invoice->get_transaction_id(),
        );

        $subs_db      = new WPInv_Subscriptions_DB;
        $subs         = $subs_db->get_subscriptions( array( 'parent_payment_id' => $invoice->ID, 'number' => 1 ) );
        $subscription = reset( $subs );

        if( !$subscription || $subscription->id <= 0 ) {

            $subscription = new WPInv_Subscription();
            $new_sub = $subscription->create( $args );

            if($new_sub->get_times_billed() >= $bill_times && 'active' == $new_sub->status || 'trialling' == $new_sub->status){
                $new_sub->complete(); // Mark completed if all times billed
            }
        }

    }
}