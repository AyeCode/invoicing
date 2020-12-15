<?php
/**
 * Plugin Name: GetPaid Beta
 * Plugin URI: https://wpinvoicing.com/
 * Description: A lightweight and VAT compliant payments and invoicing plugin.
 * Version: 2.0.1-beta
 * Author: AyeCode Ltd
 * Author URI: https://wpinvoicing.com
 * Text Domain: invoicing
 * Domain Path: /languages
 * License: GPLv3
 * Requires at least: 4.9
 * Requires PHP: 5.3
 *
 * @package GetPaid
 */

defined( 'ABSPATH' ) || exit;

// Define constants.
if ( ! defined( 'WPINV_PLUGIN_FILE' ) ) {
	define( 'WPINV_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WPINV_VERSION' ) ) {
	define( 'WPINV_VERSION', '2.0.1-beta' );
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
 * @deprecated
 */
function wpinv_run() {
    return getpaid();
}

// Kickstart the plugin.
getpaid();
