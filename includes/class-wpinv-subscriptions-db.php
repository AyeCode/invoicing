<?php
// Exit if accessed directly.
if (!defined( 'ABSPATH' ) ) exit;

/**
 * The Subscriptions DB Class
 *
 * @since  1.0.0
 */

class WPInv_Subscriptions_DB extends Wpinv_DB {

    /**
     * Get things started
     *
     * @access  public
     * @since   1.0.0
     */
    public function __construct() {

        global $wpdb;

        $this->table_name  = $wpdb->prefix . 'wpinv_subscriptions';
        $this->primary_key = 'id';
        $this->version     = '1.0.0';

    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   1.0.0
     */
    public function get_columns() {
        return array(
            'id'                => '%d',
            'customer_id'       => '%d',
            'frequency'         => '%d',
            'period'            => '%s',
            'initial_amount'    => '%s',
            'recurring_amount'  => '%s',
            'bill_times'        => '%d',
            'transaction_id'    => '%s',
            'parent_payment_id' => '%d',
            'product_id'        => '%d',
            'created'           => '%s',
            'expiration'        => '%s',
            'trial_period'      => '%s',
            'status'            => '%s',
            'profile_id'        => '%s',
        );
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   1.0.0
     */
    public function get_column_defaults() {
        return array(
            'customer_id'       => 0,
            'period'            => '',
            'initial_amount'    => '',
            'recurring_amount'  => '',
            'bill_times'        => 0,
            'transaction_id'    => '',
            'parent_payment_id' => 0,
            'product_id'        => 0,
            'created'           => date( 'Y-m-d H:i:s' ),
            'expiration'        => date( 'Y-m-d H:i:s' ),
            'trial_period'      => '',
            'status'            => '',
            'profile_id'        => '',
        );
    }

    /**
     * Retrieve all subscriptions for a customer
     *
     * @access  public
     * @since   1.0.0
     */
    public function get_subscriptions( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'number'       => get_option( 'posts_per_page' ),
            'offset'       => 0,
            'search'       => '',
            'customer_id'  => 0,
            'orderby'      => 'id',
            'order'        => 'DESC'
        );

        $args  = wp_parse_args( $args, $defaults );

        if( $args['number'] < 1 ) {
            $args['number'] = 999999999999;
        }

        $where = ' WHERE 1=1 ';

        // specific customers
        if( ! empty( $args['id'] ) ) {

            if( is_array( $args['id'] ) ) {
                $ids = implode( ',', array_map('intval', $args['id'] ) );
            } else {
                $ids = intval( $args['id'] );
            }

            $where .= " AND `id` IN( {$ids} ) ";

        }

        // Specific products
        if( ! empty( $args['product_id'] ) ) {

            if( is_array( $args['product_id'] ) ) {
                $product_ids = implode( ',', array_map('intval', $args['product_id'] ) );
            } else {
                $product_ids = intval( $args['product_id'] );
            }

            $where .= " AND `product_id` IN( {$product_ids} ) ";

        }

        // Specific parent payments
        if( ! empty( $args['parent_payment_id'] ) ) {

            if( is_array( $args['parent_payment_id'] ) ) {
                $parent_payment_ids = implode( ',', array_map('intval', $args['parent_payment_id'] ) );
            } else {
                $parent_payment_ids = intval( $args['parent_payment_id'] );
            }

            $where .= " AND `parent_payment_id` IN( {$parent_payment_ids} ) ";

        }

        // Specific transaction IDs
        if( ! empty( $args['transaction_id'] ) ) {

            if( is_array( $args['transaction_id'] ) ) {
                $transaction_ids = implode( "','", array_map('sanitize_text_field', $args['transaction_id'] ) );
            } else {
                $transaction_ids = sanitize_text_field( $args['transaction_id'] );
            }

            $where .= " AND `transaction_id` IN ( '{$transaction_ids}' ) ";

        }

        // Subscriptoins for specific customers
        if( ! empty( $args['customer_id'] ) ) {

            if( is_array( $args['customer_id'] ) ) {
                $customer_ids = implode( ',', array_map('intval', $args['customer_id'] ) );
            } else {
                $customer_ids = intval( $args['customer_id'] );
            }

            $where .= " AND `customer_id` IN( {$customer_ids} ) ";

        }

        // Subscriptions for specific profile IDs
        if( ! empty( $args['profile_id'] ) ) {

            if( is_array( $args['profile_id'] ) ) {
                $profile_ids = implode( "','", array_map('sanitize_text_field', $args['profile_id'] ) );
            } else {
                $profile_ids = sanitize_text_field( $args['profile_id'] );
            }

            $where .= " AND `profile_id` IN( '{$profile_ids}' ) ";

        }

        // Subscriptions for specific statuses
        if( ! empty( $args['status'] ) ) {

            if( is_array( $args['status'] ) ) {
                $statuses = implode( "','", array_map( 'sanitize_text_field', $args['status'] ) );
            } else {
                $statuses = sanitize_text_field( $args['status'] );
            }

            $where .= " AND `status` IN( '{$statuses}' ) ";

        }

        // Subscriptions created for a specific date or in a date range
        if( ! empty( $args['date'] ) ) {

            if( is_array( $args['date'] ) ) {

                if( ! empty( $args['date']['start'] ) ) {

                    $start = date( 'Y-m-d H:i:s', strtotime( $args['date']['start'] ) );

                    $where .= " AND `created` >= '{$start}'";

                }

                if( ! empty( $args['date']['end'] ) ) {

                    $end = date( 'Y-m-d H:i:s', strtotime( $args['date']['end'] ) );

                    $where .= " AND `created` <= '{$end}'";

                }

            } else {

                $year  = date( 'Y', strtotime( $args['date'] ) );
                $month = date( 'm', strtotime( $args['date'] ) );
                $day   = date( 'd', strtotime( $args['date'] ) );

                $where .= " AND $year = YEAR ( created ) AND $month = MONTH ( created ) AND $day = DAY ( created )";
            }

        }

        // Subscriptions with a specific expiration date or in an expiration date range
        if( ! empty( $args['expiration'] ) ) {

            if( is_array( $args['expiration'] ) ) {

                if( ! empty( $args['expiration']['start'] ) ) {

                    $start = date( 'Y-m-d H:i:s', strtotime( $args['expiration']['start'] ) );

                    $where .= " AND `expiration` >= '{$start}'";

                }

                if( ! empty( $args['expiration']['end'] ) ) {

                    $end = date( 'Y-m-d H:i:s', strtotime( $args['expiration']['end'] ) );

                    $where .= " AND `expiration` <= '{$end}'";

                }

            } else {

                $year  = date( 'Y', strtotime( $args['expiration'] ) );
                $month = date( 'm', strtotime( $args['expiration'] ) );
                $day   = date( 'd', strtotime( $args['expiration'] ) );

                $where .= " AND $year = YEAR ( expiration ) AND $month = MONTH ( expiration ) AND $day = DAY ( expiration )";
            }

        }

        if ( ! empty( $args['search'] ) ) {

            if( false !== strpos( 'id:', $args['search'] ) ) {

                $args['search'] = trim( str_replace( 'id:', '', $args['search'] ) );
                $where .= " AND `id` = '" . esc_sql( $args['search'] ) . "'";

            } else if( false !== strpos( $args['search'], 'txn:' ) ) {

                $args['search'] = trim( str_replace( 'txn:', '', $args['search'] ) );
                $where .= " AND `transaction_id` = '" . esc_sql( $args['search'] ) . "'";

            } else if( false !== strpos( $args['search'], 'profile_id:' ) ) {

                $args['search'] = trim( str_replace( 'profile_id:', '', $args['search'] ) );
                $where .= " AND `profile_id` = '" . esc_sql( $args['search'] ) . "'";

            } else if( false !== strpos( $args['search'], 'product_id:' ) ) {

                $args['search'] = trim( str_replace( 'product_id:', '', $args['search'] ) );
                $where .= " AND `product_id` = '" . esc_sql( $args['search'] ) . "'";

            } else if( false !== strpos( $args['search'], 'customer_id:' ) ) {

                $args['search'] = trim( str_replace( 'customer_id:', '', $args['search'] ) );
                $where .= " AND `customer_id` = '" . esc_sql( $args['search'] ) . "'";

            } else {

                $where .= " AND ( `parent_payment_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `profile_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `transaction_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `product_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `id` = '" . esc_sql( $args['search'] ) . "' )";

            }

        }

        $args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'id' : $args['orderby'];

        if( 'amount' == $args['orderby'] ) {
            $args['orderby'] = 'amount+0';
        }

        $cache_key = md5( 'wpinv_subscriptions_' . serialize( $args ) );

        $subscriptions = wp_cache_get( $cache_key, 'subscriptions' );

        $args['orderby'] = esc_sql( $args['orderby'] );
        $args['order']   = esc_sql( $args['order'] );

        if( $subscriptions === false ) {
            $subscriptions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM  $this->table_name $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) ), OBJECT );

