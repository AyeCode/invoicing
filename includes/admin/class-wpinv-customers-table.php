<?php
/**
 * Customers Table Class
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

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
	public $per_page = 25;

	/**
	 * @var int Number of items
	 * @since 1.0.19
	 */
	public $total_count = 0;

	public $query;

	/**
	 * Get things started
	 *
	 * @since 1.0.19
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		// Set parent defaults
		parent::__construct(
            array(
				'singular' => 'id',
				'plural'   => 'ids',
				'ajax'     => false,
            )
        );

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
		return 'customer';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @since 1.0.19
	 *
	 * @param GetPaid_Customer $customer
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $customer, $column_name ) {
		$value = esc_html( $customer->get( $column_name ) );
		return apply_filters( 'wpinv_customers_table_column' . $column_name, $value, $customer );
	}

	/**
	 * Displays the country column.
	 *
	 * @since 1.0.19
	 *
	 * @param GetPaid_Customer $customer
	 *
	 * @return string Column Name
	 */
	public function column_country( $customer ) {
		$country = wpinv_sanitize_country( $customer->get( 'country' ) );
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
	 * @param GetPaid_Customer $customer
	 *
	 * @return string Column Name
	 */
	public function column_state( $customer ) {
		$country = wpinv_sanitize_country( $customer->get( 'country' ) );
		$state   = $customer->get( 'state' );
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
	 * @param GetPaid_Customer $customer
	 *
	 * @return string Column Name
	 */
	public function column_date_created( $customer ) {
		return getpaid_format_date_value( $customer->get( 'date_created' ) );
	}

	/**
	 * Displays the total spent column.
	 *
	 * @since 1.0.19
	 *
	 * @param GetPaid_Customer $customer
	 *
	 * @return string Column Name
	 */
	public function column_purchase_value( $customer ) {
		return wpinv_price( (float) $customer->get( 'purchase_value' ) );
	}

	/**
	 * Displays the total spent column.
	 *
	 * @since 1.0.19
	 *
	 * @param GetPaid_Customer $customer
	 *
	 * @return string Column Name
	 */
	public function column_purchase_count( $customer ) {
		$value = $customer->get( 'purchase_count' );
		$url   = $customer->get( 'user_id' ) ? add_query_arg( array( 'post_type' => 'wpi_invoice', 'author' => $customer->get( 'user_id' ), ), admin_url( 'edit.php' ) ) : '';

		return ( empty( $value ) || empty( $url ) ) ? (int) $value : '<a href="' . esc_url( $url ) . '">' . absint( $value ) . '</a>';

	}

	/**
	 * Displays the customers name
	 *
	 * @param  GetPaid_Customer $customer customer.
	 * @return string
	 */
	public function column_customer( $customer ) {

		$first_name = $customer->get( 'first_name' );
		$last_name  = $customer->get( 'last_name' );
		$email      = $customer->get( 'email' );
		$avatar     = get_avatar( $customer->get( 'user_id' ) ? $customer->get( 'user_id' ) : $email, 32 );

		// Customer view URL.
		$view_url    = $customer->get( 'user_id' ) ? esc_url( add_query_arg( 'user_id', $customer->get( 'user_id' ), admin_url( 'user-edit.php' ) ) ) : false;
		$row_actions = $view_url ? $this->row_actions(
			array(
				'view' => '<a href="' . $view_url . '#getpaid-fieldset-billing">' . __( 'Edit Details', 'invoicing' ) . '</a>',
			)
		) : '';

		// Customer's name.
		$name   = esc_html( trim( "$first_name $last_name" ) );

		if ( ! empty( $name ) ) {
			$name = "<div style='overflow: hidden;height: 18px;'>$name</div>";
		}

		$email = "<div class='row-title'><a href='mailto:$email'>$email</a></div>";

		return "<div style='display: flex;'><div>$avatar</div><div style='margin-left: 10px;'>$name<strong>$email</strong>$row_actions</div></div>";

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

		// Prepare query args.
		$query = array(
			'number' => $this->per_page,
			'paged'  => $this->get_paged(),
		);

		foreach ( array( 'orderby', 'order', 's' ) as $field ) {
			if ( isset( $_GET[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$query[ $field ] = wpinv_clean( rawurlencode_deep( $_GET[ $field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		foreach ( GetPaid_Customer_Data_Store::get_database_fields() as $field => $type ) {

			if ( isset( $_GET[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$query[ $field ] = wpinv_clean( rawurlencode_deep( $_GET[ $field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			// Min max.
			if ( '%f' === $type || '%d' === $type ) {

				if ( isset( $_GET[ $field . '_min' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$query[ $field . '_min' ] = floatval( $_GET[ $field . '_min' ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}

				if ( isset( $_GET[ $field . '_max' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$query[ $field . '_max' ] = floatval( $_GET[ $field . '_max' ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
			}
		}

		// Prepare class properties.
		$this->query       = getpaid_get_customers( $query, 'query' );
		$this->total_count = $this->query->get_total();
		$this->items       = $this->query->get_results();
	}

	/**
	 * Setup the final data for the table
	 *
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->prepare_query();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_count,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $this->total_count / $this->per_page ),
			)
		);
	}

	/**
	 * Sortable table columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'customer' => array( 'first_name', true ),
		);

		foreach ( GetPaid_Customer_Data_Store::get_database_fields() as $field => $type ) {
			$sortable[ $field ] = array( $field, true );
		}

		return apply_filters( 'manage_getpaid_customers_sortable_table_columns', $sortable );
	}

	/**
	 * Table columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'customer' => __( 'Customer', 'invoicing' ),
		);

		// Add address fields.
		foreach ( getpaid_user_address_fields() as $key => $value ) {

			// Skip id, user_id and email.
			if ( ! in_array( $key, array( 'id', 'user_id', 'email', 'purchase_value', 'purchase_count', 'date_created', 'date_modified', 'uuid', 'first_name', 'last_name' ), true ) ) {
				$columns[ $key ] = $value;
			}
		}

		$columns['purchase_value'] = __( 'Total Spend', 'invoicing' );
		$columns['purchase_count'] = __( 'Invoices', 'invoicing' );
		$columns['date_created']   = __( 'Date created', 'invoicing' );

		return apply_filters( 'manage_getpaid_customers_table_columns', $columns );
	}
}
