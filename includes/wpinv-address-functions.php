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
	$state = wpinv_get_option( 'default_state', false );

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

function wpinv_get_user_address( $user_id = 0, $with_default = true ) {
    global $wpi_userID;
    
    if( empty( $user_id ) ) {
        $user_id = !empty( $wpi_userID ) ? $wpi_userID : get_current_user_id();
    }
    
    $address_fields = array(
        ///'user_id',
        'first_name',
        'last_name',
        'company',
        'vat_number',
        ///'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip',
    );
    
    $user_info = get_userdata( $user_id );
    
    $address = array();
    $address['user_id'] = $user_id;
    $address['email'] = !empty( $user_info ) ? $user_info->user_email : '';
    foreach ( $address_fields as $field ) {
        $address[$field] = get_user_meta( $user_id, '_wpinv_' . $field, true );
    }

    if ( !empty( $user_info ) ) {
        if( empty( $address['first_name'] ) )
            $address['first_name'] = $user_info->first_name;
        
        if( empty( $address['last_name'] ) )
            $address['last_name'] = $user_info->last_name;
    }
    
    $address['name'] = trim( trim( $address['first_name'] . ' ' . $address['last_name'] ), "," );
    
    if( empty( $address['state'] ) && $with_default )
        $address['state'] = wpinv_get_default_state();

    if( empty( $address['country'] ) && $with_default )
        $address['country'] = wpinv_get_default_country();


    return $address;
}

