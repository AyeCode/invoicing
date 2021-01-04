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
     * The settings controller class.
     *
     * @param GetPaid_REST_Settings_Controller
     */
    public $settings;

    /**
     * The reports controller class.
     *
     * @param GetPaid_REST_Reports_Controller
     */
    public $reports;

    /**
     * The sales report controller class.
     *
     * @param GetPaid_REST_Report_Sales_Controller
     */
    public $sales;

    /**
     * The top sellers report controller class.
     *
     * @param GetPaid_REST_Report_Top_Sellers_Controller
     */
    public $top_sellers;

    /**
     * The top earners report controller class.
     *
     * @param GetPaid_REST_Report_Top_Earners_Controller
     */
    public $top_earners;

    /**
     * The invoice counts report controller class.
     *
     * @param GetPaid_REST_Report_Invoice_Counts_Controller
     */
    public $invoice_counts;

    /**
     * Class constructor. 
     * 
     * @since 1.0.13
     * Sets the API namespace and inits hooks
     */
    public function __construct() {

        // Init api controllers.
        $this->invoices       = new WPInv_REST_Invoice_Controller();
        $this->items          = new WPInv_REST_Items_Controller();
        $this->discounts      = new WPInv_REST_Discounts_Controller();
        $this->settings       = new GetPaid_REST_Settings_Controller();
        $this->reports        = new GetPaid_REST_Reports_Controller();
        $this->sales          = new GetPaid_REST_Report_Sales_Controller();
        $this->top_sellers    = new GetPaid_REST_Report_Top_Sellers_Controller();
        $this->top_earners    = new GetPaid_REST_Report_Top_Earners_Controller();
        $this->invoice_counts = new GetPaid_REST_Report_Invoice_Counts_Controller();

        // Fires after loading the rest api.
        do_action( 'getpaid_rest_api_loaded', $this );
    }

}
