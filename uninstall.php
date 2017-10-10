<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://wpinvoicing.com
 * @since      1.0.0
 *
 * @package    Invoicing
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb, $wp_version;

$remove_data = get_option( 'wpinv_remove_data_on_invoice_unistall' );

/*
 * Only remove ALL product and page data if WPINV_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'WPINV_REMOVE_ALL_DATA' ) ) {
    $remove_data = true === WPINV_REMOVE_ALL_DATA ? true : false;
}

if ( $remove_data ) {
    // Load Invoicing file.
    include_once( 'invoicing.php' );

    // Roles + caps.
    include_once( dirname( __FILE__ ) . '/includes/admin/install.php' );
    wpinv_remove_admin_caps();
    
    $settings = get_option( 'wpinv_settings' );
    
    // Delete pages.
    $wpi_pages = array( 'checkout_page', 'success_page', 'failure_page', 'invoice_history_page', 'quote_history_page', 'invoice_subscription_page' );
    foreach ( $wpi_pages as $page ) {
        if ( !empty( $page ) && !empty( $settings[ $page ] ) ) {
            wp_delete_post( $settings[ $page ], true );
        }
    }
    
    // Delete posts + data.
    $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'wpi_invoice', 'wpi_item', 'wpi_discount', 'wpi_quote' );" );
    $wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
    
    // Delete comments.
    $wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_type LIKE 'wpinv_note';" );
    $wpdb->query( "DELETE meta FROM {$wpdb->commentmeta} meta LEFT JOIN {$wpdb->comments} comments ON comments.comment_ID = meta.comment_id WHERE comments.comment_ID IS NULL;" );
    
    // Delete user meta.
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '%_wpinv_%' OR meta_key LIKE '%_wpi_invoice%' OR meta_key LIKE '%_wpi_item%' OR meta_key LIKE '%_wpi_discount%' OR meta_key LIKE '_wpi_stripe%' OR meta_key LIKE '%_wpi_quote%';" );
    
    // Cleanup Cron Schedule
    wp_clear_scheduled_hook( 'wp_session_garbage_collection' );
    wp_clear_scheduled_hook( 'wpinv_register_schedule_event_twicedaily' );
    
    // Delete options.
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wpinv_%' OR option_name LIKE '_wpinv_%' OR option_name LIKE '\_transient\_wpinv\_%';" );
    
    // Clear any cached data that has been removed
    wp_cache_flush();
}