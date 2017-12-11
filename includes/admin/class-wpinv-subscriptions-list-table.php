<?php
/**
 * Subscription List Table Class
 *
 * @since 1.0.0
 * @package Invoicing
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Load WP_List_Table if not loaded
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Subscriptions List Table Class
 *
 * @access      private
 */
class WPInv_Subscription_Reports_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @since       1.0.0
	 */

	public $per_page        = 20;
	public $total_count     = 0;
	public $active_count    = 0;
	public $pending_count   = 0;
	public $expired_count   = 0;
	public $completed_count = 0;
	public $trialling_count  = 0;
	public $cancelled_count = 0;
	public $failing_count   = 0;

	/**
	 * Get things started
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      void
	 */
	function __construct(){
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular'  => 'subscription',
			'plural'    => 'subscriptions',
			'ajax'      => false
		) );

		$this->get_subscription_counts();

	}

	/**
	 * Retrieve the view types
	 *
	 * @access public
	 * @since 1.0.0
	 * @return array $views All the views available
	 */
	public function get_views() {

		$current         = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$total_count     = '&nbsp;<span class="count">(' . $this->total_count    . ')</span>';
		$active_count    = '&nbsp;<span class="count">(' . $this->active_count . ')</span>';
		$pending_count   = '&nbsp;<span class="count">(' . $this->pending_count . ')</span>';
		$expired_count   = '&nbsp;<span class="count">(' . $this->expired_count  . ')</span>';
		$completed_count = '&nbsp;<span class="count">(' . $this->completed_count . ')</span>';
		$trialling_count  = '&nbsp;<span class="count">(' . $this->trialling_count   . ')</span>';
		$cancelled_count = '&nbsp;<span class="count">(' . $this->cancelled_count   . ')</span>';
		$failing_count   = '&nbsp;<span class="count">(' . $this->failing_count   . ')</span>';

		$views = array(
			'all'       => sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( array( 'status', 'paged' ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All','invoicing' ) . $total_count ),
			'active'    => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'active', 'paged' => FALSE ) ), $current === 'active' ? ' class="current"' : '', __('Active','invoicing' ) . $active_count ),
			'pending'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'pending', 'paged' => FALSE ) ), $current === 'pending' ? ' class="current"' : '', __('Pending','invoicing' ) . $pending_count ),
			'expired'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'expired', 'paged' => FALSE ) ), $current === 'expired' ? ' class="current"' : '', __('Expired','invoicing' ) . $expired_count ),
			'completed' => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'completed', 'paged' => FALSE ) ), $current === 'completed' ? ' class="current"' : '', __('Completed','invoicing' ) . $completed_count ),
			'trialling'  => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'trialling', 'paged' => FALSE ) ), $current === 'trialling' ? ' class="current"' : '', __('Trialling','invoicing' ) . $trialling_count ),
			'cancelled' => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'cancelled', 'paged' => FALSE ) ), $current === 'cancelled' ? ' class="current"' : '', __('Cancelled','invoicing' ) . $cancelled_count ),
			'failing'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'failing', 'paged' => FALSE ) ), $current === 'failing' ? ' class="current"' : '', __('Failing','invoicing' ) . $failing_count ),
		);

		return apply_filters( 'wpinv_recurring_subscriptions_table_views', $views );
	}

	/**
	 * Show the search field
	 *
	 * @since 2.5
	 * @access public
	 *
	 * @param string $text Label for the search box
	 * @param string $input_id ID of the search box
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id ) {

		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
?>
		<p class="search-box">
			<?php do_action( 'wpinv_recurring_subscription_search_box' ); ?>
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text, 'button', false, false, array('ID' => 'search-submit') ); ?><br/>
		</p>
<?php
	}

	/**
	 * Render most columns
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_default( $item, $column_name ) {
		return $item->$column_name;
	}

    /**
     * Subscription id column
     *
     * @access      private
     * @since       1.0.0
     * @return      string
     */
    function column_sub_id( $item ) {
        return '<a href="' . esc_url( admin_url( 'admin.php?page=wpinv-subscriptions&id=' . $item->id ) ) . '" target="_blank">' . $item->id . '</a>';
    }

	/**
	 * Customer column
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_customer_id( $item ) {
		$subscriber = get_userdata( $item->customer_id );
		$customer   = ! empty( $subscriber->display_name ) ? $subscriber->display_name : $subscriber->user_email;

		return '<a href="' . esc_url( get_edit_user_link( $item->customer_id ) ) . '" target="_blank">' . $customer . '</a>';
	}

	/**
	 * Status column
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_status( $item ) {
		return $item->get_status_label();
	}

	/**
	 * Period column
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_period( $item ) {

		$period = WPInv_Subscriptions::wpinv_get_pretty_subscription_frequency( $item->period,$item->frequency );

		return wpinv_price( wpinv_format_amount( $item->recurring_amount ), wpinv_get_invoice_currency_code( $item->parent_payment_id ) ) . ' / ' . $period;
	}

	/**
	 * Billing Times column
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_bill_times( $item ) {
		return $item->get_times_billed() . ' / ' . ( ( $item->bill_times == 0 ) ? 'Until Cancelled' : $item->bill_times );
	}

	/**
	 * Initial Amount column
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_initial_amount( $item ) {
		return wpinv_price( wpinv_format_amount( $item->initial_amount ), wpinv_get_invoice_currency_code( $item->parent_payment_id ) );
	}

	/**
	 * Renewal date column
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_renewal_date( $item ) {
		return $renewal_date = ! empty( $item->expiration ) ? date_i18n( get_option( 'date_format' ), strtotime( $item->expiration ) ) : __( 'N/A', 'invoicing' );
	}

	/**
	 * Payment column
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_parent_payment_id( $item ) {
		return '<a href="' . get_edit_post_link( $item->parent_payment_id ) . '" target="_blank">' . wpinv_get_invoice_number( $item->parent_payment_id ) . '</a>';
	}

	/**
	 * Product ID column
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      string
	 */
	function column_product_id( $item ) {
		return '<a href="' . esc_url( admin_url( 'post.php?action=edit&post=' . $item->product_id ) ) . '" target="_blank">' . get_the_title( $item->product_id ) . '</a>';
	}

	/**
	 * Render the edit column
	 *
	 * @access      private
	 * @since       2.0
	 * @return      string
	 */
	function column_actions( $item ) {
		return '<a href="' . esc_url( admin_url( 'admin.php?page=wpinv-subscriptions&id=' . $item->id ) ) . '" title="' . esc_attr( __( 'View or edit subscription', 'invoicing' ) ) . '" target="_blank">' . __( 'View', 'invoicing' ) . '</a>';
	}


	/**
	 * Retrieve the table columns
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      array
	 */

	function get_columns(){
		$columns = array(
			'sub_id'            => __( 'ID', 'invoicing' ),
			'customer_id'       => __( 'Customer', 'invoicing' ),
			'status'            => __( 'Status', 'invoicing' ),
			'period'            => __( 'Billing Cycle', 'invoicing' ),
			'initial_amount'    => __( 'Initial Amount', 'invoicing' ),
			'bill_times'        => __( 'Times Billed', 'invoicing' ),
			'renewal_date'      => __( 'Renewal Date', 'invoicing' ),
			'parent_payment_id' => __( 'Invoice', 'invoicing' ),
			'product_id'        => __( 'Item', 'invoicing' ),
			'actions'           => __( 'Actions', 'invoicing' ),
		);

		return apply_filters( 'wpinv_report_subscription_columns', $columns );
	}

	/**
	 * Retrieve the current page number
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      int
	 */
	function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Retrieve the subscription counts
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function get_subscription_counts() {

		global $wp_query;

		$db = new WPInv_Subscriptions_DB;

		$search = ! empty( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

		$this->total_count     = $db->count();
		$this->active_count    = $db->count( array( 'status' => 'active', 'search' => $search ) );
		$this->pending_count   = $db->count( array( 'status' => 'pending', 'search' => $search ) );
		$this->expired_count   = $db->count( array( 'status' => 'expired', 'search' => $search ) );
		$this->trialling_count  = $db->count( array( 'status' => 'trialling', 'search' => $search ) );
		$this->cancelled_count = $db->count( array( 'status' => 'cancelled', 'search' => $search ) );
		$this->completed_count = $db->count( array( 'status' => 'completed', 'search' => $search ) );
		$this->failing_count   = $db->count( array( 'status' => 'failing', 'search' => $search ) );

	}

	/**
	 * Setup the final data for the table
	 *
	 * @access      private
	 * @since       1.0.0
	 * @uses        $this->_column_headers
	 * @uses        $this->items
	 * @uses        $this->get_columns()
	 * @uses        $this->get_sortable_columns()
	 * @uses        $this->get_pagenum()
	 * @uses        $this->set_pagination_args()
	 * @return      array
	 */
	function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$status   = isset( $_GET['status'] ) ? $_GET['status'] : 'any';
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();

		$db     = new WPInv_Subscriptions_DB;
		$search = ! empty( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$args   = array(
			'number' => $this->per_page,
			'offset' => $this->per_page * ( $this->get_paged() - 1 ),
			'search' => $search
		);

		if ( 'any' !== $status ) {
			$args['status'] = $status;
		}

		$this->items = $db->get_subscriptions( $args );

		switch ( $status ) {
			case 'active':
				$total_items = $this->active_count;
				break;
			case 'pending':
				$total_items = $this->pending_count;
				break;
			case 'expired':
				$total_items = $this->expired_count;
				break;
			case 'cancelled':
				$total_items = $this->cancelled_count;
				break;
			case 'failing':
				$total_items = $this->failing_count;
				break;
			case 'trialling':
				$total_items = $this->trialling_count;
				break;
			case 'completed':
				$total_items = $this->completed_count;
				break;
			case 'any':
			default:
				$total_items = $this->total_count;
				break;
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total_items / $this->per_page )
		) );
	}
}
