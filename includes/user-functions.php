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
function wpinv_create_user( $email, $prefix = '' ) {

    // Prepare user values.
    $prefix = preg_replace( '/\s+/', '', $prefix );
    $prefix = empty( $prefix ) ? $email : $prefix;
	$args   = array(
		'user_login' => wpinv_generate_user_name( $prefix ),
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
    $prefix = trim( $prefix, '.' );

	// Sanitize the username.
	$prefix = sanitize_user( $prefix, true );

	$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );
	if ( empty( $prefix ) || in_array( strtolower( $prefix ), array_map( 'strtolower', $illegal_logins ), true ) ) {
		$prefix = 'gtp_' . zeroise( wp_rand( 0, 9999 ), 4 );
	}

    $username = $prefix;
    $postfix  = 2;

    while ( username_exists( $username ) ) {
        $username = $prefix + $postfix;
        $postfix ++;
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

        'gp-invoices'   => array(
            'label'     => __( 'Invoices', 'invoicing' ), // Name of the tab.
            'content'   => '[wpinv_history]', // Content of the tab. Or specify "callback" to provide a callback instead.
            'icon'      => 'fas fa-file-invoice', // Shown on some profile plugins.
        ),

        'gp-subscriptions' => array(
            'label'        => __( 'Subscriptions', 'invoicing' ),
            'content'      => '[wpinv_subscriptions]',
            'icon'         => 'fas fa-redo',
        ),

        'gp-edit-address'  => array(
            'label'        => __( 'Billing Address', 'invoicing' ),
            'callback'     => 'getpaid_display_address_edit_tab',
            'icon'         => 'fas fa-credit-card',
        ),

    );

    $tabs = apply_filters( 'getpaid_user_content_tabs', $tabs );

    // Make sure address editing is last on the list.
    if ( isset( $tabs['gp-edit-address'] ) ) {
        $address = $tabs['gp-edit-address'];
        unset( $tabs['gp-edit-address'] );
        $tabs['gp-edit-address'] = $address;
    }

    return $tabs;
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
        return convert_smilies( capital_P_dangit( wp_filter_content_tags( do_shortcode( shortcode_unautop( wpautop( wptexturize( do_blocks( $tab['content'] ) ) ) ) ) ) ) );
    }

    $notice = aui()->alert(
        array(
            'content'     => __( 'This tab has no content or content callback.', 'invoicing' ),
            'type'        => 'error',
        )
    );

    return "<div class='bsui'>$notice</div>";
}

/**
 * Generates the current integrations tab URL.
 *
 * @since 1.0.19
 * @param string $tab
 * @param string $default
 * @return array
 */
function getpaid_get_tab_url( $tab, $default ) {
    global $getpaid_tab_url;

    if ( empty( $getpaid_tab_url ) ) {
        return $default;
    }

    return sprintf( $getpaid_tab_url, $tab );

}

/**
 * Generates the address edit tab.
 *
 * @since 2.1.4
 * @return string
 */
