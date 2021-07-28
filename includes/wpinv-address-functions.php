<?php
/**
 * Contains functions related to Invoicing plugin.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}


function wpinv_get_default_country() {
	$country = wpinv_get_option( 'default_country', 'UK' );

	return apply_filters( 'wpinv_default_country', $country );
}

/**
 * GeoLocates an IP Address.
 *
 * @return string
 */
function getpaid_get_ip_country( $ip_address = '' ) {
    $country = GetPaid_Geolocation::geolocate_ip( $ip_address, true );
    return $country['country'];
}

/**
 * Sanitizes a country code.
 * 
 * @param string $country The country code to sanitize
 * @return array
 */
function wpinv_sanitize_country( $country ) {

	// Enure the country is specified
    if ( empty( $country ) ) {
        $country = wpinv_get_default_country();
    }
    return trim( wpinv_utf8_strtoupper( $country ) );

}

function wpinv_is_base_country( $country ) {
    $base_country = wpinv_get_default_country();
    
    if ( $base_country === 'UK' ) {
        $base_country = 'GB';
    }
    if ( $country == 'UK' ) {
        $country = 'GB';
    }

    return ( $country && $country === $base_country ) ? true : false;
}

function wpinv_country_name( $country_code = '' ) { 
    $countries = wpinv_get_country_list();
    $country_code = $country_code == 'UK' ? 'GB' : $country_code;
    $country = isset( $countries[$country_code] ) ? $countries[$country_code] : $country_code;

    return apply_filters( 'wpinv_country_name', $country, $country_code );
}

function wpinv_get_default_state() {
	$state = wpinv_get_option( 'default_state', '' );

	return apply_filters( 'wpinv_default_state', $state );
}

function wpinv_state_name( $state_code = '', $country_code = '' ) {
    $state = $state_code;
    
    if ( !empty( $country_code ) ) {
        $states = wpinv_get_country_states( $country_code );
        
        $state = !empty( $states ) && isset( $states[$state_code] ) ? $states[$state_code] : $state;
    }

    return apply_filters( 'wpinv_state_name', $state, $state_code, $country_code );
}

function wpinv_store_address() {
    $address = wpinv_get_option( 'store_address', '' );

    return apply_filters( 'wpinv_store_address', $address );
}

/**
 * (Maybe) adds the default address to an invoice.
 *
 * @param WPInv_Invoice $invoice
 */
function getpaid_maybe_add_default_address( &$invoice ) {

    $user_id = $invoice->get_user_id();

    // Abort if the invoice belongs to no one.
    if ( empty( $user_id ) ) {
        return;
    }

    // Fill in defaults whenever necessary.
    foreach ( wpinv_get_user_address( $user_id ) as $key => $value ) {

        if ( is_callable( $invoice, "get_$key" ) ) {
            $current = call_user_func( array( $invoice, "get_$key" ) );

            if ( empty( $current ) ) {
                $method = "set_$key";
                $invoice->$method( $value );
            }

        }

    }

}

/**
 * Returns an array of user address fields
 * 
 * @return array
 */
function getpaid_user_address_fields() {

    $address_fields = apply_filters(
        'getpaid_user_address_fields',
        array(
            'first_name' => __( 'First Name', 'invoicing' ),
            'last_name'  => __( 'Last Name', 'invoicing' ),
            'address'    => __( 'Address', 'invoicing' ),
            'city'       => __( 'City', 'invoicing' ),
            'country'    => __( 'Country', 'invoicing' ),
            'state'      => __( 'State', 'invoicing' ),
            'zip'        => __( 'Zip/Postal Code', 'invoicing' ),
            'phone'      => __( 'Phone Number', 'invoicing' ),
            'company'    => __( 'Company', 'invoicing' ),
            'company_id'    => __( 'Company ID', 'invoicing' ),
            'vat_number' => __( 'VAT Number', 'invoicing' ),
        )
    );

    if ( ! wpinv_use_taxes() && isset( $address_fields['vat_number'] ) ) {
        unset( $address_fields['vat_number'] );
    }

    return $address_fields;
}

/**
 * Checks whether or not an address field is whitelisted.
 * 
 * @return bool
 */
