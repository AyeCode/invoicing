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
            self::$instance->notes      = new WPInv_Notes();
            self::$instance->reports    = new WPInv_Reports();
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
        
        if ( class_exists( 'BuddyPress' ) ) {
            add_action( 'bp_include', array( &$this, 'bp_invoicing_init' ) );
        }

        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
        
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
            add_action( 'admin_body_class', array( &$this, 'admin_body_class' ) );
        } else {
            add_filter( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
        }
        
        /**
         * Fires after the setup of all WPInv_Plugin actions.
         *
         * @since 1.0.0
         *
         * @param WPInv_Plugin $this. Current WPInv_Plugin instance. Passed by reference.
         */
        do_action_ref_array( 'wpinv_actions', array( &$this ) );

        add_action( 'admin_init', array( &$this, 'activation_redirect') );
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
        require_once( WPINV_PLUGIN_DIR . 'includes/wpinv-tax-functions.php' );
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
        //require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-db.php' );
        //require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-subscriptions-db.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-invoice.php' );
        //require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-subscription.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-item.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-notes.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-session.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-ajax.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-api.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-reports.php' );
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-shortcodes.php' );
        if ( !class_exists( 'Geodir_EUVat' ) ) {
            require_once( WPINV_PLUGIN_DIR . 'includes/libraries/wpinv-euvat/class-wpinv-euvat.php' );
        }
        
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
        
        if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/wpinv-upgrade-functions.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/wpinv-admin-functions.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/admin-meta-boxes.php' );
            //require_once( WPINV_PLUGIN_DIR . 'includes/admin/class-wpinv-recurring-admin.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/meta-boxes/class-mb-invoice-details.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/meta-boxes/class-mb-invoice-items.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/meta-boxes/class-mb-invoice-notes.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/meta-boxes/class-mb-invoice-address.php' );
            require_once( WPINV_PLUGIN_DIR . 'includes/admin/admin-pages.php' );
            //require_once( WPINV_PLUGIN_DIR . 'includes/admin/subscriptions.php' );
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

    public function activation_redirect() {
        // Bail if no activation redirect
        if ( !get_transient( '_wpinv_activation_redirect' ) ) {
            return;
        }

        // Delete the redirect transient
        delete_transient( '_wpinv_activation_redirect' );

        // Bail if activating from network, or bulk
        if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
            return;
        }

        wp_safe_redirect( admin_url( 'admin.php?page=wpinv-settings&tab=general' ) );
        exit;
    }
    
    public function enqueue_scripts() {
        $suffix       = '';//defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        
        wp_deregister_style( 'font-awesome' );
        wp_register_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome' . $suffix . '.css', array(), '4.7.0' );
        wp_enqueue_style( 'font-awesome' );
        
        wp_register_style( 'wpinv_front_style', WPINV_PLUGIN_URL . 'assets/css/invoice-front.css', array(), WPINV_VERSION );
        wp_enqueue_style( 'wpinv_front_style' );
               
        // Register scripts
        wp_register_script( 'jquery-blockui', WPINV_PLUGIN_URL . 'assets/js/jquery.blockUI.min.js', array( 'jquery' ), '2.70', true );
        wp_register_script( 'wpinv-front-script', WPINV_PLUGIN_URL . 'assets/js/invoice-front' . $suffix . '.js', array( 'jquery', 'wpinv-vat-script' ),  WPINV_VERSION );
        
        $localize                         = array();
        $localize['ajax_url']             = admin_url( 'admin-ajax.php' );
        $localize['nonce']                = wp_create_nonce( 'wpinv-nonce' );
        $localize['currency_symbol']      = wpinv_currency_symbol();
        $localize['currency_pos']         = wpinv_currency_position();
        $localize['thousand_sep']         = wpinv_thousands_separator();
        $localize['decimal_sep']          = wpinv_decimal_separator();
        $localize['decimals']             = wpinv_decimals();
        
        $localize = apply_filters( 'wpinv_front_js_localize', $localize );
        
        wp_enqueue_script( 'jquery-blockui' );
        wp_enqueue_script( 'wpinv-front-script' );
        wp_localize_script( 'wpinv-front-script', 'WPInv', $localize );
    }
    
    public function admin_enqueue_scripts() {
        global $post, $pagenow;
        
        $post_type  = wpinv_admin_post_type();
        $suffix     = '';//defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        
        wp_deregister_style( 'font-awesome' );
        wp_register_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome' . $suffix . '.css', array(), '4.7.0' );
        wp_enqueue_style( 'font-awesome' );
        
        wp_register_style( 'jquery-ui-css', WPINV_PLUGIN_URL . 'assets/css/jquery-ui' . $suffix . '.css', array(), '1.8.16' );
        wp_enqueue_style( 'jquery-ui-css' );
        
        wp_register_style( 'jquery-chosen', WPINV_PLUGIN_URL . 'assets/css/chosen' . $suffix . '.css', array(), '1.6.2' );
        wp_enqueue_style( 'jquery-chosen' );

        wp_register_script( 'jquery-chosen', WPINV_PLUGIN_URL . 'assets/js/chosen.jquery' . $suffix . '.js', array( 'jquery' ), '1.6.2' );
        wp_enqueue_script( 'jquery-chosen' );
        
        wp_register_style( 'wpinv_meta_box_style', WPINV_PLUGIN_URL . 'assets/css/meta-box.css', array(), WPINV_VERSION );
        wp_enqueue_style( 'wpinv_meta_box_style' );
        
        wp_register_style( 'wpinv_admin_style', WPINV_PLUGIN_URL . 'assets/css/admin.css', array(), WPINV_VERSION );
        wp_enqueue_style( 'wpinv_admin_style' );
        
        if ( $post_type == 'wpi_discount' || $post_type == 'wpi_invoice' && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        wp_register_script( 'jquery-blockui', WPINV_PLUGIN_URL . 'assets/js/jquery.blockUI.min.js', array( 'jquery' ), '2.70', true );
        
        wp_register_script( 'wpinv-admin-script', WPINV_PLUGIN_URL . 'assets/js/admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui' ),  WPINV_VERSION );
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
        $localize['thousand_sep']               = wpinv_thousands_separator();
        $localize['decimal_sep']                = wpinv_decimal_separator();
        $localize['decimals']                   = wpinv_decimals();
        $localize['save_invoice']               = __( 'Save Invoice', 'invoicing' );
        $localize['status_publish']             = wpinv_status_nicename( 'publish' );
        $localize['status_pending']             = wpinv_status_nicename( 'wpi-pending' );
        $localize['delete_tax_rate']            = __( 'Are you sure you wish to delete this tax rate?', 'invoicing' );
        $localize['OneItemMin']                 = __( 'Invoice must contain at least one item', 'invoicing' );
        $localize['DeleteInvoiceItem']          = __( 'Are you sure you wish to delete this item?', 'invoicing' );
        $localize['FillBillingDetails']         = __( 'Fill the user\'s billing information? This will remove any currently entered billing information', 'invoicing' );
        $localize['confirmCalcTotals']          = __( 'Recalculate totals? This will recalculate totals based on the user billing country. If no billing country is set it will use the base country.', 'invoicing' );
        $localize['AreYouSure']                 = __( 'Are you sure?', 'invoicing' );
        $localize['hasGD']                      = wpinv_gd_active();
        $localize['hasPM']                      = wpinv_pm_active();
        $localize['emptyInvoice']               = __( 'Add atleast one item to save invoice!', 'invoicing' );
        $localize['deletePackage']              = __( 'GD package items should be deleted from GD payment manager only, otherwise it will break invoices that created with this package!', 'invoicing' );
        $localize['deletePackages']             = __( 'GD package items should be deleted from GD payment manager only', 'invoicing' );
        $localize['deleteInvoiceFirst']         = __( 'This item is in use! Before delete this item, you need to delete all the invoice(s) using this item.', 'invoicing' );

        $localize = apply_filters( 'wpinv_admin_js_localize', $localize );

        wp_localize_script( 'wpinv-admin-script', 'WPInv_Admin', $localize );
    }
    
    public function admin_body_class( $classes ) {
        global $pagenow, $post, $current_screen;
        
        if ( !empty( $current_screen->post_type ) && ( $current_screen->post_type == 'wpi_invoice' || $current_screen->post_type == 'wpi_quote' ) ) {
            $classes .= ' wpinv-cpt';
        }
        
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

        if ( $post_type == 'wpi_invoice' || $post_type == 'wpi_quote' || $add_class !== false ) {
            return $classes .= ' wpinv';
        }
        
        if ( $pagenow == 'post.php' && $post_type == 'wpi_item' && !empty( $post ) && !wpinv_item_is_editable( $post ) ) {
            $classes .= ' wpi-editable-n';
        }

        return $classes;
    }
    
    public function admin_print_scripts_edit_php() {

    }
    
    public function wpinv_actions() {
        if ( isset( $_REQUEST['wpi_action'] ) ) {
            do_action( 'wpinv_' . wpinv_sanitize_key( $_REQUEST['wpi_action'] ), $_REQUEST );
        }
    }
    
    public function pre_get_posts( $wp_query ) {
        if ( !empty( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] == 'wpi_invoice' && is_user_logged_in() && is_single() && $wp_query->is_main_query() ) {
            $wp_query->query_vars['post_status'] = array_keys( wpinv_get_invoice_statuses() );
        }
        
        return $wp_query;
    }
    
    public function bp_invoicing_init() {
        require_once( WPINV_PLUGIN_DIR . 'includes/class-wpinv-bp-core.php' );
    }
}