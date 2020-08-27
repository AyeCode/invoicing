<?php
/**
 * Contains functions related to Invoicing plugin.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
defined( 'ABSPATH' ) || exit;

/**
 * Retrieves the current invoice.
 */
function getpaid_get_current_invoice_id() {

    // Ensure that we have an invoice key.
    if ( empty( $_GET['invoice_key'] ) ) {
        return 0;
    }

    // Retrieve an invoice using the key.
    $invoice = new WPInv_Invoice( $_GET['invoice_key'] );

    // Compare the invoice key and the parsed key.
    if ( $invoice->get_id() != 0 && $invoice->get_key() == $_GET['invoice_key'] ) {
        return $invoice->get_id();
    }

    return 0;
}

/**
 * Checks if the current user cna view an invoice.
 */
function wpinv_user_can_view_invoice( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );

    // Abort if the invoice does not exist.
    if ( 0 == $invoice->get_id() ) {
        return false;
    }

    // Don't allow trash, draft status
    if ( $invoice->is_draft() ) {
        return false;
    }

    // If users are not required to login to check out, compare the invoice keys.
    if ( ! wpinv_require_login_to_checkout() && isset( $_GET['invoice_key'] ) && trim( $_GET['invoice_key'] ) == $invoice->get_key() ) {
        return true;
    }

    // Always enable for admins..
    if ( wpinv_current_user_can_manage_invoicing() || current_user_can( 'view_invoices', $invoice->get_id() ) ) { // Admin user
        return true;
    }

    // Else, ensure that this is their invoice.
    if ( is_user_logged_in() && $invoice->get_user_id() == get_current_user_id() ) {
        return true;
    }

    return apply_filters( 'wpinv_current_user_can_view_invoice', false, $invoice );
}

/**
 * Checks if the current user cna view an invoice receipt.
 */
function wpinv_can_view_receipt( $invoice ) {
	return (bool) apply_filters( 'wpinv_can_view_receipt', wpinv_user_can_view_invoice( $invoice ), $invoice );
}

/**
 * Returns an array of all invoice post types.
 * 
 * @return array
 */
function getpaid_get_invoice_post_types() {
    $post_types = array(
        'wpi_quote'   => __( 'Quote', 'invoicing' ),
        'wpi_invoice' => __( 'Invoice', 'invoicing' ),
    );

    return apply_filters( 'getpaid_invoice_post_types', $post_types );
}

/**
 * Checks if this is an invocing post type.
 * 
 * 
 * @param string $post_type The post type to check for.
 */
function getpaid_is_invoice_post_type( $post_type ) {
    return ! empty( $post_type ) && array_key_exists( $post_type, getpaid_get_invoice_post_types() );
}

/**
 * Creates a new invoice.
 * 
 * @param  array $data   An array of invoice properties.
 * @param  bool  $wp_error       Whether to return false or WP_Error on failure.
 * @return int|WP_Error|WPInv_Invoice The value 0 or WP_Error on failure. The WPInv_Invoice object on success.
 */
function wpinv_create_invoice( $data = array(), $deprecated = null, $wp_error = false ) {
    $data[ 'invoice_id' ] = 0;
    return wpinv_insert_invoice( $data, $wp_error );
}

/**
 * Updates an existing invoice.
 * 
 * @param  array $data   An array of invoice properties.
 * @param  bool  $wp_error       Whether to return false or WP_Error on failure.
 * @return int|WP_Error|WPInv_Invoice The value 0 or WP_Error on failure. The WPInv_Invoice object on success.
 */
function wpinv_update_invoice( $data = array(), $wp_error = false ) {

    // Backwards compatibility.
    if ( ! empty( $data['ID'] ) ) {
        $data['invoice_id'] = $data['ID'];
    }

    // Do we have an invoice id?
    if ( empty( $data['invoice_id'] ) ) {
        return $wp_error ? new WP_Error( 'invalid_invoice_id', __( 'Invalid invoice ID.', 'invoicing' ) ) : 0;
    }

    // Retrieve the invoice.
    $invoice = wpinv_get_invoice( $data['invoice_id'] );

    // And abort if it does not exist.
    if ( empty( $invoice ) ) {
        return $wp_error ? new WP_Error( 'missing_invoice', __( 'Invoice not found.', 'invoicing' ) ) : 0;
    }

    // Do not update totals for paid / refunded invoices.
    if ( $invoice->is_paid() || $invoice->is_refunded() ) {

        if ( ! empty( $data['items'] ) || ! empty( $data['cart_details'] ) ) {
            return $wp_error ? new WP_Error( 'paid_invoice', __( 'You can not update cart items for invoices that have already been paid for.', 'invoicing' ) ) : 0;
        }

    }

    return wpinv_insert_invoice( $data, $wp_error );

}

/**
 * Create/Update an invoice
 * 
 * @param  array $data   An array of invoice properties.
 * @param  bool  $wp_error       Whether to return false or WP_Error on failure.
 * @return int|WP_Error|WPInv_Invoice The value 0 or WP_Error on failure. The WPInv_Invoice object on success.
 */
