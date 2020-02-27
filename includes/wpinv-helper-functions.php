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

function wpinv_item_quantities_enabled() {
    $ret = wpinv_get_option( 'item_quantities', true );

    return (bool) apply_filters( 'wpinv_item_quantities_enabled', $ret );
}

function wpinv_get_ip() {
    $ip = '127.0.0.1';

    if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        $ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
    } elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
    } elseif( !empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
    }

    return apply_filters( 'wpinv_get_ip', $ip );
}

function wpinv_get_user_agent() {
    if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );
    } else {
        $user_agent = '';
    }

    return apply_filters( 'wpinv_get_user_agent', $user_agent );
}

function wpinv_sanitize_amount( $amount, $decimals = NULL ) {
    $is_negative   = false;
    $thousands_sep = wpinv_thousands_separator();
    $decimal_sep   = wpinv_decimal_separator();
    if ( $decimals === NULL ) {
        $decimals = wpinv_decimals();
    }

    // Sanitize the amount
    if ( $decimal_sep == ',' && false !== ( $found = strpos( $amount, $decimal_sep ) ) ) {
        if ( ( $thousands_sep == '.' || $thousands_sep == ' ' ) && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
            $amount = str_replace( $thousands_sep, '', $amount );
        } elseif( empty( $thousands_sep ) && false !== ( $found = strpos( $amount, '.' ) ) ) {
            $amount = str_replace( '.', '', $amount );
        }

        $amount = str_replace( $decimal_sep, '.', $amount );
    } elseif( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
        $amount = str_replace( $thousands_sep, '', $amount );
    }

    if( $amount < 0 ) {
        $is_negative = true;
    }

    $amount   = preg_replace( '/[^0-9\.]/', '', $amount );

    $decimals = apply_filters( 'wpinv_sanitize_amount_decimals', absint( $decimals ), $amount );
    $amount   = number_format( (double) $amount, absint( $decimals ), '.', '' );

    if( $is_negative ) {
        $amount *= -1;
    }

    return apply_filters( 'wpinv_sanitize_amount', $amount, $decimals );
}
add_filter( 'wpinv_sanitize_amount_decimals', 'wpinv_currency_decimal_filter', 10, 1 );

function wpinv_round_amount( $amount, $decimals = NULL ) {
    if ( $decimals === NULL ) {
        $decimals = wpinv_decimals();
    }
    
    $amount = round( (double)$amount, wpinv_currency_decimal_filter( absint( $decimals ) ) );

    return apply_filters( 'wpinv_round_amount', $amount, $decimals );
}

function wpinv_get_invoice_statuses( $draft = false, $trashed = false, $invoice = false ) {
    global $post;

    $invoice_statuses = array(
        'wpi-pending' => __( 'Pending Payment', 'invoicing' ),
        'publish' => __( 'Paid', 'invoicing'),
        'wpi-processing' => __( 'Processing', 'invoicing' ),
        'wpi-onhold' => __( 'On Hold', 'invoicing' ),
        'wpi-refunded' => __( 'Refunded', 'invoicing' ),
        'wpi-cancelled' => __( 'Cancelled', 'invoicing' ),
        'wpi-failed' => __( 'Failed', 'invoicing' ),
        'wpi-renewal' => __( 'Renewal Payment', 'invoicing' )
    );

    if ( $draft ) {
        $invoice_statuses['draft'] = __( 'Draft', 'invoicing' );
    }

    if ( $trashed ) {
        $invoice_statuses['trash'] = __( 'Trash', 'invoicing' );
    }

    return apply_filters( 'wpinv_statuses', $invoice_statuses, $invoice );
}

function wpinv_status_nicename( $status ) {
    $statuses = wpinv_get_invoice_statuses( true, true );
    $status   = isset( $statuses[$status] ) ? $statuses[$status] : __( $status, 'invoicing' );

    return $status;
}

function wpinv_get_currency() {
    $currency = wpinv_get_option( 'currency', 'USD' );
    
    return apply_filters( 'wpinv_currency', $currency );
}

