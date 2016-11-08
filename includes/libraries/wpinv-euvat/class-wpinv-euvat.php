<?php
// Exit if accessed directly.
if (!defined( 'ABSPATH' ) ) exit;

class WPInv_EUVat {
    private static $default_country;
    private static $instance = false;
    
    public static function get_instance() {
        if ( !self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    public function __construct() {
        self::$default_country = wpinv_get_default_country();
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
        $response = file_get_contents( $url );
        
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
    
    public static function requires_vat( $requires_vat = false, $user_id = 0, $is_digital = null ) {
        global $wpinv_options, $wpi_item_id, $wpi_country;
        
        if ( !empty( $_POST['wpinv_country'] ) ) {
            $country_code = trim( $_POST['wpinv_country'] );
        } else if ( !empty( $_POST['country'] ) ) {
            $country_code = trim( $_POST['country'] );
        } else if ( !empty( $wpi_country ) ) {
            $country_code = $wpi_country;
        } else {
            $country_code = wpinv_user_country( '', $user_id );
        }
        
        if ( $is_digital === null && $wpi_item_id ) {
            $is_digital = $wpi_item_id ? wpinv_vat_rule_is_digital( $wpi_item_id ) : wpinv_allow_vat_rules();
        }
        
        if ( !empty( $country_code ) ) {
            $requires_vat = ( self::is_eu_state( $country_code ) && ( self::is_eu_state( self::$default_country ) || $is_digital ) ) || ( self::is_gst_country( $country_code ) && self::is_gst_country( self::$default_country ) );
        }
        
        return apply_filters( 'wpinv_requires_vat', $requires_vat, $user_id );
    }
}

global $wpinv_euvat;
$wpinv_euvat = WPInv_EUVat::get_instance();