function wpinv_insert_invoice( $data = array(), $wp_error = false ) {

    // Ensure that we have invoice data.
    if ( empty( $data ) ) {
        return false;
    }

    // The invoice id will be provided when updating an invoice.
    $data['invoice_id'] = ! empty( $data['invoice_id'] ) ? (int) $data['invoice_id'] : false;

    // Retrieve the invoice.
    $invoice = new WPInv_Invoice( $data['invoice_id'] );

    // Do we have an error?
    if ( ! empty( $invoice->last_error ) ) {
        return $wp_error ? new WP_Error( 'invalid_invoice_id', $invoice->last_error ) : 0;
    }

    // Backwards compatibility (billing address).
    if ( ! empty( $data['user_info'] ) ) {

        foreach ( $data['user_info'] as $key => $value ) {

            if ( $key == 'discounts' ) {
                $value = (array) $value;
                $data[ 'discount_code' ] = empty( $value ) ? null : $value[0];
            } else {
                $data[ $key ] = $value;
            }

        }

    }

    // Backwards compatibility.
    if ( ! empty( $data['payment_details'] ) ) {

        foreach ( $data['payment_details'] as $key => $value ) {
            $data[ $key ] = $value;
        }

    }

    // Set up the owner of the invoice.
    $user_id = ! empty( $data['user_id'] ) ? wpinv_clean( $data['user_id'] ) : get_current_user_id();

    // Make sure the user exists.
    if ( ! get_userdata( $user_id ) ) {
        return $wp_error ? new WP_Error( 'wpinv_invalid_user', __( 'There is no user with that ID.', 'invoicing' ) ) : 0;
    }

    $address = wpinv_get_user_address( $user_id );

    foreach ( $address as $key => $value ) {

        if ( $value == '' ) {
            $address[ $key ] = null;
        } else {
            $address[ $key ] = wpinv_clean( $value );
        }

    }

    // Load new data.
    $invoice->set_props(

        array(

            // Basic info.
            'template'             => isset( $data['template'] ) ? wpinv_clean( $data['template'] ) : null,
            'email_cc'             => isset( $data['email_cc'] ) ? wpinv_clean( $data['email_cc'] ) : null,
            'date_created'         => isset( $data['created_date'] ) ? wpinv_clean( $data['created_date'] ) : null,
            'due_date'             => isset( $data['due_date'] ) ? wpinv_clean( $data['due_date'] ) : null,
            'date_completed'       => isset( $data['date_completed'] ) ? wpinv_clean( $data['date_completed'] ) : null,
            'number'               => isset( $data['number'] ) ? wpinv_clean( $data['number'] ) : null,
            'key'                  => isset( $data['key'] ) ? wpinv_clean( $data['key'] ) : null,
            'status'               => isset( $data['status'] ) ? wpinv_clean( $data['status'] ) : null,
            'post_type'            => isset( $data['post_type'] ) ? wpinv_clean( $data['post_type'] ) : null,
            'user_ip'              => isset( $data['ip'] ) ? wpinv_clean( $data['ip'] ) : wpinv_get_ip(),
            'parent_id'            => isset( $data['parent'] ) ? intval( $data['parent'] ) : null,
            'mode'                 => isset( $data['mode'] ) ? wpinv_clean( $data['mode'] ) : null,
            'description'          => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : null,

            // Payment info.
            'disable_taxes'        => ! empty( $data['disable_taxes'] ),
            'currency'             => isset( $data['currency'] ) ? wpinv_clean( $data['currency'] ) : wpinv_get_currency(),
            'gateway'              => isset( $data['gateway'] ) ? wpinv_clean( $data['gateway'] ) : null,
            'transaction_id'       => isset( $data['transaction_id'] ) ? wpinv_clean( $data['transaction_id'] ) : null,
            'discount_code'        => isset( $data['discount_code'] ) ? wpinv_clean( $data['discount_code'] ) : null,
            'payment_form'         => isset( $data['payment_form'] ) ? intval( $data['payment_form'] ) : null,
            'submission_id'        => isset( $data['submission_id'] ) ? wpinv_clean( $data['submission_id'] ) : null,
            'subscription_id'      => isset( $data['subscription_id'] ) ? wpinv_clean( $data['subscription_id'] ) : null,
            'is_viewed'            => isset( $data['is_viewed'] ) ? wpinv_clean( $data['is_viewed'] ) : null,
            'fees'                 => isset( $data['fees'] ) ? wpinv_clean( $data['fees'] ) : null,
            'discounts'            => isset( $data['discounts'] ) ? wpinv_clean( $data['discounts'] ) : null,
            'taxes'                => isset( $data['taxes'] ) ? wpinv_clean( $data['taxes'] ) : null,
            

            // Billing details.
            'user_id'              => $data['user_id'],
            'first_name'           => isset( $data['first_name'] ) ? wpinv_clean( $data['first_name'] ) : $address['first_name'],
            'last_name'            => isset( $data['last_name'] ) ? wpinv_clean( $data['last_name'] ) : $address['last_name'],
            'address'              => isset( $data['address'] ) ? wpinv_clean( $data['address'] ) : $address['address'] ,
            'vat_number'           => isset( $data['vat_number'] ) ? wpinv_clean( $data['vat_number'] ) : $address['vat_number'],
            'company'              => isset( $data['company'] ) ? wpinv_clean( $data['company'] ) : $address['company'],
            'zip'                  => isset( $data['zip'] ) ? wpinv_clean( $data['zip'] ) : $address['zip'],
            'state'                => isset( $data['state'] ) ? wpinv_clean( $data['state'] ) : $address['state'],
            'city'                 => isset( $data['city'] ) ? wpinv_clean( $data['city'] ) : $address['city'],
            'country'              => isset( $data['country'] ) ? wpinv_clean( $data['country'] ) : $address['country'],
            'phone'                => isset( $data['phone'] ) ? wpinv_clean( $data['phone'] ) : $address['phone'],
            'address_confirmed'    => ! empty( $data['address_confirmed'] ),

        )

    );

    // Backwards compatibililty.
    if ( ! empty( $data['cart_details'] ) && is_array( $data['cart_details'] ) ) {
        $data['items'] = array();

        foreach( $data['cart_details'] as $_item ) {

            // Ensure that we have an item id.
            if ( empty(  $_item['id']  ) ) {
                continue;
            }

            // Retrieve the item.
            $item = new GetPaid_Form_Item(  $_item['id']  );

            // Ensure that it is purchasable.
            if ( ! $item->can_purchase() ) {
                continue;
            }

            // Set quantity.
            if ( ! empty( $_item['quantity'] ) && is_numeric( $_item['quantity'] ) ) {
                $item->set_quantity( $_item['quantity'] );
            }

            // Set price.
            if ( isset( $_item['item_price'] ) ) {
                $item->set_price( $_item['item_price'] );
            }

            if ( isset( $_item['custom_price'] ) ) {
                $item->set_price( $_item['custom_price'] );
            }

            // Set name.
            if ( ! empty( $_item['name'] ) ) {
                $item->set_name( $_item['name'] );
            }

            // Set description.
            if ( isset( $_item['description'] ) ) {
                $item->set_custom_description( $_item['description'] );
            }

            // Set meta.
            if ( isset( $_item['meta'] ) && is_array( $_item['meta'] ) ) {

                $item->set_item_meta( $_item['meta'] );

                if ( isset( $_item['meta']['description'] ) ) {
                    $item->set_custom_description( $_item['meta']['description'] );
                }

            }

            $data['items'][] = $item;

        }
    }

    // Add invoice items.
    if ( ! empty( $data['items'] ) && is_array( $data['items'] ) ) {

        $invoice->set_items( array() );

        foreach ( $data['items'] as $item ) {

            if ( is_object( $item ) && is_a( $item, 'GetPaid_Form_Item' ) && $item->can_purchase() ) {
                $invoice->add_item( $item );
            }

        }

    }

    // Save the invoice.
    $invoice->save();

    if ( ! $invoice->get_id() ) {
        return $wp_error ? new WP_Error( 'wpinv_insert_invoice_error', __( 'An error occured when saving your invoice.', 'invoicing' ) ) : 0;
    }

    // Add private note.
    if ( ! empty( $data['private_note'] ) ) {
        $invoice->add_note( $data['private_note'] );
    }

    // User notes.
    if ( !empty( $data['user_note'] ) ) {
        $invoice->add_note( $data['user_note'], true );
    }

    // Created via.
    if ( isset( $data['created_via'] ) ) {
        update_post_meta( $invoice->get_id(), 'wpinv_created_via', $data['created_via'] );
    }

    // Backwards compatiblity.
    if ( $invoice->is_quote() ) {

        if ( isset( $data['valid_until'] ) ) {
            update_post_meta( $invoice->get_id(), 'wpinv_quote_valid_until', $data['valid_until'] );
        }
        return $invoice;

    }

}

