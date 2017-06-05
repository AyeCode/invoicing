<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpgeodirectory.com
 * @since             1.0.0
 * @package           Invoicing_Paypal_Pro
 *
 * @wordpress-plugin
 * Plugin Name:       Invoicing Paypal Pro
 * Plugin URI:        https://wpgeodirectory.com/invoicing-sagepay-payment
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            GeoDirectory Team
 * Author URI:        https://wpgeodirectory.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       invoicing
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-invoicing-paypal-pro-activator.php
 */
function activate_invoicing_paypal_pro() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-invoicing-paypal-pro-activator.php';
	Invoicing_Paypal_Pro_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-invoicing-paypal-pro-deactivator.php
 */
function deactivate_invoicing_paypal_pro() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-invoicing-paypal-pro-deactivator.php';
	Invoicing_Paypal_Pro_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_invoicing_paypal_pro' );
register_deactivation_hook( __FILE__, 'deactivate_invoicing_paypal_pro' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-invoicing-paypal-pro.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_invoicing_paypal_pro() {

	$plugin = new Invoicing_Paypal_Pro();
	$plugin->run();

}
run_invoicing_paypal_pro();