function wpinv_currency_symbol( $currency = '' ) {
    if ( empty( $currency ) ) {
        $currency = wpinv_get_currency();
    }
    
    $symbols = apply_filters( 'wpinv_currency_symbols', array(
        'AED' => '&#x62f;.&#x625;',
        'AFN' => '&#x60b;',
        'ALL' => 'L',
        'AMD' => 'AMD',
        'ANG' => '&fnof;',
        'AOA' => 'Kz',
        'ARS' => '&#36;',
        'AUD' => '&#36;',
        'AWG' => '&fnof;',
        'AZN' => 'AZN',
        'BAM' => 'KM',
        'BBD' => '&#36;',
        'BDT' => '&#2547;',
        'BGN' => '&#1083;&#1074;.',
        'BHD' => '.&#x62f;.&#x628;',
        'BIF' => 'Fr',
        'BMD' => '&#36;',
        'BND' => '&#36;',
        'BOB' => 'Bs.',
        'BRL' => '&#82;&#36;',
        'BSD' => '&#36;',
        'BTC' => '&#3647;',
        'BTN' => 'Nu.',
        'BWP' => 'P',
        'BYN' => 'Br',
        'BZD' => '&#36;',
        'CAD' => '&#36;',
        'CDF' => 'Fr',
        'CHF' => '&#67;&#72;&#70;',
        'CLP' => '&#36;',
        'CNY' => '&yen;',
        'COP' => '&#36;',
        'CRC' => '&#x20a1;',
        'CUC' => '&#36;',
        'CUP' => '&#36;',
        'CVE' => '&#36;',
        'CZK' => '&#75;&#269;',
        'DJF' => 'Fr',
        'DKK' => 'DKK',
        'DOP' => 'RD&#36;',
        'DZD' => '&#x62f;.&#x62c;',
        'EGP' => 'EGP',
        'ERN' => 'Nfk',
        'ETB' => 'Br',
        'EUR' => '&euro;',
        'FJD' => '&#36;',
        'FKP' => '&pound;',
        'GBP' => '&pound;',
        'GEL' => '&#x10da;',
        'GGP' => '&pound;',
        'GHS' => '&#x20b5;',
        'GIP' => '&pound;',
        'GMD' => 'D',
        'GNF' => 'Fr',
        'GTQ' => 'Q',
        'GYD' => '&#36;',
        'HKD' => '&#36;',
        'HNL' => 'L',
        'HRK' => 'Kn',
        'HTG' => 'G',
        'HUF' => '&#70;&#116;',
        'IDR' => 'Rp',
        'ILS' => '&#8362;',
        'IMP' => '&pound;',
        'INR' => '&#8377;',
        'IQD' => '&#x639;.&#x62f;',
        'IRR' => '&#xfdfc;',
        'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
        'ISK' => 'kr.',
        'JEP' => '&pound;',
        'JMD' => '&#36;',
        'JOD' => '&#x62f;.&#x627;',
        'JPY' => '&yen;',
        'KES' => 'KSh',
        'KGS' => '&#x441;&#x43e;&#x43c;',
        'KHR' => '&#x17db;',
        'KMF' => 'Fr',
        'KPW' => '&#x20a9;',
        'KRW' => '&#8361;',
        'KWD' => '&#x62f;.&#x643;',
        'KYD' => '&#36;',
        'KZT' => 'KZT',
        'LAK' => '&#8365;',
        'LBP' => '&#x644;.&#x644;',
        'LKR' => '&#xdbb;&#xdd4;',
        'LRD' => '&#36;',
        'LSL' => 'L',
        'LYD' => '&#x644;.&#x62f;',
        'MAD' => '&#x62f;.&#x645;.',
        'MDL' => 'MDL',
        'MGA' => 'Ar',
        'MKD' => '&#x434;&#x435;&#x43d;',
        'MMK' => 'Ks',
        'MNT' => '&#x20ae;',
        'MOP' => 'P',
        'MRO' => 'UM',
        'MUR' => '&#x20a8;',
        'MVR' => '.&#x783;',
        'MWK' => 'MK',
        'MXN' => '&#36;',
        'MYR' => '&#82;&#77;',
        'MZN' => 'MT',
        'NAD' => '&#36;',
        'NGN' => '&#8358;',
        'NIO' => 'C&#36;',
        'NOK' => '&#107;&#114;',
        'NPR' => '&#8360;',
        'NZD' => '&#36;',
        'OMR' => '&#x631;.&#x639;.',
        'PAB' => 'B/.',
        'PEN' => 'S/.',
        'PGK' => 'K',
        'PHP' => '&#8369;',
        'PKR' => '&#8360;',
        'PLN' => '&#122;&#322;',
        'PRB' => '&#x440;.',
        'PYG' => '&#8370;',
        'QAR' => '&#x631;.&#x642;',
        'RMB' => '&yen;',
        'RON' => 'lei',
        'RSD' => '&#x434;&#x438;&#x43d;.',
        'RUB' => '&#8381;',
        'RWF' => 'Fr',
        'SAR' => '&#x631;.&#x633;',
        'SBD' => '&#36;',
        'SCR' => '&#x20a8;',
        'SDG' => '&#x62c;.&#x633;.',
        'SEK' => '&#107;&#114;',
        'SGD' => '&#36;',
        'SHP' => '&pound;',
        'SLL' => 'Le',
        'SOS' => 'Sh',
        'SRD' => '&#36;',
        'SSP' => '&pound;',
        'STD' => 'Db',
        'SYP' => '&#x644;.&#x633;',
        'SZL' => 'L',
        'THB' => '&#3647;',
        'TJS' => '&#x405;&#x41c;',
        'TMT' => 'm',
        'TND' => '&#x62f;.&#x62a;',
        'TOP' => 'T&#36;',
        'TRY' => '&#8378;',
        'TTD' => '&#36;',
        'TWD' => '&#78;&#84;&#36;',
        'TZS' => 'Sh',
        'UAH' => '&#8372;',
        'UGX' => 'UGX',
        'USD' => '&#36;',
        'UYU' => '&#36;',
        'UZS' => 'UZS',
        'VEF' => 'Bs.',
        'VND' => '&#8363;',
        'VUV' => 'Vt',
        'WST' => 'T',
        'XAF' => 'Fr',
        'XCD' => '&#36;',
        'XOF' => 'Fr',
        'XPF' => 'Fr',
        'YER' => '&#xfdfc;',
        'ZAR' => '&#82;',
        'ZMW' => 'ZK',
    ) );

    $currency_symbol = isset( $symbols[$currency] ) ? $symbols[$currency] : $currency;

    return apply_filters( 'wpinv_currency_symbol', $currency_symbol, $currency );
}

