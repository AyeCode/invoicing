<?php
/**
 * Customers Table Class
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * WPInv_Customers_Table Class
 *
 * Renders the Gateway Reports table
 *
 * @since 1.0.19
 */
class WPInv_Customers_Table extends WP_List_Table {

	/**
	 * @var int Number of items per page
	 * @since 1.0.19
	 */
	public $per_page = 10;

	/**
	 * Get things started
	 *
	 * @since 1.0.19
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		// Set parent defaults
		parent::__construct( array(
			'singular' => 'id',
			'plural'   => 'ids',
			'ajax'     => false,
		) );

	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 1.0.19
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'name';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @since 1.0.19
	 *
	 * @param WP_User $item
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		$value = sanitize_text_field( get_user_meta( $item->ID, '_wpinv_' . $column_name, true ) );
		return apply_filters( 'wpinv_customers_table_column' . $column_name, $value, $item );
	}

	/**
	 * Generates content for a single row of the table
	 * @since 1.0.19
	 *
	 * @param int $item The user id.
	 */
	public function single_row( $item ) {
		$item = get_user_by( 'id', $item );

		if ( empty( $item ) ) {
			return;
		}

		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Displays the customers name
	 *
	 * @param  WP_User $customer customer.
	 * @return string
	 */
	public function column_name( $customer ) {

		// Customer view URL.
		$view_url    = esc_url( add_query_arg( 'user_id', $customer->ID, admin_url( 'user-edit.php' ) ) );
		$row_actions = $this->row_actions(
			array(
				'view' => '<a href="' . $view_url . '">' . __( 'View', 'invoicing' ) . '</a>',
			)
		);

		// Customer email address.
		$email       = sanitize_email( $customer->user_email );

		// Customer's avatar.
		$avatar = esc_url( get_avatar_url( $email ) );
		$avatar = "<img src='$avatar' height='32' width='32'/>";

		// Customer's name.
		$name   = sanitize_text_field( "{$customer->display_name} ($customer->user_login)" );

		if ( ! empty( $name ) ) {
			$name = "<div style='overflow: hidden;height: 18px;'>$name</div>";
		}

		$email = "<div class='row-title'><a href='$view_url'>$email</a></div>";

		return "<div style='display: flex;'><div>$avatar</div><div style='margin-left: 10px;'>$name<strong>$email</strong></div></div>";

	}

	/**
	 * Retrieve the table columns
	 *
	 * @since 1.0.19
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {

		$columns = array(
			'name'     => __( 'Name', 'invoicing' ),
			'country'  => __( 'Country', 'invoicing' ),
			'state'    => __( 'State', 'invoicing' ),
			'city'     => __( 'City', 'invoicing' ),
			'zip'      => __( 'ZIP', 'invoicing' ),
			'address'  => __( 'Address', 'invoicing' ),
			'phone'    => __( 'Phone', 'invoicing' ),
			'company'  => __( 'Company', 'invoicing' ),
		);
		return apply_filters( 'wpinv_customers_table_columns', $columns );

	}

	/**
	 * Retrieve the current page number
	 *
	 * @since 1.0.19
	 * @return int Current page number
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Returns bulk actions.
	 *
	 * @since 1.0.19
	 * @return void
	 */
	public function bulk_actions( $which = '' ) {
		return array();
	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {
		global $wpdb;

		// Users with invoices.
    	$customers = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT( post_author ) FROM $wpdb->posts WHERE post_type=%s LIMIT %d,%d",
				'wpi_invoice',
				$this->get_paged() * 10 - 10,
				$this->per_page
			)
		);

		$this->items = $customers;
		$this->total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT( post_author ) ) FROM $wpdb->posts WHERE post_type=%s", 'wpi_invoice' ) );

	}

	/**
	 * Setup the final data for the table
	 *
	 * @since 1.0.19
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->prepare_query();
	}
}
