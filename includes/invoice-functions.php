<?php
/**
 * Contains invoice functions.
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

    // Ensure the quotes addon is installed.
    if ( ! defined( 'WPINV_QUOTES_VERSION' ) ) {
        unset( $post_types['wpi_quote'] );
    }

    return apply_filters( 'getpaid_invoice_post_types', $post_types );
}

/**
 * Checks if this is an invocing post type.
 *
 *
 * @param string $post_type The post type to check for.
 */
function getpaid_is_invoice_post_type( $post_type ) {
    return is_scalar( $post_type ) && ! empty( $post_type ) && array_key_exists( $post_type, getpaid_get_invoice_post_types() );
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
    $invoice->recalculate_total();
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

    }

    return $invoice;
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
    if ( ! is_a( $invoice, 'WPInv_Invoice' ) ) {
        $invoice = new WPInv_Invoice( $invoice );
    }

    // Check if it exists.
    if ( $invoice->exists() ) {
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
    }

    return $results;

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
 * @return array|null
 */
function wpinv_get_invoice_notes( $invoice = 0, $type = '' ) {

    // Prepare the invoice.
    $invoice = wpinv_get_invoice( $invoice );
    if ( empty( $invoice ) ) {
        return NULL;
    }

    // Fetch notes.
    $notes = getpaid_notes()->get_invoice_notes( $invoice->get_id(), $type );

    // Filter the notes.
    return apply_filters( 'wpinv_invoice_notes', $notes, $invoice->get_id(), $type );
}

/**
 * Returns an array of columns to display on the invoices page.
 *
 * @param string $post_type
 */
