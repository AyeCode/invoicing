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
	 * @var int Number of items
	 * @since 1.0.19
	 */
	public $total = 0;

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
		$value = esc_html( get_user_meta( $item->ID, '_wpinv_' . $column_name, true ) );
		return apply_filters( 'wpinv_customers_table_column' . $column_name, $value, $item );
	}

	/**
	 * Displays the country column.
	 *
	 * @since 1.0.19
	 *
	 * @param WP_User $user
	 *
	 * @return string Column Name
	 */
	public function column_country( $user ) {
		$country = wpinv_sanitize_country( $user->_wpinv_country );
		if ( $country ) {
			$country = wpinv_country_name( $country );
		}
		return esc_html( $country );
	}

	/**
	 * Displays the state column.
	 *
	 * @since 1.0.19
	 *
	 * @param WP_User $user
	 *
	 * @return string Column Name
	 */
	public function column_state( $user ) {
		$country = wpinv_sanitize_country( $user->_wpinv_country );
		$state   = $user->_wpinv_state;
		if ( $state ) {
			$state = wpinv_state_name( $state, $country );
		}

		return esc_html( $state );
	}

	/**
	 * Displays the signup column.
	 *
	 * @since 1.0.19
	 *
	 * @param WP_User $user
	 *
	 * @return string Column Name
	 */
	public function column_signup( $user ) {
		return getpaid_format_date_value( $user->user_registered );
	}

	/**
	 * Displays the total spent column.
	 *
	 * @since 1.0.19
	 *
	 * @param WP_User $user
	 *
	 * @return string Column Name
	 */
	public function column_total( $user ) {
		return wpinv_price( $this->column_total_raw( $user ) );
	}

	/**
	 * Displays the total spent column.
	 *
	 * @since 1.0.19
	 *
	 * @param WP_User $user
	 *
	 * @return float
	 */
	public function column_total_raw( $user ) {

		$args = array(
			'data'             => array(

				'total'        => array(
					'type'     => 'invoice_data',
					'function' => 'SUM',
					'name'     => 'total_sales',
				)

			),
			'where'            => array(

				'author'       => array(
					'type'     => 'post_data',
					'value'    => absint( $user->ID ),
					'key'      => 'posts.post_author',
					'operator' => '=',
				),

			),
			'query_type'     => 'get_var',
			'invoice_status' => array( 'wpi-renewal', 'wpi-processing', 'publish' ),
		);

		return wpinv_round_amount( GetPaid_Reports_Helper::get_invoice_report_data( $args ) );

	}

	/**
	 * Displays the total spent column.
	 *
	 * @since 1.0.19
	 *
	 * @param WP_User $user
	 *
	 * @return string Column Name
	 */
	public function column_invoices( $user ) {

		$args = array(
			'data'             => array(

				'ID'           => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'count',
					'distinct' => true,
				),

			),
			'where'            => array(

				'author'       => array(
					'type'     => 'post_data',
					'value'    => absint( $user->ID ),
					'key'      => 'posts.post_author',
					'operator' => '=',
				),

			),
			'query_type'     => 'get_var',
			'invoice_status' => array_keys( wpinv_get_invoice_statuses() ),
		);

		return absint( GetPaid_Reports_Helper::get_invoice_report_data( $args ) );

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
				'view' => '<a href="' . $view_url . '#getpaid-fieldset-billing">' . __( 'Edit Details', 'invoicing' ) . '</a>',
			)
		);

		// Get user's address.
		$address = wpinv_get_user_address( $customer->ID );

		// Customer email address.
		$email       = sanitize_email( $customer->user_email );

		// Customer's avatar.
		$avatar = esc_url( get_avatar_url( $email ) );
		$avatar = "<img src='$avatar' height='32' width='32'/>";

		// Customer's name.
		$name   = esc_html( "{$address['first_name']} {$address['last_name']}" );

		if ( ! empty( $name ) ) {
			$name = "<div style='overflow: hidden;height: 18px;'>$name</div>";
		}

		$email = "<div class='row-title'><a href='$view_url'>$email</a></div>";

		return "<div style='display: flex;'><div>$avatar</div><div style='margin-left: 10px;'>$name<strong>$email</strong>$row_actions</div></div>";

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
			'invoices' => __( 'Invoices', 'invoicing' ),
			'total'    => __( 'Total Spend', 'invoicing' ),
			'signup'   => __( 'Date created', 'invoicing' ),
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

		$post_types = '';

		foreach ( array_keys( getpaid_get_invoice_post_types() ) as $post_type ) {
			$post_types .= $wpdb->prepare( "post_type=%s OR ", $post_type );
		}

		$post_types = rtrim( $post_types, ' OR' );

		// Maybe search.
		if ( ! empty( $_POST['s'] ) ) {
			$users = get_users(
				array(
					'search'         => '*' . sanitize_text_field( urldecode( $_POST['s'] ) ) . '*',
					'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
					'fields'         => 'ID',
				)
			);

			$users      = implode( ', ', $users );
			$post_types = "($post_types) AND ( post_author IN ( $users ) )";
		}

		// Users with invoices.
    	$customers = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT( post_author ) FROM $wpdb->posts WHERE $post_types LIMIT %d,%d",
				$this->get_paged() * 10 - 10,
				$this->per_page
			)
		);

		$this->items = $customers;
		$this->total = (int) $wpdb->get_var( "SELECT COUNT( DISTINCT( post_author ) ) FROM $wpdb->posts WHERE $post_types" );

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

		$this->set_pagination_args(
			array(
			'total_items' => $this->total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $this->total / $this->per_page )
			)
		);

	}
}