/**
 * Retrieves an invoice.
 * 
 * @param int|string|object|WPInv_Invoice|WPInv_Legacy_Invoice|WP_Post $invoice Invoice id, key, transaction id, number or object.
 * @param $bool $deprecated
 * @return WPInv_Invoice|null
 */
function wpinv_get_invoice( $invoice = 0, $deprecated = false ) {

    // If we are retrieving the invoice from the cart...
    if ( $deprecated && empty( $invoice ) ) {
        $invoice = (int) getpaid_get_current_invoice_id();
    }

    // Retrieve the invoice.
    $invoice = new WPInv_Invoice( $invoice );

    // Check if it exists.
    if ( $invoice->get_id() != 0 ) {
        return $invoice;
    }

    return null;
}

/**
 * Retrieves several invoices.
 * 
 * @param array $args Args to search for.
 * @return WPInv_Invoice[]|int[]|object
 */
function wpinv_get_invoices( $args ) {

    // Prepare args.
    $args = wp_parse_args(
        $args,
        array(
            'status'   => array_keys( wpinv_get_invoice_statuses() ),
            'type'     => 'wpi_invoice',
            'limit'    => get_option( 'posts_per_page' ),
            'return'   => 'objects',
        )
    );

    // Map params to wp_query params.
    $map_legacy = array(
        'numberposts'    => 'limit',
        'post_type'      => 'type',
        'post_status'    => 'status',
        'post_parent'    => 'parent',
        'author'         => 'user',
        'posts_per_page' => 'limit',
        'paged'          => 'page',
        'post__not_in'   => 'exclude',
        'post__in'       => 'include',
    );

    foreach ( $map_legacy as $to => $from ) {
        if ( isset( $args[ $from ] ) ) {
            $args[ $to ] = $args[ $from ];
            unset( $args[ $from ] );
        }
    }

    // Backwards compatibility.
    if ( ! empty( $args['email'] ) && empty( $args['user'] ) ) {
        $args['user'] = $args['email'];
        unset( $args['email'] );
    }

    // Handle cases where the user is set as an email.
    if ( ! empty( $args['author'] ) && is_email( $args['author'] ) ) {
        $user = get_user_by( 'email', $args['user'] );

        if ( $user ) {
            $args['author'] = $user->user_email;
        }

    }

    // We only want invoice ids.
    $args['fields'] = 'ids';

    // Show all posts.
    $paginate = true;
    if ( isset( $args['paginate'] ) ) {
        
        $paginate = $args['paginate'];
        $args['no_found_rows'] = empty( $args['paginate'] );
        unset( $args['paginate'] );

    }

    // Whether to return objects or fields.
    $return = $args['return'];
    unset( $args['return'] );

    // Get invoices.
    $invoices = new WP_Query( apply_filters( 'wpinv_get_invoices_args', $args ) );

    // Prepare the results.
    if ( 'objects' === $return ) {
        $results = array_map( 'wpinv_get_invoice', $invoices->posts );
    } elseif ( 'self' === $return ) {
        return $invoices;
    } else {
        $results = $invoices->posts;
    }

    if ( $paginate ) {
        return (object) array(
            'invoices'      => $results,
            'total'         => $invoices->found_posts,
            'max_num_pages' => $invoices->max_num_pages,
        );
    } else {
        return $results;
    }

}

