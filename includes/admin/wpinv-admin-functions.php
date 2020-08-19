<?php
/**
 * Contains functions related to Invoicing plugin.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
// MUST have WordPress.
if ( !defined( 'WPINC' ) ) {
    exit( 'Do NOT access this file directly: ' . basename( __FILE__ ) );
}

function wpinv_bulk_actions( $actions ) {
    if ( isset( $actions['edit'] ) ) {
        unset( $actions['edit'] );
    }

    return $actions;
}
add_filter( 'bulk_actions-edit-wpi_invoice', 'wpinv_bulk_actions' );
add_filter( 'bulk_actions-edit-wpi_item', 'wpinv_bulk_actions' );

function wpinv_admin_post_id( $id = 0 ) {
    global $post;

    if ( isset( $id ) && ! empty( $id ) ) {
        return (int)$id;
    } else if ( get_the_ID() ) {
        return (int) get_the_ID();
    } else if ( isset( $post->ID ) && !empty( $post->ID ) ) {
        return (int) $post->ID;
    } else if ( isset( $_GET['post'] ) && !empty( $_GET['post'] ) ) {
        return (int) $_GET['post'];
    } else if ( isset( $_GET['id'] ) && !empty( $_GET['id'] ) ) {
        return (int) $_GET['id'];
    } else if ( isset( $_POST['id'] ) && !empty( $_POST['id'] ) ) {
        return (int) $_POST['id'];
    } 

    return null;
}
    
function wpinv_admin_post_type( $id = 0 ) {
    if ( !$id ) {
        $id = wpinv_admin_post_id();
    }
    
    $type = get_post_type( $id );
    
    if ( !$type ) {
        $type = isset( $_GET['post_type'] ) && !empty( $_GET['post_type'] ) ? $_GET['post_type'] : null;
    }
    
    return apply_filters( 'wpinv_admin_post_type', $type, $id );
}

function wpinv_admin_messages() {
	global $wpinv_options, $pagenow, $post;

	if ( isset( $_GET['wpinv-message'] ) && 'discount_added' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		 add_settings_error( 'wpinv-notices', 'wpinv-discount-added', __( 'Discount code added.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'discount_add_failed' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-discount-add-fail', __( 'There was a problem adding your discount code, please try again.', 'invoicing' ), 'error' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'discount_exists' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-discount-exists', __( 'A discount with that code already exists, please use a different code.', 'invoicing' ), 'error' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'discount_updated' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		 add_settings_error( 'wpinv-notices', 'wpinv-discount-updated', __( 'Discount code updated.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'discount_update_failed' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-discount-updated-fail', __( 'There was a problem updating your discount code, please try again.', 'invoicing' ), 'error' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'invoice_deleted' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-deleted', __( 'The invoice has been deleted.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'email_disabled' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-sent-fail', __( 'Email notification is disabled. Please check settings.', 'invoicing' ), 'error' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'email_sent' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-sent', __( 'The email has been sent to customer.', 'invoicing' ), 'updated' );
    }
    
    if ( isset( $_GET['wpinv-message'] ) && 'email_fail' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-sent-fail', __( 'Fail to send email to the customer.', 'invoicing' ), 'error' );
    }

    if ( isset( $_GET['wpinv-message'] ) && 'invoice-note-deleted' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
        add_settings_error( 'wpinv-notices', 'wpinv-note-deleted', __( 'The invoice note has been deleted.', 'invoicing' ), 'updated' );
    }

	if ( isset( $_GET['wpinv-message'] ) && 'settings-imported' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-settings-imported', __( 'The settings have been imported.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'note-added' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-note-added', __( 'The invoice note has been added successfully.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'invoice-updated' == $_GET['wpinv-message'] && wpinv_current_user_can_manage_invoicing() ) {
		add_settings_error( 'wpinv-notices', 'wpinv-updated', __( 'The invoice has been successfully updated.', 'invoicing' ), 'updated' );
	}
    
	if ( $pagenow == 'post.php' && !empty( $post->post_type ) && $post->post_type == 'wpi_item' && !wpinv_item_is_editable( $post ) ) {
		$message = apply_filters( 'wpinv_item_non_editable_message', __( 'This item in not editable.', 'invoicing' ), $post->ID );

		if ( !empty( $message ) ) {
			add_settings_error( 'wpinv-notices', 'wpinv-edit-n', $message, 'updated' );
		}
	}

	settings_errors( 'wpinv-notices' );
}
add_action( 'admin_notices', 'wpinv_admin_messages' );

add_action( 'admin_init', 'wpinv_show_test_payment_gateway_notice' );
function wpinv_show_test_payment_gateway_notice(){
    add_action( 'admin_notices', 'wpinv_test_payment_gateway_messages' );
}

function wpinv_test_payment_gateway_messages(){
    $gateways = wpinv_get_enabled_payment_gateways();
    $name = array(); $test_gateways = '';
    if ($gateways) {
        foreach ($gateways as $id => $gateway) {
            if (wpinv_is_test_mode($id)) {
                $name[] = $gateway['checkout_label'];
            }
        }
        $test_gateways = implode(', ', $name);
    }
    if(isset($test_gateways) && !empty($test_gateways)){
        $link = admin_url('admin.php?page=wpinv-settings&tab=gateways');
        $notice = wp_sprintf( __('<strong>Important:</strong> Payment Gateway(s) %s are in testing mode and will not receive real payments. Go to <a href="%s"> Gateway Settings</a>.', 'invoicing'), $test_gateways, $link );
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo $notice; ?></p>
        </div>
        <?php
    }
}

function wpinv_items_columns( $existing_columns ) {
    global $wpinv_euvat;
    
    $columns                = array();
    $columns['cb']          = $existing_columns['cb'];
    $columns['title']       = __( 'Title', 'invoicing' );
    $columns['price']       = __( 'Price', 'invoicing' );
    $columns['shortcode']   = __( 'Shortcode', 'invoicing' );
    if ( $wpinv_euvat->allow_vat_rules() ) {
        $columns['vat_rule']    = __( 'VAT rule type', 'invoicing' );
    }
    if ( $wpinv_euvat->allow_vat_classes() ) {
        $columns['vat_class']   = __( 'VAT class', 'invoicing' );
    }
    $columns['type']        = __( 'Type', 'invoicing' );
    $columns['recurring']   = __( 'Recurring', 'invoicing' );
    $columns['date']        = __( 'Date', 'invoicing' );
    $columns['id']          = __( 'ID', 'invoicing' );

    return apply_filters( 'wpinv_items_columns', $columns );
}
add_filter( 'manage_wpi_item_posts_columns', 'wpinv_items_columns' );

function wpinv_items_sortable_columns( $columns ) {
    $columns['price']       = 'price';
    $columns['vat_rule']    = 'vat_rule';
    $columns['vat_class']   = 'vat_class';
    $columns['type']        = 'type';
    //$columns['recurring']   = 'recurring';
    $columns['id']          = 'ID';

    return $columns;
}
add_filter( 'manage_edit-wpi_item_sortable_columns', 'wpinv_items_sortable_columns' );

function wpinv_items_table_custom_column( $column ) {
    global $wpinv_euvat, $post, $wpi_item;
    
    if ( empty( $wpi_item ) || ( !empty( $wpi_item ) && $post->ID != $wpi_item->ID ) ) {
        $wpi_item = new WPInv_Item( $post->ID );
    }

    switch ( $column ) {
        case 'price' :
            echo wpinv_item_price( $post->ID );
        break;
        case 'vat_rule' :
            echo $wpinv_euvat->item_rule_label( $post->ID );
        break;
        case 'shortcode' :
            echo WPInv_Meta_Box_Items::shortcode( $post->ID );
        break;
        case 'vat_class' :
            echo $wpinv_euvat->item_class_label( $post->ID );
        break;
        case 'type' :
            echo wpinv_item_type( $post->ID ) . '<span class="meta">' . $wpi_item->get_custom_singular_name() . '</span>';
        break;
        case 'recurring' :
            echo ( wpinv_is_recurring_item( $post->ID ) ? '<i class="fa fa-check fa-recurring-y"></i>' : '<i class="fa fa-close fa-recurring-n"></i>' );
        break;
        case 'id' :
           echo $post->ID;
           echo '<div class="hidden" id="wpinv_inline-' . $post->ID . '">
                    <div class="price">' . wpinv_get_item_price( $post->ID ) . '</div>';
                    if ( $wpinv_euvat->allow_vat_rules() ) {
                        echo '<div class="vat_rule">' . $wpinv_euvat->get_item_rule( $post->ID ) . '</div>';
                    }
                    if ( $wpinv_euvat->allow_vat_classes() ) {
                        echo '<div class="vat_class">' . $wpinv_euvat->get_item_class( $post->ID ) . '</div>';
                    }
                    echo '<div class="type">' . wpinv_get_item_type( $post->ID ) . '</div>
                </div>';
        break;
    }
    
    do_action( 'wpinv_items_table_column_item_' . $column, $wpi_item, $post );
}
add_action( 'manage_wpi_item_posts_custom_column', 'wpinv_items_table_custom_column' );

function wpinv_add_items_filters() {
    global $wpinv_euvat, $typenow;

    // Checks if the current post type is 'item'
    if ( $typenow == 'wpi_item') {
        if ( $wpinv_euvat->allow_vat_rules() ) {

            // Sanitize selected vat rule.
            $vat_rule   = '';
            if( isset( $_GET['vat_rule'] ) && array_key_exists(  $_GET['type'], $wpinv_euvat->get_rules() ) ) {
                $class   =  $_GET['type'];
            }

            echo wpinv_html_select( array(
                    'options'          => array_merge( array( '' => __( 'All VAT rules', 'invoicing' ) ), $wpinv_euvat->get_rules() ),
                    'name'             => 'vat_rule',
                    'id'               => 'vat_rule',
                    'selected'         => ( isset( $_GET['vat_rule'] ) ? $_GET['vat_rule'] : '' ),
                    'show_option_all'  => false,
                    'show_option_none' => false,
                    'class'            => 'gdmbx2-text-medium wpi_select2',
                    'placeholder'      => __( 'Select VAT rule', 'invoicing' ),
                ) );
        }

        if ( $wpinv_euvat->allow_vat_classes() ) {

            $classes = $wpinv_euvat->get_all_classes();

            // Sanitize selected vat class.
            $class   = '';
            if( isset( $_GET['vat_class'] ) && array_key_exists(  $_GET['vat_class'], $classes ) ) {
                $class   =  $_GET['vat_class'];
            }

            echo wpinv_html_select( array(
                    'options'          => array_merge( array( '' => __( 'All VAT classes', 'invoicing' ) ), $classes ),
                    'name'             => 'vat_class',
                    'id'               => 'vat_class',
                    'selected'         => $class,
                    'show_option_all'  => false,
                    'show_option_none' => false,
                    'class'            => 'gdmbx2-text-medium wpi_select2',
                    'placeholder'      => __( 'Select VAT class', 'invoicing' ),
                ) );
        }
        
        // Sanitize selected item type.
        $type   = '';
        if( isset( $_GET['type'] ) && array_key_exists(  $_GET['type'], wpinv_get_item_types() ) ) {
            $class   =  $_GET['type'];
        }

        echo wpinv_html_select( array(
                'options'          => array_merge( array( '' => __( 'All item types', 'invoicing' ) ), wpinv_get_item_types() ),
                'name'             => 'type',
                'id'               => 'type',
                'selected'         => $type,
                'show_option_all'  => false,
                'show_option_none' => false,
                'class'            => 'gdmbx2-text-medium',
            ) );

        if ( isset( $_REQUEST['all_posts'] ) && '1' === $_REQUEST['all_posts'] ) {
            echo '<input type="hidden" name="all_posts" value="1" />';
        }
    }
}
add_action( 'restrict_manage_posts', 'wpinv_add_items_filters', 100 );

function wpinv_send_invoice_after_save( $invoice ) {
    if ( empty( $_POST['wpi_save_send'] ) ) {
        return;
    }
    
    if ( !empty( $invoice->ID ) && !empty( $invoice->post_type ) && 'wpi_invoice' == $invoice->post_type ) {
        wpinv_user_invoice_notification( $invoice->ID );
    }
}
add_action( 'wpinv_invoice_metabox_saved', 'wpinv_send_invoice_after_save', 100, 1 );


add_action('admin_init', 'admin_init_example_type');

/**
 * hook the posts search if we're on the admin page for our type
 */
