<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_Recurring_Admin {
    
    /**
     * Get started
     */
    function __construct() {
        self::actions();
        self::filters();
    }

     /**
     * Add actions
     *
     * @since  1.0.0
     * @return void
     */
    private function actions() {
        add_action( 'admin_menu', array( $this, 'add_submenu' ), 11 );

        // Register styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

        // Register scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Add filters
     *
     * @since  1.0.0
     * @return void
     */
    private function filters() {
    }
    
    public function add_submenu() {
        global $wpi_subscriptions_page;
        
        $wpi_subscriptions_page = add_submenu_page( 
            'wpinv', 
            __( 'Subscriptions', 'invoicing' ), 
            __( 'Subscriptions', 'invoicing' ), 
            'manage_options', 
            'wpinv-subscriptions', 
            array( $this, 'subscriptions_page' ) 
        );
    }
    
    public function subscriptions_page() {
        wpinv_recurring_subscriptions_list();
    }

    /**
     * Load frontend styles
     *
     * @since  1.0.0
     * @return bool
     */
    public function enqueue_styles() {
    }

    /**
     * Load frontend javascript files
     *
     * @since  1.0.0
     * @return bool
     */
    public function enqueue_scripts() {
    }
}

new WPInv_Recurring_Admin();