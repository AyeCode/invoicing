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
    wpinv_update_new_email_settings();

    // Add Subscription tables
    $db = new WPInv_Subscriptions_DB;
    @$db->create_table();
}

function wpinv_update_new_email_settings(){
    global $wpinv_options;

    $current_options = get_option( 'wpinv_settings', array() );
    $options = array();

    // Populate some default values
    foreach( wpinv_get_registered_settings() as $tab => $sections ) {
        foreach( $sections as $section => $settings) {
            // Check for backwards compatibility
            $tab_sections = wpinv_get_settings_tab_sections( $tab );
            if( ! is_array( $tab_sections ) || ! array_key_exists( $section, $tab_sections ) ) {
                $section = 'main';
                $settings = $sections;
            }

            foreach ( $settings as $option ) {
                if ( !empty( $option['id'] ) && !isset( $wpinv_options[ $option['id'] ] ) ) {
                    if ( 'checkbox' == $option['type'] && !empty( $option['std'] ) ) {
                        $options[ $option['id'] ] = '1';
                    } else if ( !empty( $option['std'] ) ) {
                        $options[ $option['id'] ] = $option['std'];
                    }
                }
            }
        }
    }

    $merged_options_current     = array_merge( $wpinv_options, $options );
    $merged_options     = array_merge( $merged_options_current, $current_options );
    $wpinv_options      = $merged_options;

    update_option( 'wpinv_settings', $merged_options );
}