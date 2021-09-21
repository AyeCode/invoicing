<?php
/**
 * Post Types
 *
 * Registers post types and statuses.
 *
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types Class
 *
 */
class GetPaid_Post_Types {

    /**
	 * Hook in methods.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 1 );
		add_action( 'init', array( __CLASS__, 'register_post_status' ), 4 );
		add_action( 'getpaid_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
		add_action( 'getpaid_after_register_post_types', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
	}

	/**
	 * Register core post types.
	 */
	public static function register_post_types() {

		if ( ! is_blog_installed() || post_type_exists( 'wpi_item' ) ) {
			return;
		}

		$capabilities = wpinv_current_user_can_manage_invoicing();

		// Fires before registering post types.
		do_action( 'getpaid_register_post_types' );

		// Register item post type.
		register_post_type(
			'wpi_item',
			apply_filters(
				'wpinv_register_post_type_invoice_item',
				array(
					'labels'             => array(
						'name'               => _x( 'Items', 'post type general name', 'invoicing' ),
						'singular_name'      => _x( 'Item', 'post type singular name', 'invoicing' ),
						'menu_name'          => _x( 'Items', 'admin menu', 'invoicing' ),
						'name_admin_bar'     => _x( 'Item', 'add new on admin bar', 'invoicing' ),
						'add_new'            => _x( 'Add New', 'Item', 'invoicing' ),
						'add_new_item'       => __( 'Add New Item', 'invoicing' ),
						'new_item'           => __( 'New Item', 'invoicing' ),
						'edit_item'          => __( 'Edit Item', 'invoicing' ),
						'view_item'          => __( 'View Item', 'invoicing' ),
						'all_items'          => __( 'Items', 'invoicing' ),
						'search_items'       => __( 'Search items', 'invoicing' ),
						'parent_item_colon'  => __( 'Parent item:', 'invoicing' ),
						'not_found'          => __( 'No items found.', 'invoicing' ),
						'not_found_in_trash' => __( 'No items found in trash.', 'invoicing' )
					),
					'description'           => __( 'This is where you can add new invoice items.', 'invoicing' ),
					'public'                => false,
					'has_archive'           => false,
					'_builtin'              => false,
					'show_ui'               => true,
					'show_in_menu'          => wpinv_current_user_can_manage_invoicing() ? 'wpinv' : false,
					'show_in_nav_menus'     => false,
					'supports'              => array( 'title', 'excerpt', 'thumbnail' ),
					'rewrite'               => false,
					'query_var'             => false,
					'map_meta_cap'          => true,
					'show_in_admin_bar'     => true,
					'can_export'            => true,
				)
			)
		);

		// Register payment form post type.
		register_post_type(
			'wpi_payment_form',
			apply_filters(
				'wpinv_register_post_type_payment_form',
				array(
					'labels'             => array(
						'name'               => _x( 'Payment Forms', 'post type general name', 'invoicing' ),
						'singular_name'      => _x( 'Payment Form', 'post type singular name', 'invoicing' ),
						'menu_name'          => _x( 'Payment Forms', 'admin menu', 'invoicing' ),
						'name_admin_bar'     => _x( 'Payment Form', 'add new on admin bar', 'invoicing' ),
						'add_new'            => _x( 'Add New', 'Payment Form', 'invoicing' ),
						'add_new_item'       => __( 'Add New Payment Form', 'invoicing' ),
						'new_item'           => __( 'New Payment Form', 'invoicing' ),
						'edit_item'          => __( 'Edit Payment Form', 'invoicing' ),
						'view_item'          => __( 'View Payment Form', 'invoicing' ),
						'all_items'          => __( 'Payment Forms', 'invoicing' ),
						'search_items'       => __( 'Search Payment Forms', 'invoicing' ),
						'parent_item_colon'  => __( 'Parent Payment Forms:', 'invoicing' ),
						'not_found'          => __( 'No payment forms found.', 'invoicing' ),
						'not_found_in_trash' => __( 'No payment forms found in trash.', 'invoicing' )
					),
					'description'        => __( 'Add new payment forms.', 'invoicing' ),
					'public'             => false,
					'show_ui'            => true,
					'show_in_menu'       => wpinv_current_user_can_manage_invoicing() ? 'wpinv' : false,
					'show_in_nav_menus'  => false,
					'query_var'          => false,
					'rewrite'            => true,
					'map_meta_cap'       => true,
					'has_archive'        => false,
					'hierarchical'       => false,
					'menu_position'      => null,
					'supports'           => array( 'title' ),
					'menu_icon'          => 'dashicons-media-form',
				)
			)
		);

		// Register invoice post type.
		register_post_type(
			'wpi_invoice',
			apply_filters(
				'wpinv_register_post_type_invoice',
				array(
					'labels'                 => array(
						'name'                  => __( 'Invoices', 'invoicing' ),
						'singular_name'         => __( 'Invoice', 'invoicing' ),
						'all_items'             => __( 'Invoices', 'invoicing' ),
						'menu_name'             => _x( 'Invoices', 'Admin menu name', 'invoicing' ),
						'add_new'               => __( 'Add New', 'invoicing' ),
						'add_new_item'          => __( 'Add new invoice', 'invoicing' ),
						'edit'                  => __( 'Edit', 'invoicing' ),
						'edit_item'             => __( 'Edit invoice', 'invoicing' ),
						'new_item'              => __( 'New invoice', 'invoicing' ),
						'view_item'             => __( 'View invoice', 'invoicing' ),
						'view_items'            => __( 'View Invoices', 'invoicing' ),
						'search_items'          => __( 'Search invoices', 'invoicing' ),
						'not_found'             => __( 'No invoices found', 'invoicing' ),
						'not_found_in_trash'    => __( 'No invoices found in trash', 'invoicing' ),
						'parent'                => __( 'Parent invoice', 'invoicing' ),
						'featured_image'        => __( 'Invoice image', 'invoicing' ),
						'set_featured_image'    => __( 'Set invoice image', 'invoicing' ),
						'remove_featured_image' => __( 'Remove invoice image', 'invoicing' ),
						'use_featured_image'    => __( 'Use as invoice image', 'invoicing' ),
						'insert_into_item'      => __( 'Insert into invoice', 'invoicing' ),
						'uploaded_to_this_item' => __( 'Uploaded to this invoice', 'invoicing' ),
						'filter_items_list'     => __( 'Filter invoices', 'invoicing' ),
						'items_list_navigation' => __( 'Invoices navigation', 'invoicing' ),
						'items_list'            => __( 'Invoices list', 'invoicing' ),
					),
					'description'           => __( 'This is where invoices are stored.', 'invoicing' ),
					'public'                => true,
					'has_archive'           => false,
					'publicly_queryable'    => true,
        			'exclude_from_search'   => true,
        			'show_ui'               => true,
					'show_in_menu'          => wpinv_current_user_can_manage_invoicing() ? 'wpinv' : false,
					'show_in_nav_menus'     => false,
					'supports'              => array( 'title', 'author', 'excerpt'  ),
					'rewrite'               => array(
						'slug'              => 'invoice',
						'with_front'        => false,
					),
					'query_var'             => false,
					'map_meta_cap'          => true,
					'show_in_admin_bar'     => true,
					'can_export'            => true,
					'hierarchical'          => false,
					'menu_position'         => null,
					'menu_icon'             => 'dashicons-media-spreadsheet',
				)
			)
		);

		// Register discount post type.
		register_post_type(
			'wpi_discount',
			apply_filters(
				'wpinv_register_post_type_discount',
				array(
					'labels'                 => array(
						'name'                  => __( 'Discounts', 'invoicing' ),
						'singular_name'         => __( 'Discount', 'invoicing' ),
						'all_items'             => __( 'Discounts', 'invoicing' ),
						'menu_name'             => _x( 'Discounts', 'Admin menu name', 'invoicing' ),
						'add_new'               => __( 'Add New', 'invoicing' ),
						'add_new_item'          => __( 'Add new discount', 'invoicing' ),
						'edit'                  => __( 'Edit', 'invoicing' ),
						'edit_item'             => __( 'Edit discount', 'invoicing' ),
						'new_item'              => __( 'New discount', 'invoicing' ),
						'view_item'             => __( 'View discount', 'invoicing' ),
						'view_items'            => __( 'View Discounts', 'invoicing' ),
						'search_items'          => __( 'Search discounts', 'invoicing' ),
						'not_found'             => __( 'No discounts found', 'invoicing' ),
						'not_found_in_trash'    => __( 'No discounts found in trash', 'invoicing' ),
						'parent'                => __( 'Parent discount', 'invoicing' ),
						'featured_image'        => __( 'Discount image', 'invoicing' ),
						'set_featured_image'    => __( 'Set discount image', 'invoicing' ),
						'remove_featured_image' => __( 'Remove discount image', 'invoicing' ),
						'use_featured_image'    => __( 'Use as discount image', 'invoicing' ),
						'insert_into_item'      => __( 'Insert into discount', 'invoicing' ),
						'uploaded_to_this_item' => __( 'Uploaded to this discount', 'invoicing' ),
						'filter_items_list'     => __( 'Filter discounts', 'invoicing' ),
						'items_list_navigation' => __( 'Discount navigation', 'invoicing' ),
						'items_list'            => __( 'Discounts list', 'invoicing' ),
					),
					'description'        => __( 'This is where you can add new discounts that users can use in invoices.', 'invoicing' ),
					'public'             => false,
					'can_export'         => true,
					'_builtin'           => false,
					'publicly_queryable' => false,
					'exclude_from_search'=> true,
					'show_ui'            => true,
					'show_in_menu'       => wpinv_current_user_can_manage_invoicing() ? 'wpinv' : false,
					'query_var'          => false,
					'rewrite'            => false,
					'map_meta_cap'       => true,
					'has_archive'        => false,
					'hierarchical'       => false,
					'supports'           => array( 'title', 'excerpt' ),
					'show_in_nav_menus'  => false,
					'show_in_admin_bar'  => true,
					'menu_position'      => null,
				)
			)
		);

		do_action( 'getpaid_after_register_post_types' );
	}

