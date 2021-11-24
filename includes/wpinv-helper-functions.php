<?php
/**
 * Contains helper functions.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
defined( 'ABSPATH' ) || exit;

/**
 * Are we supporting item quantities?
 */
function wpinv_item_quantities_enabled() {
    return true;
}

/**
 * Returns the user's ip address.
 */
function wpinv_get_ip() {

    if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
        return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
    }

    if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        // Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
        // Make sure we always only send through the first IP in the list which should always be the client IP.
        return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
    }

    if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
    }

    if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
        return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
    }

    return '';
}

function wpinv_get_user_agent() {
    if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
        $user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );
    } else {
        $user_agent = '';
    }

    return apply_filters( 'wpinv_get_user_agent', $user_agent );
}

/**
 * Standardizes an amount for insterting into the database.
 *
 * @param string $amount The amount to sanitize.
 * @return float
 */
function getpaid_standardize_amount( $amount ) {

    $amount = str_replace( wpinv_thousands_separator(), '', $amount );
    $amount = str_replace( wpinv_decimal_separator(), '.', $amount );
    if ( is_numeric( $amount ) ) {
        return floatval( $amount );
    }

    // Cast the remaining to a float.
    return wpinv_round_amount( preg_replace( '/[^0-9\.\-]/', '', $amount ) );

}

/**
 * Standardizes an amount that has been retrieved from the database.
 *
 * @param string $amount The amount to sanitize.
 */
function getpaid_unstandardize_amount( $amount ) {
    return str_replace( '.', wpinv_decimal_separator(), $amount );
}

/**
 * Sanitizes an amount.
 * 
 * @param string $amount The amount to sanitize.
 */
function wpinv_sanitize_amount( $amount ) {

    if ( is_numeric( $amount ) ) {
        return floatval( $amount );
    }

    // Separate the decimals and thousands.
    $amount    = explode( wpinv_decimal_separator(), $amount );

    // Remove thousands.
    $amount[0] = str_replace( wpinv_thousands_separator(), '', $amount[0] );

    // Convert back to string.
    $amount = count( $amount ) > 1 ? "{$amount[0]}.{$amount[1]}" : $amount[0];

    // Cast the remaining to a float.
    return (float) preg_replace( '/[^0-9\.\-]/', '', $amount );

}

/**
 * Rounds an amount.
 * 
 * @param float $amount
 * @param float|string|int|null $decimals
 */
function wpinv_round_amount( $amount, $decimals = null, $use_sprintf = false ) {

    if ( $decimals === null ) {
        $decimals = wpinv_decimals();
    }

    if ( $use_sprintf ) {
        $amount = sprintf( "%.{$decimals}f", (float) $amount );
    } else {
        $amount = round( (float) $amount, absint( $decimals ) );
    }

    return apply_filters( 'wpinv_round_amount', $amount, $decimals );
}

/**
 * Get all invoice statuses.
 *
 * @since 1.0.19
 * @param bool $draft Whether or not to include the draft status.
 * @param bool $trashed Whether or not to include the trash status.
 * @param string|WPInv_Invoice $invoice The invoice object|post type|type
 * @return array
 */
function wpinv_get_invoice_statuses( $draft = false, $trashed = false, $invoice = false ) {

	$invoice_statuses = array(
		'wpi-pending'    => _x( 'Pending payment', 'Invoice status', 'invoicing' ),
        'publish'        => _x( 'Paid', 'Invoice status', 'invoicing' ),
        'wpi-processing' => _x( 'Processing', 'Invoice status', 'invoicing' ),
		'wpi-onhold'     => _x( 'On hold', 'Invoice status', 'invoicing' ),
		'wpi-cancelled'  => _x( 'Cancelled', 'Invoice status', 'invoicing' ),
		'wpi-refunded'   => _x( 'Refunded', 'Invoice status', 'invoicing' ),
        'wpi-failed'     => _x( 'Failed', 'Invoice status', 'invoicing' ),
        'wpi-renewal'    => _x( 'Renewal Payment', 'Invoice status', 'invoicing' ),
    );

    if ( $draft ) {
        $invoice_statuses['draft'] = __( 'Draft', 'invoicing' );
    }

    if ( $trashed ) {
        $invoice_statuses['trash'] = __( 'Trash', 'invoicing' );
    }

    if ( $invoice instanceof WPInv_Invoice ) {
        $invoice = $invoice->get_post_type();
    }

	return apply_filters( 'wpinv_statuses', $invoice_statuses, $invoice );
}

/**
 * Returns the formated invoice status.
 * 
 * @param string $status The raw status
 * @param string|WPInv_Invoice $invoice The invoice object|post type|type
 */
