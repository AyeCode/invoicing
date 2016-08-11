<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpinv_user_invoices( $current_page = 1 ) {    
    global $current_page;
    wpinv_get_template_part( 'wpinv-invoice-history' );
}

function wpinv_get_users_invoices( $user = 0, $number = 20, $pagination = false, $status = 'complete' ) {
    if ( empty( $user ) ) {
        $user = get_current_user_id();
    }

    if ( 0 === $user ) {
        return false;
    }

    $status = $status === 'complete' ? 'publish' : $status;

    if ( $pagination ) {
        if ( get_query_var( 'paged' ) )
            $paged = get_query_var('paged');
        else if ( get_query_var( 'page' ) )
            $paged = get_query_var( 'page' );
        else
            $paged = 1;
    }

    $args = array(
        'post_type'      => 'wpi_invoice',
        'posts_per_page' => 20,
        'paged'          => null,
        'post_status'    => array( 'complete', 'publish', 'pending' ),
        'user'           => $user,
        'order'          => 'date',
    );

    $invoices = get_posts( $args );

    // No invoices
    if ( ! $invoices )
        return false;

    return $invoices;
}