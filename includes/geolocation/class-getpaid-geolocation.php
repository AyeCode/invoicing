<?php
/**
 * Geolocation class
 *
 * Handles geolocation of IP Addresses.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Geolocation Class.
 */
class GetPaid_Geolocation {

	/**
	 * Holds the current user's IP Address.
	 *
	 * @var string
	 */
	public static $current_user_ip;

	/**
	 * API endpoints for looking up a user IP address.
	 *
	 * For example, in case a user is on localhost.
	 *
	 * @var array
	 */
	protected static $ip_lookup_apis = array(
		'ipify'             => 'http://api.ipify.org/',
		'ipecho'            => 'http://ipecho.net/plain',
		'ident'             => 'http://ident.me',
		'whatismyipaddress' => 'http://bot.whatismyipaddress.com',
	);

	/**
	 * API endpoints for geolocating an IP address
	 *
	 * @var array
	 */
	protected static $geoip_apis = array(
		'ip-api.com' => 'http://ip-api.com/json/%s',
		'ipinfo.io'  => 'https://ipinfo.io/%s/json',
	);

	/**
	 * Get current user IP Address.
	 *
	 * @return string
	 */
	public static function get_ip_address() {
		return wpinv_get_ip();
	}

	/**
	 * Get user IP Address using an external service.
	 * This can be used as a fallback for users on localhost where
	 * get_ip_address() will be a local IP and non-geolocatable.
	 *
	 * @return string
	 */
	public static function get_external_ip_address() {

		$transient_name = 'external_ip_address_0.0.0.0';

		if ( '' !== self::get_ip_address() ) {
			$transient_name      = 'external_ip_address_' . self::get_ip_address();
		}

		// Try retrieving from cache.
		$external_ip_address = get_transient( $transient_name );

		if ( false === $external_ip_address ) {
			$external_ip_address     = '0.0.0.0';
			$ip_lookup_services      = apply_filters( 'getpaid_geolocation_ip_lookup_apis', self::$ip_lookup_apis );
			$ip_lookup_services_keys = array_keys( $ip_lookup_services );
			shuffle( $ip_lookup_services_keys );

			foreach ( $ip_lookup_services_keys as $service_name ) {
				$service_endpoint = $ip_lookup_services[ $service_name ];
				$response         = wp_safe_remote_get( $service_endpoint, array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && rest_is_ip_address( $response['body'] ) ) {
					$external_ip_address = apply_filters( 'getpaid_geolocation_ip_lookup_api_response', wpinv_clean( $response['body'] ), $service_name );
					break;
				}

			}

			set_transient( $transient_name, $external_ip_address, WEEK_IN_SECONDS );
		}

		return $external_ip_address;
	}

	/**
	 * Geolocate an IP address.
	 *
	 * @param  string $ip_address   IP Address.
	 * @param  bool   $fallback     If true, fallbacks to alternative IP detection (can be slower).
	 * @param  bool   $api_fallback If true, uses geolocation APIs if the database file doesn't exist (can be slower).
	 * @return array
	 */
	public static function geolocate_ip( $ip_address = '', $fallback = false, $api_fallback = true ) {

		if ( empty( $ip_address ) ) {
			$ip_address = self::get_ip_address();
		}

		// Update the current user's IP Address.
		self::$current_user_ip = $ip_address;

		// Filter to allow custom geolocation of the IP address.
		$country_code = apply_filters( 'getpaid_geolocate_ip', false, $ip_address, $fallback, $api_fallback );

		if ( false !== $country_code ) {

			return array(
				'country'  => $country_code,
				'state'    => '',
				'city'     => '',
				'postcode' => '',
			);

		}

		$country_code = self::get_country_code_from_headers();

		/**
		 * Get geolocation filter.
		 *
		 * @since 1.0.19
		 * @param array  $geolocation Geolocation data, including country, state, city, and postcode.
		 * @param string $ip_address  IP Address.
		 */
		$geolocation  = apply_filters(
			'getpaid_get_geolocation',
			array(
				'country'  => $country_code,
				'state'    => '',
				'city'     => '',
				'postcode' => '',
			),
			$ip_address
		);

		// If we still haven't found a country code, let's consider doing an API lookup.
		if ( '' === $geolocation['country'] && $api_fallback ) {
			$geolocation['country'] = self::geolocate_via_api( $ip_address );
		}

		// It's possible that we're in a local environment, in which case the geolocation needs to be done from the
		// external address.
		if ( '' === $geolocation['country'] && $fallback ) {
			$external_ip_address = self::get_external_ip_address();

			// Only bother with this if the external IP differs.
			if ( '0.0.0.0' !== $external_ip_address && $external_ip_address !== $ip_address ) {
				return self::geolocate_ip( $external_ip_address, false, $api_fallback );
			}

		}

		return array(
			'country'  => $geolocation['country'],
			'state'    => $geolocation['state'],
			'city'     => $geolocation['city'],
			'postcode' => $geolocation['postcode'],
		);

	}

