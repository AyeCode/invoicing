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
                    'name' => __( 'Paypal Pro Sandbox', 'invoicing' ),
                    'desc' => __( 'Check this to test your installation and integration to your website before going live. Use paypal sandbox API credentials.', 'invoicing' ),
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
        
        function wpinv_meta_boxes($post) {
            $gateways = wpinv_get_enabled_payment_gateways( true );
            if(isset($gateways['paypalpro'])){
                add_meta_box(
                        'paypalpro_recurring_metabox',
                        __( 'Paypal Pro Recurring', 'invoicing' ),
                        array($this, 'metabox_output'),
                        'wpi_invoice',
                        'side',
                        'high'
                );
            }
        }
        
        function metabox_output( $post ) {

            // Add a nonce field so we can check for it later.
                wp_nonce_field( 'myplugin_adpost_meta_awesome_box', 'adpost_meta_box_nonce' );

                /*
                 * Use get_post_meta() to retrieve an existing value
                 * from the database and use the value for the form.
                 */
                $period = esc_attr(get_post_meta($post->ID, 'paypalpro_rec_period', TRUE));
                $select_disable = $select_enable = '';
                $enabled = get_post_meta($post->ID, 'paypalpro_rec_enable', TRUE);
                if(empty($enabled) or $enabled == 'N' ) $select_disable = 'checked';
                else $select_enable = 'checked';
            ?>
        <div class="inside">
            <div class="gdmbx2-wrap form-table">
                <div class="gdmbx2-metabox gdmbx-field-list">
                    <div class="gdmbx-row gdmbx-type-text table-layout">
                        <div class="gdmbx-td">
                            <div class="paypal-radio-wrap">
                                <input type="radio" value="N" id="paypalpro_rec_disable"  name="paypalpro_rec_enable" class="" <?php echo $select_disable ?>>
                                <label for="paypalpro_rec_disable"><?php _e('Disable', 'invoicing'); ?></label>
                            </div>
                            <div class="paypal-radio-wrap">
                                <input type="radio" value="Y" id="paypalpro_rec_enable" name="paypalpro_rec_enable" style="display: inline-block; margin-top: 0; " class="" <?php echo $select_enable; ?>>
                                <label for="paypalpro_rec_enable"><?php _e('Enable', 'invoicing'); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="gdmbx-row gdmbx-type-text table-layout">
                        <div class="gdmbx-th"><label for="paypalpro_rec_period">Billing Period</label></div>
                        <div class="gdmbx-td">
                            <select name="paypalpro_rec_period">
                                <option value="Day" <?php selected($period, 'Day'); ?>>Day</option>
                                <option value="Week" <?php selected($period, 'Week'); ?>>Week</option>
                                <option value="SemiMonth" <?php selected($period, 'SemiMonth'); ?>>Semi Month</option>
                                <option value="Month" <?php selected($period, 'Month'); ?>>Month</option>
                                <option value="Year" <?php selected($period, 'Year'); ?>>Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="gdmbx-row gdmbx-type-text table-layout">
                    <div class="gdmbx-th"><label for="paypalpro_rec_frequency">Billing Frequency</label></div>
                    <div class="gdmbx-td">
                        <input placeholder="Billing Frequency" value="<?php echo esc_attr(get_post_meta($post->ID, 'paypalpro_rec_frequency', TRUE)); ?>" id="paypalpro_rec_frequency" name="paypalpro_rec_frequency" class="regular-text" type="number" min="1">
                    </div>
                </div>
                <div class="gdmbx-td"><i><?php _e('The combination of Billing Period and Billing Frequency cannot exceed one year.', 'invoicing'); ?> </i></div>
                </div>
            </div>
        </div>
            <?php 
        }
        
        function wpinv_save_meta( $post_id ) {

            /*
             * We need to verify this came from our screen and with proper authorization,
             * because the save_post action can be triggered at other times.
             */

            // Check if our nonce is set.
            if ( ! isset( $_POST['adpost_meta_box_nonce'] ) ) {
                    return;
            }

            // Verify that the nonce is valid.
            if ( ! wp_verify_nonce( $_POST['adpost_meta_box_nonce'], 'myplugin_adpost_meta_awesome_box' ) ) {
                    return;
            }

            // If this is an autosave, our form has not been submitted, so we don't want to do anything.
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                    return;
            }

            // Check the user's permissions.
            if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

                    if ( ! current_user_can( 'edit_page', $post_id ) ) {
                            return;
                    }

            } else {

                    if ( ! current_user_can( 'edit_post', $post_id ) ) {
                            return;
                    }
            }

            /* OK, it's safe for us to save the data now. */

            // Add/update new value.
            foreach ($_POST as $key => $val):
                // Make sure that it is set.
                
                if ( in_array($key, array('paypalpro_rec_startdate', 'paypalpro_rec_period', 'paypalpro_rec_frequency', 'paypalpro_rec_enable')) and isset( $val ) ) {                    
                    //Sanitize user input.
                    $my_data = sanitize_text_field( $val );
                    update_post_meta( $post_id, $key,  $my_data); // Add new value.
                }
            endforeach;
        }  

}
