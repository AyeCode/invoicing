<?php
/**
 * Notes class.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles invoice notes.
 *
 */
class WPInv_Notes {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Filter inovice notes.
		add_action( 'pre_get_comments', array( $this, 'set_invoice_note_type' ), 11, 1 );
		add_action( 'comment_feed_where', array( $this, 'wpinv_comment_feed_where' ), 10, 1 );

		// Delete comments count cache whenever there is a new comment or a comment status changes.
		add_action( 'wp_insert_comment', array( $this, 'delete_comments_count_cache' ) );
		add_action( 'wp_set_comment_status', array( $this, 'delete_comments_count_cache' ) );

		// Count comments.
		add_filter( 'wp_count_comments', array( $this, 'wp_count_comments' ), 100, 2 );

		// Fires after notes are loaded.
		do_action( 'wpinv_notes_init', $this );
	}

	/**
	 * Filters invoice notes query to only include our notes.
	 *
	 * @param WP_Comment_Query $query
	 */
	public function set_invoice_note_type( $query ) {
		$post_id = ! empty( $query->query_vars['post_ID'] ) ? $query->query_vars['post_ID'] : $query->query_vars['post_id'];

		if ( $post_id && getpaid_is_invoice_post_type( get_post_type( $post_id ) ) ) {
			$query->query_vars['type'] = 'wpinv_note';
		} else {

			if ( empty( $query->query_vars['type__not_in'] ) ) {
				$query->query_vars['type__not_in'] = array();
			}

			$query->query_vars['type__not_in'] = wpinv_parse_list( $query->query_vars['type__not_in'] );
			$query->query_vars['type__not_in'] = array_merge( array( 'wpinv_note' ), $query->query_vars['type__not_in'] );
		}

		return $query;
	}

	/**
	 * Exclude notes from the comments feed.
	 */
	function wpinv_comment_feed_where( $where ){
		return $where . ( $where ? ' AND ' : '' ) . " comment_type != 'wpinv_note' ";
	}

	/**
	 * Delete comments count cache whenever there is
	 * new comment or the status of a comment changes. Cache
	 * will be regenerated next time WPInv_Notes::wp_count_comments()
	 * is called.
	 */
	public function delete_comments_count_cache() {
		delete_transient( 'getpaid_count_comments' );
	}

	/**
	 * Remove invoice notes from wp_count_comments().
	 *
	 * @since  2.2
	 * @param  object $stats   Comment stats.
	 * @param  int    $post_id Post ID.
	 * @return object
	 */
	public function wp_count_comments( $stats, $post_id ) {
		global $wpdb;

		if ( empty( $post_id ) ) {
			$stats = get_transient( 'getpaid_count_comments' );

			if ( ! $stats ) {
				$stats = array(
					'total_comments' => 0,
					'all'            => 0,
				);

				$count = $wpdb->get_results(
					"
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE comment_type NOT IN ('action_log', 'order_note', 'webhook_delivery', 'wpinv_note')
					GROUP BY comment_approved
					",
					ARRAY_A
				);

				$approved = array(
					'0'            => 'moderated',
					'1'            => 'approved',
					'spam'         => 'spam',
					'trash'        => 'trash',
					'post-trashed' => 'post-trashed',
				);

				foreach ( (array) $count as $row ) {
					// Don't count post-trashed toward totals.
					if ( ! in_array( $row['comment_approved'], array( 'post-trashed', 'trash', 'spam' ), true ) ) {
						$stats['all']            += $row['num_comments'];
						$stats['total_comments'] += $row['num_comments'];
					} elseif ( ! in_array( $row['comment_approved'], array( 'post-trashed', 'trash' ), true ) ) {
						$stats['total_comments'] += $row['num_comments'];
					}
					if ( isset( $approved[ $row['comment_approved'] ] ) ) {
						$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
					}
				}

				foreach ( $approved as $key ) {
					if ( empty( $stats[ $key ] ) ) {
						$stats[ $key ] = 0;
					}
				}

				$stats = (object) $stats;
				set_transient( 'getpaid_count_comments', $stats );
			}

		}

		return $stats;
	}

	/**
	 * Returns an array of invoice notes.
	 *
	 * @param int $invoice_id The invoice ID whose notes to retrieve.
	 * @param string $type Optional. Pass in customer to only return customer notes.
	 * @return WP_Comment[]
	 */
	public function get_invoice_notes( $invoice_id = 0, $type = 'all' ) {

		// Default comment args.
		$args = array(
			'post_id'   => $invoice_id,
			'orderby'   => 'comment_ID',
			'order'     => 'ASC',
		);

		// Maybe only show customer comments.
		if ( $type == 'customer' ) {
			$args['meta_key']   = '_wpi_customer_note';
			$args['meta_value'] = 1;
		}

		$args = apply_filters( 'wpinv_invoice_notes_args', $args, $this, $invoice_id, $type );

		return get_comments( $args );
	}

	/**
	 * Saves an invoice comment.
	 * 
	 * @param WPInv_Invoice $invoice The invoice to add the comment to.
	 * @param string $note The note content.
	 * @param string $note_author The name of the author of the note.
	 * @param bool $for_customer Whether or not this comment is meant to be sent to the customer.
	 * @return int|false The new note's ID on success, false on failure.
	 */
	function add_invoice_note( $invoice, $note, $note_author, $author_email, $for_customer = false ){

		do_action( 'wpinv_pre_insert_invoice_note', $invoice->get_id(), $note, $for_customer );

		/**
		 * Insert the comment.
		 */
		$note_id = wp_insert_comment(
			wp_filter_comment(
				array(
					'comment_post_ID'      => $invoice->get_id(),
					'comment_content'      => $note,
					'comment_agent'        => 'Invoicing',
					'user_id'              => get_current_user_id(),
					'comment_author'       => $note_author,
					'comment_author_IP'    => wpinv_get_ip(),
					'comment_author_email' => $author_email,
					'comment_author_url'   => $invoice->get_view_url(),
					'comment_type'         => 'wpinv_note',
				)
			)
		);

		do_action( 'wpinv_insert_payment_note', $note_id, $invoice->get_id(), $note, $for_customer );

		// Are we notifying the customer?
		if ( empty( $note_id ) || empty( $for_customer ) ) {
			return $note_id;
		}

		add_comment_meta( $note_id, '_wpi_customer_note', 1 );
		do_action( 'wpinv_new_customer_note', array( 'invoice_id' => $invoice->get_id(), 'user_note' => $note ) );
		do_action( 'getpaid_new_customer_note', $invoice, $note );
		return $note_id;
	}

}
