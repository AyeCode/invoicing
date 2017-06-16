<?php
/*
Plugin Name: Invoicing
Plugin URI: https://wpinvoicing.com/
Description: Invoicing plugin.
Version: 0.0.4
Author: AyeCode
Author URI: https://wpinvoicing.com/
License: GPLv3
Update URL: https://github.com/AyeCode/invoicing/
*/

// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

if ( !defined( 'WPINV_VERSION' ) ) {
    define( 'WPINV_VERSION', '0.0.4' );
}

if ( !defined( 'WPINV_PLUGIN_FILE' ) ) {
    define( 'WPINV_PLUGIN_FILE', __FILE__ );
}

require plugin_dir_path( __FILE__ ) . 'includes/class-wpinv.php';

function wpinv_run() {
    global $invoicing;
    
    $invoicing = WPInv_Plugin::run();
    
    return $invoicing;
}

// load WPInv_Plugin instance.
wpinv_run();


/**
 * Show update plugin admin notification.
 */
if(is_admin()){
    if (!function_exists('ayecode_show_update_plugin_requirement')) {//only load the update file if needed
        function ayecode_show_update_plugin_requirement() {
            if ( !defined( 'WP_EASY_UPDATES_ACTIVE' ) ) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong>
                            <?php
                            echo sprintf( __( 'The plugin %sWP Easy Updates%s is required to check for and update some installed plugins, please install it now.', 'geodirectory' ), '<a href="https://wpeasyupdates.com/" target="_blank" title="WP Easy Updates">', '</a>' );
                            ?>
                        </strong>
                    </p>
                </div>
                <?php
            }
        }

        add_action( 'admin_notices', 'ayecode_show_update_plugin_requirement' );
    }
}
