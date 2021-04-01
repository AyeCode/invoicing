<?php
/**
 * Contains the tax functions.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of eu states.
 * 
 * @return array
 */
function getpaid_get_eu_states() {
    return wpinv_get_data( 'eu-states' );
}

/**
 * Checks if a given country is an EU state.
 * 
 * @return bool
 */
function getpaid_is_eu_state( $country ) {
    return ! empty( $country ) && in_array( strtoupper( $country ), getpaid_get_eu_states() ) ? true : false;
}

/**
 * Returns an array of gst states.
 * 
 * @return array
 */
function getpaid_get_gst_states() {
    return array( 'AU', 'NZ', 'CA', 'CN' );
}

/**
 * Checks if a given country is GST country.
 * 
 * @return bool
 */
function getpaid_is_gst_country( $country ) {
    return ! empty( $country ) && in_array( strtoupper( $country ), getpaid_get_gst_states() ) ? true : false;
}

/**
 * Checks whether or not taxes are enabled.
 *
 * @return bool
 */
function wpinv_use_taxes() {

    $ret = wpinv_get_option( 'enable_taxes', false );
    return (bool) apply_filters( 'wpinv_use_taxes', ! empty( $ret ) );

}

/**
 * Checks whether or not an invoice is taxable.
 *
 * @param WPInv_Invoice $invoice
 * @return bool
 */
function wpinv_is_invoice_taxable( $invoice ) {
    return $invoice->is_taxable();
}

/**
 * Checks whether or not a given country is taxable.
 *
 * @param string $country
 * @return bool
 */
function wpinv_is_country_taxable( $country ) {
    $is_eu     = getpaid_is_eu_state( $country );
    $is_exempt = ! $is_eu && wpinv_is_base_country( $country ) && wpinv_same_country_exempt_vat();

    return (bool) apply_filters( 'wpinv_is_country_taxable', ! $is_exempt, $country ); 

}

/**
 * Checks whether or not an item is taxable.
 *
 * @param WPInv_Item|GetPaid_Form_Item $item
 * @return bool
 */
function wpinv_is_item_taxable( $item ) {
    return '_exempt' != $item->get_vat_rule();
}

/**
 * Checks whether or not taxes are calculated based on the store address.
 *
 * @return bool
 */
function wpinv_use_store_address_as_tax_base() {
    $use_base = wpinv_get_option( 'tax_base', 'billing' ) == 'base';
    return (bool) apply_filters( 'wpinv_use_store_address_as_tax_base', $use_base );
}

/**
 * Checks whether or not prices include tax.
 *
 * @return bool
 */
function wpinv_prices_include_tax() {
    $is_inclusive = wpinv_get_option( 'prices_include_tax', 'no' ) == 'yes';
    return (bool) apply_filters( 'wpinv_prices_include_tax', $is_inclusive );
}

/**
 * Checks whether we should round per rate or per subtotal
 *
 * @return bool
 */
function wpinv_round_tax_per_tax_rate() {
    $subtotal_rounding = wpinv_get_option( 'tax_subtotal_rounding', 1 );
    return (bool) apply_filters( 'wpinv_round_tax_per_tax_rate', empty( $subtotal_rounding ) );
}

/**
 * Checks whether we should display individual tax rates.
 *
 * @return bool
 */
function wpinv_display_individual_tax_rates() {
    $individual = wpinv_get_option( 'tax_display_totals', 'single' ) == 'individual';
    return (bool) apply_filters( 'wpinv_display_individual_tax_rates', $individual );
}

/**
 * Retrieves the default tax rate.
 *
 * @return float
 */
function wpinv_get_default_tax_rate() {
    $rate = wpinv_get_option( 'tax_rate', 0 );
    return (float) apply_filters( 'wpinv_get_default_tax_rate', floatval( $rate ) );
}

/**
 * Checks if we should exempt same country vat.
 *
 * @return bool
 */
function wpinv_same_country_exempt_vat() {
    return 'no' == wpinv_get_option( 'vat_same_country_rule', 'vat_too' );
}

/**
 * Retrieves an array of all tax rates.
 *
 * @return array
 */
function wpinv_get_tax_rates() {
    return GetPaid_Tax::get_all_tax_rates();
}

/**
 * Retrieves an item's tax rates.
 *
 * @param WPInv_Item|GetPaid_Form_Item $item
 * @param string $country
 * @param string $state
 * @return array
 */
function getpaid_get_item_tax_rates( $item, $country = '', $state = '' ) {

    // Abort if the item is not taxable.
    if ( ! wpinv_is_item_taxable( $item ) ) {
        return array();
    }

    // Maybe use the store address.
    if ( wpinv_use_store_address_as_tax_base() ) {
        $country = wpinv_get_default_country();
        $state   = wpinv_get_default_state();
    }

    // Retrieve tax rates.
    $tax_rates = GetPaid_Tax::get_address_tax_rates( $country, $state );

    // Fallback to the default tax rates if non were found.
    if ( empty( $tax_rates ) ) {
        $tax_rates = GetPaid_Tax::get_default_tax_rates();
    }

    return apply_filters( 'getpaid_get_item_tax_rates', $tax_rates, $item, $country, $state );
}

