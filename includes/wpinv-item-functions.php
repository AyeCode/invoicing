<?php
/**
 * Contains item functions.
 *
 * @since 1.0.0
 * @package Invoicing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Retrieves an item by it's ID.
 *
 * @param int the item ID to retrieve.
 * @return WPInv_Item|false
 */
function wpinv_get_item_by_id( $id ) {
    $item = wpinv_get_item( $id );
    return empty( $item ) || $id != $item->get_id() ? false : $item;
}

/**
 * Retrieves an item by it's ID, Name, Slug or custom id.
 *
 * @return WPInv_Item|false
 */
function wpinv_get_item_by( $field = '', $value = '', $type = '' ) {

    if ( 'id' == strtolower( $field ) ) {
        return wpinv_get_item_by_id( $field );
    }

    $id = WPInv_Item::get_item_id_by_field( $value, strtolower( $field ), $type );
    return empty( $id ) ? false : wpinv_get_item( $id );

}

/**
 * Retrieves an item by it's ID, name or custom_name.
 *
 * @param int|WPInv_Item the item to retrieve.
 * @return WPInv_Item|false
 */
function wpinv_get_item( $item = 0 ) {

    if ( empty( $item ) ) {
        return false;
    }

    $item = new WPInv_Item( $item );
    return $item->exists() ? $item : false;

}

function wpinv_get_all_items( $args = array() ) {

    $args = wp_parse_args( $args, array(
        'status'         => array( 'publish' ),
        'limit'          => get_option( 'posts_per_page' ),
        'page'           => 1,
        'exclude'        => array(),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'type'           => wpinv_item_types(),
        'meta_query'     => array(),
        'return'         => 'objects',
        'paginate'       => false,
    ) );

    $wp_query_args = array(
        'post_type'      => 'wpi_item',
        'post_status'    => $args['status'],
        'posts_per_page' => $args['limit'],
        'meta_query'     => $args['meta_query'],
        'fields'         => 'ids',
        'orderby'        => $args['orderby'],
        'order'          => $args['order'],
        'paged'          => absint( $args['page'] ),
    );

    if ( ! empty( $args['exclude'] ) ) {
        $wp_query_args['post__not_in'] = array_map( 'absint', $args['exclude'] );
    }

    if ( ! $args['paginate' ] ) {
        $wp_query_args['no_found_rows'] = true;
    }

    if ( ! empty( $args['search'] ) ) {
        $wp_query_args['s'] = $args['search'];
    }

    if ( ! empty( $args['type'] ) && $args['type'] !== wpinv_item_types() ) {
        $types = wpinv_parse_list( $args['type'] );
        $wp_query_args['meta_query'][] = array(
            'key'     => '_wpinv_type',
            'value'   => implode( ',', $types ),
            'compare' => 'IN',
        );
    }

    $wp_query_args = apply_filters('wpinv_get_items_args', $wp_query_args, $args);

    // Get results.
    $items = new WP_Query( $wp_query_args );

    if ( 'objects' === $args['return'] ) {
        $return = array_map( 'wpinv_get_item_by_id', $items->posts );
    } elseif ( 'self' === $args['return'] ) {
        return $items;
    } else {
        $return = $items->posts;
    }

    if ( $args['paginate' ] ) {
        return (object) array(
            'items'      => $return,
            'total'         => $items->found_posts,
            'max_num_pages' => $items->max_num_pages,
        );
    } else {
        return $return;
    }

}

function wpinv_is_free_item( $item_id = 0 ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );

    return $item->is_free();
}

/**
 * Checks whether an item is editable.
 *
 * @param WP_Post|WPInv_Item|Int $item The item to check for.
 */
function wpinv_item_is_editable( $item = 0 ) {

    // Fetch the item.
    $item = new WPInv_Item( $item );

    // Check if it is editable.
    return $item->is_editable();
}

function wpinv_get_item_price( $item_id = 0 ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );

    return $item->get_price();
}

/**
 * Checks if the provided item is recurring.
 *
 * @param WPInv_Item|int $item
 */
function wpinv_is_recurring_item( $item = 0 ) {
    $item = new WPInv_Item( $item );
    return $item->is_recurring();
}

function wpinv_item_price( $item_id = 0 ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $price = wpinv_get_item_price( $item_id );
    $price = wpinv_price( $price );

    return apply_filters( 'wpinv_item_price', $price, $item_id );
}

function wpinv_get_item_final_price( $item_id = 0, $amount_override = null ) {
    if ( is_null( $amount_override ) ) {
        $original_price = get_post_meta( $item_id, '_wpinv_price', true );
    } else {
        $original_price = $amount_override;
    }

    $price = $original_price;

    return apply_filters( 'wpinv_get_item_final_price', $price, $item_id );
}

