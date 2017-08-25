<?php
// Exit if accessed directly.
if (!defined( 'ABSPATH' ) ) exit;

function wpinv_subscription_init() {
    return WPInv_Subscriptions::instance();
}
add_action( 'plugins_loaded', 'wpinv_subscription_init', 100 );

/**
 * WPInv_Subscriptions Class.
 *
 * @since 1.0.0
 */
class WPInv_Subscriptions {

    private static $instance;

    /**
     * Main WPInv_Subscriptions Instance
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new WPInv_Subscriptions;

            self::$instance->init();
        }

        return self::$instance;
    }

    /**
     * Constructor -- prevent new instances
     *
     * @since 1.0.0
     */
    private function __construct(){

    }

    /**
     * Get things started
     *
     * Sets up inits actions and filters
     *
     * @since 1.0.0
     */
    function init() {

        self::setup_constants();
        self::actions();
        self::filters();

    }

    /**
     * Setup plugin constants.
     *
     * @access private
     * @since 1.0.0
     * @return void
     */
    private function setup_constants() {

        // Make sure CAL_GREGORIAN is defined.
        if ( ! defined( 'CAL_GREGORIAN' ) ) {
            define( 'CAL_GREGORIAN', 1 );
        }
    }

    /**
     * Add our actions
     *
     * @since  1.0.0
     * @return void
     */
    private function actions() {

        add_action( 'admin_menu', array( $this, 'wpinv_subscriptions_list' ), 10 );
        add_action( 'admin_notices', array( $this, 'notices' ) );
        add_action( 'init', array( $this, 'wpinv_post_actions' ) );
        add_action( 'init', array( $this, 'wpinv_get_actions' ) );
        add_action( 'wpinv_cancel_subscription', array( $this, 'wpinv_process_cancellation' ) );
        add_action( 'wpinv_checkout_before_send_to_gateway', array( $this, 'wpinv_checkout_add_subscription' ), 10, 2 );
    }

    /**
     * Add our filters
     *
     * @since  1.0
     * @return void
     */
    private function filters() {

    }

    /**
     * Register our Subscriptions submenu
     *
     * @since  2.4
     * @return void
     */
    public function wpinv_subscriptions_list() {
        add_submenu_page(
            'wpinv',
            __( 'Subscriptions', 'invoicing' ),
            __( 'Subscriptions', 'invoicing' ),
            'manage_invoicing',
            'wpinv-subscriptions',
            'wpinv_subscriptions_page'
        );
    }

    public function notices() {

        if( empty( $_GET['wpinv-message'] ) ) {
            return;
        }

        $type    = 'updated';
        $message = '';

        switch( strtolower( $_GET['wpinv-message'] ) ) {

            case 'updated' :

                $message = __( 'Subscription updated successfully', 'invoicing' );

                break;

            case 'deleted' :

                $message = __( 'Subscription deleted successfully', 'invoicing' );

                break;

            case 'cancelled' :

                $message = __( 'Subscription cancelled successfully', 'invoicing' );

                break;

        }

        if ( ! empty( $message ) ) {
            echo '<div class="' . esc_attr( $type ) . '"><p>' . $message . '</p></div>';
        }

    }

    /**
     * Every wpinv_action present in $_GET is called using WordPress's do_action function.
     * These functions are called on init.
     *
     * @since 1.0.0
     * @return void
     */
    function wpinv_get_actions() {
        if ( isset( $_GET['wpinv_action'] ) ) {
            do_action( 'wpinv_' . $_GET['wpinv_action'], $_GET );
        }
    }

    /**
     * Every wpinv_action present in $_POST is called using WordPress's do_action function.
     * These functions are called on init.
     *
     * @since 1.0.0
     * @return void
     */
    function wpinv_post_actions() {
        if ( isset( $_POST['wpinv_action'] ) ) {
            do_action( 'wpinv_' . $_POST['wpinv_action'], $_POST );
        }
    }

    /**
     * Get pretty subscription frequency
     *
     * @param $period
     * @param int $frequency_count The frequency of the period.
     * @return mixed|string|void
     */
    public static function wpinv_get_pretty_subscription_frequency( $period, $frequency_count = 1) {
        $frequency = '';
        //Format period details
        switch ( $period ) {
            case 'day' :
                $frequency = sprintf( _n('%d Day', '%d Days', $frequency_count, 'invoicing'), $frequency_count);
                break;
            case 'week' :
                $frequency = sprintf( _n('%d Week', '%d Weeks', $frequency_count, 'invoicing'), $frequency_count);
                break;
            case 'month' :
                $frequency = sprintf( _n('%d Month', '%d Months', $frequency_count, 'invoicing'), $frequency_count);
                break;
            case 'year' :
                $frequency = sprintf( _n('%d Year', '%d Years', $frequency_count, 'invoicing'), $frequency_count);
                break;
            default :
                $frequency = apply_filters( 'wpinv_recurring_subscription_frequency', $frequency, $period, $frequency_count );
                break;
        }

        return $frequency;

    }

