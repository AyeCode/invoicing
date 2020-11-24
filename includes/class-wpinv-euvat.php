<?php
/**
 * Tax calculation and rate finding class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * @deprecated
 */
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
    public static function get_eu_states() {
        return getpaid_get_eu_states();
    }

    /**
     * @deprecated
     */
    public static function get_gst_countries() {
        return getpaid_get_gst_states();
    }

    /**
     * @deprecated
     */
    public static function is_eu_state( $country_code ) {
        return getpaid_is_eu_state( $country_code );
    }

    /**
     * @deprecated
     */
    public static function is_gst_country( $country_code ) {
        return getpaid_is_gst_country( $country_code );
    }

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
    public static function maxmind_folder() {}

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
    public static function geoip2_country_dbfile() {}

    /**
     * @deprecated
     */
    public static function geoip2_city_dbfile() {}

    /**
     * @deprecated
     */
    public static function geoip2_country_reader() {}

    /**
     * @deprecated
     */
    public static function geoip2_city_reader() {}

    /**
     * @deprecated
     */
    public static function geoip2_country_record() {}

    /**
     * @deprecated
     */
    public static function geoip2_city_record() {}

    /**
     * @deprecated
     */
    public static function geoip2_country_code() {}

    /**
     * @deprecated
     */
    public static function get_country_by_ip() {}

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
    public static function same_country_rule() {}

    /**
     * @deprecated
     */
    public static function sanitize_vat() {}

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
    public static function requires_vat() {}

    /**
     * @deprecated
     */
    public static function tax_label() {}

    /**
     * @deprecated
     */
    public static function get_rate_classes() {}

    /**
     * @deprecated
     */
    public static function get_all_classes() {}

    /**
     * @deprecated
     */
    public static function get_class_desc() {}

    /**
     * @deprecated
     */
    public static function get_vat_groups() {}

    /**
     * @deprecated
     */
    public static function get_rules() {}

    /**
     * @deprecated
     */
    public static function get_vat_rates() {}

    /**
     * @deprecated
     */
    public static function get_non_standard_rates() {}

    /**
     * @deprecated
     */
    public static function item_class_label() {}

    /**
     * @deprecated
     */
    public static function get_item_rule() {}

    /**
     * @deprecated
     */
    public static function item_rule_label() {}

    /**
     * @deprecated
     */
    public static function item_has_digital_rule() {}

    /**
     * @deprecated
     */
    public static function invoice_has_digital_rule() {}

    /**
     * @deprecated
     */
    public static function item_is_taxable() {}

    /**
     * @deprecated
     */
    public static function find_rate() {}

    /**
     * @deprecated
     */
    public static function get_rate() {}

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

