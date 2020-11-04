<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

add_action( 'manage_wpi_discount_posts_custom_column', 'wpinv_discount_custom_column' );
function wpinv_discount_custom_column( $column ) {
    global $post;

    $discount = new WPInv_Discount( $post );

    switch ( $column ) {
        case 'code' :
            echo $discount->get_code();
        break;
        case 'amount' :
            echo $discount->get_formatted_amount();
        break;
        case 'usage' :
            echo $discount->get_usage();
        break;
        case 'start_date' :
            echo getpaid_format_date_value( $discount->get_start_date() );
        break;
        case 'expiry_date' :
            echo getpaid_format_date_value( $discount->get_expiration_date(), __( 'Never', 'invoicing' ) );
        break;
    }
}

add_filter( 'post_row_actions', 'wpinv_post_row_actions', 9999, 2 );
function wpinv_post_row_actions( $actions, $post ) {
    $post_type = !empty( $post->post_type ) ? $post->post_type : '';

    if ( $post_type == 'wpi_invoice' ) {
        $actions = array();
    }

    if ( $post_type == 'wpi_discount' ) {
        $actions = wpinv_discount_row_actions( $post, $actions );
    }

    return $actions;
}

function wpinv_discount_row_actions( $discount, $row_actions ) {
    $row_actions  = array();
    $edit_link = get_edit_post_link( $discount->ID );
    $row_actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit', 'invoicing' ) . '</a>';

    if ( in_array( strtolower( $discount->post_status ),  array(  'publish' ) ) ) {

        $url    = esc_url(
                    wp_nonce_url(
                        add_query_arg(
                            array(
                                'getpaid-admin-action' => 'deactivate_discount',
                                'discount'             => $discount->ID,
                            )
                        ),
                        'getpaid-nonce',
                        'getpaid-nonce'
                    )
                );
		$anchor = __( 'Deactivate', 'invoicing' );
		$title  = esc_attr__( 'Are you sure you want to deactivate this discount?', 'invoicing' );
        $row_actions['deactivate'] = "<a href='$url' onclick='return confirm(\"$title\")'>$anchor</a>";

    } else if( in_array( strtolower( $discount->post_status ),  array( 'pending', 'draft' ) ) ) {

        $url    = esc_url(
            wp_nonce_url(
                add_query_arg(
                    array(
                        'getpaid-admin-action' => 'activate_discount',
                        'discount'             => $discount->ID,
                    )
                ),
                'getpaid-nonce',
                'getpaid-nonce'
            )
        );
		$anchor = __( 'Activate', 'invoicing' );
		$title  = esc_attr__( 'Are you sure you want to activate this discount?', 'invoicing' );
        $row_actions['activate'] = "<a href='$url' onclick='return confirm(\"$title\")'>$anchor</a>";

    }

    $url    = esc_url(
        wp_nonce_url(
            add_query_arg(
                array(
                    'getpaid-admin-action' => 'delete_discount',
                    'discount'             => $discount->ID,
                )
            ),
            'getpaid-nonce',
            'getpaid-nonce'
        )
    );
	$anchor = __( 'Delete', 'invoicing' );
	$title  = esc_attr__( 'Are you sure you want to delete this discount?', 'invoicing' );
    $row_actions['delete'] = "<a href='$url' onclick='return confirm(\"$title\")'>$anchor</a>";

    $row_actions = apply_filters( 'wpinv_discount_row_actions', $row_actions, $discount );

    return $row_actions;
}

add_filter( 'list_table_primary_column', 'wpinv_table_primary_column', 10, 2 );
function wpinv_table_primary_column( $default, $screen_id ) {
    if ( 'edit-wpi_invoice' === $screen_id ) {
        return 'name';
    }

    return $default;
}

function wpinv_disable_months_dropdown( $disable, $post_type ) {
    if ( $post_type == 'wpi_discount' ) {
        $disable = true;
    }

    return $disable;
}
add_filter( 'disable_months_dropdown', 'wpinv_disable_months_dropdown', 10, 2 );

function wpinv_restrict_manage_posts() {
    global $typenow;

    if( 'wpi_discount' == $typenow ) {
        wpinv_discount_filters();
    }
}
add_action( 'restrict_manage_posts', 'wpinv_restrict_manage_posts', 10 );

function wpinv_discount_filters() {

    ?>
    <select name="discount_type" id="dropdown_wpinv_discount_type">
        <option value=""><?php _e( 'Show all types', 'invoicing' ); ?></option>
        <?php
            $types = wpinv_get_discount_types();

            foreach ( $types as $name => $type ) {
                echo '<option value="' . esc_attr( $name ) . '"';

                if ( isset( $_GET['discount_type'] ) )
                    selected( $name, $_GET['discount_type'] );

                echo '>' . esc_html__( $type, 'invoicing' ) . '</option>';
            }
        ?>
    </select>
    <?php
}