function wpinv_currency_position() {
    $position = wpinv_get_option( 'currency_position', 'left' );
    
    return apply_filters( 'wpinv_currency_position', $position );
}

function wpinv_thousands_separator() {
    $thousand_sep = wpinv_get_option( 'thousands_separator', ',' );
    
    return apply_filters( 'wpinv_thousands_separator', $thousand_sep );
}

function wpinv_decimal_separator() {
    $decimal_sep = wpinv_get_option( 'decimal_separator', '.' );
    
    return apply_filters( 'wpinv_decimal_separator', $decimal_sep );
}

function wpinv_decimals() {
    $decimals = apply_filters( 'wpinv_decimals', wpinv_get_option( 'decimals', 2 ) );
    
    return absint( $decimals );
}

function wpinv_get_currencies() {
    $currencies = array(
        'USD' => __( 'US Dollar', 'invoicing' ),
        'EUR' => __( 'Euro', 'invoicing' ),
        'GBP' => __( 'Pound Sterling', 'invoicing' ),
        'AED' => __( 'United Arab Emirates', 'invoicing' ),
        'AFN' => __( 'Afghan Afghani', 'invoicing' ),
        'ALL' => __( 'Albanian Lek', 'invoicing' ),
        'AMD' => __( 'Armenian Dram', 'invoicing' ),
        'ANG' => __( 'Netherlands Antillean Guilder', 'invoicing' ),
        'AOA' => __( 'Angolan Kwanza', 'invoicing' ),
        'ARS' => __( 'Argentine Peso', 'invoicing' ),
        'AUD' => __( 'Australian Dollar', 'invoicing' ),
        'AWG' => __( 'Aruban Florin', 'invoicing' ),
        'AZN' => __( 'Azerbaijani Manat', 'invoicing' ),
        'BAM' => __( 'Bosnia and Herzegovina Convertible Marka', 'invoicing' ),
        'BBD' => __( 'Barbadian Dollar', 'invoicing' ),
        'BDT' => __( 'Bangladeshi Taka', 'invoicing' ),
        'BGN' => __( 'Bulgarian Lev', 'invoicing' ),
        'BHD' => __( 'Bahraini Dinar', 'invoicing' ),
        'BIF' => __( 'Burundian Franc', 'invoicing' ),
        'BMD' => __( 'Bermudian Dollar', 'invoicing' ),
        'BND' => __( 'Brunei Dollar', 'invoicing' ),
        'BOB' => __( 'Bolivian Boliviano', 'invoicing' ),
        'BRL' => __( 'Brazilian Real', 'invoicing' ),
        'BSD' => __( 'Bahamian Dollar', 'invoicing' ),
        'BTC' => __( 'Bitcoin', 'invoicing' ),
        'BTN' => __( 'Bhutanese Ngultrum', 'invoicing' ),
        'BWP' => __( 'Botswana Pula', 'invoicing' ),
        'BYN' => __( 'Belarusian Ruble', 'invoicing' ),
        'BZD' => __( 'Belize Dollar', 'invoicing' ),
        'CAD' => __( 'Canadian Dollar', 'invoicing' ),
        'CDF' => __( 'Congolese Franc', 'invoicing' ),
        'CHF' => __( 'Swiss Franc', 'invoicing' ),
        'CLP' => __( 'Chilean Peso', 'invoicing' ),
        'CNY' => __( 'Chinese Yuan', 'invoicing' ),
        'COP' => __( 'Colombian Peso', 'invoicing' ),
        'CRC' => __( 'Costa Rican Colon', 'invoicing' ),
        'CUC' => __( 'Cuban Convertible Peso', 'invoicing' ),
        'CUP' => __( 'Cuban Peso', 'invoicing' ),
        'CVE' => __( 'Cape Verdean escudo', 'invoicing' ),
        'CZK' => __( 'Czech Koruna', 'invoicing' ),
        'DJF' => __( 'Djiboutian Franc', 'invoicing' ),
        'DKK' => __( 'Danish Krone', 'invoicing' ),
        'DOP' => __( 'Dominican Peso', 'invoicing' ),
        'DZD' => __( 'Algerian Dinar', 'invoicing' ),
        'EGP' => __( 'Egyptian Pound', 'invoicing' ),
        'ERN' => __( 'Eritrean Nakfa', 'invoicing' ),
        'ETB' => __( 'Ethiopian Irr', 'invoicing' ),
        'FJD' => __( 'Fijian Dollar', 'invoicing' ),
        'FKP' => __( 'Falkland Islands Pound', 'invoicing' ),
        'GEL' => __( 'Georgian Lari', 'invoicing' ),
        'GGP' => __( 'Guernsey Pound', 'invoicing' ),
        'GHS' => __( 'Ghana Cedi', 'invoicing' ),
        'GIP' => __( 'Gibraltar Pound', 'invoicing' ),
        'GMD' => __( 'Gambian Dalasi', 'invoicing' ),
        'GNF' => __( 'Guinean Franc', 'invoicing' ),
        'GTQ' => __( 'Guatemalan Quetzal', 'invoicing' ),
        'GYD' => __( 'Guyanese Dollar', 'invoicing' ),
        'HKD' => __( 'Hong Kong Dollar', 'invoicing' ),
        'HNL' => __( 'Honduran Lempira', 'invoicing' ),
        'HRK' => __( 'Croatian Kuna', 'invoicing' ),
        'HTG' => __( 'Haitian Gourde', 'invoicing' ),
        'HUF' => __( 'Hungarian Forint', 'invoicing' ),
        'IDR' => __( 'Indonesian Rupiah', 'invoicing' ),
        'ILS' => __( 'Israeli New Shekel', 'invoicing' ),
        'IMP' => __( 'Manx Pound', 'invoicing' ),
        'INR' => __( 'Indian Rupee', 'invoicing' ),
        'IQD' => __( 'Iraqi Dinar', 'invoicing' ),
        'IRR' => __( 'Iranian Rial', 'invoicing' ),
        'IRT' => __( 'Iranian Toman', 'invoicing' ),
        'ISK' => __( 'Icelandic Krona', 'invoicing' ),
        'JEP' => __( 'Jersey Pound', 'invoicing' ),
        'JMD' => __( 'Jamaican Dollar', 'invoicing' ),
        'JOD' => __( 'Jordanian Dinar', 'invoicing' ),
        'JPY' => __( 'Japanese Yen', 'invoicing' ),
        'KES' => __( 'Kenyan Shilling', 'invoicing' ),
        'KGS' => __( 'Kyrgyzstani Som', 'invoicing' ),
        'KHR' => __( 'Cambodian Riel', 'invoicing' ),
        'KMF' => __( 'Comorian Franc', 'invoicing' ),
        'KPW' => __( 'North Korean Won', 'invoicing' ),
        'KRW' => __( 'South Korean Won', 'invoicing' ),
        'KWD' => __( 'Kuwaiti Dinar', 'invoicing' ),
        'KYD' => __( 'Cayman Islands Dollar', 'invoicing' ),
        'KZT' => __( 'Kazakhstani Tenge', 'invoicing' ),
        'LAK' => __( 'Lao Kip', 'invoicing' ),
        'LBP' => __( 'Lebanese Pound', 'invoicing' ),
        'LKR' => __( 'Sri Lankan Rupee', 'invoicing' ),
        'LRD' => __( 'Liberian Dollar', 'invoicing' ),
        'LSL' => __( 'Lesotho Loti', 'invoicing' ),
        'LYD' => __( 'Libyan Dinar', 'invoicing' ),
        'MAD' => __( 'Moroccan Dirham', 'invoicing' ),
        'MDL' => __( 'Moldovan Leu', 'invoicing' ),
        'MGA' => __( 'Malagasy Ariary', 'invoicing' ),
        'MKD' => __( 'Macedonian Denar', 'invoicing' ),
        'MMK' => __( 'Burmese Kyat', 'invoicing' ),
        'MNT' => __( 'Mongolian Tughrik', 'invoicing' ),
        'MOP' => __( 'Macanese Pataca', 'invoicing' ),
        'MRO' => __( 'Mauritanian Ouguiya', 'invoicing' ),
        'MUR' => __( 'Mauritian Rupee', 'invoicing' ),
        'MVR' => __( 'Maldivian Rufiyaa', 'invoicing' ),
        'MWK' => __( 'Malawian Kwacha', 'invoicing' ),
        'MXN' => __( 'Mexican Peso', 'invoicing' ),
        'MYR' => __( 'Malaysian Ringgit', 'invoicing' ),
        'MZN' => __( 'Mozambican Metical', 'invoicing' ),
        'NAD' => __( 'Namibian Dollar', 'invoicing' ),
        'NGN' => __( 'Nigerian Naira', 'invoicing' ),
        'NIO' => __( 'Nicaraguan Cordoba', 'invoicing' ),
        'NOK' => __( 'Norwegian Krone', 'invoicing' ),
        'NPR' => __( 'Nepalese Rupee', 'invoicing' ),
        'NZD' => __( 'New Zealand Dollar', 'invoicing' ),
        'OMR' => __( 'Omani Rial', 'invoicing' ),
        'PAB' => __( 'Panamanian Balboa', 'invoicing' ),
        'PEN' => __( 'Peruvian Nuevo Sol', 'invoicing' ),
        'PGK' => __( 'Papua New Guinean Kina', 'invoicing' ),
        'PHP' => __( 'Philippine Peso', 'invoicing' ),
        'PKR' => __( 'Pakistani Rupee', 'invoicing' ),
        'PLN' => __( 'Polish Zloty', 'invoicing' ),
        'PRB' => __( 'Transnistrian Ruble', 'invoicing' ),
        'PYG' => __( 'Paraguayan Guarani', 'invoicing' ),
        'QAR' => __( 'Qatari Riyal', 'invoicing' ),
        'RON' => __( 'Romanian Leu', 'invoicing' ),
        'RSD' => __( 'Serbian Dinar', 'invoicing' ),
        'RUB' => __( 'Russian Ruble', 'invoicing' ),
        'RWF' => __( 'Rwandan Franc', 'invoicing' ),
        'SAR' => __( 'Saudi Riyal', 'invoicing' ),
        'SBD' => __( 'Solomon Islands Dollar', 'invoicing' ),
        'SCR' => __( 'Seychellois Rupee', 'invoicing' ),
        'SDG' => __( 'Sudanese Pound', 'invoicing' ),
        'SEK' => __( 'Swedish Krona', 'invoicing' ),
        'SGD' => __( 'Singapore Dollar', 'invoicing' ),
        'SHP' => __( 'Saint Helena Pound', 'invoicing' ),
        'SLL' => __( 'Sierra Leonean Leone', 'invoicing' ),
        'SOS' => __( 'Somali Shilling', 'invoicing' ),
        'SRD' => __( 'Surinamese Dollar', 'invoicing' ),
        'SSP' => __( 'South Sudanese Pound', 'invoicing' ),
        'STD' => __( 'Sao Tomean Dobra', 'invoicing' ),
        'SYP' => __( 'Syrian Pound', 'invoicing' ),
        'SZL' => __( 'Swazi Lilangeni', 'invoicing' ),
        'THB' => __( 'Thai Baht', 'invoicing' ),
        'TJS' => __( 'Tajikistani Somoni', 'invoicing' ),
        'TMT' => __( 'Turkmenistan Manat', 'invoicing' ),
        'TND' => __( 'Tunisian Dinar', 'invoicing' ),
        'TOP' => __( 'Tongan Pa&#x2bb;anga', 'invoicing' ),
        'TRY' => __( 'Turkish Lira', 'invoicing' ),
        'TTD' => __( 'Trinidad and Tobago Dollar', 'invoicing' ),
        'TWD' => __( 'New Taiwan Dollar', 'invoicing' ),
        'TZS' => __( 'Tanzanian Shilling', 'invoicing' ),
        'UAH' => __( 'Ukrainian Hryvnia', 'invoicing' ),
        'UGX' => __( 'Ugandan Shilling', 'invoicing' ),
        'UYU' => __( 'Uruguayan Peso', 'invoicing' ),
        'UZS' => __( 'Uzbekistani Som', 'invoicing' ),
        'VEF' => __( 'Venezuelan Bol&iacute;var', 'invoicing' ),
        'VND' => __( 'Vietnamese Dong', 'invoicing' ),
        'VUV' => __( 'Vanuatu Vatu', 'invoicing' ),
        'WST' => __( 'Samoan Tala', 'invoicing' ),
        'XAF' => __( 'Central African CFA Franc', 'invoicing' ),
        'XCD' => __( 'East Caribbean Dollar', 'invoicing' ),
        'XOF' => __( 'West African CFA Franc', 'invoicing' ),
        'XPF' => __( 'CFP Franc', 'invoicing' ),
        'YER' => __( 'Yemeni Rial', 'invoicing' ),
        'ZAR' => __( 'South African Rand', 'invoicing' ),
        'ZMW' => __( 'Zambian Kwacha', 'invoicing' ),
    );
    
    //asort( $currencies ); // this

    return apply_filters( 'wpinv_currencies', $currencies );
}

