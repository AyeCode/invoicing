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
        
        // Count comments
        add_filter( 'wp_count_comments', array( $this, 'wp_count_comments' ), 11, 2 );
        
        // Delete comments count cache whenever there is a new comment or a comment status changes
        add_action( 'wp_insert_comment', array( $this, 'delete_comments_count_cache' ) );
        add_action( 'wp_set_comment_status', array( $this, 'delete_comments_count_cache' ) );
        
        do_action( 'wpinv_class_notes_actions', $this );
    }
        
    public function set_invoice_note_type( $query ) {
        $post_ID        = !empty( $query->query_vars['post_ID'] ) ? $query->query_vars['post_ID'] : $query->query_vars['post_id'];
        
        if ( $post_ID && in_array(get_post_type( $post_ID ), array($this->invoice_post_type, 'wpi_quote' )) ) {
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
            'orderby'   => 'comment_ID',
            'order'     => 'ASC',
        );
        
        if ( $type == 'customer' ) {
            $args['meta_key']   = '_wpi_customer_note';
            $args['meta_value'] = 1;
        }
        
        $args   = apply_filters( 'wpinv_invoice_notes_args', $args, $this, $invoice_id, $type );
        
        return get_comments( $args );
    }
    
    /**
     * Delete comments count cache whenever there is new comment or the 
     * status of a comment changes. Cache will be regenerated next time 
     * WPInv_Notes::wp_count_comments() is called.
     *
     * @return void
     */
    public function delete_comments_count_cache() {
        delete_transient( 'wpinv_count_comments' );
    }
    
    /**
     * Remove invoice notes from wp_count_comments().
     *
     * @since  1.0.0
     * @param  object $stats   Comment stats.
     * @param  int    $post_id Post ID.
     * @return object
     */
    public function wp_count_comments( $stats, $post_id ) {
        global $wpdb;

        if ( 0 === $post_id ) {
            $stats = get_transient( 'wpinv_count_comments' );

            if ( ! $stats ) {
                $stats = array();

                $count = $wpdb->get_results( "SELECT comment_approved, COUNT(*) AS num_comments FROM {$wpdb->comments} WHERE comment_type NOT IN ('" . $this->comment_type . "') GROUP BY comment_approved", ARRAY_A );

                $total = 0;
                $approved = array(
                    '0'            => 'moderated',
                    '1'            => 'approved',
                    'spam'         => 'spam',
                    'trash'        => 'trash',
                    'post-trashed' => 'post-trashed',
                );

                foreach ( (array) $count as $row ) {
                    // Do not count post-trashed toward totals.
                    if ( 'post-trashed' !== $row['comment_approved'] && 'trash' !== $row['comment_approved'] ) {
                        $total += $row['num_comments'];
                    }
                    if ( isset( $approved[ $row['comment_approved'] ] ) ) {
                        $stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
                    }
                }

                $stats['total_comments'] = $total;
                $stats['all'] = $total;
                foreach ( $approved as $key ) {
                    if ( empty( $stats[ $key ] ) ) {
                        $stats[ $key ] = 0;
                    }
                }

                $stats = (object) $stats;
                set_transient( 'wpinv_count_comments', $stats );
            }
        }

        return $stats;
    }
}
