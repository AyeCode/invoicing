<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Invoicing Subscriptions List Table Class
 *
 * @access      private
 */
class WPInv_Subscriptions_List_Table extends WP_List_Table {

    public $per_page        = 30;
    public $total_count     = 0;
    public $active_count    = 0;
    public $cancelled_count = 0;
    public $completed_count = 0;
    public $expired_count   = 0;
    public $failing_count   = 0;
    public $pending_count   = 0;
    public $stopped_count   = 0;
    public $trialling_count = 0;

    function __construct(){
        global $status, $page;

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
     * @since 2.4
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
            'all'       => sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( array( 'status', 'paged' ) ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All','easy-digital-downloads' ) . $total_count ),
            'active'    => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'active', 'paged' => FALSE ) ), $current === 'active' ? ' class="current"' : '', __('Active','easy-digital-downloads' ) . $active_count ),
            'pending'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'pending', 'paged' => FALSE ) ), $current === 'pending' ? ' class="current"' : '', __('Pending','easy-digital-downloads' ) . $pending_count ),
            'expired'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'expired', 'paged' => FALSE ) ), $current === 'expired' ? ' class="current"' : '', __('Expired','easy-digital-downloads' ) . $expired_count ),
            'completed' => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'completed', 'paged' => FALSE ) ), $current === 'completed' ? ' class="current"' : '', __('Completed','easy-digital-downloads' ) . $completed_count ),
            'trialling'  => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'trialling', 'paged' => FALSE ) ), $current === 'trialling' ? ' class="current"' : '', __('Trialling','easy-digital-downloads' ) . $trialling_count ),
            'cancelled' => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'cancelled', 'paged' => FALSE ) ), $current === 'cancelled' ? ' class="current"' : '', __('Cancelled','easy-digital-downloads' ) . $cancelled_count ),
            'failing'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( array( 'status' => 'failing', 'paged' => FALSE ) ), $current === 'failing' ? ' class="current"' : '', __('Failing','easy-digital-downloads' ) . $failing_count ),
        );

        return apply_filters( 'edd_recurring_subscriptions_table_views', $views );
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
            <?php do_action( 'edd_recurring_subscription_search_box' ); ?>
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
     * @since       2.4
     * @return      string
     */
    function column_default( $item, $column_name ) {
        return $item->$column_name;
    }

    /**
     * Customer column
     *
     * @access      private
     * @since       2.4
     * @return      string
     */
    function column_customer_id( $item ) {
        $subscriber = new EDD_Recurring_Subscriber( $item->customer_id );
        $customer   = ! empty( $subscriber->name ) ? $subscriber->name : $subscriber->email;

        return '<a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=edd-customers&view=overview&id=' . $subscriber->id ) ) . '">' . $customer . '</a>';
    }


    /**
     * Status column
     *
     * @access      private
     * @since       2.4
     * @return      string
     */
    function column_status( $item ) {
        return $item->get_status_label();
    }

    /**
     * Period column
     *
     * @access      private
     * @since       2.4
     * @return      string
     */
    function column_period( $item ) {

        $period = EDD_Recurring()->get_pretty_subscription_frequency( $item->period,$item->frequency );

        return edd_currency_filter( edd_format_amount( $item->recurring_amount ), edd_get_payment_currency_code( $item->parent_payment_id ) ) . ' / ' . $period;
    }

    /**
     * Billing Times column
     *
     * @access      private
     * @since       2.4
     * @return      string
     */
    function column_bill_times( $item ) {
        return $item->get_times_billed() . ' / ' . ( ( $item->bill_times == 0 ) ? 'Until Cancelled' : $item->bill_times );
    }

    /**
     * Initial Amount column
     *
     * @access      private
     * @since       2.4
     * @return      string
     */
    function column_initial_amount( $item ) {
        return edd_currency_filter( edd_format_amount( $item->initial_amount ), edd_get_payment_currency_code( $item->parent_payment_id ) );
    }

    /**
     * Renewal date column
     *
     * @access      private
     * @since       2.4
     * @return      string
     */
    function column_renewal_date( $item ) {
        return $renewal_date = ! empty( $item->expiration ) ? date_i18n( get_option( 'date_format' ), strtotime( $item->expiration ) ) : __( 'N/A', 'edd-recurring' );
    }

    /**
     * Payment column
     *
     * @access      private
     * @since       2.4
     * @return      string
     */
    function column_parent_payment_id( $item ) {
        return '<a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $item->parent_payment_id ) ) . '">' . edd_get_payment_number( $item->parent_payment_id ) . '</a>';
    }

    /**
     * Product ID column
     *
     * @access      private
     * @since       2.4
     * @return      string
     */
    function column_product_id( $item ) {
        return '<a href="' . esc_url( admin_url( 'post.php?action=edit&post=' . $item->product_id ) ) . '">' . get_the_title( $item->product_id ) . '</a>';
    }

    /**
     * Render the edit column
     *
     * @access      private
     * @since       2.0
     * @return      string
     */
    function column_actions( $item ) {
        return '<a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=edd-subscriptions&id=' . $item->id ) ) . '" title="' . esc_attr( __( 'View or edit subscription', 'edd-recurring' ) ) . '">' . __( 'View', 'edd-recurring' ) . '</a>';
    }


    /**
     * Retrieve the table columns
     *
     * @access      private
     * @since       2.4
     * @return      array
     */

    function get_columns(){
        $columns = array(
            'customer_id'       => __( 'Customer', 'edd-recurring' ),
            'status'            => __( 'Status', 'edd-recurring' ),
            'period'            => __( 'Billing Cycle', 'edd-recurring' ),
            'initial_amount'    => __( 'Initial Amount', 'edd-recurring' ),
            'bill_times'        => __( 'Times Billed', 'edd-recurring' ),
            'renewal_date'      => __( 'Renewal Date', 'edd-recurring' ),
            'parent_payment_id' => __( 'Payment', 'edd-recurring' ),
            'product_id'        => edd_get_label_singular(),
            'actions'           => __( 'Actions', 'edd-recurring' ),
        );

        return apply_filters( 'edd_report_subscription_columns', $columns );
    }

    /**
     * Retrieve the current page number
     *
     * @access      private
     * @since       2.4
     * @return      int
     */
    function get_paged() {
        return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
    }

    public function get_subscription_counts() {

        global $wp_query;

        $db = new EDD_Subscriptions_DB;

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
     * @since       2.4
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

        $db     = new EDD_Subscriptions_DB;
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
