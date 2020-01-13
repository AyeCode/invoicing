<?php
/**
 * Contains the main API class
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit;
}

/**
 * The main API class
 */
class WPInv_API {

    /**
     * @param string A prefix for our REST routes
     */
    public $api_namespace    = '';

    /**
     * @param WPInv_REST_Invoice_Controller Invoices controller
     */
    public $invoices_controller;
    
    /**
     * Class constructor. 
     * 
     * @since 1.0.13
     * Sets the API namespace and inits hooks
     */
    public function __construct( $api_namespace = 'invoicing/v1' ) {

        // Include controllers and related files
        $this->includes();

        // Set up class variables
        $this->api_namespace       = apply_filters( 'wpinv_rest_api_namespace', $api_namespace );
        $this->invoices_controller = new WPInv_REST_Invoice_Controller( $this->api_namespace );

        //Register REST routes
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }


	/**
	 * Registers routes
	 *
     * @since 1.0.13
	 */
	public function register_rest_routes() {

		//Invoices
		$this->invoices_controller->register_routes();
        
    }


    /**
     * Loads API files and controllers
     * 
     *  @return void
     */
    protected function includes() {
        
        // Invoices
        require_once( WPINV_PLUGIN_DIR . 'includes/api/class-wpinv-rest-invoice-controller.php' );

    }
    

}