function admin_init_example_type() {
    global $typenow;

    if ($typenow === 'wpi_invoice' || $typenow === 'wpi_quote' ) {
        add_filter('posts_search', 'posts_search_example_type', 10, 2);
    }
}

/**
 * add query condition for search invoice by email
 * @param string $search the search string so far
 * @param WP_Query $query
 * @return string
 */
function posts_search_example_type($search, $query) {
    global $wpdb;

    if ($query->is_main_query() && !empty($query->query['s'])) {
        $conditions_str = "{$wpdb->posts}.post_author IN ( SELECT ID FROM {$wpdb->users} WHERE user_email LIKE '%" . esc_sql( $query->query['s'] ) . "%' )";
        if ( ! empty( $search ) ) {
            $search = preg_replace( '/^ AND /', '', $search );
            $search = " AND ( {$search} OR ( {$conditions_str} ) )";
        } else {
            $search = " AND ( {$conditions_str} )";
        }
    }

    return $search;
}

add_action( 'admin_init', 'wpinv_reset_invoice_count' );
function wpinv_reset_invoice_count(){
    if(isset($_GET['reset_invoice_count']) && 1 == $_GET['reset_invoice_count'] && isset($_GET['_nonce']) && wp_verify_nonce($_GET['_nonce'], 'reset_invoice_count')) {
        wpinv_update_option('invoice_sequence_start', 1);
        delete_option('wpinv_last_invoice_number');
        $url = add_query_arg(array('reset_invoice_done' => 1));
        $url = remove_query_arg(array('reset_invoice_count', '_nonce'), $url);
        wp_redirect($url);
        exit();
    }
}

add_action('admin_notices', 'wpinv_invoice_count_reset_message');
function wpinv_invoice_count_reset_message(){
    if(isset($_GET['reset_invoice_done']) && 1 == $_GET['reset_invoice_done']) {
        $notice = __('Invoice number sequence reset successfully.', 'invoicing');
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo $notice; ?></p>
        </div>
        <?php
    }
}
