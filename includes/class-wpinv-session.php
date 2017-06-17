<?php
// Exit if accessed directly.
if (!defined( 'ABSPATH' ) ) exit;

/**
 * WPInv_Session Class.
 *
 * @since 1.0.0
 */
class WPInv_Session {
	/**
	 * Holds our session data.
	 *
	 * @var array
	 * @access private
	 * @since 1.0.0
	 */
	private $session;

	/**
	 * Whether to use PHP $_SESSION or WP_Session.
	 *
	 * @var bool
	 * @access private
	 * @since 1.0.0
	 */
	private $use_php_sessions = false;

	/**
	 * Session index prefix.
	 *
	 * @var string
	 * @access private
	 * @since 1.0.0
	 */
	private $prefix = '';

	/**
	 * Get things started.
	 *
	 * Defines our WP_Session constants, includes the necessary libraries and
	 * retrieves the WP Session instance.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->use_php_sessions = $this->use_php_sessions();

		if ( $this->use_php_sessions ) {
			if ( is_multisite() ) {
				$this->prefix = '_' . get_current_blog_id();
			}

			// Use PHP SESSION (must be enabled via the WPINV_USE_PHP_SESSIONS constant)
			add_action( 'init', array( $this, 'maybe_start_session' ), -2 );
		} else {
			// Use WP_Session (default)
			if ( !defined( 'WP_SESSION_COOKIE' ) ) {
				define( 'WP_SESSION_COOKIE', 'wpinv_wp_session' );
			}

			if ( !class_exists( 'Recursive_ArrayAccess' ) ) {
				require_once WPINV_PLUGIN_DIR . 'includes/libraries/wp-session/class-recursive-arrayaccess.php';
			}
            
			if ( !class_exists( 'WP_Session_Utils' ) ) {
				require_once WPINV_PLUGIN_DIR . 'includes/libraries/wp-session/class-wp-session-utils.php';
			}
            
			if ( defined( 'WP_CLI' ) && WP_CLI && !class_exists( 'WP_Session_Command' ) ) {
				require_once WPINV_PLUGIN_DIR . 'includes/libraries/wp-session/wp-cli.php';
			}

			if ( !class_exists( 'WP_Session' ) ) {
				require_once WPINV_PLUGIN_DIR . 'includes/libraries/wp-session/class-wp-session.php';
				require_once WPINV_PLUGIN_DIR . 'includes/libraries/wp-session/wp-session.php';
			}

			add_filter( 'wp_session_expiration_variant', array( $this, 'set_expiration_variant_time' ), 99999 );
			add_filter( 'wp_session_expiration', array( $this, 'set_expiration_time' ), 99999 );
		}

		if ( empty( $this->session ) && ! $this->use_php_sessions ) {
			add_action( 'plugins_loaded', array( $this, 'init' ), -1 );
		} else {
			add_action( 'init', array( $this, 'init' ), -1 );
		}
	}

	/**
	 * Setup the WP_Session instance.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		if ( $this->use_php_sessions ) {
			$this->session = isset( $_SESSION['wpi' . $this->prefix ] ) && is_array( $_SESSION['wpi' . $this->prefix ] ) ? $_SESSION['wpi' . $this->prefix ] : array();
		} else {
			$this->session = WP_Session::get_instance();
		}

		return $this->session;
	}

	/**
	 * Retrieve session ID.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return string Session ID
	 */
	public function get_id() {
		if ( $this->use_php_sessions ) {
			$session_id = !empty( $_SESSION ) && function_exists( 'session_id' ) ? session_id() : NULL;
		} else {
			$session_id = !empty( $this->session ) && isset( $this->session->session_id ) ? $this->session->session_id : NULL;
		}
		return $session_id;
	}

	/**
	 * Retrieve a session variable.
	 *
	 * @access public
	 * @since 1.0.0
	 * @param string $key Session key
	 * @return string Session variable
	 */
	public function get( $key ) {
		$key = sanitize_key( $key );
		return isset( $this->session[ $key ] ) ? maybe_unserialize( $this->session[ $key ] ) : false;
	}

