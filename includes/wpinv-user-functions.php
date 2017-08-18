<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpinv_get_users_invoices( $user = 0, $number = 20, $pagination = false, $status = 'publish' ) {
    if ( empty( $user ) ) {
        $user = get_current_user_id();
    }

    if ( 0 === $user ) {
        return false;
    }

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
        'post_status'    => array( 'publish', 'wpi-pending' ),
        'user'           => $user,
        'order'          => 'date',
    );

    $invoices = get_posts( $args );

    // No invoices
    if ( ! $invoices )
        return false;

    return $invoices;
}

function wpinv_dropdown_users( $args = '' ) {
    $defaults = array(
        'show_option_all' => '', 'show_option_none' => '', 'hide_if_only_one_author' => '',
        'orderby' => 'display_name', 'order' => 'ASC',
        'include' => '', 'exclude' => '', 'multi' => 0,
        'show' => 'display_name', 'echo' => 1,
        'selected' => 0, 'name' => 'user', 'class' => '', 'id' => '',
        'blog_id' => $GLOBALS['blog_id'], 'who' => '', 'include_selected' => false,
        'option_none_value' => -1
    );

    $defaults['selected'] = is_author() ? get_query_var( 'author' ) : 0;

    $r = wp_parse_args( $args, $defaults );

    $query_args = wp_array_slice_assoc( $r, array( 'blog_id', 'include', 'exclude', 'orderby', 'order', 'who' ) );

    $fields = array( 'ID', 'user_login', 'user_email' );

    $show = ! empty( $r['show'] ) ? $r['show'] : 'display_name';
    if ( 'display_name_with_login' === $show ) {
        $fields[] = 'display_name';
    } else if ( 'display_name_with_email' === $show ) {
        $fields[] = 'display_name';
    } else {
        $fields[] = $show;
    }

    $query_args['fields'] = $fields;

    $show_option_all = $r['show_option_all'];
    $show_option_none = $r['show_option_none'];
    $option_none_value = $r['option_none_value'];

    $query_args = apply_filters( 'wpinv_dropdown_users_args', $query_args, $r );

    $users = get_users( $query_args );

    $output = '';
    if ( ! empty( $users ) && ( empty( $r['hide_if_only_one_author'] ) || count( $users ) > 1 ) ) {
        $name = esc_attr( $r['name'] );
        if ( $r['multi'] && ! $r['id'] ) {
            $id = '';
        } else {
            $id = $r['id'] ? " id='" . esc_attr( $r['id'] ) . "'" : " id='$name'";
        }
        $output = "<select name='{$name}'{$id} class='" . $r['class'] . "'>\n";

        if ( $show_option_all ) {
            $output .= "\t<option value='0'>$show_option_all</option>\n";
        }

        if ( $show_option_none ) {
            $_selected = selected( $option_none_value, $r['selected'], false );
            $output .= "\t<option value='" . esc_attr( $option_none_value ) . "'$_selected>$show_option_none</option>\n";
        }

        if ( $r['include_selected'] && ( $r['selected'] > 0 ) ) {
            $found_selected = false;
            $r['selected'] = (int) $r['selected'];
            foreach ( (array) $users as $user ) {
                $user->ID = (int) $user->ID;
                if ( $user->ID === $r['selected'] ) {
                    $found_selected = true;
                }
            }

            if ( ! $found_selected ) {
                $users[] = get_userdata( $r['selected'] );
            }
        }

        foreach ( (array) $users as $user ) {
            if ( 'display_name_with_login' === $show ) {
                /* translators: 1: display name, 2: user_login */
                $display = sprintf( _x( '%1$s (%2$s)', 'user dropdown' ), $user->display_name, $user->user_login );
            } elseif ( 'display_name_with_email' === $show ) {
                /* translators: 1: display name, 2: user_email */
                if ( $user->display_name == $user->user_email ) {
                    $display = $user->display_name;
                } else {
                    $display = sprintf( _x( '%1$s (%2$s)', 'user dropdown' ), $user->display_name, $user->user_email );
                }
            } elseif ( ! empty( $user->$show ) ) {
                $display = $user->$show;
            } else {
                $display = '(' . $user->user_login . ')';
            }

            $_selected = selected( $user->ID, $r['selected'], false );
            $output .= "\t<option value='$user->ID'$_selected>" . esc_html( $display ) . "</option>\n";
        }

        $output .= "</select>";
    }

    $html = apply_filters( 'wpinv_dropdown_users', $output );

    if ( $r['echo'] ) {
        echo $html;
    }
    return $html;
}

function wpinv_guest_redirect( $redirect_to, $user_id = 0 ) {
    if ( (int)wpinv_get_option( 'guest_checkout' ) && $user_id > 0 ) {
        wpinv_login_user( $user_id );
    } else {
        $redirect_to = wp_login_url( $redirect_to );
    }
    
    $redirect_to = apply_filters( 'wpinv_invoice_link_guest_redirect', $redirect_to, $user_id );
    
    wp_redirect( $redirect_to );
}

function wpinv_login_user( $user_id ) {
    if ( is_user_logged_in() ) {
        return true;
    }
    
    $user = get_user_by( 'id', $user_id );
    
    if ( !empty( $user ) && !is_wp_error( $user ) && !empty( $user->user_login ) ) {
        wp_set_current_user( $user_id, $user->user_login );
        wp_set_auth_cookie( $user_id );
        do_action( 'wp_login', $user->user_login );
        
        return true;
    }
    
    return false;
}