function getpaid_is_address_field_whitelisted( $key ) {
    return array_key_exists( $key, getpaid_user_address_fields() );
}

/**
 * Saves a user address.
 *
 * This function is called whenever an invoice is created/updated to ensure that the user address is always up to date.
 *
 * @param WPInv_Invoice $invoice
 */
function getpaid_save_invoice_user_address( $invoice ) {

    // Retrieve the invoice.
    $invoice = wpinv_get_invoice( $invoice );

    // Abort if it does not exist.
    if ( empty( $invoice ) || $invoice->is_renewal() ) {
        return;
    }

    foreach ( array_keys( getpaid_user_address_fields() ) as $field ) {

        if ( is_callable( array( $invoice, "get_$field" ) ) ) {
            $value = call_user_func( array( $invoice, "get_$field" ) );

            // Only save if it is not empty.
            if ( ! empty( $value ) ) {
                update_user_meta( $invoice->get_user_id(), '_wpinv_' . $field, $value );
            }

        }

    }

}
add_action( 'getpaid_checkout_invoice_updated', 'getpaid_save_invoice_user_address' );

/**
 * Retrieves a saved user address.
 *
 * @param int $user_id The user id whose address we should get. Defaults to the current user id.
 * @param bool $with_default Whether or not we should use the default country and state.
 * @return array
 */
function wpinv_get_user_address( $user_id = 0, $with_default = true ) {

    // Prepare the user id.
    $user_id   = empty( $user_id ) ? get_current_user_id() : $user_id;
    $user_info = get_userdata( $user_id );

    // Abort if non exists.
    if ( empty( $user_info ) ) {
        return array();
    }

    // Prepare the address.
    $address = array(
        'user_id' => $user_id,
        'email'   => $user_info->user_email,
    );

    foreach ( array_keys( getpaid_user_address_fields() ) as $field ) {
        $address[$field] = getpaid_get_user_address_field( $user_id, $field );
    }

    if ( ! $with_default ) {
        return $address;
    }

    $defaults = array(
        'first_name' => $user_info->first_name,
        'last_name'  => $user_info->last_name,
        'state'      => wpinv_get_default_state(),
        'country'    => wpinv_get_default_country(),
    );

    return getpaid_array_merge_if_empty( $address, $defaults );

}

/**
 * Retrieves a saved user address field.
 *
 * @param int $user_id The user id whose address field we should get.
 * @param string $field The field to use.
 * @return string|null
 */
function getpaid_get_user_address_field( $user_id, $field ) {

    $prefixes = array(
        '_wpinv_',
        'billing_',
        ''
    );

    foreach ( $prefixes as $prefix ) {

        // Meta table.
        $value = get_user_meta( $user_id, $prefix . $field, true );
        
        // UWP table.
        $value = ( empty( $value ) && function_exists( 'uwp_get_usermeta' ) ) ? uwp_get_usermeta( $user_id, $prefix . $field ) : $value;

        if ( ! empty( $value ) ) {
            return $value;
        }

    }

    return null;

}

/**
 * Get all continents.
 * 
 * @since 1.0.14
 * @param string $return What to return.
 * @return array
 */
function wpinv_get_continents( $return = 'all' ) {

    $continents = wpinv_get_data( 'continents' );

    switch( $return ) {
        case 'name' :
            return wp_list_pluck( $continents, 'name' );
            break;
        case 'countries' :
            return wp_list_pluck( $continents, 'countries' );
            break;
        default :
            return $continents;
            break;
    }

}

/**
 * Get continent code for a country code.
 * 
 * @since 1.0.14
 * @param string $country Country code. If no code is specified, defaults to the default country.
 * @return string
 */
function wpinv_get_continent_code_for_country( $country = false ) {

    $country = wpinv_sanitize_country( $country );
    
	foreach ( wpinv_get_continents( 'countries' ) as $continent_code => $countries ) {
		if ( false !== array_search( $country, $countries, true ) ) {
			return $continent_code;
		}
	}

    return '';
    
}

/**
 * Get all calling codes.
 * 
 * @since 1.0.14
 * @param string $country Country code. If no code is specified, defaults to the default country.
 * @return array
 */
