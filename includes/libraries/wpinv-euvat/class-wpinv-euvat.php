<?php
// Exit if accessed directly.
if (!defined( 'ABSPATH' ) ) exit;

class WPInv_EUVat {
    private static $is_ajax = false;
    private static $default_country;
    private static $instance = false;
    
    public static function get_instance() {
        if ( !self::$instance ) {
            self::$instance = new self();
            self::$instance->actions();
        }

        return self::$instance;
    }
    
    public function __construct() {
        self::$is_ajax          = defined( 'DOING_AJAX' ) && DOING_AJAX;
        self::$default_country  = wpinv_get_default_country();
    }
    
    public static function actions() {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_scripts' ) );
            add_action( 'wpinv_settings_sections_taxes', array( self::$instance, 'section_vat_settings' ) ); 
            add_action( 'wpinv_settings_taxes', array( self::$instance, 'vat_settings' ) );
            add_filter( 'wpinv_settings_taxes-vat_sanitize', array( self::$instance, 'sanitize_vat_settings' ) );
            add_filter( 'wpinv_settings_taxes-vat_rates_sanitize', array( self::$instance, 'sanitize_vat_rates' ) );
            add_action( 'wp_ajax_wpinv_add_vat_class', array( self::$instance, 'add_class' ) );
            add_action( 'wp_ajax_nopriv_wpinv_add_vat_class', array( self::$instance, 'add_class' ) );
            add_action( 'wp_ajax_wpinv_delete_vat_class', array( self::$instance, 'delete_class' ) );
            add_action( 'wp_ajax_nopriv_wpinv_delete_vat_class', array( self::$instance, 'delete_class' ) );
            add_action( 'wp_ajax_wpinv_update_vat_rates', array( self::$instance, 'update_eu_rates' ) );
            add_action( 'wp_ajax_nopriv_wpinv_update_vat_rates', array( self::$instance, 'update_eu_rates' ) );
            add_action( 'wp_ajax_wpinv_geoip2', array( self::$instance, 'geoip2_download_database' ) );
            add_action( 'wp_ajax_nopriv_wpinv_geoip2', array( self::$instance, 'geoip2_download_database' ) );
        }
        
