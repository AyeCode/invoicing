<?php
/**
 * Contains a helper class for generating reports.
 *
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * GetPaid_Reports_Helper Class.
 */
class GetPaid_Reports_Helper {

	/**
	 * Get report totals such as invoice totals and discount amounts.
	 *
	 * Data example:
	 *
	 * 'subtotal' => array(
	 *     'type'     => 'invoice_data',
	 *     'function' => 'SUM',
	 *     'name'     => 'subtotal'
	 * )
	 *
	 * @param  array $args
	 * @return mixed depending on query_type
	 */
	public static function get_invoice_report_data( $args = array() ) {
		global $wpdb;

		$default_args = array(
			'data'                  => array(), // The data to retrieve.
			'where'                 => array(), // An array of where queries.
			'query_type'            => 'get_row', // wpdb query to run.
			'group_by'              => '', // What to group results by.
			'order_by'              => '', // What to order by.
			'limit'                 => '', // Results limit.
			'filter_range'          => array(), // An array of before and after dates to limit results by.
			'invoice_types'         => array( 'wpi_invoice' ), // An array of post types to retrieve.
			'invoice_status'        => array( 'publish', 'wpi-processing', 'wpi-onhold' ),
			'parent_invoice_status' => false, // Optionally filter by parent invoice status.
		);

		$args         = apply_filters( 'getpaid_reports_get_invoice_report_data_args', $args );
		$args         = wp_parse_args( $args, $default_args );

		extract( $args );

		if ( empty( $data ) ) {
			return '';
		}

		$query           = array();
		$query['select'] = 'SELECT ' . implode( ',', self::prepare_invoice_data( $data ) );
		$query['from']   = "FROM {$wpdb->posts} AS posts";
		$query['join']   = implode( ' ', self::prepare_invoice_joins( $data + $where, ! empty( $parent_invoice_status ) ) );

		$query['where']  = "
			WHERE 	posts.post_type 	IN ( '" . implode( "','", $invoice_types ) . "' )
			";

		if ( ! empty( $invoice_status ) ) {
			$query['where'] .= "
				AND 	posts.post_status 	IN ( '" . implode( "','", $invoice_status ) . "' )
			";
		}

		if ( ! empty( $parent_invoice_status ) ) {
			if ( ! empty( $invoice_status ) ) {
				$query['where'] .= " AND ( parent.post_status IN ( '" . implode( "','", $parent_invoice_status ) . "' ) OR parent.ID IS NULL ) ";
			} else {
				$query['where'] .= " AND parent.post_status IN ( '" . implode( "','", $parent_invoice_status ) . "' ) ";
			}
		}

		if ( ! empty( $filter_range['before'] ) ) {
			$query['where'] .= "
				AND 	posts.post_date < '" . date( 'Y-m-d H:i:s', strtotime( $filter_range['before'] ) ) . "'
			";
		}

		if ( ! empty( $filter_range['after'] ) ) {
			$query['where'] .= "
				AND 	posts.post_date > '" . date( 'Y-m-d H:i:s', strtotime( $filter_range['after'] ) ) . "'
			";
		}

		if ( ! empty( $where ) ) {

			foreach ( $where as $value ) {

				if ( strtolower( $value['operator'] ) == 'in' || strtolower( $value['operator'] ) == 'not in' ) {

					if ( is_array( $value['value'] ) ) {
						$value['value'] = implode( "','", $value['value'] );
					}

					if ( ! empty( $value['value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['value']}'";
				}

				if ( ! empty( $where_value ) ) {
					$query['where'] .= " AND {$value['key']} {$where_value}";
				}
			}
		}

		if ( $group_by ) {
			$query['group_by'] = "GROUP BY {$group_by}";
		}

		if ( $order_by ) {
			$query['order_by'] = "ORDER BY {$order_by}";
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT {$limit}";
		}

		$query = apply_filters( 'getpaid_reports_get_invoice_report_query', $query, $data );
		$query = implode( ' ', $query );

		return self::execute( $query_type, $query );

	}

	/**
	 * Prepares the data to select.
	 *
	 *
	 * @param  array $data
	 * @return array
	 */
	public static function prepare_invoice_data( $data ) {

		$prepared = array();

		foreach ( $data as $raw_key => $value ) {
			$key      = sanitize_key( $raw_key );
			$distinct = '';

			if ( isset( $value['distinct'] ) ) {
				$distinct = 'DISTINCT';
			}

			$get_key = self::get_invoice_table_key( $key, $value['type'] );

			if ( false === $get_key ) {
				// Skip to the next foreach iteration else the query will be invalid.
				continue;
			}

			if ( ! empty( $value['function'] ) ) {
				$get = "{$value['function']}({$distinct} {$get_key})";
			} else {
				$get = "{$distinct} {$get_key}";
			}

			$prepared[] = "{$get} as {$value['name']}";
		}

		return $prepared;

	}

	/**
	 * Prepares the joins to use.
	 *
	 *
	 * @param  array $data
	 * @param  bool $with_parent
	 * @return array
	 */
	public static function prepare_invoice_joins( $data, $with_parent ) {
		global $wpdb;

		$prepared = array();

		foreach ( $data as $raw_key => $value ) {
			$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
			$type      = isset( $value['type'] ) ? $value['type'] : false;
			$key       = sanitize_key( $raw_key );

			switch ( $type ) {
				case 'meta':
					$prepared[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS meta_{$key} ON ( posts.ID = meta_{$key}.post_id AND meta_{$key}.meta_key = '{$raw_key}' )";
					break;
				case 'parent_meta':
					$prepared[ "parent_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS parent_meta_{$key} ON (posts.post_parent = parent_meta_{$key}.post_id) AND (parent_meta_{$key}.meta_key = '{$raw_key}')";
					break;
				case 'invoice_data':
					$prepared['invoices'] = "{$join_type} JOIN {$wpdb->prefix}getpaid_invoices AS invoices ON posts.ID = invoices.post_id";
					break;
				case 'invoice_item':
					$prepared['invoice_items'] = "{$join_type} JOIN {$wpdb->prefix}getpaid_invoice_items AS invoice_items ON posts.ID = invoice_items.post_id";
					break;
			}
		}

		if ( $with_parent ) {
			$prepared['parent'] = "LEFT JOIN {$wpdb->posts} AS parent ON posts.post_parent = parent.ID";
		}

		return $prepared;

	}

	/**
	 * Retrieves the appropriate table key to use.
	 *
	 *
	 * @param  string $key
	 * @param  string $table
	 * @return string|false
	 */
	public static function get_invoice_table_key( $key, $table ) {

		$keys = array(
			'meta'         => "meta_{$key}.meta_value",
			'parent_meta'  => "parent_meta_{$key}.meta_value",
			'post_data'    => "posts.{$key}",
			'invoice_data' => "invoices.{$key}",
			'invoice_item' => "invoice_items.{$key}",
		);

		return isset( $keys[ $table ] ) ? $keys[ $table ] : false;

	}

	/**
	 * Executes a query and caches the result for a minute.
	 *
	 *
	 * @param  string $query_type
	 * @param  string $query
	 * @return mixed depending on query_type
	 */
	public static function execute( $query_type, $query ) {
		global $wpdb;

		$query_hash = md5( $query_type . $query );
		$result     = self::get_cached_query( $query_hash );
		if ( $result === false ) {
			self::enable_big_selects();

			$result = $wpdb->$query_type( $query );
			self::set_cached_query( $query_hash, $result );
		}

		return $result;

	}

	/**
	 * Enables big mysql selects for reports, just once for this session.
	 */
	protected static function enable_big_selects() {
		static $big_selects = false;

		global $wpdb;

		if ( ! $big_selects ) {
			$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
			$big_selects = true;
		}
	}

	/**
	 * Get the cached query result or null if it's not in the cache.
	 *
	 * @param string $query_hash The query hash.
	 *
	 * @return mixed|false The cache contents on success, false on failure to retrieve contents.
	 */
	protected static function get_cached_query( $query_hash ) {

		return wp_cache_get(
			$query_hash,
			strtolower( __CLASS__ )
		);

	}

	/**
	 * Set the cached query result.
	 *
	 * @param string $query_hash The query hash.
	 * @param mixed  $data The data to cache.
	 */
	protected static function set_cached_query( $query_hash, $data ) {

		wp_cache_set(
			$query_hash,
			$data,
			strtolower( __CLASS__ ),
			MINUTE_IN_SECONDS
		);

	}

}
