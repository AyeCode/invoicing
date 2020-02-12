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
        add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
        add_action( 'admin_menu', array( $this, 'add_addons_menu' ), 99 );
        add_action( 'admin_menu', array( $this, 'remove_admin_submenus' ), 10 );
        add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );
    }

    public function admin_menu() {
        global $menu, $submenu;

        if ( ! wpinv_current_user_can_manage_invoicing() ) {
            return;
        }

        $capability = apply_filters( 'invoicing_capability', wpinv_get_capability() );

        if ( wpinv_current_user_can_manage_invoicing() ) {
            $menu[] = array( '', 'read', 'separator-wpinv', '', 'wp-menu-separator wpinv' );

            // Allow users with 'manage_invocing' capability to create new invoices
            $submenu['post-new.php?post_type=wpi_invoice'][]  = array( '', '', 'post-new.php?post_type=wpi_invoice', '' );
            $submenu['post-new.php?post_type=wpi_item'][]     = array( '', '', 'post-new.php?post_type=wpi_item', '' );
            $submenu['post-new.php?post_type=wpi_discount'][] = array( '', '', 'post-new.php?post_type=wpi_discount', '' );

        }

        $wpi_invoice = get_post_type_object( 'wpi_invoice' );

        add_menu_page( __( 'Invoicing', 'invoicing' ), __( 'Invoicing', 'invoicing' ), $capability, 'wpinv', null, $wpi_invoice->menu_icon, '54.123460' );

        add_submenu_page( 'wpinv', __( 'Invoice Settings', 'invoicing' ), __( 'Settings', 'invoicing' ), $capability, 'wpinv-settings', array( $this, 'options_page' ));
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
        $page       = isset( $_GET['page'] )                ? strtolower( $_GET['page'] )               : false;

        if ( $page !== 'wpinv-settings' ) {
            return;
        }

        $settings_tabs = wpinv_get_settings_tabs();
        $settings_tabs = empty($settings_tabs) ? array() : $settings_tabs;
        $active_tab    = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $settings_tabs ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        $sections      = wpinv_get_settings_tab_sections( $active_tab );
        $key           = 'main';

        if ( is_array( $sections ) ) {
            $key = key( $sections );
        }

        $registered_sections = wpinv_get_settings_tab_sections( $active_tab );
        $section             = isset( $_GET['section'] ) && ! empty( $registered_sections ) && array_key_exists( $_GET['section'], $registered_sections ) ? $_GET['section'] : $key;
        ob_start();
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
        $content = ob_get_clean();
        echo $content;
    }

    public function remove_admin_submenus() {
        remove_submenu_page( 'edit.php?post_type=wpi_invoice', 'post-new.php?post_type=wpi_invoice' );
    }

    public function add_nav_menu_meta_boxes(){
        add_meta_box( 'wpinv_endpoints_nav_link', __( 'Invoicing Pages', 'invoicing' ), array( $this, 'nav_menu_links' ), 'nav-menus', 'side', 'low' );
    }

    public function nav_menu_links(){
        $endpoints = $this->get_menu_items();
        ?>
        <div id="invoicing-endpoints" class="posttypediv">
        <?php if(!empty($endpoints['pages'])){ ?>
            <div id="tabs-panel-invoicing-endpoints" class="tabs-panel tabs-panel-active">
                <ul id="invoicing-endpoints-checklist" class="categorychecklist form-no-clear">
                    <?php
                    $walker = new Walker_Nav_Menu_Checklist(array());
                    echo walk_nav_menu_tree(array_map('wp_setup_nav_menu_item', $endpoints['pages']), 0, (object) array('walker' => $walker));
                    ?>
                </ul>
            </div>
        <?php } ?>
        <p class="button-controls">
        <span class="list-controls">
            <a href="<?php echo admin_url( 'nav-menus.php?page-tab=all&selectall=1#invoicing-endpoints' ); ?>" class="select-all"><?php _e( 'Select all', 'invoicing' ); ?></a>
        </span>
            <span class="add-to-menu">
            <input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'invoicing' ); ?>" name="add-post-type-menu-item" id="submit-invoicing-endpoints">
            <span class="spinner"></span>
        </span>
        </p>
        <?php
    }

    public function get_menu_items(){
        $items = array();

        $wpinv_history_page_id = (int)wpinv_get_option( 'invoice_history_page' );
        if($wpinv_history_page_id > 0){
            $item = new stdClass();
            $item->object_id = $wpinv_history_page_id;
            $item->db_id = 0;
            $item->object =  'page';
            $item->menu_item_parent = 0;
            $item->type = 'post_type';
            $item->title = __('Invoice History Page','invoicing');
            $item->url = get_permalink( $wpinv_history_page_id );
            $item->target = '';
            $item->attr_title = '';
            $item->classes = array('wpinv-menu-item');
            $item->xfn = '';

            $items['pages'][] = $item;
        }

        $wpinv_sub_history_page_id = (int)wpinv_get_option( 'invoice_subscription_page' );
        if($wpinv_sub_history_page_id > 0){
            $item = new stdClass();
            $item->object_id = $wpinv_sub_history_page_id;
            $item->db_id = 0;
            $item->object =  'page';
            $item->menu_item_parent = 0;
            $item->type = 'post_type';
            $item->title = __('Invoice Subscriptions Page','invoicing');
            $item->url = get_permalink( $wpinv_sub_history_page_id );
            $item->target = '';
            $item->attr_title = '';
            $item->classes = array('wpinv-menu-item');
            $item->xfn = '';

            $items['pages'][] = $item;
        }

        $wpinv_checkout_page_id = (int)wpinv_get_option( 'checkout_page' );
        if($wpinv_checkout_page_id > 0){
            $item = new stdClass();
            $item->object_id = $wpinv_checkout_page_id;
            $item->db_id = 0;
            $item->object =  'page';
            $item->menu_item_parent = 0;
            $item->type = 'post_type';
            $item->title = __('Checkout Page','invoicing');
            $item->url = get_permalink( $wpinv_checkout_page_id );
            $item->target = '';
            $item->attr_title = '';
            $item->classes = array('wpinv-menu-item');
            $item->xfn = '';

            $items['pages'][] = $item;
        }

        $wpinv_tandc_page_id = (int)wpinv_get_option( 'tandc_page' );
        if($wpinv_tandc_page_id > 0){
            $item = new stdClass();
            $item->object_id = $wpinv_tandc_page_id;
            $item->db_id = 0;
            $item->object =  'page';
            $item->menu_item_parent = 0;
            $item->type = 'post_type';
            $item->title = __('Terms & Conditions','invoicing');
            $item->url = get_permalink( $wpinv_tandc_page_id );
            $item->target = '';
            $item->attr_title = '';
            $item->classes = array('wpinv-menu-item');
            $item->xfn = '';

            $items['pages'][] = $item;
        }

        $wpinv_success_page_id = (int)wpinv_get_option( 'success_page' );
        if($wpinv_success_page_id > 0){
            $item = new stdClass();
            $item->object_id = $wpinv_success_page_id;
            $item->db_id = 0;
            $item->object =  'page';
            $item->menu_item_parent = 0;
            $item->type = 'post_type';
            $item->title = __('Success Page','invoicing');
            $item->url = get_permalink( $wpinv_success_page_id );
            $item->target = '';
            $item->attr_title = '';
            $item->classes = array('wpinv-menu-item');
            $item->xfn = '';

            $items['pages'][] = $item;
        }

        $wpinv_failure_page_id = (int)wpinv_get_option( 'failure_page' );
        if($wpinv_failure_page_id > 0){
            $item = new stdClass();
            $item->object_id = $wpinv_failure_page_id;
            $item->db_id = 0;
            $item->object =  'page';
            $item->menu_item_parent = 0;
            $item->type = 'post_type';
            $item->title = __('Failed Transaction Page','invoicing');
            $item->url = get_permalink( $wpinv_failure_page_id );
            $item->target = '';
            $item->attr_title = '';
            $item->classes = array('wpinv-menu-item');
            $item->xfn = '';

            $items['pages'][] = $item;
        }

        return apply_filters( 'wpinv_menu_items', $items );
    }

}

return new WPInv_Admin_Menus();