function wpinv_price( $amount = '', $currency = '' ) {
    if( empty( $currency ) ) {
        $currency = wpinv_get_currency();
    }

    $position = wpinv_currency_position();

    $negative = $amount < 0;

    if ( $negative ) {
        $amount = substr( $amount, 1 );
    }

    $symbol = wpinv_currency_symbol( $currency );

    if ( $position == 'left' || $position == 'left_space' ) {
        switch ( $currency ) {
            case "GBP" :
            case "BRL" :
            case "EUR" :
            case "USD" :
            case "AUD" :
            case "CAD" :
            case "HKD" :
            case "MXN" :
            case "NZD" :
            case "SGD" :
            case "JPY" :
                $price = $position == 'left_space' ? $symbol . ' ' .  $amount : $symbol . $amount;
                break;
            default :
                //$price = $currency . ' ' . $amount;
                $price = $position == 'left_space' ? $symbol . ' ' .  $amount : $symbol . $amount;
                break;
        }
    } else {
        switch ( $currency ) {
            case "GBP" :
            case "BRL" :
            case "EUR" :
            case "USD" :
            case "AUD" :
            case "CAD" :
            case "HKD" :
            case "MXN" :
            case "SGD" :
            case "JPY" :
                $price = $position == 'right_space' ? $amount . ' ' .  $symbol : $amount . $symbol;
                break;
            default :
                //$price = $amount . ' ' . $currency;
                $price = $position == 'right_space' ? $amount . ' ' .  $symbol : $amount . $symbol;
                break;
        }
    }
    
    if ( $negative ) {
        $price = '-' . $price;
    }
    
    $price = apply_filters( 'wpinv_' . strtolower( $currency ) . '_currency_filter_' . $position, $price, $currency, $amount );

    return $price;
}

