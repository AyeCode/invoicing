<?php
/**
 * Setup menus in WP admin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Menus Class.
 */
class WPInv_Admin_Menus {
    /**
     * Hook in tabs.
     */
    public function __construct() {
        add_action( 'admin_head', array( $this, 'set_admin_menu_class' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
        add_action( 'admin_menu', array( $this, 'add_customers_menu' ), 18 );
        add_action( 'admin_menu', array( $this, 'add_subscriptions_menu' ), 40 );
        add_action( 'admin_menu', array( $this, 'add_addons_menu' ), 100 );
        add_action( 'admin_menu', array( $this, 'add_settings_menu' ), 60 );
        add_action( 'admin_menu', array( $this, 'remove_admin_submenus' ), 10 );
        add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );
    }

    /**
	 * Highlights sub menus.
	 */
	public function set_admin_menu_class() {
		global $current_screen, $parent_file, $submenu_file;

        if ( ! empty( $current_screen->id ) && in_array( $current_screen->id , array( 'wpi_discount', 'wpi_payment_form', 'wpi_invoice' ) ) ) {
			$parent_file = 'wpinv';
			$submenu_file = 'edit.php?post_type=' . $current_screen->id;
        }

    }

    public function admin_menu() {

        $capability = apply_filters( 'invoicing_capability', wpinv_get_capability() );
        add_menu_page(
            __( 'GetPaid', 'invoicing' ),
            __( 'GetPaid', 'invoicing' ),
            $capability,
            'wpinv',
            null,
            'data:image/svg+xml;base64,' . base64_encode( file_get_contents( WPINV_PLUGIN_DIR . 'assets/images/GetPaid.svg' ) ),
            '54.123460'
        );

    }

    /**
     * Registers the customers menu
     */
    public function add_customers_menu() {
        add_submenu_page(
            'wpinv',
            __( 'Customers', 'invoicing' ),
            __( 'Customers', 'invoicing' ),
            wpinv_get_capability(),
            'wpinv-customers',
            array( $this, 'customers_page' )
        );
    }

    /**
     * Registers the subscriptions menu
     */
    public function add_subscriptions_menu() {
        add_submenu_page(
            'wpinv',
            __( 'Subscriptions', 'invoicing' ),
            __( 'Subscriptions', 'invoicing' ),
            wpinv_get_capability(),
            'wpinv-subscriptions',
            'wpinv_subscriptions_page'
        );
    }

    /**
     * Displays the customers page.
     */
    public function customers_page() {
        require_once( WPINV_PLUGIN_DIR . 'includes/admin/class-wpinv-customers-table.php' );
        ?>
        <div class="wrap wpi-customers-wrap">
            <style>
                .column-primary {
                    width: 30%;
                }
            </style>
            <h1><?php echo esc_html( __( 'Customers', 'invoicing' ) ); ?>&nbsp;<a href="<?php echo wp_nonce_url( add_query_arg( 'getpaid-admin-action', 'download_customers' ), 'getpaid-nonce', 'getpaid-nonce' ); ?>" class="page-title-action"><?php _e( 'Export', 'invoicing' ); ?></a></h1>
            <form method="post">
            <?php
                $table = new WPInv_Customers_Table();
                $table->prepare_items();
                $table->search_box( __( 'Search Customers', 'invoicing' ), 'search-customers' );
                $table->display();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Registers the settings menu.
     */
    public function add_settings_menu() {
        add_submenu_page(
            'wpinv',
            __( 'Invoice Settings', 'invoicing' ),
            __( 'Settings', 'invoicing' ),
            apply_filters( 'invoicing_capability', wpinv_get_capability() ),
            'wpinv-settings',
            array( $this, 'options_page' )
        );
    }

    public function add_addons_menu(){
        if ( !apply_filters( 'wpi_show_addons_page', true ) ) {
            return;
        }

        add_submenu_page(
            "wpinv",
            __('Invoicing extensions', 'invoicing'),
            __('Extensions', 'invoicing'),
            'manage_options',
            'wpi-addons',
            array( $this, 'addons_page' )
        );
    }

    public function addons_page(){
        $addon_obj = new WPInv_Admin_Addons();
        $addon_obj->output();
    }

    function options_page() {

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            return;
        }

        $settings_tabs = wpinv_get_settings_tabs();
        $settings_tabs = empty($settings_tabs) ? array() : $settings_tabs;
        $active_tab    = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $settings_tabs ) ? esc_html( $_GET['tab'] ) : 'general';
        $sections      = wpinv_get_settings_tab_sections( $active_tab );
        $key           = 'main';

        if ( is_array( $sections ) ) {
            $key = key( $sections );
        }

        add_thickbox();

        $registered_sections = wpinv_get_settings_tab_sections( $active_tab );
        $section             = isset( $_GET['section'] ) && ! empty( $registered_sections ) && array_key_exists( $_GET['section'], $registered_sections ) ? $_GET['section'] : $key;
        ?>
        <div class="wrap">
            <h1 class="nav-tab-wrapper">
                <?php
                foreach( wpinv_get_settings_tabs() as $tab_id => $tab_name ) {
                    $tab_url = add_query_arg( array(
                        'settings-updated' => false,
                        'tab' => $tab_id,
                    ) );

                    // Remove the section from the tabs so we always end up at the main section
                    $tab_url = remove_query_arg( 'section', $tab_url );
                    $tab_url = remove_query_arg( 'wpi_sub', $tab_url );

                    $active = $active_tab == $tab_id ? ' nav-tab-active' : '';

                    echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
                    echo esc_html( $tab_name );
                    echo '</a>';
                }
                ?>
            </h1>
            <?php
            $number_of_sections = count( $sections );
            $number = 0;
            if ( $number_of_sections > 1 ) {
                echo '<div><ul class="subsubsub">';
                foreach( $sections as $section_id => $section_name ) {
                    echo '<li>';
                    $number++;
                    $tab_url = add_query_arg( array(
                        'settings-updated' => false,
                        'tab' => $active_tab,
                        'section' => $section_id
                    ) );
                    $tab_url = remove_query_arg( 'wpi_sub', $tab_url );
                    $class = '';
                    if ( $section == $section_id ) {
                        $class = 'current';
                    }
                    echo '<a class="' . $class . '" href="' . esc_url( $tab_url ) . '">' . $section_name . '</a>';

                    if ( $number != $number_of_sections ) {
                        echo ' | ';
                    }
                    echo '</li>';
                }
                echo '</ul></div>';
            }
            ?>
            <div id="tab_container">
                <form method="post" action="options.php">
                    <table class="form-table">
                        <?php
                        settings_fields( 'wpinv_settings' );

                        if ( 'main' === $section ) {
                            do_action( 'wpinv_settings_tab_top', $active_tab );
                        }

                        do_action( 'wpinv_settings_tab_top_' . $active_tab . '_' . $section, $active_tab, $section );
                        do_settings_sections( 'wpinv_settings_' . $active_tab . '_' . $section, $active_tab, $section );
                        do_action( 'wpinv_settings_tab_bottom_' . $active_tab . '_' . $section, $active_tab, $section );
                        do_action( 'getpaid_settings_tab_bottom', $active_tab, $section );

                        // For backwards compatibility
                        if ( 'main' === $section ) {
                            do_action( 'wpinv_settings_tab_bottom', $active_tab );
                        }
                        ?>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div><!-- #tab_container-->
        </div><!-- .wrap -->
        <?php
    }

