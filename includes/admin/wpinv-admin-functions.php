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
    if(isset($test_gateways) && !empty($test_gateways) && wpinv_current_user_can_manage_invoicing()){
        $link = admin_url('admin.php?page=wpinv-settings&tab=gateways');
        $notice = wp_sprintf( __('<strong>Important:</strong> Payment Gateway(s) %s are in testing mode and will not receive real payments. Go to <a href="%s"> Gateway Settings</a>.', 'invoicing'), $test_gateways, $link );
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php echo $notice; ?></p>
        </div>
        <?php
    }
}

/**
 * Checks if all tables are available,
 * and alerts the user for any missing tables.
 */
function wpinv_check_for_missing_tables() {
    global $wpdb;

    // Only do this on our settings page.
    if ( empty( $_GET[ 'page' ] ) || 'wpinv-settings' !== $_GET[ 'page' ] ) {
        return;
    }

    // Check tables.
    $tables             = array(
        "{$wpdb->prefix}wpinv_subscriptions",
        "{$wpdb->prefix}getpaid_invoices",
        "{$wpdb->prefix}getpaid_invoice_items",
    );

    foreach ( $tables as $table ) {
        if ( $table != $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) {

            $url     = esc_url(
                wp_nonce_url(
                    add_query_arg( 'getpaid-admin-action', 'create_missing_tables' ),
                    'getpaid-nonce',
                    'getpaid-nonce'
                )
            );
            $message  = __( 'Some GetPaid database tables are missing. To use GetPaid without any issues, click on the button below to create the missing tables.', 'invoicing' );
            $message2 = __( 'Create Tables', 'invoicing' );
            echo "<div class='notice notice-warning is-dismissible'><p>$message<br><br><a href='$url' class='button button-primary'>$message2</a></p></div>";
            break;

        }
    }

}
add_action( 'admin_notices', 'wpinv_check_for_missing_tables' );

add_action('admin_init', 'wpinv_admin_search_by_invoice');

/**
 * hook the posts search if we're on the admin page for our type
 */
function wpinv_admin_search_by_invoice() {
    global $typenow;

    if ($typenow === 'wpi_invoice' || $typenow === 'wpi_quote' ) {
        add_filter('posts_search', 'wpinv_posts_search_example_type', 10, 2);
    }
}

/**
 * add query condition for search invoice by email
 * @param string $search the search string so far
 * @param WP_Query $query
 * @return string
 */
function wpinv_posts_search_example_type($search, $query) {
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

/**
 * Resets invoice counts.
 */
function wpinv_reset_invoice_count(){
    if ( ! empty( $_GET['reset_invoice_count'] ) && isset( $_GET['_nonce'] ) && wp_verify_nonce( $_GET['_nonce'], 'reset_invoice_count' ) ) {
        wpinv_update_option('invoice_sequence_start', 1);
        delete_option('wpinv_last_invoice_number');
        getpaid_admin()->show_success( __( 'Invoice number sequence reset successfully.', 'invoicing' ) );
        $url = remove_query_arg( array('reset_invoice_count', '_nonce') );
        wp_redirect($url);
        exit();
    }
}
add_action( 'admin_init', 'wpinv_reset_invoice_count' );

/**
 * Displays line items on the invoice edit page.
 *
 * @param WPInv_Invoice $invoice
 * @param array $columns
 * @return string
 */
function wpinv_admin_get_line_items( $invoice, $columns ) {

    ob_start();

    do_action( 'getpaid_admin_before_line_items', $invoice );

    $count = 0;
    foreach ( $invoice->get_items() as $item ) {

        $item_price     = wpinv_price( $item->get_price(), $invoice->get_currency() );
        $quantity       = (int) $item->get_quantity();
        $item_subtotal  = wpinv_price( $item->get_sub_total(), $invoice->get_currency() );
        $summary        = apply_filters( 'getpaid_admin_invoice_line_item_summary', $item->get_description(), $item, $invoice );
        $item_tax       = $item->item_tax;
        $tax_rate       = wpinv_round_amount( getpaid_get_invoice_tax_rate( $invoice, $item ), 2, true ) . '%';;
        $tax_rate       = empty( $tax_rate ) ? ' <span class="tax-rate">(' . $tax_rate . '%)</span>' : '';
        $line_item_tax  = $item_tax . $tax_rate;
        $line_item      = '<tr class="item item-' . ( ($count % 2 == 0) ? 'even' : 'odd' ) . '" data-item-id="' . esc_attr( $item->get_id() ) . '">';
        $line_item     .= '<td class="id">' . (int) $item->get_id() . '</td>';
        $line_item     .= '<td class="title"><a href="' . get_edit_post_link( $item->get_id() ) . '" target="_blank">' . $item->get_name() . '</a>';

        if ( $summary !== '' ) {
            $line_item .= '<span class="meta">' . wpautop( wp_kses_post( $summary ) ) . '</span>';
        }

        $line_item .= '</td>';
        $line_item .= '<td class="price">' . $item_price . '</td>';
        $line_item .= '<td class="qty" data-quantity="' . $quantity . '">&nbsp;&times;&nbsp;' . $quantity . '</td>';
        $line_item .= '<td class="total">' . $item_subtotal . '</td>';

        if ( wpinv_use_taxes() && $invoice->is_taxable() ) {
            $line_item .= '<td class="tax">' . $line_item_tax . '</td>';
        }

        $line_item .= '<td class="action">';
        if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) {
            $line_item .= '<i class="fa fa-remove wpinv-item-remove"></i>';
        }
        $line_item .= '</td>';
        $line_item .= '</tr>';

        echo apply_filters( 'getpaid_admin_line_item', $line_item, $item, $invoice );

        $count++;
    }

    do_action( 'getpaid_admin_after_line_items', $invoice );

    return ob_get_clean();
}