function wpinv_format_amount( $amount, $decimals = NULL, $calculate = false ) {
    $thousands_sep = wpinv_thousands_separator();
    $decimal_sep   = wpinv_decimal_separator();

    if ( $decimals === NULL ) {
        $decimals = wpinv_decimals();
    }

    if ( $decimal_sep == ',' && false !== ( $sep_found = strpos( $amount, $decimal_sep ) ) ) {
        $whole = substr( $amount, 0, $sep_found );
        $part = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
        $amount = $whole . '.' . $part;
    }

    if ( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
        $amount = str_replace( ',', '', $amount );
    }

    if ( $thousands_sep == ' ' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
        $amount = str_replace( ' ', '', $amount );
    }

    if ( empty( $amount ) ) {
        $amount = 0;
    }
    
    $decimals  = apply_filters( 'wpinv_amount_format_decimals', $decimals ? $decimals : 0, $amount, $calculate );
    $formatted = number_format( (float)$amount, $decimals, $decimal_sep, $thousands_sep );
    
    if ( $calculate ) {
        if ( $thousands_sep === "," ) {
            $formatted = str_replace( ",", "", $formatted );
        }
        
        if ( $decimal_sep === "," ) {
            $formatted = str_replace( ",", ".", $formatted );
        }
    }

    return apply_filters( 'wpinv_amount_format', $formatted, $amount, $decimals, $decimal_sep, $thousands_sep, $calculate );
}
add_filter( 'wpinv_amount_format_decimals', 'wpinv_currency_decimal_filter', 10, 1 );

