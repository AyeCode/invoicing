<?php
/**
 * BuddyPress & GetPaid integration.
 *
 * @package GetPaid
 * @subpackage BuddyPress
 * @since 2.1.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main GetPaid Class.
 *
 * @since 2.1.5
 */
class BP_GetPaid_Component extends BP_Component {

	/**
	 * Start the component setup process.
	 *
	 * @since 2.1.5
	 */
	public function __construct() {
		parent::start(
			'getpaid',
			'GetPaid',
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 30,
			)
		);
	}

    /**
	 * Set up component global variables.
	 *
	 * @since 2.1.5
	 *
	 *
	 * @param array $args {
	 *     All values are optional.
	 *     @type string   $slug                  The component slug. Used to construct certain URLs, such as 'friends' in
	 *                                           http://example.com/members/joe/friends/. Default: the value of $this->id.
	 *     @type string   $root_slug             The component root slug. Note that this value is generally unused if the
	 *                                           component has a root directory (the slug will be overridden by the
	 *                                           post_name of the directory page). Default: the slug of the directory page
	 *                                           if one is found, otherwise an empty string.
	 *     @type bool     $has_directory         Set to true if the component requires an associated WordPress page.
	 *     @type callable $notification_callback Optional. The callable function that formats the component's notifications.
	 *     @type string   $search_term           Optional. The placeholder text in the component directory search box. Eg,
	 *                                           'Search Groups...'.
	 *     @type array    $global_tables         Optional. An array of database table names.
	 *     @type array    $meta_tables           Optional. An array of metadata table names.
	 * }
	 */
	public function setup_globals( $args = array() ) {
        parent::setup_globals(
            array(
                'id'            => 'getpaid',
                'slug'          => 'getpaid',
                'root_slug'     => 'getpaid',
                'has_directory' => false
            )
        );
	}

	/**
	 * Set up component navigation.
	 *
	 * @since 2.1.5
	 *
	 * @see BP_Component::setup_nav() for a description of arguments.
	 *
	 * @param array $main_nav Optional. See BP_Component::setup_nav() for description.
	 * @param array $sub_nav  Optional. See BP_Component::setup_nav() for description.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Abort if the integration is inactive.
        if ( ! getpaid_is_buddypress_integration_active() || ! is_user_logged_in() ) {
            return;
        }
    
        // Or a user is not viewing their profile.
        if ( bp_displayed_user_id() != bp_loggedin_user_id() ) {
            return;
        }

		// Determine user to use.
		$user_domain   = bp_loggedin_user_domain();
		$slug          = 'getpaid';
		$payments_link = trailingslashit( $user_domain . $slug );

		// Add 'Payments' to the main navigation.
		$main_nav = array(
			'name'                => _x( 'Billing', 'BuddyPress profile payments screen nav', 'invoicing' ),
			'slug'                => $slug,
			'position'            => apply_filters( 'wpinv_bp_nav_position', wpinv_get_option( 'wpinv_menu_position', 91 ), $slug ),
			'screen_function'     => array( $this, 'display_current_tab' ),
			'default_subnav_slug' => 'gp-edit-address',
            'show_for_displayed_user' => false,
			'item_css_id'         => $this->id,
			'parent_url'          => $user_domain,
			'parent_slug'         => buddypress()->slug,
		);

		// Add the subnav items to the payments nav item if we are using a theme that supports this.
        foreach ( getpaid_get_user_content_tabs() as $_slug => $tab ) {

            $sub_nav[] = array(
                'name'            => $tab[ 'label'],
                'slug'            => $_slug,
                'parent_url'      => $payments_link,
                'parent_slug'     => $slug,
                'position' => 10,
                'screen_function'        => function() use ( $tab ) {
					$GLOBALS['getpaid_bp_current_tab'] = $tab;
					$this->display_current_tab();
                },
                'show_for_displayed_user' => false,
                'item_css_id'             => "getpaid-bp-$_slug",
            );

        }

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since 2.1.5
	 *
	 * @see BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a
	 *                            description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$payments_link = trailingslashit( bp_loggedin_user_domain() . 'getpaid/' );

            // Add the "Payments" sub menu.
            $wp_admin_nav[] = array(
                'parent' => buddypress()->my_account_menu_id,
                'id'     => 'my-account-getpaid',
                'title'  => _x( 'Billing', 'BuddyPress my account payments sub nav', 'invoicing' ),
                'href'   => $payments_link . 'gp-edit-address'
            );

            foreach ( getpaid_get_user_content_tabs() as $slug => $tab ) {

                $wp_admin_nav[] = array(
                    'parent'   => 'my-account-getpaid',
                    'id'       => 'my-account-getpaid' . $slug,
                    'title'    => $tab[ 'label'],
                    'href'     => trailingslashit( $payments_link . $slug ),
                    'position' => 20
                );

            }

		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Retrieves the current tab.
	 *
	 * @since 2.1.5
	 */
	public function get_current_tab() {
		global $getpaid_bp_current_tab;

		if ( empty( $getpaid_bp_current_tab ) ) {
			return array(
				'label'     => __( 'Invoices', 'invoicing' ),
				'content'   => '[wpinv_history]',
				'icon'      => 'fas fa-file-invoice',
			);
		}

		return $getpaid_bp_current_tab;
	}

	/**
	 * Displays the current tab.
	 *
	 * @since 2.1.5
	 */
	public function display_current_tab() {

		add_action( 'bp_template_content', array( $this, 'handle_display_current_tab' ) );
		$template = apply_filters( 'bp_core_template_plugin', 'members/single/plugins' );

        bp_core_load_template( apply_filters( 'wpinv_bp_core_template_plugin', $template ) );
	}

	/**
	 * Handles the actual display of the current tab.
	 *
	 * @since 2.1.5
	 */
	public function handle_display_current_tab() {
		echo getpaid_prepare_user_content_tab( $this->get_current_tab() );
	}

}
