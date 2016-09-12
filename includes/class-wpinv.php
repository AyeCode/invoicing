<?php
/**
 * Contains functions related to Invoicing plugin.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Plugin {
    private static $instance;
    
    public static function run() {
        if ( !isset( self::$instance ) && !( self::$instance instanceof WPInv_Plugin ) ) {
            self::$instance = new WPInv_Plugin;
            self::$instance->includes();
            self::$instance->actions();
        }
        
        do_action( 'wpinv_loaded' );
        
        return self::$instance;
    }
    
    public function __construct() {
        $this->define_constants();
    }
    
    public function define_constants() {
        define( 'WPINV_PLUGIN_DIR', plugin_dir_path( WPINV_PLUGIN_FILE ) );
        define( 'WPINV_PLUGIN_URL', plugin_dir_url( WPINV_PLUGIN_FILE ) );
    }
    
    private function actions() {
        /* Internationalize the text strings used. */
        add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
        
        /* Perform actions on admin initialization. */
        add_action( 'admin_init', array( &$this, 'admin_init') );
        add_action( 'init', array( &$this, 'init' ), 3 );
        add_action( 'init', array( 'WPInv_Shortcodes', 'init' ) );
        add_action( 'init', array( &$this, 'wpinv_actions' ) );

        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
        
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
            add_action( 'admin_body_class', array( &$this, 'admin_body_class' ) );
        }
        
        /**
         * Fires after the setup of all WPInv_Plugin actions.
         *
         * @since 1.0.0
         *
         * @param WPInv_Plugin $this. Current WPInv_Plugin instance. Passed by reference.
         */
        do_action_ref_array( 'wpinv_actions', array( &$this ) );
    }
    
    public function plugins_loaded() {
        /* Internationalize the text strings used. */
        $this->load_textdomain();
    }
    
    /**
     * Load the translation of the plugin.
     *
     * @since 1.0
     */
    public function load_textdomain() {
        $locale = apply_filters( 'plugin_locale', get_locale(), 'invoicing' );
        
        load_textdomain( 'invoicing', WP_LANG_DIR . '/invoicing/invoicing-' . $locale . '.mo' );
        load_plugin_textdomain( 'invoicing', false, WPINV_PLUGIN_DIR . 'languages' );
        
        /**
         * Define language constants.
         */
        require_once( WPINV_PLUGIN_DIR . 'language.php' );
    }
        
    public function includes() {
        global $wpinv_options;
        
        require_once( WPINV_PLUGIN_DIR . 'includes/admin/register-settings.php' );
        $wpinv_options = wpinv_get_settings();
        
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-post-types.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-email-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-general-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-helper-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-vat-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-template-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-address-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-invoice-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-item-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-discount-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-gateway-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-payment-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-user-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-gd-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-error-functions.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-invoice.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-item.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-session.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-ajax.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-api.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-shortcodes.php' );
        
        $gateways = array_keys( wpinv_get_enabled_payment_gateways() );
        if ( !empty( $gateways ) ) {
            foreach ( $gateways as $gateway ) {
                if ( $gateway == 'manual' ) {
                    continue;
                }
                
                $gateway_file = WPINV_PLUGIN_DIR . 'includes/gateways/' . $gateway . '.php';
                
                if ( file_exists( $gateway_file ) ) {
                    require_once( $gateway_file );
                }
            }
        }
        require_once( WPINV_PLUGIN_DIR . 'includes/gateways/manual.php' );
        
        if ( is_admin() ) {
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/wpinv-admin-functions.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/admin-meta-boxes.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/meta-boxes/class-mb-invoice-details.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/meta-boxes/class-mb-invoice-items.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/meta-boxes/class-mb-invoice-notes.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/meta-boxes/class-mb-invoice-address.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/admin-pages.php' );
        }
        
        // include css inliner
        if ( ! class_exists( 'Emogrifier' ) && class_exists( 'DOMDocument' ) ) {
            include_once( WPINV_PLUGIN_DIR . 'includes/libraries/class-emogrifier.php' );
        }
        
        require_once( WPINV_PLUGIN_DIR . 'includes/admin/install.php' );
    }
    
    public function init() {
    }
    
    public function admin_init() {
        if (!(defined( 'DOING_AJAX' ) && DOING_AJAX)) {
        }
        
        add_action( 'admin_print_scripts-edit.php', array( &$this, 'admin_print_scripts_edit_php' ) );
    }
    
    public function enqueue_scripts() {
        $suffix       = '';//defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        
        wp_register_style( 'wpinv_front_style', WPINV_PLUGIN_URL . 'assets/css/invoice-front.css', array(), WPINV_VERSION );
        
        wp_enqueue_style( 'wpinv_front_style' );
        
        // VAT scripts
        wpinv_vat_enqueue_vat_scripts();
        
        // Register scripts
        wp_register_script( 'jquery-blockui', WPINV_PLUGIN_URL . 'assets/js/jquery.blockUI.min.js', array( 'jquery' ), '2.70', true );
        wp_register_script( 'wpinv-front-script', WPINV_PLUGIN_URL . 'assets/js/invoice-front' . $suffix . '.js', array( 'jquery', 'wpinv-vat-script' ),  WPINV_VERSION );
        
        $localize                               = array();
        $localize['ajax_url']                   = admin_url( 'admin-ajax.php' );
        
        wp_enqueue_script( 'jquery-blockui' );
        wp_enqueue_script( 'wpinv-front-script' );
        wp_localize_script( 'wpinv-front-script', 'WPInv', $localize );
    }
    
    public function admin_enqueue_scripts() {
        global $post, $pagenow;
        
        wp_register_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css', array(), '4.5.0' );
        wp_enqueue_style( 'font-awesome' );
        
        wp_register_style( 'wpinv_admin_style', WPINV_PLUGIN_URL . 'assets/css/admin.css', array(), WPINV_VERSION );
        wp_register_style( 'wpinv_meta_box_style', WPINV_PLUGIN_URL . 'assets/css/meta-box.css', array(), WPINV_VERSION );
        
        wp_enqueue_style( 'wpinv_meta_box_style' );
        wp_enqueue_style( 'wpinv_admin_style' );
        
        $suffix       = '';//defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        // Register scripts
        wp_register_script( 'wpinv-admin-script', WPINV_PLUGIN_URL . 'assets/js/admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'wpinv-gdmbx-script' ),  WPINV_VERSION );
        wp_register_script( 'jquery-blockui', WPINV_PLUGIN_URL . 'assets/js/jquery.blockUI.min.js', array( 'jquery' ), '2.70', true );
        wp_register_script( 'wpinv-gdmbx-script', WPINV_PLUGIN_URL . 'assets/js/gdmbx' . $suffix . '.js', array( 'jquery' ), WPINV_VERSION );
        $localize = array(
            'ajax_nonce'       => wp_create_nonce( 'ajax_nonce' ),
            'ajaxurl'          => admin_url( '/admin-ajax.php' ),
            'script_debug'     => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? true : false,
            'up_arrow_class'   => 'dashicons dashicons-arrow-up-alt2',
            'down_arrow_class' => 'dashicons dashicons-arrow-down-alt2',
            'defaults'         => array(
                'color_picker' => false,
                'date_picker'  => array(
                    'changeMonth'     => true,
                    'changeYear'      => true,
                    'dateFormat'      => _x( 'yy-mm-dd', 'Valid formatDate string for jquery-ui datepicker', 'invoicing' ),
                    'dayNames'        => explode( ',', __( 'Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday', 'invoicing' ) ),
                    'dayNamesMin'     => explode( ',', __( 'Su, Mo, Tu, We, Th, Fr, Sa', 'invoicing' ) ),
                    'dayNamesShort'   => explode( ',', __( 'Sun, Mon, Tue, Wed, Thu, Fri, Sat', 'invoicing' ) ),
                    'monthNames'      => explode( ',', __( 'January, February, March, April, May, June, July, August, September, October, November, December', 'invoicing' ) ),
                    'monthNamesShort' => explode( ',', __( 'Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec', 'invoicing' ) ),
                    'nextText'        => __( 'Next', 'invoicing' ),
                    'prevText'        => __( 'Prev', 'invoicing' ),
                    'currentText'     => __( 'Today', 'invoicing' ),
                    'closeText'       => __( 'Done', 'invoicing' ),
                    'clearText'       => __( 'Clear', 'invoicing' ),
                ),
                'time_picker'  => array(
                    'timeOnlyTitle' => __( 'Choose Time', 'invoicing' ),
                    'timeText'      => __( 'Time', 'invoicing' ),
                    'hourText'      => __( 'Hour', 'invoicing' ),
                    'minuteText'    => __( 'Minute', 'invoicing' ),
                    'secondText'    => __( 'Second', 'invoicing' ),
                    'currentText'   => __( 'Now', 'invoicing' ),
                    'closeText'     => __( 'Done', 'invoicing' ),
                    'timeFormat'    => _x( 'hh:mm TT', 'Valid formatting string, as per http://trentrichardson.com/examples/timepicker/', 'invoicing' ),
                    'controlType'   => 'select',
                    'stepMinute'    => 5,
                ),
            ),
            'strings' => array(
                'upload_file'  => __( 'Use this file', 'invoicing' ),
                'upload_files' => __( 'Use these files', 'invoicing' ),
                'remove_image' => __( 'Remove Image', 'invoicing' ),
                'remove_file'  => __( 'Remove', 'invoicing' ),
                'file'         => __( 'File:', 'invoicing' ),
                'download'     => __( 'Download', 'invoicing' ),
                'check_toggle' => __( 'Select / Deselect All', 'invoicing' ),
            ),
        );

        wp_localize_script( 'wpinv-gdmbx-script', 'gdmbx2_l10', apply_filters( 'wpinv_gdmbx2_localized_data', $localize ) );
        wp_enqueue_script( 'wpinv-admin-script' );
        
        $localize                               = array();
        $localize['ajax_url']                   = admin_url( 'admin-ajax.php' );
        $localize['post_ID']                    = isset( $post->ID ) ? $post->ID : '';
        $localize['wpinv_nonce']                = wp_create_nonce( 'wpinv-nonce' );
        $localize['add_invoice_note_nonce']     = wp_create_nonce( 'add-invoice-note' );
        $localize['delete_invoice_note_nonce']  = wp_create_nonce( 'delete-invoice-note' );
        $localize['invoice_item_nonce']         = wp_create_nonce( 'invoice-item' );
        $localize['billing_details_nonce']      = wp_create_nonce( 'get-billing-details' );
        $localize['tax']                        = wpinv_tax_amount();
        $localize['discount']                   = wpinv_discount_amount();
        $localize['currency_symbol']            = wpinv_currency_symbol();
        $localize['currency_pos']               = wpinv_currency_position();
        $localize['thousand_sep']               = wpinv_thousands_seperator();
        $localize['decimal_sep']                = wpinv_decimal_seperator();
        $localize['decimals']                   = wpinv_decimals();
        $localize['save_invoice']               = __( 'Save Invoice', 'invoicing' );
        $localize['status_publish']             = wpinv_status_nicename( 'publish' );
        $localize['status_pending']             = wpinv_status_nicename( 'pending' );
        $localize['delete_tax_rate']            = __( 'Are you sure you wish to delete this tax rate?', 'invoicing' );
        $localize['OneItemMin']                 = __( 'Invoice must contain at least one item', 'invoicing' );
        $localize['DeleteInvoiceItem']          = __( 'Are you sure you wish to delete this item?', 'invoicing' );
        $localize['FillBillingDetails']         = __( 'Fill the user\'s billing information? This will remove any currently entered billing information', 'invoicing' );
        $localize['confirmCalcTotals']           = __( 'Recalculate totals? This will recalculate totals based on the user billing country. If no billing country is set it will use the base country.', 'invoicing' );
        
        wp_localize_script( 'wpinv-admin-script', 'WPInv_Admin', $localize );
    }
    
    public function admin_body_class( $classes ) {
        global $pagenow;
        
        $page = isset( $_GET['page'] ) ? strtolower( $_GET['page'] ) : false;

        $add_class = false;
        if ( $pagenow == 'admin.php' && $page ) {
            $add_class = strpos( $page, 'wpinv-' );
        }
        
        $settings_class = array();
        if ( $page == 'wpinv-settings' ) {
            if ( !empty( $_REQUEST['tab'] ) ) {
                $settings_class[] = sanitize_text_field( $_REQUEST['tab'] );
            }
            
            if ( !empty( $_REQUEST['section'] ) ) {
                $settings_class[] = sanitize_text_field( $_REQUEST['section'] );
            }
            
            $settings_class[] = isset( $_REQUEST['wpi_sub'] ) && $_REQUEST['wpi_sub'] !== '' ? sanitize_text_field( $_REQUEST['wpi_sub'] ) : 'main';
        }
        
        if ( !empty( $settings_class ) ) {
            $classes .= ' wpi-' . wpinv_sanitize_key( implode( $settings_class, '-' ) );
        }
        
        $post_type = wpinv_admin_post_type();
        
        if ( $post_type == 'wpi_invoice' || $add_class !== false ) {
            return $classes .= ' wpinv';
        } else {
            return $classes;
        }

        return $classes;
    }
    
    public function admin_print_scripts_edit_php() {
        $post_type = wpinv_admin_post_type();
        
        if ( $post_type == 'wpi_item' ) {
            wp_enqueue_script( 'wpinv-inline-edit-post', WPINV_PLUGIN_URL . 'assets/js/quick-edit.js', array( 'jquery', 'inline-edit-post' ), '', true );
        }
    }
    
    public function wpinv_actions() {
        if ( isset( $_REQUEST['wpi_action'] ) ) {
            do_action( 'wpinv_' . wpinv_sanitize_key( $_REQUEST['wpi_action'] ), $_REQUEST );
        }
    }
}