/**
 * Retrieves an invoice's id from a transaction id.
 * 
 * @param string $transaction_id The transaction id to check.
 * @return int Invoice id on success or 0 on failure
 */
function wpinv_get_id_by_transaction_id( $transaction_id ) {
    return WPInv_Invoice::get_invoice_id_by_field( $transaction_id, 'transaction_id' );
}

/**
 * Retrieves an invoice's id from the invoice number.
 * 
 * @param string $invoice_number The invoice number to check.
 * @return int Invoice id on success or 0 on failure
 */
function wpinv_get_id_by_invoice_number( $invoice_number ) {
    return WPInv_Invoice::get_invoice_id_by_field( $invoice_number, 'number' );
}

/**
 * Retrieves an invoice's id from the invoice key.
 * 
 * @param string $invoice_key The invoice key to check.
 * @return int Invoice id on success or 0 on failure
 */
function wpinv_get_invoice_id_by_key( $invoice_key ) {
    return WPInv_Invoice::get_invoice_id_by_field( $invoice_key, 'key' );
}

/**
 * Retrieves an invoice's notes.
 * 
 * @param int|string|object|WPInv_Invoice|WPInv_Legacy_Invoice|WP_Post $invoice Invoice id, key, transaction id, number or object.
 * @param string $type Optionally filter by type i.e customer|system
 * @return WPInv_Invoice|null
 */
function wpinv_get_invoice_notes( $invoice = 0, $type = '' ) {
    global $invoicing;

    // Prepare the invoice.
    $invoice = new WPInv_Invoice( $invoice );
    if ( ! $invoice->exists() ) {
        return NULL;
    }

    // Fetch notes.
    $notes = $invoicing->notes->get_invoice_notes( $invoice->get_id(), $type );

    // Filter the notes.
    return apply_filters( 'wpinv_invoice_notes', $notes, $invoice->get_id(), $type );
}

/**
 * Returns an array of columns to display on the invoices page.
 */