function wpinv_request( $vars ) {
    global $typenow, $wp_query, $wp_post_statuses;

    if ( getpaid_is_invoice_post_type( $typenow ) ) {
        if ( !isset( $vars['post_status'] ) ) {
            $post_statuses = wpinv_get_invoice_statuses( false, false, $typenow );

            foreach ( $post_statuses as $status => $value ) {
                if ( isset( $wp_post_statuses[ $status ] ) && false === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
                    unset( $post_statuses[ $status ] );
                }
            }

            $vars['post_status'] = array_keys( $post_statuses );
        }

        if ( isset( $vars['orderby'] ) ) {
            if ( 'amount' == $vars['orderby'] ) {
                $vars = array_merge(
                    $vars,
                    array(
                        'meta_key' => '_wpinv_total',
                        'orderby'  => 'meta_value_num'
                    )
                );
            } else if ( 'customer' == $vars['orderby'] ) {
                $vars = array_merge(
                    $vars,
                    array(
                        'meta_key' => '_wpinv_first_name',
                        'orderby'  => 'meta_value'
                    )
                );
            } else if ( 'number' == $vars['orderby'] ) {
                $vars = array_merge(
                    $vars,
                    array(
                        'meta_key' => '_wpinv_number',
                        'orderby'  => 'meta_value'
                    )
                );
            } else if ( 'payment_date' == $vars['orderby'] ) {
                $vars = array_merge(
                    $vars,
                    array(
                        'meta_key' => '_wpinv_completed_date',
                        'orderby'  => 'meta_value'
                    )
                );
            }
        }
    } else if ( 'wpi_discount' == $typenow ) {
        $meta_query = !empty( $vars['meta_query'] ) ? $vars['meta_query'] : array();
        // Filter vat rule type
        if ( isset( $_GET['discount_type'] ) && $_GET['discount_type'] !== '' ) {
            $meta_query[] = array(
                    'key'   => '_wpi_discount_type',
                    'value' => sanitize_text_field( $_GET['discount_type'] ),
                    'compare' => '='
                );
        }

        if ( !empty( $meta_query ) ) {
            $vars['meta_query'] = $meta_query;
        }
    }

    return $vars;
}
add_filter( 'request', 'wpinv_request' );

function wpinv_item_type_class( $classes, $class, $post_id ) {
    global $pagenow, $typenow;

    if ( $pagenow == 'edit.php' && $typenow == 'wpi_item' && get_post_type( $post_id ) == $typenow ) {
        if ( $type = get_post_meta( $post_id, '_wpinv_type', true ) ) {
            $classes[] = 'wpi-type-' . sanitize_html_class( $type );
        }

        if ( !wpinv_item_is_editable( $post_id ) ) {
            $classes[] = 'wpi-editable-n';
        }
    }
    return $classes;
}
add_filter( 'post_class', 'wpinv_item_type_class', 10, 3 );

function wpinv_check_quick_edit() {
    global $pagenow, $current_screen, $wpinv_item_screen;

    if ( $pagenow == 'edit.php' && !empty( $current_screen->post_type ) ) {
        if ( empty( $wpinv_item_screen ) ) {
            if ( $current_screen->post_type == 'wpi_item' ) {
                $wpinv_item_screen = 'y';
            } else {
                $wpinv_item_screen = 'n';
            }
        }

        if ( $wpinv_item_screen == 'y' && $pagenow == 'edit.php' ) {
            add_filter( 'post_row_actions', 'wpinv_item_disable_quick_edit', 10, 2 );
            add_filter( 'page_row_actions', 'wpinv_item_disable_quick_edit', 10, 2 );
        }
    }
}
add_action( 'admin_head', 'wpinv_check_quick_edit', 10 );

function wpinv_item_disable_quick_edit( $actions = array(), $row = null ) {
    if ( isset( $actions['inline hide-if-no-js'] ) ) {
        unset( $actions['inline hide-if-no-js'] );
    }

    if ( !empty( $row->post_type ) && $row->post_type == 'wpi_item' && !wpinv_item_is_editable( $row ) ) {
        if ( isset( $actions['trash'] ) ) {
            unset( $actions['trash'] );
        }
        if ( isset( $actions['delete'] ) ) {
            unset( $actions['delete'] );
        }
    }

    return $actions;
}

/**
 * Create a page and store the ID in an option.
 *
 * @param mixed $slug Slug for the new page
 * @param string $option Option name to store the page's ID
 * @param string $page_title (default: '') Title for the new page
 * @param string $page_content (default: '') Content for the new page
 * @param int $post_parent (default: 0) Parent for the new page
 * @return int page ID
 */
function wpinv_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
    global $wpdb;

    $option_value = wpinv_get_option( $option );

    if ( $option_value > 0 && ( $page_object = get_post( $option_value ) ) ) {
        if ( 'page' === $page_object->post_type && ! in_array( $page_object->post_status, array( 'pending', 'trash', 'future', 'auto-draft' ) ) ) {
            // Valid page is already in place
            return $page_object->ID;
        }
    }

    if(!empty($post_parent)){
        $page = get_page_by_path($post_parent);
        if ($page) {
            $post_parent = $page->ID;
        } else {
            $post_parent = '';
        }
    }

    if ( strlen( $page_content ) > 0 ) {
        // Search for an existing page with the specified page content (typically a shortcode)
        $valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
    } else {
        // Search for an existing page with the specified page slug
        $valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
    }

    $valid_page_found = apply_filters( 'wpinv_create_page_id', $valid_page_found, $slug, $page_content );

    if ( $valid_page_found ) {
        if ( $option ) {
            wpinv_update_option( $option, $valid_page_found );
        }
        return $valid_page_found;
    }

    // Search for a matching valid trashed page
    if ( strlen( $page_content ) > 0 ) {
        // Search for an existing page with the specified page content (typically a shortcode)
        $trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
    } else {
        // Search for an existing page with the specified page slug
        $trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );
    }

    if ( $trashed_page_found ) {
        $page_id   = $trashed_page_found;
        $page_data = array(
            'ID'             => $page_id,
            'post_status'    => 'publish',
            'post_parent'    => $post_parent,
        );
        wp_update_post( $page_data );
    } else {
        $page_data = array(
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => 1,
            'post_name'      => $slug,
            'post_title'     => $page_title,
            'post_content'   => $page_content,
            'post_parent'    => $post_parent,
            'comment_status' => 'closed',
        );
        $page_id = wp_insert_post( $page_data );
    }

    if ( $option ) {
        wpinv_update_option( $option, (int)$page_id );
    }

    return $page_id;
}