function wpinv_get_country_calling_code( $country = null) {

    $country = wpinv_sanitize_country( $country );
    $codes   = wpinv_get_data( 'phone-codes' );
    $code    = isset( $codes[ $country ] ) ? $codes[ $country ] : '';

    if ( is_array( $code ) ) {
        return $code[0];
    }
    return $code;

}

/**
 * Get all countries.
 * 
 * @param bool $first_empty Whether or not the first item in the list should be empty
 * @return array
 */
function wpinv_get_country_list( $first_empty = false ) {
    return wpinv_maybe_add_empty_option( apply_filters( 'wpinv_countries', wpinv_get_data( 'countries' ) ), $first_empty );
}

/**
 * Retrieves a given country's states.
 * 
 * @param string $country Country code. If no code is specified, defaults to the default country.
 * @param bool $first_empty Whether or not the first item in the list should be empty
 * @return array
 */
function wpinv_get_country_states( $country = null, $first_empty = false ) {
    
    // Prepare the country.
    $country = wpinv_sanitize_country( $country );

    // Fetch all states.
    $all_states = wpinv_get_data( 'states' );

    // Fetch the specified country's states.
    $states     = isset( $all_states[ $country ] ) ? $all_states[ $country ] : array() ;
    $states     = apply_filters( "wpinv_{$country}_states", $states );
    $states     = apply_filters( 'wpinv_country_states', $states, $country );

    asort( $states );
     
    return wpinv_maybe_add_empty_option( $states, $first_empty );
}

/**
 * Returns US states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_us_states_list() {
    return apply_filters( 'wpinv_usa_states', wpinv_get_country_states( 'US' ) );
}

/**
 * Returns Canada states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_canada_states_list() {
    return apply_filters( 'wpinv_canada_provinces', wpinv_get_country_states( 'CA' ) );
}

/**
 * Returns australian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_australia_states_list() {
    return apply_filters( 'wpinv_australia_states', wpinv_get_country_states( 'AU' ) );
}

/**
 * Returns bangladeshi states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_bangladesh_states_list() {
    return apply_filters( 'wpinv_bangladesh_states', wpinv_get_country_states( 'BD' ) );
}

/**
 * Returns brazilianUS states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_brazil_states_list() {
    return apply_filters( 'wpinv_brazil_states', wpinv_get_country_states( 'BR' ) );
}

/**
 * Returns bulgarian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_bulgaria_states_list() {
    return apply_filters( 'wpinv_bulgaria_states', wpinv_get_country_states( 'BG' ) );
}

/**
 * Returns hong kon states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_hong_kong_states_list() {
    return apply_filters( 'wpinv_hong_kong_states', wpinv_get_country_states( 'HK' ) );
}

/**
 * Returns hungarian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_hungary_states_list() {
    return apply_filters( 'wpinv_hungary_states', wpinv_get_country_states( 'HU' ) );
}

/**
 * Returns japanese states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_japan_states_list() {
    return apply_filters( 'wpinv_japan_states', wpinv_get_country_states( 'JP' ) );
}

/**
 * Returns chinese states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_china_states_list() {
    return apply_filters( 'wpinv_china_states', wpinv_get_country_states( 'CN' ) );
}

/**
 * Returns new zealand states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_new_zealand_states_list() {
    return apply_filters( 'wpinv_new_zealand_states', wpinv_get_country_states( 'NZ' ) );
}

/**
 * Returns perusian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_peru_states_list() {
    return apply_filters( 'wpinv_peru_states', wpinv_get_country_states( 'PE' ) );
}

/**
 * Returns indonesian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_indonesia_states_list() {
    return apply_filters( 'wpinv_indonesia_states', wpinv_get_country_states( 'ID' ) );
}

/**
 * Returns indian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_india_states_list() {
    return apply_filters( 'wpinv_india_states', wpinv_get_country_states( 'IN' ) );
}

/**
 * Returns iranian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_iran_states_list() {
    return apply_filters( 'wpinv_iran_states', wpinv_get_country_states( 'IR' ) );
}

/**
 * Returns italian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_italy_states_list() {
    return apply_filters( 'wpinv_italy_states', wpinv_get_country_states( 'IT' ) );
}

/**
 * Returns malaysian states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_malaysia_states_list() {
    return apply_filters( 'wpinv_malaysia_states', wpinv_get_country_states( 'MY' ) );
}

/**
 * Returns mexican states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_mexico_states_list() {
    return apply_filters( 'wpinv_mexico_states', wpinv_get_country_states( 'MX' ) );
}

/**
 * Returns nepal states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_nepal_states_list() {
    return apply_filters( 'wpinv_nepal_states', wpinv_get_country_states( 'NP' ) );
}

/**
 * Returns south african states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_south_africa_states_list() {
    return apply_filters( 'wpinv_south_africa_states', wpinv_get_country_states( 'ZA' ) );
}

/**
 * Returns thailandese states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_thailand_states_list() {
    return apply_filters( 'wpinv_thailand_states', wpinv_get_country_states( 'TH' ) );
}

/**
 * Returns turkish states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_turkey_states_list() {
    return apply_filters( 'wpinv_turkey_states', wpinv_get_country_states( 'TR' ) );
}

/**
 * Returns spanish states.
 * 
 * @deprecated 1.0.14
 * @return array
 */
