<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wpgeodirectory.com
 * @since      1.0.0
 *
 * @package    Invoicing_Paypal_Pro
 * @subpackage Invoicing_Paypal_Pro/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Invoicing_Paypal_Pro
 * @subpackage Invoicing_Paypal_Pro/includes
 * @author     GeoDirectory Team <info@wpgeodirectory.com>
 */
class Invoicing_Paypal_Pro {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Invoicing_Paypal_Pro_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'invoicing-paypal-pro';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
                new PaypalPro();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Invoicing_Paypal_Pro_Loader. Orchestrates the hooks of the plugin.
	 * - Invoicing_Paypal_Pro_i18n. Defines internationalization functionality.
	 * - Invoicing_Paypal_Pro_Admin. Defines all hooks for the admin area.
	 * - Invoicing_Paypal_Pro_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-invoicing-paypal-pro-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-invoicing-paypal-pro-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-invoicing-paypal-pro-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-invoicing-paypal-pro-public.php';

		$this->loader = new Invoicing_Paypal_Pro_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Invoicing_Paypal_Pro_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Invoicing_Paypal_Pro_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
            $plugin_admin = new Invoicing_Paypal_Pro_Admin( $this->get_plugin_name(), $this->get_version() );
            
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
            
            $this->loader->add_action( 'wpinv_payment_gateways', $plugin_admin, 'add_gateway' );
            $this->loader->add_filter( 'wpinv_gateway_settings_paypalpro', $plugin_admin, 'paypalpro_settings'  );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Invoicing_Paypal_Pro_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
                
                $this->loader->add_action( 'wpinv_paypalpro_cc_form', $plugin_public, 'paypalpro_form', 10, 1 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Invoicing_Paypal_Pro_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}

class PaypalPro{
    //Configuration Options
    var $apiUsername = ''; 
    var $apiPassword = '';
    var $apiSignature = '';
    var $apiEndpoint = 'https://api-3t.paypal.com/nvp';
    var $subject = '';
    var $authToken = '';
    var $authSignature = '';
    var $authTimestamp = '';
    var $useProxy = FALSE;
    var $paypalURL = 'https://www.paypal.com/webscr&cmd=_express-checkout&token=';
    var $version = '65.1';
    var $ackSuccess = 'SUCCESS';
    var $ackSuccessWarning = 'SUCCESSWITHWARNING';
    
    public function __construct($config = array()){
        $ppp_cred = get_option('wpinv_settings');
        if(isset($ppp_cred['paypalpro_api_username'])) $this->apiUsername = $ppp_cred['paypalpro_api_username'];
        if(isset($ppp_cred['paypalpro_api_password'])) $this->apiPassword = $ppp_cred['paypalpro_api_password'];
        if(isset($ppp_cred['paypalpro_api_signature'])) $this->apiSignature = $ppp_cred['paypalpro_api_signature'];
        if(isset($ppp_cred['paypalpro_sandbox']) AND $ppp_cred['paypalpro_sandbox'] == 1){
            $this->apiEndpoint = 'https://api-3t.sandbox.paypal.com/nvp';
            $this->paypalURL = 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=';
        }
        add_action( 'wpinv_gateway_paypalpro', array($this, 'process_paypal_call') );
    }
    public function nvpHeader(){
        $nvpHeaderStr = "";
    
        if((!empty($this->apiUsername)) && (!empty($this->apiPassword)) && (!empty($this->apiSignature)) && (!empty($subject))) {
            $authMode = "THIRDPARTY";
        }else if((!empty($this->apiUsername)) && (!empty($this->apiPassword)) && (!empty($this->apiSignature))) {
            $authMode = "3TOKEN";
        }elseif (!empty($this->authToken) && !empty($this->authSignature) && !empty($this->authTimestamp)) {
            $authMode = "PERMISSION";
        }elseif(!empty($subject)) {
            $authMode = "FIRSTPARTY";
        }
        
        switch($authMode) {
            case "3TOKEN" : 
                $nvpHeaderStr = "&PWD=".$this->apiPassword."&USER=".$this->apiUsername."&SIGNATURE=".$this->apiSignature;
                break;
            case "FIRSTPARTY" :
                $nvpHeaderStr = "&SUBJECT=".$this->subject;
                break;
            case "THIRDPARTY" :
                $nvpHeaderStr = "&PWD=".$this->apiPassword."&USER=".$this->apiUsername."&SIGNATURE=".$this->apiSignature."&SUBJECT=".$subject;
                break;		
            case "PERMISSION" :
                $nvpHeaderStr = $this->formAutorization($this->authToken,$this->authSignature,$this->authTimestamp);
                break;
        }
        return $nvpHeaderStr;
    }
    