/**
 * Filters an item's tax rate.
 *
 * @param WPInv_Item|GetPaid_Form_Item $item
 * @param array $rates
 * @return array
 */
function getpaid_filter_item_tax_rates( $item, $rates ) {

    $tax_class = $item->get_vat_class();

    foreach ( $rates as $i => $rate ) {

        if ( $tax_class == '_reduced' ) {
            $rates[ $i ]['rate'] = empty( $rate['reduced_rate'] ) ? 0 : $rate['reduced_rate'];
        }

        if ( $tax_class == '_exempt' ) {
            $rates[ $i ]['rate'] = 0;
        }

    }

    return apply_filters( 'getpaid_filter_item_tax_rates', $rates, $item );
}

/**
 * Retrieves an item's taxes.
 *
 * @param float $amount
 * @param array $rates
 * @return array
 */
function getpaid_calculate_item_taxes( $amount, $rates ) {

    $is_inclusive = wpinv_prices_include_tax();
    $taxes        = GetPaid_Tax::calc_tax( $amount, $rates, $is_inclusive );

    return apply_filters( 'getpaid_calculate_taxes', $taxes, $amount, $rates );
}

/**
 * Prepares an item's tax.
 *
 * @param WPInv_Item|GetPaid_Form_Item $item
 * @param string $tax_name
 * @param float $tax_amount
 * @param float $recurring_tax_amount
 * @return array
 */
function getpaid_prepare_item_tax( $item, $tax_name, $tax_amount, $recurring_tax_amount ) {

    $initial_tax   = $tax_amount;
	$recurring_tax = 0;

    if ( $item->is_recurring() ) {
		$recurring_tax = $recurring_tax_amount;
	}

	return array(
		'name'          => sanitize_text_field( $tax_name ),
		'initial_tax'   => $initial_tax,
		'recurring_tax' => $recurring_tax,
    );

}

/**
 * Sanitizes a VAT number.
 *
 * @param string $vat_number
 * @return string
 */
function wpinv_sanitize_vat_number( $vat_number ) {
    return str_replace( array(' ', '.', '-', '_', ',' ), '', strtoupper( trim( $vat_number ) ) );
}

/**
 * Validates a vat number via a REGEX.
 *
 * @param string $vat_number
 * @return bool
 */
function wpinv_regex_validate_vat_number( $vat_number ) {

    $country    = substr( $vat_number, 0, 2 );
    $vatin      = substr( $vat_number, 2 );
    $regexes    = wpinv_get_data( 'vat-number-regexes' );

    if ( isset( $regexes[ $country ] ) ) {

        $regex = $regexes[ $country ];
        $regex = '/^(?:' . $regex . ')$/';
        return 1 === preg_match( $regex, $vatin );

    }

    // Not an EU state, use filters to validate the number.
    return apply_filters( 'wpinv_regex_validate_vat_number', true, $vat_number );
}

/**
 * Validates a vat number via a VIES.
 *
 * @param string $vat_number
 * @return bool
 */
function wpinv_vies_validate_vat_number( $vat_number ) {

    $country    = substr( $vat_number, 0, 2 );
    $vatin      = substr( $vat_number, 2 );

    $url        = add_query_arg(
        array(
            'ms'  => urlencode( $country ),
            'iso' => urlencode( $country ),
            'vat' => urlencode( $vatin ),
        ),
        'http://ec.europa.eu/taxation_customs/vies/viesquer.do'
    );

    $response   = wp_remote_get( $url );
    $response   = wp_remote_retrieve_body( $response );

    // Fallback gracefully if the VIES website is down.
    if ( empty( $response ) ) {
        return true;
    }

    return 1 !== preg_match( '/invalid VAT number/i', $response );

}

/**
 * Validates a vat number.
 *
 * @param string $vat_number
 * @param string $country
 * @return bool
 */
function wpinv_validate_vat_number( $vat_number, $country ) {

    // In case the vat number does not have a country code...
    $vat_number = wpinv_sanitize_vat_number( $vat_number );
    $_country   = substr( $vat_number, 0, 2 );
    $_country   = $_country == wpinv_country_name( $_country );

    if ( $_country ) {
        $vat_number = strtoupper( $country ) . $vat_number;
    }

    return wpinv_regex_validate_vat_number( $vat_number ) && wpinv_vies_validate_vat_number( $vat_number );
}

/**
 * Checks whether or not we should validate vat numbers.
 *
 * @return bool
 */
function wpinv_should_validate_vat_number() {
    $validate = wpinv_get_option( 'validate_vat_number' );
	return ! empty( $validate );
}

function wpinv_sales_tax_for_year( $year = null ) {
    return wpinv_price( wpinv_get_sales_tax_for_year( $year ) );
}

