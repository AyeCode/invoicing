<?php
/**
 * Handle data for the current customers session.
 * Implements the WPInv_Session abstract class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Session handler class.
 */
class WPInv_Session_Handler extends WPInv_Session {

	/**
	 * Cookie name used for the session.
	 *
	 * @var string cookie name
	 */
	protected $_cookie;

	/**
	 * Stores session expiry.
	 *
	 * @var int session due to expire timestamp
	 */
	protected $_session_expiring;

	/**
	 * Stores session due to expire timestamp.
	 *
	 * @var string session expiration timestamp
	 */
	protected $_session_expiration;

	/**
	 * True when the cookie exists.
	 *
	 * @var bool Based on whether a cookie exists.
	 */
	protected $_has_cookie = false;

	/**
	 * Table name for session data.
	 *
	 * @var string Custom session table name
	 */
	protected $_table;

	/**
	 * Constructor for the session class.
	 */
	public function __construct() {

	    $this->_cookie = apply_filters( 'wpinv_cookie', 'wpinv_session_' . COOKIEHASH );
        add_action( 'init', array( $this, 'init' ), -1 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );
		add_action( 'wp', array( $this, 'set_customer_session_cookie' ), 10 );
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );

	}

	/**
	 * Init hooks and session data.
	 *
	 * @since 3.3.0
	 */
	public function init() {
		$this->init_session_cookie();

		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ), 10, 2 );
		}
	}

	/**
	 * Setup cookie and customer ID.
	 *
	 * @since 3.6.0
	 */
	public function init_session_cookie() {
		$cookie = $this->get_session_cookie();

		if ( $cookie ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];
			$this->_has_cookie         = true;
			$this->_data               = $this->get_session_data();

			// If the user logs in, update session.
			if ( is_user_logged_in() && get_current_user_id() != $this->_customer_id ) {
				$this->_customer_id = get_current_user_id();
				$this->_dirty       = true;
				$this->save_data();
				$this->set_customer_session_cookie( true );
			}

			// Update session if its close to expiring.
			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
			}
		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();
			$this->_data        = $this->get_session_data();
		}
	}

	/**
	 * Sets the session cookie on-demand (usually after adding an item to the cart).
	 *
	 * Since the cookie name (as of 2.1) is prepended with wp, cache systems like batcache will not cache pages when set.
	 *
	 * Warning: Cookies will only be set if this is called before the headers are sent.
	 *
	 * @param bool $set Should the session cookie be set.
	 */
	public function set_customer_session_cookie( $set ) {
		if ( $set ) {
			$to_hash           = $this->_customer_id . '|' . $this->_session_expiration;
			$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
			$cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $this->_session_expiring . '||' . $cookie_hash;
			$this->_has_cookie = true;

			if ( ! isset( $_COOKIE[ $this->_cookie ] ) || $_COOKIE[ $this->_cookie ] !== $cookie_value ) {
				$this->setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, $this->use_secure_cookie(), true );
			}
		}
	}

	public function setcookie($name, $value, $expire = 0, $secure = false, $httponly = false){
        if ( ! headers_sent() ) {
            setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, apply_filters( 'wpinv_cookie_httponly', $httponly, $name, $value, $expire, $secure ) );
        } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            headers_sent( $file, $line );
            trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
        }
    }

	/**
	 * Should the session cookie be secure?
	 *
	 * @since 3.6.0
	 * @return bool
	 */
	protected function use_secure_cookie() {
        $is_https = false !== strstr( get_option( 'home' ), 'https:' );
		return apply_filters( 'wpinv_session_use_secure_cookie', $is_https && is_ssl() );
	}

	/**
	 * Return true if the current user has an active session, i.e. a cookie to retrieve values.
	 *
	 * @return bool
	 */
	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in(); // @codingStandardsIgnoreLine.
	}

	/**
	 * Set session expiration.
	 */
	public function set_session_expiration() {
		$this->_session_expiring   = time() + intval( apply_filters( 'wpinv_session_expiring', 60 * 60 * 47 ) ); // 47 Hours.
		$this->_session_expiration = time() + intval( apply_filters( 'wpinv_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.
	}

	/**
	 * Generates session ids.
	 *
	 * @return string
	 */
	public function generate_customer_id() {
		require_once ABSPATH . 'wp-includes/class-phpass.php';
		$hasher      = new PasswordHash( 8, false );
		return md5( $hasher->get_random_bytes( 32 ) );
	}

	/**
	 * Get the session cookie, if set. Otherwise return false.
	 *
	 * Session cookies without a customer ID are invalid.
	 *
	 * @return bool|array
	 */
	public function get_session_cookie() {
		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? wp_unslash( $_COOKIE[ $this->_cookie ] ) : false; // @codingStandardsIgnoreLine.

		if ( empty( $cookie_value ) || ! is_string( $cookie_value ) ) {
			return false;
		}

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $cookie_value );

		if ( empty( $customer_id ) ) {
			return false;
		}

		// Validate hash.
		$to_hash = $customer_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * Get session data.
	 *
	 * @return array
	 */
	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_customer_id ) : array();
	}

	public function generate_key($customer_id){
        if(!$customer_id){
            return;
        }

        return 'wpi_trans_'.$customer_id;
    }

	/**
	 * Save data.
	 */
	public function save_data() {
		// Dirty if something changed - prevents saving nothing new.
		if ( $this->_dirty && $this->has_session() ) {

            set_transient( $this->generate_key($this->_customer_id), $this->_data, $this->_session_expiration);

			$this->_dirty = false;
		}
	}

	/**
	 * Destroy all session data.
	 */
	public function destroy_session() {
		$this->delete_session( $this->_customer_id );
		$this->forget_session();
	}

	/**
	 * Forget all session data without destroying it.
	 */
	public function forget_session() {
		$this->setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, $this->use_secure_cookie(), true );

		wpinv_empty_cart();

		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
	}

	/**
	 * When a user is logged out, ensure they have a unique nonce by using the customer/session ID.
	 *
	 * @param int $uid User ID.
	 * @return string
	 */
	public function nonce_user_logged_out( $uid ) {

		// Check if one of our nonces.
		if ( substr( $uid, 0, 5 ) === 'wpinv' || substr( $uid, 0, 7 ) === 'getpaid' ) {
			return $this->has_session() && $this->_customer_id ? $this->_customer_id : $uid;
		}

		return $uid;
	}

	/**
	 * Returns the session.
	 *
	 * @param string $customer_id Customer ID.
	 * @param mixed  $default Default session value.
	 * @return string|array
	 */
	public function get_session( $customer_id, $default = false ) {

		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return array();
		}

        $key = $this->generate_key($customer_id);
        $value = get_transient($key);

        if ( !$value ) {
            $value = $default;
        }

		return maybe_unserialize( $value );
	}

	/**
	 * Delete the session from the cache and database.
	 *
	 * @param int $customer_id Customer ID.
	 */
	public function delete_session( $customer_id ) {

        $key = $this->generate_key($customer_id);

		delete_transient($key);
	}

	/**
	 * Update the session expiry timestamp.
	 *
	 * @param string $customer_id Customer ID.
	 * @param int    $timestamp Timestamp to expire the cookie.
	 */
	public function update_session_timestamp( $customer_id, $timestamp ) {

        set_transient( $this->generate_key($customer_id), maybe_serialize( $this->_data ), $timestamp);

	}
}
