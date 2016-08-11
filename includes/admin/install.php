<?php
register_activation_hook( WPINV_PLUGIN_FILE, 'wpinv_plugin_activation' );
register_deactivation_hook( WPINV_PLUGIN_FILE, 'wpinv_plugin_deactivation' );
register_uninstall_hook( WPINV_PLUGIN_FILE, 'wpinv_plugin_uninstall' );

function wpinv_plugin_activation( $network_wide = false ) {
    error_log( 'plugin_activation' );
    wpinv_install( $network_wide );
}

function wpinv_plugin_deactivation() {
    error_log( 'plugin_deactivation' );
}

function wpinv_plugin_uninstall() {
    error_log( 'plugin_uninstall' );
}
    
function wpinv_install( $network_wide = false ) {
    global $wpdb;

    if ( is_multisite() && $network_wide ) {
        foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
            switch_to_blog( $blog_id );
            wpinv_run_install();
            restore_current_blog();
        }
    } else {
        wpinv_run_install();
    }
}

function wpinv_run_install() {
    global $wpdb, $wpinv_options, $wp_version, $wpi_session;
    
    // Setup the invoice Custom Post Type
    wpinv_register_post_types();
    
    // Clear the permalinks
    flush_rewrite_rules( false );
    
    // Add Upgraded From Option
    $current_version = get_option( 'wpinv_version' );
    if ( $current_version ) {
        update_option( 'wpinv_version_upgraded_from', $current_version );
    }
        
    // Pull options from WP, not GD Invoice's global
    $current_options = get_option( 'wpinv_settings', array() );
    
    // Setup some default options
    $options = wpinv_create_pages();
    
    // Populate some default values
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
    
    $merged_options     = array_merge( $wpinv_options, $options );
    $wpinv_options      = $merged_options;
    
    update_option( 'wpinv_settings', $merged_options );
    update_option( 'wpinv_version', WPINV_VERSION );
    
    // Check for PHP Session support, and enable if available
    $wpi_session->use_php_sessions();
    
    // Add a temporary option to note that GD Invoice pages have been created
    set_transient( '_wpinv_installed', $merged_options, 30 );
    
    // Bail if activating from network, or bulk
    if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
        return;
    }
    
    // Add the transient to redirect
    set_transient( '_wpinv_activation_redirect', true, 30 );
}

/**
 * When a new Blog is created in multisite, see if Invoicing is network activated, and run the installer.
 *
 */
function wpinv_new_blog_created( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    if ( is_plugin_active_for_network( plugin_basename( WPINV_PLUGIN_FILE ) ) ) {
        switch_to_blog( $blog_id );
        wpinv_run_install();
        restore_current_blog();
    }
}
add_action( 'wpmu_new_blog', 'wpinv_new_blog_created', 10, 6 );

/**
 * Post-installation.
 *
 * Runs just after plugin installation and exposes the wpinv_after_install hook.
 */
function wpinv_after_install() {
    if ( ! is_admin() ) {
        return;
    }

    $wpinv_options      = get_transient( '_wpinv_installed' );
    $wpinv_table_check  = get_option( '_wpinv_table_check', false );

    if ( false === $wpinv_table_check || current_time( 'timestamp' ) > $wpinv_table_check ) {
        update_option( '_wpinv_table_check', ( current_time( 'timestamp' ) + WEEK_IN_SECONDS ) );
    }

    if ( false !== $wpinv_options ) {
        // Delete the transient
        delete_transient( '_wpinv_installed' );
    }
}
add_action( 'admin_init', 'wpinv_after_install' );

function wpinv_create_pages() {
    global $wpinv_options;
    
    $options = array();
    
    // Checks if the purchase page option exists
    if ( ! array_key_exists( 'checkout_page', $wpinv_options ) ) {
        // Checkout Page
        $checkout = wp_insert_post(
            array(
                'post_title'     => __( 'Checkout', 'invoicing' ),
                'post_content'   => '[wpinv_checkout]',
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'post_name'      => 'wpi-checkout',
                'page_template'  => 'full-width.php'
            )
        );
        
        // Invoice History (History) Page
        $history = wp_insert_post(
            array(
                'post_title'     => __( 'Invoice History', 'invoicing' ),
                'post_content'   => '[wpinv_history]',
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'      => 'page',
                'post_parent'    => $checkout,
                'comment_status' => 'closed',
                'page_template'  => 'full-width.php'
            )
        );

        // Payment Confirmation (Success) Page
        $success = wp_insert_post(
            array(
                'post_title'     => __( 'Payment Confirmation', 'invoicing' ),
                'post_content'   => __( '[wpinv_receipt]', 'invoicing' ),
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_parent'    => $checkout,
                'post_type'      => 'page',
                'comment_status' => 'closed',
                'page_template'  => 'full-width.php'
            )
        );

        // Failed Payment Page
        $failed = wp_insert_post(
            array(
                'post_title'     => __( 'Transaction Failed', 'invoicing' ),
                'post_content'   => __( 'Your transaction failed, please try again or contact site support.', 'invoicing' ),
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'      => 'page',
                'post_parent'    => $checkout,
                'comment_status' => 'closed',
                'page_template'  => 'full-width.php'
            )
        );

        // Store our page IDs
        $options['checkout_page']         = $checkout;
        $options['success_page']          = $success;
        $options['failure_page']          = $failed;
        $options['invoice_history_page']  = $history;
    }
    
    return $options;
}