    /**
      * hashCall: Function to perform the API call to PayPal using API signature
      * @methodName is name of API  method.
      * @nvpStr is nvp string.
      * returns an associtive array containing the response from the server.
    */
    public function hashCall($methodName,$nvpStr){
        // form header string
        $args = array();
        $nvpheader = $this->nvpHeader();

    
        //turning off the server and peer verification(TrustManager Concept).
        $args['sslverify'] = false;
            
        //in case of permission APIs send headers as HTTPheders
        if(!empty($this->authToken) && !empty($this->authSignature) && !empty($this->authTimestamp)){
            $headers_array[] = "X-PP-AUTHORIZATION: ".$nvpheader;
            $args['headers'] = $headers_array;
        }
        else{
            $nvpStr = $nvpheader.$nvpStr;
        }
    
        //check if version is included in $nvpStr else include the version.
        if(strlen(str_replace('VERSION=', '', strtoupper($nvpStr))) == strlen($nvpStr)) {
            $nvpStr = "&VERSION=" . $this->version . $nvpStr;	
        }
        
        $nvpreq="METHOD=".$methodName.$nvpStr;
        $args['timeout'] = 30;
        $args['body'] = $nvpreq;
        
        //getting response from server
        
        $response = wp_remote_post($this->apiEndpoint, $args); 
        if(is_wp_error($response)) return $response;
        
        //convrting NVPResponse to an Associative Array
        parse_str($response['body'], $resArray);
        return $resArray;
    }
    
    public function formAutorization($auth_token,$auth_signature,$auth_timestamp){
        $authString="token=".$auth_token.",signature=".$auth_signature.",timestamp=".$auth_timestamp ;
        return $authString;
    }
    
    public function paypalCall($params){
        /*
         * Construct the request string that will be sent to PayPal.
         * The variable $nvpstr contains all the variables and is a
         * name value pair string with & as a delimiter
         */
        
        $recurringStr = (array_key_exists("recurring",$params) && $params['recurring'] == 'Y')?'&RECURRING=Y':'';
        $nvpstr = "&PAYMENTACTION=".$params['paymentAction']."&AMT=".$params['amount']."&CREDITCARDTYPE=".$params['creditCardType']."&ACCT=".$params['creditCardNumber']."&EXPDATE=".$params['expMonth'].$params['expYear']."&CVV2=".$params['cvv2']."&FIRSTNAME=".$params['firstName']."&LASTNAME=".$params['lastName']."&STREET=".$params['street']."&CITY=".$params['city']."&STATE=".$params['state']."&ZIP=".$params['zip']."&COUNTRYCODE=".$params['countryCode']."&CURRENCYCODE=".$params['currencyCode'].$recurringStr;
    
        /* Make the API call to PayPal, using API signature.
           The API response is stored in an associative array called $resArray */
        
        $resArray = $this->hashCall("DoDirectPayment",$nvpstr);
        return $resArray;
    }
    
    
    public function process_paypal_call($purchase_data){
        if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'wpi-gateway' ) ) {
            wp_die( __( 'Nonce verification has failed', 'invoicing' ), __( 'Error', 'invoicing' ), array( 'response' => 403 ) );
        }
        
        // Collect payment data
        $payment_data = array(
            'price'         => $purchase_data['price'],
            'date'          => $purchase_data['date'],
            'user_email'    => $purchase_data['user_email'],
            'invoice_key'   => $purchase_data['invoice_key'],
            'currency'      => wpinv_get_currency(),
            'items'         => $purchase_data['items'],
            'user_info'     => $purchase_data['user_info'],
            'cart_details'  => $purchase_data['cart_details'],
            'gateway'       => 'paypalpro',
            'status'        => 'pending'
        );