function wpinv_sanitize_key( $key ) {
    $raw_key = $key;
    $key = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

    return apply_filters( 'wpinv_sanitize_key', $key, $raw_key );
}

function wpinv_get_file_extension( $str ) {
    $parts = explode( '.', $str );
    return end( $parts );
}

function wpinv_string_is_image_url( $str ) {
    $ext = wpinv_get_file_extension( $str );

    switch ( strtolower( $ext ) ) {
        case 'jpeg';
        case 'jpg';
            $return = true;
            break;
        case 'png';
            $return = true;
            break;
        case 'gif';
            $return = true;
            break;
        default:
            $return = false;
            break;
    }

    return (bool)apply_filters( 'wpinv_string_is_image', $return, $str );
}

function wpinv_error_log( $log, $title = '', $file = '', $line = '', $exit = false ) {
    $should_log = apply_filters( 'wpinv_log_errors', WP_DEBUG );
    
    if ( true === $should_log ) {
        $label = '';
        if ( $file && $file !== '' ) {
            $label .= basename( $file ) . ( $line ? '(' . $line . ')' : '' );
        }
        
        if ( $title && $title !== '' ) {
            $label = $label !== '' ? $label . ' ' : '';
            $label .= $title . ' ';
        }
        
        $label = $label !== '' ? trim( $label ) . ' : ' : '';
        
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( $label . print_r( $log, true ) );
        } else {
            error_log( $label . $log );
        }
        
        if ( $exit ) {
            exit;
        }
    }
}

function wpinv_is_ajax_disabled() {
    $retval = false;
    return apply_filters( 'wpinv_is_ajax_disabled', $retval );
}

function wpinv_get_current_page_url( $nocache = false ) {
    global $wp;

    if ( get_option( 'permalink_structure' ) ) {
        $base = trailingslashit( home_url( $wp->request ) );
    } else {
        $base = add_query_arg( $wp->query_string, '', trailingslashit( home_url( $wp->request ) ) );
        $base = remove_query_arg( array( 'post_type', 'name' ), $base );
    }

    $scheme = is_ssl() ? 'https' : 'http';
    $uri    = set_url_scheme( $base, $scheme );

    if ( is_front_page() ) {
        $uri = home_url( '/' );
    } elseif ( wpinv_is_checkout( array(), false ) ) {
        $uri = wpinv_get_checkout_uri();
    }

    $uri = apply_filters( 'wpinv_get_current_page_url', $uri );

    if ( $nocache ) {
        $uri = wpinv_add_cache_busting( $uri );
    }

    return $uri;
}

function wpinv_get_php_arg_separator_output() {
	return ini_get( 'arg_separator.output' );
}

function wpinv_rgb_from_hex( $color ) {
    $color = str_replace( '#', '', $color );
    // Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
    $color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

    $rgb      = array();
    $rgb['R'] = hexdec( $color{0}.$color{1} );
    $rgb['G'] = hexdec( $color{2}.$color{3} );
    $rgb['B'] = hexdec( $color{4}.$color{5} );

    return $rgb;
}

function wpinv_hex_darker( $color, $factor = 30 ) {
    $base  = wpinv_rgb_from_hex( $color );
    $color = '#';

    foreach ( $base as $k => $v ) {
        $amount      = $v / 100;
        $amount      = round( $amount * $factor );
        $new_decimal = $v - $amount;

        $new_hex_component = dechex( $new_decimal );
        if ( strlen( $new_hex_component ) < 2 ) {
            $new_hex_component = "0" . $new_hex_component;
        }
        $color .= $new_hex_component;
    }

    return $color;
}

