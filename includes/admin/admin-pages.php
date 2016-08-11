<?php
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

add_action( 'admin_menu', 'wpinv_add_options_link', 10 );
function wpinv_add_options_link() {
    global $menu;

    if ( current_user_can( 'manage_options' ) ) {
        $menu[] = array( '', 'read', 'separator-wpinv', '', 'wp-menu-separator wpinv' );
    }
    
    $wpi_invoice            = get_post_type_object( 'wpi_invoice' );

    add_menu_page( __( 'Invoices', 'invoicing' ), __( 'Invoices', 'invoicing' ), 'manage_options', 'wpinv', null, $wpi_invoice->menu_icon, '54.123460' );
    
    $wpi_settings_page   = add_submenu_page( 'wpinv', __( 'Invoice Settings', 'invoicing' ), __( 'Settings', 'invoicing' ), 'manage_options', 'wpinv-settings', 'wpinv_options_page' );
}

add_action( 'admin_menu', 'wpinv_remove_admin_submenus', 999 );
function wpinv_remove_admin_submenus() {
    remove_submenu_page( 'edit.php?post_type=wpi_invoice', 'post-new.php?post_type=wpi_invoice' );
}

function wpinv_is_admin_page( $passed_page = '', $passed_view = '' ) {
    global $pagenow, $typenow;

    $found      = false;
    $post_type  = isset( $_GET['post_type'] )           ? strtolower( $_GET['post_type'] )          : false;
    $action     = isset( $_GET['action'] )              ? strtolower( $_GET['action'] )             : false;
    $page       = isset( $_GET['page'] )                ? strtolower( $_GET['page'] )               : false;
    $view       = isset( $_GET['view'] )                ? strtolower( $_GET['view'] )               : false;
    $edd_action = isset( $_GET['wpinv-action'] )   ? strtolower( $_GET['wpinv-action'] )  : false;
    $tab        = isset( $_GET['tab'] )                 ? strtolower( $_GET['tab'] )                : false;

    switch ( $passed_page ) {
        case 'wpi_invoice':
            switch ( $passed_view ) {
                case 'list-table':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' ) {
                        $found = true;
                    }
                    break;
                case 'edit':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'post.php' ) {
                        $found = true;
                    }
                    break;
                case 'new':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'post-new.php' ) {
                        $found = true;
                    }
                    break;
                default:
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) || 'wpi_invoice' === $post_type || ( 'post-new.php' == $pagenow && 'wpi_invoice' === $post_type ) ) {
                        $found = true;
                    }
                    break;
            }
            break;
        case 'discounts':
            switch ( $passed_view ) {
                case 'list-table':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-discounts' === $page && false === $edd_action ) {
                        $found = true;
                    }
                    break;
                case 'edit':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-discounts' === $page && 'edit_discount' === $edd_action ) {
                        $found = true;
                    }
                    break;
                case 'new':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-discounts' === $page && 'add_discount' === $edd_action ) {
                        $found = true;
                    }
                    break;
                default:
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-discounts' === $page ) {
                        $found = true;
                    }
                    break;
            }
            break;
        case 'settings':
            switch ( $passed_view ) {
                case 'general':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-settings' === $page && ( 'genera' === $tab || false === $tab ) ) {
                        $found = true;
                    }
                    break;
                case 'gateways':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-settings' === $page && 'gateways' === $tab ) {
                        $found = true;
                    }
                    break;
                case 'emails':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-settings' === $page && 'emails' === $tab ) {
                        $found = true;
                    }
                    break;
                case 'taxes':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-settings' === $page && 'taxes' === $tab ) {
                        $found = true;
                    }
                    break;
                case 'misc':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-settings' === $page && 'misc' === $tab ) {
                        $found = true;
                    }
                    break;
                default:
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-settings' === $page ) {
                        $found = true;
                    }
                    break;
            }
            break;
        case 'customers':
            switch ( $passed_view ) {
                case 'list-table':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-customers' === $page && false === $view ) {
                        $found = true;
                    }
                    break;
                case 'overview':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-customers' === $page && 'overview' === $view ) {
                        $found = true;
                    }
                    break;
                case 'notes':
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-customers' === $page && 'notes' === $view ) {
                        $found = true;
                    }
                    break;
                default:
                    if ( ( 'wpi_invoice' == $typenow || 'wpi_invoice' === $post_type ) && $pagenow == 'edit.php' && 'wpinv-customers' === $page ) {
                        $found = true;
                    }
                    break;
            }
            break;
        default:
            global $wpi_discounts_page, $wpi_invoices_page, $wpi_settings_page, $wpi_system_info_pag, $wpi_customers_page;
            
            $admin_pages = apply_filters( 'wpinv_admin_pages', array( $wpi_discounts_page, $wpi_invoices_page, $wpi_settings_page, $wpi_system_info_pag, $wpi_customers_page ) );
            
            if ( 'wpi_invoice' == $typenow || 'index.php' == $pagenow || 'post-new.php' == $pagenow || 'post.php' == $pagenow ) {
                $found = true;
            } elseif ( in_array( $pagenow, $admin_pages ) ) {
                $found = true;
            }
            break;
    }

    return (bool) apply_filters( 'wpinv_is_admin_page', $found, $page, $view, $passed_page, $passed_view );
}