function wpinv_item_custom_singular_name( $item_id ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );

    return $item->get_custom_singular_name();
}

function wpinv_get_item_types() {
    $item_types = array(
            'custom'    => __( 'Standard', 'invoicing' ),
            'fee'       => __( 'Fee', 'invoicing' ),
        );
    return apply_filters( 'wpinv_get_item_types', $item_types );
}

function wpinv_item_types() {
    $item_types = wpinv_get_item_types();

    return ( !empty( $item_types ) ? array_keys( $item_types ) : array() );
}

function wpinv_get_item_type( $item_id ) {
    if( empty( $item_id ) ) {
        return false;
    }

    $item = new WPInv_Item( $item_id );

    return $item->get_type();
}

function wpinv_item_type( $item_id ) {
    $item_types = wpinv_get_item_types();

    $item_type = wpinv_get_item_type( $item_id );

    if ( empty( $item_type ) ) {
        $item_type = '-';
    }

    $item_type = isset( $item_types[$item_type] ) ? $item_types[$item_type] : __( $item_type, 'invoicing' );

    return apply_filters( 'wpinv_item_type', $item_type, $item_id );
}

function wpinv_get_random_item( $post_ids = true ) {
    wpinv_get_random_items( 1, $post_ids );
}

function wpinv_get_random_items( $num = 3, $post_ids = true ) {
    if ( $post_ids ) {
        $args = array( 'post_type' => 'wpi_item', 'orderby' => 'rand', 'post_count' => $num, 'fields' => 'ids' );
    } else {
        $args = array( 'post_type' => 'wpi_item', 'orderby' => 'rand', 'post_count' => $num );
    }

    $args  = apply_filters( 'wpinv_get_random_items', $args );

    return get_posts( $args );
}

/**
 * Adds additional information suffixes to an item name.
 *
 * @param WPInv_Item|int $item
 * @param bool $html
 */
function wpinv_get_item_suffix( $item, $html = true ) {

    $item   = new WPInv_Item( $item );
    $suffix = $item->is_recurring() ? ' ' . __( '(r)', 'invoicing' ) : '';
    $suffix = $html ? $suffix : strip_tags( $suffix );

    return apply_filters( 'wpinv_get_item_suffix', $suffix, $item, $html );
}

/**
 * Deletes an invoicing item.
 *
 * @param WPInv_Item|int $item
 * @param bool $force_delete
 */
function wpinv_remove_item( $item = 0, $force_delete = false ) {
    $item = new WPInv_Item( $item );
    $item->delete( $force_delete );
}

/**
 * Create/Update an item.
 *
 * @param array $args an array of arguments to create the item.
 *
 *    Here are all the args (with defaults) that you can set/modify.
 *    array(
 *		  'ID'                   => 0,           - If specified, the item with that ID will be updated.
 *        'parent_id'            => 0,           - Int. Parent item ID.
 *		  'status'               => 'draft',     - String. Item status - either draft, pending or publish.
 *		  'date_created'         => null,        - String. strtotime() compatible string.
 *        'date_modified'        => null,        - String. strtotime() compatible string.
 *        'name'                 => '',          - String. Required. Item name.
 *        'description'          => '',          - String. Item description.
 *        'author'               => 1,           - int. Owner of the item.
 *        'price'                => 0,           - float. Item price.
 *        'vat_rule'             => 'digital',   - string. VAT rule.
 *        'vat_class'            => '_standard', - string. VAT class.
 *        'type'                 => 'custom',    - string. Item type.
 *        'custom_id'            => null,        - string. Custom item id.
 *        'custom_name'          => null,        - string. Custom item name.
 *        'custom_singular_name' => null,        - string. Custom item singular name.
 *        'is_editable'          => 1,           - int|bool. Whether or not the item is editable.
 *        'is_dynamic_pricing'   => 0,           - int|bool. Whether or not users can update the item price.
 *        'minimum_price'        => 0,           - float. If dynamic, set the minimum price that a user can set..
 *        'is_recurring'         => 0,           - int|bool. Whether or not this item is recurring.
 *        'recurring_period'     => 'D',         - string. If recurring, set the recurring period as either days (D), weeks (W), months (M) or years (Y).
 *        'recurring_interval'   => 1,           - int. The recurring interval.
 *        'recurring_limit'      => 0,           - int. The recurring limit. Enter 0 for unlimited.
 *        'is_free_trial'        => false,       - int|bool. Whether or not this item has a free trial.
 *        'trial_period'         => 'D',         - string. If it has a free trial, set the trial period as either days (D), weeks (W), months (M) or years (Y).
 *        'trial_interval'       => 1,           - int. The trial interval.
 *    );
 * @param bool $wp_error whether or not to return a WP_Error on failure.
 * @return bool|WP_Error|WPInv_Item
 */
