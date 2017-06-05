<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Invoicing_Paypal_Pro
 * @subpackage Invoicing_Paypal_Pro/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Invoicing_Paypal_Pro
 * @subpackage Invoicing_Paypal_Pro/admin
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Invoicing_Paypal_Pro_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Invoicing_Paypal_Pro_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Invoicing_Paypal_Pro_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/invoicing-paypal-pro-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Invoicing_Paypal_Pro_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Invoicing_Paypal_Pro_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/invoicing-paypal-pro-admin.js', array( 'jquery' ), $this->version, false );

	}
        public function add_gateway( $gateways = array() ) {
            $gateways['paypalpro'] = array(
                'admin_label'    => __( 'Paypal Pro Payment', 'invoicing' ),
                'checkout_label' => __( 'Paypal Pro Payment', 'invoicing' ),
                'ordering'       => 10,
            );

            return $gateways;
        }

        public function paypalpro_settings( $settings ) { 
            $setting['paypalpro_desc']['std'] = __( 'Pay using a credit card / debit card.', 'invoicing' );
            $settings['paypalpro_sandbox'] = array(
                    'type' => 'checkbox',
                    'id'   => 'paypalpro_sandbox',
                    'name' => __( 'Paypal Pro Test Mode', 'invoicing' ),
                    'desc' => __( 'This provides a special Test Environment to enable you to test your installation and integration to your website before going live.', 'invoicing' ),
                    'std'  => 1
                );

            $settings['paypalpro_api_username'] = array(
                    'type' => 'text',
                    'id'   => 'paypalpro_api_username',
                    'name' => __( 'Username', 'invoicing' ),
                    'desc' => __( 'Your Paypal Pro API username provided in your developer account.', 'invoicing' ),
                );

            $settings['paypalpro_api_password'] = array(
                    'type' => 'text',
                    'id'   => 'paypalpro_api_password',
                    'name' => __( 'API Password', 'invoicing' ),
                    'desc' => __( 'Your Paypal Pro API password provided in your developer account.', 'invoicing' ),
                );
            
            $settings['paypalpro_api_signature'] = array(
                    'type' => 'text',
                    'id'   => 'paypalpro_api_signature',
                    'name' => __( 'API signature', 'invoicing' ),
                    'desc' => __( 'Your Paypal Pro API signature provided in your developer account.', 'invoicing' ),
                );
            /*
            $settings['paypalpro_ipn_url'] = array(
                    'type' => 'ipn_url',
                    'id'   => 'paypalpro_ipn_url',
                    'name' => __( 'ITN Url', 'invoicing' ),
                    'std' => wpinv_get_ipn_url( 'paypalpro' ),
                    'desc' => __( 'Paypal Pro payment callback url (should not be changed)', 'invoicing' ),
                    'size' => 'large',
                    'custom' => 'paypalpro',
                    'readonly' => true
                );
                */
            return $settings;
        }

}