function getpaid_display_address_edit_tab() {

    if ( 0 === get_current_user_id() ) {
        return '<div class="bsui">' . aui()->alert(
            array(
                'type'       => 'error',
                'content'    => __( 'Your must be logged in to view this section', 'invoicing' ),
                'dismissible'=> false,
            )
        ) . '</div>';
    }

    ob_start();
    ?>
        <div class="bsui">
            <?php wpinv_print_errors(); ?>
            <form method="post" class="getpaid-address-edit-form">

                <?php

                    foreach ( getpaid_user_address_fields() as $key => $label ) {

                        // Display the country.
                        if ( 'country' == $key ) {

                            echo aui()->select(
                                array(
                                    'options'     => wpinv_get_country_list(),
                                    'name'        => 'getpaid_address[' . esc_attr( $key ) . ']',
                                    'id'          => 'wpinv-' . sanitize_html_class( $key ),
                                    'value'       => sanitize_text_field( getpaid_get_user_address_field( get_current_user_id(), $key ) ),
                                    'placeholder' => $label,
                                    'label'       => wp_kses_post( $label ),
                                    'label_type'  => 'vertical',
                                    'class'       => 'getpaid-address-field',
                                )
                            );

                        }

                        // Display the state.
                        else if ( 'state' == $key ) {

                            echo getpaid_get_states_select_markup (
                                getpaid_get_user_address_field( get_current_user_id(), 'country' ),
                                getpaid_get_user_address_field( get_current_user_id(), 'state' ),
                                $label,
                                $label,
                                '',
                                false,
                                '',
                                'getpaid_address[' . esc_attr( $key ) . ']'
                            );

                        } else {

                            echo aui()->input(
                                array(
                                    'name'        => 'getpaid_address[' . esc_attr( $key ) . ']',
                                    'id'          => 'wpinv-' . sanitize_html_class( $key ),
                                    'placeholder' => $label,
                                    'label'       => wp_kses_post( $label ),
                                    'label_type'  => 'vertical',
                                    'type'        => 'text',
                                    'value'       => sanitize_text_field( getpaid_get_user_address_field( get_current_user_id(), $key ) ),
                                    'class'       => 'getpaid-address-field',
                                )
                            );

                        }

                    }

                    echo aui()->input(
                        array(
                            'name'        => 'getpaid_address[email_cc]',
                            'id'          => 'wpinv-email_cc',
                            'placeholder' => 'email1@example.com, email2@example.com',
                            'label'       => __( 'Other email addresses', 'invoicing' ),
                            'label_type'  => 'vertical',
                            'type'        => 'text',
                            'value'       => sanitize_text_field( get_user_meta( get_current_user_id(), '_wpinv_email_cc', true ) ),
                            'class'       => 'getpaid-address-field',
                            'help_text'   => __( 'Optionally provide other email addresses where we should send payment notifications', 'invoicing' ),
                        )
                    );

                    do_action( 'getpaid_display_address_edit_tab' );

                    echo aui()->input(
                        array(
                            'name'             => 'getpaid_profile_edit_submit_button',
                            'id'               => 'getpaid_profile_edit_submit_button',
                            'value'            => __( 'Save Address', 'invoicing' ),
                            'help_text'        => __( 'New invoices will use this address as the billing address.', 'invoicing' ),
                            'type'             => 'submit',
                            'class'            => 'btn btn-primary btn-block submit-button',
                        )
                    );

                    wp_nonce_field( 'getpaid-nonce', 'getpaid-nonce' );
                    getpaid_hidden_field( 'getpaid-action', 'edit_billing_details' );
                ?>

            </form>

        </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'getpaid_edit_address', 'getpaid_display_address_edit_tab' );

/**
 * Saves the billing address edit tab.
 *
 * @since 2.1.4
 * @param array $data
 */
function getpaid_save_address_edit_tab( $data ) {

    if ( empty( $data['getpaid_address'] ) || ! is_array( $data['getpaid_address'] ) ) {
        return;
    }

    $data    = $data['getpaid_address'];
    $user_id = get_current_user_id();

    foreach ( array_keys( getpaid_user_address_fields() ) as $field ) {

        if ( isset( $data[ $field ] ) ) {
            $value = sanitize_text_field( $data[ $field ] );
            update_user_meta( $user_id, '_wpinv_' . $field, $value );
        }

    }

    if ( isset( $data['email_cc'] ) ) {
        update_user_meta( $user_id, '_wpinv_email_cc', sanitize_text_field( $data['email_cc'] ) );
    }

    wpinv_set_error( 'address_updated', __( 'Your billing address has been updated', 'invoicing' ), 'success');
}
add_action( 'getpaid_authenticated_action_edit_billing_details', 'getpaid_save_address_edit_tab' );


/*
 |--------------------------------------------------------------------------
 | UsersWP
 |--------------------------------------------------------------------------
 |
 | Functions that integrate GetPaid and UsersWP.
*/

/**
 * Add our tabs to UsersWP account tabs.
 *
 * @since 1.0.19
 * @param  array $tabs
 * @return array
 */
function getpaid_filter_userswp_account_tabs( $tabs ) {

    // Abort if the integration is inactive.
    if ( ! getpaid_is_userswp_integration_active() ) {
        return $tabs;
    }

    $new_tabs   = array();

    foreach ( getpaid_get_user_content_tabs() as $slug => $tab ) {

        $new_tabs[ $slug ] = array(
            'title' => $tab[ 'label'],
            'icon'  =>  $tab[ 'icon'],
        );

    }

    return array_merge( $tabs, $new_tabs );
}
add_filter( 'uwp_account_available_tabs', 'getpaid_filter_userswp_account_tabs' );

/**
 * Display our UsersWP account tabs.
 *
 * @since 1.0.19
 * @param  array $tabs
 * @return array
 */
function getpaid_display_userswp_account_tabs( $tab ) {
    global $getpaid_tab_url;

    $our_tabs = getpaid_get_user_content_tabs();

    if ( getpaid_is_userswp_integration_active() && isset( $our_tabs[ $tab ] ) ) {
        $getpaid_tab_url = add_query_arg( 'type', '%s', uwp_get_account_page_url() );
        echo getpaid_prepare_user_content_tab( $our_tabs[ $tab ] );
    }

}
add_action( 'uwp_account_form_display', 'getpaid_display_userswp_account_tabs' );