function wpinv_status_nicename( $status, $invoice = false ) {
    $statuses = wpinv_get_invoice_statuses( true, true, $invoice );
    $status   = isset( $statuses[$status] ) ? $statuses[$status] : $status;

    return sanitize_text_field( $status );
}

/**
 * Retrieves the default currency code.
 * 
 * @param string $current
 */
function wpinv_get_currency( $current = '' ) {

    if ( empty( $current ) ) {
        $current = apply_filters( 'wpinv_currency', wpinv_get_option( 'currency', 'USD' ) );
    }

    return trim( strtoupper( $current ) );
}

/**
 * Given a currency, it returns a currency symbol.
 * 
 * @param string|null $currency The currency code. Defaults to the default currency.
 */
function wpinv_currency_symbol( $currency = null ) {

    // Prepare the currency.
    $currency = empty( $currency ) ? wpinv_get_currency() : wpinv_clean( $currency );

    // Fetch all symbols.
    $symbols = wpinv_get_currency_symbols();

    // Fetch this currencies symbol.
    $currency_symbol = isset( $symbols[$currency] ) ? $symbols[$currency] : $currency;

    // Filter the symbol.
    return apply_filters( 'wpinv_currency_symbol', $currency_symbol, $currency );
}

function wpinv_currency_position() {
    $position = wpinv_get_option( 'currency_position', 'left' );
    
    return apply_filters( 'wpinv_currency_position', $position );
}

/**
 * Returns the thousands separator for a currency.
 * 
 * @param $string|null $current
 */
function wpinv_thousands_separator( $current = null ) {

    if ( null == $current ) {
        $current = wpinv_get_option( 'thousands_separator', ',' );
    }

    return trim( $current );
}

/**
 * Returns the decimal separator for a currency.
 * 
 * @param $string|null $current
 */
function wpinv_decimal_separator( $current = null ) {

    if ( null == $current ) {
        $current = wpinv_get_option( 'decimal_separator', '.' );
    }
    
    return trim( $current );
}

/**
 * Returns the number of decimals to use.
 * 
 * @param $string|null $current
 */
function wpinv_decimals( $current = null ) {

    if ( null == $current ) {
        $current = wpinv_get_option( 'decimals', 2 );
    }
    
    return absint( $current );
}

/**
 * Retrieves a list of all supported currencies.
 */
function wpinv_get_currencies() {
    return apply_filters( 'wpinv_currencies', wpinv_get_data( 'currencies' ) );
}

/**
 * Retrieves a list of all currency symbols.
 */
function wpinv_get_currency_symbols() {
    return apply_filters( 'wpinv_currency_symbols', wpinv_get_data( 'currency-symbols' ) );
}

/**
 * Get the price format depending on the currency position.
 *
 * @return string
 */
function getpaid_get_price_format() {
	$currency_pos = wpinv_currency_position();
	$format       = '%1$s%2$s';

	switch ( $currency_pos ) {
		case 'left':
			$format = '%1$s%2$s';
			break;
		case 'right':
			$format = '%2$s%1$s';
			break;
		case 'left_space':
			$format = '%1$s&nbsp;%2$s';
			break;
		case 'right_space':
			$format = '%2$s&nbsp;%1$s';
			break;
	}

	return apply_filters( 'getpaid_price_format', $format, $currency_pos );
}

/**
 * Format the amount with a currency symbol.
 *
 * @param  float  $amount Raw price.
 * @param  string $currency Currency.
 * @return string
 */
function wpinv_price( $amount = 0, $currency = '' ) {

    // Backwards compatibility.
    $amount             = wpinv_sanitize_amount( $amount );

    // Prepare variables.
    $currency           = wpinv_get_currency( $currency );
    $amount             = (float) $amount;
    $unformatted_amount = $amount;
    $negative           = $amount < 0;
    $amount             = apply_filters( 'getpaid_raw_amount', floatval( $negative ? $amount * -1 : $amount ) );
    $amount             = wpinv_format_amount( $amount );

    // Format the amount.
    $format             = getpaid_get_price_format();
    $formatted_amount   = ( $negative ? '-' : '' ) . sprintf( $format, '<span class="getpaid-currency__symbol">' . wpinv_currency_symbol( $currency ) . '</span>', $amount );

    // Filter the formatting.
    return apply_filters( 'wpinv_price', $formatted_amount, $amount, $currency, $unformatted_amount );
}

/**
 * Format an amount with separators.
 *
 * @param  float    $amount Raw amount.
 * @param  null|int $decimals Number of decimals to use.
 * @param  bool     $calculate Whether or not to apply separators.
 * @return string
 */
