<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WPInv_Notes {
    private $invoice_post_type  = 'wpi_invoice';
    private $comment_type       = 'wpinv_note';
    
    public function __construct() {
        $this->init();
        $this->includes();
        $this->actions();
    }
    
    public function init() {
        do_action( 'wpinv_class_notes_init', $this );
    }
    
    public function includes() {
        do_action( 'wpinv_class_notes_includes', $this );
    }
    
    public function actions() {
        // Secure inovice notes
        add_action( 'pre_get_comments', array( $this, 'set_invoice_note_type' ), 11, 1 );
        
        do_action( 'wpinv_class_notes_actions', $this );
    }
        
    public function set_invoice_note_type( $query ) {
        $post_ID        = !empty( $query->query_vars['post_ID'] ) ? $query->query_vars['post_ID'] : $query->query_vars['post_id'];
        
        if ( $post_ID && get_post_type( $post_ID ) == $this->invoice_post_type ) {
            $query->query_vars['type__in']      = $this->comment_type;
            $query->query_vars['type__not_in']  = '';
        } else {        
            if ( isset( $query->query_vars['type__in'] ) && $type_in = $query->query_vars['type__in'] ) {
                if ( is_array( $type_in ) && in_array( $this->comment_type, $type_in ) ) {
                    $key = array_search( $this->comment_type, $type_in );
                    unset( $query->query_vars['type__in'][$key] );
                } else if ( !is_array( $type_in ) && $type_in == $this->comment_type ) {
                    $query->query_vars['type__in'] = '';
                }
            }
            
            if ( isset( $query->query_vars['type__not_in'] ) && $type_not_in = $query->query_vars['type__not_in'] ) {
                if ( is_array( $type_not_in ) && !in_array( $this->comment_type, $type_not_in ) ) {
                    $query->query_vars['type__not_in'][] = $this->comment_type;
                } else if ( !is_array( $type_not_in ) && $type_not_in != $this->comment_type ) {
                    $query->query_vars['type__not_in'] = (array)$query->query_vars['type__not_in'];
                    $query->query_vars['type__not_in'][] = $this->comment_type;
                }
            } else {
                $query->query_vars['type__not_in']  = $this->comment_type;
            }
        }
        
        return $query;
    }
    
    public function get_invoice_notes( $invoice_id = 0, $type = '' ) {
        $args = array( 
            'post_id'   => $invoice_id,
            'order'     => 'comment_date_gmt',
            'order'     => 'DESC',
        );
        
        if ( $type == 'customer' ) {
            $args['meta_key']   = '_wpi_customer_note';
            $args['meta_value'] = 1;
        }
        
        $args   = apply_filters( 'wpinv_invoice_notes_args', $args, $this, $invoice_id, $type );
        
        return get_comments( $args );
    }
}
