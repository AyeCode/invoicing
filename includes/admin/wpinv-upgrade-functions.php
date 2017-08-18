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