    /**
     * Handles cancellation requests for a subscription
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function wpinv_process_cancellation( $data ) {


        if( empty( $data['sub_id'] ) ) {
            return;
        }

        if( ! is_user_logged_in() ) {
            return;
        }

        if( ! wp_verify_nonce( $data['_wpnonce'], 'wpinv-recurring-cancel' ) ) {
            wp_die( __( 'Error', 'invoicing' ), __( 'Nonce verification failed', 'invoicing' ), array( 'response' => 403 ) );
        }

        $data['sub_id'] = absint( $data['sub_id'] );
        $subscription   = new WPInv_Subscription( $data['sub_id'] );

        if( ! $subscription->can_cancel() ) {
            wp_die( __( 'Error', 'invoicing' ), __( 'This subscription cannot be cancelled', 'invoicing' ), array( 'response' => 403 ) );
        }

        try {

            do_action( 'wpinv_recurring_cancel_' . $subscription->gateway . '_subscription', $subscription, true );

            $subscription->cancel();

            if( is_admin() ) {

                wp_redirect( admin_url( 'admin.php?page=wpinv-subscriptions&wpinv-message=cancelled&id=' . $subscription->id ) );
                exit;

            } else {

                $redirect = remove_query_arg( array( '_wpnonce', 'wpinv_action', 'sub_id' ), add_query_arg( array( 'wpinv-message' => 'cancelled' ) ) );
                $redirect = apply_filters( 'wpinv_recurring_cancellation_redirect', $redirect, $subscription );
                wp_safe_redirect( $redirect );
                exit;

            }

        } catch ( Exception $e ) {
            wp_die( __( 'Error', 'invoicing' ), $e->getMessage(), array( 'response' => 403 ) );
        }

    }

    /**
     * Create subscription on checkout
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function wpinv_checkout_add_subscription( $invoice, $invoice_data ) {

        if(!$invoice->ID){
            return;
        }

        $invoice_obj = new WPInv_Invoice($invoice->ID);

        if ( !$invoice_obj->is_recurring() ) {
            return;
        }


        $item_id = $invoice_obj->get_recurring();
        $item = new WPInv_Item( $item_id );

        $period             = $item->get_recurring_period(true);
        $interval           = $item->get_recurring_interval();
        $bill_times         = (int)$item->get_recurring_limit();
        $initial_amount     = wpinv_sanitize_amount( $invoice_obj->get_total(), 2 );
        $recurring_amount   = wpinv_sanitize_amount( $invoice_obj->get_recurring_details( 'total' ), 2 );
        $status             = 'pending';
        $expiration         = date( 'Y-m-d H:i:s', strtotime( '+' . $interval . ' ' . $period  . ' 23:59:59', current_time( 'timestamp' ) ) );


        $trial_period = '';
        if ( $invoice_obj->is_free_trial() && $item->has_free_trial() ) {
            $trial_period       = $item->get_trial_period(true);
            $free_trial         = $item->get_free_trial();
            $trial_period       = ! empty( $invoice_obj->is_free_trial() ) ? $free_trial . ' ' . $trial_period : '';
            $expiration         = date( 'Y-m-d H:i:s', strtotime( '+' . $trial_period . ' 23:59:59', current_time( 'timestamp' ) ) );
            $status = 'trialling';
        }

        $args = array(
            'product_id'        => $item_id,
            'customer_id'       => $invoice_obj->user_id,
            'parent_payment_id' => $invoice_obj->ID,
            'status'            => $status,
            'frequency'         => $interval,
            'period'            => $period,
            'initial_amount'    => $initial_amount,
            'recurring_amount'  => $recurring_amount,
            'bill_times'        => $bill_times,
            'created'           => date( 'Y-m-d H:i:s' ),
            'expiration'        => $expiration,
            'trial_period'      => $trial_period,
            'profile_id'        => '',
            'transaction_id'    => '',
        );

        // Now create the subscription record
        $subscription = new WPInv_Subscription();
        $subscription->create( $args );
    }
}