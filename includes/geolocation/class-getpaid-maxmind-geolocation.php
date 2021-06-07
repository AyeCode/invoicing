<?php
/**
 * Maxmind Geolocation class
 *
 * Handles geolocation and updating the geolocation database.
 * This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Uses MaxMind for Geolocation
 *
 * @since 1.0.19
 */
class GetPaid_MaxMind_Geolocation {

	/**
	 * The service responsible for interacting with the MaxMind database.
	 *
	 * @var GetPaid_MaxMind_Database_Service
	 */
	private $database_service;

	/**
	 * Initialize the integration.
	 */
	public function __construct() {

		/**
		 * Supports overriding the database service to be used.
		 *
		 * @since 1.0.19
		 * @return mixed|null The geolocation database service.
		 */
		$this->database_service = apply_filters( 'getpaid_maxmind_geolocation_database_service', null );
		if ( null === $this->database_service ) {
			$this->database_service = new GetPaid_MaxMind_Database_Service( $this->get_database_prefix() );
		}

		// Bind to the scheduled updater action.
		add_action( 'getpaid_update_geoip_databases', array( $this, 'update_database' ) );

		// Bind to the geolocation filter for MaxMind database lookups.
		add_filter( 'getpaid_get_geolocation', array( $this, 'get_geolocation' ), 10, 2 );

		// Handle maxmind key updates.
		add_filter( 'wpinv_settings_sanitize_maxmind_license_key', array( $this, 'handle_key_updates' ) );

	}

	/**
	 * Get database service.
	 *
	 * @return GetPaid_MaxMind_Database_Service|null
	 */
	public function get_database_service() {
		return $this->database_service;
	}

	/**
	 * Checks to make sure that the license key is valid.
	 *
	 * @param string $license_key The new license key.
	 * @return string
	 */
	public function handle_key_updates( $license_key ) {

		// Trim whitespaces and strip slashes.
		$license_key = trim( $license_key );

		// Abort if the license key is empty or unchanged.
		if ( empty( $license_key ) ) {
			return $license_key;
		}

		// Abort if a database exists and the license key is unchaged.
		if ( file_exists( $this->database_service->get_database_path() && $license_key == wpinv_get_option( 'maxmind_license_key' ) ) ) {
			return $license_key;
		}

		// Check the license key by attempting to download the Geolocation database.
		$tmp_database_path = $this->database_service->download_database( $license_key );
		if ( is_wp_error( $tmp_database_path ) ) {
			getpaid_admin()->show_error( $tmp_database_path->get_error_message() );
			return $license_key;
		}

		$this->update_database( /** @scrutinizer ignore-type */ $tmp_database_path );

		return $license_key;
	}

	/**
	 * Updates the database used for geolocation queries.
	 *
	 * @param string $tmp_database_path Temporary database path.
	 */
	public function update_database( $tmp_database_path = null ) {

		// Allow us to easily interact with the filesystem.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		// Remove any existing archives to comply with the MaxMind TOS.
		$target_database_path = $this->database_service->get_database_path();

		// If there's no database path, we can't store the database.
		if ( empty( $target_database_path ) ) {
			return;
		}

		if ( $wp_filesystem->exists( $target_database_path ) ) {
			$wp_filesystem->delete( $target_database_path );
		}

		// We can't download a database if there's no license key configured.
		$license_key = wpinv_get_option( 'maxmind_license_key' );
		if ( empty( $license_key ) ) {
			return;
		}

		if ( empty( $tmp_database_path ) ) {
			$tmp_database_path = $this->database_service->download_database( $license_key );
		}

		if ( is_wp_error( $tmp_database_path ) ) {
			wpinv_error_log( $tmp_database_path->get_error_message() );
			return;
		}

		// Move the new database into position.
		$wp_filesystem->move( $tmp_database_path, $target_database_path, true );
		$wp_filesystem->delete( dirname( $tmp_database_path ) );
	}

	/**
	 * Performs a geolocation lookup against the MaxMind database for the given IP address.
	 *
	 * @param array  $data       Geolocation data.
	 * @param string $ip_address The IP address to geolocate.
	 * @return array Geolocation including country code, state, city and postcode based on an IP address.
	 */
	public function get_geolocation( $data, $ip_address ) {

		if ( ! empty( $data['country'] ) || empty( $ip_address ) ) {
			return $data;
		}

		$country_code = $this->database_service->get_iso_country_code_for_ip( $ip_address );

		return array(
			'country'  => $country_code,
			'state'    => '',
			'city'     => '',
			'postcode' => '',
		);

	}

	/**
	 * Fetches the prefix for the MaxMind database file.
	 *
	 * @return string
	 */
	private function get_database_prefix() {

		$prefix = get_option( 'wpinv_maxmind_database_prefix' );

		if ( empty( $prefix ) ) {
			$prefix = md5( uniqid( 'wpinv' ) );
			update_option( 'wpinv_maxmind_database_prefix', $prefix );
		}

		return $prefix;
	}

}
