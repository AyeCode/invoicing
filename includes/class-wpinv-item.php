<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPInv_Item {
    public $ID = 0;
    private $type;
    private $title;
    private $custom_id;
    private $price;
    private $status;
    private $custom_name;
    private $custom_singular_name;
    private $vat_rule;
    private $vat_class;
    private $editable;
    private $excerpt;
    private $is_recurring;
    private $recurring_period;
    private $recurring_interval;
    private $recurring_limit;
    private $free_trial;
    private $trial_period;
    private $trial_interval;

    public $post_author = 0;
    public $post_date = '0000-00-00 00:00:00';
    public $post_date_gmt = '0000-00-00 00:00:00';
    public $post_content = '';
    public $post_title = '';
    public $post_excerpt = '';
    public $post_status = 'publish';
    public $comment_status = 'open';
    public $ping_status = 'open';
    public $post_password = '';
    public $post_name = '';
    public $to_ping = '';
    public $pinged = '';
    public $post_modified = '0000-00-00 00:00:00';
    public $post_modified_gmt = '0000-00-00 00:00:00';
    public $post_content_filtered = '';
    public $post_parent = 0;
    public $guid = '';
    public $menu_order = 0;
    public $post_mime_type = '';
    public $comment_count = 0;
    public $filter;


    public function __construct( $_id = false, $_args = array() ) {
        $item = WP_Post::get_instance( $_id );
        return $this->setup_item( $item );
    }

    private function setup_item( $item ) {
        if( ! is_object( $item ) ) {
            return false;
        }

        if( ! is_a( $item, 'WP_Post' ) ) {
            return false;
        }

        if( 'wpi_item' !== $item->post_type ) {
            return false;
        }

        foreach ( $item as $key => $value ) {
            switch ( $key ) {
                default:
                    $this->$key = $value;
                    break;
            }
        }

        return true;
    }

    public function __get( $key ) {
        if ( method_exists( $this, 'get_' . $key ) ) {
            return call_user_func( array( $this, 'get_' . $key ) );
        } else {
            return new WP_Error( 'wpinv-item-invalid-property', sprintf( __( 'Can\'t get property %s', 'invoicing' ), $key ) );
        }
    }

    public function create( $data = array(), $wp_error = false ) {
        if ( $this->ID != 0 ) {
            return false;
        }

        $defaults = array(
            'post_type'   => 'wpi_item',
            'post_status' => 'draft',
            'post_title'  => __( 'New Invoice Item', 'invoicing' )
        );

        $args = wp_parse_args( $data, $defaults );

        do_action( 'wpinv_item_pre_create', $args );

        $id = wp_insert_post( $args, $wp_error );
        if ($wp_error && is_wp_error($id)) {
            return $id;
        }
        if ( !$id ) {
            return false;
        }
        
        $item = WP_Post::get_instance( $id );
        
        if (!empty($item) && !empty($data['meta'])) {
            $this->ID = $item->ID;
            $this->save_metas($data['meta']);
        }
        
        // Set custom id if not set.
        if ( empty( $data['meta']['custom_id'] ) && !$this->get_custom_id() ) {
            $this->save_metas( array( 'custom_id' => $id ) );
        }

        do_action( 'wpinv_item_create', $id, $args );

        return $this->setup_item( $item );
    }
    
    public function update( $data = array(), $wp_error = false ) {
        if ( !$this->ID > 0 ) {
            return false;
        }
        
        $data['ID'] = $this->ID;

        do_action( 'wpinv_item_pre_update', $data );
        
        $id = wp_update_post( $data, $wp_error );
        if ($wp_error && is_wp_error($id)) {
            return $id;
        }
        
        if ( !$id ) {
            return false;
        }

        $item = WP_Post::get_instance( $id );
        if (!empty($item) && !empty($data['meta'])) {
            $this->ID = $item->ID;
            $this->save_metas($data['meta']);
        }
        
        // Set custom id if not set.
        if ( empty( $data['meta']['custom_id'] ) && !$this->get_custom_id() ) {
            $this->save_metas( array( 'custom_id' => $id ) );
        }

        do_action( 'wpinv_item_update', $id, $data );

        return $this->setup_item( $item );
    }

    public function get_ID() {
        return $this->ID;
    }

    public function get_name() {
        return get_the_title( $this->ID );
    }
    
    public function get_title() {
        return get_the_title( $this->ID );
    }
    
    public function get_status() {
        return get_post_status( $this->ID );
    }
    
    public function get_summary() {
        return get_the_excerpt( $this->ID );
    }

    public function get_price() {
        if ( ! isset( $this->price ) ) {
            $this->price = get_post_meta( $this->ID, '_wpinv_price', true );
            
            if ( $this->price ) {
                $this->price = wpinv_sanitize_amount( $this->price );
            } else {
                $this->price = 0;
            }
        }
        
        return apply_filters( 'wpinv_get_item_price', $this->price, $this->ID );
    }
    
    public function get_vat_rule() {
        global $wpinv_euvat;
        
        if( !isset( $this->vat_rule ) ) {
            $this->vat_rule = get_post_meta( $this->ID, '_wpinv_vat_rule', true );

            if ( empty( $this->vat_rule ) ) {        
                $this->vat_rule = $wpinv_euvat->allow_vat_rules() ? 'digital' : 'physical';
            }
        }
        
        return apply_filters( 'wpinv_get_item_vat_rule', $this->vat_rule, $this->ID );
    }
    
    public function get_vat_class() {
        if( !isset( $this->vat_class ) ) {
            $this->vat_class = get_post_meta( $this->ID, '_wpinv_vat_class', true );

            if ( empty( $this->vat_class ) ) {        
                $this->vat_class = '_standard';
            }
        }
        
        return apply_filters( 'wpinv_get_item_vat_class', $this->vat_class, $this->ID );
    }

    public function get_type() {
        if( ! isset( $this->type ) ) {
            $this->type = get_post_meta( $this->ID, '_wpinv_type', true );

            if ( empty( $this->type ) ) {
                $this->type = 'custom';
            }
        }

        return apply_filters( 'wpinv_get_item_type', $this->type, $this->ID );
    }
    
    public function get_custom_id() {
        $custom_id = get_post_meta( $this->ID, '_wpinv_custom_id', true );

        return apply_filters( 'wpinv_get_item_custom_id', $custom_id, $this->ID );
    }
    
    public function get_custom_name() {
        $custom_name = get_post_meta( $this->ID, '_wpinv_custom_name', true );

        return apply_filters( 'wpinv_get_item_custom_name', $custom_name, $this->ID );
    }
    
    public function get_custom_singular_name() {
        $custom_singular_name = get_post_meta( $this->ID, '_wpinv_custom_singular_name', true );

        return apply_filters( 'wpinv_get_item_custom_singular_name', $custom_singular_name, $this->ID );
    }
    
    public function get_editable() {
        $editable = get_post_meta( $this->ID, '_wpinv_editable', true );

        return apply_filters( 'wpinv_item_get_editable', $editable, $this->ID );
    }
    
    public function get_excerpt() {
        $excerpt = get_the_excerpt( $this->ID );
        
        return apply_filters( 'wpinv_item_get_excerpt', $excerpt, $this->ID );
    }
    
    public function get_is_recurring() {
        $is_recurring = get_post_meta( $this->ID, '_wpinv_is_recurring', true );

        return apply_filters( 'wpinv_item_get_is_recurring', $is_recurring, $this->ID );

    }
    
    public function get_recurring_period( $full = false ) {
        $period = get_post_meta( $this->ID, '_wpinv_recurring_period', true );
        
        if ( !in_array( $period, array( 'D', 'W', 'M', 'Y' ) ) ) {
            $period = 'D';
        }
        
        if ( $full ) {
            switch( $period ) {
                case 'D':
                    $period = 'day';
                break;
                case 'W':
                    $period = 'week';
                break;
                case 'M':
                    $period = 'month';
                break;
                case 'Y':
                    $period = 'year';
                break;
            }
        }

        return apply_filters( 'wpinv_item_recurring_period', $period, $full, $this->ID );
    }
    
    public function get_recurring_interval() {
        $interval = (int)get_post_meta( $this->ID, '_wpinv_recurring_interval', true );
        
        if ( !$interval > 0 ) {
            $interval = 1;
        }

        return apply_filters( 'wpinv_item_recurring_interval', $interval, $this->ID );
    }
    
    public function get_recurring_limit() {
        $limit = get_post_meta( $this->ID, '_wpinv_recurring_limit', true );

        return (int)apply_filters( 'wpinv_item_recurring_limit', $limit, $this->ID );
    }
    
    public function get_free_trial() {
        $free_trial = get_post_meta( $this->ID, '_wpinv_free_trial', true );

        return apply_filters( 'wpinv_item_get_free_trial', $free_trial, $this->ID );
    }
    
    public function get_trial_period( $full = false ) {
        $period = get_post_meta( $this->ID, '_wpinv_trial_period', true );
        
        if ( !in_array( $period, array( 'D', 'W', 'M', 'Y' ) ) ) {
            $period = 'D';
        }
        
        if ( $full ) {
            switch( $period ) {
                case 'D':
                    $period = 'day';
                break;
                case 'W':
                    $period = 'week';
                break;
                case 'M':
                    $period = 'month';
                break;
                case 'Y':
                    $period = 'year';
                break;
            }
        }

        return apply_filters( 'wpinv_item_trial_period', $period, $full, $this->ID );
    }
    
    public function get_trial_interval() {
        $interval = absint( get_post_meta( $this->ID, '_wpinv_trial_interval', true ) );
        
        if ( !$interval > 0 ) {
            $interval = 1;
        }

        return apply_filters( 'wpinv_item_trial_interval', $interval, $this->ID );
    }
    
    public function get_the_price() {
        $item_price = wpinv_price( wpinv_format_amount( $this->price ) );
        
        return apply_filters( 'wpinv_get_the_item_price', $item_price, $this->ID );
    }
    
    public function is_recurring() {
        $is_recurring = $this->get_is_recurring();

        return (bool)apply_filters( 'wpinv_is_recurring_item', $is_recurring, $this->ID );
    }
    
    public function has_free_trial() {
        $free_trial = $this->is_recurring() && $this->get_free_trial() ? true : false;

        return (bool)apply_filters( 'wpinv_item_has_free_trial', $free_trial, $this->ID );
    }

    public function is_free() {
        $is_free = false;
        
        $price = get_post_meta( $this->ID, '_wpinv_price', true );

        if ( (float)$price == 0 ) {
            $is_free = true;
        }

        return (bool) apply_filters( 'wpinv_is_free_item', $is_free, $this->ID );

    }
    
    public function is_package() {
        $is_package = $this->get_type() == 'package' ? true : false;

        return (bool) apply_filters( 'wpinv_is_package_item', $is_package, $this->ID );

    }
    
    public function is_editable() {
        $editable = $this->get_editable();

        $is_editable = $editable === 0 || $editable === '0' ? false : true;

        return (bool) apply_filters( 'wpinv_item_is_editable', $is_editable, $this->ID );
    }
    
    public function save_metas( $metas = array() ) {
        if ( empty( $metas ) ) {
            return false;
        }
        
        foreach ( $metas as $meta_key => $meta_value ) {
            $meta_key = strpos($meta_key, '_wpinv_') !== 0 ? '_wpinv_' . $meta_key : $meta_key;
            
            $this->update_meta($meta_key, $meta_value);
        }

        return true;
    }

    public function update_meta( $meta_key = '', $meta_value = '', $prev_value = '' ) {
        if ( empty( $meta_key ) ) {
            return false;
        }
        
        $meta_value = apply_filters( 'wpinv_update_item_meta_' . $meta_key, $meta_value, $this->ID );

        return update_post_meta( $this->ID, $meta_key, $meta_value, $prev_value );
    }
    
    public function get_fees( $type = 'fee', $item_id = 0 ) {
        global $wpi_session;
        
        $fees = $wpi_session->get( 'wpi_cart_fees' );

        if ( ! wpinv_get_cart_contents() ) {
            // We can only get item type fees when the cart is empty
            $type = 'custom';
        }

        if ( ! empty( $fees ) && ! empty( $type ) && 'all' !== $type ) {
            foreach( $fees as $key => $fee ) {
                if( ! empty( $fee['type'] ) && $type != $fee['type'] ) {
                    unset( $fees[ $key ] );
                }
            }
        }

        if ( ! empty( $fees ) && ! empty( $item_id ) ) {
            // Remove fees that don't belong to the specified Item
            foreach ( $fees as $key => $fee ) {
                if ( (int) $item_id !== (int)$fee['custom_id'] ) {
                    unset( $fees[ $key ] );
                }
            }
        }

        if ( ! empty( $fees ) ) {
            // Remove fees that belong to a specific item but are not in the cart
            foreach( $fees as $key => $fee ) {
                if( empty( $fee['custom_id'] ) ) {
                    continue;
                }

                if ( !wpinv_item_in_cart( $fee['custom_id'] ) ) {
                    unset( $fees[ $key ] );
                }
            }
        }

        return ! empty( $fees ) ? $fees : array();
    }
    
    public function can_purchase() {
        $can_purchase = true;

        if ( !current_user_can( 'edit_post', $this->ID ) && $this->post_status != 'publish' ) {
            $can_purchase = false;
        }

        return (bool)apply_filters( 'wpinv_can_purchase_item', $can_purchase, $this );
    }
}