function wpinv_get_sales_tax_for_year( $year = null ) {
    global $wpdb;

    // Start at zero
    $tax = 0;

    if ( ! empty( $year ) ) {
        $args = array(
            'post_type'      => 'wpi_invoice',
            'post_status'    => array( 'publish' ),
            'posts_per_page' => -1,
            'year'           => $year,
            'fields'         => 'ids'
        );

        $payments    = get_posts( $args );
        $payment_ids = implode( ',', $payments );

        if ( count( $payments ) > 0 ) {
            $sql = "SELECT SUM( meta_value ) FROM $wpdb->postmeta WHERE meta_key = '_wpinv_tax' AND post_id IN( $payment_ids )";
            $tax = $wpdb->get_var( $sql );
        }

    }

    return apply_filters( 'wpinv_get_sales_tax_for_year', $tax, $year );
}

function wpinv_is_cart_taxed() {
    return wpinv_use_taxes();
}

function wpinv_prices_show_tax_on_checkout() {
    return false; // TODO
    $ret = ( wpinv_get_option( 'checkout_include_tax', false ) == 'yes' && wpinv_use_taxes() );

    return apply_filters( 'wpinv_taxes_on_prices_on_checkout', $ret );
}

function wpinv_display_tax_rate() {
    $ret = wpinv_use_taxes() && wpinv_get_option( 'display_tax_rate', false );

    return apply_filters( 'wpinv_display_tax_rate', $ret );
}

function wpinv_cart_needs_tax_address_fields() {
    if( !wpinv_is_cart_taxed() )
        return false;

    return ! did_action( 'wpinv_after_cc_fields', 'wpinv_default_cc_address_fields' );
}

function wpinv_item_is_tax_exclusive( $item_id = 0 ) {
    $ret = (bool)get_post_meta( $item_id, '_wpinv_tax_exclusive', false );
    return apply_filters( 'wpinv_is_tax_exclusive', $ret, $item_id );
}

function wpinv_currency_decimal_filter( $decimals = 2 ) {
    $currency = wpinv_get_currency();

    switch ( $currency ) {
        case 'RIAL' :
        case 'JPY' :
        case 'TWD' :
        case 'HUF' :
            $decimals = 0;
            break;
    }

    return apply_filters( 'wpinv_currency_decimal_count', $decimals, $currency );
}

function wpinv_tax_amount() {
    $output = 0.00;
    
    return apply_filters( 'wpinv_tax_amount', $output );
}

/**
 * Filters the VAT rules to ensure that each item has a VAT rule.
 * 
 * @param string|bool|null $vat_rule
 */
function getpaid_filter_vat_rule( $vat_rule ) {

    if ( empty( $vat_rule ) ) {        
        return 'digital';
    }

    return $vat_rule;
}
add_filter( 'wpinv_get_item_vat_rule', 'getpaid_filter_vat_rule' );

/**
 * Filters the VAT class to ensure that each item has a VAT class.
 * 
 * @param string|bool|null $vat_rule
 */
function getpaid_filter_vat_class( $vat_class ) {
    return empty( $vat_class ) ? '_standard' : $vat_class;
}
add_filter( 'wpinv_get_item_vat_class', 'getpaid_filter_vat_class' );

/**
 * Returns a list of all tax classes.
 * 
 * @return array
 */
function getpaid_get_tax_classes() {

    return apply_filters(
        'getpaid_tax_classes',
        array(
            '_standard' => __( 'Standard Tax Rate', 'invoicing' ),
            '_reduced'  => __( 'Reduced Tax Rate', 'invoicing' ),
            '_exempt'   => __( 'Tax Exempt', 'invoicing' ),
        )
    );

}

/**
 * Returns a list of all tax rules.
 * 
 * @return array
 */
function getpaid_get_tax_rules() {

    return apply_filters(
        'getpaid_tax_rules',
        array(
            'physical' => __( 'Physical Item', 'invoicing' ),
            'digital'  => __( 'Digital Item', 'invoicing' ),
        )
    );

}

/**
 * Returns the label of a tax class.
 * 
 * @param string $tax_class
 * @return string
 */
function getpaid_get_tax_class_label( $tax_class ) {

    $classes = getpaid_get_tax_classes();

    if ( isset( $classes[ $tax_class ] ) ) {
        return sanitize_text_field( $classes[ $tax_class ] );
    }

    return sanitize_text_field( $tax_class );

}

/**
 * Returns the label of a tax rule.
 * 
 * @param string $tax_rule
 * @return string
 */
function getpaid_get_tax_rule_label( $tax_rule ) {

    $rules = getpaid_get_tax_rules();

    if ( isset( $rules[ $tax_rule ] ) ) {
        return sanitize_text_field( $rules[ $tax_rule ] );
    }

    return sanitize_text_field( $tax_rule );

}

/**
 * Returns the taxable amount
 *
 * @param GetPaid_Form_Item $item
 * @param string $recurring
 * @return string
 */
function getpaid_get_taxable_amount( $item, $recurring = false ) {

    $taxable_amount  = $recurring ? $item->get_recurring_sub_total() : $item->get_sub_total();
    $taxable_amount -= $recurring ? $item->recurring_item_discount : $item->item_discount;
    $taxable_amount  = max( 0, $taxable_amount );
    return apply_filters( 'getpaid_taxable_amount', $taxable_amount, $item, $recurring );

}