add_filter( 'manage_wpinv_discount_posts_columns', 'wpinv_discount_columns' );
function wpinv_discount_columns( $existing_columns ) {
    $columns                = array();
    $columns['cb']          = $existing_columns['cb'];
    $columns['name']        = __( 'Name', 'invoicing' );
    $columns['code']        = __( 'Code', 'invoicing' );
    $columns['type']        = __( 'Type', 'invoicing' );
    $columns['amount']      = __( 'Amount', 'invoicing' );
    $columns['usage']       = __( 'Usage / Limit', 'invoicing' );
    $columns['expiry_date'] = __( 'Expiry Date', 'invoicing' );
    $columns['status']      = __( 'Status', 'invoicing' );

    return $columns;
}

add_action( 'manage_wpinv_discount_posts_custom_column', 'wpinv_discount_custom_column' );
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
        case 'type' :
            echo wpinv_get_discount_type( $discount->ID, true );
        break;
        case 'amount' :
            echo wpinv_get_discount_amount( $discount->ID );
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
    
    if ( $post_type == 'wpinv_discount' ) {
        $actions = wpinv_discount_row_actions( $post, $actions );
    }
    
    return $actions;
}

function wpinv_discount_row_actions( $discount, $row_actions ) {
    $row_actions  = array();
    $edit_link = get_edit_post_link( $discount->ID );
    $row_actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit', 'invoicing' ) . '</a>';

    if( in_array( strtolower( $discount->post_status ),  array( 'active', 'publish' ) ) ) {
        $row_actions['deactivate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'wpinv-action' => 'deactivate_discount', 'discount' => $discount->ID ) ), 'wpinv_discount_nonce' ) ) . '">' . __( 'Deactivate', 'invoicing' ) . '</a>';
    } elseif( in_array( strtolower( $discount->post_status ),  array( 'inactive', 'pending', 'draft' ) ) ) {
        $row_actions['activate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'wpinv-action' => 'activate_discount', 'discount' => $discount->ID ) ), 'wpinv_discount_nonce' ) ) . '">' . __( 'Activate', 'invoicing' ) . '</a>';
    }

    $row_actions['delete'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'wpinv-action' => 'delete_discount', 'discount' => $discount->ID ) ), 'wpinv_discount_nonce' ) ) . '">' . __( 'Delete', 'invoicing' ) . '</a>';

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
add_filter( 'bulk_actions-edit-wpinv_discount', 'wpinv_discount_bulk_actions', 10 );

function wpinv_disable_months_dropdown( $disable, $post_type ) {
    if ( $post_type == 'wpinv_discount' ) {
        $disable = true;
    }
    
    return $disable;
}
add_filter( 'disable_months_dropdown', 'wpinv_disable_months_dropdown', 10, 2 );

function wpinv_restrict_manage_posts() {
    global $typenow;

    if( 'wpinv_discount' == $typenow ) {
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

function wpinv_discount_views( $views ) {
    $base           = admin_url('edit.php?post_type=wpinv_discount');

    $current        = isset( $_GET['status'] ) ? $_GET['status'] : '';
    $total_count    = '&nbsp;<span class="count">(' . 0    . ')</span>';
    $active_count   = '&nbsp;<span class="count">(' . 0 . ')</span>';
    $inactive_count = '&nbsp;<span class="count">(' . 0  . ')</span>';

    $views = array(
        'all'      => sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( 'status', $base ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All', 'invoicing') . $total_count ),
        'active'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'status', 'active', $base ), $current === 'active' ? ' class="current"' : '', __('Active', 'invoicing') . $active_count ),
        'inactive' => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'status', 'inactive', $base ), $current === 'inactive' ? ' class="current"' : '', __('Inactive', 'invoicing') . $inactive_count ),
    );

    return $views;
}
//add_filter( 'views_edit-wpinv_discount', 'wpinv_discount_views', 10 );

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
                        'orderby'  => 'meta_value_num'
                    )
                );
            } else if ( 'email' == $vars['orderby'] ) {
                $vars = array_merge(
                    $vars,
                    array(
                        'meta_key' => '_wpinv_email',
                        'orderby'  => 'meta_value_num'
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
    $active_tab    = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $settings_tabs ) ? $_GET['tab'] : 'general';
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