        add_action( 'wp_enqueue_scripts', array( self::$instance, 'enqueue_vat_scripts' ) );
        add_filter( 'wpinv_default_billing_country', array( self::$instance, 'get_user_country' ), 10 );
        add_filter( 'wpinv_get_user_country', array( self::$instance, 'set_user_country' ), 10 );
        add_action( 'wp_ajax_wpinv_vat_validate', array( self::$instance, 'ajax_vat_validate' ) );
        add_action( 'wp_ajax_nopriv_wpinv_vat_validate', array( self::$instance, 'ajax_vat_validate' ) );
        add_action( 'wp_ajax_wpinv_vat_reset', array( self::$instance, 'ajax_vat_reset' ) );
        add_action( 'wp_ajax_nopriv_wpinv_vat_reset', array( self::$instance, 'ajax_vat_reset' ) );
        add_action( 'wpinv_invoice_print_after_line_items', array( self::$instance, 'show_vat_notice' ), 999, 1 );
        if ( wpinv_use_taxes() ) {
            add_action( 'wpinv_after_billing_fields', array( self::$instance, 'checkout_vat_fields' ) );
            if ( self::allow_vat_rules() ) {
                add_action( 'wpinv_checkout_error_checks', array( self::$instance, 'checkout_vat_validate' ), 10, 2 );
                add_filter( 'wpinv_tax_rate', array( self::$instance, 'get_rate' ), 10, 4 );
            }
        }
    }        
    
    public static function get_eu_states( $sort = true ) {
        $eu_states = array( 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GB', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' );
        if ( $sort ) {
            $sort = sort( $eu_states );
        }
        
        return apply_filters( 'wpinv_get_eu_states', $eu_states, $sort );
    }
    
    public static function get_gst_countries( $sort = true ) {
        $gst_countries  = array( 'AU', 'NZ', 'CA', 'CN' );
        
        if ( $sort ) {
            $sort = sort( $gst_countries );
        }
        
        return apply_filters( 'wpinv_get_gst_countries', $gst_countries, $sort );
    }
    
    public static function is_eu_state( $country_code ) {
        $return = !empty( $country_code ) && in_array( strtoupper( $country_code ), self::get_eu_states() ) ? true : false;
                
        return apply_filters( 'wpinv_is_eu_state', $return, $country_code );
    }
    
    public static function is_gst_country( $country_code ) {
        $return = !empty( $country_code ) && in_array( strtoupper( $country_code ), self::get_gst_countries() ) ? true : false;
                
        return apply_filters( 'wpinv_is_gst_country', $return, $country_code );
    }
    
    public static function enqueue_vat_scripts() {
        $suffix     = '';//defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        
        wp_register_script( 'wpinv-vat-validation-script', WPINV_PLUGIN_URL . 'assets/js/jsvat' . $suffix . '.js', array( 'jquery' ),  WPINV_VERSION );
        wp_register_script( 'wpinv-vat-script', WPINV_PLUGIN_URL . 'assets/js/euvat' . $suffix . '.js', array( 'jquery' ),  WPINV_VERSION );
        
        $vat_name   = self::get_vat_name();
        
        $vars = array();
        $vars['UseTaxes'] = wpinv_use_taxes();
        $vars['EUStates'] = self::get_eu_states();
        $vars['NoRateSet'] = __( 'You have not set a rate. Do you want to continue?', 'invoicing' );
        $vars['EmptyCompany'] = __( 'Please enter your registered company name!', 'invoicing' );
        $vars['EmptyVAT'] = wp_sprintf( __( 'Please enter your %s number!', 'invoicing' ), $vat_name );
        $vars['TotalsRefreshed'] = wp_sprintf( __( 'The invoice totals will be refreshed to update the %s.', 'invoicing' ), $vat_name );
        $vars['ErrValidateVAT'] = wp_sprintf( __( 'Fail to validate the %s number!', 'invoicing' ), $vat_name );
        $vars['ErrResetVAT'] = wp_sprintf( __( 'Fail to reset the %s number!', 'invoicing' ), $vat_name );
        $vars['ErrInvalidVat'] = wp_sprintf( __( 'The %s number supplied does not have a valid format!', 'invoicing' ), $vat_name );
        $vars['ErrInvalidResponse'] = __( 'An invalid response has been received from the server!', 'invoicing' );
        $vars['ApplyVATRules'] = $vars['UseTaxes'] ? self::allow_vat_rules() : false;
        $vars['HideVatFields'] = $vars['ApplyVATRules'] ? self::hide_vat_fields() : true;
        $vars['ErrResponse'] = __( 'The request response is invalid!', 'invoicing' );
        $vars['ErrRateResponse'] = __( 'The get rate request response is invalid', 'invoicing' );
        $vars['PageRefresh'] = __( 'The page will be refreshed in 10 seconds to show the new options.', 'invoicing' );
        $vars['RequestResponseNotValidJSON'] = __( 'The get rate request response is not valid JSON', 'invoicing' );
        $vars['GetRateRequestFailed'] = __( 'The get rate request failed: ', 'invoicing' );
        $vars['NoRateInformationInResponse'] = __( 'The get rate request response does not contain any rate information', 'invoicing' );
        $vars['RatesUpdated'] = __( 'The rates have been updated. Press the save button to record these new rates.', 'invoicing' );
        $vars['IPAddressInformation'] = __( 'IP Address Information', 'invoicing' );
        $vars['VatValidating'] = wp_sprintf( __( 'Validating %s number...', 'invoicing' ), $vat_name );
        $vars['VatReseting'] = __( 'Reseting...', 'invoicing' );
        $vars['VatValidated'] = wp_sprintf( __( '%s number validated', 'invoicing' ), $vat_name );
        $vars['VatNotValidated'] = wp_sprintf( __( '%s number not validated', 'invoicing' ), $vat_name );
        $vars['ConfirmDeleteClass'] = __( 'Are you sure you wish to delete this rates class?', 'invoicing' );
        $vars['isFront'] = is_admin() ? false : true;
        $vars['checkoutNonce'] = wp_create_nonce( 'wpinv_checkout_nonce' );
        $vars['baseCountry'] = wpinv_get_default_country();
        $vars['disableVATSameCountry'] = ( self::same_country_rule() == 'no' ? true : false );
        $vars['disableVATSimpleCheck'] = wpinv_get_option( 'vat_offline_check' ) ? true : false;
        
        wp_enqueue_script( 'wpinv-vat-validation-script' );
        wp_enqueue_script( 'wpinv-vat-script' );
        wp_localize_script( 'wpinv-vat-script', 'WPInv_VAT_Vars', $vars );
    }

    public static function enqueue_admin_scripts() {
        if( isset( $_GET['page'] ) && 'wpinv-settings' == $_GET['page'] ) {
            self::enqueue_vat_scripts();
        }
    }
    
    public static function section_vat_settings( $sections ) {
        if ( !empty( $sections ) ) {
            $sections['vat'] = __( 'EU VAT Settings', 'invoicing' );
            
            if ( self::allow_vat_classes() ) {
                $sections['vat_rates'] = __( 'EU VAT Rates', 'invoicing' );
            }
        }
        return $sections;
    }
    
    public static function vat_rates_settings() {
        $vat_classes = self::get_rate_classes();
        $vat_rates = array();
        $vat_class = isset( $_REQUEST['wpi_sub'] ) && $_REQUEST['wpi_sub'] !== '' && isset( $vat_classes[$_REQUEST['wpi_sub']] )? sanitize_text_field( $_REQUEST['wpi_sub'] ) : '_new';
        $current_url = remove_query_arg( 'wpi_sub' );
        
        $vat_rates['vat_rates_header'] = array(
            'id' => 'vat_rates_header',
            'name' => '<h3>' . __( 'Manage VAT Rates', 'invoicing' ) . '</h3>',
            'desc' => '',
            'type' => 'header',
            'size' => 'regular'
        );
        $vat_rates['vat_rates_class'] = array(
            'id'          => 'vat_rates_class',
            'name'        => __( 'Edit VAT Rates', 'invoicing' ),
            'desc'        => __( 'The standard rate will apply where no explicit rate is provided.', 'invoicing' ),
            'type'        => 'select',
            'options'     => array_merge( $vat_classes, array( '_new' => __( 'Add New Rate Class', 'invoicing' ) ) ),
            'chosen'      => true,
            'placeholder' => __( 'Select a VAT Rate', 'invoicing' ),
            'selected'    => $vat_class,
            'onchange'    => 'document.location.href="' . $current_url . '&wpi_sub=" + this.value;',
        );
        
        if ( $vat_class != '_standard' && $vat_class != '_new' ) {
            $vat_rates['vat_rate_delete'] = array(
                'id'   => 'vat_rate_delete',
                'type' => 'vat_rate_delete',
            );
        }
                    
        if ( $vat_class == '_new' ) {
            $vat_rates['vat_rates_settings'] = array(
                'id' => 'vat_rates_settings',
                'name' => '<h3>' . __( 'Add New Rate Class', 'invoicing' ) . '</h3>',
                'type' => 'header',
            );
            $vat_rates['vat_rate_name'] = array(
                'id'   => 'vat_rate_name',
                'name' => __( 'Name', 'invoicing' ),
                'desc' => __( 'A short name for the new VAT Rate class', 'invoicing' ),
                'type' => 'text',
                'size' => 'regular',
            );
            $vat_rates['vat_rate_desc'] = array(
                'id'   => 'vat_rate_desc',
                'name' => __( 'Description', 'invoicing' ),
                'desc' => __( 'Manage VAT Rate class', 'invoicing' ),
                'type' => 'text',
                'size' => 'regular',
            );
            $vat_rates['vat_rate_add'] = array(
                'id'   => 'vat_rate_add',
                'type' => 'vat_rate_add',
            );
        } else {
            $vat_rates['vat_rates'] = array(
                'id'   => 'vat_rates',
                'name' => '<h3>' . $vat_classes[$vat_class] . '</h3>',
                'desc' => self::get_class_desc( $vat_class ),
                'type' => 'vat_rates',
            );
        }
        
        return $vat_rates;
    }
    
    public static function vat_settings( $settings ) {
        if ( !empty( $settings ) ) {    
            $vat_settings = array();
            $vat_settings['vat_company_title'] = array(
                'id' => 'vat_company_title',
                'name' => '<h3>' . __( 'Your Company Details', 'invoicing' ) . '</h3>',
                'desc' => '',
                'type' => 'header',
                'size' => 'regular'
            );
            
            $vat_settings['vat_company_name'] = array(
                'id' => 'vat_company_name',
                'name' => __( 'Your Company Name', 'invoicing' ),
                'desc' => wp_sprintf(__( 'Your company name as it appears on your VAT return, you can verify it via your VAT ID on the %sEU VIES System.%s', 'invoicing' ), '<a href="http://ec.europa.eu/taxation_customs/vies/" target="_blank">', '</a>' ),
                'type' => 'text',
                'size' => 'regular',
            );
            
            $vat_settings['vat_number'] = array(
                'id'   => 'vat_number',
                'name' => __( 'Your VAT Number', 'invoicing' ),
                'type' => 'vat_number',
                'size' => 'regular',
            );

            $vat_settings['vat_settings_title'] = array(
                'id' => 'vat_settings_title',
                'name' => '<h3>' . __( 'Apply VAT Settings', 'invoicing' ) . '</h3>',
                'desc' => '',
                'type' => 'header',
                'size' => 'regular'
            );

            $vat_settings['apply_vat_rules'] = array(
                'id' => 'apply_vat_rules',
                'name' => __( 'Enable VAT Rules', 'invoicing' ),
                'desc' => __( 'Apply VAT to consumer sales from IP addresses within the EU, even if the billing address is outside the EU.', 'invoicing' ) . '<br><font style="color:red">' . __( 'Do not disable unless you know what you are doing.', 'invoicing' ) . '</font>',
                'type' => 'checkbox',
                'std' => '1'
            );

            /*
            $vat_settings['vat_allow_classes'] = array(
                'id' => 'vat_allow_classes',
                'name' => __( 'Allow the use of VAT rate classes', 'invoicing' ),
                'desc' =>  __( 'When enabled this option makes it possible to define alternative rate classes so rates for items that do not use the standard VAT rate in all member states can be defined.<br>A menu option will appear under the "Invoicing -> Settings -> Taxes -> EU VAT Rates" menu heading that will take you to a page on which new classes can be defined and rates entered. A meta-box will appear in the invoice page in which you are able to select one of the alternative classes you create so the rates associated with the class will be applied to invoice.<br>By default the standard rates class will be used just as they are when this option is not enabled.', 'invoicing' ),
                'type' => 'checkbox'
            );
            */

            $vat_settings['vat_prevent_b2c_purchase'] = array(
                'id' => 'vat_prevent_b2c_purchase',
                'name' => __( 'Prevent EU B2C Sales', 'invoicing' ),
                'desc' => __( 'Enable this option if you are not registered for VAT in the EU.', 'invoicing' ),
                'type' => 'checkbox'
            );



            $vat_settings['vat_same_country_rule'] = array(
                'id'          => 'vat_same_country_rule',
                'name'        => __( 'Same Country Rule', 'invoicing' ),
                'desc'        => __( 'Select how you want to handle VAT charge if sales are in the same country as the base country.', 'invoicing' ),
                'type'        => 'select',
                'options'     => array(
                    ''          => __( 'Normal', 'invoicing' ),
                    'no'        => __( 'No VAT', 'invoicing' ),
                    'always'    => __( 'Always apply VAT', 'invoicing' ),
                ),
                'placeholder' => __( 'Select an option', 'invoicing' ),
                'std'         => ''
            );

            $vat_settings['vat_checkout_title'] = array(
                'id' => 'vat_checkout_title',
                'name' => '<h3>' . __( 'Checkout Fields', 'invoicing' ) . '</h3>',
                'desc' => '',
                'type' => 'header',
                'size' => 'regular'
            );

            $vat_settings['vat_disable_fields'] = array(
                'id' => 'vat_disable_fields',
                'name' => __( 'Disable VAT Fields', 'invoicing' ),
                'desc' => __( 'Disable VAT fields if Invoicing is being used for GST.', 'invoicing' ) . '<br><font style="color:red">' . __( 'Do not disable if you have enabled Prevent EU B2C sales, otherwise Prevent EU B2C sales setting will not work.', 'invoicing' ) . '</font>',
                'type' => 'checkbox'
            );

            $vat_settings['vat_ip_lookup'] = array(
                'id'   => 'vat_ip_lookup',
                'name' => __( 'IP Country Look-up', 'invoicing' ),
                'type' => 'vat_ip_lookup',
                'size' => 'regular',
                'std' => 'default'
            );

            $vat_settings['hide_ip_address'] = array(
                'id' => 'hide_ip_address',
                'name' => __( 'Hide IP Info at Checkout', 'invoicing' ),
                'desc' => __( 'Hide the user IP info at checkout.', 'invoicing' ),
                'type' => 'checkbox'
            );

            $vat_settings['vat_ip_country_default'] = array(
                'id' => 'vat_ip_country_default',
                'name' => __( 'Enable IP Country as Default', 'invoicing' ),
                'desc' => __( 'Show the country of the users IP as the default country, otherwise the site default country will be used.', 'invoicing' ),
                'type' => 'checkbox'
            );

            $vat_settings['vies_validation_title'] = array(
                'id' => 'vies_validation_title',
                'name' => '<h3>' . __( 'VIES Validation', 'invoicing' ) . '</h3>',
                'desc' => '',
                'type' => 'header',
                'size' => 'regular'
            );

            $vat_settings['vat_vies_check'] = array(
                'id' => 'vat_vies_check',
                'name' => __( 'Disable VIES VAT ID Check', 'invoicing' ),
                'desc' => wp_sprintf( __( 'Prevent VAT numbers from being validated by the %sEU VIES System.%s', 'invoicing' ), '<a href="http://ec.europa.eu/taxation_customs/vies/" target="_blank">', '</a>' ),
                'type' => 'checkbox'
            );

            $vat_settings['vat_disable_company_name_check'] = array(
                'id' => 'vat_disable_company_name_check',
                'name' => __( 'Disable VIES Name Check', 'invoicing' ),
                'desc' => wp_sprintf( __( 'Prevent company name from being validated by the %sEU VIES System.%s', 'invoicing' ), '<a href="http://ec.europa.eu/taxation_customs/vies/" target="_blank">', '</a>' ),
                'type' => 'checkbox'
            );

            $vat_settings['vat_offline_check'] = array(
                'id' => 'vat_offline_check',
                'name' => __( 'Disable Basic Checks', 'invoicing' ),
                'desc' => __( 'This will disable basic JS correct format validation attempts, it is very rare this should need to be disabled.', 'invoicing' ),
                'type' => 'checkbox'
            );
            

            $settings['vat'] = $vat_settings;
            
            if ( self::allow_vat_classes() ) {
                $settings['vat_rates'] = self::vat_rates_settings();
            }
            
            $eu_fallback_rate = array(
                'id'   => 'eu_fallback_rate',
                'name' => '<h3>' . __( 'VAT rate for EU member states', 'invoicing' ) . '</h3>',
                'type' => 'eu_fallback_rate',
                'desc' => __( 'Enter the VAT rate to be charged for EU member states. You can edit the rates for each member state when a country rate has been set up by pressing this button.', 'invoicing' ),
                'std'  => '20',
                'size' => 'small'
            );
            $settings['rates']['eu_fallback_rate'] = $eu_fallback_rate;
        }

        return $settings;
    }
    // IP Geolocation
    public static function geoip2_download_database() {
        $upload_dir         = wp_upload_dir();
        
        $database_url       = 'http' . (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === 'on' ? 's' : '') . '://geolite.maxmind.com/download/geoip/database/';
        $destination_dir    = $upload_dir['basedir'] . '/invoicing';
        
        if ( !is_dir( $destination_dir ) ) { 
            mkdir( $destination_dir );
        }
        
        $database_files     = array(
            'country'   => array(
                'source'        => $database_url . 'GeoLite2-Country.mmdb.gz',
                'destination'   => $destination_dir . '/GeoLite2-Country.mmdb',
            ),
            'city'      => array(
                'source'        => $database_url . 'GeoLite2-City.mmdb.gz',
                'destination'   => $destination_dir . '/GeoLite2-City.mmdb',
            )
        );

        foreach( $database_files as $database => $files ) {
            $result = self::geoip2_download_file( $files['source'], $files['destination'] );
            
            if ( empty( $result['success'] ) ) {
                echo $result['message'];
                exit;
            }
            
            wpinv_update_option( 'wpinv_geoip2_date_updated', current_time( 'timestamp' ) );
            echo sprintf(__( 'GeoIp2 %s database updated successfully.', 'invoicing' ), $database ) . ' ';
        }
        
        exit;
    }
    
    public static function geoip2_download_file( $source_url, $destination_file ) {
        $success    = false;
        $message    = '';
        
        if ( !function_exists( 'download_url' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $temp_file  = download_url( $source_url );
        
        if ( is_wp_error( $temp_file ) ) {
            $message = sprintf( __( 'Error while downloading GeoIp2 database( %s ): %s', 'invoicing' ), $source_url, $temp_file->get_error_message() );
        } else {
            $handle = gzopen( $temp_file, 'rb' );
            
            if ( $handle ) {
                $fopen  = fopen( $destination_file, 'wb' );
                if ( $fopen ) {
                    while ( ( $data = gzread( $handle, 4096 ) ) != false ) {
                        fwrite( $fopen, $data );
                    }

                    gzclose( $handle );
                    fclose( $fopen );
                        
                    $success = true;
                } else {
                    gzclose( $handle );
                    $message = sprintf( __( 'Error could not open destination GeoIp2 database file for writing: %s', 'invoicing' ), $destination_file );
                }
            } else {
                $message = sprintf( __( 'Error could not open GeoIp2 database file for reading: %s', 'invoicing' ), $temp_file );
            }
            
            if ( file_exists( $temp_file ) ) {
                unlink( $temp_file );
            }
        }
        
        $return             = array();
        $return['success']  = $success;
        $return['message']  = $message;

        return $return;
    }
    
    public static function load_geoip2() {
        if ( defined( 'WPINV_GEOIP2_LODDED' ) ) {
            return;
        }
        
        if ( !class_exists( '\MaxMind\Db\Reader' ) ) {
            $maxmind_db_files = array(
                'Reader/Decoder.php',
                'Reader/InvalidDatabaseException.php',
                'Reader/Metadata.php',
                'Reader/Util.php',
                'Reader.php',
            );
            
            foreach ( $maxmind_db_files as $key => $file ) {
                require_once( WPINV_PLUGIN_DIR . 'includes/libraries/MaxMind/Db/' . $file );
            }
        }
        
        if ( !class_exists( '\GeoIp2\Database\Reader' ) ) {        
            $geoip2_files = array(
                'ProviderInterface.php',
                'Compat/JsonSerializable.php',
                'Database/Reader.php',
                'Exception/GeoIp2Exception.php',
                'Exception/AddressNotFoundException.php',
                'Exception/AuthenticationException.php',
                'Exception/HttpException.php',
                'Exception/InvalidRequestException.php',
                'Exception/OutOfQueriesException.php',
                'Model/AbstractModel.php',
                'Model/AnonymousIp.php',
                'Model/Country.php',
                'Model/City.php',
                'Model/ConnectionType.php',
                'Model/Domain.php',
                'Model/Enterprise.php',
                'Model/Insights.php',
                'Model/Isp.php',
                'Record/AbstractRecord.php',
                'Record/AbstractPlaceRecord.php',
                'Record/Country.php',
                'Record/City.php',
                'Record/Continent.php',
                'Record/Location.php',
                'Record/MaxMind.php',
                'Record/Postal.php',
                'Record/RepresentedCountry.php',
                'Record/Subdivision.php',
                'Record/Traits.php',
                'WebService/Client.php',
            );
            
            foreach ( $geoip2_files as $key => $file ) {
                require_once( WPINV_PLUGIN_DIR . 'includes/libraries/GeoIp2/' . $file );
            }
        }

        define( 'WPINV_GEOIP2_LODDED', true );
    }

    public static function geoip2_country_dbfile() {
        $upload_dir = wp_upload_dir();

        if ( !isset( $upload_dir['basedir'] ) ) {
            return false;
        }

        $filename = $upload_dir['basedir'] . '/invoicing/GeoLite2-Country.mmdb';
        if ( !file_exists( $filename ) ) {
            return false;
        }
        
        return $filename;
    }

    public static function geoip2_city_dbfile() {
        $upload_dir = wp_upload_dir();

        if ( !isset( $upload_dir['basedir'] ) ) {
            return false;
        }

        $filename = $upload_dir['basedir'] . '/invoicing/GeoLite2-City.mmdb';
        if ( !file_exists( $filename ) ) {
            return false;
        }
        
        return $filename;
    }

    public static function geoip2_country_reader() {
        try {
            self::load_geoip2();

            if ( $filename = self::geoip2_country_dbfile() ) {
                return new \GeoIp2\Database\Reader( $filename );
            }
        } catch( Exception $e ) {
            return false;
        }
        
        return false;
    }

    public static function geoip2_city_reader() {
        try {
            self::load_geoip2();

            if ( $filename = self::geoip2_city_dbfile() ) {
                return new \GeoIp2\Database\Reader( $filename );
            }
        } catch( Exception $e ) {
            return false;
        }
        
        return false;
    }

    public static function geoip2_country_record( $ip_address ) {
        try {
            $reader = self::geoip2_country_reader();

            if ( $reader ) {
                $record = $reader->country( $ip_address );
                
                if ( !empty( $record->country->isoCode ) ) {
                    return $record;
                }
            }
        } catch(\InvalidArgumentException $e) {
            wpinv_error_log( $e->getMessage(), 'GeoIp2 Lookup( ' . $ip_address . ' )' );
            
            return false;
        } catch(\GeoIp2\Exception\AddressNotFoundException $e) {
            wpinv_error_log( $e->getMessage(), 'GeoIp2 Lookup( ' . $ip_address . ' )' );
            
            return false;
        } catch( Exception $e ) {
            return false;
        }
        
        return false;
    }

    public static function geoip2_city_record( $ip_address ) {
        try {
            $reader = self::geoip2_city_reader();

            if ( $reader ) {
                $record = $reader->city( $ip_address );
                
                if ( !empty( $record->country->isoCode ) ) {
                    return $record;
                }
            }
        } catch(\InvalidArgumentException $e) {
            wpinv_error_log( $e->getMessage(), 'GeoIp2 Lookup( ' . $ip_address . ' )' );
            
            return false;
        } catch(\GeoIp2\Exception\AddressNotFoundException $e) {
            wpinv_error_log( $e->getMessage(), 'GeoIp2 Lookup( ' . $ip_address . ' )' );
            
            return false;
        } catch( Exception $e ) {
            return false;
        }
        
        return false;
    }

    public static function geoip2_country_code( $ip_address ) {
        $record = self::geoip2_country_record( $ip_address );
        return !empty( $record->country->isoCode ) ? $record->country->isoCode : wpinv_get_default_country();
    }

    // Find country by IP address.
    public static function get_country_by_ip( $ip = '' ) {
        global $wpinv_ip_address_country;
        
        if ( !empty( $wpinv_ip_address_country ) ) {
            return $wpinv_ip_address_country;
        }
        
        if ( empty( $ip ) ) {
            $ip = wpinv_get_ip();
        }

        $ip_country_service = wpinv_get_option( 'vat_ip_lookup' );
        $is_default         = empty( $ip_country_service ) || $ip_country_service === 'default' ? true : false;

        if ( !empty( $ip ) && $ip !== '127.0.0.1' ) { // For 127.0.0.1(localhost) use default country.
            if ( function_exists( 'geoip_country_code_by_name') && ( $ip_country_service === 'geoip' || $is_default ) ) {
                try {
                    $wpinv_ip_address_country = geoip_country_code_by_name( $ip );
                } catch( Exception $e ) {
                    wpinv_error_log( $e->getMessage(), 'GeoIP Lookup( ' . $ip . ' )' );
                }
            } else if ( self::geoip2_country_dbfile() && ( $ip_country_service === 'geoip2' || $is_default ) ) {
                $wpinv_ip_address_country = self::geoip2_country_code( $ip );
            } else if ( function_exists( 'simplexml_load_file' ) && ( $ip_country_service === 'geoplugin' || $is_default ) ) {
                $load_xml = simplexml_load_file( 'http://www.geoplugin.net/xml.gp?ip=' . $ip );
                
                if ( !empty( $load_xml ) && !empty( $load_xml->geoplugin_countryCode ) ) {
                    $wpinv_ip_address_country = (string)$load_xml->geoplugin_countryCode;
                }
            }
        }

        if ( empty( $wpinv_ip_address_country ) ) {
            $wpinv_ip_address_country = wpinv_get_default_country();
        }

        return $wpinv_ip_address_country;
    }
    
    public static function sanitize_vat_settings( $input ) {
        global $wpinv_options;
        
        $valid      = false;
        $message    = '';
        
        if ( !empty( $wpinv_options['vat_vies_check'] ) ) {
            if ( empty( $wpinv_options['vat_offline_check'] ) ) {
                $valid = self::offline_check( $input['vat_number'] );
            } else {
                $valid = true;
            }
            
            $message = $valid ? '' : __( 'VAT number not validated', 'invoicing' );
        } else {
            $result = self::check_vat( $input['vat_number'] );
            
            if ( empty( $result['valid'] ) ) {
                $valid      = false;
                $message    = $result['message'];
            } else {
                $valid      = ( isset( $result['company'] ) && ( $result['company'] == '---' || ( strcasecmp( trim( $result['company'] ), trim( $input['vat_company_name'] ) ) == 0 ) ) ) || !empty( $wpinv_options['vat_disable_company_name_check'] );
                $message    = $valid ? '' : __( 'The company name associated with the VAT number provided is not the same as the company name provided.', 'invoicing' );
            }
        }

        if ( $message && self::is_vat_validated() != $valid ) {
            add_settings_error( 'wpinv-notices', '', $message, ( $valid ? 'updated' : 'error' ) );
        }

        $input['vat_valid'] = $valid;
        return $input;
    }
    
    public static function sanitize_vat_rates( $input ) {
        if( !current_user_can( 'manage_options' ) ) {
            add_settings_error( 'wpinv-notices', '', __( 'Your account does not have permission to add rate classes.', 'invoicing' ), 'error' );
            return $input;
        }
        
        $vat_classes = self::get_rate_classes();
        $vat_class = !empty( $_REQUEST['wpi_vat_class'] ) && isset( $vat_classes[$_REQUEST['wpi_vat_class']] )? sanitize_text_field( $_REQUEST['wpi_vat_class'] ) : '';
        
        if ( empty( $vat_class ) ) {
            add_settings_error( 'wpinv-notices', '', __( 'No valid VAT rates class contained in the request to save rates.', 'invoicing' ), 'error' );
            
            return $input;
        }

        $new_rates = ! empty( $_POST['vat_rates'] ) ? array_values( $_POST['vat_rates'] ) : array();

        if ( $vat_class === '_standard' ) {
            // Save the active rates in the invoice settings
            update_option( 'wpinv_tax_rates', $new_rates );
        } else {
            // Get the existing set of rates
            $rates = self::get_non_standard_rates();
            $rates[$vat_class] = $new_rates;

            update_option( 'wpinv_vat_rates', $rates );
        }
        
        return $input;
    }
    
    public static function add_class() {        
        $response = array();
        $response['success'] = false;
        
        if ( !current_user_can( 'manage_options' ) ) {
            $response['error'] = __( 'Invalid access!', 'invoicing' );
            wp_send_json( $response );
        }
        
        $vat_class_name = !empty( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : false;
        $vat_class_desc = !empty( $_POST['desc'] ) ? sanitize_text_field( $_POST['desc'] ) : false;
        
        if ( empty( $vat_class_name ) ) {
            $response['error'] = __( 'Select the VAT rate name', 'invoicing' );
            wp_send_json( $response );
        }
        
        $vat_classes = (array)self::get_rate_classes();

        if ( !empty( $vat_classes ) && in_array( strtolower( $vat_class_name ), array_map( 'strtolower', array_values( $vat_classes ) ) ) ) {
            $response['error'] = wp_sprintf( __( 'A VAT Rate name "%s" already exists', 'invoicing' ), $vat_class_name );
            wp_send_json( $response );
        }
        
        $rate_class_key = normalize_whitespace( 'wpi-' . $vat_class_name );
        $rate_class_key = sanitize_key( str_replace( " ", "-", $rate_class_key ) );
        
        $vat_classes = (array)self::get_rate_classes( true );
        $vat_classes[$rate_class_key] = array( 'name' => $vat_class_name, 'desc' => $vat_class_desc );
        
        update_option( '_wpinv_vat_rate_classes', $vat_classes );
        
        $response['success'] = true;
        $response['redirect'] = admin_url( 'admin.php?page=wpinv-settings&tab=taxes&section=vat_rates&wpi_sub=' . $rate_class_key );
        
        wp_send_json( $response );
    }
    
    public static function delete_class() {
        $response = array();
        $response['success'] = false;
        
        if ( !current_user_can( 'manage_options' ) || !isset( $_POST['class'] ) ) {
            $response['error'] = __( 'Invalid access!', 'invoicing' );
            wp_send_json( $response );
        }
        
        $vat_class = isset( $_POST['class'] ) && $_POST['class'] !== '' ? sanitize_text_field( $_POST['class'] ) : false;
        $vat_classes = (array)self::get_rate_classes();

        if ( !isset( $vat_classes[$vat_class] ) ) {
            $response['error'] = __( 'Requested class does not exists', 'invoicing' );
            wp_send_json( $response );
        }
        
        if ( $vat_class == '_new' || $vat_class == '_standard' ) {
            $response['error'] = __( 'You can not delete standard rates class', 'invoicing' );
            wp_send_json( $response );
        }
            
        $vat_classes = (array)self::get_rate_classes( true );
        unset( $vat_classes[$vat_class] );
        
        update_option( '_wpinv_vat_rate_classes', $vat_classes );
        
        $response['success'] = true;
        $response['redirect'] = admin_url( 'admin.php?page=wpinv-settings&tab=taxes&section=vat_rates&wpi_sub=_new' );
        
        wp_send_json( $response );
    }
    
    public static function update_eu_rates() {        
        $response               = array();
        $response['success']    = false;
        $response['error']      = null;
        $response['data']       = null;
        
        if ( !current_user_can( 'manage_options' ) ) {
            $response['error'] = __( 'Invalid access!', 'invoicing' );
            wp_send_json( $response );
        }
        
        $group      = !empty( $_POST['group'] ) ? sanitize_text_field( $_POST['group'] ) : '';
        $euvatrates = self::request_euvatrates( $group );
        
        if ( !empty( $euvatrates ) ) {
            if ( !empty( $euvatrates['success'] ) && !empty( $euvatrates['rates'] ) ) {
                $response['success']        = true;
                $response['data']['rates']  = $euvatrates['rates'];
            } else if ( !empty( $euvatrates['error'] ) ) {
                $response['error']          = $euvatrates['error'];
            }
        }
            
        wp_send_json( $response );
    }
    
    public static function hide_vat_fields() {
        $hide = wpinv_get_option( 'vat_disable_fields' );
        
        return apply_filters( 'wpinv_hide_vat_fields', $hide );
    }
    
    public static function same_country_rule() {
        $same_country_rule = wpinv_get_option( 'vat_same_country_rule' );
        
        return apply_filters( 'wpinv_vat_same_country_rule', $same_country_rule );
    }
    
    public static function get_vat_name() {
        $vat_name   = wpinv_get_option( 'vat_name' );
        $vat_name   = !empty( $vat_name ) ? $vat_name : 'VAT';
        
        return apply_filters( 'wpinv_get_owner_vat_name', $vat_name );
    }
    
    public static function get_company_name() {
        $company_name = wpinv_get_option( 'vat_company_name' );
        
        return apply_filters( 'wpinv_get_owner_company_name', $company_name );
    }
    
    public static function get_vat_number() {
        $vat_number = wpinv_get_option( 'vat_number' );
        
        return apply_filters( 'wpinv_get_owner_vat_number', $vat_number );
    }
    
    public static function is_vat_validated() {
        $validated = self::get_vat_number() && wpinv_get_option( 'vat_valid' );
        
        return apply_filters( 'wpinv_is_owner_vat_validated', $validated );
    }
    
    public static function sanitize_vat( $vat_number, $country_code = '' ) {        
        $vat_number = str_replace( array(' ', '.', '-', '_', ',' ), '', strtoupper( trim( $vat_number ) ) );
        
        if ( empty( $country_code ) ) {
            $country_code = substr( $vat_number, 0, 2 );
        }
        
        if ( strpos( $vat_number , $country_code ) === 0 ) {
            $vat = str_replace( $country_code, '', $vat_number );
        } else {
            $vat = $country_code . $vat_number;
        }
        
        $return                 = array();
        $return['vat']          = $vat;
        $return['iso']          = $country_code;
        $return['vat_number']   = $country_code . $vat;
        
        return $return;
    }
    
    public static function offline_check( $vat_number, $country_code = '', $formatted = false ) {
        $vat            = self::sanitize_vat( $vat_number, $country_code );
        $vat_number     = $vat['vat_number'];
        $country_code   = $vat['iso'];
        $regex          = array();
        
        switch ( $country_code ) {
            case 'AT':
                $regex[] = '/^(AT)U(\d{8})$/';                           // Austria
                break;
            case 'BE':
                $regex[] = '/^(BE)(0?\d{9})$/';                          // Belgium
                break;
            case 'BG':
                $regex[] = '/^(BG)(\d{9,10})$/';                         // Bulgaria
                break;
            case 'CH':
            case 'CHE':
                $regex[] = '/^(CHE)(\d{9})MWST$/';                       // Switzerland (Not EU)
                break;
            case 'CY':
                $regex[] = '/^(CY)([0-5|9]\d{7}[A-Z])$/';                // Cyprus
                break;
            case 'CZ':
                $regex[] = '/^(CZ)(\d{8,13})$/';                         // Czech Republic
                break;
            case 'DE':
                $regex[] = '/^(DE)([1-9]\d{8})$/';                       // Germany
                break;
            case 'DK':
                $regex[] = '/^(DK)(\d{8})$/';                            // Denmark
                break;
            case 'EE':
                $regex[] = '/^(EE)(10\d{7})$/';                          // Estonia
                break;
            case 'EL':
                $regex[] = '/^(EL)(\d{9})$/';                            // Greece
                break;
            case 'ES':
                $regex[] = '/^(ES)([A-Z]\d{8})$/';                       // Spain (National juridical entities)
                $regex[] = '/^(ES)([A-H|N-S|W]\d{7}[A-J])$/';            // Spain (Other juridical entities)
                $regex[] = '/^(ES)([0-9|Y|Z]\d{7}[A-Z])$/';              // Spain (Personal entities type 1)
                $regex[] = '/^(ES)([K|L|M|X]\d{7}[A-Z])$/';              // Spain (Personal entities type 2)
                break;
            case 'EU':
                $regex[] = '/^(EU)(\d{9})$/';                            // EU-type
                break;
            case 'FI':
                $regex[] = '/^(FI)(\d{8})$/';                            // Finland
                break;
            case 'FR':
                $regex[] = '/^(FR)(\d{11})$/';                           // France (1)
                $regex[] = '/^(FR)[(A-H)|(J-N)|(P-Z)](\d{10})$/';        // France (2)
                $regex[] = '/^(FR)\d[(A-H)|(J-N)|(P-Z)](\d{9})$/';       // France (3)
                $regex[] = '/^(FR)[(A-H)|(J-N)|(P-Z)]{2}(\d{9})$/';      // France (4)
                break;
            case 'GB':
                $regex[] = '/^(GB)?(\d{9})$/';                           // UK (Standard)
                $regex[] = '/^(GB)?(\d{12})$/';                          // UK (Branches)
                $regex[] = '/^(GB)?(GD\d{3})$/';                         // UK (Government)
                $regex[] = '/^(GB)?(HA\d{3})$/';                         // UK (Health authority)
                break;
            case 'GR':
                $regex[] = '/^(GR)(\d{8,9})$/';                          // Greece
                break;
            case 'HR':
                $regex[] = '/^(HR)(\d{11})$/';                           // Croatia
                break;
            case 'HU':
                $regex[] = '/^(HU)(\d{8})$/';                            // Hungary
                break;
            case 'IE':
                $regex[] = '/^(IE)(\d{7}[A-W])$/';                       // Ireland (1)
                $regex[] = '/^(IE)([7-9][A-Z\*\+)]\d{5}[A-W])$/';        // Ireland (2)
                $regex[] = '/^(IE)(\d{7}[A-Z][AH])$/';                   // Ireland (3) (new format from 1 Jan 2013)
                break;
            case 'IT':
                $regex[] = '/^(IT)(\d{11})$/';                           // Italy
                break;
            case 'LV':
                $regex[] = '/^(LV)(\d{11})$/';                           // Latvia
                break;
            case 'LT':
                $regex[] = '/^(LT)(\d{9}|\d{12})$/';                     // Lithuania
                break;
            case 'LU':
                $regex[] = '/^(LU)(\d{8})$/';                            // Luxembourg
                break;
            case 'MT':
                $regex[] = '/^(MT)([1-9]\d{7})$/';                       // Malta
                break;
            case 'NL':
                $regex[] = '/^(NL)(\d{9})B\d{2}$/';                      // Netherlands
                break;
            case 'NO':
                $regex[] = '/^(NO)(\d{9})$/';                            // Norway (Not EU)
                break;
            case 'PL':
                $regex[] = '/^(PL)(\d{10})$/';                           // Poland
                break;
            case 'PT':
                $regex[] = '/^(PT)(\d{9})$/';                            // Portugal
                break;
            case 'RO':
                $regex[] = '/^(RO)([1-9]\d{1,9})$/';                     // Romania
                break;
            case 'RS':
                $regex[] = '/^(RS)(\d{9})$/';                            // Serbia (Not EU)
                break;
            case 'SI':
                $regex[] = '/^(SI)([1-9]\d{7})$/';                       // Slovenia
                break;
            case 'SK':
                $regex[] = '/^(SK)([1-9]\d[(2-4)|(6-9)]\d{7})$/';        // Slovakia Republic
                break;
            case 'SE':
                $regex[] = '/^(SE)(\d{10}01)$/';                         // Sweden
                break;
            default:
                $regex = array();
            break;
        }
        
        if ( empty( $regex ) ) {
            return false;
        }
        
        foreach ( $regex as $pattern ) {
            $matches = null;
            preg_match_all( $pattern, $vat_number, $matches );
            
            if ( !empty( $matches[1][0] ) && !empty( $matches[2][0] ) ) {
                if ( $formatted ) {
                    return array( 'code' => $matches[1][0], 'number' => $matches[2][0] );
                } else {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public static function vies_check( $vat_number, $country_code = '', $result = false ) {
        $vat            = self::sanitize_vat( $vat_number, $country_code );
        $vat_number     = $vat['vat'];
        $iso            = $vat['iso'];
        
        $url = 'http://ec.europa.eu/taxation_customs/vies/viesquer.do?ms=' . urlencode( $iso ) . '&iso=' . urlencode( $iso ) . '&vat=' . urlencode( $vat_number );
        
        if ( ini_get( 'allow_url_fopen' ) ) {
            $response = file_get_contents( $url );
        } else if ( function_exists( 'curl_init' ) ) {
            $ch = curl_init();
            
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            
            $response = curl_exec( $ch );
            
            if ( curl_errno( $ch ) ) {
                wpinv_error_log( curl_error( $ch ), 'VIES CHECK ERROR' );
                $response = '';
            }
            
            curl_close( $ch );
        } else {
            wpinv_error_log( 'To use VIES CHECK you must have allow_url_fopen is ON or cURL installed & active on your server.', 'VIES CHECK ERROR' );
        }
        
        if ( empty( $response ) ) {
            return $result;
        }

        if ( preg_match( '/invalid VAT number/i', $response ) ) {
            return false;
        } else if ( preg_match( '/valid VAT number/i', $response, $matches ) ) {
            $content = explode( "valid VAT number", htmlentities( $response ) );
            
            if ( !empty( $content[1] ) ) {
                preg_match_all( '/<tr>(.*?)<td.*?>(.*?)<\/td>(.*?)<\/tr>/si', html_entity_decode( $content[1] ), $matches );
                
                if ( !empty( $matches[2] ) && $matches[3] ) {
                    $return = array();
                    
                    foreach ( $matches[2] as $key => $label ) {
                        $label = trim( $label );
                        
                        switch ( strtolower( $label ) ) {
                            case 'member state':
                                $return['state'] = trim( strip_tags( $matches[3][$key] ) );
                            break;
                            case 'vat number':
                                $return['number'] = trim( strip_tags( $matches[3][$key] ) );
                            break;
                            case 'name':
                                $return['company'] = trim( strip_tags( $matches[3][$key] ) );
                            break;
                            case 'address':
                                $address           = str_replace( array( "<br><br>", "<br /><br />", "<br/><br/>" ), "<br>", html_entity_decode( trim( $matches[3][$key] ) ) );
                                $return['address'] = trim( strip_tags( $address, '<br>' ) );
                            break;
                            case 'consultation number':
                                $return['consultation'] = trim( strip_tags( $matches[3][$key] ) );
                            break;
                        }
                    }
                    
                    if ( !empty( $return ) ) {
                        return $return;
                    }
                }
            }
            
            return true;
        } else {
            return $result;
        }
    }
    
    public static function check_vat( $vat_number, $country_code = '' ) {        
        $vat_name           = self::get_vat_name();
        
        $return             = array();
        $return['valid']    = false;
        $return['message']  = wp_sprintf( __( '%s number not validated', 'invoicing' ), $vat_name );
                
        if ( !wpinv_get_option( 'vat_offline_check' ) && !self::offline_check( $vat_number, $country_code ) ) {
            return $return;
        }
            
        $response = self::vies_check( $vat_number, $country_code );
        
        if ( $response ) {
            $return['valid']    = true;
            
            if ( is_array( $response ) ) {
                $return['company'] = isset( $response['company'] ) ? $response['company'] : '';
                $return['address'] = isset( $response['address'] ) ? $response['address'] : '';
                $return['message'] = $return['company'] . '<br/>' . $return['address'];
            }
        } else {
            $return['valid']    = false;
            $return['message']  = wp_sprintf( __( 'Fail to validate the %s number: EU Commission VAT server (VIES) check fails.', 'invoicing' ), $vat_name );
        }
        
        return $return;
    }
    
    public static function request_euvatrates( $group ) {
        $response               = array();
        $response['success']    = false;
        $response['error']      = null;
        $response['eurates']    = null;
        
        $euvatrates_url = 'https://euvatrates.com/rates.json';
        $euvatrates_url = apply_filters( 'wpinv_euvatrates_url', $euvatrates_url );
        $api_response   = wp_remote_get( $euvatrates_url );
    
        try {
            if ( is_wp_error( $api_response ) ) {
                $response['error']      = __( $api_response->get_error_message(), 'invoicing' );
            } else {
                $body = json_decode( $api_response['body'] );
                
                if ( isset( $body->rates ) ) {
                    $rates = array();
                    
                    foreach ( $body->rates as $country_code => $rate ) {
                        $vat_rate = array();
                        $vat_rate['country']        = $rate->country;
                        $vat_rate['standard']       = (float)$rate->standard_rate;
                        $vat_rate['reduced']        = (float)$rate->reduced_rate;
                        $vat_rate['superreduced']   = (float)$rate->super_reduced_rate;
                        $vat_rate['parking']        = (float)$rate->parking_rate;
                        
                        if ( $group !== '' && in_array( $group, array( 'standard', 'reduced', 'superreduced', 'parking' ) ) ) {
                            $vat_rate_group = array();
                            $vat_rate_group['country'] = $rate->country;
                            $vat_rate_group[$group]    = $vat_rate[$group];
                            
                            $vat_rate = $vat_rate_group;
                        }
                        
                        $rates[$country_code] = $vat_rate;
                    }
                    
                    $response['success']    = true;                                
                    $response['rates']      = apply_filters( 'wpinv_process_euvatrates', $rates, $api_response, $group );
                } else {
                    $response['error']      = __( 'No EU rates found!', 'invoicing' );
                }
            }
        } catch ( Exception $e ) {
            $response['error'] = __( $e->getMessage(), 'invoicing' );
        }
        
        return apply_filters( 'wpinv_response_euvatrates', $response, $group );
    }    
    
    public static function requires_vat( $requires_vat = false, $user_id = 0, $is_digital = null ) {
        global $wpi_item_id, $wpi_country;
        
        if ( !empty( $_POST['wpinv_country'] ) ) {
            $country_code = trim( $_POST['wpinv_country'] );
        } else if ( !empty( $_POST['country'] ) ) {
            $country_code = trim( $_POST['country'] );
        } else if ( !empty( $wpi_country ) ) {
            $country_code = $wpi_country;
        } else {
            $country_code = self::get_user_country( '', $user_id );
        }
        
        if ( $is_digital === null && $wpi_item_id ) {
            $is_digital = $wpi_item_id ? self::item_has_digital_rule( $wpi_item_id ) : self::allow_vat_rules();
        }
        
        if ( !empty( $country_code ) ) {
            $requires_vat = ( self::is_eu_state( $country_code ) && ( self::is_eu_state( self::$default_country ) || $is_digital ) ) || ( self::is_gst_country( $country_code ) && self::is_gst_country( self::$default_country ) );
        }
        
        return apply_filters( 'wpinv_requires_vat', $requires_vat, $user_id );
    }
    
    public static function tax_label( $label = '' ) {
        global $wpi_requires_vat;
        
        if ( !( $wpi_requires_vat !== 0 && $wpi_requires_vat ) ) {
            $wpi_requires_vat = self::requires_vat( 0, false );
        }
        
        return $wpi_requires_vat ? __( self::get_vat_name(), 'invoicing' ) : ( $label ? $label : __( 'Tax', 'invoicing' ) );
    }
    
    public static function standard_rates_label() {
        return __( 'Standard Rates', 'invoicing' );
    }
    
    public static function get_rate_classes( $with_desc = false ) {        
        $rate_classes_option = get_option( '_wpinv_vat_rate_classes', true );
        $classes = maybe_unserialize( $rate_classes_option );
        
        if ( empty( $classes ) || !is_array( $classes ) ) {
            $classes = array();
        }

        $rate_classes = array();
        if ( !array_key_exists( '_standard', $classes ) ) {
            if ( $with_desc ) {
                $rate_classes['_standard'] = array( 'name' => self::standard_rates_label(), 'desc' => __( 'EU member states standard VAT rates', 'invoicing' ) );
            } else {
                $rate_classes['_standard'] = self::standard_rates_label();
            }
        }
        
        foreach ( $classes as $key => $class ) {
            $name = !empty( $class['name'] ) ? __( $class['name'], 'invoicing' ) : $key;
            $desc = !empty( $class['desc'] ) ? __( $class['desc'], 'invoicing' ) : '';
            
            if ( $with_desc ) {
                $rate_classes[$key] = array( 'name' => $name, 'desc' => $desc );
            } else {
                $rate_classes[$key] = $name;
            }
        }

        return $rate_classes;
    }
    
    public static function get_all_classes() {        
        $classes            = self::get_rate_classes();
        $classes['_exempt'] = __( 'Exempt (0%)', 'invoicing' );
        
        return apply_filters( 'wpinv_vat_get_all_classes', $classes );
    }
    
    public static function get_class_desc( $rate_class ) {        
        $rate_classes = self::get_rate_classes( true );

        if ( !empty( $rate_classes ) && isset( $rate_classes[$rate_class] ) && isset( $rate_classes[$rate_class]['desc'] ) ) {
            return $rate_classes[$rate_class]['desc'];
        }
        
        return '';
    }
    
    public static function get_vat_groups() {
        $vat_groups = array(
            'standard'      => 'Standard',
            'reduced'       => 'Reduced',
            'superreduced'  => 'Super Reduced',
            'parking'       => 'Parking',
            'increased'     => 'Increased'
        );
        
        return apply_filters( 'wpinv_get_vat_groups', $vat_groups );
    }

    public static function get_rules() {
        $vat_rules = array(
            'digital' => __( 'Digital Product', 'invoicing' ),
            'physical' => __( 'Physical Product', 'invoicing' )
        );
        return apply_filters( 'wpinv_get_vat_rules', $vat_rules );
    }

    public static function get_vat_rates( $class ) {
        if ( $class === '_standard' ) {
            return wpinv_get_tax_rates();
        }

        $rates = self::get_non_standard_rates();

        return array_key_exists( $class, $rates ) ? $rates[$class] : array();
    }

    public static function get_non_standard_rates() {
        $option = get_option( 'wpinv_vat_rates', array());
        return is_array( $option ) ? $option : array();
    }
    
    public static function allow_vat_rules() {
        return ( wpinv_use_taxes() && wpinv_get_option( 'apply_vat_rules' ) ? true : false );
    }
    
    public static function allow_vat_classes() {
        return false; // TODO
        return ( wpinv_get_option( 'vat_allow_classes' ) ? true : false );
    }
    
    public static function get_item_class( $postID ) {
        $class = get_post_meta( $postID, '_wpinv_vat_class', true );

        if ( empty( $class ) ) {
            $class = '_standard';
        }
        
        return apply_filters( 'wpinv_get_item_vat_class', $class, $postID );
    }
    
    public static function item_class_label( $postID ) {        
        $vat_classes = self::get_all_classes();
        
        $class = self::get_item_class( $postID );
        $class = isset( $vat_classes[$class] ) ? $vat_classes[$class] : __( $class, 'invoicing' );
        
        return apply_filters( 'wpinv_item_class_label', $class, $postID );
    }
    
    public static function get_item_rule( $postID ) {        
        $rule_type = get_post_meta( $postID, '_wpinv_vat_rule', true );
        
        if ( empty( $rule_type ) ) {        
            $rule_type = self::allow_vat_rules() ? 'digital' : 'physical';
        }
        
        return apply_filters( 'wpinv_item_get_vat_rule', $rule_type, $postID );
    }
    
    public static function item_rule_label( $postID ) {
        $vat_rules  = self::get_rules();
        $vat_rule   = self::get_item_rule( $postID );
        $vat_rule   = isset( $vat_rules[$vat_rule] ) ? $vat_rules[$vat_rule] : $vat_rule;
        
        return apply_filters( 'wpinv_item_rule_label', $vat_rule, $postID );
    }
    
    public static function item_has_digital_rule( $item_id = 0 ) {        
        return self::get_item_rule( $item_id ) == 'digital' ? true : false;
    }
    
    public static function invoice_has_digital_rule( $invoice = 0 ) {        
        if ( !self::allow_vat_rules() ) {
            return false;
        }
        
        if ( empty( $invoice ) ) {
            return true;
        }
        
        if ( is_int( $invoice ) ) {
            $invoice = new WPInv_Invoice( $invoice );
        }
        
        if ( !( is_object( $invoice ) && is_a( $invoice, 'WPInv_Invoice' ) ) ) {
            return true;
        }
        
        $cart_items  = $invoice->get_cart_details();
        
        if ( !empty( $cart_items ) ) {
            $has_digital_rule = false;
            
            foreach ( $cart_items as $key => $item ) {
                if ( self::item_has_digital_rule( $item['id'] ) ) {
                    $has_digital_rule = true;
                    break;
                }
            }
        } else {
            $has_digital_rule = true;
        }
        
        return $has_digital_rule;
    }
    
    public static function item_is_taxable( $item_id = 0, $country = false, $state = false ) {        
        if ( !wpinv_use_taxes() ) {
            return false;
        }
        
        $is_taxable = true;

        if ( !empty( $item_id ) && self::get_item_class( $item_id ) == '_exempt' ) {
            $is_taxable = false;
        }
        
        return apply_filters( 'wpinv_item_is_taxable', $is_taxable, $item_id, $country , $state );
    }
    
    public static function find_rate( $country, $state, $rate, $class ) {
        global $wpi_zero_tax;

        if ( $class === '_exempt' || $wpi_zero_tax ) {
            return 0;
        }

        $tax_rates   = wpinv_get_tax_rates();
        
        if ( $class !== '_standard' ) {
            $class_rates = self::get_vat_rates( $class );
            
            if ( is_array( $class_rates ) ) {
                $indexed_class_rates = array();
                
                foreach ( $class_rates as $key => $cr ) {
                    $indexed_class_rates[$cr['country']] = $cr;
                }

                $tax_rates = array_map( function( $tr ) use( $indexed_class_rates ) {
                    $tr_country = $tr['country'];
                    if ( !isset( $indexed_class_rates[$tr_country] ) ) {
                        return $tr;
                    }
                    $icr = $indexed_class_rates[$tr_country];
                    return ( empty( $icr['rate'] ) && $icr['rate'] !== '0' ) ? $tr : $icr;

                }, $tax_rates, $class_rates );
            }
        }

        if ( !empty( $tax_rates ) ) {
            foreach ( $tax_rates as $key => $tax_rate ) {
                if ( $country != $tax_rate['country'] )
                    continue;

                if ( !empty( $tax_rate['global'] ) ) {
                    if ( 0 !== $tax_rate['rate'] || !empty( $tax_rate['rate'] ) ) {
                        $rate = number_format( $tax_rate['rate'], 4 );
                    }
                } else {
                    if ( empty( $tax_rate['state'] ) || strtolower( $state ) != strtolower( $tax_rate['state'] ) )
                        continue;

                    $state_rate = $tax_rate['rate'];
                    if ( 0 !== $state_rate || !empty( $state_rate ) ) {
                        $rate = number_format( $state_rate, 4 );
                    }
                }
            }
        }
        
        return $rate;
    }
    
    public static function get_rate( $rate = 1, $country = '', $state = '', $item_id = 0 ) {
        global $wpinv_options, $wpi_session, $wpi_item_id, $wpi_zero_tax;
        
        $item_id = $item_id > 0 ? $item_id : $wpi_item_id;
        $allow_vat_classes = self::allow_vat_classes();
        $class = $item_id ? self::get_item_class( $item_id ) : '_standard';

        if ( $class === '_exempt' || $wpi_zero_tax ) {
            return 0;
        } else if ( !$allow_vat_classes ) {
            $class = '_standard';
        }

        if( !empty( $_POST['wpinv_country'] ) ) {
            $post_country = $_POST['wpinv_country'];
        } elseif( !empty( $_POST['wpinv_country'] ) ) {
            $post_country = $_POST['wpinv_country'];
        } elseif( !empty( $_POST['country'] ) ) {
            $post_country = $_POST['country'];
        } else {
            $post_country = '';
        }

        $country        = !empty( $post_country ) ? $post_country : wpinv_default_billing_country( $country );
        $base_country   = wpinv_is_base_country( $country );
        
        $requires_vat   = self::requires_vat( 0, false );
        $is_digital     = self::get_item_rule( $item_id ) == 'digital' ;
        $rate           = $requires_vat && isset( $wpinv_options['eu_fallback_rate'] ) ? $wpinv_options['eu_fallback_rate'] : $rate;
          
        if ( self::same_country_rule() == 'no' && $base_country ) { // Disable VAT to same country
            $rate = 0;
        } else if ( $requires_vat ) {
            $vat_number = self::get_user_vat_number( '', 0, true );
            $vat_info   = self::current_vat_data();
            
            if ( is_array( $vat_info ) ) {
                $vat_number = isset( $vat_info['number'] ) && !empty( $vat_info['valid'] ) ? $vat_info['number'] : "";
            }
            
            if ( $country == 'UK' ) {
                $country = 'GB';
            }

            if ( !empty( $vat_number ) ) {
                $rate = 0;
            } else {
                $rate = self::find_rate( $country, $state, $rate, $class ); // Fix if there are no tax rated and you try to pay an invoice it does not add the fallback tax rate
            }

            if ( empty( $vat_number ) && !$is_digital ) {
                if ( $base_country ) {
                    $rate = self::find_rate( $country, null, $rate, $class );
                } else {
                    if ( empty( $country ) && isset( $wpinv_options['eu_fallback_rate'] ) ) {
                        $rate = $wpinv_options['eu_fallback_rate'];
                    } else if( !empty( $country ) ) {
                        $rate = self::find_rate( $country, $state, $rate, $class );
                    }
                }
            } else if ( empty( $vat_number ) || ( self::same_country_rule() == 'always' && $base_country ) ) {
                if ( empty( $country ) && isset( $wpinv_options['eu_fallback_rate'] ) ) {
                    $rate = $wpinv_options['eu_fallback_rate'];
                } else if( !empty( $country ) ) {
                    $rate = self::find_rate( $country, $state, $rate, $class );
                }
            }
        } else {
            if ( $is_digital ) {
                $ip_country_code = self::get_country_by_ip();
                
                if ( $ip_country_code && self::is_eu_state( $ip_country_code ) ) {
                    $rate = self::find_rate( $ip_country_code, '', 0, $class );
                } else {
                    $rate = self::find_rate( $country, $state, $rate, $class );
                }
            } else {
                $rate = self::find_rate( $country, $state, $rate, $class );
            }
        }

        return $rate;
    }
    
    public static function current_vat_data() {
        global $wpi_session;
        
        return $wpi_session->get( 'user_vat_data' );
    }
    
    public static function get_user_country( $country = '', $user_id = 0 ) {
        $user_address = wpinv_get_user_address( $user_id, false );
        
        if ( wpinv_get_option( 'vat_ip_country_default' ) ) {
            $country = '';
        }
        
        $country    = empty( $user_address ) || !isset( $user_address['country'] ) || empty( $user_address['country'] ) ? $country : $user_address['country'];
        $result     = apply_filters( 'wpinv_get_user_country', $country, $user_id );

        if ( empty( $result ) ) {
            $result = self::get_country_by_ip();
        }

        return $result;
    }
    
    public static function set_user_country( $country = '', $user_id = 0 ) {
        global $wpi_userID;
        
        if ( empty($country) && !empty($wpi_userID) && get_current_user_id() != $wpi_userID ) {
            $country = wpinv_get_default_country();
        }
        
        return $country;
    }
    
    public static function get_user_vat_number( $vat_number = '', $user_id = 0, $is_valid = false ) {
        global $wpi_current_id, $wpi_userID;
        
        if ( !empty( $_POST['new_user'] ) ) {
            return '';
        }
        
        if ( empty( $user_id ) ) {
            $user_id = !empty( $wpi_userID ) ? $wpi_userID : ( $wpi_current_id ? wpinv_get_user_id( $wpi_current_id ) : get_current_user_id() );
        }

        $vat_number = empty( $user_id ) ? '' : get_user_meta( $user_id, '_wpinv_vat_number', true );
        
        /* TODO
        if ( $is_valid && $vat_number ) {
            $adddress_confirmed = empty( $user_id ) ? false : get_user_meta( $user_id, '_wpinv_adddress_confirmed', true );
            if ( !$adddress_confirmed ) {
                $vat_number = '';
            }
        }
        */

        return apply_filters('wpinv_get_user_vat_number', $vat_number, $user_id, $is_valid );
    }
    
    public static function get_user_company( $company = '', $user_id = 0 ) {
        if ( empty( $user_id ) ) {
            $user_id = get_current_user_id();
        }

        $company = empty( $user_id ) ? "" : get_user_meta( $user_id, '_wpinv_company', true );

        return apply_filters( 'wpinv_user_company', $company, $user_id );
    }
    
    public static function save_user_vat_details( $company = '', $vat_number = '' ) {
        $save = apply_filters( 'wpinv_allow_save_user_vat_details', true );

        if ( is_user_logged_in() && $save ) {
            $user_id = get_current_user_id();

            if ( !empty( $vat_number ) ) {
                update_user_meta( $user_id, '_wpinv_vat_number', $vat_number );
            } else {
                delete_user_meta( $user_id, '_wpinv_vat_number');
            }

            if ( !empty( $company ) ) {
                update_user_meta( $user_id, '_wpinv_company', $company );
            } else {
                delete_user_meta( $user_id, '_wpinv_company');
                delete_user_meta( $user_id, '_wpinv_vat_number');
            }
        }

        do_action('wpinv_save_user_vat_details', $company, $vat_number);
    }
    
    public static function ajax_vat_validate() {
        global $wpinv_options, $wpi_session;
        
        $is_checkout            = ( !empty( $_POST['source'] ) && $_POST['source'] == 'checkout' ) ? true : false;
        $response               = array();
        $response['success']    = false;
        
        if ( empty( $_REQUEST['_wpi_nonce'] ) || ( !empty( $_REQUEST['_wpi_nonce'] ) && !wp_verify_nonce( $_REQUEST['_wpi_nonce'], 'vat_validation' ) ) ) {
            $response['error'] = __( 'Invalid security nonce', 'invoicing' );
            wp_send_json( $response );
        }
        
        $vat_name   = self::get_vat_name();
        
        if ( $is_checkout ) {
            $invoice = wpinv_get_invoice_cart();
            
            if ( !self::requires_vat( false, 0, self::invoice_has_digital_rule( $invoice ) ) ) {
                $vat_info = array();
                $wpi_session->set( 'user_vat_data', $vat_info );

                self::save_user_vat_details();
                
                $response['success'] = true;
                $response['message'] = wp_sprintf( __( 'Ignore %s', 'invoicing' ), $vat_name );
                wp_send_json( $response );
            }
        }
        
        $company    = !empty( $_POST['company'] ) ? sanitize_text_field( $_POST['company'] ) : '';
        $vat_number = !empty( $_POST['number'] ) ? sanitize_text_field( $_POST['number'] ) : '';
        
        $vat_info = $wpi_session->get( 'user_vat_data' );
        if ( !is_array( $vat_info ) || empty( $vat_info ) ) {
            $vat_info = array( 'company'=> $company, 'number' => '', 'valid' => true );
        }
        
        if ( empty( $vat_number ) ) {
            if ( $is_checkout ) {
                $response['success'] = true;
                $response['message'] = wp_sprintf( __( 'No %s number has been applied. %s will be added to invoice totals', 'invoicing' ), $vat_name, $vat_name );
                
                $vat_info = $wpi_session->get( 'user_vat_data' );
                $vat_info['number'] = "";
                $vat_info['valid'] = true;
                
                self::save_user_vat_details( $company );
            } else {
                $response['error'] = wp_sprintf( __( 'Please enter your %s number!', 'invoicing' ), $vat_name );
                
                $vat_info['valid'] = false;
            }

            $wpi_session->set( 'user_vat_data', $vat_info );
            wp_send_json( $response );
        }
        
        if ( empty( $company ) ) {
            $vat_info['valid'] = false;
            $wpi_session->set( 'user_vat_data', $vat_info );
            
            $response['error'] = __( 'Please enter your registered company name!', 'invoicing' );
            wp_send_json( $response );
        }
        
        if ( !empty( $wpinv_options['vat_vies_check'] ) ) {
            if ( empty( $wpinv_options['vat_offline_check'] ) && !self::offline_check( $vat_number ) ) {
                $vat_info['valid'] = false;
                $wpi_session->set( 'user_vat_data', $vat_info );
                
                $response['error'] = wp_sprintf( __( '%s number not validated', 'invoicing' ), $vat_name );
                wp_send_json( $response );
            }
            
            $response['success'] = true;
            $response['message'] = wp_sprintf( __( '%s number validated', 'invoicing' ), $vat_name );
        } else {
            $result = self::check_vat( $vat_number );
            
            if ( empty( $result['valid'] ) ) {
                $response['error'] = $result['message'];
                wp_send_json( $response );
            }
            
            $vies_company = !empty( $result['company'] ) ? $result['company'] : '';
            $vies_company = apply_filters( 'wpinv_vies_company_name', $vies_company );
            
            $valid_company = $vies_company && $company && ( $vies_company == '---' || strcasecmp( trim( $vies_company ), trim( $company ) ) == 0 ) ? true : false;

            if ( !empty( $wpinv_options['vat_disable_company_name_check'] ) || $valid_company ) {
                $response['success'] = true;
                $response['message'] = wp_sprintf( __( '%s number validated', 'invoicing' ), $vat_name );
            } else {           
                $vat_info['valid'] = false;
                $wpi_session->set( 'user_vat_data', $vat_info );
                
                $response['success'] = false;
                $response['message'] = wp_sprintf( __( 'The company name associated with the %s number provided is not the same as the company name provided.', 'invoicing' ), $vat_name );
                wp_send_json( $response );
            }
        }
        
        if ( $is_checkout ) {
            self::save_user_vat_details( $company, $vat_number );

            $vat_info = array('company' => $company, 'number' => $vat_number, 'valid' => true );
            $wpi_session->set( 'user_vat_data', $vat_info );
        }

        wp_send_json( $response );
    }
    
    public static function ajax_vat_reset() {
        global $wpi_session;
        
        $company    = is_user_logged_in() ? self::get_user_company() : '';
        $vat_number = self::get_user_vat_number();
        
        $vat_info   = array('company' => $company, 'number' => $vat_number, 'valid' => false );
        $wpi_session->set( 'user_vat_data', $vat_info );
        
        $response                       = array();
        $response['success']            = true;
        $response['data']['company']    = $company;
        $response['data']['number']     = $vat_number;
        
        wp_send_json( $response );
    }
    
    public static function checkout_vat_validate( $valid_data, $post ) {
        global $wpinv_options, $wpi_session;
        
        $vat_name  = __( self::get_vat_name(), 'invoicing' );
        
        if ( !isset( $_POST['_wpi_nonce'] ) || !wp_verify_nonce( $_POST['_wpi_nonce'], 'vat_validation' ) ) {
            wpinv_set_error( 'vat_validation', wp_sprintf( __( "Invalid %s validation request.", 'invoicing' ), $vat_name ) );
            return;
        }
        
        $vat_saved = $wpi_session->get( 'user_vat_data' );
        $wpi_session->set( 'user_vat_data', null );
        
        $invoice        = wpinv_get_invoice_cart();
        $amount         = $invoice->get_total();
        $is_digital     = self::invoice_has_digital_rule( $invoice );
        $no_vat         = !self::requires_vat( 0, false, $is_digital );
        
        $company        = !empty( $_POST['wpinv_company'] ) ? $_POST['wpinv_company'] : null;
        $vat_number     = !empty( $_POST['wpinv_vat_number'] ) ? $_POST['wpinv_vat_number'] : null;
        $country        = !empty( $_POST['wpinv_country'] ) ? $_POST['wpinv_country'] : $invoice->country;
        if ( empty( $country ) ) {
            $country = wpinv_default_billing_country();
        }
        
        if ( !$is_digital && $no_vat ) {
            return;
        }
            
        $vat_data           = array( 'company' => '', 'number' => '', 'valid' => false );
        
        $ip_country_code    = self::get_country_by_ip();
        $is_eu_state        = self::is_eu_state( $country );
        $is_eu_state_ip     = self::is_eu_state( $ip_country_code );
        $is_non_eu_user     = !$is_eu_state && !$is_eu_state_ip;
        
        if ( $is_digital && !$is_non_eu_user && empty( $vat_number ) && apply_filters( 'wpinv_checkout_requires_country', true, $amount ) ) {
            $vat_data['adddress_confirmed'] = false;
            
            if ( !isset( $_POST['wpinv_adddress_confirmed'] ) ) {
                if ( $ip_country_code != $country ) {
                    wpinv_set_error( 'vat_validation', sprintf( __( 'The country of your current location must be the same as the country of your billing location or you must %s confirm %s the billing address is your home country.', 'invoicing' ), '<a href="#wpinv_adddress_confirm">', '</a>' ) );
                }
            } else {
                $vat_data['adddress_confirmed'] = true;
            }
        }
        
        if ( !empty( $wpinv_options['vat_prevent_b2c_purchase'] ) && !$is_non_eu_user && ( empty( $vat_number ) || $no_vat ) ) {
            if ( $is_eu_state ) {
                wpinv_set_error( 'vat_validation', wp_sprintf( __( 'Please enter and validate your %s number to verify your purchase is by an EU business.', 'invoicing' ), $vat_name ) );
            } else if ( $is_digital && $is_eu_state_ip ) {
                wpinv_set_error( 'vat_validation', wp_sprintf( __( 'Sales to non-EU countries cannot be completed because %s must be applied.', 'invoicing' ), $vat_name ) );
            }
        }
        
        if ( !$is_eu_state || $no_vat || empty( $vat_number ) ) {
            return;
        }

        if ( !empty( $vat_saved ) && isset( $vat_saved['valid'] ) ) {
            $vat_data['valid']  = $vat_saved['valid'];
        }
            
        if ( $company !== null ) {
            $vat_data['company'] = $company;
        }

        $message = '';
        if ( $vat_number !== null ) {
            $vat_data['number'] = $vat_number;
            
            if ( !$vat_data['valid'] || ( $vat_saved['number'] !== $vat_data['number'] ) || ( $vat_saved['company'] !== $vat_data['company'] ) ) {
                if ( !empty( $wpinv_options['vat_vies_check'] ) ) {            
                    if ( empty( $wpinv_options['vat_offline_check'] ) && !self::offline_check( $vat_number ) ) {
                        $vat_data['valid'] = false;
                    }
                } else {
                    $result = self::check_vat( $vat_number );
                    
                    if ( !empty( $result['valid'] ) ) {                
                        $vat_data['valid'] = true;
                        $vies_company = !empty( $result['company'] ) ? $result['company'] : '';
                        $vies_company = apply_filters( 'wpinv_vies_company_name', $vies_company );
                    
                        $valid_company = $vies_company && $company && ( $vies_company == '---' || strcasecmp( trim( $vies_company ), trim( $company ) ) == 0 ) ? true : false;

                        if ( !( !empty( $wpinv_options['vat_disable_company_name_check'] ) || $valid_company ) ) {         
                            $vat_data['valid'] = false;
                            
                            $message = wp_sprintf( __( 'The company name associated with the %s number provided is not the same as the company name provided.', 'invoicing' ), $vat_name );
                        }
                    } else {
                        $message = wp_sprintf( __( 'Fail to validate the %s number: EU Commission VAT server (VIES) check fails.', 'invoicing' ), $vat_name );
                    }
                }
                
                if ( !$vat_data['valid'] ) {
                    $error = wp_sprintf( __( 'The %s %s number %s you have entered has not been validated', 'invoicing' ), '<a href="#wpi-vat-details">', $vat_name, '</a>' ) . ( $message ? ' ( ' . $message . ' )' : '' );
                    wpinv_set_error( 'vat_validation', $error );
                }
            }
        }

        $wpi_session->set( 'user_vat_data', $vat_data );
    }
    
    public static function checkout_vat_fields( $billing_details ) {
        global $wpi_session, $wpinv_options, $wpi_country, $wpi_requires_vat;
        
        $ip_address         = wpinv_get_ip();
        $ip_country_code    = self::get_country_by_ip();
        
        $tax_label          = __( self::get_vat_name(), 'invoicing' );
        $invoice            = wpinv_get_invoice_cart();
        $is_digital         = self::invoice_has_digital_rule( $invoice );
        $wpi_country        = $invoice->country;
        
        $requires_vat       = !self::hide_vat_fields() && $invoice->get_total() > 0 && self::requires_vat( 0, false, $is_digital );
        $wpi_requires_vat   = $requires_vat;
        
        $company            = is_user_logged_in() ? self::get_user_company() : '';
        $vat_number         = self::get_user_vat_number();
        
        $validated          = $vat_number ? self::get_user_vat_number( '', 0, true ) : 1;
        $vat_info           = $wpi_session->get( 'user_vat_data' );

        if ( is_array( $vat_info ) ) {
            $company    = isset( $vat_info['company'] ) ? $vat_info['company'] : '';
            $vat_number = isset( $vat_info['number'] ) ? $vat_info['number'] : '';
            $validated  = isset( $vat_info['valid'] ) ? $vat_info['valid'] : false;
        }
        
        $selected_country = $invoice->country ? $invoice->country : wpinv_default_billing_country();

        if ( $ip_country_code == 'UK' ) {
            $ip_country_code = 'GB';
        }
        
        if ( $selected_country == 'UK' ) {
            $selected_country = 'GB';
        }
        
        if ( $requires_vat && ( self::same_country_rule() == 'no' && wpinv_is_base_country( $selected_country ) || !self::allow_vat_rules() ) ) {
            $requires_vat = false;
        }

        $display_vat_details    = $requires_vat ? 'block' : 'none';
        $display_validate_btn   = 'none';
        $display_reset_btn      = 'none';
        
        if ( !empty( $vat_number ) && $validated ) {
            $vat_vailidated_text    = wp_sprintf( __( '%s number validated', 'invoicing' ), $tax_label );
            $vat_vailidated_class   = 'wpinv-vat-stat-1';
            $display_reset_btn      = 'block';
        } else {
            $vat_vailidated_text    = empty( $vat_number ) ? '' : wp_sprintf( __( '%s number not validated', 'invoicing' ), $tax_label );
            $vat_vailidated_class   = empty( $vat_number ) ? '' : 'wpinv-vat-stat-0';
            $display_validate_btn   = 'block';
        }
        
        $show_ip_country        = $is_digital && ( empty( $vat_number ) || !$requires_vat ) && $ip_country_code != $selected_country ? 'block' : 'none';
        ?>
        <div id="wpi-vat-details" class="wpi-vat-details clearfix" style="display:<?php echo $display_vat_details; ?>">
            <div id="wpi_vat_info" class="clearfix panel panel-default">
                <div class="panel-heading"><h3 class="panel-title"><?php echo wp_sprintf( __( '%s Details', 'invoicing' ), $tax_label );?></h3></div>
                <div id="wpinv-fields-box" class="panel-body">
                    <p id="wpi_show_vat_note">
                        <?php echo wp_sprintf( __( 'Validate your registered %s number to exclude tax.', 'invoicing' ), $tax_label ); ?>
                    </p>
                    <div id="wpi_vat_fields" class="wpi_vat_info">
                        <p class="wpi-cart-field wpi-col2 wpi-colf">
                            <label for="wpinv_company" class="wpi-label"><?php _e( 'Company Name', 'invoicing' );?></label>
                            <?php
                            echo wpinv_html_text( array(
                                    'id'            => 'wpinv_company',
                                    'name'          => 'wpinv_company',
                                    'value'         => $company,
                                    'class'         => 'wpi-input form-control',
                                    'placeholder'   => __( 'Company name', 'invoicing' ),
                                ) );
                            ?>
                        </p>
                        <p class="wpi-cart-field wpi-col2 wpi-coll wpi-cart-field-vat">
                            <label for="wpinv_vat_number" class="wpi-label"><?php echo wp_sprintf( __( '%s Number', 'invoicing' ), $tax_label );?></label>
                            <span id="wpinv_vat_number-wrap">
                                <label for="wpinv_vat_number" class="wpinv-label"></label>
                                <input type="text" class="wpi-input form-control" placeholder="<?php echo esc_attr( wp_sprintf( __( '%s number', 'invoicing' ), $tax_label ) );?>" value="<?php esc_attr_e( $vat_number );?>" id="wpinv_vat_number" name="wpinv_vat_number">
                                <span class="wpinv-vat-stat <?php echo $vat_vailidated_class;?>"><i class="fa"></i>&nbsp;<font><?php echo $vat_vailidated_text;?></font></span>
                            </span>
                        </p>
                        <p class="wpi-cart-field wpi-col wpi-colf wpi-cart-field-actions">
                            <button class="btn btn-success btn-sm wpinv-vat-validate" type="button" id="wpinv_vat_validate" style="display:<?php echo $display_validate_btn; ?>"><?php echo wp_sprintf( __("Validate %s Number", 'invoicing'), $tax_label ); ?></button>
                            <button class="btn btn-danger btn-sm wpinv-vat-reset" type="button" id="wpinv_vat_reset" style="display:<?php echo $display_reset_btn; ?>"><?php echo wp_sprintf( __("Reset %s", 'invoicing'), $tax_label ); ?></button>
                            <span class="wpi-vat-box wpi-vat-box-info"><span id="text"></span></span>
                            <span class="wpi-vat-box wpi-vat-box-error"><span id="text"></span></span>
                            <input type="hidden" name="_wpi_nonce" value="<?php echo wp_create_nonce( 'vat_validation' ) ?>" />
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div id="wpinv_adddress_confirm" class="wpi-vat-info clearfix panel panel-info" value="<?php echo $ip_country_code; ?>" style="display:<?php echo $show_ip_country; ?>;">
            <div id="wpinv-fields-box" class="panel-body">
                <span id="wpinv_adddress_confirmed-wrap">
                    <input type="checkbox" id="wpinv_adddress_confirmed" name="wpinv_adddress_confirmed" value="1">
                    <label for="wpinv_adddress_confirmed"><?php _e('The country of your current location must be the same as the country of your billing location or you must confirm the billing address is your home country.', 'invoicing'); ?></label>
                </span>
            </div>
        </div>
        <?php if ( empty( $wpinv_options['hide_ip_address'] ) ) { 
            $ip_link = '<a title="' . esc_attr( __( 'View more details on map', 'invoicing' ) ) . '" target="_blank" href="' . esc_url( admin_url( 'admin-ajax.php?action=wpinv_ip_geolocation&ip=' . $ip_address ) ) . '" class="wpi-ip-address-link">' . $ip_address . '&nbsp;&nbsp;<i class="fa fa-external-link-square" aria-hidden="true"></i></a>';
        ?>
        <div class="wpi-ip-info clearfix panel panel-info">
            <div id="wpinv-fields-box" class="panel-body">
                <span><?php echo wp_sprintf( __( "Your IP address is: %s", 'invoicing' ), $ip_link ); ?></span>
            </div>
        </div>
        <?php }
    }
    
    public static function show_vat_notice( $invoice ) {
        if ( empty( $invoice ) ) {
            return NULL;
        }
        
        $label      = wpinv_get_option( 'vat_invoice_notice_label' );
        $notice     = wpinv_get_option( 'vat_invoice_notice' );
        if ( $label || $notice ) {
        ?>
        <div class="row wpinv-vat-notice">
            <div class="col-sm-12">
                <?php if ( $label ) { ?>
                <strong><?php _e( $label, 'invoicing' ); ?></strong>
                <?php } if ( $notice ) { ?>
                <?php echo wpautop( wptexturize( __( $notice, 'invoicing' ) ) ) ?>
                <?php } ?>
            </div>
        </div>
        <?php
        }
    }
}

global $wpinv_euvat;
$wpinv_euvat = WPInv_EUVat::get_instance();