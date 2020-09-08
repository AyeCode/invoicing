<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

if ( get_option( 'wpinv_remove_data_on_invoice_unistall' ) ) {

    // Fetch settings.
    $settings = get_option( 'wpinv_settings' );

    // Delete pages.
    $pages = array( 'checkout_page', 'success_page', 'failure_page', 'invoice_history_page', 'quote_history_page', 'invoice_subscription_page' );
    foreach ( $pages as $page ) {
        if ( is_array( $settings ) && ! empty( $settings[ $page ] ) ) {
            wp_delete_post( $settings[ $page ], true );
        }
    }

    // Delete options.
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wpinv\_%';" );

    // Delete posts.
    $wpdb->query(
        "DELETE a,b
        FROM {$wpdb->posts} a
        LEFT JOIN {$wpdb->postmeta} b
            ON (a.ID = b.post_id)
        WHERE a.post_type 
            IN ( 'wpi_invoice', 'wpi_item', 'wpi_discount', 'wpi_quote' );"
    );

    // Delete invoice notes.
    $wpdb->query(
        "DELETE a,b
        FROM {$wpdb->comments} a
        LEFT JOIN {$wpdb->commentmeta} b
            ON (a.comment_ID = b.comment_id)
        WHERE a.comment_type = 'wpinv_note'"
    );

    // Delete user meta.
    $wpdb->query(
        "DELETE
        FROM {$wpdb->usermeta}
        WHERE meta_key LIKE '%_wpinv_%' OR meta_key LIKE '%_wpi_%';"
    );

    // Cleanup Cron Schedule
    wp_clear_scheduled_hook( 'wp_session_garbage_collection' );
    wp_clear_scheduled_hook( 'wpinv_register_schedule_event_twicedaily' );
    wp_clear_scheduled_hook( 'wpinv_register_schedule_event_daily' );

    // Clear any cached data that has been removed
    wp_cache_flush();

    // Delete tables.
    $tables = array(
        "{$wpdb->prefix}wpinv_subscriptions",
        "{$wpdb->prefix}getpaid_invoices",
        "{$wpdb->prefix}getpaid_invoice_items",
    );

    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
    }

}
