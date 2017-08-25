<?php
/*
Plugin Name: Invoicing
Plugin URI: https://wpinvoicing.com/
Description: Invoicing plugin, this plugin allows you to send invoices (also EU VAT compliant) to people and have them pay you online.
Version: 1.0.0
Author: AyeCode Ltd
Author URI: https://wpinvoicing.com
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
    global $invoicing;
    
    $invoicing = WPInv_Plugin::run();
    
    return $invoicing;
}

// load WPInv_Plugin instance.
wpinv_run();