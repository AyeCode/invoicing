<?php
/**
 * Contains functions related to Invoicing users.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

class WPInv_Admin_Users {
    private static $instance;
    
    public static function run() {
        if ( !isset( self::$instance ) && !( self::$instance instanceof WPInv_Admin_Users ) ) {
            self::$instance = new WPInv_Admin_Users;
        }

        return self::$instance;
    }
    
    public function __construct() {
        add_filter( 'manage_users_columns', array( $this, 'wpinv_add_user_column') );
        add_filter( 'manage_users_custom_column', array( $this, 'wpinv_user_column_content') , 10, 3 );
    }

    /**
     * Adds a new backend user column.
     *
     * @param $column
     *
     * @return mixed
     */
    public function wpinv_add_user_column( $column ) {
        $column['wpinvoicing'] = __('Invoicing','invoicing');
        return $column;
    }

    /**
     * Add the backend user column content.
     *
     * @param $val
     * @param $column_name
     * @param $user_id
     *
     * @return string
     */
    function wpinv_user_column_content( $val, $column_name, $user_id ) {
        switch ($column_name) {
            case 'wpinvoicing' :
                return $this->get_user_invoices( $user_id );
                break;
            default:
        }
        return $val;
    }

    /**
     * Get the backend user invoices.
     *
     * @param $user_id
     *
     * @return string
     */
    public function get_user_invoices($user_id){
        $output = '';
        $wp_query_args = array(
            'post_type'      => 'wpi_invoice',
            'post_status'    => array('wpi-pending', 'publish', 'wpi-processing', 'wpi-onhold', 'wpi-refunded', 'wpi-cancelled', 'wpi-failed', 'wpi-renewal'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'author'         => $user_id,
        );

        $wp_query_args = apply_filters('wpinv_get_user_invoices_args', $wp_query_args, $user_id);

        $invoices = new WP_Query( $wp_query_args );
        $count = absint( $invoices->found_posts );

        if(empty($count)){
            $output .= __('No Invoice(s)','invoicing');
        }else{
            $link_url = admin_url( "edit.php?post_type=wpi_invoice&author=".absint($user_id) );
            $link_text = sprintf( __('Invoices ( %d )', 'invoicing'), $count );
            $output .= "<a href='$link_url' >$link_text</a>";
        }

        return apply_filters('wpinv_user_invoice_content', $output, $user_id);
    }

}