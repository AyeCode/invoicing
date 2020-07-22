<?php
/**
 * GetPaid_Cache_Helper class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Cache_Helper.
 */
class GetPaid_Cache_Helper {

	/**
	 * Transients to delete on shutdown.
	 *
	 * @var array Array of transient keys.
	 */
	private static $delete_transients = array();

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'shutdown', array( __CLASS__, 'delete_transients_on_shutdown' ), 10 );
		add_action( 'wp', array( __CLASS__, 'prevent_caching' ) );
	}

	/**
	 * Add a transient to delete on shutdown.
	 *
	 * @since 1.0.19
	 * @param string|array $keys Transient key or keys.
	 */
	public static function queue_delete_transient( $keys ) {
		self::$delete_transients = array_unique( array_merge( is_array( $keys ) ? $keys : array( $keys ), self::$delete_transients ) );
	}

	/**
	 * Transients that don't need to be cleaned right away can be deleted on shutdown to avoid repetition.
	 *
	 * @since 1.0.19
	 */
	public static function delete_transients_on_shutdown() {
		if ( self::$delete_transients ) {
			foreach ( self::$delete_transients as $key ) {
				delete_transient( $key );
			}
			self::$delete_transients = array();
		}
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		// Get cache key.
		$prefix = wp_cache_get( 'getpaid_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = microtime();
			wp_cache_set( 'getpaid_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'getpaid_cache_' . $prefix . '_';
	}

	/**
	 * Invalidate cache group.
	 *
	 * @param string $group Group of cache to clear.
	 * @since 1.0.19
	 */
	public static function invalidate_cache_group( $group ) {
		wp_cache_set( 'getpaid_' . $group . '_cache_prefix', microtime(), $group );
	}

	/**
	 * Prevent caching on certain pages
	 */
	public static function prevent_caching() {
		if ( ! is_blog_installed() ) {
			return;
		}

		if ( wpinv_is_checkout() || wpinv_is_success_page() || wpinv_is_invoice_history_page() || wpinv_is_subscriptions_history_page() ) {
			self::set_nocache_constants();
			nocache_headers();
		}

	}

	/**
	 * Get transient version.
	 *
	 *
	 * @param  string  $group   Name for the group of transients we need to invalidate.
	 * @param  boolean $refresh true to force a new version.
	 * @return string transient version based on time(), 10 digits.
	 */
	public static function get_transient_version( $group, $refresh = false ) {
		$transient_name  = $group . '-transient-version';
		$transient_value = get_transient( $transient_name );

		if ( false === $transient_value || true === $refresh ) {
			$transient_value = (string) time();

			set_transient( $transient_name, $transient_value );
		}

		return $transient_value;
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		getpaid_maybe_define_constant( 'DONOTCACHEPAGE', true );
		getpaid_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		getpaid_maybe_define_constant( 'DONOTCACHEDB', true );
		return $return;
	}

}

GetPaid_Cache_Helper::init();