function wpinv_create_item( $args = array(), $wp_error = false ) {

    // Prepare the item.
    if ( ! empty( $args['custom_id'] ) && empty( $args['ID'] ) ) {
        $type = empty( $args['type'] ) ? 'custom' : $args['type'];
        $item = wpinv_get_item_by( 'custom_id', $args['custom_id'], $type );

        if ( ! empty( $item ) ) {
            $args['ID'] = $item->get_id();
        }

    }

    // Do we have an item?
    if ( ! empty( $args['ID'] ) ) {
        $item = new WPInv_Item( $args['ID'] );
    } else {
        $item = new WPInv_Item();
    }

    // Do we have an error?
    if ( ! empty( $item->last_error ) ) {
        return $wp_error ? new WP_Error( 'invalid_item', $item->last_error ) : false;
    }

    // Update item props.
    $item->set_props( $args );

    // Save the item.
    $item->save();

    // Do we have an error?
    if ( ! empty( $item->last_error ) ) {
        return $wp_error ? new WP_Error( 'not_saved', $item->last_error ) : false;
    }

    // Was the item saved?
    if ( ! $item->get_id() ) {
        return $wp_error ? new WP_Error( 'not_saved', __( 'An error occured while saving the item', 'invoicing' ) ) : false;
    }

    return $item;

}

/**
 * Updates an item.
 *
 * @see wpinv_create_item()
 */
function wpinv_update_item( $args = array(), $wp_error = false ) {
    return wpinv_create_item( $args, $wp_error );
}

/**
 * Sanitizes a recurring period
 */
function getpaid_sanitize_recurring_period( $period, $full = false ) {

    $periods = array(
        'D' => 'day',
        'W' => 'week',
        'M' => 'month',
        'Y' => 'year',
    );

    if ( ! isset( $periods[ $period ] ) ) {
        $period = 'D';
    }

    return $full ? $periods[ $period ] : $period;

}

/**
 * Retrieves recurring price description.
 *
 * @param WPInv_Item|GetPaid_Form_Item $item
 */
