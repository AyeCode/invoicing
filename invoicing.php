<?php
/**
 * Plugin Name: GetPaid
 * Plugin URI: https://wpinvoicing.com/
 * Description: A lightweight and VAT compliant payments and invoicing plugin.
 * Version: 2.5.9
 * Author: AyeCode Ltd
 * Author URI: https://wpinvoicing.com
 * Text Domain: invoicing
 * Domain Path: /languages
 * License: GPLv3
 * Requires at least: 4.9
 * Requires PHP: 5.6
 *
 * @package GetPaid
 */

defined( 'ABSPATH' ) || exit;

// Define constants.
if ( ! defined( 'WPINV_PLUGIN_FILE' ) ) {
	define( 'WPINV_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WPINV_VERSION' ) ) {
	define( 'WPINV_VERSION', '2.5.9' );
}

// Include the main Invoicing class.
if ( ! class_exists( 'WPInv_Plugin', false ) ) {
	require_once plugin_dir_path( WPINV_PLUGIN_FILE ) . 'includes/class-wpinv.php';
}

/**
 * Returns the main instance of Invoicing.
 *
 * @since  1.0.19
 * @return WPInv_Plugin
 */
function getpaid() {

    if ( empty( $GLOBALS['invoicing'] ) ) {
        $GLOBALS['invoicing'] = new WPInv_Plugin();
    }

	return $GLOBALS['invoicing'];
}

/**
 * Deactivation hook.
 *
 * @since  2.0.8
 */
function getpaid_deactivation_hook() {
    update_option( 'wpinv_flush_permalinks', 1 );
}
register_deactivation_hook( __FILE__, 'getpaid_deactivation_hook' );

/**
 * @deprecated
 */
function wpinv_run() {
    return getpaid();
}

// Kickstart the plugin.
add_action( 'plugins_loaded', 'getpaid', -100 );