            if( ! empty( $subscriptions ) ) {

                foreach( $subscriptions as $key => $subscription ) {
                    $subscriptions[ $key ] = new WPInv_Subscription( $subscription );
                }

                wp_cache_set( $cache_key, $subscriptions, 'subscriptions', 3600 );

            }

        }

        return $subscriptions;
    }

    /**
     * Count the total number of subscriptions in the database
     *
     * @access  public
     * @since   1.0.0
     */
    public function count( $args = array() ) {

        global $wpdb;

        $where = ' WHERE 1=1 ';

        // specific customers
        if( ! empty( $args['id'] ) ) {

            if( is_array( $args['id'] ) ) {
                $ids = implode( ',', array_map('intval', $args['id'] ) );
            } else {
                $ids = intval( $args['id'] );
            }

            $where .= " AND `id` IN( {$ids} ) ";

        }

        // Specific products
        if( ! empty( $args['product_id'] ) ) {

            if( is_array( $args['product_id'] ) ) {
                $product_ids = implode( ',', array_map('intval', $args['product_id'] ) );
            } else {
                $product_ids = intval( $args['product_id'] );
            }

            $where .= " AND `product_id` IN( {$product_ids} ) ";

        }

        // Specific parent payments
        if( ! empty( $args['parent_payment_id'] ) ) {

            if( is_array( $args['parent_payment_id'] ) ) {
                $parent_payment_ids = implode( ',', array_map('intval', $args['parent_payment_id'] ) );
            } else {
                $parent_payment_ids = intval( $args['parent_payment_id'] );
            }

            $where .= " AND `parent_payment_id` IN( {$parent_payment_ids} ) ";

        }

        // Subscriptoins for specific customers
        if( ! empty( $args['customer_id'] ) ) {

            if( is_array( $args['customer_id'] ) ) {
                $customer_ids = implode( ',', array_map('intval', $args['customer_id'] ) );
            } else {
                $customer_ids = intval( $args['customer_id'] );
            }

            $where .= " AND `customer_id` IN( {$customer_ids} ) ";

        }

        // Subscriptions for specific profile IDs
        if( ! empty( $args['profile_id'] ) ) {

            if( is_array( $args['profile_id'] ) ) {
                $profile_ids = implode( ',', array_map('intval', $args['profile_id'] ) );
            } else {
                $profile_ids = intval( $args['profile_id'] );
            }

            $where .= " AND `profile_id` IN( {$profile_ids} ) ";

        }

        // Specific transaction IDs
        if( ! empty( $args['transaction_id'] ) ) {

            if( is_array( $args['transaction_id'] ) ) {
                $transaction_ids = implode( ',', array_map('sanitize_text_field', $args['transaction_id'] ) );
            } else {
                $transaction_ids = sanitize_text_field( $args['transaction_id'] );
            }

            $where .= " AND `transaction_id` IN( {$transaction_ids} ) ";

        }

        // Subscriptions for specific statuses
        if( ! empty( $args['status'] ) ) {

            if( is_array( $args['status'] ) ) {
                $statuses = implode( ',', $args['status'] );
                $where  .= " AND `status` IN( {$statuses} ) ";
            } else {
                $statuses = $args['status'];
                $where  .= " AND `status` = '{$statuses}' ";
            }



        }

        // Subscriptions created for a specific date or in a date range
        if( ! empty( $args['date'] ) ) {

            if( is_array( $args['date'] ) ) {

                if( ! empty( $args['date']['start'] ) ) {

                    $start = date( 'Y-m-d H:i:s', strtotime( $args['date']['start'] ) );

                    $where .= " AND `created` >= '{$start}'";

                }

                if( ! empty( $args['date']['end'] ) ) {

                    $end = date( 'Y-m-d H:i:s', strtotime( $args['date']['end'] ) );

                    $where .= " AND `created` <= '{$end}'";

                }

            } else {

                $year  = date( 'Y', strtotime( $args['date'] ) );
                $month = date( 'm', strtotime( $args['date'] ) );
                $day   = date( 'd', strtotime( $args['date'] ) );

                $where .= " AND $year = YEAR ( created ) AND $month = MONTH ( created ) AND $day = DAY ( created )";
            }

        }

        // Subscriptions with a specific expiration date or in an expiration date range
        if( ! empty( $args['expiration'] ) ) {

            if( is_array( $args['expiration'] ) ) {

                if( ! empty( $args['expiration']['start'] ) ) {

                    $start = date( 'Y-m-d H:i:s', strtotime( $args['expiration']['start'] ) );

                    $where .= " AND `expiration` >= '{$start}'";

                }

                if( ! empty( $args['expiration']['end'] ) ) {

                    $end = date( 'Y-m-d H:i:s', strtotime( $args['expiration']['end'] ) );

                    $where .= " AND `expiration` <= '{$end}'";

                }

            } else {

                $year  = date( 'Y', strtotime( $args['expiration'] ) );
                $month = date( 'm', strtotime( $args['expiration'] ) );
                $day   = date( 'd', strtotime( $args['expiration'] ) );

                $where .= " AND $year = YEAR ( expiration ) AND $month = MONTH ( expiration ) AND $day = DAY ( expiration )";
            }

        }

        if ( ! empty( $args['search'] ) ) {

            if( false !== strpos( 'id:', $args['search'] ) ) {

                $args['search'] = trim( str_replace( 'id:', '', $args['search'] ) );
                $where .= " AND `id` = '" . esc_sql( $args['search'] ) . "'";

            } else if( false !== strpos( $args['search'], 'txn:' ) ) {

                $args['search'] = trim( str_replace( 'txn:', '', $args['search'] ) );
                $where .= " AND `transaction_id` = '" . esc_sql( $args['search'] ) . "'";

            } else if( false !== strpos( $args['search'], 'profile_id:' ) ) {

                $args['search'] = trim( str_replace( 'profile_id:', '', $args['search'] ) );
                $where .= " AND `profile_id` = '" . esc_sql( $args['search'] ) . "'";

            } else if( false !== strpos( $args['search'], 'product_id:' ) ) {

                $args['search'] = trim( str_replace( 'product_id:', '', $args['search'] ) );
                $where .= " AND `product_id` = '" . esc_sql( $args['search'] ) . "'";

            } else if( false !== strpos( $args['search'], 'customer_id:' ) ) {

                $args['search'] = trim( str_replace( 'customer_id:', '', $args['search'] ) );
                $where .= " AND `customer_id` = '" . esc_sql( $args['search'] ) . "'";

            } else {

                $where .= " AND ( `parent_payment_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `profile_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `transaction_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `product_id` LIKE '%%" . esc_sql( $args['search'] ) . "%%' OR `id` = '" . esc_sql( $args['search'] ) . "' )";

            }

        }

        $cache_key = md5( 'wpinv_subscriptions_count' . serialize( $args ) );

        $count = wp_cache_get( $cache_key, 'subscriptions' );

        if( $count === false ) {

            $sql   = "SELECT COUNT($this->primary_key) FROM " . $this->table_name . "{$where};";
            $count = $wpdb->get_var( $sql );

            wp_cache_set( $cache_key, $count, 'subscriptions', 3600 );

        }

        return absint( $count );

    }

    /**
     * Create the table
     *
     * @access  public
     * @since   1.0.0
     */
    public function create_table() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE " . $this->table_name . " (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        frequency int(11) NOT NULL DEFAULT '1',
        period varchar(20) NOT NULL,
        initial_amount mediumtext NOT NULL,
        recurring_amount mediumtext NOT NULL,
        bill_times bigint(20) NOT NULL,
        transaction_id varchar(60) NOT NULL,
        parent_payment_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        created datetime NOT NULL,
        expiration datetime NOT NULL,
        trial_period varchar(20) NOT NULL,
        status varchar(20) NOT NULL,
        profile_id varchar(60) NOT NULL,
        PRIMARY KEY  (id),
        KEY profile_id (profile_id),
        KEY customer (customer_id),
        KEY transaction (transaction_id),
        KEY customer_and_status ( customer_id, status)
        ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}