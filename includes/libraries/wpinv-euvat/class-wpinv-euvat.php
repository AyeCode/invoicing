<?php
/**
 * Tax calculation and rate finding class.
 *
 */

defined( 'ABSPATH' ) || exit;

class WPInv_EUVat {

    /**
     * Retrieves an instance of this class.
     * 
     * @deprecated
     * @return WPInv_EUVat
     */
    public static function get_instance() {
        return new self();
    }

    /**
     * @deprecated
     */
    public function init() {}

    /**
     * @deprecated
     */
    public static function section_vat_settings() {}

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

    /**
     * @deprecated
     */
    public function enqueue_vat_scripts() {}

    /**
     * @deprecated
     */
    public function load_vat_scripts(){}

    /**
     * @deprecated
     */
    public static function enqueue_admin_scripts() {}

    /**
     * @deprecated
     */
    public static function vat_rates_settings() {}

    /**
	 *
	 * @deprecated
	 */
    public static function vat_settings() {}

    /**
	 *
	 * @deprecated
	 */
    public static function maxmind_folder() {
        return false;
    }

    /**
	 *
	 * @deprecated
	 */
    public static function geoip2_download_database() {}

    /**
	 *
	 * @deprecated
	 */
    public static function geoip2_download_file() {}

    /**
     * @deprecated
     */
    public static function load_geoip2() {}

    /**
     * @deprecated
     */
    public static function geoip2_country_dbfile() {
        return false;
    }

    /**
     * @deprecated
     */
    public static function geoip2_city_dbfile() {
        return false;
    }

    /**
     * @deprecated
     */
    public static function geoip2_country_reader() {
        return false;
    }

    /**
     * @deprecated
     */
    public static function geoip2_city_reader() {
        return false;
    }

    /**
     * @deprecated
     */
    public static function geoip2_country_record() {
        return false;
    }

    /**
     * @deprecated
     */
    public static function geoip2_city_record() {
        return false;
    }

    /**
     * @deprecated
     */
    public static function geoip2_country_code() {
        wpinv_get_default_country();
    }

    /**
     * @deprecated
     */
    public static function get_country_by_ip() {
        return getpaid_get_ip_country();
    }

    /**
     * @deprecated
     */
    public static function sanitize_vat_settings() {}

    /**
     * @deprecated
     */
    public static function sanitize_vat_rates() {}

    /**
     * @deprecated
     */
    public static function add_class() {}

    /**
     * @deprecated
     */
    public static function delete_class() {}

    /**
     * @deprecated
     */
    public static function update_eu_rates() {}

    /**
     * @deprecated
     */
    public static function hide_vat_fields() {}

    /**
     * @deprecated
     */
    public static function same_country_rule() {
        return wpinv_same_country_exempt_vat();
    }

    /**
     * Retrieves the vat name.
     */
    public function get_vat_name() {
        $vat_name = wpinv_get_option( 'vat_name' );
        return empty( $vat_name ) ? __( 'VAT', 'invoicing' ) : sanitize_text_field( $vat_name );
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

    /**
     * @deprecated
     */
    public static function sanitize_vat() {}

    /**
     * @deprecated
     */
    public static function offline_check( $vat_number ) {
        return wpinv_regex_validate_vat_number( $vat_number );
    }

    /**
     * @deprecated
     */
    public static function vies_check() {}

    /**
     * @deprecated
     */
    public static function check_vat() {}

    /**
     * @deprecated
     */
    public static function request_euvatrates() {
        return array();
    }

    /**
     * @deprecated
     */
    public static function requires_vat() {}

    /**
     * @deprecated
     */
    public static function tax_label() {}

    public static function standard_rates_label() {
        return __( 'Standard Rates', 'invoicing' );
    }

    /**
     * @deprecated
     */
    public static function get_rate_classes() {
        return array();
    }

    /**
     * @deprecated
     */
    public static function get_all_classes() {
        return array();
    }

    /**
     * @deprecated
     */
    public static function get_class_desc() {
        return '';
    }

    /**
     * @deprecated
     */
    public static function get_vat_groups() {}

    public static function get_rules() {
        $vat_rules = array(
            'digital' => __( 'Digital Product', 'invoicing' ),
            'physical' => __( 'Physical Product', 'invoicing' ),
            '_exempt' => __( 'Tax-Free Product', 'invoicing' ),
        );
        return apply_filters( 'wpinv_get_vat_rules', $vat_rules );
    }

    /**
     * @deprecated
     */
    public static function get_vat_rates() {
        return array();
    }

    /**
     * @deprecated
     */
    public static function get_non_standard_rates() {
        return array();
    }

    /**
     * @deprecated
     */
    public static function allow_vat_rules() {
        return wpinv_use_taxes();
    }

    /**
     * @deprecated
     */
    public static function allow_vat_classes() {
        return wpinv_use_taxes();
    }

    /**
     * @deprecated
     */
    public static function get_item_class( $postID ) {
        return get_post_meta( $postID, '_wpinv_vat_class', true );
    }

    /**
     * @deprecated
     */
    public static function item_class_label() {
        return '';
    }

    /**
     * @deprecated
     */
    public static function get_item_rule( $postID ) {
        return get_post_meta( $postID, '_wpinv_vat_rule', true );
    }

    /**
     * @deprecated
     */
    public static function item_rule_label() {
        return '';
    }

    /**
     * @deprecated
     */
    public static function item_has_digital_rule() {
        return true;
    }

    /**
     * @deprecated
     */
    public static function invoice_has_digital_rule() {
        return false;
    }

    /**
     * @deprecated
     */
    public static function item_is_taxable() {
        return true;
    }

    /**
     * @deprecated
     */
    public static function find_rate() {
        return array();
    }

    /**
     * @deprecated
     */
    public static function get_rate() {
        return 0;
    }

    /**
     * @deprecated
     */
    public static function current_vat_data() {}

    /**
     * @deprecated
     */
    public static function get_user_country() {}

    /**
     * @deprecated
     */
    public static function set_user_country() {}

    /**
     * @deprecated
     */
    public static function get_user_vat_number() {}

    /**
     * @deprecated
     */
    public static function get_user_company() {}

    /**
     * @deprecated
     */
    public static function save_user_vat_details() {}

    /**
     * @deprecated
     */
    public static function ajax_vat_validate() {}

    /**
     * @deprecated
     */
    public static function validate_vat_number() {}

}

