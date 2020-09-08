<?php
/**
 * Contains the main REST API Class
 *
 * @package  Invoicing
 * @since    1.0.19
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main API class
 */
class WPInv_API {

    /**
     * The invoices controller class.
     *
     * @param WPInv_REST_Invoice_Controller
     */
    public $invoices;

    /**
     * The items controller class.
     *
     * @param WPInv_REST_Items_Controller
     */
    public $items;

    /**
     * The discounts controller class.
     *
     * @param WPInv_REST_Discounts_Controller
     */
    public $discounts;

    /**
     * Class constructor. 
     * 
     * @since 1.0.13
     * Sets the API namespace and inits hooks
     */
    public function __construct() {

        // Init api controllers.
        $this->invoices  = new WPInv_REST_Invoice_Controller();
        $this->items     = new WPInv_REST_Items_Controller();
        $this->discounts = new WPInv_REST_Discounts_Controller();

        // Fires after loading the rest api.
        do_action( 'getpaid_rest_api_loaded', $this );
    }

}
