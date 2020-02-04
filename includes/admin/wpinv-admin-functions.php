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

function wpinv_columns( $columns ) {
    $columns = array(
        'cb'                => $columns['cb'],
        'number'            => __( 'Number', 'invoicing' ),
        'customer'          => __( 'Customer', 'invoicing' ),
        'amount'            => __( 'Amount', 'invoicing' ),
        'invoice_date'      => __( 'Created Date', 'invoicing' ),
        'payment_date'      => __( 'Payment Date', 'invoicing' ),
        'status'            => __( 'Status', 'invoicing' ),
        'ID'                => __( 'ID', 'invoicing' ),
        'wpi_actions'       => __( 'Actions', 'invoicing' ),
    );

    return apply_filters( 'wpi_invoice_table_columns', $columns );
}
add_filter( 'manage_wpi_invoice_posts_columns', 'wpinv_columns' );

function wpinv_bulk_actions( $actions ) {
    if ( isset( $actions['edit'] ) ) {
        unset( $actions['edit'] );
    }

    return $actions;
}
add_filter( 'bulk_actions-edit-wpi_invoice', 'wpinv_bulk_actions' );
add_filter( 'bulk_actions-edit-wpi_item', 'wpinv_bulk_actions' );

function wpinv_sortable_columns( $columns ) {
    $columns = array(
        'ID'            => array( 'ID', true ),
        'number'        => array( 'number', false ),
        'amount'        => array( 'amount', false ),
        'invoice_date'  => array( 'date', false ),
        'payment_date'  => array( 'payment_date', true ),
        'customer'      => array( 'customer', false ),
        'status'        => array( 'status', false ),
    );
    
    return apply_filters( 'wpi_invoice_table_sortable_columns', $columns );
}
add_filter( 'manage_edit-wpi_invoice_sortable_columns', 'wpinv_sortable_columns' );

