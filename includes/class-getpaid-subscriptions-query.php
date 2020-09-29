<?php
/**
 * GetPaid_Subscriptions_Query class
 *
 * Contains core class used to query for subscriptions.
 *
 * @since 1.0.19
 */

/**
 * Main class used for querying subscriptions.
 *
 * @since 1.0.19
 *
 * @see GetPaid_Subscriptions_Query::prepare_query() for information on accepted arguments.
 */
class GetPaid_Subscriptions_Query {

	/**
	 * Query vars, after parsing
	 *
	 * @since 1.0.19
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * List of found subscriptions.
	 *
	 * @since 1.0.19
	 * @var array
	 */
	private $results;

	/**
	 * Total number of found subscriptions for the current query
	 *
	 * @since 1.0.19
	 * @var int
	 */
	private $total_subscriptions = 0;

	/**
	 * The SQL query used to fetch matching subscriptions.
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
			'status'            => 'all',
			'customer_in'       => array(),
			'customer_not_in'   => array(),
			'product_in'        => array(),
			'product_not_in'    => array(),
			'include'           => array(),
			'exclude'           => array(),
			'orderby'           => 'id',
			'order'             => 'DESC',
			'offset'            => '',
			'number'            => 10,
			'paged'             => 1,
			'count_total'       => true,
			'fields'            => 'all',
		);

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Prepare the query variables.
	 *
	 * @since 1.0.19
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string|array $query {
	 *     Optional. Array or string of Query parameters.
	 *
	 *     @type string|array $status              The subscription status to filter by. Can either be a single status or an array of statuses.
	 *                                             Default is all.
	 *     @type int[]        $customer_in         An array of customer ids to filter by.
	 *     @type int[]        $customer_not_in     An array of customer ids whose subscriptions should be excluded.
	 *     @type int[]        $invoice_in          An array of invoice ids to filter by.
	 *     @type int[]        $invoice_not_in      An array of invoice ids whose subscriptions should be excluded.
	 *     @type int[]        $product_in          An array of product ids to filter by.
	 *     @type int[]        $product_not_in      An array of product ids whose subscriptions should be excluded.
	 *     @type array        $date_created_query  A WP_Date_Query compatible array use to filter subscriptions by their date of creation.
	 *     @type array        $date_expires_query  A WP_Date_Query compatible array use to filter subscriptions by their expiration date.
	 *     @type array        $include             An array of subscription IDs to include. Default empty array.
	 *     @type array        $exclude             An array of subscription IDs to exclude. Default empty array.
	 *     @type string|array $orderby             Field(s) to sort the retrieved subscription by. May be a single value,
	 *                                             an array of values, or a multi-dimensional array with fields as
	 *                                             keys and orders ('ASC' or 'DESC') as values. Accepted values are
	 *                                             'id', 'customer_id', 'frequency', 'period', 'initial_amount,
	 *                                             'recurring_amount', 'bill_times', 'parent_payment_id', 'created', 'expiration'
	 *                                             'transaction_id', 'product_id', 'trial_period', 'include', 'status', 'profile_id'. Default array( 'id' ).
	 *     @type string       $order               Designates ascending or descending order of subscriptions. Order values
	 *                                             passed as part of an `$orderby` array take precedence over this
	 *                                             parameter. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 *     @type int          $offset              Number of subscriptions to offset in retrieved results. Can be used in
	 *                                             conjunction with pagination. Default 0.
	 *     @type int          $number              Number of subscriptions to limit the query for. Can be used in
	 *                                             conjunction with pagination. Value -1 (all) is supported, but
	 *                                             should be used with caution on larger sites.
	 *                                             Default 10.
	 *     @type int          $paged               When used with number, defines the page of results to return.
	 *                                             Default 1.
	 *     @type bool         $count_total         Whether to count the total number of subscriptions found. If pagination
	 *                                             is not needed, setting this to false can improve performance.
	 *                                             Default true.
	 *     @type string|array $fields              Which fields to return. Single or all fields (string), or array
	 *                                             of fields. Accepts 'id', 'customer_id', 'frequency', 'period', 'initial_amount,
	 *                                             'recurring_amount', 'bill_times', 'parent_payment_id', 'created', 'expiration'
	 *                                             'transaction_id', 'product_id', 'trial_period', 'status', 'profile_id'.
	 *                                             Use 'all' for all fields. Default 'all'.
	 * }
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

		do_action( 'getpaid_pre_get_subscriptions', array( &$this ) );

		// Ensure that query vars are filled after 'getpaid_pre_get_subscriptions'.
		$qv                =& $this->query_vars;
		$qv                = $this->fill_query_vars( $qv );
		$table             = $wpdb->prefix . 'wpinv_subscriptions';
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

		do_action_ref_array( 'getpaid_after_subscriptions_query', array( &$this ) );
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
			$qv['fields'] = array_unique( $qv['fields'] );

			$query_fields = array();
			foreach ( $qv['fields'] as $field ) {
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

		// Status.
		if ( 'all' !== $qv['status'] ) {
			$statuses           = wpinv_clean( wpinv_parse_list( $qv['status'] ) );
			$prepared_statuses  = join( ',', array_fill( 0, count( $statuses ), '%s' ) );
			$this->query_where .= $wpdb->prepare( " AND $table.`status` IN ( $prepared_statuses )", $statuses );
		}

		if ( ! empty( $qv['customer_in'] ) ) {
			$customer_in        = implode( ',', wp_parse_id_list( $qv['customer_in'] ) );
			$this->query_where .= " AND $table.`customer_id` IN ($customer_in)";
		} elseif ( ! empty( $qv['customer_not_in'] ) ) {
			$customer_not_in    = implode( ',', wp_parse_id_list( $qv['customer_not_in'] ) );
			$this->query_where .= " AND $table.`customer_id` NOT IN ($customer_not_in)";
		}

		if ( ! empty( $qv['product_in'] ) ) {
			$product_in         = implode( ',', wp_parse_id_list( $qv['product_in'] ) );
			$this->query_where .= " AND $table.`product_id` IN ($product_in)";
		} elseif ( ! empty( $qv['product_not_in'] ) ) {
			$product_not_in     = implode( ',', wp_parse_id_list( $qv['product_not_in'] ) );
			$this->query_where .= " AND $table.`product_id` NOT IN ($product_not_in)";
		}

		if ( ! empty( $qv['invoice_in'] ) ) {
			$invoice_in         = implode( ',', wp_parse_id_list( $qv['invoice_in'] ) );
			$this->query_where .= " AND $table.`parent_payment_id` IN ($invoice_in)";
		} elseif ( ! empty( $qv['invoice_not_in'] ) ) {
			$invoice_not_in     = implode( ',', wp_parse_id_list( $qv['invoice_not_in'] ) );
			$this->query_where .= " AND $table.`parent_payment_id` NOT IN ($invoice_not_in)";
		}

		if ( ! empty( $qv['include'] ) ) {
			$include            = implode( ',', wp_parse_id_list( $qv['include'] ) );
			$this->query_where .= " AND $table.`id` IN ($include)";
		} elseif ( ! empty( $qv['exclude'] ) ) {
			$exclude            = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND $table.`id` NOT IN ($exclude)";
		}

		// Date queries are allowed for the subscription creation date.
		if ( ! empty( $qv['date_created_query'] ) && is_array( $qv['date_created_query'] ) ) {
			$date_created_query = new WP_Date_Query( $qv['date_created_query'], "$table.created" );
			$this->query_where .= $date_created_query->get_sql();
		}

		// Date queries are also allowed for the subscription expiration date.
		if ( ! empty( $qv['date_expires_query'] ) && is_array( $qv['date_expires_query'] ) ) {
			$date_expires_query = new WP_Date_Query( $qv['date_expires_query'], "$table.expiration" );
			$this->query_where .= $date_expires_query->get_sql();
		}

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

		// Default order is by 'id' (latest subscriptions).
		if ( empty( $qv['orderby'] ) ) {
			$qv['orderby'] = array( 'id' );
		}

		// 'orderby' values may be an array, comma- or space-separated list.
		$ordersby      = array_filter( wpinv_parse_list(  $qv['orderby'] ) );

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

		// Return a non-null value to bypass the default GetPaid subscriptions query and remember to set the
		// total_subscriptions property.
		$this->results = apply_filters_ref_array( 'getpaid_subscriptions_pre_query', array( null, &$this ) );

		if ( null === $this->results ) {
			$this->request = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

			if ( ( is_array( $qv['fields'] ) && 1 != count( $qv['fields'] ) ) || 'all' == $qv['fields'] ) {
				$this->results = $wpdb->get_results( $this->request );
			} else {
				$this->results = $wpdb->get_col( $this->request );
			}

			if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
				$found_subscriptions_query = apply_filters( 'getpaid_found_subscriptions_query', 'SELECT FOUND_ROWS()', $this );
				$this->total_subscriptions   = (int) $wpdb->get_var( $found_subscriptions_query );
			}
		}

		if ( 'all' == $qv['fields'] ) {
			foreach ( $this->results as $key => $subscription ) {
				wp_cache_set( $subscription->id, $subscription, 'getpaid_subscriptions' );
				wp_cache_set( $subscription->profile_id, $subscription->id, 'getpaid_subscription_profile_ids_to_subscription_ids' );
				wp_cache_set( $subscription->transaction_id, $subscription->id, 'getpaid_subscription_transaction_ids_to_subscription_ids' );
				wp_cache_set( $subscription->transaction_id, $subscription->id, 'getpaid_subscription_transaction_ids_to_subscription_ids' );
				$this->results[ $key ] = new WPInv_Subscription( $subscription );
			}
		}

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
	 * Return the list of subscriptions.
	 *
	 * @since 1.0.19
	 *
	 * @return WPInv_Subscription[]|array Found subscriptions.
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Return the total number of subscriptions for the current query.
	 *
	 * @since 1.0.19
	 *
	 * @return int Number of total subscriptions.
	 */
	public function get_total() {
		return $this->total_subscriptions;
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the subscriptions query.
	 *
	 * @since 1.0.19
	 *
	 * @param string $orderby Alias for the field to order by.
	 *  @param string $table The current table.
	 * @return string Value to use in the ORDER clause, if `$orderby` is valid.
	 */
	protected function parse_orderby( $orderby, $table ) {

		$_orderby = '';
		if ( in_array( $orderby, array( 'customer_id', 'frequency', 'period', 'initial_amount', 'recurring_amount', 'bill_times', 'transaction_id', 'parent_payment_id', 'product_id', 'created', 'expiration', 'trial_period', 'status', 'profile_id' ) ) ) {
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
