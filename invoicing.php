<?php
/*
Plugin Name: Invoicing
Plugin URI: http://wpgeodirectory.com/
Description: Invoicing plugin.
Version: 1.0.0
Author: GeoDirectory
Author URI: http://wpgeodirectory.com/
License: GPLv3
*/

// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

if ( !defined( 'WPINV_VERSION' ) ) {
    define( 'WPINV_VERSION', '1.0.0' );
}

if ( !defined( 'WPINV_PLUGIN_FILE' ) ) {
    define( 'WPINV_PLUGIN_FILE', __FILE__ );
}

require plugin_dir_path( __FILE__ ) . 'includes/class-wpinv.php';

function wpinv_run() {
    return WPInv_Plugin::run();
}

// load WPInv_Plugin instance.
wpinv_run();