function wpinv_get_user_invoices_columns() {
    $columns = array(

            'invoice-number'  => array(
                'title' => __( 'Invoice', 'invoicing' ),
                'class' => 'text-left'
            ),

            'created-date'    => array(
                'title' => __( 'Created Date', 'invoicing' ),
                'class' => 'text-left'
            ),

            'payment-date'    => array(
                'title' => __( 'Payment Date', 'invoicing' ),
                'class' => 'text-left'
            ),

            'invoice-status'  => array(
                'title' => __( 'Status', 'invoicing' ),
                'class' => 'text-center'
            ),

            'invoice-total'   => array(
                'title' => __( 'Total', 'invoicing' ),
                'class' => 'text-right'
            ),

            'invoice-actions' => array(
                'title' => '&nbsp;',
                'class' => 'text-center'
            ),

        );

    return apply_filters( 'wpinv_user_invoices_columns', $columns );
}

/**
 * Displays the invoice receipt.
 */
function wpinv_payment_receipt() {

    // Find the invoice.
    $invoice_id = getpaid_get_current_invoice_id();
    $invoice = new WPInv_Invoice( $invoice_id );

    // Abort if non was found.
    if ( empty( $invoice_id ) || $invoice->is_draft() ) {

        return aui()->alert(
            array(
                'type'    => 'warning',
                'content' => __( 'We could not find your invoice', 'invoicing' ),
            )
        );

    }

    // Can the user view this invoice?
    if ( ! wpinv_can_view_receipt( $invoice_id ) ) {

        return aui()->alert(
            array(
                'type'    => 'warning',
                'content' => __( 'You are not allowed to view this receipt', 'invoicing' ),
            )
        );

    }

    // Load the template.
    return wpinv_get_template_html( 'invoice-receipt.php', compact( 'invoice' ) );

}

/**
 * Displays the invoice history.
 */
function getpaid_invoice_history( $user_id = 0 ) {

    // Ensure that we have a user id.
    if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
        $user_id = get_current_user_id();
    }

    // View user id.
    if ( empty( $user_id ) ) {

        return aui()->alert(
            array(
                'type'    => 'warning',
                'content' => __( 'You must be logged in to view your invoice history.', 'invoicing' ),
            )
        );

    }

    // Fetch invoices.
    $invoices = wpinv_get_invoices(

        array(
            'page'     => ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1,
            'user'     => $user_id,
            'paginate' => true,
        )

    );

    if ( empty( $invoices->total ) ) {

        return aui()->alert(
            array(
                'type'    => 'info',
                'content' => __( 'No invoices found.', 'invoicing' ),
            )
        );

    }

    // Load the template.
    return wpinv_get_template_html( 'invoice-history.php', compact( 'invoices' ) );

}

function wpinv_pay_for_invoice() {
    global $wpinv_euvat;
    
    if ( isset( $_GET['invoice_key'] ) ) {
        $checkout_uri   = wpinv_get_checkout_uri();
        $invoice_key    = sanitize_text_field( $_GET['invoice_key'] );
        
        if ( empty( $invoice_key ) ) {
            wpinv_set_error( 'invalid_invoice', __( 'Invoice not found', 'invoicing' ) );
            wp_redirect( $checkout_uri );
            exit();
        }
        
        do_action( 'wpinv_check_pay_for_invoice', $invoice_key );

        $invoice_id    = wpinv_get_invoice_id_by_key( $invoice_key );
        $user_can_view = wpinv_can_view_receipt( $invoice_key );
        if ( $user_can_view && isset( $_GET['invoice-id'] ) ) {
            $invoice_id     = (int)$_GET['invoice-id'];
            $user_can_view  = $invoice_key == wpinv_get_payment_key( (int)$_GET['invoice-id'] ) ? true : false;
        }
        
        if ( $invoice_id && $user_can_view && ( $invoice = wpinv_get_invoice( $invoice_id ) ) ) {
            if ( $invoice->needs_payment() ) {
                $data                   = array();
                $data['invoice_id']     = $invoice_id;
                $data['cart_discounts'] = $invoice->get_discounts( true );
                
                wpinv_set_checkout_session( $data );
                
                if ( wpinv_get_option( 'vat_ip_country_default' ) ) {
                    $_POST['country']   = $wpinv_euvat->get_country_by_ip();
                    $_POST['state']     = $_POST['country'] == $invoice->country ? $invoice->state : '';
                    
                    wpinv_recalculate_tax( true );
                }
                
            } else {
                $checkout_uri = $invoice->get_view_url();
            }
        } else {
            wpinv_set_error( 'invalid_invoice', __( 'You are not allowed to view this invoice', 'invoicing' ) );
            
            $checkout_uri = is_user_logged_in() ? wpinv_get_history_page_uri() : wp_login_url( get_permalink() );
        }
        
        if(wp_redirect( $checkout_uri )){
            exit;
        };
        wpinv_die();
    }
}
add_action( 'wpinv_pay_for_invoice', 'wpinv_pay_for_invoice' );