	/**
	 * Register our custom post statuses.
	 */
	public static function register_post_status() {

		$invoice_statuses = apply_filters(
			'getpaid_register_invoice_post_statuses',
			array(

				'wpi-pending' => array(
					'label'                     => _x( 'Pending Payment', 'Invoice status', 'invoicing' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of invoices */
        			'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'invoicing' )
				),

				'wpi-processing' => array(
					'label'                     => _x( 'Processing', 'Invoice status', 'invoicing' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of invoices */
        			'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'invoicing' )
				),

				'wpi-onhold' => array(
					'label'                     => _x( 'On Hold', 'Invoice status', 'invoicing' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of invoices */
        			'label_count'               => _n_noop( 'On Hold <span class="count">(%s)</span>', 'On Hold <span class="count">(%s)</span>', 'invoicing' )
				),

				'wpi-cancelled' => array(
					'label'                     => _x( 'Cancelled', 'Invoice status', 'invoicing' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of invoices */
        			'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'invoicing' )
				),

				'wpi-refunded' => array(
					'label'                     => _x( 'Refunded', 'Invoice status', 'invoicing' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of invoices */
        			'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'invoicing' )
				),

				'wpi-failed' => array(
					'label'                     => _x( 'Failed', 'Invoice status', 'invoicing' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of invoices */
        			'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'invoicing' )
				),

				'wpi-renewal' => array(
					'label'                     => _x( 'Renewal', 'Invoice status', 'invoicing' ),
        			'public'                    => true,
        			'exclude_from_search'       => true,
        			'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of invoices */
        			'label_count'               => _n_noop( 'Renewal <span class="count">(%s)</span>', 'Renewal <span class="count">(%s)</span>', 'invoicing' )
				)
			)
		);

		foreach ( $invoice_statuses as $invoice_statuse => $args ) {
			register_post_status( $invoice_statuse, $args );
		}
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Flush rules to prevent 404.
	 *
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( ! get_option( 'getpaid_flushed_rewrite_rules' ) ) {
			update_option( 'getpaid_flushed_rewrite_rules', '1' );
			self::flush_rewrite_rules();
		}
	}

}
