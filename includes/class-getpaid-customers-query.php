<?php
/**
 * GetPaid_Customers_Query class
 *
 * Contains core class used to query for customers.
 *
 * @since 1.0.19
 */

/**
 * Main class used for querying customers.
 *
 * @since 1.0.19
 *
 * @see GetPaid_Subscriptions_Query::prepare_query() for information on accepted arguments.
 */
class GetPaid_Customers_Query {

	/**
	 * Query vars, after parsing
	 *
	 * @since 1.0.19
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * List of found customers.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	private $results;

	/**
	 * Total number of found customers for the current query
	 *
	 * @since 1.0.19
	 * @var int
	 */
	private $total_customers = 0;

	/**
	 * The SQL query used to fetch matching customers.
	 *
	 * @since 1.0.19
	 * @var string
	 */
	public $request;

	// SQL clauses

	/**
	 * Contains the 'FIELDS' sql clause
	 *
	 * @since 1.0.19
	 * @var string
	 */
	public $query_fields;

	/**
	 * Contains the 'FROM' sql clause
	 *
	 * @since 1.0.19
	 * @var string
	 */
	public $query_from;

	/**
	 * Contains the 'WHERE' sql clause
	 *
	 * @since 1.0.19
	 * @var string
	 */
	public $query_where;

	/**
	 * Contains the 'ORDER BY' sql clause
	 *
	 * @since 1.0.19
	 * @var string
	 */
	public $query_orderby;

	/**
	 * Contains the 'LIMIT' sql clause
	 *
	 * @since 1.0.19
	 * @var string
	 */
	public $query_limit;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.19
	 *
	 * @param null|string|array $query Optional. The query variables.
	 */
	public function __construct( $query = null ) {
		if ( ! is_null( $query ) ) {
			$this->prepare_query( $query );
			$this->query();
		}
	}

	/**
	 * Fills in missing query variables with default values.
	 *
	 * @since 1.0.19
	 *
	 * @param  string|array $args Query vars, as passed to `GetPaid_Subscriptions_Query`.
	 * @return array Complete query variables with undefined ones filled in with defaults.
	 */
	public static function fill_query_vars( $args ) {
		$defaults = array(
			'include'     => array(),
			'exclude'     => array(),
			'orderby'     => 'id',
			'order'       => 'DESC',
			'offset'      => '',
			'number'      => 10,
			'paged'       => 1,
			'count_total' => true,
			'fields'      => 'all',
			's'           => '',
		);

		foreach ( GetPaid_Customer_Data_Store::get_database_fields() as $field => $type ) {
			$defaults[ $field ] = 'any';

			if ( '%f' === $type || '%d' === $type ) {
				$defaults[ $field . '_min' ] = '';
				$defaults[ $field . '_max' ] = '';
			}
		}

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Prepare the query variables.
	 *
	 * @since 1.0.19
	 *
	 * @see self::fill_query_vars() For allowede args and their defaults.
	 */
	public function prepare_query( $query = array() ) {
		global $wpdb;

		if ( empty( $this->query_vars ) || ! empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars  = $this->fill_query_vars( $query );
		}

		if ( ! empty( $this->query_vars['fields'] ) && 'all' !== $this->query_vars['fields'] ) {
			$this->query_vars['fields'] = wpinv_parse_list( $this->query_vars['fields'] );
		}

		do_action( 'getpaid_pre_get_customers', array( &$this ) );

		// Ensure that query vars are filled after 'getpaid_pre_get_customers'.
		$qv                = & $this->query_vars;
		$qv                = $this->fill_query_vars( $qv );
		$table             = $wpdb->prefix . 'getpaid_customers';
		$this->query_from  = "FROM $table";

		// Prepare query fields.
		$this->prepare_query_fields( $qv, $table );

		// Prepare query where.
		$this->prepare_query_where( $qv, $table );

		// Prepare query order.
		$this->prepare_query_order( $qv, $table );

		// limit
		if ( isset( $qv['number'] ) && $qv['number'] > 0 ) {
			if ( $qv['offset'] ) {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['offset'], $qv['number'] );
			} else {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
			}
		}

		do_action_ref_array( 'getpaid_after_customers_query', array( &$this ) );
	}