function wpinv_invoice_status_label( $status, $status_display = '' ) {
    if ( empty( $status_display ) ) {
        $status_display = wpinv_status_nicename( $status );
    }
    
    switch ( $status ) {
        case 'publish' :
        case 'wpi-renewal' :
            $class = 'label-success';
        break;
        case 'wpi-pending' :
            $class = 'label-primary';
        break;
        case 'wpi-processing' :
            $class = 'label-warning';
        break;
        case 'wpi-onhold' :
            $class = 'label-info';
        break;
        case 'wpi-cancelled' :
        case 'wpi-failed' :
            $class = 'label-danger';
        break;
        default:
            $class = 'label-default';
        break;
    }

    $label = '<span class="label label-inv-' . $status . ' ' . $class . '">' . $status_display . '</span>';

    return apply_filters( 'wpinv_invoice_status_label', $label, $status, $status_display );
}

function wpinv_format_invoice_number( $number, $type = '' ) {
    $check = apply_filters( 'wpinv_pre_format_invoice_number', null, $number, $type );
    if ( null !== $check ) {
        return $check;
    }

    if ( !empty( $number ) && !is_numeric( $number ) ) {
        return $number;
    }

    $padd  = wpinv_get_option( 'invoice_number_padd' );
    $prefix  = wpinv_get_option( 'invoice_number_prefix' );
    $postfix = wpinv_get_option( 'invoice_number_postfix' );
    
    $padd = absint( $padd );
    $formatted_number = absint( $number );
    
    if ( $padd > 0 ) {
        $formatted_number = zeroise( $formatted_number, $padd );
    }    

    $formatted_number = $prefix . $formatted_number . $postfix;

    return apply_filters( 'wpinv_format_invoice_number', $formatted_number, $number, $prefix, $postfix, $padd );
}

function wpinv_get_next_invoice_number( $type = '' ) {
    $check = apply_filters( 'wpinv_get_pre_next_invoice_number', null, $type );
    if ( null !== $check ) {
        return $check;
    }
    
    if ( !wpinv_sequential_number_active() ) {
        return false;
    }

    $number = $last_number = get_option( 'wpinv_last_invoice_number', 0 );
    $start  = wpinv_get_option( 'invoice_sequence_start', 1 );
    if ( !absint( $start ) > 0 ) {
        $start = 1;
    }
    $increment_number = true;
    $save_number = false;

    if ( !empty( $number ) && !is_numeric( $number ) && $number == wpinv_format_invoice_number( $number ) ) {
        $number = wpinv_clean_invoice_number( $number );
    }

    if ( empty( $number ) ) {
        if ( !( $last_number === 0 || $last_number === '0' ) ) {
            $last_invoice = wpinv_get_invoices( array( 'limit' => 1, 'order' => 'DESC', 'orderby' => 'ID', 'return' => 'posts', 'fields' => 'ids', 'status' => array_keys( wpinv_get_invoice_statuses( true, true ) ) ) );

            if ( !empty( $last_invoice[0] ) && $invoice_number = wpinv_get_invoice_number( $last_invoice[0] ) ) {
                if ( is_numeric( $invoice_number ) ) {
                    $number = $invoice_number;
                } else {
                    $number = wpinv_clean_invoice_number( $invoice_number );
                }
            }

            if ( empty( $number ) ) {
                $increment_number = false;
                $number = $start;
                $save_number = ( $number - 1 );
            } else {
                $save_number = $number;
            }
        }
    }

    if ( $start > $number ) {
        $increment_number = false;
        $number = $start;
        $save_number = ( $number - 1 );
    }

    if ( $save_number !== false ) {
        update_option( 'wpinv_last_invoice_number', $save_number );
    }
    
    $increment_number = apply_filters( 'wpinv_increment_payment_number', $increment_number, $number );

    if ( $increment_number ) {
        $number++;
    }

    return apply_filters( 'wpinv_get_next_invoice_number', $number );
}

function wpinv_clean_invoice_number( $number, $type = '' ) {
    $check = apply_filters( 'wpinv_pre_clean_invoice_number', null, $number, $type );
    if ( null !== $check ) {
        return $check;
    }
    
    $prefix  = wpinv_get_option( 'invoice_number_prefix' );
    $postfix = wpinv_get_option( 'invoice_number_postfix' );

    $number = preg_replace( '/' . $prefix . '/', '', $number, 1 );

    $length      = strlen( $number );
    $postfix_pos = strrpos( $number, $postfix );
    
    if ( false !== $postfix_pos ) {
        $number      = substr_replace( $number, '', $postfix_pos, $length );
    }

    $number = intval( $number );

    return apply_filters( 'wpinv_clean_invoice_number', $number, $prefix, $postfix );
}

function wpinv_update_invoice_number( $post_ID, $save_sequential = false, $type = '' ) {
    global $wpdb;

    $check = apply_filters( 'wpinv_pre_update_invoice_number', null, $post_ID, $save_sequential, $type );
    if ( null !== $check ) {
        return $check;
    }

    if ( wpinv_sequential_number_active() ) {
        $number = wpinv_get_next_invoice_number();

        if ( $save_sequential ) {
            update_option( 'wpinv_last_invoice_number', $number );
        }
    } else {
        $number = $post_ID;
    }

    $number = wpinv_format_invoice_number( $number );

    update_post_meta( $post_ID, '_wpinv_number', $number );

    $wpdb->update( $wpdb->posts, array( 'post_title' => $number ), array( 'ID' => $post_ID ) );

    clean_post_cache( $post_ID );

    return $number;
}

