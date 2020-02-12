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
        add_action( 'wpinv_checkout_before_send_to_gateway', array( $this, 'wpinv_checkout_add_subscription' ), -999, 2 );
        add_action( 'wpinv_subscriptions_front_notices', array( $this, 'notices' ) );
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
            wpinv_get_capability(),
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

                $message = __( 'Subscription updated successfully.', 'invoicing' );

                break;

            case 'deleted' :

                $message = __( 'Subscription deleted successfully.', 'invoicing' );

                break;

            case 'cancelled' :

                $message = __( 'Subscription cancelled successfully.', 'invoicing' );

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
        if ( ! ( ! empty( $invoice->ID ) && $invoice->is_recurring() ) ) {
            return;
        }
        
        $item               = $invoice->get_recurring( true );
        if ( empty( $item ) ) {
            return;
        }

        $invoice_date       = $invoice->get_invoice_date( false );
        $status             = 'pending';

        $period             = $item->get_recurring_period( true );
        $interval           = $item->get_recurring_interval();
        $bill_times         = (int)$item->get_recurring_limit();
        $add_period         = $interval . ' ' . $period;
        $trial_period       = '';

        if ( $invoice->is_free_trial() ) {
            $status         = 'trialling';
            $trial_period   = $item->get_trial_period( true );
            $free_interval  = $item->get_trial_interval();
            $trial_period   = $free_interval . ' ' . $trial_period;

            $add_period     = $trial_period;
        }

        $expiration         = date_i18n( 'Y-m-d H:i:s', strtotime( '+' . $add_period  . ' 23:59:59', strtotime( $invoice_date ) ) );

        $args = array(
            'product_id'        => $item->ID,
            'customer_id'       => $invoice->user_id,
            'parent_payment_id' => $invoice->ID,
            'status'            => $status,
            'frequency'         => $interval,
            'period'            => $period,
            'initial_amount'    => $invoice->get_total(),
            'recurring_amount'  => $invoice->get_recurring_details( 'total' ),
            'bill_times'        => $bill_times,
            'created'           => $invoice_date,
            'expiration'        => $expiration,
            'trial_period'      => $trial_period,
            'profile_id'        => '',
            'transaction_id'    => '',
        );

        $subscription = wpinv_get_subscription( $invoice );

        if ( empty( $subscription ) ) {
            $subscription = new WPInv_Subscription();
            $subscription->create( $args );
        }
        
        return $subscription;
    }
}