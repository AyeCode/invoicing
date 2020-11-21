<?php
/**
 * Contains the subscriptions db class.
 *
 */

defined( 'ABSPATH' ) || exit;

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
        return getpaid_get_subscriptions( $args );
    }

    /**
     * Count the total number of subscriptions in the database
     *
     * @access  public
     * @since   1.0.0
     */
    public function count( $args = array() ) {
        return getpaid_get_subscriptions( $args, 'count' );
    }

    /**
     * Create the table
     *
     * @access  public
     * @since   1.0.0
     */
    public function create_table() {

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