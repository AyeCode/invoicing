<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * WPInv_Cache_Helper class.
 *
 * @class   WPInv_Cache_Helper
 */
class WPInv_Cache_Helper {

    /**
     * Hook in methods.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'init_hooks' ), 0 );
        add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
    }

    public static function init_hooks() {
        if ( false === ( $page_uris = get_transient( 'wpinv_cache_excluded_uris' ) ) ) {
            $checkout_page = wpinv_get_option( 'checkout_page', '' );
            $success_page  = wpinv_get_option( 'success_page', '' );
            $failure_page  = wpinv_get_option( 'failure_page', '' );
            $history_page  = wpinv_get_option( 'invoice_history_page', '' );
            $subscr_page   = wpinv_get_option( 'invoice_subscription_page', '' );
            if ( empty( $checkout_page ) || empty( $success_page ) || empty( $failure_page ) || empty( $history_page ) || empty( $subscr_page ) ) {
                return;
            }

            $page_uris   = array();

            // Exclude querystring when using page ID
            $page_uris[] = 'p=' . $checkout_page;
            $page_uris[] = 'p=' . $success_page;
            $page_uris[] = 'p=' . $failure_page;
            $page_uris[] = 'p=' . $history_page;
            $page_uris[] = 'p=' . $subscr_page;

            // Exclude permalinks
            $checkout_page  = get_post( $checkout_page );
            $success_page   = get_post( $success_page );
            $failure_page   = get_post( $failure_page );
            $history_page   = get_post( $history_page );
            $subscr_page    = get_post( $subscr_page );

            if ( ! is_null( $checkout_page ) ) {
                $page_uris[] = '/' . $checkout_page->post_name;
            }
            if ( ! is_null( $success_page ) ) {
                $page_uris[] = '/' . $success_page->post_name;
            }
            if ( ! is_null( $failure_page ) ) {
                $page_uris[] = '/' . $failure_page->post_name;
            }
            if ( ! is_null( $history_page ) ) {
                $page_uris[] = '/' . $history_page->post_name;
            }
            if ( ! is_null( $subscr_page ) ) {
                $page_uris[] = '/' . $subscr_page->post_name;
            }

            set_transient( 'wpinv_cache_excluded_uris', $page_uris );
        }

        if ( is_array( $page_uris ) ) {
            foreach( $page_uris as $uri ) {
                if ( strstr( $_SERVER['REQUEST_URI'], $uri ) ) {
                    self::nocache();
                    break;
                }
            }
        }
    }

    /**
     * Set nocache constants and headers.
     * @access private
     */
    private static function nocache() {
        if ( ! defined( 'DONOTCACHEPAGE' ) ) {
            define( "DONOTCACHEPAGE", true );
        }
        if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
            define( "DONOTCACHEOBJECT", true );
        }
        if ( ! defined( 'DONOTCACHEDB' ) ) {
            define( "DONOTCACHEDB", true );
        }
        nocache_headers();
    }
    
    /**
     * notices function.
     */
    public static function notices() {
        if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
            return;
        }

        $config   = w3_instance( 'W3_Config' );
        $enabled  = $config->get_integer( 'dbcache.enabled' );
        $settings = array_map( 'trim', $config->get_array( 'dbcache.reject.sql' ) );

        if ( $enabled && ! in_array( '_wp_session_', $settings ) ) {
            ?>
            <div class="error">
                <p><?php printf( __( 'In order for <strong>database caching</strong> to work with Invoicing you must add %1$s to the "Ignored Query Strings" option in <a href="%2$s">W3 Total Cache settings</a>.', 'invoicing' ), '<code>_wp_session_</code>', admin_url( 'admin.php?page=w3tc_dbcache' ) ); ?></p>
            </div>
            <?php
        }
    }
}

WPInv_Cache_Helper::init();
