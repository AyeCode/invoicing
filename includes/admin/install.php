<?php
register_activation_hook(WPINV_PLUGIN_FILE, 'wpinv_plugin_activation');
register_deactivation_hook(WPINV_PLUGIN_FILE, 'wpinv_plugin_deactivation');

function wpinv_plugin_activation($network_wide = false)
{
    set_transient('_wpinv_activation_redirect', true, 30);
    wpinv_install($network_wide);

    if(!wpinv_get_option('address_autofill_api') && $api_key = get_option('geodir_google_api_key')){
        wpinv_update_option( 'address_autofill_api', $api_key);
    }

}

function wpinv_plugin_deactivation()
{
    wpinv_remove_admin_caps();
}

function wpinv_install($network_wide = false)
{
    global $wpdb;

    if (is_multisite() && $network_wide) {
        foreach ($wpdb->get_col("SELECT blog_id FROM $wpdb->blogs LIMIT 100") as $blog_id) {
            switch_to_blog($blog_id);
            wpinv_run_install();
            restore_current_blog();
        }
    } else {
        wpinv_run_install();
    }
}

function wpinv_run_install()
{
    global $wpdb, $wpinv_options, $wp_version, $wpi_session;

    // Setup the invoice Custom Post Type
    wpinv_register_post_types();

    // Clear the permalinks
    flush_rewrite_rules(false);

    // Add Upgraded From Option
    $current_version = get_option('wpinv_version');
    if ($current_version) {
        update_option('wpinv_version_upgraded_from', $current_version);
    }

    wpinv_create_pages();
    wpinv_add_admin_caps();

    // Pull options from WP, not GD Invoice's global
    $options = get_option('wpinv_settings', array());

    // Populate some default values
    foreach (wpinv_get_registered_settings() as $tab => $sections) {
        foreach ($sections as $section => $settings) {
            // Check for backwards compatibility
            $tab_sections = wpinv_get_settings_tab_sections($tab);
            if (!is_array($tab_sections) || !array_key_exists($section, $tab_sections)) {
                $section = 'main';
                $settings = $sections;
            }

            foreach ($settings as $option) {
                if (!empty($option['id']) && !isset($wpinv_options[$option['id']])) {
                    if ('checkbox' == $option['type'] && !empty($option['std'])) {
                        $options[$option['id']] = '1';
                    } else if (!empty($option['std'])) {
                        $options[$option['id']] = $option['std'];
                    }
                }
            }
        }
    }

    $merged_options = array_merge($wpinv_options, $options);
    $wpinv_options = $merged_options;

    update_option('wpinv_settings', $merged_options);
    update_option('wpinv_version', WPINV_VERSION);

    // Check for PHP Session support, and enable if available
    $wpi_session->use_php_sessions();

    // Add a temporary option to note that GD Invoice pages have been created
    set_transient('_wpinv_installed', $merged_options, 30);

    // Bail if activating from network, or bulk
    if (is_network_admin() || isset($_GET['activate-multi'])) {
        return;
    }

    // Add the transient to redirect
    set_transient('_wpinv_activation_redirect', true, 30);
}

/**
 * When a new Blog is created in multisite, see if Invoicing is network activated, and run the installer.
 *
 */
function wpinv_new_blog_created($blog_id, $user_id, $domain, $path, $site_id, $meta)
{
    if (is_plugin_active_for_network(plugin_basename(WPINV_PLUGIN_FILE))) {
        switch_to_blog($blog_id);
        wpinv_run_install();
        restore_current_blog();
    }
}

add_action('wpmu_new_blog', 'wpinv_new_blog_created', 10, 6);

/**
 * Post-installation.
 *
 * Runs just after plugin installation and exposes the wpinv_after_install hook.
 */
function wpinv_after_install()
{
    if (!is_admin()) {
        return;
    }

    $wpinv_options = get_transient('_wpinv_installed');
    $wpinv_table_check = get_option('_wpinv_table_check', false);

    if (false === $wpinv_table_check || current_time('timestamp') > $wpinv_table_check) {
        update_option('_wpinv_table_check', (current_time('timestamp') + WEEK_IN_SECONDS));
    }

    if (false !== $wpinv_options) {
        // Delete the transient
        delete_transient('_wpinv_installed');
    }
}

add_action('admin_init', 'wpinv_after_install');

function wpinv_create_pages()
{

    $pages = apply_filters('wpinv_create_pages', array(
        'checkout_page' => array(
            'name' => _x('wpi-checkout', 'Page slug', 'invoicing'),
            'title' => _x('Checkout', 'Page title', 'invoicing'),
            'content' => '[' . apply_filters('wpinv_checkout_shortcode_tag', 'wpinv_checkout') . ']',
            'parent' => '',
        ),
        'invoice_history_page' => array(
            'name' => _x('wpi-history', 'Page slug', 'invoicing'),
            'title' => _x('Invoice History', 'Page title', 'invoicing'),
            'content' => '[' . apply_filters('wpinv_history_shortcode_tag', 'wpinv_history') . ']',
            'parent' => 'wpi-checkout',
        ),
        'success_page' => array(
            'name' => _x('wpinv-receipt', 'Page slug', 'invoicing'),
            'title' => _x('Payment Confirmation', 'Page title', 'invoicing'),
            'content' => '[' . apply_filters('wpinv_receipt_shortcode_tag', 'wpinv_receipt') . ']',
            'parent' => 'wpi-checkout',
        ),
        'failure_page' => array(
            'name' => _x('wpinv-transaction-failed', 'Page slug', 'invoicing'),
            'title' => _x('Transaction Failed', 'Page title', 'invoicing'),
            'content' => __('Your transaction failed, please try again or contact site support.', 'invoicing'),
            'parent' => 'wpi-checkout',
        ),
    ));

    foreach ($pages as $key => $page) {
        wpinv_create_page(esc_sql($page['name']), $key, $page['title'], $page['content'], $page['parent']);
    }
}

function wpinv_get_core_capabilities()
{
    $capabilities = array();

    $capabilities['core'] = array(
        'manage_invoicing',
    );

    $capability_types = array('wpi_invoice', 'wpi_quote', 'wpi_item', 'wpi_discount');

    foreach ($capability_types as $capability_type) {

        $capabilities[$capability_type] = array(
            // Post type
            "edit_{$capability_type}",
            "read_{$capability_type}",
            "delete_{$capability_type}",
            "edit_{$capability_type}s",
            "edit_others_{$capability_type}s",
            "publish_{$capability_type}s",
            "read_private_{$capability_type}s",
            "delete_{$capability_type}s",
            "delete_private_{$capability_type}s",
            "delete_published_{$capability_type}s",
            "delete_others_{$capability_type}s",
            "edit_private_{$capability_type}s",
            "edit_published_{$capability_type}s",
        );
    }

    return $capabilities;
}

function wpinv_add_admin_caps()
{
    global $wp_roles;

    $capabilities = wpinv_get_core_capabilities();

    foreach ($capabilities as $cap_group) {
        foreach ($cap_group as $cap) {
            $wp_roles->add_cap('administrator', $cap);
        }
    }
}

function wpinv_remove_admin_caps()
{
    global $wp_roles;

    $capabilities = wpinv_get_core_capabilities();

    foreach ($capabilities as $cap_group) {
        foreach ($cap_group as $cap) {
            $wp_roles->remove_cap('administrator', $cap);
        }
    }
}