function wpinv_hex_lighter( $color, $factor = 30 ) {
    $base  = wpinv_rgb_from_hex( $color );
    $color = '#';

    foreach ( $base as $k => $v ) {
        $amount      = 255 - $v;
        $amount      = $amount / 100;
        $amount      = round( $amount * $factor );
        $new_decimal = $v + $amount;

        $new_hex_component = dechex( $new_decimal );
        if ( strlen( $new_hex_component ) < 2 ) {
            $new_hex_component = "0" . $new_hex_component;
        }
        $color .= $new_hex_component;
    }

    return $color;
}

function wpinv_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
    $hex = str_replace( '#', '', $color );

    $c_r = hexdec( substr( $hex, 0, 2 ) );
    $c_g = hexdec( substr( $hex, 2, 2 ) );
    $c_b = hexdec( substr( $hex, 4, 2 ) );

    $brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

    return $brightness > 155 ? $dark : $light;
}

function wpinv_format_hex( $hex ) {
    $hex = trim( str_replace( '#', '', $hex ) );

    if ( strlen( $hex ) == 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return $hex ? '#' . $hex : null;
}

/**
 * Get truncated string with specified width.
 *
 * @since 1.0.0
 *
 * @param string $str The string being decoded.
 * @param int $start The start position offset. Number of characters from the beginning of string.
 *                      For negative value, number of characters from the end of the string.
 * @param int $width The width of the desired trim. Negative widths count from the end of the string.
 * @param string $trimmaker A string that is added to the end of string when string is truncated. Ex: "...".
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return string
 */
function wpinv_utf8_strimwidth( $str, $start, $width, $trimmaker = '', $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_strimwidth' ) ) {
        return mb_strimwidth( $str, $start, $width, $trimmaker, $encoding );
    }
    
    return wpinv_utf8_substr( $str, $start, $width, $encoding ) . $trimmaker;
}

/**
 * Get the string length.
 *
 * @since 1.0.0
 *
 * @param string $str The string being checked for length. 
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return int Returns the number of characters in string.
 */
function wpinv_utf8_strlen( $str, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_strlen' ) ) {
        return mb_strlen( $str, $encoding );
    }
        
    return strlen( $str );
}

function wpinv_utf8_strtolower( $str, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_strtolower' ) ) {
        return mb_strtolower( $str, $encoding );
    }
    
    return strtolower( $str );
}

function wpinv_utf8_strtoupper( $str, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_strtoupper' ) ) {
        return mb_strtoupper( $str, $encoding );
    }
    
    return strtoupper( $str );
}

/**
 * Find position of first occurrence of string in a string
 *
 * @since 1.0.0
 *
 * @param string $str The string being checked.
 * @param string $find The string to find in input string.
 * @param int $offset The search offset. Default "0". A negative offset counts from the end of the string.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return int Returns the position of the first occurrence of search in the string.
 */
function wpinv_utf8_strpos( $str, $find, $offset = 0, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_strpos' ) ) {
        return mb_strpos( $str, $find, $offset, $encoding );
    }
        
    return strpos( $str, $find, $offset );
}

/**
 * Find position of last occurrence of a string in a string.
 *
 * @since 1.0.0
 *
 * @param string $str The string being checked, for the last occurrence of search.
 * @param string $find The string to find in input string.
 * @param int $offset Specifies begin searching an arbitrary number of characters into the string.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return int Returns the position of the last occurrence of search.
 */
function wpinv_utf8_strrpos( $str, $find, $offset = 0, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_strrpos' ) ) {
        return mb_strrpos( $str, $find, $offset, $encoding );
    }
        
    return strrpos( $str, $find, $offset );
}

/**
 * Get the part of string.
 *
 * @since 1.0.0
 *
 * @param string $str The string to extract the substring from.
 * @param int $start If start is non-negative, the returned string will start at the entered position in string, counting from zero.
 *                      If start is negative, the returned string will start at the entered position from the end of string. 
 * @param int|null $length Maximum number of characters to use from string.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return string
 */
function wpinv_utf8_substr( $str, $start, $length = null, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_substr' ) ) {
        if ( $length === null ) {
            return mb_substr( $str, $start, wpinv_utf8_strlen( $str, $encoding ), $encoding );
        } else {
            return mb_substr( $str, $start, $length, $encoding );
        }
    }
        
    return substr( $str, $start, $length );
}

/**
 * Get the width of string.
 *
 * @since 1.0.0
 *
 * @param string $str The string being decoded.
 * @param string $encoding The encoding parameter is the character encoding. Default "UTF-8".
 * @return string The width of string.
 */
function wpinv_utf8_strwidth( $str, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_strwidth' ) ) {
        return mb_strwidth( $str, $encoding );
    }
    
    return wpinv_utf8_strlen( $str, $encoding );
}

function wpinv_utf8_ucfirst( $str, $lower_str_end = false, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_strlen' ) ) {
        $first_letter = wpinv_utf8_strtoupper( wpinv_utf8_substr( $str, 0, 1, $encoding ), $encoding );
        $str_end = "";
        
        if ( $lower_str_end ) {
            $str_end = wpinv_utf8_strtolower( wpinv_utf8_substr( $str, 1, wpinv_utf8_strlen( $str, $encoding ), $encoding ), $encoding );
        } else {
            $str_end = wpinv_utf8_substr( $str, 1, wpinv_utf8_strlen( $str, $encoding ), $encoding );
        }

        return $first_letter . $str_end;
    }
    
    return ucfirst( $str );
}