    public function remove_admin_submenus() {
        remove_submenu_page( 'edit.php?post_type=wpi_invoice', 'post-new.php?post_type=wpi_invoice' );
    }

    /**
     * Register our own endpoints section.
     */
    public function add_nav_menu_meta_boxes() {

        add_meta_box(
            'wpinv_endpoints_nav_link',
            __( 'GetPaid endpoints', 'invoicing' ),
            array( $this, 'nav_menu_links' ),
            'nav-menus',
            'side',
            'low'
        );

    }

    /**
     * Displays GetPaid nav menu links.
     */
    public function nav_menu_links() {
        $endpoints = $this->get_menu_items();
        ?>
        <div id="invoicing-endpoints" class="posttypediv">
            <?php if ( ! empty( $endpoints['pages'] ) ) : ?>
                <div id="tabs-panel-invoicing-endpoints" class="tabs-panel tabs-panel-active">
                    <ul id="invoicing-endpoints-checklist" class="categorychecklist form-no-clear">
                        <?php
                            $walker = new Walker_Nav_Menu_Checklist( array() );
                            echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $endpoints['pages'] ), 0, (object) array( 'walker' => $walker ) );
                        ?>
                    </ul>
                </div>
            <?php endif; ?>

            <p class="button-controls wp-clearfix" data-items-type="invoicing-endpoints">
                <span class="list-controls hide-if-no-js">
                    <input type="checkbox" id="invoicing-endpoints-tab" class="select-all">
                    <label for="invoicing-endpoints-tab"><?php _e( 'Select all', 'invoicing' ); ?></label>
                </span>

                <span class="add-to-menu">
                    <input type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'invoicing' ); ?>" name="add-invoicing-endpoints-item" id="submit-invoicing-endpoints">
                    <span class="spinner"></span>
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Returns the menu entry pages.
     *
     * @return array.
     */
    public function get_menu_items(){
        $items = array();

        $pages = array(
            array(
                'id'    => wpinv_get_option( 'invoice_history_page' ),
                'label' => __( 'My Invoices', 'invoicing' ),
            ),
            array(
                'id'    => wpinv_get_option( 'invoice_subscription_page' ),
                'label' => __( 'My Subscriptions', 'invoicing' ),
            )
        );

        foreach ( apply_filters( 'getpaid_menu_pages', $pages ) as $page ) {

            if ( (int) $page['id'] > 0 ) {

                $item                   = new stdClass();
                $item->object_id        = (int) $page['id'];
                $item->db_id            = 0;
                $item->object           =  'page';
                $item->menu_item_parent = 0;
                $item->type             = 'post_type';
                $item->title            = esc_html( $page['label'] );
                $item->url              = get_permalink( (int) $page['id'] );
                $item->target           = '';
                $item->attr_title       = '';
                $item->classes          = array( 'wpinv-menu-item' );
                $item->xfn              = '';

                $items['pages'][]       = $item;

            }

        }

        return apply_filters( 'wpinv_menu_items', $items );
    }

}

return new WPInv_Admin_Menus();