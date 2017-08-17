<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

add_action( 'admin_menu', 'wpinv_add_options_link', 10 );
function wpinv_add_options_link() {
    global $menu;

    if ( !(current_user_can( 'manage_invoicing' ) || current_user_can( 'manage_options' )) ) {
        return;
    }

    $capability = apply_filters( 'invoicing_capability', 'manage_invoicing' );

    if ( current_user_can( 'manage_options' ) ) {
        $menu[] = array( '', 'read', 'separator-wpinv', '', 'wp-menu-separator wpinv' );
    }

    $wpi_invoice = get_post_type_object( 'wpi_invoice' );

    add_menu_page( __( 'Invoicing', 'invoicing' ), __( 'Invoicing', 'invoicing' ), $capability, 'wpinv', null, $wpi_invoice->menu_icon, '54.123460' );

    $wpi_settings_page   = add_submenu_page( 'wpinv', __( 'Invoice Settings', 'invoicing' ), __( 'Settings', 'invoicing' ), $capability, 'wpinv-settings', 'wpinv_options_page' );
}

add_action( 'admin_menu', 'wpinv_remove_admin_submenus', 999 );
function wpinv_remove_admin_submenus() {
    remove_submenu_page( 'edit.php?post_type=wpi_invoice', 'post-new.php?post_type=wpi_invoice' );
}

add_filter( 'manage_wpi_discount_posts_columns', 'wpinv_discount_columns' );
function wpinv_discount_columns( $existing_columns ) {
    $columns                = array();
    $columns['cb']          = $existing_columns['cb'];
    $columns['name']        = __( 'Name', 'invoicing' );
    $columns['code']        = __( 'Code', 'invoicing' );
    $columns['amount']      = __( 'Amount', 'invoicing' );
    $columns['usage']       = __( 'Usage / Limit', 'invoicing' );
    $columns['expiry_date'] = __( 'Expiry Date', 'invoicing' );
    $columns['status']      = __( 'Status', 'invoicing' );

    return $columns;
}