function wpinv_get_country_list( $first_empty = false ) {
	$countries = array(
		'US' => __('United States', 'invoicing'),
		'CA' => __('Canada', 'invoicing'),
		'GB' => __('United Kingdom', 'invoicing'),
		'AF' => __('Afghanistan', 'invoicing'),
		'AX' => __('Aland Islands', 'invoicing'),
		'AL' => __('Albania', 'invoicing'),
		'DZ' => __('Algeria', 'invoicing'),
		'AS' => __('American Samoa', 'invoicing'),
		'AD' => __('Andorra', 'invoicing'),
		'AO' => __('Angola', 'invoicing'),
		'AI' => __('Anguilla', 'invoicing'),
		'AQ' => __('Antarctica', 'invoicing'),
		'AG' => __('Antigua and Barbuda', 'invoicing'),
		'AR' => __('Argentina', 'invoicing'),
		'AM' => __('Armenia', 'invoicing'),
		'AW' => __('Aruba', 'invoicing'),
		'AU' => __('Australia', 'invoicing'),
		'AT' => __('Austria', 'invoicing'),
		'AZ' => __('Azerbaijan', 'invoicing'),
		'BS' => __('Bahamas', 'invoicing'),
		'BH' => __('Bahrain', 'invoicing'),
		'BD' => __('Bangladesh', 'invoicing'),
		'BB' => __('Barbados', 'invoicing'),
		'BY' => __('Belarus', 'invoicing'),
		'BE' => __('Belgium', 'invoicing'),
		'BZ' => __('Belize', 'invoicing'),
		'BJ' => __('Benin', 'invoicing'),
		'BM' => __('Bermuda', 'invoicing'),
		'BT' => __('Bhutan', 'invoicing'),
		'BO' => __('Bolivia', 'invoicing'),
		'BQ' => __('Bonaire, Saint Eustatius and Saba', 'invoicing'),
		'BA' => __('Bosnia and Herzegovina', 'invoicing'),
		'BW' => __('Botswana', 'invoicing'),
		'BV' => __('Bouvet Island', 'invoicing'),
		'BR' => __('Brazil', 'invoicing'),
		'IO' => __('British Indian Ocean Territory', 'invoicing'),
		'BN' => __('Brunei Darrussalam', 'invoicing'),
		'BG' => __('Bulgaria', 'invoicing'),
		'BF' => __('Burkina Faso', 'invoicing'),
		'BI' => __('Burundi', 'invoicing'),
		'KH' => __('Cambodia', 'invoicing'),
		'CM' => __('Cameroon', 'invoicing'),
		'CV' => __('Cape Verde', 'invoicing'),
		'KY' => __('Cayman Islands', 'invoicing'),
		'CF' => __('Central African Republic', 'invoicing'),
		'TD' => __('Chad', 'invoicing'),
		'CL' => __('Chile', 'invoicing'),
		'CN' => __('China', 'invoicing'),
		'CX' => __('Christmas Island', 'invoicing'),
		'CC' => __('Cocos Islands', 'invoicing'),
		'CO' => __('Colombia', 'invoicing'),
		'KM' => __('Comoros', 'invoicing'),
		'CD' => __('Congo, Democratic People\'s Republic', 'invoicing'),
		'CG' => __('Congo, Republic of', 'invoicing'),
		'CK' => __('Cook Islands', 'invoicing'),
		'CR' => __('Costa Rica', 'invoicing'),
		'CI' => __('Cote d\'Ivoire', 'invoicing'),
		'HR' => __('Croatia/Hrvatska', 'invoicing'),
		'CU' => __('Cuba', 'invoicing'),
		'CW' => __('Cura&Ccedil;ao', 'invoicing'),
		'CY' => __('Cyprus', 'invoicing'),
		'CZ' => __('Czech Republic', 'invoicing'),
		'DK' => __('Denmark', 'invoicing'),
		'DJ' => __('Djibouti', 'invoicing'),
		'DM' => __('Dominica', 'invoicing'),
		'DO' => __('Dominican Republic', 'invoicing'),
		'TP' => __('East Timor', 'invoicing'),
		'EC' => __('Ecuador', 'invoicing'),
		'EG' => __('Egypt', 'invoicing'),
		'GQ' => __('Equatorial Guinea', 'invoicing'),
		'SV' => __('El Salvador', 'invoicing'),
		'ER' => __('Eritrea', 'invoicing'),
		'EE' => __('Estonia', 'invoicing'),
		'ET' => __('Ethiopia', 'invoicing'),
		'FK' => __('Falkland Islands', 'invoicing'),
		'FO' => __('Faroe Islands', 'invoicing'),
		'FJ' => __('Fiji', 'invoicing'),
		'FI' => __('Finland', 'invoicing'),
		'FR' => __('France', 'invoicing'),
		'GF' => __('French Guiana', 'invoicing'),
		'PF' => __('French Polynesia', 'invoicing'),
		'TF' => __('French Southern Territories', 'invoicing'),
		'GA' => __('Gabon', 'invoicing'),
		'GM' => __('Gambia', 'invoicing'),
		'GE' => __('Georgia', 'invoicing'),
		'DE' => __('Germany', 'invoicing'),
		'GR' => __('Greece', 'invoicing'),
		'GH' => __('Ghana', 'invoicing'),
		'GI' => __('Gibraltar', 'invoicing'),
		'GL' => __('Greenland', 'invoicing'),
		'GD' => __('Grenada', 'invoicing'),
		'GP' => __('Guadeloupe', 'invoicing'),
		'GU' => __('Guam', 'invoicing'),
		'GT' => __('Guatemala', 'invoicing'),
		'GG' => __('Guernsey', 'invoicing'),
		'GN' => __('Guinea', 'invoicing'),
		'GW' => __('Guinea-Bissau', 'invoicing'),
		'GY' => __('Guyana', 'invoicing'),
		'HT' => __('Haiti', 'invoicing'),
		'HM' => __('Heard and McDonald Islands', 'invoicing'),
		'VA' => __('Holy See (City Vatican State)', 'invoicing'),
		'HN' => __('Honduras', 'invoicing'),
		'HK' => __('Hong Kong', 'invoicing'),
		'HU' => __('Hungary', 'invoicing'),
		'IS' => __('Iceland', 'invoicing'),
		'IN' => __('India', 'invoicing'),
		'ID' => __('Indonesia', 'invoicing'),
		'IR' => __('Iran', 'invoicing'),
		'IQ' => __('Iraq', 'invoicing'),
		'IE' => __('Ireland', 'invoicing'),
		'IM' => __('Isle of Man', 'invoicing'),
		'IL' => __('Israel', 'invoicing'),
		'IT' => __('Italy', 'invoicing'),
		'JM' => __('Jamaica', 'invoicing'),
		'JP' => __('Japan', 'invoicing'),
		'JE' => __('Jersey', 'invoicing'),
		'JO' => __('Jordan', 'invoicing'),
		'KZ' => __('Kazakhstan', 'invoicing'),
		'KE' => __('Kenya', 'invoicing'),
		'KI' => __('Kiribati', 'invoicing'),
		'KW' => __('Kuwait', 'invoicing'),
		'KG' => __('Kyrgyzstan', 'invoicing'),
		'LA' => __('Lao People\'s Democratic Republic', 'invoicing'),
		'LV' => __('Latvia', 'invoicing'),
		'LB' => __('Lebanon', 'invoicing'),
		'LS' => __('Lesotho', 'invoicing'),
		'LR' => __('Liberia', 'invoicing'),
		'LY' => __('Libyan Arab Jamahiriya', 'invoicing'),
		'LI' => __('Liechtenstein', 'invoicing'),
		'LT' => __('Lithuania', 'invoicing'),
		'LU' => __('Luxembourg', 'invoicing'),
		'MO' => __('Macau', 'invoicing'),
		'MK' => __('Macedonia', 'invoicing'),
		'MG' => __('Madagascar', 'invoicing'),
		'MW' => __('Malawi', 'invoicing'),
		'MY' => __('Malaysia', 'invoicing'),
		'MV' => __('Maldives', 'invoicing'),
		'ML' => __('Mali', 'invoicing'),
		'MT' => __('Malta', 'invoicing'),
		'MH' => __('Marshall Islands', 'invoicing'),
		'MQ' => __('Martinique', 'invoicing'),
		'MR' => __('Mauritania', 'invoicing'),
		'MU' => __('Mauritius', 'invoicing'),
		'YT' => __('Mayotte', 'invoicing'),
		'MX' => __('Mexico', 'invoicing'),
		'FM' => __('Micronesia', 'invoicing'),
		'MD' => __('Moldova, Republic of', 'invoicing'),
		'MC' => __('Monaco', 'invoicing'),
		'MN' => __('Mongolia', 'invoicing'),
		'ME' => __('Montenegro', 'invoicing'),
		'MS' => __('Montserrat', 'invoicing'),
		'MA' => __('Morocco', 'invoicing'),
		'MZ' => __('Mozambique', 'invoicing'),
		'MM' => __('Myanmar', 'invoicing'),
		'NA' => __('Namibia', 'invoicing'),
		'NR' => __('Nauru', 'invoicing'),
		'NP' => __('Nepal', 'invoicing'),
		'NL' => __('Netherlands', 'invoicing'),
		'AN' => __('Netherlands Antilles', 'invoicing'),
		'NC' => __('New Caledonia', 'invoicing'),
		'NZ' => __('New Zealand', 'invoicing'),
		'NI' => __('Nicaragua', 'invoicing'),
		'NE' => __('Niger', 'invoicing'),
		'NG' => __('Nigeria', 'invoicing'),
		'NU' => __('Niue', 'invoicing'),
		'NF' => __('Norfolk Island', 'invoicing'),
		'KP' => __('North Korea', 'invoicing'),
		'MP' => __('Northern Mariana Islands', 'invoicing'),
		'NO' => __('Norway', 'invoicing'),
		'OM' => __('Oman', 'invoicing'),
		'PK' => __('Pakistan', 'invoicing'),
		'PW' => __('Palau', 'invoicing'),
		'PS' => __('Palestinian Territories', 'invoicing'),
		'PA' => __('Panama', 'invoicing'),
		'PG' => __('Papua New Guinea', 'invoicing'),
		'PY' => __('Paraguay', 'invoicing'),
		'PE' => __('Peru', 'invoicing'),
		'PH' => __('Phillipines', 'invoicing'),
		'PN' => __('Pitcairn Island', 'invoicing'),
		'PL' => __('Poland', 'invoicing'),
		'PT' => __('Portugal', 'invoicing'),
		'PR' => __('Puerto Rico', 'invoicing'),
		'QA' => __('Qatar', 'invoicing'),
		'XK' => __('Republic of Kosovo', 'invoicing'),
		'RE' => __('Reunion Island', 'invoicing'),
		'RO' => __('Romania', 'invoicing'),
		'RU' => __('Russian Federation', 'invoicing'),
		'RW' => __('Rwanda', 'invoicing'),
		'BL' => __('Saint Barth&eacute;lemy', 'invoicing'),
		'SH' => __('Saint Helena', 'invoicing'),
		'KN' => __('Saint Kitts and Nevis', 'invoicing'),
		'LC' => __('Saint Lucia', 'invoicing'),
		'MF' => __('Saint Martin (French)', 'invoicing'),
		'SX' => __('Saint Martin (Dutch)', 'invoicing'),
		'PM' => __('Saint Pierre and Miquelon', 'invoicing'),
		'VC' => __('Saint Vincent and the Grenadines', 'invoicing'),
		'SM' => __('San Marino', 'invoicing'),
		'ST' => __('S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'invoicing'),
		'SA' => __('Saudi Arabia', 'invoicing'),
		'SN' => __('Senegal', 'invoicing'),
		'RS' => __('Serbia', 'invoicing'),
		'SC' => __('Seychelles', 'invoicing'),
		'SL' => __('Sierra Leone', 'invoicing'),
		'SG' => __('Singapore', 'invoicing'),
		'SK' => __('Slovak Republic', 'invoicing'),
		'SI' => __('Slovenia', 'invoicing'),
		'SB' => __('Solomon Islands', 'invoicing'),
		'SO' => __('Somalia', 'invoicing'),
		'ZA' => __('South Africa', 'invoicing'),
		'GS' => __('South Georgia', 'invoicing'),
		'KR' => __('South Korea', 'invoicing'),
		'SS' => __('South Sudan', 'invoicing'),
		'ES' => __('Spain', 'invoicing'),
		'LK' => __('Sri Lanka', 'invoicing'),
		'SD' => __('Sudan', 'invoicing'),
		'SR' => __('Suriname', 'invoicing'),
		'SJ' => __('Svalbard and Jan Mayen Islands', 'invoicing'),
		'SZ' => __('Swaziland', 'invoicing'),
		'SE' => __('Sweden', 'invoicing'),
		'CH' => __('Switzerland', 'invoicing'),
		'SY' => __('Syrian Arab Republic', 'invoicing'),
		'TW' => __('Taiwan', 'invoicing'),
		'TJ' => __('Tajikistan', 'invoicing'),
		'TZ' => __('Tanzania', 'invoicing'),
		'TH' => __('Thailand', 'invoicing'),
		'TL' => __('Timor-Leste', 'invoicing'),
		'TG' => __('Togo', 'invoicing'),
		'TK' => __('Tokelau', 'invoicing'),
		'TO' => __('Tonga', 'invoicing'),
		'TT' => __('Trinidad and Tobago', 'invoicing'),
		'TN' => __('Tunisia', 'invoicing'),
		'TR' => __('Turkey', 'invoicing'),
		'TM' => __('Turkmenistan', 'invoicing'),
		'TC' => __('Turks and Caicos Islands', 'invoicing'),
		'TV' => __('Tuvalu', 'invoicing'),
		'UG' => __('Uganda', 'invoicing'),
		'UA' => __('Ukraine', 'invoicing'),
		'AE' => __('United Arab Emirates', 'invoicing'),
		'UY' => __('Uruguay', 'invoicing'),
		'UM' => __('US Minor Outlying Islands', 'invoicing'),
		'UZ' => __('Uzbekistan', 'invoicing'),
		'VU' => __('Vanuatu', 'invoicing'),
		'VE' => __('Venezuela', 'invoicing'),
		'VN' => __('Vietnam', 'invoicing'),
		'VG' => __('Virgin Islands (British)', 'invoicing'),
		'VI' => __('Virgin Islands (USA)', 'invoicing'),
		'WF' => __('Wallis and Futuna Islands', 'invoicing'),
		'EH' => __('Western Sahara', 'invoicing'),
		'WS' => __('Western Samoa', 'invoicing'),
		'YE' => __('Yemen', 'invoicing'),
		'ZM' => __('Zambia', 'invoicing'),
		'ZW' => __('Zimbabwe', 'invoicing'),
	);
    
    if ( $first_empty ) {
        $countries = array_merge( array( '' => '' ), $countries );
    }
    
    $countries = apply_filters( 'wpinv_countries', $countries );
    
    asort($countries);

    return $countries;
}

function wpinv_get_country_states( $country = null, $first_empty = false ) {
    if ( empty( $country ) ) {
        $country = wpinv_get_default_country();
    }

    switch( $country ) {
        case 'US' :
            $states = wpinv_get_us_states_list();
            break;
        case 'CA' :
            $states = wpinv_get_canada_states_list();
            break;
        case 'AU' :
            $states = wpinv_get_australia_states_list();
            break;
        case 'BD' :
            $states = wpinv_get_bangladesh_states_list();
            break;
        case 'BG' :
            $states = wpinv_get_bulgaria_states_list();
            break;
        case 'BR' :
            $states = wpinv_get_brazil_states_list();
            break;
        case 'CN' :
            $states = wpinv_get_china_states_list();
            break;
        case 'HK' :
            $states = wpinv_get_hong_kong_states_list();
            break;
        case 'HU' :
            $states = wpinv_get_hungary_states_list();
            break;
        case 'ID' :
            $states = wpinv_get_indonesia_states_list();
            break;
        case 'IN' :
            $states = wpinv_get_india_states_list();
            break;
        case 'IR' :
            $states = wpinv_get_iran_states_list();
            break;
        case 'IT' :
            $states = wpinv_get_italy_states_list();
            break;
        case 'JP' :
            $states = wpinv_get_japan_states_list();
            break;
        case 'MX' :
            $states = wpinv_get_mexico_states_list();
            break;
        case 'MY' :
            $states = wpinv_get_malaysia_states_list();
            break;
        case 'NP' :
            $states = wpinv_get_nepal_states_list();
            break;
        case 'NZ' :
            $states = wpinv_get_new_zealand_states_list();
            break;
        case 'PE' :
            $states = wpinv_get_peru_states_list();
            break;
        case 'TH' :
            $states = wpinv_get_thailand_states_list();
            break;
        case 'TR' :
            $states = wpinv_get_turkey_states_list();
            break;
        case 'ZA' :
            $states = wpinv_get_south_africa_states_list();
            break;
        case 'ES' :
            $states = wpinv_get_spain_states_list();
            break;
        default :
            $states = array();
            break;
    }
    
    if ( !empty( $states ) && $first_empty ) {
        $states = array_merge( array( '' => '' ), $states );
    }
    
    $states = apply_filters( 'wpinv_country_states', $states, $country );
    
    asort($states);

    return $states;
}

function wpinv_get_us_states_list() {
    $states = array(
        'AL' => __( 'Alabama', 'invoicing' ),
        'AK' => __( 'Alaska', 'invoicing' ),
        'AZ' => __( 'Arizona', 'invoicing' ),
        'AR' => __( 'Arkansas', 'invoicing' ),
        'CA' => __( 'California', 'invoicing' ),
        'CO' => __( 'Colorado', 'invoicing' ),
        'CT' => __( 'Connecticut', 'invoicing' ),
        'DE' => __( 'Delaware', 'invoicing' ),
        'DC' => __( 'District of Columbia', 'invoicing' ),
        'FL' => __( 'Florida', 'invoicing' ),
        'GA' => __( 'Georgia', 'invoicing' ),
        'HI' => __( 'Hawaii', 'invoicing' ),
        'ID' => __( 'Idaho', 'invoicing' ),
        'IL' => __( 'Illinois', 'invoicing' ),
        'IN' => __( 'Indiana', 'invoicing' ),
        'IA' => __( 'Iowa', 'invoicing' ),
        'KS' => __( 'Kansas', 'invoicing' ),
        'KY' => __( 'Kentucky', 'invoicing' ),
        'LA' => __( 'Louisiana', 'invoicing' ),
        'ME' => __( 'Maine', 'invoicing' ),
        'MD' => __( 'Maryland', 'invoicing' ),
        'MA' => __( 'Massachusetts', 'invoicing' ),
        'MI' => __( 'Michigan', 'invoicing' ),
        'MN' => __( 'Minnesota', 'invoicing' ),
        'MS' => __( 'Mississippi', 'invoicing' ),
        'MO' => __( 'Missouri', 'invoicing' ),
        'MT' => __( 'Montana', 'invoicing' ),
        'NE' => __( 'Nebraska', 'invoicing' ),
        'NV' => __( 'Nevada', 'invoicing' ),
        'NH' => __( 'New Hampshire', 'invoicing' ),
        'NJ' => __( 'New Jersey', 'invoicing' ),
        'NM' => __( 'New Mexico', 'invoicing' ),
        'NY' => __( 'New York', 'invoicing' ),
        'NC' => __( 'North Carolina', 'invoicing' ),
        'ND' => __( 'North Dakota', 'invoicing' ),
        'OH' => __( 'Ohio', 'invoicing' ),
        'OK' => __( 'Oklahoma', 'invoicing' ),
        'OR' => __( 'Oregon', 'invoicing' ),
        'PA' => __( 'Pennsylvania', 'invoicing' ),
        'RI' => __( 'Rhode Island', 'invoicing' ),
        'SC' => __( 'South Carolina', 'invoicing' ),
        'SD' => __( 'South Dakota', 'invoicing' ),
        'TN' => __( 'Tennessee', 'invoicing' ),
        'TX' => __( 'Texas', 'invoicing' ),
        'UT' => __( 'Utah', 'invoicing' ),
        'VT' => __( 'Vermont', 'invoicing' ),
        'VA' => __( 'Virginia', 'invoicing' ),
        'WA' => __( 'Washington', 'invoicing' ),
        'WV' => __( 'West Virginia', 'invoicing' ),
        'WI' => __( 'Wisconsin', 'invoicing' ),
        'WY' => __( 'Wyoming', 'invoicing' ),
        'AS' => __( 'American Samoa', 'invoicing' ),
        'CZ' => __( 'Canal Zone', 'invoicing' ),
        'CM' => __( 'Commonwealth of the Northern Mariana Islands', 'invoicing' ),
        'FM' => __( 'Federated States of Micronesia', 'invoicing' ),
        'GU' => __( 'Guam', 'invoicing' ),
        'MH' => __( 'Marshall Islands', 'invoicing' ),
        'MP' => __( 'Northern Mariana Islands', 'invoicing' ),
        'PW' => __( 'Palau', 'invoicing' ),
        'PI' => __( 'Philippine Islands', 'invoicing' ),
        'PR' => __( 'Puerto Rico', 'invoicing' ),
        'TT' => __( 'Trust Territory of the Pacific Islands', 'invoicing' ),
        'VI' => __( 'Virgin Islands', 'invoicing' ),
        'AA' => __( 'Armed Forces - Americas', 'invoicing' ),
        'AE' => __( 'Armed Forces - Europe, Canada, Middle East, Africa', 'invoicing' ),
        'AP' => __( 'Armed Forces - Pacific', 'invoicing' )
    );

    return apply_filters( 'wpinv_us_states', $states );
}

function wpinv_get_canada_states_list() {
    $states = array(
        'AB' => __( 'Alberta', 'invoicing' ),
        'BC' => __( 'British Columbia', 'invoicing' ),
        'MB' => __( 'Manitoba', 'invoicing' ),
        'NB' => __( 'New Brunswick', 'invoicing' ),
        'NL' => __( 'Newfoundland and Labrador', 'invoicing' ),
        'NS' => __( 'Nova Scotia', 'invoicing' ),
        'NT' => __( 'Northwest Territories', 'invoicing' ),
        'NU' => __( 'Nunavut', 'invoicing' ),
        'ON' => __( 'Ontario', 'invoicing' ),
        'PE' => __( 'Prince Edward Island', 'invoicing' ),
        'QC' => __( 'Quebec', 'invoicing' ),
        'SK' => __( 'Saskatchewan', 'invoicing' ),
        'YT' => __( 'Yukon', 'invoicing' )
    );

    return apply_filters( 'wpinv_canada_provinces', $states );
}

function wpinv_get_australia_states_list() {
    $states = array(
        'ACT' => __( 'Australian Capital Territory', 'invoicing' ),
        'NSW' => __( 'New South Wales', 'invoicing' ),
        'NT'  => __( 'Northern Territory', 'invoicing' ),
        'QLD' => __( 'Queensland', 'invoicing' ),
        'SA'  => __( 'South Australia', 'invoicing' ),
        'TAS' => __( 'Tasmania', 'invoicing' ),
        'VIC' => __( 'Victoria', 'invoicing' ),
        'WA'  => __( 'Western Australia', 'invoicing' )
    );

    return apply_filters( 'wpinv_australia_states', $states );
}

function wpinv_get_bangladesh_states_list() {
    $states = array(
        'BAG' => __( 'Bagerhat', 'invoicing' ),
        'BAN' => __( 'Bandarban', 'invoicing' ),
        'BAR' => __( 'Barguna', 'invoicing' ),
        'BARI'=> __( 'Barisal', 'invoicing' ),
        'BHO' => __( 'Bhola', 'invoicing' ),
        'BOG' => __( 'Bogra', 'invoicing' ),
        'BRA' => __( 'Brahmanbaria', 'invoicing' ),
        'CHA' => __( 'Chandpur', 'invoicing' ),
        'CHI' => __( 'Chittagong', 'invoicing' ),
        'CHU' => __( 'Chuadanga', 'invoicing' ),
        'COM' => __( 'Comilla', 'invoicing' ),
        'COX' => __( 'Cox\'s Bazar', 'invoicing' ),
        'DHA' => __( 'Dhaka', 'invoicing' ),
        'DIN' => __( 'Dinajpur', 'invoicing' ),
        'FAR' => __( 'Faridpur', 'invoicing' ),
        'FEN' => __( 'Feni', 'invoicing' ),
        'GAI' => __( 'Gaibandha', 'invoicing' ),
        'GAZI'=> __( 'Gazipur', 'invoicing' ),
        'GOP' => __( 'Gopalganj', 'invoicing' ),
        'HAB' => __( 'Habiganj', 'invoicing' ),
        'JAM' => __( 'Jamalpur', 'invoicing' ),
        'JES' => __( 'Jessore', 'invoicing' ),
        'JHA' => __( 'Jhalokati', 'invoicing' ),
        'JHE' => __( 'Jhenaidah', 'invoicing' ),
        'JOY' => __( 'Joypurhat', 'invoicing' ),
        'KHA' => __( 'Khagrachhari', 'invoicing' ),
        'KHU' => __( 'Khulna', 'invoicing' ),
        'KIS' => __( 'Kishoreganj', 'invoicing' ),
        'KUR' => __( 'Kurigram', 'invoicing' ),
        'KUS' => __( 'Kushtia', 'invoicing' ),
        'LAK' => __( 'Lakshmipur', 'invoicing' ),
        'LAL' => __( 'Lalmonirhat', 'invoicing' ),
        'MAD' => __( 'Madaripur', 'invoicing' ),
        'MAG' => __( 'Magura', 'invoicing' ),
        'MAN' => __( 'Manikganj', 'invoicing' ),
        'MEH' => __( 'Meherpur', 'invoicing' ),
        'MOU' => __( 'Moulvibazar', 'invoicing' ),
        'MUN' => __( 'Munshiganj', 'invoicing' ),
        'MYM' => __( 'Mymensingh', 'invoicing' ),
        'NAO' => __( 'Naogaon', 'invoicing' ),
        'NAR' => __( 'Narail', 'invoicing' ),
        'NARG'=> __( 'Narayanganj', 'invoicing' ),
        'NARD'=> __( 'Narsingdi', 'invoicing' ),
        'NAT' => __( 'Natore', 'invoicing' ),
        'NAW' => __( 'Nawabganj', 'invoicing' ),
        'NET' => __( 'Netrakona', 'invoicing' ),
        'NIL' => __( 'Nilphamari', 'invoicing' ),
        'NOA' => __( 'Noakhali', 'invoicing' ),
        'PAB' => __( 'Pabna', 'invoicing' ),
        'PAN' => __( 'Panchagarh', 'invoicing' ),
        'PAT' => __( 'Patuakhali', 'invoicing' ),
        'PIR' => __( 'Pirojpur', 'invoicing' ),
        'RAJB'=> __( 'Rajbari', 'invoicing' ),
        'RAJ' => __( 'Rajshahi', 'invoicing' ),
        'RAN' => __( 'Rangamati', 'invoicing' ),
        'RANP'=> __( 'Rangpur', 'invoicing' ),
        'SAT' => __( 'Satkhira', 'invoicing' ),
        'SHA' => __( 'Shariatpur', 'invoicing' ),
        'SHE' => __( 'Sherpur', 'invoicing' ),
        'SIR' => __( 'Sirajganj', 'invoicing' ),
        'SUN' => __( 'Sunamganj', 'invoicing' ),
        'SYL' => __( 'Sylhet', 'invoicing' ),
        'TAN' => __( 'Tangail', 'invoicing' ),
        'THA' => __( 'Thakurgaon', 'invoicing' )
    );

    return apply_filters( 'wpinv_bangladesh_states', $states );
}

function wpinv_get_brazil_states_list() {
    $states = array(
        'AC' => __( 'Acre', 'invoicing' ),
        'AL' => __( 'Alagoas', 'invoicing' ),
        'AP' => __( 'Amap&aacute;', 'invoicing' ),
        'AM' => __( 'Amazonas', 'invoicing' ),
        'BA' => __( 'Bahia', 'invoicing' ),
        'CE' => __( 'Cear&aacute;', 'invoicing' ),
        'DF' => __( 'Distrito Federal', 'invoicing' ),
        'ES' => __( 'Esp&iacute;rito Santo', 'invoicing' ),
        'GO' => __( 'Goi&aacute;s', 'invoicing' ),
        'MA' => __( 'Maranh&atilde;o', 'invoicing' ),
        'MT' => __( 'Mato Grosso', 'invoicing' ),
        'MS' => __( 'Mato Grosso do Sul', 'invoicing' ),
        'MG' => __( 'Minas Gerais', 'invoicing' ),
        'PA' => __( 'Par&aacute;', 'invoicing' ),
        'PB' => __( 'Para&iacute;ba', 'invoicing' ),
        'PR' => __( 'Paran&aacute;', 'invoicing' ),
        'PE' => __( 'Pernambuco', 'invoicing' ),
        'PI' => __( 'Piau&iacute;', 'invoicing' ),
        'RJ' => __( 'Rio de Janeiro', 'invoicing' ),
        'RN' => __( 'Rio Grande do Norte', 'invoicing' ),
        'RS' => __( 'Rio Grande do Sul', 'invoicing' ),
        'RO' => __( 'Rond&ocirc;nia', 'invoicing' ),
        'RR' => __( 'Roraima', 'invoicing' ),
        'SC' => __( 'Santa Catarina', 'invoicing' ),
        'SP' => __( 'S&atilde;o Paulo', 'invoicing' ),
        'SE' => __( 'Sergipe', 'invoicing' ),
        'TO' => __( 'Tocantins', 'invoicing' )
    );

    return apply_filters( 'wpinv_brazil_states', $states );
}

function wpinv_get_bulgaria_states_list() {
    $states = array(
        'BG-01' => __( 'Blagoevgrad', 'invoicing' ),
        'BG-02' => __( 'Burgas', 'invoicing' ),
        'BG-08' => __( 'Dobrich', 'invoicing' ),
        'BG-07' => __( 'Gabrovo', 'invoicing' ),
        'BG-26' => __( 'Haskovo', 'invoicing' ),
        'BG-09' => __( 'Kardzhali', 'invoicing' ),
        'BG-10' => __( 'Kyustendil', 'invoicing' ),
        'BG-11' => __( 'Lovech', 'invoicing' ),
        'BG-12' => __( 'Montana', 'invoicing' ),
        'BG-13' => __( 'Pazardzhik', 'invoicing' ),
        'BG-14' => __( 'Pernik', 'invoicing' ),
        'BG-15' => __( 'Pleven', 'invoicing' ),
        'BG-16' => __( 'Plovdiv', 'invoicing' ),
        'BG-17' => __( 'Razgrad', 'invoicing' ),
        'BG-18' => __( 'Ruse', 'invoicing' ),
        'BG-27' => __( 'Shumen', 'invoicing' ),
        'BG-19' => __( 'Silistra', 'invoicing' ),
        'BG-20' => __( 'Sliven', 'invoicing' ),
        'BG-21' => __( 'Smolyan', 'invoicing' ),
        'BG-23' => __( 'Sofia', 'invoicing' ),
        'BG-22' => __( 'Sofia-Grad', 'invoicing' ),
        'BG-24' => __( 'Stara Zagora', 'invoicing' ),
        'BG-25' => __( 'Targovishte', 'invoicing' ),
        'BG-03' => __( 'Varna', 'invoicing' ),
        'BG-04' => __( 'Veliko Tarnovo', 'invoicing' ),
        'BG-05' => __( 'Vidin', 'invoicing' ),
        'BG-06' => __( 'Vratsa', 'invoicing' ),
        'BG-28' => __( 'Yambol', 'invoicing' )
    );

    return apply_filters( 'wpinv_bulgaria_states', $states );
}

function wpinv_get_hong_kong_states_list() {
    $states = array(
        'HONG KONG'       => __( 'Hong Kong Island', 'invoicing' ),
        'KOWLOON'         => __( 'Kowloon', 'invoicing' ),
        'NEW TERRITORIES' => __( 'New Territories', 'invoicing' )
    );

    return apply_filters( 'wpinv_hong_kong_states', $states );
}

function wpinv_get_hungary_states_list() {
    $states = array(
        'BK' => __( 'Bács-Kiskun', 'invoicing' ),
        'BE' => __( 'Békés', 'invoicing' ),
        'BA' => __( 'Baranya', 'invoicing' ),
        'BZ' => __( 'Borsod-Abaúj-Zemplén', 'invoicing' ),
        'BU' => __( 'Budapest', 'invoicing' ),
        'CS' => __( 'Csongrád', 'invoicing' ),
        'FE' => __( 'Fejér', 'invoicing' ),
        'GS' => __( 'Győr-Moson-Sopron', 'invoicing' ),
        'HB' => __( 'Hajdú-Bihar', 'invoicing' ),
        'HE' => __( 'Heves', 'invoicing' ),
        'JN' => __( 'Jász-Nagykun-Szolnok', 'invoicing' ),
        'KE' => __( 'Komárom-Esztergom', 'invoicing' ),
        'NO' => __( 'Nógrád', 'invoicing' ),
        'PE' => __( 'Pest', 'invoicing' ),
        'SO' => __( 'Somogy', 'invoicing' ),
        'SZ' => __( 'Szabolcs-Szatmár-Bereg', 'invoicing' ),
        'TO' => __( 'Tolna', 'invoicing' ),
        'VA' => __( 'Vas', 'invoicing' ),
        'VE' => __( 'Veszprém', 'invoicing' ),
        'ZA' => __( 'Zala', 'invoicing' )
    );

    return apply_filters( 'wpinv_hungary_states', $states );
}

function wpinv_get_japan_states_list() {
    $states = array(
        'JP01' => __( 'Hokkaido', 'invoicing' ),
        'JP02' => __( 'Aomori', 'invoicing' ),
        'JP03' => __( 'Iwate', 'invoicing' ),
        'JP04' => __( 'Miyagi', 'invoicing' ),
        'JP05' => __( 'Akita', 'invoicing' ),
        'JP06' => __( 'Yamagata', 'invoicing' ),
        'JP07' => __( 'Fukushima', 'invoicing' ),
        'JP08' => __( 'Ibaraki', 'invoicing' ),
        'JP09' => __( 'Tochigi', 'invoicing' ),
        'JP10' => __( 'Gunma', 'invoicing' ),
        'JP11' => __( 'Saitama', 'invoicing' ),
        'JP12' => __( 'Chiba', 'invoicing' ),
        'JP13' => __( 'Tokyo', 'invoicing' ),
        'JP14' => __( 'Kanagawa', 'invoicing' ),
        'JP15' => __( 'Niigata', 'invoicing' ),
        'JP16' => __( 'Toyama', 'invoicing' ),
        'JP17' => __( 'Ishikawa', 'invoicing' ),
        'JP18' => __( 'Fukui', 'invoicing' ),
        'JP19' => __( 'Yamanashi', 'invoicing' ),
        'JP20' => __( 'Nagano', 'invoicing' ),
        'JP21' => __( 'Gifu', 'invoicing' ),
        'JP22' => __( 'Shizuoka', 'invoicing' ),
        'JP23' => __( 'Aichi', 'invoicing' ),
        'JP24' => __( 'Mie', 'invoicing' ),
        'JP25' => __( 'Shiga', 'invoicing' ),
        'JP26' => __( 'Kyouto', 'invoicing' ),
        'JP27' => __( 'Osaka', 'invoicing' ),
        'JP28' => __( 'Hyougo', 'invoicing' ),
        'JP29' => __( 'Nara', 'invoicing' ),
        'JP30' => __( 'Wakayama', 'invoicing' ),
        'JP31' => __( 'Tottori', 'invoicing' ),
        'JP32' => __( 'Shimane', 'invoicing' ),
        'JP33' => __( 'Okayama', 'invoicing' ),
        'JP34' => __( 'Hiroshima', 'invoicing' ),
        'JP35' => __( 'Yamaguchi', 'invoicing' ),
        'JP36' => __( 'Tokushima', 'invoicing' ),
        'JP37' => __( 'Kagawa', 'invoicing' ),
        'JP38' => __( 'Ehime', 'invoicing' ),
        'JP39' => __( 'Kochi', 'invoicing' ),
        'JP40' => __( 'Fukuoka', 'invoicing' ),
        'JP41' => __( 'Saga', 'invoicing' ),
        'JP42' => __( 'Nagasaki', 'invoicing' ),
        'JP43' => __( 'Kumamoto', 'invoicing' ),
        'JP44' => __( 'Oita', 'invoicing' ),
        'JP45' => __( 'Miyazaki', 'invoicing' ),
        'JP46' => __( 'Kagoshima', 'invoicing' ),
        'JP47' => __( 'Okinawa', 'invoicing' )
    );

    return apply_filters( 'wpinv_japan_states', $states );
}

function wpinv_get_china_states_list() {
    $states = array(
        'CN1'  => __( 'Yunnan / &#20113;&#21335;', 'invoicing' ),
        'CN2'  => __( 'Beijing / &#21271;&#20140;', 'invoicing' ),
        'CN3'  => __( 'Tianjin / &#22825;&#27941;', 'invoicing' ),
        'CN4'  => __( 'Hebei / &#27827;&#21271;', 'invoicing' ),
        'CN5'  => __( 'Shanxi / &#23665;&#35199;', 'invoicing' ),
        'CN6'  => __( 'Inner Mongolia / &#20839;&#33945;&#21476;', 'invoicing' ),
        'CN7'  => __( 'Liaoning / &#36797;&#23425;', 'invoicing' ),
        'CN8'  => __( 'Jilin / &#21513;&#26519;', 'invoicing' ),
        'CN9'  => __( 'Heilongjiang / &#40657;&#40857;&#27743;', 'invoicing' ),
        'CN10' => __( 'Shanghai / &#19978;&#28023;', 'invoicing' ),
        'CN11' => __( 'Jiangsu / &#27743;&#33487;', 'invoicing' ),
        'CN12' => __( 'Zhejiang / &#27993;&#27743;', 'invoicing' ),
        'CN13' => __( 'Anhui / &#23433;&#24509;', 'invoicing' ),
        'CN14' => __( 'Fujian / &#31119;&#24314;', 'invoicing' ),
        'CN15' => __( 'Jiangxi / &#27743;&#35199;', 'invoicing' ),
        'CN16' => __( 'Shandong / &#23665;&#19996;', 'invoicing' ),
        'CN17' => __( 'Henan / &#27827;&#21335;', 'invoicing' ),
        'CN18' => __( 'Hubei / &#28246;&#21271;', 'invoicing' ),
        'CN19' => __( 'Hunan / &#28246;&#21335;', 'invoicing' ),
        'CN20' => __( 'Guangdong / &#24191;&#19996;', 'invoicing' ),
        'CN21' => __( 'Guangxi Zhuang / &#24191;&#35199;&#22766;&#26063;', 'invoicing' ),
        'CN22' => __( 'Hainan / &#28023;&#21335;', 'invoicing' ),
        'CN23' => __( 'Chongqing / &#37325;&#24198;', 'invoicing' ),
        'CN24' => __( 'Sichuan / &#22235;&#24029;', 'invoicing' ),
        'CN25' => __( 'Guizhou / &#36149;&#24030;', 'invoicing' ),
        'CN26' => __( 'Shaanxi / &#38485;&#35199;', 'invoicing' ),
        'CN27' => __( 'Gansu / &#29976;&#32899;', 'invoicing' ),
        'CN28' => __( 'Qinghai / &#38738;&#28023;', 'invoicing' ),
        'CN29' => __( 'Ningxia Hui / &#23425;&#22799;', 'invoicing' ),
        'CN30' => __( 'Macau / &#28595;&#38376;', 'invoicing' ),
        'CN31' => __( 'Tibet / &#35199;&#34255;', 'invoicing' ),
        'CN32' => __( 'Xinjiang / &#26032;&#30086;', 'invoicing' )
    );

    return apply_filters( 'wpinv_china_states', $states );
}

function wpinv_get_new_zealand_states_list() {
    $states = array(
        'AK' => __( 'Auckland', 'invoicing' ),
        'BP' => __( 'Bay of Plenty', 'invoicing' ),
        'CT' => __( 'Canterbury', 'invoicing' ),
        'HB' => __( 'Hawke&rsquo;s Bay', 'invoicing' ),
        'MW' => __( 'Manawatu-Wanganui', 'invoicing' ),
        'MB' => __( 'Marlborough', 'invoicing' ),
        'NS' => __( 'Nelson', 'invoicing' ),
        'NL' => __( 'Northland', 'invoicing' ),
        'OT' => __( 'Otago', 'invoicing' ),
        'SL' => __( 'Southland', 'invoicing' ),
        'TK' => __( 'Taranaki', 'invoicing' ),
        'TM' => __( 'Tasman', 'invoicing' ),
        'WA' => __( 'Waikato', 'invoicing' ),
        'WR' => __( 'Wairarapa', 'invoicing' ),
        'WE' => __( 'Wellington', 'invoicing' ),
        'WC' => __( 'West Coast', 'invoicing' )
    );

    return apply_filters( 'wpinv_new_zealand_states', $states );
}

function wpinv_get_peru_states_list() {
    $states = array(
        'CAL' => __( 'El Callao', 'invoicing' ),
        'LMA' => __( 'Municipalidad Metropolitana de Lima', 'invoicing' ),
        'AMA' => __( 'Amazonas', 'invoicing' ),
        'ANC' => __( 'Ancash', 'invoicing' ),
        'APU' => __( 'Apur&iacute;mac', 'invoicing' ),
        'ARE' => __( 'Arequipa', 'invoicing' ),
        'AYA' => __( 'Ayacucho', 'invoicing' ),
        'CAJ' => __( 'Cajamarca', 'invoicing' ),
        'CUS' => __( 'Cusco', 'invoicing' ),
        'HUV' => __( 'Huancavelica', 'invoicing' ),
        'HUC' => __( 'Hu&aacute;nuco', 'invoicing' ),
        'ICA' => __( 'Ica', 'invoicing' ),
        'JUN' => __( 'Jun&iacute;n', 'invoicing' ),
        'LAL' => __( 'La Libertad', 'invoicing' ),
        'LAM' => __( 'Lambayeque', 'invoicing' ),
        'LIM' => __( 'Lima', 'invoicing' ),
        'LOR' => __( 'Loreto', 'invoicing' ),
        'MDD' => __( 'Madre de Dios', 'invoicing' ),
        'MOQ' => __( 'Moquegua', 'invoicing' ),
        'PAS' => __( 'Pasco', 'invoicing' ),
        'PIU' => __( 'Piura', 'invoicing' ),
        'PUN' => __( 'Puno', 'invoicing' ),
        'SAM' => __( 'San Mart&iacute;n', 'invoicing' ),
        'TAC' => __( 'Tacna', 'invoicing' ),
        'TUM' => __( 'Tumbes', 'invoicing' ),
        'UCA' => __( 'Ucayali', 'invoicing' )
    );

    return apply_filters( 'wpinv_peru_states', $states );
}

function wpinv_get_indonesia_states_list() {
    $states  = array(
        'AC' => __( 'Daerah Istimewa Aceh', 'invoicing' ),
        'SU' => __( 'Sumatera Utara', 'invoicing' ),
        'SB' => __( 'Sumatera Barat', 'invoicing' ),
        'RI' => __( 'Riau', 'invoicing' ),
        'KR' => __( 'Kepulauan Riau', 'invoicing' ),
        'JA' => __( 'Jambi', 'invoicing' ),
        'SS' => __( 'Sumatera Selatan', 'invoicing' ),
        'BB' => __( 'Bangka Belitung', 'invoicing' ),
        'BE' => __( 'Bengkulu', 'invoicing' ),
        'LA' => __( 'Lampung', 'invoicing' ),
        'JK' => __( 'DKI Jakarta', 'invoicing' ),
        'JB' => __( 'Jawa Barat', 'invoicing' ),
        'BT' => __( 'Banten', 'invoicing' ),
        'JT' => __( 'Jawa Tengah', 'invoicing' ),
        'JI' => __( 'Jawa Timur', 'invoicing' ),
        'YO' => __( 'Daerah Istimewa Yogyakarta', 'invoicing' ),
        'BA' => __( 'Bali', 'invoicing' ),
        'NB' => __( 'Nusa Tenggara Barat', 'invoicing' ),
        'NT' => __( 'Nusa Tenggara Timur', 'invoicing' ),
        'KB' => __( 'Kalimantan Barat', 'invoicing' ),
        'KT' => __( 'Kalimantan Tengah', 'invoicing' ),
        'KI' => __( 'Kalimantan Timur', 'invoicing' ),
        'KS' => __( 'Kalimantan Selatan', 'invoicing' ),
        'KU' => __( 'Kalimantan Utara', 'invoicing' ),
        'SA' => __( 'Sulawesi Utara', 'invoicing' ),
        'ST' => __( 'Sulawesi Tengah', 'invoicing' ),
        'SG' => __( 'Sulawesi Tenggara', 'invoicing' ),
        'SR' => __( 'Sulawesi Barat', 'invoicing' ),
        'SN' => __( 'Sulawesi Selatan', 'invoicing' ),
        'GO' => __( 'Gorontalo', 'invoicing' ),
        'MA' => __( 'Maluku', 'invoicing' ),
        'MU' => __( 'Maluku Utara', 'invoicing' ),
        'PA' => __( 'Papua', 'invoicing' ),
        'PB' => __( 'Papua Barat', 'invoicing' )
    );

    return apply_filters( 'wpinv_indonesia_states', $states );
}

function wpinv_get_india_states_list() {
    $states = array(
        'AP' => __( 'Andhra Pradesh', 'invoicing' ),
        'AR' => __( 'Arunachal Pradesh', 'invoicing' ),
        'AS' => __( 'Assam', 'invoicing' ),
        'BR' => __( 'Bihar', 'invoicing' ),
        'CT' => __( 'Chhattisgarh', 'invoicing' ),
        'GA' => __( 'Goa', 'invoicing' ),
        'GJ' => __( 'Gujarat', 'invoicing' ),
        'HR' => __( 'Haryana', 'invoicing' ),
        'HP' => __( 'Himachal Pradesh', 'invoicing' ),
        'JK' => __( 'Jammu and Kashmir', 'invoicing' ),
        'JH' => __( 'Jharkhand', 'invoicing' ),
        'KA' => __( 'Karnataka', 'invoicing' ),
        'KL' => __( 'Kerala', 'invoicing' ),
        'MP' => __( 'Madhya Pradesh', 'invoicing' ),
        'MH' => __( 'Maharashtra', 'invoicing' ),
        'MN' => __( 'Manipur', 'invoicing' ),
        'ML' => __( 'Meghalaya', 'invoicing' ),
        'MZ' => __( 'Mizoram', 'invoicing' ),
        'NL' => __( 'Nagaland', 'invoicing' ),
        'OR' => __( 'Orissa', 'invoicing' ),
        'PB' => __( 'Punjab', 'invoicing' ),
        'RJ' => __( 'Rajasthan', 'invoicing' ),
        'SK' => __( 'Sikkim', 'invoicing' ),
        'TN' => __( 'Tamil Nadu', 'invoicing' ),
        'TG' => __( 'Telangana', 'invoicing' ),
        'TR' => __( 'Tripura', 'invoicing' ),
        'UT' => __( 'Uttarakhand', 'invoicing' ),
        'UP' => __( 'Uttar Pradesh', 'invoicing' ),
        'WB' => __( 'West Bengal', 'invoicing' ),
        'AN' => __( 'Andaman and Nicobar Islands', 'invoicing' ),
        'CH' => __( 'Chandigarh', 'invoicing' ),
        'DN' => __( 'Dadar and Nagar Haveli', 'invoicing' ),
        'DD' => __( 'Daman and Diu', 'invoicing' ),
        'DL' => __( 'Delhi', 'invoicing' ),
        'LD' => __( 'Lakshadweep', 'invoicing' ),
        'PY' => __( 'Pondicherry (Puducherry)', 'invoicing' )
    );

    return apply_filters( 'wpinv_india_states', $states );
}

function wpinv_get_iran_states_list() {
    $states = array(
        'KHZ' => __( 'Khuzestan', 'invoicing' ),
        'THR' => __( 'Tehran', 'invoicing' ),
        'ILM' => __( 'Ilaam', 'invoicing' ),
        'BHR' => __( 'Bushehr', 'invoicing' ),
        'ADL' => __( 'Ardabil', 'invoicing' ),
        'ESF' => __( 'Isfahan', 'invoicing' ),
        'YZD' => __( 'Yazd', 'invoicing' ),
        'KRH' => __( 'Kermanshah', 'invoicing' ),
        'KRN' => __( 'Kerman', 'invoicing' ),
        'HDN' => __( 'Hamadan', 'invoicing' ),
        'GZN' => __( 'Ghazvin', 'invoicing' ),
        'ZJN' => __( 'Zanjan', 'invoicing' ),
        'LRS' => __( 'Luristan', 'invoicing' ),
        'ABZ' => __( 'Alborz', 'invoicing' ),
        'EAZ' => __( 'East Azerbaijan', 'invoicing' ),
        'WAZ' => __( 'West Azerbaijan', 'invoicing' ),
        'CHB' => __( 'Chaharmahal and Bakhtiari', 'invoicing' ),
        'SKH' => __( 'South Khorasan', 'invoicing' ),
        'RKH' => __( 'Razavi Khorasan', 'invoicing' ),
        'NKH' => __( 'North Khorasan', 'invoicing' ),
        'SMN' => __( 'Semnan', 'invoicing' ),
        'FRS' => __( 'Fars', 'invoicing' ),
        'QHM' => __( 'Qom', 'invoicing' ),
        'KRD' => __( 'Kurdistan', 'invoicing' ),
        'KBD' => __( 'Kohgiluyeh and BoyerAhmad', 'invoicing' ),
        'GLS' => __( 'Golestan', 'invoicing' ),
        'GIL' => __( 'Gilan', 'invoicing' ),
        'MZN' => __( 'Mazandaran', 'invoicing' ),
        'MKZ' => __( 'Markazi', 'invoicing' ),
        'HRZ' => __( 'Hormozgan', 'invoicing' ),
        'SBN' => __( 'Sistan and Baluchestan', 'invoicing' )
    );

    return apply_filters( 'wpinv_iran_states', $states );
}

function wpinv_get_italy_states_list() {
    $states = array(
        'AG' => __( 'Agrigento', 'invoicing' ),
        'AL' => __( 'Alessandria', 'invoicing' ),
        'AN' => __( 'Ancona', 'invoicing' ),
        'AO' => __( 'Aosta', 'invoicing' ),
        'AR' => __( 'Arezzo', 'invoicing' ),
        'AP' => __( 'Ascoli Piceno', 'invoicing' ),
        'AT' => __( 'Asti', 'invoicing' ),
        'AV' => __( 'Avellino', 'invoicing' ),
        'BA' => __( 'Bari', 'invoicing' ),
        'BT' => __( 'Barletta-Andria-Trani', 'invoicing' ),
        'BL' => __( 'Belluno', 'invoicing' ),
        'BN' => __( 'Benevento', 'invoicing' ),
        'BG' => __( 'Bergamo', 'invoicing' ),
        'BI' => __( 'Biella', 'invoicing' ),
        'BO' => __( 'Bologna', 'invoicing' ),
        'BZ' => __( 'Bolzano', 'invoicing' ),
        'BS' => __( 'Brescia', 'invoicing' ),
        'BR' => __( 'Brindisi', 'invoicing' ),
        'CA' => __( 'Cagliari', 'invoicing' ),
        'CL' => __( 'Caltanissetta', 'invoicing' ),
        'CB' => __( 'Campobasso', 'invoicing' ),
        'CI' => __( 'Caltanissetta', 'invoicing' ),
        'CE' => __( 'Caserta', 'invoicing' ),
        'CT' => __( 'Catania', 'invoicing' ),
        'CZ' => __( 'Catanzaro', 'invoicing' ),
        'CH' => __( 'Chieti', 'invoicing' ),
        'CO' => __( 'Como', 'invoicing' ),
        'CS' => __( 'Cosenza', 'invoicing' ),
        'CR' => __( 'Cremona', 'invoicing' ),
        'KR' => __( 'Crotone', 'invoicing' ),
        'CN' => __( 'Cuneo', 'invoicing' ),
        'EN' => __( 'Enna', 'invoicing' ),
        'FM' => __( 'Fermo', 'invoicing' ),
        'FE' => __( 'Ferrara', 'invoicing' ),
        'FI' => __( 'Firenze', 'invoicing' ),
        'FG' => __( 'Foggia', 'invoicing' ),
        'FC' => __( 'Forli-Cesena', 'invoicing' ),
        'FR' => __( 'Frosinone', 'invoicing' ),
        'GE' => __( 'Genova', 'invoicing' ),
        'GO' => __( 'Gorizia', 'invoicing' ),
        'GR' => __( 'Grosseto', 'invoicing' ),
        'IM' => __( 'Imperia', 'invoicing' ),
        'IS' => __( 'Isernia', 'invoicing' ),
        'SP' => __( 'La Spezia', 'invoicing' ),
        'AQ' => __( 'L&apos;Aquila', 'invoicing' ),
        'LT' => __( 'Latina', 'invoicing' ),
        'LE' => __( 'Lecce', 'invoicing' ),
        'LC' => __( 'Lecco', 'invoicing' ),
        'LI' => __( 'Livorno', 'invoicing' ),
        'LO' => __( 'Lodi', 'invoicing' ),
        'LU' => __( 'Lucca', 'invoicing' ),
        'MC' => __( 'Macerata', 'invoicing' ),
        'MN' => __( 'Mantova', 'invoicing' ),
        'MS' => __( 'Massa-Carrara', 'invoicing' ),
        'MT' => __( 'Matera', 'invoicing' ),
        'ME' => __( 'Messina', 'invoicing' ),
        'MI' => __( 'Milano', 'invoicing' ),
        'MO' => __( 'Modena', 'invoicing' ),
        'MB' => __( 'Monza e della Brianza', 'invoicing' ),
        'NA' => __( 'Napoli', 'invoicing' ),
        'NO' => __( 'Novara', 'invoicing' ),
        'NU' => __( 'Nuoro', 'invoicing' ),
        'OT' => __( 'Olbia-Tempio', 'invoicing' ),
        'OR' => __( 'Oristano', 'invoicing' ),
        'PD' => __( 'Padova', 'invoicing' ),
        'PA' => __( 'Palermo', 'invoicing' ),
        'PR' => __( 'Parma', 'invoicing' ),
        'PV' => __( 'Pavia', 'invoicing' ),
        'PG' => __( 'Perugia', 'invoicing' ),
        'PU' => __( 'Pesaro e Urbino', 'invoicing' ),
        'PE' => __( 'Pescara', 'invoicing' ),
        'PC' => __( 'Piacenza', 'invoicing' ),
        'PI' => __( 'Pisa', 'invoicing' ),
        'PT' => __( 'Pistoia', 'invoicing' ),
        'PN' => __( 'Pordenone', 'invoicing' ),
        'PZ' => __( 'Potenza', 'invoicing' ),
        'PO' => __( 'Prato', 'invoicing' ),
        'RG' => __( 'Ragusa', 'invoicing' ),
        'RA' => __( 'Ravenna', 'invoicing' ),
        'RC' => __( 'Reggio Calabria', 'invoicing' ),
        'RE' => __( 'Reggio Emilia', 'invoicing' ),
        'RI' => __( 'Rieti', 'invoicing' ),
        'RN' => __( 'Rimini', 'invoicing' ),
        'RM' => __( 'Roma', 'invoicing' ),
        'RO' => __( 'Rovigo', 'invoicing' ),
        'SA' => __( 'Salerno', 'invoicing' ),
        'VS' => __( 'Medio Campidano', 'invoicing' ),
        'SS' => __( 'Sassari', 'invoicing' ),
        'SV' => __( 'Savona', 'invoicing' ),
        'SI' => __( 'Siena', 'invoicing' ),
        'SR' => __( 'Siracusa', 'invoicing' ),
        'SO' => __( 'Sondrio', 'invoicing' ),
        'TA' => __( 'Taranto', 'invoicing' ),
        'TE' => __( 'Teramo', 'invoicing' ),
        'TR' => __( 'Terni', 'invoicing' ),
        'TO' => __( 'Torino', 'invoicing' ),
        'OG' => __( 'Ogliastra', 'invoicing' ),
        'TP' => __( 'Trapani', 'invoicing' ),
        'TN' => __( 'Trento', 'invoicing' ),
        'TV' => __( 'Treviso', 'invoicing' ),
        'TS' => __( 'Trieste', 'invoicing' ),
        'UD' => __( 'Udine', 'invoicing' ),
        'VA' => __( 'Varesa', 'invoicing' ),
        'VE' => __( 'Venezia', 'invoicing' ),
        'VB' => __( 'Verbano-Cusio-Ossola', 'invoicing' ),
        'VC' => __( 'Vercelli', 'invoicing' ),
        'VR' => __( 'Verona', 'invoicing' ),
        'VV' => __( 'Vibo Valentia', 'invoicing' ),
        'VI' => __( 'Vicenza', 'invoicing' ),
        'VT' => __( 'Viterbo', 'invoicing' )
    );

    return apply_filters( 'wpinv_italy_states', $states );
}

function wpinv_get_malaysia_states_list() {
    $states = array(
        'JHR' => __( 'Johor', 'invoicing' ),
        'KDH' => __( 'Kedah', 'invoicing' ),
        'KTN' => __( 'Kelantan', 'invoicing' ),
        'MLK' => __( 'Melaka', 'invoicing' ),
        'NSN' => __( 'Negeri Sembilan', 'invoicing' ),
        'PHG' => __( 'Pahang', 'invoicing' ),
        'PRK' => __( 'Perak', 'invoicing' ),
        'PLS' => __( 'Perlis', 'invoicing' ),
        'PNG' => __( 'Pulau Pinang', 'invoicing' ),
        'SBH' => __( 'Sabah', 'invoicing' ),
        'SWK' => __( 'Sarawak', 'invoicing' ),
        'SGR' => __( 'Selangor', 'invoicing' ),
        'TRG' => __( 'Terengganu', 'invoicing' ),
        'KUL' => __( 'W.P. Kuala Lumpur', 'invoicing' ),
        'LBN' => __( 'W.P. Labuan', 'invoicing' ),
        'PJY' => __( 'W.P. Putrajaya', 'invoicing' )
    );

    return apply_filters( 'wpinv_malaysia_states', $states );
}

function wpinv_get_mexico_states_list() {
    $states = array(
        'DIF' => __( 'Distrito Federal', 'invoicing' ),
        'JAL' => __( 'Jalisco', 'invoicing' ),
        'NLE' => __( 'Nuevo Le&oacute;n', 'invoicing' ),
        'AGU' => __( 'Aguascalientes', 'invoicing' ),
        'BCN' => __( 'Baja California Norte', 'invoicing' ),
        'BCS' => __( 'Baja California Sur', 'invoicing' ),
        'CAM' => __( 'Campeche', 'invoicing' ),
        'CHP' => __( 'Chiapas', 'invoicing' ),
        'CHH' => __( 'Chihuahua', 'invoicing' ),
        'COA' => __( 'Coahuila', 'invoicing' ),
        'COL' => __( 'Colima', 'invoicing' ),
        'DUR' => __( 'Durango', 'invoicing' ),
        'GUA' => __( 'Guanajuato', 'invoicing' ),
        'GRO' => __( 'Guerrero', 'invoicing' ),
        'HID' => __( 'Hidalgo', 'invoicing' ),
        'MEX' => __( 'Edo. de M&eacute;xico', 'invoicing' ),
        'MIC' => __( 'Michoac&aacute;n', 'invoicing' ),
        'MOR' => __( 'Morelos', 'invoicing' ),
        'NAY' => __( 'Nayarit', 'invoicing' ),
        'OAX' => __( 'Oaxaca', 'invoicing' ),
        'PUE' => __( 'Puebla', 'invoicing' ),
        'QUE' => __( 'Quer&eacute;taro', 'invoicing' ),
        'ROO' => __( 'Quintana Roo', 'invoicing' ),
        'SLP' => __( 'San Luis Potos&iacute;', 'invoicing' ),
        'SIN' => __( 'Sinaloa', 'invoicing' ),
        'SON' => __( 'Sonora', 'invoicing' ),
        'TAB' => __( 'Tabasco', 'invoicing' ),
        'TAM' => __( 'Tamaulipas', 'invoicing' ),
        'TLA' => __( 'Tlaxcala', 'invoicing' ),
        'VER' => __( 'Veracruz', 'invoicing' ),
        'YUC' => __( 'Yucat&aacute;n', 'invoicing' ),
        'ZAC' => __( 'Zacatecas', 'invoicing' )
    );

    return apply_filters( 'wpinv_mexico_states', $states );
}

function wpinv_get_nepal_states_list() {
    $states = array(
        'ILL' => __( 'Illam', 'invoicing' ),
        'JHA' => __( 'Jhapa', 'invoicing' ),
        'PAN' => __( 'Panchthar', 'invoicing' ),
        'TAP' => __( 'Taplejung', 'invoicing' ),
        'BHO' => __( 'Bhojpur', 'invoicing' ),
        'DKA' => __( 'Dhankuta', 'invoicing' ),
        'MOR' => __( 'Morang', 'invoicing' ),
        'SUN' => __( 'Sunsari', 'invoicing' ),
        'SAN' => __( 'Sankhuwa', 'invoicing' ),
        'TER' => __( 'Terhathum', 'invoicing' ),
        'KHO' => __( 'Khotang', 'invoicing' ),
        'OKH' => __( 'Okhaldhunga', 'invoicing' ),
        'SAP' => __( 'Saptari', 'invoicing' ),
        'SIR' => __( 'Siraha', 'invoicing' ),
        'SOL' => __( 'Solukhumbu', 'invoicing' ),
        'UDA' => __( 'Udayapur', 'invoicing' ),
        'DHA' => __( 'Dhanusa', 'invoicing' ),
        'DLK' => __( 'Dolakha', 'invoicing' ),
        'MOH' => __( 'Mohottari', 'invoicing' ),
        'RAM' => __( 'Ramechha', 'invoicing' ),
        'SAR' => __( 'Sarlahi', 'invoicing' ),
        'SIN' => __( 'Sindhuli', 'invoicing' ),
        'BHA' => __( 'Bhaktapur', 'invoicing' ),
        'DHD' => __( 'Dhading', 'invoicing' ),
        'KTM' => __( 'Kathmandu', 'invoicing' ),
        'KAV' => __( 'Kavrepalanchowk', 'invoicing' ),
        'LAL' => __( 'Lalitpur', 'invoicing' ),
        'NUW' => __( 'Nuwakot', 'invoicing' ),
        'RAS' => __( 'Rasuwa', 'invoicing' ),
        'SPC' => __( 'Sindhupalchowk', 'invoicing' ),
        'BAR' => __( 'Bara', 'invoicing' ),
        'CHI' => __( 'Chitwan', 'invoicing' ),
        'MAK' => __( 'Makwanpur', 'invoicing' ),
        'PAR' => __( 'Parsa', 'invoicing' ),
        'RAU' => __( 'Rautahat', 'invoicing' ),
        'GOR' => __( 'Gorkha', 'invoicing' ),
        'KAS' => __( 'Kaski', 'invoicing' ),
        'LAM' => __( 'Lamjung', 'invoicing' ),
        'MAN' => __( 'Manang', 'invoicing' ),
        'SYN' => __( 'Syangja', 'invoicing' ),
        'TAN' => __( 'Tanahun', 'invoicing' ),
        'BAG' => __( 'Baglung', 'invoicing' ),
        'PBT' => __( 'Parbat', 'invoicing' ),
        'MUS' => __( 'Mustang', 'invoicing' ),
        'MYG' => __( 'Myagdi', 'invoicing' ),
        'AGR' => __( 'Agrghakanchi', 'invoicing' ),
        'GUL' => __( 'Gulmi', 'invoicing' ),
        'KAP' => __( 'Kapilbastu', 'invoicing' ),
        'NAW' => __( 'Nawalparasi', 'invoicing' ),
        'PAL' => __( 'Palpa', 'invoicing' ),
        'RUP' => __( 'Rupandehi', 'invoicing' ),
        'DAN' => __( 'Dang', 'invoicing' ),
        'PYU' => __( 'Pyuthan', 'invoicing' ),
        'ROL' => __( 'Rolpa', 'invoicing' ),
        'RUK' => __( 'Rukum', 'invoicing' ),
        'SAL' => __( 'Salyan', 'invoicing' ),
        'BAN' => __( 'Banke', 'invoicing' ),
        'BDA' => __( 'Bardiya', 'invoicing' ),
        'DAI' => __( 'Dailekh', 'invoicing' ),
        'JAJ' => __( 'Jajarkot', 'invoicing' ),
        'SUR' => __( 'Surkhet', 'invoicing' ),
        'DOL' => __( 'Dolpa', 'invoicing' ),
        'HUM' => __( 'Humla', 'invoicing' ),
        'JUM' => __( 'Jumla', 'invoicing' ),
        'KAL' => __( 'Kalikot', 'invoicing' ),
        'MUG' => __( 'Mugu', 'invoicing' ),
        'ACH' => __( 'Achham', 'invoicing' ),
        'BJH' => __( 'Bajhang', 'invoicing' ),
        'BJU' => __( 'Bajura', 'invoicing' ),
        'DOT' => __( 'Doti', 'invoicing' ),
        'KAI' => __( 'Kailali', 'invoicing' ),
        'BAI' => __( 'Baitadi', 'invoicing' ),
        'DAD' => __( 'Dadeldhura', 'invoicing' ),
        'DAR' => __( 'Darchula', 'invoicing' ),
        'KAN' => __( 'Kanchanpur', 'invoicing' )
    );

    return apply_filters( 'wpinv_nepal_states', $states );
}

function wpinv_get_south_africa_states_list() {
    $states = array(
        'EC'  => __( 'Eastern Cape', 'invoicing' ),
        'FS'  => __( 'Free State', 'invoicing' ),
        'GP'  => __( 'Gauteng', 'invoicing' ),
        'KZN' => __( 'KwaZulu-Natal', 'invoicing' ),
        'LP'  => __( 'Limpopo', 'invoicing' ),
        'MP'  => __( 'Mpumalanga', 'invoicing' ),
        'NC'  => __( 'Northern Cape', 'invoicing' ),
        'NW'  => __( 'North West', 'invoicing' ),
        'WC'  => __( 'Western Cape', 'invoicing' )
    );

    return apply_filters( 'wpinv_south_africa_states', $states );
}

function wpinv_get_thailand_states_list() {
    $states = array(
        'TH-37' => __( 'Amnat Charoen (&#3629;&#3635;&#3609;&#3634;&#3592;&#3648;&#3592;&#3619;&#3636;&#3597;)', 'invoicing' ),
        'TH-15' => __( 'Ang Thong (&#3629;&#3656;&#3634;&#3591;&#3607;&#3629;&#3591;)', 'invoicing' ),
        'TH-14' => __( 'Ayutthaya (&#3614;&#3619;&#3632;&#3609;&#3588;&#3619;&#3624;&#3619;&#3637;&#3629;&#3618;&#3640;&#3608;&#3618;&#3634;)', 'invoicing' ),
        'TH-10' => __( 'Bangkok (&#3585;&#3619;&#3640;&#3591;&#3648;&#3607;&#3614;&#3617;&#3627;&#3634;&#3609;&#3588;&#3619;)', 'invoicing' ),
        'TH-38' => __( 'Bueng Kan (&#3610;&#3638;&#3591;&#3585;&#3634;&#3628;)', 'invoicing' ),
        'TH-31' => __( 'Buri Ram (&#3610;&#3640;&#3619;&#3637;&#3619;&#3633;&#3617;&#3618;&#3660;)', 'invoicing' ),
        'TH-24' => __( 'Chachoengsao (&#3593;&#3632;&#3648;&#3594;&#3636;&#3591;&#3648;&#3607;&#3619;&#3634;)', 'invoicing' ),
        'TH-18' => __( 'Chai Nat (&#3594;&#3633;&#3618;&#3609;&#3634;&#3607;)', 'invoicing' ),
        'TH-36' => __( 'Chaiyaphum (&#3594;&#3633;&#3618;&#3616;&#3641;&#3617;&#3636;)', 'invoicing' ),
        'TH-22' => __( 'Chanthaburi (&#3592;&#3633;&#3609;&#3607;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-50' => __( 'Chiang Mai (&#3648;&#3594;&#3637;&#3618;&#3591;&#3651;&#3627;&#3617;&#3656;)', 'invoicing' ),
        'TH-57' => __( 'Chiang Rai (&#3648;&#3594;&#3637;&#3618;&#3591;&#3619;&#3634;&#3618;)', 'invoicing' ),
        'TH-20' => __( 'Chonburi (&#3594;&#3621;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-86' => __( 'Chumphon (&#3594;&#3640;&#3617;&#3614;&#3619;)', 'invoicing' ),
        'TH-46' => __( 'Kalasin (&#3585;&#3634;&#3628;&#3626;&#3636;&#3609;&#3608;&#3640;&#3660;)', 'invoicing' ),
        'TH-62' => __( 'Kamphaeng Phet (&#3585;&#3635;&#3649;&#3614;&#3591;&#3648;&#3614;&#3594;&#3619;)', 'invoicing' ),
        'TH-71' => __( 'Kanchanaburi (&#3585;&#3634;&#3597;&#3592;&#3609;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-40' => __( 'Khon Kaen (&#3586;&#3629;&#3609;&#3649;&#3585;&#3656;&#3609;)', 'invoicing' ),
        'TH-81' => __( 'Krabi (&#3585;&#3619;&#3632;&#3610;&#3637;&#3656;)', 'invoicing' ),
        'TH-52' => __( 'Lampang (&#3621;&#3635;&#3611;&#3634;&#3591;)', 'invoicing' ),
        'TH-51' => __( 'Lamphun (&#3621;&#3635;&#3614;&#3641;&#3609;)', 'invoicing' ),
        'TH-42' => __( 'Loei (&#3648;&#3621;&#3618;)', 'invoicing' ),
        'TH-16' => __( 'Lopburi (&#3621;&#3614;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-58' => __( 'Mae Hong Son (&#3649;&#3617;&#3656;&#3630;&#3656;&#3629;&#3591;&#3626;&#3629;&#3609;)', 'invoicing' ),
        'TH-44' => __( 'Maha Sarakham (&#3617;&#3627;&#3634;&#3626;&#3634;&#3619;&#3588;&#3634;&#3617;)', 'invoicing' ),
        'TH-49' => __( 'Mukdahan (&#3617;&#3640;&#3585;&#3604;&#3634;&#3627;&#3634;&#3619;)', 'invoicing' ),
        'TH-26' => __( 'Nakhon Nayok (&#3609;&#3588;&#3619;&#3609;&#3634;&#3618;&#3585;)', 'invoicing' ),
        'TH-73' => __( 'Nakhon Pathom (&#3609;&#3588;&#3619;&#3611;&#3600;&#3617;)', 'invoicing' ),
        'TH-48' => __( 'Nakhon Phanom (&#3609;&#3588;&#3619;&#3614;&#3609;&#3617;)', 'invoicing' ),
        'TH-30' => __( 'Nakhon Ratchasima (&#3609;&#3588;&#3619;&#3619;&#3634;&#3594;&#3626;&#3637;&#3617;&#3634;)', 'invoicing' ),
        'TH-60' => __( 'Nakhon Sawan (&#3609;&#3588;&#3619;&#3626;&#3623;&#3619;&#3619;&#3588;&#3660;)', 'invoicing' ),
        'TH-80' => __( 'Nakhon Si Thammarat (&#3609;&#3588;&#3619;&#3624;&#3619;&#3637;&#3608;&#3619;&#3619;&#3617;&#3619;&#3634;&#3594;)', 'invoicing' ),
        'TH-55' => __( 'Nan (&#3609;&#3656;&#3634;&#3609;)', 'invoicing' ),
        'TH-96' => __( 'Narathiwat (&#3609;&#3619;&#3634;&#3608;&#3636;&#3623;&#3634;&#3626;)', 'invoicing' ),
        'TH-39' => __( 'Nong Bua Lam Phu (&#3627;&#3609;&#3629;&#3591;&#3610;&#3633;&#3623;&#3621;&#3635;&#3616;&#3641;)', 'invoicing' ),
        'TH-43' => __( 'Nong Khai (&#3627;&#3609;&#3629;&#3591;&#3588;&#3634;&#3618;)', 'invoicing' ),
        'TH-12' => __( 'Nonthaburi (&#3609;&#3609;&#3607;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-13' => __( 'Pathum Thani (&#3611;&#3607;&#3640;&#3617;&#3608;&#3634;&#3609;&#3637;)', 'invoicing' ),
        'TH-94' => __( 'Pattani (&#3611;&#3633;&#3605;&#3605;&#3634;&#3609;&#3637;)', 'invoicing' ),
        'TH-82' => __( 'Phang Nga (&#3614;&#3633;&#3591;&#3591;&#3634;)', 'invoicing' ),
        'TH-93' => __( 'Phatthalung (&#3614;&#3633;&#3607;&#3621;&#3640;&#3591;)', 'invoicing' ),
        'TH-56' => __( 'Phayao (&#3614;&#3632;&#3648;&#3618;&#3634;)', 'invoicing' ),
        'TH-67' => __( 'Phetchabun (&#3648;&#3614;&#3594;&#3619;&#3610;&#3641;&#3619;&#3603;&#3660;)', 'invoicing' ),
        'TH-76' => __( 'Phetchaburi (&#3648;&#3614;&#3594;&#3619;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-66' => __( 'Phichit (&#3614;&#3636;&#3592;&#3636;&#3605;&#3619;)', 'invoicing' ),
        'TH-65' => __( 'Phitsanulok (&#3614;&#3636;&#3625;&#3603;&#3640;&#3650;&#3621;&#3585;)', 'invoicing' ),
        'TH-54' => __( 'Phrae (&#3649;&#3614;&#3619;&#3656;)', 'invoicing' ),
        'TH-83' => __( 'Phuket (&#3616;&#3641;&#3648;&#3585;&#3655;&#3605;)', 'invoicing' ),
        'TH-25' => __( 'Prachin Buri (&#3611;&#3619;&#3634;&#3592;&#3637;&#3609;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-77' => __( 'Prachuap Khiri Khan (&#3611;&#3619;&#3632;&#3592;&#3623;&#3610;&#3588;&#3637;&#3619;&#3637;&#3586;&#3633;&#3609;&#3608;&#3660;)', 'invoicing' ),
        'TH-85' => __( 'Ranong (&#3619;&#3632;&#3609;&#3629;&#3591;)', 'invoicing' ),
        'TH-70' => __( 'Ratchaburi (&#3619;&#3634;&#3594;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-21' => __( 'Rayong (&#3619;&#3632;&#3618;&#3629;&#3591;)', 'invoicing' ),
        'TH-45' => __( 'Roi Et (&#3619;&#3657;&#3629;&#3618;&#3648;&#3629;&#3655;&#3604;)', 'invoicing' ),
        'TH-27' => __( 'Sa Kaeo (&#3626;&#3619;&#3632;&#3649;&#3585;&#3657;&#3623;)', 'invoicing' ),
        'TH-47' => __( 'Sakon Nakhon (&#3626;&#3585;&#3621;&#3609;&#3588;&#3619;)', 'invoicing' ),
        'TH-11' => __( 'Samut Prakan (&#3626;&#3617;&#3640;&#3607;&#3619;&#3611;&#3619;&#3634;&#3585;&#3634;&#3619;)', 'invoicing' ),
        'TH-74' => __( 'Samut Sakhon (&#3626;&#3617;&#3640;&#3607;&#3619;&#3626;&#3634;&#3588;&#3619;)', 'invoicing' ),
        'TH-75' => __( 'Samut Songkhram (&#3626;&#3617;&#3640;&#3607;&#3619;&#3626;&#3591;&#3588;&#3619;&#3634;&#3617;)', 'invoicing' ),
        'TH-19' => __( 'Saraburi (&#3626;&#3619;&#3632;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-91' => __( 'Satun (&#3626;&#3605;&#3641;&#3621;)', 'invoicing' ),
        'TH-17' => __( 'Sing Buri (&#3626;&#3636;&#3591;&#3627;&#3660;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-33' => __( 'Sisaket (&#3624;&#3619;&#3637;&#3626;&#3632;&#3648;&#3585;&#3625;)', 'invoicing' ),
        'TH-90' => __( 'Songkhla (&#3626;&#3591;&#3586;&#3621;&#3634;)', 'invoicing' ),
        'TH-64' => __( 'Sukhothai (&#3626;&#3640;&#3650;&#3586;&#3607;&#3633;&#3618;)', 'invoicing' ),
        'TH-72' => __( 'Suphan Buri (&#3626;&#3640;&#3614;&#3619;&#3619;&#3603;&#3610;&#3640;&#3619;&#3637;)', 'invoicing' ),
        'TH-84' => __( 'Surat Thani (&#3626;&#3640;&#3619;&#3634;&#3625;&#3598;&#3619;&#3660;&#3608;&#3634;&#3609;&#3637;)', 'invoicing' ),
        'TH-32' => __( 'Surin (&#3626;&#3640;&#3619;&#3636;&#3609;&#3607;&#3619;&#3660;)', 'invoicing' ),
        'TH-63' => __( 'Tak (&#3605;&#3634;&#3585;)', 'invoicing' ),
        'TH-92' => __( 'Trang (&#3605;&#3619;&#3633;&#3591;)', 'invoicing' ),
        'TH-23' => __( 'Trat (&#3605;&#3619;&#3634;&#3604;)', 'invoicing' ),
        'TH-34' => __( 'Ubon Ratchathani (&#3629;&#3640;&#3610;&#3621;&#3619;&#3634;&#3594;&#3608;&#3634;&#3609;&#3637;)', 'invoicing' ),
        'TH-41' => __( 'Udon Thani (&#3629;&#3640;&#3604;&#3619;&#3608;&#3634;&#3609;&#3637;)', 'invoicing' ),
        'TH-61' => __( 'Uthai Thani (&#3629;&#3640;&#3607;&#3633;&#3618;&#3608;&#3634;&#3609;&#3637;)', 'invoicing' ),
        'TH-53' => __( 'Uttaradit (&#3629;&#3640;&#3605;&#3619;&#3604;&#3636;&#3605;&#3606;&#3660;)', 'invoicing' ),
        'TH-95' => __( 'Yala (&#3618;&#3632;&#3621;&#3634;)', 'invoicing' ),
        'TH-35' => __( 'Yasothon (&#3618;&#3650;&#3626;&#3608;&#3619;)', 'invoicing' )
    );

    return apply_filters( 'wpinv_thailand_states', $states );
}

function wpinv_get_turkey_states_list() {
    $states = array(
        'TR01' => __( 'Adana', 'invoicing' ),
        'TR02' => __( 'Ad&#305;yaman', 'invoicing' ),
        'TR03' => __( 'Afyon', 'invoicing' ),
        'TR04' => __( 'A&#287;r&#305;', 'invoicing' ),
        'TR05' => __( 'Amasya', 'invoicing' ),
        'TR06' => __( 'Ankara', 'invoicing' ),
        'TR07' => __( 'Antalya', 'invoicing' ),
        'TR08' => __( 'Artvin', 'invoicing' ),
        'TR09' => __( 'Ayd&#305;n', 'invoicing' ),
        'TR10' => __( 'Bal&#305;kesir', 'invoicing' ),
        'TR11' => __( 'Bilecik', 'invoicing' ),
        'TR12' => __( 'Bing&#246;l', 'invoicing' ),
        'TR13' => __( 'Bitlis', 'invoicing' ),
        'TR14' => __( 'Bolu', 'invoicing' ),
        'TR15' => __( 'Burdur', 'invoicing' ),
        'TR16' => __( 'Bursa', 'invoicing' ),
        'TR17' => __( '&#199;anakkale', 'invoicing' ),
        'TR18' => __( '&#199;ank&#305;kesir', 'invoicing' ),
        'TR19' => __( '&#199;orum', 'invoicing' ),
        'TR20' => __( 'Denizli', 'invoicing' ),
        'TR21' => __( 'Diyarbak&#305;r', 'invoicing' ),
        'TR22' => __( 'Edirne', 'invoicing' ),
        'TR23' => __( 'Elaz&#305;&#287;', 'invoicing' ),
        'TR24' => __( 'Erzincan', 'invoicing' ),
        'TR25' => __( 'Erzurum', 'invoicing' ),
        'TR26' => __( 'Eski&#351;ehir', 'invoicing' ),
        'TR27' => __( 'Gaziantep', 'invoicing' ),
        'TR28' => __( 'Giresun', 'invoicing' ),
        'TR29' => __( 'G&#252;m&#252;&#351;hane', 'invoicing' ),
        'TR30' => __( 'Hakkari', 'invoicing' ),
        'TR31' => __( 'Hatay', 'invoicing' ),
        'TR32' => __( 'Isparta', 'invoicing' ),
        'TR33' => __( '&#304;&#231;el', 'invoicing' ),
        'TR34' => __( '&#304;stanbul', 'invoicing' ),
        'TR35' => __( '&#304;zmir', 'invoicing' ),
        'TR36' => __( 'Kars', 'invoicing' ),
        'TR37' => __( 'Kastamonu', 'invoicing' ),
        'TR38' => __( 'Kayseri', 'invoicing' ),
        'TR39' => __( 'K&#305;rklareli', 'invoicing' ),
        'TR40' => __( 'K&#305;r&#351;ehir', 'invoicing' ),
        'TR41' => __( 'Kocaeli', 'invoicing' ),
        'TR42' => __( 'Konya', 'invoicing' ),
        'TR43' => __( 'K&#252;tahya', 'invoicing' ),
        'TR44' => __( 'Malatya', 'invoicing' ),
        'TR45' => __( 'Manisa', 'invoicing' ),
        'TR46' => __( 'Kahramanmara&#351;', 'invoicing' ),
        'TR47' => __( 'Mardin', 'invoicing' ),
        'TR48' => __( 'Mu&#287;la', 'invoicing' ),
        'TR49' => __( 'Mu&#351;', 'invoicing' ),
        'TR50' => __( 'Nev&#351;ehir', 'invoicing' ),
        'TR51' => __( 'Ni&#287;de', 'invoicing' ),
        'TR52' => __( 'Ordu', 'invoicing' ),
        'TR53' => __( 'Rize', 'invoicing' ),
        'TR54' => __( 'Sakarya', 'invoicing' ),
        'TR55' => __( 'Samsun', 'invoicing' ),
        'TR56' => __( 'Siirt', 'invoicing' ),
        'TR57' => __( 'Sinop', 'invoicing' ),
        'TR58' => __( 'Sivas', 'invoicing' ),
        'TR59' => __( 'Tekirda&#287;', 'invoicing' ),
        'TR60' => __( 'Tokat', 'invoicing' ),
        'TR61' => __( 'Trabzon', 'invoicing' ),
        'TR62' => __( 'Tunceli', 'invoicing' ),
        'TR63' => __( '&#350;anl&#305;urfa', 'invoicing' ),
        'TR64' => __( 'U&#351;ak', 'invoicing' ),
        'TR65' => __( 'Van', 'invoicing' ),
        'TR66' => __( 'Yozgat', 'invoicing' ),
        'TR67' => __( 'Zonguldak', 'invoicing' ),
        'TR68' => __( 'Aksaray', 'invoicing' ),
        'TR69' => __( 'Bayburt', 'invoicing' ),
        'TR70' => __( 'Karaman', 'invoicing' ),
        'TR71' => __( 'K&#305;r&#305;kkale', 'invoicing' ),
        'TR72' => __( 'Batman', 'invoicing' ),
        'TR73' => __( '&#350;&#305;rnak', 'invoicing' ),
        'TR74' => __( 'Bart&#305;n', 'invoicing' ),
        'TR75' => __( 'Ardahan', 'invoicing' ),
        'TR76' => __( 'I&#287;d&#305;r', 'invoicing' ),
        'TR77' => __( 'Yalova', 'invoicing' ),
        'TR78' => __( 'Karab&#252;k', 'invoicing' ),
        'TR79' => __( 'Kilis', 'invoicing' ),
        'TR80' => __( 'Osmaniye', 'invoicing' ),
        'TR81' => __( 'D&#252;zce', 'invoicing' )
    );

    return apply_filters( 'wpinv_turkey_states', $states );
}

function wpinv_get_spain_states_list() {
    $states = array(
        'C'  => __( 'A Coru&ntilde;a', 'invoicing' ),
        'VI' => __( 'Araba', 'invoicing' ),
        'AB' => __( 'Albacete', 'invoicing' ),
        'A'  => __( 'Alicante', 'invoicing' ),
        'AL' => __( 'Almer&iacute;a', 'invoicing' ),
        'O'  => __( 'Asturias', 'invoicing' ),
        'AV' => __( '&Aacute;vila', 'invoicing' ),
        'BA' => __( 'Badajoz', 'invoicing' ),
        'PM' => __( 'Baleares', 'invoicing' ),
        'B'  => __( 'Barcelona', 'invoicing' ),
        'BU' => __( 'Burgos', 'invoicing' ),
        'CC' => __( 'C&aacute;ceres', 'invoicing' ),
        'CA' => __( 'C&aacute;diz', 'invoicing' ),
        'S'  => __( 'Cantabria', 'invoicing' ),
        'CS' => __( 'Castell&oacute;n', 'invoicing' ),
        'CE' => __( 'Ceuta', 'invoicing' ),
        'CR' => __( 'Ciudad Real', 'invoicing' ),
        'CO' => __( 'C&oacute;rdoba', 'invoicing' ),
        'CU' => __( 'Cuenca', 'invoicing' ),
        'GI' => __( 'Girona', 'invoicing' ),
        'GR' => __( 'Granada', 'invoicing' ),
        'GU' => __( 'Guadalajara', 'invoicing' ),
        'SS' => __( 'Gipuzkoa', 'invoicing' ),
        'H'  => __( 'Huelva', 'invoicing' ),
        'HU' => __( 'Huesca', 'invoicing' ),
        'J'  => __( 'Ja&eacute;n', 'invoicing' ),
        'LO' => __( 'La Rioja', 'invoicing' ),
        'GC' => __( 'Las Palmas', 'invoicing' ),
        'LE' => __( 'Le&oacute;n', 'invoicing' ),
        'L'  => __( 'Lleida', 'invoicing' ),
        'LU' => __( 'Lugo', 'invoicing' ),
        'M'  => __( 'Madrid', 'invoicing' ),
        'MA' => __( 'M&aacute;laga', 'invoicing' ),
        'ML' => __( 'Melilla', 'invoicing' ),
        'MU' => __( 'Murcia', 'invoicing' ),
        'NA' => __( 'Navarra', 'invoicing' ),
        'OR' => __( 'Ourense', 'invoicing' ),
        'P'  => __( 'Palencia', 'invoicing' ),
        'PO' => __( 'Pontevedra', 'invoicing' ),
        'SA' => __( 'Salamanca', 'invoicing' ),
        'TF' => __( 'Santa Cruz de Tenerife', 'invoicing' ),
        'SG' => __( 'Segovia', 'invoicing' ),
        'SE' => __( 'Sevilla', 'invoicing' ),
        'SO' => __( 'Soria', 'invoicing' ),
        'T'  => __( 'Tarragona', 'invoicing' ),
        'TE' => __( 'Teruel', 'invoicing' ),
        'TO' => __( 'Toledo', 'invoicing' ),
        'V'  => __( 'Valencia', 'invoicing' ),
        'VA' => __( 'Valladolid', 'invoicing' ),
        'BI' => __( 'Bizkaia', 'invoicing' ),
        'ZA' => __( 'Zamora', 'invoicing' ),
        'Z'  => __( 'Zaragoza', 'invoicing' )
    );

    return apply_filters( 'wpinv_spain_states', $states );
}

function wpinv_get_states_field() {
	if( empty( $_POST['country'] ) ) {
		$_POST['country'] = wpinv_get_default_country();
	}
	$states = wpinv_get_country_states( sanitize_text_field( $_POST['country'] ) );

	if( !empty( $states ) ) {
		$sanitized_field_name = sanitize_text_field( $_POST['field_name'] );
        
        $args = array(
			'name'    => $sanitized_field_name,
			'id'      => $sanitized_field_name,
			'class'   => $sanitized_field_name . ' wpinv-select',
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