function wpinv_get_user_invoices_columns( $post_type = 'wpi_invoice' ) {

    $label   = getpaid_get_post_type_label( $post_type, false );
    $label   = empty( $label ) ? __( 'Invoice', 'invoicing' ) : sanitize_text_field( $label );
    $columns = array(

            'invoice-number'  => array(
                'title' => $label,
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

    return apply_filters( 'wpinv_user_invoices_columns', $columns, $post_type );
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
function getpaid_invoice_history( $user_id = 0, $post_type = 'wpi_invoice' ) {

    // Ensure that we have a user id.
    if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
        $user_id = get_current_user_id();
    }

    $label = getpaid_get_post_type_label( $post_type );
    $label = empty( $label ) ? __( 'Invoices', 'invoicing' ) : sanitize_text_field( $label );

    // View user id.
    if ( empty( $user_id ) ) {

        return aui()->alert(
            array(
                'type'    => 'warning',
                'content' => sprintf(
                    __( 'You must be logged in to view your %s.', 'invoicing' ),
                    strtolower( $label )
                )
            )
        );

    }

    // Fetch invoices.
    $invoices = wpinv_get_invoices(

        array(
            'page'      => ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1,
            'user'      => $user_id,
            'paginate'  => true,
            'type'      => $post_type,
            'status'    => array_keys( wpinv_get_invoice_statuses( false, false, $post_type ) ),
        )

    );

    if ( empty( $invoices->total ) ) {

        return aui()->alert(
            array(
                'type'    => 'info',
                'content' => sprintf(
                    __( 'No %s found.', 'invoicing' ),
                    strtolower( $label )
                )
            )
        );

    }

    // Load the template.
    return wpinv_get_template_html( 'invoice-history.php', compact( 'invoices', 'post_type' ) );

}

/**
 * Formats an invoice number given an invoice type.
 */
function wpinv_format_invoice_number( $number, $type = '' ) {

    // Allow other plugins to overide this.
    $check = apply_filters( 'wpinv_pre_format_invoice_number', null, $number, $type );
    if ( null !== $check ) {
        return $check;
    }

    // Ensure that we have a numeric number.
    if ( ! is_numeric( $number ) ) {
        return $number;
    }

    // Format the number.
    $padd             = absint( (int) wpinv_get_option( 'invoice_number_padd', 5 ) );
    $prefix           = sanitize_text_field( (string) wpinv_get_option( 'invoice_number_prefix', 'INV-' ) );
    $prefix           = sanitize_text_field( apply_filters( 'getpaid_invoice_type_prefix', $prefix, $type ) );
    $postfix          = sanitize_text_field( (string) wpinv_get_option( 'invoice_number_postfix' ) );
    $postfix          = sanitize_text_field( apply_filters( 'getpaid_invoice_type_postfix', $postfix, $type ) );
    $formatted_number = zeroise( absint( $number ), $padd );

    // Add the prefix and post fix.
    $formatted_number = $prefix . $formatted_number . $postfix;

    return apply_filters( 'wpinv_format_invoice_number', $formatted_number, $number, $prefix, $postfix, $padd );
}

/**
 * Returns the next invoice number.
 *
 * @param string $type.
 * @return int|null|bool
 */
function wpinv_get_next_invoice_number( $type = '' ) {

    // Allow plugins to overide this.
    $check = apply_filters( 'wpinv_get_pre_next_invoice_number', null, $type );
    if ( null !== $check ) {
        return $check;
    }

    // Ensure sequential invoice numbers is active.
    if ( ! wpinv_sequential_number_active() ) {
        return false;
    }

    // Retrieve the current number and the start number.
    $number = (int) get_option( 'wpinv_last_invoice_number', 0 );
    $start  = absint( (int) wpinv_get_option( 'invoice_sequence_start', 1 ) );

    // Ensure that we are starting at a positive integer.
    $start  = max( $start, 1 );

    // If this is the first invoice, use the start number.
    $number = max( $start, $number );

    // Format the invoice number.
    $formatted_number = wpinv_format_invoice_number( $number, $type );

    // Ensure that this number is unique.
    $invoice_id = WPInv_Invoice::get_invoice_id_by_field( $formatted_number, 'number' );

    // We found a match. Nice.
    if ( empty( $invoice_id ) ) {
        update_option( 'wpinv_last_invoice_number', $number );
        return apply_filters( 'wpinv_get_next_invoice_number', $number );
    }

    update_option( 'wpinv_last_invoice_number', $number + 1 );
    return wpinv_get_next_invoice_number( $type );

}

/**
 * The prefix used for invoice paths.
 */
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
 * Processes an invoice refund.
 *
 * @param WPInv_Invoice $invoice
 * @param array $status_transition
 * @todo: descrease customer/store earnings
 */
function getpaid_maybe_process_refund( $invoice, $status_transition ) {

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

    do_action( 'wpinv_pre_refund_invoice', $invoice, $invoice->get_id() );
    do_action( 'wpinv_refund_invoice', $invoice, $invoice->get_id() );
    do_action( 'wpinv_post_refund_invoice', $invoice, $invoice->get_id() );
}
add_action( 'getpaid_invoice_status_wpi-refunded', 'getpaid_maybe_process_refund', 10, 2 );


/**
 * Processes invoice payments.
 *
 * @param int $invoice_id
 */
function getpaid_process_invoice_payment( $invoice_id ) {

    // Fetch the invoice.
    $invoice = new WPInv_Invoice( $invoice_id );

    // We only want to do this once.
    if ( 1 ==  get_post_meta( $invoice->get_id(), 'wpinv_processed_payment', true ) ) {
        return;
    }

    update_post_meta( $invoice->get_id(), 'wpinv_processed_payment', 1 );

    // Fires when processing a payment.
    do_action( 'getpaid_process_payment', $invoice );

    // Fire an action for each invoice item.
    foreach( $invoice->get_items() as $item ) {
        do_action( 'getpaid_process_item_payment', $item, $invoice );
    }

    // Increase discount usage.
    $discount_code = $invoice->get_discount_code();
    if ( ! empty( $discount_code ) && ! $invoice->is_renewal() ) {
        $discount = wpinv_get_discount_obj( $discount_code );

        if ( $discount->exists() ) {
            $discount->increase_usage();
        }

    }

    // Record reverse vat.
    if ( 'invoice' == $invoice->get_type() && wpinv_use_taxes() && ! $invoice->get_disable_taxes() ) {

        $taxes = $invoice->get_total_tax();
        if ( empty( $taxes ) && GetPaid_Payment_Form_Submission_Taxes::is_eu_transaction( $invoice->get_country() ) ) {
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
            'tax_rate' => __( 'Tax Rate', 'invoicing' ),
            'quantity' => __( 'Quantity', 'invoicing' ),
            'subtotal' => __( 'Item Subtotal', 'invoicing' ),
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

    // Tax rates.
    if ( isset( $columns[ 'tax_rate' ] ) ) {

        if ( 0 == $invoice->get_tax() ) {
            unset( $columns[ 'tax_rate' ] );
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
            'fee'      => __( 'Fee', 'invoicing' ),
            'discount' => __( 'Discount', 'invoicing' ),
            'total'    => __( 'Total', 'invoicing' ),
        ),
        $invoice
    );

    if ( ( $invoice->get_disable_taxes() || ! wpinv_use_taxes() ) && isset( $totals['tax'] ) ) {
        unset( $totals['tax'] );
    }

    if ( 0 == $invoice->get_total_fees() && isset( $totals['fee'] ) ) {
        unset( $totals['fee'] );
    }

    if ( 0 == $invoice->get_total_discount() && isset( $totals['discount'] ) ) {
        unset( $totals['discount'] );
    }

    return $totals;
}

/**
 * This function is called whenever an invoice is created.
 *
 * @param WPInv_Invoice $invoice
 */
function getpaid_new_invoice( $invoice ) {

    if ( ! $invoice->get_status() ) {
        return;
    }

    // Add an invoice created note.
    $invoice->add_note(
        sprintf(
            __( '%s created with the status "%s".', 'invoicing' ),
            ucfirst( $invoice->get_invoice_quote_type() ),
            wpinv_status_nicename( $invoice->get_status(), $invoice  )
        )
    );

}
add_action( 'getpaid_new_invoice', 'getpaid_new_invoice' );

/**
 * This function updates invoice caches.
 *
 * @param WPInv_Invoice $invoice
 */
function getpaid_update_invoice_caches( $invoice ) {

    // Cache invoice number.
    wp_cache_set( $invoice->get_number(), $invoice->get_id(), "getpaid_invoice_numbers_to_invoice_ids" );

    // Cache invoice key.
    wp_cache_set( $invoice->get_key(), $invoice->get_id(), "getpaid_invoice_keys_to_invoice_ids" );

    // (Maybe) cache transaction id.
    $transaction_id = $invoice->get_transaction_id();

    if ( ! empty( $transaction_id ) ) {
        wp_cache_set( $transaction_id, $invoice->get_id(), "getpaid_invoice_transaction_ids_to_invoice_ids" );
    }

}
add_action( 'getpaid_new_invoice', 'getpaid_update_invoice_caches', 5 );
add_action( 'getpaid_update_invoice', 'getpaid_update_invoice_caches', 5 );

/**
 * Duplicates an invoice.
 *
 * Please note that this function does not save the duplicated invoice.
 *
 * @param  WPInv_Invoice $old_invoice The invoice to duplicate
 * @return WPInv_Invoice The new invoice.
 */
function getpaid_duplicate_invoice( $old_invoice ) {

    // Create the new invoice.
    $invoice = new WPInv_Invoice();
    $invoice->set_props(

        array(

            // Basic info.
            'template'             => $old_invoice->get_template(),
            'email_cc'             => $old_invoice->get_email_cc(),
            'post_type'            => $old_invoice->get_post_type(),
            'user_ip'              => $old_invoice->get_user_ip(),
            'parent_id'            => $old_invoice->get_parent_id(),
            'mode'                 => $old_invoice->get_mode(),
            'description'          => $old_invoice->get_description(),
            'created_via'          => $old_invoice->get_created_via(),

            // Payment info.
            'disable_taxes'        => $old_invoice->get_disable_taxes(),
            'currency'             => $old_invoice->get_currency(),
            'gateway'              => $old_invoice->get_gateway(),
            'discount_code'        => $old_invoice->get_discount_code(),
            'payment_form'         => $old_invoice->get_payment_form(),
            'submission_id'        => $old_invoice->get_submission_id(),
            'subscription_id'      => $old_invoice->get_subscription_id(),
            'fees'                 => $old_invoice->get_fees(),
            'discounts'            => $old_invoice->get_discounts(),
            'taxes'                => $old_invoice->get_taxes(),
            'items'                => $old_invoice->get_items(),

            // Billing details.
            'user_id'              => $old_invoice->get_user_id(),
            'first_name'           => $old_invoice->get_first_name(),
            'last_name'            => $old_invoice->get_last_name(),
            'address'              => $old_invoice->get_address(),
            'vat_number'           => $old_invoice->get_vat_number(),
            'company'              => $old_invoice->get_company(),
            'zip'                  => $old_invoice->get_zip(),
            'state'                => $old_invoice->get_state(),
            'city'                 => $old_invoice->get_city(),
            'country'              => $old_invoice->get_country(),
            'phone'                => $old_invoice->get_phone(),
            'address_confirmed'    => $old_invoice->get_address_confirmed(),

        )

    );

    // Recalculate totals.
    $invoice->recalculate_total();

    return $invoice;
}

/**
 * Retrieves invoice meta fields.
 *
 * @param WPInv_Invoice $invoice
 * @return array
 */
function getpaid_get_invoice_meta( $invoice ) {

    // Load the invoice meta.
    $meta = array(

        'number' => array(
            'label' => sprintf(
                __( '%s Number', 'invoicing' ),
                ucfirst( $invoice->get_invoice_quote_type() )
            ),
            'value' => sanitize_text_field( $invoice->get_number() ),
        ),

        'status' => array(
            'label' => sprintf(
                __( '%s Status', 'invoicing' ),
                ucfirst( $invoice->get_invoice_quote_type() )
            ),
            'value' => $invoice->get_status_label_html(),
        ),

        'date' => array(
            'label' => sprintf(
                __( '%s Date', 'invoicing' ),
                ucfirst( $invoice->get_invoice_quote_type() )
            ),
            'value' => getpaid_format_date( $invoice->get_created_date() ),
        ),

        'date_paid' => array(
            'label' => __( 'Paid On', 'invoicing' ),
            'value' => getpaid_format_date( $invoice->get_completed_date() ),
        ),

        'gateway'   => array(
            'label' => __( 'Payment Method', 'invoicing' ),
            'value' => sanitize_text_field( $invoice->get_gateway_title() ),
        ),

        'transaction_id' => array(
            'label' => __( 'Transaction ID', 'invoicing' ),
            'value' => sanitize_text_field( $invoice->get_transaction_id() ),
        ),

        'due_date'  => array(
            'label' => __( 'Due Date', 'invoicing' ),
            'value' => getpaid_format_date( $invoice->get_due_date() ),
        ),

        'vat_number' => array(
            'label' => __( 'VAT Number', 'invoicing' ),
            'value' => sanitize_text_field( $invoice->get_vat_number() ),
        ),

    );

    $additional_meta = get_post_meta( $invoice->get_id(), 'additional_meta_data', true );

    if ( ! empty( $additional_meta ) ) {

        foreach ( $additional_meta as $label => $value ) {
            $meta[ sanitize_key( $label ) ] = array(
                'label' => esc_html( $label ),
                'value' => esc_html( $value ),
            );
        }

    }
    // If it is not paid, remove the date of payment.
    if ( ! $invoice->is_paid() && ! $invoice->is_refunded() ) {
        unset( $meta[ 'date_paid' ] );
        unset( $meta[ 'transaction_id' ] );
    }

    if ( ! $invoice->is_paid() || 'none' == $invoice->get_gateway() ) {
        unset( $meta[ 'gateway' ] );
    }

    // Only display the due date if due dates are enabled.
    if ( ! $invoice->needs_payment() || ! wpinv_get_option( 'overdue_active' ) ) {
        unset( $meta[ 'due_date' ] );
    }

    // Only display the vat number if taxes are enabled.
    if ( ! wpinv_use_taxes() ) {
        unset( $meta[ 'vat_number' ] );
    }

    // Link to the parent invoice.
    if ( $invoice->get_parent_id() > 0 ) {

        $meta[ 'parent' ] = array(

            'label' => sprintf(
                __( 'Parent %s', 'invoicing' ),
                ucfirst( $invoice->get_invoice_quote_type() )
            ),

            'value' => wpinv_invoice_link( $invoice->get_parent_id() ),

        );

    }

    
    if ( $invoice->is_recurring() ) {

        $subscription = getpaid_get_invoice_subscriptions( $invoice );
        if ( ! empty ( $subscription ) && ! is_array( $subscription ) && $subscription->exists() ) {

            // Display the renewal date.
            if ( $subscription->is_active() && 'cancelled' != $subscription->get_status() ) {

                $meta[ 'renewal_date' ] = array(
                    'label' => __( 'Renews On', 'invoicing' ),
                    'value' => getpaid_format_date( $subscription->get_expiration() ),
                );

            }

            if ( $invoice->is_parent() ) {

                // Display the recurring amount.
                $meta[ 'recurring_total' ] = array(

                    'label' => __( 'Recurring Amount', 'invoicing' ),
                    'value' => wpinv_price( $subscription->get_recurring_amount(), $invoice->get_currency() ),

                );

            }

        }
    }

    // Add the invoice total to the meta.
    $meta[ 'invoice_total' ] = array(

        'label' => __( 'Total Amount', 'invoicing' ),
        'value' => wpinv_price( $invoice->get_total(), $invoice->get_currency() ),

    );

    // Provide a way for third party plugins to filter the meta.
    $meta = apply_filters( 'getpaid_invoice_meta_data', $meta, $invoice );

    return $meta;

}

/**
 * Returns an array of valid invoice status classes.
 *
 * @return array
 */
function getpaid_get_invoice_status_classes() {

	return apply_filters(
		'getpaid_get_invoice_status_classes',
		array(
            'wpi-quote-declined' => 'badge-danger',
            'wpi-failed'         => 'badge-danger',
			'wpi-processing'     => 'badge-info',
			'wpi-onhold'         => 'badge-warning',
			'wpi-quote-accepted' => 'badge-success',
			'publish'            => 'badge-success',
			'wpi-renewal'        => 'badge-primary',
            'wpi-cancelled'      => 'badge-secondary',
            'wpi-pending'        => 'badge-dark',
            'wpi-quote-pending'  => 'badge-dark',
            'wpi-refunded'       => 'badge-secondary',
		)
	);

}

/**
 * Returns an invoice's tax rate percentage.
 *
 * @param WPInv_Invoice $invoice
 * @param GetPaid_Form_Item $item
 * @return float
 */
function getpaid_get_invoice_tax_rate( $invoice, $item ) {

    $rates   = getpaid_get_item_tax_rates( $item, $invoice->get_country(), $invoice->get_state() );
	$rates   = getpaid_filter_item_tax_rates( $item, $rates );
    $rates   = wp_list_pluck( $rates, 'rate' );

    return array_sum( $rates );

}