	/**
	 * Set a session variable
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Session key
	 * @param integer $value Session variable
	 * @return string Session variable
	 */
	public function set( $key, $value ) {
		$key = sanitize_key( $key );

		if ( is_array( $value ) ) {
			$this->session[ $key ] = maybe_serialize( $value );
		} else {
			$this->session[ $key ] = $value;
		}

		if ( $this->use_php_sessions ) {
			$_SESSION['wpi' . $this->prefix ] = $this->session;
		}

		return $this->session[ $key ];
	}
	
	/**
	 * Unset a session variable.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $key Session key.
	 * @param integer $value Session variable.
	 * @return string Session variable.
	 */
	public function un_set( $key ) {
		if ( empty( $key ) ) {
			return false;
		}
		
		if ( is_array( $key ) ) {
			foreach ($key as $index) {
				$index = sanitize_key( $index );
			
				if ( $index && isset( $this->session[ $index ] ) ) {
					unset( $this->session[ $index ] );
				}
			}
		} else {
			$key = sanitize_key( $key );
			
			if ( isset( $this->session[ $key ] ) ) {
				unset( $this->session[ $key ] );
			}
		}

		if ( $this->use_php_sessions ) {
			$_SESSION['wpi' . $this->prefix ] = $this->session;
		}

		return true;
	}
	
	/**
	 * Check a session variable is set or not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Session key.
	 * @param integer $value Session variable.
	 * @return string Session variable.
	 */
	public function is_set( $key ) {
		$key = sanitize_key( $key );
		
		if ( empty( $key ) ) {
			return false;
		}

		if ( isset( $this->session[ $key ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Force the cookie expiration variant time to 23 hours
	 *
	 * @access public
	 * @since 1.0.0
	 * @param int $exp Default expiration (1 hour)
	 * @return int
	 */
	public function set_expiration_variant_time( $exp ) {
		return ( 30 * 60 * 23 );
	}

	/**
	 * Force the cookie expiration time to 24 hours
	 *
	 * @access public
	 * @since 1.0.0
	 * @param int $exp Default expiration (1 hour)
	 * @return int
	 */
	public function set_expiration_time( $exp ) {
		return ( 30 * 60 * 24 );
	}

	/**
	 * Starts a new session if one hasn't started yet.
	 *
	 * @return boolean
	 * Checks to see if the server supports PHP sessions
	 * or if the WPINV_USE_PHP_SESSIONS constant is defined
	 *
	 * @access public
	 * @since 1.0.0
	 * @return boolean $ret True if we are using PHP sessions, false otherwise
	 */
	public function use_php_sessions() {
		$ret = false;

		// If the database variable is already set, no need to run autodetection
		$wpinv_use_php_sessions = (bool)get_option( 'wpinv_use_php_sessions' );

		if (!$wpinv_use_php_sessions ) {
			// Attempt to detect if the server supports PHP sessions
			if ( function_exists( 'session_start' ) && ! ini_get( 'safe_mode' ) ) {
				$this->set( 'wpinv_use_php_sessions', 1 );
				
				if ( $this->get( 'wpinv_use_php_sessions' ) ) {
					$ret = true;

					// Set the database option
					update_option( 'wpinv_use_php_sessions', true );
				}
			}
		} else {
			$ret = $wpinv_use_php_sessions;
		}

		// Enable or disable PHP Sessions based on the WPINV_USE_PHP_SESSIONS constant
		if ( defined( 'WPINV_USE_PHP_SESSIONS' ) && WPINV_USE_PHP_SESSIONS ) {
			$ret = true;
		} else if ( defined( 'WPINV_USE_PHP_SESSIONS' ) && ! WPINV_USE_PHP_SESSIONS ) {
			$ret = false;
		}

		return (bool) apply_filters( 'wpinv_use_php_sessions', $ret );
	}

	/**
	 * Starts a new session if one hasn't started yet.
	 */
	public function maybe_start_session() {
		if ( !session_id() && !headers_sent() ) {
			session_start();
		}
	}
}

global $wpi_session;
$wpi_session = new WPInv_Session();