add_action( 'manage_wpi_invoice_posts_custom_column', 'wpinv_posts_custom_column');
function wpinv_posts_custom_column( $column_name, $post_id = 0 ) {
    global $post, $wpi_invoice;
    
    if ( empty( $wpi_invoice ) || ( !empty( $wpi_invoice ) && $post->ID != $wpi_invoice->ID ) ) {
        $wpi_invoice = new WPInv_Invoice( $post->ID );
    }

    $value = NULL;
    
    switch ( $column_name ) {
        case 'email' :
            $value   = $wpi_invoice->get_email();
            break;
        case 'customer' :
            $customer_name = $wpi_invoice->get_user_full_name();
            $customer_name = $customer_name != '' ? $customer_name : __( 'Customer', 'invoicing' );
            $value = '<a href="' . esc_url( get_edit_user_link( $wpi_invoice->get_user_id() ) ) . '">' . $customer_name . '</a>';
            if ( $email = $wpi_invoice->get_email() ) {
                $value .= '<br><a class="email" href="mailto:' . $email . '">' . $email . '</a>';
            }
            break;
        case 'amount' :
            echo $wpi_invoice->get_total( true );
            break;
        case 'invoice_date' :
            $date_format = get_option( 'date_format' );
            
            $m_time = $post->post_date;
            $h_time = mysql2date( $date_format, $m_time );
            
            $value   = '<abbr title="' . $m_time . '">' . $h_time . '</abbr>';
            break;
        case 'payment_date' :
            if ( $date_completed = $wpi_invoice->get_meta( '_wpinv_completed_date', true ) ) {
                $date_format = get_option( 'date_format' );
                
                $m_time = $date_completed;
                $h_time = mysql2date( $date_format, $m_time );
                
                $value   = '<abbr title="' . $m_time . '">' . $h_time . '</abbr>';
            } else {
                $value = '-';
            }
            break;
        case 'status' :
            $value   = $wpi_invoice->get_status( true ) . ( $wpi_invoice->is_recurring() && $wpi_invoice->is_parent() ? ' <span class="wpi-suffix">' . __( '(r)', 'invoicing' ) . '</span>' : '' );
            $is_viewed = wpinv_is_invoice_viewed( $wpi_invoice->ID );
	        $gateway_title = wpinv_get_gateway_admin_label( $wpi_invoice->get_gateway() );
	        $offline_gateways = apply_filters('wpinv_offline_payments', array('bank_transfer', 'cheque', 'cod'));
	        $is_offline_payment = in_array($wpi_invoice->get_gateway(), $offline_gateways) ? true : false;

            if ( 1 == $is_viewed ) {
                $value .= '&nbsp;&nbsp;<i class="fa fa-eye" title="'.__( 'Viewed by Customer', 'invoicing' ).'"></i>';
            }
            if ( ( $wpi_invoice->is_paid() || $wpi_invoice->is_refunded() || $is_offline_payment ) && ( isset( $gateway_title ) ) ) {
                $value .= '<br><small class="meta gateway">' . wp_sprintf( __( 'Via %s', 'invoicing' ), $gateway_title ) . '</small>';
            }
            break;
        case 'number' :
            $edit_link = get_edit_post_link( $post->ID );
            $value = '<a title="' . esc_attr__( 'View Invoice Details', 'invoicing' ) . '" href="' . esc_url( $edit_link ) . '">' . $wpi_invoice->get_number() . '</a>';
            break;
        case 'wpi_actions' :
            $value = '';
            if ( !empty( $post->post_name ) ) {
                $value .= '<a title="' . esc_attr__( 'Print invoice', 'invoicing' ) . '" href="' . esc_url( get_permalink( $post->ID ) ) . '" class="button ui-tip column-act-btn" title="" target="_blank"><span class="dashicons dashicons-print"><i style="" class="fa fa-print"></i></span></a>';
            }
            
            if ( $email = $wpi_invoice->get_email() ) {
                $value .= '<a title="' . esc_attr__( 'Send invoice to customer', 'invoicing' ) . '" href="' . esc_url( add_query_arg( array( 'wpi_action' => 'send_invoice', 'invoice_id' => $post->ID ) ) ) . '" class="button ui-tip column-act-btn"><span class="dashicons dashicons-email-alt"></span></a>';
            }
            
            break;
        default:
            $value = isset( $post->$column_name ) ? $post->$column_name : '';
            break;

    }
    $value = apply_filters( 'wpinv_payments_table_column', $value, $post->ID, $column_name );
    
    if ( $value !== NULL ) {
        echo $value;
    }
}

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