function wpinv_post_name_prefix( $post_type = 'wpi_invoice' ) {
    return apply_filters( 'wpinv_post_name_prefix', 'inv-', $post_type );
}

function wpinv_generate_post_name( $post_ID ) {
    $prefix = wpinv_post_name_prefix( get_post_type( $post_ID ) );
    $post_name = sanitize_title( $prefix . $post_ID );

    return apply_filters( 'wpinv_generate_post_name', $post_name, $post_ID, $prefix );
}

/**
 * Checks if an invoice was viewed by the customer.
 * 
 * @param int|string|object|WPInv_Invoice|WPInv_Legacy_Invoice|WP_Post $invoice Invoice id, key, transaction id, number or object.
 */
function wpinv_is_invoice_viewed( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );
    return (bool) $invoice->get_is_viewed();
}

/**
 * Marks an invoice as viewed.
 * 
 * @param int|string|object|WPInv_Invoice|WPInv_Legacy_Invoice|WP_Post $invoice Invoice id, key, transaction id, number or object.
 */
function getpaid_maybe_mark_invoice_as_viewed( $invoice ) {
    $invoice = new WPInv_Invoice( $invoice );

    if ( get_current_user_id() == $invoice->get_user_id() && ! $invoice->get_is_viewed() ) {
        $invoice->set_is_viewed( true );
        $invoice->save();
    }

}
add_action( 'wpinv_invoice_print_before_display', 'getpaid_maybe_mark_invoice_as_viewed' );
add_action( 'wpinv_before_receipt', 'getpaid_maybe_mark_invoice_as_viewed' );

/**
 * Fetch a subscription given an invoice.
 *
 * @return WPInv_Subscription|bool
 */
function wpinv_get_subscription( $invoice ) {

    // Abort if we do not have an invoice.
    if ( empty( $invoice ) ) {
        return false;
    }

    // Retrieve the invoice.
    $invoice = new WPInv_Invoice( $invoice );

    // Ensure it is a recurring invoice.
    if ( ! $invoice->is_recurring() ) {
        return false;
    }

    // Fetch the subscription handler.
    $subs_db    = new WPInv_Subscriptions_DB();

    // Fetch the parent in case it is a renewal.
    if ( $invoice->is_renewal() ) {
        $subs = $subs_db->get_subscriptions( array( 'parent_payment_id' => $invoice->get_parent_id(), 'number' => 1 ) );
    } else {
        $subs = $subs_db->get_subscriptions( array( 'parent_payment_id' => $invoice->get_id(), 'number' => 1 ) );
    }

    // Return the subscription if it exists.
    if ( ! empty( $subs ) ) {
        return reset( $subs );
    }

    return false;
}

/**
 * Processes an invoice refund.
 * 
 * @param int $invoice_id
 * @param WPInv_Invoice $invoice
 * @param array $status_transition
 * @todo: descrease customer/store earnings
 */
function getpaid_maybe_process_refund( $invoice_id, $invoice, $status_transition ) {

    if ( empty( $status_transition['from'] ) || ! in_array( $status_transition['from'], array( 'publish', 'wpi-processing', 'wpi-renewal' ) ) ) {
        return;
    }

    $discount_code = $invoice->get_discount_code();
    if ( ! empty( $discount_code ) ) {
        $discount = wpinv_get_discount_obj( $discount_code );

        if ( $discount->exists() ) {
            $discount->increase_usage( -1 );
        }

    }

    do_action( 'wpinv_pre_refund_invoice', $invoice, $invoice_id );
    do_action( 'wpinv_refund_invoice', $invoice, $invoice_id );
    do_action( 'wpinv_post_refund_invoice', $invoice, $invoice_id );
}
add_action( 'getpaid_invoice_status_wpi-refunded', 'getpaid_maybe_process_refund', 10, 3 );


/**
 * Processes invoice payments.
 *
 * @param int $invoice_id
 */
function getpaid_process_invoice_payment( $invoice_id ) {

    // Fetch the invoice.
    $invoice = new WPInv_Invoice( $invoice_id );

    // We only want to do this once.
    if ( 1 ==  get_post_meta( $invoice_id, 'wpinv_processed_payment', true ) ) {
        return;
    }

    update_post_meta( $invoice_id, 'wpinv_processed_payment', 1 );

    // Fires when processing a payment.
    do_action( 'getpaid_process_payment', $invoice );

    // Fire an action for each invoice item.
    foreach( $invoice->get_items() as $item ) {
        do_action( 'getpaid_process_item_payment', $item, $invoice );
    }

    // Increase discount usage.
    $discount_code = $invoice->get_discount_code();
    if ( ! empty( $discount_code ) ) {
        $discount = wpinv_get_discount_obj( $discount_code );

        if ( $discount->exists() ) {
            $discount->increase_usage();
        }

    }

    // Record reverse vat.
    if ( 'invoice' == $invoice->get_type() && wpinv_use_taxes() && ! $invoice->get_disable_taxes() ) {

        if ( WPInv_EUVat::same_country_rule() == 'no' && wpinv_is_base_country( $invoice->get_country() ) ) {
            $invoice->add_note( __( 'VAT was reverse charged', 'invoicing' ), false, false, true );
        }

    }

}
add_action( 'getpaid_invoice_payment_status_changed', 'getpaid_process_invoice_payment' );

