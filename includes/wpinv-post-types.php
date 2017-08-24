<?php
/**
 * 
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

add_action( 'init', 'wpinv_register_post_types', 1 );
function wpinv_register_post_types() {    
    $labels = array(
        'name'               => _x( 'Invoices', 'post type general name', 'invoicing' ),
        'singular_name'      => _x( 'Invoice', 'post type singular name', 'invoicing' ),
        'menu_name'          => _x( 'Invoices', 'admin menu', 'invoicing' ),
        'name_admin_bar'     => _x( 'Invoice', 'add new on admin bar', 'invoicing' ),
        'add_new'            => _x( 'Add New', 'book', 'invoicing' ),
        'add_new_item'       => __( 'Add New Invoice', 'invoicing' ),
        'new_item'           => __( 'New Invoice', 'invoicing' ),
        'edit_item'          => __( 'Edit Invoice', 'invoicing' ),
        'view_item'          => __( 'View Invoice', 'invoicing' ),
        'all_items'          => __( 'Invoices', 'invoicing' ),
        'search_items'       => __( 'Search Invoices', 'invoicing' ),
        'parent_item_colon'  => __( 'Parent Invoices:', 'invoicing' ),
        'not_found'          => __( 'No invoices found.', 'invoicing' ),
        'not_found_in_trash' => __( 'No invoices found in trash.', 'invoicing' )
    );
    $labels = apply_filters( 'wpinv_labels', $labels );
    
    $menu_icon = WPINV_PLUGIN_URL . '/assets/images/favicon.ico';
    $menu_icon = apply_filters( 'wpinv_menu_icon_invoice', $menu_icon );

    $cap_type = 'wpi_invoice';
    $args = array(
        'labels'             => $labels,
        'description'        => __( 'This is where invoices are stored.', 'invoicing' ),
        'public'             => true,
        'can_export'         => true,
        '_builtin'           => false,
        'publicly_queryable' => true,
        'exclude_from_search'=> true,
        'show_ui'            => true,
        'show_in_menu'       => current_user_can( 'manage_invoicing' ) ? 'wpinv' : true,
        'query_var'          => false,
        'rewrite'            => true,
        'capability_type'    => 'wpi_invoice',
        'map_meta_cap'          => true,
        'capabilities' => array(
            'delete_post' => "delete_{$cap_type}",
            'delete_posts' => "delete_{$cap_type}s",
            'delete_private_posts' => "delete_private_{$cap_type}s",
            'delete_published_posts' => "delete_published_{$cap_type}s",
            'delete_others_posts' => "delete_others_{$cap_type}s",
            'edit_post' => "edit_{$cap_type}",
            'edit_posts' => "edit_{$cap_type}s",
            'edit_others_posts' => "edit_others_{$cap_type}s",
            'edit_private_posts' => "edit_private_{$cap_type}s",
            'edit_published_posts' => "edit_published_{$cap_type}s",
            'publish_posts' => "publish_{$cap_type}s",
            'read_post' => "read_{$cap_type}",
            'read_private_posts' => "read_private_{$cap_type}s",

        ),
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'author' ),
        'menu_icon'          => $menu_icon,
    );
            
    $args = apply_filters( 'wpinv_register_post_type_invoice', $args );
    
    register_post_type( 'wpi_invoice', $args );
    
    $items_labels = array(
        'name'               => _x( 'Items', 'post type general name', 'invoicing' ),
        'singular_name'      => _x( 'Item', 'post type singular name', 'invoicing' ),
        'menu_name'          => _x( 'Items', 'admin menu', 'invoicing' ),
        'add_new'            => _x( 'Add New', 'wpi_item', 'invoicing' ),
        'add_new_item'       => __( 'Add New Item', 'invoicing' ),
        'new_item'           => __( 'New Item', 'invoicing' ),
        'edit_item'          => __( 'Edit Item', 'invoicing' ),
        'view_item'          => __( 'View Item', 'invoicing' ),
        'all_items'          => __( 'Items', 'invoicing' ),
        'search_items'       => __( 'Search Items', 'invoicing' ),
        'parent_item_colon'  => '',
        'not_found'          => __( 'No items found.', 'invoicing' ),
        'not_found_in_trash' => __( 'No items found in trash.', 'invoicing' )
    );
    $items_labels = apply_filters( 'wpinv_items_labels', $items_labels );

    $cap_type = 'wpi_item';
    $invoice_item_args = array(
        'labels'                => $items_labels,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => current_user_can( 'manage_invoicing' ) ? 'wpinv' : false,
        'supports'              => array( 'title', 'excerpt' ),
        'register_meta_box_cb'  => 'wpinv_register_item_meta_boxes',
        'rewrite'               => false,
        'query_var'             => false,
        'capability_type'       => 'wpi_item',
        'map_meta_cap'          => true,
        'capabilities' => array(
            'delete_post' => "delete_{$cap_type}",
            'delete_posts' => "delete_{$cap_type}s",
            'delete_private_posts' => "delete_private_{$cap_type}s",
            'delete_published_posts' => "delete_published_{$cap_type}s",
            'delete_others_posts' => "delete_others_{$cap_type}s",
            'edit_post' => "edit_{$cap_type}",
            'edit_posts' => "edit_{$cap_type}s",
            'edit_others_posts' => "edit_others_{$cap_type}s",
            'edit_private_posts' => "edit_private_{$cap_type}s",
            'edit_published_posts' => "edit_published_{$cap_type}s",
            'publish_posts' => "publish_{$cap_type}s",
            'read_post' => "read_{$cap_type}",
            'read_private_posts' => "read_private_{$cap_type}s",

        ),
        'can_export'            => true,
    );
    $invoice_item_args = apply_filters( 'wpinv_register_post_type_invoice_item', $invoice_item_args );

    register_post_type( 'wpi_item', $invoice_item_args );
    
    $labels = array(
        'name'               => _x( 'Discounts', 'post type general name', 'invoicing' ),
        'singular_name'      => _x( 'Discount', 'post type singular name', 'invoicing' ),
        'menu_name'          => _x( 'Discounts', 'admin menu', 'invoicing' ),
        'name_admin_bar'     => _x( 'Discount', 'add new on admin bar', 'invoicing' ),
        'add_new'            => _x( 'Add New', 'book', 'invoicing' ),
        'add_new_item'       => __( 'Add New Discount', 'invoicing' ),
        'new_item'           => __( 'New Discount', 'invoicing' ),
        'edit_item'          => __( 'Edit Discount', 'invoicing' ),
        'view_item'          => __( 'View Discount', 'invoicing' ),
        'all_items'          => __( 'Discounts', 'invoicing' ),
        'search_items'       => __( 'Search Discounts', 'invoicing' ),
        'parent_item_colon'  => __( 'Parent Discounts:', 'invoicing' ),
        'not_found'          => __( 'No discounts found.', 'invoicing' ),
        'not_found_in_trash' => __( 'No discounts found in trash.', 'invoicing' )
    );
    $labels = apply_filters( 'wpinv_discounts_labels', $labels );

    $cap_type = 'wpi_discount';
    
    $args = array(
        'labels'             => $labels,
        'description'        => __( 'This is where you can add new discounts that users can use in invoices.', 'invoicing' ),
        'public'             => false,
        'can_export'         => true,
        '_builtin'           => false,
        'publicly_queryable' => false,
        'exclude_from_search'=> true,
        'show_ui'            => true,
        'show_in_menu'       => current_user_can( 'manage_invoicing' ) ? 'wpinv' : false,
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => $cap_type,
        'map_meta_cap'       => true,
        'capabilities' => array(
            'delete_post' => "delete_{$cap_type}",
            'delete_posts' => "delete_{$cap_type}s",
            'delete_private_posts' => "delete_private_{$cap_type}s",
            'delete_published_posts' => "delete_published_{$cap_type}s",
            'delete_others_posts' => "delete_others_{$cap_type}s",
            'edit_post' => "edit_{$cap_type}",
            'edit_posts' => "edit_{$cap_type}s",
            'edit_others_posts' => "edit_others_{$cap_type}s",
            'edit_private_posts' => "edit_private_{$cap_type}s",
            'edit_published_posts' => "edit_published_{$cap_type}s",
            'publish_posts' => "publish_{$cap_type}s",
            'read_post' => "read_{$cap_type}",
            'read_private_posts' => "read_private_{$cap_type}s",

        ),
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title', 'excerpt' ),
        'register_meta_box_cb'  => 'wpinv_register_discount_meta_boxes',
        'show_in_nav_menus'  => false,
        'show_in_admin_bar'  => true,
        'menu_icon'          => $menu_icon,
        'menu_position'      => null,
    );
            
    $args = apply_filters( 'wpinv_register_post_type_discount', $args );
    
    register_post_type( 'wpi_discount', $args );
}

function wpinv_get_default_labels() {
    $defaults = array(
       'singular' => __( 'Invoice', 'invoicing' ),
       'plural'   => __( 'Invoices', 'invoicing' )
    );
    
    return apply_filters( 'wpinv_default_invoices_name', $defaults );
}

function wpinv_get_label_singular( $lowercase = false ) {
    $defaults = wpinv_get_default_labels();
    
    return ($lowercase) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
}

function wpinv_get_label_plural( $lowercase = false ) {
    $defaults = wpinv_get_default_labels();
    
    return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
}

function wpinv_change_default_title( $title ) {
     if ( !is_admin() ) {
        $label = wpinv_get_label_singular();
        $title = sprintf( __( 'Enter %s name here', 'invoicing' ), $label );
        return $title;
     }

     $screen = get_current_screen();

     if ( 'wpi_invoice' == $screen->post_type ) {
        $label = wpinv_get_label_singular();
        $title = sprintf( __( 'Enter %s name here', 'invoicing' ), $label );
     }

     return $title;
}
add_filter( 'enter_title_here', 'wpinv_change_default_title' );

function wpinv_register_post_status() {
    register_post_status( 'wpi-pending', array(
        'label'                     => _x( 'Pending', 'Invoice status', 'invoicing' ),
        'public'                    => true,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'invoicing' )
    ) );
    register_post_status( 'wpi-processing', array(
        'label'                     => _x( 'Processing', 'Invoice status', 'invoicing' ),
        'public'                    => true,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'invoicing' )
    ) );
    register_post_status( 'wpi-onhold', array(
        'label'                     => _x( 'On Hold', 'Invoice status', 'invoicing' ),
        'public'                    => true,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'On Hold <span class="count">(%s)</span>', 'On Hold <span class="count">(%s)</span>', 'invoicing' )
    ) );
    register_post_status( 'wpi-cancelled', array(
        'label'                     => _x( 'Cancelled', 'Invoice status', 'invoicing' ),
        'public'                    => true,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'invoicing' )
    ) );
    register_post_status( 'wpi-refunded', array(
        'label'                     => _x( 'Refunded', 'Invoice status', 'invoicing' ),
        'public'                    => true,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'invoicing' )
    ) );
    register_post_status( 'wpi-failed', array(
        'label'                     => _x( 'Failed', 'Invoice status', 'invoicing' ),
        'public'                    => true,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'invoicing' )
    ) );
    register_post_status( 'wpi-renewal', array(
        'label'                     => _x( 'Renewal', 'Invoice status', 'invoicing' ),
        'public'                    => true,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Renewal <span class="count">(%s)</span>', 'Renewal <span class="count">(%s)</span>', 'invoicing' )
    ) );
}
add_action( 'init', 'wpinv_register_post_status', 10 );