        // Record the pending payment
        $invoice = wpinv_get_invoice( $purchase_data['invoice_id'] );
        if ( !empty( $invoice ) ) {
            $ppp_card  = !empty( $_POST['paypalpro'] ) ? $_POST['paypalpro'] : array();
            $card_defaults      = array(
                'cc_owner'          => $invoice->get_user_full_name(),
                'cc_number'         => false,
                'cc_expire_month'   => false,
                'cc_expire_year'    => false,
                'cc_cvv2'           => false,
            );
            $ppp_card = wp_parse_args( $ppp_card, $card_defaults );
            


            if ( empty( $ppp_card['cc_owner'] ) ) {
                wpinv_set_error( 'empty_card_name', __( 'Name on the card is required!', 'invoicing'));
            }
            if ( empty( $ppp_card['cc_number'] ) ) {
                wpinv_set_error( 'empty_card', __( 'Card number is required!', 'invoicing'));
            }
            if ( empty( $ppp_card['cc_expire_month'] ) or $ppp_card['cc_expire_month'] > 12 or $ppp_card['cc_expire_month'] < 0 ) {
                wpinv_set_error( 'empty_month', __( 'Invalid card expire month!', 'invoicing'));
            }
            if ( empty( $ppp_card['cc_expire_year'] ) ) {
                wpinv_set_error( 'empty_year', __( 'You must enter a card expiration year!', 'invoicing'));
            }
            if ( empty( $ppp_card['cc_cvv2'] ) ) {
                wpinv_set_error( 'empty_cvv2', __( 'You must enter a valid CVV2!', 'invoicing' ) );
            }

            $errors = wpinv_get_errors();

            if ( empty( $errors ) ) {
                $invoice_id = $invoice->ID;
                $quantities_enabled = wpinv_item_quantities_enabled();
                $use_taxes          = wpinv_use_taxes();

                $nameArray = explode(' ',$ppp_card['cc_owner']);

                    //Payment details
                $paypalParams = array(
                    'paymentAction' => 'Sale',
                    'amount' => wpinv_sanitize_amount( $invoice->get_total() ),
                    'currencyCode' => wpinv_get_currency(),
                    'creditCardType' => $ppp_card['card_type'],
                    'creditCardNumber' => trim(str_replace(" ","",$ppp_card['cc_number'])),
                    'expMonth' => $ppp_card['cc_expire_month'],
                    'expYear' => $ppp_card['cc_expire_year'],
                    'cvv2' => $ppp_card['cc_cvv2'],
                    'firstName' => $invoice->get_first_name(),
                    'lastName' => $invoice->get_last_name(),
                    'street'  => $invoice->get_address(),
                    'phone' => $invoice->phone,
                    'city' => $invoice->city,
                    'state'  => $_POST['state'],
                    'zip'	=> $invoice->zip,
                    'countryCode' => $invoice->country,
                );

                try {
                    $response = $this->paypalCall($paypalParams);
                    $paymentStatus = strtoupper($response["ACK"]);
                    if ($paymentStatus == "SUCCESS"){
                            wpinv_update_payment_status( $invoice_id, 'publish' );
                            wpinv_set_payment_transaction_id( $invoice_id, $response['TRANSACTIONID'] );
                            
                            $message = wp_sprintf( __( 'Paypal Pro Payment with transaction id %s was successfull', 'invoicing' ), $response['TRANSACTIONID'] );
                            wpinv_insert_payment_note( $invoice_id, $message );
                            do_action( 'wpinv_paypalpro_handle_response', $response, $invoice );
                            wpinv_clear_errors();
                            wpinv_empty_cart();
                            wpinv_send_to_success_page( array( 'invoice_key' => $invoice->get_key() ) );

                    }else{
                        $error = wp_sprintf( __( 'Paypal Pro payment error occurred. %s', 'invoicing' ), $response['L_LONGMESSAGE0'] );
                        wpinv_set_error( 'payment_error', $error );
                        wpinv_record_gateway_error( $error, $response );
                        wpinv_insert_payment_note( $invoice_id, $error );

                        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                    }

                    } catch ( PaypalProException $e ) {
                        wpinv_set_error( 'request_error', $e->getMessage() );
                        wpinv_record_gateway_error( wp_sprintf( __( 'Paypal Pro payment error occurred. %s', 'invoicing' ), $e->getMessage() ) );
                        wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
                    }
            } else {
                wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
            }
        } else {
            wpinv_record_gateway_error( wp_sprintf( __( 'Paypal Pro payment error occurred. Payment failed while processing a Paypal pro payment. Payment data: %s', 'invoicing' ), print_r( $payment_data, true ) ), $invoice );
            wpinv_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['wpi-gateway'] );
        }
        
        /*Paypal Starts here*/
    }
    
}
