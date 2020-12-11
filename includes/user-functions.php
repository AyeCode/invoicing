<?php
/**
 * Contains all user related functions.
 *
 * @since 1.0.0
 * @package GetPaid
 */

defined( 'ABSPATH' ) || exit;

/**
 *  Generates a users select dropdown.
 *
 * @since 1.0.0
 * @return string|void Users dropdown markup.
 * @param array $args
 * @see wp_dropdown_users
 */
function wpinv_dropdown_users( $args = '' ) {

    if ( is_array( $args ) && ! empty( $args['show'] ) && 'display_name_with_email' == $args['show'] ) {
        $args['show'] = 'display_name_with_login';
    }

    return wp_dropdown_users( $args );
}

/**
 *  Returns the appropriate capability to check against
 *
 * @since 1.0.13
 * @return string capability to check against
 * @param string $capalibilty Optional. The alternative capability to check against.
 */
function wpinv_get_capability( $capalibilty = 'manage_invoicing' ) {

	if ( current_user_can( 'manage_options' ) ) {
		return 'manage_options';
	};

	return $capalibilty;
}

/**
 *  Checks if the current user can manager invoicing
 *
 * @since 1.0.13
 * @return bool
 */
function wpinv_current_user_can_manage_invoicing() {
    return current_user_can( wpinv_get_capability() );
}

/**
 *  Given an email address, it creates a new user.
 *
 * @since 1.0.19
 * @return int|WP_Error
 */
function wpinv_create_user( $email ) {

    // Prepare user values.
	$args = array(
		'user_login' => wpinv_generate_user_name( $email ),
		'user_pass'  => wp_generate_password(),
		'user_email' => $email,
        'role'       => 'subscriber',
    );

    return wp_insert_user( $args );

}

/**
 *  Generates a unique user name from an email.
 *
 * @since 1.0.19
 * @return bool|WP_User
 */
function wpinv_generate_user_name( $prefix = '' ) {

    // If prefix is an email, retrieve the part before the email.
	$prefix = strtok( $prefix, '@' );

	// Trim to 4 characters max.
	$prefix = sanitize_user( $prefix );

	$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );
	if ( empty( $prefix ) || in_array( strtolower( $prefix ), array_map( 'strtolower', $illegal_logins ), true ) ) {
		$prefix = 'gtp';
	}

	$username = $prefix . '_' . zeroise( wp_rand( 0, 9999 ), 4 );
	if ( username_exists( $username ) ) {
		return wpinv_generate_user_name( $username );
	}

    return $username;
}

/**
 * Returns an array of user content tabs.
 *
 * @since 1.0.19
 * @return array
 */
function getpaid_get_user_content_tabs() {

    $tabs = array(

        // Slug - invoices.
        'invoices'      => array(
            'label'     => __( 'Invoices', 'invoicing' ), // Name of the tab.
            'content'   => '[wpinv_history]', // Content of the tab. Or specify "callback" to provide a callback instead.
        ),

        'subscriptions' => array(
            'label'     => __( 'Subscriptions', 'invoicing' ),
            'content'   => '[wpinv_subscriptions]',
        )
    );

    return apply_filters( 'getpaid_user_content_tabs', $tabs );
}

/**
 * Prepares the contents of a tab.
 *
 * @since 1.0.19
 * @param array $tab
 * @return array
 */
function getpaid_prepare_user_content_tab( $tab ) {

    if ( ! empty( $tab['callback'] ) ) {
        return call_user_func( $tab['callback'] );
    }

    if ( ! empty( $tab['content'] ) ) {
        return convert_smilies( capital_P_dangit( wp_filter_content_tags( the_content( shortcode_unautop( wpautop( wptexturize( do_blocks( $tab['content'] ) ) ) ) ) ) ) );
    }

    $notice = aui()->alert(
        array(
            'content'     => __( 'This tab has no content or content callback.', 'invoicing' ),
            'type'        => 'error',
        )
    );

    return "<div class='bsui'>$notice</div>";
}
