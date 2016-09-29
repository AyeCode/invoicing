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
        'ID'                => __( 'ID', 'invoicing' ),
        'details'           => __( 'Details', 'invoicing' ),
        'email'             => __( 'Email', 'invoicing' ),
        'customer'          => __( 'Customer', 'invoicing' ),
        'amount'            => __( 'Amount', 'invoicing' ),
        'invoice_date'      => __( 'Date', 'invoicing' ),
        'status'            => __( 'Status', 'invoicing' ),
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

function wpinv_sortable_columns( $columns ) {
    $columns = array(
        'ID'     => array( 'ID', true ),
        'amount' => array( 'amount', false ),
        'invoice_date'   => array( 'date', false ),
        'customer'   => array( 'customer', false ),
        ///'email'   => array( 'email', false ),
        'status'   => array( 'status', false ),
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
            break;
        case 'amount' :
            echo $wpi_invoice->get_total( true );
            break;
        case 'invoice_date' :
            $date_format = get_option( 'date_format' );
            $time_format = get_option( 'time_format' );
            $date_time_format = $date_format . ' '. $time_format;
            
            $t_time = get_the_time( $date_time_format );
            $m_time = $post->post_date;
            $h_time = mysql2date( $date_format, $m_time );
            
            $value   = '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
            break;
        case 'status' :
            $value   = $wpi_invoice->get_status( true );
            break;
        case 'details' :
            $edit_link = get_edit_post_link( $post->ID );
            $value = '<a href="' . esc_url( $edit_link ) . '">' . __( 'View Invoice Details', 'invoicing' ) . '</a>';
            break;
        case 'wpi_actions' :
            $value = '';
            if ( !empty( $post->post_name ) ) {
                $value .= '<a title="' . esc_attr__( 'Print invoice', 'invoicing' ) . '" href="' . esc_url( get_permalink( $post->ID ) ) . '" class="button ui-tip column-act-btn" title="" target="_blank"><span class="dashicons dashicons-media-default"></span></a>';
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
	global $wpinv_options;

	if ( isset( $_GET['wpinv-message'] ) && 'discount_added' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		 add_settings_error( 'wpinv-notices', 'wpinv-discount-added', __( 'Discount code added.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'discount_add_failed' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-discount-add-fail', __( 'There was a problem adding your discount code, please try again.', 'invoicing' ), 'error' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'discount_exists' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-discount-exists', __( 'A discount with that code already exists, please use a different code.', 'invoicing' ), 'error' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'discount_updated' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		 add_settings_error( 'wpinv-notices', 'wpinv-discount-updated', __( 'Discount code updated.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'discount_update_failed' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-discount-updated-fail', __( 'There was a problem updating your discount code, please try again.', 'invoicing' ), 'error' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'invoice_deleted' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-deleted', __( 'The invoice has been deleted.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'email_sent' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-sent', __( 'The invoice has been sent to customer.', 'invoicing' ), 'updated' );
    }
    
    if ( isset( $_GET['wpinv-message'] ) && 'email_fail' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-sent-fail', __( 'Fail to send invoice to the customer.', 'invoicing' ), 'error' );
    }

    if ( isset( $_GET['wpinv-message'] ) && 'invoice-note-deleted' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
        add_settings_error( 'wpinv-notices', 'wpinv-note-deleted', __( 'The invoice note has been deleted.', 'invoicing' ), 'updated' );
    }

	if ( isset( $_GET['wpinv-message'] ) && 'settings-imported' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-settings-imported', __( 'The settings have been imported.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'note-added' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-note-added', __( 'The invoice note has been added successfully.', 'invoicing' ), 'updated' );
	}

	if ( isset( $_GET['wpinv-message'] ) && 'invoice-updated' == $_GET['wpinv-message'] && current_user_can( 'manage_options' ) ) {
		add_settings_error( 'wpinv-notices', 'wpinv-updated', __( 'The invoice has been successfully updated.', 'invoicing' ), 'updated' );
	}

	settings_errors( 'wpinv-notices' );
}
add_action( 'admin_notices', 'wpinv_admin_messages' );

function wpinv_download_geoip2_file( $download_url, $db_file ) {
    // Download the file from MaxMind, this places it in a temporary location.
    $TempFile = download_url( $download_url );
    
    $result['state'] = false;
    $result['message'] = __( 'Unknown error', 'invoicing' );

    // If we failed, through a message, otherwise proceed.
    if ( is_wp_error( $TempFile ) ) {
        $message = sprintf( __( 'Error downloading GeoIP database from: %s - %s', 'invoicing' ), $download_url, $TempFile->get_error_message() );
        wpinv_error_log( $message );
    } else {
        // Open the downloaded file to unzip it.
        $ZipHandle = gzopen( $TempFile, 'rb' );
            
        // Create th new file to unzip to.
        $DBfh = fopen( $db_file, 'wb' );

        // If we failed to open the downloaded file, through an error and remove the temporary file.  Otherwise do the actual unzip.
        if ( !$ZipHandle ) {
            $result['message'] = sprintf( __( 'Error could not open downloaded GeoIP database for reading: %s', 'invoicing' ), $TempFile );
            wpinv_error_log($result['message']);
        } else {
            // If we failed to open the new file, through and error and remove the temporary file.  Otherwise actually do the unzip.
            if ( !$DBfh ) {
                $result['message'] = sprintf( __( 'Error could not open destination GeoIP database for writing %s', 'invoicing' ), $DBFile );
                wpinv_error_log($result['message']);
            } else {
                while ( ( $data = gzread( $ZipHandle, 4096 ) ) != false ) {
                    fwrite( $DBfh, $data );
                }

                // Close the files.
                gzclose( $ZipHandle );
                fclose( $DBfh );
                    
                // Display the success message.
                $result['message'] = "";
                $result['state'] = true;
            }
        }
        // Delete the temporary file.
        if ( file_exists( $TempFile ) ) {
            unlink( $TempFile );
        }
    }

    return $result;
}

function wpinv_download_geoip2_database() {
    $scheme = 'http' . (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === 'on' ? 's' : '');

    // This is the location of the file to download.
    $download_urls = array(
        'city' => $scheme . '://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz',
        'country' => $scheme . '://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz'
    );

    $filenames = array(
        'city' => '/invoicing/GeoLite2-City.mmdb',
        'country' => '/invoicing/GeoLite2-Country.mmdb'
    );

    // Get the upload directory from WordPRess.
    $upload_dir = wp_upload_dir();

    // Check to see if the subdirectory we're going to upload to exists, if not create it.
    if ( !is_dir( $upload_dir['basedir'] . '/invoicing' ) ) { 
        mkdir( $upload_dir['basedir'] . '/invoicing' );
    }

    foreach( $download_urls as $key => $download_url ) {
        // Create a variable with the name of the database file to download.
        $db_file = $upload_dir['basedir'] . $filenames[$key];

        $result = wpinv_download_geoip2_file( $download_url, $db_file );
        if ( empty( $result['state'] ) ) {
            echo $result['message'];
            exit;
        }
        
        echo __( 'GeoIP Database updated successfully!', 'invoicing' );
    }
    
    exit;
}
add_action( 'wp_ajax_wpinv_download_geoip2', 'wpinv_download_geoip2_database' );
add_action( 'wp_ajax_nopriv_wpinv_download_geoip2', 'wpinv_download_geoip2_database' );

function wpinv_items_columns( $existing_columns ) {
    $columns                = array();
    $columns['cb']          = $existing_columns['cb'];
    $columns['title']       = __( 'Title', 'invoicing' );
    $columns['price']       = __( 'Price', 'invoicing' );
    if ( wpinv_allow_vat_rules() ) {
        $columns['vat_rule']    = __( 'VAT rule type', 'invoicing' );
    }
    if ( wpinv_allow_vat_classes() ) {
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

function wpinv_item_quick_edit( $column_name, $post_type ) {
    if ( !( $post_type == 'wpi_item' && $column_name == 'price' ) ) {
        return;
    }
    global $post;
    
    $symbol    = wpinv_currency_symbol();
    $position  = wpinv_currency_position();

    $price     = wpinv_get_item_price( $post->ID );
    $item_type = wpinv_get_item_type( $post->ID );
    ?>
    <fieldset class="inline-edit-col-right wpi-inline-item-col">
        <div class="inline-edit-col">
            <div class="inline-edit-group wp-clearfix">
                <label class="inline-edit-wpinv-price">
                    <span class="title"><?php _e( 'Item price', 'invoicing' );?></span>
                    <span class="input-text-wrap"><?php echo ( $position != 'right' ? $symbol . '&nbsp;' : '' );?><input type="text" placeholder="<?php echo wpinv_format_amount( 0 ); ?>" value="<?php echo wpinv_format_amount( $price );?>" name="_wpinv_item_price" class="wpi-field-price wpi-price" id="wpinv_item_price-<?php echo $post->ID;?>"><?php echo ( $position == 'right' ? $symbol . '&nbsp;' : '' );?></span>
                </label>
            </div>
            <?php if ( wpinv_allow_vat_rules() ) { $rule_type = wpinv_item_get_vat_rule( $post->ID ); ?>
            <div class="inline-edit-group wp-clearfix">
                <label class="inline-edit-wpinv-vat-rate">
                    <span class="title"><?php _e( 'VAT rule type to use', 'invoicing' );?></span>
                    <span class="input-text-wrap">
                        <?php echo wpinv_html_select( array(
                            'options'          => wpinv_vat_rule_types(),
                            'name'             => '_wpinv_vat_rules',
                            'id'               => 'wpinv_vat_rules-' . $post->ID,
                            'selected'         => $rule_type,
                            'show_option_all'  => false,
                            'show_option_none' => false,
                            'class'            => 'gdmbx2-text-medium wpinv-vat-rules',
                        ) ); ?>
                    </span>
                </label>
            </div>
            <?php } if ( wpinv_allow_vat_classes() ) { $vat_class = wpinv_get_item_vat_class( $post->ID ); ?>
            <div class="inline-edit-group wp-clearfix">
                <label class="inline-edit-wpinv-vat-class">
                    <span class="title"><?php _e( 'VAT class to use', 'invoicing' );?></span>
                    <span class="input-text-wrap">
                        <?php echo wpinv_html_select( array(
                            'options'          => wpinv_vat_get_all_rate_classes(),
                            'name'             => '_wpinv_vat_class',
                            'id'               => 'wpinv_vat_class-' . $post->ID,
                            'selected'         => $vat_class,
                            'show_option_all'  => false,
                            'show_option_none' => false,
                            'class'            => 'gdmbx2-text-medium wpinv-vat-class',
                        ) ); ?>
                    </span>
                </label>
            </div>
            <?php } ?>
            <div class="inline-edit-group wp-clearfix">
                <label class="inline-edit-wpinv-type">
                    <span class="title"><?php _e( 'Item type', 'invoicing' );?></span>
                    <span class="input-text-wrap">
                        <?php echo wpinv_html_select( array(
                            'options'          => wpinv_get_item_types(),
                            'name'             => '_wpinv_item_type',
                            'id'               => 'wpinv_item_type-' . $post->ID,
                            'selected'         => $item_type,
                            'show_option_all'  => false,
                            'show_option_none' => false,
                            'class'            => 'gdmbx2-text-medium wpinv-item-type',
                        ) ); ?>
                    </span>
                </label>
            </div>
        </div>
    </fieldset>
    <?php
}
add_action( 'quick_edit_custom_box', 'wpinv_item_quick_edit', 10, 2 );
add_action( 'bulk_edit_custom_box', 'wpinv_item_quick_edit', 10, 2 );

function wpinv_items_table_custom_column( $column ) {
    global $post, $wpi_item;
    
    if ( empty( $wpi_item ) || ( !empty( $wpi_item ) && $post->ID != $wpi_item->ID ) ) {
        $wpi_item = new WPInv_Item( $post->ID );
    }

    switch ( $column ) {
        case 'price' :
            echo wpinv_item_price( $post->ID );
        break;
        case 'vat_rule' :
            echo wpinv_item_vat_rule( $post->ID );
        break;
        case 'vat_class' :
            echo wpinv_item_vat_class( $post->ID );
        break;
        case 'type' :
            echo wpinv_item_type( $post->ID ) . '<span class="meta">' . $wpi_item->get_cpt_singular_name() . '</span>';
        break;
        case 'recurring' :
            echo ( wpinv_is_recurring_item( $post->ID ) ? '<i class="fa fa-check fa-recurring-y"></i>' : '<i class="fa fa-close fa-recurring-n"></i>' );
        break;
        case 'id' :
           echo $post->ID;
           echo '<div class="hidden" id="wpinv_inline-' . $post->ID . '">
                    <div class="price">' . wpinv_get_item_price( $post->ID ) . '</div>';
                    if ( wpinv_allow_vat_rules() ) {
                        echo '<div class="vat_rule">' . wpinv_item_get_vat_rule( $post->ID ) . '</div>';
                    }
                    if ( wpinv_allow_vat_classes() ) {
                        echo '<div class="vat_class">' . wpinv_get_item_vat_class( $post->ID ) . '</div>';
                    }
                    echo '<div class="type">' . wpinv_get_item_type( $post->ID ) . '</div>
                </div>';
        break;
    }
}
add_action( 'manage_wpi_item_posts_custom_column', 'wpinv_items_table_custom_column' );

function wpinv_add_items_filters() {
    global $typenow;

    // Checks if the current post type is 'item'
    if ( $typenow == 'wpi_item') {
        if ( wpinv_allow_vat_rules() ) {
            echo wpinv_html_select( array(
                    'options'          => array_merge( array( '' => __( 'All VAT rules', 'invoicing' ) ), wpinv_vat_rule_types() ),
                    'name'             => 'vat_rule',
                    'id'               => 'vat_rule',
                    'selected'         => ( isset( $_GET['vat_rule'] ) ? $_GET['vat_rule'] : '' ),
                    'show_option_all'  => false,
                    'show_option_none' => false,
                    'class'            => 'gdmbx2-text-medium',
                ) );
        }
        
        if ( wpinv_allow_vat_classes() ) {
            echo wpinv_html_select( array(
                    'options'          => array_merge( array( '' => __( 'All VAT classes', 'invoicing' ) ), wpinv_vat_get_all_rate_classes() ),
                    'name'             => 'vat_class',
                    'id'               => 'vat_class',
                    'selected'         => ( isset( $_GET['vat_class'] ) ? $_GET['vat_class'] : '' ),
                    'show_option_all'  => false,
                    'show_option_none' => false,
                    'class'            => 'gdmbx2-text-medium',
                ) );
        }
            
        echo wpinv_html_select( array(
                'options'          => array_merge( array( '' => __( 'All item types', 'invoicing' ) ), wpinv_get_item_types() ),
                'name'             => 'type',
                'id'               => 'type',
                'selected'         => ( isset( $_GET['type'] ) ? $_GET['type'] : '' ),
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

function wpinv_send_invoice_after_save( $post_id ) {
    // If this is just a revision, don't send the email.
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    
    if ( !current_user_can( 'manage_options' ) || get_post_type( $post_id ) != 'wpi_invoice' ) {
        return;
    }
    
    if ( !empty( $_POST['wpi_save_send'] ) ) {
        wpinv_user_invoice_notification( $post_id );
    }
}
add_action( 'save_post', 'wpinv_send_invoice_after_save', 100, 1 );

function wpinv_send_register_new_user( $data, $postarr ) {
    if ( current_user_can( 'manage_options' ) && !empty( $data['post_type'] ) && $data['post_type'] == 'wpi_invoice' ) {
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
                $user_login = sanitize_user( str_replace( ' ', '', $user_company ), true );
                
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
                'user_nicename' => mb_substr( $user_nicename, 0, 50 ),
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
                wpinv_error_log( $user_id->get_error_message(), 'Invoice add new user', __FILE__, __LINE__ );
            }
        }
    }
    
    return $data;
}
add_filter( 'wp_insert_post_data', 'wpinv_send_register_new_user', 10, 2 );