<?php
/**
 * Contains discount functions.
 *
 * @since 1.0.0
 * @package Invoicing
 */
 
defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of discount type.
 * 
 * @return array
 */
function wpinv_get_discount_types() {
    return apply_filters(
        'wpinv_discount_types',
        array(
            'percent'   => __( 'Percentage', 'invoicing' ),
            'flat'     => __( 'Flat Amount', 'invoicing' ),
        )
    );
}

/**
 * Returns the name of a discount type.
 * 
 * @return string
 */
function wpinv_get_discount_type_name( $type = '' ) {
    $types = wpinv_get_discount_types();
    return isset( $types[ $type ] ) ? $types[ $type ] : $type;
}

/**
 * Deletes a discount via the admin page.
 * 
 */
function wpinv_delete_discount( $data ) {

    $discount = new WPInv_Discount( absint( $data['discount'] ) );
    $discount->delete( true );

}
add_action( 'getpaid_authenticated_admin_action_delete_discount', 'wpinv_delete_discount' );

/**
 * Deactivates a discount via the admin page.
 */
function wpinv_activate_discount( $data ) {

    $discount = new WPInv_Discount( absint( $data['discount'] ) );
    $discount->set_status( 'publish' );
    $discount->save();

}
add_action( 'getpaid_authenticated_admin_action_activate_discount', 'wpinv_activate_discount' );

/**
 * Activates a discount via the admin page.
 */
function wpinv_deactivate_discount( $data ) {

    $discount = new WPInv_Discount( absint( $data['discount'] ) );
    $discount->set_status( 'pending' );
    $discount->save();

}
add_action( 'getpaid_authenticated_admin_action_deactivate_discount', 'wpinv_deactivate_discount' );

/**
 * Fetches a discount object.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @since 1.0.15
 * @return WPInv_Discount
 */
function wpinv_get_discount( $discount ) {
    return new WPInv_Discount( $discount );
}

/**
 * Fetches a discount object.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @since 1.0.15
 * @return WPInv_Discount
 */
function wpinv_get_discount_obj( $discount = 0 ) {
    return new WPInv_Discount( $discount );
}

/**
 * Fetch a discount from the db/cache using a given field.
 *
 * @param string $deprecated deprecated
 * @param string|int $value The field value
 * @return bool|WPInv_Discount
 */
function wpinv_get_discount_by( $deprecated = null, $value = '' ) {
    $discount = new WPInv_Discount( $value );

    if ( $discount->get_id() != 0 ) {
        return $discount;
    }

    return  false;
}

/**
 * Returns an array discount statuses.
 * 
 * @return array
 */
function wpinv_get_discount_statuses() {

    return array(
        'expired'  => __( 'Expired', 'invoicing' ),
        'publish'  => __( 'Active', 'invoicing' ),
        'inactive' => __( 'Inactive', 'invoicing' ),
    );

}

/**
 * Retrieves an invoice status label.
 */
function wpinv_discount_status( $status ) {
    $statuses = wpinv_get_discount_statuses();
    return isset( $statuses[ $status ] ) ? $statuses[ $status ] : __( 'Inactive', 'invoicing' );
}

/**
 * Checks if a discount is recurring.
 *
 * @param int|array|string|WPInv_Discount $discount discount data, object, ID or code.
 * @param int|array|string|WPInv_Discount $code discount data, object, ID or code.
 * @return bool
 */
function wpinv_discount_is_recurring( $discount = 0, $code = 0 ) {

    if( ! empty( $discount ) ) {
        $discount    = wpinv_get_discount_obj( $discount );
    } else {
        $discount    = wpinv_get_discount_obj( $code );
    }

    return $discount->get_is_recurring();
}