function wpinv_send_register_new_user( $data, $postarr ) {
    if ( wpinv_current_user_can_manage_invoicing() && !empty( $data['post_type'] ) && ( 'wpi_invoice' == $data['post_type'] || 'wpi_quote' == $data['post_type'] ) ) {
        $is_new_user = !empty( $postarr['wpinv_new_user'] ) ? true : false;
        $email = !empty( $postarr['wpinv_email'] ) && $postarr['wpinv_email'] && is_email( $postarr['wpinv_email'] ) ? $postarr['wpinv_email'] : NULL;
        
        if ( $is_new_user && $email && !email_exists( $email ) ) {
            $first_name = !empty( $postarr['wpinv_first_name'] ) ? sanitize_text_field( $postarr['wpinv_first_name'] ) : '';
            $last_name = !empty( $postarr['wpinv_last_name'] ) ? sanitize_text_field( $postarr['wpinv_last_name'] ) : '';
            $display_name = $first_name || $last_name ? trim( $first_name . ' ' . $last_name ) : '';
            $user_nicename = $display_name ? trim( $display_name ) : $email;
            $user_company = !empty( $postarr['wpinv_company'] ) ? sanitize_text_field( $postarr['wpinv_company'] ) : '';
            
            $user_login = sanitize_user( str_replace( ' ', '', $display_name ), true );
            if ( !( validate_username( $user_login ) && !username_exists( $user_login ) ) ) {
                $new_user_login = strstr($email, '@', true);
                if ( validate_username( $user_login ) && username_exists( $user_login ) ) {
                    $user_login = sanitize_user($new_user_login, true );
                }
                if ( validate_username( $user_login ) && username_exists( $user_login ) ) {
                    $user_append_text = rand(10,1000);
                    $user_login = sanitize_user($new_user_login.$user_append_text, true );
                }
                
                if ( !( validate_username( $user_login ) && !username_exists( $user_login ) ) ) {
                    $user_login = $email;
                }
            }
            
            $userdata = array(
                'user_login' => $user_login,
                'user_pass' => wp_generate_password( 12, false ),
                'user_email' => sanitize_text_field( $email ),
                'first_name' => $first_name,
                'last_name' => $last_name,
                'user_nicename' => wpinv_utf8_substr( $user_nicename, 0, 50 ),
                'nickname' => $display_name,
                'display_name' => $display_name,
            );

            $userdata = apply_filters( 'wpinv_register_new_user_data', $userdata );
            
            $new_user_id = wp_insert_user( $userdata );
            
            if ( !is_wp_error( $new_user_id ) ) {
                $data['post_author'] = $new_user_id;
                $_POST['post_author'] = $new_user_id;
                $_POST['post_author_override'] = $new_user_id;
                
                $meta_fields = array(
                    'first_name',
                    'last_name',
                    'company',
                    'vat_number',
                    ///'email',
                    'address',
                    'city',
                    'state',
                    'country',
                    'zip',
                    'phone',
                );
                
                $meta = array();
                ///$meta['_wpinv_user_id'] = $new_user_id;
                foreach ( $meta_fields as $field ) {
                    $meta['_wpinv_' . $field] = isset( $postarr['wpinv_' . $field] ) ? sanitize_text_field( $postarr['wpinv_' . $field] ) : '';
                }
                
                $meta = apply_filters( 'wpinv_register_new_user_meta', $meta, $new_user_id );

                // Update user meta.
                foreach ( $meta as $key => $value ) {
                    update_user_meta( $new_user_id, $key, $value );
                }
                
                if ( function_exists( 'wp_send_new_user_notifications' ) ) {
                    // Send email notifications related to the creation of new user.
                    wp_send_new_user_notifications( $new_user_id, 'user' );
                }
            } else {
                wpinv_error_log( $new_user_id->get_error_message(), 'Invoice add new user', __FILE__, __LINE__ );
            }
        }
    }
    
    return $data;
}
add_filter( 'wp_insert_post_data', 'wpinv_send_register_new_user', 10, 2 );

function wpinv_show_recurring_supported_gateways( $item_ID ) {
    $all_gateways = wpinv_get_payment_gateways();

    if ( !empty( $all_gateways ) ) {
        $gateways = array();

        foreach ( $all_gateways as $key => $gateway ) {
            if ( wpinv_gateway_support_subscription( $key ) ) {
                $gateways[] = $gateway['admin_label'];
            }
        }

        if ( !empty( $gateways ) ) {
            ?>
            <span class="description"><?php echo wp_sprintf( __( 'Recurring payments only supported by: %s', 'invoicing' ), implode( ', ', $gateways ) ); ?></span>
            <?php
        }
    }
}
add_action( 'wpinv_item_price_field', 'wpinv_show_recurring_supported_gateways', -10, 1 );

function wpinv_post_updated_messages( $messages ) {
    global $post, $post_ID;

    $messages['wpi_discount'] = array(
        0   => '',
        1   => __( 'Discount updated.', 'invoicing' ),
        2   => __( 'Custom field updated.', 'invoicing' ),
        3   => __( 'Custom field deleted.', 'invoicing' ),
        4   => __( 'Discount updated.', 'invoicing' ),
        5   => isset( $_GET['revision'] ) ? wp_sprintf( __( 'Discount restored to revision from %s', 'invoicing' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
        6   => __( 'Discount updated.', 'invoicing' ),
        7   => __( 'Discount saved.', 'invoicing' ),
        8   => __( 'Discount submitted.', 'invoicing' ),
        9   => wp_sprintf( __( 'Discount scheduled for: <strong>%1$s</strong>.', 'invoicing' ), date_i18n( __( 'M j, Y @ G:i', 'invoicing' ), strtotime( $post->post_date ) ) ),
        10  => __( 'Discount draft updated.', 'invoicing' ),
    );

    return $messages;
}
add_filter( 'post_updated_messages', 'wpinv_post_updated_messages', 10, 1 );

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