	/**
	 * Prepares the query fields.
	 *
	 * @since 1.0.19
	 *
	 * @param array $qv Query vars.
	 * @param string $table Table name.
	 */
	protected function prepare_query_fields( &$qv, $table ) {

		if ( is_array( $qv['fields'] ) ) {
			$qv['fields']   = array_unique( $qv['fields'] );
			$allowed_fields = array_keys( GetPaid_Customer_Data_Store::get_database_fields() );

			$query_fields = array();
			foreach ( $qv['fields'] as $field ) {
				if ( ! in_array( $field, $allowed_fields ) ) {
					continue;
				}

				$field          = sanitize_key( $field );
				$query_fields[] = "$table.`$field`";
			}
			$this->query_fields = implode( ',', $query_fields );
		} else {
			$this->query_fields = "$table.*";
		}

		if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		}

	}

	/**
	 * Prepares the query where.
	 *
	 * @since 1.0.19
	 *
	 * @param array $qv Query vars.
	 * @param string $table Table name.
	 */
	protected function prepare_query_where( &$qv, $table ) {
		global $wpdb;
		$this->query_where = 'WHERE 1=1';

		// Fields.
		foreach ( GetPaid_Customer_Data_Store::get_database_fields() as $field => $type ) {
			if ( 'any' !== $qv[ $field ] ) {

				// In.
				if ( is_array( $qv[ $field ] ) ) {
					$in                 = join( ',', array_fill( 0, count( $qv[ $field ] ), $type ) );
					$this->query_where .= $wpdb->prepare( " AND $table.`status` IN ( $in )", $qv[ $field ] );
				} elseif ( ! empty( $qv[ $field ] ) ) {
					$this->query_where .= $wpdb->prepare( " AND $table.`$field` = $type", $qv[ $field ] );
				}
			}

			// Min/Max.
			if ( '%f' === $type || '%d' === $type ) {

				// Min.
				if ( is_numeric( $qv[ $field . '_min' ] ) ) {
					$this->query_where .= $wpdb->prepare( " AND $table.`$field` >= $type", $qv[ $field . '_min' ] );
				}

				// Max.
				if ( is_numeric( $qv[ $field . '_max' ] ) ) {
					$this->query_where .= $wpdb->prepare( " AND $table.`$field` <= $type", $qv[ $field . '_max' ] );
				}
			}
		}

		if ( ! empty( $qv['include'] ) ) {
			$include            = implode( ',', wp_parse_id_list( $qv['include'] ) );
			$this->query_where .= " AND $table.`id` IN ($include)";
		} elseif ( ! empty( $qv['exclude'] ) ) {
			$exclude            = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND $table.`id` NOT IN ($exclude)";
		}

		// Date queries are allowed for the customer creation date.
		if ( ! empty( $qv['date_created_query'] ) && is_array( $qv['date_created_query'] ) ) {
			$date_created_query = new WP_Date_Query( $qv['date_created_query'], "$table.date_created" );
			$this->query_where .= $date_created_query->get_sql();
		}

		// Search.
		if ( ! empty( $qv['s'] ) ) {
			$this->query_where .= $this->get_search_sql( $qv['s'] );
		}
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @since 1.2.7
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $string The string to search for.
	 * @return string
	 */
	protected function get_search_sql( $string ) {
		global $wpdb;

		$searches = array();
		$string   = trim( $string, '%' );
		$like     = '%' . $wpdb->esc_like( $string ) . '%';

		foreach ( array_keys( GetPaid_Customer_Data_Store::get_database_fields() ) as $col ) {
			if ( 'id' === $col || 'user_id' === $col ) {
				$searches[] = $wpdb->prepare( "$col = %s", $string );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			} else {
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		return ' AND (' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Prepares the query order.
	 *
	 * @since 1.0.19
	 *
	 * @param array $qv Query vars.
	 * @param string $table Table name.
	 */
	protected function prepare_query_order( &$qv, $table ) {

		// sorting.
		$qv['order'] = isset( $qv['order'] ) ? strtoupper( $qv['order'] ) : '';
		$order       = $this->parse_order( $qv['order'] );

		// Default order is by 'id' (latest customers).
		if ( empty( $qv['orderby'] ) ) {
			$qv['orderby'] = array( 'id' );
		}

		// 'orderby' values may be an array, comma- or space-separated list.
		$ordersby      = array_filter( wpinv_parse_list( $qv['orderby'] ) );

		$orderby_array = array();
		foreach ( $ordersby as $_key => $_value ) {

			if ( is_int( $_key ) ) {
				// Integer key means this is a flat array of 'orderby' fields.
				$_orderby = $_value;
				$_order   = $order;
			} else {
				// Non-integer key means that the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order   = $_value;
			}

			$parsed = $this->parse_orderby( $_orderby, $table );

			if ( $parsed ) {
				$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
			}
		}

		// If no valid clauses were found, order by id.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = "id $order";
		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

	}

	/**
	 * Execute the query, with the current variables.
	 *
	 * @since 1.0.19
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function query() {
		global $wpdb;

		$qv =& $this->query_vars;

		// Return a non-null value to bypass the default GetPaid customers query and remember to set the
		// total_customers property.
		$this->results = apply_filters_ref_array( 'getpaid_customers_pre_query', array( null, &$this ) );

		if ( null === $this->results ) {
			$this->request = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

			if ( ( is_array( $qv['fields'] ) && 1 !== count( $qv['fields'] ) ) || 'all' === $qv['fields'] ) {
				$this->results = $wpdb->get_results( $this->request );
			} else {
				$this->results = $wpdb->get_col( $this->request );
			}

			if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
				$found_customers_query = apply_filters( 'getpaid_found_customers_query', 'SELECT FOUND_ROWS()', $this );
				$this->total_customers = (int) $wpdb->get_var( $found_customers_query );
			}
		}

		if ( 'all' === $qv['fields'] ) {
			foreach ( $this->results as $key => $customer ) {
				$this->set_cache( $customer->id, $customer, 'getpaid_customers' );
				$this->set_cache( $customer->user_id, $customer->id, 'getpaid_customer_ids_by_user_id' );
				$this->set_cache( $customer->email, $customer->id, 'getpaid_customer_ids_by_email' );
				$this->results[ $key ] = new GetPaid_Customer( $customer );
			}
		}

	}

	/**
	 * Set cache
	 *
	 * @param string  $id
	 * @param mixed   $data
	 * @param string  $group
	 * @param integer $expire
	 * @return boolean
	 */
	public function set_cache( $key, $data, $group = '', $expire = 0 ) {

		if ( empty( $key ) ) {
			return false;
		}

		wp_cache_set( $key, $data, $group, $expire );
	}

	/**
	 * Retrieve query variable.
	 *
	 * @since 1.0.19
	 *
	 * @param string $query_var Query variable key.
	 * @return mixed
	 */
	public function get( $query_var ) {
		if ( isset( $this->query_vars[ $query_var ] ) ) {
			return $this->query_vars[ $query_var ];
		}

		return null;
	}

	/**
	 * Set query variable.
	 *
	 * @since 1.0.19
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed $value Query variable value.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[ $query_var ] = $value;
	}

	/**
	 * Return the list of customers.
	 *
	 * @since 1.0.19
	 *
	 * @return GetPaid_Customer[]|array Found customers.
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Return the total number of customers for the current query.
	 *
	 * @since 1.0.19
	 *
	 * @return int Number of total customers.
	 */
	public function get_total() {
		return $this->total_customers;
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the customers query.
	 *
	 * @since 1.0.19
	 *
	 * @param string $orderby Alias for the field to order by.
	 *  @param string $table The current table.
	 * @return string Value to use in the ORDER clause, if `$orderby` is valid.
	 */
	protected function parse_orderby( $orderby, $table ) {

		$_orderby = '';
		if ( in_array( $orderby, array_keys( GetPaid_Customer_Data_Store::get_database_fields() ), true ) ) {
			$_orderby = "$table.`$orderby`";
		} elseif ( 'id' === strtolower( $orderby ) ) {
			$_orderby = "$table.id";
		} elseif ( 'include' === $orderby && ! empty( $this->query_vars['include'] ) ) {
			$include     = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby    = "FIELD( $table.id, $include_sql )";
		}

		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @since 1.0.19
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

}