add_action( 'manage_wpi_discount_posts_custom_column', 'wpinv_discount_custom_column' );
function wpinv_discount_custom_column( $column ) {
    global $post;
    
    $discount = $post;

    switch ( $column ) {
        case 'name' :
            echo get_the_title( $discount->ID );
        break;
        case 'code' :
            echo wpinv_get_discount_code( $discount->ID );
        break;
        case 'amount' :
            echo wpinv_format_discount_rate( wpinv_get_discount_type( $discount->ID ), wpinv_get_discount_amount( $discount->ID ) );
        break;
        case 'usage_limit' :
            echo wpinv_get_discount_uses( $discount->ID );
        break;
        case 'usage' :
            $usage = wpinv_get_discount_uses( $discount->ID ) . ' / ';
            if ( wpinv_get_discount_max_uses( $discount->ID ) ) {
                $usage .= wpinv_get_discount_max_uses( $discount->ID );
            } else {
                $usage .= ' &infin;';
            }
            
            echo $usage;
        break;
        case 'expiry_date' :
            if ( wpinv_get_discount_expiration( $discount->ID ) ) {
                $expiration = date_i18n( get_option( 'date_format' ), strtotime( wpinv_get_discount_expiration( $discount->ID ) ) );
            } else {
                $expiration = __( 'Never', 'invoicing' );
            }
                
            echo $expiration;
        break;
        case 'description' :
            echo wp_kses_post( $post->post_excerpt );
        break;
        case 'status' :
            $status = wpinv_is_discount_expired( $discount->ID ) ? 'expired' : $discount->post_status;
            
            echo wpinv_discount_status( $status );
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

    if( in_array( strtolower( $discount->post_status ),  array(  'publish' ) ) ) {
        $row_actions['deactivate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'wpi_action' => 'deactivate_discount', 'discount' => $discount->ID ) ), 'wpinv_discount_nonce' ) ) . '">' . __( 'Deactivate', 'invoicing' ) . '</a>';
    } elseif( in_array( strtolower( $discount->post_status ),  array( 'pending', 'draft' ) ) ) {
        $row_actions['activate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'wpi_action' => 'activate_discount', 'discount' => $discount->ID ) ), 'wpinv_discount_nonce' ) ) . '">' . __( 'Activate', 'invoicing' ) . '</a>';
    }

    if ( wpinv_get_discount_uses( $discount->ID ) > 0 ) {
        if ( isset( $row_actions['delete'] ) ) {
            unset( $row_actions['delete'] ); // Don't delete used discounts.
        }
    } else {
        $row_actions['delete'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'wpi_action' => 'delete_discount', 'discount' => $discount->ID ) ), 'wpinv_discount_nonce' ) ) . '">' . __( 'Delete', 'invoicing' ) . '</a>';
    }
    

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

function wpinv_discount_bulk_actions( $actions, $display = false ) {    
    if ( !$display ) {
        return array();
    }
    
    $actions = array(
        'activate'   => __( 'Activate', 'invoicing' ),
        'deactivate' => __( 'Deactivate', 'invoicing' ),
        'delete'     => __( 'Delete', 'invoicing' ),
    );
    $two = '';
    $which = 'top';
    echo '</div><div class="alignleft actions bulkactions">';
    echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
    echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">";
    echo '<option value="-1">' . __( 'Bulk Actions' ) . "</option>";

    foreach ( $actions as $name => $title ) {
        $class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

        echo "" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>";
    }
    echo "</select>";

    submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
    
    echo '</div><div class="alignleft actions">';
}
add_filter( 'bulk_actions-edit-wpi_discount', 'wpinv_discount_bulk_actions', 10 );

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
    echo wpinv_discount_bulk_actions( array(), true );
    
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

    if ( 'wpi_invoice' === $typenow ) {
        if ( !isset( $vars['post_status'] ) ) {
            $post_statuses = wpinv_get_invoice_statuses();

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
            }
        }
    } else if ( 'wpi_item' == $typenow ) {
        // Check if 'orderby' is set to "price"
        if ( isset( $vars['orderby'] ) && 'price' == $vars['orderby'] ) {
            $vars = array_merge(
                $vars,
                array(
                    'meta_key' => '_wpinv_price',
                    'orderby'  => 'meta_value_num'
                )
            );
        }

        // Check if "orderby" is set to "vat_rule"
        if ( isset( $vars['orderby'] ) && 'vat_rule' == $vars['orderby'] ) {
            $vars = array_merge(
                $vars,
                array(
                    'meta_key' => '_wpinv_vat_rule',
                    'orderby'  => 'meta_value'
                )
            );
        }

        // Check if "orderby" is set to "vat_class"
        if ( isset( $vars['orderby'] ) && 'vat_class' == $vars['orderby'] ) {
            $vars = array_merge(
                $vars,
                array(
                    'meta_key' => '_wpinv_vat_class',
                    'orderby'  => 'meta_value'
                )
            );
        }
        
        // Check if "orderby" is set to "type"
        if ( isset( $vars['orderby'] ) && 'type' == $vars['orderby'] ) {
            $vars = array_merge(
                $vars,
                array(
                    'meta_key' => '_wpinv_type',
                    'orderby'  => 'meta_value'
                )
            );
        }
        
        // Check if "orderby" is set to "recurring"
        if ( isset( $vars['orderby'] ) && 'recurring' == $vars['orderby'] ) {
            $vars = array_merge(
                $vars,
                array(
                    'meta_key' => '_wpinv_is_recurring',
                    'orderby'  => 'meta_value'
                )
            );
        }

        $meta_query = !empty( $vars['meta_query'] ) ? $vars['meta_query'] : array();
        // Filter vat rule type
        if ( isset( $_GET['vat_rule'] ) && $_GET['vat_rule'] !== '' ) {
            $meta_query[] = array(
                    'key'   => '_wpinv_vat_rule',
                    'value' => sanitize_text_field( $_GET['vat_rule'] ),
                    'compare' => '='
                );
        }
        
        // Filter vat class
        if ( isset( $_GET['vat_class'] ) && $_GET['vat_class'] !== '' ) {
            $meta_query[] = array(
                    'key'   => '_wpinv_vat_class',
                    'value' => sanitize_text_field( $_GET['vat_class'] ),
                    'compare' => '='
                );
        }
        
        // Filter item type
        if ( isset( $_GET['type'] ) && $_GET['type'] !== '' ) {
            $meta_query[] = array(
                    'key'   => '_wpinv_type',
                    'value' => sanitize_text_field( $_GET['type'] ),
                    'compare' => '='
                );
        }
        
        if ( !empty( $meta_query ) ) {
            $vars['meta_query'] = $meta_query;
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

function wpinv_options_page() {
    $page       = isset( $_GET['page'] )                ? strtolower( $_GET['page'] )               : false;
    
    if ( $page !== 'wpinv-settings' ) {
        return;
    }
    
    $settings_tabs = wpinv_get_settings_tabs();
    $settings_tabs = empty($settings_tabs) ? array() : $settings_tabs;
    $active_tab    = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $settings_tabs ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
    $sections      = wpinv_get_settings_tab_sections( $active_tab );
    $key           = 'main';

    if ( is_array( $sections ) ) {
        $key = key( $sections );
    }

    $registered_sections = wpinv_get_settings_tab_sections( $active_tab );
    $section             = isset( $_GET['section'] ) && ! empty( $registered_sections ) && array_key_exists( $_GET['section'], $registered_sections ) ? $_GET['section'] : $key;
    ob_start();
    ?>
    <div class="wrap">
        <h1 class="nav-tab-wrapper">
            <?php
            foreach( wpinv_get_settings_tabs() as $tab_id => $tab_name ) {
                $tab_url = add_query_arg( array(
                    'settings-updated' => false,
                    'tab' => $tab_id,
                ) );

                // Remove the section from the tabs so we always end up at the main section
                $tab_url = remove_query_arg( 'section', $tab_url );
                $tab_url = remove_query_arg( 'wpi_sub', $tab_url );

                $active = $active_tab == $tab_id ? ' nav-tab-active' : '';

                echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
                    echo esc_html( $tab_name );
                echo '</a>';
            }
            ?>
        </h1>
        <?php
        $number_of_sections = count( $sections );
        $number = 0;
        if ( $number_of_sections > 1 ) {
            echo '<div><ul class="subsubsub">';
            foreach( $sections as $section_id => $section_name ) {
                echo '<li>';
                $number++;
                $tab_url = add_query_arg( array(
                    'settings-updated' => false,
                    'tab' => $active_tab,
                    'section' => $section_id
                ) );
                $tab_url = remove_query_arg( 'wpi_sub', $tab_url );
                $class = '';
                if ( $section == $section_id ) {
                    $class = 'current';
                }
                echo '<a class="' . $class . '" href="' . esc_url( $tab_url ) . '">' . $section_name . '</a>';

                if ( $number != $number_of_sections ) {
                    echo ' | ';
                }
                echo '</li>';
            }
            echo '</ul></div>';
        }
        ?>
        <div id="tab_container">
            <form method="post" action="options.php">
                <table class="form-table">
                <?php
                settings_fields( 'wpinv_settings' );

                if ( 'main' === $section ) {
                    do_action( 'wpinv_settings_tab_top', $active_tab );
                }

                do_action( 'wpinv_settings_tab_top_' . $active_tab . '_' . $section );
                do_settings_sections( 'wpinv_settings_' . $active_tab . '_' . $section );
                do_action( 'wpinv_settings_tab_bottom_' . $active_tab . '_' . $section  );

                // For backwards compatibility
                if ( 'main' === $section ) {
                    do_action( 'wpinv_settings_tab_bottom', $active_tab );
                }
                ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div><!-- #tab_container-->
    </div><!-- .wrap -->
    <?php
    $content = ob_get_clean(); 
    echo $content;
}

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