<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_Subscription {

    private $subscriptions_db;

    public $id                = 0;
    public $user_id           = 0;
    public $interval          = 1;
    public $period            = '';
    public $trial_interval    = 0;
    public $trial_period      = '';
    public $initial_amount    = '';
    public $recurring_amount  = '';
    public $bill_times        = 0;
    public $transaction_id    = '';
    public $parent_invoice_id = 0;
    public $item_id           = 0;
    public $created           = '0000-00-00 00:00:00';
    public $expiration        = '0000-00-00 00:00:00';
    public $status            = 'pending';
    public $profile_id        = '';
    public $gateway           = '';
    public $user;
    public $post_type;

    function __construct( $id_or_object = 0, $by_profile_id = false ) {
        $this->subscriptions_db = new WPInv_Subscriptions_DB;

        if ( $by_profile_id ) {
            $subscription = $this->subscriptions_db->get_by( 'profile_id', $id_or_object );

            if ( empty( $subscription ) ) {
                return false;
            }

            $id_or_object = $subscription;
        }

        return $this->setup_subscription( $id_or_object );
    }

    private function setup_subscription( $id_or_object = 0 ) {
        if ( empty( $id_or_object ) ) {
            return false;
        }

        if ( is_numeric( $id_or_object ) ) {
            $subscription = $this->subscriptions_db->get( $id_or_object );
        } elseif ( is_object( $id_or_object ) ) {
            $subscription = $id_or_object;
        }

        if ( empty( $subscription ) ) {
            return false;
        }

        foreach( $subscription as $key => $value ) {
            $this->$key = $value;
        }

        $this->post_type = get_post_type( $this->parent_invoice_id );
        $this->user = get_user_by( 'id', $this->user_id );
        $this->gateway = wpinv_get_payment_gateway( $this->parent_invoice_id );

        do_action( 'wpinv_recurring_setup_subscription', $this );

        return $this;
    }

    public function __get( $key ) {
        if ( method_exists( $this, 'get_' . $key ) ) {
            return call_user_func( array( $this, 'get_' . $key ) );
        } else {
            return new WP_Error( 'wpinv-subscription-invalid-property', sprintf( __( 'Can\'t get property %s', 'invoicing' ), $key ) );
        }
    }

    public function create( $data = array() ) {
        if ( $this->id != 0 ) {
            return false;
        }

        $defaults = array(
            'user_id'           => 0,
            'interval'          => '',
            'period'            => '',
            'trial_pinterval'   => '',
            'trial_period'      => '',
            'initial_amount'    => '',
            'recurring_amount'  => '',
            'bill_times'        => 0,
            'parent_invoice_id' => 0,
            'item_id'           => 0,
            'created'           => '',
            'expiration'        => '',
            'status'            => '',
            'profile_id'        => '',
        );

        $args = wp_parse_args( $data, $defaults );

        if ( $args['expiration'] && strtotime( 'NOW', current_time( 'timestamp' ) ) > strtotime( $args['expiration'], current_time( 'timestamp' ) ) ) {
            if ( 'active' == $args['status'] || 'trialling' == $args['status'] ) {
                $args['status'] = 'expired';
            }
        }

        do_action( 'wpinv_subscription_pre_create', $args );

        $id = $this->subscriptions_db->insert( $args, 'subscription' );

        do_action( 'wpinv_subscription_post_create', $id, $args );

        return $this->setup_subscription( $id );
    }

    public function update( $args = array() ) {
        if ( isset( $args['status'] ) && strtolower( $this->status ) !== strtolower( $args['status'] ) ) {
            $this->add_note( sprintf( __( 'Status changed from %s to %s', 'invoicing' ), $this->status, $args['status'] ) );
        }

        $ret = $this->subscriptions_db->update( $this->id, $args );

        do_action( 'wpinv_recurring_update_subscription', $this->id, $args, $this );

        return $ret;
    }

    public function delete() {
        return $this->subscriptions_db->delete( $this->id );
    }

    public function get_original_invoice_id() {
        return $this->parent_invoice_id;
    }

    public function get_child_invoices() {
        $invoices = wpinv_get_invoices( array(
            'post_parent'    => (int)$this->parent_invoice_id,
            'posts_per_page' => '999',
            'post_status'    => 'any',
            'post_type'      => $this->post_type,
            'meta_key'       => '_wpinv_subscription_id',
            'meta_value'     => $this->id,
            'orderby'        => 'ID',
            'order'          => 'DESC',
        ) );

        return (array)$invoices;
    }

    public function get_total_invoices() {
        return count( $this->get_child_invoices() ) + 1;
    }

    public function get_times_billed() {
        $times_billed = $this->get_total_invoices();

        if ( ! empty( $this->trial_period ) ) {
            $times_billed -= 1;
        }

        return $times_billed;
    }

    public function get_lifetime_value() {
        $amount = 0.00;

        $parent_invoice   = new WPInv_Invoice( $this->parent_invoice_id );
        $ignored_statuses = array( 'wpi-refunded', 'pending', 'wpi-cancelled', 'wpi-failed' );

        if ( false === in_array( $parent_invoice->status, $ignored_statuses ) ) {
            foreach ( $parent_invoice->cart_details as $cart_item ) {
                if ( (int) $this->item_id === (int) $cart_item['id'] ) {
                    $amount += $cart_item['price'];
                    break;
                }
            }
        }

        $children = $this->get_child_invoices();

        if ( $children ) {
            foreach( $children as $child ) {
                $child_invoice = new WPInv_Invoice( $child->ID );
                
                if ( 'wpi-refunded' === $child_invoice->status ) {
                    continue;
                }

                $amount += $child_invoice->total;
            }
        }

        return $amount;
    }

    public function add_invoice( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'amount'         => '',
            'transaction_id' => '',
            'gateway'        => ''
        ) );

        if ( wpinv_payment_exists( $args['transaction_id'] ) ) {
            return false;
        }
        
        $parent_invoice = wpinv_get_invoice( $this->parent_invoice_id );
        if ( empty( $parent_invoice ) ) {
            return;
        }
        
        $invoice = new WPInv_Invoice();
        $invoice->set( 'parent_invoice', $this->parent_invoice_id );
        $invoice->set( 'currency', $parent_invoice->get_currency() );
        $invoice->set( 'transaction_id', $args['transaction_id'] );
        $invoice->set( 'key', $parent_invoice->get_key() );
        $invoice->set( 'ip', $parent_invoice->ip );
        $invoice->set( 'user_id', $parent_invoice->get_user_id() );
        $invoice->set( 'first_name', $parent_invoice->get_first_name() );
        $invoice->set( 'last_name', $parent_invoice->get_last_name() );
        $invoice->set( 'phone', $parent_invoice->phone );
        $invoice->set( 'address', $parent_invoice->address );
        $invoice->set( 'city', $parent_invoice->city );
        $invoice->set( 'country', $parent_invoice->country );
        $invoice->set( 'state', $parent_invoice->state );
        $invoice->set( 'zip', $parent_invoice->zip );
        $invoice->set( 'company', $parent_invoice->company );
        $invoice->set( 'vat_number', $parent_invoice->vat_number );
        $invoice->set( 'vat_rate', $parent_invoice->vat_rate );
        $invoice->set( 'adddress_confirmed', $parent_invoice->adddress_confirmed );

        if ( empty( $args['gateway'] ) ) {
            $invoice->set( 'gateway', $parent_invoice->get_gateway() );
        } else {
            $invoice->set( 'gateway', $args['gateway'] );
        }
        
        $recurring_details = $parent_invoice->get_recurring_details();

        // increase the earnings for each item in the subscription
        $items = $recurring_details['cart_details'];
        
        if ( $items ) {        
            $add_items      = array();
            $cart_details   = array();
            
            foreach ( $items as $item ) {
                $add_item             = array();
                $add_item['id']       = $item['id'];
                $add_item['quantity'] = $item['quantity'];
                
                $add_items[]    = $add_item;
                $cart_details[] = $item;
                break;
            }
            
            $invoice->set( 'items', $add_items );
            $invoice->cart_details = $cart_details;
        }
        
        $total              = $args['amount'];
        
        $subtotal           = $recurring_details['subtotal'];
        $tax                = $recurring_details['tax'];
        $discount           = $recurring_details['discount'];
        
        if ( $discount > 0 ) {
            $invoice->set( 'discount_code', $parent_invoice->discount_code );
        }
        
        $invoice->subtotal = wpinv_round_amount( $subtotal );
        $invoice->tax      = wpinv_round_amount( $tax );
        $invoice->discount = wpinv_round_amount( $discount );
        $invoice->total    = wpinv_round_amount( $total );
        $invoice->save();
        $invoice->update_meta( '_wpinv_subscription_id', $this->id );
        
        wpinv_update_payment_status( $invoice->ID, 'publish' );
        sleep(1);
        wpinv_update_payment_status( $invoice->ID, 'wpi-renewal' );
        
        do_action( 'wpinv_recurring_add_subscription_payment', $invoice, $parent_invoice, $this );
        do_action( 'wpinv_recurring_record_payment', $invoice->ID, $this->parent_invoice_id, $total, $this );

        return $invoice;
    }

    public function get_transaction_id() {
        if ( empty( $this->transaction_id ) ) {
            $txn_id = wpinv_get_payment_transaction_id( $this->parent_invoice_id );

            if ( ! empty( $txn_id ) && (int) $this->parent_invoice_id !== (int) $txn_id ) {
                $this->set_transaction_id( $txn_id );
            }
        }

        return $this->transaction_id;
    }

    public function set_transaction_id( $txn_id = '' ) {
        $this->update( array( 'transaction_id' => $txn_id ) );
        $this->transaction_id = $txn_id;
    }

    public function renew() {
        $expires = $this->get_expiration_time();

        if ( $expires > current_time( 'timestamp' ) && $this->is_active() ) {
            $base_date  = $expires;
        } else {
            $base_date  = current_time( 'timestamp' );
        }

        $last_day = cal_days_in_month( CAL_GREGORIAN, date_i18n( 'n', $base_date ), date_i18n( 'Y', $base_date ) );

        $interval = isset($this->interval) ? $this->interval : 1;
        $expiration = date_i18n( 'Y-m-d H:i:s', strtotime( '+' . $interval . ' ' . $this->period  . ' 23:59:59', $base_date ) );

        if ( date_i18n( 'j', $base_date ) == $last_day && 'day' != $this->period ) {
            $expiration = date_i18n( 'Y-m-d H:i:s', strtotime( $expiration . ' +2 days' ) );
        }

        $expiration  = apply_filters( 'wpinv_subscription_renewal_expiration', $expiration, $this->id, $this );

        do_action( 'wpinv_subscription_pre_renew', $this->id, $expiration, $this );

        $this->status = 'active';
        $times_billed = $this->get_times_billed();

        if ( $this->bill_times > 0 && $times_billed >= $this->bill_times ) {
            $this->complete();
            $this->status = 'completed';
        }

        $args = array(
            'expiration' => $expiration,
            'status'     => $this->status,
        );

        $this->update( $args );

        do_action( 'wpinv_subscription_post_renew', $this->id, $expiration, $this );
        do_action( 'wpinv_recurring_set_subscription_status', $this->id, $this->status, $this );
    }

    public function complete() {
        if ( 'cancelled' === $this->status ) {
            return;
        }

        $args = array(
            'status' => 'completed'
        );

        if ( $this->subscriptions_db->update( $this->id, $args ) ) {
            $this->add_note( sprintf( __( 'Status changed from %s to %s', 'invoicing' ), $this->status, 'completed' ) );

            $this->status = 'completed';

            do_action( 'wpinv_subscription_completed', $this->id, $this );
        }
    }

    public function expire( $check_expiration = false ) {
        $expiration = $this->expiration;

        if ( $check_expiration && $this->check_expiration() ) {
            if ( $expiration < $this->get_expiration() && current_time( 'timestamp' ) < $this->get_expiration_time() ) {
                return false;
            }
        }

        $args = array(
            'status' => 'expired'
        );

        if ( $this->subscriptions_db->update( $this->id, $args ) ) {
            $this->add_note( sprintf( __( 'Status changed from %s to %s', 'invoicing' ), $this->status, 'expired' ) );

            $this->status = 'expired';

            do_action( 'wpinv_subscription_expired', $this->id, $this );
        }
    }

    public function failing() {
        $args = array(
            'status' => 'failing'
        );

        if ( $this->subscriptions_db->update( $this->id, $args ) ) {
            $this->add_note( sprintf( __( 'Status changed from %s to %s', 'invoicing' ), $this->status, 'failing' ) );

            $this->status = 'failing';

            do_action( 'wpinv_subscription_failing', $this->id, $this );
        }
    }

    public function cancel() {
        if ( 'cancelled' === $this->status ) {
            return;
        }

        $args = array(
            'status' => 'cancelled'
        );

        if ( $this->subscriptions_db->update( $this->id, $args ) ) {
            if ( is_user_logged_in() ) {
                $userdata = get_userdata( get_current_user_id() );
                $user     = $userdata->user_login;
            } else {
                $user = __( 'gateway', 'invoicing' );
            }

            $note = sprintf( __( 'Subscription #%d cancelled by %s', 'invoicing' ), $this->id, $user );
            $this->add_note( $note );
            $this->status = 'cancelled';

            do_action( 'wpinv_subscription_cancelled', $this->id, $this );
        }
    }

    public function can_cancel() {
        return apply_filters( 'wpinv_subscription_can_cancel', false, $this );
    }

    public function get_cancel_url() {
        $url = wp_nonce_url( add_query_arg( array( 'wpi_action' => 'cancel_subscription', 'sub_id' => $this->id ) ), 'wpinv-recurring-cancel' );

        return apply_filters( 'wpinv_subscription_cancel_url', $url, $this );
    }

    public function can_renew() {
        return apply_filters( 'wpinv_subscription_can_renew', false, $this );
    }

    public function get_renew_url() {
        $url = wp_nonce_url( add_query_arg( array( 'wpi_action' => 'renew_subscription', 'sub_id' => $this->id ) ), 'wpinv-recurring-renew' );

        return apply_filters( 'wpinv_subscription_renew_url', $url, $this );
    }

    public function can_update() {
        return apply_filters( 'wpinv_subscription_can_update', false, $this );
    }

    public function get_update_url() {
        $url = add_query_arg( array( 'action' => 'update', 'subscription_id' => $this->id ) );

        return apply_filters( 'wpinv_subscription_update_url', $url, $this );
    }

    public function is_active() {
        $ret = false;

        if ( ! $this->is_expired() && ( $this->status == 'active' || $this->status == 'cancelled' || $this->status == 'trialling' ) ) {
            $ret = true;
        }

        return apply_filters( 'wpinv_subscription_is_active', $ret, $this->id, $this );
    }

    public function is_expired() {
        $ret = false;

        if ( $this->status == 'expired' ) {
            $ret = true;
        } elseif ( 'active' === $this->status || 'cancelled' === $this->status || $this->status == 'trialling'  ) {
            $ret        = false;
            $expiration = $this->get_expiration_time();

            if ( $expiration && strtotime( 'NOW', current_time( 'timestamp' ) ) > $expiration ) {
                $ret = true;

                if ( 'active' === $this->status || $this->status == 'trialling'  ) {
                    $this->expire();
                }
            }
        }

        return apply_filters( 'wpinv_subscription_is_expired', $ret, $this->id, $this );
    }

    public function get_expiration( $check_gateway = true ) {
        return $this->expiration;
    }

    public function check_expiration() {
        $ret   = false;
        
        $expiration = apply_filters( 'wpinv_subscription_ ' . $this->gateway . '_get_expiration', NULL, $this->id, $this );
        
        $class = edd_recurring()->get_gateway_class( $this->gateway );

        if ( $class && class_exists( $class ) ) {
            $gateway = new $class;

            if ( is_callable( array( $gateway, 'get_expiration' ) ) ) {
                $expiration = $gateway->get_expiration( $this );

                if ( ! is_wp_error( $expiration ) && $this->get_expiration_time() < strtotime( $expiration, current_time( 'timestamp' ) ) ) {
                    $this->update( array( 'expiration' => $expiration ) );
                    $this->expiration = $expiration;
                    $ret = true;

                    $this->add_note( sprintf( __( 'Expiration synced with gateway and updated to %s', 'invoicing' ), $expiration ) );

                    do_action( 'edd_recurring_check_expiration', $this, $expiration );
                }
            }
        }

        return $ret;
    }

    public function get_expiration_time() {
        return strtotime( $this->expiration, current_time( 'timestamp' ) );
    }

    public function get_status() {
        $this->is_expired();
        
        return $this->status;
    }

    public function get_status_label() {
        switch( $this->get_status() ) {
            case 'active' :
                $status = __( 'Active', 'invoicing' );
                break;
            case 'cancelled' :
                $status = __( 'Cancelled', 'invoicing' );
                break;
            case 'completed' :
                $status = __( 'Completed', 'invoicing' );
                break;
            case 'expired' :
                $status = __( 'Expired', 'invoicing' );
                break;
            case 'failing' :
                $status = __( 'Failing', 'invoicing' );
                break;
            case 'pending' :
                $status = __( 'Pending', 'invoicing' );
                break;
            case 'stopped' :
                $status = __( 'Stopped', 'invoicing' );
                break;
            case 'trialling' :
                $status = __( 'Trialling', 'invoicing' );
                break;
            default:
                $status = $this->get_status();
                
                if ( $status ) {
                    $status = __( wpinv_utf8_ucfirst( $status ), 'invoicing' );
                }
                break;
        }

        return $status;
    }

    public function get_notes( $length = 20, $paged = 1 ) {
        $length = is_numeric( $length ) ? $length : 20;
        $offset = is_numeric( $paged ) && $paged != 1 ? ( ( absint( $paged ) - 1 ) * $length ) : 0;

        $all_notes   = $this->get_raw_notes();
        $notes_array = array_reverse( array_filter( explode( "\n\n", $all_notes ) ) );

        $desired_notes = array_slice( $notes_array, $offset, $length );

        return $desired_notes;
    }

    public function get_notes_count() {
        $all_notes = $this->get_raw_notes();
        $notes_array = array_reverse( array_filter( explode( "\n\n", $all_notes ) ) );

        return count( $notes_array );
    }

    public function add_note( $note = '' ) {
        $note = trim( $note );
        if ( empty( $note ) ) {
            return false;
        }

        $notes = $this->get_raw_notes();

        if ( empty( $notes ) ) {
            $notes = '';
        }

        $note_string = date_i18n( 'F j, Y H:i:s', current_time( 'timestamp' ) ) . ' - ' . $note;
        $new_note    = apply_filters( 'edd_subscription_add_note_string', $note_string );
        $notes      .= "\n\n" . $new_note;

        do_action( 'edd_subscription_pre_add_note', $new_note, $this->id );

        $updated = $this->update( array( 'notes' => $notes ) );

        if ( $updated ) {
            $this->notes = $this->get_notes();
        }

        do_action( 'edd_subscription_post_add_note', $this->notes, $new_note, $this->id );

        return $new_note;
    }

    private function get_raw_notes() {
        $all_notes = $this->subscriptions_db->get_column( 'notes', $this->id );

        return (string) $all_notes;
    }
}