function getpaid_item_recurring_price_help_text( $item, $currency = '', $_initial_price = false, $_recurring_price = false ) {

    // Abort if it is not recurring.
    if ( ! $item->is_recurring() ) {
        return '';
    }

    $initial_price   = false === $_initial_price ? wpinv_price( $item->get_initial_price(), $currency ) : $_initial_price;
    $recurring_price = false === $_recurring_price ? wpinv_price( $item->get_recurring_price(), $currency ) : $_recurring_price;
    $period          = getpaid_get_subscription_period_label( $item->get_recurring_period(), $item->get_recurring_interval(), '' );
    $initial_class   = 'getpaid-item-initial-price';
    $recurring_class = 'getpaid-item-recurring-price';
    $bill_times      = $item->get_recurring_limit();

    if ( ! empty( $bill_times ) ) {
		$bill_times = $item->get_recurring_interval() * $bill_times;
		$bill_times = getpaid_get_subscription_period_label( $item->get_recurring_period(), $bill_times );
	}

    if ( $item instanceof GetPaid_Form_Item && false === $_initial_price ) {
        $initial_price   = wpinv_price( $item->get_sub_total(), $currency );
        $recurring_price = wpinv_price( $item->get_recurring_sub_total(), $currency );
    }

    if ( wpinv_price( 0, $currency ) == $initial_price && wpinv_price( 0, $currency ) == $recurring_price ) {
        return __( 'Free forever', 'invoicing' );
    }

    // For free trial items.
    if ( $item->has_free_trial() ) {
        $trial_period = getpaid_get_subscription_period_label( $item->get_trial_period(), $item->get_trial_interval() );

        if ( wpinv_price( 0, $currency ) == $initial_price ) {

            if ( empty( $bill_times ) ) {

                return sprintf(

                    // translators: $1: is the trial period, $2: is the recurring price, $3: is the susbcription period
                    _x( 'Free for %1$s then %2$s / %3$s', 'Item subscription amount. (e.g.: Free for 1 month then $120 / year)', 'invoicing' ),
                    "<span class='getpaid-item-trial-period'>$trial_period</span>",
                    "<span class='$recurring_class'>$recurring_price</span>",
                    "<span class='getpaid-item-recurring-period'>$period</span>"

                );

            }

            return sprintf(

                // translators: $1: is the trial period, $2: is the recurring price, $3: is the susbcription period, $4: is the bill times
                _x( 'Free for %1$s then %2$s / %3$s for %4$s', 'Item subscription amount. (e.g.: Free for 1 month then $120 / year for 4 years)', 'invoicing' ),
                "<span class='getpaid-item-trial-period'>$trial_period</span>",
                "<span class='$recurring_class'>$recurring_price</span>",
                "<span class='getpaid-item-recurring-period'>$period</span>",
                "<span class='getpaid-item-recurring-bill-times'>$bill_times</span>"

            );

        }

        if ( empty( $bill_times ) ) {

            return sprintf(

                // translators: $1: is the initial price, $2: is the trial period, $3: is the recurring price, $4: is the susbcription period
                _x( '%1$s for %2$s then %3$s / %4$s', 'Item subscription amount. (e.g.: $7 for 1 month then $120 / year)', 'invoicing' ),
                "<span class='$initial_class'>$initial_price</span>",
                "<span class='getpaid-item-trial-period'>$trial_period</span>",
                "<span class='$recurring_class'>$recurring_price</span>",
                "<span class='getpaid-item-recurring-period'>$period</span>"

            );

        }

        return sprintf(

            // translators: $1: is the initial price, $2: is the trial period, $3: is the recurring price, $4: is the susbcription period, $4: is the susbcription bill times
            _x( '%1$s for %2$s then %3$s / %4$s for %5$s', 'Item subscription amount. (e.g.: $7 for 1 month then $120 / year for 5 years)', 'invoicing' ),
            "<span class='$initial_class'>$initial_price</span>",
            "<span class='getpaid-item-trial-period'>$trial_period</span>",
            "<span class='$recurring_class'>$recurring_price</span>",
            "<span class='getpaid-item-recurring-period'>$period</span>",
            "<span class='getpaid-item-recurring-bill-times'>$bill_times</span>"

        );

    }

    if ( $initial_price == $recurring_price ) {

        if ( empty( $bill_times ) ) {

            return sprintf(

                // translators: $1: is the recurring price, $2: is the susbcription period
                _x( '%1$s / %2$s', 'Item subscription amount. (e.g.: $120 / year)', 'invoicing' ),
                "<span class='$recurring_class'>$recurring_price</span>",
                "<span class='getpaid-item-recurring-period'>$period</span>"

            );

        }

        return sprintf(

            // translators: $1: is the recurring price, $2: is the susbcription period, $3: is the susbcription bill times
            _x( '%1$s / %2$s for %3$s', 'Item subscription amount. (e.g.: $120 / year for 5 years)', 'invoicing' ),
            "<span class='$recurring_class'>$recurring_price</span>",
            "<span class='getpaid-item-recurring-period'>$period</span>",
            "<span class='getpaid-item-recurring-bill-times'>$bill_times</span>"

        );

    }

    if ( $initial_price == wpinv_price( 0, $currency ) ) {

        if ( empty( $bill_times ) ) {

            return sprintf(

                // translators: $1: is the recurring period, $2: is the recurring price
                _x( 'Free for %1$s then %2$s / %1$s', 'Item subscription amount. (e.g.: Free for 3 months then $7 / 3 months)', 'invoicing' ),
                "<span class='getpaid-item-recurring-period'>$period</span>",
                "<span class='$recurring_class'>$recurring_price</span>"

            );

        }

        return sprintf(

            // translators: $1: is the recurring period, $2: is the recurring price, $3: is the bill times
            _x( 'Free for %1$s then %2$s / %1$s for %3$s', 'Item subscription amount. (e.g.: Free for 3 months then $7 / 3 months for 12 months)', 'invoicing' ),
            "<span class='getpaid-item-recurring-period'>$period</span>",
            "<span class='$recurring_class'>$recurring_price</span>",
            "<span class='getpaid-item-recurring-bill-times'>$bill_times</span>"

        );

    }

    if ( empty( $bill_times ) ) {

        return sprintf(

            // translators: $1: is the initial price, $2: is the recurring price, $3: is the susbcription period
            _x( 'Initial payment of %1$s then %2$s / %3$s', 'Item subscription amount. (e.g.: Initial payment of $7 then $120 / year)', 'invoicing' ),
            "<span class='$initial_class'>$initial_price</span>",
            "<span class='$recurring_class'>$recurring_price</span>",
            "<span class='getpaid-item-recurring-period'>$period</span>"

        );

    }

    return sprintf(

        // translators: $1: is the initial price, $2: is the recurring price, $3: is the susbcription period, $4: is the susbcription bill times
        _x( 'Initial payment of %1$s then %2$s / %3$s for %4$s', 'Item subscription amount. (e.g.: Initial payment of $7 then $120 / year for 4 years)', 'invoicing' ),
        "<span class='$initial_class'>$initial_price</span>",
        "<span class='$recurring_class'>$recurring_price</span>",
        "<span class='getpaid-item-recurring-period'>$period</span>",
        "<span class='getpaid-item-recurring-bill-times'>$bill_times</span>"

    );

}