function wpinv_format_amount( $amount, $decimals = null, $calculate = false ) {
    $thousands_sep = wpinv_thousands_separator();
    $decimal_sep   = wpinv_decimal_separator();
    $decimals      = wpinv_decimals( $decimals );
    $amount        = wpinv_sanitize_amount( $amount );

    if ( $calculate ) {
        return $amount;
    }

    // Fomart the amount.
    return number_format( $amount, $decimals, $decimal_sep, $thousands_sep );
}

function wpinv_sanitize_key( $key ) {
    $raw_key = $key;
    $key = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

    return apply_filters( 'wpinv_sanitize_key', $key, $raw_key );
}

/**
 * Returns a file extesion.
 * 
 * @param $str the file whose extension should be retrieved.
 */
function wpinv_get_file_extension( $str ) {
    $filetype = wp_check_filetype( $str );
    return $filetype['ext'];
}

/**
 * Checks if a given string is an image URL.
 * 
 * @param string $string
 */
function wpinv_string_is_image_url( $string ) {
    $extension = strtolower( wpinv_get_file_extension( $string ) );
    return in_array( $extension, array( 'jpeg', 'jpg', 'png', 'gif', 'ico' ), true );
}

/**
 * Returns the current URL.
 */
function wpinv_get_current_page_url() {
    return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Define a constant if it is not already defined.
 *
 * @since 1.0.19
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function getpaid_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

function wpinv_get_php_arg_separator_output() {
	return ini_get( 'arg_separator.output' );
}

function wpinv_rgb_from_hex( $color ) {
    $color = str_replace( '#', '', $color );

    // Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
    $color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );
    if ( empty( $color ) ) {
        return NULL;
    }

    $color = str_split( $color );

    $rgb      = array();
    $rgb['R'] = hexdec( $color[0] . $color[1] );
    $rgb['G'] = hexdec( $color[2] . $color[3] );
    $rgb['B'] = hexdec( $color[4] . $color[5] );

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
function wpi_help_tip( $tip, $allow_html = false, $is_vue = false ) {

    if ( $allow_html ) {
        $tip = wpi_sanitize_tooltip( $tip );
    } else {
        $tip = esc_attr( $tip );
    }

    if ( $is_vue ) {
        return '<span class="dashicons dashicons-editor-help" title="' . $tip . '"></span>';
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
    return wp_kses( html_entity_decode( $var ), array(
        'br'     => array(),
        'em'     => array(),
        'strong' => array(),
        'b'      => array(),
        'small'  => array(),
        'span'   => array(),
        'ul'     => array(),
        'li'     => array(),
        'ol'     => array(),
        'p'      => array(),
    ) );
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
        'wpi_payment_form',
        'edit-wpi_invoice',
        'edit-wpi_item',
        'edit-wpi_discount',
        'edit-wpi_quote',
        'edit-wpi_payment_form',
        'getpaid_page_wpinv-settings',
        'getpaid_page_wpinv-subscriptions',
        'getpaid_page_wpinv-reports',
        'getpaid_page_wpi-addons',
        'getpaid_page_wpinv-customers',
        'gp-setup',// setup wizard
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

    if ( empty( $list ) ) {
        $list = array();
    }

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
    $data = wp_cache_get( "wpinv-data-$key", 'wpinv' );
    if( $data ) {
        return $data;
    }

    $data = apply_filters( "wpinv_get_$key", include WPINV_PLUGIN_DIR . "includes/data/$key.php" );
	wp_cache_set( "wpinv-data-$key", $data, 'wpinv' );

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

    return is_string( $var ) ? sanitize_text_field( stripslashes( $var ) ) : $var;
}

/**
 * Converts a price string into an options array.
 *
 * @param string $str Data to convert.
 * @return string|array
 */
function getpaid_convert_price_string_to_options( $str ) {

	$raw_options = array_map( 'trim', explode( ',', $str ) );
    $options     = array();

    foreach ( $raw_options as $option ) {

        if ( '' == $option ) {
            continue;
        }

        $option = array_map( 'trim', explode( '|', $option ) );

        $price = null;
        $label = null;

        if ( isset( $option[0] ) && '' !=  $option[0] ) {
            $label  = $option[0];
        }

        if ( isset( $option[1] ) && '' !=  $option[1] ) {
            $price = $option[1];
        }

        if ( ! isset( $price ) ) {
            $price = $label;
        }

        if ( ! isset( $price ) || ! is_numeric( $price ) ) {
            continue;
        }

        if ( ! isset( $label ) ) {
            $label = $price;
        }

        $options[ "$label|$price" ] = $label;
    }

    return $options;
}

/**
 * Returns the help tip.
 */
function getpaid_get_help_tip( $tip, $additional_classes = '' ) {
    $additional_classes = sanitize_html_class( $additional_classes );
    $tip                = esc_attr__( $tip );
    return "<span class='wpi-help-tip dashicons dashicons-editor-help $additional_classes' title='$tip'></span>";
}

/**
 * Formats a date
 */
function getpaid_format_date( $date, $with_time = false ) {

    if ( empty( $date ) || $date == '0000-00-00 00:00:00' ) {
        return '';
    }

    $format = getpaid_date_format();

    if ( $with_time ) {
        $format .= ' ' . getpaid_time_format();
    }
    return date_i18n( $format, strtotime( $date ) );

}

/**
 * Formats a date into the website's date setting.
 *
 * @return string
 */
function getpaid_format_date_value( $date, $default = "&mdash;", $with_time = false ) {
    $date = getpaid_format_date( $date, $with_time );
    return empty( $date ) ? $default : $date;
}

/**
 * Get the date format used all over the plugin.
 *
 * @return string
 */
function getpaid_date_format() {
	return apply_filters( 'getpaid_date_format', get_option( 'date_format' ) );
}

/**
 * Get the time format used all over the plugin.
 *
 * @return string
 */
function getpaid_time_format() {
	return apply_filters( 'getpaid_time_format', get_option( 'time_format' ) );
}

/**
 * Limit length of a string.
 *
 * @param  string  $string string to limit.
 * @param  integer $limit Limit size in characters.
 * @return string
 */
function getpaid_limit_length( $string, $limit ) {
    $str_limit = $limit - 3;

	if ( function_exists( 'mb_strimwidth' ) ) {
		if ( mb_strlen( $string ) > $limit ) {
			$string = mb_strimwidth( $string, 0, $str_limit ) . '...';
		}
	} else {
		if ( strlen( $string ) > $limit ) {
			$string = substr( $string, 0, $str_limit ) . '...';
		}
	}
    return $string;

}

/**
 * Returns the REST API handler.
 * 
 * @return WPInv_API
 * @since 1.0.19
 */
function getpaid_api() {
    return getpaid()->get( 'api' );
}

/**
 * Returns the post types object.
 * 
 * @return GetPaid_Post_Types
 * @since 1.0.19
 */
function getpaid_post_types() {
    return getpaid()->get( 'post_types' );
}

/**
 * Returns the session handler.
 * 
 * @return WPInv_Session_Handler
 * @since 1.0.19
 */
function getpaid_session() {
    return getpaid()->get( 'session' );
}

/**
 * Returns the notes handler.
 * 
 * @return WPInv_Notes
 * @since 1.0.19
 */
function getpaid_notes() {
    return getpaid()->get( 'notes' );
}

/**
 * Returns the main admin class.
 * 
 * @return GetPaid_Admin
 */
function getpaid_admin() {
    return getpaid()->get( 'admin' );
}

/**
 * Retrieves a URL to an authenticated action
 *
 * @param string $action
 * @param string $base the base url
 * @return string
 */
function getpaid_get_authenticated_action_url( $action, $base = false ) {
    return wp_nonce_url( add_query_arg( 'getpaid-action', $action, $base ), 'getpaid-nonce', 'getpaid-nonce' );
}

/**
 * Returns a post type label.
 *
 * @return string
 */
function getpaid_get_post_type_label( $post_type, $plural = true ) {

    $post_type = get_post_type_object( $post_type );

    if ( ! is_object( $post_type ) ) {
        return null;
    }

    return $plural ? $post_type->labels->name : $post_type->labels->singular_name;

}

/**
 * Retrieves an array
 *
 * @return mixed|null
 */
function getpaid_get_array_field( $array, $key, $secondary_key = null ) {

    if ( ! is_array( $array ) ) {
        return null;
    }

    if ( ! empty( $secondary_key ) ) {
        $array = isset( $array[ $secondary_key ] ) ? $array[ $secondary_key ] : array();
        return getpaid_get_array_field( $array, $key );
    }

    return isset( $array[ $key ] ) ? $array[ $key ] : null;

}

/**
 * Merges an empty array
 *
 * @return array
 */
function getpaid_array_merge_if_empty( $args, $defaults ) {

    foreach ( $defaults as $key => $value ) {

        if ( array_key_exists( $key, $args ) && empty( $args[ $key ] ) ) {
            $args[ $key ] = $value;
        }

    }

    return $args;

}

/**
 * Returns allowed file types.
 *
 * @return array
 */
function getpaid_get_allowed_mime_types() {

    $types = get_allowed_mime_types();

    if ( isset( $types['htm|html'] ) ) {
		unset( $types['htm|html'] );
	}

    if ( isset( $types['js'] ) ) {
		unset( $types['js'] );
	}

    return $types;

}