function wpinv_get_spain_states_list() {
    return apply_filters( 'wpinv_spain_states', wpinv_get_country_states( 'ES' ) );
}

function wpinv_get_states_field() {
	if( empty( $_POST['country'] ) ) {
		$_POST['country'] = wpinv_get_default_country();
	}
	$states = wpinv_get_country_states( sanitize_text_field( $_POST['country'] ) );

	if( !empty( $states ) ) {
		$sanitized_field_name = sanitize_text_field( $_POST['field_name'] );

        $class  = isset( $_POST['class'] ) ? esc_attr( $_POST['class'] ) : '';
        $class .= " $sanitized_field_name getpaid_js_field-state custom-select wpinv-select wpi_select2";

        $args  = array(
			'name'    => $sanitized_field_name,
			'id'      => $sanitized_field_name,
			'class'   => implode( ' ', array_unique( explode( ' ', $class ) ) ),
			'options' => array_merge( array( '' => '' ), $states ),
			'show_option_all'  => false,
			'show_option_none' => false
		);

		$response = wpinv_html_select( $args );

	} else {
		$response = 'nostates';
	}

	return $response;
}

function wpinv_default_billing_country( $country = '', $user_id = 0 ) {
    $country = !empty( $country ) ? $country : wpinv_get_default_country();
    
    return apply_filters( 'wpinv_default_billing_country', $country, $user_id );
}

/**
 * Returns country address formats.
 *
 * These define how addresses are formatted for display in various countries.
 *
 * @return array
 */
function wpinv_get_address_formats() {

		return apply_filters( 'wpinv_localisation_address_formats',
			array(
				'default' => "{{name}}\n{{company}}\n{{address}}\n{{city}}\n{{state}}\n{{zip}}\n{{country}}",
				'AU'      => "{{name}}\n{{company}}\n{{address}}\n{{city}}\n{{state}} {{zip}}\n{{country}}",
				'AT'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'BE'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'CA'      => "{{company}}\n{{name}}\n{{address}}\n{{city}} {{state_code}}&nbsp;&nbsp;{{zip}}\n{{country}}",
				'CH'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'CL'      => "{{company}}\n{{name}}\n{{address}}\n{{state}}\n{{zip}} {{city}}\n{{country}}",
				'CN'      => "{{country}} {{zip}}\n{{state}}, {{city}}, {{address}}\n{{company}}\n{{name}}",
				'CZ'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'DE'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'EE'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'FI'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'DK'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'FR'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city_upper}}\n{{country}}",
				'HK'      => "{{company}}\n{{first_name}} {{last_name_upper}}\n{{address}}\n{{city_upper}}\n{{state_upper}}\n{{country}}",
				'HU'      => "{{name}}\n{{company}}\n{{city}}\n{{address}}\n{{zip}}\n{{country}}",
				'IN'      => "{{company}}\n{{name}}\n{{address}}\n{{city}} {{zip}}\n{{state}}, {{country}}",
				'IS'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'IT'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}}\n{{city}}\n{{state_upper}}\n{{country}}",
				'JP'      => "{{zip}}\n{{state}} {{city}} {{address}}\n{{company}}\n{{last_name}} {{first_name}}\n{{country}}",
				'TW'      => "{{company}}\n{{last_name}} {{first_name}}\n{{address}}\n{{state}}, {{city}} {{zip}}\n{{country}}",
				'LI'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'NL'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'NZ'      => "{{name}}\n{{company}}\n{{address}}\n{{city}} {{zip}}\n{{country}}",
				'NO'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'PL'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'PT'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'SK'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'RS'      => "{{name}}\n{{company}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'SI'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'ES'      => "{{name}}\n{{company}}\n{{address}}\n{{zip}} {{city}}\n{{state}}\n{{country}}",
				'SE'      => "{{company}}\n{{name}}\n{{address}}\n{{zip}} {{city}}\n{{country}}",
				'TR'      => "{{name}}\n{{company}}\n{{address}}\n{{zip}} {{city}} {{state}}\n{{country}}",
				'UG'      => "{{name}}\n{{company}}\n{{address}}\n{{city}}\n{{state}}, {{country}}",
				'US'      => "{{name}}\n{{company}}\n{{address}}\n{{city}}, {{state_code}} {{zip}}\n{{country}}",
				'VN'      => "{{name}}\n{{company}}\n{{address}}\n{{city}}\n{{country}}",
			)
		);
}