/**
 * Filters the account page title.
 *
 * @since  1.0.19
 * @param  string $title Current title.
 * @param  string $tab   Current tab.
 * @return string Title.
 */
function getpaid_filter_userswp_account_title( $title, $tab ) {

    $our_tabs   = getpaid_get_user_content_tabs();

    if ( getpaid_is_userswp_integration_active() && isset( $our_tabs[ $tab ] ) ) {
        return $our_tabs[ $tab ]['label'];
    }

    return $title;
}
add_filter( 'uwp_account_page_title', 'getpaid_filter_userswp_account_title', 10, 2 );

/**
 * Registers the UsersWP integration settings.
 *
 * @since  1.0.19
 * @param  array $settings An array of integration settings.
 * @return array
 */
function getpaid_register_userswp_settings( $settings ) {

    if ( defined( 'USERSWP_PLUGIN_FILE' ) ) {

        $settings[] = array(

            'id'       => 'userswp',
            'label'    => __( 'UsersWP', 'invoicing' ),
            'settings' => array(

                'userswp_settings' => array(
                    'id'   => 'userswp_settings',
                    'name' => '<h3>' . __( 'UsersWP', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),

                'enable_userswp' => array(
                    'id'         => 'enable_userswp',
                    'name'       => __( 'Enable Integration', 'invoicing' ),
                    'desc'       => __( 'Display GetPaid items on UsersWP account page.', 'invoicing' ),
                    'type'       => 'checkbox',
                    'std'        => 1,
                )

            )

        );

    }

    return $settings;
}
add_filter( 'getpaid_integration_settings', 'getpaid_register_userswp_settings' );

/**
 * Ovewrites the invoices history page to UsersWP.
 *
 * @since  2.3.1
 * @return bool
 */
function getpaid_userswp_overwrite_invoice_history_page( $url, $post_type ) {

    $our_tabs = getpaid_get_user_content_tabs();
    $tab      = "gp-{$post_type}s";
    if ( getpaid_is_userswp_integration_active() && isset( $our_tabs[ $tab ] ) ) {
        return add_query_arg( 'type', $tab, uwp_get_account_page_url() );
    }

    return $url;

}
add_filter( 'wpinv_get_history_page_uri', 'getpaid_userswp_overwrite_invoice_history_page', 10, 2 );

/**
 * Checks if the integration is enabled.
 *
 * @since  1.0.19
 * @return bool
 */
function getpaid_is_userswp_integration_active() {
    $enabled = wpinv_get_option( 'enable_userswp', 1 );
    return defined( 'USERSWP_PLUGIN_FILE' ) && ! empty( $enabled );
}

/*
 |--------------------------------------------------------------------------
 | BuddyPress
 |--------------------------------------------------------------------------
 |
 | Functions that integrate GetPaid and BuddyPress.
*/

/**
 * Registers the BuddyPress integration settings.
 *
 * @since  2.1.5
 * @param  array $settings An array of integration settings.
 * @return array
 */
function getpaid_register_buddypress_settings( $settings ) {

    if ( class_exists( 'BuddyPress' ) ) {

        $settings[] = array(

            'id'       => 'buddypress',
            'label'    => __( 'BuddyPress', 'invoicing' ),
            'settings' => array(

                'buddypress_settings' => array(
                    'id'   => 'buddypress_settings',
                    'name' => '<h3>' . __( 'BuddyPress', 'invoicing' ) . '</h3>',
                    'type' => 'header',
                ),

                'enable_buddypress' => array(
                    'id'         => 'enable_buddypress',
                    'name'       => __( 'Enable Integration', 'invoicing' ),
                    'desc'       => __( 'Display GetPaid items on BuddyPress account pages.', 'invoicing' ),
                    'type'       => 'checkbox',
                    'std'        => 1,
                )

            )

        );

    }

    return $settings;
}
add_filter( 'getpaid_integration_settings', 'getpaid_register_buddypress_settings' );

/**
 * Checks if the integration is enabled.
 *
 * @since  2.1.5
 * @return bool
 */
function getpaid_is_buddypress_integration_active() {
    $enabled = wpinv_get_option( 'enable_buddypress', 1 );
    return class_exists( 'BuddyPress' ) && ! empty( $enabled );
}

/**
 * Loads the BuddyPress component.
 *
 * @since  2.1.5
 * @return bool
 */
function getpaid_setup_buddypress_integration() {

    if ( getpaid_is_buddypress_integration_active() ) {
        require_once( WPINV_PLUGIN_DIR . 'includes/class-bp-getpaid-component.php' );
        buddypress()->getpaid = new BP_GetPaid_Component();
    }

}
add_action( 'bp_setup_components', 'getpaid_setup_buddypress_integration' );