/**
 * Returns an array of invoice item columns
 * 
 * @param int|WPInv_Invoice $invoice
 * @return array
 */
function getpaid_invoice_item_columns( $invoice ) {

    // Prepare the invoice.
    $invoice = new WPInv_Invoice( $invoice );

    // Abort if there is no invoice.
    if ( 0 == $invoice->get_id() ) {
        return array();
    }

    // Line item columns.
    $columns = apply_filters(
        'getpaid_invoice_item_columns',
        array(
            'name'     => __( 'Item', 'invoicing' ),
            'price'    => __( 'Price', 'invoicing' ),
            'quantity' => __( 'Quantity', 'invoicing' ),
            'subtotal' => __( 'Subtotal', 'invoicing' ),
        ),
        $invoice
    );

    // Quantities.
    if ( isset( $columns[ 'quantity' ] ) ) {

        if ( 'hours' == $invoice->get_template() ) {
            $columns[ 'quantity' ] = __( 'Hours', 'invoicing' );
        }

        if ( ! wpinv_item_quantities_enabled() || 'amount' == $invoice->get_template() ) {
            unset( $columns[ 'quantity' ] );
        }

    }


    // Price.
    if ( isset( $columns[ 'price' ] ) ) {

        if ( 'amount' == $invoice->get_template() ) {
            $columns[ 'price' ] = __( 'Amount', 'invoicing' );
        }

        if ( 'hours' == $invoice->get_template() ) {
            $columns[ 'price' ] = __( 'Rate', 'invoicing' );
        }

    }


    // Sub total.
    if ( isset( $columns[ 'subtotal' ] ) ) {

        if ( 'amount' == $invoice->get_template() ) {
            unset( $columns[ 'subtotal' ] );
        }

    }

    return $columns;
}

/**
 * Returns an array of invoice totals rows
 * 
 * @param int|WPInv_Invoice $invoice
 * @return array
 */
function getpaid_invoice_totals_rows( $invoice ) {

    // Prepare the invoice.
    $invoice = new WPInv_Invoice( $invoice );

    // Abort if there is no invoice.
    if ( 0 == $invoice->get_id() ) {
        return array();
    }

    $totals = apply_filters(
        'getpaid_invoice_totals_rows',
        array(
            'subtotal' => __( 'Subtotal', 'invoicing' ),
            'tax'      => __( 'Tax', 'invoicing' ),
            'discount' => __( 'Discount', 'invoicing' ),
            'total'    => __( 'Total', 'invoicing' ),
        ),
        $invoice
    );

    if ( ( $invoice->get_disable_taxes() || ! wpinv_use_taxes() ) && isset( $totals['tax'] ) ) {
        unset( $totals['tax'] );
    }

    return $totals;
}

/**
 * This function is called whenever an invoice is created.
 * 
 * @param int $invoice_id
 * @param WPInv_Invoice $invoice
 */
function getpaid_new_invoice( $invoice_id, $invoice ) {

    // Add an invoice created note.
    $invoice->add_note(
        wp_sprintf(
            __( 'Invoice created with the status "%s".', 'invoicing' ),
            wpinv_status_nicename( $invoice->get_status() )
        )
    );

}
add_action( 'getpaid_new_invoice', 'getpaid_new_invoice', 10, 2 );

/**
 * This function updates invoice caches.
 * 
 * @param int $invoice_id
 * @param WPInv_Invoice $invoice
 */
function getpaid_update_invoice_caches( $invoice_id, $invoice ) {

    // Cache invoice number.
    wp_cache_set( $invoice->get_number(), $invoice_id, "getpaid_invoice_numbers_to_invoice_ids" );

    // Cache invoice key.
    wp_cache_set( $invoice->get_key(), $invoice_id, "getpaid_invoice_keys_to_invoice_ids" );

    // (Maybe) cache transaction id.
    $transaction_id = $invoice->get_transaction_id();

    if ( ! empty( $transaction_id ) ) {
        wp_cache_set( $transaction_id, $invoice_id, "getpaid_invoice_transaction_ids_to_invoice_ids" );
    }

}
add_action( 'getpaid_new_invoice', 'getpaid_update_invoice_caches', 5, 2 );
add_action( 'getpaid_update_invoice', 'getpaid_update_invoice_caches', 5, 2 );