	/**
	 * Fetches the country code from the request headers, if one is available.
	 *
	 * @since 1.0.19
	 * @return string The country code pulled from the headers, or empty string if one was not found.
	 */
	protected static function get_country_code_from_headers() {
		$country_code = '';

		$headers = array(
			'MM_COUNTRY_CODE',
			'GEOIP_COUNTRY_CODE',
			'HTTP_CF_IPCOUNTRY',
			'HTTP_X_COUNTRY_CODE',
		);

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$country_code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
			break;
		}

		return $country_code;
	}

	/**
	 * Use APIs to Geolocate the user.
	 *
	 * Geolocation APIs can be added through the use of the getpaid_geolocation_geoip_apis filter.
	 * Provide a name=>value pair for service-slug=>endpoint.
	 *
	 * If APIs are defined, one will be chosen at random to fulfil the request. After completing, the result
	 * will be cached in a transient.
	 *
	 * @param  string $ip_address IP address.
	 * @return string
	 */
	protected static function geolocate_via_api( $ip_address ) {

		// Retrieve from cache...
		$country_code = get_transient( 'geoip_' . $ip_address );

		// If missing, retrieve from the API.
		if ( false === $country_code ) {
			$geoip_services = apply_filters( 'getpaid_geolocation_geoip_apis', self::$geoip_apis );

			if ( empty( $geoip_services ) ) {
				return '';
			}

			$geoip_services_keys = array_keys( $geoip_services );

			shuffle( $geoip_services_keys );

			foreach ( $geoip_services_keys as $service_name ) {

				$service_endpoint = $geoip_services[ $service_name ];
				$response         = wp_safe_remote_get( sprintf( $service_endpoint, $ip_address ), array( 'timeout' => 2 ) );
				$country_code     = sanitize_text_field( strtoupper( self::handle_geolocation_response( $response, $service_name ) ) );

				if ( ! empty( $country_code ) ) {
					break;
				}

			}

			set_transient( 'geoip_' . $ip_address, $country_code, WEEK_IN_SECONDS );
		}

		return $country_code;
	}

	/**
	 * Handles geolocation response
	 *
	 * @param  WP_Error|String $geolocation_response
	 * @param  String $geolocation_service
	 * @return string Country code
	 */
	protected static function handle_geolocation_response( $geolocation_response, $geolocation_service ) {

		if ( is_wp_error( $geolocation_response ) || empty( $geolocation_response['body'] ) ) {
			return '';
		}

		if ( $geolocation_service === 'ipinfo.io' ) {
			$data = json_decode( $geolocation_response['body'] );
			return empty( $data ) || empty( $data->country ) ? '' : $data->country;
		}

		if ( $geolocation_service === 'ip-api.com' ) {
			$data = json_decode( $geolocation_response['body'] );
			return empty( $data ) || empty( $data->countryCode ) ? '' : $data->countryCode;
		}

		return apply_filters( 'getpaid_geolocation_geoip_response_' . $geolocation_service, '', $geolocation_response['body'] );

	}

}