function wpinv_utf8_ucwords( $str, $encoding = 'UTF-8' ) {
    if ( function_exists( 'mb_convert_case' ) ) {
        return mb_convert_case( $str, MB_CASE_TITLE, $encoding );
    }
    
    return ucwords( $str );
}

function wpinv_period_in_days( $period, $unit ) {
    $period = absint( $period );
    
    if ( $period > 0 ) {
        if ( in_array( strtolower( $unit ), array( 'w', 'week', 'weeks' ) ) ) {
            $period = $period * 7;
        } else if ( in_array( strtolower( $unit ), array( 'm', 'month', 'months' ) ) ) {
            $period = $period * 30;
        } else if ( in_array( strtolower( $unit ), array( 'y', 'year', 'years' ) ) ) {
            $period = $period * 365;
        }
    }
    
    return $period;
}

function wpinv_cal_days_in_month( $calendar, $month, $year ) {
    if ( function_exists( 'cal_days_in_month' ) ) {
        return cal_days_in_month( $calendar, $month, $year );
    }

    // Fallback in case the calendar extension is not loaded in PHP
    // Only supports Gregorian calendar
    return date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
}

/**
 * Display a help tip for settings.
 *
 * @param  string $tip Help tip text
 * @param  bool $allow_html Allow sanitized HTML if true or escape
 *
 * @return string
 */
function wpi_help_tip( $tip, $allow_html = false ) {
    if ( $allow_html ) {
        $tip = wpi_sanitize_tooltip( $tip );
    } else {
        $tip = esc_attr( $tip );
    }

    return '<span class="wpi-help-tip dashicons dashicons-editor-help" title="' . $tip . '"></span>';
}

/**
 * Sanitize a string destined to be a tooltip.
 *
 * Tooltips are encoded with htmlspecialchars to prevent XSS. Should not be used in conjunction with esc_attr()
 *
 * @param string $var
 * @return string
 */
function wpi_sanitize_tooltip( $var ) {
    return htmlspecialchars( wp_kses( html_entity_decode( $var ), array(
        'br'     => array(),
        'em'     => array(),
        'strong' => array(),
        'small'  => array(),
        'span'   => array(),
        'ul'     => array(),
        'li'     => array(),
        'ol'     => array(),
        'p'      => array(),
    ) ) );
}

/**
 * Get all WPI screen ids.
 *
 * @return array
 */
function wpinv_get_screen_ids() {

    $screen_id = sanitize_title( __( 'Invoicing', 'invoicing' ) );

    $screen_ids = array(
        'toplevel_page_' . $screen_id,
        'wpi_invoice',
        'wpi_item',
        'wpi_quote',
        'wpi_discount',
        'edit-wpi_invoice',
        'edit-wpi_item',
        'edit-wpi_discount',
        'edit-wpi_quote',
        'invoicing_page_wpinv-settings',
        'invoicing_page_wpinv-subscriptions',
        'invoicing_page_wpinv-reports',
        'invoicing_page_wpi-addons',
    );

    return apply_filters( 'wpinv_screen_ids', $screen_ids );
}

/**
 * Cleans up an array, comma- or space-separated list of scalar values.
 *
 * @since 1.0.13
 *
 * @param array|string $list List of values.
 * @return array Sanitized array of values.
 */
function wpinv_parse_list( $list ) {
	if ( ! is_array( $list ) ) {
		return preg_split( '/[\s,]+/', $list, -1, PREG_SPLIT_NO_EMPTY );
	}

	return $list;
}

/**
 * Fetches data stored on disk.
 *
 * @since 1.0.14
 *
 * @param string $key Type of data to fetch.
 * @return mixed Fetched data.
 */
function wpinv_get_data( $key ) {
    
    // Try fetching it from the cache.
    $data = wp_cache_get( "wpinv-$key", 'wpinv' );
    if( $data ) {
        return $data;
    }

    $data = apply_filters( "wpinv_get_$key", include WPINV_PLUGIN_DIR . "includes/data/$key.php" );
	wp_cache_set( "wpinv-$key", $data, 'wpinv' );

	return $data;
}

/**
 * (Maybe) Adds an empty option to an array of options.
 *
 * @since 1.0.14
 *
 * @param array $options
 * @param bool $first_empty Whether or not the first item in the list should be empty
 * @return mixed Fetched data.
 */
function wpinv_maybe_add_empty_option( $options, $first_empty ) {

    if ( ! empty( $options ) && $first_empty ) {
        return array_merge( array( '' => '' ), $options );
    }
    return $options;

}

/**
 * Clean variables using sanitize_text_field.
 *
 * @param mixed $var Data to sanitize.
 * @return string|array
 */
function wpinv_clean( $var ) {

	if ( is_array( $var ) ) {
		return array_map( 'wpinv_clean', $var );
    }

    if ( is_object( $var ) ) {
		$object_vars = get_object_vars( $var );
		foreach ( $object_vars as $property_name => $property_value ) {
			$var->$property_name = wpinv_clean( $property_value );
        }
        return $var;
	}
    
    return is_string( $var ) ? sanitize_text_field( $var ) : $var;
}