/**
 * Retrieves the address format to use on Invoices.
 * 
 * @since 1.0.13
 * @see `wpinv_get_invoice_address_replacements`
 * @return string
 */
function wpinv_get_full_address_format( $country = false) {

    if( empty( $country ) ) {
        $country = wpinv_get_default_country();
    }

    // Get all formats.
	$formats = wpinv_get_address_formats();

	// Get format for the specified country.
	$format = ( $country && isset( $formats[ $country ] ) ) ? $formats[ $country ] : $formats['default'];
    
    /**
	 * Filters the address format to use on Invoices.
     * 
     * New lines will be replaced by a `br` element. Double new lines will be replaced by a paragraph. HTML tags are allowed.
	 *
	 * @since 1.0.13
	 *
	 * @param string $format  The address format to use.
     * @param string $country The country who's address format is being retrieved.
	 */
    return apply_filters( 'wpinv_get_full_address_format', $format, $country );
}

/**
 * Retrieves the address format replacements to use on Invoices.
 * 
 * @since 1.0.13
 * @see `wpinv_get_full_address_format`
 * @param array $billing_details customer's billing details
 * @return array
 */
function wpinv_get_invoice_address_replacements( $billing_details ) {

    $default_args = array(
        'address'           => '',
        'city'              => '',
        'state'             => '',
        'country'           => '',
        'zip'               => '',
        'first_name'        => '',
		'last_name'         => '',
		'company'           => '',
    );

    $args    = map_deep( wp_parse_args( $billing_details, $default_args ), 'trim' );
    $state   = $args['state'];
    $country = $args['country'];

    // Handle full country name.
    $full_country = empty( $country ) ? $country : wpinv_country_name( $country );

    // Handle full state name.
    $full_state   = ( $country && $state ) ?  wpinv_state_name( $state, $country ) : $state;

    $args['postcode']    = $args['zip'];
    $args['name']        = $args['first_name'] . ' ' . $args['last_name'];
    $args['state']       = $full_state;
    $args['state_code']  = $state;
    $args['country']     = $full_country;
    $args['country_code']= $country;

    /**
	 * Filters the address format replacements to use on Invoices.
     * 
	 *
	 * @since 1.0.13
	 *
	 * @param array $replacements  The address replacements to use.
     * @param array $billing_details  The billing details to use.
	 */
    $replacements = apply_filters( 'wpinv_get_invoice_address_replacements', $args, $billing_details );

    $return = array();

    foreach( $replacements as $key => $value ) {
        $value  = is_scalar( $value ) ? trim( sanitize_text_field( $value ) ) : '';
        $return['{{' . $key . '}}'] = $value;
        $return['{{' . $key . '_upper}}'] = wpinv_utf8_strtoupper( $value );
    }

    return $return;

}

/**
 * Trim white space and commas off a line.
 *
 * @param  string $line Line.
 * @since 1.0.14
 * @return string
 */
function wpinv_trim_formatted_address_line( $line ) {
	return trim( $line, ', ' );
}